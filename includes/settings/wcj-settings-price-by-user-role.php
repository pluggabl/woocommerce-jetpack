<?php
/**
 * WooCommerce Jetpack Settings - Price by User Role
 *
 * @version 2.7.2
 * @since   2.7.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_by_user_role_options',
	),
	array(
		'title'    => __( 'Enable per Product Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will add new "Booster: Price by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_user_role_per_product_enabled',
		'default'  => 'yes',
	),
	array(
		'title'    => __( 'Per Product Settings Type', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_price_by_user_role_per_product_type',
		'default'  => 'fixed',
		'options'  => array(
			'fixed'      => __( 'Fixed', 'woocommerce-jetpack' ),
			'multiplier' => __( 'Multiplier', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Show Roles on per Product Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'If per product settings is enabled, you can choose which roles to show on product\'s edit page. Leave blank to show all roles.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'id'       => 'wcj_price_by_user_role_per_product_show_roles',
		'default'  => '',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'title'    => __( 'Shipping', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will apply user role multipliers to shipping calculations.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_user_role_shipping_enabled',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Search Engine Bots', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable Price by User Role for Bots', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_for_bots_disabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_user_role_options',
	),
	array(
		'title'    => __( 'Roles & Multipliers', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf( __( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ),
		'id'       => 'wcj_price_by_user_role_multipliers_options',
	),
);
foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $role_data['name'],
			'id'       => 'wcj_price_by_user_role_' . $role_key,
			'default'  => 1,
			'type'     => 'number',
			'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
		),
		array(
			'desc'     => __( 'Make Empty Price', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_user_role_empty_price_' . $role_key,
			'default'  => 'no',
			'type'     => 'checkbox',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_user_role_multipliers_options',
	),
) );
return $settings;
