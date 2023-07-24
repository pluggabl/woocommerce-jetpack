<?php
/**
 * Booster for WooCommerce - Settings - Email Options
 *
 * @version 7.0.0
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'email_options_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'email_options_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'email_options_product_info_tab'  => __( 'Product Info in Item Name', 'woocommerce-jetpack' ),
			'email_options_email_options_tab' => __( 'Email Forwarding Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'email_options_product_info_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Product Info in Item Name', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_info_in_email_order_item_name_options',
	),
	array(
		'title'   => __( 'Add Product Info to Item Name', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add', 'woocommerce-jetpack' ),
		'type'    => 'checkbox',
		'id'      => 'wcj_product_info_in_email_order_item_name_enabled',
		'default' => 'no',
	),
	array(
		'title'   => __( 'Info', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'You can use <a target="_blank" href="%s">Booster\'s products shortcodes</a> here.', 'woocommerce-jetpack' ), 'https://booster.io/category/shortcodes/products-shortcodes/' ),
		'type'    => 'textarea',
		'id'      => 'wcj_product_info_in_email_order_item_name',
		'default' => '[wcj_product_categories strip_tags="yes" before="<hr><em>" after="</em>"]',
		'css'     => 'width:66%;min-width:300px;height:150px;',
	),
	array(
		'id'   => 'wcj_product_info_in_email_order_item_name_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'email_options_product_info_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'email_options_email_options_tab',
		'type' => 'tab_start',
	),
);
$settings = array_merge( $settings, $this->get_emails_forwarding_settings() );

$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'email_options_email_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
