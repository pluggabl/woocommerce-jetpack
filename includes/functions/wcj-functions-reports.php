<?php
/**
 * Booster for WooCommerce - Functions - Reports
 *
 * @version 6.0.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_get_product_sales_daily_report_columns' ) ) {
	/**
	 * Wcj_get_product_sales_daily_report_columns.
	 *
	 * @version 6.0.0
	 * @since   2.9.0
	 */
	function wcj_get_product_sales_daily_report_columns() {
		return array(
			'date'                   => __( 'Date', 'woocommerce-jetpack' ),
			'daily_total_sum'        => __( 'Daily Total Sum', 'woocommerce-jetpack' ),
			'daily_total_quantity'   => __( 'Daily Total Quantity', 'woocommerce-jetpack' ),
			'product_id'             => __( 'Product ID', 'woocommerce-jetpack' ),
			'item_title'             => __( 'Item Title', 'woocommerce-jetpack' ),
			'item_quantity'          => __( 'Quantity', 'woocommerce-jetpack' ),
			'sum'                    => __( 'Sum', 'woocommerce-jetpack' ),
			'profit'                 => __( 'Profit', 'woocommerce-jetpack' ),
			'last_sale'              => __( 'Last Sale date', 'woocommerce-jetpack' ),
			'last_sale_order_id'     => __( 'Last Sale Order ID', 'woocommerce-jetpack' ),
			'last_sale_order_status' => __( 'Last Sale Order Status', 'woocommerce-jetpack' ),
		);
	}
}

if ( ! function_exists( 'wcj_get_reports_standard_ranges' ) ) {
	/**
	 * Wcj_get_reports_standard_ranges.
	 *
	 * @version 5.6.8
	 * @since   2.9.0
	 */
	function wcj_get_reports_standard_ranges() {
		$current_time = wcj_get_timestamp_date_from_gmt();
		return array(
			'year'        => array(
				'title'      => __( 'Year', 'woocommerce' ),
				'start_date' => gmdate( 'Y-01-01', $current_time ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_month'  => array(
				'title'      => __( 'Last month', 'woocommerce' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( 'first day of previous month', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( 'last day of previous month', $current_time ) ),
			),
			'this_month'  => array(
				'title'      => __( 'This month', 'woocommerce' ),
				'start_date' => gmdate( 'Y-m-01', $current_time ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_7_days' => array(
				'title'      => __( 'Last 7 days', 'woocommerce' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-7 days', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
		);
	}
}

if ( ! function_exists( 'wcj_get_reports_custom_ranges' ) ) {
	/**
	 * Wcj_get_reports_custom_ranges.
	 *
	 * @version 5.6.8
	 * @since   2.9.0
	 * @todo    fix `-1 month` - sometimes it produces the wrong result (e.g. on current gmdate = "2018.03.30")
	 */
	function wcj_get_reports_custom_ranges() {
		$current_time = wcj_get_timestamp_date_from_gmt();
		return array(
			'last_14_days'         => array(
				'title'      => __( 'Last 14 days', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-14 days', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_30_days'         => array(
				'title'      => __( 'Last 30 days', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-30 days', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_3_months'        => array(
				'title'      => __( 'Last 3 months', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-3 months', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_6_months'        => array(
				'title'      => __( 'Last 6 months', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-6 months', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_12_months'       => array(
				'title'      => __( 'Last 12 months', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-12 months', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_24_months'       => array(
				'title'      => __( 'Last 24 months', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-24 months', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'last_36_months'       => array(
				'title'      => __( 'Last 36 months', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-36 months', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
			'same_days_last_month' => array(
				'title'      => __( 'Same days last month', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-01', strtotime( '-1 month', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 month', $current_time ) ),
			),
			'same_days_last_year'  => array(
				'title'      => __( 'Same days last year', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-01', strtotime( '-1 year', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 year', $current_time ) ),
			),
			'last_year'            => array(
				'title'      => __( 'Last year', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-01-01', strtotime( '-1 year', $current_time ) ),
				'end_date'   => gmdate( 'Y-12-31', strtotime( '-1 year', $current_time ) ),
			),
			'yesterday'            => array(
				'title'      => __( 'Yesterday', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-1 day', $current_time ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 day', $current_time ) ),
			),
			'today'                => array(
				'title'      => __( 'Today', 'woocommerce-jetpack' ),
				'start_date' => gmdate( 'Y-m-d', $current_time ),
				'end_date'   => gmdate( 'Y-m-d', $current_time ),
			),
		);
	}
}
