<?php
/**
 * Booster for WooCommerce - Settings - Gateways PDF notes
 *
 * @version 6.0.5
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$tip = sprintf(
					/* translators: %s: translators Added */
	__( 'You can use order Shortcodes here, please visit %s.', 'woocommerce-jetpack' ),
	'<a target="_blank" href="https://booster.io/category/shortcodes/?utm_source=shortcodes_list&utm_medium=module_button&utm_campaign=booster_documentation">' .
		'https://booster.io/category/shortcodes/' .
	'</a>'
);

$settings           = array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'If you want to display a gateways notes in the PDF invoice add notes here.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_pdf_notes_options',
	),
);
$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$default_gateways = array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		$desc_tip          = apply_filters( 'booster_message', '', 'desc' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}

	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $gateway->title,
				'desc_tip'          => __( 'Add notes for ' ) . $gateway->title,
				'desc'              => $tip,
				'id'                => 'wcj_gateways_' . $key . '_pdf_notes',
				'default'           => '',
				'type'              => 'text',
				'css'               => 'min-width:300px;width:50%;',
				'custom_attributes' => $custom_attributes,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_payment_gateways_pdf_notes_options',
		),
	)
);
return $settings;
