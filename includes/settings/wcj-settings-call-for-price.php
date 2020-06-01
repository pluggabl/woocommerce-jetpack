<?php
/**
 * Booster for WooCommerce - Settings - Call for Price
 *
 * @version 3.2.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Call for Price Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Leave price empty when adding or editing products. Then set the options here.', 'woocommerce-jetpack' ) .
			' ' . __( 'You can use shortcodes in options.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_call_for_price_options',
	),
	array(
		'title'    => __( 'Label to Show on Single', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text',
		'default'  => '<strong>Call for price</strong>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Archives', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_archive',
		'default'  => '<strong>Call for price</strong>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Homepage', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_home',
		'default'  => '<strong>Call for price</strong>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Related', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_related',
		'default'  => '<strong>Call for price</strong>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show for Variations', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_variation',
		'default'  => '<strong>Call for price</strong>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Hide Sale! Tag', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide the tag', 'woocommerce-jetpack' ),
		'id'       => 'wcj_call_for_price_hide_sale_sign',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Make All Products Call for Price', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this to make all products prices empty. When checkbox disabled, all prices go back to normal.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_call_for_price_make_all_empty',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_call_for_price_options',
	),
);
