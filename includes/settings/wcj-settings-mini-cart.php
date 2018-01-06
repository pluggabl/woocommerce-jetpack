<?php
/**
 * Booster for WooCommerce - Settings - Mini Cart Custom Info
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Mini Cart Custom Info Blocks', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_mini_cart_custom_info_options',
	),
	array(
		'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
		'id'       => 'wcj_mini_cart_custom_info_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_mini_cart_custom_info_options',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'title',
			'id'       => 'wcj_mini_cart_custom_info_options_' . $i,
		),
		array(
			'title'    => __( 'Content', 'woocommerce-jetpack' ),
			'id'       => 'wcj_mini_cart_custom_info_content_' . $i,
			'default'  => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
			'type'     => 'textarea',
			'css'      => 'width:30%;min-width:300px;height:100px;',
		),
		array(
			'title'    => __( 'Position', 'woocommerce-jetpack' ),
			'id'       => 'wcj_mini_cart_custom_info_hook_' . $i,
			'default'  => 'woocommerce_after_mini_cart',
			'type'     => 'select',
			'options'  => array(
				'woocommerce_before_mini_cart'                    => __( 'Before mini cart', 'woocommerce-jetpack' ),
				'woocommerce_widget_shopping_cart_before_buttons' => __( 'Before buttons', 'woocommerce-jetpack' ),
				'woocommerce_after_mini_cart'                     => __( 'After mini cart', 'woocommerce-jetpack' ),
			),
			'css'      => 'width:250px;',
		),
		array(
			'title'    => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_mini_cart_custom_info_priority_' . $i,
			'default'  => 10,
			'type'     => 'number',
			'css'      => 'width:250px;',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_mini_cart_custom_info_options_' . $i,
		),
	) );
}
return $settings;
