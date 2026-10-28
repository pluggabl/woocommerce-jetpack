<?php
/**
 * Booster for WooCommerce - Functions - EU VAT
 *
 * @version 5.6.8
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_validate_vat_no_soap' ) ) {
	/**
	 * Wcj_validate_vat_no_soap.
	 *
	 * @version 5.6.8
	 * @since   2.5.7
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 * @param   string $country_code defines the country_code.
	 * @param   int    $vat_number defines the vat_number.
	 * @param   string $method defines the method.
	 */
	function wcj_validate_vat_no_soap( $country_code, $vat_number, $method ) {
		$country_code = strtoupper( $country_code );
		$api_url      = esc_url_raw(
			add_query_arg(
				array(
					'requesterMemberStateCode' => $country_code,
					'requesterNumber'          => $vat_number,
				),
				'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/' . $country_code . '/vat/' . $vat_number
			)
		);
		switch ( $method ) {
			case 'file_get_contents':
				if ( ini_get( 'allow_url_fopen' ) ) {
					require_once ABSPATH . '/wp-admin/includes/file.php';
					global $wp_filesystem;
					WP_Filesystem();
					$response = $wp_filesystem->get_contents( $api_url );
				} else {
					return null;
				}
				break;
			default: // 'curl'
				if ( function_exists( 'wp_remote_get' ) ) {
					$response = wp_remote_get( $api_url );
					$response = $response['body'];
				} else {
					return null;
				}
				break;
		}
		if ( false === $response ) {
			return null;
		}
		$response = json_decode( $response );
		$is_valid = 'isValid';
		if ( $response->$is_valid ) {
			return true;
		} else {
			return null;
		}
	}
}

if ( ! function_exists( 'wcj_validate_vat_soap' ) ) {
	/**
	 * Wcj_validate_vat_soap.
	 *
	 * @version 4.7.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 * @param   string | int $country_code defines the country_code.
	 * @param   int          $vat_number defines the vat_number.
	 */
	function wcj_validate_vat_soap( $country_code, $vat_number ) {
		try {
			if ( class_exists( 'SoapClient' ) ) {
				$opts   = array(
					'http' => array(
						'user_agent' => 'PHPSoapClient',
					),
				);
				$client = new SoapClient(
					'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
					array(
						'exceptions'     => true,
						'stream_context' => stream_context_create( $opts ),
						'cache_wsdl'     => WSDL_CACHE_NONE,
					)
				);
				$result = $client->checkVat(
					array(
						'countryCode' => $country_code,
						'vatNumber'   => $vat_number,
					)
				);
				return ( isset( $result->valid ) ) ? $result->valid : null;
			} else {
				return null;
			}
		} catch ( Exception $exception ) {
			return null;
		}
	}
}

if ( ! function_exists( 'wcj_validate_vat_with_method' ) ) {
	/**
	 * Wcj_validate_vat_with_method.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 * @param   string | int $country_code defines the country_code.
	 * @param   int          $vat_number defines the vat_number.
	 * @param   string       $method defines the method.
	 */
	function wcj_validate_vat_with_method( $country_code, $vat_number, $method ) {
		switch ( $method ) {
			case 'soap':
				return wcj_validate_vat_soap( $country_code, $vat_number );
			default: // 'curl. file_get_contents.
				return wcj_validate_vat_no_soap( $country_code, $vat_number, $method );
		}
	}
}

if ( ! function_exists( 'wcj_validate_vat' ) ) {
	/**
	 * Wcj_validate_vat.
	 *
	 * @version 3.2.2
	 * @since   2.9.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 * @param   string | int $country_code defines the country_code.
	 * @param   int          $vat_number defines the vat_number.
	 */
	function wcj_validate_vat( $country_code, $vat_number ) {
		$skip_countries = wcj_get_option( 'wcj_eu_vat_number_advanced_skip_countries', 'no' );
		if ( '' !== ( $skip_countries ) ) {
			$skip_countries = array_map( 'trim', explode( ',', $skip_countries ) );
			$skip_countries = array_map( 'strtoupper', $skip_countries );
			if ( in_array( strtoupper( $country_code ), $skip_countries, true ) ) {
				return true;
			}
		}
		$methods = array();
		switch ( wcj_get_option( 'wcj_eu_vat_number_first_method', 'soap' ) ) {
			case 'curl':
				$methods = array( 'curl', 'file_get_contents', 'soap' );
				break;
			case 'file_get_contents':
				$methods = array( 'file_get_contents', 'curl', 'soap' );
				break;
			default: // 'soap'.
				$methods = array( 'soap', 'curl', 'file_get_contents' );
				break;
		}
		foreach ( $methods as $method ) {
			$result = wcj_validate_vat_with_method( $country_code, $vat_number, $method );
			if ( null !== ( $result ) ) {
				return $result;
			}
		}
		return null;
	}
}
