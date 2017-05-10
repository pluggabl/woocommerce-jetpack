<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Templates
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Templates' ) ) :

class WCJ_PDF_Invoicing_Templates extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.7
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_templates';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Templates', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
	}

}

endif;

return new WCJ_PDF_Invoicing_Templates();
