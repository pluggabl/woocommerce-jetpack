<?php
/**
 * Booster for WooCommerce - Module - Shipping Time
 *
 * @version 3.5.0
 * @since   3.5.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_Time' ) ) :

class WCJ_Shipping_Time extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function __construct() {

		$this->id         = 'shipping_time';
		$this->short_desc = __( 'Shipping Time', 'woocommerce-jetpack' );
		$this->extra_desc = sprintf( __( 'After you set estimated shipping time here, you can display it on frontend with %s shortcodes.', 'woocommerce-jetpack' ),
			'<code>[wcj_shipping_time_table]</code>, <code>[wcj_product_shipping_time_table]</code>' );
		$this->desc       = __( 'Add delivery time estimation to shipping methods.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-time';
		parent::__construct();

	}

}

endif;

return new WCJ_Shipping_Time();
