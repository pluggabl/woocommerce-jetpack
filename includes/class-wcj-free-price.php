<?php
/**
 * WooCommerce Jetpack Free Price
 *
 * The WooCommerce Jetpack Free Price class.
 *
 * @version 2.5.9
 * @since   2.5.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Free_Price' ) ) :

class WCJ_Free_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 * @todo    what's paid (if any)
	 * @todo    add views: single, related, homepage, pages, archives
	 */
	function __construct() {

		$this->id         = 'free_price';
		$this->short_desc = __( 'Free Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce free price.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-free-price/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_free_price_html',           array( $this, 'modify_free_price_simple_external_custom' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_grouped_free_price_html',   array( $this, 'modify_free_price_grouped' ),                PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_variable_free_price_html',  array( $this, 'modify_free_price_variable' ),               PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_variation_free_price_html', array( $this, 'modify_free_price_variation' ),              PHP_INT_MAX, 2 );
		}
	}

	/**
	 * modify_free_price_simple_external_custom.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_simple_external_custom( $price, $_product ) {
		return ( $_product->is_type( 'external' ) ) ?
			get_option( 'wcj_free_price_external', '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>' ) :
			get_option( 'wcj_free_price_simple',   '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>' );
	}

	/**
	 * modify_free_price_grouped.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_grouped( $price, $_product ) {
		return get_option( 'wcj_free_price_grouped', __( 'Free!', 'woocommerce' ) );
	}

	/**
	 * modify_free_price_variable.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_variable( $price, $_product ) {
		return get_option( 'wcj_free_price_variable', __( 'Free!', 'woocommerce' ) );
	}

	/**
	 * modify_free_price_variation.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_variation( $price, $_product ) {
		return get_option( 'wcj_free_price_variation', __( 'Free!', 'woocommerce' ) );
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function add_settings_hook() {
		add_filter( 'wcj_' . $this->id . '_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_' . $this->id . '_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function add_settings( $settings ) {
		$settings = array(
			array(
				'title'    => __( 'Free Price Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_free_price_options',
			),
			array(
				'title'    => __ ( 'Simple and Custom Products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_free_price_simple',
				'default'  => '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>',
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => __ ( 'Variable Products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_free_price_variable',
				'default'  => __( 'Free!', 'woocommerce' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => __ ( 'Variable Products - Variation', 'woocommerce-jetpack' ),
				'id'       => 'wcj_free_price_variation',
				'default'  => __( 'Free!', 'woocommerce' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => __ ( 'Grouped Products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_free_price_grouped',
				'default'  => __( 'Free!', 'woocommerce' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => __ ( 'External Products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_free_price_external',
				'default'  => '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>',
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_free_price_options',
			),
		);
		return $settings;
	}
}

endif;

return new WCJ_Free_Price();
