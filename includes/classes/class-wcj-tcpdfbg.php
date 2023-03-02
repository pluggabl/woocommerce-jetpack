<?php
/**
 * Booster for WooCommerce - TCPDF
 *
 * @version 6.0.4
 * @author  Pluggabl LLC.
 * @todo    (maybe) `Header()`
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_TCPDF' ) ) :

	// Enable custom TCPDF config.
	define( 'K_TCPDF_EXTERNAL_CONFIG', true );
	require_once wcj_free_plugin_path() . '/includes/lib/tcpdf_config.php';

	// Include TCPDF library.
	if ( ! class_exists( 'TCPDF' ) ) {
		require_once wcj_free_plugin_path() . '/includes/lib/tcpdf/tcpdf.php';
	}
	/**
	 * WCJ_TCPDF.
	 *
	 * @version 6.0.0
	 */
	class WCJ_TCPDF extends TCPDF {

		/**
		 * Set_invoice_type.
		 *
		 * @param string $invoice_type Get invoice type.
		 */
		public function set_invoice_type( $invoice_type ) {
			$this->invoice_type = $invoice_type;
		}

		/**
		 * Page footer.
		 *
		 * @version 6.0.0
		 * @todo    (maybe) e.g. "Set font" - `$this->SetFont( 'helvetica', 'I', 8 );`
		 */
		public function Footer() {
			$invoice_type          = $this->invoice_type;
			$footer_text           = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text', __( 'Page %page_number% / %total_pages%', 'woocommerce-jetpack' ) );
			$footer_text           = str_replace( '%page_number%', $this->getAliasNumPage(), $footer_text );
			$footer_text           = str_replace( '%total_pages%', $this->getAliasNbPages(), $footer_text );
			$border_desc           = array(
				'T' => array(
					'color' => wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_line_color', '#cccccc' ) ),
					'width' => 0,
				),
			);
			$footer_text_color_rgb = wcj_hex2rgb( wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_footer_text_color', '#cccccc' ) );
			$this->SetTextColor( $footer_text_color_rgb[0], $footer_text_color_rgb[1], $footer_text_color_rgb[2] );
			$this->writeHTMLCell( 0, 0, '', '', do_shortcode( $footer_text ), $border_desc, 1, 0, true, '', true );
		}

		/**
		 * Page Header.
		 *
		 * @version 6.0.3
		 * @todo    (maybe) e.g. "Set font" - `$this->SetFont( 'helvetica', 'I', 8 );`
		 */
		public function Header() {
			$invoice_type     = $this->invoice_type;
			$background_image = get_option( 'wcj_invoicing_' . $invoice_type . '_background_image', '' );
			if ( '' !== $background_image ) {
				$parse_bkg_image  = wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_background_image_parse', 'yes' );
				$document_root    = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';
				$background_image = 'yes' === ( $parse_bkg_image ) ? $document_root . wp_parse_url( $background_image, PHP_URL_PATH ) : $background_image;
			}
			// get the current page break margin.
			$b_margin = $this->getBreakMargin();
			// get current auto-page-break mode.
			$auto_page_break = 'AutoPageBreak';
			$auto_page_break = $this->$auto_page_break;
			// disable auto-page-break.
			$this->SetAutoPageBreak( false, 0 );
			$this->Image( $background_image, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0 );
			// restore auto-page-break status.
			$this->SetAutoPageBreak( $auto_page_break, $b_margin );
			// set the starting point for the page content.
			$this->setPageMark();
		}
	}

endif;
