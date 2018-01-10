<?php
/**
 * Booster for WooCommerce - Settings - Cart Custom Info
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    (maybe) 'Hide "Note: Shipping and taxes are estimated..." message on Cart page' - `wcj_cart_hide_shipping_and_taxes_estimated_message`
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	// Cart Custom Info Options
	array(
		'title'    => __( 'Cart Custom Info Blocks', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_cart_custom_info_options',
		'desc'     => __( 'This feature allows you to add a final checkpoint for your customers before they proceed to payment.', 'woocommerce-jetpack' ) . '<br>' .
			__( 'Show custom information at on the cart page using Booster\'s various shortcodes and give your customers a seamless cart experience.', 'woocommerce-jetpack' ) . '<br>' .
			__( 'For example, show them the total weight of their items, any additional fees or taxes, or a confirmation of the address their products are being sent to.', 'woocommerce-jetpack' ),
	),
	array(
		'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
		'id'       => 'wcj_cart_custom_info_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_cart_custom_info_options',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'title',
			'id'       => 'wcj_cart_custom_info_options_' . $i,
		),
		array(
			'title'    => __( 'Content', 'woocommerce-jetpack' ),
			'id'       => 'wcj_cart_custom_info_content_' . $i,
			'default'  => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
			'type'     => 'textarea',
			'css'      => 'width:100%;height:200px;',
		),
		array(
			'title'    => __( 'Position', 'woocommerce-jetpack' ),
			'id'       => 'wcj_cart_custom_info_hook_' . $i,
			'default'  => 'woocommerce_after_cart_totals',
			'type'     => 'select',
			'options'  => wcj_get_cart_filters(),
		),
		array(
			'title'    => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_cart_custom_info_priority_' . $i,
			'default'  => 10,
			'type'     => 'number',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_cart_custom_info_options_' . $i,
		),
	) );
}
$settings = array_merge( $settings, array(
	// Cart Items Table Custom Info Options
	array(
		'title'    => __( 'Cart Items Table Custom Info', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_cart_custom_info_item_options',
		'desc'     => '',
	),
	array(
		'title'    => __( 'Add to Each Item Name', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here. E.g.: [wcj_product_sku]. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_cart_custom_info_item',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_cart_custom_info_item_options',
	),
) );
return $settings;
