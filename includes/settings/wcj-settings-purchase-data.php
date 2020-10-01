<?php
/**
 * Booster for WooCommerce - Settings - Product Cost Price
 *
 * @version 4.5.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    add options to set fields and column titles
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'     => __( 'Price Fields', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'These fields will be added to product\'s edit page and will be included in product\'s purchase cost calculation.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_price_fields_options',
	),
	array(
		'title'     => __( 'Product cost (purchase) price', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_price_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_price_extra_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Affiliate commission', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_price_affiliate_commission_enabled',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Profit Percentage Type', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Example:', 'woocommerce-jetpack' ).'<br />'.__( 'Selling: $3.000 | Buying: $1.000', 'woocommerce-jetpack' ).'<br />'.__( 'Margin: 66% | Markup: $200%', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_price_profit_percentage_type',
		'default'   => 'markup',
		'options'   => array(
			'margin'   => __( 'Margin', 'woocommerce-jetpack' ),
			'markup'   => __( 'Markup', 'woocommerce-jetpack' ),
		),
		'type'      => 'select',
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_price_fields_options',
	),
	array(
		'title'     => __( 'Custom Price Fields', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'These fields will be added to product\'s edit page and will be included in product\'s purchase cost calculation.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_price_fields_options',
	),
	array(
		'title'     => __( 'Total Custom Price Fields', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_price_fields_total_number',
		'default'   => 1,
		'type'      => 'custom_number',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'     => __( 'Custom Price Field', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'        => 'wcj_purchase_data_custom_price_field_name_' . $i,
			'desc'      => __( 'Title', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
			'default'   => '',
			'type'      => 'text',
		),
		array(
			'id'        => 'wcj_purchase_data_custom_price_field_type_' . $i,
			'desc'      => __( 'Type', 'woocommerce-jetpack' ),
			'default'   => 'fixed',
			'type'      => 'select',
			'options'   => array(
				'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
				'percent' => __( 'Percent', 'woocommerce-jetpack' ),
			),
		),
		array(
			'id'        => 'wcj_purchase_data_custom_price_field_default_value_' . $i,
			'desc'      => __( 'Default Value', 'woocommerce-jetpack' ),
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '0.0001' ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_custom_price_fields_options',
	),
	array(
		'title'     => __( 'Info Fields', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'These fields will be added to product\'s edit page.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_info_fields_options',
	),
	array(
		'title'     => __( '(Last) Purchase date', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_date_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Seller', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_partner_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Purchase info', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_info_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_info_fields_options',
	),
	array(
		'title'     => __( 'Admin Products List Custom Columns', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you add custom columns to WooCommerce admin products list.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_products_columns_options',
	),
	array(
		'title'     => __( 'Profit', 'woocommerce-jetpack' ),
		'desc'      => __( 'Add', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_products_columns_profit',
		'default'   => 'no',
		'type'      => 'checkbox',
		'desc_tip'  => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'     => __( 'Purchase Cost', 'woocommerce-jetpack' ),
		'desc'      => __( 'Add', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_products_columns_purchase_cost',
		'default'   => 'no',
		'type'      => 'checkbox',
		'desc_tip'  => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_custom_products_columns_options',
	),
	array(
		'title'     => __( 'Admin Orders List Custom Columns', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you add custom columns to WooCommerce admin orders list.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_columns_options',
	),
	array(
		'title'     => __( 'Profit', 'woocommerce-jetpack' ),
		'desc'      => __( 'Add', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_columns_profit',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Purchase Cost', 'woocommerce-jetpack' ),
		'desc'      => __( 'Add', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_custom_columns_purchase_cost',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_custom_columns_options',
	),
	array(
		'title'     => __( 'More Options', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'id'        => 'wcj_purchase_data_options',
	),
	array(
		'title'     => __( 'Treat Variable Products as Simple Products', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_purchase_data_variable_as_simple_enabled',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_purchase_data_options',
	),
) );
return $settings;
