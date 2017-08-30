<?php
/**
 * Booster for WooCommerce - Settings - Product Visibility by Country
 *
 * @version 3.0.2
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Visibility Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_country_options',
	),
	array(
		'title'    => __( 'Hide Visibility', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will hide selected products in shop and search results. However product still will be accessible via direct link.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_country_visibility',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Make Non-purchasable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will make selected products non-purchasable (i.e. product can\'t be added to the cart).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_country_purchasable',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Modify Query', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will hide selected products completely (including direct link).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_country_query',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_country_options',
	),
	array(
		'title'    => __( 'Admin Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_country_admin_options',
	),
	array(
		'title'    => __( 'Admin Products List Column', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add "Countries" column to the admin products list.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_country_add_column_visible_countries',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_country_admin_options',
	),
);
