<?php
/**
 * Booster for WooCommerce - Module - Prices and Currencies by Country
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_Country' ) ) :

class WCJ_Price_By_Country extends WCJ_Module {

	/**
	 * @var WCJ_Price_by_Country_Core
	 *
	 * @since 4.1.0
	 */
	public $core;

	/**
	 * @var WCJ_Price_By_Country_Updater
	 */
	public $bkg_process_price_updater;

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 */
	function __construct() {

		$this->id         = 'price_by_country';
		$this->short_desc = __( 'Prices and Currencies by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change product price and currency automatically by customer\'s country (1 country group allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Change product price and currency automatically by customer\'s country.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-prices-and-currencies-by-country';
		parent::__construct();

		global $wcj_notice;
		$wcj_notice = '';

		if ( $this->is_enabled() ) {

			if ( wcj_is_frontend() ) {
				$do_load_core = true;
				if ( ! defined( 'DOING_AJAX' ) && '/wc-api/WC_Gateway_Paypal/' == $_SERVER['REQUEST_URI'] ) {
					// "Wrong currency in emails" bug fix
					$do_load_core = false;
				}
				if ( $do_load_core ) {
					// Frontend
					$this->core = include_once( 'price-by-country/class-wcj-price-by-country-core.php' );
				}
			}
			if ( is_admin() ) {
				// Backend
				include_once( 'reports/class-wcj-currency-reports.php' );
				if ( 'yes' === wcj_get_option( 'wcj_price_by_country_local_enabled', 'yes' ) ) {
					$backend_user_roles = wcj_get_option( 'wcj_price_by_country_backend_user_roles', '' );
					if ( empty( $backend_user_roles ) || wcj_is_user_role( $backend_user_roles ) ) {
						if ( 'inline' === wcj_get_option( 'wcj_price_by_country_local_options_style', 'inline' ) ) {
							include_once( 'price-by-country/class-wcj-price-by-country-local.php' );
						} else {
							add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
							add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
						}
					}
				}

				// Reset Price Filter
				add_action( 'init', array( $this, 'recalculate_price_filter_products_prices' ) );
			}

			// Price Filter Widget
			if ( 'yes' === wcj_get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
				add_action( 'save_post_product', array( $this, 'update_products_price_by_country_product_saved' ), PHP_INT_MAX, 2 );
				add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'update_products_price_by_country_product_saved_ajax' ), PHP_INT_MAX, 1 );
			}

			// Coupons Compatibility
			add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'fix_wc_coupon_currency' ), 10, 5 );

			// Bkg Process
			if ( 'init' === current_filter() ) {
				$this->init_bkg_process_class();
			} else {
				add_action( 'plugins_loaded', array( $this, 'init_bkg_process_class' ) );
			}
		}

		// Price Filter Widget
		if ( 'yes' === wcj_get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
			add_action( 'woojetpack_after_settings_save', array( $this, 'update_products_price_by_country_module_saved' ), PHP_INT_MAX, 2  );
		}

		if ( is_admin() ) {
			include_once( 'price-by-country/class-wcj-price-by-country-group-generator.php' );
		}
	}

	/**
	 * init_bkg_process_class.
	 *
	 * @version 5.1.0
	 * @since   5.1.0
	 */
	function init_bkg_process_class() {
		require_once( wcj_plugin_path() . '/includes/background-process/class-wcj-price-by-country-updater.php' );
		$this->bkg_process_price_updater = new WCJ_Price_By_Country_Updater();
	}

	/**
	 * fix_wc_coupon_currency.
	 *
	 * @version 4.8.0
	 * @since   4.8.0
	 *
	 * @param $amount
	 * @param $discount
	 * @param $cart_item
	 * @param $single
	 * @param $wc_coupon
	 *
	 * @return float|int
	 */
	function fix_wc_coupon_currency( $amount, $discount, $cart_item, $single, $wc_coupon ) {
		if (
			'yes' !== wcj_get_option( 'wcj_price_by_country_compatibility_wc_coupons', 'no' ) ||
			'fixed_cart' !== $wc_coupon->get_discount_type()
		) {
			return $amount;
		}

		if ( ! empty( $this->core ) ) {
			$amount = $this->core->change_price( $amount, null );
		}

		return $amount;
	}

	/**
	 * recalculate_price_filter_products_prices.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function recalculate_price_filter_products_prices() {
		if ( isset( $_GET['recalculate_price_filter_products_prices'] ) && ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) ) {
			wcj_update_products_price_by_country();
			global $wcj_notice;
			$wcj_notice = __( 'Price filter widget product prices recalculated.', 'woocommerce-jetpack' );
		}
	}

	/**
	 * update_products_price_by_country_module_saved.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function update_products_price_by_country_module_saved( $all_sections, $current_section ) {
		if ( 'price_by_country' === $current_section && wcj_is_module_enabled( 'price_by_country' ) ) {
			wcj_update_products_price_by_country();
		}
	}

	/**
	 * update_products_price_by_country_product_saved_ajax.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function update_products_price_by_country_product_saved_ajax( $post_id ) {
		wcj_update_products_price_by_country_for_single_product( $post_id );
	}

	/**
	 * update_products_price_by_country_product_saved.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function update_products_price_by_country_product_saved( $post_id, $post ) {
		wcj_update_products_price_by_country_for_single_product( $post_id );
	}

}

endif;

return new WCJ_Price_By_Country();
