<?php
/**
 * Booster for WooCommerce - Module - Debug Tools
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Debug_Tools' ) ) :
	/**
	 * WCJ_Debug_Tools.
	 */
	class WCJ_Debug_Tools extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.1.0
		 * @version 4.1.0
		 */
		public function __construct() {

			$this->id         = 'debug_tools';
			$this->short_desc = __( 'Debug Tools', 'woocommerce-jetpack' );
			$this->desc       = __( 'Booster for WooCommerce debug and log tools.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-booster-debug-tools';
			parent::__construct();

			$this->add_tools(
				array(
					'debug_tools' => array(
						'title' => __( 'Log', 'woocommerce-jetpack' ),
						'desc'  => __( 'Log.', 'woocommerce-jetpack' ),
					),
				)
			);

		}

		/**
		 * Create_debug_tools_tool.
		 *
		 * @version 5.6.7
		 */
		public function create_debug_tools_tool() {
			// Delete log.
			$wpnonce = isset( $_REQUEST['wcj_delete_log_nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_delete_log_nonce'] ), 'wcj-delete-log' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_delete_log'] ) && wcj_is_user_role( 'administrator' ) ) {
				update_option( 'wcj_log', '' );
				if ( wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'wcj_delete_log', 'wcj_delete_log_nonce' ) ) ) ) ) {
					exit;
				}
			}
			// Header.
			$the_tools  = '';
			$the_tools .= '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
			$the_tools .= $this->get_tool_header_html( 'debug_tools' );
			$the_tools .= '<p><a href="' . esc_url_raw(
				add_query_arg(
					array(
						'wcj_delete_log'       => '1',
						'wcj_delete_log_nonce' => wp_create_nonce( 'wcj-delete-log' ),
					)
				)
			) . '">' . __( 'Delete Log', 'woocommerce-jetpack' ) . '</a></p>';
			// Log.
			$the_log = '';
			/* translators: %s: translation added */
			$the_log .= '<p style="font-style:italic;color:gray;">' . sprintf( __( 'Now: %s', 'woocommerce-jetpack' ), gmdate( 'Y-m-d H:i:s' ) ) . '</p>';
			$log      = wcj_get_option( 'wcj_log', '' );
			if ( '' !== ( $log ) ) {
				$the_log .= '<pre style="color:green;background-color:black;padding:5px;">' . $log . '</pre>';
			} else {
				$the_log .= '<p style="font-style:italic;color:gray;">' . __( 'Log is empty.', 'woocommerce-jetpack' ) . '</p>';
			}
			// Final output.
			$html  = '';
			$html .= '<div class="wrap">';
			$html .= '<p>' . $the_tools . '</p>';
			$html .= '<p>' . $the_log . '</p>';
			$html .= '</div>';
			$html .= '</div>';
			echo wp_kses_post( $html );
		}

		/**
		 * Get_system_info_table_array.
		 *
		 * @version 5.6.8
		 * @since   2.5.7
		 * @todo    [feature] (maybe) 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE'
		 */
		public function get_system_info_table_array() {
			$system_info     = array();
			$constants_array = array(
				'WP_MEMORY_LIMIT',
				'WP_MAX_MEMORY_LIMIT',
				'WP_DEBUG',
				'ABSPATH',
				'DISABLE_WP_CRON',
				'WP_CRON_LOCK_TIMEOUT',
				'WCJ_WC_VERSION',
				'WCJ_SESSION_TYPE',
			);
			foreach ( $constants_array as $the_constant ) {
				$system_info[] = array( $the_constant, ( defined( $the_constant ) ? constant( $the_constant ) : __( 'NOT DEFINED', 'woocommerce-jetpack' ) ) );
			}
			$wpnonce = isset( $_REQUEST['wcj_debug-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj_debug-nonce'] ), 'wcj_debug' ) : false;
			if ( isset( $_GET['wcj_debug'] ) && $wpnonce ) {
				foreach ( $_SERVER as $server_var_id => $server_var_value ) {
					$system_info[] = array( $server_var_id, esc_html( $server_var_value ) );
				}
			}
			return $system_info;
		}

	}

endif;

return new WCJ_Debug_Tools();
