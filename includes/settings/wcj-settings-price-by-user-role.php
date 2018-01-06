<?php
/**
 * Booster for WooCommerce - Settings - Price by User Role
 *
 * @version 3.2.2
 * @since   2.8.0
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
		'desc'     => __( 'If per product settings are enabled, you can choose which roles to show on product\'s edit page. Leave blank to show all roles.', 'woocommerce-jetpack' ),
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
		'title'    => __( 'Disable Price by User Role for Regular Price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disable price by user role for regular price when using multipliers (global or per product).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_disable_for_regular_price',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Search Engine Bots', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable Price by User Role for Bots', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_for_bots_disabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Advanced: Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. Set to zero to use default priority.' ),
		'id'       => 'wcj_price_by_user_role_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
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
	array(
		'title'    => __( 'Disable Price by User Role for Products on Sale', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_disable_for_products_on_sale',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
);
foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $role_data['name'],
			'id'       => 'wcj_price_by_user_role_' . $role_key,
			'default'  => 1,
			'type'     => 'wcj_number_plus_checkbox_start',
			'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
		),
		array(
			'desc'     => __( 'Make Empty Price', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_user_role_empty_price_' . $role_key,
			'default'  => 'no',
			'type'     => 'wcj_number_plus_checkbox_end',
		),
	) );
}
$product_cats_options = array();
$product_cats = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ){
	foreach ( $product_cats as $product_cat ) {
		$product_cats_options[ $product_cat->term_id ] = $product_cat->name;
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_user_role_multipliers_options',
	),
	array(
		'title'    => __( 'Price by User Role by Products Categories', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_by_user_role_categories_options',
	),
	array(
		'title'    => __( 'Categories', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save module\'s settings after changing this option to see new settings fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_categories',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $product_cats_options,
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
) );
$categories = apply_filters( 'booster_option', '', get_option( 'wcj_price_by_user_role_categories', '' ) );
if ( ! empty( $categories ) ) {
	foreach ( $categories as $category ) {
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => $product_cats_options[ $category ] . ': ' . $role_data['name'],
					'id'       => 'wcj_price_by_user_role_cat_' . $category . '_' . $role_key,
					'default'  => 1,
					'type'     => 'wcj_number_plus_checkbox_start',
					'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
				),
				array(
					'desc'     => __( 'Make Empty Price', 'woocommerce-jetpack' ),
					'id'       => 'wcj_price_by_user_role_cat_empty_price_' . $category . '_' . $role_key,
					'default'  => 'no',
					'type'     => 'wcj_number_plus_checkbox_end',
				),
			) );
		}
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_user_role_categories_options',
	),
) );
return $settings;
