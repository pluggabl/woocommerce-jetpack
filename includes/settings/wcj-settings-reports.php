<?php
/**
 * Booster for WooCommerce - Settings - Reports
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$reports_and_settings = array(
	array(
		'id'   => 'reports_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'reports_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'reports_options_orders_reports_tab'      => __( 'Orders Reports', 'woocommerce-jetpack' ),
			'reports_endpoints_customers_reports_tab' => __( 'Customers Reports', 'woocommerce-jetpack' ),
			'reports_pages_stock_reports_tab'         => __( 'Stock Reports', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'reports_options_orders_reports_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Orders Reports', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_reports_orders_options',
	),
	array(
		'title'     => __( 'Product Sales (Daily)', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => __( 'Orders', 'woocommerce-jetpack' ),
		'report'    => 'booster_products_sales_daily',
	),
	array(
		'id'       => 'wcj_reports_products_sales_daily_columns',
		'desc'     => __( 'Report columns', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set empty to include all columns.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'default'  => '',
		'options'  => wcj_get_product_sales_daily_report_columns(),
		'class'    => 'chosen_select',
	),
	array(
		'id'       => 'wcj_reports_products_sales_daily_order_statuses',
		'desc'     => __( 'Order statuses', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set empty to include all statuses.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'default'  => '',
		'options'  => wc_get_order_statuses(),
		'class'    => 'chosen_select',
	),
	array(
		'id'            => 'wcj_reports_products_sales_daily_include_taxes',
		'desc'          => __( 'Include taxes', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => 'start',
	),
	array(
		'id'            => 'wcj_reports_products_sales_daily_count_variations',
		'desc'          => __( 'Count variations for variable products', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => 'end',
	),
	array(
		'title'     => __( 'Product Sales (Monthly)', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => __( 'Orders', 'woocommerce-jetpack' ),
		'report'    => 'booster_products_sales',
	),
	array(
		'id'            => 'wcj_reports_products_sales_display_sales',
		'desc'          => __( 'Display item sales', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
		'default'       => 'yes',
	),
	array(
		'id'            => 'wcj_reports_products_sales_display_sales_sum',
		'desc'          => __( 'Display sales sum', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'yes',
		'checkboxgroup' => '',
	),
	array(
		'id'            => 'wcj_reports_products_sales_display_profit',
		'desc'          => __( 'Display profit', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => '',
	),
	array(
		'id'            => 'wcj_reports_products_sales_include_taxes',
		'desc'          => __( 'Include taxes', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => '',
	),
	array(
		'id'            => 'wcj_reports_products_sales_count_variations',
		'desc'          => __( 'Count variations for variable products', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => 'end',
	),
	array(
		'title'     => __( 'Monthly Sales (with Currency Conversion)', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => __( 'Orders', 'woocommerce-jetpack' ),
		'report'    => 'booster_monthly_sales',
	),
	array(
		'id'            => 'wcj_reports_orders_monthly_sales_include_today',
		'desc'          => __( 'Include current day for current month', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => 'start',
	),
	array(
		'id'            => 'wcj_reports_orders_monthly_sales_forecast',
		'desc'          => __( 'Forecast total orders and sum (excl. TAX) for current month and year', 'woocommerce-jetpack' ),
		'type'          => 'checkbox',
		'default'       => 'no',
		'checkboxgroup' => 'end',
	),
	array(
		'title'     => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => __( 'Orders', 'woocommerce-jetpack' ),
		'report'    => 'booster_gateways',
	),
	array(
		'id'   => 'wcj_reports_orders_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'reports_options_orders_reports_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'reports_endpoints_customers_reports_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Customers Reports', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_reports_customers_options',
	),
	array(
		'title'     => __( 'Customers by Country', 'woocommerce-jetpack' ),
		'tab'       => 'customers',
		'tab_title' => __( 'Customers', 'woocommerce-jetpack' ),
		'report'    => 'customers_by_country',
	),
	array(
		'title'     => __( 'Customers by Country Sets', 'woocommerce-jetpack' ),
		'tab'       => 'customers',
		'tab_title' => __( 'Customers', 'woocommerce-jetpack' ),
		'report'    => 'customers_by_country_sets',
	),
	array(
		'id'   => 'wcj_reports_customers_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'reports_endpoints_customers_reports_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'reports_pages_stock_reports_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Stock Reports', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_reports_stock_options',
	),
	array(
		'title'     => __( 'All in Stock with sales data', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => __( 'Stock', 'woocommerce-jetpack' ),
		'report'    => 'on_stock',
	),
	array(
		'title'     => __( 'Understocked products (calculated by sales data)', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => __( 'Stock', 'woocommerce-jetpack' ),
		'report'    => 'understocked',
	),
	array(
		'title'     => __( 'Overstocked products (calculated by sales data)', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => __( 'Stock', 'woocommerce-jetpack' ),
		'report'    => 'overstocked',
	),
	array(
		'id'       => 'wcj_reports_stock_product_type',
		'desc'     => __( 'product type', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Product type for all "Stock" reports.', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'default'  => 'product',
		'options'  => array(
			'product'           => __( 'Products', 'woocommerce-jetpack' ),
			'product_variation' => __( 'Variations', 'woocommerce-jetpack' ),
			'both'              => __( 'Both products and variations', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'       => 'wcj_reports_stock_include_deleted_products',
		'desc'     => __( 'Include deleted products', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Include deleted products in all "Stock" reports.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'default'  => 'yes',
	),
	array(
		'id'   => 'wcj_reports_stock_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'reports_pages_stock_reports_tab',
		'type' => 'tab_end',
	),
);
$settings             = array();
$button_style         = 'background: orange; border-color: orange; box-shadow: 0 1px 0 orange; text-shadow: 0 -1px 1px orange,1px 0 1px orange,0 1px 1px orange,-1px 0 1px orange;';
foreach ( $reports_and_settings as $report ) {
	if ( isset( $report['report'] ) ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title'    => $report['title'],
					'desc_tip' => 'WooCommerce &gt; Reports &gt; ' . $report['tab_title'] . ' &gt; ' . $report['title'],
					'id'       => 'wcj_' . $report['report'] . '_link',
					'type'     => 'custom_link',
					'link'     => '<a class="button-primary" '
						. 'style="' . $button_style . '" '
						. 'href="' . get_admin_url() . 'admin.php?page=wc-reports&tab=' . $report['tab'] . '&report=' . $report['report'] . '">'
						. __( 'View report', 'woocommerce-jetpack' ) . '</a>',
				),
			)
		);
	} else {
		$settings = array_merge( $settings, array( $report ) );
	}
}
return $settings;
