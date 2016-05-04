<?php
/**
 * WooCommerce Jetpack currencies
 *
 * The WooCommerce Jetpack currencies class stores currencies data.
 *
 * @version 2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currencies' ) ) :

class WCJ_Currencies extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	function __construct() {

		$this->id         = 'currency';
		$this->short_desc = __( 'Currencies', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add all world currencies to your WooCommerce store; change currency symbol.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-all-currencies/';
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
	 * @version 2.4.4
	 */
	function change_currency_symbol( $currency_symbol, $currency ) {
		if ( 'yes' === get_option( 'wcj_currency_hide_symbol' ) ) {
			return '';
		}
//		return apply_filters( 'wcj_get_option_filter', wcj_get_currency_symbol( $currency ), get_option( 'wcj_currency_' . $currency, $currency_symbol ) ); // TODO: custom currency
		return wcj_get_currency_symbol( $currency );
	}

	/**
	 * add_edit_currency_symbol_field.
	 *
	 * @version 2.4.0
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
					'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
					'css'      => 'width: 50px;',
					'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.4
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'     => __( 'Currency Symbol Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_all_currencies_list_options',
			),

			array(
				'title'     => __( 'Hide Currency Symbol', 'woocommerce-jetpack' ),
				'desc'      => __( 'Hide', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Default: no.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_currency_hide_symbol',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
		);

		$currency_names   = wcj_get_currencies_names_and_symbols( 'names',   'no_custom' );
		$currency_symbols = wcj_get_currencies_names_and_symbols( 'symbols', 'no_custom' );
		foreach ( $currency_names as $currency_code => $currency_name ) {
			$settings[] = array(
				'title'     => $currency_name,
				'desc_tip'  => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
				'id'        => 'wcj_currency_' . $currency_code,
				'default'   => $currency_symbols[ $currency_code ],
				'type'      => 'text',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			);
		}

		$settings[] = array(
				'type'      => 'sectionend',
				'id'        => 'wcj_all_currencies_list_options',
		);

		$settings[] = array(
				'title'     => __( 'Custom Currencies', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_currency_custom_currency_options',
		);

		$settings[] = array(
				'title'     => __( 'Total Custom Currencies', 'woocommerce-jetpack' ),
				'id'        => 'wcj_currency_custom_currency_total_number',
				'default'   => 1,
				'type'      => 'custom_number',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
		);

		$custom_currency_total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
		for ( $i = 1; $i <= $custom_currency_total_number; $i++) {

			$settings[] = array(
				'title'     => __( 'Custom Currency', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'      => __( 'Currency Name (required)', 'woocommerce-jetpack' ),
				'id'        => 'wcj_currency_custom_currency_name_' . $i,
				'default'   => '',
				'type'      => 'text',
			);

			$settings[] = array(
				'title'     => '',
				'desc'      => __( 'Currency Code (required)', 'woocommerce-jetpack' ),
				'id'        => 'wcj_currency_custom_currency_code_' . $i,
				'default'   => '',
				'type'      => 'text',
			);

			$settings[] = array(
				'title'     => '',
				'desc'      => __( 'Currency Symbol', 'woocommerce-jetpack' ),
				'id'        => 'wcj_currency_custom_currency_symbol_' . $i,
				'default'   => '',
				'type'      => 'text',
			);
		}

		$settings[] = array(
				'type'      => 'sectionend',
				'id'        => 'wcj_currency_custom_currency_options',
		);

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Currencies();
