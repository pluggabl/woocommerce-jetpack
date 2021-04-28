<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Advanced
 *
 * @version 5.4.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) create "Tools (Options)" submodule
 * @todo    (maybe) remove `tcpdf_default` option in `wcj_invoicing_general_header_images_path`
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$is_full_fonts = wcj_check_and_maybe_download_tcpdf_fonts();
if ( 'yes' === wcj_get_option( 'wcj_invoicing_fonts_manager_do_not_download', 'no' ) ) {
	$fonts_manager_desc = __( 'Fonts download is disabled.', 'woocommerce-jetpack' );
} else {
	if ( $is_full_fonts ) {
		$fonts_manager_desc = __( 'Fonts are up to date.', 'woocommerce-jetpack' ) . ' ' . sprintf(
			__( 'Latest successful download or version check was on %s.', 'woocommerce-jetpack' ),
			date( 'Y-m-d H:i:s', wcj_get_option( 'wcj_invoicing_fonts_version_timestamp', null ) )
		);
	} else {
		$fonts_manager_desc = __( 'Fonts are NOT up to date. Please try downloading by pressing the button below.', 'woocommerce-jetpack' );
		if ( null != wcj_get_option( 'wcj_invoicing_fonts_version', null ) ) {
			$fonts_manager_desc .= ' ' . sprintf(
				__( 'Latest successful downloaded version is %s.', 'woocommerce-jetpack' ),
				get_option( 'wcj_invoicing_fonts_version', null )
			);
		}
		if ( null != wcj_get_option( 'wcj_invoicing_fonts_version_timestamp', null ) ) {
			$fonts_manager_desc .= ' ' . sprintf(
				__( 'Latest download executed on %s.', 'woocommerce-jetpack' ),
				date( 'Y-m-d H:i:s', wcj_get_option( 'wcj_invoicing_fonts_version_timestamp', null ) )
			);
		}
	}
}

