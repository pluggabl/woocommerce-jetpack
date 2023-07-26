<?php
/**
 * Booster for WooCommerce - Settings - Coupon by User Role
 *
 * @version 7.0.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'wcj_coupon_by_user_role_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_coupon_by_user_role_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_coupon_by_user_role_all_coupons_tab' => __( 'All Coupons', 'woocommerce-jetpack' ),
			'wcj_coupon_by_user_role_per_coupons_tab' => __( 'Per Coupon', 'woocommerce-jetpack' ),
			'wcj_coupon_by_user_role_message_tab'     => __( 'Message', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_all_coupons_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'All Coupons', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_coupon_by_user_role_all_coupons_options',
	),
	array(
		'title'    => __( 'Disable All Coupons for Selected User Roles', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will disable all coupons for selected user roles. Coupons will be disabled completely, including coupon code input on the cart page.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'default'  => '',
		'id'       => 'wcj_coupon_by_user_role_disabled',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'title'    => __( 'Invalidate All Coupons for Selected User Roles', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will invalidate all coupons for selected user roles. Coupon code input will still be available on the cart page.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'default'  => '',
		'id'       => 'wcj_coupon_by_user_role_invalid',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_all_coupons_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_all_coupons_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_per_coupons_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Per Coupon', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_coupon_by_user_role_per_coupon_options',
	),
	array(
		'title'             => __( 'Invalidate per Coupon', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will add "Booster: Coupon by User Role" meta box to each coupon\'s admin edit page.', 'woocommerce-jetpack' ) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'type'              => 'checkbox',
		'default'           => 'no',
		'id'                => 'wcj_coupon_by_user_role_invalid_per_coupon',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_per_coupon_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_per_coupons_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_message_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Message', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_coupon_by_user_role_messages_options',
	),
	array(
		'title'             => __( '"Coupon is not valid" Message', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Message that will be displayed for invalid coupons by user role.', 'woocommerce-jetpack' ),
		'type'              => 'textarea',
		'default'           => __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_coupon_by_user_role_invalid_message',
		'css'               => 'width:100%;',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_messages_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_coupon_by_user_role_message_tab',
		'type' => 'tab_end',
	),
);
