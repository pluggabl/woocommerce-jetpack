<?php
/**
 * Booster for WooCommerce - Settings - Gateways Fees and Discounts
 *
 * @version 3.6.2
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products           = wcj_get_products();
$settings           = array();
$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$settings = array_merge( $settings, array(
		array(
			'title'     => $gateway->title . ( $gateway->is_available() ? ' &#10003;' : '' ),
			'type'      => 'title',
			'id'        => 'wcj_gateways_fees_options_' . $key,
		),
		array(
			'title'     => __( 'Fee (or Discount) Title', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Fee (or discount) title to show to customer.', 'woocommerce-jetpack' ),
			'desc'      => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_text_' . $key,
			'default'   => '',
			'type'      => 'text',
		),
		array(
			'title'     => __( 'Fee (or Discount) Type', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Percent or fixed value.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_type_' . $key,
			'default'   => 'fixed',
			'type'      => 'select',
			'options'   => array(
				'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
				'percent' => __( 'Percent', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'     => __( 'Fee (or Discount) Value', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'The value. For discount enter a negative number.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_value_' . $key,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '0.01' ),
		),
		array(
			'title'     => __( 'Minimum Cart Amount', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Minimum cart amount for adding the fee (or discount).', 'woocommerce-jetpack' ),
			'desc'      => __( 'Set 0 to disable', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_min_cart_amount_' . $key,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '0.01', 'min' => '0' ),
		),
		array(
			'title'     => __( 'Maximum Cart Amount', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Maximum cart amount for adding the fee (or discount).', 'woocommerce-jetpack' ),
			'desc'      => __( 'Set 0 to disable', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_max_cart_amount_' . $key,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '0.01', 'min' => '0' ),
		),
		array(
			'title'     => __( 'Rounding', 'woocommerce-jetpack' ),
			'desc'      => __( 'Round the fee (or discount) value before adding to the cart', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_round_' . $key,
			'default'   => 'no',
			'type'      => 'checkbox',
		),
		array(
			'title'     => __( 'Rounding Precision', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'If Rounding is enabled, set precision (i.e. number of decimals) here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_round_precision_' . $key,
			'default'   => get_option( 'woocommerce_price_num_decimals', 2 ),
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '1', 'min' => '0' ),
		),
		array(
			'title'     => __( 'Taxing', 'woocommerce-jetpack' ),
			'desc'      => __( 'Taxable', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_is_taxable_' . $key,
			'default'   => 'no',
			'type'      => 'checkbox',
		),
		array(
			'title'     => __( 'Tax Class', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'If Taxing is enabled, set tax class here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_tax_class_id_' . $key,
			'default'   => '',
			'type'      => 'select',
			'options'   => array_merge( array( __( 'Standard Rate', 'woocommerce-jetpack' ) ), WC_Tax::get_tax_classes() ),
		),
		array(
			'title'     => __( 'Exclude Shipping when Calculating Total Cart Amount', 'woocommerce-jetpack' ),
			'desc'      => __( 'Exclude', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'This affects "Percent" type fees and "Minimum/Maximum Cart Amount" options.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_exclude_shipping_' . $key,
			'default'   => 'no',
			'type'      => 'checkbox',
		),
		array(
			'title'     => __( 'Require Products', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Require at least one of selected products to be in cart for fee to be applied.', 'woocommerce-jetpack' ) . ' ' .
				__( 'Ignored if empty.', 'woocommerce-jetpack' ),
			'id'        => "wcj_gateways_fees_include_products[$key]",
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'options'   => $products,
			'desc'      => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
		array(
			'title'     => __( 'Exclude Products', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Do not apply fee, if at least one of selected products is in cart.', 'woocommerce-jetpack' ) . ' ' .
				__( 'Ignored if empty.', 'woocommerce-jetpack' ),
			'id'        => "wcj_gateways_fees_exclude_products[$key]",
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'options'   => $products,
			'desc'      => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
		array(
			'type'      => 'sectionend',
			'id'        => 'wcj_gateways_fees_options_' . $key,
		),
	) );
}
return $settings;
