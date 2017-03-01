<?php
/**
 * WooCommerce Jetpack Exchange Rates Crons
 *
 * The WooCommerce Jetpack Exchange Rates Crons class.
 *
 * @version 2.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Exchange_Rates_Crons' ) ) :

class WCJ_Exchange_Rates_Crons {

	/**
	 * Constructor.
	 *
	 * @version 2.6.0
	 */
	public function __construct() {
		$this->update_intervals  = array(
			'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
			'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
			'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
			'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
			'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
		);
		add_action( 'init',                            array( $this, 'schedule_the_events' ) );
		add_action( 'admin_init',                      array( $this, 'schedule_the_events' ) );
		add_action( 'auto_update_exchange_rates_hook', array( $this, 'update_the_exchange_rates' ) );
		add_filter( 'cron_schedules',                  array( $this, 'cron_add_custom_intervals' ) );

		add_action( 'wp_ajax_'        . 'wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );
		add_action( 'wp_ajax_nopriv_' . 'wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );
	}

	/**
	 * wcj_ajax_get_exchange_rates.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @todo    this shouldn't be in crons
	 */
	function wcj_ajax_get_exchange_rates() {
		echo $this->get_exchange_rate( $_POST['wcj_currency_from'], $_POST['wcj_currency_to'] );
		die();
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 *
	 * @version 2.5.5
	 */
	function schedule_the_events() {
		$selected_interval = get_option( 'wcj_currency_exchange_rates_auto', 'daily' );
		foreach ( $this->update_intervals as $interval => $desc ) {
			$event_hook = 'auto_update_exchange_rates_hook';
			$event_timestamp = wp_next_scheduled( $event_hook, array( $interval ) );
			if ( $selected_interval === $interval ) {
				update_option( 'wcj_currency_exchange_rate_cron_time', $event_timestamp );
			}
			if ( ! $event_timestamp && $selected_interval === $interval ) {
				wp_schedule_event( time(), $selected_interval, $event_hook, array( $selected_interval ) );
			} elseif ( $event_timestamp && $selected_interval !== $interval ) {
				wp_unschedule_event( $event_timestamp, $event_hook, array( $interval ) );
			}
		}
	}

	/*
	 * get_exchange_rate.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function get_exchange_rate( $currency_from, $currency_to ) {
		$exchange_rates_server = get_option( 'wcj_currency_exchange_rates_server', 'yahoo' );
		switch ( $exchange_rates_server ) {
			case 'tcmb':
				return $this->tcmb_get_exchange_rate( $currency_from, $currency_to );
			case 'ecb':
				return $this->ecb_get_exchange_rate( $currency_from, $currency_to );
			default: // 'yahoo'
				return $this->yahoo_get_exchange_rate( $currency_from, $currency_to );
		}
	}

	/*
	 * ecb_get_exchange_rate.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function ecb_get_exchange_rate( $currency_from, $currency_to ) {
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

	/*
	 * tcmb_get_exchange_rate_TRY.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function tcmb_get_exchange_rate_TRY( $currency_from ) {
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

	/*
	 * tcmb_get_exchange_rate.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function tcmb_get_exchange_rate( $currency_from, $currency_to ) {
		$currency_from_TRY = $this->tcmb_get_exchange_rate_TRY( strtoupper( $currency_from ) );
		if ( false == $currency_from_TRY  ) {
			return false;
		}
		$currency_to_TRY = $this->tcmb_get_exchange_rate_TRY( strtoupper( $currency_to )  );
		if ( false == $currency_to_TRY ) {
			return false;
		}
		if ( 1 == $currency_to_TRY ) {
			return round( $currency_from_TRY, 6 );
		}
		return round( ( $currency_from_TRY / $currency_to_TRY ), 6 );
	}

	/*
	 * yahoo_get_exchange_rate.
	 *
	 * @version 2.6.0
	 * @return  float rate on success, else 0
	 */
	function yahoo_get_exchange_rate( $currency_from, $currency_to ) {

		$url = "http://query.yahooapis.com/v1/public/yql?q=select%20rate%2Cname%20from%20csv%20where%20url%3D'http%3A%2F%2Fdownload.finance.yahoo.com%2Fd%2Fquotes%3Fs%3D" . $currency_from . $currency_to . "%253DX%26f%3Dl1n'%20and%20columns%3D'rate%2Cname'&format=json";
//		$url = 'http://rate-exchange.appspot.com/currency?from=' . $currency_from . '&to=' . $currency_to;

		ob_start();
		$max_execution_time = ini_get( 'max_execution_time' );
		set_time_limit( 5 );

		$response = '';
		if ( ini_get( 'allow_url_fopen' ) ) {
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

	/**
	 * get_currency_pair.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function get_currency_pair( $currency_pairs, $currency_to, $option_name ) {

		foreach ( $currency_pairs as $k => $currency_pair ) {
			if ( $currency_pair['currency_to'] == $currency_to ) {
				$currency_pairs[ $k ]['option_name'][] = $option_name;
				return $currency_pairs;
			}
		}
		$currency_pairs[] = array(
			'currency_from' => get_option( 'woocommerce_currency' ),
			'currency_to'   => $currency_to,
			'option_name'   => array( $option_name, ),
		);
		return $currency_pairs;
	}

	/**
	 * On the scheduled action hook, run a function.
	 *
	 * @version 2.6.0
	 */
	function update_the_exchange_rates( $interval ) {

		$currency_pairs[] = array();

		if ( wcj_is_module_enabled( 'price_by_country' ) ) {
			// Currency Pairs - Preparation - Price by Country
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_price_by_country_auto_exchange_rates', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
					$currency_pairs = $this->get_currency_pair( $currency_pairs, $currency_to, 'wcj_price_by_country_exchange_rate_group_' . $i );
				}
			}
		}

		if ( wcj_is_module_enabled( 'multicurrency' ) ) {
			// Currency Pairs - Preparation - Multicurrency
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_exchange_rate_update_auto', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_multicurrency_currency_' . $i );
					$currency_pairs = $this->get_currency_pair( $currency_pairs, $currency_to, 'wcj_multicurrency_exchange_rate_' . $i );
				}
			}
		}

		if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
			// Currency Pairs - Preparation - Multicurrency Product Base Price
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_base_price_exchange_rate_update', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_multicurrency_base_price_currency_' . $i );
					$currency_pairs = $this->get_currency_pair( $currency_pairs, $currency_to, 'wcj_multicurrency_base_price_exchange_rate_' . $i );
				}
			}
		}

		if ( wcj_is_module_enabled( 'currency_per_product' ) ) {
			// Currency Pairs - Preparation - Currency per Product
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_currency_per_product_exchange_rate_update', 'manual' ) ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) ); $i++ ) {
					$currency_to = get_option( 'wcj_currency_per_product_currency_' . $i );
					$currency_pairs = $this->get_currency_pair( $currency_pairs, $currency_to, 'wcj_currency_per_product_exchange_rate_' . $i );
				}
			}
		}

		if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
			// Currency Pairs - Preparation - Gateway Currency
			if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_gateways_currency_exchange_rate_update_auto', 'manual' ) ) ) {
				global $woocommerce;
				$available_gateways = $woocommerce->payment_gateways->payment_gateways();
				foreach ( $available_gateways as $key => $gateway ) {
					$currency_to = get_option( 'wcj_gateways_currency_' . $key );
					if ( 'no_changes' != $currency_to ) {
						$currency_pairs = $this->get_currency_pair( $currency_pairs, $currency_to, 'wcj_gateways_currency_exchange_rate_' . $key );
					}
				}
			}
		}

		// Currency Pairs - Final
		$rate_offset_percent = get_option( 'wcj_currency_exchange_rates_offset_percent', 0 );
		if ( 0 != $rate_offset_percent ) {
			$rate_offset_percent = 1 + ( $rate_offset_percent / 100 );
		}
		$rate_offset_fixed = get_option( 'wcj_currency_exchange_rates_offset_fixed', 0 );
		foreach ( $currency_pairs as $currency_pair ) {
			$currency_from = $currency_pair['currency_from'];
			$currency_to   = $currency_pair['currency_to'];
			$the_rate = $this->get_exchange_rate( $currency_from, $currency_to );
			if ( 0 != $the_rate ) {
				if ( 0 != $rate_offset_percent ) {
					$the_rate = round( $the_rate * $rate_offset_percent, 6 );
				}
				if ( 0 != $rate_offset_fixed ) {
					$the_rate = $the_rate + $rate_offset_fixed;
				}
				if ( $currency_from != $currency_to ) {
					foreach ( $currency_pair['option_name'] as $option_name ) {
						update_option( $option_name, $the_rate );
					}
					$field_id = 'wcj_currency_exchange_rates_' . sanitize_title( $currency_from . $currency_to );
					update_option( $field_id, $the_rate );
					$result_message = __( 'Cron job: exchange rates successfully updated', 'woocommerce-jetpack' );
				} else {
					$result_message = __( 'Cron job: exchange rates not updated, as currency_from == currency_to', 'woocommerce-jetpack' );
				}
			} else {
				$result_message = __( 'Cron job: exchange rates update failed', 'woocommerce-jetpack' );
			}
			/* if ( 'yes' === get_option( 'wcj_currency_exchange_logging_enabled', 'no' ) ) {
				wcj_log( $result_message . ': ' . $currency_from . $currency_to . ': ' . $the_rate . ': ' . 'update_the_exchange_rates: ' . $interval );
			} */
		}

		if ( wcj_is_module_enabled( 'price_by_country' ) ) {
			if ( 'yes' === get_option( 'wcj_price_by_country_price_filter_widget_support_enabled', 'no' ) ) {
				wcj_update_products_price_by_country();
			}
		}
	}

	/**
	 * cron_add_custom_intervals.
	 */
	function cron_add_custom_intervals( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Once Weekly', 'woocommerce-jetpack' )
		);
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute', 'woocommerce-jetpack' )
		);
		return $schedules;
	}
}

endif;

return new WCJ_Exchange_Rates_Crons();
