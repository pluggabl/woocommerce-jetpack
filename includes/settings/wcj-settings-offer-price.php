<?php
/**
 * Booster for WooCommerce - Settings - Offer Price
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_general_options',
		'type'     => 'title',
	),
	array(
		'title'    => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Possible values: Enable for all products; Enable for all products with empty price; Enable per product.', 'woocommerce-jetpack' ) . ' ' .
			__( 'If Enable per product is selected, this will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_enabled_type',
		'type'     => 'select',
		'default'  => 'all_products',
		'options'  => array(
			'all_products' => __( 'Enable for all products', 'woocommerce-jetpack' ),
			'empty_prices' => __( 'Enable for all products with empty price', 'woocommerce-jetpack' ),
			'per_product'  => __( 'Enable per product', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'       => 'wcj_offer_price_general_options',
		'type'     => 'sectionend',
	),
	array(
		'title'    => __( 'Button Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_options',
		'type'     => 'title',
	),
	array(
		'title'    => __( 'Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_label',
		'type'     => 'text',
		'default'  => __( 'Make an offer', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'CSS Class', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_class',
		'type'     => 'text',
		'default'  => 'button',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'CSS Style', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_style',
		'type'     => 'text',
		'default'  => '',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Position On Single Product Page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_position',
		'type'     => 'select',
		'default'  => 'woocommerce_single_product_summary',
		'options'  => array(
			'disable'                                   => __( 'Do not add', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product'         => __( 'Before single product', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product'          => __( 'After single product', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Position Priority (i.e. Order)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_position_priority',
		'type'     => 'number',
		'default'  => 31,
	),
	array(
		'title'    => __( 'Position On Archive Pages', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Possible values: Do not add; Before product; After product.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_position_archives',
		'type'     => 'select',
		'default'  => 'disable',
		'options'  => array(
			'disable'                                 => __( 'Do not add', 'woocommerce-jetpack' ),
			'woocommerce_before_shop_loop_item'       => __( 'Before product', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item'        => __( 'After product', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'     => __( 'Position Priority (i.e. Order)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_button_position_priority_archives',
		'type'     => 'number',
		'default'  => 10,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'       => 'wcj_offer_price_button_options',
		'type'     => 'sectionend',
	),
	array(
		'title'    => __( 'Form and Notice Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_form_options',
		'type'     => 'title',
	),
	array(
		'title'    => __( 'Price Input', 'woocommerce-jetpack' ),
		'desc'     => __( 'Label', 'woocommerce-jetpack' ) .
			'. ' . wcj_message_replaced_values( array( '%currency_symbol%' ) ),
		'id'       => 'wcj_offer_price_price_label',
		'type'     => 'custom_textarea',
		'default'  => sprintf( __( 'Your price (%s)', 'woocommerce-jetpack' ), '%currency_symbol%' ),
		'css'      => 'width:99%;',
	),
	array(
		'desc'     => __( 'Price Step', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Number of decimals', 'woocommerce' ),
		'id'       => 'wcj_offer_price_price_step',
		'type'     => 'number',
		'default'  => get_option( 'woocommerce_price_num_decimals' ),
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'desc'     => __( 'Minimal Price', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_min_price',
		'type'     => 'number',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'desc'     => __( 'Maximal Price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_max_price',
		'type'     => 'number',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'desc'     => __( 'Default Price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_default_price',
		'type'     => 'number',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Customer Email', 'woocommerce-jetpack' ),
		'desc'     => __( 'Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_customer_email',
		'type'     => 'custom_textarea',
		'default'  => __( 'Your email', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Customer Name', 'woocommerce-jetpack' ),
		'desc'     => __( 'Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_customer_name',
		'type'     => 'custom_textarea',
		'default'  => __( 'Your name', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Customer Message', 'woocommerce-jetpack' ),
		'desc'     => __( 'Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_customer_message',
		'type'     => 'custom_textarea',
		'default'  => __( 'Your message', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Send a Copy to Customer Checkbox', 'woocommerce-jetpack' ),
		'desc'     => __( 'Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_customer_copy',
		'type'     => 'custom_textarea',
		'default'  => __( 'Send a copy to your email', 'woocommerce-jetpack' ),
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Form Header', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%product_title%' ) ),
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
		'title'    => __( 'Required HTML', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_form_required_html',
		'type'     => 'custom_textarea',
		'default'  => ' <abbr class="required" title="required">*</abbr>',
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
		'id'       => 'wcj_offer_price_form_options',
		'type'     => 'sectionend',
	),
	array(
		'title'    => __( 'Email Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_offer_price_email_options',
		'type'     => 'title',
	),
	array(
		'title'    => __( 'Email Recipient', 'woocommerce-jetpack' ),
		'desc'     => __( 'Can be comma separated list.', 'woocommerce-jetpack' ) . ' ' .
			sprintf(
				__( 'Use %s to send to administrator email: %s.',
				'woocommerce-jetpack' ), '<code>' . '%admin_email%' . '</code>',
				'<code>' . get_option( 'admin_email' ) . '</code>'
			) . ' ' .
			wcj_message_replaced_values( array( '%admin_email%', '%product_author_email%' ) ),
		'id'       => 'wcj_offer_price_email_address',
		'type'     => 'custom_textarea',
		'default'  => '%admin_email%',
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
		'desc'     => wcj_message_replaced_values( array( '%product_title%', '%offered_price%', '%customer_name%', '%customer_email%', '%customer_message%' ) ),
		'id'       => 'wcj_offer_price_email_template',
		'type'     => 'custom_textarea',
		'default'  =>
			sprintf( __( 'Product: %s', 'woocommerce-jetpack' ),       '%product_title%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'Offered price: %s', 'woocommerce-jetpack' ), '%offered_price%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'From: %s %s', 'woocommerce-jetpack' ),       '%customer_name%', '%customer_email%' ) . '<br>' . PHP_EOL .
			sprintf( __( 'Message: %s', 'woocommerce-jetpack' ),       '%customer_message%' ),
		'css'      => 'width:99%;height:200px;',
	),
	array(
		'id'       => 'wcj_offer_price_email_options',
		'type'     => 'sectionend',
	),
);
