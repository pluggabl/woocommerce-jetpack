<?php
/**
 * Booster for WooCommerce - Scripts
 *
 * @version 3.4.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Scripts' ) ) :

		/**
		 * WCJ_Scripts.
		 *
		 * @version 3.4.0
		 * @since   2.9.0
		 */
	class WCJ_Scripts {

		/**
		 * Constructor.
		 *
		 * @version 3.4.0
		 * @since   2.9.0
		 */
		public function __construct() {
			// Scripts - Admin.
			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_wcj_backend_scripts' ) );
				if (
				wcj_is_module_enabled( 'export' ) ||
				wcj_is_module_enabled( 'purchase_data' ) ||
				wcj_is_module_enabled( 'pdf_invoicing' ) ||
				wcj_is_module_enabled( 'crowdfunding' ) ||
				wcj_is_module_enabled( 'reports' ) ||
				wcj_is_module_enabled( 'product_by_date' )
				) {
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts' ) );
				}
			}
			// Scripts - Frontend.
			if (
			wcj_is_module_enabled( 'product_input_fields' ) ||
			wcj_is_module_enabled( 'checkout_custom_fields' ) ||
			wcj_is_module_enabled( 'product_bookings' )
			) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
			}
		}

		/**
		 * Enqueue_wcj_backend_scripts.
		 *
		 * @version 2.5.3
		 * @since   2.5.3
		 */
		public function enqueue_wcj_backend_scripts() {
			wp_enqueue_style( 'wcj-admin', wcj_plugin_url() . '/includes/css/wcj-admin.css', array(), time() );
		}

		/**
		 * Enqueue_backend_scripts.
		 *
		 * @version 2.4.0
		 */
		public function enqueue_backend_scripts() {
			$this->maybe_enqueue_datepicker_scripts();
			$this->maybe_enqueue_datepicker_style();
		}

		/**
		 * Enqueue_frontend_scripts.
		 *
		 * @version 2.4.0
		 * @since   2.3.0
		 */
		public function enqueue_frontend_scripts() {
			$this->maybe_enqueue_datepicker_scripts();
			$this->maybe_enqueue_timepicker_scripts();
			$this->maybe_enqueue_datepicker_style();
			$this->maybe_enqueue_timepicker_style();
		}

		/**
		 * Maybe_enqueue_datepicker_scripts.
		 *
		 * @version 2.9.0
		 * @since   2.4.0
		 */
		public function maybe_enqueue_datepicker_scripts() {
			if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === wcj_get_option( 'wcj_general_advanced_disable_datepicker_js', 'no' ) ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script(
					'wcj-datepicker',
					wcj_plugin_url() . '/includes/js/wcj-datepicker.js',
					array( 'jquery' ),
					w_c_j()->version,
					true
				);
				wp_enqueue_script(
					'wcj-weekpicker',
					wcj_plugin_url() . '/includes/js/wcj-weekpicker.js',
					array( 'jquery' ),
					w_c_j()->version,
					true
				);
			}
		}

		/**
		 * Maybe_enqueue_timepicker_scripts.
		 *
		 * @version 2.9.0
		 * @since   2.4.0
		 */
		public function maybe_enqueue_timepicker_scripts() {
			if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === wcj_get_option( 'wcj_general_advanced_disable_timepicker_js', 'no' ) ) ) {
				wp_enqueue_script(
					'jquery-ui-timepicker',
					wcj_plugin_url() . '/includes/lib/timepicker/jquery.timepicker.min.js',
					array( 'jquery' ),
					w_c_j()->version,
					true
				);
				wp_enqueue_script(
					'wcj-timepicker',
					wcj_plugin_url() . '/includes/js/wcj-timepicker.js',
					array( 'jquery' ),
					w_c_j()->version,
					true
				);
			}
		}

		/**
		 * Maybe_enqueue_datepicker_style.
		 *
		 * @version 2.9.0
		 * @since   2.4.0
		 */
		public function maybe_enqueue_datepicker_style() {
			if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === wcj_get_option( 'wcj_general_advanced_disable_datepicker_css', 'no' ) ) ) {
				$datepicker_css_path = wcj_plugin_url() . '/includes/css/jquery-ui.css';
				if ( wcj_is_module_enabled( 'general' ) ) {
					$datepicker_css_path = wcj_get_option( 'wcj_general_advanced_datepicker_css', $datepicker_css_path );
				}
				wp_enqueue_style( 'jquery-ui-style', $datepicker_css_path, array(), time() );
			}
		}

		/**
		 * Maybe_enqueue_timepicker_style.
		 *
		 * @version 2.9.0
		 * @since   2.4.0
		 */
		public function maybe_enqueue_timepicker_style() {
			if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === wcj_get_option( 'wcj_general_advanced_disable_timepicker_css', 'no' ) ) ) {
				wp_enqueue_style( 'wcj-timepicker-style', wcj_plugin_url() . '/includes/lib/timepicker/jquery.timepicker.min.css', array(), w_c_j()->version );
			}
		}

	}

endif;

return new WCJ_Scripts();
