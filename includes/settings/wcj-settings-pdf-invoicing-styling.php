<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Styling
 *
 * @version 2.8.2
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings      = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$default_template_filename = ( false === strpos( $invoice_type['id'], 'custom_doc_' ) ? $invoice_type['id'] : 'custom_doc' );
	$default_template_filename = WCJ()->plugin_path() . '/includes/settings/pdf-invoicing/wcj-' . $default_template_filename . '.css';
	if ( file_exists( $default_template_filename ) ) {
		ob_start();
		include( $default_template_filename );
		$default_template = ob_get_clean();
	} else {
		$default_template = '';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => strtoupper( $invoice_type['desc'] ),
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
		),
		array(
			'title'    => __( 'CSS', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_css',
			'default'  => $default_template,
			'type'     => 'textarea',
			'css'      => 'width:66%;min-width:300px;height:200px;',
		),
		array(
			'title'    => __( 'Font Family', 'woocommerce-jetpack' ),
			'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family',
			'default'  => 'dejavusans',
			'type'     => 'select',
			'options'  => array(
				'dejavusans'        => 'DejaVu Sans (Unicode)',
				'courier'           => 'Courier',
				'helvetica'         => 'Helvetica',
				'times'             => 'Times',
				'droidsansfallback' => 'Droid Sans Fallback (Unicode)',
				'angsanaupc'        => 'AngsanaUPC (Unicode)',
				'cordiaupc'         => 'CordiaUPC (Unicode)',
				'thsarabun'         => 'THSarabunPSK (Unicode)',
			),
			'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		),
		array(
			'title'    => __( 'Font Size', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_size',
			'default'  => 8,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Make Font Shadowed', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_shadowed',
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
		),
	) );
}

return $settings;
