<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Display
 *
 * @version 7.2.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) add "Save all settings" buttons to each document type
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types() );
$tab_ids       = array();
foreach ( $invoice_types as $invoice_type ) {
	$tab_ids[ 'pdf_invoicing_display_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Display & Misc.', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing : Display & Misc. Settings' ),
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
		'id'   => 'pdf_invoicing_display_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_display_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$document_number_shortode = ( isset( $invoice_type['is_custom_doc'] ) && true === $invoice_type['is_custom_doc'] ?
		'[wcj_custom_doc_number doc_nr="' . $invoice_type['custom_doc_nr'] . '"]' : '[wcj_' . $invoice_type['id'] . '_number]' );
	$settings                 = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_display_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
			),
			array(
				'title'   => __( 'Admin Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_title',
				'default' => $invoice_type['title'],
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'title'   => __( 'Admin\'s "Orders" Page', 'woocommerce-jetpack' ),
				'desc'    => __( 'Add Column', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => '',
				'desc'    => __( 'Column Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text',
				'default' => $invoice_type['title'],
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'desc'          => __( 'Add "View" button', 'woocommerce-jetpack' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'          => __( 'Add "Create" button', 'woocommerce-jetpack' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Add "Delete" button', 'woocommerce-jetpack' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( '"Create" button requires confirmation', 'woocommerce-jetpack' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( '"Delete" button requires confirmation', 'woocommerce-jetpack' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'title'   => __( 'Thank You Page', 'woocommerce-jetpack' ),
				'desc'    => __( 'Add link', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_on_thankyou_page',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'desc'    => __( 'Link Text', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_link_text',
				'default' => $invoice_type['title'],
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'desc'    => __( 'HTML Template', 'woocommerce-jetpack' ) . '. ' . wcj_message_replaced_values( array( '%link%' ) ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_template',
				/* translators: %s: translators Added */
				'default' => '<p><strong>' . sprintf( __( 'Your %s:', 'woocommerce-jetpack' ), $invoice_type['title'] ) . ' </strong> %link%</p>',
				'type'    => 'textarea',
				'class'   => 'widefat',
			),
			array(
				'title'   => __( 'Customer\'s "My Account" Page', 'woocommerce-jetpack' ),
				'desc'    => __( 'Add link', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'desc'    => __( 'Link Text', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_link_text',
				'default' => $invoice_type['title'],
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'title'    => __( 'Enable "Save as"', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Enable "save as" pdf instead of view pdf in browser', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'   => __( 'PDF File Name', 'woocommerce-jetpack' ),
				'desc'    => sprintf(
									/* translators: %s: translators Added */
					__( 'Enter file name for PDF documents. You can use shortcodes here, e.g. %s.', 'woocommerce-jetpack' ),
					'<code>' . $document_number_shortode . '</code>'
				),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_file_name',
				'default' => $document_number_shortode,
				'type'    => 'text',
				'class'   => 'widefat',
			),
			array(
				'title'   => __( 'Allowed User Roles', 'woocommerce-jetpack' ),
				'desc'    => __( 'If set to empty - Administrator role will be used.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_roles',
				'default' => array( 'administrator', 'shop_manager' ),
				'type'    => 'multiselect',
				'class'   => 'chosen_select',
				'options' => wcj_get_user_roles_options(),
			),
			array(
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'pdf_invoicing_display_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
