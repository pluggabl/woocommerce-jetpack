<?php
/**
 * WooCommerce Jetpack PDF Invoicing Display
 *
 * The WooCommerce Jetpack PDF Invoicing Display class.
 *
 * @version 2.3.10
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Display' ) ) :

class WCJ_PDF_Invoicing_Display extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 */
	function __construct() {

		$this->id         = 'pdf_invoicing_display';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Display & Misc.', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );

		if ( $this->is_enabled() ) {
			// Columns on Admin's Orders page
			add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_column' ),     PHP_INT_MAX );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), 2 );
			// Action Links on Customer's My Account page
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_pdf_invoices_action_links' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * add_order_column.
	 *
	 * @version 2.3.10
	 */
	function add_order_column( $columns ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column', 'yes' ) ) {
				$columns[ $invoice_type['id'] ] = get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text' );
			}
		}
		return $columns;
	}

	/**
	 * Ouput custom columns for products
	 *
	 * @param   string $column
	 * @version 2.3.10
	 */
	public function render_order_columns( $column ) {
		$invoice_types_ids = wcj_get_enabled_invoice_types_ids();
		if ( ! in_array( $column, $invoice_types_ids ) ) {
			return;
		}
		$order_id = get_the_ID();
		$invoice_type_id = $column;
		$html = '';
		if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
			$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
			$the_number  = $the_invoice->get_invoice_number();

			// Document Link
			$query_args = array( 'order_id' => $order_id, 'invoice_type_id' => $invoice_type_id, 'get_invoice' => '1', );
			if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type_id . '_save_as_enabled', 'no' ) ) {
				$query_args['save_pdf_invoice'] = '1';
			}
			$html .= '<a href="'
				. add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'delete_invoice_for_order_id' ) ) )
				. '">' . $the_number . '</a>';

			// Delete button
			$delete_button_label = get_option( 'wcj_invoicing_' . $invoice_type_id . '_admin_column_delete_btn', __( 'Delete', 'woocommerce-jetpack' ) );
			if ( '' != $delete_button_label ) {
				$html .= ' ';
				$html .= '<a href="';
				$html .= add_query_arg(
					array( 'delete_invoice_for_order_id' => $order_id, 'invoice_type_id' => $invoice_type_id ),
					remove_query_arg( 'create_invoice_for_order_id' )
				);
				$html .= '"><span style="color:gray;font-style:italic;font-size:x-small;text-decoration:underline;">' . $delete_button_label . '</span></a>';
			}
		} else {
			// Create Button
			$create_button_label = get_option( 'wcj_invoicing_' . $invoice_type_id . '_admin_column_create_btn', __( 'Create', 'woocommerce-jetpack' ) );
			if ( '' != $create_button_label ) {
				$html .= '<a href="';
				$html .= add_query_arg(
					array( 'create_invoice_for_order_id' => $order_id, 'invoice_type_id' => $invoice_type_id ),
					remove_query_arg( 'delete_invoice_for_order_id' )
				);
				$html .= '"><span style="color:gray;font-style:italic;font-size:x-small;text-decoration:underline;">' . $create_button_label . '</span></a>';
			}
		}
		echo $html;
	}

	/**
	 * add_pdf_invoices_action_links.
	 *
	 * @version 2.3.7
	 */
	function add_pdf_invoices_action_links( $actions, $the_order ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( ! wcj_is_invoice_created( $the_order->id, $invoice_type['id'] ) )
				continue;
			$my_account_option_name = 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers';
			if ( 'yes' === get_option( $my_account_option_name, 'no' ) ) {

				$the_action_id = $invoice_type['id'];

				$query_args = array( 'order_id' => $the_order->id, 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
				if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
					$query_args['save_pdf_invoice'] = '1';
				}
				$the_url = add_query_arg( $query_args );

				$the_name = get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_link_text' );
				if ( '' == $the_name ) $the_name = $invoice_type['title'];

				$the_action = 'view ' . $invoice_type['id'];

				$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action, );
			}
		}
		return $actions;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.3.10
	 */
	function get_settings() {

		$settings = array();
		$invoice_types = wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {

			$settings[] = array(
				'title'        => strtoupper( $invoice_type['desc'] ),
				'type'         => 'title',
				'id'           => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
			);

			$settings = array_merge( $settings, array(

				array(
					'title'    => __( 'Admin\'s "Orders" Page', 'woocommerce-jetpack' ),
					'desc'     => __( 'Add Column', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),

				array(
					'title'    => '',
					'desc'     => __( 'Column Title', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text',
					'default'  => $invoice_type['title'],
					'type'     => 'text',
				),

				array(
					'title'    => '',
					'desc'     => __( 'Create Button', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Set empty to disable the button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_column_create_btn',
					'default'  => __( 'Create', 'woocommerce-jetpack' ),
					'type'     => 'text',
				),

				array(
					'title'    => '',
					'desc'     => __( 'Delete Button', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Set empty to disable the button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_column_delete_btn',
					'default'  => __( 'Delete', 'woocommerce-jetpack' ),
					'type'     => 'text',
				),

				array(
					'title'    => __( 'Customer\'s "My Account" Page', 'woocommerce-jetpack' ),
					'desc'     => __( 'Add link', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers',
					'default'  => 'no',
					'type'     => 'checkbox',
				),

				array(
					'title'    => '',
					'desc'     => __( 'Link Text', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_link_text',
					'default'  => $invoice_type['title'],
					'type'     => 'text',
				),

				array(
					'title'    => __( 'Enable "Save as"', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Enable "save as" pdf instead of view pdf in browser', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),

				array(
					'title'    => __( 'PDF File Name', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enter file name for PDF documents. You can use shortcodes here, e.g. [wcj_' . $invoice_type['id'] . '_number]', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_file_name',
					'default'  => '[wcj_' . $invoice_type['id'] . '_number]',
					'type'     => 'text',
				),
			) );

			$settings[] = array(
				'type'         => 'sectionend',
				'id'           => 'wcj_invoicing_' . $invoice_type['id'] . '_display_options',
			);
		}

		return $settings;
	}
}

endif;

return new WCJ_PDF_Invoicing_Display();
