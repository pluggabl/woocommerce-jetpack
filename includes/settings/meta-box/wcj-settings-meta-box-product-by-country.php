<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Visibility by Country
 *
 * @version 3.1.1
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$countries = ( 'wc' === apply_filters( 'booster_option', 'all', get_option( 'wcj_product_by_country_country_list', 'all' ) ) ? WC()->countries->get_allowed_countries() : wcj_get_countries() );

$options = array();
if ( 'invisible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
	$options = array_merge( $options, array(
		array(
			'title'    => __( 'Visible in Countries', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'Use "Control" key to select/deselect multiple countries. Hold "Control" and "A" to select all countries. Leave empty to disable.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_by_country_visible',
			'default'  => '',
			'type'     => 'select',
			'options'  => $countries,
			'multiple' => true,
			'css'      => 'height:200px;',
			'class'    => 'widefat',
			'show_value' => true,
		),
	) );
}
if ( 'visible' != apply_filters( 'booster_option', 'visible', get_option( 'wcj_product_by_country_visibility_method', 'visible' ) ) ) {
	$options = array_merge( $options, array(
		array(
			'title'    => __( 'Invisible in Countries', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'Use "Control" key to select/deselect multiple countries. Hold "Control" and "A" to select all countries. Leave empty to disable.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_by_country_invisible',
			'default'  => '',
			'type'     => 'select',
			'options'  => $countries,
			'multiple' => true,
			'css'      => 'height:200px;',
			'class'    => 'widefat',
			'show_value' => true,
		),
	) );
}
return $options;
