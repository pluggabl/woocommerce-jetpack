<?php
/**
 * Booster for WooCommerce - Module - Shipping Time
 *
 * @version 3.4.6
 * @since   3.4.6
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_Time' ) ) :

class WCJ_Shipping_Time extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.6
	 * @since   3.4.6
	 */
	function __construct() {

		$this->id         = 'shipping_time';
		$this->short_desc = __( 'Shipping Time', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add delivery time estimation to WooCommerce shipping methods.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-time';
		parent::__construct();

	}

}

endif;

return new WCJ_Shipping_Time();
