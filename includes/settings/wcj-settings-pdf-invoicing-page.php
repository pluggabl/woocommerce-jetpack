<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Page Settings
 *
 * @version 4.7.1
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
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
			'default'  => 'A4', // PDF_PAGE_FORMAT
			'type'     => 'select',
			'options'  => array_replace( array( 'custom' => __( 'Custom', 'woocommerce-jetpack' ) ), $this->get_page_formats() ),
		),
		array(
			'desc'     => __( 'Custom: width (millimeters)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format_custom_width',
			'default'  => '0',
			'type'     => 'number',
			'custom_attributes' => array( 'min' => 0 ),
		),
		array(
			'desc'     => __( 'Custom: height (millimeters)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format_custom_height',
			'default'  => '0',
			'type'     => 'number',
			'custom_attributes' => array( 'min' => 0 ),
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
			'default'  => 10, // PDF_MARGIN_BOTTOM,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Background Image', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_background_image',
			'default'  => '',
			'type'     => 'text',
			'desc'     => sprintf( __( 'Enter a local URL to an image. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce-jetpack' ),
					admin_url( 'media-new.php' ) ) .
				wcj_get_invoicing_current_image_path_desc( 'wcj_invoicing_' . $invoice_type['id'] . '_background_image' ) . '<br>' .
				sprintf( __( 'If you are experiencing issues with displaying background image, please try setting different values for the "Advanced: Default Images Directory" option in %s.', 'woocommerce-jetpack' ),
					'<a target="_blank" href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing_advanced' ) . '">' .
						__( 'PDF Invoicing & Packing Slips > Advanced', 'woocommerce-jetpack' ) .
					'</a>' ),
			'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
			'class'    => 'widefat',
		),
		array(
			'title'    => __( 'Parse Background Image URL', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_background_image_parse',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Converts the Background Image URL to its local path.', 'woocommerce-jetpack' ) . '<br>' .__( 'If you are experiencing issues with displaying background image, please try to disable this option', 'woocommerce-jetpack' ),
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
		),
	) );
}
return $settings;
