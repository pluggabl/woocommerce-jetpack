<?php
/**
 * WooCommerce Jetpack Mothly Sales Reports
 *
 * The WooCommerce Jetpack Mothly Sales Reports class.
 *
 * @version 2.4.8
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
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	public function get_report() {
		$html = '';
		$this->year = isset( $_GET['year'] ) ? $_GET['year'] : date( 'Y' );
		$html .= $this->get_monthly_sales_report();
		return $html;
	}

	/*
	 * get_exchange_rate_average.
	 *
	 * @version 2.4.8
	 * @since   2.4.7
	 */
	function get_exchange_rate_average( $currency_from, $currency_to, $start_date, $end_date ) {
		if ( $currency_from == $currency_to ) {
			return 1.0;
		}
		if ( 'USD' != $currency_from ) {
			// USD/$currency_from
			$url = 'https://query.yahooapis.com/v1/public/yql?q=select%20Close%20from%20yahoo.finance.historicaldata%20where%20symbol%20%3D%20%22'
				. $currency_from . '%3DX%22%20and%20startDate%20%3D%20%22'
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
			$average_currency_from = 0;
			foreach ( $exchange_rate->query->results->quote as $quote ) {
				$average_currency_from += $quote->Close;
			}
			$average_currency_from = $average_currency_from / count( $exchange_rate->query->results->quote );
		} else {
			$average_currency_from = 1.0;
		}
		if ( 'USD' != $currency_to ) {
			// USD/$currency_to
			$url = 'https://query.yahooapis.com/v1/public/yql?q=select%20Close%20from%20yahoo.finance.historicaldata%20where%20symbol%20%3D%20%22'
				. $currency_to . '%3DX%22%20and%20startDate%20%3D%20%22'
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
			$average_currency_to = 0;
			foreach ( $exchange_rate->query->results->quote as $quote ) {
				$average_currency_to += $quote->Close;
			}
			$average_currency_to = $average_currency_to / count( $exchange_rate->query->results->quote );
		} else {
			$average_currency_to = 1.0;
		}
		return $average_currency_to / $average_currency_from;
	}

	/*
	 * get_monthly_sales_report.
	 *
	 * @version 2.4.8
	 * @since   2.4.7
	 */
	function get_monthly_sales_report() {
		$block_size = 96;
		$table_data = array();
		$months_array = array( '' );
		$total_orders_array = array( __( 'Total Orders', 'woocommerce-jetpack' ) );
		$total_orders_sum_array = array( __( 'Total Sum', 'woocommerce-jetpack' ) . '<br>' . __( 'Total Sum (excl. TAX)', 'woocommerce-jetpack' ) );
		$order_currencies_array = array(); // TODO
		$report_currency = ( isset( $_GET['currency'] ) && 'merge' != $_GET['currency'] ) ? $_GET['currency'] : get_woocommerce_currency();
		for ( $i = 1; $i <= 12; $i++ ) {
			$current_months_averages = array();
			$total_orders = 0;
			$total_orders_sum = 0;
			$total_orders_sum_excl_tax = 0;
			$offset = 0;
			while( true ) {
				$args_orders = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'wc-completed',
					'posts_per_page' => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'date_query' => array(
						array(
							'year'  => $this->year,
							'month' => $i,
						),
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
						$start_date = $this->year . '-' . sprintf( '%02d', $i ) . '-'. '01';
						$end_date   = date( 'Y-m-t', strtotime( $start_date ) );
						$current_months_averages[ $order_currency ][ $report_currency ] = $this->get_exchange_rate_average(
							$order_currency,
							$report_currency,
							$start_date,
							$end_date
						);
						// TODO: take not monthly average, but "Close" of closest day
					}
					$total_orders_sum += $order_total * $current_months_averages[ $order_currency ][ $report_currency ];
					$total_orders_sum_excl_tax += $order_total_excl_tax * $current_months_averages[ $order_currency ][ $report_currency ];
				endwhile;
				$offset += $block_size;
			}
			$months_array[] = date_i18n( 'F', mktime( 0, 0, 0, $i, 10 ) )/*  . '[' . $start_date . ' ~ ' . $end_date . ']' */;
			$total_orders_array[] = $total_orders;

			$total_orders_result_html = $report_currency . ' ' . number_format( $total_orders_sum, 2, '.', ',' );
			//if ( $total_orders_sum != $total_orders_sum_excl_tax) {
				$total_orders_result_html .= '<br>' . $report_currency . ' ' . number_format( $total_orders_sum_excl_tax, 2, '.', ',' );
			//}
			/* $total_orders_result_html .= '<br>' . $report_currency . ' ' . number_format( $total_orders_sum_excl_tax * 0.965, 2, '.', ',' ); // TODO !!!;
			$total_orders_result_html .= '<br>' . $report_currency . ' ' . number_format( $total_orders_sum_excl_tax * 0.965 * 0.80, 2, '.', ',' ); // TODO !!!; */
//			if ( isset( $_GET['show_rates'] ) ) { // TODO
				ksort( $current_months_averages, true );
//				$total_orders_result_html .= '<pre style="font-size:8px;">' . print_r( $current_months_averages, true ) . '</pre>';
				$total_orders_result_html .= '<pre style="font-size:x-small;">';
				foreach ( $current_months_averages as $currency_from => $currencies_to ) {
					foreach ( $currencies_to as $currency_to => $rate ) {
						$total_orders_result_html .= $currency_from . $currency_to . '~' . number_format( $rate, 4 ) . '<br>';
					}
				}
				$total_orders_result_html .= '</pre>';
//			}
			$total_orders_sum_array[] = $total_orders_result_html;
		}
		$table_data[] = $months_array;
		$table_data[] = $total_orders_array;
		$table_data[] = $total_orders_sum_array;
		/* foreach ( $order_currencies_array as $order_currency => $total_currency_orders ) {
			$table_data[] = array( $order_currency . ' (' . $total_currency_orders . ')' );
		} */
		$html = '';
		$menu = '';
		$menu .= '<ul class="subsubsub">';
		$menu .= '<li><a href="' . add_query_arg( 'year', date( 'Y' ) )         . '" class="' . ( ( $this->year == date( 'Y' ) ) ? 'current' : '' ) . '">' . date( 'Y' ) . '</a> | </li>';
		$menu .= '<li><a href="' . add_query_arg( 'year', ( date( 'Y' ) - 1 ) ) . '" class="' . ( ( $this->year == ( date( 'Y' ) - 1 ) ) ? 'current' : '' ) . '">' . ( date( 'Y' ) - 1 ) . '</a> | </li>';
		$menu .= '</ul>';
		$menu .= '<br class="clear">';
		$html .= $menu;
		$html .= '<h4>' . __( 'Report currency', 'woocommerce-jetpack' ) . ': ' . $report_currency . '</h4>';
		$html .= wcj_get_table_html( $table_data, array(
				'table_class'        => 'widefat striped',
				'table_heading_type' => 'horizontal',
				'columns_styles'     => array(
					'width:16%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
					'width:7%;',
				),
		) );
		return $html;
	}
}

endif;
