<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Footer
 *
 * @version 3.2.1
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
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
		),
		array(
			'title'    => __( 'Enable Footer', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_enabled',
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'title'    => __( 'Footer Text', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text',
			'default'  => __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ),
			'type'     => 'textarea',
			'css'      => 'width:100%;height:165px;',
			'desc'     => __( 'You can use HTML here, as well as any WordPress shortcodes.', 'woocommerce-jetpack' ) . ' ' .
				wcj_message_replaced_values( array( '%page_number%', '%total_pages%' ) ),
		),
		array(
			'title'    => __( 'Footer Text Color', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text_color',
			'default'  => '#cccccc',
			'type'     => 'color',
			'css'      => 'width:6em;',
		),
		array(
			'title'    => __( 'Footer Line Color', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_line_color',
			'default'  => '#cccccc',
			'type'     => 'color',
			'css'      => 'width:6em;',
		),
		array(
			'title'    => __( 'Footer Margin', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_footer',
			'default'  => 10, // PDF_MARGIN_FOOTER
			'type'     => 'number',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
		),
	) );
}
return $settings;
