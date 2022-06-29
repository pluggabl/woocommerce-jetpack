<?php
/**
 * Booster for WooCommerce - Settings - Admin Products List
 *
 * @version 5.6.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = array(
	array(
		'title' => __( 'Custom Columns', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_products_admin_list_custom_columns_options',
	),
	array(
		'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_products_admin_list_custom_columns_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Custom Columns Total Number', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Save module\'s settings after changing this option to see new settings fields.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_admin_list_custom_columns_total_number',
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
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'    => __( 'Custom Column', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Key:', 'woocommerce-jetpack' ) . ' <code> wcj_products_custom_column_' . $i . '</code>',
				'id'       => 'wcj_products_admin_list_custom_columns_enabled_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'    => __( 'Label', 'woocommerce-jetpack' ),
				'id'      => 'wcj_products_admin_list_custom_columns_label_' . $i,
				'default' => '',
				'type'    => 'text',
			),
			array(
				'desc'     => __( 'Value', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can use shortcodes and/or HTML here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_admin_list_custom_columns_value_' . $i,
				'default'  => '',
				'type'     => 'custom_textarea',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_products_admin_list_custom_columns_options',
		),
		array(
			'title' => __( 'Columns Order', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_products_admin_list_columns_order_options',
		),
		array(
			'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
			'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
			'id'      => 'wcj_products_admin_list_columns_order_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'id'       => 'wcj_products_admin_list_columns_order',
			'desc_tip' => __( 'Default columns order', 'woocommerce-jetpack' ) . ':<br>' . str_replace( PHP_EOL, '<br>', $this->get_products_default_columns_in_order() ),
			'default'  => $this->get_products_default_columns_in_order(),
			'type'     => 'textarea',
			'css'      => 'height:300px;',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_products_admin_list_columns_order_options',
		),
	)
);
return $settings;
