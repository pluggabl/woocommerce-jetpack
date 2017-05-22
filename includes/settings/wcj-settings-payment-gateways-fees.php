<?php
/**
 * Booster for WooCommerce - Settings - Gateways Fees and Discounts
 *
 * @version 2.8.2
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$settings = array_merge( $settings, array(
		array(
			'title'     => $gateway->title,
			'type'      => 'title',
//			'desc'      => ( $gateway->is_available() ? __( 'Available', 'woocommerce-jetpack' ) : __( 'Not available', 'woocommerce-jetpack' ) ),
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
			'custom_attributes' => array( 'step' => '0.01', 'min'  => '0' ),
		),
		array(
			'title'     => __( 'Maximum Cart Amount', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Maximum cart amount for adding the fee (or discount).', 'woocommerce-jetpack' ),
			'desc'      => __( 'Set 0 to disable', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_max_cart_amount_' . $key,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '0.01', 'min'  => '0' ),
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
			'desc_tip'  => __( 'If Rounding is enabled, set precision here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_fees_round_precision_' . $key,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => array( 'step' => '1', 'min'  => '0' ),
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
			'type'      => 'sectionend',
			'id'        => 'wcj_gateways_fees_options_' . $key,
		),
	) );
}
return $settings;
