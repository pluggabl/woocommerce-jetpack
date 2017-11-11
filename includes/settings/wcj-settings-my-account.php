<?php
/**
 * Booster for WooCommerce - Settings - My Account
 *
 * @version 2.9.0
 * @since   2.9.0
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
		'title'    => __( 'Add Order Status Actions', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Let your customers change order status manually.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_my_account_add_order_status_actions',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => wcj_get_order_statuses(),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_my_account_options',
	),
);
