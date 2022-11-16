<?php
/**
 * Booster for WooCommerce - Module - Related Products
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'classes/class-wcj-dummy-term.php';

if ( ! class_exists( 'WCJ_Related_Products' ) ) :
	/**
	 * WCJ_Related_Products.
	 */
	class WCJ_Related_Products extends WCJ_Module {


		/**
		 * Constructor.
		 *
		 * @version 5.5.9
		 */
		public function __construct() {
			$this->id         = 'related_products';
			$this->short_desc = __( 'Related Products', 'woocommerce-jetpack' );
			$this->desc       = __( 'Change displayed related products number, columns, order; relate by tag, category, product attribute or manually on per product basis (Plus). Hide related products completely.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Change displayed related products number, columns, order; relate by tag, category, product attribute or manually on per product basis. Hide related products completely.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-related-products';
			$this->extra_desc = sprintf(
				/* translators: %s: translation added */
				__( 'You may need to <a href="%s">clear all products transients</a> to immediately see results on frontend after changing module\'s settings. Alternatively you can just update each product individually to clear its transients.', 'woocommerce-jetpack' ),
				esc_url( add_query_arg( 'wcj_clear_all_products_transients', 'yes' ) )
			);
			parent::__construct();

			// Delete Transients.
			add_action( 'admin_init', array( $this, 'maybe_delete_product_transients' ), PHP_INT_MAX, 2 );

			if ( $this->is_enabled() ) {
				// Related per Product.
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}

				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					// Related Args.
					add_filter( 'woocommerce_related_products_args', array( $this, 'related_products_args' ), PHP_INT_MAX ); // filter doesn't exist in WC3.
					add_filter( 'woocommerce_output_related_products_args', array( $this, 'output_related_products_args' ), PHP_INT_MAX );
					// Fix Empty Initial Related Products Issue.
					add_filter( 'woocommerce_get_related_product_tag_terms', array( $this, 'fix_empty_initial_related_products' ), PHP_INT_MAX, 2 );
				} else {
					// Related Query.
					add_filter( 'woocommerce_product_related_posts_query', array( $this, 'related_products_query_wc3' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_product_related_posts_force_display', '__return_true', PHP_INT_MAX );
					// Related Args.
					add_filter( 'woocommerce_output_related_products_args', array( $this, 'output_related_products_args_wc3' ), PHP_INT_MAX );
				}

				// Related Columns.
				add_filter( 'woocommerce_related_products_columns', array( $this, 'related_products_columns' ), PHP_INT_MAX );

				// Relate by Category.
				if ( 'no' === wcj_get_option( 'wcj_product_info_related_products_relate_by_category', 'yes' ) ) {
					add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false', PHP_INT_MAX );
				} else {
					add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_true', PHP_INT_MAX );
				}

				// Relate by Tag.
				if ( 'no' === wcj_get_option( 'wcj_product_info_related_products_relate_by_tag', 'yes' ) ) {
					add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', PHP_INT_MAX );
				} else {
					add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_true', PHP_INT_MAX );
				}

				// Hide Related.
				if ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_hide', 'no' ) ) {
					add_action( ( $this->do_hide_for_all_products() ? 'init' : 'wp_head' ), array( $this, 'remove_output_related_products_action' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Do_hide_for_all_products.
		 *
		 * @version 3.1.1
		 * @since   3.1.1
		 */
		public function do_hide_for_all_products() {
			return ( '' === wcj_get_option( 'wcj_product_info_related_products_hide_products_incl', '' ) &&
				'' === wcj_get_option( 'wcj_product_info_related_products_hide_products_excl', '' ) &&
				'' === wcj_get_option( 'wcj_product_info_related_products_hide_cats_incl', '' ) &&
				'' === wcj_get_option( 'wcj_product_info_related_products_hide_cats_excl', '' ) &&
				'' === wcj_get_option( 'wcj_product_info_related_products_hide_tags_incl', '' ) &&
				'' === wcj_get_option( 'wcj_product_info_related_products_hide_tags_excl', '' ) );
		}

		/**
		 * Do_hide_for_product.
		 *
		 * @version 3.1.1
		 * @since   3.1.1
		 * @param int $product_id defines the product_id.
		 */
		public function do_hide_for_product( $product_id ) {
			return wcj_is_enabled_for_product(
				$product_id,
				array(
					'include_products'   => wcj_get_option( 'wcj_product_info_related_products_hide_products_incl', '' ),
					'exclude_products'   => wcj_get_option( 'wcj_product_info_related_products_hide_products_excl', '' ),
					'include_categories' => wcj_get_option( 'wcj_product_info_related_products_hide_cats_incl', '' ),
					'exclude_categories' => wcj_get_option( 'wcj_product_info_related_products_hide_cats_excl', '' ),
					'include_tags'       => wcj_get_option( 'wcj_product_info_related_products_hide_tags_incl', '' ),
					'exclude_tags'       => wcj_get_option( 'wcj_product_info_related_products_hide_tags_excl', '' ),
				)
			);
		}

		/**
		 * Remove_output_related_products_action.
		 *
		 * @version 3.1.1
		 * @since   2.8.0
		 * @param array $args defines the args.
		 */
		public function remove_output_related_products_action( $args ) {
			if ( ! $this->do_hide_for_all_products() && ! $this->do_hide_for_product( get_the_ID() ) ) {
				return;
			}
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		}

		/**
		 * Related_products_args_wc3.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @todo    somehow add `meta_key` in WC3 (`wc_products_array_orderby()`)
		 * @param array $args defines the args.
		 */
		public function related_products_args_wc3( $args ) {
			// Related Num.
			$args['posts_per_page'] = wcj_get_option( 'wcj_product_info_related_products_num', 3 );
			// Order By.
			$args['orderby'] = wcj_get_option( 'wcj_product_info_related_products_orderby', 'rand' );
			// Order.
			if ( 'rand' !== $args['orderby'] ) {
				$args['order'] = wcj_get_option( 'wcj_product_info_related_products_order', 'desc' );
			}
			return $args;
		}

		/**
		 * Output_related_products_args_wc3.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 * @param array $args defines the args.
		 */
		public function output_related_products_args_wc3( $args ) {
			$args['columns'] = wcj_get_option( 'wcj_product_info_related_products_columns', 3 );
			$args            = $this->related_products_args_wc3( $args );
			return $args;
		}

		/**
		 * Get_related_products_ids_wc3.
		 *
		 * @version 5.6.8
		 * @since   2.8.0
		 * @param int $product_id defines the product_id.
		 */
		public function get_related_products_ids_wc3( $product_id ) {
			$include_ids = array();
			// Change Related Products.
			if (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) &&
				( 'yes' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) || ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_per_product_cmb_default', 'no' ) ) && '' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) )
			) {
				// Relate per Product (Manual).
				$related_per_product = get_post_meta( get_the_ID(), '_wcj_product_info_related_products_ids', true );
				$include_ids         = ( ! empty( $related_per_product ) ? $related_per_product : false );
			} elseif ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
				$args            = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
				);
				$attribute_name  = wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_name', '' );
				$attribute_value = wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_value', '' );
				if ( 'global' === wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_type', 'global' ) ) {
					// Relate by Global Attributes.
					// http://snippet.fm/snippets/query-for-woocommerce-products-by-global-product-attributes/.
					$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'pa_' . $attribute_name,
							'field'    => 'name',
							'terms'    => $attribute_value,
						),
					);
				} else {
					// Relate by Local Product Attributes.
					// http://snippet.fm/snippets/query-woocommerce-products-product-specific-custom-attribute/.
					$serialized_value = serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					// extended version: $serialized_value = serialize( $attribute_name ) . 'a:6:{' . serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value ) . serialize( 'position' );.
					$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => '_product_attributes',
							'value'   => $serialized_value,
							'compare' => 'LIKE',
						),
					);
				}
				$loop        = new WP_Query( $args );
				$include_ids = $loop->posts;
			}
			return $include_ids;
		}

		/**
		 * Related_products_query_wc3.
		 *
		 * @version 5.5.6
		 * @since   2.8.0
		 * @see     WC_Product_Data_Store_CPT::get_related_products_query()
		 * @todo    "Relate by Product Attribute" - directly to `$query['where']` instead of getting ids via `WP_Query`
		 * @todo    rethink hide related (for >= WC3)
		 * @param array $_query defines the _query.
		 * @param int   $product_id defines the product_id.
		 */
		public function related_products_query_wc3( $_query, $product_id ) {
			//
			// Pluggabl.
			if ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_hide', 'no' ) ) {
				$include_ids = false;
			} else {
				$include_ids = $this->get_related_products_ids_wc3( $product_id );
				if ( false !== $include_ids && empty( $include_ids ) ) {
					return $_query;
				}
			}
			if ( false !== $include_ids ) {
				$include_ids = implode( ',', array_map( 'absint', $include_ids ) );
			}
			$cats_array = array();
			$tags_array = array();
			$_product   = wc_get_product( $product_id );

			$exclude_ids = array_merge( array( 0, $product_id ), $_product->get_upsell_ids() );
			if ( false === $include_ids ) {
				$include_ids = array( 0 );
				$limit       = 0;
			} else {
				$limit  = wcj_get_option( 'wcj_product_info_related_products_num', 3 );
				$limit  = $limit > 0 ? $limit : 5;
				$limit += 20;
			}
			global $wpdb;

			// Arrays to string.
			$exclude_ids = implode( ',', array_map( 'absint', $exclude_ids ) );
			$cats_array  = implode( ',', array_map( 'absint', $cats_array ) );
			$tags_array  = implode( ',', array_map( 'absint', $tags_array ) );

			$limit            = absint( $limit );
			$_query           = array();
			$_query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
			$_query['join']   = " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
			$_query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
			$_query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";
			$_query['where']  = ' WHERE 1=1';
			$_query['where'] .= " AND p.post_status = 'publish'";
			$_query['where'] .= " AND p.post_type = 'product'";
			$_query['where'] .= " AND p.ID NOT IN ( {$exclude_ids} )";
			//
			// Pluggabl.
			if ( ! is_array( $include_ids ) && $include_ids ) {
				$_query['where'] .= " AND p.ID IN ( {$include_ids} )";
			}

			$product_visibility_term_ids = wc_get_product_visibility_term_ids();

			if ( $product_visibility_term_ids['exclude-from-catalog'] ) {
				$_query['where'] .= ' AND t.term_id !=' . $product_visibility_term_ids['exclude-from-catalog'];
			}

			if ( 'yes' === wcj_get_option( 'woocommerce_hide_out_of_stock_items' ) && $product_visibility_term_ids['outofstock'] ) {
				$_query['where'] .= ' AND t.term_id !=' . $product_visibility_term_ids['outofstock'];
			}

			if ( $cats_array || $tags_array ) {
				$_query['where'] .= ' AND (';

				if ( $cats_array ) {
					$_query['where'] .= " ( tt.taxonomy = 'product_cat' AND t.term_id IN ( {$cats_array} ) ) ";
					if ( $tags_array ) {
						$_query['where'] .= ' OR ';
					}
				}

				if ( $tags_array ) {
					$_query['where'] .= " ( tt.taxonomy = 'product_tag' AND t.term_id IN ( {$tags_array} ) ) ";
				}

				$_query['where'] .= ')';
			}

			$_query['limits'] = " LIMIT {$limit} ";

			return $_query;
		}

		/**
		 * Fix_empty_initial_related_products.
		 *
		 * @version 4.1.0
		 * @since   2.6.0
		 * @param array $terms defines the terms.
		 * @param int   $product_id defines the product_id.
		 */
		public function fix_empty_initial_related_products( $terms, $product_id ) {
			$do_fix = false;
			if ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
				$do_fix = true;
			} elseif (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) &&
				( 'yes' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) || ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_per_product_cmb_default', 'no' ) ) && '' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) ) &&
				'' !== get_post_meta( $product_id, '_wcj_product_info_related_products_ids', true )
			) {
				$do_fix = true;
			}
			if ( $do_fix ) {
				add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false', PHP_INT_MAX );
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', PHP_INT_MAX );
				if ( empty( $terms ) ) {
					$dummy_term = new WCJ_Dummy_Term();
					$terms[]    = $dummy_term;
				}
			}
			return $terms;
		}

		/**
		 * Related_products_columns.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param array $columns defines the columns.
		 */
		public function related_products_columns( $columns ) {
			return wcj_get_option( 'wcj_product_info_related_products_columns', 3 );
		}

		/**
		 * Maybe_delete_product_transients.
		 *
		 * @since   5.6.8
		 * @version 2.6.0
		 */
		public function maybe_delete_product_transients() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_clear_all_products_transients'] ) ) {
				$offset     = 0;
				$block_size = 256;
				while ( true ) {
					$args = array(
						'post_type'      => 'product',
						'post_status'    => 'any',
						'posts_per_page' => $block_size,
						'offset'         => $offset,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'fields'         => 'ids',
					);
					$loop = new WP_Query( $args );
					if ( ! $loop->have_posts() ) {
						break;
					}
					foreach ( $loop->posts as $post_id ) {
						wc_delete_product_transients( $post_id );
					}
					$offset += $block_size;
				}
				wp_safe_redirect( remove_query_arg( 'wcj_clear_all_products_transients' ) );
				exit;
			}
		}

		/**
		 * Related_products_args.
		 *
		 * @version 5.6.8
		 * @todo    save custom results as product transient (for < WC3)
		 * @param array $args defines the args.
		 */
		public function related_products_args( $args ) {
			// Hide Related.
			if ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_hide', 'no' ) ) {
				return array();
			}
			// Related Num.
			$args['posts_per_page'] = wcj_get_option( 'wcj_product_info_related_products_num', 3 );
			// Order By.
			$orderby         = wcj_get_option( 'wcj_product_info_related_products_orderby', 'rand' );
			$args['orderby'] = $orderby;
			if ( 'meta_value' === $orderby || 'meta_value_num' === $orderby ) {
				$args['meta_key'] = wcj_get_option( 'wcj_product_info_related_products_orderby_meta_value_meta_key', '' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			}
			// Order.
			if ( 'rand' !== $orderby ) {
				$args['order'] = wcj_get_option( 'wcj_product_info_related_products_order', 'desc' );
			}
			// Change Related Products.
			if (
				'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_product_info_related_products_per_product', 'no' ) ) &&
				( 'yes' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) || ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_per_product_cmb_default', 'no' ) ) && '' === get_post_meta( get_the_ID(), '_wcj_product_info_related_products_enabled', true ) )
			) {
				// Relate per Product (Manual).
				$related_per_product = get_post_meta( get_the_ID(), '_wcj_product_info_related_products_ids', true );
				if ( '' !== $related_per_product ) {
					$args['post__in'] = $related_per_product;
				} else {
					return array();
				}
			} elseif ( 'yes' === wcj_get_option( 'wcj_product_info_related_products_by_attribute_enabled', 'no' ) ) {
				unset( $args['post__in'] );
				$attribute_name  = wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_name', '' );
				$attribute_value = wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_value', '' );
				if ( 'global' === wcj_get_option( 'wcj_product_info_related_products_by_attribute_attribute_type', 'global' ) ) {
					// Relate by Global Attributes.
					// http://snippet.fm/snippets/query-for-woocommerce-products-by-global-product-attributes/.
					$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'pa_' . $attribute_name,
							'field'    => 'name',
							'terms'    => $attribute_value,
						),
					);
				} else {
					// Relate by Local Product Attributes.
					// http://snippet.fm/snippets/query-woocommerce-products-product-specific-custom-attribute/.
					$serialized_value = serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					// extended version: $serialized_value = serialize( $attribute_name ) . 'a:6:{' . serialize( 'name' ) . serialize( $attribute_name ) . serialize( 'value' ) . serialize( $attribute_value ) . serialize( 'position' );.
					$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => '_product_attributes',
							'value'   => $serialized_value,
							'compare' => 'LIKE',
						),
					);
				}
			}
			return $args;
		}

		/**
		 * Output_related_products_args.
		 *
		 * @version 2.6.0
		 * @param array $args defines the args.
		 */
		public function output_related_products_args( $args ) {
			$args['columns'] = wcj_get_option( 'wcj_product_info_related_products_columns', 3 );
			$args            = $this->related_products_args( $args );
			return $args;
		}
	}

endif;

return new WCJ_Related_Products();
