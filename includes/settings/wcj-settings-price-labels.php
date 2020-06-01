<?php
/**
 * Booster for WooCommerce - Settings - Custom Price Labels
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_cats = wcj_get_terms( 'product_cat' );
$products     = wcj_get_products();

return array(
	array(
		'title'     => __( 'Custom Price Labels - Per Product', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'id'        => 'wcj_local_price_labels_options'
	),
	array(
		'title'     => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'This will add metaboxes to each product\'s admin edit page.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_local_price_labels_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_local_price_labels_options',
	),
	array(
		'title'     => __( 'Custom Price Labels - Globally', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you set price labels for all products globally.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_options',
	),
	array(
		'title'     => __( 'Add before the price', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to add before all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_add_before_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Add after the price', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to add after all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_add_after_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Add between regular and sale prices', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to add between regular and sale prices. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_between_regular_and_sale_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Remove from price', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to remove from all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_remove_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Replace in price', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to replace in all products prices. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_replace_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes'
					=> apply_filters( 'booster_message', '', 'readonly' ),
		'css'       => 'width:100%;',
	),
	array(
		'title'     => '',
		'desc_tip'  => __( 'Enter text to replace with. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_replace_with_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'desc'      => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Instead of the price', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Enter text to display instead of the price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_instead_text',
		'default'   => '',
		'type'      => 'custom_textarea',
		'css'       => 'width:100%;',
	),
	array(
		'title'     => __( 'Products - Include', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Apply global price labels only for selected products. Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_products_incl',
		'default'   => '',
		'type'      => 'multiselect',
		'class'     => 'chosen_select',
		'options'   => $products,
	),
	array(
		'title'     => __( 'Products - Exclude', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Do not apply global price labels only for selected products. Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_products_excl',
		'default'   => '',
		'type'      => 'multiselect',
		'class'     => 'chosen_select',
		'options'   => $products,
	),
	array(
		'title'     => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Apply global price labels only for selected product categories. Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_product_cats_incl',
		'default'   => '',
		'type'      => 'multiselect',
		'class'     => 'chosen_select',
		'options'   => $product_cats,
	),
	array(
		'title'     => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Do not apply global price labels only for selected product categories. Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_product_cats_excl',
		'default'   => '',
		'type'      => 'multiselect',
		'class'     => 'chosen_select',
		'options'   => $product_cats,
	),
	array(
		'title'     => __( 'Product Types - Include', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Apply global price labels only for selected product types. Leave blank to disable the option.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_global_price_labels_product_types_incl',
		'default'   => '',
		'type'      => 'multiselect',
		'class'     => 'chosen_select',
		'options'   => array_merge( wc_get_product_types(), array( 'variation' => __( 'Variable product\'s variation', 'woocommerce-jetpack' ) ) ),
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_global_price_labels_options',
	),
);
