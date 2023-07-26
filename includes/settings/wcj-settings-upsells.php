<?php
/**
 * Booster for WooCommerce - Settings - Upsells
 *
 * @version 7.0.0
 * @since   3.5.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'wcj_product_upsells_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_upsells_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_upsells_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_upsells_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_upsells_options',
	),
	array(
		'title'             => __( 'Upsells Total', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ) . ' ' . __( 'Set to -1 for unlimited.', 'woocommerce-jetpack' ),
		'type'              => 'number',
		'id'                => 'wcj_upsells_total',
		'default'           => 0,
		'custom_attributes' => array( 'min' => -1 ),
	),
	array(
		'title'             => __( 'Upsells Columns', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ),
		'type'              => 'number',
		'id'                => 'wcj_upsells_columns',
		'default'           => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'   => __( 'Upsells Order By', 'woocommerce-jetpack' ),
		'type'    => 'select',
		'id'      => 'wcj_upsells_orderby',
		'default' => 'no_changes',
		'options' => array(
			'no_changes' => __( 'No changes (default behaviour)', 'woocommerce-jetpack' ),
			'rand'       => __( 'Random', 'woocommerce-jetpack' ),
			'title'      => __( 'Title', 'woocommerce-jetpack' ),
			'id'         => __( 'ID', 'woocommerce-jetpack' ),
			'date'       => __( 'Date', 'woocommerce-jetpack' ),
			'modified'   => __( 'Modified', 'woocommerce-jetpack' ),
			'menu_order' => __( 'Menu order', 'woocommerce-jetpack' ),
			'price'      => __( 'Price', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Upsells Position', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Upsells position on single product page.', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_upsells_position',
		'default'  => 'no_changes',
		'options'  => array(
			'no_changes'                                => __( 'No changes (default)', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product'         => __( 'Before single product', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product'          => __( 'After single product', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Position priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Ignored if "Upsells Position" option above is set to "No changes (default)".', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_upsells_position_priority',
		'default'  => 15,
	),
	array(
		'title'   => __( 'Hide Upsells', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
		'type'    => 'checkbox',
		'id'      => 'wcj_upsells_hide',
		'default' => 'no',
	),
	array(
		'title'             => __( 'Global Upsells', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Enable this section if you want to add same upsells to all products.', 'woocommerce-jetpack' ) . ' ' .
			apply_filters( 'booster_message', '', 'desc' ),
		'type'              => 'checkbox',
		'id'                => 'wcj_upsells_global_enabled',
		'default'           => 'no',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'    => __( 'Global upsells', 'woocommerce-jetpack' ),
		'type'    => 'multiselect',
		'id'      => 'wcj_upsells_global_ids',
		'default' => '',
		'class'   => 'chosen_select',
		'options' => wcj_get_products(),
	),
	array(
		'id'   => 'wcj_product_tax_display_by_product_tab',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_upsells_general_options_tab',
		'type' => 'tab_end',
	),
);
return $settings;
