<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Styling
 *
 * @version 3.1.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Styling' ) ) :

class WCJ_PDF_Invoicing_Styling extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 */
	function __construct() {
		$this->id         = 'pdf_invoicing_styling';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Styling', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );
		add_action( 'init',                          array( $this, 'manually_download_fonts' ) );
		add_action( 'init',                          array( $this, 'schedule_download_fonts_event' ) );
		add_action( 'admin_init',                    array( $this, 'schedule_download_fonts_event' ) );
		add_action( 'wcj_download_tcpdf_fonts_hook', array( $this, 'download_fonts' ) );
	}

	/**
	 * get_default_css_template.
	 *
	 * @version 3.1.0
	 * @version 3.1.0
	 */
	function get_default_css_template( $invoice_type_id ) {
		if ( ! isset( $this->default_css_template[ $invoice_type_id ] ) ) {
			$default_template_filename = ( false === strpos( $invoice_type_id, 'custom_doc_' ) ? $invoice_type_id : 'custom_doc' );
			$default_template_filename = wcj_plugin_path() . '/includes/settings/pdf-invoicing/wcj-' . $default_template_filename . '.css';
			if ( file_exists( $default_template_filename ) ) {
				ob_start();
				include( $default_template_filename );
				$this->default_css_template[ $invoice_type_id ] = ob_get_clean();
			} else {
				$this->default_css_template[ $invoice_type_id ] = '';
			}
		}
		return $this->default_css_template[ $invoice_type_id ];
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 *
	 * @version 2.9.0
	 * @version 2.9.0
	 * @todo    save `$event_timestamp` info (i.e. hook next scheduled time)
	 */
	function schedule_download_fonts_event() {
		$interval        = 'hourly';
		$event_hook      = 'wcj_download_tcpdf_fonts_hook';
		$event_timestamp = wp_next_scheduled( $event_hook, array( $interval ) );
		if ( ! $event_timestamp ) {
			wp_schedule_event( time(), $interval, $event_hook, array( $interval ) );
		}
	}

	/**
	 * download_fonts.
	 *
	 * @version 2.9.0
	 * @version 2.9.0
	 */
	function download_fonts( $interval ) {
		update_option( 'wcj_download_tcpdf_fonts_hook_timestamp', (int) current_time( 'timestamp' ) );
		wcj_check_and_maybe_download_tcpdf_fonts( true );
	}

	/**
	 * manually_download_fonts.
	 *
	 * @version 2.9.0
	 * @version 2.9.0
	 * @todo    add success/error message
	 */
	function manually_download_fonts() {
		if ( isset( $_GET['wcj_download_fonts'] ) ) {
			delete_option( 'wcj_invoicing_fonts_version' );
			delete_option( 'wcj_invoicing_fonts_version_timestamp' );
			wcj_check_and_maybe_download_tcpdf_fonts();
			wp_safe_redirect( remove_query_arg( 'wcj_download_fonts' ) );
			exit;
		}
	}

}

endif;

return new WCJ_PDF_Invoicing_Styling();
