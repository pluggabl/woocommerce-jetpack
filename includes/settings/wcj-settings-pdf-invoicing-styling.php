<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Styling
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_full_fonts = wcj_check_and_maybe_download_tcpdf_fonts();
$settings      = array();
$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	// Font family.
	$font_family_option = ( $is_full_fonts ?
		array(
			'title'   => __( 'Font Family', 'woocommerce-jetpack' ),
			'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family',
			'default' => 'helvetica',
			'type'    => 'select',
			'options' => apply_filters(
				'wcj_pdf_invoicing_fonts',
				array(
					'courier'           => 'Courier',
					'helvetica'         => 'Helvetica',
					'times'             => 'Times',
					'dejavusans'        => 'DejaVu Sans (Unicode)',
					'droidsansfallback' => 'Droid Sans Fallback (Unicode)',
					'angsanaupc'        => 'AngsanaUPC (Unicode)',
					'cordiaupc'         => 'CordiaUPC (Unicode)',
					'thsarabun'         => 'THSarabunPSK (Unicode)',
					'stsongstdlight'    => 'STSong Light (Simp. Chinese)',
					'cid0ct'            => 'cid0ct (Chinese Traditional)',
				)
			),
		) :
		array(
			'title'   => __( 'Font Family', 'woocommerce-jetpack' ),
			'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family_fallback',
			'default' => 'helvetica',
			'type'    => 'select',
			'options' => array(
				'courier'        => 'Courier',
				'helvetica'      => 'Helvetica',
				'times'          => 'Times',
				'stsongstdlight' => 'STSong Light (Simp. Chinese)',
			),
		)
	);
	$settings           = array_merge(
		$settings,
		array(
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
			),
			array(
				'title'   => __( 'CSS', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_css',
				'default' => $this->get_default_css_template( $invoice_type['id'] ),
				'type'    => 'textarea',
				'css'     => 'width:100%;height:500px;',
			),
		),
		array( $font_family_option ),
		array(
			array(
				'title'   => __( 'Font Size', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_size',
				'default' => 8,
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Make Font Shadowed', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_shadowed',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
			),
		)
	);
}
return $settings;
