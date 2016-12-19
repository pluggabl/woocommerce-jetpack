<?php
/**
 * WooCommerce Jetpack Shipping Calculator
 *
 * The WooCommerce Jetpack Shipping Calculator class.
 *
 * @version 2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Shipping_Calculator' ) ) :

class WCJ_Shipping_Calculator extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 */
	public function __construct() {

		$this->id         = 'shipping_calculator';
		$this->short_desc = __( 'Shipping Calculator', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce shipping calculator on cart page.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-shipping-calculator-customizer/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_shipping_calculator_enable_city' ,    array( $this, 'enable_city' ) );
			add_filter( 'woocommerce_shipping_calculator_enable_postcode', array( $this, 'enable_postcode' ) );
			add_action( 'wp_head',                                         array( $this, 'add_custom_styles' ) );
//			add_filter( 'gettext',                                         array( $this, 'change_labels' ), 20, 3 );
			if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_shipping_calculator_labels_enabled', 'no' ) ) ) {
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
	 * change_labels.
	 */
	/* public function change_labels( $translated_text, $text, $domain ) {

		if ( ! function_exists( 'is_cart' ) || ! is_cart() )
			return $translated_text;

		if ( 'Update Totals' === $text ) {
			$the_label = get_option( 'wcj_shipping_calculator_label_update_totals' );
			if ( '' != $the_label )
				return $the_label;
		}
		elseif ( 'Calculate Shipping' === $text ) {
			$the_label = get_option( 'wcj_shipping_calculator_label_calculate_shipping' );
			if ( '' != $the_label )
				return $the_label;
		}

		return $translated_text;
	} */

	/**
	 * add_custom_styles.
	 */
	public function add_custom_styles() {

		$html = '<style type="text/css">';

		if ( 'no' === get_option( 'wcj_shipping_calculator_enable_state' ) ) {
			$html .= '#calc_shipping_state { display: none !important; }';
		}

		if ( 'yes' === get_option( 'wcj_shipping_calculator_enable_force_block_open' ) ) {

			$html .= '.shipping-calculator-form { display: block !important; }';

			if ( 'hide' === get_option( 'wcj_shipping_calculator_enable_force_block_open_button' ) )
				$html .= 'a.shipping-calculator-button { display: none !important; }';
			else if ( 'noclick' === get_option( 'wcj_shipping_calculator_enable_force_block_open_button' ) )
				$html .= 'a.shipping-calculator-button { pointer-events: none; cursor: default; }';
		}

		$html .= '</style>';

		echo $html;
	}

	/**
	 * enable_city.
	 */
	public function enable_city() {
		return ( 'yes' === get_option( 'wcj_shipping_calculator_enable_city' ) ) ? true : false;
	}

	/**
	 * enable_postcode.
	 */
	public function enable_postcode() {
		return ( 'yes' === get_option( 'wcj_shipping_calculator_enable_postcode' ) ) ? true : false;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Shipping Calculator Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_calculator_options',
			),
			array(
				'title'    => __( 'Enable City', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_enable_city',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Enable Postcode', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_enable_postcode',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Enable State', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_enable_state',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Force Block Open', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_enable_force_block_open',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( '', 'woocommerce-jetpack' ),
				'desc'     => __( 'Calculate Shipping button', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'When "Force Block Open" options is enabled, set Calculate Shipping button options.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_enable_force_block_open_button',
				'default'  => 'hide',
				'type'     => 'select',
				'options'  => array(
					'hide'    => __( 'Hide', 'woocommerce-jetpack' ),
					'noclick' => __( 'Make non clickable', 'woocommerce-jetpack' ),
				),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_calculator_options',
			),
			array(
				'title'    => __( 'Labels Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_calculator_labels_options',
			),
			array(
				'title'    => __( 'Labels', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_labels_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Label for Calculate Shipping', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_label_calculate_shipping',
				'default'  => __( 'Calculate Shipping', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
			),
			array(
				'title'    => __( 'Label for Update Totals', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_calculator_label_update_totals',
				'default'  => __( 'Update Totals', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_calculator_labels_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Shipping_Calculator();
