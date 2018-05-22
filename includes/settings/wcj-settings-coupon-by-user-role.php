<?php
/**
 * Booster for WooCommerce - Settings - Coupon by User Role
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Coupon by User Role Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_coupon_by_user_role_options',
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
		'title'    => __( 'Coupon not Valid Message', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Message that will be displayed for invalid coupons by user role.', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'default'  => __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_coupon_by_user_role_invalid_message',
		'css'      => 'width:100%;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_coupon_by_user_role_options',
	),
);
