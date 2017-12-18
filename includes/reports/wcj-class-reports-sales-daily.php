<?php
/**
 * Booster for WooCommerce - Reports - Product Sales (Daily)
 *
 * @version 3.2.4
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Reports_Product_Sales_Daily' ) ) :

class WCJ_Reports_Product_Sales_Daily {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function __construct( $args = null ) {
		return true;
	}

	/**
	 * get_report.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_report() {
		$this->get_report_args();
		$this->get_report_data();
		return $this->output_report_data();
	}

	/*
	 * get_report_args.
	 *
	 * @version 3.2.4
	 * @since   2.9.0
	 */
	function get_report_args() {
		$current_time = (int) current_time( 'timestamp' );
		$this->start_date    = isset( $_GET['start_date'] )    ? $_GET['start_date']    : date( 'Y-m-d', strtotime( '-7 days', $current_time ) );
		$this->end_date      = isset( $_GET['end_date'] )      ? $_GET['end_date']      : date( 'Y-m-d', $current_time );
		$this->product_title = isset( $_GET['product_title'] ) ? $_GET['product_title'] : '';
	}

	/*
	 * get_report_data.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) currency conversion
	 * @todo    recheck if `wc_get_product_purchase_price()` working correctly for variations
	 */
	function get_report_data() {
		$include_taxes    = ( 'yes' === get_option( 'wcj_reports_products_sales_daily_include_taxes', 'no' ) );
		$count_variations = ( 'yes' === get_option( 'wcj_reports_products_sales_daily_count_variations', 'no' ) );
		$order_statuses   = get_option( 'wcj_reports_products_sales_daily_order_statuses', '' );
		if ( empty( $order_statuses ) ) {
			$order_statuses = 'any';
		} elseif ( 1 == count( $order_statuses ) ) {
			$order_statuses = $order_statuses[0];
		}
		$this->sales_by_day       = array();
		$this->total_sales_by_day = array();
		$this->purchase_data      = array();
		$this->last_sale_data     = array();
		$this->total_orders       = 0;
		$offset     = 0;
		$block_size = 512;
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => $order_statuses,
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'after'     => $this->start_date,
						'before'    => $this->end_date,
						'inclusive' => true,
					),
				),
			);
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) {
				break;
			}
			foreach ( $loop_orders->posts as $order_id ) {
				$order = wc_get_order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					// Filtering by product title
					if ( '' != $this->product_title && false === stripos( $item['name'], $this->product_title ) ) {
						continue;
					}
					// Preparing data
					$product_id = ( 0 != $item['variation_id'] && $count_variations ) ? $item['variation_id'] : $item['product_id'];
					$order_day_date  = get_the_date( 'Y-m-d', $order_id );
					$sale_line_total = $item['line_total'] + ( $include_taxes ? $item['line_tax'] : 0 );
					// Total sales by day
					if ( ! isset( $this->total_sales_by_day[ $order_day_date ] ) ) {
						$this->total_sales_by_day[  $order_day_date ] = array( 'qty' => 0, 'sum' => 0 );
					}
					$this->total_sales_by_day[  $order_day_date ]['qty'] += $item['qty'];
					$this->total_sales_by_day[  $order_day_date ]['sum'] += $sale_line_total;
					// Sales by day by product
					if ( ! isset( $this->sales_by_day[ $order_day_date ] ) ) {
						$this->sales_by_day[ $order_day_date ] = array();
					}
					if ( ! isset( $this->sales_by_day[ $order_day_date ][ $product_id ] ) ) {
						$this->sales_by_day[ $order_day_date ][ $product_id ] = array( 'qty' => 0, 'sum' => 0 );
					}
					if ( $count_variations ) {
						$this->sales_by_day[ $order_day_date ][ $product_id ]['name'] = $item['name'];
					} else {
						$this->sales_by_day[ $order_day_date ][ $product_id ]['name'][] = $item['name'];
					}
					$this->sales_by_day[ $order_day_date ][ $product_id ]['qty'] += $item['qty'];
					$this->sales_by_day[ $order_day_date ][ $product_id ]['sum'] += $sale_line_total;
					// Purchase data
					if ( ! isset( $this->purchase_data[ $product_id ] ) ) {
						$this->purchase_data[ $product_id ] = wc_get_product_purchase_price( $product_id );
					}
					// Last Sale Time
					if ( ! isset( $this->last_sale_data[ $product_id ] ) ) {
						$this->last_sale_data[ $product_id ]['date']         = get_the_time( 'Y-m-d H:i:s', $order_id );
						$this->last_sale_data[ $product_id ]['order_id']     = $order_id;
						$this->last_sale_data[ $product_id ]['order_status'] = get_post_status( $order_id );
					}
				}
				$this->total_orders++;
			}
			$offset += $block_size;
		}
	}

	/*
	 * output_report_data.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function output_report_data() {
		return $this->output_report_header() . $this->output_report_results();
	}

	/*
	 * output_report_header.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function output_report_header() {
		// Settings link and dates menu
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=reports' ) . '">' .
			'<< ' . __( 'Reports Settings', 'woocommerce-jetpack' ) . '</a>';
		$menu = '';
		$menu .= '<ul class="subsubsub">';
		foreach ( array_merge( wcj_get_reports_standard_ranges(), wcj_get_reports_custom_ranges() ) as $custom_range ) {
			$menu .= '<li><a ' .
				'href="' . add_query_arg( array( 'start_date' => $custom_range['start_date'], 'end_date' => $custom_range['end_date'] ) ) . '" ' .
				'class="' . ( ( $this->start_date == $custom_range['start_date'] && $this->end_date == $custom_range['end_date'] ) ? 'current' : '' ) . '"' .
			'>' . $custom_range['title'] . '</a> | </li>';
		}
		$menu .= '</ul>';
		$menu .= '<br class="clear">';
		// Product and date filter form
		$filter_form = '';
		$filter_form .= '<form method="get" action="">';
		$filter_form .= '<input type="hidden" name="page" value="'       . $_GET['page']     . '" />';
		$filter_form .= '<input type="hidden" name="tab" value="'        . $_GET['tab']      . '" />';
		$filter_form .= '<input type="hidden" name="report" value="'     . $_GET['report']   . '" />';
		$filter_form .= '<label style="font-style:italic;" for="start_date">' . __( 'From:', 'woocommerce-jetpack' ) . '</label>' . ' ' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js_v2( 'Y-m-d' ) . '" name="start_date" title="" value="' . $this->start_date . '" />';
		$filter_form .= ' ';
		$filter_form .= '<label style="font-style:italic;" for="end_date">' . __( 'To:', 'woocommerce-jetpack' ) . '</label>' . ' ' .
			'<input type="text" display="date" dateformat="' . wcj_date_format_php_to_js_v2( 'Y-m-d' ) . '" name="end_date" title="" value="' . $this->end_date . '" />';
		$filter_form .= ' ';
		$filter_form .= '<label style="font-style:italic;" for="product_title">' . __( 'Product:', 'woocommerce-jetpack' ) . '</label>' . ' ' .
			'<input type="text" name="product_title" id="product_title" title="" value="' . $this->product_title . '" />';
		$filter_form .= '<input type="submit" value="' . __( 'Filter', 'woocommerce-jetpack' ) . '" />';
		$filter_form .= '</form>';
		// Final result
		return '<p>' . $settings_link . '</p>' . '<p>' . $menu . '</p>' . '<p>' . $filter_form . '</p>';
	}

	/*
	 * output_report_results.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function output_report_results() {
		$table_data = array();
		$table_header   = array();
		$all_columns    = wcj_get_product_sales_daily_report_columns();
		$report_columns = get_option( 'wcj_reports_products_sales_daily_columns', '' );
		if ( empty( $report_columns ) ) {
			$report_columns = array_keys( $all_columns );
		}
		foreach ( $report_columns as $report_column ) {
			$table_header[] = $all_columns[ $report_column ];
		}
		$table_data[] = $table_header;
		$totals = array(
			'qty'    => 0,
			'sum'    => 0,
			'profit' => 0,
		);
		foreach ( $this->sales_by_day as $day_date => $day_sales ) {
			$day_date_info['date']                 = $day_date;
			$day_date_info['daily_total_sum']      = wc_price( $this->total_sales_by_day[ $day_date ]['sum'] );
			$day_date_info['daily_total_quantity'] = $this->total_sales_by_day[ $day_date ]['qty'];
			foreach ( $day_sales as $product_id => $product_day_sales ) {
				$row = array();
				foreach ( $report_columns as $report_column ) {
					switch ( $report_column ) {
						case 'date':
							$row[] = $day_date_info['date'];
							$day_date_info['date'] = '';
							break;
						case 'daily_total_sum':
							$row[] = $day_date_info['daily_total_sum'];
							$day_date_info['daily_total_sum'] = '';
							break;
						case 'daily_total_quantity':
							$row[] = $day_date_info['daily_total_quantity'];
							$day_date_info['daily_total_quantity'] = '';
							break;
						case 'product_id':
							$row[] = '<a target="_blank" href="' . get_permalink( $product_id ) . '">' . $product_id . '</a>';
							break;
						case 'item_title':
							$row[] = ( is_array( $product_day_sales['name'] ) ? implode( ', ', array_unique( $product_day_sales['name'] ) ) : $product_day_sales['name'] );
							break;
						case 'item_quantity':
							$row[] = $product_day_sales['qty'];
							$totals['qty'] += $product_day_sales['qty'];
							break;
						case 'sum':
							$row[] = wc_price( $product_day_sales['sum'] );
							$totals['sum'] += $product_day_sales['sum'];
							break;
						case 'profit':
							$profit = $product_day_sales['sum'] - $this->purchase_data[ $product_id ] * $product_day_sales['qty'];
							$row[] = wc_price( $profit );
							$totals['profit'] += $profit;
							break;
						case 'last_sale':
							$row[] = $this->last_sale_data[ $product_id ]['date'];
							break;
						case 'last_sale_order_id':
							$row[] = $this->last_sale_data[ $product_id ]['order_id'];
							break;
						case 'last_sale_order_status':
							$row[] = $this->last_sale_data[ $product_id ]['order_status'];
							break;
					}
				}
				$table_data[] = $row;
			}
		}
		$display_totals = false;
		$totals_row     = array();
		foreach ( $report_columns as $report_column ) {
			switch ( $report_column ) {
				case 'item_quantity':
					$totals_row[] = '<strong>' . sprintf( __( 'Total: %d', 'woocommerce-jetpack' ), $totals['qty'] ) . '</strong>';
					$display_totals = true;
					break;
				case 'sum':
					$totals_row[] = '<strong>' . sprintf( __( 'Total: %s', 'woocommerce-jetpack' ), wc_price( $totals['sum'] ) ) . '</strong>';
					$display_totals = true;
					break;
				case 'profit':
					$totals_row[] = '<strong>' . sprintf( __( 'Total: %s', 'woocommerce-jetpack' ), wc_price( $totals['profit'] ) ) . '</strong>';
					$display_totals = true;
					break;
				default:
					$totals_row[] = '';
			}
		}
		if ( $display_totals ) {
			$table_data[] = $totals_row;
		}
		$result = ( ! empty( $this->sales_by_day ) ) ?
			wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'none' ) ) .
				'<p><em>' . sprintf( __( 'Total orders: %d', 'woocommerce-jetpack' ), $this->total_orders ) . '</em></p>' :
			'<p><em>' . __( 'No sales data for current period.', 'woocommerce-jetpack' ) . '</em></p>';
		return $result;
	}
}

endif;
