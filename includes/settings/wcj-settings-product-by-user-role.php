<?php
/**
 * Booster for WooCommerce - Settings - Product Visibility by User Role
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_user_role_options',
	),
	array(
		'title'    => __( 'Visibility', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_user_role_visibility',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Purchasable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_user_role_purchasable',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Query', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_user_role_query',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_user_role_options',
	),
);
