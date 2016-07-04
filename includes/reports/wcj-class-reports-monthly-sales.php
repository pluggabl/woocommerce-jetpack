<?php
/**
 * WooCommerce Jetpack Mothly Sales Reports
 *
 * The WooCommerce Jetpack Mothly Sales Reports class.
 *
 * @version 2.5.3
 * @since   2.4.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Reports_Monthly_Sales' ) ) :

class WCJ_Reports_Monthly_Sales {

	/**
	 * Constructor.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	public function __construct( $args = null ) {

	}

	/**
	 * get_report.
	 *
	 * @version 2.5.3
	 * @since   2.4.7
	 */
	public function get_report() {
		$html = '';
		$this->year = isset( $_GET['year'] ) ? $_GET['year'] : date( 'Y' );
		if ( isset( $_POST['wcj_reset_currency_rates'] ) ) {
			delete_option( 'wcj_reports_currency_rates' );
			$html .= '<p>' . __( 'Currency rates deleted.', 'woocommerce-jetpack' ) . '</p>';
		}
		$html .= $this->get_monthly_sales_report();
		return $html;
	}

	/*
	 * get_exchange_rate_average_USD.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_exchange_rate_average_USD( $currency, $start_date, $end_date ) {
		$url = 'https://query.yahooapis.com/v1/public/yql?q=select%20Close%20from%20yahoo.finance.historicaldata%20where%20symbol%20%3D%20%22'
			. $currency . '%3DX%22%20and%20startDate%20%3D%20%22'
			. $start_date . '%22%20and%20endDate%20%3D%20%22'
			. $end_date. '%22&format=json&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=';
		ob_start();
		$max_execution_time = ini_get( 'max_execution_time' );
		set_time_limit( 5 );
		$exchange_rate = json_decode( file_get_contents( $url ) );
		set_time_limit( $max_execution_time );
		ob_end_clean();
		if ( ! isset( $exchange_rate->query->results->quote ) || count( $exchange_rate->query->results->quote ) < 1 ) {
			return false;
		}
		$average_currency = 0;
		foreach ( $exchange_rate->query->results->quote as $quote ) {
			$average_currency += $quote->Close;
		}
		$average_currency = $average_currency / count( $exchange_rate->query->results->quote );
		if ( 0 == $average_currency ) {
			return false;
		}
		return $average_currency;
	}

	/*
	 * get_exchange_rate_average.
	 *
	 * @version 2.5.3
	 * @since   2.4.7
	 * @todo    current month rate - save only once a day (now saving every time)
	 */
	function get_exchange_rate_average( $currency_from, $currency_to, $start_date, $end_date ) {
		// Same currency
		if ( $currency_from == $currency_to ) {
			return 1.0;
		}
		// Saved values
		$saved_rates = get_option( 'wcj_reports_currency_rates', array() );
		$is_current_month = ( date( 'Y-m' ) == date( 'Y-m', strtotime( $start_date ) ) ) ? true : false;
		if ( ! $is_current_month ) {
			if ( ! empty( $saved_rates ) ) {
				if ( isset( $saved_rates[ $currency_from ][ $currency_to ][ $start_date ][ $end_date ] ) ) {
					return $saved_rates[ $currency_from ][ $currency_to ][ $start_date ][ $end_date ];
				}
			}
		}
		// Get exchange rate
		if ( 'USD' != $currency_from ) {
			// USD/$currency_from
			$average_currency_from = $this->get_exchange_rate_average_USD( $currency_from, $start_date, $end_date );
			if ( false === $average_currency_from ) {
				return false;
			}
		} else {
			$average_currency_from = 1.0;
		}
		if ( 'USD' != $currency_to ) {
			// USD/$currency_to
			$average_currency_to = $this->get_exchange_rate_average_USD( $currency_to, $start_date, $end_date );
			if ( false === $average_currency_to ) {
				return false;
			}
		} else {
			$average_currency_to = 1.0;
		}
		$the_rate = $average_currency_to / $average_currency_from;
		if ( ! $is_current_month ) {
			$saved_rates[ $currency_from ][ $currency_to ][ $start_date ][ $end_date ] = $the_rate;
			update_option( 'wcj_reports_currency_rates', $saved_rates );
		}
		return $the_rate;
	}

	/*
	 * get_monthly_sales_report.
	 *
	 * @version 2.5.3
	 * @since   2.4.7
	 * @todo   take not monthly average, but "Close" of closest day; forecast for current month; $order_currencies_array;
	 */
	function get_monthly_sales_report() {

		$execution_time_start = microtime( true );

		$months_array                          = array( '' );
		$months_days_array                     = array( __( 'Days', 'woocommerce-jetpack' ) );
		$total_orders_array                    = array( __( 'Total Orders', 'woocommerce-jetpack' ) );
		$total_orders_average_array            = array( __( 'Orders Average / Day', 'woocommerce-jetpack' ) );
		$total_orders_sum_array                = array( __( 'Total Sum', 'woocommerce-jetpack' ) );
		$total_orders_sum_excl_tax_array       = array( __( 'Total Sum (excl. TAX)', 'woocommerce-jetpack' ) );
		$total_orders_sum_average_order_array  = array( __( 'Average / Order (excl. TAX)', 'woocommerce-jetpack' ) );
		$total_orders_sum_average_array        = array( __( 'Average / Day (excl. TAX)', 'woocommerce-jetpack' ) );
		$currency_rates_array                  = array( __( 'Currency Rates', 'woocommerce-jetpack' ) );

		$total_months_days               = 0;
		$total_orders_total              = 0;
		$total_orders_sum_total          = 0;
		$total_orders_sum_excl_tax_total = 0;

		$order_currencies_array = array();
		$report_currency = ( isset( $_GET['currency'] ) && 'merge' != $_GET['currency'] ) ? $_GET['currency'] : get_woocommerce_currency();
		$block_size = 96;
		$table_data = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$current_months_averages = array();
			$total_orders = 0;
			$total_orders_sum = 0;
			$total_orders_sum_excl_tax = 0;
			$offset = 0;
			$day_for_average = ( $i == date( 'm' ) && $this->year == date( 'Y' ) ) ? date( 'd' ) - 1 : date( 't', strtotime( $this->year . '-' . sprintf( '%02d', $i ) . '-' . '01' ) );
			if ( 0 == $day_for_average ) {
				$months_array[]                          = date_i18n( 'F', mktime( 0, 0, 0, $i, 1, $this->year ) );
				$months_days_array[]                     = '-';
				$total_orders_array[]                    = '-';
				$total_orders_average_array[]            = '-';
				$total_orders_sum_array[]                = '-';
				$total_orders_sum_excl_tax_array[]       = '-';
				$total_orders_sum_average_order_array[]  = '-';
				$total_orders_sum_average_array[]        = '-';
				$currency_rates_array[]                  = '';
				continue;
			}
			while( true ) {
				$args_orders = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'wc-completed',
					'posts_per_page' => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'date_query'     => array(
						'after' => array(
							'year'  => $this->year,
							'month' => $i,
							'day'   => 1,
						),
						'before' => array(
							'year'  => $this->year,
							'month' => $i,
							'day'   => $day_for_average,
						),
						'inclusive' => true,
					),
				);
				$loop_orders = new WP_Query( $args_orders );
				if ( ! $loop_orders->have_posts() ) break;
				while ( $loop_orders->have_posts() ) : $loop_orders->the_post();
					$order_id = $loop_orders->post->ID;
					$order = new WC_Order( $order_id );
					$order_currency = $order->get_order_currency();
					if ( ! isset( $order_currencies_array[ $order_currency ] ) ) {
						$order_currencies_array[ $order_currency ] = 0;
					}
					$order_currencies_array[ $order_currency ]++;
					$total_orders++;
					$order_total = $order->get_total();
					$order_total_excl_tax = $order->get_total() - $order->get_total_tax();
					if ( ! isset( $current_months_averages[ $order_currency ][ $report_currency ] ) ) {
						$start_date = $this->year . '-' . sprintf( '%02d', $i ) . '-' . '01';
						$end_date   = date( 'Y-m-t', strtotime( $start_date ) );
						$the_rate   = $this->get_exchange_rate_average( $order_currency, $report_currency, $start_date, $end_date );
						if ( false === $the_rate ) {
							// Try previous month
							$start_date_prev_month = date( 'Y-m-d', strtotime( 'first day of last month', strtotime( $start_date ) ) );
							$end_date_prev_month   = date( 'Y-m-t', strtotime( $start_date_prev_month ) );
							$the_rate = $this->get_exchange_rate_average( $order_currency, $report_currency, $start_date_prev_month, $end_date_prev_month );
							if ( false === $the_rate ) {
								return '<p>' . sprintf( __( 'Error getting currency rate for %s', 'woocommerce-jetpack' ), $order_currency . $report_currency ) . '</p>';
							}
						}
						$current_months_averages[ $order_currency ][ $report_currency ] = $the_rate;
					}
					$total_orders_sum += $order_total * $current_months_averages[ $order_currency ][ $report_currency ];
					$total_orders_sum_excl_tax += $order_total_excl_tax * $current_months_averages[ $order_currency ][ $report_currency ];
				endwhile;
				$offset += $block_size;
			}

			// Month Name
			$months_array[]      = date_i18n( 'F', mktime( 0, 0, 0, $i, 1, $this->year ) ) /* . '[' . $start_date . ' ~ ' . $end_date . ']' */;
			// Month Days
			$months_days_array[] = ( date( 'm' ) >= $i || $this->year != date( 'Y' ) ? $day_for_average : '-' );
			$total_months_days  += ( date( 'm' ) >= $i || $this->year != date( 'Y' ) ? $day_for_average : 0 );

			// Sales
			$total_orders_array[] = ( $total_orders > 0 ? $total_orders : '-' );
			$total_orders_total  += $total_orders;
			// Sales Average
			$average_sales_result = $total_orders / $day_for_average;
			$total_orders_average_array[] = ( $average_sales_result > 0 ? number_format( $average_sales_result, 2, '.', ',' ) : '-' );

			// Sum
			$total_orders_sum_array[] = ( $total_orders_sum > 0 ? $report_currency . ' ' . number_format( $total_orders_sum, 2, '.', ',' ) : '-' );
			$total_orders_sum_total  += $total_orders_sum;
			// Sum excl. Tax
			//if ( $total_orders_sum != $total_orders_sum_excl_tax) {
				$total_orders_sum_excl_tax_array[] = ( $total_orders_sum_excl_tax > 0 ?
					$report_currency . ' ' . number_format( $total_orders_sum_excl_tax, 2, '.', ',' ) : '-' );
				$total_orders_sum_excl_tax_total  += $total_orders_sum_excl_tax;
			//}
			// Order Average
			$total_orders_sum_average_order_array[] = ( $total_orders_sum_excl_tax > 0 && $total_orders > 0 ?
				$report_currency . ' ' . number_format( $total_orders_sum_excl_tax / $total_orders, 2, '.', ',' ) : '-' );
			// Sum Average
			$average_result = $total_orders_sum_excl_tax / $day_for_average;
			$total_orders_sum_average_array[] = ( $average_result > 0 ? $report_currency . ' ' . number_format( $average_result, 2, '.', ',' ) : '-' );

			// Currency Rates
