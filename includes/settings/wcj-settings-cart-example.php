<?php
/**
 * Booster for WooCommerce - Settings - Cart Custom Info (Example with help_text and friendly_label)
 *
 * This is an EXAMPLE file demonstrating how to use the new help_text and friendly_label fields.
 * This file is NOT loaded by the plugin - it's for reference only.
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$example_settings = array(
	array(
		'title'     => __( 'Cart Custom Info Blocks', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'id'        => 'wcj_cart_custom_info_options',
		'desc'      => __( 'This feature allows you to add a final checkpoint for your customers before they proceed to payment.', 'woocommerce-jetpack' ),
	),
	array(
		'title'          => __( 'Total Blocks', 'woocommerce-jetpack' ),
		'id'             => 'wcj_cart_custom_info_total_number',
		'default'        => 1,
		'type'           => 'custom_number',
		'desc'           => apply_filters( 'booster_message', '', 'desc' ),
		'help_text'      => __( 'Set the number of custom information blocks you want to display on the cart page. Each block can be positioned independently.', 'woocommerce-jetpack' ),
		'friendly_label' => __( 'Number of custom info blocks', 'woocommerce-jetpack' ),
	),
	array(
		'title'          => __( 'Content', 'woocommerce-jetpack' ),
		'id'             => 'wcj_cart_custom_info_content_1',
		'default'        => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
		'type'           => 'textarea',
		'css'            => 'width:100%;height:200px;',
		'help_text'      => __( 'Enter the content to display. You can use Booster shortcodes like [wcj_cart_items_total_weight] to show dynamic cart information.', 'woocommerce-jetpack' ),
		'friendly_label' => __( 'Custom block content', 'woocommerce-jetpack' ),
	),
	array(
		'title'          => __( 'Position', 'woocommerce-jetpack' ),
		'id'             => 'wcj_cart_custom_info_hook_1',
		'default'        => 'woocommerce_after_cart_totals',
		'type'           => 'select',
		'options'        => array(
			'woocommerce_before_cart'                    => __( 'Before cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_table'              => __( 'Before cart table', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_table'               => __( 'After cart table', 'woocommerce-jetpack' ),
			'woocommerce_cart_collaterals'               => __( 'Cart collaterals', 'woocommerce-jetpack' ),
			'woocommerce_after_cart'                     => __( 'After cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_totals'             => __( 'Before cart totals', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_totals'              => __( 'After cart totals', 'woocommerce-jetpack' ),
			'woocommerce_proceed_to_checkout'            => __( 'Proceed to checkout', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_contents'            => __( 'After cart contents', 'woocommerce-jetpack' ),
		),
		'help_text'      => __( 'Choose where on the cart page you want this information block to appear. Different positions work better for different types of content.', 'woocommerce-jetpack' ),
		'friendly_label' => __( 'Where to show this block', 'woocommerce-jetpack' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_cart_custom_info_options',
	),
);

/*
$help_text = wcj_get_setting_help_text( 'cart', 'wcj_cart_custom_info_total_number', '' );
if ( ! empty( $help_text ) ) {
    echo '<span class="wcj-help-tooltip" title="' . esc_attr( $help_text ) . '">?</span>';
}

$friendly_label = wcj_get_setting_friendly_label( 'cart', 'wcj_cart_custom_info_total_number', '' );
if ( ! empty( $friendly_label ) ) {
    echo '<span class="wcj-friendly-label">' . esc_html( $friendly_label ) . '</span>';
}
*/

return $example_settings;
