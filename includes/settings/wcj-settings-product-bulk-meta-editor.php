<?php
/**
 * Booster for WooCommerce Settings - Product Bulk Meta Editor
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title' => __( 'Product Bulk Meta Editor Tool Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_bulk_meta_editor_options',
	),
	array(
		'title'    => __( 'Check if Meta Exists', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled - meta can be changed only if it already existed for product. If you want to be able to create new meta for products, disable this option.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bulk_meta_editor_check_if_exists',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Add Variations to Products List', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'When enabled - variations of variable products will be added to the list. If you want to edit only main product\'s meta, disable this option.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_bulk_meta_editor_add_variations',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Additional Columns', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bulk_meta_editor_additional_columns',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => array(
			'product_id'            => __( 'Product ID', 'woocommerce-jetpack' ),
			'product_status'        => __( 'Product status', 'woocommerce-jetpack' ),
			'product_all_meta_keys' => __( 'Meta keys', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_bulk_meta_editor_options',
	),
);
