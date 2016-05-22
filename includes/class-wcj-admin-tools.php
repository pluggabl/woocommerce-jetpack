<?php
/**
 * WooCommerce Jetpack Admin Tools
 *
 * The WooCommerce Jetpack Admin Tools class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Admin_Tools' ) ) :

class WCJ_Admin_Tools extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'admin_tools';
		$this->short_desc = __( 'Admin Tools', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce debug and log tools.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-booster-admin-tools/';
		parent::__construct();

		$this->add_tools( array(
			'admin_tools' => array(
				'title' => __( 'Admin Tools', 'woocommerce-jetpack' ),
				'desc'  => __( 'Log.', 'woocommerce-jetpack' ),
				'tab_title' => __( 'Log', 'woocommerce-jetpack' ),
			),
		) );
	}

	/**
	 * create_tool.
	 *
	 * @version 2.5.0
	 */
	public function create_admin_tools_tool() {

		$the_notice = '';
		if ( isset( $_GET['wcj_delete_log'] ) && wcj_is_user_role( 'administrator' ) ) {
			update_option( 'wcj_log', '' );
			$the_notice .= __( 'Log deleted successfully.', 'woocommerce-jetpack' );
		}

		$the_tools = '';
		$the_tools .= $this->get_back_to_settings_link_html();
		$the_tools .= '<br>';
		$the_tools .= '<a href="' . add_query_arg( 'wcj_delete_log', '1' ) . '">' . __( 'Delete Log', 'woocommerce-jetpack' ) . '</a>';

		$the_log = '';
//		if ( isset( $_GET['wcj_view_log'] ) ) {
			$the_log .= '<pre>' . get_option( 'wcj_log', '' ) . '</pre>';
//		}

		echo '<p>' . $the_tools  . '</p>';
		echo '<p>' . $the_notice . '</p>';
		echo '<p>' . $the_log    . '</p>';
	}

	/**
	 * get_settings.
	 *
	 * @version 2.3.10
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'    => __( 'Admin Tools Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_admin_tools_module_options',
			),

			array(
				'title'    => __( 'Logging', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_logging_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'title'    => __( 'Debug', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_debuging_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			/* array(
				'title'    => __( 'Custom Shortcode', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_shortcode_1',
				'default'  => '',
				'type'     => 'textarea',
			), */

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_admin_tools_module_options',
			),
		);

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Admin_Tools();
