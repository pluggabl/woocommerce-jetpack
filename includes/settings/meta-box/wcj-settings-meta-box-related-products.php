<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Related Products
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$product_id = get_the_ID();
$products   = wcj_get_products( array(), 'publish' );
unset( $products[ $product_id  ] );
$options = array(
	array(
		'name'     => 'wcj_product_info_related_products_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'  => __( 'No', 'woocommerce-jetpack' ),
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
		),
		'title'    => __( 'Enable', 'woocommerce-jetpack' ),
		'tooltip'  => __( 'If enabled and no products selected - will hide related products section on frontend for current product.', 'woocommerce-jetpack' ),
	),
	array(
		'name'     => 'wcj_product_info_related_products_ids',
		'default'  => '',
		'type'     => 'select',
		'options'  => $products,
		'title'    => __( 'Related Products', 'woocommerce-jetpack' ),
		'multiple' => true,
		'tooltip'  => __( 'Hold Control (Ctrl) key to select multiple products.', 'woocommerce-jetpack' ),
	),
);
return $options;
