<?php
/**
 * Booster for WooCommerce Tools
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Tools' ) ) :
		/**
		 * WCJ_Tools.
		 */
	class WCJ_Tools {

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {
				if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
					add_action( 'admin_menu', array( $this, 'add_wcj_tools' ), 100 );
					add_action( 'admin_enqueue_scripts', array( $this, 'wcj_new_desing_dashboard_enqueue' ) );
				}
			}
		}

		/**
		 * Wcj_new_desing_dashboard_enqueue.
		 *
		 * @version 5.6.8
		 * @since   5.5.6
		 */
		public function wcj_new_desing_dashboard_enqueue() {
			$_get = array();
			parse_str( isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '', $_get );
			$page = isset( $_get['page'] ) ? sanitize_text_field( wp_unslash( $_get['page'] ) ) : '';
			if ( 'wcj-tools' === $page ) {
				wp_enqueue_style( 'wcj-admin-wcj-new_desing', wcj_plugin_url() . '/includes/css/admin-style.css', array(), time() );
				wp_enqueue_script( 'wcj-admin-script', wcj_plugin_url() . '/includes/js/admin-script.js', array( 'jquery' ), '5.0.0', true );

			}
		}
		/**
		 * Add_wcj_tools.
		 *
		 * @version 4.8.0
		 */
		public function add_wcj_tools() {
			if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
				add_submenu_page(
					'woocommerce',
					__( 'Booster for WooCommerce Tools', 'woocommerce-jetpack' ),
					__( 'Booster Tools', 'woocommerce-jetpack' ),
					( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
					'wcj-tools',
					array( $this, 'create_tools_page' )
				);
			}
		}

		/**
		 * Create_tools_page.
		 *
		 * @version 5.6.8
		 */
		public function create_tools_page() {

			// Tabs.
			$tabs = apply_filters(
				'wcj_tools_tabs',
				array(
					array(
						'id'    => 'dashboard',
						'title' => __( 'Tools Dashboard', 'woocommerce-jetpack' ),
					),
				)
			);
			$html = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper wcj_tool_tab_part">';
			// phpcs:disable WordPress.Security.NonceVerification
			$active_tab = ( isset( $_GET['tab'] ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
			// phpcs:enable WordPress.Security.NonceVerification

			foreach ( $tabs as $tab ) {
				$is_active = ( $active_tab === $tab['id'] ) ? 'nav-tab-active' : '';
				$html     .= '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'            => 'wcj-tools',
							'tab'             => $tab['id'],
							'wcj_tools_nonce' => wp_create_nonce( 'wcj_tools' ),
						),
						get_admin_url() . 'admin.php'
					)
				) . '" class="nav-tab ' . $is_active . '">' . $tab['title'] . '</a>';
			}
			$html .= '</h2>';
			echo wp_kses_post( $html );

			// Content.
			if ( 'dashboard' === $active_tab ) {
				$title = __( 'Booster for WooCommerce Tools - Dashboard', 'woocommerce-jetpack' );
				$desc  = __( 'This dashboard lets you check statuses and short descriptions of all available Booster for WooCommerce tools. Tools can be enabled through WooCommerce > Settings > Booster. Enabled tools will appear in the tabs menu above.', 'woocommerce-jetpack' );
				echo '<div class="wcj-setting-jetpack-body wcj_tools_cnt_main">';
				echo '<h3>' . wp_kses_post( $title ) . '</h3>';
				echo '<p>' . wp_kses_post( $desc ) . '</p>';
				echo '<table class="widefat striped" style="width:90%;">';
				echo '<tr>';
				echo '<th style="width:20%;">' . wp_kses_post( 'Tool', 'woocommerce-jetpack' ) . '</th>';
				echo '<th style="width:20%;">' . wp_kses_post( 'Module', 'woocommerce-jetpack' ) . '</th>';
				echo '<th style="width:50%;">' . wp_kses_post( 'Description', 'woocommerce-jetpack' ) . '</th>';
				echo '<th style="width:10%;">' . wp_kses_post( 'Status', 'woocommerce-jetpack' ) . '</th>';
				echo '</tr>';
				do_action( 'wcj_tools_dashboard' );
				echo '</table>';
				echo '</div>';
			} else {
				do_action( 'wcj_tools_' . $active_tab );
			}
		}
	}

endif;

return new WCJ_Tools();
