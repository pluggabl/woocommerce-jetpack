<?php
/**
 * Booster for WooCommerce - Settings - Coupon Code Generator
 *
 * @version 3.2.3
 * @since   3.2.3
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$algorithms = array(
	'crc32'                      => __( 'Hash', 'woocommerce-jetpack' ) . ': ' . 'crc32'      . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 8 )  . ')',
	'md5'                        => __( 'Hash', 'woocommerce-jetpack' ) . ': ' . 'md5'        . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	'sha1'                       => __( 'Hash', 'woocommerce-jetpack' ) . ': ' . 'sha1'       . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 40 ) . ')',
	'random_letters_and_numbers' => __( 'Random letters and numbers', 'woocommerce-jetpack' ) . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	'random_letters'             => __( 'Random letters', 'woocommerce-jetpack' )             . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	'random_numbers'             => __( 'Random numbers', 'woocommerce-jetpack' )             . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
);

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_coupon_code_generator_options',
	),
	array(
		'title'    => __( 'Generate Coupon Code Automatically', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will generate coupon code automatically when adding new coupon.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_coupons_code_generator_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Algorithm', 'woocommerce-jetpack' ),
		'id'       => 'wcj_coupons_code_generator_algorithm',
		'default'  => 'crc32',
		'type'     => 'select',
		'options'  => $algorithms,
		'desc_tip' => sprintf( __( 'Algorithms: %s.', 'woocommerce-jetpack' ), implode( '; ', $algorithms ) ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Length', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Length value will be ignored if set above the maximum length for selected algorithm. Set to zero to use full length for selected algorithm.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_coupons_code_generator_length',
		'default'  => 0,
		'type'     => 'number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_coupon_code_generator_options',
	),
);
