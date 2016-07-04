<?php
/**
 * WooCommerce Jetpack PDF Invoices Report Tool
 *
 * The WooCommerce Jetpack PDF Invoices Report Tool class.
 *
 * @version 2.5.3
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Report_Tool' ) ) :

class WCJ_PDF_Invoicing_Report_Tool {

	/**
	 * Constructor.
	 *
	 * @version 2.3.10
	 */
	public function __construct() {
		$this->notice = '';
		add_action( 'init', array( $this, 'generate_report_zip' ) );
	}

	/**
	 * generate_report_zip.
	 *
	 * @version 2.5.0
	 * @since   2.3.10
	 */
	function generate_report_zip() {
		if ( isset( $_POST['get_invoices_report_zip'] ) ) {
			if ( wcj_is_module_enabled( 'general' ) && 'yes' === get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
				$this->notice = '<div class="error"><p><strong>' . __( 'This option is disabled in WooCommerce > Settings > Booster > Emails & Misc. > General > Advanced Options > Disable Saving PDFs in PHP directory for temporary files', 'woocommerce-jetpack' ) . '</strong></p></div>';
			} else {
				if ( ! empty( $_POST['report_year'] ) && ! empty( $_POST['report_month'] ) && ! empty( $_POST['invoice_type'] ) ) {
					if ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) {
						if ( false === $this->get_invoices_report_zip( $_POST['report_year'], $_POST['report_month'], $_POST['invoice_type'] ) ) {
							$this->notice = '<div class="error"><p><strong>' . __( 'Sorry, but something went wrong...', 'woocommerce-jetpack' ) . '</strong></p></div>';
						}
					}
				} else {
					$this->notice = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
				}
			}
		}
	}

	/**
	 * Add Invoices Report tool to WooCommerce menu (the content).
	 *
	 * @version 2.4.7
	 */
	function create_invoices_report_tool() {
		$result_message = '';
		$result_message .= $this->notice;
		$the_year  = ( ! empty( $_POST['report_year'] ) )  ? $_POST['report_year']  : '';
		$the_month = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : '';
		$the_invoice_type = ( ! empty( $_POST['invoice_type'] ) ) ? $_POST['invoice_type'] : 'invoice';
		if ( isset( $_POST['get_invoices_report'] ) ) {
			if ( ! empty( $the_year ) && ! empty( $the_month ) && ! empty( $the_invoice_type ) ) {
				$result_message = $this->get_invoices_report( $the_year, $the_month, $the_invoice_type );
			} else {
				$result_message = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
		}
		?><div>
			<h3><?php echo __( 'Booster - Invoices Report', 'woocommerce-jetpack' ); ?></h3>
			<p><?php echo __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<p><form method="post" action=""><?php

				// Type
				$invoice_type_select_html = '<select name="invoice_type">';
				$invoice_types = wcj_get_enabled_invoice_types();
				foreach ( $invoice_types as $invoice_type ) {
					$invoice_type_select_html .= '<option value="' . $invoice_type['id'] . '" ' . selected( $invoice_type['id'], $the_invoice_type, false ) . '>' . $invoice_type['title'] . '</option>';
				}
				$invoice_type_select_html .= '</select>';

				$data = array(
					// Year
					array(
						__( 'Year', 'woocommerce-jetpack' ),
						'<input class="input-text" type="number" min="2000" max="2100" step="1" name="report_year" value="' . $the_year . '">',
					),
					// Month
					array(
						__( 'Month', 'woocommerce-jetpack' ),
						'<input class="input-text" type="number" min="1" max="12" step="1" name="report_month" value="' . $the_month . '">',
					),
					// Type
					array(
						__( 'Document Type', 'woocommerce-jetpack' ),
						$invoice_type_select_html,
					),
					// Get Report Button
					array(
						'',
						'<input class="button-primary" type="submit" name="get_invoices_report" value="' . __( 'Display monthly documents table', 'woocommerce-jetpack' ) . '">',
					),
					// Get Report Zip Button
					array(
						'',
						'<input class="button-primary" type="submit" name="get_invoices_report_zip" value="' . __( 'Download all monthly documents PDFs in single ZIP file', 'woocommerce-jetpack' ) . '">',
					),
				);
				// Print all
				echo wcj_get_table_html( $data, array( 'table_heading_type' => 'vertical', ) );
			?></form></p>
		</div><?php
	}

	/**
	 * get_invoices_report_zip.
	 *
	 * @version 2.5.2
	 * @since   2.3.10
	 */
	function get_invoices_report_zip( $year, $month, $invoice_type_id ) {
		$zip = new ZipArchive();
		$zip_file_name = $year . '_' . $month . '-' . $invoice_type_id . '.zip';
		$zip_file_path = sys_get_temp_dir() . '/' . $zip_file_name;
		if ( file_exists( $zip_file_path ) ) {
			unlink ( $zip_file_path );
		}
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) !== TRUE ) {
			return false;
		}

		$first_minute = mktime( 0, 0, 0, $month, 1, $year );
		$last_minute  = mktime( 23, 59, 59, $month, date( 't', $first_minute ), $year );

		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_wcj_invoicing_' . $invoice_type_id . '_date',
				'order'          => 'ASC',
