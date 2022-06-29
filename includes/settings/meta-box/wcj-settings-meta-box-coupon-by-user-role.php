<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Coupon by User Role
 *
 * @version 5.6.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title'    => __( 'Invalidate for roles', 'woocommerce-jetpack' ),
		'tooltip'  => __( 'Invalidate coupon for selected user roles.', 'woocommerce-jetpack' ),
		'name'     => 'wcj_coupon_by_user_role_invalid',
		'default'  => '',
		'type'     => 'select',
		'multiple' => 'true',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
);
