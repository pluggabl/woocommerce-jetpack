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
		return untrailingslashit( plugin_dir_url( realpath( dirname( __FILE__ ) . '/..' ) ) );
	}
}

if ( ! function_exists( 'wcj_plugin_path' ) ) {
	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	function wcj_plugin_path() {
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) );
	}
}

if ( ! function_exists( 'wcj_is_module_enabled' ) ) {
	/*
	 * wcj_is_module_enabled.
	 *
	 * @version 2.9.0
	 * @since   2.4.0
	 * @return  boolean
	 */
	function wcj_is_module_enabled( $module_id ) {
		return ( 'yes' === get_option( 'wcj_' . $module_id . '_enabled', 'no' ) );
	}
}