//				'year'           => $year,
//				'monthnum'       => $month,
				'meta_query' => array(
					array(
						'key'     => '_wcj_invoicing_' . $invoice_type_id . '_date',
						'value'   => array( $first_minute , $last_minute ),
						'type'    => 'numeric',
						'compare' => 'BETWEEN',
					),
				),
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;
				if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
					$the_invoice = wcj_get_pdf_invoice( $order_id, $invoice_type_id );
					$file_name = $the_invoice->get_pdf( 'F' );
					$zip->addFile( $file_name, $the_invoice->get_file_name() );
				}
			endwhile;
			$offset += $block_size;
		}

		/* $output .=  "numfiles: " . $zip->numFiles . "\n";
		$output .=  "status: "   . $zip->getStatusString()   . "\n"; */
		$zip->close();

		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=" . urlencode( $zip_file_name ) );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );
		header( "Content-Description: File Transfer" );
		header( "Content-Length: " . filesize( $zip_file_path ) );
		flush(); // this doesn't really matter.
		if ( false !== ( $fp = fopen( $zip_file_path, "r" ) ) ) {
			while ( ! feof( $fp ) ) {
				echo fread( $fp, 65536 );
				flush(); // this is essential for large downloads
			}
			fclose( $fp );
		} else {
			die( __( 'Unexpected error', 'woocommerce-jetpack' ) );
		}
		return true;
	}

	/**
	 * Invoices Report function.
	 *
	 * @version 2.5.3
	 */
	function get_invoices_report( $year, $month, $invoice_type_id ) {

		$output = '';

		$data = array();
		$data[] = array(
			__( 'Document Nr.', 'woocommerce-jetpack' ),
			__( 'Document Date', 'woocommerce-jetpack' ),
			__( 'Order ID', 'woocommerce-jetpack' ),
			__( 'Customer Country', 'woocommerce-jetpack' ),
			__( 'Customer VAT ID', 'woocommerce-jetpack' ),
			__( 'Tax %', 'woocommerce-jetpack' ),
			__( 'Order Total Tax Excl.', 'woocommerce-jetpack' ),
			__( 'Order Taxes', 'woocommerce-jetpack' ),
			__( 'Order Total', 'woocommerce-jetpack' ),
			__( 'Order Currency', 'woocommerce-jetpack' ),
			__( 'Refunds', 'woocommerce-jetpack' ),
		);

		$total_sum = 0;
		$total_sum_excl_tax = 0;
		$total_tax = 0;

		$first_minute = mktime( 0, 0, 0, $month, 1, $year );
		$last_minute  = mktime( 23, 59, 59, $month, date( 't', $first_minute ), $year );

		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_wcj_invoicing_' . $invoice_type_id . '_date',
				'order'          => 'ASC',
//				'year'           => $year,
//				'monthnum'       => $month,
				'meta_query' => array(
					array(
						'key'     => '_wcj_invoicing_' . $invoice_type_id . '_date',
						'value'   => array( $first_minute , $last_minute ),
						'type'    => 'numeric',
						'compare' => 'BETWEEN',
					),
				),
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;

				if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {

					$the_order = wc_get_order( $order_id );

					$user_meta = get_user_meta( $the_order->get_user_id() );
					$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
					$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
					$customer_country = ( '' == $billing_country ) ? $shipping_country : $billing_country;
					$customer_vat_id = get_post_meta( $order_id, '_billing_eu_vat_number', true );

					$order_total = $the_order->get_total();

					$order_tax = apply_filters( 'wcj_order_total_tax', $the_order->get_total_tax(), $the_order );
//					$order_tax_percent = ( isset( $taxes_by_countries_eu[ $customer_country ] ) ) ? $taxes_by_countries_eu[ $customer_country ] : 0;
//					$order_tax_percent /= 100;
//					$order_tax = $order_total * $order_tax_percent;
					$order_total_exlc_tax = $order_total - $order_tax;
					$order_total_tax_not_rounded = $the_order->get_cart_tax() + $the_order->get_shipping_tax();
					$order_tax_percent = ( 0 == $order_total ) ? 0 : $order_total_tax_not_rounded / $order_total_exlc_tax;

					$total_sum += $order_total;
					$total_sum_excl_tax += $order_total_exlc_tax;
					$total_tax += $order_tax;

//					$order_tax_html = ( 0 == $order_tax ) ? '' : sprintf( '$ %.2f', $order_tax );
					$order_tax_html = sprintf( '%.2f', $order_tax );

					$data[] = array(
						wcj_get_invoice_number( $order_id, $invoice_type_id ),
						wcj_get_invoice_date( $order_id, $invoice_type_id, 0, get_option( 'date_format' ) ),
						$order_id,
						$customer_country,
						$customer_vat_id,
						sprintf( '%.0f %%', $order_tax_percent * 100 ),
						sprintf( '%.2f', $order_total_exlc_tax ),
						$order_tax_html,
						sprintf( '%.2f', $order_total ),
						$the_order->get_order_currency(),
						$the_order->get_total_refunded(),
						//'<pre>' . print_r( get_post_meta( $order_id ), true ) . '</pre>',
					);
				}
			endwhile;
			$offset += $block_size;
		}

		/* $output .= '<h3>' . 'Total Sum Excl. Tax: ' . sprintf( '$ %.2f', $total_sum_excl_tax ) . '</h3>';
		$output .= '<h3>' . 'Total Sum: ' . sprintf( '$ %.2f', $total_sum ) . '</h3>';
		$output .= '<h3>' . 'Total Tax: ' . sprintf( '$ %.2f', $total_tax ) . '</h3>'; */
		$output .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', ) );

//		$output .= date( "Y-m-d H:i:s", $first_minute ) . ' -> ' . date( "Y-m-d H:i:s", $last_minute );

		return $output;
	}
}

endif;

return new WCJ_PDF_Invoicing_Report_Tool();
