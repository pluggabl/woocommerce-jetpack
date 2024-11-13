<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Footer
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
	$tab_ids[ 'pdf_invoicing_footer_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Footer', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing: Footer Settings' ),
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
		'id'   => 'pdf_invoicing_footer_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_footer_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_footer_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
			),
			array(
				'title'   => __( 'Enable Footer', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Footer Text', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text',
				'default' => __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ),
				'type'    => 'textarea',
				'css'     => 'width:100%;height:165px;',
				'desc'    => __( 'You can use HTML here, as well as any WordPress shortcodes.', 'woocommerce-jetpack' ) . ' ' .
					wcj_message_replaced_values( array( '%page_number%', '%total_pages%' ) ),
			),
			array(
				'title'   => __( 'Footer Text Color', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text_color',
				'default' => '#cccccc',
				'type'    => 'color',
				'css'     => 'width:6em;',
			),
			array(
				'title'   => __( 'Footer Line Color', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_line_color',
				'default' => '#cccccc',
				'type'    => 'color',
				'css'     => 'width:6em;',
			),
			array(
				'title'   => __( 'Footer Margin', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_footer',
				'default' => 10, // PDF_MARGIN_FOOTER.
				'type'    => 'number',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
			),
			array(
				'id'   => 'pdf_invoicing_footer_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
