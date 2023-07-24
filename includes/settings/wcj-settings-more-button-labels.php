<?php
/**
 * Booster for WooCommerce - Settings - More Button Labels
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'more_button_labels_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'more_button_labels_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'more_button_labels_order_button_tab' => __( 'Place order (Order now) Button', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'more_button_labels_order_button_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Place order (Order now) Button', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_checkout_place_order_button_options',
	),
	array(
		'title'    => __( 'Text', 'woocommerce-jetpack' ),
		'desc'     => __( 'Leave blank for WooCommerce default.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Button on the checkout page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_place_order_button_text',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Override Default Text', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if button text is not changing for some payment gateway (e.g. PayPal).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_checkout_place_order_button_override',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_checkout_place_order_button_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'more_button_labels_order_button_tab',
		'type' => 'tab_end',
	),
);
