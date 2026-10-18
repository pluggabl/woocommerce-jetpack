<?php
/**
 * Booster for WooCommerce - Settings - Pre Orders
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Render Lite → Elite upgrade block.
if ( function_exists( 'wcj_render_upgrade_block' ) && wcj_has_upgrade_block( 'preorders' ) ) {
	wcj_render_upgrade_block( 'preorders' );
}

$user_roles                = wcj_get_user_roles_options();
$product_cats              = wcj_get_terms( 'product_cat' );
$products                  = wcj_get_products();
$wcj_preorders_compare_url = function ( $content ) {
	return wcj_build_commercial_url(
		'compare',
		array(
			'campaign' => 'locked_setting',
			'content'  => $content,
		)
	);
};

$settings = array(
	array(
		'id'      => 'wcj_preorders_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_preorders_general_tab'    => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_preorders_outofstock_tab' => __( 'Out of Stock Options', 'woocommerce-jetpack' ),
			'wcj_preorders_buttons_tab'    => __( 'Button Customization', 'woocommerce-jetpack' ),
			'wcj_preorders_shipping_tab'   => __( 'Shipping Options', 'woocommerce-jetpack' ),
			'wcj_preorders_pricing_tab'    => __( 'Pre-order Fee', 'woocommerce-jetpack' ),
			'wcj_preorders_email_tab'      => __( 'Email Notifications', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_preorders_general_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Prevent Mixed Cart', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Want to ensure pre-order items are purchased separately from regular stock for easier management? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock this feature.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_separate_cart__compare' ) ),
		'id'                => 'wcj_preorders_prevent_mixed_cart',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Release Date Input Date Format', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_preorder_release_date_format',
		'default'           => 'm/d/Y',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Choose the input format for release dates. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to customize release date formats.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_release_date_format__compare' ) ),
	),
	array(
		'title'             => __( 'Pre-order Access', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_access_type',
		'default'           => 'all',
		'type'              => 'select',
		'options'           => array(
			'all'        => __( 'All Users', 'woocommerce-jetpack' ),
			'registered' => __( 'Only Registered Users', 'woocommerce-jetpack' ),
			'roles'      => __( 'Specific User Roles', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Control who can place pre-orders (all users, logged-in users, or specific roles). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock role-based access.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_role_access__compare' ) ),
	),
	array(
		'title'             => __( 'Allowed User Roles', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_allowed_user_roles',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $user_roles,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Select specific user roles allowed to place pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock role-based restrictions.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_role_selection__compare' ) ),
	),
	array(
		'id'   => 'wcj_preorders_general_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_preorders_outofstock_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Pre-order Products Include', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_enable_products_include',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $products,
		'desc'              => wcj_replace_booster_url( __( 'Select up to 3 products to enable pre-orders. Want to enable pre-orders for unlimited products? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock this feature.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_unlimited_products__compare' ) ),
		'custom_attributes' => array(
			'data-max-selected' => 3,
		),
	),
	array(
		'title'             => __( 'Auto-enable Pre-orders', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Want to enable pre-orders automatically for all out-of-stock items? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock this feature.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_auto_enable__compare' ) ),
		'id'                => 'wcj_preorders_auto_enable_outofstock',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Auto-enable pre-orders only for products in these categories. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock category-based pre-orders.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_category_include__compare' ) ),
		'id'                => 'wcj_preorders_auto_enable_categories_include',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $product_cats,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Exclude categories from auto-enabled pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to manage category exclusions.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_category_exclude__compare' ) ),
		'id'                => 'wcj_preorders_auto_enable_categories_exclude',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $product_cats,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Products - Include', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Auto-enable pre-orders only for selected products. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock product-level control.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_product_include__compare' ) ),
		'id'                => 'wcj_preorders_auto_enable_products_include',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $products,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Products - Exclude', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Exclude specific products from pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock product exclusions.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_product_exclude__compare' ) ),
		'id'                => 'wcj_preorders_auto_enable_products_exclude',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $products,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Default Availability Days', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Set default number of days until release date. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> for customizable availability periods.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_default_days__compare' ) ),
		'id'                => 'wcj_preorders_default_availability_days',
		'default'           => '30',
		'type'              => 'number',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Default Price Type', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Set pricing type when auto-enabling pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock discount/increase options.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_pricing_type__compare' ) ),
		'id'                => 'wcj_preorders_default_price_type',
		'default'           => 'default',
		'type'              => 'select',
		'options'           => array(
			'default'  => __( 'Default Product Price', 'woocommerce-jetpack' ),
			'discount' => __( 'Discount on Default Price', 'woocommerce-jetpack' ),
			'increase' => __( 'Increase on Default Price', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Default Price Adjustment', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Set default discount or markup for pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock flexible pricing.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_pricing_value__compare' ) ),
		'id'                => 'wcj_preorders_default_price_adjustment',
		'default'           => '0',
		'type'              => 'number',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_preorders_outofstock_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_preorders_buttons_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Button Customization', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_preorders_button_options',
	),
	array(
		'title'             => __( 'Pre-order Button Text', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Set custom text for the pre-order button. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock button customization.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_button_text__compare' ) ),
		'id'                => 'wcj_preorders_button_text',
		'default'           => __( 'Pre-order Now', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Button Background Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_button_bg_color',
		'default'           => '#007cba',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Choose a background color for pre-order buttons. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock button styling.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_button_bg__compare' ) ),
	),
	array(
		'title'             => __( 'Button Text Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_button_text_color',
		'default'           => '#ffffff',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Choose a text color for the pre-order button. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock button text styling.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_button_text_color__compare' ) ),
	),
	array(
		'title'             => __( 'Button Hover Background Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_button_hover_bg_color',
		'default'           => '#0073aa',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Set background color on button hover. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> for full hover styling.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_button_hover_bg__compare' ) ),
	),
	array(
		'title'             => __( 'Button Hover Text Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_button_hover_text_color',
		'default'           => '#ffffff',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Set text color on button hover. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock hover text styling.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_button_hover_text__compare' ) ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_preorders_button_options',
	),
	array(
		'title' => __( 'Message Customization', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_preorders_message_options',
	),
	array(
		'title'             => __( 'Pre-order Message', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Message shown for pre-order products. Use %release_date% shortcode. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to customize messages.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_message_text__compare' ) ),
		'id'                => 'wcj_preorders_message',
		'default'           => __( 'This item is available for pre-order and will be released on %release_date%.', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Message Style', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_message_style',
		'default'           => 'custom',
		'type'              => 'select',
		'options'           => array(
			'custom'  => __( 'Custom', 'woocommerce-jetpack' ),
			'notice'  => __( 'Notice', 'woocommerce-jetpack' ),
			'success' => __( 'Success', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Choose style for pre-order messages (custom, notice, success). Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock style options.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_message_style__compare' ) ),
	),
	array(
		'title'             => __( 'Custom Message Text Color', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_message_text_color',
		'default'           => '#515151',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Choose custom text color for messages. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> for message styling.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_message_color__compare' ) ),
	),
	array(
		'id'   => 'wcj_preorders_buttons_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_preorders_shipping_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Free Shipping Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_preorders_free_shipping_options',
		'desc'  => __( 'Please Clear WooCommerce shipping caches (WooCommerce > Status > Tools > Clear transients). <br> Refresh the cart after enabling the settings.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Enable Free Shipping', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Enable free shipping for pre-order products. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock free shipping rules.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_free_shipping__compare' ) ),
		'id'                => 'wcj_preorders_free_shipping',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Free Shipping Label', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Text shown for free shipping. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to customize shipping labels.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_free_shipping_text__compare' ) ),
		'id'                => 'wcj_preorders_free_shipping_label',
		'default'           => __( 'Free Shipping (Pre-order)', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Force Free Shipping Only', 'woocommerce-jetpack' ),
		'desc'              => wcj_replace_booster_url( __( 'Remove other shipping methods when free shipping is active. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> for shipping control.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_shipping_control__compare' ) ),
		'id'                => 'wcj_preorders_free_shipping_only',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_preorders_shipping_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_preorders_pricing_tab',
		'type' => 'tab_start',
	),
	array(
		'title'             => __( 'Enable Pre-order Fee', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_global_fee_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Charge a fee for pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock fee management.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_fee_enable__compare' ) ),
	),
	array(
		'title'             => __( 'Fee Title', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_global_fee_title',
		'default'           => __( 'Pre-order Fee', 'woocommerce-jetpack' ),
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Fee Title shown in cart/checkout for pre-order fee. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> for customizable labels.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_fee_title__compare' ) ),
	),
	array(
		'title'             => __( 'Fee Amount', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_global_fee',
		'default'           => '',
		'type'              => 'number',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => wcj_replace_booster_url( __( 'Set global fee amount for pre-orders. Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock flexible fee amounts.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_fee_amount__compare' ) ),
	),
	array(
		'title'             => __( 'Include Categories', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_fee_include_cats',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $product_cats,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Exclude Categories', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_fee_exclude_cats',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $product_cats,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Include User Roles', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_fee_include_roles',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $user_roles,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Exclude User Roles', 'woocommerce-jetpack' ),
		'id'                => 'wcj_preorders_fee_exclude_roles',
		'default'           => array(),
		'type'              => 'multiselect',
		'options'           => $user_roles,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'id'   => 'wcj_preorders_pricing_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_preorders_email_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Email Notifications', 'woocommerce-jetpack' ),
		'id'      => 'wcj_preorders_email_notifications',
		'default' => array(),
		'type'    => 'multiselect',
		'options' => array(
			'admin_purchase'   => __( 'Admin: New Pre-order Purchase', 'woocommerce-jetpack' ),
			'customer_confirm' => __( 'Customer: Pre-order Confirmation', 'woocommerce-jetpack' ),
		),
		'desc'    => wcj_replace_booster_url( __( 'Keep customers and admins fully informed with dedicated pre-order confirmations, product release updates, and more advanced email options. <br>Upgrade to <a href="https://booster.io/buy-booster/" target="_blank">Booster</a> to unlock advanced email notifications.', 'woocommerce-jetpack' ), $wcj_preorders_compare_url( 'preorders_email_notifications__compare' ) ),
	),
	array(
		'id'   => 'wcj_preorders_email_tab',
		'type' => 'tab_end',
	),
);

return $settings;
