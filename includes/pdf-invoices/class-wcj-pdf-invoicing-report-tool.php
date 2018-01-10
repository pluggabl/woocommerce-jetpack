<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Report Tool
 *
 * @version 3.3.0
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Report_Tool' ) ) :

class WCJ_PDF_Invoicing_Report_Tool {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 */
	function __construct() {
		$this->notice = '';
		add_action( 'init', array( $this, 'generate_report_zip' ) );
		add_action( 'init', array( $this, 'export_csv' ) );
	}

	/**
	 * get_report_file_name.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_report_file_name( $year, $month, $invoice_type, $extension ) {
		$replaced_values = array(
			'%year%'         => $year,
			'%month%'        => sprintf( '%02d', $month ),
			'%invoice_type%' => $invoice_type,
			'%site%'         => sanitize_title( str_replace( array( 'http://', 'https://' ), '', site_url() ) ),
		);
		return str_replace( array_keys( $replaced_values ), array_values( $replaced_values ),
			get_option( 'wcj_pdf_invoicing_report_tool_filename', '%site%-%invoice_type%-%year%_%month%' ) ) . '.' . $extension;
	}

	/**
	 * generate_report_zip.
	 *
	 * @version 3.1.0
	 * @since   2.3.10
	 */
	function generate_report_zip() {
		if ( isset( $_POST['get_invoices_report_zip'] ) ) {
			if ( wcj_is_module_enabled( 'general' ) && 'yes' === get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
				$this->notice = '<div class="error"><p><strong>' . sprintf(
					__( 'This option is disabled with "Disable Saving PDFs in PHP directory for temporary files" checkbox in <a href="%s" target="_blank">WooCommerce > Settings > Booster > Emails & Misc. > General > Advanced Options</a>.', 'woocommerce-jetpack' ),
					admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ) .
				'</strong></p></div>';
			} else {
				$_year         = ( ! empty( $_POST['report_year'] ) )  ? $_POST['report_year']  : date( 'Y' );
				$_month        = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : date( 'n' );
				$_invoice_type = ( ! empty( $_POST['invoice_type'] ) ) ? $_POST['invoice_type'] : 'invoice';
				if ( ! empty( $_year ) && ! empty( $_month ) && ! empty( $_invoice_type ) ) {
					if ( wcj_is_user_role( 'administrator' ) || is_shop_manager() ) {
						$result = $this->get_invoices_report_zip( $_year, $_month, $_invoice_type );
						if ( false === $result ) {
							$this->notice = '<div class="error"><p><strong>' . __( 'Sorry, but something went wrong...', 'woocommerce-jetpack' ) . '</strong></p></div>';
						} elseif ( true !== $result ) {
							$this->notice = '<div class="error"><p><strong>' . $result . '</strong></p></div>';
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
	 * @version 3.1.0
	 */
	function create_invoices_report_tool() {
		$result_message = '';
		$result_message .= $this->notice;
		$the_year         = ( ! empty( $_POST['report_year'] ) )  ? $_POST['report_year']  : date( 'Y' );
		$the_month        = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : date( 'n' );
		$the_invoice_type = ( ! empty( $_POST['invoice_type'] ) ) ? $_POST['invoice_type'] : 'invoice';
		if ( isset( $_POST['get_invoices_report'] ) ) {
			if ( ! empty( $the_year ) && ! empty( $the_month ) && ! empty( $the_invoice_type ) ) {
				$result_message = $this->get_invoices_report( $the_year, $the_month, $the_invoice_type );
			} else {
				$result_message = '<div class="error"><p><strong>' . __( 'Please fill year and month values.', 'woocommerce-jetpack' ) . '</strong></p></div>';
			}
		}
		$html = '';
		$html .= '<div class="wrap">';
		$html .= WCJ()->modules['pdf_invoicing']->get_tool_header_html( 'invoices_report' );
		$html .= $result_message;
		$html .= '<p><form method="post" action="">';
		// Type
		$invoice_type_select_html = '<select name="invoice_type" class="widefat">';
		$invoice_types            = wcj_get_enabled_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {
			$invoice_type_select_html .= '<option value="' . $invoice_type['id'] . '" ' . selected( $invoice_type['id'], $the_invoice_type, false ) . '>' .
				$invoice_type['title'] . '</option>';
		}
		$invoice_type_select_html .= '</select>';
		$data = array(
			// Year
			array(
				__( 'Year', 'woocommerce-jetpack' ),
				'<input class="input-text widefat" type="number" min="2000" max="2100" step="1" name="report_year" value="' . $the_year . '">',
			),
			// Month
			array(
				__( 'Month', 'woocommerce-jetpack' ),
				'<input class="input-text widefat" type="number" min="1" max="12" step="1" name="report_month" value="' . $the_month . '">',
			),
			// Type
			array(
				__( 'Document Type', 'woocommerce-jetpack' ),
				$invoice_type_select_html,
			),
			// Get Report Button
			array(
				'',
				'<input class="button-primary" type="submit" name="get_invoices_report" value="' .
					__( 'Display monthly documents table', 'woocommerce-jetpack' ) . '">',
			),
			// Get Report Zip Button
			array(
				'',
				'<input class="button-primary" type="submit" name="get_invoices_report_zip" value="' .
					__( 'Download all monthly documents PDFs in single ZIP file', 'woocommerce-jetpack' ) . '">',
			),
			// Get Report CSV Button
			array(
				'',
				'<input class="button-primary" type="submit" name="get_invoices_report_csv" value="' .
					__( 'Download monthly documents CSV', 'woocommerce-jetpack' ) . '">',
			),
		);
		$html .= wcj_get_table_html( $data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical' ) );
		$html .= '</form></p>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * get_invoices_report_zip.
	 *
	 * @version 3.1.0
	 * @since   2.3.10
	 */
	function get_invoices_report_zip( $year, $month, $invoice_type_id ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return sprintf( __( 'Booster: %s class is not accessible on your server. Please contact your hosting provider.', 'woocommerce-jetpack' ),
				'<a target="_blank" href="http://php.net/manual/en/class.ziparchive.php">PHP ZipArchive</a>' );
		}
		$zip           = new ZipArchive();
		$zip_file_name = $this->get_report_file_name( $year, $month, $invoice_type_id, 'zip' );
		$zip_file_path = sys_get_temp_dir() . '/' . $zip_file_name;
		if ( file_exists( $zip_file_path ) ) {
			unlink ( $zip_file_path );
		}
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) !== TRUE ) {
			return false;
		}

		$first_minute = mktime( 0, 0, 0, $month, 1, $year );
		$last_minute  = mktime( 23, 59, 59, $month, date( 't', $first_minute ), $year );

		$archive_is_empty = true;

		$offset     = 0;
		$block_size = 512;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_wcj_invoicing_' . $invoice_type_id . '_date',
				'order'          => 'ASC',
				'meta_query' => array(
					array(
						'key'     => '_wcj_invoicing_' . $invoice_type_id . '_date',
						'value'   => array( $first_minute , $last_minute ),
						'type'    => 'numeric',
						'compare' => 'BETWEEN',
					),
				),
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $order_id ) {
				if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
					$the_invoice = wcj_get_pdf_invoice( $order_id, $invoice_type_id );
					$file_name   = $the_invoice->get_pdf( 'F' );
					$zip->addFile( $file_name, $the_invoice->get_file_name() );
					$archive_is_empty = false;
				}
			}
			$offset += $block_size;
		}

		$zip->close();

		if ( $archive_is_empty ) {
			return $this->get_no_documents_found_message( $year, $month, $invoice_type_id );
		}

		header( "Content-Disposition: attachment; filename=" . urlencode( $zip_file_name ) );
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
			exit();
		} else {
			die( __( 'Unexpected error', 'woocommerce-jetpack' ) );
		}
		return true;
	}

	/**
	 * get_no_documents_found_message.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_no_documents_found_message( $year, $month, $invoice_type_id ) {
		return sprintf( __( 'No documents (%s) found for %d-%02d.', 'woocommerce-jetpack' ), $invoice_type_id, $year, $month );
	}

	/**
	 * export_csv.
	 *
	 * @version 3.1.0
	 * @since   2.5.7
	 */
	function export_csv() {
		$_year         = ( ! empty( $_POST['report_year'] ) )  ? $_POST['report_year']  : date( 'Y' );
		$_month        = ( ! empty( $_POST['report_month'] ) ) ? $_POST['report_month'] : date( 'm' );
		$_invoice_type = ( ! empty( $_POST['invoice_type'] ) ) ? $_POST['invoice_type'] : 'invoice';
		if ( isset( $_POST['get_invoices_report_csv'] ) ) {
			$data = $this->get_invoices_report_data( $_year, $_month, $_invoice_type );
			if ( empty( $data ) ) {
				$this->notice = '<div class="error"><p><strong>' . $this->get_no_documents_found_message( $_year, $_month, $_invoice_type ) . '</strong></p></div>';
				return false;
			}
			$csv  = '';
			$sep  = get_option( 'wcj_pdf_invoicing_report_tool_csv_separator', ';' );
			$replace_periods_with_commas = ( 'yes' === get_option( 'wcj_pdf_invoicing_report_tool_csv_replace_periods_w_commas', 'yes' ) );
			foreach ( $data as $row ) {
				if ( $replace_periods_with_commas ) {
					$row = str_replace( '.', ',', $row );
				}
				$csv .= implode( $sep, $row ) . PHP_EOL;
			}
			if ( 'yes' === get_option( 'wcj_pdf_invoicing_report_tool_csv_add_utf_8_bom', 'yes' ) ) {
				$csv = "\xEF\xBB\xBF" . $csv; // UTF-8 BOM
			}
			$filename = $this->get_report_file_name( $_year, $_month, $_invoice_type, 'csv' );
			header( "Content-Disposition: attachment; filename=" . $filename );
			header( "Content-Type: Content-Type: text/html; charset=utf-8" );
			header( "Content-Description: File Transfer" );
			header( "Content-Length: " . strlen( $csv ) );
			echo $csv;
			die();
		}
	}

	/**
	 * Invoices Report function.
	 *
	 * @version 3.1.0
	 */
	function get_invoices_report( $year, $month, $invoice_type_id ) {
		$data = $this->get_invoices_report_data( $year, $month, $invoice_type_id );
		return ( ! empty( $data ) ?
			wcj_get_table_html( $data, array( 'table_class' => 'widefat' ) ) :
			'<div class="error"><p><strong>' . $this->get_no_documents_found_message( $year, $month, $invoice_type_id ) . '</strong></p></div>' );
	}

	/**
	 * Invoices Report Data function.
	 *
	 * @version 3.3.0
	 * @since   2.5.7
	 */
	function get_invoices_report_data( $year, $month, $invoice_type_id ) {

		$columns = get_option( 'wcj_pdf_invoicing_report_tool_columns', '' );
		if ( empty( $columns ) ) {
			$columns = array_keys( WCJ()->modules['pdf_invoicing_advanced']->get_report_columns() );
		}

		$total_sum          = 0;
		$total_sum_excl_tax = 0;
		$total_tax          = 0;

		$first_minute = mktime( 0, 0, 0, $month, 1, $year );
		$last_minute  = mktime( 23, 59, 59, $month, date( 't', $first_minute ), $year );

		$tax_percent_format = '%.' . get_option( 'wcj_pdf_invoicing_report_tool_tax_percent_precision', 0 ) . 'f %%';

		$data       = array();
		$offset     = 0;
		$block_size = 512;
		while( true ) {
			$args = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_wcj_invoicing_' . $invoice_type_id . '_date',
				'order'          => 'ASC',
				'meta_query' => array(
					array(
						'key'     => '_wcj_invoicing_' . $invoice_type_id . '_date',
						'value'   => array( $first_minute , $last_minute ),
						'type'    => 'numeric',
						'compare' => 'BETWEEN',
					),
				),
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $order_id ) {

				if ( wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {

					$the_order = wc_get_order( $order_id );

					$user_meta        = get_user_meta( $the_order->get_user_id() );
					$billing_country  = isset( $user_meta['billing_country'][0] )  ? $user_meta['billing_country'][0]  : '';
					$shipping_country = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
					$customer_country = ( '' == $billing_country ) ? $shipping_country : $billing_country;
					$customer_vat_id  = get_post_meta( $order_id, '_billing_eu_vat_number', true );

					$order_total = $the_order->get_total();

					$order_tax                   = apply_filters( 'wcj_order_total_tax', $the_order->get_total_tax(), $the_order );
					$order_total_exlc_tax        = $order_total - $order_tax;
					$order_total_tax_not_rounded = $the_order->get_cart_tax() + $the_order->get_shipping_tax();
					$order_tax_percent           = ( 0 == $order_total_exlc_tax ? 0 : $order_total_tax_not_rounded / $order_total_exlc_tax );

					$total_sum          += $order_total;
					$total_sum_excl_tax += $order_total_exlc_tax;
					$total_tax          += $order_tax;

					$order_cart_tax                = $the_order->get_cart_tax();
					$order_shipping_tax            = $the_order->get_shipping_tax();
					$order_cart_total_excl_tax     = 0;
					foreach ( $the_order->get_items() as $item ) {
						$order_cart_total_excl_tax += $item->get_total();
					}
					$order_shipping_total_excl_tax = $the_order->get_shipping_total();
					$order_cart_tax_percent        = ( 0 == $order_cart_total_excl_tax ? 0 : $order_cart_tax / $order_cart_total_excl_tax );
					$order_shipping_tax_percent    = ( 0 == $order_shipping_total_excl_tax ? 0 : $order_shipping_tax / $order_shipping_total_excl_tax );

					$row = array();
					foreach ( $columns as $column ) {
						switch ( $column ) {
							case 'document_number':
								$row[] = wcj_get_invoice_number( $order_id, $invoice_type_id );
								break;
							case 'document_date':
								$row[] = wcj_get_invoice_date( $order_id, $invoice_type_id, 0, get_option( 'date_format' ) );
								break;
							case 'order_id':
								$row[] = $order_id;
								break;
							case 'customer_country':
								$row[] = $customer_country;
								break;
							case 'customer_vat_id':
								$row[] = $customer_vat_id;
								break;
							case 'tax_percent':
								$row[] = sprintf( $tax_percent_format, $order_tax_percent * 100 );
								break;
							case 'order_total_tax_excluding':
								$row[] = sprintf( '%.2f', $order_total_exlc_tax );
								break;
							case 'order_taxes':
								$row[] = sprintf( '%.2f', $order_tax );
								break;
							case 'order_cart_total_excl_tax':
								$row[] = sprintf( '%.2f', $order_cart_total_excl_tax );
								break;
							case 'order_cart_tax':
								$row[] = sprintf( '%.2f', $order_cart_tax );
								break;
							case 'order_cart_tax_percent':
								$row[] = sprintf( $tax_percent_format, $order_cart_tax_percent * 100 );
								break;
							case 'order_shipping_total_excl_tax':
								$row[] = sprintf( '%.2f', $order_shipping_total_excl_tax );
								break;
							case 'order_shipping_tax':
								$row[] = sprintf( '%.2f', $order_shipping_tax );
								break;
							case 'order_shipping_tax_percent':
								$row[] = sprintf( $tax_percent_format, $order_shipping_tax_percent * 100 );
								break;
							case 'order_total':
								$row[] = sprintf( '%.2f', $order_total );
								break;
							case 'order_currency':
								$row[] = wcj_get_order_currency( $the_order );
								break;
							case 'payment_gateway':
								$row[] = get_post_meta( $order_id, '_payment_method_title', true );
								break;
							case 'refunds':
								$row[] = $the_order->get_total_refunded();
								break;
						}
					}
					$data[] = $row;
				}
			}
			$offset += $block_size;
		}

		$headers = $this->get_headers( $columns );

		return ( ! empty( $data ) ? array_merge( array( $headers ), $data ) : array() );
	}

	/**
	 * get_headers.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function get_headers( $columns ) {
		$headers = array();
		$all_headers = WCJ()->modules['pdf_invoicing_advanced']->get_report_columns();
		foreach ( $columns as $column ) {
			$headers[] = $all_headers[ $column ];
		}
		return $headers;
	}


}

endif;

return new WCJ_PDF_Invoicing_Report_Tool();
