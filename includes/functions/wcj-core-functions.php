<?php
/**
 * Booster for WooCommerce - Functions - Core
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_plugin_url' ) ) {
	/**
	 * wcj_plugin_url.
	 *
	 * @version 2.3.0
	 */
	function wcj_plugin_url() {
		return untrailingslashit( plugin_dir_url( realpath( dirname( __FILE__ ) . '/..' ) ) ); // return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}
}

if ( ! function_exists( 'wcj_plugin_path' ) ) {
	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	function wcj_plugin_path() {
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) ); // return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) ) );
	}
}