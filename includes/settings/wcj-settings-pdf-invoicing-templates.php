<?php
/**
 * Booster for WooCommerce - Settings - Templates
 *
 * @version 7.2.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types() );
$tab_ids       = array();
foreach ( $invoice_types as $invoice_type ) {
	$tab_ids[ 'pdf_invoicing_templates_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$tip = sprintf(
					/* translators: %s: translators Added */
	__( 'For the list of available shortcodes, please visit %s.', 'woocommerce-jetpack' ),
	'<a target="_blank" href="https://booster.io/category/shortcodes/?utm_source=shortcodes_list&utm_medium=module_button&utm_campaign=booster_documentation">' .
		'https://booster.io/category/shortcodes/' .
	'</a>'
);

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Templates', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing: Templates Settings' ),
		'icon'              => 'pr-sm-icn.png',
		'module_reset_link' => '<a style="width:auto;" onclick="return confirm(\'' . __( 'Are you sure? This will reset module to default settings.', 'woocommerce-jetpack' ) . '\')" class="wcj_manage_settting_btn wcj_tab_end_save_btn" href="' . esc_url(
			add_query_arg(
				array(
					'wcj_reset_settings' => $this->id,
					'wcj_reset_settings-' . $this->id . '-nonce' => wp_create_nonce( 'wcj_reset_settings' ),
				)
			)
		) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
	),
	array(
		'id'   => 'pdf_invoicing_templates_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_templates_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_templates_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
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
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_templates_options',
			),
			array(
				'id'   => 'pdf_invoicing_templates_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
