<?php
/**
 * Storewide basic Returns settings. Disabled by default in every edition.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'title' => __( 'Return Requests', 'woocommerce-jetpack' ),
		'desc'  => __( 'Logged-in owners of completed orders can request returns. Approval never refunds or restocks. Guest access is separate and disabled by default.', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_returns_options',
	),
	array(
		'title'             => __( 'Return window', 'woocommerce-jetpack' ),
		'desc'              => __( 'Elapsed24-hour days after order completion. Invalid values block new intake.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_returns_window_days',
		'default'           => '30',
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => '1',
			'max'  => '365',
			'step' => '1',
		),
	),
	array(
		'title'   => __( 'Notifications', 'woocommerce-jetpack' ),
		'desc'    => __( 'Opt in to customer/store notification intents. Dispatch is manual from the Returns queue. Unknown delivery is never resent.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_returns_notifications',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Guest verification', 'woocommerce-jetpack' ),
		'desc'    => __( 'Opt in to mandatory short-lived email verification for guest orders. Requires HTTPS, strong configured AUTH_KEY/AUTH_SALT, Action Scheduler and separately initialized private auth storage. This consent is independent of Return-status notifications.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_returns_guest',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Guest verification Page ID', 'woocommerce-jetpack' ),
		'desc'    => __( 'Existing published, password-free same-origin HTTPS Page containing [wcj_returns_guest]. No page is created automatically. Confirm this page is never served from a public cache.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_returns_guest_page',
		'default' => '',
		'type'    => 'text',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_returns_options',
	),
);
