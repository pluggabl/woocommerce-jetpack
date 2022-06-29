<?php
/**
 * Booster for WooCommerce - Settings - Sorting
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'Add Custom Sorting', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_more_sorting_options',
	),
	array(
		'title'   => __( 'Add More Sorting', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'      => 'wcj_more_sorting_enabled',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Sort by Name', 'woocommerce-jetpack' ),
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by title: A to Z', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_name_asc_text',
		'default'  => __( 'Sort by title: A to Z', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by title: Z to A', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_name_desc_text',
		'default'  => __( 'Sort by title: Z to A', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'    => __( 'Sort by SKU', 'woocommerce-jetpack' ),
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by SKU: low to high', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_sku_asc_text',
		'default'  => __( 'Sort by SKU: low to high', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by SKU: high to low', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_sku_desc_text',
		'default'  => __( 'Sort by SKU: high to low', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'             => '',
		'desc'              => __( 'Sort SKUs as numbers instead of as texts', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sorting_by_sku_num_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Sort by stock quantity', 'woocommerce-jetpack' ),
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by stock quantity: low to high', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_stock_quantity_asc_text',
		'default'  => __( 'Sort by stock quantity: low to high', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Default: ', 'woocommerce-jetpack' ) . __( 'Sort by stock quantity: high to low', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Text to show on frontend. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_by_stock_quantity_desc_text',
		'default'  => __( 'Sort by stock quantity: high to low', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_more_sorting_options',
	),
	array(
		'title' => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_sorting_rearrange_options',
	),
	array(
		'title'   => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'      => 'wcj_sorting_rearrange_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Rearrange Sorting', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sorting_rearrange',
		'desc_tip' => __( 'Default:', 'woocommerce-jetpack' ) . '<br>' . implode( '<br>', $this->get_woocommerce_sortings_order() ),
		'default'  => implode( PHP_EOL, $this->get_woocommerce_sortings_order() ),
		'type'     => 'textarea',
		'css'      => 'min-height:300px;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_sorting_rearrange_options',
	),
	array(
		'title' => __( 'Default WooCommerce Sorting', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_sorting_default_sorting_options',
	),
	array(
		'title'             => __( 'Default Sorting Options', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'                => 'wcj_sorting_default_sorting_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
);
foreach ( $this->get_woocommerce_default_sortings() as $sorting_key => $sorting_desc ) {
	$option_key = str_replace( '-', '_', $sorting_key );
	$settings[] = array(
		'title'   => $sorting_desc,
		'id'      => 'wcj_sorting_default_sorting_' . $option_key,
		'default' => $sorting_desc,
		'type'    => 'text',
		'css'     => 'min-width:300px;',
	);
	if ( 'menu_order' === $sorting_key ) {
		continue;
	}
	$settings[] = array(
		'desc'    => __( 'Remove', 'woocommerce-jetpack' ) . ' "' . $sorting_desc . '"',
		'id'      => 'wcj_sorting_default_sorting_' . $option_key . '_disable',
		'default' => 'no',
		'type'    => 'checkbox',
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sorting_default_sorting_options',
		),
		array(
			'title' => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_sorting_remove_all_options',
		),
		array(
			'title'             => __( 'Remove All Sorting', 'woocommerce-jetpack' ),
			'desc'              => __( 'Remove all sorting (including WooCommerce default) from shop\'s frontend', 'woocommerce-jetpack' ),
			'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
			'id'                => 'wcj_sorting_remove_all_enabled',
			'default'           => 'no',
			'type'              => 'checkbox',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sorting_remove_all_options',
		),
		array(
			'title'   => __( 'Restore Default WooCommerce Sorting', 'woocommerce-jetpack' ),
			'desc'    => __( "Replaces theme's sorting by default WooCommerce sorting", 'woocommerce-jetpack' ),
			'type'    => 'title',
			'default' => 'no',
			'id'      => 'wcj_sorting_restore_default_sorting_opt',
		),
		array(
			'title' => __( 'Restore', 'woocommerce-jetpack' ),
			'desc'  => __( 'Restore', 'woocommerce-jetpack' ),
			'type'  => 'checkbox',
			'id'    => 'wcj_sorting_restore_default_sorting',
		),
		array(
			'title'    => __( 'Theme', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Theme that will have its sorting replaced.', 'woocommerce-jetpack' ),
			'type'     => 'select',
			'options'  => array(
				'avada' => __( 'Avada', 'woocommerce-jetpack' ),
			),
			'default'  => 'avada',
			'id'       => 'wcj_sorting_restore_default_sorting_theme',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sorting_restore_default_sorting_opt',
		),
	)
);
return $settings;
