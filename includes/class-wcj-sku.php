<?php
/**
 * Booster for WooCommerce - Module - SKU
 *
 * @version 3.2.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_SKU' ) ) :

class WCJ_SKU extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.3
	 */
	function __construct() {

		$this->id         = 'sku';
		$this->short_desc = __( 'SKU', 'woocommerce-jetpack' );
		$this->desc       = __( 'Generate WooCommerce SKUs automatically. Search by SKU on frontend.', 'woocommerce-jetpack' );
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
			// Search by SKU
			if ( 'yes' === get_option( 'wcj_sku_search_enabled', 'no' ) ) {
				add_filter( 'pre_get_posts', array( $this, 'add_search_by_sku_to_frontend' ), PHP_INT_MAX );
			}
			// Disable SKU
			if ( 'yes' === get_option( 'wcj_sku_disabled', 'no' ) ) {
				add_filter( 'wc_product_sku_enabled', '__return_false', PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_sku_to_customer_emails.
	 *
	 * @version 2.5.5
	 * @since   2.5.5
	 */
	function add_sku_to_customer_emails( $args ) {
		$args['show_sku'] = true;
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
	 * @version 2.9.0
	 * @todo    `as_variable_with_suffix` - handle cases with more than 26 variations
	 */
	function set_sku_with_variable( $product_id, $is_preview ) {

		/* if ( 'random' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$sku_number = rand();
		} */
		if ( 'sequential' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$sku_number = $this->get_sequential_counter( $product_id );
		} elseif ( 'hash_crc32' === apply_filters( 'booster_option', 'product_id', get_option( 'wcj_sku_number_generation', 'product_id' ) ) ) {
			$sku_number = sprintf( "%u", crc32( $product_id ) );
		} else { // if 'product_id'
			$sku_number = $product_id;
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
	 * set_sku.
	 *
	 * @version 2.9.0
	 */
	function set_sku( $product_id, $sku_number, $variation_suffix, $is_preview, $parent_product_id, $_product ) {

		$old_sku = $_product->get_sku();
		$do_generate_new_sku = ( 'no' === get_option( 'wcj_sku_generate_only_for_empty_sku', 'no' ) || '' === $old_sku );

		$category_prefix = '';
		$category_suffix = '';
		$product_cat = '';
		$product_terms = get_the_terms( $parent_product_id, 'product_cat' );
		if ( is_array( $product_terms ) ) {
			foreach ( $product_terms as $term ) {
				$product_cat = esc_html( $term->name );
				$category_prefix = get_option( 'wcj_sku_prefix_cat_' . $term->term_id, '' );
				$category_suffix = get_option( 'wcj_sku_suffix_cat_' . $term->term_id, '' );
				break;
			}
		}

		$format_template = get_option( 'wcj_sku_template',
			'{category_prefix}{prefix}{sku_number}{suffix}{category_suffix}{variation_suffix}' );
		$replace_values = array(
			'{category_prefix}'  => apply_filters( 'booster_option', '', $category_prefix ),
//			'{tag_prefix}'       => $tag_prefix,
			'{prefix}'           => get_option( 'wcj_sku_prefix', '' ),
			'{sku_number}'       => sprintf( '%0' . get_option( 'wcj_sku_minimum_number_length', 0 ) . 's', $sku_number ),
			'{suffix}'           => get_option( 'wcj_sku_suffix', '' ),
//			'{tag_suffix}'       => $tag_suffix,
			'{category_suffix}'  => $category_suffix,
			'{variation_suffix}' => $variation_suffix,
		);
		$the_sku = ( $do_generate_new_sku ) ? str_replace( array_keys( $replace_values ), array_values( $replace_values ), $format_template ) : $old_sku;

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
	 * @version 2.9.0
	 */
	function set_all_products_skus( $is_preview ) {
		$this->maybe_get_sequential_counters();
		$limit = 512;
		$offset = 0;
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
	 * @version 2.9.0
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
		$html .= '<input class="button-primary" type="submit" name="preview_sku" id="preview_sku" value="' . __( 'Preview SKUs', 'woocommerce-jetpack' ) . '">';
		$html .= ' ';
		$html .= '<input class="button-primary" type="submit" name="set_sku" id="set_sku" value="' . __( 'Set SKUs', 'woocommerce-jetpack' ) . '">';
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
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function search_post_join( $join = '' ) {
		global $wp_the_query;
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) ) {
			return $join;
		}
		$join .= "INNER JOIN wp_postmeta AS wcj_sku ON (wp_posts.ID = wcj_sku.post_id)";
		return $join;
	}

	/**
	 * search_post_where.
	 *
	 * @version 3.2.3
	 * @since   2.9.0
	 */
	function search_post_where( $where = '' ) {
		global $wp_the_query;
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) ) {
			return $where;
		}
		$where = preg_replace( "/\(\s*wp_posts.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/", "(wp_posts.post_title LIKE $1) OR (wcj_sku.meta_key = '_sku' AND CAST(wcj_sku.meta_value AS CHAR) LIKE $1)", $where );
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
