<?php
/**
 * Booster for WooCommerce - Settings - EU VAT Number
 *
 * @version 7.1.6
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    set default value for "wcj_eu_vat_number_add_progress_text" to "yes"
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message  = apply_filters( 'booster_message', '', 'desc' );
$settings = array(
	array(
		'id'   => 'wcj_eu_vat_number_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_eu_vat_number_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_eu_vat_number_general_options_tab'  => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_eu_vat_number_messages_options_tab' => __( 'Messages & Other Options', 'woocommerce-jetpack' ),
			'wcj_eu_vat_number_advanced_options_tab' => __( 'Advanced Options', 'woocommerce-jetpack' ),
			'wcj_eu_vat_number_tool_options_tab'     => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_eu_vat_number_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_eu_vat_number_options',
	),
	array(
		'title'   => __( 'Field Label', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_label',
		'default' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'   => __( 'Placeholder', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_placeholder',
		'default' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'   => __( 'Description', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_description',
		'default' => '',
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'   => __( 'Required', 'woocommerce-jetpack' ),
		'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_required',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Clear', 'woocommerce-jetpack' ),
		'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_clear',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Class', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_field_class',
		'default' => 'form-row-wide',
		'type'    => 'select',
		'options' => array(
			'form-row-wide'  => __( 'Wide', 'woocommerce-jetpack' ),
			'form-row-first' => __( 'First', 'woocommerce-jetpack' ),
			'form-row-last'  => __( 'Last', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Validate', 'woocommerce-jetpack' ),
		'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_validate',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'desc'    => __( 'Message on not valid', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_not_valid_message',
		'default' => __( '<strong>EU VAT Number</strong> is not valid.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
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
		'title'   => __( 'Exempt VAT for Valid Numbers', 'woocommerce-jetpack' ),
		'desc'    => __( 'Yes', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_disable_for_valid',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Exempt VAT on Cart', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Exempts VAT even on Cart page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_disable_for_valid_on_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Show VAT field for EU countries only', 'woocommerce-jetpack' ),
		'desc'              => __( 'Yes', 'woocommerce-jetpack' ),
		'id'                => 'wcj_eu_vat_number_show_vat_field_for_eu_only',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Exempt VAT by Customer\'s EU VAT', 'woocommerce-jetpack' ),
		'desc'     => __( 'Yes', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Exempts VAT by checking previously registered EU VAT numbers from customers.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_disable_for_valid_by_user_vat',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Preserve VAT in Base Country By Billing country', 'woocommerce-jetpack' ),
		'desc_tip'          => sprintf(
						/* translators: %s: translators Added */
			__( 'This will validate the VAT, but won\'t exempt VAT if base country matches the Billing country. Base (i.e. store) country is set in %s.', 'woocommerce-jetpack' ),
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">' . __( 'WooCommerce > Settings > General', 'woocommerce-jetpack' ) . '</a>'
		) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Yes', 'woocommerce-jetpack' ),
		'id'                => 'wcj_eu_vat_number_preserve_in_base_country',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Preserve VAT in Base Country By Shipping country', 'woocommerce-jetpack' ),
		'desc_tip'          => sprintf(
						/* translators: %s: translators Added */
			__( 'This will validate the VAT, but won\'t exempt VAT if base country matches the Shipping country. Base (i.e. store) country is set in %s.', 'woocommerce-jetpack' ),
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">' . __( 'WooCommerce > Settings > General', 'woocommerce-jetpack' ) . '</a>'
		) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Yes', 'woocommerce-jetpack' ),
		'id'                => 'wcj_eu_vat_number_preserve_in_base_country_by_shipping_country',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Check for IP Location Country', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will check if customer\'s country (located by customer\'s IP) matches the country in entered VAT number.', 'woocommerce-jetpack' ) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Yes', 'woocommerce-jetpack' ),
		'id'                => 'wcj_eu_vat_number_check_ip_location_country',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Display', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_display_position',
		'default' => 'after_order_table',
		'type'    => 'select',
		'options' => array(
			'after_order_table'  => __( 'After order table', 'woocommerce-jetpack' ),
			'in_billing_address' => __( 'In billing address', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_eu_vat_number_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_eu_vat_number_messages_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Add Progress Messages', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_add_progress_text',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Progress Message: Validating', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_progress_text_validating',
		'default' => __( 'Validating VAT. Please wait...', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'   => __( 'Progress Message: Valid', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_progress_text_valid',
		'default' => __( 'VAT is valid.', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'   => __( 'Progress Message: Not Valid', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_progress_text_not_valid',
		'default' => __( 'VAT is not valid.', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:100%;',
	),
	array(
		'title'    => __( 'Progress Message: Validation Failed', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Message on VAT validation server timeout etc.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_progress_text_validation_failed',
		'default'  => __( 'Validation failed. Please try again.', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'   => __( 'Add EU VAT Number Summary Meta Box to Admin Order Edit Page', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add', 'woocommerce-jetpack' ),
		'id'      => 'wcj_eu_vat_number_add_order_edit_metabox',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Restrictive Loading', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enqueues module scripts on some conditions.', 'woocommerce-jetpack' ) . '<br />' . __( 'Probably the best options are <code>Is Cart</code> and <code>Is Checkout</code>', 'woocommerce-jetpack' ) : $message,
		'desc_tip'          => __( 'Leave it empty to load it in all situations.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_eu_vat_number_restrictive_loading',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'default'           => '',
		'class'             => 'chosen_select',
		'options'           => array(
			'is_woocommerce'      => __( 'Is WooCommerce', 'woocommerce-jetpack' ),
			'is_shop'             => __( 'Is Shop', 'woocommerce-jetpack' ),
			'is_product_category' => __( 'Is Product Category', 'woocommerce-jetpack' ),
			'is_product_tag'      => __( 'Is Product Tag', 'woocommerce-jetpack' ),
			'is_product'          => __( 'Is Product', 'woocommerce-jetpack' ),
			'is_cart'             => __( 'Is Cart', 'woocommerce-jetpack' ),
			'is_checkout'         => __( 'Is Checkout', 'woocommerce-jetpack' ),
			'is_account_page'     => __( 'Is Account Page', 'woocommerce-jetpack' ),
			'is_wc_endpoint_url'  => __( 'Is WC Endpoint URL', 'woocommerce-jetpack' ),
			'is_ajax'             => __( 'Is AJAX', 'woocommerce-jetpack' ),
		),
		'type'              => 'multiselect',
	),
	array(
		'id'   => 'wcj_eu_vat_number_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_eu_vat_number_messages_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_eu_vat_number_advanced_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_eu_vat_number_advanced_options',
	),
	array(
		'title'    => __( 'Skip VAT Validation for Selected Countries', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'List all countries you want VAT validation to be skipped for (i.e. VAT always valid). Ignored if empty.', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'     => sprintf( __( 'Enter country codes as comma separated list, e.g. %s.', 'woocommerce-jetpack' ), '<code>IT,NL</code>' ),
		'id'       => 'wcj_eu_vat_number_advanced_skip_countries',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Apply Country Code Prefix to EU VAT ', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If the VAT number missing a country code, this feature will automatically assign the country code based on the billing country.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_apply_country_code',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( "Read '_vat_number' meta", 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc_tip' => sprintf( __( "Try to add compatibility with <a href='%s' target='_blank'>EU VAT Number</a> plugin, reading meta from '_vat_number'.", 'woocommerce-jetpack' ), 'https://woocommerce.com/products/eu-vat-number/' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_eu_vat_number_read_vat_number_meta',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_eu_vat_number_advanced_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_eu_vat_number_advanced_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_eu_vat_number_tool_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=eu_countries_vat_rates&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'EU Countries VAT Rates', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'EU Countries VAT Rates', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'wcj_eu_vat_number_tool_options_tab',
		'type' => 'tab_end',
	),
);
return $settings;
