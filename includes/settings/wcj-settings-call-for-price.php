<?php
/**
 * Booster for WooCommerce - Settings - Call for Price
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text',
		'default'  => $this->default_empty_price_text,
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Archives', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_archive',
		'default'  => $this->default_empty_price_text,
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Homepage', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_home',
		'default'  => $this->default_empty_price_text,
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label to Show on Related', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets the html to output on empty price. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'id'       => 'wcj_call_for_price_text_on_related',
		'default'  => $this->default_empty_price_text,
		'type'     => 'textarea',
		'css'      => 'width:50%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
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
		'desc_tip' => __( 'Enable this to make all products (except variable) prices empty. When checkbox disabled, all prices go back to normal.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_call_for_price_make_all_empty',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_call_for_price_options',
	),
);
