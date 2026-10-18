<?php
/**
 * Booster for WooCommerce - Quick Start Presets
 *
 * This file contains the central configuration and helper functions for Quick Start presets.
 * Quick Start presets allow modules to define pre-configured settings that users can apply
 * with a single click to get started quickly with common use cases.
 *
 * @version 7.8.0
 * @since   7.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get all Quick Start presets for all modules.
 *
 * This function returns the complete Quick Start presets configuration array.
 * The array is keyed by module ID and contains preset definitions for each module.
 *
 * Schema structure:
 * array(
 *     'module_id' => array(
 *         'module_id'   => 'cart_abandonment',                    // Must match the module's canonical ID
 *         'module_name' => 'Abandoned Cart',                      // Human-readable module name
 *         'headline'    => 'Send reminder emails...',             // Short description of module's main use
 *         'presets'     => array(
 *             'preset_id' => array(
 *                 'id'       => 'balanced',                       // Unique ID within this module
 *                 'label'    => 'Balanced (recommended)',         // Button label text
 *                 'tagline'  => 'Safe starting point...',         // Optional short description
 *                 'steps'    => array(                            // Optional array of step descriptions
 *                     'Turn on cart tracking',
 *                     'Send 1 reminder email after 1 hour',
 *                 ),
 *                 'settings' => array(                            // Map of setting key => value
 *                     'wcj_cart_abandonment_enabled' => 'yes',
 *                     'wcj_cart_abandonment_email_interval' => 60,
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 *
 * How to add a new preset:
 * 1. Identify the module's canonical ID (e.g., 'cart_abandonment', 'sales_notifications')
 * 2. Add an entry to the $presets array below, keyed by the module ID
 * 3. Define module-level metadata: module_id, module_name, headline
 * 4. Add one or more presets under the 'presets' key
 * 5. For each preset, specify: id, label, optional tagline, optional steps, and settings map
 * 6. Ensure all setting keys match the actual setting IDs used in the module's settings array
 * 7. Wrap all user-facing strings in translation functions using 'woocommerce-jetpack' text domain
 *
 * @version 1.0.0
 * @since   7.8.0
 * @return  array Complete Quick Start presets configuration
 */
