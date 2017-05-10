<?php
/**
 * Booster for WooCommerce - Settings - Reports
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$reports_and_settings = array(
	array(
		'title'     => __( 'Product Sales', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => 'Orders',
		'report'    => 'booster_products_sales',
	),
	array(
		'id'        => 'wcj_reports_products_sales_display_sales',
		'desc'      => __( 'Display item sales', 'woocommerce-jetpack' ),
		'type'      => 'checkbox',
		'checkboxgroup' => 'start',
		'default'   => 'yes',
	),
	array(
		'id'        => 'wcj_reports_products_sales_display_sales_sum',
		'desc'      => __( 'Display sales sum', 'woocommerce-jetpack' ),
		'type'      => 'checkbox',
		'default'   => 'yes',
		'checkboxgroup' => '',
	),
	array(
		'id'        => 'wcj_reports_products_sales_display_profit',
		'desc'      => __( 'Display profit', 'woocommerce-jetpack' ),
		'type'      => 'checkbox',
		'default'   => 'no',
		'checkboxgroup' => '',
	),
	array(
		'id'        => 'wcj_reports_products_sales_include_taxes',
		'desc'      => __( 'Include taxes', 'woocommerce-jetpack' ),
		'type'      => 'checkbox',
		'default'   => 'no',
		'checkboxgroup' => '',
	),
	array(
		'id'        => 'wcj_reports_products_sales_count_variations',
		'desc'      => __( 'Count variations for variable products', 'woocommerce-jetpack' ),
		'type'      => 'checkbox',
		'default'   => 'no',
		'checkboxgroup' => 'end',
	),
	array(
		'title'     => __( 'Monthly Sales (with currency conversions)', 'woocommerce-jetpack' ),
		'tab'       => 'orders',
		'tab_title' => 'Orders',
		'report'    => 'booster_monthly_sales',
	),
	array(
		'title'     => __( 'Customers by Country', 'woocommerce-jetpack' ),
		'tab'       => 'customers',
		'tab_title' => 'Customers',
		'report'    => 'customers_by_country',
	),
	array(
		'title'     => __( 'Customers by Country Sets', 'woocommerce-jetpack' ),
		'tab'       => 'customers',
		'tab_title' => 'Customers',
		'report'    => 'customers_by_country_sets',
	),
	array(
		'title'     => __( 'All in Stock with sales data', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => 'Stock',
		'report'    => 'on_stock',
	),
	array(
		'title'     => __( 'Understocked products (calculated by sales data)', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => 'Stock',
		'report'    => 'understocked',
	),
	array(
		'title'     => __( 'Overstocked products (calculated by sales data)', 'woocommerce-jetpack' ),
		'tab'       => 'stock',
		'tab_title' => 'Stock',
		'report'    => 'overstocked',
	),
);
$settings = array(
	array(
		'title'    => __( 'Available Reports', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_reports_more_options'
	),
);
$button_style = "background: orange; border-color: orange; box-shadow: 0 1px 0 orange; text-shadow: 0 -1px 1px orange,1px 0 1px orange,0 1px 1px orange,-1px 0 1px orange;";
foreach ( $reports_and_settings as $report ) {
	if ( isset( $report['report'] ) ) {
		$settings = array_merge( $settings, array(
			array(
//				'title'    => 'WooCommerce > Reports > ' . $report['tab_title'] . ' > ' . $report['title'],
				'title'    => '[' . $report['tab_title'] . '] ' . $report['title'],
				'id'       => 'wcj_' . $report['report'] . '_link',
				'type'     => 'custom_link',
				'link'     => '<a class="button-primary" '
					. 'style="' . $button_style . '" '
					. 'href="' . get_admin_url() . 'admin.php?page=wc-reports&tab=' . $report['tab'] . '&report=' . $report['report'] . '">'
					. __( 'View report', 'woocommerce-jetpack' ) . '</a>',
			),
		) );
	} else {
		$settings = array_merge( $settings, array ( $report ) );
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_reports_more_options',
	),
) );
return $settings;
