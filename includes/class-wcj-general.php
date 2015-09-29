<?php
/**
 * WooCommerce Jetpack General
 *
 * The WooCommerce Jetpack General class.
 *
 * @version 2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_General' ) ) :

class WCJ_General extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id         = 'general';
		$this->short_desc = __( 'General', 'woocommerce-jetpack' );
		$this->desc       = __( 'Separate custom CSS for front and back end. Shortcodes in Wordpress text widgets.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_general_shortcodes_in_text_widgets_enabled' ) ) {
				add_filter( 'widget_text', 'do_shortcode' );
			}

			if ( '' != get_option( 'wcj_general_custom_css' ) ) {
				add_action( 'wp_head', array( $this, 'hook_custom_css' ) );
			}
			if ( '' != get_option( 'wcj_general_custom_admin_css' ) ) {
				add_action( 'admin_head', array( $this, 'hook_custom_admin_css' ) );
			}
		}
	}

	/**
	 * hook_custom_css.
	 */
	public function hook_custom_css() {
		$output = '<style>' . get_option( 'wcj_general_custom_css' ) . '</style>';
		echo $output;
	}

	/**
	 * hook_custom_admin_css.
	 */
	public function hook_custom_admin_css() {
		$output = '<style>' . get_option( 'wcj_general_custom_admin_css' ) . '</style>';
		echo $output;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'   => __( 'Shortcodes Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'desc'    => '',
				'id'      => 'wcj_general_shortcodes_options',
			),

			array(
				'title'   => __( 'Enable Shortcodes in WordPress Text Widgets', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_shortcodes_in_text_widgets_enabled',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_shortcodes_options',
			),

			array(
				'title'   => __( 'Custom CSS Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'desc'    => __( 'Another custom CSS, if you need one.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_css_options',
			),

			array(
				'title'   => __( 'Custom CSS - Front end (Customers)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_css',
				'default' => '',
				'type'    => 'textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'title'   => __( 'Custom CSS - Back end (Admin)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_admin_css',
				'default' => '',
				'type'    => 'textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_custom_css_options',
			),
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_General();
