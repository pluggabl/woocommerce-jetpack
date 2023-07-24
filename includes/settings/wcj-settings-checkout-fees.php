<?php
/**
 * Booster for WooCommerce - Settings - Checkout Fees
 *
 * @version 7.0.0
 * @since   3.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = array(
	array(
		'id'   => 'wcj_checkout_fees_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_checkout_fees_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_checkout_fees_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_checkout_fees_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Fees', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_checkout_fees_general_options',
	),
	array(
		'title'             => __( 'Total Fees', 'woocommerce-jetpack' ),
		'id'                => 'wcj_checkout_fees_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_checkout_fees_general_options',
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_fees_total_number', 1 ) );
$fees_titles  = wcj_get_option( 'wcj_checkout_fees_data_titles', array() );
$fees         = array();
for ( $i = 1; $i <= $total_number; $i ++ ) {
	$fees[ $i ] = null !== $fees_titles && isset( $fees_titles[ $i ] ) ? $fees_titles[ $i ] : '';
}

for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'  => 'title',
				'id'    => "wcj_checkout_fees_data_options[$i]",
			),
			array(
				'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => "wcj_checkout_fees_data_enabled[$i]",
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Taxable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => "wcj_checkout_fees_data_taxable[$i]",
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => "wcj_checkout_fees_data_titles[$i]",
				'default' => __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Type', 'woocommerce-jetpack' ),
				'id'      => "wcj_checkout_fees_data_types[$i]",
				'default' => 'fixed',
				'type'    => 'select',
				'options' => array(
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'             => __( 'Value', 'woocommerce-jetpack' ),
				'id'                => "wcj_checkout_fees_data_values[$i]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array( 'step' => 0.000001 ),
			),
			array(
				'title'             => __( 'Cart Minimum Quantity', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Minimum amount of items in cart.', 'woocommerce-jetpack' ),
				'id'                => "wcj_checkout_fees_cart_min_amount[$i]",
				'default'           => 1,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'title'    => __( 'Cart Maximum Quantity', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Maximum amount of items in cart.', 'woocommerce-jetpack' ) . '<br />' . __( 'Zero or empty values will not be considered', 'woocommerce-jetpack' ),
				'id'       => "wcj_checkout_fees_cart_max_amount[$i]",
				'default'  => '',
				'type'     => 'number',
			),
			array(
				'title'             => __( 'Cart Minimum Total', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Minimum total amount in cart.', 'woocommerce-jetpack' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'id'                => "wcj_checkout_fees_cart_min_total_amount[$i]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'title'             => __( 'Cart Maximum Total', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Maximum total amount in cart.', 'woocommerce-jetpack' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'id'                => "wcj_checkout_fees_cart_max_total_amount[$i]",
				'type'              => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Checkout Field', 'woocommerce-jetpack' ),
				/* translators: %s: translators Added */
				'desc_tip' => sprintf( __( 'If you want fee to be added only if some checkout field is enabled, enter field\'s key here. For example, if you have added one custom billing checkout field with Booster\'s "Checkout Custom Fields" module, enter %s here.', 'woocommerce-jetpack' ), '<em>billing_wcj_checkout_field_1</em>' ) . ' ' .
					__( 'Ignored if empty (i.e. fee will be always added).', 'woocommerce-jetpack' ),
				'id'       => "wcj_checkout_fees_data_checkout_fields[$i]",
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => __( 'Overlap', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If valid, will overlap other fee', 'woocommerce-jetpack' ),
				'id'       => "wcj_checkout_fees_overlap[$i]",
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'default'  => '',
				'options'  => array_filter(
					$fees,
					function ( $fee_id ) use ( $i ) {
						return $i !== $fee_id;
					},
					ARRAY_FILTER_USE_KEY
				),
			),
			array(
				'title'    => __( 'Priority', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'The higher the number the higher the priority.', 'woocommerce-jetpack' ) . '<br />' . __( 'Will mostly make sense for overlapping.', 'woocommerce-jetpack' ),
				'id'       => "wcj_checkout_fees_priority[$i]",
				'type'     => 'number',
				'default'  => 0,
			),
			array(
				'id'   => "wcj_checkout_fees_data_options[$i]",
				'type' => 'sectionend',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_checkout_fees_general_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
