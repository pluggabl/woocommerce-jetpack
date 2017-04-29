<?php
/**
 * WooCommerce Jetpack Settings - Product Bulk Meta Editor
 *
 * @version 2.7.2
 * @since   2.7.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_bulk_meta_editor_options',
	),
	array(
		'title'    => __( 'Check if Meta Exists', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled - meta can be changed only if it already existed for product. If you want to be able to create new meta for products, disable this option.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bulk_meta_editor_check_if_exists',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_bulk_meta_editor_options',
	),
);
