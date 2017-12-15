<?php
/**
 * Booster for WooCommerce - Core - Options
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 * @todo    (maybe) this only loads Enable, Tools and Reset settings for each module
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
	// Modules statuses
	$submodules_classes = array(
		'WCJ_PDF_Invoicing_Display',
		'WCJ_PDF_Invoicing_Emails',
		'WCJ_PDF_Invoicing_Footer',
		'WCJ_PDF_Invoicing_Header',
		'WCJ_PDF_Invoicing_Numbering',
		'WCJ_PDF_Invoicing_Page',
		'WCJ_PDF_Invoicing_Styling',
		'WCJ_PDF_Invoicing_Templates',
	);
	foreach ( $this->modules as $module ) {
		if ( ! in_array( get_class( $module ), $submodules_classes ) ) {
			$status_settings = $module->add_enable_module_setting( array() );
			$this->module_statuses[] = $status_settings[1];
		}
		if ( get_option( 'booster_for_woocommerce_version' ) === $this->version ) {
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
	if ( get_option( 'booster_for_woocommerce_version' ) !== $this->version ) {
		update_option( 'booster_for_woocommerce_version', $this->version );
		add_action( 'admin_notices', 'wcj_admin_notices_version_updated' );
	}
}
