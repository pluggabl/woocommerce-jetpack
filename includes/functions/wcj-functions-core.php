<?php
/**
 * Booster for WooCommerce - Functions - Core
 *
 * @version 3.4.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_is_plugin_active_simple' ) ) {
	/**
	 * Wcj_is_plugin_active_simple.
	 *
	 * @version 3.4.0
	 * @since   2.8.0
	 * @param string $plugin defines the plugin.
	 * @return  bool
	 */
	function wcj_is_plugin_active_simple( $plugin ) {
		return (
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		);
	}
}

if ( ! function_exists( 'wcj_get_active_plugins' ) ) {
	/**
	 * Wcj_get_active_plugins.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  array
	 */
	function wcj_get_active_plugins() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		return $active_plugins;
	}
}

if ( ! function_exists( 'wcj_is_plugin_active_by_file' ) ) {
	/**
	 * Wcj_is_plugin_active_by_file.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  bool
	 * @param string $plugin_file defines the plugin_file.
	 */
	function wcj_is_plugin_active_by_file( $plugin_file ) {
		foreach ( wcj_get_active_plugins() as $active_plugin ) {
			$active_plugin = explode( '/', $active_plugin );
			if ( isset( $active_plugin[1] ) && $plugin_file === $active_plugin[1] ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_is_plugin_activated' ) ) {
	/**
	 * Wcj_is_plugin_activated.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  bool
	 * @param string $plugin_folder defines the plugin_folder.
	 * @param string $plugin_file defines the plugin_file.
	 */
	function wcj_is_plugin_activated( $plugin_folder, $plugin_file ) {
		if ( wcj_is_plugin_active_simple( $plugin_folder . '/' . $plugin_file ) ) {
			return true;
		} else {
			return wcj_is_plugin_active_by_file( $plugin_file );
		}
	}
}

if ( ! function_exists( 'wcj_get_option' ) ) {
	/**
	 * Wcj_get_option.
	 *
	 * @version 5.3.3
	 * @since   5.3.3
	 *
	 * @param string $option_name define option_name.
	 * @param null   $default Get defult null value.
	 *
	 * @return  bool
	 */
	function wcj_get_option( $option_name, $default = null ) {
		if ( ! isset( w_c_j()->options[ $option_name ] ) ) {
			w_c_j()->options[ $option_name ] = get_option( $option_name, $default );
		}
		return apply_filters( $option_name, w_c_j()->options[ $option_name ] );
	}
}
