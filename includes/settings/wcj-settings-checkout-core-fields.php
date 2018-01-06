<?php
/**
 * Booster for WooCommerce - Settings - Checkout Core Fields
 *
 * @version 3.1.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_core_fields_general_options',
	),
	array(
		'title'    => __( 'Override Default Address Fields', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_checkout_core_fields_override_default_address_fields',
		'default'  => 'billing',
		'options'  => array(
			'billing'  => __( 'Override with billing fields', 'woocommerce-jetpack' ),
			'shipping' => __( 'Override with shipping fields', 'woocommerce-jetpack' ),
			'disable'  => __( 'Do not override', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Override Country Locale Fields', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_checkout_core_fields_override_country_locale_fields',
		'default'  => 'billing',
		'options'  => array(
			'billing'  => __( 'Override with billing fields', 'woocommerce-jetpack' ),
			'shipping' => __( 'Override with shipping fields', 'woocommerce-jetpack' ),
			'disable'  => __( 'Do not override', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_core_fields_general_options',
	),
	array(
		'title'    => __( 'Fields Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_core_fields_options',
	),
);
foreach ( $this->woocommerce_core_checkout_fields as $field ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => ucwords( str_replace( '_', ' ', $field ) ),
			'desc'     => __( 'enabled', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'is_enabled',
			'default'  => 'default',
			'type'     => 'select',
			'options'  => array(
				'default' => __( 'Default', 'woocommerce-jetpack' ),
				'yes'     => __( 'Enabled', 'woocommerce-jetpack' ),
				'no'      => __( 'Disabled', 'woocommerce-jetpack' ),
			),
			'css'       => 'min-width:300px;width:50%;',
		),
		array(
			'desc'     => __( 'required', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'is_required',
			'default'  => 'default',
			'type'     => 'select',
			'options'  => array(
				'default' => __( 'Default', 'woocommerce-jetpack' ),
				'yes'     => __( 'Required', 'woocommerce-jetpack' ),
				'no'      => __( 'Not Required', 'woocommerce-jetpack' ),
			),
			'css'      => 'min-width:300px;width:50%;',
		),
		array(
			'title'    => '',
			'desc'     => __( 'label', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'label',
			'default'  => '',
			'type'     => 'text',
			'css'      => 'min-width:300px;width:50%;',
		),
		array(
			'desc'     => __( 'placeholder', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'placeholder',
			'default'  => '',
			'type'     => 'text',
			'css'      => 'min-width:300px;width:50%;',
		),
		array(
			'desc'     => __( 'class', 'woocommerce-jetpack' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'class',
			'default'  => 'default',
			'type'     => 'select',
			'options'  => array(
				'default'        => __( 'Default', 'woocommerce-jetpack' ),
				'form-row-first' => __( 'Align Left', 'woocommerce-jetpack' ),
				'form-row-last'  => __( 'Align Right', 'woocommerce-jetpack' ),
				'form-row-full'  => __( 'Full Row', 'woocommerce-jetpack' ),
			),
			'css'      => 'min-width:300px;width:50%;',
		),
		array(
			'desc'     => __( 'priority (i.e. order)', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave zero for WooCommerce defaults.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc_no_link' ),
			'id'       => 'wcj_checkout_fields_' . $field . '_' . 'priority',
			'default'  => 0,
			'type'     => 'number',
			'css'      => 'min-width:300px;width:50%;',
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_core_fields_options',
	),
) );
return $settings;
