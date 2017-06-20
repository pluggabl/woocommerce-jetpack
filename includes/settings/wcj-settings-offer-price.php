<?php
/**
 * Booster for WooCommerce - Settings - Offer Price
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_options',
		'type'     => 'title',
	),
	array(
		'title'    => __( 'Enable for All Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_enabled_for_all_products',
		'type'     => 'checkbox',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Enable per Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_enabled_per_product',
		'type'     => 'checkbox',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Button Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_label',
		'type'     => 'text',
		'default'  => __( 'Make an offer', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Form Header', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_form_header_template',
		'type'     => 'custom_textarea',
		'default'  => '<h3>' . sprintf( __( 'Suggest your price for %s', 'woocommerce-jetpack' ), '%product_title%' ) . '</h3>',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Form Button Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_form_button_label',
		'type'     => 'text',
		'default'  => __( 'Send', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Form Footer', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_form_footer_template',
		'type'     => 'custom_textarea',
		'default'  => '',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Customer Notice', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_customer_notice',
		'type'     => 'custom_textarea',
		'default'  => __( 'Your price offer has been sent.', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Email Recipient', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'Leave blank to send to administrator email: %s', 'woocommerce-jetpack' ), '<code>' . get_option( 'admin_email' ) . '</code>' ),
		'id'       => 'wcj_offer_price_email_address',
		'type'     => 'text',
		'default'  => '',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Email Subject', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_email_subject',
		'type'     => 'text',
		'default'  => __( 'Price Offer', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Email Template', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_email_template',
		'type'     => 'custom_textarea',
		'default'  => sprintf( __( 'Product: %s', 'woocommerce-jetpack' ),       '%product_title%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'Offered price: %s', 'woocommerce-jetpack' ), '%offered_price%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'From: %s %s', 'woocommerce-jetpack' ),       '%customer_name%', '%customer_email%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'Message: %s', 'woocommerce-jetpack' ),       '%customer_message%' ),
		'css'      => 'width:99%;height:200px;',
	),
	array(
		'id'       => 'wcj_offer_price_options',
		'type'     => 'sectionend',
	),
);
