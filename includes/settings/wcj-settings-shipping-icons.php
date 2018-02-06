<?php
/**
 * Booster for WooCommerce - Settings - Shipping Icons
 *
 * @version 3.4.0
 * @since   3.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section will allow you to add icons for shipping method. Icons will be visible on cart and checkout pages.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_options',
	),
	array(
		'title'    => __( 'Icon Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_position',
		'default'  => 'before',
		'type'     => 'select',
		'options'  => array(
			'before' => __( 'Before label', 'woocommerce-jetpack' ),
			'after'  => __( 'After label', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Icon Visibility', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_visibility',
		'default'  => 'both',
		'type'     => 'select',
		'options'  => array(
			'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
			'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
			'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Icon Style', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can also style icons with CSS class "wcj_shipping_icon", or id "wcj_shipping_icon_method_id"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_style',
		'default'  => 'display:inline;',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_icons_options',
	),
	array(
		'title'    => __( 'Shipping Methods Icons', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_icons_methods_options',
	),
);
foreach ( WC()->shipping->get_shipping_methods() as $method ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $method->method_title,
			'desc_tip' => __( 'Image URL', 'woocommerce-jetpack' ),
			'id'       => 'wcj_shipping_icon_' . $method->id,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:100%;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_icons_methods_options',
	),
) );
return $settings;
