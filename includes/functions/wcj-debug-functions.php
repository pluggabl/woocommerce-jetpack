<?php
/**
 * WooCommerce Jetpack Debug Functions
 *
 * The WooCommerce Jetpack Debug functions.
 *
 * @version     1.0.0
 * @author      Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Debug Mode
if ( 'yes' === get_option( 'wcj_admin_tools_enabled' ) && 'yes' === get_option( 'wcj_debuging_enabled', 'no' ) ) {
	error_reporting( E_ALL );
}

/**
 * wcj_log.
 */
if ( ! function_exists( 'wcj_log' ) ) {
	function wcj_log( $message = '' ) {
		if ( 'no' === get_option( 'wcj_admin_tools_enabled' ) || 'no' === get_option( 'wcj_logging_enabled', 'no' ) ) return;
		if ( '' == $message ) $message = 'CHECKPOINT';
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}
		update_option( 'wcj_log', date( 'Y-m-d H:i:s' ) . ' ' . $_SERVER['REQUEST_URI'] . ' [' . $message . ']' . '<br>' . get_option( 'wcj_log', '' ) );
	}
}
