<?php
/**
 * Booster for WooCommerce - Settings - Shipping Time
 *
 * @version 3.4.6
 * @since   3.4.6
 * @author  Algoritmika Ltd.
 * @todo    ! add "Locations not covered by your other zones"
 * @todo    estimated date calculation
 * @todo    add e.g.: "... order before 2 PM to receive"
 * @todo    check for `WC()` etc. to exist
 * @todo    other display options (besides shortcode)
 * @todo    (maybe) global fallback time
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$shipping_classes = WC()->shipping->get_shipping_classes();
$shipping_classes_data = array();
foreach ( $shipping_classes as $shipping_class ) {
	$shipping_classes_data[ $shipping_class->term_id ] = $shipping_class->name;
}
$shipping_classes_data[0] = __( 'No shipping class', 'woocommerce' );
foreach ( WC_Shipping_Zones::get_zones() as $zone_id => $zone_data ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $zone_data['formatted_zone_location'],
			'type'     => 'title',
			'id'       => 'wcj_shipping_time_' . $zone_id . '_options',
		),
	) );
	foreach ( $zone_data['shipping_methods'] as $shipping_method ) {
		$is_first = true;
		foreach ( $shipping_classes_data as $shipping_class_id => $shipping_class_name ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => ( $is_first ? $shipping_method->method_title : '' ),
					'desc'     => $shipping_class_name,
					'id'       => 'wcj_shipping_time_' . $zone_id . '_' . $shipping_method->id . '_' . $shipping_class_id,
					'type'     => 'text',
					'default'  => '',
				),
			) );
			$is_first = false;
		}
	}
	$settings = array_merge( $settings, array(
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_shipping_time_' . $zone_id . '_options',
		),
	) );
}
return $settings;
