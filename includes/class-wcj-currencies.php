<?php
/**
 * Booster for WooCommerce - Module - Currencies
 *
 * @version 3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currencies' ) ) :

class WCJ_Currencies extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 */
	function __construct() {

		$this->id         = 'currency';
		$this->short_desc = __( 'Currencies', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add all world currencies and cryptocurrencies to your WooCommerce store; change currency symbol.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-all-currencies';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_currencies',       array( $this, 'add_all_currencies'),              PHP_INT_MAX );
			add_filter( 'woocommerce_currency_symbol',  array( $this, 'change_currency_symbol'),          PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_general_settings', array( $this, 'add_edit_currency_symbol_field' ), PHP_INT_MAX );
		}
	}

	/**
	 * add_all_currencies - changing currency code.
	 *
	 * @version 2.4.4
	 */
	function add_all_currencies( $currencies ) {
		$currency_names = wcj_get_currencies_names_and_symbols( 'names' );
		foreach ( $currency_names as $currency_code => $currency_name ) {
			$currencies[ $currency_code ] = $currency_name;
		}
		asort( $currencies );
		return $currencies;
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.8.0
	 */
	function change_currency_symbol( $currency_symbol, $currency ) {
		return ( 'yes' === get_option( 'wcj_currency_hide_symbol', 'no' ) ? '' : wcj_get_currency_symbol( $currency ) );
	}

	/**
	 * add_edit_currency_symbol_field.
	 *
	 * @version 2.4.0
	 * @todo    (maybe) remove this
	 */
	function add_edit_currency_symbol_field( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'woocommerce_currency_pos' == $section['id'] ) {
				$updated_settings[] = array(
					'name'     => __( 'Booster: Currency Symbol', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'This sets the currency symbol.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_currency_' . get_woocommerce_currency(),
					'type'     => 'text',
					'default'  => get_woocommerce_currency_symbol(),
					'desc'     => apply_filters( 'booster_message', '', 'desc' ),
					'css'      => 'width: 50px;',
					'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

}

endif;

return new WCJ_Currencies();
