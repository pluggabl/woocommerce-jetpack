<?php
/**
 * Booster for WooCommerce - Settings - Shipping Time
 *
 * @version 3.5.1
 * @since   3.5.0
 * @author  Pluggabl LLC.
 * @todo    estimated date calculation
 * @todo    add e.g.: "... order before 2 PM to receive"
 * @todo    check for `WC()` etc. to exist
 * @todo    other display options (besides shortcode)
 * @todo    (maybe) rename to "Delivery Time"
 * @todo    (maybe) global fallback time
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_time_use_shipping_instance', 'no' ) );
$use_shipping_classes   = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_shipping_time_use_shipping_classes', 'no' ) ) );
$shipping_methods       = ( $use_shipping_instances ? wcj_get_shipping_methods_instances( true ) : WC()->shipping()->load_shipping_methods() );
$shipping_classes_data  = ( $use_shipping_classes ? wcj_get_shipping_classes() : array( '' => '' ) );
$settings = array();
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_time_general_options',
	),
	array(
		'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_shipping_time_use_shipping_instance',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Use Product Shipping Classes', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to set options for each shipping class separately.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_shipping_time_use_shipping_classes',
		'default'  => 'no',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_time_general_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Shipping Time Options', 'woocommerce-jetpack' ),
		'desc'     => __( 'Set estimated shipping time in <strong>days</strong>.', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_time_options',
	),
) );
foreach ( $shipping_methods as $method ) {
	$method_id = ( $use_shipping_instances ? $method['shipping_method_id'] : $method->id );
	foreach ( $shipping_classes_data as $shipping_class_id => $shipping_class_name ) {
		$settings = array_merge( $settings, array(
			array(
				'title'    => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title']: $method->get_method_title() ),
				'desc'     => ( $use_shipping_classes ? $shipping_class_name : '' ),
				'id'       => 'wcj_shipping_time_' .
					( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id ) .
					( $use_shipping_classes ? '_class_' . $shipping_class_id : '' ),
				'type'     => 'text',
				'default'  => '',
			),
		) );
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_time_options',
	),
) );
return $settings;
