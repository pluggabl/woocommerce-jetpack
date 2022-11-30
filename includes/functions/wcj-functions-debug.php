<?php
/**
 * Booster for WooCommerce - Functions - Debug
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_log' ) ) {
	/**
	 * Wcj_log.
	 *
	 * @version 6.0.0
	 * @param   null | string $message defines the message.
	 * @param   bool          $do_var_dump defines the do_var_dump.
	 */
	function wcj_log( $message = '', $do_var_dump = false ) {
		// phpcs:disable WordPress.PHP.DevelopmentFunctions
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
			update_option( 'wcj_log', '<span style="color:red;">' . gmdate( 'Y-m-d H:i:s' ) . ' ' . ! empty( esc_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) . '</span> <span style="color:orange;">[</span>' . $message . '<span style="color:orange;">]</span> <br>' . wcj_get_option( 'wcj_log', '' ) );
		}
		// WC log.
		if ( 'yes' === wcj_get_option( 'wcj_wc_logging_enabled', 'no' ) && function_exists( 'wc_get_logger' ) ) {
			$log = wc_get_logger();
			if ( $log ) {
				$log->log( 'info', $message, array( 'source' => 'booster_for_woocommerce' ) );
			}
		}
		// phpcs:enable
	}
}
