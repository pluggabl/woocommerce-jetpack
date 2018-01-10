<?php
/**
 * Booster for WooCommerce - Functions - Booster Core
 *
 * @version 3.3.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_plugin_url' ) ) {
	/**
	 * wcj_plugin_url.
	 *
	 * @version 2.3.0
	 * @todo    (maybe) add `WCJ_PLUGIN_URL` constant instead
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
	 * @todo   use `WCJ_PLUGIN_PATH` constant instead
	 */
	function wcj_plugin_path() {
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) );
	}
}

if ( ! function_exists( 'wcj_is_module_enabled' ) ) {
	/*
	 * wcj_is_module_enabled.
	 *
	 * @version 3.3.0
	 * @since   2.4.0
	 * @return  boolean
	 */
	function wcj_is_module_enabled( $module_id ) {
		if ( 'modules_by_user_roles' != $module_id && wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
			global $wcj_modules_by_user_roles_data;
			if ( ! isset( $wcj_modules_by_user_roles_data ) ) {
				if( ! function_exists( 'wp_get_current_user' ) ) {
					require_once( ABSPATH . 'wp-includes/pluggable.php' );
				}
				$current_user = wp_get_current_user();
				$wcj_modules_by_user_roles_data['role'] = ( isset( $current_user->roles ) && is_array( $current_user->roles ) && ! empty( $current_user->roles ) ?
					reset( $current_user->roles ) : 'guest' );
				$wcj_modules_by_user_roles_data['role'] = ( '' != $wcj_modules_by_user_roles_data['role'] ? $wcj_modules_by_user_roles_data['role'] : 'guest' );
				$wcj_modules_by_user_roles_data['modules_incl'] = get_option( 'wcj_modules_by_user_roles_incl_' . $wcj_modules_by_user_roles_data['role'], '' );
				$wcj_modules_by_user_roles_data['modules_excl'] = get_option( 'wcj_modules_by_user_roles_excl_' . $wcj_modules_by_user_roles_data['role'], '' );
			}
			if ( ! empty( $wcj_modules_by_user_roles_data['modules_incl'] ) && ! in_array( $module_id, $wcj_modules_by_user_roles_data['modules_incl'] ) ) {
				return false;
			}
			if ( ! empty( $wcj_modules_by_user_roles_data['modules_excl'] ) &&   in_array( $module_id, $wcj_modules_by_user_roles_data['modules_excl'] ) ) {
				return false;
			}
		}
		return ( 'yes' === get_option( 'wcj_' . $module_id . '_enabled', 'no' ) );
	}
}
