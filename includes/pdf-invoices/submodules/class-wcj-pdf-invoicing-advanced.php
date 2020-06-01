<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Advanced
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Advanced' ) ) :

class WCJ_PDF_Invoicing_Advanced extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_advanced';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Advanced', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
	}

	/**
	 * get_report_default_columns.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function get_report_default_columns() {
		return array_keys( array(
			'document_number'                    => __( 'Document Number', 'woocommerce-jetpack' ),
			'document_date'                      => __( 'Document Date', 'woocommerce-jetpack' ),
			'order_id'                           => __( 'Order ID', 'woocommerce-jetpack' ),
			'customer_country'                   => __( 'Customer Country', 'woocommerce-jetpack' ),
			'customer_vat_id'                    => __( 'Customer VAT ID', 'woocommerce-jetpack' ),
			'tax_percent'                        => __( 'Tax %', 'woocommerce-jetpack' ),
			'order_total_tax_excluding'          => __( 'Order Total Excl. Tax', 'woocommerce-jetpack' ),
			'order_taxes'                        => __( 'Order Taxes', 'woocommerce-jetpack' ),
			'order_total'                        => __( 'Order Total', 'woocommerce-jetpack' ),
			'order_currency'                     => __( 'Order Currency', 'woocommerce-jetpack' ),
			'payment_gateway'                    => __( 'Payment Gateway', 'woocommerce-jetpack' ),
			'refunds'                            => __( 'Refunds', 'woocommerce-jetpack' ),
		) );
	}

	/**
	 * get_report_columns.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 * @todo    (maybe) `order_discount_tax`
	 */
	function get_report_columns() {
		return array(
			'document_number'                    => __( 'Document Number', 'woocommerce-jetpack' ),
			'document_date'                      => __( 'Document Date', 'woocommerce-jetpack' ),
			'order_id'                           => __( 'Order ID', 'woocommerce-jetpack' ),
			'customer_country'                   => __( 'Customer Country', 'woocommerce-jetpack' ),
			'customer_vat_id'                    => __( 'Customer VAT ID', 'woocommerce-jetpack' ),
			'tax_percent'                        => __( 'Tax %', 'woocommerce-jetpack' ),
			'order_total_tax_excluding'          => __( 'Order Total Excl. Tax', 'woocommerce-jetpack' ),
			'order_taxes'                        => __( 'Order Taxes', 'woocommerce-jetpack' ),
			'order_cart_total_excl_tax'          => __( 'Cart Total Excl. Tax', 'woocommerce-jetpack' ),
			'order_cart_tax'                     => __( 'Cart Tax', 'woocommerce-jetpack' ),
			'order_cart_tax_percent'             => __( 'Cart Tax %', 'woocommerce-jetpack' ),
			'order_shipping_total_excl_tax'      => __( 'Shipping Total Excl. Tax', 'woocommerce-jetpack' ),
			'order_shipping_tax'                 => __( 'Shipping Tax', 'woocommerce-jetpack' ),
			'order_shipping_tax_percent'         => __( 'Shipping Tax %', 'woocommerce-jetpack' ),
			'order_total'                        => __( 'Order Total', 'woocommerce-jetpack' ),
			'order_currency'                     => __( 'Order Currency', 'woocommerce-jetpack' ),
			'payment_gateway'                    => __( 'Payment Gateway', 'woocommerce-jetpack' ),
			'refunds'                            => __( 'Refunds', 'woocommerce-jetpack' ),
		);
	}

}

endif;

return new WCJ_PDF_Invoicing_Advanced();
