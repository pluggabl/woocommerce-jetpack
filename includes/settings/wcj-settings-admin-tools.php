<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 5.6.2
 * @since   2.7.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message = apply_filters( 'booster_message', '', 'desc' );
return array(
	array(
		'title' => __( 'Admin Tools Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_tools_general_options',
	),
	array(
		'title'    => __( 'Show Booster Menus Only to Admin', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf(
			/* translators: %1$s,%2$s: translators Added */
			__( 'Will require %1$s capability to see Booster menus (instead of %2$s capability).', 'woocommerce-jetpack' ),
			'<code>manage_options</code>',
			'<code>manage_woocommerce</code>'
		),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_menus_to_admin_only',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Suppress Admin Connect Notice', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf(
			/* translators: %s: translators Added */
			__( 'Will remove "%s" admin notice.', 'woocommerce-jetpack' ),
			__( 'Connect your store to WooCommerce.com to receive extensions updates and support.', 'woocommerce-jetpack' )
		),
		'id'       => 'wcj_admin_tools_suppress_connect_notice',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Suppress Admin Notices', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will remove admin notices (including the Connect notice).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_suppress_admin_notices',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Enable Interface By User Roles', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'The interface can\'t be disabled for The Administrator role.', 'woocommerce-jetpack' ) . '<br /><br />' . __( 'Leave it empty to enable the interface for all the roles.', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Disables the whole Booster admin interface for not selected roles.', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'id'                => 'wcj_admin_tools_enable_interface_by_role',
		'default'           => '',
		'type'              => 'multiselect',
		'class'             => 'chosen_select',
		'options'           => wcj_get_user_roles_options(),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_tools_general_options',
	),
	array(
		'title' => __( 'Orders Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_tools_orders_options',
	),
	array(
		'title'    => __( 'Show Order Meta', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will show order meta table in meta box.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_order_meta_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_tools_orders_options',
	),
	array(
		'title' => __( 'Products Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_tools_products_options',
	),
	array(
		'title'    => __( 'Show Product Meta', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will show product meta table in meta box.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_product_meta_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show Variable Product Pricing Table', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will allow to set all variations prices in single meta box.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_variable_product_pricing_table_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Product Revisions', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will enable product revisions.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_revisions_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'JSON Product Search Limit', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will set the maximum number of products to return on JSON search (e.g. when setting Upsells and Cross-sells on product edit page).', 'woocommerce-jetpack' ) . ' ' .
			__( 'Ignored if set to zero.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_json_search_limit',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_tools_products_options',
	),
	array(
		'title' => __( 'Users Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_tools_users_options',
	),
	array(
		'title'             => __( 'Shop Manager Editable Roles', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Changes the roles the Shop Manager role can edit.', 'woocommerce-jetpack' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'id'                => 'wcj_admin_tools_shop_manager_editable_roles',
		'default'           => apply_filters( 'woocommerce_shop_manager_editable_roles', array( 'customer' ) ),
		'type'              => 'multiselect',
		'class'             => 'chosen_select',
		'options'           => wcj_get_user_roles_options(),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_tools_users_options',
	),
);
