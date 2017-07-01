<?php
/**
 * Booster for WooCommerce - Functions - Debug
 *
 * @version 2.9.0
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
	 * @version 2.9.0
	 */
	function wcj_log( $message = '' ) {
		if ( ! wcj_is_module_enabled( 'admin_tools' ) || 'no' === get_option( 'wcj_logging_enabled', 'no' ) ) {
			return;
		}
		if ( '' == $message ) {
			$message = 'CHECKPOINT';
		}
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}
		update_option( 'wcj_log', date( 'Y-m-d H:i:s' ) . ' ' . $_SERVER['REQUEST_URI'] . ' [' . $message . ']' . '<br>' . get_option( 'wcj_log', '' ) );
	}
}
