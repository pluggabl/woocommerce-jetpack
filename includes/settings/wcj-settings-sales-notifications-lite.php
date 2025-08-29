<?php
/**
 * Booster for WooCommerce - Settings - Sales Notifications Lite
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elite_active = class_exists( 'WCJ_Elite' ) || defined( 'WCJ_ELITE_VERSION' );
$elite_sn_on  = function_exists( 'wcj_is_module_enabled' ) ? wcj_is_module_enabled( 'sales_notifications' ) : false;

$settings = array();

if ( $elite_active && $elite_sn_on ) {
	$settings[] = array(
		'title' => __( 'Elite Override Notice', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => '<div class="notice notice-info"><p>' . __( 'Sales Notifications are managed by Booster Elite. Lite output is disabled.', 'woocommerce-jetpack' ) . '</p></div>',
		'id'    => 'wcj_sales_notifications_lite_elite_notice',
	);
	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'wcj_sales_notifications_lite_elite_notice',
	);
} else {
	$settings = array_merge( $settings, array(
		array(
			'title' => __( 'Sales Notifications (Lite)', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Show recent real purchases to create social proof. This Lite version displays simple notifications with fixed timing and template.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_options',
		),
		array(
			'title'   => __( 'Enable Sales Notifications', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_sales_notifications_lite_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_options',
		),
		array(
			'title' => __( 'Notification Types', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_sales_notifications_lite_types',
		),
		array(
			'title'             => __( 'Recent Order', 'woocommerce-jetpack' ),
			'desc'              => __( 'Show recent completed orders (required in Lite)', 'woocommerce-jetpack' ),
			'id'                => 'wcj_sales_notifications_lite_recent_orders',
			'default'           => 'yes',
			'type'              => 'checkbox',
			'custom_attributes' => array( 'checked' => 'checked', 'disabled' => 'disabled' ),
		),
		array(
			'title'             => __( 'Viewing Now', 'woocommerce-jetpack' ),
			'desc'              => apply_filters( 'booster_message', __( 'Show real concurrent viewers per product (honest mode only)', 'woocommerce-jetpack' ), 'desc' ),
			'id'                => 'wcj_sales_notifications_lite_viewing_now',
			'default'           => 'no',
			'type'              => 'checkbox',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc_tip'          => __( 'This feature uses real presence data only. Available in full version with Booster Elite.', 'woocommerce-jetpack' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_types',
		),
		array(
			'title' => __( 'Theme', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_sales_notifications_lite_theme_options',
		),
		array(
			'title'   => __( 'Theme', 'woocommerce-jetpack' ),
			'id'      => 'wcj_sales_notifications_lite_theme',
			'type'    => 'select',
			'default' => 'light',
			'options' => array(
				'light'    => __( 'Light', 'woocommerce-jetpack' ),
				'dark'     => __( 'Dark', 'woocommerce-jetpack' ),
				'colorful' => __( 'Colorful', 'woocommerce-jetpack' ),
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_theme_options',
		),
		array(
			'title' => __( 'Test Mode', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_sales_notifications_lite_test_options',
		),
		array(
			'title'   => __( 'Test Mode', 'woocommerce-jetpack' ),
			'desc'    => __( 'Show notifications to administrators for testing', 'woocommerce-jetpack' ),
			'id'      => 'wcj_sales_notifications_lite_test_mode',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_test_options',
		),
		array(
			'title' => __( 'Fixed Settings (Lite)', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'These settings are fixed in the Lite version. Upgrade to Booster Elite for full customization.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_fixed',
		),
		array(
			'title' => __( 'Look-back Window', 'woocommerce-jetpack' ),
			'desc'  => __( '72 hours (fixed)', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_lookback_display',
			'type'  => 'text',
			'custom_attributes' => array( 'readonly' => 'readonly', 'value' => '72 hours' ),
		),
		array(
			'title' => __( 'Order Statuses', 'woocommerce-jetpack' ),
			'desc'  => __( 'Processing + Completed (fixed)', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_statuses_display',
			'type'  => 'text',
			'custom_attributes' => array( 'readonly' => 'readonly', 'value' => 'Processing + Completed' ),
		),
		array(
			'title' => __( 'Position', 'woocommerce-jetpack' ),
			'desc'  => __( 'Bottom-left (fixed)', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_position_display',
			'type'  => 'text',
			'custom_attributes' => array( 'readonly' => 'readonly', 'value' => 'Bottom-left' ),
		),
		array(
			'title' => __( 'Timing', 'woocommerce-jetpack' ),
			'desc'  => __( '5s delay / 5s display / 12s gap / 5 max (fixed)', 'woocommerce-jetpack' ),
			'id'    => 'wcj_sales_notifications_lite_timing_display',
			'type'  => 'text',
			'custom_attributes' => array( 'readonly' => 'readonly', 'value' => '5s delay / 5s display / 12s gap / 5 max' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_fixed',
		),
		array(
			'title' => __( 'Preview', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_sales_notifications_lite_preview',
		),
		array(
			'title' => __( 'Preview Example', 'woocommerce-jetpack' ),
			'desc'  => '<div class="wcj-sn-lite-preview" style="background: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 4px; margin: 10px 0;"><strong>Preview:</strong> J. from New York, United States bought Sample Product — 15 minutes ago</div>',
			'id'    => 'wcj_sales_notifications_lite_preview_display',
			'type'  => 'text',
			'custom_attributes' => array( 'style' => 'display: none;' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_preview',
		),
		array(
			'title' => __( 'Upgrade to Booster Elite', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => '<div class="wcj-upsell-panel" style="background: #f0f8ff; border: 1px solid #0073aa; padding: 15px; border-radius: 4px; margin: 15px 0;">' .
				'<h3 style="margin-top: 0;">' . __( 'Unlock Advanced Sales Notifications', 'woocommerce-jetpack' ) . '</h3>' .
				'<p>' . __( 'Upgrade to Booster Elite to unlock:', 'woocommerce-jetpack' ) . '</p>' .
				'<ul style="list-style: disc; margin-left: 20px;">' .
				'<li>' . __( 'Multiple positions (top/bottom, left/right)', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Unlimited queue & randomization', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Per-device/page rules', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Include/exclude products/categories', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Full text templating/variables', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Color & typography controls', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Product thumbnails/avatars', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Animations & sounds', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Schedule windows', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Seed/demo mode', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Analytics/UTM tracking', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'JS hooks/template overrides', 'woocommerce-jetpack' ) . '</li>' .
				'<li>' . __( 'Presence ("Viewing now") at scale', 'woocommerce-jetpack' ) . '</li>' .
				'</ul>' .
				'<p><a href="https://booster.io/buy-booster/?utm_source=plugin&utm_medium=upsell&utm_campaign=sn_lite&utm_content=settings_panel" target="_blank" class="button-primary">' . __( 'Upgrade to Booster Elite', 'woocommerce-jetpack' ) . '</a></p>' .
				'</div>',
			'id'    => 'wcj_sales_notifications_lite_upsell',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_sales_notifications_lite_upsell',
		),
	) );
}

return $settings;
