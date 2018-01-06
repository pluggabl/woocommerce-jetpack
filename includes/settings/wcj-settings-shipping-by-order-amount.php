<?php
/**
 * Booster for WooCommerce Settings - Shipping Methods by Min/Max Order Amount
 *
 * @version 3.2.1
 * @since   3.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'   => __( 'Shipping Methods by Min/Max Order Amount', 'woocommerce-jetpack' ),
		'type'    => 'title',
		'desc'    => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_shipping_by_order_amount_options',
	),
);
foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
	if ( ! in_array( $method->id, array( 'flat_rate', 'free_shipping' ) ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' == $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip = '';
	}
	$custom_attributes = array_merge( $custom_attributes, array( 'min' => 0 ) );
	$settings = array_merge( $settings, array(
		array(
			'title'     => $method->get_method_title(),
			'desc_tip'  => $desc_tip,
			'desc'      => '<br>' . __( 'Minimum order amount', 'woocommerce-jetpack' ),
			'id'        => 'wcj_shipping_by_order_amount_min_' . $method->id,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => $custom_attributes,
		),
		array(
			'desc_tip'  => $desc_tip,
			'desc'      => '<br>' . __( 'Maximum order amount', 'woocommerce-jetpack' ),
			'id'        => 'wcj_shipping_by_order_amount_max_' . $method->id,
			'default'   => 0,
			'type'      => 'number',
			'custom_attributes' => $custom_attributes,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'  => 'sectionend',
		'id'    => 'wcj_shipping_by_order_amount_options',
	),
) );
return $settings;
