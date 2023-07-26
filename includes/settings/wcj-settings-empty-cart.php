<?php
/**
 * Booster for WooCommerce - Settings - Empty Cart Button
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
		'id'   => 'wcj_empty_cart_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_empty_cart_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_empty_cart_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_empty_cart_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'desc'  => __( 'You can also use <strong>[wcj_empty_cart_button]</strong> shortcode to place the button anywhere on your site.', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_empty_cart_customization_options',
	),
	array(
		'title'             => __( 'Empty Cart Button Text', 'woocommerce-jetpack' ),
		'id'                => 'wcj_empty_cart_text',
		'default'           => 'Empty Cart',
		'type'              => 'text',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Wrapping DIV style', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Style for the button\'s div. Default is "float: right;"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_div_style',
		'default'  => 'float: right;',
		'type'     => 'text',
	),
	array(
		'title'   => __( 'Button HTML Class', 'woocommerce-jetpack' ),
		'id'      => 'wcj_empty_cart_button_class',
		'default' => 'button',
		'type'    => 'text',
	),
	array(
		'title'             => __( 'Button position on the Cart page', 'woocommerce-jetpack' ),
		'id'                => 'wcj_empty_cart_position',
		'default'           => 'woocommerce_after_cart',
		'type'              => 'select',
		'options'           => array(
			'disable'                                    => __( 'Do not add', 'woocommerce-jetpack' ),
			'woocommerce_before_cart'                    => __( 'Before cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_totals'             => __( 'Cart totals: Before cart totals', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'woocommerce-jetpack' ),
			'woocommerce_proceed_to_checkout'            => __( 'Cart totals: After proceed to checkout button', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_totals'              => __( 'Cart totals: After cart totals', 'woocommerce-jetpack' ),
			'woocommerce_cart_collaterals'               => __( 'After cart collaterals', 'woocommerce-jetpack' ),
			'woocommerce_after_cart'                     => __( 'After cart', 'woocommerce-jetpack' ),
		),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Button position on the Checkout page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_empty_cart_checkout_position',
		'default' => 'disable',
		'type'    => 'select',
		'options' => array(
			'disable'                          => __( 'Do not add', 'woocommerce-jetpack' ),
			'woocommerce_before_checkout_form' => __( 'Before checkout form', 'woocommerce-jetpack' ),
			'woocommerce_after_checkout_form'  => __( 'After checkout form', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Confirmation', 'woocommerce-jetpack' ),
		'id'      => 'wcj_empty_cart_confirmation',
		'default' => 'no_confirmation',
		'type'    => 'select',
		'options' => array(
			'no_confirmation'         => __( 'No confirmation', 'woocommerce-jetpack' ),
			'confirm_with_pop_up_box' => __( 'Confirm by pop up box', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Confirmation Text (if enabled)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_empty_cart_confirmation_text',
		'default' => __( 'Are you sure?', 'woocommerce-jetpack' ),
		'type'    => 'text',
	),
	array(
		'id'   => 'wcj_empty_cart_customization_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_empty_cart_general_options_tab',
		'type' => 'tab_end',
	),
);
