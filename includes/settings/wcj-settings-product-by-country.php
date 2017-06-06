<?php
/**
 * Booster for WooCommerce - Settings - Product Visibility by Country
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_country_options',
	),
	array(
		'title'    => __( 'Admin Products List Column', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_country_add_column_visible_countries',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_country_options',
	),
);
