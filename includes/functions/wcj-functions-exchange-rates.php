<?php
/**
 * Booster for WooCommerce - Functions - Exchange Rates
 *
 * @version 3.3.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_saved_exchange_rate' ) ) {
	/**
	 * wcj_get_saved_exchange_rate.
	 *
	 * @version 3.3.0
	 * @since   3.2.4
	 * @todo    (maybe) need to check if `currency_exchange_rates` module `is_enabled()`
	 */
	function wcj_get_saved_exchange_rate( $from, $to ) {
		if ( $from == $to ) {
			return 1;
		}
		// Preparing `active_currencies` array
		if ( ! isset( WCJ()->modules['currency_exchange_rates']->active_currencies ) ) {
			$active_currencies_settings = WCJ()->modules['currency_exchange_rates']->get_all_currencies_exchange_rates_settings();
			$active_currencies          = array();
			foreach ( $active_currencies_settings as $currency ) {
				$active_currencies[ str_replace( 'wcj_currency_exchange_rates_', '', $currency['id'] ) ] = get_option( $currency['id'] );
			}
			WCJ()->modules['currency_exchange_rates']->active_currencies = $active_currencies;
		} else {
			$active_currencies = WCJ()->modules['currency_exchange_rates']->active_currencies;
		}
		if ( empty( $active_currencies ) ) {
			return 0;
		}
		// Getting exchange rate - simple method
		$exchange_rate = ( isset( $active_currencies[ sanitize_title( $from . $to ) ] ) ? $active_currencies[ sanitize_title( $from . $to ) ] : 0 );
		// Getting exchange rate - invert method
		if ( 0 == $exchange_rate ) {
			$exchange_rate = ( isset( $active_currencies[ sanitize_title( $to . $from ) ] ) ? $active_currencies[ sanitize_title( $to . $from ) ] : 0 );
			if ( 0 != $exchange_rate ) {
				$exchange_rate = 1 / $exchange_rate;
			}
		}
		// Getting exchange rate - shop base
		if ( 0 == $exchange_rate ) {
			$shop_currency      = get_option( 'woocommerce_currency' );
			$exchange_rate_from = ( isset( $active_currencies[ sanitize_title( $shop_currency . $from ) ] ) ? $active_currencies[ sanitize_title( $shop_currency . $from ) ] : 0 );
			$exchange_rate_to   = ( isset( $active_currencies[ sanitize_title( $shop_currency . $to ) ] )   ? $active_currencies[ sanitize_title( $shop_currency . $to ) ]   : 0 );
			if ( 0 != $exchange_rate_from ) {
				$exchange_rate = $exchange_rate_to / $exchange_rate_from;
			}
		}
		return $exchange_rate;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_servers' ) ) {
	/**
	 * wcj_get_currency_exchange_rate_servers.
	 *
	 * @version 3.2.4
	 * @since   2.6.0
	 */
	function wcj_get_currency_exchange_rate_servers() {
		return array(
			'yahoo'           => __( 'Yahoo', 'woocommerce-jetpack' ),
			'ecb'             => __( 'European Central Bank (ECB)', 'woocommerce-jetpack' ),
			'tcmb'            => __( 'TCMB', 'woocommerce-jetpack' ),
			'fixer'           => __( 'Fixer.io', 'woocommerce-jetpack' ),
			'coinbase'        => __( 'Coinbase', 'woocommerce-jetpack' ),
			'coinmarketcap'   => __( 'CoinMarketCap', 'woocommerce-jetpack' ),
		);
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_server' ) ) {
	/*
	 * wcj_get_currency_exchange_rate_server.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function wcj_get_currency_exchange_rate_server( $currency_from, $currency_to ) {
		if ( 'default_server' === ( $server = get_option( 'wcj_currency_exchange_rates_server_' . sanitize_title( $currency_from . $currency_to ), 'default_server' ) ) ) {
			return get_option( 'wcj_currency_exchange_rates_server', 'ecb' );
		}
		return $server;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_server_name' ) ) {
	/*
	 * wcj_get_currency_exchange_rate_server_name.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function wcj_get_currency_exchange_rate_server_name( $currency_from, $currency_to ) {
		$servers = wcj_get_currency_exchange_rate_servers();
		$server  = wcj_get_currency_exchange_rate_server( $currency_from, $currency_to );
		return ( isset( $servers[ $server ] ) ? $servers[ $server ] : $server );
	}
}

if ( ! function_exists( 'alg_get_exchange_rate' ) ) {
	/*
	 * alg_get_exchange_rate.
	 *
	 * @version 3.2.4
	 * @since   2.6.0
	 */
	function alg_get_exchange_rate( $currency_from, $currency_to ) {
		$exchange_rates_server = wcj_get_currency_exchange_rate_server( $currency_from, $currency_to );
		if ( 'yes' === ( $calculate_by_invert = get_option( 'wcj_currency_exchange_rates_calculate_by_invert', 'no' ) ) ) {
			$_currency_to  = $currency_to;
			$currency_to   = $currency_from;
			$currency_from = $_currency_to;
		}
		switch ( $exchange_rates_server ) {
			case 'tcmb':
				$return = alg_tcmb_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'ecb':
				$return = alg_ecb_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'fixer':
				$return = alg_fixer_io_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'coinbase':
				$return = alg_coinbase_get_exchange_rate( $currency_from, $currency_to );
				break;
			case 'coinmarketcap':
				$return = alg_coinmarketcap_get_exchange_rate( $currency_from, $currency_to );
				break;
			default: // 'yahoo'
				$return = alg_yahoo_get_exchange_rate( $currency_from, $currency_to );
				break;
		}
		return ( 'yes' === $calculate_by_invert ) ? round( ( 1 / $return ), 6 ) : $return;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rates_url_response' ) ) {
	/*
	 * wcj_get_currency_exchange_rates_url_response.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    use where needed
	 */
	function wcj_get_currency_exchange_rates_url_response( $url ) {
		$response = '';
		if ( 'no' === get_option( 'wcj_currency_exchange_rates_always_curl', 'no' ) && ini_get( 'allow_url_fopen' ) ) {
			$response = file_get_contents( $url );
		} elseif ( function_exists( 'curl_version' ) ) {
			$curl = curl_init( $url );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
			$response = curl_exec( $curl );
			curl_close( $curl );
		}
		return ( '' != $response ? json_decode( $response ) : false );
	}
}

if ( ! function_exists( 'alg_coinmarketcap_get_exchange_rate_specific' ) ) {
	/*
	 * alg_coinmarketcap_get_exchange_rate_specific.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @see     https://coinmarketcap.com/api/
	 * @todo    add more `$cryptocurrencies_ids`
	 * @todo    (maybe) try reverse only if `! isset( $cryptocurrencies_ids[ $currency_from ] )`
	 */
	function alg_coinmarketcap_get_exchange_rate_specific( $currency_from, $currency_to, $try_reverse = true ) {
		$return = false;
		$cryptocurrencies_ids = array(
			'BTC' => 'bitcoin',
			'XRP' => 'ripple',
		);
		if ( isset( $cryptocurrencies_ids[ $currency_from ] ) ) {
			$url = 'https://api.coinmarketcap.com/v1/ticker/' . $cryptocurrencies_ids[ $currency_from ] . '/?convert=' . $currency_to;
			if ( false != ( $response = wcj_get_currency_exchange_rates_url_response( $url ) ) ) {
				$att    = 'price_' . strtolower( $currency_to );
				$return = ( isset( $response[0]->{$att} ) ? $response[0]->{$att} : false );
			}
		}
		if ( false === $return && $try_reverse ) {
			$return = alg_coinmarketcap_get_exchange_rate_specific( $currency_to, $currency_from, false );
			if ( 0 != $return ) {
				$return = round( ( 1 / $return ), 12 );
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'alg_coinmarketcap_get_exchange_rate' ) ) {
	/*
	 * alg_coinmarketcap_get_exchange_rate.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @see     https://coinmarketcap.com/api/
	 * @todo    `WCJ()->modules['currency_exchange_rates']->coinmarketcap_response`
	 * @todo    `alg_coinmarketcap_get_exchange_rate_specific()`
	 * @todo    (maybe) `limit=0`
	 */
	function alg_coinmarketcap_get_exchange_rate( $currency_from, $currency_to, $try_reverse = true ) {
		$return = false;
		/*
		if ( ! isset( WCJ()->modules['currency_exchange_rates']->coinmarketcap_response ) ) {
			$response = wcj_get_currency_exchange_rates_url_response( 'https://api.coinmarketcap.com/v1/ticker/?convert=' . $currency_to );
			if ( false != $response ) {
				WCJ()->modules['currency_exchange_rates']->coinmarketcap_response = $response;
			}
		} else {
			$response = WCJ()->modules['currency_exchange_rates']->coinmarketcap_response;
		}
		*/
		if ( false != ( $response = wcj_get_currency_exchange_rates_url_response( 'https://api.coinmarketcap.com/v1/ticker/?convert=' . $currency_to ) ) && is_array( $response ) ) {
			foreach ( $response as $pair ) {
				if ( isset( $pair->symbol ) && $currency_from === $pair->symbol ) {
					$att = 'price_' . strtolower( $currency_to );
					$return = ( isset( $pair->{$att} ) ? $pair->{$att} : false );
					break;
				}
			}
		}
		if ( false === $return && $try_reverse ) {
			$return = alg_coinmarketcap_get_exchange_rate( $currency_to, $currency_from, false );
			if ( 0 != $return ) {
				$return = round( ( 1 / $return ), 12 );
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'alg_coinbase_get_exchange_rate' ) ) {
	/*
	 * alg_coinbase_get_exchange_rate.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function alg_coinbase_get_exchange_rate( $currency_from, $currency_to ) {
		$response = wcj_get_currency_exchange_rates_url_response( "https://api.coinbase.com/v2/exchange-rates?currency=$currency_from" );
		return ( isset( $response->data->rates->{$currency_to} ) ? $response->data->rates->{$currency_to} : false );
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
	 * @version 3.2.4
	 * @return  float rate on success, else 0
	 * @todo    `alg_` to `wcj_`
	 */
	function alg_yahoo_get_exchange_rate( $currency_from, $currency_to ) {
		$response = wcj_get_currency_exchange_rates_url_response( "https://finance.yahoo.com/webservice/v1/symbols/allcurrencies/quote?format=json" );
		if ( ! isset( $response->list->resources ) ) {
			return false;
		}
		$currencies = array(
			'currency_from' => array(
				'name'     => $currency_from . '=X',
				'usd_rate' => false,
			),
			'currency_to' => array(
				'name'     => $currency_to . '=X',
				'usd_rate' => false,
			),
		);
		foreach ( $currencies as &$currency ) {
			foreach ( $response->list->resources as $resource ) {
				if ( isset( $resource->resource->fields->symbol ) && $currency['name'] === $resource->resource->fields->symbol ) {
					if ( ! isset( $resource->resource->fields->price ) ) {
						return false;
					}
					$currency['usd_rate'] = $resource->resource->fields->price;
					break;
				}
			}
		}
		return ( false == $currencies['currency_to']['usd_rate'] || false == $currencies['currency_from']['usd_rate'] ? false :
			round( ( $currencies['currency_to']['usd_rate'] / $currencies['currency_from']['usd_rate'] ), 6 ) );
	}
}

if ( ! function_exists( 'alg_fixer_io_get_exchange_rate' ) ) {
	/*
	 * alg_fixer_io_get_exchange_rate.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 * @return  false or rate
	 */
	function alg_fixer_io_get_exchange_rate( $currency_from, $currency_to ) {
		return wcj_fixer_io_get_exchange_rate_by_date( $currency_from, $currency_to, 'latest' );
	}
}

if ( ! function_exists( 'wcj_fixer_io_get_exchange_rate_by_date' ) ) {
	/*
	 * wcj_fixer_io_get_exchange_rate_by_date.
	 *
	 * @version 3.2.4
	 * @since   3.2.2
	 * @return  false or rate
	 */
	function wcj_fixer_io_get_exchange_rate_by_date( $currency_from, $currency_to, $date ) {
		$response = wcj_get_currency_exchange_rates_url_response( 'https://api.fixer.io/' . $date . '?base=' . $currency_from . '&symbols=' . $currency_to );
		return ( isset( $response->rates->{$currency_to} ) ? $response->rates->{$currency_to} : false );
	}
}

if ( ! function_exists( 'wcj_fixer_io_get_exchange_rate_average' ) ) {
	/*
	 * wcj_fixer_io_get_exchange_rate_average.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 * @return  false or rate
	 * @todo    customizable '+1 day' (could be '+1 week' etc.)
	 */
	function wcj_fixer_io_get_exchange_rate_average( $currency_from, $currency_to, $start_date, $end_date ) {
		$average_rate         = 0;
		$average_rate_counter = 0;
		$start_date = new DateTime( $start_date );
		$end_date   = new DateTime( $end_date );
		for ( $i = $start_date; $i <= $end_date; $i->modify( '+1 day' ) ) {
			$date = $i->format( "Y-m-d" );
			$rate = wcj_fixer_io_get_exchange_rate_by_date( $currency_from, $currency_to, $date );
			if ( false != $rate ) {
				$average_rate += $rate;
				$average_rate_counter++;
			}
		}
		return ( 0 == $average_rate || 0 == $average_rate_counter ? false : round( ( $average_rate / $average_rate_counter ), 6 ) );
	}
}
