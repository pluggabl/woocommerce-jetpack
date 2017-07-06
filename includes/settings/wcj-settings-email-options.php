<?php
/**
 * Booster for WooCommerce - Settings - Email Options
 *
 * @version 2.9.1
 * @since   2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Product Info in Item Name', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_in_email_order_item_name_options',
	),
	array(
		'title'    => __( 'Add Product Info to Item Name', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_product_info_in_email_order_item_name_enabled',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Info', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'You can use <a target="_blank" href="%s">Booster\'s products shortcodes</a> here.', 'woocommerce-jetpack' ), 'http://booster.io/category/shortcodes/products-shortcodes/' ),
		'type'     => 'custom_textarea',
		'id'       => 'wcj_product_info_in_email_order_item_name',
		'default'  => '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]',
		'css'      => 'width:66%;min-width:300px;height:150px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_in_email_order_item_name_options',
	),
);
$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );
return $settings;
