<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Numbering
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
	$tab_ids[ 'pdf_invoicing_numbering_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Numbering', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing: Numbering Settings' ),
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
		'id'   => 'pdf_invoicing_numbering_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_numbering_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_numbering_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
			),
			array(
				'title'   => __( 'Sequential', 'woocommerce-jetpack' ),
				'desc'    => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_sequential_enabled',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Counter', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter',
				'default' => 1,
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Counter Width', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter_width',
				'default' => 0,
				'type'    => 'number',
				'desc'    => __(
					'Counter Width is the min width of the document length.<br>
               For Ex. If you set 2, It will show the counter in 2 digit (15). If you set 3, It will show counter in 3 digit (015),',
					'woocommerce-jetpack'
				),
			),
			array(
				'title'   => __( 'Prefix', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_prefix',
				'default' => '',
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Suffix', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_suffix',
				'default' => '',
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Template', 'woocommerce-jetpack' ),
				'desc'    => '<br>' . wcj_message_replaced_values( array( '%prefix%', '%counter%', '%suffix%' ) ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_template',
				'default' => '%prefix%%counter%%suffix%',
				'type'    => 'text',
			),
			array(
				'title' => __( 'Bulk Invoice Number DESC Order', 'woocommerce-jetpack' ),
				'desc'  => 'If you want generate invoice number sequence in DESC order sequence please enable this otherwise leave it uncheck',
				'id'    => 'wcj_invoicing_desc_order_sequence',
				'type'  => 'checkbox',
			),
			array(
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'pdf_invoicing_numbering_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
