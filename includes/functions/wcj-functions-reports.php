<?php
/**
 * Booster for WooCommerce - Functions - Reports
 *
 * @version 2.9.0
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_product_sales_daily_report_columns' ) ) {
	/*
	 * wcj_get_product_sales_daily_report_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_get_product_sales_daily_report_columns() {
		return array(
			'date'                   => __( 'Date', 'woocommerce-jetpack' ),
			'daily_total_sum'        => __( 'Daily Total Sum', 'woocommerce-jetpack' ),
			'daily_total_quantity'   => __( 'Daily Total Quantity', 'woocommerce-jetpack' ),
			'date'                   => __( 'Date', 'woocommerce-jetpack' ),
			'product_id'             => __( 'Product ID', 'woocommerce-jetpack' ),
			'item_title'             => __( 'Item Title', 'woocommerce-jetpack' ),
			'item_quantity'          => __( 'Quantity', 'woocommerce-jetpack' ),
			'sum'                    => __( 'Sum', 'woocommerce-jetpack' ),
			'profit'                 => __( 'Profit', 'woocommerce-jetpack' ),
			'last_sale'              => __( 'Last Sale Date', 'woocommerce-jetpack' ),
			'last_sale_order_id'     => __( 'Last Sale Order ID', 'woocommerce-jetpack' ),
			'last_sale_order_status' => __( 'Last Sale Order Status', 'woocommerce-jetpack' ),
		);
	}
}

if ( ! function_exists( 'wcj_get_reports_standard_ranges' ) ) {
	/*
	 * wcj_get_reports_standard_ranges.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_get_reports_standard_ranges() {
		return array(
			'year' => array(
				'title'      => __( 'Year', 'woocommerce' ),
				'start_date' => date( 'Y-01-01' ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_month' => array(
				'title'      => __( 'Last month', 'woocommerce' ),
				'start_date' => date( 'Y-m-d', strtotime( 'first day of previous month' ) ),
				'end_date'   => date( 'Y-m-d', strtotime( 'last day of previous month' )  ),
			),
			'this_month' => array(
				'title'      => __( 'This month', 'woocommerce' ),
				'start_date' => date( 'Y-m-01' ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_7_days' => array(
				'title'      => __( 'Last 7 days', 'woocommerce' ),
				'start_date' => date( 'Y-m-d', strtotime( '-7 days' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
		);
	}
}

if ( ! function_exists( 'wcj_get_reports_custom_ranges' ) ) {
	/*
	 * wcj_get_reports_custom_ranges.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function wcj_get_reports_custom_ranges() {
		return array(
			'last_14_days' => array(
				'title'      => __( 'Last 14 days', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-14 days' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_30_days' => array(
				'title'      => __( 'Last 30 days', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-30 days' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_3_months' => array(
				'title'      => __( 'Last 3 months', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-3 months' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_6_months' => array(
				'title'      => __( 'Last 6 months', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-6 months' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_12_months' => array(
				'title'      => __( 'Last 12 months', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-12 months' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_24_months' => array(
				'title'      => __( 'Last 24 months', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-24 months' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'last_36_months' => array(
				'title'      => __( 'Last 36 months', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-36 months' ) ),
				'end_date'   => date( 'Y-m-d' ),
			),
			'same_days_last_month' => array(
				'title'      => __( 'Same days last month', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-01', strtotime( '-1 month' ) ),
				'end_date'   => date( 'Y-m-d', strtotime( '-1 month' ) ),
			),
			'same_days_last_year' => array(
				'title'      => __( 'Same days last year', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-01', strtotime( '-1 year' ) ),
				'end_date'   => date( 'Y-m-d', strtotime( '-1 year' ) ),
			),
			'last_year' => array(
				'title'      => __( 'Last year', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-01-01', strtotime( '-1 year' ) ),
				'end_date'   => date( 'Y-12-31', strtotime( '-1 year' ) ),
			),
			'yesterday' => array(
				'title'      => __( 'Yesterday', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( '-1 day' ) ),
				'end_date'   => date( 'Y-m-d', strtotime( '-1 day' ) ),
			),
			'today' => array(
				'title'      => __( 'Today', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d' ),
				'end_date'   => date( 'Y-m-d' ),
			),
			/*
			'last_week' => array(
				'title'      => __( 'Last week', 'woocommerce-jetpack' ),
				'start_date' => date( 'Y-m-d', strtotime( 'last monday' ) ),
				'end_date'   => date( 'Y-m-d', strtotime( 'last sunday' ) ),
			),
			*/
		);
	}
}
