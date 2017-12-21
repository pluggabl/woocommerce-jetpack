<?php
/**
 * Booster for WooCommerce - Settings - Product Custom Visibility
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Visibility Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_custom_visibility_options',
	),
	array(
		'title'    => __( 'Hide Visibility', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will hide selected products in shop and search results. However product still will be accessible via direct link.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_custom_visibility_visibility',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Make Non-purchasable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will make selected products non-purchasable (i.e. product can\'t be added to the cart).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_custom_visibility_purchasable',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Modify Query', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will hide selected products completely (including direct link).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_custom_visibility_query',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'One per line.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_custom_visibility_options_list',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:200px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_custom_visibility_options',
	),
);
