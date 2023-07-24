<?php
/**
 * Booster for WooCommerce Tools
 *
 * @version 7.0.0
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
		 *
		 * @version 7.0.0
		 */
		public function __construct() {
			if ( is_admin() ) {
				if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
					add_action( 'admin_menu', array( $this, 'add_wcj_tools' ), 1000 );
					$wpnonce     = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
					$active_page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
					if ( 'wcj-tools' === $active_page ) {
						add_action( 'admin_head', array( $this, 'admin_head_tools_page_style' ), 10 );
					}
				}
			}
		}

		/**
		 * Add_wcj_tools.
		 *
		 * @version 7.0.0
		 */
		public function add_wcj_tools() {
			if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
				add_submenu_page(
					'wcj-dashboard',
					__( 'Booster for WooCommerce Tools', 'woocommerce-jetpack' ),
					__( 'Booster Tools', 'woocommerce-jetpack' ),
					( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
					'wcj-tools',
					array( $this, 'create_tools_page' )
				);
			}
		}

		/**
		 * Admin_head_tools_page_style.
		 *
		 * @version 7.0.0
		 */
		public function admin_head_tools_page_style() {
			$style = '<style>
		    #wpbody {
			padding-left: 20px;
		    } 
		  </style>';
			echo wp_kses_post( $style );
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
