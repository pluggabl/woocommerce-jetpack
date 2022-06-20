<?php
/**
 * Booster for WooCommerce - Module - Address Formats
 *
 * @version 2.8.0
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Address_Formats' ) ) :

	/**
	 * WCJ_Address_Formats.
	 *
	 * @version 2.8.0
	 */
	class WCJ_Address_Formats extends WCJ_Module {


		/**
		 * Constructor.
		 *
		 * @version 2.8.0
		 */
		public function __construct() {
			$this->id         = 'address_formats';
			$this->short_desc = __( 'Address Formats', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set address format in orders on per country basis. Force base country display.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-address-formats';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_localisation_address_formats', array( $this, 'customize_address_formats' ), PHP_INT_MAX );
				add_filter( 'woocommerce_formatted_address_force_country_display', array( $this, 'customize_force_country_display' ), PHP_INT_MAX );
			}
		}

		/**
		 * Customize_force_country_display.
		 *
		 * @param  int|bool $display  Optional. Whether to use GMT timezone. Default false.
		 * @version 2.7.0
		 */
		public function customize_force_country_display( $display ) {
			return ( 'yes' === wcj_get_option( 'wcj_address_formats_force_country_display', 'no' ) );
		}

		/**
		 * Customize_address_formats.
		 *
		 * @param int|bool $formats  Optional. Whether to use GMT timezone. Default false.
		 */
		public function customize_address_formats( $formats ) {
			$modified_formats = array();
			$default_formats  = $this->get_default_address_formats();
			foreach ( $default_formats as $country_code => $format ) {
				$default_format                    = isset( $formats[ $country_code ] ) ? $formats[ $country_code ] : $format;
				$format                            = wcj_get_option( 'wcj_address_formats_country_' . $country_code, $default_format );
				$modified_formats[ $country_code ] = $format;
			}
			return $modified_formats;
		}

		/**
		 * Get country address formats.
		 *
		 * @return array
		 */
		public function get_default_address_formats() {
			// Common formats.
			$postcode_before_city = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
			$default              = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
			// Define address formats.
			$formats       = array(
				'default' => $default,
				'AU'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
				'AT'      => $postcode_before_city,
				'BE'      => $postcode_before_city,
				'CA'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
				'CH'      => $postcode_before_city,
				'CL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
				'CN'      => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
				'CZ'      => $postcode_before_city,
				'DE'      => $postcode_before_city,
				'EE'      => $postcode_before_city,
				'FI'      => $postcode_before_city,
				'DK'      => $postcode_before_city,
				'FR'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
				'HK'      => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
				'HU'      => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
				'IN'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} - {postcode}\n{state}, {country}",
				'IS'      => $postcode_before_city,
				'IT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
				'JP'      => "{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}",
				'TW'      => "{company}\n{last_name} {first_name}\n{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
				'LI'      => $postcode_before_city,
				'NL'      => $postcode_before_city,
				'NZ'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
				'NO'      => $postcode_before_city,
				'PL'      => $postcode_before_city,
				'SK'      => $postcode_before_city,
				'SI'      => $postcode_before_city,
				'ES'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
				'SE'      => $postcode_before_city,
				'TR'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
				'US'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
				'VN'      => "{name}\n{company}\n{address_1}\n{city}\n{country}",
			);
			$all_countries = wcj_get_countries();
			foreach ( $all_countries as $country_code => $country_name ) {
				if ( ! isset( $formats[ $country_code ] ) ) {
					$formats[ $country_code ] = $default;
				}
			}
			ksort( $formats );
			return $formats;
		}
	}

endif;

return new WCJ_Address_Formats();
