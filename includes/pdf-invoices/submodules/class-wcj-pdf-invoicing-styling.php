<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Styling
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Styling' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Styling.
		 *
		 * @version 7.1.6
		 */
	class WCJ_PDF_Invoicing_Styling extends WCJ_Module {

		/**
		 * The module default_css_template
		 *
		 * @var array
		 */
		public $default_css_template = array();

		/**
		 * Constructor.
		 *
		 * @version 2.9.0
		 */
		public function __construct() {
			$this->id         = 'pdf_invoicing_styling';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'Styling', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );
			add_action( 'init', array( $this, 'manually_download_fonts' ) );
			add_action( 'init', array( $this, 'schedule_download_fonts_event' ) );
			add_action( 'admin_init', array( $this, 'schedule_download_fonts_event' ) );
			add_action( 'wcj_download_tcpdf_fonts_hook', array( $this, 'download_fonts' ) );
		}

		/**
		 * Get_default_css_template.
		 *
		 * @version 5.6.1
		 * @version 3.1.0
		 * @param int $invoice_type_id Get invoice ID.
		 */
		public function get_default_css_template( $invoice_type_id ) {
			if ( ! isset( $this->default_css_template[ $invoice_type_id ] ) ) {
				$default_template_filename = ( false === strpos( $invoice_type_id, 'custom_doc_' ) ? $invoice_type_id : 'custom_doc' );
				$default_template_filename = wcj_free_plugin_path() . '/includes/settings/pdf-invoicing/wcj-' . $default_template_filename . '.css';
				if ( file_exists( $default_template_filename ) ) {
					ob_start();
					include $default_template_filename;
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
		 * @version 5.5.6
		 * @version 2.9.0
		 * @todo    save `$event_timestamp` info (i.e. hook next scheduled time)
		 */
		public function schedule_download_fonts_event() {
			$interval = 'daily';
			if ( 'yes' === wcj_get_option( 'wcj_pdf_invoicing_enabled' ) ) {
				$event_hook      = 'wcj_download_tcpdf_fonts_hook';
				$event_timestamp = wp_next_scheduled( $event_hook, array( $interval ) );
				if ( ! $event_timestamp ) {
					wp_schedule_event( time(), $interval, $event_hook, array( $interval ) );
				}
			} else {
				wp_clear_scheduled_hook( 'wcj_download_tcpdf_fonts_hook', array( 'daily' ) );
			}
		}

		/**
		 * Download_fonts.
		 *
		 * @version 5.6.8
		 * @version 2.9.0
		 * @param int $interval Get time interval.
		 */
		public function download_fonts( $interval ) {
			update_option( 'wcj_download_tcpdf_fonts_hook_timestamp', wcj_get_timestamp_date_from_gmt() );
			wcj_check_and_maybe_download_tcpdf_fonts( true );
		}

		/**
		 * Manually_download_fonts.
		 *
		 * @version 5.6.8
		 * @version 2.9.0
		 * @todo    add success/error message
		 */
		public function manually_download_fonts() {
			$wpnonce = isset( $_REQUEST['wcj_download_fonts-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_download_fonts-nonce'] ), 'wcj_download_fonts' ) : false;
			if ( isset( $_GET['wcj_download_fonts'] ) && $wpnonce ) {
				delete_option( 'wcj_invoicing_fonts_version' );
				delete_option( 'wcj_invoicing_fonts_version_timestamp' );
				wcj_check_and_maybe_download_tcpdf_fonts();
				wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'wcj_download_fonts', 'wcj_download_fonts-nonce' ) ) ) );
				exit;
			}
		}

	}

endif;

return new WCJ_PDF_Invoicing_Styling();
