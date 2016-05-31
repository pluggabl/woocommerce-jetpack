<?php
/**
 * WooCommerce Jetpack PDF Invoices Numbering
 *
 * The WooCommerce Jetpack PDF Invoices Numbering class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Numbering' ) ) :

class WCJ_PDF_Invoicing_Numbering extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 */
	public function __construct() {
		$this->id         = 'pdf_invoicing_numbering';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Numbering', 'woocommerce-jetpack' );
		parent::__construct( 'submodule' );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 */
	function get_settings() {
		$settings = array();
		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => strtoupper( $invoice_type['desc'] ),
					'type'     => 'title',
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
				),
				array(
					'title'    => __( 'Sequential', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_sequential_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'title'    => __( 'Counter', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter',
					'default'  => 1,
					'type'     => 'number',
				),
				array(
					'title'    => __( 'Counter Width', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter_width',
					'default'  => 0,
					'type'     => 'number',
				),
				array(
					'title'    => __( 'Prefix', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_prefix',
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px',
				),
				array(
					'title'    => __( 'Suffix', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_suffix',
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:300px',
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
				),
			) );
		}
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_PDF_Invoicing_Numbering();
