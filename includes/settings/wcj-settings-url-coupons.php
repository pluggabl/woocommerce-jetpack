<?php
/**
 * Booster for WooCommerce - Settings - URL Coupons
 *
 * @version 5.6.8
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'desc'  => sprintf(
			/* translators: %s: translators Added */
			__( 'Additionally you can hide standard coupon field on cart page in Booster\'s <a href="%s">Cart Customization</a> module.', 'woocommerce-jetpack' ),
			admin_url( wcj_admin_tab_url() . '&wcj-cat=cart_and_checkout&section=cart_customization' )
		),
		'type'  => 'title',
		'id'    => 'wcj_url_coupons_options',
	),
	array(
		'title'    => __( 'URL Coupons Key', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'URL key. If you change this, make sure it\'s unique and is not used anywhere on your site (e.g. by another plugin).', 'woocommerce-jetpack' ),
		'desc'     => sprintf(
			/* translators: %s: translators Added */
			__( 'Your users can apply shop\'s standard coupons, by visiting URL. E.g.: %s.', 'woocommerce-jetpack' ),
			'<code>' . site_url() . '/?' . wcj_get_option( 'wcj_url_coupons_key', 'wcj_apply_coupon' ) . '=couponcode </code>'
		),
		'id'       => 'wcj_url_coupons_key',
		'default'  => 'wcj_apply_coupon',
		'type'     => 'text',
	),
	array(
		'title'             => __( 'Redirect URL', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Possible values: No redirect; redirect to cart; redirect to checkout; redirect to custom local URL.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_url_coupons_redirect',
		'default'           => 'no',
		'type'              => 'select',
		'options'           => array(
			'no'       => __( 'No redirect', 'woocommerce-jetpack' ),
			'cart'     => __( 'Redirect to cart', 'woocommerce-jetpack' ),
			'checkout' => __( 'Redirect to checkout', 'woocommerce-jetpack' ),
			'custom'   => __( 'Redirect to custom local URL', 'woocommerce-jetpack' ),
		),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'     => __( 'Custom Local URL', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If redirect to custom local URL is selected, set URL here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_url_coupons_redirect_custom_url',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'min-width:300px;',
	),
	array(
		'title'             => __( '"Fixed product discount" Coupons', 'woocommerce-jetpack' ),
		'desc'              => __( 'Automatically add coupon\'s products to the cart', 'woocommerce-jetpack' ),
		'id'                => 'wcj_url_coupons_fixed_product_discount_add_products',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_url_coupons_options',
	),
);
return $settings;
