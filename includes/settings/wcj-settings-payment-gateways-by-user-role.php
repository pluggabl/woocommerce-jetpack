<?php
/**
 * Booster for WooCommerce - Settings - Gateways by User Role
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ) . ' ' .
			sprintf( __( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
				admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ),
		'id'    => 'wcj_payment_gateways_by_user_role_gateways_options',
	),
);
$user_roles = wcj_get_user_roles_options();
$gateways = WC()->payment_gateways->payment_gateways();
foreach ( $gateways as $key => $gateway ) {
	$default_gateways = array( 'bacs' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' == $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip = '';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'     => $gateway->title,
			'desc_tip'  => $desc_tip,
			'desc'      => __( 'Include User Roles', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_user_roles_include_' . $key,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'css'       => 'width: 450px;',
			'options'   => $user_roles,
			'custom_attributes' => $custom_attributes,
		),
		array(
			'desc_tip'  => $desc_tip,
			'desc'      => __( 'Exclude User Roles', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_user_roles_exclude_' . $key,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'css'       => 'width: 450px;',
			'options'   => $user_roles,
			'custom_attributes' => $custom_attributes,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'  => 'sectionend',
		'id'    => 'wcj_payment_gateways_by_user_role_gateways_options',
	),
) );
return $settings;
