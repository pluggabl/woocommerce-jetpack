<?php
/**
 * Booster for WooCommerce - Functions - Debug
 *
 * @version 3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Debug Mode
if ( wcj_is_module_enabled( 'admin_tools' ) && 'yes' === get_option( 'wcj_debuging_enabled', 'no' ) ) {
	error_reporting( E_ALL );
}

if ( ! function_exists( 'wcj_log' ) ) {
	/**
	 * wcj_log.
	 *
	 * @version 3.3.0
	 */
	function wcj_log( $message = '' ) {
		if ( ! wcj_is_module_enabled( 'admin_tools' ) || ( 'no' === get_option( 'wcj_logging_enabled', 'no' ) && 'no' === get_option( 'wcj_wc_logging_enabled', 'no' ) ) ) {
			return;
		}
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}
		if ( 'yes' === get_option( 'wcj_logging_enabled', 'no' ) ) {
			update_option( 'wcj_log', date( 'Y-m-d H:i:s' ) . ' ' . esc_url( $_SERVER['REQUEST_URI'] ) . ' [' . $message . ']' . '<br>' . get_option( 'wcj_log', '' ) );
		}
		// WC log
		if ( 'yes' === get_option( 'wcj_wc_logging_enabled', 'no' ) && function_exists( 'wc_get_logger' ) ) {
			if ( $log = wc_get_logger() ) {
				$log->log( 'info', $message, array( 'source' => 'booster_for_woocommerce' ) );
			}
		}
	}
}
