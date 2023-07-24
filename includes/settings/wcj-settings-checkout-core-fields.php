<?php
/**
 * Booster for WooCommerce - Settings - Checkout Core Fields
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_cats = wcj_get_terms( 'product_cat' );

$settings = array(
	array(
		'id'   => 'wcj_cart_core_fields_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_cart_core_fields_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_cart_core_fields_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_cart_core_fields_fields_options_tab'  => __( 'Fields Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_cart_core_fields_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_checkout_core_fields_general_options',
	),
	array(
		'title'   => __( 'Override Default Address Fields', 'woocommerce-jetpack' ),
		'type'    => 'select',
		'id'      => 'wcj_checkout_core_fields_override_default_address_fields',
		'default' => 'billing',
		'options' => array(
			'billing'  => __( 'Override with billing fields', 'woocommerce-jetpack' ),
			'shipping' => __( 'Override with shipping fields', 'woocommerce-jetpack' ),
			'disable'  => __( 'Do not override', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Override Country Locale Fields', 'woocommerce-jetpack' ),
		'type'    => 'select',
		'id'      => 'wcj_checkout_core_fields_override_country_locale_fields',
		'default' => 'billing',
		'options' => array(
			'billing'  => __( 'Override with billing fields', 'woocommerce-jetpack' ),
			'shipping' => __( 'Override with shipping fields', 'woocommerce-jetpack' ),
			'disable'  => __( 'Do not override', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Force Fields Sort by Priority', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you are having theme related issues with "priority (i.e. order)" options.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_checkout_core_fields_force_sort_by_priority',
		'default'  => 'no',
	),
	array(
		'title'             => __( 'Checking Relation', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Use <code>Or</code> if you need that at least one condition is valid. e.g.: At least one product from a specific category is in cart. Use <code>All</code> if you need that all conditions are valid.', 'woocommerce-jetpack' ),
		'type'              => 'select',
		'id'                => 'wcj_checkout_core_fields_checking_relation',
		'options'           => array(
			'and' => __( 'And', 'woocommerce-jetpack' ),
			'or'  => __( 'Or', 'woocommerce-jetpack' ),
		),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'default'           => 'and',
	),
	array(
		'id'   => 'wcj_checkout_core_fields_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_core_fields_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_cart_core_fields_fields_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Fields Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_checkout_core_fields_options',
	),
);
foreach ( $this->woocommerce_core_checkout_fields as $field ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => ucwords( str_replace( '_', ' ', $field ) ),
				'desc'    => __( 'enabled', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_fields_' . $field . '_is_enabled',
				'default' => 'default',
				'type'    => 'select',
				'options' => array(
					'default' => __( 'Default', 'woocommerce-jetpack' ),
					'yes'     => __( 'Enabled', 'woocommerce-jetpack' ),
					'no'      => __( 'Disabled', 'woocommerce-jetpack' ),
				),
				'css'     => 'min-width:300px;width:50%;',
			),
			array(
				'desc'    => __( 'required', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_fields_' . $field . '_is_required',
				'default' => 'default',
				'type'    => 'select',
				'options' => array(
					'default' => __( 'Default', 'woocommerce-jetpack' ),
					'yes'     => __( 'Required', 'woocommerce-jetpack' ),
					'no'      => __( 'Not Required', 'woocommerce-jetpack' ),
				),
				'css'     => 'min-width:300px;width:50%;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'label', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_fields_' . $field . '_label',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width:300px;width:50%;',
			),
			array(
				'desc'     => __( 'placeholder', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_fields_' . $field . '_placeholder',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width:300px;width:50%;',
			),
			array(
				'desc'     => __( 'description', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank for WooCommerce defaults.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_fields_' . $field . '_description',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width:300px;width:50%;',
			),
			array(
				'desc'    => __( 'class', 'woocommerce-jetpack' ),
				'id'      => 'wcj_checkout_fields_' . $field . '_class',
				'default' => 'default',
				'type'    => 'select',
				'options' => array(
					'default'        => __( 'Default', 'woocommerce-jetpack' ),
					'form-row-first' => __( 'Align Left', 'woocommerce-jetpack' ),
					'form-row-last'  => __( 'Align Right', 'woocommerce-jetpack' ),
					'form-row-full'  => __( 'Full Row', 'woocommerce-jetpack' ),
					'form-row-wide'  => __( 'Wide Row', 'woocommerce-jetpack' ),
				),
				'css'     => 'min-width:300px;width:50%;',
			),
			array(
				'desc'              => __( 'priority (i.e. order)', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Leave zero for WooCommerce defaults.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc_no_link' ),
				'id'                => 'wcj_checkout_fields_' . $field . '_priority',
				'default'           => 0,
				'type'              => 'number',
				'css'               => 'min-width:300px;width:50%;',
				'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
			),
			array(
				'desc'              => __( 'include product categories', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'If not empty - selected categories products must be in cart for current field to appear.', 'woocommerce-jetpack' ) . ' ' .
					apply_filters( 'booster_message', '', 'desc_no_link' ),
				'id'                => 'wcj_checkout_fields_' . $field . '_cats_incl',
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'min-width:300px;width:50%;',
				'options'           => $product_cats,
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'desc'              => __( 'exclude product categories', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'If not empty - current field is hidden, if selected categories products are in cart.', 'woocommerce-jetpack' ) . ' ' .
					apply_filters( 'booster_message', '', 'desc_no_link' ),
				'id'                => 'wcj_checkout_fields_' . $field . '_cats_excl',
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'min-width:300px;width:50%;',
				'options'           => $product_cats,
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_checkout_core_fields_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_cart_core_fields_fields_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
