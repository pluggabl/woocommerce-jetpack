<?php
/**
 * Booster for WooCommerce - Settings - Checkout Customization
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
		'id'       => 'wcj_checkout_customization_options',
	),
	array(
		'title'    => __( '"Create an account?" Checkbox', 'woocommerce-jetpack' ),
		'desc_tip' => __( '"Create an account?" checkbox default value', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_create_account_default_checked',
		'default'  => 'default',
		'type'     => 'select',
		'options'  => array(
			'default'     => __( 'WooCommerce default', 'woocommerce-jetpack' ),
			'checked'     => __( 'Checked', 'woocommerce-jetpack' ),
			'not_checked' => __( 'Not checked', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Hide "Order Again" Button on "View Order" Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_hide_order_again',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_customization_options',
	),
);
