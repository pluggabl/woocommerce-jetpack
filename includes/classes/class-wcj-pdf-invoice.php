<?php
/**
 * Booster for WooCommerce PDF Invoice
 *
 * @version 5.3.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoice' ) ) :

class WCJ_PDF_Invoice extends WCJ_Invoice {

	private $original_internal_coding = '';

	/**
	 * Constructor.
	 */
	function __construct( $order_id, $invoice_type ) {
		parent::__construct( $order_id, $invoice_type );
	}

	/**
	 * prepare_pdf.
	 *
	 * @version 5.3.1
	 * @todo    [dev] check `addTTFfont()`
	 * @todo    [dev] maybe `$pdf->SetAuthor( 'Booster for WooCommerce' )`
	 * @todo    [dev] maybe `$pdf->setLanguageArray( $l )`
	 * @todo    [feature] (maybe) option to set different font in footer (and maybe also header)
	 */
	function prepare_pdf() {

		wcj_check_and_maybe_download_tcpdf_fonts();

		$invoice_type = $this->invoice_type;

		$page_format = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_page_format', 'A4' );
		if ( 'custom' === $page_format ) {
			$page_format = array(
				get_option( 'wcj_invoicing_' . $invoice_type . '_page_format_custom_width',  0 ),
				get_option( 'wcj_invoicing_' . $invoice_type . '_page_format_custom_height', 0 )
			);
		}

		// Create new PDF document
		require_once( wcj_plugin_path() . '/includes/classes/class-wcj-tcpdf.php' );
		$pdf = new WCJ_TCPDF(
			get_option( 'wcj_invoicing_' . $invoice_type . '_page_orientation', 'P' ),
			PDF_UNIT,
			$page_format,
			true,
			'UTF-8',
			false
		);

		$pdf->set_invoice_type( $invoice_type );

		// Set document information
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

		// Header - set default header data
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_enabled', 'yes' ) ) {
			$the_logo = '';
			$the_logo_width_mm = 0;
			if ( '' != ( $header_image = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_image', '' ) ) ) ) {
				$the_logo = parse_url( $header_image, PHP_URL_PATH );
				$the_logo_width_mm = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_image_width_mm', 50 );
				if ( ! file_exists( K_PATH_IMAGES . $the_logo ) ) {
					$the_logo = '';
					$the_logo_width_mm = 0;
				}
			}
			$pdf->SetHeaderData(
				$the_logo,
				$the_logo_width_mm,
				do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_title_text', $invoice_title ) ),
				do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_text'      , __( 'Company Name', 'woocommerce-jetpack' ) ) ),
				wcj_hex2rgb(  wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_text_color', '#cccccc' ) ),
				wcj_hex2rgb(  wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_header_line_color', '#cccccc' ) ) );
		} else {
			$pdf->SetPrintHeader( false );
		}

		// Footer
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_enabled', 'yes' ) ) {
			$pdf->setFooterData(
				wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color', '#cccccc' ) ),
				wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color', '#cccccc' ) )
			);
		} else {
			$pdf->SetPrintFooter( false );
		}

		$tcpdf_font = wcj_get_tcpdf_font( $invoice_type );

		// Set Header and Footer fonts
		$pdf->setHeaderFont( array( $tcpdf_font, '', PDF_FONT_SIZE_MAIN ) );
		$pdf->setFooterFont( array( $tcpdf_font, '', PDF_FONT_SIZE_DATA ) );

		// Set default monospaced font
		$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

		// Set margins
		$pdf->SetMargins(
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_left',  15 ),
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_top',   27 ),
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_right', 15 )
		);
		$pdf->SetHeaderMargin( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_header', 10 ) );
		$pdf->SetFooterMargin( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_footer', 10 ) );

		// Set auto page breaks
		$pdf->SetAutoPageBreak( true, wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_margin_bottom', 10 ) );

		// Set image scale factor
		$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

		// Set default font subsetting mode
		$pdf->setFontSubsetting( true );

		// Set font
		$pdf->SetFont( $tcpdf_font, '', wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_size', 8 ), '', true );

		// Add a page
		$pdf->AddPage();

		// Set text shadow effect
		if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_shadowed', 'no' ) ) {
			$pdf->setTextShadow( array( 'enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array( 196, 196, 196 ), 'opacity' => 1, 'blend_mode' => 'Normal' ) );
		}

		// Background image
		if ( '' != ( $background_image = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image', '' ) ) ) ) {
			$background_image = 'yes' === ( $parse_bkg_image = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image_parse', 'yes' ) ) ? $_SERVER['DOCUMENT_ROOT'] . parse_url( $background_image, PHP_URL_PATH ) : $background_image;
			$pdf->Image( $background_image, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight() );
		}

		return $pdf;
	}

	/**
	 * maybe_replace_tcpdf_method_params.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function maybe_replace_tcpdf_method_params( $html, $pdf ) {
		$start_str        = 'wcj_tcpdf_method_params_start';
		$end_str          = 'wcj_tcpdf_method_params_end';
		$start_str_length = strlen( $start_str );
		$end_str_length   = strlen( $end_str );
		while ( false !== ( $start = strpos( $html, $start_str ) ) ) {
			$params_start  = $start + $start_str_length;
			$params_length = strpos( $html, $end_str ) - $params_start;
			$params        = $pdf->serializeTCPDFtagParameters( unserialize( substr( $html, $params_start, $params_length ) ) );
			$html          = substr_replace( $html, 'params="' . $params . '"', $start, $start_str_length + $params_length + $end_str_length );
		}
		return $html;
	}

	/**
	 * get_html.
	 *
	 * Gets invoice content HTML.
	 *
	 * @version 4.7.0
	 * @since   3.5.0
	 * @todo    [dev] pass other params (billing_country, payment_method) as global (same as user_id) instead of $_GET
	 * @todo    [fix] `force_balance_tags()` - there are some bugs and performance issues, see http://wordpress.stackexchange.com/questions/89121/why-doesnt-default-wordpress-page-view-use-force-balance-tags
	 */
	function get_html( $order_id, $pdf ) {
		$this->original_internal_coding = mb_internal_encoding();
		if ( ! empty( $internal_encoding = wcj_get_option( 'wcj_general_advanced_mb_internal_encoding', '' ) ) ) {
			mb_internal_encoding( $internal_encoding );
		}
		$_GET['order_id'] = $order_id;
		$the_order        = wc_get_order( $order_id );
		if ( ! isset( $_GET['billing_country'] ) ) {
			$_GET['billing_country'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->billing_country : $the_order->get_billing_country() );
		}
		if ( ! isset( $_GET['payment_method'] ) ) {
			$_GET['payment_method'] = wcj_order_get_payment_method( $the_order );
		}
		global $wcj_pdf_invoice_data;
		if ( ! isset( $wcj_pdf_invoice_data['user_id'] ) ) {
			$wcj_pdf_invoice_data['user_id'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->customer_user : $the_order->get_customer_id() );
		}
		$html = do_shortcode( wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_template',
			WCJ()->modules['pdf_invoicing_templates']->get_default_template( $this->invoice_type ) ) );
		$html = $this->maybe_replace_tcpdf_method_params( $html, $pdf );
		$html = force_balance_tags( $html );
		mb_internal_encoding( $this->original_internal_coding );
		return $html;
	}

	/**
	 * get_pdf.
	 *
	 * @version 5.1.0
	 * @todo    [dev] (maybe) `die()` on success
	 */
	function get_pdf( $dest ) {
		$pdf     = $this->prepare_pdf();
		$html    = $this->get_html( $this->order_id, $pdf );
		$styling = '<style>' . wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_css',
			WCJ()->modules['pdf_invoicing_styling']->get_default_css_template( $this->invoice_type ) ) . '</style>';
		$pdf->writeHTMLCell( 0, 0, '', '', $styling . $html, 0, 1, 0, true, '', true );
		$result_pdf = $pdf->Output( '', 'S' );
		$file_name  = $this->get_file_name();
		if ( 'F' === $dest ) {
			$file_path = wcj_get_invoicing_temp_dir() . '/' . $file_name;
			if ( ! file_put_contents( $file_path, $result_pdf ) ) {
				return null;
			}
			return $file_path;
		} elseif ( 'D' === $dest || 'I' === $dest ) {
			if ( 'D' === $dest ) {
				header( "Content-Type: application/octet-stream" );
				header( "Content-Disposition: attachment; filename=" . urlencode( $file_name ) );
				header( "Content-Type: application/octet-stream" );
				header( "Content-Type: application/download" );
				header( "Content-Description: File Transfer" );
			} elseif ( 'I' === $dest ) {
				header( "Content-type: application/pdf" );
				header( "Content-Disposition: inline; filename=" . urlencode( $file_name ) );
			}
			if ( 'yes' === wcj_get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
				header( "Content-Length: " . strlen( $result_pdf ) );
				echo $result_pdf;
			} else {
				$file_path = wcj_get_invoicing_temp_dir() . '/' . $file_name;
				if ( ! file_put_contents( $file_path, $result_pdf ) ) {
					return null;
				}
				if ( apply_filters( 'wcj_invoicing_header_content_length', true ) ) {
					header( "Content-Length: " . filesize( $file_path ) );
				}
				flush(); // this doesn't really matter.
				if ( false !== ( $fp = fopen( $file_path, "r" ) ) ) {
					while ( ! feof( $fp ) ) {
						echo fread( $fp, 65536 );
						flush(); // this is essential for large downloads
					}
					fclose( $fp );
				} else {
					die( __( 'Unexpected error', 'woocommerce-jetpack' ) );
				}
			}
		}
		return null;
	}
}

endif;
