<?php
/**
 * Booster for WooCommerce - Settings - Checkout Custom Fields
 *
 * @version 3.2.4
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_cats = wcj_get_terms( 'product_cat' );
$products     = wcj_get_products();
$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_custom_fields_options',
	),
	array(
		'title'    => __( 'Add All Fields to Admin Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_email_all_to_admin',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Add All Fields to Customers Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_email_all_to_customer',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Emails Fields Template', 'woocommerce-jetpack' ),
		'desc'     => __( 'Before the fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_emails_template_before',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'Each field', 'woocommerce-jetpack' ) . '. ' . wcj_message_replaced_values( array( '%label%', '%value%' ) ),
		'id'       => 'wcj_checkout_custom_fields_emails_template_field',
		'default'  => '<p><strong>%label%:</strong> %value%</p>',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'After the fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_emails_template_after',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'title'    => __( 'Add All Fields to "Order Received" (i.e. "Thank You") and "View Order" Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_add_to_order_received',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( '"Order Received" Fields Template', 'woocommerce-jetpack' ),
		'desc'     => __( 'Before the fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_order_received_template_before',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'Each field', 'woocommerce-jetpack' ) . '. ' . wcj_message_replaced_values( array( '%label%', '%value%' ) ),
		'id'       => 'wcj_checkout_custom_fields_order_received_template_field',
		'default'  => '<p><strong>%label%:</strong> %value%</p>',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'After the fields', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_order_received_template_after',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'title'    => __( 'Custom Fields Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Click Save changes after you change this number.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_custom_fields_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1' )
		),
		'css'      => 'width:100px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_custom_fields_options',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings,
		array(
			array(
				'title'    => __( 'Custom Field', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'title',
				'id'       => 'wcj_checkout_custom_fields_options_' . $i,
			),
			array(
				'title'    => __( 'Enable/Disable', 'woocommerce-jetpack' ),
				'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip' => __( 'Key', 'woocommerce-jetpack' ) . ': ' .
					'<code>' . get_option( 'wcj_checkout_custom_field_section_' . $i, 'billing' ) . '_' . 'wcj_checkout_field_' . $i . '</code>',
				'id'       => 'wcj_checkout_custom_field_enabled_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Type', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_type_' . $i,
				'default'  => 'text',
				'type'     => 'select',
				'options'  => array(
					'text'       => __( 'Text', 'woocommerce-jetpack' ),
					'textarea'   => __( 'Textarea', 'woocommerce-jetpack' ),
					'number'     => __( 'Number', 'woocommerce-jetpack' ),
					'checkbox'   => __( 'Checkbox', 'woocommerce-jetpack' ),
					'datepicker' => __( 'Datepicker', 'woocommerce-jetpack' ),
					'weekpicker' => __( 'Weekpicker', 'woocommerce-jetpack' ),
					'timepicker' => __( 'Timepicker', 'woocommerce-jetpack' ),
					'select'     => __( 'Select', 'woocommerce-jetpack' ),
					'radio'      => __( 'Radio', 'woocommerce-jetpack' ),
					'password'   => __( 'Password', 'woocommerce-jetpack' ),
					'country'    => __( 'Country', 'woocommerce-jetpack' ),
					'state'      => __( 'State', 'woocommerce-jetpack' ),
					'email'      => __( 'Email', 'woocommerce-jetpack' ),
					'tel'        => __( 'Phone', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Required', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_required_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Label', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_label_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'min-width:300px;',
			),
			array(
				'title'    => __( 'Placeholder', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_placeholder_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'min-width:300px;',
			),
			array(
				'title'    => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_priority_' . $i,
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
			),
			array(
				'title'    => __( 'Section', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_section_' . $i,
				'default'  => 'billing',
				'type'     => 'select',
				'options'  => array(
					'billing'   => __( 'Billing', 'woocommerce-jetpack' ),
					'shipping'  => __( 'Shipping', 'woocommerce-jetpack' ),
					'order'     => __( 'Order Notes', 'woocommerce-jetpack' ),
					'account'   => __( 'Account', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Class', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_class_' . $i,
				'default'  => 'form-row-wide',
				'type'     => 'select',
				'options'  => array(
					'form-row-wide'  => __( 'Wide', 'woocommerce-jetpack' ),
					'form-row-first' => __( 'First', 'woocommerce-jetpack' ),
					'form-row-last'  => __( 'Last', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Clear', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_clear_' . $i,
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Customer Meta Fields', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_customer_meta_fields_' . $i,
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Select/Radio: Options', 'woocommerce-jetpack' ),
				'desc'     => __( 'One option per line', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_select_options_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'min-width:300px;height:150px;',
			),
			array(
				'title'    => __( 'Select: Use select2 Library', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_select_select2_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'id'       => 'wcj_checkout_custom_field_checkbox_yes_' . $i,
				'title'    => __( 'Checkbox: Value for ON', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'default'  => __( 'Yes', 'woocommerce-jetpack' ),
			),
			array(
				'id'       => 'wcj_checkout_custom_field_checkbox_no_' . $i,
				'title'    => __( 'Checkbox: Value for OFF', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'default'  => __( 'No', 'woocommerce-jetpack' ),
			),
			array(
				'id'       => 'wcj_checkout_custom_field_checkbox_default_' . $i,
				'title'    => __( 'Checkbox: Default Value', 'woocommerce-jetpack' ),
				'type'     => 'select',
				'default'  => 'no',
				'options'  => array(
					'no'  => __( 'Not Checked', 'woocommerce-jetpack' ),
					'yes' => __( 'Checked', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: Date Format', 'woocommerce-jetpack' ),
				'desc'     => __( 'Visit <a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">documentation on date and time formatting</a> for valid date formats', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to use your current WordPress format', 'woocommerce-jetpack' ) . ': ' . get_option( 'date_format' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_format_' . $i,
				'type'     => 'text',
				'default'  => '',
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: Min Date', 'woocommerce-jetpack' ),
				'desc'     => __( 'days', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_mindate_' . $i,
				'type'     => 'number',
				'default'  => -365,
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: Max Date', 'woocommerce-jetpack' ),
				'desc'     => __( 'days', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_maxdate_' . $i,
				'type'     => 'number',
				'default'  => 365,
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: Add Year Selector', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_changeyear_' . $i,
				'type'     => 'checkbox',
				'default'  => 'no',
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: Year Selector: Year Range', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'The range of years displayed in the year drop-down: either relative to today\'s year ("-nn:+nn"), relative to the currently selected year ("c-nn:c+nn"), absolute ("nnnn:nnnn"), or combinations of these formats ("nnnn:-nn"). Note that this option only affects what appears in the drop-down, to restrict which dates may be selected use the minDate and/or maxDate options.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_yearrange_' . $i,
				'type'     => 'text',
				'default'  => 'c-10:c+10',
			),
			array(
				'title'    => __( 'Datepicker/Weekpicker: First Week Day', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_datepicker_firstday_' . $i,
				'type'     => 'select',
				'default'  => 0,
				'options'  => array(
					__( 'Sunday', 'woocommerce-jetpack' ),
					__( 'Monday', 'woocommerce-jetpack' ),
					__( 'Tuesday', 'woocommerce-jetpack' ),
					__( 'Wednesday', 'woocommerce-jetpack' ),
					__( 'Thursday', 'woocommerce-jetpack' ),
					__( 'Friday', 'woocommerce-jetpack' ),
					__( 'Saturday', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Timepicker: Time Format', 'woocommerce-jetpack' ),
				'desc'     => __( 'Visit <a href="http://timepicker.co/options/" target="_blank">timepicker options page</a> for valid time formats', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_timepicker_format_' . $i,
				'type'     => 'text',
				'default'  => 'hh:mm p',
			),
			array(
				'title'    => __( 'Timepicker: Interval', 'woocommerce-jetpack' ),
				'desc'     => __( 'minutes', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_timepicker_interval_' . $i,
				'type'     => 'number',
				'default'  => 15,
			),
			array(
				'title'    => __( 'Exclude Categories', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hide this field if there is a product of selected category in cart.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_categories_ex_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Include Categories', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Show this field only if there is a product of selected category in cart.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_categories_in_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Exclude Products', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hide this field if there is a selected product in cart.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_products_ex_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Include Products', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Show this field only if there is a selected product in cart.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_products_in_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Min Cart Amount', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Show this field only if cart total is at least this amount. Set zero to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_min_cart_amount_' . $i,
				'default'  => 0,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => wcj_get_wc_price_step() ),
			),
			array(
				'title'    => __( 'Max Cart Amount', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Show this field only if cart total is not more than this amount. Set zero to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_field_max_cart_amount_' . $i,
				'default'  => 0,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => wcj_get_wc_price_step() ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_checkout_custom_fields_options_' . $i,
			),
		)
	);
}
return $settings;
