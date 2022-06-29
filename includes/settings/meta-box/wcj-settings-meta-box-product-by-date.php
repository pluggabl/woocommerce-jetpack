<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Availability by Date
 *
 * @version 5.6.0
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @todo    (maybe) Direct Date: all products
 * @todo    (maybe) Direct Date: option to disable months in admin product edit page
 * @todo    (maybe) Direct Date: add time also
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$format     = wcj_get_option( 'wcj_product_by_date_direct_date_format', 'm/d/Y' );
$settings   = array(
	array(
		'title'   => __( 'Enable/Disable per Product Settings', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_by_date_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'Disabled', 'woocommerce-jetpack' ),
			'yes' => __( 'Enabled', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'             => __( 'Direct Date', 'woocommerce-jetpack' ),
		'name'              => 'wcj_product_by_date_direct_date',
		'default'           => '',
		'type'              => 'date',
		'tooltip'           => __( 'Fill this if you want to set one date from which the product will be available.', 'woocommerce-jetpack' ) . ' ' .
			__( 'If this field is filled in, monthly settings fields are ignored.', 'woocommerce-jetpack' ) . '<br /><br />' .
			__( 'If you are not using english, please set some numeric format like m/d/Y on "Direct Date Admin Input Date Format" option', 'woocommerce-jetpack' ),
		'custom_attributes' => ( '' !== ( $format ) ?
			'dateformat="' . wcj_date_format_php_to_js( $format ) . '"' : '' ),
	),
);
$_timestamp = 1; // January 1 1970.
for ( $i = 1; $i <= 12; $i++ ) {
	$settings   = array_merge(
		$settings,
		array(
			array(
				'title'   => date_i18n( 'F', $_timestamp ),
				'name'    => 'wcj_product_by_date_' . $i,
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:300px;',
			),
		)
	);
	$_timestamp = strtotime( '+1 month', $_timestamp );
}
return $settings;
