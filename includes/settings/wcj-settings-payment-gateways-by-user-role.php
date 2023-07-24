<?php
/**
 * Booster for WooCommerce - Settings - Gateways by User Role
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message    = apply_filters( 'booster_message', '', 'desc' );
$settings   = array(
	array(
		'id'   => 'wcj_payment_gateways_by_user_role_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_payment_gateways_by_user_role_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_payment_gateways_by_user_role_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_payment_gateways_by_user_role_payment_gatways_tab'   => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_payment_gateways_by_user_role_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_payment_gateways_by_user_role_general_options',
	),
	array(
		'title'             => __( 'Multiple Role Checking', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'default'           => 'no',
		'desc_tip'          => __( 'Enable if you have some plugin that allows users with multiple roles like "User Role Editor".', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'id'                => 'wcj_payment_gateways_by_user_role_multi_role_check',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_user_role_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_user_role_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_user_role_payment_gatways_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ) . ' ' .
			sprintf(
								/* translators: %s: translators Added */
				__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
				admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
			),
		'id'    => 'wcj_payment_gateways_by_user_role_gateways_options',
	),
);
$user_roles = wcj_get_user_roles_options();
$gateways   = WC()->payment_gateways->payment_gateways();
foreach ( $gateways as $key => $gateway ) {
	$default_gateways = array( 'bacs' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' === $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $gateway->title,
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Include User Roles', 'woocommerce-jetpack' ),
				'id'                => 'wcj_gateways_user_roles_include_' . $key,
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'options'           => $user_roles,
				'custom_attributes' => $custom_attributes,
			),
			array(
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Exclude User Roles', 'woocommerce-jetpack' ),
				'id'                => 'wcj_gateways_user_roles_exclude_' . $key,
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'options'           => $user_roles,
				'custom_attributes' => $custom_attributes,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_payment_gateways_by_user_role_gateways_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_payment_gateways_by_user_role_payment_gatways_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
