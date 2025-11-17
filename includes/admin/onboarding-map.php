<?php
/**
 * Booster for WooCommerce - Onboarding Map
 * Data-driven configuration for onboarding goals
 *
 * @version 7.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'grow_sales'                           => array(
		'title'           => __( 'Grow sales now', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sales notifications', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-chart-line',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22,6 13.5,15.5 8.5,10.5 2,17"></polyline><polyline points="16,6 22,6 22,12"></polyline></svg>',
		'modules'         => array(
			array(
				'id'       => 'sales_notifications',
				'name'     => 'Sales Notifications',
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
	'work_smarter'                         => array(
		'title'           => __( 'Work smarter (backend)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sequential order numbers and admin enhancements', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10,9 9,9 8,9"></polyline></svg>',
		'modules'         => array(
			array(
				'id'       => 'order_numbers',
				'name'     => 'Order Numbers',
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
				'name'     => 'Admin Orders List',
				'settings' => array(
					'wcj_admin_orders_list_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'order_numbers_enabled',
		'next_step_text'  => __( 'Configure order numbers', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=shipping_and_orders&section=order_numbers&wcj-cat-nonce=',
	),
	'go_global'                            => array(
		'title'           => __( 'Go global (starter)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Add additional currency support', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-site',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'currency',
				'name'     => 'Currencies',
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
	'professional_invoices'                => array(
		'title'           => __( 'Professional invoices (starter)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Auto-generate PDF invoices (free tier: Invoice only)', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-media-document',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
		'modules'         => array(
			array(
				'id'       => 'pdf_invoicing',
				'name'     => 'PDF Invoicing',
				'settings' => array(
					'wcj_pdf_invoicing_enabled'       => 'yes',
					'wcj_invoicing_invoice_enabled'   => 'yes',
					'wcj_invoicing_invoice_create_on' => array( 'woocommerce_new_order' ),
					'wcj_invoicing_invoice_attach_to_email_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_invoicing_invoice_enabled',
		'next_step_text'  => __( 'Customize invoice template', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj-cat-nonce=',
	),
	'boost_conversions_free'               => array(
		'title'           => __( 'Boost conversions (free tools)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable product add-ons and related products', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-star-filled',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon></svg>',
		'modules'         => array(
			array(
				'id'       => 'product_addons',
				'name'     => 'Product Addons',
				'settings' => array(
					'wcj_product_addons_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'related_products',
				'name'     => 'Related Products',
				'settings' => array(
					'wcj_related_products_enabled'  => 'yes',
					'wcj_related_products_relate_by_category_enabled' => 'yes',
					'wcj_related_products_columns'  => 3,
					'wcj_related_products_per_page' => 3,
				),
			),
		),
		'first_win_check' => 'wcj_product_addons_enabled',
		'next_step_text'  => __( 'Add your first product add-on', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=products&section=product_addons&wcj-cat-nonce=',
	),
	'better_checkout_basics'               => array(
		'title'           => __( 'Better checkout (basics)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Customize checkout fields and button labels', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-cart',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'checkout_core_fields',
				'name'     => 'Checkout Core Fields',
				'settings' => array(
					'wcj_checkout_core_fields_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'more_button_labels',
				'name'     => 'More Button Labels',
				'settings' => array(
					'wcj_more_button_labels_enabled'       => 'yes',
					'wcj_checkout_place_order_button_text' => 'Pay now',
				),
			),
		),
		'first_win_check' => 'wcj_checkout_core_fields_enabled',
		'next_step_text'  => __( 'Customize checkout fields', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=cart_and_checkout&section=checkout_core_fields&wcj-cat-nonce=',
	),
	'store_essentials_quick'               => array(
		'title'           => __( 'Store essentials (quick setup)', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sequential order numbers and product tabs', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'order_numbers',
				'name'     => 'Order Numbers',
				'settings' => array(
					'wcj_order_numbers_enabled'           => 'yes',
					'wcj_order_number_sequential_enabled' => 'yes',
					'wcj_order_number_counter'            => 1,
				),
			),
			array(
				'id'       => 'product_tabs',
				'name'     => 'Product Tabs',
				'settings' => array(
					'wcj_product_tabs_enabled' => 'yes',
					'wcj_custom_product_tabs_global_enabled' => 'yes',
					'wcj_custom_product_tabs_title_global_1' => 'Custom Tab',
					'wcj_custom_product_tabs_content_global_1' => 'Product ID : [wcj_product_id]',
				),
			),
		),
		'first_win_check' => 'wcj_order_numbers_enabled',
		'next_step_text'  => __( 'Configure order numbers', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=shipping_and_orders&section=order_numbers&wcj-cat-nonce=',
	),
	'recover_lost_sales_goal'              => array(
		'title'           => __( 'Recover Lost Sale', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Enable sequential Cart Abandonment', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'cart_abandonment',
				'name'     => 'Cart Abandonment',
				'settings' => array(
					'wcj_cart_abandonment_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_cart_abandonment_enabled',
	),
	'b2b_store'                            => array(
		'title'           => __( 'B2B Store', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Coupon by user role, Gateways by User Role, Shipping Methods by Users, and Business tools', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-store',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'coupon_by_user_role',
				'name'     => 'Coupon by User Role',
				'settings' => array(
					'wcj_coupon_by_user_role_enabled' => 'yes',
					'wcj_coupon_by_user_role_invalid' => array( 'guest' ),

				),
			),
			array(
				'id'       => 'payment_gateways_by_user_role',
				'name'     => 'Gateways by User Role',
				'settings' => array(
					'wcj_payment_gateways_by_user_role_enabled' => 'yes',
					'wcj_gateways_user_roles_include_bacs' => array( 'administrator' ),
				),
			),
			array(
				'id'       => 'shipping_by_user_role',
				'name'     => 'Shipping Methods by Users',
				'settings' => array(
					'wcj_shipping_by_user_role_enabled' => 'yes',
					'wcj_shipping_user_roles_include_flat_rate' => array( 'administrator' ),
					'wcj_shipping_user_roles_include_local_pickup' => array( 'guest' ),
				),
			),
			array(
				'id'       => 'price_by_user_role',
				'name'     => 'Price based on User Role',
				'settings' => array(
					'wcj_price_by_user_role_enabled' => 'yes',
					'wcj_price_by_user_role_guest'   => 1.5,
				),
			),
			array(
				'id'       => 'product_by_user_role',
				'name'     => 'Product Visibility by User Role',
				'settings' => array(
					'wcj_product_by_user_role_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'my_account',
				'name'     => 'My Account',
				'settings' => array(
					'wcj_my_account_enabled' => 'yes',
					'wcj_my_account_registration_extra_fields_user_role_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'wholesale_price',
				'name'     => 'Wholesale Price',
				'settings' => array(
					'wcj_wholesale_price_enabled'         => 'yes',
					'wcj_wholesale_price_show_info_on_cart' => 'yes',
					'wcj_wholesale_price_level_min_qty_1' => 2,
					'wcj_wholesale_price_level_discount_percent_1' => 10,
				),
			),
			array(
				'id'       => 'tax_display',
				'name'     => 'Tax Display',
				'settings' => array(
					'wcj_tax_display_enabled'        => 'yes',
					'wcj_tax_display_toggle_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'offer_price',
				'name'     => 'Offer Your Price',
				'settings' => array(
					'wcj_offer_price_enabled'    => 'yes',
					'wcj_offer_price_price_step' => 2,
				),
			),
			array(
				'id'       => 'order_min_amount',
				'name'     => 'Order Minimum Amount',
				'settings' => array(
					'wcj_order_min_amount_enabled' => 'yes',
					'wcj_order_minimum_amount_cart_notice_enabled' => 'yes',
					'wcj_order_minimum_amount_stop_from_seeing_checkout' => 'yes',
					'wcj_order_minimum_amount_by_user_role_administrator' => 100,
					'wcj_order_minimum_amount_by_user_role_guest' => 150,
				),
			),
			array(
				'id'       => 'eu_vat_number',
				'name'     => 'EU VAT Number',
				'settings' => array(
					'wcj_eu_vat_number_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_coupon_by_user_role_enabled',
		'next_step_text'  => __( 'Configure Coupon by User Role', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&wcj-cat=cart_and_checkout&section=coupon_by_user_role&wcj-cat-nonce=feca9b5418',
	),
	'intl_Store'                           => array(
		'title'           => __( 'INTL Store', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Currency Exchange Rates, Prices and Currencies by Country, Multicurrency Product Base Price, and International currency tools', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-store',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'currency_exchange_rates',
				'name'     => 'Currency Exchange Rates',
				'settings' => array(
					'wcj_currency_exchange_rates_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'price_by_country',
				'name'     => 'Prices and Currencies by Country',
				'settings' => array(
					'wcj_price_by_country_enabled' => 'yes',
					'wcj_price_by_country_customer_country_detection_method' => 'by_user_selection',
				),
			),
			array(
				'id'       => 'multicurrency_base_price',
				'name'     => 'Multicurrency Product Base Price',
				'settings' => array(
					'wcj_multicurrency_base_price_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_currency_exchange_rates_enabled',
		'next_step_text'  => __( 'Configure Exchange Rates', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=currency_exchange_rates&wcj-cat-nonce=feca9b5418',
	),
	'merchant_getting_started'             => array(
		'title'           => __( 'Merchant getting started', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Product input fields, Checkout Custom Info, Product Variation Swatches, and other product tools', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-products',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'product_input_fields',
				'name'     => 'Product Input Fields',
				'settings' => array(
					'wcj_product_input_fields_enabled' => 'yes',
					'wcj_product_input_fields_global_enabled' => 'yes',
					'wcj_product_input_fields_enabled_global_1' => 'yes',
					'wcj_product_input_fields_title_global_1' => 'Additional Information',
					'wcj_product_input_fields_placeholder_global_1' => ' Add Additional Information',
					'wcj_product_input_fields_required_global_1' => 'yes',
					'wcj_product_input_fields_required_message_global_1' => 'Additional Information can\'t be empty.',
				),
			),
			array(
				'id'       => 'checkout_custom_info',
				'name'     => 'Checkout Custom Info',
				'settings' => array(
					'wcj_checkout_custom_info_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'product_variation_swatches',
				'name'     => 'Product Variation Swatches',
				'settings' => array(
					'wcj_product_variation_swatches_enabled'  => 'yes',
				),
			),
			array(
				'id'       => 'checkout_files_upload',
				'name'     => 'Checkout Files Upload',
				'settings' => array(
					'wcj_checkout_files_upload_enabled' => 'yes',
					'wcj_checkout_files_upload_add_to_thankyou_1' => 'yes',
				),
			),
			array(
				'id'       => 'checkout_fees',
				'name'     => 'Checkout Fees',
				'settings' => array(
					'wcj_checkout_fees_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_product_input_fields_enabled',
		'next_step_text'  => __( 'Configure Product Input Fields', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&wcj-cat=products&section=product_input_fields&wcj-cat-nonce=feca9b5418',
	),
	'merchant_aov_increase'                => array(
		'title'           => __( 'Merchant AOV increase', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Coupon Code Generator, URL Coupon, and Sale Flash', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-links',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'coupon_code_generator',
				'name'     => 'Coupon Code Generator',
				'settings' => array(
					'wcj_coupon_code_generator_enabled'  => 'yes',
					'wcj_coupons_code_generator_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'url_coupons',
				'name'     => 'URL Coupons',
				'settings' => array(
					'wcj_url_coupons_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'sale_flash',
				'name'     => 'Sale Flash',
				'settings' => array(
					'wcj_sale_flash_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_coupon_code_generator_enabled',
		'next_step_text'  => __( 'Configure Coupon Code', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&wcj-cat=cart_and_checkout&section=coupon_code_generator&wcj-cat-nonce=feca9b5418',
	),
	'merchant_run_their_store_efficiently' => array(
		'title'           => __( 'Merchant run their store efficiently', 'woocommerce-jetpack' ),
		'subtitle'        => __( 'Export, Admin product List, and Purchase data', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-tools',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
		'modules'         => array(
			array(
				'id'       => 'export',
				'name'     => 'Export',
				'settings' => array(
					'wcj_export_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'admin_products_list',
				'name'     => 'Admin Products List',
				'settings' => array(
					'wcj_admin_products_list_enabled' => 'yes',
					'wcj_products_admin_list_custom_columns_enabled' => 'yes',
					'wcj_products_admin_list_custom_columns_enabled_1' => 'yes',
					'wcj_products_admin_list_custom_columns_label_1' => 'Sale price',
					'wcj_products_admin_list_custom_columns_value_1' => '[wcj_product_sale_price]',
					'wcj_products_admin_list_columns_order_enabled' => 'yes',
				),
			),
			array(
				'id'       => 'purchase_data',
				'name'     => 'Cost of Goods',
				'settings' => array(
					'wcj_purchase_data_enabled' => 'yes',
					'wcj_purchase_price_affiliate_commission_enabled' => 'yes',
					'wcj_purchase_data_custom_columns_purchase_cost' => 'yes',
				),
			),
			array(
				'id'       => 'order_custom_statuses',
				'name'     => 'Order Custom Statuses',
				'settings' => array(
					'wcj_order_custom_statuses_enabled' => 'yes',
					'wcj_orders_custom_statuses_default_status' => 'processing',
				),
			),
			array(
				'id'       => 'products_xml',
				'name'     => 'Products XML Feeds',
				'settings' => array(
					'wcj_products_xml_enabled'     => 'yes',
					'wcj_products_xml_file_path_1' => 'Click on URL to view xml file.',
					'wcj_products_xml_orderby_1'   => 'ID',
					'wcj_products_xml_order_1'     => 'ASC',

				),
			),
			array(
				'id'       => 'product_bulk_meta_editor',
				'name'     => 'Product Bulk Meta Editor',
				'settings' => array(
					'wcj_product_bulk_meta_editor_enabled' => 'yes',
					'wcj_product_bulk_meta_editor_additional_columns' => array( 'product_id', 'product_status' ),
				),
			),
			array(
				'id'       => 'admin_tools',
				'name'     => 'Admin Tools',
				'settings' => array(
					'wcj_admin_tools_enabled' => 'yes',
					'wcj_admin_tools_show_order_meta_enabled' => 'yes',
					'wcj_admin_tools_show_product_meta_enabled' => 'yes',
				),
			),
		),
		'first_win_check' => 'wcj_export_enabled',
		'next_step_text'  => __( 'Configure Export Tool', 'woocommerce-jetpack' ),
		'next_step_link'  => 'admin.php?page=wcj-plugins&wcj-cat=emails_and_misc&section=export&wcj-cat-nonce=feca9b5418',
	),
);
