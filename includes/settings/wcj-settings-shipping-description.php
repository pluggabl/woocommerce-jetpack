<?php
/**
 * Booster for WooCommerce - Settings - Shipping Descriptions
 *
 * @version 3.6.0
 * @since   3.4.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf( __( 'This section will allow you to add any text (e.g. description) for shipping method. Text will be visible on cart and checkout pages. You can add HTML tags here, e.g. try %s.', 'woocommerce-jetpack' ),
			'<code>' . esc_html( '<br><small>Your shipping description.</small>' ) . '</code>' ),
		'id'       => 'wcj_shipping_description_options',
	),
	array(
		'title'    => __( 'Description Visibility', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_descriptions_visibility',
		'default'  => 'both',
		'type'     => 'select',
		'options'  => array(
			'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
			'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
			'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Description Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_descriptions_position',
		'default'  => 'after',
		'type'     => 'select',
		'options'  => array(
			'after'   => __( 'After the label', 'woocommerce-jetpack' ),
			'before'  => __( 'Before the label', 'woocommerce-jetpack' ),
			'instead' => __( 'Instead of the label', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: after the label; before the label; instead of the label.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_description_options',
	),
	array(
		'title'    => __( 'Shipping Methods Descriptions', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_description_methods_options',
	),
	array(
		'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_shipping_descriptions_use_shipping_instance',
		'default'  => 'no',
	),
);
$use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_descriptions_use_shipping_instance', 'no' ) );
$shipping_methods       = ( $use_shipping_instances ? wcj_get_shipping_methods_instances( true ) : WC()->shipping()->get_shipping_methods() );
foreach ( $shipping_methods as $method ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title'] : $method->method_title ),
			'id'       => 'wcj_shipping_description_' . ( $use_shipping_instances ? 'instance_' . $method['shipping_method_instance_id'] : $method->id ),
			'default'  => '',
			'type'     => 'textarea',
			'css'      => 'width:100%;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_description_methods_options',
	),
) );
return $settings;
