<?php
/**
 * Booster for WooCommerce - Settings - Coupon Code Generator
 *
 * @version 7.0.0
 * @since   3.2.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$algorithms = array(
	/* translators: %d: translators Added */
	'crc32'                      => __( 'Hash', 'woocommerce-jetpack' ) . ': crc32 (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 8 ) . ')',
	/* translators: %d: translators Added */
	'md5'                        => __( 'Hash', 'woocommerce-jetpack' ) . ': md5 (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	/* translators: %d: translators Added */
	'sha1'                       => __( 'Hash', 'woocommerce-jetpack' ) . ': sha1 (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 40 ) . ')',
	/* translators: %d: translators Added */
	'random_letters_and_numbers' => __( 'Random letters and numbers', 'woocommerce-jetpack' ) . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	/* translators: %d: translators Added */
	'random_letters'             => __( 'Random letters', 'woocommerce-jetpack' ) . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
	/* translators: %d: translators Added */
	'random_numbers'             => __( 'Random numbers', 'woocommerce-jetpack' ) . ' (' . sprintf( __( 'length %d', 'woocommerce-jetpack' ), 32 ) . ')',
);

return array(
	array(
		'id'   => 'wcj_coupon_code_generator_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_coupon_code_generator_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_coupon_code_generator_general_options_tab'   => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_coupon_code_generator_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_coupon_code_generator_options',
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
		'title'             => __( 'Algorithm', 'woocommerce-jetpack' ),
		'id'                => 'wcj_coupons_code_generator_algorithm',
		'default'           => 'crc32',
		'type'              => 'select',
		'options'           => $algorithms,
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Algorithms: %s.', 'woocommerce-jetpack' ), implode( '; ', $algorithms ) ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Length', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Length value will be ignored if set above the maximum length for selected algorithm. Set to zero to use full length for selected algorithm.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_coupons_code_generator_length',
		'default'           => 0,
		'type'              => 'number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'id'   => 'wcj_coupon_code_generator_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_coupon_code_generator_general_options_tab',
		'type' => 'tab_end',
	),
);
