<?php
/**
 * WooCommerce Modules Array
 *
 * The WooCommerce Modules Array.
 *
 * @version 2.5.4
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

return array(

	'dashboard' => array(
		'label'          => __( 'Dashboard', 'woocommerce-jetpack' ),
		'default_cat_id' => 'by_category',
		'all_cat_ids'    => array(
			'alphabetically',
			'by_category',
			'active',
			'manager',
		),
	),

	'prices_and_currencies' => array(
		'label'          => __( 'Prices & Currencies', 'woocommerce-jetpack' ),
		'default_cat_id' => 'price_by_country',
		'all_cat_ids'    => array(
			'price_by_country',
			'multicurrency',
			'multicurrency_base_price',
			'currency_per_product',
			'currency',
			'currency_external_products',
			'bulk_price_converter',
			'wholesale_price',
			'product_open_pricing',
			'price_by_user_role',
			'product_price_by_formula',
			'currency_exchange_rates',
			'price_formats',
		),
	),

	'labels' => array(
		'label'          => __( 'Button & Price Labels', 'woocommerce-jetpack' ),
		'default_cat_id' => 'price_labels',
		'all_cat_ids'    => array(
			'price_labels',
			'call_for_price',
			'add_to_cart',
			'more_button_labels',
		),
	),

	'products' => array(
		'label'          => __( 'Products', 'woocommerce-jetpack' ),
		'default_cat_id' => 'product_listings',
		'all_cat_ids'    => array(
			'product_listings',
			'product_tabs',
			'product_custom_info',
			'related_products',
			'sorting',
			'sku',
			'product_input_fields',
			'product_add_to_cart',
			'purchase_data',
			'product_bookings',
			'crowdfunding',
			'product_addons',
			'product_images',
			'product_by_country',
			'product_by_user',
		),
	),

	'cart_and_checkout' => array(
		'label'          => __( 'Cart & Checkout', 'woocommerce-jetpack' ),
		'default_cat_id' => 'cart',
		'all_cat_ids'    => array(
			'cart',
			'empty_cart',
			'mini_cart',
			'checkout_core_fields',
			'checkout_custom_fields',
			'checkout_files_upload',
			'checkout_custom_info',
		),
	),

	'payment_gateways' => array(
		'label'          => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'default_cat_id' => 'payment_gateways',
		'all_cat_ids'    => array(
			'payment_gateways',
			'payment_gateways_icons',
			'payment_gateways_fees',
			'payment_gateways_per_category',
			'payment_gateways_currency',
			'payment_gateways_min_max',
			'payment_gateways_by_country',
			'payment_gateways_by_user_role',
		),
	),

	'shipping_and_orders' => array(
		'label'          => __( 'Shipping & Orders', 'woocommerce-jetpack' ),
		'default_cat_id' => 'shipping',
		'all_cat_ids'    => array(
			'shipping',
			'shipping_calculator',
			'address_formats',
			'orders',
			'order_numbers',
			'order_custom_statuses',
		),
	),

	'pdf_invoicing' => array(
		'label'          => __( 'PDF Invoicing & Packing Slips', 'woocommerce-jetpack' ),
		'default_cat_id' => 'pdf_invoicing',
		'all_cat_ids'    => array(
			'pdf_invoicing',
			'pdf_invoicing_numbering',
			'pdf_invoicing_templates',
			'pdf_invoicing_header',
			'pdf_invoicing_footer',
			'pdf_invoicing_styling',
			'pdf_invoicing_page',
			'pdf_invoicing_emails',
			'pdf_invoicing_display',
		),
	),

	'emails_and_misc' => array(
		'label'          => __( 'Emails & Misc.', 'woocommerce-jetpack' ),
		'default_cat_id' => 'general',
		'all_cat_ids'    => array(
			'general',
			'export',
//			'shortcodes',
			'eu_vat_number',
			'old_slugs',
			'reports',
			'admin_tools',
			'emails',
			'wpml',
//			'pdf_invoices',
			'product_info',
		),
	),

);
