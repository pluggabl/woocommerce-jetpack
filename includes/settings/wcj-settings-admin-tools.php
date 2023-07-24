<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 7.0.0
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
		'id'   => 'admin_tools_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'admin_tools_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'admin_tools_general_options_tab'  => __( 'General options', 'woocommerce-jetpack' ),
			'admin_tools_orders_options_tab'   => __( 'Orders options', 'woocommerce-jetpack' ),
			'admin_tools_products_options_tab' => __( 'Products options', 'woocommerce-jetpack' ),
			'admin_tools_users_options_tab'    => __( 'Users options', 'woocommerce-jetpack' ),
			'admin_tools_tool_tab'             => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'admin_tools_general_options_tab',
		'type' => 'tab_start',
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
		'id'   => 'wcj_admin_tools_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'admin_tools_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'admin_tools_orders_options_tab',
		'type' => 'tab_start',
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
		'id'   => 'wcj_admin_tools_orders_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'admin_tools_orders_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'admin_tools_products_options_tab',
		'type' => 'tab_start',
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
		'id'   => 'wcj_admin_tools_products_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'admin_tools_products_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'admin_tools_users_options_tab',
		'type' => 'tab_start',
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
		'id'   => 'wcj_admin_tools_users_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'admin_tools_users_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'admin_tools_tool_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=products_atts&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'Products Attributes', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'Products Attributes', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'admin_tools_tool_tab',
		'type' => 'tab_end',
	),
);
