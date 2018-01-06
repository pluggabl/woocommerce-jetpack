<?php
/**
 * Booster for WooCommerce - Module - Shipping Calculator
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Shipping_Calculator' ) ) :

class WCJ_Shipping_Calculator extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'shipping_calculator';
		$this->short_desc = __( 'Shipping Calculator', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce shipping calculator on cart page.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-calculator-customizer';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_shipping_calculator_enable_city' ,    array( $this, 'enable_city' ) );
			add_filter( 'woocommerce_shipping_calculator_enable_postcode', array( $this, 'enable_postcode' ) );
			add_action( 'wp_head',                                         array( $this, 'add_custom_styles' ) );
			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_shipping_calculator_labels_enabled', 'no' ) ) ) {
				add_action( 'wp_enqueue_scripts',                          array( $this, 'change_labels' ) );
			}
		}
	}

	/**
	 * change_labels.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	 function change_labels() {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_style(   'wcj-shipping-calculator',    wcj_plugin_url() . '/includes/css/wcj-shipping-calculator.css', array(),           WCJ()->version );
			wp_enqueue_script(  'wcj-shipping-calculator-js', wcj_plugin_url() . '/includes/js/wcj-shipping-calculator.js',   array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-shipping-calculator-js', 'alg_object', array(
				'calculate_shipping_label' => get_option( 'wcj_shipping_calculator_label_calculate_shipping', '' ),
				'update_totals_label'      => get_option( 'wcj_shipping_calculator_label_update_totals', '' ),
			) );
		}
	 }

	/**
	 * add_custom_styles.
	 *
	 * @version 2.8.0
	 */
	function add_custom_styles() {
		$html = '<style type="text/css">';
		if ( 'no' === get_option( 'wcj_shipping_calculator_enable_state' ) ) {
			$html .= '#calc_shipping_state { display: none !important; }';
		}
		if ( 'yes' === get_option( 'wcj_shipping_calculator_enable_force_block_open' ) ) {
			$html .= '.shipping-calculator-form { display: block !important; }';
			if ( 'hide' === get_option( 'wcj_shipping_calculator_enable_force_block_open_button' ) ) {
				$html .= 'a.shipping-calculator-button { display: none !important; }';
			} elseif ( 'noclick' === get_option( 'wcj_shipping_calculator_enable_force_block_open_button' ) ) {
				$html .= 'a.shipping-calculator-button { pointer-events: none; cursor: default; }';
			}
		}
		$html .= '</style>';
		echo $html;
	}

	/**
	 * enable_city.
	 *
	 * @version 2.8.0
	 */
	function enable_city() {
		return ( 'yes' === get_option( 'wcj_shipping_calculator_enable_city' ) );
	}

	/**
	 * enable_postcode.
	 *
	 * @version 2.8.0
	 */
	function enable_postcode() {
		return ( 'yes' === get_option( 'wcj_shipping_calculator_enable_postcode' ) );
	}

}

endif;

return new WCJ_Shipping_Calculator();
