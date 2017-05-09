<?php
/**
 * Booster for WooCommerce Settings - More Button Labels
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Place order (Order now) Button', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_checkout_place_order_button_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_checkout_place_order_button_options',
	),
);
