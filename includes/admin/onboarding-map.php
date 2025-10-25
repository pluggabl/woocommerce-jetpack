<?php
/**
 * Booster for WooCommerce - Onboarding Map
 * Data-driven configuration for onboarding goals
 *
 * @version 7.3.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'grow_sales'      => array(
		'title'           => __( 'Grow sales now', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sales notifications', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-chart-line',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22,6 13.5,15.5 8.5,10.5 2,17"></polyline><polyline points="16,6 22,6 22,12"></polyline></svg>',
		'modules'         => array(
			array(
				'id'       => 'sales_notifications',
				'settings' => array(
					'wcj_sales_notifications_enabled' => 'yes',
					'wcj_sale_msg_duration'           => 4,
					'wcj_sale_msg_next'               => 8,
					'wcj_sale_msg_styling'            => array(
						'animation' => 'wcj_fadein',
						'width'     => '35%',
						'bgcolor'   => '#ffffff',
						'color'     => '#000000',
					),
				),
			),
		),
		'first_win_check' => 'sales_notifications_enabled',
	),
	'work_smarter'    => array(
		'title'           => __( 'Work smarter (backend)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sequential order numbers and admin enhancements', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10,9 9,9 8,9"></polyline></svg>',
		'modules'         => array(
			array(
				'id'       => 'order_numbers',
				'settings' => array(
					'wcj_order_numbers_enabled'           => 'yes',
					'wcj_order_number_sequential_enabled' => 'yes',
					'wcj_order_number_counter'            => 1,
					'wcj_order_number_order_tracking_enabled' => 'yes',
					'wcj_order_number_search_by_custom_number_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'admin_orders_list',
				'settings' => array(
					'wcj_admin_orders_list_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'order_numbers_enabled',
		'next_step_text'  => __( 'Configure order numbers', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=shipping_and_orders&section=order_numbers&wcj-cat-nonce=',
	),
	'go_global'       => array(
		'title'           => __( 'Go global (starter)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Add additional currency support', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-site',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'currency',
				'settings' => array(
					'wcj_currency_enabled' => 'yes',
					'add_one_extra'        => true,
				),
			),
		),
		'first_win_check' => 'extra_currency_added',
		'next_step_text'  => __( 'Configure currencies', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=prices_and_currencies&section=currency&wcj-cat-nonce=',
	),
);
