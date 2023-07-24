<?php
/**
 * Booster for WooCommerce - Settings - Free Price
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$settings      = array(
	array(
		'id'   => 'free_price_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'free_price_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'free_price_simple_tab'   => __( 'Simple and Custom Products', 'woocommerce-jetpack' ),
			'free_price_variable_tab' => __( 'Variable Products', 'woocommerce-jetpack' ),
			'free_price_grouped_tab'  => __( 'Grouped Products', 'woocommerce-jetpack' ),
			'free_price_external_tab' => __( 'External Products', 'woocommerce-jetpack' ),
		),
	),
);
$product_types = array(
	'simple'   => __( 'Simple and Custom Products', 'woocommerce-jetpack' ),
	'variable' => __( 'Variable Products', 'woocommerce-jetpack' ),
	'grouped'  => __( 'Grouped Products', 'woocommerce-jetpack' ),
	'external' => __( 'External Products', 'woocommerce-jetpack' ),
);
$views         = array(
	'single'  => __( 'Single Product Page', 'woocommerce-jetpack' ),
	'related' => __( 'Related Products', 'woocommerce-jetpack' ),
	'home'    => __( 'Homepage', 'woocommerce-jetpack' ),
	'page'    => __( 'Pages (e.g. Shortcodes)', 'woocommerce-jetpack' ),
	'archive' => __( 'Archives (Product Categories)', 'woocommerce-jetpack' ),
);
foreach ( $product_types as $product_type => $product_type_desc ) {
	$settings      = array_merge(
		$settings,
		array(
			array(
				'id'   => 'free_price_' . $product_type . '_tab',
				'type' => 'tab_start',
			),
		)
	);
	$default_value = ( 'simple' === $product_type || 'external' === $product_type ) ? '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>' : __( 'Free!', 'woocommerce' );
	$settings      = array_merge(
		$settings,
		array(
			array(
				'title' => $product_type_desc,
				'desc'  => __( 'Labels can contain shortcodes.', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'id'    => 'wcj_free_price_' . $product_type . 'options',
			),
		)
	);
	$current_views = $views;
	if ( 'variable' === $product_type ) {
		$current_views['variation'] = __( 'Variations', 'woocommerce-jetpack' );
	}
	foreach ( $current_views as $view => $view_desc ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title'             => $view_desc,
					'id'                => 'wcj_free_price_' . $product_type . '_' . $view,
					'default'           => $default_value,
					'type'              => 'textarea',
					'css'               => 'width:30%;min-width:300px;min-height:50px;',
					'desc'              => ( 'variable' === $product_type ) ? apply_filters( 'booster_message', '', 'desc' ) : '',
					'custom_attributes' => ( 'variable' === $product_type ) ? apply_filters( 'booster_message', '', 'readonly' ) : '',
				),
			)
		);
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_free_price_' . $product_type . 'options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'free_price_' . $product_type . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
