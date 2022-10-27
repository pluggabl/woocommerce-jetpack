<?php
/**
 * Booster for WooCommerce - Module - Empty Cart Button
 *
 * @version 5.6.7
 * @since   2.2.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Empty_Cart_Button' ) ) :
	/**
	 * WCJ_Empty_Cart_Button.
	 */
	class WCJ_Empty_Cart_Button extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.2.1
		 */
		public function __construct() {

			$this->id         = 'empty_cart';
			$this->short_desc = __( 'Empty Cart Button', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add (and customize) "Empty Cart" button to the cart and checkout pages. Customize empty cart button text (Plus). Different button positions on the cart page (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add (and customize) "Empty Cart" button to the cart and checkout pages.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-empty-cart-button';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'init', array( $this, 'maybe_empty_cart' ) );
				$empty_cart_cart_position = apply_filters(
					'booster_option',
					'woocommerce_after_cart',
					get_option( 'wcj_empty_cart_position', 'woocommerce_after_cart' )
				);
				if ( 'disable' !== ( $empty_cart_cart_position )
				) {
					add_action( $empty_cart_cart_position, array( $this, 'add_empty_cart_link' ) );
				}
				$empty_cart_checkout_position = wcj_get_option( 'wcj_empty_cart_checkout_position', 'disable' );
				if ( 'disable' !== ( $empty_cart_checkout_position ) ) {
					$deprecated_hooks = array(
						'woocommerce_checkout_before_customer_details' => 'woocommerce_before_checkout_form',
						'woocommerce_checkout_billing'  => 'woocommerce_before_checkout_form',
						'woocommerce_checkout_shipping' => 'woocommerce_before_checkout_form',
						'woocommerce_checkout_after_customer_details' => 'woocommerce_after_checkout_form',
						'woocommerce_checkout_before_order_review' => 'woocommerce_after_checkout_form',
						'woocommerce_checkout_order_review' => 'woocommerce_after_checkout_form',
						'woocommerce_checkout_after_order_review' => 'woocommerce_after_checkout_form',
					);
					if ( isset( $deprecated_hooks[ $empty_cart_checkout_position ] ) ) {
						$empty_cart_checkout_position = $deprecated_hooks[ $empty_cart_checkout_position ];
						update_option( 'wcj_empty_cart_checkout_position', $empty_cart_checkout_position );
					}
					add_action( $empty_cart_checkout_position, array( $this, 'add_empty_cart_link' ) );
				}
			}
		}

		/**
		 * Add_empty_cart_link.
		 *
		 * @version 2.8.0
		 */
		public function add_empty_cart_link() {
			echo wp_kses_post( wcj_empty_cart_button_html() );
		}

		/**
		 * Maybe_empty_cart.
		 *
		 * @version 5.6.7
		 */
		public function maybe_empty_cart() {
			$wpnonce = isset( $_REQUEST['wcj_empty_cart_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_empty_cart_nonce'] ), 'wcj-empty-cart' ) : false;

			if ( isset( $_POST['wcj_empty_cart'] ) && isset( WC()->cart ) && $wpnonce ) {
				WC()->cart->empty_cart();
			}
		}

	}

endif;

return new WCJ_Empty_Cart_Button();
