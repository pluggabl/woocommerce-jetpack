<?php
/**
 * Booster for WooCommerce - Module - Gateways Icons
 *
 * @version 2.8.0
 * @since   2.2.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Icons' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Payment_Gateways_Icons extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 2.8.0
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_icons';
			$this->short_desc = __( 'Gateways Icons', 'woocommerce-jetpack' );
			$this->desc       = __( 'Change or completely remove icons (images) for any (default or custom) payment gateway.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-icons';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_gateway_icon', array( $this, 'set_icon' ), PHP_INT_MAX, 2 );

				// Compatibility with 2.3.0 or below.
				$default_gateways = array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' );
				foreach ( $default_gateways as $key ) {
					$deprecated_option = wcj_get_option( 'wcj_payment_gateways_icons_woocommerce_' . $key . '_icon', '' );
					if ( '' !== $deprecated_option ) {
						update_option( 'wcj_gateways_icons_' . $key . '_icon', $deprecated_option );
						delete_option( 'wcj_payment_gateways_icons_woocommerce_' . $key . '_icon' );
					}
				}
			}
		}

		/**
		 * Set_icon.
		 *
		 * @version 2.3.1
		 * @param string $icon defines the icon.
		 * @param string $key defines the key.
		 */
		public function set_icon( $icon, $key ) {
			$default_gateways = apply_filters( 'booster_option', array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' ), array() );
			if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
				return $icon;
			}
			if ( 'yes' === wcj_get_option( 'wcj_gateways_icons_' . $key . '_icon_remove', 'no' ) ) {
				return '';
			}
			$custom_icon_url = wcj_get_option( 'wcj_gateways_icons_' . $key . '_icon', '' );
			return ( '' === $custom_icon_url ) ? $icon : '<img src="' . $custom_icon_url . '" alt="' . $key . '" />';
		}

	}

endif;

return new WCJ_Payment_Gateways_Icons();
