<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Plugin Name: Booster for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://booster.io
 * Description: Supercharge your WooCommerce site with these awesome powerful features. More than 100 modules.All in one WooCommerce plugin.
 * Version: 7.4.0
 * Author: Pluggabl LLC
 * Author URI: https://booster.io
 * Text Domain: woocommerce-jetpack
 * Domain Path: /langs
 * Copyright: © 2020 Pluggabl LLC.
 * WC tested up to: 10.3.3
 * License: GNU General Public License v3.0
 * php version 7.2
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Booster_For_WooCommerce
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Check if Plus is active.
if ( 'woocommerce-jetpack.php' === basename( __FILE__ ) &&
	( wcj_is_plugin_activated( 'booster-plus-for-woocommerce', 'booster-plus-for-woocommerce.php' ) ||
	wcj_is_plugin_activated( 'booster-elite-for-woocommerce', 'booster-elite-for-woocommerce.php' ) ||
	wcj_is_plugin_activated( 'booster-basic-for-woocommerce', 'booster-basic-for-woocommerce.php' ) ||
	wcj_is_plugin_activated( 'booster-pro-for-woocommerce', 'booster-pro-for-woocommerce.php' ) )
) {
	return;
}

if ( ! defined( 'WCJ_FREE_PLUGIN_FILE' ) ) {
	/**
	 * WCJ_FREE_PLUGIN_FILE.
	 *
	 * @since 5.6.1
	 */
	define( 'WCJ_FREE_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Jetpack' ) ) :

	/**
	 * Main WC_Jetpack Class
	 *
	 * @class   WC_Jetpack
	 * @version 5.6.0
	 * @since   1.0.0
	 */
	final class WC_Jetpack {

		/**
		 * Booster for WooCommerce version.
		 *
		 * @var   string
		 * @since 2.4.7
		 */
		public $version = '7.4.0';

		/**
		 * The single instance of the class
		 *
		 * @var WC_Jetpack The single instance of the class
		 */
		protected static $instances = null;

		/**
		 * WC Jetpack
		 *
		 * @version 5.3.3
		 * @since   5.3.3
		 *
		 * @var array
		 */
		public $options = array();

		/**
		 * WC Jetpack
		 *
		 * @version 7.1.6
		 *
		 * @var array
		 */
		public $shortcodes = array();

		/**
		 * WC Jetpack
		 *
		 * @version 7.1.6
		 *
		 * @var array
		 */
		public $modules = array();

		/**
		 * WC Jetpack
		 *
		 * @version 7.1.6
		 *
		 * @var array
		 */
		public $all_modules = array();

		/**
		 * WC Jetpack
		 *
		 * @version 7.1.6
		 *
		 * @var array
		 */
		public $module_statuses = array();

		/**
		 * Main WC_Jetpack Instance.
		 *
		 * Ensures only one instance of WC_Jetpack is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @see     w_c_j()
		 * @return  WC_Jetpack - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$instances ) ) {
				self::$instances = new self();
			}
			return self::$instances;
		}

		/**
		 * WC_Jetpack Constructor.
		 *
		 * @version 3.2.4
		 * @since   1.0.0
		 * @access  public
		 */
		public function __construct() {
			include_once 'includes/core/wcj-loader.php';
		}
	}

endif;

if ( ! function_exists( 'w_c_j' ) ) {
	/**
	 * Returns the main instance of WC_Jetpack to prevent the need to use globals.
	 *
	 * @version 2.5.7
	 * @since   1.0.0
	 * @return  WC_Jetpack
	 */
	function w_c_j() {
		return WC_Jetpack::instance();
	}
}

	/**
	 * Wcj_delete_plugin_database_option
	 *
	 * @version 6.0.3
	 * @since   6.0.3
	 */
function wcj_delete_free_plugin_database_option() {
	global $wpdb;

	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'wcj_%' OR option_name LIKE '_transient_timeout_wcj%' OR option_name LIKE '_transient_wcj%' OR option_name LIKE 'woocommerce_wcj_%' OR option_name LIKE 'widget_wcj_widget_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $plugin_options as $option ) {
		delete_option( $option->option_name );
		delete_site_option( $option->option_name );
	}

	$plugin_meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_wcj_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $plugin_meta as $meta ) {
		delete_post_meta( $meta->post_id, $meta->meta_key );
	}
}

	register_uninstall_hook( __FILE__, 'wcj_delete_free_plugin_database_option' );


	/**
	 * Booster Pro
	 *
	 * @version 5.5.8
	 * @since   1.0.0
	 * @return  Booster_Pro
	 */
/**
 * This function allows you to track usage of your plugin
 * Place in your main plugin file
 * Refer to https://wisdomplugin.com/support for help
 */
if ( ! class_exists( 'Plugin_Usage_Tracker' ) ) {
	include_once dirname( __FILE__ ) . '/tracking/class-plugin-usage-tracker.php';
}
if ( ! function_exists( 'woocommerce_jetpack_start_plugin_tracking' ) ) {
	/**
	 * Woocommerce jetpack start plugin tracking
	 *
	 * @version 5.5.8
	 * @since   1.0.0
	 */
	function woocommerce_jetpack_start_plugin_tracking() {
		$wisdom = new Plugin_Usage_Tracker(
			__FILE__,
			'https://boosterio.bigscoots-staging.com',
			array(),
			true,
			true,
			1
		);
	}
	woocommerce_jetpack_start_plugin_tracking();
}

add_action( 'plugins_loaded', 'w_c_j' );

// 1. Plugin activate hone par flag set karo
register_activation_hook( __FILE__, 'wcj_set_activation_redirect_free' );

/**
 * Set redirect flag after plugin activation.
 *
 * This function runs on plugin activation and stores an option
 * that triggers a redirect to the onboarding/setup page.
 *
 * @return void
 */
function wcj_set_activation_redirect_free() {
	add_option( 'wcj_do_redirect', true );
}

// 2. Plugin update hone par flag set karo
add_action( 'upgrader_process_complete', 'wcj_set_update_redirect_free', 10, 2 );

/**
 * Set redirect flag after plugin update.
 *
 * This function checks if the current process is a plugin update
 * and, if this plugin was updated, it sets an option that triggers
 * a redirect to the onboarding/setup page.
 *
 * @param WP_Upgrader $upgrader_object The upgrader object.
 * @param array       $options         Array of bulk item update data.
 *
 * @return void
 */
function wcj_set_update_redirect_free( $upgrader_object, $options ) {
	// Yoda conditions used below ('literal' === $var).
	if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( plugin_basename( __FILE__ ) === $plugin ) {
					add_option( 'wcj_do_redirect', true );
					// stop looping once found.
					break;
				}
			}
		}
	}
}

// Redirect to Getting Started page after plugin activation/update.
add_action( 'admin_init', 'wcj_redirect_after_activation_or_update_free' );

/**
 * Redirects admin to the Getting Started page after plugin activation or update.
 *
 * Checks for the `wcj_do_redirect` option set during activation/update.
 * Prevents redirect in network admin or multi-site bulk activation cases.
 *
 * @return void
 */
function wcj_redirect_after_activation_or_update_free() {
	if ( get_option( 'wcj_do_redirect', false ) ) {
		delete_option( 'wcj_do_redirect' );

		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wcj-getting-started&modal=onboarding#launch-onboarding-modal' ) );
		exit;
	}
}
