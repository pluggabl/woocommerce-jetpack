<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Wholesale Price
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_id = get_the_ID();
$_product = wc_get_product( $product_id );
if ( ! $_product ) {
	return array();
}
$discount_type_options = array(
	'percent'        => __( 'Percent', 'woocommerce-jetpack' ),
	'fixed'          => __( 'Fixed', 'woocommerce-jetpack' ),
	'price_directly' => __( 'Price directly', 'woocommerce-jetpack' ),
);
if ( $_product->is_type( 'variable' ) ) {
	unset( $discount_type_options['price_directly'] );
}
$options = array(
	array(
		'name'     => 'wcj_wholesale_price_per_product_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'    => __( 'Enable per Product Levels', 'woocommerce-jetpack' ),
	),
	array(
		'name'     => 'wcj_wholesale_price_discount_type',
		'default'  => 'percent',
		'type'     => 'select',
		'options'  => $discount_type_options,
		'title'    => __( 'Discount Type', 'woocommerce-jetpack' ),
//		'tooltip'  => __( '\'Price directly\' option is only available for simple (i.e. non variable) product type.', 'woocommerce-jetpack' ),
	),
	array(
		'name'     => 'wcj_wholesale_price_levels_number',
		'default'  => 0,
		'type'     => 'number',
		'title'    => __( 'Number of levels', 'woocommerce-jetpack' ),
		'tooltip'  => __( 'Save product after you change this number.', 'woocommerce-jetpack' ) . apply_filters( 'booster_option', ' ' . __( 'Free Booster\'s version is limited to one level maximum. Please visit http://booster.io to get full version.', 'woocommerce-jetpack' ), '' ),
		'custom_attributes' => 'min="0" max="' . apply_filters( 'booster_option', 1, 1000 ) . '"',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number', true ) ); $i++ ) {
	$options = array_merge( $options, array(
		/*
		array(
			'type'    => 'title',
			'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i,
		),
		*/
		array(
			'name'    => 'wcj_wholesale_price_level_min_qty_' . $i,
			'default' => 0,
			'type'    => 'number',
			'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . __( 'Min quantity', 'woocommerce-jetpack' ),
			'custom_attributes' => 'min="0"',
		),
		array(
			'name'    => 'wcj_wholesale_price_level_discount_' . $i,
			'default' => 0,
			'type'    => 'price',
			'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . ( 'price_directly' === get_post_meta( $product_id, '_' . 'wcj_wholesale_price_discount_type', true ) ? __( 'Price', 'woocommerce-jetpack' ) : __( 'Discount', 'woocommerce-jetpack' ) ),
		),
	) );
}
$user_roles = get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
if ( ! empty( $user_roles ) ) {
	foreach ( $user_roles as $user_role_key ) {
		$options = array_merge( $options, array(
			array(
				'name'    => 'wcj_wholesale_price_levels_number_' . $user_role_key,
				'default' => 0,
				'type'    => 'number',
				'title'   => __( 'Number of levels', 'woocommerce-jetpack' ) . ' [' . $user_role_key . ']',
				'tooltip' => __( 'Save product after you change this number.', 'woocommerce-jetpack' ) . apply_filters( 'booster_option', ' ' . __( 'Free Booster\'s version is limited to one level maximum. Please visit http://booster.io to get full version.', 'woocommerce-jetpack' ), '' ),
				'custom_attributes' => 'min="0" max="' . apply_filters( 'booster_option', 1, 1000 ) . '"',
			),
		) );
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number_' . $user_role_key, true ) ); $i++ ) {
			$options = array_merge( $options, array(
				/*
				array(
					'type'    => 'title',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i,
				),
				*/
				array(
					'name'    => 'wcj_wholesale_price_level_min_qty_' . $user_role_key . '_' . $i,
					'default' => 0,
					'type'    => 'number',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . __( 'Min quantity', 'woocommerce-jetpack' ) . ' [' . $user_role_key . ']',
					'custom_attributes' => 'min="0"',
				),
				array(
					'name'    => 'wcj_wholesale_price_level_discount_' . $user_role_key . '_' . $i,
					'default' => 0,
					'type'    => 'price',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . __( 'Discount', 'woocommerce-jetpack' ) . ' [' . $user_role_key . ']',
				),
			) );
		}
	}
}
return $options;
