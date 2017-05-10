<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Styling
 *
 * @version 2.8.0
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

}

endif;

return new WCJ_PDF_Invoicing_Styling();
