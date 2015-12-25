<?php
/**
 * WooCommerce Jetpack PDF Invoices Report Tool
 *
 * The WooCommerce Jetpack PDF Invoices Report Tool class.
 *
 * @version 2.3.10
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
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function generate_report_zip() {
		if ( isset( $_POST['get_invoices_report_zip'] ) ) {
			if ( ! empty( $_POST['report_year'] ) && ! empty( $_POST['report_month'] ) ) {
				if ( is_super_admin() || is_shop_manager() ) {
					if ( false === $this->get_invoices_report_zip( $_POST['report_year'], $_POST['report_month'] ) ) {
						$this->notice = '<div class="error"><p><strong>' . __( 'Sorry, but something went wrong...', 'woocommerce-jetpack' ) . '</strong></p></div>';
					}
				}
			} else {
				$this->notice = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * Add Invoices Report tool to WooCommerce menu (the content).
	 *
	 * @version 2.3.10
	 */
	function create_invoices_report_tool() {
		$result_message = '';
		$result_message .= $this->notice;
		$the_year  = ( ! empty( $_POST['report_year'] ) )  ? $_POST['report_year']  : '';
		$the_month = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : '';
		if ( isset( $_POST['get_invoices_report'] ) ) {
			if ( ! empty( $the_year ) && ! empty( $the_month ) ) {
				$result_message = $this->get_invoices_report( $the_year, $the_month );
			} else {
				$result_message = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
		}
		?><div>
			<h3><?php echo __( 'Booster - Invoices Report', 'woocommerce-jetpack' ); ?></h3>
			<p><?php echo __( 'Invoices Monthly Reports.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<p><form method="post" action=""><?php
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
					// Get Report Button
					array(
						'',
						'<input class="button-primary" type="submit" name="get_invoices_report" value="' . __( 'Display monthly invoices table', 'woocommerce-jetpack' ) . '">',
					),
					// Get Report Zip Button
					array(
						'',
						'<input class="button-primary" type="submit" name="get_invoices_report_zip" value="' . __( 'Download all monthly invoices PDFs in single Zip file', 'woocommerce-jetpack' ) . '">',
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
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function get_invoices_report_zip( $year, $month ) {
		$zip = new ZipArchive();
		$zip_file_name = $year . '_' . $month . '.zip';
		$zip_file_path = sys_get_temp_dir() . '/' . $zip_file_name;
		if ( file_exists( $zip_file_path ) ) {
			unlink ( $zip_file_path );
		}
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) !== TRUE ) {
			return false;
		}

		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'year'           => $year,
				'monthnum'       => $month,
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;
				$invoice_type_id = 'invoice';
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
	 * @version 2.3.10
	 */
	function get_invoices_report( $year, $month ) {

		$output = '';

		$data = array();
		$data[] = array(
			__( 'Invoice Nr.', 'woocommerce-jetpack' ),
			__( 'Invoice Date', 'woocommerce-jetpack' ),
			__( 'Order ID', 'woocommerce-jetpack' ),
			__( 'Customer Country', 'woocommerce-jetpack' ),
			__( 'Tax %', 'woocommerce-jetpack' ),
			__( 'Order Total Tax Excl.', 'woocommerce-jetpack' ),
			__( 'Order Taxes', 'woocommerce-jetpack' ),
			__( 'Order Total', 'woocommerce-jetpack' ),
			__( 'Order Currency', 'woocommerce-jetpack' ),
		);

		$total_sum = 0;
		$total_sum_excl_tax = 0;
		$total_tax = 0;

		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'year'           => $year,
				'monthnum'       => $month,
				'offset'         => $offset,
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$order_id = $loop->post->ID;
				$invoice_type_id = 'invoice';

				if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {

					$the_order = wc_get_order( $order_id );

					$user_meta = get_user_meta( $the_order->get_user_id() );
					$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
					$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
					$customer_country = ( '' == $billing_country ) ? $shipping_country : $billing_country;

					$order_total = $the_order->get_total();

					$order_tax = apply_filters( 'wcj_order_total_tax', $the_order->get_total_tax(), $the_order );
					//$order_tax_percent = ( isset( $taxes_by_countries_eu[ $customer_country ] ) ) ? $taxes_by_countries_eu[ $customer_country ] : 0;
					//$order_tax_percent /= 100;
					//$order_tax = $order_total * $order_tax_percent;
					$order_total_exlc_tax = $order_total - $order_tax;
					$order_tax_percent = ( 0 == $order_total ) ? 0 : $order_tax / $order_total_exlc_tax;

					$total_sum += $order_total;
					$total_sum_excl_tax += $order_total_exlc_tax;
					$total_tax += $order_tax;

					//$order_tax_html = ( 0 == $order_tax ) ? '' : sprintf( '$ %.2f', $order_tax );
					$order_tax_html = sprintf( '%.2f', $order_tax );

					$data[] = array(
						wcj_get_invoice_number( $order_id, $invoice_type_id ),
						wcj_get_invoice_date( $order_id, $invoice_type_id, 0, get_option( 'date_format' ) ),
						$order_id,
						$customer_country,
						sprintf( '%.0f %%', $order_tax_percent * 100 ),
						sprintf( '%.2f', $order_total_exlc_tax ),
						$order_tax_html,
						sprintf( '%.2f', $order_total ),
						$the_order->get_order_currency(),
					);
				}
			endwhile;
			$offset += $block_size;
		}

		/* $output .= '<h3>' . 'Total Sum Excl. Tax: ' . sprintf( '$ %.2f', $total_sum_excl_tax ) . '</h3>';
		$output .= '<h3>' . 'Total Sum: ' . sprintf( '$ %.2f', $total_sum ) . '</h3>';
		$output .= '<h3>' . 'Total Tax: ' . sprintf( '$ %.2f', $total_tax ) . '</h3>'; */
		$output .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', ) );
		/**/

		return $output;
	}
	/**/
}

endif;

return new WCJ_PDF_Invoicing_Report_Tool();
