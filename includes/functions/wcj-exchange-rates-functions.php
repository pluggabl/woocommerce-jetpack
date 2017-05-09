<?php
/**
 * Booster for WooCommerce - Functions - Exchange Rates
 *
 * @version 2.7.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_currency_exchange_rate_servers' ) ) {
	/**
	 * wcj_get_currency_exchange_rate_servers.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_get_currency_exchange_rate_servers() {
		return array(
			'yahoo' => __( 'Yahoo', 'woocommerce-jetpack' ),
			'ecb'   => __( 'European Central Bank (ECB)', 'woocommerce-jetpack' ),
			'tcmb'  => __( 'TCMB', 'woocommerce-jetpack' ),
		);
	}
}

if ( ! function_exists( 'alg_get_exchange_rate' ) ) {
	/*
	 * alg_get_exchange_rate.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function alg_get_exchange_rate( $currency_from, $currency_to ) {
		if ( 'yes' === ( $calculate_by_invert = get_option( 'wcj_currency_exchange_rates_calculate_by_invert', 'no' ) ) ) {
			$_currency_to  = $currency_to;
			$currency_to   = $currency_from;
			$currency_from = $_currency_to;
		}
		$exchange_rates_server = get_option( 'wcj_currency_exchange_rates_server', 'yahoo' );
		switch ( $exchange_rates_server ) {
			case 'tcmb':
				$return = alg_tcmb_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'ecb':
				$return = alg_ecb_get_exchange_rate( $currency_from, $currency_to );
				break;
			default: // 'yahoo'
				$return = alg_yahoo_get_exchange_rate( $currency_from, $currency_to );
				break;
		}
		return ( 'yes' === $calculate_by_invert ) ? round( ( 1 / $return ), 6 ) : $return;
	}
}

if ( ! function_exists( 'alg_ecb_get_exchange_rate' ) ) {
	/*
	 * alg_ecb_get_exchange_rate.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function alg_ecb_get_exchange_rate( $currency_from, $currency_to ) {
		$final_rate = false;
		if ( function_exists( 'simplexml_load_file' ) ) {
			$xml = simplexml_load_file( 'http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml' );
			if ( isset( $xml->Cube->Cube->Cube ) ) {
				if ( 'EUR' === $currency_from ) {
					$EUR_currency_from_rate = 1;
				}
				if ( 'EUR' === $currency_to ) {
					$EUR_currency_to_rate = 1;
				}
				foreach ( $xml->Cube->Cube->Cube as $currency_rate ) {
					$currency_rate = $currency_rate->attributes();
					if ( ! isset( $EUR_currency_from_rate ) && $currency_from == $currency_rate->currency ) {
						$EUR_currency_from_rate = (float) $currency_rate->rate;
					}
					if ( ! isset( $EUR_currency_to_rate ) && $currency_to == $currency_rate->currency ) {
						$EUR_currency_to_rate = (float) $currency_rate->rate;
					}
				}
				if ( isset( $EUR_currency_from_rate ) && isset( $EUR_currency_to_rate ) && 0 != $EUR_currency_from_rate ) {
					$final_rate = round( $EUR_currency_to_rate / $EUR_currency_from_rate, 6 );
				} else {
					$final_rate = false;
				}
			}
		}
		return $final_rate;
	}
}

if ( ! function_exists( 'alg_tcmb_get_exchange_rate_TRY' ) ) {
	/*
	 * alg_tcmb_get_exchange_rate_TRY.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function alg_tcmb_get_exchange_rate_TRY( $currency_from ) {
		if ( 'TRY' === $currency_from ) {
			return 1;
		}
		$xml = simplexml_load_file( 'http://www.tcmb.gov.tr/kurlar/today.xml' );
		if ( isset( $xml->Currency ) ) {
			foreach ( $xml->Currency as $the_rate ) {
				$attributes = $the_rate->attributes();
				if ( isset( $attributes['CurrencyCode'] ) ) {
					$currency_code = (string) $attributes['CurrencyCode'];
					if ( $currency_code === $currency_from  ) {
						// Possible values: ForexSelling, ForexBuying, BanknoteSelling, BanknoteBuying. Not used: CrossRateUSD, CrossRateOther.
						if ( '' != ( $property_to_check = apply_filters( 'wcj_currency_exchange_rates_tcmb_property_to_check', '' ) ) ) {
							if ( isset( $the_rate->{$property_to_check} ) ) {
								$rate = (float) $the_rate->{$property_to_check};
							} else {
								continue;
							}
						} else {
							if ( isset( $the_rate->ForexSelling ) ) {
								$rate = (float) $the_rate->ForexSelling;
							} elseif ( isset( $the_rate->ForexBuying ) ) {
								$rate = (float) $the_rate->ForexBuying;
							} elseif ( isset( $the_rate->BanknoteSelling ) ) {
								$rate = (float) $the_rate->BanknoteSelling;
							} elseif ( isset( $the_rate->BanknoteBuying ) ) {
								$rate = (float) $the_rate->BanknoteBuying;
							} else {
								continue;
							}
						}
						$unit = ( isset( $the_rate->Unit ) ) ? (float) $the_rate->Unit : 1;
						return ( $rate / $unit );
					}
				}
			}
		}
		return false;
	}
}

if ( ! function_exists( 'alg_tcmb_get_exchange_rate' ) ) {
	/*
	 * alg_tcmb_get_exchange_rate.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function alg_tcmb_get_exchange_rate( $currency_from, $currency_to ) {
		$currency_from_TRY = alg_tcmb_get_exchange_rate_TRY( strtoupper( $currency_from ) );
		if ( false == $currency_from_TRY  ) {
			return false;
		}
		$currency_to_TRY = alg_tcmb_get_exchange_rate_TRY( strtoupper( $currency_to )  );
		if ( false == $currency_to_TRY ) {
			return false;
		}
		if ( 1 == $currency_to_TRY ) {
			return round( $currency_from_TRY, 6 );
		}
		return round( ( $currency_from_TRY / $currency_to_TRY ), 6 );
	}
}

if ( ! function_exists( 'alg_yahoo_get_exchange_rate' ) ) {
	/*
	 * alg_yahoo_get_exchange_rate.
	 *
	 * @version 2.7.0
	 * @return  float rate on success, else 0
	 */
	function alg_yahoo_get_exchange_rate( $currency_from, $currency_to ) {

		$url = "http://query.yahooapis.com/v1/public/yql?q=select%20rate%2Cname%20from%20csv%20where%20url%3D'http%3A%2F%2Fdownload.finance.yahoo.com%2Fd%2Fquotes%3Fs%3D" . $currency_from . $currency_to . "%253DX%26f%3Dl1n'%20and%20columns%3D'rate%2Cname'&format=json";
//		$url = 'http://rate-exchange.appspot.com/currency?from=' . $currency_from . '&to=' . $currency_to;

		ob_start();
		$max_execution_time = ini_get( 'max_execution_time' );
		set_time_limit( 5 );

		$response = '';
		if ( 'no' === get_option( 'wcj_currency_exchange_rates_always_curl', 'no' ) && ini_get( 'allow_url_fopen' ) ) {
			$response = file_get_contents( $url );
		} elseif ( function_exists( 'curl_version' ) ) {
			$curl = curl_init( $url );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			$response = curl_exec( $curl );
			curl_close( $curl );
		}
		$exchange_rate = json_decode( $response );

		set_time_limit( $max_execution_time );
		ob_end_clean();

		return ( isset( $exchange_rate->query->results->row->rate ) ) ? floatval( $exchange_rate->query->results->row->rate ) : 0;
//		return ( isset( $exchange_rate->rate ) ) ? $exchange_rate->rate : 0;
	}
}
