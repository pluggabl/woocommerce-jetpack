<?php
/**
 * Booster for WooCommerce - Module - Custom CSS
 *
 * @version 4.0.0
 * @since   2.7.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Custom_CSS' ) ) :

class WCJ_Custom_CSS extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.0.0
	 * @since   2.7.0
	 * @todo    [dev] `do_shortcode()`
	 * @todo    [dev] wp_safe_redirect after saving settings
	 * @todo    [dev] (maybe) set `add_action` `priority` to `PHP_INT_MAX`
	 */
	function __construct() {

		$this->id         = 'custom_css';
		$this->short_desc = __( 'Custom CSS', 'woocommerce-jetpack' );
		$this->desc       = __( 'Separate custom CSS for front and back end. Per product CSS.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-booster-custom-css';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Frontend
			if ( '' != wcj_get_option( 'wcj_general_custom_css', '' ) ) {
				add_action( 'wp_'    . wcj_get_option( 'wcj_custom_css_hook', 'head' ), array( $this, 'hook_custom_css' ) );
			}
			// Admin
			if ( '' != wcj_get_option( 'wcj_general_custom_admin_css', '' ) ) {
				add_action( 'admin_' . wcj_get_option( 'wcj_custom_css_hook', 'head' ), array( $this, 'hook_custom_admin_css' ) );
			}
			// Per product
			if ( 'yes' === wcj_get_option( 'wcj_custom_css_per_product', 'no' ) ) {
				add_action( 'wp_'    . wcj_get_option( 'wcj_custom_css_hook', 'head' ), array( $this, 'maybe_add_per_product_css' ) );
				// Settings
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * maybe_add_per_product_css.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function maybe_add_per_product_css() {
		$post_id = get_the_ID();
		if ( $post_id > 0 && 'yes' === get_post_meta( $post_id, '_' . 'wcj_product_css_enabled', true ) ) {
			if ( '' != ( $css = get_post_meta( $post_id, '_' . 'wcj_product_css', true ) ) ) {
				echo '<style>' . $css . '</style>';
			}
		}
	}

	/**
	 * hook_custom_css.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function hook_custom_css() {
		echo '<style>' . wcj_get_option( 'wcj_general_custom_css', '' ) . '</style>';
	}

	/**
	 * hook_custom_admin_css.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function hook_custom_admin_css() {
		echo '<style>' . wcj_get_option( 'wcj_general_custom_admin_css', '' ) . '</style>';
	}

}

endif;

return new WCJ_Custom_CSS();
