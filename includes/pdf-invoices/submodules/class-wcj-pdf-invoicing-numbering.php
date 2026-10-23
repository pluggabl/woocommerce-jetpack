<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Numbering
 *
 * @version 3.2.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Numbering' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Numbering.
		 *
		 * @version 3.2.3
		 */
	class WCJ_PDF_Invoicing_Numbering extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.2.3
		 */
		public function __construct() {
			$this->id         = 'pdf_invoicing_numbering';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = 'Numbering';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Numbering', 'woocommerce-jetpack' );
				}
			);
			parent::__construct( 'submodule' );

			if ( 'yes' === wcj_get_option( 'wcj_invoicing_admin_search_by_invoice', 'no' ) ) {
				add_action( 'pre_get_posts', array( $this, 'search_orders_by_invoice_number' ) );
				if ( true === wcj_is_hpos_enabled() && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '8.2', '>=' ) ) {
					add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( $this, 'search_orders_by_invoice_number_hpos' ) );
				}
			}
		}

		/**
		 * Replace default HPOS admin search with an exact invoice-number query.
		 *
		 * @since 9.0.0
		 * @param array $args Native order-list query arguments.
		 * @return array Query arguments with existing filters preserved.
		 */
		public function search_orders_by_invoice_number_hpos( $args ) {
			if ( ! is_admin() || true !== wcj_is_hpos_enabled()
				|| ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '8.2', '<' )
				|| ! is_array( $args ) || ! isset( $args['type'], $args['s'] )
				|| 'shop_order' !== $args['type'] || ! is_string( $args['s'] ) || ! is_numeric( $args['s'] )
				|| ( array_key_exists( 'search_filter', $args ) && ! in_array( $args['search_filter'], array( '', 'all' ), true ) )
				|| ( array_key_exists( 'meta_query', $args ) && ! is_array( $args['meta_query'] ) ) ) {
				return $args;
			}
			if ( isset( $args['meta_query'] ) && ( array_key_exists( 'key', $args['meta_query'] ) || array_key_exists( 'value', $args['meta_query'] ) ) ) {
				return $args;
			}
			$meta_query = array( 'relation' => 'AND' );
			if ( ! empty( $args['meta_query'] ) ) {
				// Keep the original group first, including its relation and named sort clauses.
				$meta_query[] = $args['meta_query'];
			}
			$meta_query[] = array(
				'key'     => '_wcj_invoicing_invoice_number_id',
				'value'   => $args['s'],
				'compare' => '=',
			);
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['s']          = '';
			return $args;
		}

		/**
		 * Search_orders_by_invoice_number.
		 *
		 * @version 3.2.3
		 * @since   3.2.3
		 * @param array $query Get object.
		 */
		public function search_orders_by_invoice_number( $query ) {
			if (
			! is_admin() ||
			! isset( $query->query ) ||
			! isset( $query->query['s'] ) ||
			false === is_numeric( $query->query['s'] ) ||
			0 === $query->query['s'] ||
			'shop_order' !== $query->query['post_type'] ||
			! $query->query_vars['shop_order_search']
			) {
				return;
			}
			$invoice_number                = $query->query['s'];
			$query->query_vars['post__in'] = array();
			$query->query['s']             = '';
			$query->set( 'meta_key', '_wcj_invoicing_invoice_number_id' );
			$query->set( 'meta_value', $invoice_number );
		}

	}

endif;

return new WCJ_PDF_Invoicing_Numbering();
