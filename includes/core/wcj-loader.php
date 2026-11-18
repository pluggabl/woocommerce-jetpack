<?php
/**
 * Booster for WooCommerce - Core - Loader
 *
 * @version 7.9.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debug Mode.
if ( 'yes' === wcj_get_option( 'wcj_debug_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_debuging_enabled', 'no' ) ) {
	error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP
}

if ( ! defined( 'WCJ_FREE_PLUGIN_PATH' ) ) {
	/**
	 * WCJ_FREE_PLUGIN_PATH.
	 *
	 * @version 7.2.8
	 * @since   3.2.4
	 */
	define( 'WCJ_FREE_PLUGIN_PATH', untrailingslashit( realpath( plugin_dir_path( WCJ_FREE_PLUGIN_FILE ) ) ) );
}

// Set up localisation using WordPress catalog precedence and locale switching.
require_once 'class-wcj-localization.php';
new WCJ_Localization( WCJ_FREE_PLUGIN_FILE );

// Include required core files used in admin and on the frontend.

// Constants.
require_once 'wcj-constants.php';

// Functions.
require_once 'wcj-functions.php';

// Quick Start Presets.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/wcj-quick-start-presets.php';

// Shared fee and payment diagnostics bind wcj_loaded now, before either module path.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-rule-explanations.php';

// Shared Returns summary definitions do not construct or read the queue.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-returns-summary.php';

// Quick Start Admin UI.
// Cost ownership invalidation must also observe front-end, REST and scheduled WordPress metadata edits.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-missing-cost-job.php';

if ( is_admin() ) {
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-quick-start-admin.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-status-service.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-order-health-service.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-order-health-admin.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-rule-explanations-admin.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-returns-summary-admin.php';
	// Branded invoice job canvas (shared file, identical across editions).
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-invoice-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-task-finder.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-label-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-catalog-job.php';
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-invoice-job.php';
	}
	// Checkout question job (shared file, identical across editions).
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-checkout-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-checkout-job.php';
	}
	// Native-backed one-product question and complete product-family help.
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-question-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-date-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-order-notes-job.php';
	// Optional extra job (shared file, identical across editions).
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-extra-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-extra-job.php';
	}
	// Payment method fee job (shared file, identical across editions).
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-gateway-fee-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-gateway-fee-job.php';
	}
	// Compatibility Status.
	// Return requests job. Item rules remain enforced only by the native Elite service.
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-returns-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-returns-job.php';
	}
	// Optional amount task and complete payment family help; native engines stay unchanged.
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-gateway-amount-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-shipping-description-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-global-discount-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-currency-symbol-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-currency-tax-groups.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-sale-badge-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-promotions-groups.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-account-orders-label-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-order-account-groups.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-document-label-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-document-workspace.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-communication-subject-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-communications-workspace.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-booking-label-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-specialized-selling-workspace.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-toolbar-categories-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-catalog-admin-workspace.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-handoff-job.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-data-exchange-workspace.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-slug-history.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-diagnose-customize-workspace.php';
	// One payment method country job; native engines and legacy paid controls remain unchanged.
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-gateway-country-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-gateway-country-job.php';
	}
	// One plain global product tab; advanced native settings remain untouched.
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-tab-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-product-tab-job.php';
	}
	// Guided shipping choice; native paid modes and other shipping rules are preserved.
	if ( file_exists( WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-shipping-hide-job.php' ) ) {
		require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-shipping-hide-job.php';
	}
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-compatibility-status.php';
}

// WordPress 6.9+ Abilities API. Older WordPress versions do not load these frontend classes.
if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '6.9', '>=' ) && function_exists( 'wp_register_ability' ) ) {
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-status-service.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-order-health-service.php';
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-abilities.php';
}

// Classes.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-module.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-module-product-by-condition.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-module-shipping-by-condition.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-invoice.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-pdf-invoice.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-welcome.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-product-variation-swatches-term-meta.php';

// Upgrade Blocks.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/class-wcj-upgrade-blocks.php';

// Mini Plugin.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/mini-plugin/wcj-mini-plugin.php';

// Plus.
if ( 'booster-plus-for-woocommerce.php' === basename( WCJ_FREE_PLUGIN_FILE ) && apply_filters( 'wcj_full_pack', true ) ) {
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/plus/class-wcj-plus.php';
}

// Tools.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-tools.php';

// Shortcodes.
require_once 'wcj-shortcodes.php';

// Widgets.
require_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-widget.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-multicurrency.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-country-switcher.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-left-to-free-shipping.php';
require_once WCJ_FREE_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-selector.php';

// Scripts.
require_once 'class-wcj-scripts.php';

// Modules and Submodules.
if ( 'no' === wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) {
	require_once 'wcj-modules.php';

	// Add and Manage options.
	require_once 'wcj-options.php';

	// Admin.
	require_once 'class-wcj-admin.php';

	// Settings manager.
	require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-settings-manager.php';

	// Loaded action.
	do_action( 'wcj_loaded' );
} else {
	add_action(
		'plugins_loaded',
		function () {
			require_once 'wcj-modules.php';

			// Add and Manage options.
			require_once 'wcj-options.php';

			// Admin.
			require_once 'class-wcj-admin.php';

			// Settings manager.
			require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wcj-settings-manager.php';

			// Loaded action.
			do_action( 'wcj_loaded' );
		},
		20
	);
}
