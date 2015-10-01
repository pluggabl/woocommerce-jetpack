<?php
/**
 * WooCommerce Jetpack Currency Exchange Rates
 *
 * The WooCommerce Jetpack Currency Exchange Rates class.
 *
 * @version 2.3.0
 * @since   2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Currency_Exchange_Rates' ) ) :

class WCJ_Currency_Exchange_Rates extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'currency_exchange_rates';
		$this->short_desc = __( 'Currency Exchange Rates', 'woocommerce-jetpack' );
		$this->desc       = __( 'Automatic currency exchange rates for WooCommerce.', 'woocommerce-jetpack' );
		parent::__construct();

		add_filter( 'init', array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			include_once( 'exchange-rates/class-wcj-exchange-rates-crons.php' );
		}
		include_once( 'exchange-rates/class-wcj-exchange-rates.php' );
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_currency_exchange_rates_settings', $settings );
		return $this->add_enable_module_setting( $settings );
	}

	/**
	 * add_hooks.
	 */
	function add_hooks() {
		add_filter( 'wcj_currency_exchange_rates_settings', array( $this, 'add_currency_exchange_rates_settings' ) );
	}

	/**
	 * add_currency_pair_setting.
	 */
	function add_currency_pair_setting( $currency_from, $currency_to, $settings ) {
		if ( $currency_from != $currency_to ) {
			$field_id = 'wcj_currency_exchange_rates_' . sanitize_title( $currency_from . $currency_to );
			foreach ( $settings as $setting ) {
				if ( $setting['id'] === $field_id ) return $settings;
			}
			$custom_attributes = array(
				'currency_from'        => $currency_from,
				'currency_to'          => $currency_to,
				'multiply_by_field_id' => $field_id,
			);
			$settings[] = array(
				'title'                    => $currency_from . ' / ' . $currency_to,
				'id'                       => $field_id,
				'default'                  => 0,
				'type'                     => 'exchange_rate',
				'custom_attributes'        => array( 'step' => '0.000001', 'min'  => '0', ),
				'custom_attributes_button' => $custom_attributes,
				'css'                      => 'width:100px;',
				'value'                    => $currency_from . '/' . $currency_to,
				'value_title'              => sprintf( __( 'Grab %s rate from Yahoo.com', 'woocommerce-jetpack' ), $currency_from . '/' . $currency_to ),
			);
		}
		return $settings;
	}

	/**
	 * add_currency_exchange_rates_settings.
	 */
	function add_currency_exchange_rates_settings() {

		$settings = array();

		$settings[] = array(
			'title' => __( 'Exchange Rates', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_currency_exchange_rates_options',
		);

		$settings[] = array(
			'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
			'id'       => 'wcj_currency_exchange_rates_auto',
			'default'  => 'daily',
			'type'     => 'select',
			'options'  => array(
//				'manual'     => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
				'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
				'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
				'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
				'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
				'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
			),
		);

		// Currency Pairs - Price by Country
		$currency_from = get_option( 'woocommerce_currency' );
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
			$currency_to   = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}
		// Currency Pairs - Gateway Currency
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			$currency_to   = get_option( 'wcj_gateways_currency_' . $key );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}

		$settings[] = array(
			'type'  => 'sectionend',
			'id'    => 'wcj_currency_exchange_rates_options',
		);

		return $settings;
	}
}

endif;

return new WCJ_Currency_Exchange_Rates();
