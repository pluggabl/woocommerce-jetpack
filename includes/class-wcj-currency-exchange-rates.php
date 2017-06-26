<?php
/**
 * Booster for WooCommerce - Module - Currency Exchange Rates
 *
 * @version 2.9.0
 * @since   2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Currency_Exchange_Rates' ) ) :

class WCJ_Currency_Exchange_Rates extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'currency_exchange_rates';
		$this->short_desc = __( 'Currency Exchange Rates', 'woocommerce-jetpack' );
		$this->desc       = __( 'Automatic currency exchange rates for WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-currency-exchange-rates';
		parent::__construct();

		add_action( 'wp_ajax_'        . 'wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );
		add_action( 'wp_ajax_nopriv_' . 'wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );

		if ( $this->is_enabled() ) {
			include_once( 'exchange-rates/class-wcj-exchange-rates-crons.php' );
		}
		include_once( 'exchange-rates/class-wcj-exchange-rates.php' );
	}

	/**
	 * wcj_ajax_get_exchange_rates.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function wcj_ajax_get_exchange_rates() {
		echo alg_get_exchange_rate( $_POST['wcj_currency_from'], $_POST['wcj_currency_to'] );
		die();
	}

	/**
	 * add_currency_pair_setting.
	 *
	 * @version 2.6.0
	 */
	function add_currency_pair_setting( $currency_from, $currency_to, $settings ) {
		if ( $currency_from != $currency_to ) {
			$field_id = 'wcj_currency_exchange_rates_' . sanitize_title( $currency_from . $currency_to );
			foreach ( $settings as $setting ) {
				if ( $setting['id'] === $field_id ) {
					return $settings;
				}
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
			);
		}
		return $settings;
	}

	/**
	 * get_all_currencies_exchange_rates_currencies.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_all_currencies_exchange_rates_currencies() {
		$currencies = array();
		// Additional currencies (via filter)
		$additional_currencies = apply_filters( 'wcj_currency_exchange_rates_additional_currencies', array() );
		foreach ( $additional_currencies as $additional_currency ) {
			$currencies[] = $additional_currency;
		}
		// Additional currencies (via custom currencies section)
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_currency_exchange_custom_currencies_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'disabled' != ( $additional_currency = get_option( 'wcj_currency_exchange_custom_currencies_' . $i, 'disabled' ) ) ) {
				$currencies[] = $additional_currency;
			}
		}
		if ( wcj_is_module_enabled( 'price_by_country' ) ) {
			// Currency Pairs - Price by Country
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_price_by_country_auto_exchange_rates', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
					$currencies[] = $currency_to;
				}
			}
		}
		if ( wcj_is_module_enabled( 'multicurrency' ) ) {
			// Currency Pairs - Multicurrency
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_exchange_rate_update_auto', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_multicurrency_currency_' . $i );
					$currencies[] = $currency_to;
				}
			}
		}
		if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
			// Currency Pairs - Multicurrency Product Base Price
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_base_price_exchange_rate_update', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_multicurrency_base_price_currency_' . $i );
					$currencies[] = $currency_to;
				}
			}
		}
		if ( wcj_is_module_enabled( 'currency_per_product' ) ) {
			// Currency Pairs - Currency per Product
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_currency_per_product_exchange_rate_update', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_currency_per_product_currency_' . $i );
					$currencies[] = $currency_to;
				}
			}
		}
		if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_gateways_currency_exchange_rate_update_auto', 'manual' ) ) ) {
				// Currency Pairs - Gateway Currency
				global $woocommerce;
				$available_gateways = $woocommerce->payment_gateways->payment_gateways();
				foreach ( $available_gateways as $key => $gateway ) {
					$currency_to = get_option( 'wcj_gateways_currency_' . $key );
					if ( 'no_changes' != $currency_to ) {
						$currencies[] = $currency_to;
					}
				}
			}
		}
		return $currencies;
	}

	/**
	 * get_all_currencies_exchange_rates_settings.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_all_currencies_exchange_rates_settings() {
		$settings = array();
		$currency_from = get_option( 'woocommerce_currency' );
		$currencies = $this->get_all_currencies_exchange_rates_currencies();
		foreach ( $currencies as $currency ) {
			$settings = $this->add_currency_pair_setting( $currency_from, $currency, $settings );
		}
		return $settings;
	}

}

endif;

return new WCJ_Currency_Exchange_Rates();
