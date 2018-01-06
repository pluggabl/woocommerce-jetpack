<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Cost Price
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    wcj_purchase_price_currency
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$main_product_id = get_the_ID();
$_product = wc_get_product( $main_product_id );
if ( ! $_product ) {
	return array();
}
$products = array();
if ( $_product->is_type( 'variable' ) && 'no' === get_option( 'wcj_purchase_data_variable_as_simple_enabled', 'no' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}
$options = array();
foreach ( $products as $product_id => $desc ) {
	$product_options = array(
		array(
			'name'       => 'wcj_purchase_price_' . $product_id,
			'default'    => 0,
			'type'       => 'price',
			'title'      => __( 'Product cost (purchase) price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_price',
			'enabled'    => get_option( 'wcj_purchase_price_enabled', 'yes' ),
		),
		array(
			'name'       => 'wcj_purchase_price_extra_' . $product_id,
			'default'    => 0,
			'type'       => 'price',
			'title'      => __( 'Extra expenses (shipping etc.)', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_price_extra',
			'enabled'    => get_option( 'wcj_purchase_price_extra_enabled', 'yes' ),
		),
		array(
			'name'       => 'wcj_purchase_price_affiliate_commission_' . $product_id,
			'default'    => 0,
			'type'       => 'price',
			'title'      => __( 'Affiliate commission', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_price_affiliate_commission',
			'enabled'    => get_option( 'wcj_purchase_price_affiliate_commission_enabled', 'no' ),
		),
	);
	$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
	for ( $i = 1; $i <= $total_number; $i++ ) {
		$the_title = get_option( 'wcj_purchase_data_custom_price_field_name_' . $i, '' );
		if ( '' == $the_title ) {
			continue;
		}
		$the_type           = get_option( 'wcj_purchase_data_custom_price_field_type_' . $i, 'fixed' );
		$the_default_value  = get_option( 'wcj_purchase_data_custom_price_field_default_value_' . $i, 0 );
		$the_title .= ( 'fixed' === $the_type ) ? ' (' . get_woocommerce_currency_symbol() . ')'  : ' (' . '%' . ')';
		$product_options[] = array(
			'name'       => 'wcj_purchase_price_custom_field_' . $i . '_' . $product_id,
			'default'    => $the_default_value,
			'type'       => 'price',
			'title'      => $the_title,
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_price_custom_field_' . $i,
			'enabled'    => 'yes',
		);
	}
	$product_options = array_merge( $product_options, array(
		array(
			'name'       => 'wcj_purchase_date_' . $product_id,
			'default'    => '',
			'type'       => 'date',
			'title'      => '<em>' . __( '(Last) Purchase date', 'woocommerce-jetpack' ) . '</em>',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_date',
			'enabled'    => get_option( 'wcj_purchase_date_enabled', 'yes' ),
		),
		array(
			'name'       => 'wcj_purchase_partner_' . $product_id,
			'default'    => '',
			'type'       => 'text',
			'title'      => '<em>' . __( 'Seller', 'woocommerce-jetpack' ) . '</em>',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_partner',
			'enabled'    => get_option( 'wcj_purchase_partner_enabled', 'yes' ),
		),
		array(
			'name'       => 'wcj_purchase_info_' . $product_id,
			'default'    => '',
			'type'       => 'textarea',
			'title'      => '<em>' . __( 'Purchase info', 'woocommerce-jetpack' ) . '</em>',
			'desc'       => $desc,
			'product_id' => $product_id,
			'meta_name'  => '_' . 'wcj_purchase_info',
			'enabled'    => get_option( 'wcj_purchase_info_enabled', 'yes' ),
		),
	) );
	$product_options = apply_filters( 'wcj_purchase_data_product_options', $product_options, $product_id, $desc );
	$options = array_merge( $options, $product_options );
}
return $options;
