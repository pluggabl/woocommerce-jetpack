<?php
/**
 * Booster for WooCommerce Settings - Orders
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_admin_currency_options',
	),
	array(
		'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled this will add "Booster: Orders" metabox to each order\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_currency',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Admin Order Currency Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Choose if you want changed order currency to be saved directly to DB, or if you want to use filter. When using <em>filter</em> method, changes will be active only when "Admin Order Currency" section is enabled. When using <em>directly to DB</em> method, changes will be permanent, that is even if Booster plugin is removed.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_currency_method',
		'default'  => 'filter',
		'type'     => 'select',
		'options'  => array(
			'filter' => __( 'Filter', 'woocommerce-jetpack' ),
			'db'     => __( 'Directly to DB', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_admin_currency_options',
	),
	array(
		'title'    => __( 'Orders Auto-Complete', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you enable orders auto-complete function.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_auto_complete_options',
	),
	array(
		'title'    => __( 'Auto-complete all WooCommerce orders', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'E.g. if you sell digital products then you are not shipping anything and you may want auto-complete all your orders.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_auto_complete_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_auto_complete_options',
	),
	array(
		'title'    => __( 'Admin Orders List Custom Columns', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you add custom columns to WooCommerce orders list.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_list_custom_columns_options',
	),
	array(
		'title'    => __( 'Billing Country', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add column and filtering', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_list_custom_columns_country',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Currency Code', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add column and filtering', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_list_custom_columns_currency',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom Columns Total Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_list_custom_columns_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
);
$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Custom Column', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Key:', 'woocommerce-jetpack' ) . ' <code>' . 'wcj_orders_custom_column_' . $i . '</code>',
			'id'       => 'wcj_orders_list_custom_columns_enabled_' . $i,
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'Label', 'woocommerce-jetpack' ),
			'id'       => 'wcj_orders_list_custom_columns_label_' . $i,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'You can use shortcodes and/or HTML here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_orders_list_custom_columns_value_' . $i,
			'default'  => '',
			'type'     => 'custom_textarea',
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'Sortable', 'woocommerce-jetpack' ),
			'id'       => 'wcj_orders_list_custom_columns_sortable_' . $i,
			'default'  => 'no',
			'type'     => 'select',
			'options'  => array(
				'no'             => __( 'No', 'woocommerce-jetpack' ),
				'meta_value'     => __( 'By meta (as text)', 'woocommerce-jetpack' ),
				'meta_value_num' => __( 'By meta (as numbers)', 'woocommerce-jetpack' ),
			),
			'css'      => 'min-width:300px;',
		),
		array(
			'desc'     => __( 'Key (if sortable)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_orders_list_custom_columns_sortable_key_' . $i,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_orders_list_custom_columns_options',
	),
	array(
		'title'    => __( 'Admin Orders List Multiple Status', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_admin_list_multiple_status_options',
	),
	array(
		'title'    => __( 'Multiple Status Filtering', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_list_multiple_status_filter',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'              => __( 'Do not add', 'woocommerce-jetpack' ),
			'multiple_select' => __( 'Add as multiple select', 'woocommerce-jetpack' ),
			'checkboxes'      => __( 'Add as checkboxes', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Hide Default Statuses Menu', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_list_hide_default_statuses_menu',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Add "Not Completed" Status Link to Default Statuses Menu', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_list_multiple_status_not_completed_link',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_admin_list_multiple_status_options',
	),
	array(
		'title'    => __( 'Admin Orders List Columns Order', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_admin_list_columns_order_options',
	),
	array(
		'title'    => __( 'Columns Order', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_list_columns_order_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_order_admin_list_columns_order',
		'desc_tip' => __( 'Default columns order', 'woocommerce-jetpack' ) . ':<br>' . str_replace( PHP_EOL, '<br>', $this->get_orders_default_columns_in_order() ),
		'default'  => $this->get_orders_default_columns_in_order(),
		'type'     => 'textarea',
		'css'      => 'height:300px;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_admin_list_columns_order_options',
	),
) );
return $settings;
