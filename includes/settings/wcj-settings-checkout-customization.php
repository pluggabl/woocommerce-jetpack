<?php
/**
 * Booster for WooCommerce - Settings - Checkout Customization
 *
 * @version 3.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Restrict Countries by Customer\'s IP', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_restrict_countries_options',
	),
	array(
		'title'    => __( 'Restrict Billing Countries by Customer\'s IP', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_restrict_countries_by_customer_ip_billing',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Restrict Shipping Countries by Customer\'s IP', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf( __( 'To restrict shipping countries, "Shipping location(s)" option in %s must be set to "Ship to specific countries only" (and you can leave "Ship to specific countries" option empty there).', 'woocommerce-jetpack' ),
			'<a target="_blank" href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">' .
				__( 'WooCommerce > Settings > General', 'woocommerce-jetpack' ) . '</a>' ) . '<br>' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_checkout_restrict_countries_by_customer_ip_shipping',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_restrict_countries_options',
	),
	array(
		'title'    => __( '"Create an account?" Checkbox Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_create_account_checkbox_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_create_account_checkbox_options',
	),
	array(
		'title'    => __( '"Order Again" Button Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_order_again_button_options',
	),
	array(
		'title'    => __( 'Hide "Order Again" Button on "View Order" Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_hide_order_again',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_order_again_button_options',
	),
	array(
		'title'    => __( 'Disable Fields on Checkout for Logged Users', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_options',
	),
	array(
		'title'    => __( 'Fields to Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged',
		'default'  => array(),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => array(
			'billing_country'     => __( 'Billing country', 'woocommerce-jetpack' ),
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
			'shipping_country'    => __( 'Shipping country', 'woocommerce-jetpack' ),
			'shipping_first_name' => __( 'Shipping first name', 'woocommerce-jetpack' ),
			'shipping_last_name'  => __( 'Shipping last name', 'woocommerce-jetpack' ),
			'shipping_company'    => __( 'Shipping company', 'woocommerce-jetpack' ),
			'shipping_address_1'  => __( 'Shipping address 1', 'woocommerce-jetpack' ),
			'shipping_address_2'  => __( 'Shipping address 2', 'woocommerce-jetpack' ),
			'shipping_city'       => __( 'Shipping city', 'woocommerce-jetpack' ),
			'shipping_state'      => __( 'Shipping state', 'woocommerce-jetpack' ),
			'shipping_postcode'   => __( 'Shipping postcode', 'woocommerce-jetpack' ),
			'order_comments'      => __( 'Order comments', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Message for Logged Users', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_message',
		'default'  => '<em>' . __( 'This field can not be changed', 'woocommerce-jetpack' ) . '</em>',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Advanced: Custom Fields (Readonly)', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => sprintf( __( 'Comma separated list of fields ids, e.g.: %s.', 'woocommerce-jetpack' ), '<em>billing_wcj_checkout_field_1, billing_wcj_checkout_field_2</em>' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_custom_r',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:100%;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Advanced: Custom Fields (Disabled)', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => sprintf( __( 'Comma separated list of fields ids, e.g.: %s.', 'woocommerce-jetpack' ), '<em>billing_wcj_checkout_field_1, billing_wcj_checkout_field_2</em>' ),
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_custom_d',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:100%;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_customization_disable_fields_for_logged_options',
	),
	array(
		'title'    => __( '"Order received" Message Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_customization_order_received_message_options',
	),
	array(
		'title'    => __( 'Customize Message', 'woocommerce-jetpack' ),
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
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_customization_order_received_message_options',
	),
	array(
		'title'    => __( '"Returning customer?" Message Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_customization_checkout_login_message_options',
	),
	array(
		'title'    => __( 'Customize Message', 'woocommerce-jetpack' ),
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
		'id'       => 'wcj_checkout_customization_checkout_login_message_options',
	),
);
