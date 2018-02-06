<?php
/**
 * Booster for WooCommerce - Module - Custom Shipping
 *
 * @version 3.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 * @todo    (maybe) deprecated "Custom Shipping (Legacy - without Shipping Zones)" - hide when disabled
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Custom Shipping', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-shipping';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Custom Shipping (Legacy - without Shipping Zones)
			add_action( 'init', array( $this, 'init_wc_shipping_wcj_custom_class' ) );

			// Custom Shipping
			if ( 'yes' === get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
				add_action( 'init', array( $this, 'init_wc_shipping_wcj_custom_w_zones_class' ) );
			}
		}
	}

	/*
	 * init_wc_shipping_wcj_custom_class.
	 *
	 * @version 3.4.0
	 * @since   2.4.8
	 */
	function init_wc_shipping_wcj_custom_class() {
		if ( class_exists( 'WC_Shipping_Method' ) ) {
			require_once( 'shipping/class-wc-shipping-wcj-custom.php' );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_class' ) );
		}
	}

	/*
	 * add_wc_shipping_wcj_custom_class.
	 *
	 * @version 2.8.0
	 */
	function add_wc_shipping_wcj_custom_class( $methods ) {
		$total_number = get_option( 'wcj_shipping_custom_shipping_total_number', 1 );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$the_method = new WC_Shipping_WCJ_Custom_Template();
			$the_method->init( $i );
			$methods[ $the_method->id ] = $the_method;
		}
		return $methods;
	}

	/*
	 * init_wc_shipping_wcj_custom_w_zones_class.
	 *
	 * @version 3.4.0
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
