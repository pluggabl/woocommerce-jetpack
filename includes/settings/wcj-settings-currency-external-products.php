<?php
/**
 * Booster for WooCommerce - Settings - Currency for External Products
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'currency_external_products_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'currency_external_products_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'currency_external_products_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'currency_external_products_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Currency', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set currency for all external products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_external_products_symbol', // mislabeled, should be.'wcj_currency_external_products_code'.
		'default'  => 'EUR',
		'type'     => 'select',
		'class'    => 'wcj_select_search_input',
		'options'  => wcj_get_woocommerce_currencies_and_symbols(),
	),
	array(
		'id'   => 'wcj_currency_external_products_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'currency_external_products_general_options_tab',
		'type' => 'tab_end',
	),
);
