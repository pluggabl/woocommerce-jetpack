<?php
/**
 * Booster for WooCommerce - Module - Multicurrency Product Base Price
 *
 * @version 7.1.6
 * @since   2.4.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Multicurrency_Product_Base_Price' ) ) :
	/**
	 * WCJ_Multicurrency_Product_Base_Price.
	 */
	class WCJ_Multicurrency_Product_Base_Price extends WCJ_Module {

		/**
		 * The module do_convert_in_back_end
		 *
		 * @var varchar $do_convert_in_back_end Module do_convert_in_back_end.
		 */
		public $do_convert_in_back_end;

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.4.8
		 */
		public function __construct() {

			$this->id         = 'multicurrency_base_price';
			$this->short_desc = __( 'Multicurrency Product Base Price', 'woocommerce-jetpack' );
			$this->desc       = __( 'Enter prices for products in different currencies (1 currency allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Enter prices for products in different currencies.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-multicurrency-product-base-price';
			parent::__construct();

			if ( $this->is_enabled() ) {

				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_on_product_edit' ), PHP_INT_MAX, 2 );

				$this->do_convert_in_back_end = ( 'yes' === wcj_get_option( 'wcj_multicurrency_base_price_do_convert_in_back_end', 'no' ) );

				if ( $this->do_convert_in_back_end || wcj_is_frontend() ) {
					$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'multicurrency_base_price' );
					wcj_add_change_price_hooks( $this, $this->price_hooks_priority, false );
				}

				// Compatibility with WooCommerce Price Filter Widget.
				$this->handle_price_filter_widget_compatibility();

				// Compatibility with WooCommerce Ordering.
				$this->handle_wc_price_sorting();
			}
		}

		/**
		 * Handle_wc_ordering.
		 *
		 * @version 5.0.0
		 * @since   5.0.0
		 */
		public function handle_wc_price_sorting() {
			add_filter(
				'woocommerce_get_catalog_ordering_args',
				function ( $args, $orderby, $order ) {
					if (
					'no' === wcj_get_option( 'wcj_multicurrency_base_price_comp_wc_price_sorting', 'no' ) ||
					is_admin()
					) {
						return $args;
					}
					add_filter(
						'posts_clauses',
						function ( $args ) use ( $order ) {
							if ( false === strpos( $args['orderby'], 'wc_product_meta_lookup' ) ) {
								return $args;
							}
							$order_sql = 'DESC' === $order ? 'DESC' : 'ASC';
							global $wpdb;
							$args['join']   .= " LEFT JOIN {$wpdb->postmeta} AS pm ON ({$wpdb->posts}.ID = pm.post_id and pm.meta_key='_wcj_multicurrency_base_price')";
							$args['orderby'] = " pm.meta_value + 0 {$order_sql}, wc_product_meta_lookup.product_id {$order_sql} ";
							return $args;
						}
					);
					return $args;
				},
				10,
				3
			);
		}

		/**
		 * Adds Compatibility with WooCommerce Price Filter Widget.
		 *
		 * @version 4.8.0
		 * @since   4.3.1
		 */
		public function handle_price_filter_widget_compatibility() {
			add_action( 'updated_post_meta', array( $this, 'update_base_price_meta_on_price_update' ), 10, 3 );
			add_action( 'updated_post_meta', array( $this, 'update_base_price_meta_on_base_price_currency_update' ), 10, 3 );
			add_action( 'added_post_meta', array( $this, 'update_base_price_meta_on_base_price_currency_update' ), 10, 3 );
			add_action( 'updated_option', array( $this, 'update_products_base_price_on_exchange_rate_change' ), 10, 3 );
			add_action( 'updated_post_meta', array( $this, 'handle_price_filter_compatibility_flag_on_base_price_update' ), 10, 4 );
			add_action( 'updated_post_meta', array( $this, 'handle_price_filter_compatibility_flag_on_base_price_currency_update' ), 10, 4 );
			add_filter( 'woocommerce_price_filter_sql', array( $this, 'change_woocommerce_price_filter_sql' ) );
			add_action( 'woocommerce_product_query', array( $this, 'modify_default_price_filter_hook' ), PHP_INT_MAX );
		}

		/**
		 * Modify_default_price_filter_hook.
		 *
		 * @version 5.6.8
		 * @since 4.8.0
		 *
		 * @param string $query defines the query.
		 *
		 * @return mixed
		 */
		public function modify_default_price_filter_hook( $query ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if (
				'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp', 'no' ) ||
				! isset( $_GET['min_price'] ) ||
				! isset( $_GET['max_price'] )
			) {
				return $query;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			// Remove Price Filter Meta Query.
			$meta_query = $query->get( 'meta_query' );
			$meta_query = empty( $meta_query ) ? array() : $meta_query;
			foreach ( $meta_query as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( isset( $value['price_filter'] ) ) {
						unset( $meta_query[ $key ]['price_filter'] );
					}
				}
			}
			$query->set( 'meta_query', $meta_query );

			// Remove Price Filter Hooks.
			wcj_remove_class_filter( 'posts_clauses', 'WC_Query', 'price_filter_post_clauses' );

			// Remove Price Filter hooks from "Product Filter for WooCommerce" plugin.
			if ( class_exists( 'XforWC_Product_Filters_Frontend' ) ) {
				remove_filter( 'posts_clauses', 'XforWC_Product_Filters_Frontend::price_filter_post_clauses', 10, 2 );
			}

			// Add Price Filter Hook.
			add_filter( 'posts_clauses', array( $this, 'price_filter_post_clauses' ), 10, 2 );
		}

		/**
		 * Price_filter_post_clauses.
		 *
		 * @version 5.6.8
		 * @since 4.8.0
		 *
		 * @see WC_Query::price_filter_post_clauses
		 *
		 * @param array  $args defines the args.
		 * @param string $wp_query defines the wp_query.
		 *
		 * @return mixed
		 */
		public function price_filter_post_clauses( $args, $wp_query ) {
			global $wpdb;
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! $wp_query->is_main_query() || ( ! isset( $_GET['max_price'] ) && ! isset( $_GET['min_price'] ) ) ) {
				return $args;
			}
			$current_min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : 0;
			$current_max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : PHP_INT_MAX;
			// phpcs:enable WordPress.Security.NonceVerification
			if ( wc_tax_enabled() && 'incl' === wcj_get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
				$tax_rates = WC_Tax::get_rates( $tax_class );
				if ( $tax_rates ) {
					$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
					$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
				}
			}
			$args['where'] .= $wpdb->prepare(
				"
						AND {$wpdb->posts}.ID IN (
							SELECT p.ID
							FROM {$wpdb->posts} as p
							LEFT JOIN {$wpdb->postmeta} as pm ON p.ID = pm.post_id AND pm.meta_key = '_wcj_multicurrency_base_price'
							LEFT JOIN {$wpdb->postmeta} as pm2 ON (pm2.post_id=p.ID) AND (pm2.meta_key = '_price')
							WHERE p.post_type = 'product' AND p.post_status = 'publish'
							AND ( (pm.meta_value <= %f AND pm.meta_value >= %f) OR (pm2.meta_value <= %f AND pm2.meta_value >= %f) )
							GROUP BY p.ID
						)
						 ",
				$current_max_price,
				$current_min_price,
				$current_max_price,
				$current_min_price
			);
			return $args;
		}

		/**
		 * Changes WooCommerce Price Filter Widget SQL.
		 *
		 * All in all, it creates the min and max from '_price' meta, and from '_wcj_multicurrency_base_price' if there is the '_wcj_multicurrency_base_price_comp_pf' meta
		 *
		 * @version 4.8.0
		 * @since   4.3.1
		 *
		 * @see WC_Widget_Price_Filter::get_filtered_price()
		 * @param string $sql defines the sql.
		 *
		 * @return string
		 */
		public function change_woocommerce_price_filter_sql( $sql ) {
			if (
				is_admin() ||
				( ! is_shop() && ! is_product_taxonomy() ) ||
				'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp', 'no' )
			) {
				return $sql;
			}

			global $wpdb;
			$args       = wc()->query->get_main_query()->query_vars;
			$tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
			$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			if ( ! is_post_type_archive( 'product' ) && ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
				$tax_query[] = array(
					'taxonomy' => $args['taxonomy'],
					'terms'    => array( $args['term'] ),
					'field'    => 'slug',
				);
			}
			foreach ( $meta_query + $tax_query as $key => $query ) {
				if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
					unset( $meta_query[ $key ] );
				}
			}
			$meta_query     = new WP_Meta_Query( $meta_query );
			$tax_query      = new WP_Tax_Query( $tax_query );
			$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );
			$sql            = "SELECT MIN(FLOOR(IF(pm2.meta_value=1, pm3.meta_value, pm.meta_value))) AS min_price, MAX(CEILING(IF(pm2.meta_value=1, pm3.meta_value, pm.meta_value))) AS max_price FROM {$wpdb->posts}";
			$sql           .= " JOIN {$wpdb->postmeta} as pm ON {$wpdb->posts}.ID = pm.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
			$sql           .= " LEFT JOIN {$wpdb->postmeta} as pm2 ON {$wpdb->posts}.ID = pm2.post_id AND pm2.meta_key = '_wcj_multicurrency_base_price_comp_pf' ";
			$sql           .= " LEFT JOIN {$wpdb->postmeta} as pm3 ON {$wpdb->posts}.ID = pm3.post_id AND pm3.meta_key = '_wcj_multicurrency_base_price' ";
			$sql           .= " 	WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')				
					AND {$wpdb->posts}.post_status = 'publish'
					AND pm.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', array( '_price' ) ) ) ) . "')
					AND pm.meta_value > '' ";
			$sql           .= $tax_query_sql['where'] . $meta_query_sql['where'];
			$search         = WC_Query::get_main_search_query_sql();
			if ( $search ) {
				$sql .= ' AND ' . $search;
			}
			return $sql;
		}

		/**
		 * Updates '_wcj_multicurrency_base_price' when '_wcj_multicurrency_base_price_currency' changes.
		 *
		 * @version 5.0.0
		 * @since   4.3.1
		 *
		 * @param int    $meta_id defines the meta_id.
		 * @param int    $object_id defines the object_id.
		 * @param string $meta_key defines the meta_key.
		 */
		public function update_base_price_meta_on_base_price_currency_update( $meta_id, $object_id, $meta_key ) {
			$product = wc_get_product( $object_id );
			if (
				( 'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp' ) && 'no' === wcj_get_option( 'wcj_multicurrency_base_price_comp_wc_price_sorting', 'no' ) ) ||
				! function_exists( 'wc_get_product' ) ||
				'_wcj_multicurrency_base_price_currency' !== $meta_key ||
				! is_a( $product, 'WC_Product' )
			) {
				return;
			}
			$this->update_wcj_multicurrency_base_price_meta( $product );
		}

		/**
		 * Updates '_wcj_multicurrency_base_price' when '_price' meta is updated.
		 *
		 * @version 5.0.0
		 * @since   4.3.1
		 *
		 * @param int    $meta_id defines the meta_id.
		 * @param int    $object_id defines the object_id.
		 * @param string $meta_key defines the meta_key.
		 */
		public function update_base_price_meta_on_price_update( $meta_id, $object_id, $meta_key ) {
			$product = wc_get_product( $object_id );
			if (
				( 'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp' ) && 'no' === wcj_get_option( 'wcj_multicurrency_base_price_comp_wc_price_sorting', 'no' ) ) ||
				! function_exists( 'wc_get_product' ) ||
				'_price' !== $meta_key ||
				! is_a( $product, 'WC_Product' )
			) {
				return;
			}
			$this->update_wcj_multicurrency_base_price_meta( $product );
		}

		/**
		 * Updates '_wcj_multicurrency_base_price' meta on products by currency when exchange rate changes inside 'Multicurrency Product Base Price' module.
		 *
		 * @version 5.0.0
		 * @since   4.3.1
		 *
		 * @param string $option_name defines the option_name.
		 * @param string $old_value defines the old_value.
		 * @param string $option_value defines the option_value.
		 */
		public function update_products_base_price_on_exchange_rate_change( $option_name, $old_value, $option_value ) {
			if (
				( 'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp' ) && 'no' === wcj_get_option( 'wcj_multicurrency_base_price_comp_wc_price_sorting', 'no' ) ) ||
				false === strpos( $option_name, 'wcj_multicurrency_base_price_exchange_rate_' )
			) {
				return;
			}
			$currency_number = substr( $option_name, strrpos( $option_name, '_' ) + 1 );
			$currency        = wcj_get_option( 'wcj_multicurrency_base_price_currency_' . $currency_number );
			$the_query       = $this->get_products_by_base_price_currency( '=', $currency );
			if ( $the_query->have_posts() ) {
				foreach ( $the_query->posts as $post_id ) {
					$this->update_wcj_multicurrency_base_price_meta( $post_id );
				}
				wp_reset_postdata();
			}
		}

		/**
		 * Flags a product with '_wcj_multicurrency_base_price_comp_pf' if its '_wcj_multicurrency_base_price_currency' is different from base woocommerce currency.
		 *
		 * @version 4.3.1
		 * @since   4.3.1
		 *
		 * @param int    $meta_id defines the meta_id.
		 * @param int    $object_id defines the object_id.
		 * @param string $meta_key defines the meta_key.
		 * @param string $meta_value defines the meta_value.
		 */
		public function handle_price_filter_compatibility_flag_on_base_price_currency_update( $meta_id, $object_id, $meta_key, $meta_value ) {
			$product = wc_get_product( $object_id );
			if (
				'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp', 'no' ) ||
				! function_exists( 'wc_get_product' ) ||
				'_wcj_multicurrency_base_price_currency' !== $meta_key ||
				! is_a( $product, 'WC_Product' )
			) {
				return;
			}
			if ( wcj_get_option( 'woocommerce_currency' ) === $meta_value ) {
				delete_post_meta( $product->get_id(), '_wcj_multicurrency_base_price_comp_pf' );
			} else {
				update_post_meta( $product->get_id(), '_wcj_multicurrency_base_price_comp_pf', true );
			}
		}

		/**
		 * Flags a product with '_wcj_multicurrency_base_price_comp_pf' if its '_price' is != '_wcj_multicurrency_base_price'
		 *
		 * @version 4.3.1
		 * @since   4.3.1
		 *
		 * @param int    $meta_id defines the meta_id.
		 * @param int    $object_id defines the object_id.
		 * @param string $meta_key defines the meta_key.
		 * @param string $meta_value defines the meta_value.
		 */
		public function handle_price_filter_compatibility_flag_on_base_price_update( $meta_id, $object_id, $meta_key, $meta_value ) {
			$product = wc_get_product( $object_id );
			if (
				'no' === wcj_get_option( 'wcj_multicurrency_base_price_advanced_price_filter_comp', 'no' ) ||
				! function_exists( 'wc_get_product' ) ||
				'_wcj_multicurrency_base_price' !== $meta_key ||
				! is_a( $product, 'WC_Product' )
			) {
				return;
			}
			if ( get_post_meta( $product->get_id(), '_price', true ) === $meta_value ) {
				delete_post_meta( $product->get_id(), '_wcj_multicurrency_base_price_comp_pf' );
			} else {
				update_post_meta( $product->get_id(), '_wcj_multicurrency_base_price_comp_pf', true );
			}
		}

		/**
		 * Gets products by base price currency.
		 *
		 * @version 5.6.8
		 * @since   4.3.1
		 *
		 * @param string $compare defines the compare.
		 * @param string $currency defines the currency.
		 *
		 * @return WP_Query
		 */
		public function get_products_by_base_price_currency( $compare = '=', $currency = '' ) {
			if ( empty( $currency ) ) {
				$currency = wcj_get_option( 'woocommerce_currency' );
			}
			$args  = array(
				'post_type'              => 'product',
				'posts_per_page'         => - 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_wcj_multicurrency_base_price_currency',
						'value'   => $currency,
						'compare' => $compare,
					),
				),
			);
			$query = new WP_Query( $args );
			return $query;
		}

		/**
		 * Updates '_wcj_multicurrency_base_price' meta.
		 *
		 * @version 4.4.0
		 * @since   4.3.1
		 *
		 * @param string | array $product defines the product.
		 * @param null           $price defines the price.
		 *
		 * @return bool
		 */
		public function update_wcj_multicurrency_base_price_meta( $product, $price = null ) {
			if ( filter_var( $product, FILTER_VALIDATE_INT ) ) {
				$product = wc_get_product( $product );
			}
			if ( ! is_a( $product, 'WC_Product' ) ) {
				return false;
			}
			if ( ! $price ) {
				$price = $this->change_price( get_post_meta( $product->get_id(), '_price', true ), $product );
			}
			update_post_meta( $product->get_id(), '_wcj_multicurrency_base_price', $price );
			return true;
		}

		/**
		 * Change_price_grouped.
		 *
		 * @version 2.7.0
		 * @since   2.5.0
		 * @param int   $price defines the price.
		 * @param int   $qty defines the qty.
		 * @param array $_product defines the _product.
		 */
		public function change_price_grouped( $price, $qty, $_product ) {
			if ( $_product->is_type( 'grouped' ) ) {
				foreach ( $_product->get_children() as $child_id ) {
					$the_price   = get_post_meta( $child_id, '_price', true );
					$the_product = wc_get_product( $child_id );
					$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
					if ( $the_price === $price ) {
						return $this->change_price( $price, $the_product );
					}
				}
			}
			return $price;
		}

		/**
		 * Change_price.
		 *
		 * @version 2.7.0
		 * @since   2.4.8
		 * @param int   $price defines the price.
		 * @param array $_product defines the _product.
		 */
		public function change_price( $price, $_product ) {
			return wcj_price_by_product_base_currency( $price, wcj_get_product_id_or_variation_parent_id( $_product ) );
		}

		/**
		 * Get_variation_prices_hash.
		 *
		 * @version 3.5.0
		 * @since   2.4.8
		 * @param int    $price_hash defines the price_hash.
		 * @param array  $_product defines the _product.
		 * @param string $display defines the display.
		 */
		public function get_variation_prices_hash( $price_hash, $_product, $display ) {
			$multicurrency_base_price_currency          = get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product, true ), '_wcj_multicurrency_base_price_currency', true );
			$price_hash['wcj_multicurrency_base_price'] = array(
				'currency'           => $multicurrency_base_price_currency,
				'exchange_rate'      => wcj_get_currency_exchange_rate_product_base_currency( $multicurrency_base_price_currency ),
				'rounding'           => wcj_get_option( 'wcj_multicurrency_base_price_round_enabled', 'no' ),
				'rounding_precision' => wcj_get_option( 'wcj_multicurrency_base_price_round_precision', wcj_get_option( 'woocommerce_price_num_decimals' ) ),
				'save_prices'        => wcj_get_option( 'wcj_multicurrency_base_price_save_prices', 'no' ),
			);
			return $price_hash;
		}

		/**
		 * Change_currency_symbol_on_product_edit.
		 *
		 * @version 5.6.8
		 * @since   2.4.8
		 * @param string       $currency_symbol defines the currency_symbol.
		 * @param string | int $currency defines the currency.
		 */
		public function change_currency_symbol_on_product_edit( $currency_symbol, $currency ) {
			if ( is_admin() ) {
				global $pagenow;
				// phpcs:disable WordPress.Security.NonceVerification
				if (
					( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) || // admin product edit page.
					( ! $this->do_convert_in_back_end && 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) // admin products list.
				) {
					$multicurrency_base_price_currency = get_post_meta( get_the_ID(), '_wcj_multicurrency_base_price_currency', true );
					if ( '' !== $multicurrency_base_price_currency ) {
						remove_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_on_product_edit' ), PHP_INT_MAX, 2 );
						$return = get_woocommerce_currency_symbol( $multicurrency_base_price_currency );
						add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_on_product_edit' ), PHP_INT_MAX, 2 );
						return $return;
					}
				}
				// phpcs:enable WordPress.Security.NonceVerification
			}
			return $currency_symbol;
		}

	}

endif;

return new WCJ_Multicurrency_Product_Base_Price();
