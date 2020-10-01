<?php
/**
 * Booster for WooCommerce - Core - Options
 *
 * @version 3.8.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @todo    (dev) move version updated stuff to another file
 * @todo    (maybe) this only loads Enable, Tools and Reset settings for each module
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
	foreach ( $this->modules as $module ) {
		// Modules statuses
		if ( '' == $module->parent_id ) { // i.e. not submodule
			$status_settings = $module->add_enable_module_setting( array() );
			$this->module_statuses[] = $status_settings[1];
		}
		if ( wcj_get_option( WCJ_VERSION_OPTION ) === $this->version ) {
			continue;
		}
		$values = $module->get_settings();
		// Adding options
		foreach ( $values as $value ) {
			if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
				if ( 'yes' === wcj_get_option( 'wcj_autoload_options', 'yes' ) ) {
					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				} else {
					$autoload = false;
				}
				add_option( $value['id'], $value['default'], '', $autoload );
			}
		}
	}
	if ( wcj_get_option( WCJ_VERSION_OPTION ) !== $this->version ) {
		// "Version updated" stuff...
		update_option( WCJ_VERSION_OPTION, $this->version );
		add_action( 'admin_notices', 'wcj_admin_notices_version_updated' );
		wp_schedule_single_event( time(), 'wcj_version_updated' );
		add_action( 'init', 'wcj_handle_deprecated_options' );
	}
}
