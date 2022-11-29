<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Renumerate Tool
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Renumerate_Tool' ) ) :

		/**
		 * WCJ_PDF_Invoicing_Renumerate_Tool.
		 *
		 * @version 3.2.2
		 */
	class WCJ_PDF_Invoicing_Renumerate_Tool {

		/**
		 * Constructor.
		 *
		 * @version 3.2.2
		 */
		public function __construct() {
			return true;
		}

		/**
		 * Wcj_multi_selected.
		 *
		 * @param bool | string $selected Get selections.
		 * @param bool | string $current_multi  Current selected value.
		 */
		public function wcj_multi_selected( $selected, $current_multi ) {
			if ( ! is_array( $current_multi ) ) {
				return selected( $selected, $current_multi, false );
			}
			foreach ( $current_multi as $current ) {
				$selected_single = selected( $selected, $current, false );
				if ( '' !== $selected_single ) {
					return $selected_single;
				}
			}
			return '';
		}

		/**
		 * Add Renumerate Invoices tool to WooCommerce menu (the content).
		 *
		 * @version 6.0.0
		 */
		public function create_renumerate_invoices_tool() {

			$result_message     = '';
			$renumerate_result  = '';
			$wpnonce            = isset( $_REQUEST['wcj_tools_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_tools_nonce'] ), 'wcj_tools' ) : false;
			$the_invoice_type   = ( ! empty( $_POST['invoice_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['invoice_type'] ) ) : 'invoice';
			$the_start_number   = ( ! empty( $_POST['start_number'] ) ) ? sanitize_text_field( wp_unslash( $_POST['start_number'] ) ) : 0;
			$the_start_date     = ( ! empty( $_POST['start_date'] ) ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
			$the_end_date       = ( ! empty( $_POST['end_date'] ) ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
			$the_order_statuses = ( ! empty( $_POST['order_statuses'] ) ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['order_statuses'] ) ) : array();
			$the_delete_all     = ( isset( $_POST['delete_all'] ) );

			if ( ! $wpnonce ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wcj-tools' ) );
				exit;
			}
			if ( isset( $_POST['renumerate_invoices'] ) ) {
				if ( ! empty( $the_order_statuses ) ) {
					$renumerate_result = $this->renumerate_invoices( $the_invoice_type, $the_start_number, $the_start_date, $the_end_date, $the_order_statuses, $the_delete_all );
					$result_message    = '<div class="updated"><p><strong>' . __( 'Invoices successfully renumerated!', 'woocommerce-jetpack' ) . '</strong></p></div>';
				} else {
					$result_message = '<div class="error"><p><strong>' . __( 'Please select at least one order status.', 'woocommerce-jetpack' ) . '</strong></p></div>';
				}
			}

			?><div class="wcj-setting-jetpack-body">
			<h2><?php echo wp_kses_post( __( 'Booster - Renumerate Invoices', 'woocommerce-jetpack' ) ); ?></h2>
			<p><?php echo wp_kses_post( __( 'The tool renumerates invoices from choosen date. Invoice number format is set in WooCommerce > Settings > Booster > PDF Invoicing & Packing Slips > Numbering.', 'woocommerce-jetpack' ) ); ?></p>
			<?php echo wp_kses_post( $result_message ); ?>
			<p><form method="post" action="">
				<?php

				// Start Date.
				$data[] = array(
					__( 'Start Date', 'woocommerce-jetpack' ),
					'<input class="input-text widefat" display="date" type="text" name="start_date" value="' . $the_start_date . '">',
					'<em>' . __( 'Date to start renumerating. Leave blank to renumerate all invoices.', 'woocommerce-jetpack' ) . '</em>',
				);

				// End Date.
				$data[] = array(
					__( 'End Date', 'woocommerce-jetpack' ),
					'<input class="input-text widefat" display="date" type="text" name="end_date" value="' . $the_end_date . '">',
					'<em>' . __( 'Date to end renumerating. Leave blank to renumerate all invoices.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Number.
				$data[] = array(
					__( 'Start Number', 'woocommerce-jetpack' ),
					'<input class="input-text widefat" type="text" name="start_number" value="' . $the_start_number . '">',
					'<em>' . __( 'Counter to start renumerating. Leave 0 to continue from current counter.', 'woocommerce-jetpack' ) . '</em>',
				);

				// Delete All.
				$data[] = array(
					__( 'Delete All', 'woocommerce-jetpack' ),
					'<input id="wcj_renumerate_invoices_delete_all" type="checkbox" name="delete_all" value="" ' . checked( $the_delete_all, true, false ) . '> ' .
						'<label for="wcj_renumerate_invoices_delete_all">' . __( 'Clear all invoices before renumerating', 'woocommerce-jetpack' ) . '</label>',
					'',
				);

				// Type.
				$invoice_type_select_html = '<select class="widefat" name="invoice_type">';
				$invoice_types            = wcj_get_enabled_invoice_types();
				foreach ( $invoice_types as $invoice_type ) {
					$invoice_type_select_html .= '<option value="' . $invoice_type['id'] . '" ' . selected( $invoice_type['id'], $the_invoice_type, false ) . '>' .
						$invoice_type['title'] . '</option>';
				}
				$invoice_type_select_html .= '</select>';
				$data[]                    = array( __( 'Document Type', 'woocommerce-jetpack' ), $invoice_type_select_html, '' );

				// Statuses.
				$order_statuses_select_html = '<select class="widefat" id="order_statuses" name="order_statuses[]" multiple size="5">';
				$order_statuses             = wcj_get_order_statuses( false );
				foreach ( $order_statuses as $status => $desc ) {
					$order_statuses_select_html .= '<option value="' . $status . '" ' . $this->wcj_multi_selected( $status, $the_order_statuses ) . '>' . $desc . '</option>';
				}
				$order_statuses_select_html .= '</select>';
				$data[]                      = array( __( 'Order Statuses', 'woocommerce-jetpack' ), $order_statuses_select_html, '' );

				// Button.
				$data[] = array(
					'<input class="button-primary" type="submit" name="renumerate_invoices" value="' . __( 'Renumerate invoices', 'woocommerce-jetpack' ) . '">',
					'',
					'',
				);

				// Print all.
				echo wp_kses_post(
					wcj_get_table_html(
						$data,
						array(
							'table_class'        => 'widefat striped',
							'table_heading_type' => 'vertical',
						)
					)
				);

				?>
			</form></p>
				<?php
				if ( '' !== $renumerate_result ) {
					echo '<h3>' . esc_html__( 'Results', 'woocommerce-jetpack' ) . '</h3>';
					echo '<p>' . wp_kses_post( $renumerate_result ) . '</p>';
				}
				?>
		</div>
			<?php
		}

		/**
		 * Renumerate invoices function.
		 *
		 * @version 2.3.10
		 * @param string     $invoice_type Get invoice type.
		 * @param int        $start_number Get invoice start number.
		 * @param int        $start_date Get invoice start date.
		 * @param int        $end_date Get invoice end date.
		 * @param int | bool $order_statuses Get order status.
		 * @param mixed      $the_delete_all delete all data..
		 */
		public function renumerate_invoices( $invoice_type, $start_number, $start_date, $end_date, $order_statuses, $the_delete_all ) {

			$output = '';

			if ( 0 !== $start_number ) {
				update_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', $start_number );
			}

			$date_query_array = array(
				array(
					'after'     => $start_date,
					'inclusive' => true,
				),
			);
			if ( '' !== $end_date ) {
				$date_query_array[0]['before'] = $end_date;
			}

			$deleted_invoices_counter = 0;
			$created_invoices_counter = 0;

			$offset     = 0;
			$block_size = 96;
			while ( true ) {

				$args = array(
					'post_type'      => 'shop_order',
					'post_status'    => 'any',
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'orderby'        => 'date',
					'order'          => 'ASC',
					'date_query'     => $date_query_array,
				);

				$loop = new WP_Query( $args );

				if ( ! $loop->have_posts() ) {
					break;
				}

				while ( $loop->have_posts() ) :
					$loop->the_post();

					$order_id = $loop->post->ID;
					if (
					in_array( $loop->post->post_status, $order_statuses, true ) &&
					strtotime( $loop->post->post_date ) >= strtotime( $start_date ) &&
					( strtotime( $loop->post->post_date ) <= strtotime( $end_date ) || '' === $end_date )
					) {

						$the_order = wc_get_order( $order_id );
						if ( 0 !== $the_order->get_total() ) {

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

				$offset += $block_size;

			}
			/* translators: %d: search term */
			$output .= '<p>' . sprintf( __( 'Total documents created: %d', 'woocommerce-jetpack' ), $created_invoices_counter ) . '</p>';
			/* translators: %d: search term */
			$output .= '<p>' . sprintf( __( 'Total documents deleted: %d', 'woocommerce-jetpack' ), $deleted_invoices_counter ) . '</p>';

			return $output;
		}
	}

endif;

return new WCJ_PDF_Invoicing_Renumerate_Tool();
