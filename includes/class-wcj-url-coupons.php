<?php
/**
 * Booster for WooCommerce - Module - URL Coupons
 *
 * @version 5.6.7
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_URL_Coupons' ) ) :
		/**
		 * WCJ_URL_Coupons.
		 *
		 * @version 5.2.0
		 * @since   2.9.1
		 */
	class WCJ_URL_Coupons extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.9.1
		 */
		public function __construct() {

			$this->id         = 'url_coupons';
			$this->short_desc = __( 'URL Coupons', 'woocommerce-jetpack' );
			$this->desc       = __( 'WooCommerce URL coupons. Redirect after coupon has been applied (Plus).', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-url-coupons';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'wp_loaded', array( $this, 'maybe_apply_url_coupon' ), PHP_INT_MAX );
			}
		}

		/**
		 * Get_redirect_url.
		 *
		 * @version 2.9.1
		 * @since   2.9.1
		 * @param string $arg_key Get arg_key.
		 */
		public function get_redirect_url( $arg_key ) {
			switch ( apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_url_coupons_redirect', 'no' ) ) ) {
				case 'cart':
					return wc_get_cart_url();
				case 'checkout':
					return wc_get_checkout_url();
				case 'custom':
					return wcj_get_option( 'wcj_url_coupons_redirect_custom_url', '' );
				default: // 'no'
					return esc_url( remove_query_arg( $arg_key ) );
			}
		}

		/**
		 * Maybe_add_products_to_cart.
		 *
		 * @version 3.6.0
		 * @since   2.9.1
		 * @todo    (maybe) check if coupon is valid
		 * @param string $coupon_code Get coupon code.
		 */
		public function maybe_add_products_to_cart( $coupon_code ) {
			if ( 'no' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_url_coupons_fixed_product_discount_add_products', 'no' ) ) ) {
				return false;
			}
			// Coupons are globally disabled.
			if ( ! wc_coupons_enabled() ) {
				return false;
			}
			// Sanitize coupon code.
			$coupon_code = wc_format_coupon_code( $coupon_code );
			// Get the coupon.
			$the_coupon = new WC_Coupon( $coupon_code );
			if ( 'fixed_product' === $the_coupon->get_discount_type() ) {
				$product_ids = $the_coupon->get_product_ids();
				if ( ! empty( $product_ids ) ) {
					foreach ( $product_ids as $product_id ) {
						if ( ! wcj_is_product_in_cart( $product_id ) ) {
							WC()->cart->add_to_cart( $product_id );
						}
					}
				}
			}
		}

		/**
		 * Maybe_apply_url_coupon.
		 *
		 * @version 5.6.7
		 * @since   2.7.0
		 * @todo    (maybe) options to add products to cart with query arg
		 * @todo    (maybe) if ( ! WC()->cart->has_discount( $coupon_code ) ) {}
		 */
		public function maybe_apply_url_coupon() {
			$arg_key = wcj_get_option( 'wcj_url_coupons_key', 'wcj_apply_coupon' );
			$_get    = array();
			if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
				return;
			}
			parse_str( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ), $_get );
			if ( isset( $_get[ $arg_key ] ) && '' !== $_get[ $arg_key ] ) {
				$coupon_code = sanitize_text_field( ( wp_unslash( $_get[ $arg_key ] ) ) );
				$this->maybe_add_products_to_cart( $coupon_code );
				WC()->cart->add_discount( $coupon_code );
				wp_safe_redirect( $this->get_redirect_url( $arg_key ) );
				exit;
			}
		}

	}

endif;

return new WCJ_URL_Coupons();
