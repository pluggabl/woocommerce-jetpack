<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Visibility by User Role
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'name'       => 'wcj_product_by_user_role_visible',
		'default'    => '',
		'type'       => 'select',
		'options'    => wcj_get_user_roles_options(),
		'multiple'   => true,
		'title'      => __( 'Visible for User Roles', 'woocommerce-jetpack' ),
		'class'      => 'chosen_select',
		'css'        => 'width:100%;',
	),
);
