<?php
/**
 * Booster for WooCommerce - Functions - EU VAT
 *
 * @version 3.2.2
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_validate_vat_no_soap' ) ) {
	/**
	 * wcj_validate_vat_no_soap.
	 *
	 * @version 2.9.0
	 * @since   2.5.7
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function wcj_validate_vat_no_soap( $country_code, $vat_number, $method ) {
		$country_code = strtoupper( $country_code );
		$api_url = "http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms=" . $country_code . "&vat=" . $vat_number;
		switch ( $method ) {
			case 'file_get_contents':
				if ( ini_get( 'allow_url_fopen' ) ) {
					$response = file_get_contents( $api_url );
				} else {
					return null;
				}
				break;
			default: // 'curl'
				if ( function_exists( 'curl_version' ) ) {
					$curl = curl_init( $api_url );
					curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
					$response = curl_exec( $curl );
					curl_close( $curl );
				} else {
					return null;
				}
				break;
		}
		if ( false === $response ) {
			return null;
		}
		return ( false !== strpos( $response, '="validStyle"' ) );
	}
}

if ( ! function_exists( 'wcj_validate_vat_soap' ) ) {
	/**
	 * wcj_validate_vat_soap.
	 *
	 * @version 2.9.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function wcj_validate_vat_soap( $country_code, $vat_number ) {
		try {
			if ( class_exists( 'SoapClient' ) ) {
				$client = new SoapClient(
					'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
					array( 'exceptions' => true )
				);
				$result = $client->checkVat( array(
					'countryCode' => $country_code,
					'vatNumber'   => $vat_number,
				) );
				return ( isset( $result->valid ) ) ? $result->valid : null;
			} else {
				return null;
			}
		} catch( Exception $exception ) {
			return null;
		}
	}
}

if ( ! function_exists( 'wcj_validate_vat_with_method' ) ) {
	/**
	 * wcj_validate_vat_with_method.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function wcj_validate_vat_with_method( $country_code, $vat_number, $method ) {
		switch ( $method ) {
			case 'soap':
				return wcj_validate_vat_soap( $country_code, $vat_number );
			default: // 'curl', 'file_get_contents'
				return wcj_validate_vat_no_soap( $country_code, $vat_number, $method );
		}
	}
}

if ( ! function_exists( 'wcj_validate_vat' ) ) {
	/**
	 * wcj_validate_vat.
	 *
	 * @version 3.2.2
	 * @since   2.9.0
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function wcj_validate_vat( $country_code, $vat_number ) {
		if ( '' != ( $skip_countries = get_option( 'wcj_eu_vat_number_advanced_skip_countries', array() ) ) ) {
			$skip_countries = array_map( 'trim', explode( ',', $skip_countries ) );
			$skip_countries = array_map( 'strtoupper', $skip_countries );
			if ( in_array( strtoupper( $country_code ), $skip_countries ) ) {
				return true;
			}
		}
		$methods = array();
		switch ( get_option( 'wcj_eu_vat_number_first_method', 'soap' ) ) {
			case 'curl':
				$methods = array( 'curl', 'file_get_contents', 'soap' );
				break;
			case 'file_get_contents':
				$methods = array( 'file_get_contents', 'curl', 'soap' );
				break;
			default: // 'soap'
				$methods = array( 'soap', 'curl', 'file_get_contents' );
				break;
		}
		foreach ( $methods as $method ) {
			if ( null !== ( $result = wcj_validate_vat_with_method( $country_code, $vat_number, $method ) ) ) {
				return $result;
			}
		}
		return null;
	}
}
