<?php
/**
 * Booster for WooCommerce PDF Invoice
 *
 * @version 6.0.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoice' ) ) :
		/**
		 * WCJ_PDF_Invoice.
		 */
	class WCJ_PDF_Invoice extends WCJ_Invoice {
		/**
		 * Original_internal_coding.
		 *
		 * @var $original_internal_coding
		 */
		private $original_internal_coding = '';

		/**
		 * Constructor.
		 *
		 * @param int    $order_id Get order id.
		 * @param string $invoice_type Get invoice type.
		 */
		public function __construct( $order_id, $invoice_type ) {
			$this->wcj_invoice_type = $invoice_type;
			parent::__construct( $order_id, $invoice_type );
		}

		/**
		 * Prepare_pdf.
		 *
		 * @version 6.0.4
		 * @todo    [dev] check `addTTFfont()`
		 * @todo    [dev] maybe `$pdf->SetAuthor( 'Booster Elite for WooCommerce' )`
		 * @todo    [dev] maybe `$pdf->setLanguageArray( $l )`
		 * @todo    [feature] (maybe) option to set different font in footer (and maybe also header)
		 */
		public function prepare_pdf() {

			wcj_check_and_maybe_download_tcpdf_fonts();

			$invoice_type = $this->invoice_type;

			$page_format = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_page_format', 'A4' );
			if ( 'custom' === $page_format ) {
				$page_format = array(
					get_option( 'wcj_invoicing_' . $invoice_type . '_page_format_custom_width', 0 ),
					get_option( 'wcj_invoicing_' . $invoice_type . '_page_format_custom_height', 0 ),
				);
			}

			// Create new PDF document.
			$background_image = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image', '' ) );
			if ( '' !== $background_image ) {

				require_once wcj_free_plugin_path() . '/includes/classes/class-wcj-tcpdfbg.php';
			} else {
				require_once wcj_free_plugin_path() . '/includes/classes/class-wcj-tcpdf.php';

			}
			$pdf = new WCJ_TCPDF(
				get_option( 'wcj_invoicing_' . $invoice_type . '_page_orientation', 'P' ),
				PDF_UNIT,
				$page_format,
				true,
				'UTF-8',
				false
			);

			$pdf->set_invoice_type( $invoice_type );

			// Set document information.
			$pdf->SetCreator( PDF_CREATOR );
			$invoice_title = $invoice_type;
			$invoice_types = wcj_get_invoice_types();
			foreach ( $invoice_types as $invoice_type_data ) {
				if ( $invoice_type === $invoice_type_data['id'] ) {
					$invoice_title = $invoice_type_data['title'];
					break;
				}
			}
			$pdf->SetTitle( $invoice_title );
			$pdf->SetSubject( 'Invoice PDF' );
			$pdf->SetKeywords( 'invoice, PDF' );

			// Header - set default header data.
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_enabled', 'yes' ) ) {
				$the_logo          = '';
				$the_logo_width_mm = 0;
				$header_image      = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_image', '' ) );
				if ( '' !== $header_image ) {
					$the_logo          = wp_parse_url( $header_image, PHP_URL_PATH );
					$the_logo_width_mm = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_image_width_mm', 50 );
					if ( ! file_exists( K_PATH_IMAGES . $the_logo ) ) {
						$the_logo          = '';
						$the_logo_width_mm = 0;
					}
				}
				$pdf->SetHeaderData(
					$the_logo,
					$the_logo_width_mm,
					do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_title_text', $invoice_title ) ),
					do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_text', __( 'Company Name', 'woocommerce-jetpack' ) ) ),
					wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_text_color', '#cccccc' ) ),
					wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_line_color', '#cccccc' ) )
				);
			} else {
				$pdf->SetPrintHeader( false );
			}

			// Footer.
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_enabled', 'yes' ) ) {
				$pdf->setFooterData(
					wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color', '#cccccc' ) ),
					wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color', '#cccccc' ) )
				);
			} else {
				$pdf->SetPrintFooter( false );
			}

			$tcpdf_font = wcj_get_tcpdf_font( $invoice_type );

			// Set Header and Footer fonts.
			$pdf->setHeaderFont( array( $tcpdf_font, '', PDF_FONT_SIZE_MAIN ) );
			$pdf->setFooterFont( array( $tcpdf_font, '', PDF_FONT_SIZE_DATA ) );

			// Set default monospaced font.
			$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

			// Set margins.
			$pdf->SetMargins(
				get_option( 'wcj_invoicing_' . $invoice_type . '_margin_left', 15 ),
				get_option( 'wcj_invoicing_' . $invoice_type . '_margin_top', 27 ),
				get_option( 'wcj_invoicing_' . $invoice_type . '_margin_right', 15 )
			);
			$pdf->SetHeaderMargin( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_header', 10 ) );
			$pdf->SetFooterMargin( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_footer', 10 ) );

			// Set auto page breaks.
			$pdf->SetAutoPageBreak( true, wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_bottom', 10 ) );

			// Set image scale factor.
			$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

			// Set default font subsetting mode.
			$pdf->setFontSubsetting( true );

			// Set font.
			$pdf->SetFont( $tcpdf_font, '', wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_size', 8 ), '', true );

			// Add a page.
			$pdf->AddPage();

			// Set text shadow effect.
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_shadowed', 'no' ) ) {
				$pdf->setTextShadow(
					array(
						'enabled'    => true,
						'depth_w'    => 0.2,
						'depth_h'    => 0.2,
						'color'      => array( 196, 196, 196 ),
						'opacity'    => 1,
						'blend_mode' => 'Normal',
					)
				);
			}

			// Background image.
			$background_image = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image', '' ) );
			if ( '' !== $background_image ) {
				$pdf->SetPrintHeader( true );
				$parse_bkg_image  = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image_parse', 'yes' );
				$document_root    = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';
				$background_image = 'yes' === ( $parse_bkg_image ) ? $document_root . wp_parse_url( $background_image, PHP_URL_PATH ) : $background_image;
				$pdf->SetAutoPageBreak( false, 0 );
				$pdf->Image( $background_image, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), '', '', '', false, 300, '', false, false, 0 );
				$pdf->SetAutoPageBreak( true, wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_bottom', 10 ) );
				$pdf->setPageMark();
			}
			return $pdf;
		}

		/**
		 * Maybe_replace_tcpdf_method_params.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param mixed $html Get pdf html.
		 * @param mixed $pdf Get pdfs.
		 */
		public function maybe_replace_tcpdf_method_params( $html, $pdf ) {
			$start_str        = 'wcj_tcpdf_method_params_start';
			$end_str          = 'wcj_tcpdf_method_params_end';
			$start_str_length = strlen( $start_str );
			$end_str_length   = strlen( $end_str );
			$start            = strpos( $html, $start_str );
			while ( false !== $start ) {
				$params_start  = $start + $start_str_length;
				$params_length = strpos( $html, $end_str ) - $params_start;
				$params        = $pdf->serializeTCPDFtagParameters( json_decode( substr( $html, $params_start, $params_length ) ) );
				$html          = substr_replace( $html, 'params="' . $params . '"', $start, $start_str_length + $params_length + $end_str_length );
			}
			return $html;
		}

		/**
		 * Get_html.
		 *
		 * Gets invoice content HTML.
		 *
		 * @version 6.0.1
		 * @since  1.0.0
		 * @todo    [dev] pass other params (billing_country, payment_method) as global (same as user_id) instead of $_GET
		 * @todo    [fix] `force_balance_tags()` - there are some bugs and performance issues, see http://wordpress.stackexchange.com/questions/89121/why-doesnt-default-wordpress-page-view-use-force-balance-tags
		 * @param int   $order_id get order id.
		 * @param mixed $pdf Get pdfs.
		 */
		public function get_html( $order_id, $pdf ) {
			$this->original_internal_coding = mb_internal_encoding();
			$internal_encoding              = wcj_get_option( 'wcj_general_advanced_mb_internal_encoding', '' );
			if ( ! empty( $internal_encoding ) ) {
				mb_internal_encoding( $internal_encoding );
			}
			$_GET['order_id'] = $order_id;
			$the_order        = wc_get_order( $order_id );
			if ( ! isset( $_GET['billing_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$_GET['billing_country'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->billing_country : $the_order->get_billing_country() );
			}
			if ( ! isset( $_GET['payment_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$_GET['payment_method'] = wcj_order_get_payment_method( $the_order );
			}
			global $wcj_pdf_invoice_data;
			if ( ! isset( $wcj_pdf_invoice_data['user_id'] ) ) {
				$wcj_pdf_invoice_data['user_id'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->customer_user : $the_order->get_customer_id() );
			}
			$html = do_shortcode(
				wcj_get_option(
					'wcj_invoicing_' . $this->invoice_type . '_template',
					w_c_j()->all_modules['pdf_invoicing_templates']->get_default_template( $this->invoice_type )
				)
			);
			$html = $this->maybe_replace_tcpdf_method_params( $html, $pdf );
			$html = force_balance_tags( $html );
			mb_internal_encoding( $this->original_internal_coding );
			return $html;
		}

		/**
		 * Get_pdf.
		 *
		 * @version 6.0.1
		 * @todo    [dev] (maybe) `die()` on success
		 * @param string $dest define dest.
		 */
		public function get_pdf( $dest ) {
			global $wp_filesystem;
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
			$pdf     = $this->prepare_pdf();
			$html    = $this->get_html( $this->order_id, $pdf );
			$styling = '<style>' . wcj_get_option(
				'wcj_invoicing_' . $this->invoice_type . '_css',
				w_c_j()->all_modules['pdf_invoicing_styling']->get_default_css_template( $this->invoice_type )
			) . '</style>';
			$pdf->writeHTMLCell( 0, 0, '', '', $styling . $html, 0, 1, 0, true, '', true );
			// Invoice Paid Stamp.
			if ( 'invoice' === $this->invoice_type && 'yes' === wcj_get_option( 'wcj_invoicing_invoice_paid_stamp_enabled', 'yes' ) ) {
				$paid_stamp_image = '';
				if ( wcj_get_option( 'wcj_invoicing_invoice_custom_paid_stamp', 'yes' ) !== '' ) {
					$paid_stamp_image = wcj_get_option( 'wcj_invoicing_invoice_custom_paid_stamp', 'yes' );
				} else {
					$paid_stamp_image = wcj_plugin_url() . '/assets/images/paid_stamp_1.png';
				}

				$included_gateways = wcj_get_option( 'wcj_invoicing_invoice_paid_stamp_payment_gateways', array() );
				$the_order         = wc_get_order( $this->order_id );
				$payment_method    = wcj_order_get_payment_method( $the_order );

				if ( empty( $included_gateways ) ) {
					$included_gateway = true;
				} else {
					$included_gateway = in_array( $payment_method, $included_gateways, true );
				}
				if ( true === $included_gateway ) {
					$pdf->Image( $paid_stamp_image, 90, 180, 40, 40 );
				}
			}

			$result_pdf = $pdf->Output( '', 'S' );
			$file_name  = $this->get_file_name();
			if ( 'F' === $dest ) {
				$file_path = wcj_get_invoicing_temp_dir() . '/' . $file_name;
				if ( ! $wp_filesystem->put_contents( $file_path, $result_pdf, FS_CHMOD_FILE ) ) {
					return null;
				}
				return $file_path;
			} elseif ( 'D' === $dest || 'I' === $dest ) {
				if ( 'D' === $dest ) {
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Disposition: attachment; filename=' . rawurlencode( $file_name ) );
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Type: application/download' );
					header( 'Content-Description: File Transfer' );
				} elseif ( 'I' === $dest ) {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: inline; filename=' . rawurlencode( $file_name ) );
				}
				if ( 'yes' === wcj_get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
					header( 'Content-Length: ' . strlen( $result_pdf ) );
					echo $result_pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					$file_path = wcj_get_invoicing_temp_dir() . '/' . $file_name;
					if ( ! $wp_filesystem->put_contents( $file_path, $result_pdf, FS_CHMOD_FILE ) ) {
						return null;
					}
					if ( apply_filters( 'wcj_invoicing_header_content_length', true ) ) {
						header( 'Content-Length: ' . filesize( $file_path ) );
					}
					echo $wp_filesystem->get_contents( $file_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			return null;
		}
	}

endif;
