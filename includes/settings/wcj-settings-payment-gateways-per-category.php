<?php
/**
 * Booster for WooCommerce - Settings - Gateways per Product or Category
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_cats            = wcj_get_terms( 'product_cat' );
$is_multiselect_products = ( 'yes' === wcj_get_option( 'wcj_list_for_products', 'yes' ) );
$do_use_variations       = ( 'yes' === wcj_get_option( 'wcj_gateways_per_category_use_variations', 'no' ) );
$products                = ( $is_multiselect_products ? wcj_get_products( array(), 'any', 512, $do_use_variations, $do_use_variations ) : false );
$available_gateways      = WC()->payment_gateways->payment_gateways();
$settings                = array(
	array(
		'id'   => 'payment_gateways_per_category_role_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'payment_gateways_per_category_role_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'payment_gateways_per_category_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'payment_gateways_per_category_payment_gatways_tab'   => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'payment_gateways_per_category_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_gateways_per_category_general_options',
	),
	array(
		'title'    => __( 'Use Variations', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will use variations instead of main product for variable type products.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_gateways_per_category_use_variations',
		'default'  => 'no',
	),
	array(
		'id'   => 'wcj_gateways_per_category_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'payment_gateways_per_category_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'payment_gateways_per_category_payment_gatways_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_gateways_per_category_options',
	),
);
foreach ( $available_gateways as $gateway_id => $gateway ) {
	wcj_maybe_convert_and_update_option_value(
		array(
			array(
				'id'      => 'wcj_gateways_per_products_' . $gateway_id,
				'default' => '',
			),
			array(
				'id'      => 'wcj_gateways_per_products_excl_' . $gateway_id,
				'default' => '',
			),
		),
		$is_multiselect_products
	);
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'    => $gateway->title,
				'desc'     => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Show gateway only if there is product of selected category in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_gateways_per_category_' . $gateway_id,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			array(
				'title'    => '',
				'desc'     => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Hide gateway if there is product of selected category in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_gateways_per_category_excl_' . $gateway_id,
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			wcj_get_settings_as_multiselect_or_text(
				array(
					'title'             => '',
					'desc'              => __( 'Products - Include', 'woocommerce-jetpack' ) . '<br>' . apply_filters( 'booster_message', '', 'desc' ),
					'desc_tip'          => __( 'Show gateway only if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
					'id'                => 'wcj_gateways_per_products_' . $gateway_id,
					'default'           => '',
					'css'               => 'width: 450px;',
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				),
				$products,
				$is_multiselect_products
			),
			wcj_get_settings_as_multiselect_or_text(
				array(
					'title'             => '',
					'desc'              => __( 'Products - Exclude', 'woocommerce-jetpack' ) . '<br>' . apply_filters( 'booster_message', '', 'desc' ),
					'desc_tip'          => __( 'Hide gateway if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
					'id'                => 'wcj_gateways_per_products_excl_' . $gateway_id,
					'default'           => '',
					'css'               => 'width: 450px;',
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				),
				$products,
				$is_multiselect_products
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_gateways_per_category_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'payment_gateways_per_category_payment_gatways_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
