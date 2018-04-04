<?php
/**
 * Booster for WooCommerce TCPDF
 *
 * @version 3.4.2
 * @author  Algoritmika Ltd.
 * @todo    (maybe) `Header()`
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_TCPDF' ) ) :

// TCPDF config
// Path for PDF fonts
if ( wcj_check_tcpdf_fonts_version( true ) ) {
	define( 'K_PATH_FONTS', wcj_get_wcj_uploads_dir( 'tcpdf_fonts' ) . '/' );
}
// Default images directory
if ( false !== ( $default_images_directory = wcj_get_invoicing_default_images_directory() ) ) {
	define ( 'K_PATH_IMAGES', $default_images_directory );
}

// Include the main TCPDF library
require_once( wcj_plugin_path() . '/includes/lib/tcpdf/tcpdf.php' );

class WCJ_TCPDF extends TCPDF {

	/**
	 * set_invoice_type.
	 */
	function set_invoice_type( $invoice_type ) {
		 $this->invoice_type = $invoice_type;
	}

	/**
	 * Page footer.
	 *
	 * @version 2.9.0
	 * @todo    (maybe) e.g. "Set font" - `$this->SetFont( 'helvetica', 'I', 8 );`
	 */
	function Footer() {
		$invoice_type = $this->invoice_type;
		$footer_text = get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text', __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ) );
		$footer_text = str_replace( '%page_number%', $this->getAliasNumPage(), $footer_text );
		$footer_text = str_replace( '%total_pages%', $this->getAliasNbPages(), $footer_text );
		$border_desc = array(
			'T' => array(
				'color' => wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color', '#cccccc' ) ),
				'width' => 0,
			),
		);
		$footer_text_color_rgb = wcj_hex2rgb( get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color', '#cccccc' ) );
		$this->SetTextColor( $footer_text_color_rgb[0], $footer_text_color_rgb[1], $footer_text_color_rgb[2] );
		$this->writeHTMLCell( 0, 0, '', '', do_shortcode( $footer_text ), $border_desc, 1, 0, true, '', true );
	}
}

endif;
