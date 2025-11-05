<?php
/**
 * Booster for WooCommerce - Onboarding Blueprints
 * Outcome-oriented presets that bundle existing goals
 *
 * @version 7.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'recover_lost_sales'   => array(
		'title'           => __( 'Recover Lost Sales', 'woocommerce-jetpack' ),
		'description'     => __( 'Set up automated cart abandonment recovery to win back customers.', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-email',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
		'goal_keys'       => array( 'recover_lost_sales_goal' ),
		'modules'         => array(),
		'next_steps'      => array(
			array(
				'label' => __( 'Set sender details', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=cart_and_checkout&section=cart_abandonment',
			),
			array(
				'label' => __( 'Create a 10% coupon', 'woocommerce-jetpack' ),
				'href'  => 'edit.php?post_type=shop_coupon',
			),
			array(
				'label' => __( 'Send a test email', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=cart_and_checkout&section=cart_abandonment',
			),
		),
		'primary_cta'     => array(
			'label' => __( 'Configure Cart Recovery', 'woocommerce-jetpack' ),
			'href'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=cart_and_checkout&section=cart_abandonment',
		),
		'pro_note'        => array(
			'label' => __( 'Sequences & coupon automation available in Elite — Compare →', 'woocommerce-jetpack' ),
			'href'  => 'https://booster.io/buy-booster/#compare',
		),
		'success_message' => __( 'Great! Cart abandonment recovery is now active.', 'woocommerce-jetpack' ),
	),

	'boost_aov'            => array(
		'title'           => __( 'Boost Average Order Value', 'woocommerce-jetpack' ),
		'description'     => __( 'Enable add-ons and related products to increase cart size.', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-chart-line',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,6 13.5,15.5 8.5,10.5 2,17"></polyline><polyline points="16,6 22,6 22,12"></polyline></svg>',
		'goal_keys'       => array( 'boost_conversions_free' ),
		'modules'         => array(),
		'next_steps'      => array(
			array(
				'label' => __( 'Add an add-on to your top product', 'woocommerce-jetpack' ),
				'href'  => 'edit.php?post_type=product',
			),
			array(
				'label' => __( 'Confirm related items are showing', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wcj-plugins&tab=jetpack&wcj-cat=products&section=related_products',
			),
		),
		'primary_cta'     => array(
			'label' => __( 'Add Your First Add-On', 'woocommerce-jetpack' ),
			'href'  => 'edit.php?post_type=product',
		),
		'pro_note'        => array(
			'label' => __( 'Conditional add-ons & fees in Elite — Compare →', 'woocommerce-jetpack' ),
			'href'  => 'https://booster.io/buy-booster/#compare',
		),
		'success_message' => __( 'Nice! Your store can now offer add-ons and show related products.', 'woocommerce-jetpack' ),
	),

	'sell_internationally' => array(
		'title'           => __( 'Sell Internationally', 'woocommerce-jetpack' ),
		'description'     => __( 'Set up your store to accept international orders.', 'woocommerce-jetpack' ),
		'icon'            => 'dashicons-admin-site',
		'svg_icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
		'goal_keys'       => array( 'store_essentials_quick', 'better_checkout_basics', 'go_global' ),
		'modules'         => array(),
		'next_steps'      => array(
			array(
				'label' => __( 'Add EU/UK shipping zones', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wc-settings&tab=shipping',
			),
			array(
				'label' => __( 'Enable taxes', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wc-settings&tab=tax',
			),
			array(
				'label' => __( 'Connect payments (Stripe/PayPal)', 'woocommerce-jetpack' ),
				'href'  => 'admin.php?page=wc-settings&tab=checkout',
			),
		),
		'primary_cta'     => array(
			'label' => __( 'Set Up Shipping', 'woocommerce-jetpack' ),
			'href'  => 'admin.php?page=wc-settings&tab=shipping',
		),
		'pro_note'        => array(
			'label' => __( 'Geo-price & currency controls in Elite — Compare →', 'woocommerce-jetpack' ),
			'href'  => 'https://booster.io/buy-booster/#compare',
		),
		'success_message' => __( 'Perfect! Your store is now ready for international customers.', 'woocommerce-jetpack' ),
	),
);
