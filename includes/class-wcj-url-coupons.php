<?php
/**
 * Booster for WooCommerce - Module - URL Coupons
 *
 * @version 2.9.1
 * @since   2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_URL_Coupons' ) ) :

class WCJ_URL_Coupons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function __construct() {

		$this->id         = 'url_coupons';
		$this->short_desc = __( 'URL Coupons', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce URL coupons.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-url-coupons';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'wp_loaded', array( $this, 'maybe_apply_url_coupon' ), PHP_INT_MAX );
		}
	}

	/**
	 * maybe_apply_url_coupon.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 * @todo    (maybe) add product to cart, if it's a product discount coupon
	 * @todo    (maybe) predefined $arg_key
	 * @todo    (maybe) additional $_GET['coupon_code']
	 * @todo    (maybe) if ( ! WC()->cart->has_discount( $coupon_code ) ) {}
	 */
	function maybe_apply_url_coupon() {
		$arg_key = get_option( 'wcj_url_coupons_key', 'wcj_apply_coupon' );
		if ( isset( $_GET[ $arg_key ] ) && '' != $_GET[ $arg_key ] ) {
			$coupon_code = sanitize_text_field( $_GET[ $arg_key ] );
			WC()->cart->add_discount( $coupon_code );
			wp_safe_redirect( remove_query_arg( $arg_key ) );
			exit;
		}
	}

}

endif;

return new WCJ_URL_Coupons();
