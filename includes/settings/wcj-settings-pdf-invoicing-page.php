<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Page Settings
 *
 * @version 3.1.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $invoice_type['title'],
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
		),
		array(
			'title'    => __( 'Page Orientation', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_orientation',
			'default'  => 'P',
			'type'     => 'select',
			'options'  => array(
				'P' => __( 'Portrait', 'woocommerce-jetpack' ),
				'L' => __( 'Landscape', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Page Format', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format',
			'default'  => 'A4',
			'type'     => 'select',
			'options'  => $this->get_page_formats(),
		),
		array(
			'title'    => __( 'Margin Left', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_left',
			'default'  => 15, // PDF_MARGIN_LEFT,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Margin Right', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_right',
			'default'  => 15, // PDF_MARGIN_RIGHT,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Margin Top', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_top',
			'default'  => 27, // PDF_MARGIN_TOP,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Margin Bottom', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_bottom',
			'default'  => 0, // PDF_MARGIN_BOTTOM,
			'type'     => 'number',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
		),
	) );
}
return $settings;
