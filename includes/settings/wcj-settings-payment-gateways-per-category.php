<?php
/**
 * Booster for WooCommerce - Settings - Gateways per Product or Category
 *
 * @version 3.1.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_cats                = wcj_get_terms( 'product_cat' );
$is_multiselect_products     = ( 'yes' === get_option( 'wcj_list_for_products', 'yes' ) );
$products                    = ( $is_multiselect_products ? wcj_get_products() : false );
$available_gateways          = WC()->payment_gateways->payment_gateways();
$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_gateways_per_category_options',
	),
);
foreach ( $available_gateways as $gateway_id => $gateway ) {
	wcj_maybe_convert_and_update_option_value( array(
		array( 'id' => 'wcj_gateways_per_products_'      . $gateway_id, 'default' => '' ),
		array( 'id' => 'wcj_gateways_per_products_excl_' . $gateway_id, 'default' => '' ),
	), $is_multiselect_products );
	$settings = array_merge( $settings, array(
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
				'title'    => '',
				'desc'     => __( 'Products - Include', 'woocommerce-jetpack' ) . '. ' . apply_filters( 'booster_message', '', 'desc' ),
				'desc_tip' => __( 'Show gateway only if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_gateways_per_products_' . $gateway_id,
				'default'  => '',
				'css'      => 'width: 450px;',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			$products,
			$is_multiselect_products
		),
		wcj_get_settings_as_multiselect_or_text(
			array(
				'title'    => '',
				'desc'     => __( 'Products - Exclude', 'woocommerce-jetpack' ) . '. ' . apply_filters( 'booster_message', '', 'desc' ),
				'desc_tip' => __( 'Hide gateway if there is selected products in cart. Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_gateways_per_products_excl_' . $gateway_id,
				'default'  => '',
				'css'      => 'width: 450px;',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			$products,
			$is_multiselect_products
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_gateways_per_category_options',
	),
) );
return $settings;
