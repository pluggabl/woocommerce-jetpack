<?php
/**
 * Booster for WooCommerce - Settings - Templates
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
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
		),
		array(
			'title'    => __( 'HTML Template', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_template',
			'default'  => $this->get_default_template( $invoice_type['id'] ),
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
