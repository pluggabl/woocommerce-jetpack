<?php
/**
 * Booster for WooCommerce - Module - Custom JS
 *
 * @version 4.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Custom_JS' ) ) :

class WCJ_Custom_JS extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.0.0
	 * @since   2.8.0
	 * @todo    [dev] wp_safe_redirect after saving settings
	 * @todo    [dev] (maybe) set `add_action` `priority` to `PHP_INT_MAX`
	 */
	function __construct() {

		$this->id         = 'custom_js';
		$this->short_desc = __( 'Custom JS', 'woocommerce-jetpack' );
		$this->desc       = __( 'Separate custom JS for front and back end.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-custom-js';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( '' != wcj_get_option( 'wcj_custom_js_frontend', '' ) ) {
				add_action( 'wp_'    . wcj_get_option( 'wcj_custom_js_hook', 'head' ), array( $this, 'custom_frontend_js' ) );
			}
			if ( '' != wcj_get_option( 'wcj_custom_js_backend', '' ) ) {
				add_action( 'admin_' . wcj_get_option( 'wcj_custom_js_hook', 'head' ), array( $this, 'custom_backend_js' ) );
			}
		}
	}

	/**
	 * custom_frontend_js.
	 *
	 * @version 2.9.1
	 * @since   2.8.0
	 */
	function custom_frontend_js() {
		echo '<script>' . do_shortcode( wcj_get_option( 'wcj_custom_js_frontend', '' ) ) . '</script>';
	}

	/**
	 * custom_backend_js.
	 *
	 * @version 2.9.1
	 * @since   2.8.0
	 */
	function custom_backend_js() {
		echo '<script>' . do_shortcode( wcj_get_option( 'wcj_custom_js_backend', '' ) ) . '</script>';
	}

}

endif;

return new WCJ_Custom_JS();
