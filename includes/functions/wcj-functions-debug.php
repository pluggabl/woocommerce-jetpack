<?php
/**
 * Booster for WooCommerce - Functions - Debug
 *
 * @version 4.1.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_log' ) ) {
	/**
	 * wcj_log.
	 *
	 * @version 4.1.0
	 */
	function wcj_log( $message = '', $do_var_dump = false ) {
		if ( ! wcj_is_module_enabled( 'debug_tools' ) || ( 'no' === wcj_get_option( 'wcj_logging_enabled', 'no' ) && 'no' === wcj_get_option( 'wcj_wc_logging_enabled', 'no' ) ) ) {
			return;
		}
		if ( $do_var_dump ) {
			ob_start();
			var_dump( $message );
			$message = ob_get_clean();
		} elseif ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}
		if ( 'yes' === wcj_get_option( 'wcj_logging_enabled', 'no' ) ) {
			update_option( 'wcj_log', '<span style="color:red;">' . date( 'Y-m-d H:i:s' ) . ' ' . esc_url( $_SERVER['REQUEST_URI'] ) . '</span> <span style="color:orange;">[</span>' . $message . '<span style="color:orange;">]</span>' . '<br>' . wcj_get_option( 'wcj_log', '' ) );
		}
		// WC log
		if ( 'yes' === wcj_get_option( 'wcj_wc_logging_enabled', 'no' ) && function_exists( 'wc_get_logger' ) ) {
			if ( $log = wc_get_logger() ) {
				$log->log( 'info', $message, array( 'source' => 'booster_for_woocommerce' ) );
			}
		}
	}
}
