<?php
/**
 * Booster for WooCommerce - Settings - Coupon Code Generator
 *
 * @version 3.2.3
 * @since   3.2.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

return array(
	array(
		'title'    => __( 'Coupons Options', 'woocommerce-jetpack' ),
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
		'options'  => array(
			'crc32'          => 'crc32'                                        . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 8 )  . ')',
			'md5'            => 'md5'                                          . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
			'sha1'           => 'sha1'                                         . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 40 ) . ')',
			'random_letters' => __( 'Random letters', 'woocommerce-jetpack' )  . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
		),
	),
	array(
		'title'    => __( 'Length', 'woocommerce-jetpack' ),
		'id'       => 'wcj_coupons_code_generator_length',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_coupon_code_generator_options',
	),
);
