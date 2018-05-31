<?php
/**
 * Booster for WooCommerce - Settings - Shipping by Condition
 *
 * @version 3.6.0
 * @since   3.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$use_shipping_instances = ( 'yes' === get_option( 'wcj_' . $this->id . '_use_shipping_instance', 'no' ) );
$shipping_methods       = ( $use_shipping_instances ? wcj_get_shipping_methods_instances( true ) : WC()->shipping()->load_shipping_methods() );
$settings = array();
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_' . $this->id . '_general_options',
	),
	array(
		'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_' . $this->id . '_use_shipping_instance',
		'default'  => 'no',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_' . $this->id . '_general_options',
	),
) );
foreach ( $this->condition_options as $options_id => $options_data ) {
	$settings = array_merge( $settings, array(
		array(
			'title'   => sprintf( __( 'Shipping Methods by %s', 'woocommerce-jetpack' ), $options_data['title'] ),
			'type'    => 'title',
			'desc'    => __( 'Leave empty to disable.', 'woocommerce-jetpack' )  . ' ' . $options_data['desc'],
			'id'      => 'wcj_shipping_by_' . $options_id . '_options',
		),
		array(
			'title'   => sprintf( __( 'Shipping Methods by %s', 'woocommerce-jetpack' ), $options_data['title'] ),
			'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
			'id'      => 'wcj_shipping_by_' . $options_id . '_section_enabled',
			'type'    => 'checkbox',
			'default' => 'yes',
		),
	) );
	$settings = array_merge( $settings, $this->get_additional_section_settings( $options_id ) );
	foreach ( $shipping_methods as $method ) {
		$method_id = ( $use_shipping_instances ? $method['shipping_method_id'] : $method->id );
		if ( ! in_array( $method_id, array( 'flat_rate', 'local_pickup' ) ) ) {
			$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
			if ( '' == $custom_attributes ) {
				$custom_attributes = array();
			}
			$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
		} else {
			$custom_attributes = array();
			$desc_tip = '';
		}
		$type  = ( isset( $options_data['type'] )  ? $options_data['type']  : 'multiselect' );
		$class = ( isset( $options_data['class'] ) ? $options_data['class'] : 'chosen_select' );
		$css   = ( isset( $options_data['css'] )   ? $options_data['css']   : '' );
		$settings = array_merge( $settings, array(
			array(
				'title'     => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title'] : $method->get_method_title() ),
				'desc_tip'  => $desc_tip,
				'desc'      => '<br>' . sprintf( __( 'Include %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'id'        => 'wcj_shipping_' . $options_id . '_include_' . ( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id ),
				'default'   => '',
				'type'      => $type,
				'class'     => $class,
				'css'       => $css,
				'options'   => $this->get_condition_options( $options_id ),
				'custom_attributes' => $custom_attributes,
			),
			array(
				'desc_tip'  => $desc_tip,
				'desc'      => '<br>' . sprintf( __( 'Exclude %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'id'        => 'wcj_shipping_' . $options_id . '_exclude_' . ( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id ),
				'default'   => '',
				'type'      => $type,
				'class'     => $class,
				'css'       => $css,
				'options'   => $this->get_condition_options( $options_id ),
				'custom_attributes' => $custom_attributes,
			),
		) );
	}
	$settings = array_merge( $settings, array(
		array(
			'type'  => 'sectionend',
			'id'    => 'wcj_shipping_by_' . $options_id . '_options',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_' . $this->id . '_advanced_options',
	),
	array(
		'title'    => __( 'Filter Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to use the default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_filter_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_' . $this->id . '_advanced_options',
	),
) );
return $settings;
