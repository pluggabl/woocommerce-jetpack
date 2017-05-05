<?php
/**
 * Booster for WooCommerce - Settings - Stock
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    get_stock_html: ( WCJ_IS_WC_VERSION_BELOW_3 ? `woocommerce_stock_html` : `woocommerce_get_stock_html` )
 * @todo    apply_filters( 'woocommerce_stock_html', $html, $availability['availability'], $product );
 * @todo    apply_filters( 'woocommerce_get_stock_html', $html, $product );
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Custom Out of Stock Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_stock_custom_out_of_stock_options',
	),
	array(
		'title'    => __( 'Custom Out of Stock', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_stock_custom_out_of_stock_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom Out of Stock HTML', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_out_of_stock_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_stock_custom_out_of_stock',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Custom Out of Stock Class', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_out_of_stock_class_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_stock_custom_out_of_stock_class',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_stock_custom_out_of_stock_options',
	),
);
