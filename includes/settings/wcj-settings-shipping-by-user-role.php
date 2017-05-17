<?php
/**
 * Booster for WooCommerce Settings - Shipping by User Role
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title' => __( 'Shipping Methods', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ) . ' ' .
			sprintf(
				__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
				admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' )
			),
		'id'    => 'wcj_shipping_by_user_role_methods_options',
	),
);
$user_roles = wcj_get_user_roles_options();
foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
	if ( ! in_array( $method->id, array( 'flat_rate', 'local_pickup' ) ) ) {
		$custom_attributes = apply_filters( 'booster_get_message', '', 'disabled' );
		if ( '' == $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_get_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip = '';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'     => $method->get_method_title(),
			'desc_tip'  => $desc_tip,
			'desc'      => __( 'Include User Roles', 'woocommerce-jetpack' ),
			'id'        => 'wcj_shipping_user_roles_include_' . $method->id,
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
			'id'        => 'wcj_shipping_user_roles_exclude_' . $method->id,
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
		'id'    => 'wcj_shipping_by_user_role_methods_options',
	),
) );
return $settings;
