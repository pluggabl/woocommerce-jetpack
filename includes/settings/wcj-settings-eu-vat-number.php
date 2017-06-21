<?php
/**
 * Booster for WooCommerce - Settings - EU VAT Number
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    set default value for "wcj_eu_vat_number_add_progress_text" to "yes"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_eu_vat_number_options',
	),
	array(
		'title'    => __( 'Field Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_label',
		'default'  => __( 'EU VAT Number', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Placeholder', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_placeholder',
		'default'  => __( 'EU VAT Number', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Description', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_description',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	/*
	array(
		'title'    => __( 'Require Country Code in VAT Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_require_country_code',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	*/
	array(
		'title'    => __( 'Required', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_required',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Clear', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_clear',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Class', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_field_class',
		'default'  => 'form-row-wide',
		'type'     => 'select',
		'options'  => array(
			'form-row-wide'  => __( 'Wide', 'woocommerce-jetpack' ),
			'form-row-first' => __( 'First', 'woocommerce-jetpack' ),
			'form-row-last'  => __( 'Last', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Validate', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_validate',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Message on not valid', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_not_valid_message',
		'default'  => __( '<strong>EU VAT Number</strong> is not valid.', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'First Validation Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Change this if you are having issues when validating VAT. This only selects first method to try - if not succeeded, remaining methods will be used for validation.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_first_method',
		'default'  => 'soap',
		'type'     => 'select',
		'options'  => array(
			'soap'              => __( 'SOAP', 'woocommerce-jetpack' ),
			'curl'              => __( 'cURL', 'woocommerce-jetpack' ),
			'file_get_contents' => __( 'Simple', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Exempt VAT for Valid Numbers', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_disable_for_valid',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Preserve VAT in Base Country', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_preserve_in_base_country',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	/*
	array(
		'title'    => '',
		'desc'     => __( 'Message if customer is in base country and VAT is NOT exempted.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_preserve_in_base_country_message',
		'default'  => __( 'EU VAT Number is valid, however VAT is not exempted.', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:300px;',
	),
	*/
	array(
		'title'    => __( 'Check for IP Location Country', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_check_ip_location_country',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	/*
	array(
		'title'    => '',
		'desc'     => __( 'Message if customer\'s check for IP location country has failed.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_check_ip_location_country_message',
		'default'  => __( 'IP must be from same country as VAT ID.', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:300px;',
	),
	*/
	array(
		'title'    => __( 'Display', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_display_position',
		'default'  => 'after_order_table',
		'type'     => 'select',
		'options'  => array(
			'after_order_table'  => __( 'After order table', 'woocommerce-jetpack' ),
			'in_billing_address' => __( 'In billing address', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Add Progress Messages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_add_progress_text',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Progress Message: Validating', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_progress_text_validating',
		'default'  => __( 'Validating VAT. Please wait...', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Progress Message: Valid', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_progress_text_valid',
		'default'  => __( 'VAT is valid.', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Progress Message: Not Valid', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_progress_text_not_valid',
		'default'  => __( 'VAT is not valid.', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Progress Message: Validation Failed', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Message on VAT validation server timeout etc.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_progress_text_validation_failed',
		'default'  => __( 'Validation failed. Please try again.', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'title'    => __( 'Add EU VAT Number Summary Metabox to Order Edit Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_add_order_edit_metabox',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_eu_vat_number_options',
	),
);
return $settings;