function wcj_quick_start_get_all_presets() {
	// Base presets array with pilot configurations for three key modules.
	$presets = array(
		// Cart Abandonment - Send gentle reminder emails to recover abandoned carts.
		'cart_abandonment'     => array(
			'module_id'   => 'cart_abandonment',
			'module_name' => __( 'Cart Abandonment', 'woocommerce-jetpack' ),
			'headline'    => __( 'Send one gentle reminder email to recover abandoned carts.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Safe starting point for most stores - one reminder after 1 hour.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Enable the first email template', 'woocommerce-jetpack' ),
						__( 'Send reminder 1 hour after cart is abandoned', 'woocommerce-jetpack' ),
						__( 'Use a gentle, helpful message', 'woocommerce-jetpack' ),
						__( 'No discount coupon (keeps it simple)', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_ca_email_template_enabled_1'  => 'yes',
						'wcj_ca_email_trigger_time_1'      => '1',
						'wcj_ca_email_trigger_time_type_1' => 'hour',
						'wcj_ca_email_discount_type_1'     => 'no',
					),
				),
			),
		),

		// Sales Notifications - Show social proof without annoying customers.
		'sales_notifications'  => array(
			'module_id'   => 'sales_notifications',
			'module_name' => __( 'Sales Notifications', 'woocommerce-jetpack' ),
			'headline'    => __( 'Show social proof with recent purchase notifications.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Reasonable timing that builds trust without being intrusive.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Display notifications for 6 seconds', 'woocommerce-jetpack' ),
						__( 'Wait 30 seconds before showing next notification', 'woocommerce-jetpack' ),
						__( 'Show in bottom right corner', 'woocommerce-jetpack' ),
						__( 'Display real recent orders', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_sale_msg_duration' => '6',
						'wcj_sale_msg_next'     => '30',
						'wcj_sale_msg_position' => 'wcj_bottom_right',
					),
				),
			),
		),

		// Product Add-ons - Simple gift wrapping example.
		'product_addons'       => array(
			'module_id'   => 'product_addons',
			'module_name' => __( 'Product Add-ons', 'woocommerce-jetpack' ),
			'headline'    => __( 'Add simple upsells like gift wrapping to all products.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Simple gift wrapping option that works for most stores.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Enable global add-ons for all products', 'woocommerce-jetpack' ),
						__( 'Add a "Gift Wrapping" checkbox option', 'woocommerce-jetpack' ),
						__( 'Set a small additional fee', 'woocommerce-jetpack' ),
						__( 'Customers can opt-in at checkout', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_product_addons_all_products_enabled'   => 'yes',
						'wcj_product_addons_all_products_enabled_1' => 'yes',
						'wcj_product_addons_all_products_type_1'    => 'checkbox',
						'wcj_product_addons_all_products_title_1'   => __( 'Gift Wrapping', 'woocommerce-jetpack' ),
						'wcj_product_addons_all_products_label_1'   => __( 'Add gift wrapping', 'woocommerce-jetpack' ),
						'wcj_product_addons_all_products_price_1'   => '5',
					),
				),
			),
		),

		// PDF Invoicing - Auto-generate invoices on order completion.
		'pdf_invoicing'        => array(
			'module_id'   => 'pdf_invoicing',
			'module_name' => __( 'PDF Invoicing', 'woocommerce-jetpack' ),
			'headline'    => __( 'Auto-generate professional PDF invoices when orders are completed.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Generate invoices automatically when orders complete.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Create invoice when order status is "Completed"', 'woocommerce-jetpack' ),
						__( 'Skip invoice generation for free orders', 'woocommerce-jetpack' ),
						__( 'Invoice attached to customer email automatically', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_invoicing_invoice_create_on' => array( 'woocommerce_order_status_completed' ),
						'wcj_invoicing_invoice_skip_zero_total' => 'yes',
					),
				),
			),
		),

		// Order Numbers - Sequential numbering with professional format.
		'order_numbers'        => array(
			'module_id'   => 'order_numbers',
			'module_name' => __( 'Order Numbers', 'woocommerce-jetpack' ),
			'headline'    => __( 'Replace WooCommerce order IDs with professional sequential numbers.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Clean sequential numbers starting from 1001.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Enable sequential order numbering', 'woocommerce-jetpack' ),
						__( 'Start from order number 1001', 'woocommerce-jetpack' ),
						__( 'Enable order search by custom number', 'woocommerce-jetpack' ),
						__( 'Use MySQL transactions for reliability', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_order_number_sequential_enabled' => 'yes',
						'wcj_order_number_counter' => '1001',
						'wcj_order_number_search_by_custom_number_enabled' => 'yes',
						'wcj_order_number_order_tracking_enabled' => 'yes',
						'wcj_order_number_use_mysql_transaction_enabled' => 'yes',
					),
				),
			),
		),

		// Wishlist - Enable wishlist on product pages.
		'wishlist'             => array(
			'module_id'   => 'wishlist',
			'module_name' => __( 'Wishlist', 'woocommerce-jetpack' ),
			'headline'    => __( 'Let customers save products to purchase later.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Simple wishlist button on product pages.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Enable wishlist on single product pages', 'woocommerce-jetpack' ),
						__( 'Show as button with heart icon', 'woocommerce-jetpack' ),
						__( 'Position after add to cart button', 'woocommerce-jetpack' ),
						__( 'Include Font Awesome icons', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_wishlist_enabled_single' => 'yes',
						'wcj_wishlist_title_single'   => __( 'Add to wishlist', 'woocommerce-jetpack' ),
						'wcj_wishlist_style_single'   => 'button_icon',
						'wcj_wishlist_hook_single'    => 'woocommerce_after_add_to_cart_button',
						'wcj_wishlist_enabled_font_awesome' => 'yes',
					),
				),
			),
		),

		// Checkout Core Fields - Streamline checkout by hiding optional fields.
		'checkout_core_fields' => array(
			'module_id'   => 'checkout_core_fields',
			'module_name' => __( 'Checkout Core Fields', 'woocommerce-jetpack' ),
			'headline'    => __( 'Simplify checkout by controlling which fields are shown or required.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Streamlined checkout with essential fields only.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Keep essential address fields enabled', 'woocommerce-jetpack' ),
						__( 'Make phone number optional instead of required', 'woocommerce-jetpack' ),
						__( 'Hide company name field (optional for most stores)', 'woocommerce-jetpack' ),
						__( 'Hide address line 2 to simplify form', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_checkout_fields_billing_company_is_enabled'   => 'no',
						'wcj_checkout_fields_billing_address_2_is_enabled' => 'no',
						'wcj_checkout_fields_billing_phone_is_required'    => 'no',
						'wcj_checkout_fields_shipping_company_is_enabled'  => 'no',
						'wcj_checkout_fields_shipping_address_2_is_enabled' => 'no',
					),
				),
			),
		),

		// Related Products - Show more relevant product recommendations.
		'related_products'     => array(
			'module_id'   => 'related_products',
			'module_name' => __( 'Related Products', 'woocommerce-jetpack' ),
			'headline'    => __( 'Show relevant product recommendations to boost sales.', 'woocommerce-jetpack' ),
			'presets'     => array(
				'balanced' => array(
					'id'       => 'balanced',
					'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
					'tagline'  => __( 'Show 4 related products based on category.', 'woocommerce-jetpack' ),
					'steps'    => array(
						__( 'Display 4 related products', 'woocommerce-jetpack' ),
						__( 'Show in 4 columns for clean grid layout', 'woocommerce-jetpack' ),
						__( 'Relate products by category', 'woocommerce-jetpack' ),
						__( 'Randomize order for variety', 'woocommerce-jetpack' ),
					),
					'settings' => array(
						'wcj_product_info_related_products_num'                => '4',
						'wcj_product_info_related_products_columns'            => '4',
						'wcj_product_info_related_products_relate_by_category' => 'yes',
						'wcj_product_info_related_products_relate_by_tag'      => 'no',
						'wcj_product_info_related_products_orderby'            => 'rand',
						'wcj_product_info_related_products_hide'               => 'no',
					),
				),
			),
		),
	);

	/**
	 * Filter the Quick Start presets array.
	 *
	 * This filter allows third-party developers to add, modify, or remove Quick Start presets.
	 *
	 * Example: Add a custom preset for a module
	 * add_filter( 'wcj_quick_start_presets', function( $presets ) {
	 *     $presets['my_module']['presets']['custom'] = array(
	 *         'id'       => 'custom',
	 *         'label'    => 'My Custom Preset',
	 *         'settings' => array( 'setting_key' => 'value' ),
	 *     );
	 *     return $presets;
	 * } );
	 *
	 * @since 7.8.0
	 * @param array $presets The complete Quick Start presets configuration array
	 */
	return apply_filters( 'wcj_quick_start_presets', $presets );
}

