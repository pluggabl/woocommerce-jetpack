<?php
/**
 * Booster for WooCommerce - Settings - Modules By User Roles
 *
 * @version 7.0.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$user_roles = wcj_get_user_roles_options();
$modules    = array();
foreach ( w_c_j()->all_modules as $module_id => $module_obj ) {
	$modules[ $module_id ] = $module_obj->short_desc;
}
unset( $modules['modules_by_user_roles'] );

$tab_ids = array();
foreach ( $user_roles as $role_id => $role_desc ) {
	$tab_ids[ 'modules_by_user_roles_' . $role_id . '_tab' ] = $role_desc;
}

$settings = array(
	array(
		'id'   => 'modules_by_user_roles_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'modules_by_user_roles_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $user_roles as $role_id => $role_desc ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'modules_by_user_roles_' . $role_id . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $role_desc,
				'type'  => 'title',
				'id'    => 'wcj_modules_by_user_roles_' . $role_id,
			),
			array(
				'title'    => __( 'Enable Modules', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Select modules which should be enabled for current user role. All other modules will be disabled. Ignored if left empty.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_modules_by_user_roles_incl_' . $role_id,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $modules,
			),
			array(
				'title'    => __( 'Disable Modules', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Select modules which should be disabled for current user role. All other modules will be enabled. Ignored if left empty.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_modules_by_user_roles_excl_' . $role_id,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $modules,
			),
			array(
				'id'   => 'wcj_modules_by_user_roles_' . $role_id,
				'type' => 'sectionend',
			),
			array(
				'id'   => 'modules_by_user_roles_' . $role_id . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
