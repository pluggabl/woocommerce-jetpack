<?php
/**
 * Booster for WooCommerce - Settings - Export
 *
 * @version 5.1.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    add "Additional Export Fields" for "Customers from Orders" and (maybe) "Customers"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Export Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_options',
	),
	array(
		'title'    => __( 'CSV Separator', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_csv_separator',
		'default'  => ',',
		'type'     => 'text',
	),
	array(
		'title'             => __( 'Smart Formatting', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message = apply_filters( 'booster_message', '', 'desc' ) ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'id'                => 'wcj_export_csv_smart_formatting',
		'desc_tip'          => sprintf( __( 'Tries to handle special characters as commas and quotes, formatting fields according to <a href="%s">RFC4180</a>', 'woocommerce-jetpack' ), 'https://tools.ietf.org/html/rfc4180' ),
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'    => __( 'UTF-8 BOM', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Add UTF-8 BOM sequence', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_csv_add_utf_8_bom',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_options',
	),
	array(
		'title'    => __( 'Export Orders Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_orders_options',
	),
	array(
		'title'    => __( 'Export Orders Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_orders_fields',
		'default'  => $this->fields_helper->get_order_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_order_export_fields(),
		'css'      => 'height:300px;',
	),
	array(
		'title'    => __( 'Additional Export Orders Fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_orders_fields_additional_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_export_orders_fields_additional_enabled_' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_fields_additional_title_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
		array(
			'desc'     => __( 'Type', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_fields_additional_type_' . $i,
			'type'     => 'select',
			'default'  => 'meta',
			'options'  => array(
				'meta'      => __( 'Order Meta', 'woocommerce-jetpack' ),
				'shortcode' => __( 'Order Shortcode', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'     => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
				' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders shortcodes here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_fields_additional_value_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_orders_options',
	),
	array(
		'title'    => __( 'Export Orders Items Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_orders_items_options',
	),
	array(
		'title'    => __( 'Export Orders Items Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_orders_items_fields',
		'default'  => $this->fields_helper->get_order_items_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_order_items_export_fields(),
		'css'      => 'height:300px;',
	),
	array(
		'title'    => __( 'Additional Export Orders Items Fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_orders_items_fields_additional_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
) );
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_orders_items_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_export_orders_items_fields_additional_enabled_' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_items_fields_additional_title_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
		array(
			'desc'     => __( 'Type', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_items_fields_additional_type_' . $i,
			'type'     => 'select',
			'default'  => 'meta',
			'options'  => array(
				'meta'              => __( 'Order Meta', 'woocommerce-jetpack' ),
				'item_meta'         => __( 'Order Item Meta', 'woocommerce-jetpack' ),
				'shortcode'         => __( 'Order Shortcode', 'woocommerce-jetpack' ),
				'meta_product'      => __( 'Product Meta', 'woocommerce-jetpack' ),
				'shortcode_product' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'     => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter order/product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
				' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Orders/Products shortcodes here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_orders_items_fields_additional_value_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_orders_items_options',
	),
	array(
		'title'    => __( 'Export Products Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_products_options',
	),
	array(
		'title'    => __( 'Export Products Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_products_fields',
		'default'  => $this->fields_helper->get_product_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_product_export_fields(),
		'css'      => 'height:300px;',
	),
	array(
		'title'    => __( 'Variable Products', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_products_variable',
		'default'  => 'variable_only',
		'type'     => 'select',
		'options'  => array(
			'variable_only'           => __( 'Export variable (main) product only', 'woocommerce-jetpack' ),
			'variations_only'         => __( 'Export variation products only', 'woocommerce-jetpack' ),
			'variable_and_variations' => __( 'Export variable (main) and variation products', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Additional Export Products Fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_products_fields_additional_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
				apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
) );
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_export_products_fields_additional_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Field', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_export_products_fields_additional_enabled_' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_products_fields_additional_title_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
		array(
			'desc'     => __( 'Type', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_products_fields_additional_type_' . $i,
			'type'     => 'select',
			'default'  => 'meta',
			'options'  => array(
				'meta'      => __( 'Product Meta', 'woocommerce-jetpack' ),
				'shortcode' => __( 'Product Shortcode', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'     => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'If field\'s "Type" is set to "Meta", enter product meta key to retrieve (can be custom field name).', 'woocommerce-jetpack' ) .
				' ' . __( 'If it\'s set to "Shortcode", use Booster\'s Products shortcodes here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_export_products_fields_additional_value_' . $i,
			'type'     => 'text',
			'default'  => '',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_products_options',
	),
	array(
		'title'    => __( 'Export Customers Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_customers_options',
	),
	array(
		'title'    => __( 'Export Customers Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_customers_fields',
		'default'  => $this->fields_helper->get_customer_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_customer_export_fields(),
		'css'      => 'height:150px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_customers_options',
	),
	array(
		'title'    => __( 'Export Customers from Orders Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_export_customers_from_orders_options',
	),
	array(
		'title'    => __( 'Export Customers from Orders Fields', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hold "Control" key to select multiple fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_export_customers_from_orders_fields',
		'default'  => $this->fields_helper->get_customer_from_order_export_default_fields_ids(),
		'type'     => 'multiselect',
		'options'  => $this->fields_helper->get_customer_from_order_export_fields(),
		'css'      => 'height:150px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_export_customers_from_orders_options',
	),
) );
return $settings;
