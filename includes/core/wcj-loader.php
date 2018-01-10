<?php
/**
 * Booster for WooCommerce - Core - Loader
 *
 * @version 3.3.0
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
load_plugin_textdomain( 'woocommerce-jetpack', false, dirname( plugin_basename( WCJ_PLUGIN_FILE ) ) . '/langs/' );

// Include required core files used in admin and on the frontend

// Constants
require_once( 'wcj-constants.php' );

// Functions
require_once( 'wcj-functions.php' );

// Classes
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-module.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-module-shipping-by-condition.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-invoice.php' );
require_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-pdf-invoice.php' );

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

// Modules and Submodules
require_once( 'wcj-modules.php' );

// Add and Manage options
require_once( 'wcj-options.php' );

// Admin
require_once( 'class-wcj-admin.php' );

// Scripts
require_once( 'class-wcj-scripts.php' );

// Settings manager
require_once( WCJ_PLUGIN_PATH . '/includes/admin/class-wcj-settings-manager.php' );

// Loaded action
do_action( 'wcj_loaded' );
