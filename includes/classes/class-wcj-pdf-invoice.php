<?php
/**
 * Booster for WooCommerce PDF Invoice
 *
 * @version 3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoice' ) ) :

class WCJ_PDF_Invoice extends WCJ_Invoice {

	/**
	 * Constructor.
	 */
	function __construct( $order_id, $invoice_type ) {
		parent::__construct( $order_id, $invoice_type );
	}

	/**
	 * prepare_pdf.
	 *
	 * @version 3.2.4
	 */
	function prepare_pdf() {

		wcj_check_and_maybe_download_tcpdf_fonts();

		$invoice_type = $this->invoice_type;

		$page_format = get_option( 'wcj_invoicing_' . $invoice_type . '_page_format', 'A4' );
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
//		$pdf->SetAuthor( 'Algoritmika Ltd.' );
		$invoice_title = $invoice_type;
		$invoice_types = /* ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : */ wcj_get_invoice_types();
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
		if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type . '_header_enabled', 'yes' ) ) {
			$the_logo = '';
			$the_logo_width_mm = 0;
			if ( '' != ( $header_image = do_shortcode( get_option( 'wcj_invoicing_' . $invoice_type . '_header_image', '' ) ) ) ) {
				$the_logo = parse_url( $header_image, PHP_URL_PATH );
				$the_logo_width_mm = get_option( 'wcj_invoicing_' . $invoice_type . '_header_image_width_mm', 50 );
			}
			$pdf->SetHeaderData(
				$the_logo,
				$the_logo_width_mm,
				do_shortcode( get_option( 'wcj_invoicing_' . $invoice_type . '_header_title_text', $invoice_title ) ),
				do_shortcode( get_option( 'wcj_invoicing_' . $invoice_type . '_header_text', __( 'Company Name', 'woocommerce-jetpack' ) ) ),
				wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_header_text_color', '#cccccc' ) ),
				wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_header_line_color', '#cccccc' ) ) );
		} else {
			$pdf->SetPrintHeader( false );
		}

		// Footer
		if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type . '_footer_enabled', 'yes' ) ) {
			$pdf->setFooterData(
				wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color', '#cccccc' ) ),
				wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color', '#cccccc' ) )
			);
		} else {
			$pdf->SetPrintFooter( false );
		}

		$tcpdf_font = wcj_get_tcpdf_font( $invoice_type );

		// Set Header and Footer fonts
		$pdf->setHeaderFont( Array( /* PDF_FONT_NAME_MAIN */ $tcpdf_font, '', PDF_FONT_SIZE_MAIN ) );
		$pdf->setFooterFont( Array( /* PDF_FONT_NAME_DATA */ $tcpdf_font, '', PDF_FONT_SIZE_DATA ) );

		// Set default monospaced font
		$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

		// Set margins
		$pdf->SetMargins(
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_left',  15 ),
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_top',   27 ),
			get_option( 'wcj_invoicing_' . $invoice_type . '_margin_right', 15 )
		);
		$pdf->SetHeaderMargin( get_option( 'wcj_invoicing_' . $invoice_type . '_margin_header', 10 ) );
		$pdf->SetFooterMargin( get_option( 'wcj_invoicing_' . $invoice_type . '_margin_footer', 10 ) );

		// Set auto page breaks
		$pdf->SetAutoPageBreak( TRUE, get_option( 'wcj_invoicing_' . $invoice_type . '_margin_bottom', 0 ) );

		// Set image scale factor
		$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

		/*// Set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}*/

		// Set default font subsetting mode
		$pdf->setFontSubsetting( true );

		// Set font
		/* if ( 'DroidSansFallback' === $tcpdf_font ) {
			$pdf->addTTFfont( wcj_plugin_path() . '/includes/lib/tcpdf_min/fonts/' . 'DroidSansFallback.ttf' );
		} */
		$pdf->SetFont( $tcpdf_font, '', get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_size', 8 ), '', true );

		// Add a page
		$pdf->AddPage();

		// Set text shadow effect
		if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type . '_general_font_shadowed', 'no' ) ) {
			$pdf->setTextShadow( array( 'enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array( 196, 196, 196 ), 'opacity' => 1, 'blend_mode' => 'Normal' ) );
		}

		// Background image
		if ( '' != ( $background_image = do_shortcode( get_option( 'wcj_invoicing_' . $invoice_type . '_background_image', '' ) ) ) ) {
			$background_image = parse_url( $background_image, PHP_URL_PATH );
			$pdf->Image( $background_image, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight() );
		}

		return $pdf;
	}

	/**
	 * get_pdf.
	 *
	 * @version 3.2.4
	 * @todo    pass other params (billing_country, payment_method) as global (same as user_id) instead of $_GET
	 */
	function get_pdf( $dest ) {

		// Get invoice content HTML
		$_GET['order_id'] = $this->order_id;
		$the_order = wc_get_order( $this->order_id );
		if ( ! isset( $_GET['billing_country'] ) ) {
			$_GET['billing_country'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->billing_country : $the_order->get_billing_country() );
		}
		if ( ! isset( $_GET['payment_method'] ) ) {
			$_GET['payment_method']  = wcj_order_get_payment_method( $the_order );
		}
		global $wcj_pdf_invoice_data;
		if ( ! isset( $wcj_pdf_invoice_data['user_id'] ) ) {
			$wcj_pdf_invoice_data['user_id'] = ( WCJ_IS_WC_VERSION_BELOW_3 ? $the_order->customer_user : $the_order->get_customer_id() );
		}
		$html = do_shortcode( get_option( 'wcj_invoicing_' . $this->invoice_type . '_template',
			WCJ()->modules['pdf_invoicing_templates']->get_default_template( $this->invoice_type ) ) );
		$html = force_balance_tags( $html );
		$styling = '<style>' . get_option( 'wcj_invoicing_' . $this->invoice_type . '_css',
			WCJ()->modules['pdf_invoicing_styling']->get_default_css_template( $this->invoice_type ) ) . '</style>';

		$pdf = $this->prepare_pdf();
		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell( 0, 0, '', '', $styling . $html, 0, 1, 0, true, '', true );

		// Close and output PDF document
		$result_pdf = $pdf->Output( '', 'S' );
		$file_name = $this->get_file_name();

		$tmp_dir = get_option( 'wcj_invoicing_general_tmp_dir', '' );
		if ( '' === $tmp_dir ) {
			$tmp_dir = sys_get_temp_dir();
		}

		if ( 'F' === $dest ) {
			$file_path = $tmp_dir . '/' . $file_name;
			if ( ! file_put_contents( $file_path, $result_pdf ) ) {
				return null;
			}
			return $file_path;
		}
		elseif ( 'D' === $dest || 'I' === $dest ) {
			if ( 'D' === $dest ) {
				header( "Content-Type: application/octet-stream" );
				header( "Content-Disposition: attachment; filename=" . urlencode( $file_name ) );
				header( "Content-Type: application/octet-stream" );
				header( "Content-Type: application/download" );
				header( "Content-Description: File Transfer" );
			}
			elseif ( 'I' === $dest ) {
				header( "Content-type: application/pdf" );
				header( "Content-Disposition: inline; filename=" . urlencode( $file_name ) );
			}
			if ( wcj_is_module_enabled( 'general' ) && 'yes' === get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
				header( "Content-Length: " . strlen( $result_pdf ) );
				echo $result_pdf;
			} else {

				$file_path = $tmp_dir . '/' . $file_name;
				if ( ! file_put_contents( $file_path, $result_pdf ) ) {
					return null;
				}

				header( "Content-Length: " . filesize( $file_path ) );
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
