<?php
/**
 * Booster for WooCommerce PDF Invoice
 *
 * @version 3.1.0
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
	 * @version 3.1.0
	 */
	function prepare_pdf() {

		wcj_check_and_maybe_download_tcpdf_fonts();

		$invoice_type = $this->invoice_type;

		// Create new PDF document
		require_once( wcj_plugin_path() . '/includes/classes/class-wcj-tcpdf.php' );
		$pdf = new WCJ_TCPDF(
			get_option( 'wcj_invoicing_' . $invoice_type . '_page_orientation', 'P' ),
			PDF_UNIT,
			//PDF_PAGE_FORMAT,
			get_option( 'wcj_invoicing_' . $invoice_type . '_page_format', 'A4' ),
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
			if ( '' != get_option( 'wcj_invoicing_' . $invoice_type . '_header_image', '' ) ) {
				$the_logo = parse_url( get_option( 'wcj_invoicing_' . $invoice_type . '_header_image', '' ), PHP_URL_PATH );
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

		return $pdf;
	}

	/**
	 * get_pdf.
	 *
	 * @version 3.1.0
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

		/*
		// set style for barcode
		$style = array(
			'border' => true,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);

		// -------------------------------------------------------------------
		// PDF417 (ISO/IEC 15438:2006)

		/*

		 The $type parameter can be simple 'PDF417' or 'PDF417' followed by a
		 number of comma-separated options:

		 'PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6'

		 Possible options are:

			 a  = aspect ratio (width/height);
			 e  = error correction level (0-8);

			 Macro Control Block options:

			 t  = total number of macro segments;
			 s  = macro segment index (0-99998);
			 f  = file ID;
			 o0 = File Name (text);
			 o1 = Segment Count (numeric);
			 o2 = Time Stamp (numeric);
			 o3 = Sender (text);
			 o4 = Addressee (text);
			 o5 = File Size (numeric);
			 o6 = Checksum (numeric).

		 Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional.
		 To use a comma character ',' on text options, replace it with the character 255: "\xff".

		*//*

		$pdf->write2DBarcode( 'www.woojetpack.com', 'PDF417', 0, 200, 0, 30, $style, 'T');
		//$pdf->Text(80, 85, 'PDF417 (ISO/IEC 15438:2006)');

		// -------------------------------------------------------------------
		/**
		require_once( wcj_plugin_path() .'/includes/lib/tcpdf_min/tcpdf_barcodes_2d.php');
		$barcodeobj = new TCPDF2DBarcode('http://www.tcpdf.org', 'PDF417');
		// output the barcode as PNG image
		//$barcodeobj->getBarcodePNG(4, 4, array(0,0,0));
		$html = $barcodeobj->getBarcodeHTML(4, 4, 'black');
		//$pdf->writeHTMLCell( 0, 0, '', '', $html, 0, 1, 0, true, '', true );
		/**/

		// Close and output PDF document
		$result_pdf = $pdf->Output( '', 'S' );
		$file_name = $this->get_file_name();

		if ( 'F' === $dest ) {
			$file_path = sys_get_temp_dir() . '/' . $file_name;
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

				$file_path = sys_get_temp_dir() . '/' . $file_name;
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
