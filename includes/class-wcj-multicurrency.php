<?php
/**
 * Booster for WooCommerce - Module - Multicurrency (Currency Switcher)
 *
 * @version 6.0.1
 * @since   2.4.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Multicurrency' ) ) :
	/**
	 * WCJ_Multicurrency.
	 */
	class WCJ_Multicurrency extends WCJ_Module {

		/**
		 * Bkg_process_price_updater
		 *
		 * @var bkg_process_price_updater
		 */
		protected $bkg_process_price_updater;

		/**
		 * Constructor.
		 *
		 * @version 5.6.2
		 * @todo    check if we can just always execute `init()` on `init` hook
		 */
		public function __construct() {

			$this->id         = 'multicurrency';
			$this->short_desc = __( 'Multicurrency (Currency Switcher)', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add multiple currencies (currency switcher) to WooCommerce (2 currencies allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add multiple currencies (currency switcher) to WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-multicurrency-currency-switcher';
			$this->extra_desc = sprintf(
				/* translators: %s: translation added */
				__( 'After setting currencies in the Currencies Options section below, you can add switcher to the frontend with: %s', 'woocommerce-jetpack' ),
				'<ol>' .
				'<li>' . sprintf(
					/* translators: %s: translation added */
					__( '<strong>Widget:</strong> "%s"', 'woocommerce-jetpack' ),
					__( 'Booster - Multicurrency Switcher', 'woocommerce-jetpack' )
				) .
				'</li>' .
				'<li>' . sprintf(
					/* translators: %s: translation added */
					__( '<strong>Shortcodes:</strong> %s', 'woocommerce-jetpack' ),
					'<code>[wcj_currency_select_drop_down_list]</code>, <code>[wcj_currency_select_radio_list]</code>, <code>[wcj_currency_select_link_list]</code>'
				) .
				'</li>' .
				'<li>' . sprintf(
					/* translators: %s: translation added */
					__( '<strong>PHP code:</strong> by using %1$s function, e.g.: %2$s', 'woocommerce-jetpack' ),
					'<code>do_shortcode()</code>',
					'<code>echo&nbsp;do_shortcode(&nbsp;\'[wcj_currency_select_drop_down_list]\'&nbsp;);</code>'
				) .
				'</li>' .
				'</ol>'
			);
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'multicurrency' );

				// Session.
				if ( 'wc' === WCJ_SESSION_TYPE ) {
					// `init()` executed on `init` hook because we need to use `WC()->session`.
					add_action( 'init', array( $this, 'init' ) );
				} else {
					$this->init();
				}

				$this->add_hooks();

				if ( 'yes' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}

				if ( is_admin() ) {
					include_once 'reports/class-wcj-currency-reports.php';
				}

				if ( 'init' === current_filter() ) {
					$this->init_bkg_process_class();
				} else {
					add_action( 'plugins_loaded', array( $this, 'init_bkg_process_class' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Init_bkg_process_class.
		 *
		 * @version 5.6.1
		 * @since   4.5.0
		 */
		public function init_bkg_process_class() {
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_update_prices_on_exch_update', 'no' ) ) {
				require_once wcj_free_plugin_path() . '/includes/background-process/class-wcj-multicurrency-price-updater.php';
				$this->bkg_process_price_updater = new WCJ_Multicurrency_Price_Updater();
			}
		}

		/**
		 * Handles third party compatibility.
		 *
		 * @version 5.4.3
		 * @since   4.3.0
		 */
		public function handle_compatibility() {
			// "WooCommerce Smart Coupons" Compatibility.
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_wc_smart_coupons', 'yes' ) ) {
				add_filter( 'woocommerce_coupon_get_amount', array( $this, 'smart_coupons_get_amount' ), 10, 2 );
			}

			// WooCommerce Coupons.
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'check_woocommerce_coupon_min_max_amount' ), 10, 3 );
			add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'fix_wc_coupon_discount_amount' ), 10, 3 );

			// WooCommerce Attribute Swatches by Iconic Plugin.
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_wc_attribute_swatches_premium_variable_cart_item_price', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_price', array( $this, 'fix_wc_attribute_swatches_premium_variable_cart_item_price' ), 20, 3 );
			}

			// WooCommerce Price Filter Widget.
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_wc_price_filter', 'no' ) ) {
				add_action( 'wp_footer', array( $this, 'add_compatibility_with_price_filter_widget' ) );
				add_action( 'wp_footer', array( $this, 'fix_price_filter_widget_currency_format' ) );
				add_filter( 'woocommerce_price_filter_sql', array( $this, 'get_price_filter_sql_compatible' ), 10, 3 );
				add_filter( 'posts_clauses', array( $this, 'posts_clauses_price_filter_compatible' ), 11, 2 );
				add_filter(
					'woocommerce_price_filter_widget_step',
					function ( $step ) {
						$step = 1;
						return $step;
					}
				);
			}

			// Sort by Price.
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_price_sorting_per_product', 'no' ) ) {
				add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'handle_per_product_opt_with_sort_by_price' ), 29, 3 );
			}

			// Fix WooCommerce Import.
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_wc_import', 'no' ) ) {
				add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'fix_wc_product_import' ), 10, 2 );
			}

			// WPC Product Bundles plugin.
			add_action(
				'woocommerce_init',
				function () {
					if ( 'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_wpc_product_bundle', 'no' ) ) {
						wcj_remove_class_filter( 'woocommerce_add_to_cart', 'WPcleverWoosb', 'woosb_add_to_cart' );
					}
				},
				99
			);

			// WooCommerce Tree Table Rate Shipping (https://tablerateshipping.com/).
			$this->wc_tree_table_rate_shipping_compatibility();

			// Flexible Shipping plugin https://flexibleshipping.com/.
			add_filter( 'flexible_shipping_value_in_currency', array( $this, 'flexible_shipping_compatibility' ) );

			// Compatibility with Price by Country module.
			add_action( 'wcj_price_by_country_set_country', array( $this, 'change_currency_by_country' ), 10, 2 );

			// Compatibility with Pricing Deals.
			add_filter( 'option_vtprd_rules_set', array( $this, 'convert_pricing_deals_settings' ) );

			// "WooCommerce Product Add-ons" plugin.
			add_filter( 'woocommerce_get_item_data', array( $this, 'get_addons_item_data' ), 20, 2 );
			add_filter( 'woocommerce_product_addons_option_price_raw', array( $this, 'product_addons_option_price_raw' ), 10, 2 );
			add_filter( 'woocommerce_product_addons_price_raw', array( $this, 'product_addons_price_raw' ), 10, 2 );

			// Free Shipping.
			add_filter( 'woocommerce_shipping_free_shipping_instance_option', array( $this, 'convert_free_shipping_min_amount' ), 10, 3 );
			add_filter( 'woocommerce_shipping_free_shipping_option', array( $this, 'convert_free_shipping_min_amount' ), 10, 3 );

		}

		/**
		 * Convert_free_shipping_min_amount.
		 *
		 * @version 5.3.3
		 * @since   5.3.3
		 *
		 * @param string | int $option defines the option.
		 * @param string       $key defines the key.
		 * @param string       $method defines the method.
		 *
		 * @return mixed
		 */
		public function convert_free_shipping_min_amount( $option, $key, $method ) {
			if (
			'no' === wcj_get_option( 'wcj_multicurrency_compatibility_free_shipping', 'no' )
			|| 'min_amount' !== $key
			|| ! is_numeric( $option )
			|| 0 === (float) $option
			) {
				return $option;
			}
			$option = $this->change_price( $option, null );
			return $option;
		}



		/**
		 * Product_addons_price_raw.
		 *
		 * @version 5.6.2
		 * @since   5.1.1
		 *
		 * @param int   $price defines the price.
		 * @param array $addon defines the addon.
		 *
		 * @return mixed|string
		 */
		public function product_addons_price_raw( $price, $addon ) {
			if (
			'no' === wcj_get_option( 'wcj_multicurrency_compatibility_product_addons', 'no' )
			|| ( 'quantity_based' !== $addon['price_type'] && 'flat_fee' !== $addon['price_type'] )
			) {
				return $price;
			}
			$price = $this->change_price( $price, null );
			return $price;
		}

		/**
		 * Convert_pricing_deals_settings.
		 *
		 * @version 5.0.0
		 * @since   5.0.0
		 *
		 * @param array $option defines the option.
		 *
		 * @return mixed
		 */
		public function convert_pricing_deals_settings( $option ) {
			if ( 'no' === wcj_get_option( 'wcj_multicurrency_compatibility_pricing_deals', 'no' ) ) {
				return $option;
			}
			foreach ( $option as $key => $value ) {
				foreach ( $value->rule_deal_info as $deal_info_key => $deal_info_value ) {
					if ( 'currency' === $deal_info_value['buy_amt_type'] ) {
						$option[ $key ]->rule_deal_info[ $deal_info_key ]['buy_amt_count'] *= $this->get_currency_exchange_rate( $this->get_current_currency_code() );
					}
				}
			}
			return $option;
		}

		/**
		 * Change_currency_by_country.
		 *
		 * @version 5.0.0
		 * @since   5.0.0
		 *
		 * @param string       $country defines the country.
		 * @param int | string $currency defines the currency.
		 */
		public function change_currency_by_country( $country, $currency ) {
			if (
			empty( $currency ) ||
			'no' === wcj_get_option( 'wcj_multicurrency_compatibility_price_by_country_module', 'no' )
			) {
				return;
			}
			wcj_session_set( 'wcj-currency', $currency );
		}

		/**
		 * Wcj_multicurrency_compatibility_flexible_shipping.
		 *
		 * @see https://flexibleshipping.com
		 *
		 * @version 4.9.0
		 * @since   4.9.0
		 * @param int | string $value defines the value.
		 */
		public function flexible_shipping_compatibility( $value ) {
			if ( 'yes' !== wcj_get_option( 'wcj_multicurrency_compatibility_flexible_shipping', 'no' ) ) {
				return $value;
			}
			$value *= $this->get_currency_exchange_rate( $this->get_current_currency_code() );
			return $value;
		}

		/**
		 * Wc_tree_table_rate_shipping_compatibility.
		 *
		 * @see https://tablerateshipping.com/
		 *
		 * @version 4.9.0
		 * @since   4.9.0
		 */
		public function wc_tree_table_rate_shipping_compatibility() {
			$shipping_instance_max = apply_filters( 'wcj_multicurrency_compatibility_wc_ttrs_instances', 90 );
			for ( $i = 1; $i <= $shipping_instance_max; $i ++ ) {
				add_filter( 'option_woocommerce_tree_table_rate_' . $i . '_settings', array( $this, 'convert_wc_tree_table_rate_settings' ) );
			}
		}

		/**
		 * Get_wc_tree_table_rate_settings.
		 *
		 * @version 5.6.2
		 * @since   4.9.0
		 * @param array $option defines the option.
		 *
		 * @return mixed
		 */
		public function convert_wc_tree_table_rate_settings( $option ) {
			if ( 'no' === wcj_get_option( 'wcj_multicurrency_compatibility_wc_ttrs', 'no' ) ) {
				return $option;
			}
			$transition_name      = 'wcj_wc_tree_table_opt_' . md5( current_filter() );
			$modified_rule_result = get_transient( $transition_name );
			if ( false === ( $modified_rule_result ) ) {
				$rule          = json_decode( $option['rule'] );
				$modified_rule = $this->recursively_convert_wc_tree_settings(
					$rule,
					array(
						'change_keys'   => array( 'value', 'min', 'max' ),
						'exchange_rate' => $this->get_currency_exchange_rate( $this->get_current_currency_code() ),
					)
				);
				set_transient( $transition_name, wp_json_encode( $modified_rule ), 5 * MINUTE_IN_SECONDS );
			}
			$option['rule'] = $modified_rule_result;
			remove_filter( current_filter(), array( $this, 'convert_wc_tree_table_rate_settings' ) );
			return $option;
		}

		/**
		 * Recursively_convert_wc_tree_settings.
		 *
		 * @version 5.6.2
		 * @since   4.9.0
		 *
		 * @param array $array defines the array.
		 * @param null  $args defines the args.
		 *
		 * @return array
		 */
		public function recursively_convert_wc_tree_settings( $array, $args = null ) {
			$args          = wp_parse_args(
				$args,
				array(
					'change_keys'   => array(),
					'exchange_rate' => 1,
				)
			);
			$change_keys   = $args['change_keys'];
			$exchange_rate = $args['exchange_rate'];
			foreach ( $array as $key => $value ) {
				if ( in_array( $key, $change_keys, true ) ) {
					if (
						is_array( $array ) &&
						isset( $array['condition'] ) &&
						'price' === $array['condition'] &&
						! empty( $value ) &&
						is_numeric( $value )
					) {
						$array[ $key ] = $value * $exchange_rate;
					} elseif (
					is_a( $array, 'stdClass' ) &&
					property_exists( $array, 'condition' ) &&
					'price' === $array->condition &&
					! empty( $value ) &&
					is_numeric( $value )
					) {
						$array->$key = $value * $exchange_rate;
					}
				}
				if ( is_array( $value ) || is_a( $value, 'stdClass' ) ) {
					$this->recursively_convert_wc_tree_settings( $value, $args );
				}
			}
			return $array;
		}

		/**
		 * Check_woocommerce_coupon_min_max_amount
		 *
		 * @version 5.6.2
		 * @since   4.9.0
		 *
		 * @param string       $valid defines the valid.
		 * @param WC_Coupon    $coupon defines the  $coupon.
		 * @param WC_Discounts $wc_discounts defines the  wc_discounts.
		 *
		 * @return bool
		 * @throws Exception Throws error.
		 */
		public function check_woocommerce_coupon_min_max_amount( $valid, WC_Coupon $coupon, WC_Discounts $wc_discounts ) {
			if ( 'yes' !== wcj_get_option( 'wcj_multicurrency_compatibility_wc_coupons', 'no' ) ) {
				return $valid;
			}
			remove_filter( 'woocommerce_coupon_is_valid', array( $this, 'check_woocommerce_coupon_min_max_amount' ), 10 );
			$minimum_amount = $coupon->get_minimum_amount();
			if ( ! empty( $minimum_amount ) ) {
				$coupon->set_minimum_amount( $this->change_price( $minimum_amount, null ) );
			}
			$maximum_amount = $coupon->get_maximum_amount();
			if ( ! empty( $maximum_amount ) ) {
				$coupon->set_maximum_amount( $this->change_price( $maximum_amount, null ) );
			}
			$is_coupon_valid = $wc_discounts->is_coupon_valid( $coupon );
			if ( is_wp_error( $is_coupon_valid ) ) {
				return false;
			}
			return $valid;
		}

		/**
		 * Fix_wc_coupon_discount_amount.
		 *
		 * @version 5.6.2
		 * @since   5.2.0
		 *
		 * @param bool           $false defines the false.
		 * @param string | array $data defines the data.
		 * @param string | array $coupon defines the coupon.
		 *
		 * @return mixed
		 */
		public function fix_wc_coupon_discount_amount( $false, $data, $coupon ) {
			$coupon_id = wc_get_coupon_id_by_code( $data );
			if (
			'yes' !== wcj_get_option( 'wcj_multicurrency_compatibility_wc_coupons', 'no' ) ||
			is_admin() ||
			empty( $coupon_id ) ||
			'fixed_cart' !== get_post_meta( $coupon_id, 'discount_type', true )
			) {
				return $false;
			}
			$current_coupon_amount = get_post_meta( $coupon_id, 'coupon_amount', true );
			$coupon->set_amount( $this->change_price( $current_coupon_amount, null ) );
			return $coupon;
		}

		/**
		 * Fix_wc_attribute_swatches_premium_variable_cart_item_price.
		 *
		 * @version 5.4.3
		 * @since   5.4.3
		 *
		 * @param int    $price_html defines the price_html.
		 * @param array  $cart_item defines the cart_item.
		 * @param string $cart_item_key defines the cart_item_key.
		 *
		 * @return mixed
		 */
		public function fix_wc_attribute_swatches_premium_variable_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
			if ( empty( $cart_item['iconic_was_fee'] ) ) {
				return $price_html;
			}
			$price = $this->change_price( $cart_item['iconic_was_fee'], null );
			return wc_price( $price );
		}

		/**
		 * Fixes sort by price when `wcj_multicurrency_per_product_enabled` is enabled.
		 *
		 * @version 4.6.0
		 * @since   4.5.0
		 *
		 * @param string | array $args defines the args.
		 * @param string         $orderby defines the orderby.
		 * @param string | array $order defines the order.
		 *
		 * @return mixed
		 */
		public function handle_per_product_opt_with_sort_by_price( $args, $orderby, $order ) {
			if (
			'price' !== $orderby ||
			is_admin() ||
			! is_main_query() ||
			'no' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'no' ) ||
			get_option( 'woocommerce_currency' ) === $this->get_current_currency_code() ||
			1 === $this->get_currency_exchange_rate(
				$this->get_current_currency_code()
			)
			) {
				return $args;
			}
			$order = 'DESC' === $order ? $order : 'ASC';
			add_filter(
				'posts_clauses',
				function ( $clauses ) use ( $order ) {
					global $wpdb;
					if (
					false === strpos( $clauses['orderby'], 'wc_product_meta_lookup.max_price DESC' ) &&
					false === strpos( $clauses['orderby'], 'wc_product_meta_lookup.min_price ASC' )
					) {
						return $clauses;
					}
					$current_currency_code = $this->get_current_currency_code();
					$exchange_rate         = $this->get_currency_exchange_rate( $current_currency_code );

					$min_max_join = "LEFT JOIN {$wpdb->postmeta} AS pm on pm.post_id = {$wpdb->posts}.ID AND (pm.meta_key IN ('_wcj_multicurrency_per_product_min_price_{$current_currency_code}','_wcj_multicurrency_per_product_max_price_{$current_currency_code}') and pm.meta_value!='')";
					if ( false === strpos( $clauses['join'], $min_max_join ) ) {
						$clauses['join'] .= " {$min_max_join} ";
					}
					if ( 'DESC' === $order ) {
						$clauses['fields'] .= ", (IFNULL(MAX((pm.meta_value +0))/{$exchange_rate},max_price) +0) AS wcj_price ";
					} else {
						$clauses['fields'] .= ", (IFNULL(MIN((pm.meta_value +0))/{$exchange_rate},min_price) +0) AS wcj_price  ";
					}
					$clauses['orderby'] = " wcj_price {$order}, wc_product_meta_lookup.product_id {$order} ";

					return $clauses;
				},
				99
			);
			return $args;
		}

		/**
		 * Makes Price Filter Widget filter correct products if Per product option `wcj_multicurrency_per_product_enabled` is enabled.
		 *
		 * First it removes products witch `_wcj_multicurrency_per_product_regular_price_{$current_currency_code}` meta don't match min and max.
		 * Then it adds products witch `_wcj_multicurrency_per_product_regular_price_{$current_currency_code}` meta match min and max.
		 *
		 * @version 5.6.8
		 * @since   4.5.0
		 *
		 * @see WC_Query::price_filter_post_clauses()
		 *
		 * @param array  $args defines the args.
		 * @param string $query defines the query.
		 *
		 * @return mixed
		 */
		public function posts_clauses_price_filter_compatible( $args, $query ) {
			if (
			is_admin() ||
			'no' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'no' ) ||
			get_option( 'woocommerce_currency' ) === $this->get_current_currency_code() ||
			1 === (int) $this->get_currency_exchange_rate( $this->get_current_currency_code() ) ||
			! isset( $args['where'] ) ||
			(
				false === strpos( $args['where'], 'wc_product_meta_lookup.min_price >=' ) &&
				false === strpos( $args['where'], 'wc_product_meta_lookup.max_price <=' )
			)
			) {
				return $args;
			}

			global $wpdb;
			// phpcs:disable WordPress.Security.NonceVerification
			$current_currency_code = $this->get_current_currency_code();
			$exchange_rate         = $this->get_currency_exchange_rate( $current_currency_code );
			$min_price             = ( isset( $_GET['min_price'] ) ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : 0;
			$max_price             = ( isset( $_GET['max_price'] ) ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : PHP_INT_MAX;
			$min_max_join          = "LEFT JOIN {$wpdb->postmeta} AS pm on pm.post_id = {$wpdb->posts}.ID AND (pm.meta_key IN ('_wcj_multicurrency_per_product_min_price_{$current_currency_code}','_wcj_multicurrency_per_product_max_price_{$current_currency_code}') and pm.meta_value!='')";
			if ( false === strpos( $args['join'], $min_max_join ) ) {
				$args['join'] .= " {$min_max_join} ";
			}
			$args['fields']  .= ", (IFNULL(MIN((pm.meta_value +0))/{$exchange_rate},min_price) +0) AS wcj_min_price ";
			$args['fields']  .= ", (IFNULL(MAX((pm.meta_value +0))/{$exchange_rate},max_price) +0) AS wcj_max_price ";
			$args['where']    = preg_replace( '/and wc_product_meta_lookup.min_price >= \d.* and wc_product_meta_lookup.max_price <= \d.*\s/i', '', $args['where'] );
			$args['groupby'] .= " having wcj_min_price >= $min_price AND wcj_min_price <= $max_price ";
			// phpcs:enable WordPress.Security.NonceVerification
			return $args;
		}

		/**
		 * Makes Price Filter Widget show correct min and max values if Per product option `wcj_multicurrency_per_product_enabled` is enabled.
		 *
		 * It works comparing min and max values from "_wcj_multicurrency_per_product_regular_price_{currency_code}" meta as well as min and max price from wc_product_meta_lookup
		 *
		 * @version 4.6.0
		 * @since   4.5.0
		 *
		 * @see WC_Widget_Price_Filter::get_filtered_price()
		 *
		 * @param string $sql defines the sql.
		 * @param string $meta_query_sql defines the meta_query_sql.
		 * @param string $tax_query_sql defines the tax_query_sql.
		 *
		 * @return mixed
		 */
		public function get_price_filter_sql_compatible( $sql, $meta_query_sql, $tax_query_sql ) {
			if (
			is_admin() ||
			'no' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'no' ) ||
			get_option( 'woocommerce_currency' ) === $this->get_current_currency_code() ||
			1 === $this->get_currency_exchange_rate( $this->get_current_currency_code() )
			) {
				return $sql;
			}

			global $wpdb;
			$current_currency_code = $this->get_current_currency_code();
			$exchange_rate         = $this->get_currency_exchange_rate( $current_currency_code );
			$args                  = WC()->query->get_main_query()->query_vars;
			$tax_query             = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
			$meta_query            = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

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

			$search           = WC_Query::get_main_search_query_sql();
			$search_query_sql = $search ? ' AND ' . $search : '';

			$sql = "
			SELECT FLOOR(min(wcj_min_price)) as min_price, CEILING(max(wcj_max_price)) as max_price
			FROM(
			SELECT (IFNULL(MIN((pm.meta_value +0))/{$exchange_rate},min_price) +0) AS wcj_min_price, (IFNULL(MAX((pm.meta_value +0))/{$exchange_rate},max_price) + 0) AS wcj_max_price
				FROM {$wpdb->wc_product_meta_lookup}
				LEFT JOIN {$wpdb->postmeta} AS pm on pm.post_id = product_id AND (pm.meta_key IN ('_wcj_multicurrency_per_product_min_price_{$current_currency_code}','_wcj_multicurrency_per_product_max_price_{$current_currency_code}') and pm.meta_value!='')
				WHERE product_id IN (
					SELECT ID FROM {$wpdb->posts}
					" . $tax_query_sql['join'] . $meta_query_sql['join'] . "
					WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
					AND {$wpdb->posts}.post_status = 'publish'
					" . $tax_query_sql['where'] . $meta_query_sql['where'] . $search_query_sql . '
				)
				group by product_id
			) as wcj_min_max_price
		';
			return $sql;
		}

		/**
		 * Fixes WooCommerce import.
		 *
		 * For now it converts '_wcj_%price%_{currency}' meta with lowercase currency to uppercase.
		 *
		 * @version 4.4.0
		 * @since   4.4.0
		 *
		 * @param array          $parsed_data defines the parsed_data.
		 * @param string | array $raw_data defines the raw_data.
		 *
		 * @return mixed
		 */
		public function fix_wc_product_import( $parsed_data, $raw_data ) {
			// Gets values.
			$multicurrency_values = array_filter(
				$parsed_data['meta_data'],
				function ( $value ) {
					return preg_match( '/^_wcj_.+price.*_[a-z]{2,3}$/', $value['key'] );
				}
			);

			// Changes lowercase currency to uppercase.
			$modified_values = array_map(
				function ( $value ) {
					$str             = $value['key'];
					$last_underscore = strrpos( $str, '_' );
					$before          = substr( $str, 0, $last_underscore );
					$currency        = substr( $str, $last_underscore + 1 );
					$value['key']    = $before . '_' . strtoupper( $currency );
					return $value;
				},
				$multicurrency_values
			);

			// Applies changes to the main data.
			foreach ( $modified_values as $i => $value ) {
				$parsed_data['meta_data'][ $i ]['key'] = $value['key'];
			}

			return $parsed_data;
		}

		/**
		 * Adds compatibility with WooCommerce Price Filter widget.
		 *
		 * @see price-slider.js, init_price_filter()
		 *
		 * @version 5.6.2
		 * @since   4.3.0
		 */
		public function add_compatibility_with_price_filter_widget() {
			if ( ! is_active_widget( false, false, 'woocommerce_price_filter' ) ) {
				return;
			}
			?>
			<?php
			$exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
			if ( '1' === (string) $exchange_rate ) {
				return;
			}
			?>
		<input type="hidden" id="wcj_mc_exchange_rate" value="<?php echo esc_html( $exchange_rate ); ?>"/>
		<script>
			var wcj_mc_pf_slider = {
				slider: null,
				convert_rate: 1,
				original_min: 1,
				original_max: 1,
				original_values: [],
				current_min: 1,
				current_max: 1,
				current_values: [],
				step: 1,

				init(slider, convert_rate, step) {
					this.step = step;
					this.slider = slider;
					this.convert_rate = convert_rate;
					this.original_min = jQuery(this.slider).slider("option", "min");
					this.original_max = jQuery(this.slider).slider("option", "max");
					if (this.original_min > jQuery(this.slider).parent().find('#min_price').val()) {
						jQuery(this.slider).parent().find('#min_price').attr('value', this.original_min);
					}
					if (this.original_max < jQuery(this.slider).parent().find('#max_price').val()) {
						jQuery(this.slider).parent().find('#max_price').attr('value', this.original_max);
					}
					this.original_values = jQuery(this.slider).slider("option", "values");
					this.current_min = this.original_min * this.convert_rate;
					this.current_max = this.original_max * this.convert_rate;
					this.current_values[0] = jQuery(this.slider).parent().find('#min_price').val() * wcj_mc_pf_slider.convert_rate;
					this.current_values[1] = jQuery(this.slider).parent().find('#max_price').val() * wcj_mc_pf_slider.convert_rate;
					this.update_slider();
				},

				update_slider() {
					jQuery(this.slider).slider("destroy");
					var current_min_price = this.current_min;
					var current_max_price = this.current_max;

					jQuery(this.slider).slider({
						range: true,
						animate: true,
						min: current_min_price,
						max: current_max_price,
						step: parseFloat(this.step),
						values: wcj_mc_pf_slider.current_values,
						create: function () {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val((wcj_mc_pf_slider.current_values[0] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val((wcj_mc_pf_slider.current_values[1] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(document.body).trigger('price_slider_create', [(wcj_mc_pf_slider.current_values[0]).toFixed(2), (wcj_mc_pf_slider.current_values[1]).toFixed(2)]);
						},
						slide: function (event, ui) {
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #min_price').val((ui.values[0] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							jQuery(wcj_mc_pf_slider.slider).parent().find('.price_slider_amount #max_price').val((ui.values[1] / wcj_mc_pf_slider.convert_rate).toFixed(2));
							var the_min = ui.values[0] == wcj_mc_pf_slider.current_values[0] ? (wcj_mc_pf_slider.current_values[0]).toFixed(2) : ui.values[0];
							var the_max = ui.values[1] == wcj_mc_pf_slider.current_values[1] ? (wcj_mc_pf_slider.current_values[1]).toFixed(2) : ui.values[1];
							jQuery(document.body).trigger('price_slider_slide', [the_min, the_max]);
						},
						change: function (event, ui) {
							jQuery(document.body).trigger('price_slider_change', [ui.values[0], ui.values[1]]);
						}
					});
				}
			};
			var wcj_mc_pf = {
				price_filters: null,
				rate: 1,
				step: 1,
				init: function (price_filters) {
					this.price_filters = price_filters;
					this.rate = document.getElementById('wcj_mc_exchange_rate').value;
					this.update_slider();
				},
				update_slider: function () {
					[].forEach.call(wcj_mc_pf.price_filters, function (el) {
						wcj_mc_pf_slider.init(el, wcj_mc_pf.rate, wcj_mc_pf.step);
					});
				}
			}
			document.addEventListener("DOMContentLoaded", function () {
				var price_filters = document.querySelectorAll('.price_slider.ui-slider');
				if (price_filters.length) {
					wcj_mc_pf.init(price_filters);
				}
			});
		</script>
			<?php
		}

		/**
		 * Fixes price filter widget currency format.
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 */
		public function fix_price_filter_widget_currency_format() {
			$price_args = apply_filters(
				'wc_price_args',
				array(
					'ex_tax_label'       => false,
					'currency'           => '',
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimals'           => wc_get_price_decimals(),
					'price_format'       => get_woocommerce_price_format(),
				)
			);
			$symbol     = apply_filters( 'woocommerce_currency_symbol', get_woocommerce_currency_symbol(), get_woocommerce_currency() );
			wp_localize_script(
				'wc-price-slider',
				'woocommerce_price_slider_params',
				array(
					'currency_format_num_decimals' => $price_args['decimals'],
					'currency_format_symbol'       => $symbol,
					'currency_format_decimal_sep'  => esc_attr( $price_args['decimal_separator'] ),
					'currency_format_thousand_sep' => esc_attr( $price_args['thousand_separator'] ),
					'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), $price_args['price_format'] ) ),
				)
			);
		}

		/**
		 * Add_hooks.
		 *
		 * @version 5.0.0
		 */
		public function add_hooks() {
			if ( wcj_is_frontend() ) {

				// Prices - Compatibility - "WooCommerce TM Extra Product Options" plugin.
				add_filter( 'woocommerce_tm_epo_price_on_cart', array( $this, 'change_price_by_currency_tm_extra_product_options_plugin_cart' ), $this->price_hooks_priority, 1 );
				add_filter( 'wc_epo_price', array( $this, 'change_price_by_currency_tm_extra_product_options_plugin' ), $this->price_hooks_priority, 3 );

				// Currency hooks.
				add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), $this->price_hooks_priority, 1 );

				// Add "Change Price" hooks.
				wcj_add_change_price_hooks( $this, $this->price_hooks_priority );

				// Handle Compatibility.
				$this->handle_compatibility();

				// Additional Price Filters.
				$this->additional_price_filters = wcj_get_option( 'wcj_multicurrency_switcher_additional_price_filters', '' );
				if ( ! empty( $this->additional_price_filters ) ) {
					$this->additional_price_filters = array_map( 'trim', explode( PHP_EOL, $this->additional_price_filters ) );
					foreach ( $this->additional_price_filters as $additional_price_filter ) {
						add_filter( $additional_price_filter, array( $this, 'change_price' ), $this->price_hooks_priority, 2 );
					}
				} else {
					$this->additional_price_filters = array();
				}
			} else {
				// Saves min and max price when product is updated.
				add_action(
					'woocommerce_update_product',
					function ( $post_id ) {
						if ( 'no' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ) ) {
							return;
						}
						$this->save_min_max_prices_per_product( $post_id );
					},
					99
				);

				// Saves min and max price when exchange rate changes.
				add_action( 'updated_option', array( $this, 'update_min_max_prices_on_exchange_rate_change' ), 10, 3 );
			}
		}

		/**
		 * Updates min and max prices on exchange rate change.
		 *
		 * The update is done with background process.
		 *
		 * @version 4.5.0
		 * @since   4.5.0
		 *
		 * @param string | array $option_name defines the option_name.
		 * @param string | array $old_value defines the old_value.
		 * @param string         $option_value defines the option_value.
		 */
		public function update_min_max_prices_on_exchange_rate_change( $option_name, $old_value, $option_value ) {
			if (
			'no' === wcj_get_option( 'wcj_multicurrency_update_prices_on_exch_update', 'no' ) ||
			'no' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ) ||
			false === strpos( $option_name, 'wcj_multicurrency_exchange_rate_' )
			) {
				return;
			}
			$currency_number = substr( $option_name, strrpos( $option_name, '_' ) + 1 );
			$currency        = wcj_get_option( 'wcj_multicurrency_currency_' . $currency_number );
			$product_ids     = $this->get_products_by_per_product_currency( $currency );
			if ( is_array( $product_ids ) && count( $product_ids ) > 0 ) {
				$this->bkg_process_price_updater->cancel_process();
				foreach ( $product_ids as $post_id ) {
					$this->bkg_process_price_updater->push_to_queue(
						array(
							'id'       => $post_id,
							'currency' => $currency,
						)
					);
				}
				$this->bkg_process_price_updater->save()->dispatch();
			}
		}

		/**
		 * Gets all products, or products with variations containing meta '_wcj_multicurrency_per_product_regular_price_{currency}' or '_wcj_multicurrency_per_product_sale_price_{currency}'.
		 *
		 * @version 6.0.0
		 * @since   4.5.0
		 *
		 * @param int | string $currency defines the currency.
		 *
		 * @return array
		 */
		public function get_products_by_per_product_currency( $currency ) {
			if ( empty( $currency ) ) {
				$currency = wcj_get_option( 'woocommerce_currency' );
			}

			global $wpdb;
			$product_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT p.ID
					FROM {$wpdb->posts} AS p
					LEFT JOIN {$wpdb->posts} AS p2 ON p.ID = p2.ID OR (p2.post_parent = p.ID AND p2.post_type='product_variation')
					LEFT JOIN {$wpdb->postmeta} AS pm ON (p2.ID = pm.post_id) AND (pm.meta_key IN (%s,%s) AND pm.meta_value!='')
					WHERE p.post_status = 'publish' AND p.post_type IN ('product')
					AND pm.meta_value != 'null'
					GROUP BY p.ID",
					'_wcj_multicurrency_per_product_regular_price_' . esc_html( $currency ),
					'_wcj_multicurrency_per_product_sale_price_' . esc_html( $currency )
				)
			);

			return $product_ids;
		}

		/**
		 * Updates min and max prices.
		 *
		 * @version 5.6.8
		 * @since   4.5.0
		 *
		 * @param int          $post_id defines the post_id.
		 * @param int | string $currency_code_param defines the currency_code_param.
		 */
		public function save_min_max_prices_per_product( $post_id, $currency_code_param = '' ) {
			$wpnonce  = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) : false;
			$product  = wc_get_product( $post_id );
			$products = array();
			if ( $product->is_type( 'variable' ) ) {
				$available_variations = $product->get_variation_prices()['price'];
				foreach ( $available_variations as $variation_id => $price ) {
					$products[ $variation_id ] = $price;
				}
			} else {
				$products[ $post_id ] = $product->get_price();
			}
			if ( empty( $currency_code_param ) ) {
				$total_number = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
			} else {
				$total_number = 1;
			}

			$per_product_prices = array();
			for ( $i = 1; $i <= $total_number; $i ++ ) {
				$currency_code = empty( $currency_code_param ) ? wcj_get_option( 'wcj_multicurrency_currency_' . $i ) : $currency_code_param;
				$exchange_rate = $this->get_currency_exchange_rate( $currency_code );
				foreach ( $products as $product_id => $original_price ) {
					// Regular Price.

					if ( $wpnonce && isset( $_POST[ "wcj_multicurrency_per_product_regular_price_{$currency_code}_{$product_id}" ] ) ) {
						$regular_price = sanitize_text_field( wp_unslash( $_POST[ "wcj_multicurrency_per_product_regular_price_{$currency_code}_{$product_id}" ] ) );
					} else {
						$regular_price = get_post_meta( $product_id, '_wcj_multicurrency_per_product_regular_price_' . $currency_code, true );
					}

					// Sale Price.
					if ( $wpnonce && isset( $_POST[ "wcj_multicurrency_per_product_sale_price_{$currency_code}_{$product_id}" ] ) ) {
						$sale_price = sanitize_text_field( wp_unslash( $_POST[ "wcj_multicurrency_per_product_sale_price_{$currency_code}_{$product_id}" ] ) );
					} else {
						$sale_price = get_post_meta( $product_id, '_wcj_multicurrency_per_product_sale_price_' . $currency_code, true );
					}
					$prices = array_filter( array( $regular_price, $sale_price ) );
					$price  = count( $prices ) > 0 ? min( $prices ) : (float) $original_price * (float) $exchange_rate;
					$per_product_prices[ $currency_code ][ $product_id ] = $price;
				}
			}
			foreach ( $per_product_prices as $currency => $values ) {
				update_post_meta( $post_id, '_wcj_multicurrency_per_product_min_price_' . $currency, min( $values ) );
				update_post_meta( $post_id, '_wcj_multicurrency_per_product_max_price_' . $currency, max( $values ) );
			}
		}

		/**
		 * Converts Smart Coupon currency.
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 *
		 * @param int | string $value defines the value.
		 * @param int | string $coupon defines the coupon.
		 *
		 * @return float|int
		 */
		public function smart_coupons_get_amount( $value, $coupon ) {
			if (
			! is_a( $coupon, 'WC_Coupon' ) ||
			'smart_coupon' !== $coupon->get_discount_type()
			) {
				return $value;
			}
			$value = $this->change_price( $value, null );
			return $value;
		}

		/**
		 * Init.
		 *
		 * @version 5.6.8
		 * @since   3.4.5
		 */
		public function init() {
			wcj_session_maybe_start();
			$wpnonce       = isset( $_REQUEST['wcj-currency-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-currency-nonce'] ), 'wcj-currency' ) : false;
			$session_value = wcj_session_get( 'wcj-currency' );
			if ( null === ( $session_value ) ) {
				$currency = $this->get_default_currency();
			}
			if ( $wpnonce && isset( $_REQUEST['wcj-currency'] ) ) {
				$currency = sanitize_text_field( wp_unslash( $_REQUEST['wcj-currency'] ) );
			}
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_default_currency_force', 'no' ) ) {
				$currency = $this->get_default_currency();
			}
			if ( isset( $currency ) ) {
				wcj_session_set( 'wcj-currency', $currency );
			}
		}

		/**
		 * Get_default_currency.
		 *
		 * @version 6.0.0
		 * @since   5.3.4
		 *
		 * @return bool
		 */
		public function get_default_currency() {
			$module_roles = wcj_get_option( 'wcj_multicurrency_role_defaults_roles', '' );
			if ( ! empty( $module_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				if ( in_array( $current_user_role, $module_roles, true ) ) {
					$currency = wcj_get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
					if ( '' !== $currency && null !== $currency ) {
						return $currency;
					}
				}
			}
			$default_currency_number = wcj_get_option( 'wcj_multicurrency_default_currency', 1 );
			$currency                = wcj_get_option( 'wcj_multicurrency_currency_' . $default_currency_number, apply_filters( 'woocommerce_currency', get_option( 'woocommerce_currency' ) ) );
			return $currency;
		}

		/**
		 * Converts add-ons plugin prices.
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 *
		 * @param int            $price defines the price.
		 * @param string | array $option defines the option.
		 *
		 * @return float|int
		 */
		public function product_addons_option_price_raw( $price, $option ) {
			if ( 'no' === wcj_get_option( 'wcj_multicurrency_compatibility_product_addons', 'no' ) ) {
				return $price;
			}
			$price = $this->change_price( $price, null );
			return $price;
		}

		/**
		 * Finds old add-ons fields on cart and replace by correct price.
		 *
		 * @version 5.6.2
		 * @since   4.3.0
		 *
		 * @param array $other_data defines the other_data.
		 * @param array $cart_item defines the cart_item.
		 *
		 * @return mixed
		 */
		public function get_addons_item_data( $other_data, $cart_item ) {
			if ( 'no' === wcj_get_option( 'wcj_multicurrency_compatibility_product_addons', 'no' ) ) {
				return $other_data;
			}
			if ( ! empty( $cart_item['addons'] ) ) {
				foreach ( $cart_item['addons'] as $addon ) {
					$price    = isset( $cart_item['addons_price_before_calc'] ) ? $cart_item['addons_price_before_calc'] : $addon['price'];
					$name_old = $addon['name'];

					// Get old field name (with wrong currency price).
					if ( 0 === (int) $addon['price'] ) {
						$name_old .= '';
					} elseif ( 'percentage_based' === $addon['price_type'] && 0 === (int) $price ) {
						$name_old .= '';
					} elseif ( 'percentage_based' !== $addon['price_type'] && $addon['price'] && apply_filters( 'woocommerce_addons_add_price_to_name', '__return_true' ) ) {
						$name_old .= ' (' . wc_price( \WC_Product_Addons_Helper::get_product_addon_price_for_display( $addon['price'], $cart_item['data'], true ) ) . ')';
					} else {
						$_product = new WC_Product( $cart_item['product_id'] );
						$_product->set_price( $price * ( $addon['price'] / 100 ) );
						$name_old .= ' (' . WC()->cart->get_product_price( $_product ) . ')';
					}

					// Get new field name (with correct currency price).
					$name_new       = $addon['name'];
					$addon['price'] = $this->change_price( $addon['price'], null );
					if ( 0 === (int) $addon['price'] ) {
						$name_new .= '';
					} elseif ( 'percentage_based' === $addon['price_type'] && 0 === (int) $price ) {
						$name_new .= '';
					} elseif ( 'percentage_based' !== $addon['price_type'] && $addon['price'] && apply_filters( 'woocommerce_addons_add_price_to_name', '__return_true' ) ) {
						$name_new .= ' (' . wc_price( \WC_Product_Addons_Helper::get_product_addon_price_for_display( $addon['price'], $cart_item['data'], true ) ) . ')';
					} else {
						$_product = new WC_Product( $cart_item['product_id'] );
						$_product->set_price( $price * ( $addon['price'] / 100 ) );
						$name_new .= ' (' . WC()->cart->get_product_price( $_product ) . ')';
					}

					// Find old field on cart and replace by correct price.
					foreach ( $other_data as $key => $data ) {
						if ( $data['name'] === $name_old ) {
							$other_data[ $key ]['name'] = $name_new;
						}
					}
				}
			}
			return $other_data;
		}

		/**
		 * Change_price_by_currency_tm_extra_product_options_plugin_cart.
		 *
		 * @version 2.7.0
		 * @since   2.5.7
		 * @param int $price defines the price.
		 */
		public function change_price_by_currency_tm_extra_product_options_plugin_cart( $price ) {
			return $this->change_price( $price, null );
		}

		/**
		 * Change_price_by_currency_tm_extra_product_options_plugin.
		 *
		 * @version 2.7.0
		 * @since   2.5.7
		 * @param int    $price defines the price.
		 * @param string $type defines the type.
		 * @param int    $post_id defines the post_id.
		 */
		public function change_price_by_currency_tm_extra_product_options_plugin( $price, $type, $post_id ) {
			return $this->change_price( $price, null );
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
				if ( 'yes' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ) ) {
					foreach ( $_product->get_children() as $child_id ) {
						$the_price   = get_post_meta( $child_id, '_price', true );
						$the_product = wc_get_product( $child_id );
						$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
						if ( $the_price === $price ) {
							return $this->change_price( $price, $the_product );
						}
					}
				} else {
					return $this->change_price( $price, null );
				}
			}
			return $price;
		}

		/**
		 * Get_variation_prices_hash.
		 *
		 * @version 3.8.0
		 * @param array  $price_hash defines the price_hash.
		 * @param array  $_product defines the _product.
		 * @param string $display defines the display.
		 */
		public function get_variation_prices_hash( $price_hash, $_product, $display ) {
			$currency_code                   = $this->get_current_currency_code();
			$price_hash['wcj_multicurrency'] = array(
				'currency'               => $currency_code,
				'exchange_rate'          => $this->get_currency_exchange_rate( $currency_code ),
				'per_product'            => wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ),
				'per_product_make_empty' => wcj_get_option( 'wcj_multicurrency_per_product_make_empty', 'no' ),
				'rounding'               => wcj_get_option( 'wcj_multicurrency_rounding', 'no_round' ),
				'rounding_precision'     => wcj_get_option( 'wcj_multicurrency_rounding_precision', absint( wcj_get_option( 'woocommerce_price_num_decimals', 2 ) ) ),
			);
			return $price_hash;
		}

		/**
		 * Get_currency_exchange_rate.
		 *
		 * @param string $currency_code defines the currency_code.
		 * @version 2.4.3
		 */
		public function get_currency_exchange_rate( $currency_code ) {
			$currency_exchange_rate = 1;
			$total_number           = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( wcj_get_option( 'wcj_multicurrency_currency_' . $i ) === $currency_code ) {
					$currency_exchange_rate = wcj_get_option( 'wcj_multicurrency_exchange_rate_' . $i );
					break;
				}
			}
			return $currency_exchange_rate;
		}

		/**
		 * Do_revert.
		 *
		 * @version 3.9.0
		 * @since   2.5.0
		 */
		public function do_revert() {
			switch ( wcj_get_option( 'wcj_multicurrency_revert', 'no' ) ) {
				case 'cart_only':
					return is_cart();
				case 'yes': // checkout only.
					return is_checkout();
				case 'cart_and_checkout':
					return ( is_cart() || is_checkout() );
				default: // 'no'.
					return false;
			}
		}

		/**
		 * Saves price so it won't be necessary to calculate it multiple times.
		 *
		 * @version 6.0.1
		 * @since   4.6.0
		 *
		 * @param int    $price defines the price.
		 * @param int    $product_id defines the product_id.
		 * @param string $filter defines the filter.
		 */
		public function save_price( $price, $product_id, $filter ) {
			w_c_j()->all_modules['multicurrency']->calculated_products_prices[ $product_id ][ $filter ] = $price;
		}

		/**
		 * Change_price.
		 *
		 * @version 6.0.1
		 * @param int   $price defines the price.
		 * @param array $_product defines the _product.
		 * @param null  $args defines the args.
		 */
		public function change_price( $price, $_product, $args = null ) {
			// Pricing Deals.
			global $vtprd_cart;
			if (
			'yes' === wcj_get_option( 'wcj_multicurrency_compatibility_pricing_deals', 'no' ) &&
			( is_cart() || is_checkout() ) &&
			! empty( $vtprd_cart )
			) {
				return $price;
			}

			if ( '' === $price ) {
				return $price;
			}

			if ( $this->do_revert() ) {
				return $price;
			}

			$args = wp_parse_args(
				$args,
				array(
					'do_save' => ( 'yes' === wcj_get_option( 'wcj_multicurrency_multicurrency_save_prices', 'no' ) ),
				)
			);

			$_product_id     = wcj_get_product_id( $_product );
			$do_save         = $args['do_save'];
			$_current_filter = current_filter();
			if ( '' === $_current_filter || null === $_current_filter ) {
				$_current_filter = 'wcj_filter__none';
			}
			if ( $do_save && isset( w_c_j()->all_modules['multicurrency']->calculated_products_prices[ $_product_id ][ $_current_filter ] ) ) {
				return w_c_j()->all_modules['multicurrency']->calculated_products_prices[ $_product_id ][ $_current_filter ];
			}

			// Per product.
			$regular_price_per_product = get_post_meta( $_product_id, '_wcj_multicurrency_per_product_regular_price_' . $this->get_current_currency_code(), true );
			if ( 'yes' === wcj_get_option( 'wcj_multicurrency_per_product_enabled', 'yes' ) && null !== $_product ) {
				if (
				'yes' === wcj_get_option( 'wcj_multicurrency_per_product_make_empty', 'no' ) &&
				'yes' === get_post_meta( $_product_id, '_wcj_multicurrency_per_product_make_empty_' . $this->get_current_currency_code(), true )
				) {
					$price = '';
					$this->save_price( $price, $_product_id, $_current_filter );
					return $price;
				} elseif ( '' !== $regular_price_per_product && null !== $regular_price_per_product ) {
					if ( 'woocommerce_get_price_including_tax' === $_current_filter || 'woocommerce_get_price_excluding_tax' === $_current_filter ) {
						$price = wcj_get_product_display_price( $_product );
						$this->save_price( $price, $_product_id, $_current_filter );
						return $price;
					} elseif ( WCJ_PRODUCT_GET_PRICE_FILTER === $_current_filter || 'woocommerce_variation_prices_price' === $_current_filter || 'woocommerce_product_variation_get_price' === $_current_filter || in_array( $_current_filter, $this->additional_price_filters, true ) ) {
						if ( $_product->is_on_sale() ) {
							$sale_price_per_product = get_post_meta( $_product_id, '_wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
							$price                  = ( null !== $sale_price_per_product && '' !== $sale_price_per_product && $sale_price_per_product < $regular_price_per_product ) ? $sale_price_per_product : $regular_price_per_product;
						} else {
							$price = $regular_price_per_product;
						}
						$this->save_price( $price, $_product_id, $_current_filter );
						return $price;
					} elseif ( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER === $_current_filter || 'woocommerce_variation_prices_regular_price' === $_current_filter || 'woocommerce_product_variation_get_regular_price' === $_current_filter ) {
						$price = $regular_price_per_product;
						$this->save_price( $price, $_product_id, $_current_filter );
						return $price;
					} elseif ( WCJ_PRODUCT_GET_SALE_PRICE_FILTER === $_current_filter || 'woocommerce_variation_prices_sale_price' === $_current_filter || 'woocommerce_product_variation_get_sale_price' === $_current_filter ) {
						$sale_price_per_product = get_post_meta( $_product_id, '_wcj_multicurrency_per_product_sale_price_' . $this->get_current_currency_code(), true );
						$price                  = ( '' !== $sale_price_per_product && null !== $sale_price_per_product ) ? $sale_price_per_product : $price;
						$this->save_price( $price, $_product_id, $_current_filter );
						return $price;
					}
				}
			}

			// Global.
			$currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
			if ( '1' !== $currency_exchange_rate ) {
				$price = (float) $price * (float) $currency_exchange_rate;
				switch ( wcj_get_option( 'wcj_multicurrency_rounding', 'no_round' ) ) {
					case 'round':
						$price = round( $price, wcj_get_option( 'wcj_multicurrency_rounding_precision', absint( wcj_get_option( 'woocommerce_price_num_decimals', 2 ) ) ) );
						break;
					case 'round_up':
						$price = ceil( $price );
						break;
					case 'round_down':
						$price = floor( $price );
						break;
				}
				$this->save_price( $price, $_product_id, $_current_filter );
				return $price;
			}

			// No changes.
			$this->save_price( $price, $_product_id, $_current_filter );
			return $price;
		}

		/**
		 * Get_current_currency_code.
		 *
		 * @version 5.6.2
		 * @param string $default_currency defines the default_currency.
		 */
		public function get_current_currency_code( $default_currency = '' ) {
			$session_value = wcj_session_get( 'wcj-currency' );
			if ( null !== ( $session_value ) ) {
				return $session_value;
			} else {
				$module_roles = wcj_get_option( 'wcj_multicurrency_role_defaults_roles', '' );
				if ( ! empty( $module_roles ) ) {
					$current_user_role = wcj_get_current_user_first_role();
					if ( in_array( $current_user_role, $module_roles, true ) ) {
						$roles_default_currency = wcj_get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
						if ( '' !== $roles_default_currency && null !== $roles_default_currency ) {
							return $roles_default_currency;
						}
					}
				}
			}
			return $default_currency;
		}

		/**
		 * Change_currency_code.
		 *
		 * @version 2.5.0
		 * @param int | string $currency defines the currency.
		 */
		public function change_currency_code( $currency ) {
			if ( $this->do_revert() ) {
				return $currency;
			}
			return $this->get_current_currency_code( $currency );
		}

		/**
		 * Change_price_shipping.
		 *
		 * @version 4.9.0
		 * @param int | string $package_rates defines the package_rates.
		 * @param string       $package defines the package.
		 */
		public function change_price_shipping( $package_rates, $package ) {
			if (
			$this->do_revert() ||
			'no' === wcj_get_option( 'wcj_multicurrency_convert_shipping_values', 'yes' )
			) {
				return $package_rates;
			}
			$currency_exchange_rate = $this->get_currency_exchange_rate( $this->get_current_currency_code() );
			return wcj_change_price_shipping_package_rates( $package_rates, $currency_exchange_rate );
		}

	}

endif;

return new WCJ_Multicurrency();
