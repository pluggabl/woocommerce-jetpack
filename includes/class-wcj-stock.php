<?php
/**
 * Booster for WooCommerce - Module - Stock
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Stock' ) ) :

class WCJ_Stock extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @todo    (maybe) products_stock or product_stock
	 */
	function __construct() {

		$this->id         = 'stock';
		$this->short_desc = __( 'Stock', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products stock management.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-stock';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_section_enabled', 'no' ) ) {
				if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_text', array( $this, 'custom_out_of_stock' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_class_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_class', array( $this, 'custom_out_of_stock_class' ), PHP_INT_MAX, 2 );
				}
			}
		}
	}

	/**
	 * custom_out_of_stock_class.
	 *
	 * @version 2.8.0
	 * @version 2.8.0
	 */
	function custom_out_of_stock_class( $class, $_product ) {
		if ( ! $_product->is_in_stock() ) {
			return get_option( 'wcj_stock_custom_out_of_stock_class', '' );
		}
		return $class;
	}

	/**
	 * custom_out_of_stock.
	 *
	 * @version 2.8.0
	 * @version 2.8.0
	 * @todo    html tags in < WC3
	 */
	function custom_out_of_stock( $availability, $_product ) {
		if ( ! $_product->is_in_stock() ) {
			return get_option( 'wcj_stock_custom_out_of_stock', '' );
		}
		return $availability;
	}

}

endif;

return new WCJ_Stock();
