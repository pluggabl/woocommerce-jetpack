<?php
/**
 * Booster for WooCommerce - Settings - Pre Orders Lite
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$enabled_products = array();
$args             = array(
	'post_type'      => 'product',
	'meta_query'     => array(
		array(
			'key'   => '_wcj_product_preorder_enabled',
			'value' => 'yes',
		),
	),
	'posts_per_page' => -1,
);
$products = get_posts( $args );
foreach ( $products as $product ) {
	$enabled_products[] = '<a href="' . get_edit_post_link( $product->ID ) . '">' . get_the_title( $product->ID ) . '</a>';
}

$current_count = count( $enabled_products );
$max_limit     = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_preorders_lite_limit', 1 ) );

$settings = array(
	array(
		'title' => __( 'Pre-Orders (Lite) Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_preorders_lite_options',
	),
	array(
		'title'             => __( 'Product Limit', 'woocommerce-jetpack' ),
		'desc'              => sprintf(
			__( 'Currently using %d of %d products: %s', 'woocommerce-jetpack' ),
			$current_count,
			$max_limit,
			$enabled_products ? implode( ', ', $enabled_products ) : __( 'None', 'woocommerce-jetpack' )
		),
		'id'                => 'wcj_preorders_lite_limit',
		'default'           => 1,
		'type'              => 'number',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip'          => __( 'Maximum number of products that can have pre-orders enabled in the free version.', 'woocommerce-jetpack' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_preorders_lite_options',
	),
	array(
		'title' => __( 'Upgrade to Booster Elite', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_preorders_lite_upsell',
		'desc'  => __( 'Get advanced pre-order features with Booster Elite:', 'woocommerce-jetpack' ),
	),
);

$elite_features = array(
	__( 'Unlimited products with pre-orders enabled', 'woocommerce-jetpack' ),
	__( 'Prevent mixed cart (pre-order + regular products)', 'woocommerce-jetpack' ),
	__( 'Access control (all users / registered only / specific roles)', 'woocommerce-jetpack' ),
	__( 'Auto-enable for out-of-stock products with category/product rules', 'woocommerce-jetpack' ),
	__( 'Custom pre-order button text & colors (including hover)', 'woocommerce-jetpack' ),
	__( 'Custom message styling and content', 'woocommerce-jetpack' ),
	__( 'Pre-order pricing (default/discount/increase with percentage)', 'woocommerce-jetpack' ),
	__( 'Pre-order fees with category/role restrictions', 'woocommerce-jetpack' ),
	__( 'Free shipping options for pre-orders', 'woocommerce-jetpack' ),
	__( 'Email notifications (admin & customer)', 'woocommerce-jetpack' ),
	__( 'Maximum quantity limits per product', 'woocommerce-jetpack' ),
	__( 'Variable product controls', 'woocommerce-jetpack' ),
	__( 'Automated release date handling', 'woocommerce-jetpack' ),
);

foreach ( $elite_features as $feature ) {
	$settings[] = array(
		'title' => '✓ ' . $feature,
		'type'  => 'title',
		'id'    => 'wcj_preorders_lite_feature_' . md5( $feature ),
	);
}

$settings[] = array(
	'type' => 'sectionend',
	'id'   => 'wcj_preorders_lite_upsell',
);

$settings[] = array(
	'title' => '',
	'type'  => 'title',
	'id'    => 'wcj_preorders_lite_upgrade_button',
	'desc'  => '<a href="https://booster.io/buy-booster/" target="_blank" class="button-primary">' .
			   __( 'Upgrade to Booster Elite', 'woocommerce-jetpack' ) . '</a>',
);

$settings[] = array(
	'type' => 'sectionend',
	'id'   => 'wcj_preorders_lite_upgrade_button',
);

return $settings;
