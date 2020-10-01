<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Numbering
 *
 * @version 3.2.3
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Numbering' ) ) :

class WCJ_PDF_Invoicing_Numbering extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.3
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_numbering';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Numbering', 'woocommerce-jetpack' );
		parent::__construct( 'submodule' );

		if ( 'yes' === wcj_get_option( 'wcj_invoicing_admin_search_by_invoice', 'no' ) ) {
			add_action( 'pre_get_posts', array( $this, 'search_orders_by_invoice_number' ) );
		}
	}

	/**
	 * search_orders_by_invoice_number.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 */
	function search_orders_by_invoice_number( $query ) {
		if (
			! is_admin() ||
			! isset( $query->query ) ||
			! isset( $query->query['s'] ) ||
			false === is_numeric( $query->query['s'] ) ||
			0 == $query->query['s'] ||
			'shop_order' !== $query->query['post_type'] ||
			! $query->query_vars['shop_order_search']
		) {
			return;
		}
		$invoice_number = $query->query['s'];
		$query->query_vars['post__in'] = array();
		$query->query['s'] = '';
		$query->set( 'meta_key', '_wcj_invoicing_invoice_number_id' );
		$query->set( 'meta_value', $invoice_number );
	}

}

endif;

return new WCJ_PDF_Invoicing_Numbering();
