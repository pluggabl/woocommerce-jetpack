<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Templates
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Templates' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Templates.
		 *
		 * @version 7.1.6
		 */
	class WCJ_PDF_Invoicing_Templates extends WCJ_Module {

		/**
		 * The module default_template
		 *
		 * @var array
		 */
		public $default_template = array();

		/**
		 * Constructor.
		 *
		 * @version 2.3.7
		 */
		public function __construct() {
			$this->id         = 'pdf_invoicing_templates';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'Templates', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );
		}

		/**
		 * Get_default_template.
		 *
		 * @version 5.6.8
		 * @since   3.1.0
		 * @param int $invoice_type_id Get invoice ID.
		 */
		public function get_default_template( $invoice_type_id ) {
			if ( ! isset( $this->default_template[ $invoice_type_id ] ) ) {
				$default_template_filename = ( false === strpos( $invoice_type_id, 'custom_doc_' ) ? $invoice_type_id : 'custom_doc' );
				$default_template_filename = wcj_free_plugin_path() . '/includes/settings/pdf-invoicing/wcj-content-template-' . $default_template_filename . '.html';
				if ( file_exists( $default_template_filename ) ) {
					ob_start();
					include $default_template_filename;
					$this->default_template[ $invoice_type_id ] = ob_get_clean();
					if ( false !== strpos( $invoice_type_id, 'custom_doc' ) ) {
						$custom_doc_nr                              = ( 'custom_doc' === $invoice_type_id ) ? '1' : str_replace( 'custom_doc_', '', $invoice_type_id );
						$this->default_template[ $invoice_type_id ] = str_replace(
							'[wcj_custom_doc_number]',
							'[wcj_custom_doc_number doc_nr="' . $custom_doc_nr . '"]',
							$this->default_template[ $invoice_type_id ]
						);
						$this->default_template[ $invoice_type_id ] = str_replace(
							'[wcj_custom_doc_date]',
							'[wcj_custom_doc_date doc_nr="' . $custom_doc_nr . '"]',
							$this->default_template[ $invoice_type_id ]
						);
					}
				} else {
					$this->default_template[ $invoice_type_id ] = '';
				}
			}
			return $this->default_template[ $invoice_type_id ];
		}

	}

endif;

return new WCJ_PDF_Invoicing_Templates();
