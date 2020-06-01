<?php
/**
 * Booster for WooCommerce - Settings - Modules By User Roles
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$user_roles = wcj_get_user_roles_options();
$modules    = array();
foreach ( WCJ()->modules as $module_id => $module_obj ) {
	$modules[ $module_id ] = $module_obj->short_desc;
}
unset( $modules['modules_by_user_roles'] );

$settings = array ();
foreach ( $user_roles as $role_id => $role_desc ) {
	$settings = array_merge( $settings, array(
		array(
			'title'     => $role_desc,
			'type'      => 'title',
			'id'        => 'wcj_modules_by_user_roles_' . $role_id,
		),
		array(
			'title'     => __( 'Enable Modules', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Select modules which should be enabled for current user role. All other modules will be disabled. Ignored if left empty.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_modules_by_user_roles_incl_' . $role_id,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'options'   => $modules,
		),
		array(
			'title'     => __( 'Disable Modules', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Select modules which should be disabled for current user role. All other modules will be enabled. Ignored if left empty.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_modules_by_user_roles_excl_' . $role_id,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'options'   => $modules,
		),
		array(
			'type'      => 'sectionend',
			'id'        => 'wcj_modules_by_user_roles_' . $role_id,
		),
	) );
}
return $settings;
