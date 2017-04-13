<?php
/**
 * WooCommerce Jetpack Stock
 *
 * The WooCommerce Jetpack Stock class.
 *
 * @version 2.7.2
 * @since   2.7.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Stock' ) ) :

class WCJ_Stock extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.2
	 * @since   2.7.2
	 * @todo    (maybe) products_stock or product_stock
	 */
	function __construct() {

		$this->id         = 'stock';
		$this->short_desc = __( 'Stock', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products stock management.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-stock/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_enabled', 'no' ) ) {
				add_filter( 'woocommerce_get_availability_text', array( $this, 'custom_out_of_stock' ), PHP_INT_MAX, 2 );
			}
			if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_class_enabled', 'no' ) ) {
				add_filter( 'woocommerce_get_availability_class', array( $this, 'custom_out_of_stock_class' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * custom_out_of_stock_class.
	 *
	 * @version 2.7.2
	 * @version 2.7.2
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
	 * @version 2.7.2
	 * @version 2.7.2
	 */
	function custom_out_of_stock( $availability, $_product ) {
		if ( ! $_product->is_in_stock() ) {
			return get_option( 'wcj_stock_custom_out_of_stock', '' );
		}
		return $availability;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.7.2
	 * @version 2.7.2
	 * @todo    get_stock_html: ( WCJ_IS_WC_VERSION_BELOW_3 ? `woocommerce_stock_html` : `woocommerce_get_stock_html` )
	 * @todo    apply_filters( 'woocommerce_stock_html', $html, $availability['availability'], $product );
	 * @todo    apply_filters( 'woocommerce_get_stock_html', $html, $product );
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_stock_options',
			),
			array(
				'title'    => __( 'Custom Out of Stock HTML', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_stock_custom_out_of_stock_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'id'       => 'wcj_stock_custom_out_of_stock',
				'default'  => '',
				'type'     => 'custom_textarea',
				'css'      => 'width:66%;min-width:300px;',
			),
			array(
				'title'    => __( 'Custom Out of Stock Class', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_stock_custom_out_of_stock_class_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'id'       => 'wcj_stock_custom_out_of_stock_class',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_stock_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}

}

endif;

return new WCJ_Stock();
