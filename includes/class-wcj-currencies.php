<?php
/**
 * WooCommerce Jetpack currencies
 *
 * The WooCommerce Jetpack currencies class stores currencies data.
 *
 * @class 		WCJ_Currencies
 * @package		WC_Jetpack/Classes
 * @category	Class
 * @author 		Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currencies' ) ) :

class WCJ_Currencies {

	public function __construct() {

		$currencies = include( 'currencies/wcj-currencies.php' );
		foreach( $currencies as $data ) {
			$this->currency_symbols[ $data['code'] ]  = $data['symbol'];
			$this->currency_names[ $data['code'] ]    = $data['name'];
			//$this->currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
		}

		// Hooks
		if ( get_option( 'wcj_currency_enabled' ) == 'yes') {
			// Main hooks
			add_filter( 'woocommerce_currencies',       array( $this, 'add_all_currencies'), 100 );
			add_filter( 'woocommerce_currency_symbol',  array( $this, 'add_currency_symbol'), 100, 2 );
			// Settings
			add_filter( 'woocommerce_general_settings', array( $this, 'add_edit_currency_symbol_field' ), 100 );
		}

		// Settings
		add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
		add_filter( 'wcj_settings_currency', array( $this, 'get_settings' ), 100 );
		add_filter( 'wcj_features_status',   array( $this, 'add_enabled_option' ), 100 );
	}

	/**
	 * add_all_currencies.
	 */
	function add_all_currencies( $currencies ) {
		foreach ( $this->currency_names as $currency_code => $currency_name )
			$currencies[ $currency_code ] = $currency_name;
		asort( $currencies );
		return $currencies;
	}

	/**
	 * add_currency_symbol.
	 */
	function add_currency_symbol( $currency_symbol, $currency ) {
		if ( 'yes' === get_option( 'wcj_currency_hide_symbol' ) )
			return '';
		$default = ( isset( $this->currency_symbols[ $currency ] ) ) ? $this->currency_symbols[ $currency ] : $currency_symbol;
		return apply_filters( 'wcj_get_option_filter', $default, get_option( 'wcj_currency_' . $currency, $currency_symbol ) );
	}

	/**
	 * add_edit_currency_symbol_field.
	 */
	function add_edit_currency_symbol_field( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {

			if ( isset( $section['id'] ) && 'woocommerce_currency_pos' == $section['id'] ) {

				$updated_settings[] = array(
					'name'		=> __( 'Currency Symbol', 'woocommerce-jetpack' ), //TODO name or title?????
					'desc_tip'	=> __( 'This sets the currency symbol.', 'woocommerce-jetpack' ),
					'id'		=> 'wcj_currency_' . get_woocommerce_currency(),
					'type'		=> 'text',
					'default'	=> get_woocommerce_currency_symbol(),
					'desc'		=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
					'css'		=> 'width: 50px;',
					'custom_attributes'
								=> apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				);
			}

			$updated_settings[] = $section;
		}

		return $updated_settings;
	}

	/**
	 * add_enabled_option.
	 */
	public function add_enabled_option( $settings ) {
		$all_settings = $this->get_settings();
		$settings[] = $all_settings[1];
		return $settings;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array( 'title'	=> __( 'Currencies Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_currency_options' ),

			array(
				'title' 	=> __( 'Currencies', 'woocommerce-jetpack' ),
				'desc' 		=> '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip'	=> __( 'Add all world currencies to your WooCommerce store; change currency symbol.', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_currency_enabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox'
			),

			array( 'type' 	=> 'sectionend', 'id' => 'wcj_currency_options' ),

			array( 'title' 	=> __( 'Currency Symbol Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_all_currencies_list_options' ),

			array(
				'title' 	=> __( 'Hide Currency Symbol', 'woocommerce-jetpack' ),
				'desc' 		=> __( 'Hide', 'woocommerce-jetpack' ),
				'desc_tip'	=> __( 'Default: no.', 'woocommerce-jetpack' ),
				'id' 		=> 'wcj_currency_hide_symbol',
				'default'	=> 'no',
				'type' 		=> 'checkbox'
			),
		);

		foreach ( $this->currency_names as $currency_code => $currency_name )
			$settings[] = array(
				'title' 	=> $currency_name,
				'desc_tip' 	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
				'id' 		=> 'wcj_currency_' . $currency_code,
				'default'	=> $this->currency_symbols[ $currency_code ],
				'type' 		=> 'text',
				'custom_attributes'
							=> apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			);

		$settings[] = array( 'type' => 'sectionend', 'id' => 'wcj_all_currencies_list_options' );

		return $settings;
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		$sections['currency'] = __( 'Currencies', 'woocommerce-jetpack' );
		return $sections;
	}
}

endif;

return new WCJ_Currencies();
