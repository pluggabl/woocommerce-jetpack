<?php
/**
 * WooCommerce Jetpack PDF Invoicing Display
 *
 * The WooCommerce Jetpack PDF Invoicing Display class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Display' ) ) :

class WCJ_PDF_Invoicing_Display extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
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
			// Action Buttons to Admin's Orders list
			add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_pdf_invoices_admin_actions' ), PHP_INT_MAX, 2 );
			add_filter( 'admin_head', array( $this, 'add_pdf_invoices_admin_actions_buttons_css' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'wcj-pdf-invoicing', wcj_plugin_url() . '/includes/js/wcj-pdf-invoicing.js', array(), false, true );
	}

	/**
	 * add_pdf_invoices_admin_actions_buttons_css.
	 *
	 * @version 2.5.2
	 * @since   2.4.7
	 */
	function add_pdf_invoices_admin_actions_buttons_css() {
		echo '<style>' . PHP_EOL;
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			switch ( $invoice_type['id'] ) {
				case 'invoice':
					$color = 'green';
					break;
				case 'proforma_invoice':
					$color = 'orange';
					break;
				case 'packing_slip':
					$color = 'blue';
					break;
				case 'credit_note':
					$color = 'red';
					break;
				default: // 'custom_doc'
					$color = 'gray';
					break;
			}
			echo '.view.' . $invoice_type['id'] .                  '{ color: ' . $color . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] . '_' . 'create' . '{ color: ' . $color . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] . '_' . 'delete' . '{ color: ' . $color . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .                   '::after { content: "\f159" !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .  '_' . 'create' . '::after { content: "\f132" !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .  '_' . 'delete' . '::after { content: "\f460" !important; }' . PHP_EOL;
		}
		echo '</style>' . PHP_EOL;
	}

	/**
	 * add_pdf_invoices_admin_actions.
	 *
	 * @version 2.5.2
	 * @since   2.4.7
	 */
	function add_pdf_invoices_admin_actions( $actions, $the_order ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( wcj_is_invoice_created( $the_order->id, $invoice_type['id'] ) ) {
				if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn', 'no' ) ) {
					// Document (View) button
					$query_args = array( 'order_id' => $the_order->id, 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
					if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
						$query_args['save_pdf_invoice'] = '1';
					}
					$the_url       = add_query_arg( $query_args, remove_query_arg( array ( 'create_invoice_for_order_id', 'delete_invoice_for_order_id' ) ) );
					$the_name      = __( 'View', 'woocommerce-jetpack' ) . ' '  . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'];
					$the_action_id = $invoice_type['id'];
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action, );
				}
				if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn', 'yes' ) ) {
					// Delete button
					$query_args = array( 'delete_invoice_for_order_id' => $the_order->id, 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'create_invoice_for_order_id' ) );
					$the_name      = __( 'Delete', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'] . '_' . 'delete' . ( ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
					$the_action_id = $invoice_type['id'] . '_' . 'delete';
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action, );
				}
			} else {
				if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn', 'yes' ) ) {
					// Create button
					$query_args = array( 'create_invoice_for_order_id' => $the_order->id, 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'delete_invoice_for_order_id' ) );
					$the_name      = __( 'Create', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'] . '_' . 'create' . ( ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
					$the_action_id = $invoice_type['id'] . '_' . 'create';
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action, );
				}
			}
		}
		return $actions;
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
	 * Output custom columns for products
	 *
	 * @param   string $column
	 * @version 2.4.7
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

			/* // Delete button
			$delete_button_label = get_option( 'wcj_invoicing_' . $invoice_type_id . '_admin_column_delete_btn', __( 'Delete', 'woocommerce-jetpack' ) );
			if ( '' != $delete_button_label ) {
				$html .= ' ';
				$html .= '<a href="';
				$html .= add_query_arg(
					array( 'delete_invoice_for_order_id' => $order_id, 'invoice_type_id' => $invoice_type_id ),
					remove_query_arg( 'create_invoice_for_order_id' )
				);
				$html .= '"><span style="color:gray;font-style:italic;font-size:x-small;text-decoration:underline;">' . $delete_button_label . '</span></a>';
			} */
		} /* else {

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
		} */
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
	 * @version 2.5.2
	 * @todo    "edit order" metabox;
	 */
	function get_settings() {

		$settings = array();
		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
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

				/* array(
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
				), */

				array(
					'desc'     => __( 'Add View Button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn',
					'default'  => 'no',
					'type'     => 'checkbox',
				),

				array(
					'desc'     => __( 'Add Create Button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),

				array(
					'desc'     => __( 'Add Delete Button', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),

				array(
					'desc'     => __( 'Create Button Requires Confirmation', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm',
					'default'  => 'yes',
					'type'     => 'checkbox',
				),

				array(
					'desc'     => __( 'Delete Button Requires Confirmation', 'woocommerce-jetpack' ),
					'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm',
					'default'  => 'yes',
					'type'     => 'checkbox',
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

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_PDF_Invoicing_Display();
