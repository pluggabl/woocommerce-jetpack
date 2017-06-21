<?php
/**
 * Booster for WooCommerce - Module - Custom Shipping
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
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
				include_once( 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php' );
			}
		}
	}

}

endif;

return new WCJ_Shipping();
