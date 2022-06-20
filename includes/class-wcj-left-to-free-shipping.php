<?php
/**
 * Booster for WooCommerce - Module - Left to Free Shipping
 *
 * @version 2.8.0
 * @since   2.5.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Left_To_Free_Shipping' ) ) :
	/**
	 * WCJ_Left_To_Free_Shipping.
	 */
	class WCJ_Left_To_Free_Shipping extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 2.8.0
		 * @since   2.5.8
		 */
		public function __construct() {

			$this->id         = 'left_to_free_shipping';
			$this->short_desc = __( 'Left to Free Shipping', 'woocommerce-jetpack' );
			$this->desc       = __( 'Display "left to free shipping" info.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-left-to-free-shipping';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( 'yes' === wcj_get_option( 'wcj_shipping_left_to_free_info_enabled_cart', 'no' ) ) {
					add_action(
						get_option( 'wcj_shipping_left_to_free_info_position_cart', 'woocommerce_after_cart_totals' ),
						array( $this, 'show_left_to_free_shipping_info_cart' ),
						get_option( 'wcj_shipping_left_to_free_info_priority_cart', 10 )
					);
				}
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_shipping_left_to_free_info_enabled_mini_cart', 'no' ) ) ) {
					add_action(
						get_option( 'wcj_shipping_left_to_free_info_position_mini_cart', 'woocommerce_after_mini_cart' ),
						array( $this, 'show_left_to_free_shipping_info_mini_cart' ),
						get_option( 'wcj_shipping_left_to_free_info_priority_mini_cart', 10 )
					);
				}
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_shipping_left_to_free_info_enabled_checkout', 'no' ) ) ) {
					add_action(
						get_option( 'wcj_shipping_left_to_free_info_position_checkout', 'woocommerce_checkout_after_order_review' ),
						array( $this, 'show_left_to_free_shipping_info_checkout' ),
						get_option( 'wcj_shipping_left_to_free_info_priority_checkout', 10 )
					);
				}
			}
		}

		/**
		 * Show_left_to_free_shipping_info_checkout.
		 *
		 * @version 2.5.2
		 * @since   2.4.4
		 */
		public function show_left_to_free_shipping_info_checkout() {
			$this->show_left_to_free_shipping_info( do_shortcode( wcj_get_option( 'wcj_shipping_left_to_free_info_content_checkout', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
		}

		/**
		 * Show_left_to_free_shipping_info_mini_cart.
		 *
		 * @version 2.5.2
		 * @since   2.4.4
		 */
		public function show_left_to_free_shipping_info_mini_cart() {
			$this->show_left_to_free_shipping_info( do_shortcode( wcj_get_option( 'wcj_shipping_left_to_free_info_content_mini_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
		}

		/**
		 * Show_left_to_free_shipping_info_cart.
		 *
		 * @version 2.5.2
		 * @since   2.4.4
		 */
		public function show_left_to_free_shipping_info_cart() {
			$this->show_left_to_free_shipping_info( do_shortcode( wcj_get_option( 'wcj_shipping_left_to_free_info_content_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
		}

		/**
		 * Show_left_to_free_shipping_info.
		 *
		 * @version 2.4.4
		 * @since   2.4.4
		 * @param string | array $content defines the content.
		 */
		public function show_left_to_free_shipping_info( $content ) {
			echo wp_kses_post( wcj_get_left_to_free_shipping( $content ) );
		}

	}

endif;

return new WCJ_Left_To_Free_Shipping();
