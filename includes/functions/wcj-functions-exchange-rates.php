<?php
/**
 * Booster for WooCommerce - Functions - Exchange Rates
 *
 * @version 6.0.1
 * @since   2.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_get_saved_exchange_rate' ) ) {
	/**
	 * Wcj_get_saved_exchange_rate.
	 *
	 * @version 6.0.1
	 * @since   3.2.4
	 * @todo    (maybe) need to check if `currency_exchange_rates` module `is_enabled()`
	 * @param   string | int $from defines the from.
	 * @param   string | int $to defines the to.
	 */
	function wcj_get_saved_exchange_rate( $from, $to ) {
		if ( $from === $to ) {
			return 1;
		}
		// Preparing `active_currencies` array.
		if ( ! isset( w_c_j()->all_modules['currency_exchange_rates']->active_currencies ) ) {
			$active_currencies_settings = w_c_j()->all_modules['currency_exchange_rates']->get_all_currencies_exchange_rates_settings();
			$active_currencies          = array();
			foreach ( $active_currencies_settings as $currency ) {
				$active_currencies[ str_replace( 'wcj_currency_exchange_rates_', '', $currency['id'] ) ] = wcj_get_option( $currency['id'] );
			}
			w_c_j()->all_modules['currency_exchange_rates']->active_currencies = $active_currencies;
		} else {
			$active_currencies = w_c_j()->all_modules['currency_exchange_rates']->active_currencies;
		}
		if ( empty( $active_currencies ) ) {
			return 0;
		}
		// Getting exchange rate - simple method.
		$exchange_rate = ( isset( $active_currencies[ sanitize_title( $from . $to ) ] ) ? $active_currencies[ sanitize_title( $from . $to ) ] : 0 );
		// Getting exchange rate - invert method.
		if ( 0 === $exchange_rate ) {
			$exchange_rate = ( isset( $active_currencies[ sanitize_title( $to . $from ) ] ) ? $active_currencies[ sanitize_title( $to . $from ) ] : 0 );
			if ( 0 !== $exchange_rate ) {
				$exchange_rate = 1 / $exchange_rate;
			}
		}
		// Getting exchange rate - shop base.
		if ( 0 === $exchange_rate ) {
			$shop_currency      = wcj_get_option( 'woocommerce_currency' );
			$exchange_rate_from = ( isset( $active_currencies[ sanitize_title( $shop_currency . $from ) ] ) ? $active_currencies[ sanitize_title( $shop_currency . $from ) ] : 0 );
			$exchange_rate_to   = ( isset( $active_currencies[ sanitize_title( $shop_currency . $to ) ] ) ? $active_currencies[ sanitize_title( $shop_currency . $to ) ] : 0 );
			if ( 0 !== $exchange_rate_from ) {
				$exchange_rate = $exchange_rate_to / $exchange_rate_from;
			}
		}
		return $exchange_rate;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_servers' ) ) {
	/**
	 * Wcj_get_currency_exchange_rate_servers.
	 *
	 * @version 3.9.0
	 * @since   2.6.0
	 */
	function wcj_get_currency_exchange_rate_servers() {
		return apply_filters(
			'wcj_currency_exchange_rates_servers',
			array(
				'ecb'                  => __( 'European Central Bank (ECB)', 'woocommerce-jetpack' ) . ' [' . __( 'recommended', 'woocommerce-jetpack' ) . ']',
				'currencyconverterapi' => __( 'The Free Currency Converter API', 'woocommerce-jetpack' ),
				'boe'                  => __( 'Bank of England (BOE)', 'woocommerce-jetpack' ),
				'tcmb'                 => __( 'TCMB', 'woocommerce-jetpack' ),
				'coinbase'             => __( 'Coinbase', 'woocommerce-jetpack' ),
				'coinmarketcap'        => __( 'CoinMarketCap', 'woocommerce-jetpack' ),
			)
		);
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_server' ) ) {
	/**
	 * Wcj_get_currency_exchange_rate_server.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_get_currency_exchange_rate_server( $currency_from, $currency_to ) {
		$server = wcj_get_option( 'wcj_currency_exchange_rates_server_' . sanitize_title( $currency_from . $currency_to ), 'default_server' );
		if ( 'default_server' === ( $server ) ) {
			return wcj_get_option( 'wcj_currency_exchange_rates_server', 'ecb' );
		}
		return $server;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_offset_percent' ) ) {
	/**
	 * Wcj_get_currency_exchange_rate_offset_percent.
	 *
	 * @version 3.4.5
	 * @since   3.4.5
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_get_currency_exchange_rate_offset_percent( $currency_from, $currency_to ) {
		$field_id = 'wcj_currency_exchange_rates_offset_percent_' . sanitize_title( $currency_from . $currency_to );
		if ( 'default_offset' === wcj_get_option( $field_id, 'default_offset' ) ) {
			return wcj_get_option( 'wcj_currency_exchange_rates_offset_percent', 0 );
		}
		return wcj_get_option( $field_id . '_custom_offset', 0 );
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_server_name' ) ) {
	/**
	 * Wcj_get_currency_exchange_rate_server_name.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_get_currency_exchange_rate_server_name( $currency_from, $currency_to ) {
		$servers = wcj_get_currency_exchange_rate_servers();
		$server  = wcj_get_currency_exchange_rate_server( $currency_from, $currency_to );
		return ( isset( $servers[ $server ] ) ? $servers[ $server ] : $server );
	}
}

if ( ! function_exists( 'wcj_get_exchange_rate' ) ) {
	/**
	 * Wcj_get_exchange_rate.
	 *
	 * @version 5.2.0
	 * @since   2.6.0
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_get_exchange_rate( $currency_from, $currency_to ) {
		if ( $currency_from === $currency_to ) {
			return 1;
		}
		$exchange_rates_server = wcj_get_currency_exchange_rate_server( $currency_from, $currency_to );
		$calculate_by_invert   = wcj_get_option( 'wcj_currency_exchange_rates_calculate_by_invert', 'no' );
		if ( 'yes' === ( $calculate_by_invert ) ) {
			$_currency_to  = $currency_to;
			$currency_to   = $currency_from;
			$currency_from = $_currency_to;
		}
		switch ( $exchange_rates_server ) {
			case 'tcmb':
				$return = wcj_tcmb_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'coinbase':
				$return = wcj_coinbase_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'coinmarketcap':
				$return = wcj_coinmarketcap_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'boe':
				$return = wcj_boe_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'currencyconverterapi':
				$return = wcj_currencyconverterapi_get_exchange_rate( $currency_from, $currency_to );
				break;
			default: // 'ecb':
				$return = wcj_ecb_get_exchange_rate( $currency_from, $currency_to );
				break;
		}
		$pre_return = apply_filters( 'wcj_currency_exchange_rate_pre', $return, $exchange_rates_server, $currency_from, $currency_to );
		$return     = ( 'yes' === $calculate_by_invert && ! empty( $pre_return ) ) ? round( ( 1 / $pre_return ), 6 ) : $pre_return;
		$return     = apply_filters( 'wcj_currency_exchange_rate', $return, $exchange_rates_server, $currency_from, $currency_to );
		return $return;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rates_url_response' ) ) {
	/**
	 * Wcj_get_currency_exchange_rates_url_response.
	 *
	 * @version 6.0.0
	 * @since   3.2.4
	 * @todo    use where needed
	 * @param   string $url defines the url.
	 * @param   bool   $do_json_decode defines the do_json_decode.
	 */
	function wcj_get_currency_exchange_rates_url_response( $url, $do_json_decode = true ) {
		$response = '';
		$response = wp_remote_get( $url );
		$response = $response['body'];
		return ( '' !== $response ? ( $do_json_decode ? json_decode( $response ) : $response ) : false );
	}
}

if ( ! function_exists( 'wcj_currencyconverterapi_get_exchange_rate' ) ) {
	/**
	 * Wcj_currencyconverterapi_get_exchange_rate.
	 *
	 * @version 5.5.9
	 * @since   3.9.0
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_currencyconverterapi_get_exchange_rate( $currency_from, $currency_to ) {
		$pair    = $currency_from . '_' . $currency_to;
		$url     = 'https://free.currencyconverterapi.com/api/v6/convert?q=' . $pair . '&compact=y';
		$api_key = wcj_get_option( 'wcj_currency_exchange_api_key_fccapi' );
		if ( ! empty( $api_key ) ) {
			$url = esc_url(
				add_query_arg(
					array(
						'apiKey' => $api_key,
					),
					$url
				)
			);
		}
		$response = wcj_get_currency_exchange_rates_url_response( $url );
		if ( $response ) {
			return ( ! empty( $response->{$pair}->val ) ? $response->{$pair}->val : false );
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_boe_get_exchange_rate_gbp' ) ) {
	/**
	 * Wcj_boe_get_exchange_rate_gbp.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @param   string $currency_to defines the currency_to.
	 */
	function wcj_boe_get_exchange_rate_gbp( $currency_to ) {
		if ( 'GBP' === $currency_to ) {
			return 1;
		}
		$final_rate     = false;
		$currency_codes = array(
			'AUD' => 'EC3', // Australian Dollar.
			'CAD' => 'ECL', // Canadian Dollar.
			'CNY' => 'INB', // Chinese Yuan.
			'CZK' => 'DS7', // Czech Koruna.
			'DKK' => 'ECH', // Danish Krone.
			'EUR' => 'C8J', // Euro.
			'HKD' => 'ECN', // Hong Kong Dollar.
			'HUF' => '5LA', // Hungarian Forint.
			'INR' => 'INE', // Indian Rupee.
			'ILS' => 'IN7', // Israeli Shekel.
			'JPY' => 'C8N', // Japanese Yen.
			'MYR' => 'IN8', // Malaysian ringgit.
			'NZD' => 'ECO', // New Zealand Dollar.
			'NOK' => 'EC6', // Norwegian Krone.
			'PLN' => '5OW', // Polish Zloty.
			'RUB' => 'IN9', // Russian Ruble.
			'SAR' => 'ECZ', // Saudi Riyal.
			'SGD' => 'ECQ', // Singapore Dollar.
			'ZAR' => 'ECE', // South African Rand.
			'KRW' => 'INC', // South Korean Won.
			'SEK' => 'ECC', // Swedish Krona.
			'CHF' => 'ECU', // Swiss Franc.
			'TWD' => 'ECD', // Taiwan Dollar.
			'THB' => 'INA', // Thai Baht.
			'TRY' => 'IND', // Turkish Lira.
			'USD' => 'C8P', // US Dollar.
		);
		if ( isset( $currency_codes[ $currency_to ] ) && function_exists( 'simplexml_load_file' ) ) {
			for ( $i = 1; $i <= 7; $i++ ) {
				$date         = time() - $i * 24 * 60 * 60;
				$date_from_d  = gmdate( 'd', $date );
				$date_from_m  = gmdate( 'M', $date );
				$date_from_y  = gmdate( 'Y', $date );
				$date_to_d    = gmdate( 'd', $date );
				$date_to_m    = gmdate( 'M', $date );
				$date_to_y    = gmdate( 'Y', $date );
				$date_url     = '&FD=' . $date_from_d . '&FM=' . $date_from_m . '&FY=' . $date_from_y . '&TD=' . $date_to_d . '&TM=' . $date_to_m . '&TY=' . $date_to_y;
				$url          = 'http://www.bankofengland.co.uk/boeapps/iadb/fromshowcolumns.asp?Travel=NIxRSxSUx&FromSeries=1&ToSeries=50&DAT=RNG' . $date_url .
					'&VFD=Y&xml.x=23&xml.y=18&CSVF=TT&C=' . $currency_codes[ $currency_to ] . '&Filter=N';
				$xml          = simplexml_load_file( $url );
				$json_string  = wp_json_encode( $xml );
				$result_array = wp_json_encode( $json_string, true );
				if ( isset( $result_array['Cube']['Cube'] ) ) {
					$last_element_index = count( $result_array['Cube']['Cube'] ) - 1;
					if ( isset( $result_array['Cube']['Cube'][ $last_element_index ]['@attributes']['OBS_VALUE'] ) ) {
						return $result_array['Cube']['Cube'][ $last_element_index ]['@attributes']['OBS_VALUE'];
					}
				}
			}
		}
		return $final_rate;
	}
}

if ( ! function_exists( 'wcj_boe_get_exchange_rate' ) ) {
	/**
	 * Wcj_boe_get_exchange_rate.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_boe_get_exchange_rate( $currency_from, $currency_to ) {
		$gbp_currency_from = wcj_boe_get_exchange_rate_gbp( $currency_from );
		$gbp_currency_to   = wcj_boe_get_exchange_rate_gbp( $currency_to );
		if (
			false !== ( $gbp_currency_from ) &&
			false !== ( $gbp_currency_to )
		) {
			return round( $gbp_currency_to / $gbp_currency_from, 6 );
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_coinmarketcap_get_exchange_rate_specific' ) ) {
	/**
	 * Wcj_coinmarketcap_get_exchange_rate_specific.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @see     https://coinmarketcap.com/api/
	 * @todo    add more `$cryptocurrencies_ids`
	 * @todo    (maybe) try reverse only if `! isset( $cryptocurrencies_ids[ $currency_from ] )`
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 * @param   bool         $try_reverse defines the try_reverse.
	 */
	function wcj_coinmarketcap_get_exchange_rate_specific( $currency_from, $currency_to, $try_reverse = true ) {
		$return               = false;
		$cryptocurrencies_ids = array(
			'BTC' => 'bitcoin',
			'XRP' => 'ripple',
		);
		if ( isset( $cryptocurrencies_ids[ $currency_from ] ) ) {
			$url      = 'https://api.coinmarketcap.com/v1/ticker/' . $cryptocurrencies_ids[ $currency_from ] . '/?convert=' . $currency_to;
			$response = wcj_get_currency_exchange_rates_url_response( $url );
			if ( false !== ( $response ) ) {
				$att    = 'price_' . strtolower( $currency_to );
				$return = ( isset( $response[0]->{$att} ) ? $response[0]->{$att} : false );
			}
		}
		if ( false === $return && $try_reverse ) {
			$return = wcj_coinmarketcap_get_exchange_rate_specific( $currency_to, $currency_from, false );
			if ( 0 !== $return ) {
				$return = round( ( 1 / $return ), 12 );
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'wcj_coinmarketcap_get_exchange_rate' ) ) {
	/**
	 * Wcj_coinmarketcap_get_exchange_rate.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @see     https://coinmarketcap.com/api/
	 * @todo    `w_c_j()->all_modules['currency_exchange_rates']->coinmarketcap_response`
	 * @todo    `wcj_coinmarketcap_get_exchange_rate_specific()`
	 * @todo    (maybe) `limit=0`
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 * @param   bool         $try_reverse defines the try_reverse.
	 */
	function wcj_coinmarketcap_get_exchange_rate( $currency_from, $currency_to, $try_reverse = true ) {
		$return   = false;
		$response = wcj_get_currency_exchange_rates_url_response( 'https://api.coinmarketcap.com/v1/ticker/?convert=' . $currency_to );
		if ( false !== ( $response ) && is_array( $response ) ) {
			foreach ( $response as $pair ) {
				if ( isset( $pair->symbol ) && $currency_from === $pair->symbol ) {
					$att    = 'price_' . strtolower( $currency_to );
					$return = ( isset( $pair->{$att} ) ? $pair->{$att} : false );
					break;
				}
			}
		}
		if ( false === $return && $try_reverse ) {
			$return = wcj_coinmarketcap_get_exchange_rate( $currency_to, $currency_from, false );
			if ( 0 !== $return ) {
				$return = round( ( 1 / $return ), 12 );
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'wcj_coinbase_get_exchange_rate' ) ) {
	/**
	 * Wcj_coinbase_get_exchange_rate.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_coinbase_get_exchange_rate( $currency_from, $currency_to ) {
		$response = wcj_get_currency_exchange_rates_url_response( "https://api.coinbase.com/v2/exchange-rates?currency=$currency_from" );
		return ( isset( $response->data->rates->{$currency_to} ) ? $response->data->rates->{$currency_to} : false );
	}
}

if ( ! function_exists( 'wcj_ecb_get_exchange_rate' ) ) {
	/**
	 * Wcj_ecb_get_exchange_rate.
	 *
	 * @version 6.0.0
	 * @since   2.6.0
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_ecb_get_exchange_rate( $currency_from, $currency_to ) {
		$final_rate = false;
		$cube       = 'Cube';
		if ( function_exists( 'simplexml_load_file' ) ) {
			if ( WP_DEBUG === true ) {
				$xml = simplexml_load_file( 'http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml' );
			} else {
				$xml = simplexml_load_file( 'http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml' );
			}
			if ( isset( $xml->$cube->$cube->$cube ) ) {
				if ( 'EUR' === $currency_from ) {
					$eur_currency_from_rate = 1;
				}
				if ( 'EUR' === $currency_to ) {
					$eur_currency_to_rate = 1;
				}
				foreach ( $xml->$cube->$cube->$cube as $currency_rate ) {
					$currency_rate = $currency_rate->attributes();
					if ( ! isset( $eur_currency_from_rate ) && $currency_from === (string) $currency_rate->currency ) {
						$eur_currency_from_rate = (float) $currency_rate->rate;
					}
					if ( ! isset( $eur_currency_to_rate ) && $currency_to === (string) $currency_rate->currency ) {
						$eur_currency_to_rate = (float) $currency_rate->rate;
					}
				}
				if ( isset( $eur_currency_from_rate ) && isset( $eur_currency_to_rate ) && 0 !== $eur_currency_from_rate ) {
					$final_rate = round( $eur_currency_to_rate / $eur_currency_from_rate, 6 );
				} else {
					$final_rate = false;
				}
			}
		}
		return $final_rate;
	}
}

if ( ! function_exists( 'wcj_tcmb_get_exchange_rate_try' ) ) {
	/**
	 * Wcj_tcmb_get_exchange_rate_try.
	 *
	 * @version 5.6.2
	 * @since   2.6.0
	 * @param   string | int $currency_from defines the currency_from.
	 */
	function wcj_tcmb_get_exchange_rate_try( $currency_from ) {
		if ( 'TRY' === $currency_from ) {
			return 1;
		}
		$xml = simplexml_load_file( 'http://www.tcmb.gov.tr/kurlar/today.xml' );
		// phpcs:disable
		if ( isset( $xml->Currency ) ) {
			foreach ( $xml->Currency as $the_rate ) {
				$attributes = $the_rate->attributes();
				if ( isset( $attributes['CurrencyCode'] ) ) {
					$currency_code = (string) $attributes['CurrencyCode'];
					if ( $currency_code === $currency_from ) {
						// Possible values: ForexSelling, ForexBuying, BanknoteSelling, BanknoteBuying. Not used: CrossRateUSD, CrossRateOther.
						$property_to_check = apply_filters( 'wcj_currency_exchange_rates_tcmb_property_to_check', '' );
						if ( '' !== ( $property_to_check ) ) {
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
		// phpcs:enable
		return false;
	}
}

if ( ! function_exists( 'wcj_tcmb_get_exchange_rate' ) ) {
	/**
	 * Wcj_tcmb_get_exchange_rate.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 */
	function wcj_tcmb_get_exchange_rate( $currency_from, $currency_to ) {
		$currency_from_try = wcj_tcmb_get_exchange_rate_try( strtoupper( $currency_from ) );
		if ( false === $currency_from_try ) {
			return false;
		}
		$currency_to_try = wcj_tcmb_get_exchange_rate_try( strtoupper( $currency_to ) );
		if ( false === $currency_to_try ) {
			return false;
		}
		if ( 1 === $currency_to_try ) {
			return round( $currency_from_try, 6 );
		}
		return round( ( $currency_from_try / $currency_to_try ), 6 );
	}
}

if ( ! function_exists( 'wcj_currencyconverterapi_io_get_exchange_rate_average' ) ) {
	/**
	 * Wcj_currencyconverterapi_io_get_exchange_rate_average.
	 *
	 * @version 5.5.9
	 * @since   3.9.0
	 * @return  false or rate
	 * @param   string | int $currency_from defines the currency_from.
	 * @param   string | int $currency_to defines the currency_to.
	 * @param   string       $start_date defines the start_date.
	 * @param   string       $end_date defines the end_date.
	 */
	function wcj_currencyconverterapi_io_get_exchange_rate_average( $currency_from, $currency_to, $start_date, $end_date ) {
		$pair                 = $currency_from . '_' . $currency_to;
		$average_rate         = 0;
		$average_rate_counter = 0;
		$date_ranges          = wcj_get_date_ranges( $start_date, $end_date, 8 );
		foreach ( $date_ranges as $range ) {
			$url     = 'https://free.currencyconverterapi.com/api/v6/convert?q=' . $pair . '&compact=ultra&date=' . $range['start_date'] . '&endDate=' . $range['end_date'];
			$api_key = wcj_get_option( 'wcj_currency_exchange_api_key_fccapi' );
			if ( ! empty( $api_key ) ) {
				$url = esc_url(
					add_query_arg(
						array(
							'apiKey' => $api_key,
						),
						$url
					)
				);
			}
			$response = wcj_get_currency_exchange_rates_url_response( $url );
			if ( $response && ! empty( $response->{$pair} ) ) {
				$response     = (array) $response->{$pair};
				$response_sum = array_sum( $response );
				if ( 0 !== ( $response_sum ) ) {
					$average_rate         += $response_sum;
					$average_rate_counter += count( $response );
				}
			}
		}
		return ( 0 !== $average_rate_counter ? round( ( $average_rate / $average_rate_counter ), 6 ) : false );
	}
}
