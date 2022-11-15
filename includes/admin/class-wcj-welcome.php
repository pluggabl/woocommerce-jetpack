<?php
/**
 * Booster getting started
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Welcome' ) ) :
	/**
	 * WCJ_Welcome.
	 */
	class WCJ_Welcome {
		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {

				$wpnonce = isset( $_REQUEST['wcj-redirect-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-redirect-nonce'] ), 'wcj-redirect' ) : false;
				if ( $wpnonce && isset( $_GET['page'] ) && 'jetpack-getting-started' === $_GET['page'] ) {
					add_action(
						'in_admin_header',
						function () {
							remove_all_actions( 'admin_notices' );
							remove_all_actions( 'all_admin_notices' );
						},
						1
					);
				}

				add_action( 'admin_init', array( $this, 'wcj_redirect_to_getting_started' ), 10 );
				add_action( 'admin_menu', array( $this, 'wcj_register_welcome_page' ) );
				add_action( 'network_admin_menu', array( $this, 'wcj_register_welcome_page' ) );
				add_action( 'admin_head', array( $this, 'wcj_hide_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );

			}
		}

		/**
		 * Wcj_register_welcome_page.
		 *
		 * @version 5.4.3
		 */
		public function wcj_register_welcome_page() {
			add_dashboard_page(
				esc_html__( 'Welcome to Booster', 'woocommerce-jetpack' ),
				esc_html__( 'Welcome to Booster', 'woocommerce-jetpack' ),
				apply_filters( 'wcj_welcome_screen_filter', 'manage_options' ),
				'jetpack-getting-started',
				array( $this, 'wcj_welcome_screen_content' )
			);
		}

		/**
		 * Wcj_hide_menu.
		 *
		 * @version 5.4.1
		 */
		public function wcj_hide_menu() {
			remove_submenu_page( 'index.php', 'jetpack-getting-started' );
		}

		/**
		 * Wcj_redirect_to_getting_started.
		 *
		 * @version 5.6.8
		 */
		public function wcj_redirect_to_getting_started() {
			$wpnonce = isset( $_REQUEST['wcj-redirect-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-redirect-nonce'] ), 'wcj-redirect' ) : false;
			if ( ! get_transient( '_wcj_activation_redirect' ) || isset( $_GET['wcj-redirect'] ) || $wpnonce ) {
				return;
			}

			delete_transient( '_wcj_activation_redirect' );

			$redirect = admin_url( 'index.php?page=jetpack-getting-started&wcj-redirect=1&wcj-redirect-nonce=' . wp_create_nonce( 'wcj-redirect' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Wcj_welcome_screen_content.
		 *
		 * @version 5.6.1
		 */
		public function wcj_welcome_screen_content() {
			require_once WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-welcome-screen-content.php';
		}

		/**
		 * Enqueue_admin_script.
		 *
		 * @version 5.4.3
		 * @since   5.4.3
		 */
		public function enqueue_admin_script() {
			wp_enqueue_script( 'wcj-admin-js', trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-admin.js', array( 'jquery' ), w_c_j()->version, true );
			wp_localize_script( 'wcj-admin-js', 'admin_object', array( 'admin_object' ), false );
		}
	}

endif;

return new WCJ_Welcome();
