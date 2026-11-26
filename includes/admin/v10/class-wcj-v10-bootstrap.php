<?php
/**
 * Booster True V10 - Thin Admin & REST Bootstrap + Context-Preserving UI Toggle.
 *
 * Guarantees:
 * - Zero WooCommerce submenu additions.
 * - Zero admin_notices or meta boxes outside Booster.
 * - Zero frontend code execution or asset loading.
 * - Zero V10 assets loaded in Classic mode or on non-Booster screens.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Bootstrap' ) ) :

	/**
	 * Lightweight V10 coordinator.
	 */
	class WCJ_V10_Bootstrap {

		const META_UI_MODE = 'wcj_v10_ui_mode';
		const VERSION      = '10.0.0-phase1';

		/**
		 * Initialize admin hooks (never loaded on frontend or Store API REST requests).
		 *
		 * @return void
		 */
		public static function init() {
			if ( is_admin() ) {
				add_action( 'admin_post_wcj_v10_toggle_ui', array( __CLASS__, 'handle_toggle_ui' ) );
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 30 );
			}
		}

		/**
		 * Detect whether the store already has existing Booster options in wp_options.
		 *
		 * @return bool
		 */
		public static function is_existing_store() {
			global $wpdb;
			$found = $wpdb->get_var(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wcj\\_%' AND option_name NOT LIKE 'wcj\\_v10\\_%' LIMIT 1"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return ! empty( $found );
		}

		/**
		 * Load REST classes and register routes only during REST API initialization.
		 *
		 * @return void
		 */
		public static function register_rest() {
			require_once __DIR__ . '/class-wcj-v10-store.php';
			require_once __DIR__ . '/class-wcj-v10-registry.php';
			require_once __DIR__ . '/class-wcj-v10-catalog.php';
			require_once __DIR__ . '/class-wcj-v10-rest.php';
			WCJ_V10_REST::register_routes();
		}

		/**
		 * Determine whether the current user is in V10 New UI mode.
		 *
		 * @return bool
		 */
		public static function is_v10_mode() {
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				$saved = get_user_meta( $user_id, self::META_UI_MODE, true );
				if ( 'v10' === $saved ) {
					return true;
				}
				if ( 'classic' === $saved ) {
					return false;
				}
			}
			return 'v10' === apply_filters( 'wcj_v10_default_ui_mode', 'classic' );
		}

		/**
		 * Map a classic section slug to a flagship editor slug (if applicable).
		 *
		 * @param string $section Classic module section.
		 * @return string
		 */
		public static function map_section_to_editor( $section ) {
			require_once __DIR__ . '/class-wcj-v10-registry.php';
			$map = WCJ_V10_Registry::section_to_editor_map();
			return isset( $map[ $section ] ) ? $map[ $section ] : '';
		}

		/**
		 * Build a nonce-protected URL to flip between New UI and Classic v9 while preserving context.
		 *
		 * @param string $target_mode 'v10' or 'classic'.
		 * @param string $section     Optional active section/editor slug.
		 * @param string $page        Optional admin page slug.
		 * @return string
		 */
		public static function toggle_url( $target_mode, $section = '', $page = '' ) {
			$args = array(
				'action'   => 'wcj_v10_toggle_ui',
				'mode'     => 'v10' === $target_mode ? 'v10' : 'classic',
				'_wpnonce' => wp_create_nonce( 'wcj_v10_toggle_ui' ),
			);
			if ( '' !== $section ) {
				$args['section'] = sanitize_key( $section );
			}
			if ( '' !== $page ) {
				$args['return_page'] = sanitize_key( $page );
			}
			return add_query_arg( $args, admin_url( 'admin-post.php' ) );
		}

		/**
		 * Handle switching between New UI and Classic v9, preserving the current module context.
		 *
		 * @return void
		 */
		public static function handle_toggle_ui() {
			$admin_only = 'yes' === get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' );
			if ( ! current_user_can( 'manage_woocommerce' ) || ( $admin_only && ! current_user_can( 'manage_options' ) ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Permission denied.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			check_admin_referer( 'wcj_v10_toggle_ui' );

			$mode        = isset( $_GET['mode'] ) && 'v10' === $_GET['mode'] ? 'v10' : 'classic';
			$section     = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
			$return_page = isset( $_GET['return_page'] ) ? sanitize_key( wp_unslash( $_GET['return_page'] ) ) : '';

			update_user_meta( get_current_user_id(), self::META_UI_MODE, $mode );

			if ( 'v10' === $mode ) {
				$editor = self::map_section_to_editor( $section );
				if ( '' !== $editor ) {
					$dest = admin_url( 'admin.php?page=wcj-dashboard&v10_editor=' . rawurlencode( $editor ) );
				} else {
					$dest = admin_url( 'admin.php?page=wcj-dashboard' );
				}
			} else {
				require_once __DIR__ . '/class-wcj-v10-registry.php';
				$primary = WCJ_V10_Registry::primary_classic_section( $section );
				if ( '' !== $primary ) {
					$section = $primary;
				}
				if ( '' !== $section && 'active' !== $section ) {
					$dest = admin_url( 'admin.php?page=wcj-plugins&section=' . rawurlencode( $section ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) );
				} elseif ( 'wcj-plugins' === $return_page ) {
					$dest = admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) );
				} else {
					$dest = admin_url( 'admin.php?page=wcj-dashboard' );
				}
			}

			wp_safe_redirect( $dest );
			exit;
		}

		/**
		 * Render persistent UI toggle pills in the Classic header bar so switching is always 1 click away.
		 *
		 * @return void
		 */
		public static function render_header_toggle() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context link builder.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'wcj-dashboard';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context link builder.
			$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';

			echo '<style>.wcj-menubar ul{row-gap:8px;}.wcj-v10-classic-switch{display:inline-flex;align-items:center;gap:8px;}.wcj-v10-header-pill{display:inline-flex!important;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:600;line-height:1.25;text-decoration:none!important;}.wcj-v10-header-pill-primary{background:#fff!important;color:#00507c!important;}.wcj-v10-header-pill-ghost{background:rgba(255,255,255,0.16)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.4);}</style>';

			if ( ! self::is_v10_mode() ) {
				$url = self::toggle_url( 'v10', $section, $page );
				echo '<li class="wcj-v10-classic-switch"><a class="wcj-v10-header-pill wcj-v10-header-pill-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Switch to Guided View', 'woocommerce-jetpack' ) . '</a></li>';
				return;
			}

			$mapped_editor = self::map_section_to_editor( $section );
			$back_url      = '' !== $mapped_editor
				? admin_url( 'admin.php?page=wcj-dashboard&v10_editor=' . rawurlencode( $mapped_editor ) )
				: admin_url( 'admin.php?page=wcj-dashboard' );
			$back_label    = '' !== $mapped_editor
				? __( 'Guided Preview', 'woocommerce-jetpack' )
				: __( 'Command Center', 'woocommerce-jetpack' );
			$classic_url   = self::toggle_url( 'classic', $section, $page );

			echo '<li class="wcj-v10-classic-switch">';
			echo '<a class="wcj-v10-header-pill wcj-v10-header-pill-primary" href="' . esc_url( $back_url ) . '">&larr; ' . esc_html( $back_label ) . '</a>';
			echo '<a class="wcj-v10-header-pill wcj-v10-header-pill-ghost" href="' . esc_url( $classic_url ) . '">&#8644; ' . esc_html__( 'Classic View', 'woocommerce-jetpack' ) . '</a>';
			echo '</li>';
		}

		/**
		 * Check if current request is on a Booster page eligible for V10 rendering.
		 * Never intercepts individual module sections (?page=wcj-plugins&section=...) so All Classic Options always works.
		 *
		 * @return bool
		 */
		public static function is_v10_screen() {
			if ( ! is_admin() || ! self::is_v10_mode() ) {
				return false;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
			if ( isset( $_GET['wcj_classic'] ) ) {
				return false;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
			$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
			$v10_editor = isset( $_GET['v10_editor'] ) ? sanitize_key( wp_unslash( $_GET['v10_editor'] ) ) : '';

			if ( 'wcj-dashboard' === $page ) {
				return true;
			}
			if ( 'wcj-plugins' === $page ) {
				return '' === $section || 'active' === $section || '' !== $v10_editor;
			}
			return false;
		}

		/**
		 * Render the unified V10 Command Center root when in New UI mode.
		 *
		 * @return bool True if V10 rendered and the caller should skip legacy dashboard/plugins output.
		 */
		public static function maybe_render_app() {
			if ( ! self::is_v10_screen() ) {
				return false;
			}
			echo '<div id="wcj-v10-root" class="wcj-v10-root"><div class="wcj-v10-loading">' . esc_html__( 'Loading Booster...', 'woocommerce-jetpack' ) . '</div></div>';
			return true;
		}

		/**
		 * Enqueue the single V10 app stylesheet and script ONLY when V10 renders.
		 *
		 * @return void
		 */
		public static function enqueue_assets() {
			if ( ! self::is_v10_screen() ) {
				return;
			}
			require_once __DIR__ . '/class-wcj-v10-store.php';
			require_once __DIR__ . '/class-wcj-v10-registry.php';

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'wcj-dashboard';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_editor = isset( $_GET['v10_editor'] ) ? sanitize_key( wp_unslash( $_GET['v10_editor'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( wp_unslash( $_GET['gateway'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_doc_type = isset( $_GET['doc_type'] ) ? sanitize_key( wp_unslash( $_GET['doc_type'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_role = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_field_index = isset( $_GET['field_index'] ) ? max( 1, absint( $_GET['field_index'] ) ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI boot parameters.
			$v10_addon_index = isset( $_GET['addon_index'] ) ? max( 1, absint( $_GET['addon_index'] ) ) : 0;

			$initial_editor = '' !== $v10_editor ? $v10_editor : self::map_section_to_editor( $section );
			$initial_tab    = 'wcj-plugins' === $page ? 'all' : 'active';

			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			$plugin_root = dirname( __DIR__, 3 );
			$css_path    = $plugin_root . '/assets/css/admin/wcj-v10-app.css';
			$js_path     = $plugin_root . '/assets/js/admin/wcj-v10-app.js';
			$css_ver     = file_exists( $css_path ) ? self::VERSION . '.' . (string) filemtime( $css_path ) : self::VERSION;
			$js_ver      = file_exists( $js_path ) ? self::VERSION . '.' . (string) filemtime( $js_path ) : self::VERSION;

			wp_enqueue_style(
				'wcj-v10-app',
				wcj_plugin_url() . '/assets/css/admin/wcj-v10-app.css',
				array(),
				$css_ver
			);

			$editor_scripts = WCJ_V10_Registry::editor_scripts();
			$app_deps       = array();
			foreach ( $editor_scripts as $handle => $rel ) {
				$abs = $plugin_root . $rel;
				$ver = file_exists( $abs ) ? self::VERSION . '.' . (string) filemtime( $abs ) : self::VERSION;
				wp_enqueue_script(
					$handle,
					wcj_plugin_url() . $rel,
					array(),
					$ver,
					true
				);
				$app_deps[] = $handle;
			}

			wp_enqueue_script(
				'wcj-v10-app',
				wcj_plugin_url() . '/assets/js/admin/wcj-v10-app.js',
				$app_deps,
				$js_ver,
				true
			);

			wp_localize_script(
				'wcj-v10-app',
				'wcjV10Config',
				array(
					'restUrl'           => esc_url_raw( rest_url( 'booster/v1' ) ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
					'edition'           => WCJ_V10_Store::edition(),
					'isExistingStore'   => self::is_existing_store(),
					'upgradeUrl'        => 'https://booster.io/buy-booster/',
					'initialEditor'     => $initial_editor,
					'initialGateway'    => $v10_gateway,
					'initialDocType'    => $v10_doc_type,
					'initialRole'       => $v10_role,
					'initialFieldIndex' => $v10_field_index,
					'initialAddonIndex' => $v10_addon_index,
					'initialTab'        => $initial_tab,
					'classicBaseUrl'    => self::toggle_url( 'classic', $initial_editor ? $initial_editor : $section, $page ),
					'classicToggleBase' => self::toggle_url( 'classic', '', $page ),
					'dashboardUrl'      => admin_url( 'admin.php?page=wcj-dashboard' ),
					'generalUrl'        => admin_url( 'admin.php?page=wcj-general-settings' ),
					'toolsUrl'          => admin_url( 'admin.php?page=wcj-tools&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) ),
					'logoUrl'           => wcj_plugin_url() . '/assets/images/booster-header-logo.svg',
				)
			);
		}
	}

endif;
