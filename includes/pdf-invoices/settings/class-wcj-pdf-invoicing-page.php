<?php
/**
 * WooCommerce Jetpack PDF Invoicing Page
 *
 * The WooCommerce Jetpack PDF Invoicing Page class.
 *
 * @version 2.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Page' ) ) :

class WCJ_PDF_Invoicing_Page extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_page';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Page Settings', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.0
	 */
	function get_settings() {
		$settings = array();
		$page_formats = array();
		for ( $i = 1; $i < 8; $i++ ) {
			$page_formats[ 'A' . $i ] = 'A' . $i;
		}
		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			$settings[] = array(
				'title'    => strtoupper( $invoice_type['desc'] ),
				'type'     => 'title',
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
			);
			$settings[] = array(
				'title'    => __( 'Page Orientation', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_orientation',
				'default'  => 'P',
				'type'     => 'select',
				'options'  => array(
					'P' => __( 'Portrait', 'woocommerce-jetpack' ),
					'L' => __( 'Landscape', 'woocommerce-jetpack' ),
				),
			);
			$settings[] = array(
				'title'    => __( 'Page Format', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format',
				'default'  => 'A4',
				'type'     => 'select',
				'options'  => $page_formats,
			);
			$settings[] = array(
				'title'    => __( 'Margin Left', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_left',
				'default'  => 15, // PDF_MARGIN_LEFT,
				'type'     => 'number',
			);
			$settings[] = array(
				'title'    => __( 'Margin Right', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_right',
				'default'  => 15, // PDF_MARGIN_RIGHT,
				'type'     => 'number',
			);
			$settings[] = array(
				'title'    => __( 'Margin Top', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_top',
				'default'  => 27, // PDF_MARGIN_TOP,
				'type'     => 'number',
			);
			$settings[] = array(
				'title'    => __( 'Margin Bottom', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_bottom',
				'default'  => 0, // PDF_MARGIN_BOTTOM,
				'type'     => 'number',
			);
			$settings[] = array(
				'type'     => 'sectionend',
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
			);
		}
		return $settings;
	}
}

endif;

return new WCJ_PDF_Invoicing_Page();
