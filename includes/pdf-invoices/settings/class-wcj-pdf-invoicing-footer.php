<?php
/**
 * WooCommerce Jetpack PDF Invoicing Footer
 *
 * The WooCommerce Jetpack PDF Invoicing Footer class.
 *
 * @version 2.4.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Footer' ) ) :

class WCJ_PDF_Invoicing_Footer extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'pdf_invoicing_footer';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Footer', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.7
	 */
	function get_settings() {
		$settings = array();
		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			$settings[] = array(
				'title' => strtoupper( $invoice_type['desc'] ),
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
			);
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Enable Footer', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_enabled',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'title'    => __( 'Footer Text', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text',
//					'default'  => __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ),
					'default'  => 'Page %page_number% / %total_pages%',
					'type'     => 'textarea',
					'css'      => 'width:66%;min-width:300px;height:165px;',
					'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
					'desc_tip' => __( 'You can use HTML here, as well as any WordPress shortcodes. There is two more predefined values you can use: %page_number% and %total_pages%.', 'woocommerce-jetpack' ),
					'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				),
				array(
					'title'    => __( 'Footer Text Color', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_text_color',
					'default'  => '#cccccc',
					'type'     => 'color',
					'css'      => 'width:6em;',
				),
				array(
					'title'    => __( 'Footer Line Color', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_line_color',
					'default'  => '#cccccc',
					'type'     => 'color',
					'css'      => 'width:6em;',
				),
				array(
					'title'    => __( 'Footer Margin', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_footer',
					'default'  => 10, // PDF_MARGIN_FOOTER
					'type'     => 'number',
				),
			) );
			$settings[] = array(
				'type'  => 'sectionend',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_footer_options',
			);
		}
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_PDF_Invoicing_Footer();
