<?php
/**
 * Booster for WooCommerce - Mini plugin customizations
 *
 * @version 6.0.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/mini-plugin
 */

add_filter(
	'wcj_modules',
	function ( $module_categories ) {
		$module_categories = array_filter(
			$module_categories,
			function ( $item ) {
				return in_array( $item, array( 'emails_and_misc', 'pdf_invoicing', 'shipping_and_orders', 'payment_gateways', 'cart_and_checkout', 'products', 'labels', 'prices_and_currencies', 'labels', 'pdf_invoicing', 'dashboard' ), true );
			},
			ARRAY_FILTER_USE_KEY
		);
		$all_categories    = array( 'price_by_country', 'multicurrency', 'multicurrency_base_price', 'currency_per_product', 'currency', 'currency_external_products', 'bulk_price_converter', 'wholesale_price', 'product_open_pricing', 'offer_price', 'price_by_user_role', 'product_price_by_formula', 'global_discount', 'currency_exchange_rates', 'price_formats', 'price_labels', 'call_for_price', 'free_price', 'add_to_cart', 'more_button_labels', 'product_listings', 'tax_display', 'admin_products_list', 'products_per_page', 'product_tabs', 'product_custom_info', 'related_products', 'cross_sells', 'upsells', 'sorting', 'sku', 'stock', 'product_input_fields', 'product_add_to_cart', 'add_to_cart_button_visibility', 'purchase_data', 'product_bookings', 'crowdfunding', 'product_addons', 'product_images', 'sale_flash', 'product_by_country', 'product_by_user_role', 'product_custom_visibility', 'product_by_time', 'product_by_date', 'product_by_user', 'products_xml', 'product_bulk_meta_editor', 'product_msrp', 'product_extra_fees', 'cart', 'cart_customization', 'empty_cart', 'mini_cart', 'url_coupons', 'coupon_code_generator', 'coupon_by_user_role', 'checkout_core_fields', 'checkout_custom_fields', 'checkout_files_upload', 'checkout_custom_info', 'checkout_customization', 'checkout_fees', 'eu_vat_number', 'frequently_bought_together', 'one_page_checkout', 'payment_gateways', 'payment_gateways_icons', 'payment_gateways_pdf_notes', 'payment_gateways_fees', 'payment_gateways_per_category', 'payment_gateways_currency', 'payment_gateways_by_currency', 'payment_gateways_min_max', 'payment_gateways_by_country', 'payment_gateways_by_user_role', 'payment_gateways_by_shipping', 'shipping', 'shipping_options', 'shipping_icons', 'shipping_description', 'shipping_time', 'left_to_free_shipping', 'shipping_calculator', 'shipping_by_user_role', 'shipping_by_products', 'shipping_by_cities', 'shipping_by_time', 'shipping_by_order_amount', 'shipping_by_order_qty', 'address_formats', 'orders', 'admin_orders_list', 'order_min_amount', 'order_numbers', 'order_custom_statuses', 'order_quantities', 'max_products_per_user', 'pdf_invoicing', 'pdf_invoicing_numbering', 'pdf_invoicing_templates', 'pdf_invoicing_header', 'pdf_invoicing_footer', 'pdf_invoicing_styling', 'pdf_invoicing_page', 'pdf_invoicing_emails', 'pdf_invoicing_paid_stamp', 'pdf_invoicing_display', 'pdf_invoicing_advanced', 'pdf_invoicing_extra_columns', 'general', 'breadcrumbs', 'admin_bar', 'export', 'my_account', 'old_slugs', 'reports', 'admin_tools', 'debug_tools', 'emails', 'email_options', 'emails_verification', 'wpml', 'custom_css', 'custom_js', 'custom_php', 'track_users', 'modules_by_user_roles', 'template_editor', 'product_info' );
		$modules_all_cats  = $module_categories;
		$all_category_keys = $all_categories;

		// ----- Modules By User Roles Module Options Start -----
		if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
			$current_user = wp_get_current_user();

			$wcj_modules_by_user_roles_data['role']         = ( isset( $current_user->roles ) && is_array( $current_user->roles ) && ! empty( $current_user->roles ) ?
				reset( $current_user->roles ) : 'guest' );
			$wcj_modules_by_user_roles_data['role']         = ( '' !== $wcj_modules_by_user_roles_data['role'] ? $wcj_modules_by_user_roles_data['role'] : 'guest' );
			$wcj_modules_by_user_roles_data['modules_incl'] = wcj_get_option( 'wcj_modules_by_user_roles_incl_' . $wcj_modules_by_user_roles_data['role'], '' );
			$wcj_modules_by_user_roles_data['modules_excl'] = wcj_get_option( 'wcj_modules_by_user_roles_excl_' . $wcj_modules_by_user_roles_data['role'], '' );

			if ( empty( $wcj_modules_by_user_roles_data['modules_incl'] ) && empty( $wcj_modules_by_user_roles_data['modules_excl'] ) ) {
				$all_category_keys = $all_categories;
			} elseif ( ! empty( $wcj_modules_by_user_roles_data['modules_incl'] ) && empty( $wcj_modules_by_user_roles_data['modules_excl'] ) ) {

				$all_category_keys = $wcj_modules_by_user_roles_data['modules_incl'];

			} elseif ( empty( $wcj_modules_by_user_roles_data['modules_incl'] ) && ! empty( $wcj_modules_by_user_roles_data['modules_excl'] ) ) {
				$array = $all_categories;

				$value = $wcj_modules_by_user_roles_data['modules_excl'];
				$array = array_diff( $array, $value );

				$all_category_keys = $array;
			} elseif ( ! empty( $wcj_modules_by_user_roles_data['modules_incl'] ) && ! empty( $wcj_modules_by_user_roles_data['modules_excl'] ) ) {

				$all_category_keys = $wcj_modules_by_user_roles_data['modules_incl'];
			}
			if ( null !== wcj_get_option( 'wcj_modules_by_user_roles_incl_administrator', '' ) && 'administrator' === $wcj_modules_by_user_roles_data['role'] ) {
				array_push( $all_category_keys, 'modules_by_user_roles' );
			}
		}
		// ----- Modules By User Roles Module Options End -----

		if ( count( $all_category_keys ) <= 1 && '' === $all_category_keys[0] ) {
			foreach ( $module_categories as $key => $value ) {
				$all_category_keys = array_merge( $value['all_cat_ids'], $all_category_keys );
			}
		} else {
			foreach ( $modules_all_cats as $key => $modules_cat ) {
				foreach ( $modules_cat as $cat_keys => $value ) {
					if ( 'dashboard' === $key || 'all_cat_ids' !== $cat_keys ) {
						continue;
					}
					$module_categories[ $key ]['all_cat_ids'] = array();
					foreach ( $value as $module ) {

						if ( in_array( $module, $all_category_keys, true ) ) {
							array_push( $module_categories[ $key ]['all_cat_ids'], $module );
						}
					}
				}
			}
		}
		add_filter(
			'wcj_modules_loaded',
			function( $modules ) use ( $all_category_keys ) {
				$modules = array_filter(
					$modules,
					function ( $item ) use ( $all_category_keys ) {
						return in_array( $item, $all_category_keys, true );
					},
					ARRAY_FILTER_USE_KEY
				);
				return $modules;
			},
			10,
			2
		);
		return $module_categories;
	}
);
