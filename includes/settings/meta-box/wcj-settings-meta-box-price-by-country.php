<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Price by Country
 *
 * @version 5.6.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$products     = wcj_get_product_ids_for_meta_box_options( get_the_ID() );
$groups       = array();
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) );

for ( $i = 1; $i <= $total_number; $i++ ) {
	$group_currency_code = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
	$admin_title         = wcj_get_option( 'wcj_price_by_country_countries_group_admin_title_' . $i, __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i );
	$countries           = '';
	switch ( wcj_get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
		case 'comma_list':
			$countries .= wcj_get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
			break;
		case 'multiselect':
			$group      = wcj_get_option( 'wcj_price_by_country_countries_group_' . $i, '' );
			$countries .= ( '' !== ( $group ) ? implode( ',', $group ) : '' );
			break;
		case 'chosen_select':
			$group      = wcj_get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' );
			$countries .= ( '' !== ( $group ) ? implode( ',', $group ) : '' );
			break;
	}
	$admin_title = '<details> <summary>' . $admin_title . ' [' . $group_currency_code . '] </summary> <p>' . $countries . ' (' . count( explode( ',', $countries ) ) . ')</p> </details>';
	$groups      = array_merge(
		$groups,
		array(
			array(
				'type'  => 'title',
				'title' => $admin_title,
				'css'   => 'background-color:#cddc39;color:black;',
			),
		)
	);
	foreach ( $products as $product_id => $desc ) {
		$groups = array_merge(
			$groups,
			array(
				array(
					'name'       => 'wcj_price_by_country_regular_price_local_' . $i . '_' . $product_id,
					'default'    => 0,
					'type'       => 'price',
					'title'      => __( 'Regular price', 'woocommerce-jetpack' ),
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_wcj_price_by_country_regular_price_local_' . $i,
				),
				array(
					'name'       => 'wcj_price_by_country_sale_price_local_' . $i . '_' . $product_id,
					'default'    => '',
					'type'       => 'price',
					'title'      => __( 'Sale price', 'woocommerce-jetpack' ),
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_wcj_price_by_country_sale_price_local_' . $i,
				),
				array(
					'name'       => 'wcj_price_by_country_make_empty_price_local_' . $i . '_' . $product_id,
					'default'    => 'off',
					'type'       => 'select',
					'options'    => array(
						'off' => __( 'No', 'woocommerce-jetpack' ),
						'on'  => __( 'Yes', 'woocommerce-jetpack' ),
					),
					'title'      => __( 'Make empty price', 'woocommerce-jetpack' ),
					'desc'       => $desc,
					'product_id' => $product_id,
					'meta_name'  => '_wcj_price_by_country_make_empty_price_local_' . $i,
				),
			)
		);
	}
}
return $groups;
