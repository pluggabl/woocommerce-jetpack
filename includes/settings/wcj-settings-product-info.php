<?php
/**
 * Booster for WooCommerce - Settings - Product Info V1
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Products Info', 'woocommerce-jetpack' ), 'type' => 'title',
		'desc'     => __( 'For full list of short codes, please visit <a target="_blank" href="https://booster.io/shortcodes/">https://booster.io/shortcodes/</a>.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_more_product_info_options',
	),
);
$this->admin_add_product_info_fields_with_header( $settings, 'archive', __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ), $this->product_info_on_archive_filters_array );
$this->admin_add_product_info_fields_with_header( $settings, 'single',  __( 'Product Info on Single Pages', 'woocommerce-jetpack' ),  $this->product_info_on_single_filters_array );
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_more_product_info_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Even More Products Info', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_additional_options',
	),
	array(
		'title'    => __( 'Product Info on Archive Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_archive_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => '',
		'desc_tip' => __( 'HTML info.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_archive',
		'default'  => __( '[wcj_product_sku before="SKU: "]', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;height:100px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_archive_filter',
		'css'      => 'min-width:350px;',
		'class'    => 'chosen_select',
		'default'  => 'woocommerce_after_shop_loop_item_title',
		'type'     => 'select',
		'options'  => $this->product_info_on_archive_filters_array,
		'desc_tip' => true,
	),
	array(
		'title'    => '',
		'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_archive_filter_priority',
		'default'  => 10,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Product Info on Single Product Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_single_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => '',
		'desc_tip' => __( 'HTML info.', 'woocommerce-jetpack' ),// . ' ' . $this->list_short_codes(),
		'id'       => 'wcj_product_info_on_single',
		'default'  => __( 'Total sales: [wcj_product_total_sales]', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;height:100px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_single_filter',
		'css'      => 'min-width:350px;',
		'class'    => 'chosen_select',
		'default'  => 'woocommerce_after_single_product_summary',
		'type'     => 'select',
		'options'  => $this->product_info_on_single_filters_array,
		'desc_tip' => true,
	),
	array(
		'title'    => '',
		'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_on_single_filter_priority',
		'default'  => 10,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Product IDs to exclude', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Comma separated list of product IDs to exclude from product info.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_products_to_exclude',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_additional_options',
	),
) );
return $settings;
