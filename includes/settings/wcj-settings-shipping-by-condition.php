<?php
/**
 * Booster for WooCommerce - Settings - Shipping by Condition
 *
 * @version 3.2.4
 * @since   3.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = array();
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
			'desc'    => __( 'Enable section', 'woocommerce-jetpack' ),
			'id'      => 'wcj_shipping_by_' . $options_id . '_section_enabled',
			'type'    => 'checkbox',
			'default' => 'yes',
		),
	) );
	$settings = array_merge( $settings, $this->get_additional_section_settings( $options_id ) );
	foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
		if ( ! in_array( $method->id, array( 'flat_rate', 'local_pickup' ) ) ) {
			$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
			if ( '' == $custom_attributes ) {
				$custom_attributes = array();
			}
			$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
		} else {
			$custom_attributes = array();
			$desc_tip = '';
		}
		$settings = array_merge( $settings, array(
			array(
				'title'     => $method->get_method_title(),
				'desc_tip'  => $desc_tip,
				'desc'      => '<br>' . sprintf( __( 'Include %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'id'        => 'wcj_shipping_' . $options_id . '_include_' . $method->id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
				'options'   => $this->get_condition_options( $options_id ),
				'custom_attributes' => $custom_attributes,
			),
			array(
				'desc_tip'  => $desc_tip,
				'desc'      => '<br>' . sprintf( __( 'Exclude %s', 'woocommerce-jetpack' ), $options_data['title'] ),
				'id'        => 'wcj_shipping_' . $options_id . '_exclude_' . $method->id,
				'default'   => '',
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'       => 'width: 450px;',
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
