<?php
/**
 * Booster for WooCommerce - Module - Currency Exchange Rates
 *
 * @version 2.8.0
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

}

endif;

return new WCJ_Currency_Exchange_Rates();
