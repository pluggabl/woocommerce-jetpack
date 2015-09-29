<?php
/**
 * WooCommerce Jetpack Admin Tools
 *
 * The WooCommerce Jetpack Admin Tools class.
 *
 * @version 2.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Admin_Tools' ) ) :

class WCJ_Admin_Tools extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.2.4
	 */
	public function __construct() {

		$this->id         = 'admin_tools';
		$this->short_desc = __( 'Admin Tools', 'woocommerce-jetpack' );
		$this->desc       = __( 'Booster for WooCommerce debug and log tools.', 'woocommerce-jetpack' );
		parent::__construct();

		$this->add_tools( array( 'admin_tools' => __( 'Admin Tools', 'woocommerce-jetpack' ), ) );

		if ( $this->is_enabled() ) {
			add_filter( 'wcj_tools_tabs',             array( $this, 'add_tool_tab' ), 100 );
			add_action( 'wcj_tools_' . 'admin_tools', array( $this, 'create_tool' ), 100 );
		}

		add_action( 'wcj_tools_dashboard', array( $this, 'add_tool_info_to_tools_dashboard' ), 100 );
	}

	/**
	 * add_tool_info_to_tools_dashboard.
	 */
	public function add_tool_info_to_tools_dashboard() {
		echo '<tr>';
		if ( 'yes' === get_option( 'wcj_admin_tools_enabled') )
			$is_enabled = '<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>';
		else
			$is_enabled = '<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'Admin Tools', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'Log.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';
	}

	/**
	 * add_tool_tab.
	 */
	public function add_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'    => 'admin_tools',
			'title' => __( 'Log', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}

	/**
	 * create_tool.
	 *
	 * @version 2.2.3
	 */
	public function create_tool() {

		$the_notice = '';
		if ( isset( $_GET['wcj_delete_log'] ) && is_super_admin() ) {
			update_option( 'wcj_log', '' );
			$the_notice .= __( 'Log deleted successfully.', 'woocommerce-jetpack' );
		}

		$the_tools = '';
		$the_tools .= $this->get_back_to_settings_link_html();
		$the_tools .= '<br>';
		$the_tools .= '<a href="' . add_query_arg( 'wcj_delete_log', '1' ) . '">' . __( 'Delete Log', 'woocommerce-jetpack' ) . '</a>';

		$the_log = '';
		//if ( isset( $_GET['wcj_view_log'] ) ) {
			$the_log .= '<pre>' . get_option( 'wcj_log', '' ) . '</pre>';
		//}

		echo '<p>' . $the_tools . '</p>';

		echo '<p>' . $the_notice . '</p>';

		echo '<p>' . $the_log . '</p>';

	}

	/**
	 * get_settings.
	 *
	 * @version 2.2.3
	 */
	function get_settings() {

		$settings = array(

			array( 'title' => __( 'Admin Tools Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_admin_tools_module_options' ),

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

			/*array(
				'title'    => __( 'Custom Shortcode', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_shortcode_1',
				'default'  => '',
				'type'     => 'textarea',
			),*/

			array( 'type'  => 'sectionend', 'id' => 'wcj_admin_tools_module_options' ),
		);

		$settings = $this->add_tools_list( $settings );

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Admin_Tools();
