<?php
/**
 * Booster for WooCommerce - Settings - Templates
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$default_template_filename = ( false === strpos( $invoice_type['id'], 'custom_doc_' ) ? $invoice_type['id'] : 'custom_doc' );
	$default_template_filename = wcj_plugin_path() . '/includes/settings/pdf-invoicing/wcj-content-template-' . $default_template_filename . '.php';
	if ( file_exists( $default_template_filename ) ) {
		ob_start();
		include( $default_template_filename );
		$default_template = ob_get_clean();
	} else {
		$default_template = '';
	}
	if ( false !== strpos( $invoice_type['id'], 'custom_doc' ) ) {
		$custom_doc_nr = ( 'custom_doc' === $invoice_type['id'] ) ? '1' : str_replace( 'custom_doc_', '', $invoice_type['id'] );
		$default_template = str_replace( '[wcj_custom_doc_number]', '[wcj_custom_doc_number doc_nr="' . $custom_doc_nr . '"]', $default_template );
		$default_template = str_replace( '[wcj_custom_doc_date]',   '[wcj_custom_doc_date doc_nr="'   . $custom_doc_nr . '"]', $default_template );
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => strtoupper( $invoice_type['desc'] ),
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
		),
		array(
			'title'    => __( 'HTML Template', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_template',
			'default'  => $default_template,
			'type'     => 'textarea',
			'css'      => 'width:66%;min-width:300px;height:500px;',
		),
		array(
			'title'    => __( 'Save all templates', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_template_save_all',
			'type'     => 'wcj_save_settings_button',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Available Shortcodes', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf(
			__( 'For the list of available shortcodes, please visit %s.', 'woocommerce-jetpack' ),
			'<a target="_blank" href="https://booster.io/category/shortcodes/?utm_source=shortcodes_list&utm_medium=module_button&utm_campaign=booster_documentation">https://booster.io/category/shortcodes/</a>'
		),
		'id'       => 'wcj_invoicing_templates_desc',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_invoicing_templates_desc',
	),
) );
return $settings;
