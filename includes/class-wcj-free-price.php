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
	 * @todo    single in grouped is treated as "related"
	 */
	function __construct() {

		$this->id         = 'free_price';
		$this->short_desc = __( 'Free Price Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce free price labels.', 'woocommerce-jetpack' );
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
	 * get_view_id
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function get_view_id( $product_id ) {
		$view = 'single'; // default
		if ( is_single( $product_id ) ) {
			$view = 'single';
		} elseif ( is_single() ) {
			$view = 'related';
		} elseif ( is_front_page() ) {
			$view = 'home';
		} elseif ( is_page() ) {
			$view = 'page';
		} elseif ( is_archive() ) {
			$view = 'archive';
		}
		return $view;
	}

	/**
	 * modify_free_price_simple_external_custom.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_simple_external_custom( $price, $_product ) {
		$default = '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>';
		return ( $_product->is_type( 'external' ) ) ?
			do_shortcode( get_option( 'wcj_free_price_external_' . $this->get_view_id( $_product->id ), $default ) ) :
			do_shortcode( get_option( 'wcj_free_price_simple_'   . $this->get_view_id( $_product->id ), $default ) );
	}

	/**
	 * modify_free_price_grouped.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_grouped( $price, $_product ) {
		return do_shortcode( get_option( 'wcj_free_price_grouped_' . $this->get_view_id( $_product->id ), __( 'Free!', 'woocommerce' ) ) );
	}

	/**
	 * modify_free_price_variable.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_variable( $price, $_product ) {
		return do_shortcode( apply_filters( 'booster_get_option', __( 'Free!', 'woocommerce' ), get_option( 'wcj_free_price_variable_' . $this->get_view_id( $_product->id ), __( 'Free!', 'woocommerce' ) ) ) );
	}

	/**
	 * modify_free_price_variation.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function modify_free_price_variation( $price, $_product ) {
		return do_shortcode( apply_filters( 'booster_get_option', __( 'Free!', 'woocommerce' ), get_option( 'wcj_free_price_variable_variation', __( 'Free!', 'woocommerce' ) ) ) );
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
		return $this->add_standard_settings( $settings, __( 'Labels can contain shortcodes.', 'woocommerce-jetpack' ) );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.9
	 * @since   2.5.9
	 */
	function add_settings( $settings ) {
		$product_types = array(
			'simple'   => __( 'Simple and Custom Products', 'woocommerce-jetpack' ),
			'variable' => __( 'Variable Products', 'woocommerce-jetpack' ),
			'grouped'  => __( 'Grouped Products', 'woocommerce-jetpack' ),
			'external' => __( 'External Products', 'woocommerce-jetpack' ),
		);
		$views = array(
			'single'   => __( 'Single Product Page', 'woocommerce-jetpack' ),
			'related'  => __( 'Related Products', 'woocommerce-jetpack' ),
			'home'     => __( 'Homepage', 'woocommerce-jetpack' ),
			'page'     => __( 'Pages (e.g. Shortcodes)', 'woocommerce-jetpack' ),
			'archive'  => __( 'Archives (Product Categories)', 'woocommerce-jetpack' ),
		);
		$settings = array();
		foreach ( $product_types as $product_type => $product_type_desc ) {
			$default_value = ( 'simple' === $product_type || 'external' === $product_type ) ? '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>' : __( 'Free!', 'woocommerce' );
			$settings = array_merge( $settings, array(
				array(
					'title'    => $product_type_desc,
					'type'     => 'title',
					'id'       => 'wcj_free_price_' . $product_type . 'options',
				),
			) );
			$current_views = $views;
			if ( 'variable' === $product_type ) {
				$current_views['variation'] = __( 'Variations', 'woocommerce-jetpack' );
			}
			foreach ( $current_views as $view => $view_desc ) {
				$settings = array_merge( $settings, array(
					array(
						'title'    => $view_desc,
						'id'       => 'wcj_free_price_' . $product_type . '_' . $view,
						'default'  => $default_value,
						'type'     => 'textarea',
						'css'      => 'width:30%;min-width:300px;min-height:50px;',
						'desc'     => ( 'variable' === $product_type ) ? apply_filters( 'booster_get_message', '', 'desc' ) : '',
						'custom_attributes' => ( 'variable' === $product_type ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : '',

					),
				) );
			}
			$settings = array_merge( $settings, array(
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_free_price_' . $product_type . 'options',
				),
			) );
		}
		return $settings;
	}
}

endif;

return new WCJ_Free_Price();
