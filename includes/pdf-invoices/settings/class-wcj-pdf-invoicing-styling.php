<?php
/**
 * WooCommerce Jetpack PDF Invoicing Styling
 *
 * The WooCommerce Jetpack PDF Invoicing Styling class.
 *
 * @version 2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Styling' ) ) :

class WCJ_PDF_Invoicing_Styling extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_styling';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Styling', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {

		$settings = array();

		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {

			$settings[] = array(
				'title'    => strtoupper( $invoice_type['desc'] ),
				'type'     => 'title',
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
			);

			ob_start();
			include( 'defaults/wcj-' . $invoice_type['id'] . '.css' );
			$default_template = ob_get_clean();

			$settings[] = array(
				'title'    => __( 'CSS', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_css',
				'default'  => $default_template,
				'type'     => 'textarea',
				'css'      => 'width:66%;min-width:300px;height:200px;',
			);

			$settings[] = array(
				'title'    => __( 'Font Family', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_family',
				'default'  => 'dejavusans',
				'type'     => 'select',
				'options'  => array(
					'dejavusans'        => 'DejaVu Sans',
					'courier'           => 'Courier',
					'helvetica'         => 'Helvetica',
					'times'             => 'Times',
					'droidsansfallback' => 'Droid Sans Fallback',
					'angsanaupc'        => 'AngsanaUPC',
					'cordiaupc'         => 'CordiaUPC',
					'thsarabun'         => 'THSarabunPSK',
				),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			);

			$settings[] = array(
				'title'    => __( 'Font Size', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_size',
				'default'  => 8,
				'type'     => 'number',
			);

			$settings[] = array(
				'title'    => __( 'Make Font Shadowed', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_general_font_shadowed',
				'default'  => 'no',
				'type'     => 'checkbox',
			);

			$settings[] = array(
				'type'     => 'sectionend',
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_styling_options',
			);
		}

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_PDF_Invoicing_Styling();
