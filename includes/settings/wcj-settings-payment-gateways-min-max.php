<?php
/**
 * Booster for WooCommerce - Settings - Gateways Min/Max Amounts
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    checkout notices - add %diff_amount% and %total_in_cart% replaced values (wc_has_notice won't work then?, probably will need to use wc_clear_notices)
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_payment_gateways_min_max_general_options',
	),
	array(
		'title'   => __( 'Exclude Shipping', 'woocommerce-jetpack' ),
		'desc'    => __( 'Exclude shipping from total cart sum, when comparing with min/max amounts.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_payment_gateways_min_max_exclude_shipping',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Notices on Checkout', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable Notices', 'woocommerce-jetpack' ),
		'id'      => 'wcj_payment_gateways_min_max_notices_enable',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( 'Notice Template (Minimum Amount)', 'woocommerce-jetpack' ),
		/* translators: %gateway_title% %min_amount% : translators Added */
		'desc_tip' => __( 'Replaced values: %gateway_title%, %min_amount%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_payment_gateways_min_max_notices_template_min',
		/* translators: %gateway_title% %min_amount% : translators Added */
		'default'  => __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:90%;min-width:300px',
	),
	array(
		'desc'     => __( 'Notice Template (Maximum Amount)', 'woocommerce-jetpack' ),
		/* translators: %gateway_title% %min_amount% : translators Added */
		'desc_tip' => __( 'Replaced values: %gateway_title%, %max_amount%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_payment_gateways_min_max_notices_template_max',
		/* translators: %gateway_title% %min_amount% : translators Added */
		'default'  => __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:90%;min-width:300px',
	),
	array(
		'desc'    => __( 'Notice Styling', 'woocommerce-jetpack' ),
		'id'      => 'wcj_payment_gateways_min_max_notices_type',
		'default' => 'notice',
		'type'    => 'select',
		'options' => array(
			'notice'  => __( 'Notice', 'woocommerce-jetpack' ),
			'error'   => __( 'Error', 'woocommerce-jetpack' ),
			'success' => __( 'Success', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_payment_gateways_min_max_general_options',
	),
	array(
		'title' => __( 'Compatibility', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_payment_gateways_min_max_comp',
	),
	array(
		'title'   => __( 'Multicurrency', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable compatibility with Multicurrency module', 'woocommerce-jetpack' ),
		'id'      => 'wcj_payment_gateways_min_max_comp_mc',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_payment_gateways_min_max_comp',
	),
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave zero to disable.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_min_max_gateways_options',
	),
);
$gateways = WC()->payment_gateways->payment_gateways();
foreach ( $gateways as $key => $gateway ) {
	$default_gateways = array( 'bacs' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' === $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $gateway->title,
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Min', 'woocommerce-jetpack' ),
				'id'                => 'wcj_payment_gateways_min_' . $key,
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array_merge(
					array(
						'step' => '0.000001',
						'min'  => '0',
					),
					$custom_attributes
				),
			),
			array(
				'title'             => '',
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Max', 'woocommerce-jetpack' ),
				'id'                => 'wcj_payment_gateways_max_' . $key,
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array_merge(
					array(
						'step' => '0.000001',
						'min'  => '0',
					),
					$custom_attributes
				),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_payment_gateways_min_max_gateways_options',
		),
	)
);
return $settings;
