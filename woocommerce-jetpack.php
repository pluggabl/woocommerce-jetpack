<?php
/*
Plugin Name: Booster for WooCommerce
Plugin URI: https://booster.io
Description: Supercharge your WooCommerce site with these awesome powerful features.
Version: 3.3.0
Author: Algoritmika Ltd
Author URI: https://booster.io
Text Domain: woocommerce-jetpack
Domain Path: /langs
Copyright: © 2018 Algoritmika Ltd.
WC tested up to: 3.2
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Core functions
require_once( 'includes/functions/wcj-functions-core.php' );

// Check if WooCommerce is active
if ( ! wcj_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

// Check if Plus is active
if ( 'woocommerce-jetpack.php' === basename( __FILE__ ) && wcj_is_plugin_active( 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php' ) ) {
	return;
}

if ( ! defined( 'WCJ_PLUGIN_FILE' ) ) {
	/**
	 * WCJ_PLUGIN_FILE.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	define( 'WCJ_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WC_Jetpack' ) ) :

/**
 * Main WC_Jetpack Class
 *
 * @class   WC_Jetpack
 * @version 3.2.4
 */
final class WC_Jetpack {

	/**
	 * Booster for WooCommerce version.
	 *
	 * @var   string
	 * @since 2.4.7
	 */
	public $version = '3.3.0';

	/**
	 * @var WC_Jetpack The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Jetpack Instance
	 *
	 * Ensures only one instance of WC_Jetpack is loaded or can be loaded.
	 *
	 * @static
	 * @see    WCJ()
	 * @return WC_Jetpack - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WC_Jetpack Constructor.
	 *
	 * @version 3.2.4
	 * @access  public
	 */
	function __construct() {
		require_once( 'includes/core/wcj-loader.php' );
	}

}

endif;

if ( ! function_exists( 'WCJ' ) ) {
	/**
	 * Returns the main instance of WC_Jetpack to prevent the need to use globals.
	 *
	 * @version 2.5.7
	 * @return  WC_Jetpack
	 */
	function WCJ() {
		return WC_Jetpack::instance();
	}
}

WCJ();
