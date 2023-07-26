<?php
/**
 * Booster for WooCommerce - Settings - Crowdfunding
 *
 * @version 7.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'      => 'crowdfunding_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'crowdfunding_options_tab' => __( 'General', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'crowdfunding_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => '',
		'type'  => 'title',
		'desc'  => __( 'When enabled, module will add Crowdfunding metabox to product edit.', 'woocommerce-jetpack' ) . '<br>' .
			'<ol>' .
			'<li>' .
			sprintf(
				/* translators: %s: translation added */
				__( 'To add crowdfunding info to the product, use <a href="%s" target="_blank">Booster\'s crowdfunding shortcodes</a>.', 'woocommerce-jetpack' ),
				'https://booster.io/category/shortcodes/products-crowdfunding/'
			) .
				'</li>' .
				'<li>' .
				sprintf(
					/* translators: %s: translation added */
					__( 'Shortcodes could be used for example in <a href="%s">Product Info module</a>.', 'woocommerce-jetpack' ),
					admin_url( wcj_admin_tab_url() . '&wcj-cat=products&section=product_custom_info' )
				) .
				'</li>' .
				'<li>' .
				sprintf(
					/* translators: %s: translation added */
					__( 'To change add to cart button labels use <a href="%s">Add to Cart Labels module</a>.', 'woocommerce-jetpack' ),
					admin_url( wcj_admin_tab_url() . '&wcj-cat=labels&section=add_to_cart' )
				) .
				'</li>' .
				'<li>' .
				sprintf(
					/* translators: %s: translation added */
					__( 'If you want to allow customers to choose dynamic price, Use <a href="%s">Product Open Pricing (Name Your Price) module</a>.', 'woocommerce-jetpack' ),
					admin_url( wcj_admin_tab_url() . '&wcj-cat=prices_and_currencies&section=product_open_pricing' )
				) .
				'</li>' .
			'</ol>',
		'id'    => 'wcj_add_to_cart_redirect_options',
	),
	array(
		'id'   => 'add_to_cart_per_category_tab',
		'type' => 'tab_end',
	),
);
return $settings;
