<?php
/**
 * WooCommerce Jetpack PDF Invoices Renumerate Tool
 *
 * The WooCommerce Jetpack PDF Invoices Renumerate Tool class.
 *
 * @version 2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Renumerate_Tool' ) ) :

class WCJ_PDF_Invoicing_Renumerate_Tool {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wcj_tools_tabs',                array( $this, 'add_renumerate_invoices_tool_tab' ),                     100 );
		add_action( 'wcj_tools_renumerate_invoices', array( $this, 'create_renumerate_invoices_tool' ),                      100 );
		add_action( 'wcj_tools_dashboard',           array( $this, 'add_renumerate_invoices_tool_info_to_tools_dashboard' ), 100 );
	}

	/**
	 * add_renumerate_invoices_tool_info_to_tools_dashboard.
	 */
	public function add_renumerate_invoices_tool_info_to_tools_dashboard() {
		echo '<tr>';
		if ( 'yes' === get_option( 'wcj_pdf_invoicing_enabled') )
			$is_enabled = '<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>';
		else
			$is_enabled = '<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'Invoices Renumerate', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'Tool renumerates all invoices, proforma invoices, credit notes and packing slips.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * add_renumerate_invoices_tool_tab.
	 */
	public function add_renumerate_invoices_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'		=> 'renumerate_invoices',
			'title'		=> __( 'Renumerate Invoices', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}

	function wcj_multi_selected( $selected, $current_multi ) {
		if ( ! is_array( $current_multi ) ) return selected( $selected, $current_multi, false );
		foreach( $current_multi as $current ) {
			$selected_single = selected( $selected, $current, false );
			if ( '' != $selected_single ) return $selected_single;
		}
		return '';
	}

	/**
	 * Add Renumerate Invoices tool to WooCommerce menu (the content).
	 *
	 * @version 2.3.0
	 */
	public function create_renumerate_invoices_tool() {
		$result_message = '';
		$renumerate_result = '';

		$the_invoice_type   = ( ! empty( $_POST['invoice_type'] ) )   ? $_POST['invoice_type']   : 'invoice';
		$the_start_number   = ( ! empty( $_POST['start_number'] ) )   ? $_POST['start_number']   : 0;
		$the_start_date     = ( ! empty( $_POST['start_date'] ) )     ? $_POST['start_date']     : '';
		$the_order_statuses = ( ! empty( $_POST['order_statuses'] ) ) ? $_POST['order_statuses'] : array();
		$the_delete_all     = ( isset( $_POST['delete_all'] ) )       ? true : false;

		if ( isset( $_POST['renumerate_invoices'] ) ) {
			if ( ! empty( $the_order_statuses ) ) {
				$renumerate_result = $this->renumerate_invoices( $the_invoice_type, $the_start_number, $the_start_date, $the_order_statuses, $the_delete_all );
				$result_message = '<div class="updated"><p><strong>' . __( 'Invoices successfully renumerated!', 'woocommerce-jetpack' ) . '</strong></p></div>';
			} else {
				$result_message = '<div class="error"><p><strong>' . __( 'Please select at least one order status.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
			//$result_message .= '<p>' . $renumerate_result . '</p>';
		}
		?><div>
			<h2><?php echo __( 'Booster - Renumerate Invoices', 'woocommerce-jetpack' ); ?></h2>
			<p><?php echo __( 'The tool renumerates invoices from choosen date. Invoice number format is set in WooCommerce > Settings > Booster > PDF Invoicing & Packing Slips > Numbering.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<p><form method="post" action="">
				<?php

				// Date
				$data[] = array(
					__( 'Start Date', 'woocommerce-jetpack' ),
					'<input class="input-text" display="date" type="text" name="start_date" value="' . $the_start_date . '">',
					'<em>' . __( 'Date to start renumerating. Leave blank to renumerate all invoices.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Number
				$data[] = array(
					__( 'Start Number', 'woocommerce-jetpack' ),
					'<input class="input-text" type="text" name="start_number" value="' . $the_start_number . '">',
					'<em>' . __( 'Counter to start renumerating. Leave 0 to continue from current counter.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Delete All
				$data[] = array(
					__( 'Delete All', 'woocommerce-jetpack' ),
					'<input type="checkbox" name="delete_all" value="" ' . checked( $the_delete_all, true, false ) . '>',
					'<em>' . __( 'Clear all invoices before renumerating.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Type
				$invoice_type_select_html = '<select name="invoice_type">';
				//$invoice_types = wcj_get_invoice_types();
				$invoice_types = wcj_get_enabled_invoice_types();
				foreach ( $invoice_types as $invoice_type ) {
					$invoice_type_select_html .= '<option value="' . $invoice_type['id'] . '" ' . selected( $invoice_type['id'], $the_invoice_type, false ) . '>' . $invoice_type['title'] . '</option>';
				}
				$invoice_type_select_html .= '</select>';
				$data[] = array( __( 'Document Type', 'woocommerce-jetpack' ), $invoice_type_select_html, '', );

				// Statuses
				$order_statuses_select_html = '<select id="order_statuses" name="order_statuses[]" multiple size="5">';
				$order_statuses = wcj_get_order_statuses( false );
				foreach ( $order_statuses as $status => $desc ) {
					//$order_statuses_select_html .= '<option value="' . $status . '">' . $desc . '</option>';
					$order_statuses_select_html .= '<option value="' . $status . '" ' . $this->wcj_multi_selected( $status, $the_order_statuses ) . '>' . $desc . '</option>';
				}
				$order_statuses_select_html .= '</select>';
				$data[] = array( __( 'Order Statuses', 'woocommerce-jetpack' ), $order_statuses_select_html, '', );

				// Print all
				echo wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', ) );

				?>
				<input type="submit" name="renumerate_invoices" value="Renumerate invoices">
			</form></p>
			<?php
			if ( '' != $renumerate_result ) {
				echo '<h3>' . __( 'Results', 'woocommerce-jetpack' ) . '</h3>';
				echo '<p>' . $renumerate_result . '</p>';
			}
			?>
		</div><?php
	}

	/**
	 * Renumerate invoices function.
	 */
	public function renumerate_invoices( $invoice_type, $start_number, $start_date, $order_statuses, $the_delete_all ) {

		$output = '';

		if ( 0 != $start_number ) {
			update_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', $start_number );
		}

		$args = array(
			'post_type'      => 'shop_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'date_query'     => array(
				array(
					'after'     => $start_date,
					'inclusive' => true,
				),
			),
		);

		$loop = new WP_Query( $args );

		$deleted_invoices_counter = 0;
		$created_invoices_counter = 0;
		while ( $loop->have_posts() ) : $loop->the_post();

			$order_id = $loop->post->ID;
			if ( in_array( $loop->post->post_status, $order_statuses ) && strtotime( $loop->post->post_date ) >= strtotime( $start_date ) ) {

				$the_order = wc_get_order( $order_id );
				if ( 0 != $the_order->get_total() ) {

					wcj_create_invoice( $order_id, $invoice_type, strtotime( $loop->post->post_date ) );
					$created_invoices_counter++;
				}
			} else {
				if ( $the_delete_all && wcj_is_invoice_created( $order_id, $invoice_type ) ) {
					wcj_delete_invoice( $order_id, $invoice_type );
					$deleted_invoices_counter++;
				}
			}

		endwhile;

		$output .= '<p>' . sprintf( __( 'Total documents created: %d', 'woocommerce-jetpack' ), $created_invoices_counter ) . '</p>';
		$output .= '<p>' . sprintf( __( 'Total documents deleted: %d', 'woocommerce-jetpack' ), $deleted_invoices_counter ) . '</p>';

		return $output;
	}
}

endif;

return new WCJ_PDF_Invoicing_Renumerate_Tool();
