<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Styling
 *
 * @version 3.1.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$is_full_fonts = wcj_check_and_maybe_download_tcpdf_fonts();
$settings      = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	// Font family
	$font_family_option = ( $is_full_fonts ?
		array(
			'title'    => __( 'Font Family', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family',
			'default'  => 'helvetica',
			'type'     => 'select',
			'options'  => apply_filters( 'wcj_pdf_invoicing_fonts', array(
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
			) ),
		) :
		array(
			'title'    => __( 'Font Family', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family_fallback',
			'default'  => 'helvetica',
			'type'     => 'select',
			'options'  => array(
				'courier'           => 'Courier',
				'helvetica'         => 'Helvetica',
				'times'             => 'Times',
				'stsongstdlight'    => 'STSong Light (Simp. Chinese)',
			),
		)
	);
	$settings = array_merge( $settings, array(
		array(
			'title'    => $invoice_type['title'],
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
		),
		array(
			'title'    => __( 'CSS', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_css',
			'default'  => $this->get_default_css_template( $invoice_type['id'] ),
			'type'     => 'textarea',
			'css'      => 'width:66%;min-width:300px;height:200px;',
		),
	),
	array( $font_family_option ),
	array(
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
if ( 'yes' === get_option( 'wcj_invoicing_fonts_manager_do_not_download', 'no' ) ) {
	$fonts_manager_desc = __( 'Fonts download is disabled.', 'woocommerce-jetpack' );
} else {
	if ( $is_full_fonts ) {
		$fonts_manager_desc = __( 'Fonts are up to date.', 'woocommerce-jetpack' ) . ' ' . sprintf(
			__( 'Latest successful download or version check was on %s.', 'woocommerce-jetpack' ),
			date( 'Y-m-d H:i:s', get_option( 'wcj_invoicing_fonts_version_timestamp', null ) )
		);
	} else {
		$fonts_manager_desc = __( 'Fonts are NOT up to date. Please try downloading by pressing the button below.', 'woocommerce-jetpack' );
		if ( null != get_option( 'wcj_invoicing_fonts_version', null ) ) {
			$fonts_manager_desc .= ' ' . sprintf(
				__( 'Latest successful downloaded version is %s.', 'woocommerce-jetpack' ),
				get_option( 'wcj_invoicing_fonts_version', null )
			);
		}
		if ( null != get_option( 'wcj_invoicing_fonts_version_timestamp', null ) ) {
			$fonts_manager_desc .= ' ' . sprintf(
				__( 'Latest download executed on %s.', 'woocommerce-jetpack' ),
				date( 'Y-m-d H:i:s', get_option( 'wcj_invoicing_fonts_version_timestamp', null ) )
			);
		}
	}
}
// $hook_time = date( 'Y-m-d H:i:s', get_option( 'wcj_download_tcpdf_fonts_hook_timestamp', null ) ); // for debug
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Fonts Manager', 'woocommerce-jetpack' ),
		'desc'     => $fonts_manager_desc,
		'type'     => 'title',
		'id'       => 'wcj_invoicing_fonts_manager_styling_options',
	),
	array(
		'title'    => __( 'Actions', 'woocommerce-jetpack' ),
		'type'     => 'custom_link',
		'link'     => '<a class="button-primary" href="' . add_query_arg( 'wcj_download_fonts', '1' ) . '">' .
			( $is_full_fonts ? __( 'Re-download', 'woocommerce-jetpack' ) : __( 'Download', 'woocommerce-jetpack' ) )
			. '</a>',
		'id'       => 'wcj_invoicing_fonts_manager_styling_option',
	),
	array(
		'title'    => __( 'Disable Fonts Download', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'default'  => 'no',
		'id'       => 'wcj_invoicing_fonts_manager_do_not_download',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_invoicing_fonts_manager_styling_options',
	),
) );
return $settings;
