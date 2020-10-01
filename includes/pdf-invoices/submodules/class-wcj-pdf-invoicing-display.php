<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Display
 *
 * @version 3.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Display' ) ) :

class WCJ_PDF_Invoicing_Display extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 */
	function __construct() {

		$this->id         = 'pdf_invoicing_display';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Display & Misc.', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );

		if ( $this->is_enabled() ) {
			// Columns on Admin's Orders page
			add_filter( 'manage_edit-shop_order_columns',           array( $this, 'add_order_column' ), PHP_INT_MAX - 3 );
			add_action( 'manage_shop_order_posts_custom_column',    array( $this, 'render_order_columns' ), 2 );
			// Action Links on Customer's My Account page
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_pdf_invoices_action_links' ), PHP_INT_MAX, 2 );
			// Action Links on Customer's Thank You page
			add_action( 'woocommerce_thankyou',                     array( $this, 'add_pdf_invoices_links_to_thankyou_page' ), 10, 1 );
			// Action Buttons to Admin's Orders list
			add_filter( 'woocommerce_admin_order_actions',          array( $this, 'add_pdf_invoices_admin_actions' ), PHP_INT_MAX, 2 );
			add_filter( 'admin_head',                               array( $this, 'add_pdf_invoices_admin_actions_buttons_css' ) );
			add_action( 'admin_enqueue_scripts',                    array( $this, 'enqueue_scripts' ) );
			// Make Sortable Columns
			add_filter( 'manage_edit-shop_order_sortable_columns',  array( $this, 'shop_order_sortable_columns' ) );
			add_action( 'pre_get_posts',                            array( $this, 'shop_order_pre_get_posts_order_by_column' ) );
			// Meta box on admin order page
			add_action( 'add_meta_boxes',                           array( $this, 'add_invoices_meta_box' ) );
		}
	}

	/**
	 * shop_order_pre_get_posts_order_by_column.
	 *
	 * @version 2.9.0
	 * @since   2.5.8
	 */
	function shop_order_pre_get_posts_order_by_column( $query ) {
		if (
			$query->is_main_query() &&
			( $orderby = $query->get( 'orderby' ) ) &&
			isset( $query->query['post_type'] ) && 'shop_order' === $query->query['post_type'] &&
			isset( $query->is_admin ) && 1 == $query->is_admin
		) {
			if ( 'wcj_invoicing_' === substr( $orderby, 0, 14 ) ) {
				$query->set( 'meta_key', '_' . $orderby );
				$query->set( 'orderby', 'meta_value_num' );
			}
		}
	}

	/**
	 * Make columns sortable.
	 *
	 * @version 2.9.0
	 * @since   2.5.8
	 * @param   array $columns
	 * @return  array
	 */
	function shop_order_sortable_columns( $columns ) {
		$custom = array();
		foreach ( wcj_get_enabled_invoice_types_ids() as $invoice_type_id ) {
			$custom[ $invoice_type_id ] = 'wcj_invoicing_' . $invoice_type_id . '_number_id';
		}
		return wp_parse_args( $custom, $columns );
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
	 * @version 3.1.0
	 * @since   2.4.7
	 */
	function add_pdf_invoices_admin_actions_buttons_css() {
		echo '<style>' . PHP_EOL;
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			echo '.view.' . $invoice_type['id'] .                  '{ color: ' . $invoice_type['color'] . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] . '_' . 'create' . '{ color: ' . $invoice_type['color'] . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] . '_' . 'delete' . '{ color: ' . $invoice_type['color'] . ' !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .                   '::after { content: "\f498" !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .  '_' . 'create' . '::after { content: "\f119" !important; }' . PHP_EOL;
			echo '.view.' . $invoice_type['id'] .  '_' . 'delete' . '::after { content: "\f153" !important; }' . PHP_EOL;
		}
		echo '</style>' . PHP_EOL;
	}

	/**
	 * add_pdf_invoices_admin_actions.
	 *
	 * @version 2.7.0
	 * @since   2.4.7
	 */
	function add_pdf_invoices_admin_actions( $actions, $the_order ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( wcj_is_invoice_created( wcj_get_order_id( $the_order ), $invoice_type['id'] ) ) {
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_view_btn', 'no' ) ) {
					// Document (View) button
					$query_args = array( 'order_id' => wcj_get_order_id( $the_order ), 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
						$query_args['save_pdf_invoice'] = '1';
					}
					$the_url       = add_query_arg( $query_args, remove_query_arg( array ( 'create_invoice_for_order_id', 'delete_invoice_for_order_id' ) ) );
					$the_name      = __( 'View', 'woocommerce-jetpack' ) . ' '  . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'];
					$the_action_id = $invoice_type['id'];
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action );
				}
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn', 'yes' ) ) {
					// Delete button
					$query_args = array( 'delete_invoice_for_order_id' => wcj_get_order_id( $the_order ), 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'create_invoice_for_order_id' ) );
					$the_name      = __( 'Delete', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'] . '_' . 'delete' . ( ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_delete_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
					$the_action_id = $invoice_type['id'] . '_' . 'delete';
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action );
				}
			} else {
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn', 'yes' ) ) {
					// Create button
					$query_args = array( 'create_invoice_for_order_id' => wcj_get_order_id( $the_order ), 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'delete_invoice_for_order_id' ) );
					$the_name      = __( 'Create', 'woocommerce-jetpack' ) . ' ' . $invoice_type['title'];
					$the_action    = 'view ' . $invoice_type['id'] . '_' . 'create' . ( ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_create_btn_confirm', 'yes' ) ) ? ' wcj_need_confirmation' : '' );
					$the_action_id = $invoice_type['id'] . '_' . 'create';
					$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action );
				}
			}
		}
		return $actions;
	}

	/**
	 * add_order_column.
	 *
	 * @version 3.1.0
	 */
	function add_order_column( $columns ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_orders_page_column', 'yes' ) ) {
				$columns[ $invoice_type['id'] ] = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_admin_page_column_text', $invoice_type['title'] );
			}
		}
		return $columns;
	}

	/**
	 * Output custom columns for products
	 *
	 * @version 2.4.7
	 * @param   string $column
	 */
	function render_order_columns( $column ) {
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
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_save_as_enabled', 'no' ) ) {
				$query_args['save_pdf_invoice'] = '1';
			}
			$html .= '<a href="' . add_query_arg( $query_args, remove_query_arg( array( 'create_invoice_for_order_id', 'delete_invoice_for_order_id' ) ) ) . '">' .
				$the_number . '</a>';
		}
		echo $html;
	}

	/**
	 * add_pdf_invoices_links_to_thankyou_page.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    option to change priority for the hook (probably for all docs at once)
	 */
	function add_pdf_invoices_links_to_thankyou_page( $order_id ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( ! wcj_is_invoice_created( $order_id, $invoice_type['id'] ) ) {
				continue;
			}
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_on_thankyou_page', 'no' ) ) {
				$query_args = array( 'order_id' => $order_id, 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
					$query_args['save_pdf_invoice'] = '1';
				}
				if ( '' == ( $title = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_link_text', $invoice_type['title'] ) ) ) {
					$title = $invoice_type['title'];
				}
				echo str_replace( '%link%', '<a target="_blank" href="' . add_query_arg( $query_args ) . '">' . $title . '</a>',
					get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_thankyou_page_template',
						'<p><strong>' . sprintf( __( 'Your %s:', 'woocommerce-jetpack' ), $invoice_type['title'] ) . ' </strong> %link%</p>' ) );
			}
		}
	}

	/**
	 * add_pdf_invoices_action_links.
	 *
	 * @version 2.7.0
	 */
	function add_pdf_invoices_action_links( $actions, $the_order ) {
		$invoice_types = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			if ( ! wcj_is_invoice_created( wcj_get_order_id( $the_order ), $invoice_type['id'] ) ) {
				continue;
			}
			$my_account_option_name = 'wcj_invoicing_' . $invoice_type['id'] . '_enabled_for_customers';
			if ( 'yes' === wcj_get_option( $my_account_option_name, 'no' ) ) {
				$the_action_id = $invoice_type['id'];
				$query_args = array( 'order_id' => wcj_get_order_id( $the_order ), 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
				if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
					$query_args['save_pdf_invoice'] = '1';
				}
				$the_url = add_query_arg( $query_args );
				$the_name = wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_link_text' );
				if ( '' == $the_name ) {
					$the_name = $invoice_type['title'];
				}
				$the_action = 'view ' . $invoice_type['id'];
				$actions[ $the_action_id ] = array( 'url' => $the_url, 'name' => $the_name, 'action' => $the_action );
			}
		}
		return $actions;
	}

	/**
	 * add_invoices_meta_box.
	 *
	 * @version 3.1.0
	 * @since   2.8.0
	 */
	function add_invoices_meta_box() {
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_add_order_meta_box', 'yes' ) ) {
			add_meta_box(
				'wc-booster-pdf-invoicing',
				'<span class="dashicons dashicons-media-default" style="color:#23282d;"></span>' . ' ' . __( 'Booster: PDF Invoices', 'woocommerce-jetpack' ),
				array( $this, 'create_invoices_meta_box' ),
				'shop_order',
				'side',
				'default'
			);
		}
	}

	/**
	 * create_invoices_meta_box.
	 *
	 * @version 3.9.0
	 * @since   2.8.0
	 */
	function create_invoices_meta_box() {
		$_order        = wc_get_order();
		$order_id      = wcj_get_order_id( $_order );
		$invoice_types = wcj_get_enabled_invoice_types();
		if ( empty( $invoice_types ) ) {
			echo '<p style="font-style:italic;">' . __( 'You have no document types enabled.', 'woocommerce-jetpack' ) . '</p>';
		} else {
			foreach ( $invoice_types as $invoice_type ) {
				$table_data = array();
				$the_number = '';
				$the_date   = '';
				if ( wcj_is_invoice_created( $order_id, $invoice_type['id'] ) ) {
					// "Document (View)" link
					$query_args    = array( 'order_id' => $order_id, 'invoice_type_id' => $invoice_type['id'], 'get_invoice' => '1', );
					$target        = ( 'yes' === wcj_get_option( 'wcj_invoicing_order_meta_box_open_in_new_window', 'yes' ) ? ' target="_blank"' : '' );
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_save_as_enabled', 'no' ) ) {
						$query_args['save_pdf_invoice'] = '1';
						$target = '';
					}
					$the_url       = add_query_arg( $query_args, remove_query_arg( array ( 'create_invoice_for_order_id', 'delete_invoice_for_order_id' ) ) );
					$the_name      = __( 'View', 'woocommerce-jetpack' );
					$the_invoice   = wcj_get_invoice( $order_id, $invoice_type['id'] );
					$the_number    = ' [#' . $the_invoice->get_invoice_number() . ']';
					$the_date      = '<span style="font-size:x-small;"> (' . date( 'Y-m-d', $the_invoice->get_invoice_date() ) . ')</span>';
					$view_link     = '<a' . $target . ' href="' .  $the_url . '">' . $the_name . '</a>';
					// "Delete" link
					$query_args    = array( 'delete_invoice_for_order_id' => $order_id, 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'create_invoice_for_order_id' ) );
					$the_name      = __( 'Delete', 'woocommerce-jetpack' );
					$delete_link   = '<a class="wcj_need_confirmation" href="' .  $the_url . '">' . $the_name . '</a>';
					// Numbering & date
					$number_input  = '';
					if ( 'yes' === wcj_get_option( 'wcj_invoicing_add_order_meta_box_numbering', 'yes' ) ) {
						$number_option = 'wcj_invoicing_' . $invoice_type['id'] . '_number_id';
						$date_option   = 'wcj_invoicing_' . $invoice_type['id'] . '_date';
						$number_input  = '<br>' .
							'<input style="width:100%;" type="number"' .
								' id="' . $number_option . '" name="' . $number_option . '" value="' . get_post_meta( $order_id, '_' . $number_option, true ) . '">' .
							'<input style="width:100%;" type="text"' .
								' id="' . $date_option   . '" name="' . $date_option   . '" value="' . date( 'Y-m-d H:i:s', get_post_meta( $order_id, '_' . $date_option, true ) ) . '">' .
							'<input type="hidden" name="woojetpack_pdf_invoicing_save_post" value="woojetpack_pdf_invoicing_save_post">';
					}
					// Actions
					$actions       = array( $view_link . ' | ' . $delete_link . $number_input );
				} else {
					// "Create" link
					$query_args    = array( 'create_invoice_for_order_id' => $order_id, 'invoice_type_id' => $invoice_type['id'] );
					$the_url       = add_query_arg( $query_args, remove_query_arg( 'delete_invoice_for_order_id' ) );
					$the_name      = __( 'Create', 'woocommerce-jetpack' );
					$actions       = array( '<a class="wcj_need_confirmation" href="' .  $the_url . '">' . $the_name . '</a>' );
				}
				$maybe_toolptip = '';
				$_hooks = wcj_get_invoice_create_on( $invoice_type['id'] );
				if ( in_array( 'woocommerce_order_partially_refunded_notification', $_hooks ) ) {
					$maybe_toolptip = wc_help_tip( __( 'In case of partial refund, you need to reload the page to see created document in this meta box.', 'woocommerce-jetpack' ), true );
				}
				$table_data[] = array( '<span class="dashicons dashicons-media-default" style="color:' . $invoice_type['color'] . ';"></span>' . ' ' .
					$invoice_type['title'] . $the_number . $the_date . $maybe_toolptip );
				$table_data[] = $actions;
				echo '<p>' . wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped' ) ) . '</p>';
			}
		}
	}

}

endif;

return new WCJ_PDF_Invoicing_Display();
