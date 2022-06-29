<?php
/**
 * Booster for WooCommerce - Settings - Templates
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$tip = sprintf(
				/* translators: %s: translators Added */
	__( 'For the list of available shortcodes, please visit %s.', 'woocommerce-jetpack' ),
	'<a target="_blank" href="https://booster.io/category/shortcodes/?utm_source=shortcodes_list&utm_medium=module_button&utm_campaign=booster_documentation">' .
		'https://booster.io/category/shortcodes/' .
	'</a>'
);

$settings      = array();
$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
			),
			array(
				'title'   => __( 'HTML Template', 'woocommerce-jetpack' ),
				'desc'    => $tip,
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_template',
				'default' => $this->get_default_template( $invoice_type['id'] ),
				'type'    => 'textarea',
				'css'     => 'width:100%;height:500px;',
			),
			array(
				'title' => __( 'Save all templates', 'woocommerce-jetpack' ),
				'id'    => 'wcj_invoicing_template_save_all',
				'type'  => 'wcj_save_settings_button',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
			),
		)
	);
}
return $settings;