//			if ( isset( $_GET['show_rates'] ) ) {
				ksort( $current_months_averages, true );
//				$currency_rates_html = '<pre style="font-size:8px;">' . print_r( $current_months_averages, true ) . '</pre>';
				$currency_rates_html = '<pre style="font-size:x-small;">';
				foreach ( $current_months_averages as $currency_from => $currencies_to ) {
					foreach ( $currencies_to as $currency_to => $rate ) {
						if ( $currency_from != $currency_to ) {
							$currency_rates_html .= $currency_from . $currency_to . '~' . number_format( $rate, 4 ) . '<br>';
						}
					}
				}
				$currency_rates_html .= '</pre>';
//			}
			$currency_rates_array[] = $currency_rates_html;
		}

		// Totals
		$months_array[]                          = __( 'Totals', 'woocommerce-jetpack' );
		$months_days_array[]                     = $total_months_days;
		$total_orders_array[]                    = $total_orders_total;
		$total_orders_average_array[]            = ( $total_months_days > 0 ? number_format( ( $total_orders_total / $total_months_days ), 2, '.', ',' ) : '-' );
		$total_orders_sum_array[]                = $report_currency . ' ' . number_format( $total_orders_sum_total, 2, '.', ',' );
		$total_orders_sum_excl_tax_array[]       = $report_currency . ' ' . number_format( $total_orders_sum_excl_tax_total, 2, '.', ',' );
		$total_orders_sum_average_order_array[]  = ( $total_orders_total > 0 ? $report_currency . ' ' . number_format( ( $total_orders_sum_excl_tax_total / $total_orders_total ), 2, '.', ',' ) : '-' );
		$total_orders_sum_average_array[]        = ( $total_months_days  > 0 ? $report_currency . ' ' . number_format( ( $total_orders_sum_excl_tax_total / $total_months_days ),  2, '.', ',' ) : '-' );
		$currency_rates_array[]                  = '';

		// Table
		$table_data[] = $months_array;
		$table_data[] = $months_days_array;
		$table_data[] = $total_orders_array;
		$table_data[] = $total_orders_average_array;
		$table_data[] = $total_orders_sum_array;
		$table_data[] = $total_orders_sum_excl_tax_array;
		$table_data[] = $total_orders_sum_average_order_array;
		$table_data[] = $total_orders_sum_average_array;
		$table_data[] = $currency_rates_array;
		/* foreach ( $order_currencies_array as $order_currency => $total_currency_orders ) {
			$table_data[] = array( $order_currency . ' (' . $total_currency_orders . ')' );
		} */

		$execution_time_end = microtime( true );

		// HTML
		$html = '';
		$menu = '';
		$menu .= '<ul class="subsubsub">';
		$menu .= '<li><a href="' . add_query_arg( 'year', date( 'Y' ) )         . '" class="' . ( ( $this->year == date( 'Y' ) ) ? 'current' : '' ) . '">' . date( 'Y' ) . '</a> | </li>';
		$menu .= '<li><a href="' . add_query_arg( 'year', ( date( 'Y' ) - 1 ) ) . '" class="' . ( ( $this->year == ( date( 'Y' ) - 1 ) ) ? 'current' : '' ) . '">' . ( date( 'Y' ) - 1 ) . '</a> | </li>';
		$menu .= '</ul>';
		$menu .= '<br class="clear">';
		$html .= $menu;
		$html .= '<h4>' . __( 'Report currency', 'woocommerce-jetpack' ) . ': ' . $report_currency . '</h4>';
		$months_styles = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$months_styles[] = 'width:6%;';
		}
		$html .= wcj_get_table_html( $table_data, array(
				'table_class'        => 'widefat striped',
				'table_heading_type' => 'horizontal',
				'columns_styles'     => array_merge(
					array( 'width:16%;' ),
					$months_styles,
					array( 'width:12%;font-weight:bold;' )
				),
		) );
		$html .= '<p style="font-size:x-small;"><em>' . sprintf( __( 'Report generated in: %s s', 'woocommerce-jetpack' ),
			number_format( ( $execution_time_end - $execution_time_start ), 2, '.', ',' ) ) . '</em></p>';
		$html .= '<form method="post" action="">' . '<input name="wcj_reset_currency_rates" type="submit" class="button button-primary" value="' . __( 'Reset Currency Rates', 'woocommerce-jetpack' ) . '">' . '</form>';
//		$html .= '<pre>' . print_r( get_option( 'wcj_reports_currency_rates' ), true ) . '</pre>';
		return $html;
	}
}

endif;
