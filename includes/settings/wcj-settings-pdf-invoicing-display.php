<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Display
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    (maybe) add "Save all settings" buttons to each document type
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$document_number_shortode = ( isset( $invoice_type['is_custom_doc'] ) && true === $invoice_type['is_custom_doc'] ?
		'[wcj_custom_doc_number doc_nr="' . $invoice_type['custom_doc_nr'] . '"]' : '[wcj_' . $invoice_type['id'] . '_number]' );
	$settings = array_merge( $settings, array(
		array(
			'title'    => $invoice_type['title'],
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
		),
		array(
			'title'    => __( 'Admin Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_title',
			'default'  => $invoice_type['title'],
			'type'     => 'text',
			'class'    => 'widefat',
		),
		array(
			'title'    => __( 'Admin\'s "Orders" Page', 'woocommerce-jetpack' ),
			'desc'     => __( 'Add Column', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column',
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'title'    => '',
			'desc'     => __( 'Column Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text',
			'default'  => $invoice_type['title'],
			'type'     => 'text',
			'class'    => 'widefat',
		),
		/* array(
			'title'    => '',
			'desc'     => __( 'Create Button', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set empty to disable the button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_column_create_btn',
			'default'  => __( 'Create', 'woocommerce-jetpack' ),
			'type'     => 'text',
		),
		array(
			'title'    => '',
			'desc'     => __( 'Delete Button', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set empty to disable the button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_column_delete_btn',
			'default'  => __( 'Delete', 'woocommerce-jetpack' ),
			'type'     => 'text',
		), */
		array(
			'desc'     => __( 'Add "View" button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn',
			'default'  => 'no',
			'type'     => 'checkbox',
			'checkboxgroup' => 'start',
		),
		array(
			'desc'     => __( 'Add "Create" button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'checkboxgroup' => '',
		),
		array(
			'desc'     => __( 'Add "Delete" button', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'checkboxgroup' => '',
		),
		array(
			'desc'     => __( '"Create" button requires confirmation', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'checkboxgroup' => '',
		),
		array(
			'desc'     => __( '"Delete" button requires confirmation', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'checkboxgroup' => 'end',
		),
		array(
			'title'    => __( 'Customer\'s "My Account" Page', 'woocommerce-jetpack' ),
			'desc'     => __( 'Add link', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers',
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'Link Text', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_link_text',
			'default'  => $invoice_type['title'],
			'type'     => 'text',
			'class'    => 'widefat',
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
			'title'    => __( 'PDF File Name', 'woocommerce-jetpack' ),
			'desc'     => sprintf( __( 'Enter file name for PDF documents. You can use shortcodes here, e.g. %s.', 'woocommerce-jetpack' ),
				'<code>' . $document_number_shortode . '</code>' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_file_name',
			'default'  => $document_number_shortode,
			'type'     => 'text',
			'class'    => 'widefat',
		),
		array(
			'title'    => __( 'Allowed User Roles', 'woocommerce-jetpack' ),
			'desc'     => __( 'If set to empty - Administrator role will be used.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_roles',
			'default'  => array( 'administrator', 'shop_manager' ),
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'options'  => wcj_get_user_roles_options(),
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
		),
	) );
}
return $settings;
