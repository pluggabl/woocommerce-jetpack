<?php
/**
 * WooCommerce Jetpack Payment Gateways by Country
 *
 * The WooCommerce Jetpack Payment Gateways by Country class.
 *
 * @version 2.4.1
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Country' ) ) :

class WCJ_Payment_Gateways_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'payment_gateways_by_country';
		$this->short_desc = __( 'Gateways by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set countries to include/exclude for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		parent::__construct();

		add_filter( 'init', array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 */
	function available_payment_gateways( $_available_gateways ) {
		foreach ( $_available_gateways as $key => $gateway ) {
			$customer_country = WC()->customer->get_country();
			$include_countries = get_option( 'wcj_gateways_countries_include_' . $key, '' );
			if ( ! empty( $include_countries ) && ! in_array( $customer_country, $include_countries ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
			$exclude_countries = get_option( 'wcj_gateways_countries_exclude_' . $key, '' );
			if ( ! empty( $exclude_countries ) && in_array( $customer_country, $exclude_countries ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
		}
		return $_available_gateways;
	}

	/**
	 * add_hooks.
	 */
	function add_hooks() {
		add_filter( 'wcj_payment_gateways_by_country_settings', array( $this, 'add_countries_settings' ) );
	}

	/**
	 * add_countries_settings.
	 */
	function add_countries_settings( $settings ) {
		$settings = array();
		$settings[] = array(
			'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_payment_gateways_by_country_gateways_options',
		);
		$countries = wcj_get_countries();
		$gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $gateways as $key => $gateway ) {
			$default_gateways = array( 'bacs' );
			if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
				$custom_attributes = apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' );
				if ( '' == $custom_attributes ) {
					$custom_attributes = array();
				}
				$desc_tip = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' );
			} else {
				$custom_attributes = array();
				$desc_tip = '';
			}
			$settings[] = array(
				'title'     => $gateway->title,
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Include', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_countries_include_' . $key,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $countries,
				'custom_attributes' => $custom_attributes,
			);
			$settings[] = array(
				'title'     => '',
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Exclude', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_countries_exclude_' . $key,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $countries,
				'custom_attributes' => $custom_attributes,
			);
		}
		$settings[] = array(
			'type'  => 'sectionend',
			'id'    => 'wcj_payment_gateways_by_country_gateways_options',
		);
		return $settings;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_payment_gateways_by_country_settings', $settings );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways_By_Country();
