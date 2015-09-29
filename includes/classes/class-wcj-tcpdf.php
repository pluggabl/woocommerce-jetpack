<?php
/**
 * WooCommerce Jetpack TCPDF
 *
 * The WooCommerce Jetpack TCPDF class.
 *
 * @version 2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_TCPDF' ) ) :

// Include the main TCPDF library
require_once( wcj_plugin_path() . '/includes/lib/tcpdf_min/tcpdf.php' );

class WCJ_TCPDF extends TCPDF {
	
	/**
	 * set_invoice_type.
	 */
	public function set_invoice_type( $invoice_type ) {
		 $this->invoice_type = $invoice_type;
	}

	/**
	 * Page header.
	 *
	public function Header() {
		// Logo
		$image_file = K_PATH_IMAGES.'logo_example.jpg';
		$this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		// Set font
		$this->SetFont('helvetica', 'B', 20);
		// Title
		$this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
	}

	/**
	 * Page footer.
	 */
	public function Footer() {
		// Position at 15 mm from bottom
		//$this->SetY(-15);
		// Set font
		//$this->SetFont('helvetica', 'I', 8);

		$invoice_type = $this->invoice_type;
		$footer_text = apply_filters( 'wcj_get_option_filter', 'Page %page_number% / %total_pages%', get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text' ) );
		//$this->Cell( 0, 0, do_shortcode( $footer_text ), 0, false, 'L', 0, '', 0, false, 'T', 'M' );
		$footer_text = str_replace( '%page_number%', $this->getAliasNumPage(), $footer_text );
		$footer_text = str_replace( '%total_pages%', $this->getAliasNbPages(), $footer_text );
		$border_desc = array(
			'T' => array(
				'color' => wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color' ) ),
				'width' => 0,
			),
		);
		$footer_text_color_rgb = wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color' ) );
		$this->SetTextColor( $footer_text_color_rgb[0], $footer_text_color_rgb[1], $footer_text_color_rgb[2] );
		$this->writeHTMLCell( 0, 0, '', '', do_shortcode( $footer_text ), $border_desc, 1, 0, true, '', true );
	}
}

endif;
