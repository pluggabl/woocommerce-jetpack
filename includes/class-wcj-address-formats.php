<?php
/**
 * WooCommerce Jetpack Address Formats
 *
 * The WooCommerce Jetpack Address Formats class.
 *
 * @version 2.4.8
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Address_Formats' ) ) :

class WCJ_Address_Formats extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	function __construct() {

		$this->id         = 'address_formats';
		$this->short_desc = __( 'Address Formats', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set address format in WooCommerce orders on per country basis. Force base country display.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-address-formats/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_localisation_address_formats',            array( $this, 'customize_address_formats' ), PHP_INT_MAX );
			add_filter( 'woocommerce_formatted_address_force_country_display', array( $this, 'customize_force_country_display' ), PHP_INT_MAX );
		}
	}

	/**
	 * customize_force_country_display.
	 */
	function customize_force_country_display( $display ) {
		if ( '' != ( $customized_display = get_option( 'wcj_address_formats_force_country_display', '' ) ) ) {
			return ( 'yes' === $customized_display ) ? true : false;
		}
		return $display;
	}

	/**
	 * customize_address_formats.
	 */
	function customize_address_formats( $formats ) {
		//$formats['LT'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{state}\n{country}";
		$modified_formats = array();
		$default_formats = $this->get_default_address_formats();
		foreach ( $default_formats as $country_code => $format ) {
			$default_format = isset( $formats[ $country_code ] ) ? $formats[ $country_code ] : $format;
			$format = get_option( 'wcj_address_formats_country_' . $country_code, $default_format );
			$modified_formats[ $country_code ] = $format;
		}
		return $modified_formats;
	}

	/**
	 * Get country address formats
	 * @return array
	 */
	public function get_default_address_formats() {

		// Common formats
		$postcode_before_city = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
		$default              = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";

		// Define address formats
		$formats = array(
			'default' => $default,
			'AU' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
			'AT' => $postcode_before_city,
			'BE' => $postcode_before_city,
			'CA' => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
			'CH' => $postcode_before_city,
			'CL' => "{company}\n{name}\n{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
			'CN' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
			'CZ' => $postcode_before_city,
			'DE' => $postcode_before_city,
			'EE' => $postcode_before_city,
			'FI' => $postcode_before_city,
			'DK' => $postcode_before_city,
			'FR' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
			'HK' => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
			'HU' => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
			'IN' => "{company}\n{name}\n{address_1}\n{address_2}\n{city} - {postcode}\n{state}, {country}",
			'IS' => $postcode_before_city,
			'IT' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
			'JP' => "{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}",
			'TW' => "{company}\n{last_name} {first_name}\n{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
			'LI' => $postcode_before_city,
			'NL' => $postcode_before_city,
			'NZ' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
			'NO' => $postcode_before_city,
			'PL' => $postcode_before_city,
			'SK' => $postcode_before_city,
			'SI' => $postcode_before_city,
			'ES' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
			'SE' => $postcode_before_city,
			'TR' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
			'US' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
			'VN' => "{name}\n{company}\n{address_1}\n{city}\n{country}",
		);

		$all_countries = wcj_get_countries();
		foreach ( $all_countries as $country_code => $country_name ) {
			if ( ! isset( $formats[ $country_code ] ) ) $formats[ $country_code ] = $default;
		}

		ksort( $formats );

		return $formats;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {

		$settings = array();

		// Force country display
		$settings[] = array(
			'title' => __( 'Force Base Country Display', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Force Base Country Display Options.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_address_formats_force_country_display_options'
		);
		$settings[] = array(
			'title'   => __( 'Force Base Country Display', 'woocommerce-jetpack' ),
			'id'      => 'wcj_address_formats_force_country_display',
			'default' => 'no',
			'type'    => 'checkbox',
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_address_formats_force_country_display_options'
		);

		// Formats by Country
		$settings[] = array(
			'title' => __( 'Address Formats by Country', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Address Formats by Country Options.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_address_formats_country_options'
		);
//		$formats = apply_filters( 'wcj_get_address_formats_filter', array() );
//		$formats = WC()->countries->get_address_formats();
		$formats = $this->get_default_address_formats();
		foreach ( $formats as $country_code => $format ) {
			$settings[] = array(
				'title'   => ( 'default' === $country_code ) ? $country_code : $country_code . ' - ' . wcj_get_country_name_by_code( $country_code ),
				'id'      => 'wcj_address_formats_country_' . $country_code,
				'default' => $format,
				'type'    => 'textarea',
				'css'     => 'width:300px;height:200px;',
			);
		}
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_address_formats_country_options'
		);

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Address_Formats();
