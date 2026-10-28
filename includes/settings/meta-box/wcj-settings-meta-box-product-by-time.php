<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Availability by Time
 *
 * @version 5.6.0
 * @since   2.9.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings   = array(
	array(
		'title'   => __( 'Enable/Disable per Product Settings', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_by_time_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'Disabled', 'woocommerce-jetpack' ),
			'yes' => __( 'Enabled', 'woocommerce-jetpack' ),
		),
	),
);
$_timestamp = strtotime( 'next Sunday' );
for ( $i = 0; $i < 7; $i++ ) {
	$settings   = array_merge(
		$settings,
		array(
			array(
				'title'   => date_i18n( 'l', $_timestamp ),
				'name'    => 'wcj_product_by_time_' . $i,
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:300px;',
			),
		)
	);
	$_timestamp = strtotime( '+1 day', $_timestamp );
}
return $settings;
