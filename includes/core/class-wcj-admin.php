<?php
/**
 * Booster for WooCommerce - Core - Admin
 *
 * @version 5.6.8
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Admin' ) ) :

		/**
		 * WCJ_Admin.
		 *
		 * @version 5.3.0
		 * @since   3.2.4
		 */
	class WCJ_Admin {

		/**
		 * Constructor.
		 *
		 * @version 5.6.1
		 * @since   3.2.4
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_filter( 'booster_message', 'wcj_get_plus_message', 100, 3 );

				if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
					add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_wcj_settings_tab' ), 1 );
					add_filter( 'plugin_action_links_' . plugin_basename( WCJ_FREE_PLUGIN_FILE ), array( $this, 'action_links' ) );
					add_action( 'admin_menu', array( $this, 'booster_menu' ), 100 );
					add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 2 );
					if ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE ) ) {
						add_action( 'admin_notices', array( $this, 'check_plus_version' ) );
					}
				}
			}
		}

		/**
		 * Check_plus_version.
		 *
		 * @version 3.4.0
		 * @since   2.5.9
		 * @todo    (maybe) use `wcj_is_plugin_active_by_file()`
		 * @todo    (maybe) expand "Please upgrade ..." message
		 */
		public function check_plus_version() {
			$is_deprecated_plus_active = false;
			foreach ( wcj_get_active_plugins() as $active_plugin ) {
				$active_plugin = explode( '/', $active_plugin );
				if ( isset( $active_plugin[1] ) ) {
					if ( 'booster-plus-for-woocommerce.php' === $active_plugin[1] ) {
						return;
					} elseif ( 'woocommerce-jetpack-plus.php' === $active_plugin[1] || 'woocommerce-booster-plus.php' === $active_plugin[1] ) {
						$is_deprecated_plus_active = true; // can't `brake` because of possible active `booster-plus-for-woocommerce.php`.
					}
				}
			}
			if ( $is_deprecated_plus_active ) {
				$class   = 'notice notice-error';
				$message = __( 'Please update <strong>Booster Plus for WooCommerce</strong> plugin.', 'woocommerce-jetpack' ) . ' ' .
				sprintf(
					/* translators: %s: search term */
					__( 'Visit <a target="_blank" href="%s">your account page</a> on booster.io to download the latest Booster Plus version.', 'woocommerce-jetpack' ),
					'https://booster.io/my-account/?utm_source=plus_update'
				) . ' ' .
					sprintf(
						/* translators: %s: search term */
						__( 'Click <a target="_blank" href="%s">here</a> for more info.', 'woocommerce-jetpack' ),
						'https://booster.io/booster-plus-for-woocommerce-update/'
					);
				echo '<div class="' . esc_html( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
			}
		}

		/**
		 * Admin_footer_text
		 *
		 * @version 5.6.8
		 * @param   string $footer_text get admin footer texts.
		 */
		public function admin_footer_text( $footer_text ) {
			if ( isset( $_GET['page'] ) ) {
				$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
				if ( 'wcj-tools' === $_GET['page'] || ( $wpnonce && 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab'] ) ) {
					?>
				<div class="wclj_tl_foot">
				<div class="wcj-footer">
					<div class="wcj-review-footer">
					<p><?php esc_html_e( 'Please rate ', 'woocommerce-jetpack' ); ?><strong><?php esc_html_e( 'Booster for Woocommerce', 'woocommerce-jetpack' ); ?></strong>
						<span class="wcj-woo-star">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/star.png'; ?>">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/star.png'; ?>">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/star.png'; ?>">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/star.png'; ?>">
							<img src="<?php echo esc_url( wcj_plugin_url() ) . '/assets/images/star.png'; ?>">
						</span>
						<strong><a href="https://wordpress.org/support/plugin/woocommerce-jetpack/reviews/?rate=5#new-post" target="_blank"><?php esc_html_e( 'WordPress.org', 'woocommerce-jetpack' ); ?></a></strong><?php esc_html_e( ' to help us spread the word. Thank you from Booster team!', 'woocommerce-jetpack' ); ?>
						</p>
					</div>
				</div>
				</div>
					<?php
				}
			}
			return $footer_text;
		}
		/**
		 * Add menu item
		 *
		 * @version 5.6.8
		 */
		public function booster_menu() {
			add_submenu_page(
				'woocommerce',
				__( 'Booster for WooCommerce', 'woocommerce-jetpack' ),
				__( 'Booster Settings', 'woocommerce-jetpack' ),
				( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
				wcj_admin_tab_url()
			);
		}

		/**
		 * Show action links on the plugin screen
		 *
		 * @version 5.6.1
		 * @param   mixed $links get links.
		 * @return  array
		 */
		public function action_links( $links ) {
			$custom_links = array(
				'<a href="' . admin_url( wcj_admin_tab_url() ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>',
				'<a href="' . esc_url( 'https://booster.io/' ) . '">' . __( 'Docs', 'woocommerce-jetpack' ) . '</a>',
			);
			if ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE ) ) {
				$custom_links[] = '<a target="_blank" href="' . esc_url( 'https://booster.io/buy-booster/' ) . '">' . __( 'Unlock all', 'woocommerce-jetpack' ) . '</a>';
			} else {
				$custom_links[] = '<a target="_blank" href="' . esc_url( 'https://booster.io/my-account/booster-contact/' ) . '">' . __( 'Support', 'woocommerce-jetpack' ) . '</a>';
			}
			return array_merge( $custom_links, $links );
		}

		/**
		 * Add Jetpack settings tab to WooCommerce settings.
		 *
		 * @version 5.6.1
		 * @param   array $settings get module settings.
		 */
		public function add_wcj_settings_tab( $settings ) {
			$_settings = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/class-wc-settings-jetpack.php';
			$_settings->add_module_statuses( w_c_j()->module_statuses );
			$settings[] = $_settings;
			return $settings;
		}

	}

endif;

return new WCJ_Admin();
