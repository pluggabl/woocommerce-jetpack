<?php
/**
 * Booster for WooCommerce - Modules Array
 *
 * @version 5.4.0
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) split "Shipping & Orders", "Cart & Checkout", "Products", "Prices & Currencies" etc.
 */

return apply_filters( 'wcj_modules', array(

	'dashboard' => array(
		'label'          => __( 'Dashboard', 'woocommerce-jetpack' ),
		'desc'           => __( 'This dashboard lets you enable/disable any Booster\'s module. Each checkbox comes with short module\'s description. Please visit <a href="https://booster.io" target="_blank">https://booster.io</a> for detailed info on each feature.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'alphabetically',
			'by_category',
			'active',
			'manager',
		),
	),

	'prices_and_currencies' => array(
		'label'          => __( 'Prices & Currencies', 'woocommerce-jetpack' ),
		'desc'           => __( 'Multicurrency, Price Converter, Wholesale Pricing, Name You Price, Price based on User Role and more.', 'woocommerce-jetpack' ),
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
			'offer_price',
			'price_by_user_role',
			'product_price_by_formula',
			'global_discount',
			'currency_exchange_rates',
			'price_formats',
		),
	),
	'labels' => array(
		'label'          => __( 'Button & Price Labels', 'woocommerce-jetpack' ),
		'desc'           => __( 'Add to Cart Labels, Call for Price, Custom Price Labels and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'price_labels',
			'call_for_price',
			'free_price',
			'add_to_cart',
			'more_button_labels',
		),
	),

	'products' => array(
		'label'          => __( 'Products', 'woocommerce-jetpack' ),
		'desc'           => __( 'Bookings, Crowdfunding Products, Product Addons and Input Fields, Product Listings, Product Tabs and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'product_listings',
			'tax_display',
			'admin_products_list',
			'products_per_page',
			'product_tabs',
			'product_custom_info',
			'related_products',
			'cross_sells',
			'upsells',
			'sorting',
			'sku',
			'stock',
			'product_input_fields',
			'product_add_to_cart',
			'add_to_cart_button_visibility',
			'purchase_data',
			'product_bookings',
			'crowdfunding',
			'product_addons',
			'product_images',
			'sale_flash',
			'product_by_country',
			'product_by_user_role',
			'product_custom_visibility',
			'product_by_time',
			'product_by_date',
			'product_by_user',
			'products_xml',
			'product_bulk_meta_editor',
			'product_msrp'
		),
	),

	'cart_and_checkout' => array(
		'label'          => __( 'Cart & Checkout', 'woocommerce-jetpack' ),
		'desc'           => __( 'Cart and Checkout Customization, Empty Cart Button, Mini Cart, Coupons and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'cart',
			'cart_customization',
			'empty_cart',
			'mini_cart',
			'url_coupons',
			'coupon_code_generator',
			'coupon_by_user_role',
			'checkout_core_fields',
			'checkout_custom_fields',
			'checkout_files_upload',
			'checkout_custom_info',
			'checkout_customization',
			'checkout_fees',
			'eu_vat_number',
		),
	),

	'payment_gateways' => array(
		'label'          => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'desc'           => __( 'Custom Payment Gateways, Gateways Currency, Gateways Fees and Discounts and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'payment_gateways',
			'payment_gateways_icons',
			'payment_gateways_fees',
			'payment_gateways_per_category',
			'payment_gateways_currency',
			'payment_gateways_by_currency',
			'payment_gateways_min_max',
			'payment_gateways_by_country',
			'payment_gateways_by_user_role',
			'payment_gateways_by_shipping',
		),
	),

	'shipping_and_orders' => array(
		'label'          => __( 'Shipping & Orders', 'woocommerce-jetpack' ),
		'desc'           => __( 'Order Custom Statuses, Order Minimum Amount, Order Numbers, Custom Shipping Methods and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'shipping',
			'shipping_options',
			'shipping_icons',
			'shipping_description',
			'shipping_time',
			'left_to_free_shipping',
			'shipping_calculator',
			'shipping_by_user_role',
			'shipping_by_products',
			'shipping_by_cities',
			'shipping_by_time',
			'shipping_by_order_amount',
			'shipping_by_order_qty',
			'address_formats',
			'orders',
			'admin_orders_list',
			'order_min_amount',
			'order_numbers',
			'order_custom_statuses',
			'order_quantities',
			'max_products_per_user',
		),
	),

	'pdf_invoicing' => array(
		'label'          => __( 'PDF Invoicing & Packing Slips', 'woocommerce-jetpack' ),
		'desc'           => __( 'PDF Documents', 'woocommerce-jetpack' ),
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
			'pdf_invoicing_advanced',
		),
	),

	'emails_and_misc' => array(
		'label'          => __( 'Emails & Misc.', 'woocommerce-jetpack' ),
		'desc'           => __( 'Emails, Reports, Export, Admin Tools, General Options and more.', 'woocommerce-jetpack' ),
		'all_cat_ids'    => array(
			'general',
			'breadcrumbs',
			'admin_bar',
			'export',
			'my_account',
			'old_slugs',
			'reports',
			'admin_tools',
			'debug_tools',
			'emails',
			'email_options',
			'emails_verification',
			'wpml',
			'custom_css',
			'custom_js',
			'custom_php',
			'track_users',
			'modules_by_user_roles',
			'template_editor',
			'product_info', // deprecated
		),
	),

) );