<?php
/**
 * Booster for WooCommerce - Functions - Core
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_is_plugin_active' ) ) {
	/**
	 * wcj_is_plugin_active.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @return  bool
	 * @todo    (maybe) check `.php` file only (i.e. no folder)
	 */
	function wcj_is_plugin_active( $plugin ) {
		return (
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		);
	}
}
