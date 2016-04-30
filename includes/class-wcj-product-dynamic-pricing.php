<?php
/**
 * WooCommerce Jetpack Product Dynamic Pricing
 *
 * The WooCommerce Jetpack Product Dynamic Pricing class.
 *
 * @version 2.4.8
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Dynamic_Pricing' ) ) :

class WCJ_Product_Dynamic_Pricing extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'product_dynamic_pricing';
		$this->short_desc = __( 'Product Dynamic Pricing', 'woocommerce-jetpack' );
		$this->desc       = __( 'Product Dynamic Pricing.', 'woocommerce-jetpack' );
		$this->link       = '';
		parent::__construct();

		if ( $this->is_enabled() ) {

		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_settings() {
		$settings = array();
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Dynamic_Pricing();
