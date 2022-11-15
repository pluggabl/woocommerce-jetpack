<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Header
 *
 * @version 5.6.8
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) add info on `<img>` in "Header Image" description
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings      = array();
$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_header_options',
			),
			array(
				'title'   => __( 'Enable Header', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Header Image', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_header_image',
				'default'  => '',
				'type'     => 'text',
				'desc'     => sprintf(
				/* translators: %s: translators Added */
					__( 'Enter a local URL to an image you want to show in the invoice\'s header. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce-jetpack' ),
					admin_url( 'media-new.php' )
				) .
					wcj_get_invoicing_current_image_path_desc( 'wcj_invoicing_' . $invoice_type['id'] . '_header_image' ) . '<br>' .
					sprintf(
				/* translators: %s: translators Added */
						__( 'If you are experiencing issues with displaying header image, please try setting different values for the "Advanced: Default Images Directory" option in %s.', 'woocommerce-jetpack' ),
						'<a target="_blank" href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=pdf_invoicing&section=pdf_invoicing_advanced' ) . '">' .
							__( 'PDF Invoicing & Packing Slips > Advanced', 'woocommerce-jetpack' ) .
						'</a>'
					),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
				'class'    => 'widefat',
			),
			array(
				'title'   => __( 'Header Image Width in mm', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_image_width_mm',
				'default' => 50,
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Header Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_title_text',
				'default' => $invoice_type['title'],
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'title'   => __( 'Header Text', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_text',
				'default' => __( 'Company Name', 'woocommerce-jetpack' ),
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'title'   => __( 'Header Text Color', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_text_color',
				'default' => '#cccccc',
				'type'    => 'color',
				'css'     => 'width:6em;',
			),
			array(
				'title'   => __( 'Header Line Color', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_header_line_color',
				'default' => '#cccccc',
				'type'    => 'color',
				'css'     => 'width:6em;',
			),
			array(
				'title'   => __( 'Header Margin', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_header',
				'default' => 10, // PDF_MARGIN_HEADER.
				'type'    => 'number',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_header_options',
			),
		)
	);
}
return $settings;
