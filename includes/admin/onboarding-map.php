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
	'professional_invoices' => array(
		'title'           => __( 'Professional invoices (starter)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Auto-generate PDF invoices (free tier: Invoice only)', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-media-document',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
		'modules'         => array(
			array(
				'id'       => 'pdf_invoicing',
				'settings' => array(
					'wcj_pdf_invoicing_enabled'           => 'yes',
					'wcj_invoicing_invoice_enabled'       => 'yes',
					'wcj_invoicing_invoice_attach_to_email_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_invoicing_invoice_enabled',
		'next_step_text'  => __( 'Customize invoice template', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj-cat-nonce=',
	),
	'boost_conversions_free' => array(
		'title'           => __( 'Boost conversions (free tools)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable product add-ons and related products', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-star-filled',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon></svg>',
		'modules'         => array(
			array(
				'id'       => 'product_addons',
				'settings' => array(
					'wcj_product_addons_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'related_products',
				'settings' => array(
					'wcj_related_products_enabled' => 'yes',
					'wcj_related_products_relate_by_category_enabled' => 'yes',
					'wcj_related_products_columns' => 3,
					'wcj_related_products_per_page' => 3,
				),
			),
		),
		'first_win_check' => 'wcj_product_addons_enabled',
		'next_step_text'  => __( 'Add your first product add-on', 'woocommerce-jetpack' ),
		'next_step_link'  => 'edit.php?post_type=product',
	),
	'better_checkout_basics' => array(
		'title'           => __( 'Better checkout (basics)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Customize checkout fields and button labels', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-cart',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'checkout_core_fields',
				'settings' => array(
					'wcj_checkout_core_fields_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'more_button_labels',
				'settings' => array(
					'wcj_more_button_labels_enabled' => 'yes',
					'wcj_checkout_place_order_button_text' => 'Pay now',
				),
			),
		),
		'first_win_check' => 'wcj_checkout_core_fields_enabled',
		'next_step_text'  => __( 'Customize checkout fields', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=cart_and_checkout&section=checkout_core_fields&wcj-cat-nonce=',
	),
	'store_essentials_quick' => array(
		'title'           => __( 'Store essentials (quick setup)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sequential order numbers and product tabs', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'order_numbers',
				'settings' => array(
					'wcj_order_numbers_enabled'           => 'yes',
					'wcj_order_number_sequential_enabled' => 'yes',
					'wcj_order_number_counter'            => 1,
				),
			),
			array(
				'id'       => 'product_tabs',
				'settings' => array(
					'wcj_product_tabs_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_order_numbers_enabled',
		'next_step_text'  => __( 'Configure order numbers', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=shipping_and_orders&section=order_numbers&wcj-cat-nonce=',
	),
);
