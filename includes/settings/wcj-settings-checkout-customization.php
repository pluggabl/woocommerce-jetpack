<?php
/**
 * Booster for WooCommerce - Settings - Checkout Customization
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_customization_options',
	),
	array(
		'title'    => __( '"Create an account?" Checkbox', 'woocommerce-jetpack' ),
		'desc_tip' => __( '"Create an account?" checkbox default value', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_create_account_default_checked',
		'default'  => 'default',
		'type'     => 'select',
		'options'  => array(
			'default'     => __( 'WooCommerce default', 'woocommerce-jetpack' ),
			'checked'     => __( 'Checked', 'woocommerce-jetpack' ),
			'not_checked' => __( 'Not checked', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Hide "Order Again" Button on "View Order" Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_hide_order_again',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Fields on Checkout for Logged Users', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => array(
//			'billing_country'     => __( 'Billing country', 'woocommerce-jetpack' ),
			'billing_first_name'  => __( 'Billing first name', 'woocommerce-jetpack' ),
			'billing_last_name'   => __( 'Billing last name', 'woocommerce-jetpack' ),
			'billing_company'     => __( 'Billing company', 'woocommerce-jetpack' ),
			'billing_address_1'   => __( 'Billing address 1', 'woocommerce-jetpack' ),
			'billing_address_2'   => __( 'Billing address 2', 'woocommerce-jetpack' ),
			'billing_city'        => __( 'Billing city', 'woocommerce-jetpack' ),
			'billing_state'       => __( 'Billing state', 'woocommerce-jetpack' ),
			'billing_postcode'    => __( 'Billing postcode', 'woocommerce-jetpack' ),
			'billing_email'       => __( 'Billing email', 'woocommerce-jetpack' ),
			'billing_phone'       => __( 'Billing phone', 'woocommerce-jetpack' ),
//			'shipping_country'    => __( 'Shipping country', 'woocommerce-jetpack' ),
			'shipping_first_name' => __( 'Shipping first name', 'woocommerce-jetpack' ),
			'shipping_last_name'  => __( 'Shipping last name', 'woocommerce-jetpack' ),
			'shipping_company'    => __( 'Shipping company', 'woocommerce-jetpack' ),
			'shipping_address_1'  => __( 'Shipping address 1', 'woocommerce-jetpack' ),
			'shipping_address_2'  => __( 'Shipping address 2', 'woocommerce-jetpack' ),
			'shipping_city'       => __( 'Shipping city', 'woocommerce-jetpack' ),
			'shipping_state'      => __( 'Shipping state', 'woocommerce-jetpack' ),
			'shipping_postcode'   => __( 'Shipping postcode', 'woocommerce-jetpack' ),
//			'account_password'    => __( 'Account password', 'woocommerce-jetpack' ),
			'order_comments'      => __( 'Order comments', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Message for logged users', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_message',
		'default'  => '<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Customize "order received" message', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_order_received_message_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_order_received_message',
		'default'  => __( 'Thank you. Your order has been received.', 'woocommerce' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Customize "Returning customer?" message', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_checkout_login_message_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_checkout_customization_checkout_login_message',
		'default'  => __( 'Returning customer?', 'woocommerce' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_customization_options',
	),
);
