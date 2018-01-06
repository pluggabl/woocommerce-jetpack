<?php
/**
 * Booster for WooCommerce - Settings - Shipping Calculator
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Shipping Calculator Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_calculator_options',
	),
	array(
		'title'    => __( 'Enable City', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_enable_city',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Enable Postcode', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_enable_postcode',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Enable State', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_enable_state',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Force Block Open', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_enable_force_block_open',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( '', 'woocommerce-jetpack' ),
		'desc'     => __( 'Calculate Shipping button', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When "Force Block Open" options is enabled, set Calculate Shipping button options.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_enable_force_block_open_button',
		'default'  => 'hide',
		'type'     => 'select',
		'options'  => array(
			'hide'    => __( 'Hide', 'woocommerce-jetpack' ),
			'noclick' => __( 'Make non clickable', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_calculator_options',
	),
	array(
		'title'    => __( 'Labels Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_calculator_labels_options',
	),
	array(
		'title'    => __( 'Labels', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_labels_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Label for Calculate Shipping', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_label_calculate_shipping',
		'default'  => __( 'Calculate Shipping', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Label for Update Totals', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_calculator_label_update_totals',
		'default'  => __( 'Update Totals', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_calculator_labels_options',
	),
);