return array(
	array(
		'type'     => 'title',
		'title'    => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_advanced_options',
	),
	array(
		'title'    => __( 'Hide Disabled Docs Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_hide_disabled_docs_settings',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => __( 'This option will hide the disabled Documents sections. For ex. If you have only <strong>Invoice</strong> document is enabled and you don\'t want another document setting in this module or anywhere in admin.', 'woocommerce-jetpack' ),
	),
	array(
		'title'    => __( 'Replace Admin Order Search with Invoice Search', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_admin_search_by_invoice',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Default Images Directory', 'woocommerce-jetpack' ),
		'desc'     => '<br>' . __( 'Default images directory in TCPDF library (K_PATH_IMAGES).', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Try changing this if you have issues displaying images in page background or header.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_general_header_images_path', // mislabelled, should be `wcj_invoicing_general_images_path`
		'default'  => 'document_root',
		'type'     => 'select',
		'options'  => array(
			'empty'         => __( 'Empty', 'woocommerce-jetpack' ),
			'tcpdf_default' => __( 'TCPDF Default', 'woocommerce-jetpack' ),
			'abspath'       => __( 'ABSPATH', 'woocommerce-jetpack' ),       // . ': ' . ABSPATH,
			'document_root' => __( 'DOCUMENT_ROOT', 'woocommerce-jetpack' ), // . ': ' . $_SERVER['DOCUMENT_ROOT'],
		),
	),
	array(
		'title'    => __( 'Temp Directory', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to use the default temp directory.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_general_tmp_dir',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Disable Saving PDFs in Temp Directory', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Please note that attaching invoices to emails and generating invoices report zip will stop working, if you enable this checkbox.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_save_sys_temp_dir', // mislabelled, should be `wcj_invoicing_advanced_disable_save_sys_temp_dir`
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Prevent Output Buffer', 'woocommerce-jetpack' ),
		'desc' => __( 'Returns the content of output buffering instead of displaying it', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_output_buffer',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Internal Encoding', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Sets internal character encoding.', 'woocommerce-jetpack' ).'<br />'.__( 'e.g: UTF-8, iso-8859-1', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_mb_internal_encoding',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'WooCommerce Extra Product Options on Item Name', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf( __( 'Displays some info from <a href="%s" target="_blank">WooCommerce Extra Product Options</a> on <code>item_name</code> parameter from <code>wcj_order_items_table</code>.', 'woocommerce-jetpack' ), 'https://codecanyon.net/item/woocommerce-extra-product-options/7908619' ).'<br />'.__( 'Probably you\'ll want it disabled and use the <code>item_meta</code> parameter instead.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_wcepo_enable',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Item Meta Separator', 'woocommerce-jetpack' ),
		'desc'     => __( 'Separator used on <code>item_meta</code> parameter from <code>wcj_order_items_table</code>', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_item_meta_separator',
		'wcj_raw'  => true,
		'default'  => ', ',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_pdf_invoicing_advanced_options',
	),
	array(
		'type'     => 'title',
		'title'    => __( 'Item Name as Product Title', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replaces <code>item_name</code> by product title when using <code>[wcj_order_items_table columns="item_name"]</code>.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_advanced_item_name_as_prod_title',
	),
	array(
		'title'    => __( 'Enable', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_advanced_item_name_as_prod_title_enable',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Translate WPML Title', 'woocommerce-jetpack' ),
		'desc'              => empty( $message = apply_filters( 'booster_message', '', 'desc' ) ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => __( 'Tries to translate the product title to the current WPML language.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_pdf_invoicing_advanced_item_name_as_prod_title_wpml',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_pdf_invoicing_advanced_item_name_as_prod_title',
	),
	array(
		'title'    => __( 'Fonts Manager', 'woocommerce-jetpack' ),
		'desc'     => $fonts_manager_desc,
		'type'     => 'title',
		'id'       => 'wcj_invoicing_fonts_manager_styling_options',
	),
	array(
		'title'    => __( 'Actions', 'woocommerce-jetpack' ),
		'type'     => 'custom_link',
		'link'     => '<a class="button" href="' . add_query_arg( 'wcj_download_fonts', '1' ) . '">' .
			( $is_full_fonts ? __( 'Re-download', 'woocommerce-jetpack' ) : __( 'Download', 'woocommerce-jetpack' ) )
			. '</a>',
		'id'       => 'wcj_invoicing_fonts_manager_styling_option',
	),
	array(
		'title'    => __( 'Disable Fonts Download', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'default'  => 'no',
		'id'       => 'wcj_invoicing_fonts_manager_do_not_download',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_invoicing_fonts_manager_styling_options',
	),
	array(
		'title'    => __( 'General Display Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_invoicing_general_display_options',
	),
	array(
		'title'    => __( 'Add PDF Invoices Meta Box to Admin Edit Order Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_add_order_meta_box',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Open docs in new window', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_order_meta_box_open_in_new_window',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Add editable numbers and dates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_add_order_meta_box_numbering',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_invoicing_general_display_options',
	),
	array(
		'title'    => __( 'Report Tool Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_pdf_invoicing_report_tool_options',
	),
	array(
		'title'    => __( 'Reports Filename', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%site%', '%invoice_type%', '%year%', '%month%' ) ),
		'id'       => 'wcj_pdf_invoicing_report_tool_filename',
		'default'  => '%site%-%invoice_type%-%year%_%month%',
		'type'     => 'text',
		'class'    => 'widefat',
	),
	array(
		'title'    => __( 'Report Columns', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to show all columns.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_columns',
		'default'  => $this->get_report_default_columns(),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $this->get_report_columns(),
	),
	array(
		'title'    => __( 'Tax Percent Precision', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_tax_percent_precision',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'CSV Separator', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_separator',
		'default'  => ';',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'CSV UTF-8 BOM', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_add_utf_8_bom',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Replace Periods with Commas in CSV Data', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_replace_periods_w_commas',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_pdf_invoicing_report_tool_options',
	),
);
