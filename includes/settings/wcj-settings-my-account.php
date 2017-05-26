<?php
/**
 * Booster for WooCommerce - Settings - My Account
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_my_account_options',
	),
	array(
		'title'    => __( 'Add Order Complete Action', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_add_order_complete_action',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_my_account_options',
	),
);
