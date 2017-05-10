<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Numbering
 *
 * @version 2.8.0
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
	function __construct() {
		$this->id         = 'pdf_invoicing_numbering';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Numbering', 'woocommerce-jetpack' );
		parent::__construct( 'submodule' );
	}

}

endif;

return new WCJ_PDF_Invoicing_Numbering();
