<?php
/**
 * Booster for WooCommerce - Core - Options
 *
 * @version 3.3.0
 * @since   3.2.4
 * @author  Algoritmika Ltd.
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
		if ( get_option( WCJ_VERSION_OPTION ) === $this->version ) {
			continue;
		}
		$values = $module->get_settings();
		// Adding options
		foreach ( $values as $value ) {
			if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
				if ( 'yes' === get_option( 'wcj_autoload_options', 'yes' ) ) {
					$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				} else {
					$autoload = false;
				}
				add_option( $value['id'], $value['default'], '', $autoload );
			}
		}
	}
	if ( get_option( WCJ_VERSION_OPTION ) !== $this->version ) {
		update_option( WCJ_VERSION_OPTION, $this->version );
		add_action( 'admin_notices', 'wcj_admin_notices_version_updated' );
	}
}
