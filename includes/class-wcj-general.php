<?php
/**
 * WooCommerce Jetpack General
 *
 * The WooCommerce Jetpack General class.
 *
 * @version 2.3.9
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
	 *
	 * @version 2.3.9
	 */
	function get_settings() {

		$links_html = '';
		if ( class_exists( 'RecursiveIteratorIterator' ) && class_exists( 'RecursiveDirectoryIterator' ) ) {
			$dir = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../../woocommerce/templates' ) );
			$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
			foreach ( $rii as $file ) {
				$the_name = str_replace( $dir, '', $file->getPathname() );
				$the_name_link = str_replace( DIRECTORY_SEPARATOR, '%2F', $the_name );
				if ( $file->isDir() ) {
					/* $links_html .= '<strong>' . $the_name . '</strong>' . PHP_EOL; */
				} else {
					$links_html .= '<a href="' . get_admin_url( null, 'plugin-editor.php?file=woocommerce' . '%2F' . 'templates' . $the_name_link . '&plugin=woocommerce' ) . '">' .
							'templates' . $the_name . '</a>' . PHP_EOL;
				}
			}
		} else {
			$links_html = __( 'PHP 5 is required.', 'woocommerce-jetpack' );
		}

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
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'title'   => __( 'Custom CSS - Back end (Admin)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_custom_admin_css',
				'default' => '',
				'type'    => 'custom_textarea',
				'css'     => 'width:66%;min-width:300px;min-height:300px;',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_custom_css_options',
			),

			array(
				'title'   => __( 'WooCommerce Templates Editor Links', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'id'      => 'wcj_general_wc_templates_editor_links_options',
			),

			array(
				'title'   => __( 'Templates', 'woocommerce-jetpack' ),
				'id'      => 'wcj_general_wc_templates_editor_links',
				'type'    => 'custom_link',
				'link'    => '<pre>' . $links_html . '</pre>',
			),

			array(
				'type'    => 'sectionend',
				'id'      => 'wcj_general_wc_templates_editor_links_options',
			),
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_General();
