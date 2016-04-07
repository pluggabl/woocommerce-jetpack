<?php
/**
 * WooCommerce Jetpack Call for Price
 *
 * The WooCommerce Jetpack Call for Price class.
 *
 * @version 2.4.6
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

if ( ! class_exists( 'WCJ_Call_For_Price' ) ) :

class WCJ_Call_For_Price extends WCJ_module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.6
	 */
	public function __construct() {

		$this->id         = 'call_for_price';
		$this->short_desc = __( 'Call for Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Create any custom price label for all WooCommerce products with empty price.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-call-for-price/';
		parent::__construct();

		// Defaults
		$this->default_empty_price_text = '<strong>Call for price</strong>';

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'add_hook' ), PHP_INT_MAX );
//			add_filter( 'woocommerce_empty_price_html', array( $this, 'on_empty_price' ), PHP_INT_MAX );
			add_filter( 'woocommerce_sale_flash', array( $this, 'hide_sales_flash' ), 100, 3 );
		}
	}

	/**
	 * add_hook.
	 */
	public function add_hook() {
		add_filter( 'woocommerce_empty_price_html', array( $this, 'on_empty_price' ), PHP_INT_MAX );
	}

	/**
	 * Hide "sales" icon for empty price products.
	 */
	public function hide_sales_flash( $onsale_html, $post, $product ) {
		if ( get_option('wcj_call_for_price_hide_sale_sign') === 'yes' )
			if ( $product->get_price() === '' )
				return '';
		return $onsale_html;
	}

	/**
	 * On empty price filter - return the label.
	 */
	public function on_empty_price( $price ) {
		if ( ( get_option('wcj_call_for_price_text') !== '' ) && is_single( get_the_ID() ) )
			return do_shortcode( apply_filters( 'wcj_get_option_filter', $this->default_empty_price_text, get_option('wcj_call_for_price_text') ) );
		if ( ( get_option('wcj_call_for_price_text_on_related') !== '' ) && ( is_single() ) && ( ! is_single( get_the_ID() ) ) )
			return do_shortcode( apply_filters( 'wcj_get_option_filter', $this->default_empty_price_text, get_option('wcj_call_for_price_text_on_related') ) );
		if ( ( get_option('wcj_call_for_price_text_on_archive') !== '' ) && is_archive() )
			return do_shortcode( apply_filters( 'wcj_get_option_filter', $this->default_empty_price_text, get_option('wcj_call_for_price_text_on_archive') ) );
		if ( ( get_option('wcj_call_for_price_text_on_home') !== '' ) && is_front_page() )
			return do_shortcode( apply_filters( 'wcj_get_option_filter', $this->default_empty_price_text, get_option('wcj_call_for_price_text_on_home') ) );

		// No changes
		return $price;
	}

	/**
	 * Get settings array.
	 *
	 * @version 2.4.6
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'     => __( 'Call for Price Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'desc'      => __( 'Leave price empty when adding or editing products. Then set the options here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_call_for_price_options',
			),
			array(
				'title'     => __( 'Label to Show on Single', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'        => 'wcj_call_for_price_text',
				'default'   => $this->default_empty_price_text,
				'type'      => 'textarea',
				'css'       => 'width:50%;min-width:300px;',
				'custom_attributes'
				            => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Label to Show on Archives', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'        => 'wcj_call_for_price_text_on_archive',
				'default'   => $this->default_empty_price_text,
				'type'      => 'textarea',
				'css'       => 'width:50%;min-width:300px;',
				'custom_attributes'
				            => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Label to Show on Homepage', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'        => 'wcj_call_for_price_text_on_home',
				'default'   => $this->default_empty_price_text,
				'type'      => 'textarea',
				'css'       => 'width:50%;min-width:300px;',
				'custom_attributes'
				            => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Label to Show on Related', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'        => 'wcj_call_for_price_text_on_related',
				'default'   => $this->default_empty_price_text,
				'type'      => 'textarea',
				'css'       => 'width:50%;min-width:300px;',
				'custom_attributes'
				            => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			array(
				'title'     => __( 'Hide Sale! Tag', 'woocommerce-jetpack' ),
				'desc'      => __( 'Hide the tag', 'woocommerce-jetpack' ),
				'id'        => 'wcj_call_for_price_hide_sale_sign',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_call_for_price_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Call_For_Price();
