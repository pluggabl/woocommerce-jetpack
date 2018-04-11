<?php
/**
 * Booster for WooCommerce - Settings - Cross-sells
 *
 * @version 3.5.3
 * @since   3.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_cross_sells_options',
	),
	array(
		'title'    => __( 'Cross-sells Total', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ) . ' ' . __( 'Set to -1 for unlimited.', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_cross_sells_total',
		'default'  => 0,
		'custom_attributes' => array( 'min' => -1 ),
	),
	array(
		'title'    => __( 'Cross-sells Columns', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_cross_sells_columns',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Cross-sells Order By', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_cross_sells_orderby',
		'default'  => 'no_changes',
		'options'  => array(
			'no_changes'  => __( 'No changes (default behaviour)', 'woocommerce-jetpack' ),
			'rand'        => __( 'Random', 'woocommerce-jetpack' ),
			'title'       => __( 'Title', 'woocommerce-jetpack' ),
			'id'          => __( 'ID', 'woocommerce-jetpack' ),
			'date'        => __( 'Date', 'woocommerce-jetpack' ),
			'modified'    => __( 'Modified', 'woocommerce-jetpack' ),
			'menu_order'  => __( 'Menu order', 'woocommerce-jetpack' ),
			'price'       => __( 'Price', 'woocommerce-jetpack' ),
		),
	),
);
if ( ! WCJ_IS_WC_VERSION_BELOW_3_3_0 ) {
	$settings[] = array(
		'title'    => __( 'Cross-sells Order', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_cross_sells_order',
		'default'  => 'no_changes',
		'options'  => array(
			'no_changes'  => __( 'No changes (default behaviour)', 'woocommerce-jetpack' ),
			'desc'        => __( 'Descending', 'woocommerce-jetpack' ),
			'asc'         => __( 'Ascending', 'woocommerce-jetpack' ),
		),
	);
};
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Hide Cross-sells', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_cross_sells_hide',
		'default'  => 'no',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_cross_sells_options',
	),
) );
return $settings;
