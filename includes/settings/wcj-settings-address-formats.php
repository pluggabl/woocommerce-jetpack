<?php
/**
 * Booster for WooCommerce - Settings - Address Formats
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'Force Base Country Display', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_address_formats_force_country_display_options',
	),
	array(
		'title'   => __( 'Force Base Country Display', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_address_formats_force_country_display',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_address_formats_force_country_display_options',
	),
	array(
		'title' => __( 'Address Formats by Country', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_address_formats_country_options',
	),
);
$formats  = $this->get_default_address_formats();
foreach ( $formats as $country_code => $format ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => ( 'default' === $country_code ) ? $country_code : $country_code . ' - ' . wcj_get_country_name_by_code( $country_code ),
				'id'      => 'wcj_address_formats_country_' . $country_code,
				'default' => $format,
				'type'    => 'textarea',
				'css'     => 'width:300px;height:200px;',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_address_formats_country_options',
		),
	)
);
return $settings;
