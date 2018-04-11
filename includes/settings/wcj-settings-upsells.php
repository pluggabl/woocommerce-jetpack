<?php
/**
 * Booster for WooCommerce - Settings - Upsells
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
		'id'       => 'wcj_upsells_options',
	),
	array(
		'title'    => __( 'Upsells Total', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ) . ' ' . __( 'Set to -1 for unlimited.', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_upsells_total',
		'default'  => 0,
		'custom_attributes' => array( 'min' => -1 ),
	),
	array(
		'title'    => __( 'Upsells Columns', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero for WooCommerce default.', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_upsells_columns',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Upsells Order By', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_upsells_orderby',
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
	array(
		'title'    => __( 'Hide Upsells', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_upsells_hide',
		'default'  => 'no',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_upsells_options',
	),
);
return $settings;
