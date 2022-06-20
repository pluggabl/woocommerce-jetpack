<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Page Settings
 *
 * @version 2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Page' ) ) :
	/**
	 * WCJ_PDF_Invoicing_Page.
	 *
	 * @version 2.4.0
	 */
	class WCJ_PDF_Invoicing_Page extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 2.4.0
		 */
		public function __construct() {
			$this->id         = 'pdf_invoicing_page';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'Page Settings', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );
		}

		/**
		 * Get_page_formats.
		 *
		 * @version 2.4.7
		 * @since   2.4.7
		 */
		public function get_page_formats() {
			$page_formats = array(
				// ISO 216 A Series + 2 SIS 014711 extensions.
				'A0',
				'A1',
				'A2',
				'A3',
				'A4',
				'A5',
				'A6',
				'A7',
				'A8',
				'A9',
				'A10',
				'A11',
				'A12',
				// ISO 216 B Series + 2 SIS 014711 extensions.
				'B0',
				'B1',
				'B2',
				'B3',
				'B4',
				'B5',
				'B6',
				'B7',
				'B8',
				'B9',
				'B10',
				'B11',
				'B12',
				// ISO 216 C Series + 2 SIS 014711 extensions + 2 EXTENSION.
				'C0',
				'C1',
				'C2',
				'C3',
				'C4',
				'C5',
				'C6',
				'C7',
				'C8',
				'C9',
				'C10',
				'C11',
				'C12',
				'C76',
				'DL',
				// SIS 014711 E Series.
				'E0',
				'E1',
				'E2',
				'E3',
				'E4',
				'E5',
				'E6',
				'E7',
				'E8',
				'E9',
				'E10',
				'E11',
				'E12',
				// SIS 014711 G Series.
				'G0',
				'G1',
				'G2',
				'G3',
				'G4',
				'G5',
				'G6',
				'G7',
				'G8',
				'G9',
				'G10',
				'G11',
				'G12',
				// ISO Press.
				'RA0',
				'RA1',
				'RA2',
				'RA3',
				'RA4',
				'SRA0',
				'SRA1',
				'SRA2',
				'SRA3',
				'SRA4',
				// German  DIN 476.
				'4A0',
				'2A0',
				// Variations on the ISO Standard.
				'A2_EXTRA',
				'A3+',
				'A3_EXTRA',
				'A3_SUPER',
				'SUPER_A3',
				'A4_EXTRA',
				'A4_SUPER',
				'SUPER_A4',
				'A4_LONG',
				'F4',
				'SO_B5_EXTRA',
				'A5_EXTRA',
				// ANSI Series.
				'ANSI_E',
				'ANSI_D',
				'ANSI_C',
				'ANSI_B',
				'ANSI_A',
				// Traditional 'Loose' North American Paper Sizes.
				'USLEDGER',
				'LEDGER',
				'ORGANIZERK',
				'BIBLE',
				'USTABLOID',
				'TABLOID',
				'ORGANIZERM',
				'USLETTER',
				'LETTER',
				'USLEGAL',
				'LEGAL',
				'GOVERNMENTLETTER',
				'GLETTER',
				'JUNIORLEGAL',
				'JLEGAL',
				// Other North American Paper Sizes.
				'QUADDEMY',
				'SUPER_B',
				'QUARTO',
				'GOVERNMENTLEGAL',
				'FOLIO',
				'MONARCH',
				'EXECUTIVE',
				'ORGANIZERL',
				'STATEMENT',
				'MEMO',
				'FOOLSCAP',
				'COMPACT',
				'ORGANIZERJ',
				// Canadian standard CAN 2-9.60M.
				'P1',
				'P2',
				'P3',
				'P4',
				'P5',
				'P6',
				// North American Architectural Sizes.
				'ARCH_E',
				'ARCH_E1',
				'ARCH_D',
				'BROADSHEET',
				'ARCH_C',
				'ARCH_B',
				'ARCH_A',
				// --- North American Envelope Sizes ---.
				// - Announcement Envelopes.
				'ANNENV_A2',
				'ANNENV_A6',
				'ANNENV_A7',
				'ANNENV_A8',
				'ANNENV_A10',
				'ANNENV_SLIM',
				// - Commercial Envelopes.
				'COMMENV_N6_1/4',
				'COMMENV_N6_3/4',
				'COMMENV_N8',
				'COMMENV_N9',
				'COMMENV_N10',
				'COMMENV_N11',
				'COMMENV_N12',
				'COMMENV_N14',
				// - Catalogue Envelopes.
				'CATENV_N1',
				'CATENV_N1_3/4',
				'CATENV_N2',
				'CATENV_N3',
				'CATENV_N6',
				'CATENV_N7',
				'CATENV_N8',
				'CATENV_N9_1/2',
				'CATENV_N9_3/4',
				'CATENV_N10_1/2',
				'CATENV_N12_1/2',
				'CATENV_N13_1/2',
				'CATENV_N14_1/4',
				'CATENV_N14_1/2',
				// Japanese (JIS P 0138-61) Standard B-Series.
				'JIS_B0',
				'JIS_B1',
				'JIS_B2',
				'JIS_B3',
				'JIS_B4',
				'JIS_B5',
				'JIS_B6',
				'JIS_B7',
				'JIS_B8',
				'JIS_B9',
				'JIS_B10',
				'JIS_B11',
				'JIS_B12',
				// PA Series.
				'PA0',
				'PA1',
				'PA2',
				'PA3',
				'PA4',
				'PA5',
				'PA6',
				'PA7',
				'PA8',
				'PA9',
				'PA10',
			);

			$page_formats_options = array();
			foreach ( $page_formats as $page_format ) {
				$page_formats_options[ $page_format ] = $page_format;
			}
			return $page_formats_options;
		}

	}

endif;

return new WCJ_PDF_Invoicing_Page();
