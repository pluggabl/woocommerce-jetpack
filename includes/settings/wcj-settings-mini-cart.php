<?php
/**
 * Booster for WooCommerce - Settings - Mini Cart Custom Info
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings           = array(
	array(
		'id'   => 'wcj_mini_cart_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_mini_cart_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_mini_cart_blocks_tab' => __( 'Blocks', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_mini_cart_blocks_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Mini Cart Custom Info Blocks', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_mini_cart_custom_info_options',
	),
	array(
		'title'             => __( 'Total Blocks', 'woocommerce-jetpack' ),
		'id'                => 'wcj_mini_cart_custom_info_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_mini_cart_custom_info_options',
	),
);
$mini_cart_info_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );
for ( $i = 1; $i <= $mini_cart_info_num; $i++ ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i,
					'type'  => 'title',
					'id'    => 'wcj_mini_cart_custom_info_options_' . $i,
				),
				array(
					'title'   => __( 'Content', 'woocommerce-jetpack' ),
					'id'      => 'wcj_mini_cart_custom_info_content_' . $i,
					'default' => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
					'type'    => 'textarea',
					'css'     => 'width:100%;height:100px;',
				),
				array(
					'title'   => __( 'Position', 'woocommerce-jetpack' ),
					'id'      => 'wcj_mini_cart_custom_info_hook_' . $i,
					'default' => 'woocommerce_after_mini_cart',
					'type'    => 'select',
					'options' => array(
						'woocommerce_before_mini_cart' => __( 'Before mini cart', 'woocommerce-jetpack' ),
						'woocommerce_widget_shopping_cart_before_buttons' => __( 'Before buttons', 'woocommerce-jetpack' ),
						'woocommerce_after_mini_cart'  => __( 'After mini cart', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'   => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
					'id'      => 'wcj_mini_cart_custom_info_priority_' . $i,
					'default' => 10,
					'type'    => 'number',
					'desc'    => __( 'Change the Priority to sequence of your custom blocks, Greater value for high priority & Lower value for low priority.', 'woocommerce-jetpack' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_mini_cart_custom_info_options_' . $i,
				),
			)
		);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_mini_cart_blocks_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
