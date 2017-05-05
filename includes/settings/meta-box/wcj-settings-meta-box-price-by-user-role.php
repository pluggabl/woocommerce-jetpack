<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Price by User Role
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$main_product_id = get_the_ID();
$_product = wc_get_product( $main_product_id );
$products = array();
if ( $_product->is_type( 'variable' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}
$options = array(
	array(
		'type'       => 'title',
		'title'      => __( 'Per Product Settings (press Update after changing)', 'woocommerce-jetpack' ),
	),
	array(
		'name'       => 'wcj_price_by_user_role_per_product_settings_enabled',
		'default'    => 'no',
		'type'       => 'select',
		'options'    => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
	),
);
if ( 'yes' === get_post_meta( wcj_get_product_id( $_product ), '_' . 'wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
	$visible_roles = get_option( 'wcj_price_by_user_role_per_product_show_roles', '' );
	foreach ( $products as $product_id => $desc ) {
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			if ( ! empty( $visible_roles ) ) {
				if ( ! in_array( $role_key, $visible_roles ) ) {
					continue;
				}
			}
			if ( 'fixed' === get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ) ) {
				$prices_or_multiplier = array(
					array(
						'name'       => 'wcj_price_by_user_role_regular_price_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Regular Price', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_' . 'wcj_price_by_user_role_regular_price_' . $role_key,
					),
					array(
						'name'       => 'wcj_price_by_user_role_sale_price_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Sale Price', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_' . 'wcj_price_by_user_role_sale_price_' . $role_key,
					),
				);
			} else {
				$prices_or_multiplier = array(
					array(
						'name'       => 'wcj_price_by_user_role_multiplier_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Multiplier', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_' . 'wcj_price_by_user_role_multiplier_' . $role_key,
					),
				);
			}
			$options = array_merge( $options, array(
				array(
					'type'       => 'title',
					'title'      => '<em>' . $role_data['name'] . '</em>',
				),
			),
			$prices_or_multiplier,
			array(
				array(
					'name'       => 'wcj_price_by_user_role_empty_price_' . $role_key . '_' . $product_id,
					'default'    => 'no',
					'type'       => 'select',
					'options'    => array(
						'yes' => __( 'Yes', 'woocommerce-jetpack' ),
						'no'  => __( 'No', 'woocommerce-jetpack' ),
					),
					'title'      => __( 'Make Empty Price', 'woocommerce-jetpack' ),
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_' . 'wcj_price_by_user_role_empty_price_' . $role_key,
				),
			) );
		}
	}
}
return $options;
