<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Plugin Name: Booster for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://booster.io
 * Description: Supercharge your WooCommerce site with these awesome powerful features.
 * Version: 7.11.0
 * Author: Pluggabl LLC
 * Author URI: https://booster.io
 * Text Domain: woocommerce-jetpack
 * Domain Path: /langs
 * WC tested up to: 10.4.3
 * License: GNU General Public License v3.0
 *
 * @package Booster_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core functions.
require_once 'includes/functions/wcj-functions-core.php';

// Check if WooCommerce is active.
if ( ! wcj_is_plugin_activated( 'woocommerce', 'woocommerce.php' ) ) {
	return;
}

// Declare WooCommerce HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

// Prevent loading if paid versions are active.
if (
	'woocommerce-jetpack.php' === basename( __FILE__ ) &&
	(
		wcj_is_plugin_activated( 'booster-plus-for-woocommerce', 'booster-plus-for-woocommerce.php' ) ||
		wcj_is_plugin_activated( 'booster-elite-for-woocommerce', 'booster-elite-for-woocommerce.php' ) ||
		wcj_is_plugin_activated( 'booster-basic-for-woocommerce', 'booster-basic-for-woocommerce.php' ) ||
		wcj_is_plugin_activated( 'booster-pro-for-woocommerce', 'booster-pro-for-woocommerce.php' )
	)
) {
	return;
}

if ( ! defined( 'WCJ_FREE_PLUGIN_FILE' ) ) {
	define( 'WCJ_FREE_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Jetpack' ) ) :

	/**
	 * Main Booster for WooCommerce class.
	 *
	 * @since 1.0.0
	 */
	final class WC_Jetpack {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '7.11.0';

		/**
		 * Singleton instance.
		 *
		 * @var WC_Jetpack|null
		 */
		protected static $instances = null;

		/**
		 * Plugin options.
		 *
		 * @var array
		 */
		public $options = array();

		/**
		 * Registered shortcodes.
		 *
		 * @var array
		 */
		public $shortcodes = array();

		/**
		 * Active modules.
		 *
		 * @var array
		 */
		public $modules = array();

		/**
		 * All available modules.
		 *
		 * @var array
		 */
		public $all_modules = array();

		/**
		 * Module statuses.
		 *
		 * @var array
		 */
		public $module_statuses = array();

		/**
		 * Get main instance (Singleton).
		 *
		 * @return WC_Jetpack
		 */
		public static function instance() {
			if ( is_null( self::$instances ) ) {
				self::$instances = new self();
			}
			return self::$instances;
		}

		/**
		 * Class constructor.
		 */
		public function __construct() {
			include_once 'includes/core/wcj-loader.php';
		}
	}

endif;

// Load procedural functions.
require_once __DIR__ . '/includes/wcj-free-functions.php';

// Plugin usage tracking.
if ( ! class_exists( 'Plugin_Usage_Tracker' ) ) {
	include_once __DIR__ . '/tracking/class-plugin-usage-tracker.php';
}

// Hooks.
add_action( 'plugins_loaded', 'w_c_j' );
register_uninstall_hook( __FILE__, 'wcj_delete_free_plugin_database_option' );
register_activation_hook( __FILE__, 'wcj_set_activation_redirect_free' );
add_action( 'admin_init', 'wcj_redirect_after_first_activation_free' );

// Start tracking.
woocommerce_jetpack_start_plugin_tracking();
