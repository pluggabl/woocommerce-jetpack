<?php
/**
 * Booster for WooCommerce - Core - Loader
 *
 * @version 5.3.1
 * @since   3.2.4
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debug Mode
if ( 'yes' === wcj_get_option( 'wcj_debug_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_debuging_enabled', 'no' ) ) {
	error_reporting( E_ALL );
}

if ( ! defined( 'WCJ_PLUGIN_PATH' ) ) {
	/**
	 * WCJ_PLUGIN_PATH.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	define( 'WCJ_PLUGIN_PATH', untrailingslashit( realpath( plugin_dir_path( WCJ_PLUGIN_FILE ) ) ) );
}

// Set up localisation
if ( 'no' === wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) {
	load_plugin_textdomain( 'woocommerce-jetpack', false, dirname( plugin_basename( WCJ_PLUGIN_FILE ) ) . '/langs/' );
} else {
	add_action( 'init', function () {
		load_plugin_textdomain( 'woocommerce-jetpack', false, dirname( plugin_basename( WCJ_PLUGIN_FILE ) ) . '/langs/' );
	}, 9 );
}

// Include required core files used in admin and on the frontend

// Constants
require_once( 'wcj-constants.php' );

// Functions
require_once( 'wcj-functions.php' );

// Classes
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-module.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-module-product-by-condition.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-module-shipping-by-condition.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-invoice.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-pdf-invoice.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/admin/class-wcj-welcome.php' );

// Mini Plugin
require_once( WCJ_PLUGIN_PATH . '/includes/mini-plugin/wcj-mini-plugin.php' );

// Plus
if ( 'booster-plus-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) && apply_filters( 'wcj_full_pack', true ) ) {
	require_once( WCJ_PLUGIN_PATH . '/includes/plus/class-wcj-plus.php' );
}

// Tools
require_once( WCJ_PLUGIN_PATH . '/includes/admin/class-wcj-tools.php' );

// Shortcodes
require_once( 'wcj-shortcodes.php' );

// Widgets
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-widget.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-multicurrency.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-country-switcher.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-left-to-free-shipping.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-selector.php' );

// Scripts
require_once( 'class-wcj-scripts.php' );

// Modules and Submodules
if ( 'no' === wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) {
	require_once( 'wcj-modules.php' );

	// Add and Manage options
	require_once( 'wcj-options.php' );

	// Admin
	require_once( 'class-wcj-admin.php' );

	// Settings manager
	require_once( WCJ_PLUGIN_PATH . '/includes/admin/class-wcj-settings-manager.php' );

	// Loaded action
	do_action( 'wcj_loaded' );
} else {
	add_action( 'init', function () {
		require_once( 'wcj-modules.php' );

		// Add and Manage options
		require_once( 'wcj-options.php' );

		// Admin
		require_once( 'class-wcj-admin.php' );

		// Settings manager
		require_once( WCJ_PLUGIN_PATH . '/includes/admin/class-wcj-settings-manager.php' );

		// Loaded action
		do_action( 'wcj_loaded' );
	}, 10 );
}