/**
 * Get Quick Start presets for a specific module.
 *
 * Returns an array of presets defined for the given module ID.
 * If no presets are defined for the module, returns an empty array.
 *
 * @version 1.0.0
 * @since   7.6.0
 * @param   string $module_id The module's canonical ID (e.g., 'cart_abandonment').
 * @return  array Array of presets for the module, or empty array if none exist
 */
function wcj_quick_start_get_presets_for_module( $module_id ) {
	if ( empty( $module_id ) || ! is_string( $module_id ) ) {
		return array();
	}

	$all_presets = wcj_quick_start_get_all_presets();

	if ( ! isset( $all_presets[ $module_id ] ) ) {
		return array();
	}

	$module_config = $all_presets[ $module_id ];

	// Return the presets array if it exists, otherwise empty array.
	return isset( $module_config['presets'] ) && is_array( $module_config['presets'] )
		? $module_config['presets']
		: array();
}

/**
 * Get a specific preset for a module.
 *
 * Returns the preset configuration array for the given module ID and preset ID.
 * If the preset is not found, returns null.
 *
 * @version 1.0.0
 * @since   7.8.0
 * @param   string $module_id The module's canonical ID (e.g., 'cart_abandonment').
 * @param   string $preset_id The preset's ID within the module (e.g., 'balanced').
 * @return  array|null Preset configuration array, or null if not found
 */
function wcj_quick_start_get_preset( $module_id, $preset_id ) {
	if ( empty( $module_id ) || ! is_string( $module_id ) || empty( $preset_id ) || ! is_string( $preset_id ) ) {
		return null;
	}

	$module_presets = wcj_quick_start_get_presets_for_module( $module_id );

	if ( empty( $module_presets ) ) {
		return null;
	}

	return isset( $module_presets[ $preset_id ] ) ? $module_presets[ $preset_id ] : null;
}

/**
 * Get module-level metadata for Quick Start.
 *
 * Returns module-level information such as module_name and headline.
 * If the module has no Quick Start configuration, returns an empty array.
 *
 * @version 1.0.0
 * @since   7.8.0
 * @param   string $module_id The module's canonical ID (e.g., 'cart_abandonment').
 * @return  array Module metadata array with keys like 'module_name', 'headline', or empty array
 */
function wcj_quick_start_get_module_meta( $module_id ) {
	if ( empty( $module_id ) || ! is_string( $module_id ) ) {
		return array();
	}

	$all_presets = wcj_quick_start_get_all_presets();

	if ( ! isset( $all_presets[ $module_id ] ) ) {
		return array();
	}

	$module_config = $all_presets[ $module_id ];

	// Extract only the metadata fields, excluding the 'presets' array.
	$metadata = array();
	foreach ( $module_config as $key => $value ) {
		if ( 'presets' !== $key ) {
			$metadata[ $key ] = $value;
		}
	}

	return $metadata;
}
