<?php
/**
 * Booster for WooCommerce - Settings - Admin Orders List
 *
 * @version 7.0.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = array(
	array(
		'id'   => 'admin_orders_list_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'admin_orders_list_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'admin_orders_list_custom_columns_tab'  => __( 'Custom Columns', 'woocommerce-jetpack' ),
			'admin_orders_list_multiple_status_tab' => __( 'Multiple Status', 'woocommerce-jetpack' ),
			'admin_orders_list_columns_order_tab'   => __( 'Columns Order', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'admin_orders_list_custom_columns_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom Columns', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'This section lets you add custom columns to WooCommerce orders list.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_orders_list_custom_columns_options',
	),
	array(
		'title'   => __( 'Custom Columns', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_order_admin_list_custom_columns_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Billing Country', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add column and filtering', 'woocommerce-jetpack' ),
		'id'      => 'wcj_orders_list_custom_columns_country',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Currency Code', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add column and filtering', 'woocommerce-jetpack' ),
		'id'      => 'wcj_orders_list_custom_columns_currency',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Custom Columns Total Number', 'woocommerce-jetpack' ),
		'id'                => 'wcj_orders_list_custom_columns_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '0',
			)
		),
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'    => __( 'Custom Column', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Key:', 'woocommerce-jetpack' ) . ' <code> wcj_orders_custom_column_' . $i . '</code>',
				'id'       => 'wcj_orders_list_custom_columns_enabled_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'    => __( 'Label', 'woocommerce-jetpack' ),
				'id'      => 'wcj_orders_list_custom_columns_label_' . $i,
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:100%;',
			),
			array(
				'desc'     => __( 'Value', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can use shortcodes and/or HTML here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_value_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:100%;',
			),
			array(
				'desc'    => __( 'Sortable , Select "By meta (as text)" for date sorting', 'woocommerce-jetpack' ),
				'id'      => 'wcj_orders_list_custom_columns_sortable_' . $i,
				'default' => 'no',
				'type'    => 'select',
				'options' => array(
					'no'             => __( 'No', 'woocommerce-jetpack' ),
					'meta_value'     => __( 'By meta (as text)', 'woocommerce-jetpack' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value_num' => __( 'By meta (as numbers)', 'woocommerce-jetpack' ),
				),
			),
			array(
				/* translators: %s: translators Added */
				'desc'    => sprintf( __( 'Key (if sortable) %s Add "_" (underscore) before key if the key is from "Checkout Custom Fields module"' ), '</br>', 'woocommerce-jetpack' ),
				'id'      => 'wcj_orders_list_custom_columns_sortable_key_' . $i,
				'default' => '',
				'type'    => 'text',
			),
		)
	);
}
$settings     = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_orders_list_custom_columns_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'admin_orders_list_custom_columns_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'admin_orders_list_multiple_status_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Multiple Status', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_order_admin_list_multiple_status_options',
		),
		array(
			'title'   => __( 'Multiple Status', 'woocommerce-jetpack' ),
			'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
			'id'      => 'wcj_order_admin_list_multiple_status_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Multiple Status Filtering', 'woocommerce-jetpack' ),
			'id'      => 'wcj_order_admin_list_multiple_status_filter',
			'default' => 'no',
			'type'    => 'select',
			'options' => array(
				'no'              => __( 'Do not add', 'woocommerce-jetpack' ),
				'multiple_select' => __( 'Add as multiple select', 'woocommerce-jetpack' ),
				'checkboxes'      => __( 'Add as checkboxes', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'   => __( 'Hide Default Statuses Menu', 'woocommerce-jetpack' ),
			'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
			'id'      => 'wcj_order_admin_list_hide_default_statuses_menu',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Add "Not Completed" Status Link to Default Statuses Menu', 'woocommerce-jetpack' ),
			'desc'    => __( 'Add', 'woocommerce-jetpack' ),
			'id'      => 'wcj_order_admin_list_multiple_status_not_completed_link',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'    => __( 'Add Presets to Admin Menu', 'woocommerce-jetpack' ),
			'desc'     => '<strong>' . __( 'Add presets', 'woocommerce-jetpack' ) . '</strong>',
			'desc_tip' => __( 'To add presets, "Multiple Status Filtering" option must be enabled (as multiple select or as checkboxes).', 'woocommerce-jetpack' ),
			'id'       => 'wcj_order_admin_list_multiple_status_admin_menu',
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'desc'    => __( 'Add order counter', 'woocommerce-jetpack' ),
			'id'      => 'wcj_order_admin_list_multiple_status_admin_menu_counter',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'    => __( 'Remove original "Orders" menu', 'woocommerce-jetpack' ),
			'id'      => 'wcj_order_admin_list_multiple_status_admin_menu_remove_original',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'             => __( 'Total Presets', 'woocommerce-jetpack' ),
			'id'                => 'wcj_order_admin_list_multiple_status_presets_total_number',
			'default'           => 1,
			'type'              => 'custom_number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
	)
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_order_admin_list_multiple_status_presets_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'desc'     => __( 'Title', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Must be not empty.', 'woocommerce-jetpack' ),
				'id'       => "wcj_order_admin_list_multiple_status_presets_titles[$i]",
				'default'  => __( 'Preset', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Statuses', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Must be not empty.', 'woocommerce-jetpack' ),
				'id'       => "wcj_order_admin_list_multiple_status_presets_statuses[$i]",
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => wcj_get_order_statuses( false ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_order_admin_list_multiple_status_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'admin_orders_list_multiple_status_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'admin_orders_list_columns_order_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Columns Order', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_order_admin_list_columns_order_options',
		),
		array(
			'title'   => __( 'Columns Order', 'woocommerce-jetpack' ),
			'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
			'id'      => 'wcj_order_admin_list_columns_order_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'id'       => 'wcj_order_admin_list_columns_order',
			'desc_tip' => __( 'Default columns order', 'woocommerce-jetpack' ) . ':<br>' . str_replace( PHP_EOL, '<br>', $this->get_orders_default_columns_in_order() ),
			'default'  => $this->get_orders_default_columns_in_order(),
			'type'     => 'textarea',
			'css'      => 'height:300px;',
		),
		array(
			'id'   => 'wcj_order_admin_list_columns_order_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'admin_orders_list_columns_order_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
