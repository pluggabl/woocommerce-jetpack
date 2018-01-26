<?php
/**
 * Booster for WooCommerce - Module - Custom Shipping
 *
 * @version 3.3.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.1
	 * @todo    (maybe) rewrite `shipping/class-wc-shipping-wcj-custom.php` (same as `shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php`)
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Custom Shipping', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-shipping';
		parent::__construct();

		if ( $this->is_enabled() ) {
			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );
			if ( 'yes' === get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
				add_action( 'init', array( $this, 'init_wc_shipping_wcj_custom_w_zones_class' ) );
			}
		}
	}

	/*
	 * init_wc_shipping_wcj_custom_w_zones_class.
	 *
	 * @version 3.3.1
	 * @since   2.5.6
	 */
	function init_wc_shipping_wcj_custom_w_zones_class() {
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			require_once( 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php' );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_w_zones_class' ) );
		}
	}

	/*
	 * add_wc_shipping_wcj_custom_w_zones_class.
	 *
	 * @version 2.5.6
	 * @since   2.5.6
	 */
	function add_wc_shipping_wcj_custom_w_zones_class( $methods ) {
		$methods[ 'booster_custom_shipping_w_zones' ] = 'WC_Shipping_WCJ_Custom_W_Zones';
		return $methods;
	}

}

endif;

return new WCJ_Shipping();
