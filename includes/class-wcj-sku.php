<?php
/**
 * Booster for WooCommerce - Module - SKU
 *
 * @version 3.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_SKU' ) ) :

class WCJ_SKU extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 */
	function __construct() {

		$this->id         = 'sku';
		$this->short_desc = __( 'SKU', 'woocommerce-jetpack' );
		$this->desc       = __( 'Generate SKUs automatically. Search by SKU on frontend.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-sku';
		parent::__construct();

		$this->add_tools( array(
			'sku' => array(
				'title' => __( 'Autogenerate SKUs', 'woocommerce-jetpack' ),
				'desc'  => __( 'The tool generates and sets product SKUs for existing products.', 'woocommerce-jetpack' ),
			),
		) );

		if ( $this->is_enabled() ) {
			// New product
			if ( 'yes' === get_option( 'wcj_sku_new_products_generate_enabled', 'yes' ) ) {
				add_action( 'wp_insert_post',                array( $this, 'set_sku_for_new_product' ),          PHP_INT_MAX, 3 );
				add_action( 'woocommerce_duplicate_product', array( $this, 'set_new_product_sku_on_duplicate' ), PHP_INT_MAX, 2 );
			}
			// Allow duplicates
			if ( 'yes' === get_option( 'wcj_sku_allow_duplicates_enabled', 'no' ) ) {
				add_filter( 'wc_product_has_unique_sku', '__return_false', PHP_INT_MAX );
			}
			// SKU in emails
			if ( 'yes' === get_option( 'wcj_sku_add_to_customer_emails', 'no' ) ) {
				add_filter( 'woocommerce_email_order_items_args', array( $this, 'add_sku_to_customer_emails' ), PHP_INT_MAX, 1 );
			}
			if ( 'yes' === get_option( 'wcj_sku_remove_from_admin_emails', 'no' ) ) {
				add_filter( 'woocommerce_email_order_items_args', array( $this, 'remove_sku_from_admin_emails' ), PHP_INT_MAX, 1 );
			}
			// Search by SKU
			if ( 'yes' === get_option( 'wcj_sku_search_enabled', 'no' ) ) {
				if ( 'pre_get_posts' === get_option( 'wcj_sku_search_hook', 'pre_get_posts' ) ) {
					add_filter( 'pre_get_posts', array( $this, 'add_search_by_sku_to_frontend' ), PHP_INT_MAX );
				} else { // 'posts_search'
					add_filter( 'posts_search',  array( $this, 'add_search_by_sku_to_frontend_posts_search' ), 9 );
				}
			}
			// Disable SKU
			if ( 'yes' === get_option( 'wcj_sku_disabled', 'no' ) ) {
				add_filter( 'wc_product_sku_enabled', '__return_false', PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_search_by_sku_to_frontend_posts_search.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @see     https://plugins.svn.wordpress.org/search-by-sku-for-woocommerce/
	 */
	function add_search_by_sku_to_frontend_posts_search( $where ) {
		global $pagenow, $wpdb, $wp;
		if (
			( is_admin() && 'edit.php' != $pagenow ) ||
			! is_search() ||
			! isset( $wp->query_vars['s'] ) ||
			( isset( $wp->query_vars['post_type'] ) && 'product' != $wp->query_vars['post_type'] ) ||
			( isset( $wp->query_vars['post_type'] ) && is_array( $wp->query_vars['post_type'] ) && ! in_array( 'product', $wp->query_vars['post_type'] ) )
		) {
			return $where;
		}
		$search_ids = array();
		$terms = explode( ',', $wp->query_vars['s'] );
		foreach ( $terms as $term ) {
			if ( is_admin() && is_numeric( $term ) ) {
				$search_ids[] = $term;
			}
			$variations_query = "SELECT p.post_parent as post_id" .
				" FROM {$wpdb->posts} as p join {$wpdb->postmeta} pm on p.ID = pm.post_id and pm.meta_key='_sku' and pm.meta_value" .
				" LIKE '%%%s%%' where p.post_parent <> 0 group by p.post_parent";
			$regular_products_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_sku' AND meta_value LIKE '%%%s%%';";
			$sku_to_parent_id = $wpdb->get_col( $wpdb->prepare( $variations_query,       wc_clean( $term ) ) );
			$sku_to_id        = $wpdb->get_col( $wpdb->prepare( $regular_products_query, wc_clean( $term ) ) );
			$search_ids = array_merge( $search_ids, $sku_to_id, $sku_to_parent_id );
		}
		$search_ids = array_filter( array_map( 'absint', $search_ids ) );
		if ( sizeof( $search_ids ) > 0 ) {
			$where = str_replace( ')))', ") OR ({$wpdb->posts}.ID IN (" . implode( ',', $search_ids ) . "))))", $where );
		}
		return $where;
	}

	/**
	 * remove_sku_from_admin_emails.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function remove_sku_from_admin_emails( $args ) {
		if ( $args['sent_to_admin'] ) {
			$args['show_sku'] = false;
		}
		return $args;
	}

	/**
	 * add_sku_to_customer_emails.
	 *
	 * @version 3.4.0
	 * @since   2.5.5
	 */
	function add_sku_to_customer_emails( $args ) {
		if ( ! $args['sent_to_admin'] ) {
			$args['show_sku'] = true;
		}
		return $args;
	}

	/**
	 * get_all_variations.
	 *
	 * @version 2.9.0
	 * @since   2.4.8
	 */
	function get_all_variations( $_product ) {
		$all_variations = array();
		foreach ( $_product->get_children() as $child_id ) {
			if ( $variation = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_product->get_child( $child_id ) : wc_get_product( $child_id ) ) ) {
				$all_variations[] = $_product->get_available_variation( $variation );
			}
		}
		return $all_variations;
	}

	/**
	 * get_sequential_counter.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_sequential_counter( $product_id ) {
		if ( 'yes' === get_option( 'wcj_sku_number_generation_sequential_by_cat', 'no' ) ) {
			$product_terms = get_the_terms( $product_id, 'product_cat' );
			if ( is_array( $product_terms ) ) {
				foreach ( $product_terms as $term ) {
					$sku_number = $this->sequential_counter_cats[ $term->term_id ];
					$this->sequential_counter_cats[ $term->term_id ]++;
					return $sku_number;
				}
			}
		}
		// Cats disabled or no category found
		$sku_number = $this->sequential_counter;
		$this->sequential_counter++;
		return $sku_number;
	}

	/**
	 * set_sku_with_variable.
	 *
	 * @version 3.6.0
	 * @todo    `as_variable_with_suffix` - handle cases with more than 26 variations
	 */
	function set_sku_with_variable( $product_id, $is_preview ) {

		switch ( apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			case 'sequential':
				$sku_number = $this->get_sequential_counter( $product_id );
				break;
			case 'hash_crc32':
				$sku_number = sprintf( "%u", crc32( $product_id ) );
				break;
			default: // 'product_id'
				$sku_number = $product_id;
				break;
		}

		$product = wc_get_product( $product_id );

		$this->set_sku( $product_id, $sku_number, '', $is_preview, $product_id, $product );

		// Handling variable products
		$variation_handling = apply_filters( 'booster_option', 'as_variable', get_option( 'wcj_sku_variations_handling', 'as_variable' ) );
		if ( $product->is_type( 'variable' ) ) {
			$variations = $this->get_all_variations( $product );
			if ( 'as_variable' === $variation_handling ) {
				foreach ( $variations as $variation ) {
					$this->set_sku( $variation['variation_id'], $sku_number, '', $is_preview, $product_id, wc_get_product( $variation['variation_id'] ) );
				}
			} elseif ( 'as_variation' === $variation_handling ) {
				foreach ( $variations as $variation ) {
					if ( 'sequential' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
						$sku_number = $this->get_sequential_counter( $product_id );
					} elseif ( 'hash_crc32' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
						$sku_number = sprintf( "%u", crc32( $variation['variation_id'] ) );
					} else { // if 'product_id'
						$sku_number = $variation['variation_id'];
					}
					$this->set_sku( $variation['variation_id'], $sku_number, '', $is_preview, $product_id, wc_get_product( $variation['variation_id'] ) );
				}
			}
			else if ( 'as_variable_with_suffix' === $variation_handling ) {
				$variation_suffixes = 'abcdefghijklmnopqrstuvwxyz';
				$abc = 0;
				foreach ( $variations as $variation ) {
					$this->set_sku( $variation['variation_id'], $sku_number, $variation_suffixes[ $abc++ ], $is_preview, $product_id, wc_get_product( $variation['variation_id'] ) );
					if ( 26 == $abc ) {
						$abc = 0;
					}
				}
			}
		}
	}

	/**
	 * get_acronym.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    move to `wcj_get_acronym()`
	 */
	function get_acronym( $str, $sep ) {
		$acronym = '';
		$words   = explode( $sep, $str );
		foreach ( $words as $word ) {
			if ( ! empty( $word ) ) {
				$acronym .= $word[0];
			}
		}
		return $acronym;
	}

	/**
	 * maybe_process_attributes.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function maybe_process_attributes( $_product, $format_template, $str, $is_variation = false ) {
		while ( false !== ( $pos = strpos( $format_template, '{' . $str . '=' ) ) ) {
			if ( false !== ( $pos_end = strpos( $format_template, '}', $pos ) ) ) {
				if ( ! isset( $atts ) ) {
					if ( $is_variation ) {
						$atts = ( 'WC_Product_Variation' === get_class( $_product ) ? $_product->get_variation_attributes() : array() );
					} else {
						$atts = $_product->get_attributes();
					}
				}
				$att_str = substr( $format_template, $pos, ( $pos_end - $pos + 1 ) );
				$att     = explode( '=', str_replace( array( '{', '}' ), '', $att_str ) );
				$att_res = array();
				if ( isset( $att[1] ) ) {
					$att_name = $att[1];
					$limit    = 0;
					if ( false !== ( $pos_slash = strpos( $att_name, '/' ) ) ) {
						$att_name = explode( '/', $att_name );
						$limit    = $att_name[1];
						$att_name = $att_name[0];
					}
					if ( $is_variation ) {
						$att_name = 'attribute_' . $att_name;
					}
					if ( isset( $atts[ $att_name ] ) ) {
						if ( $is_variation ) {
							$slug = $atts[ $att_name ];
							if ( 0 != $limit ) {
								$slug = substr( $slug, 0, $limit );
							}
							$att_res[] = $slug; // array is not necessary here (always one slug)
						} else {
							$att_object = $atts[ $att_name ];
							if ( is_object( $att_object ) && 'WC_Product_Attribute' === get_class( $att_object ) ) {
								foreach ( $att_object->get_slugs() as $slug ) {
									if ( 0 != $limit ) {
										$slug = substr( $slug, 0, $limit );
									}
									$att_res[] = $slug;
								}
							}
						}
					}
				}
				$format_template = str_replace( $att_str, implode( get_option( 'wcj_sku_variations_product_slug_sep', '-' ), $att_res ), $format_template );
			}
		}
		return $format_template;
	}

	/**
	 * set_sku.
	 *
	 * @version 3.7.0
	 * @todo    deprecate `{prefix}` and `{suffix}`
	 * @todo    `{tag_prefix}`, `{tag_suffix}`
	 * @todo    (maybe) remove some "replaced values" that can be replaced by Booster products shortcodes, e.g.: `[wcj_product_slug]` (and update description in settings)
	 * @todo    (maybe) add option to disable shortcodes processing
	 */
	function set_sku( $product_id, $sku_number, $variation_suffix, $is_preview, $parent_product_id, $_product ) {

		$format_template = get_option( 'wcj_sku_template',
			'{category_prefix}{prefix}{sku_number}{suffix}{category_suffix}{variation_suffix}' );

		global $post;
		$post = get_post( $product_id );
		setup_postdata( $post );
		$format_template = do_shortcode( $format_template );
		wp_reset_postdata();

		$parent_product = wc_get_product( $parent_product_id );

		$old_sku = $_product->get_sku();
		$do_generate_new_sku = ( 'no' === get_option( 'wcj_sku_generate_only_for_empty_sku', 'no' ) || '' === $old_sku );

		// {category_prefix} & {category_suffix}
		$category_prefix = array();
		$category_suffix = array();
		$product_cat     = array();
		$product_terms = get_the_terms( $parent_product_id, 'product_cat' );
		if ( is_array( $product_terms ) ) {
			foreach ( $product_terms as $term ) {
				$product_cat[]     = esc_html( $term->name );
				$category_prefix[] = get_option( 'wcj_sku_prefix_cat_' . $term->term_id, '' );
				$category_suffix[] = get_option( 'wcj_sku_suffix_cat_' . $term->term_id, '' );
			}
		}
		$category_prefix = array_filter( $category_prefix );
		$category_suffix = array_filter( $category_suffix );
		if ( 'first' === get_option( 'wcj_sku_categories_multiple', 'first' ) ) {
			$category_prefix = reset( $category_prefix );
			$category_suffix = reset( $category_suffix );
		} else {
			$category_prefix = implode( get_option( 'wcj_sku_categories_multiple_glue', '' ), $category_prefix );
			$category_suffix = implode( get_option( 'wcj_sku_categories_multiple_glue', '' ), $category_suffix );
		}
		$product_cat = implode( ', ', $product_cat );

		// {variation_attributes}
		$variation_attributes = '';
		if ( false !== strpos( $format_template, '{variation_attributes}' ) && 'WC_Product_Variation' === get_class( $_product ) ) {
			$variation_attributes = implode( get_option( 'wcj_sku_variations_product_slug_sep', '-' ), $_product->get_variation_attributes() );
		}

		// {variation_attribute=X}
		$format_template = $this->maybe_process_attributes( $_product, $format_template, 'variation_attribute', true );

		// {attribute=X}
		$format_template = $this->maybe_process_attributes( $_product, $format_template, 'attribute' );

		// {parent_attribute=X}
		$format_template = $this->maybe_process_attributes( $parent_product, $format_template, 'parent_attribute' );

		// {product_slug_acronym}
		$product_slug_acronym = '';
		if ( false !== strpos( $format_template, '{product_slug_acronym}' ) ) {
			$product_slug_acronym = $this->get_acronym( $_product->get_slug(), '-' );
		}

		// {parent_product_slug_acronym}
		$parent_product_slug_acronym = '';
		if ( false !== strpos( $format_template, '{parent_product_slug_acronym}' ) ) {
			$parent_product_slug_acronym = $this->get_acronym( $parent_product->get_slug(), '-' );
		}

		$replace_values = array(
			'{parent_sku}'                   => $parent_product->get_sku(),
			'{product_slug}'                 => $_product->get_slug(),
			'{product_slug_acronym}'         => $product_slug_acronym,
			'{parent_product_slug}'          => $parent_product->get_slug(),
			'{parent_product_slug_acronym}'  => $parent_product_slug_acronym,
			'{variation_attributes}'         => $variation_attributes,
			'{category_prefix}'              => apply_filters( 'booster_option', '', $category_prefix ),
			'{prefix}'                       => get_option( 'wcj_sku_prefix', '' ),
			'{sku_number}'                   => sprintf( '%0' . get_option( 'wcj_sku_minimum_number_length', 0 ) . 's', $sku_number ),
			'{suffix}'                       => get_option( 'wcj_sku_suffix', '' ),
			'{category_suffix}'              => $category_suffix,
			'{variation_suffix}'             => $variation_suffix,
		);

		if ( $do_generate_new_sku ) {
			$the_sku = str_replace( array_keys( $replace_values ), $replace_values, $format_template );
			if ( 'original' != ( $characters_case = get_option( 'wcj_sku_characters_case', 'original' ) ) ) {
				$the_sku = ( 'lower' == $characters_case ? strtolower( $the_sku ) : strtoupper( $the_sku ) );
			}
		} else {
			$the_sku = $old_sku;
		}

		if ( $is_preview ) {
			$this->preview_buffer .= '<tr>' .
				'<td>' . $this->product_counter++ . '</td>' .
				'<td>' . $product_id              . '</td>' .
				'<td>' . $_product->get_title()   . '</td>' .
				'<td>' . $product_cat             . '</td>' .
				'<td>' . $the_sku                 . '</td>' .
				'<td>' . $old_sku                 . '</td>' .
			'</tr>';
		} elseif ( $do_generate_new_sku ) {
			update_post_meta( $product_id, '_' . 'sku', $the_sku );
		}
	}

	/**
	 * maybe_get_sequential_counters.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function maybe_get_sequential_counters() {
		if ( 'sequential' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$this->sequential_counter = apply_filters( 'booster_option', 1, get_option( 'wcj_sku_number_generation_sequential', 1 ) );
			if ( 'yes' === get_option( 'wcj_sku_number_generation_sequential_by_cat', 'no' ) ) {
				$this->product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
				if ( ! empty( $this->product_categories ) && ! is_wp_error( $this->product_categories ) ) {
					foreach ( $this->product_categories as $product_category ) {
						$this->sequential_counter_cats[ $product_category->term_id ] = get_option( 'wcj_sku_counter_cat_' . $product_category->term_id, 1 );
					}
				}
			}
		}
	}

	/**
	 * maybe_save_sequential_counters.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function maybe_save_sequential_counters( $is_preview = false ) {
		if ( 'sequential' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) && ! $is_preview ) {
			update_option( 'wcj_sku_number_generation_sequential', $this->sequential_counter );
			if ( 'yes' === get_option( 'wcj_sku_number_generation_sequential_by_cat', 'no' ) ) {
				if ( ! empty( $this->product_categories ) && ! is_wp_error( $this->product_categories ) ) {
					foreach ( $this->product_categories as $product_category ) {
						update_option( 'wcj_sku_counter_cat_' . $product_category->term_id, $this->sequential_counter_cats[ $product_category->term_id ] );
					}
				}
			}
		}
	}

	/**
	 * set_all_products_skus.
	 *
	 * @version 3.5.0
	 */
	function set_all_products_skus( $is_preview ) {
		$this->maybe_get_sequential_counters();
		$limit  = 512;
		$offset = 0;
		$start_id = ( isset( $_POST['wcj_sku_start_id'] ) && 0 != $_POST['wcj_sku_start_id'] ? $_POST['wcj_sku_start_id'] : 0 );
		$end_id   = ( isset( $_POST['wcj_sku_end_id'] )   && 0 != $_POST['wcj_sku_end_id']   ? $_POST['wcj_sku_end_id']   : PHP_INT_MAX );
		while ( true ) {
			$posts = new WP_Query( array(
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'post_type'      => 'product',
				'post_status'    => 'any',
				'order'          => 'ASC',
				'orderby'        => 'date',
				'fields'         => 'ids',
			) );
			if ( ! $posts->have_posts() ) {
				break;
			}
			foreach ( $posts->posts as $post_id ) {
				if ( $post_id < $start_id || $post_id > $end_id ) {
					continue;
				}
				$this->set_sku_with_variable( $post_id, $is_preview );
			}
			$offset += $limit;
		}
		$this->maybe_save_sequential_counters( $is_preview );
	}

	/**
	 * set_new_product_sku_on_duplicate.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function set_new_product_sku_on_duplicate( $post_ID, $post ) {
		$this->maybe_get_sequential_counters();
		$this->set_sku_with_variable( $post_ID, false );
		$this->maybe_save_sequential_counters();
	}

	/**
	 * set_sku_for_new_product.
	 *
	 * @version 3.1.3
	 * @todo    (maybe) set `wcj_sku_new_products_generate_only_on_publish` to `yes` by default
	 */
	function set_sku_for_new_product( $post_ID, $post, $update ) {
		if ( 'product' != $post->post_type ) {
			return;
		}
		$do_generate_only_on_first_publish = ( 'yes' === get_option( 'wcj_sku_new_products_generate_only_on_publish', 'no' ) );
		if (
			( false === $update && ! $do_generate_only_on_first_publish ) ||
			( $do_generate_only_on_first_publish && 'publish' === $post->post_status && '' == get_post_meta( $post_ID, '_sku', true ) )
		) {
			$this->maybe_get_sequential_counters();
			$this->set_sku_with_variable( $post_ID, false );
			$this->maybe_save_sequential_counters();
		}
	}

	/**
	 * create_sku_tool
	 *
	 * @version 3.5.0
	 */
	function create_sku_tool() {
		$result_message = '';
		$is_preview = ( isset( $_POST['preview_sku'] ) );
		if ( isset( $_POST['set_sku'] ) || isset( $_POST['preview_sku'] ) ) {
			$this->product_counter = 1;
			$preview_html = '<table class="widefat" style="width:50%; min-width: 300px; margin-top: 10px;">';
			$preview_html .=
				'<tr>' .
					'<th></th>' .
					'<th>' . __( 'ID', 'woocommerce-jetpack' )         . '</th>' .
					'<th>' . __( 'Product', 'woocommerce-jetpack' )    . '</th>' .
					'<th>' . __( 'Categories', 'woocommerce-jetpack' ) . '</th>' .
					'<th>' . __( 'SKU', 'woocommerce-jetpack' )        . '</th>' .
					'<th>' . __( 'Old SKU', 'woocommerce-jetpack' )    . '</th>' .
				'</tr>';
			$this->preview_buffer = '';
			$this->set_all_products_skus( $is_preview );
			$preview_html .= $this->preview_buffer;
			$preview_html .= '</table>';
			$result_message = '<p><div class="updated"><p><strong>' . __( 'SKUs generated and set successfully!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
		}
		$html = '';
		$html .= '<div class="wrap">';
		$html .= $this->get_tool_header_html( 'sku' );
		if ( ! $is_preview ) {
			$html .= $result_message;
		}
		$html .= '<form method="post" action="">';
		$html .= '<p>';
		$html .= '<input class="button-primary" type="submit" name="preview_sku" id="preview_sku" value="' . __( 'Preview SKUs', 'woocommerce-jetpack' ) . '">';
		$html .= ' ';
		$html .= '<input class="button-primary" type="submit" name="set_sku" id="set_sku" value="' . __( 'Set SKUs', 'woocommerce-jetpack' ) . '">';
		$html .= '</p>';
		$html .= '<p>';
		$html .= '<em>' . __( 'You can optionally limit affected products by main product\'s ID (set option to zero to ignore):', 'woocommerce-jetpack' ) . '</em>';
		$html .= '<br>';
		$html .= '<label for="wcj_sku_start_id">' . __( 'Min ID', 'woocommerce-jetpack' ) . ': ' . '</label>';
		$html .= '<input type="number" name="wcj_sku_start_id" id="wcj_sku_start_id" min="0" value="' . ( isset( $_POST['wcj_sku_start_id'] ) ? $_POST['wcj_sku_start_id'] : 0 ) . '">';
		$html .= ' ';
		$html .= '<label for="wcj_sku_end_id">' . __( 'Max ID', 'woocommerce-jetpack' ) . ': ' . '</label>';
		$html .= '<input type="number" name="wcj_sku_end_id" id="wcj_sku_end_id" min="0" value="' . ( isset( $_POST['wcj_sku_end_id'] ) ? $_POST['wcj_sku_end_id'] : 0 ) . '">';
		$html .= '</p>';
		$html .= '</form>';
		if ( $is_preview ) {
			$html .= $preview_html;
		}
		$html .= '</div>';
		echo $html;
	}

	/**
	 * search_post_join.
	 *
	 * @version 3.4.1
	 * @since   2.9.0
	 */
	function search_post_join( $join = '' ) {
		global $wpdb, $wp_the_query;
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) ) {
			return $join;
		}
		$join .= "INNER JOIN {$wpdb->prefix}postmeta AS wcj_sku ON ({$wpdb->prefix}posts.ID = wcj_sku.post_id)";
		return $join;
	}

	/**
	 * search_post_where.
	 *
	 * @version 3.4.1
	 * @since   2.9.0
	 */
	function search_post_where( $where = '' ) {
		global $wpdb, $wp_the_query;
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) ) {
			return $where;
		}
		$where = preg_replace( "/\(\s*{$wpdb->prefix}posts.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/", "({$wpdb->prefix}posts.post_title LIKE $1) OR (wcj_sku.meta_key = '_sku' AND CAST(wcj_sku.meta_value AS CHAR) LIKE $1)", $where );
		return $where;
	}

	/*
	 * add_search_by_sku_to_frontend.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_search_by_sku_to_frontend( $query ) {
		if (
			isset( $query->query ) &&
			! $query->is_admin &&
			$query->is_search &&
			! empty( $query->query_vars['wc_query'] ) &&
			! empty( $query->query_vars['s'] ) &&
			'product' === $query->query_vars['post_type']
		) {
			add_filter( 'posts_join',  array( $this, 'search_post_join' ) );
			add_filter( 'posts_where', array( $this, 'search_post_where' ) );
		}
	}

}

endif;

return new WCJ_SKU();
