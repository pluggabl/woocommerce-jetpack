<?php
/**
 * Booster for WooCommerce - Settings Manager - Import / Export / Reset Booster's settings
 *
 * @version 7.1.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Settings_Manager' ) ) :
	/**
	 * WCJ_Settings_Manager.
	 */
	class WCJ_Settings_Manager {


		/**
		 * Constructor.
		 *
		 * @version 7.1.0
		 * @since   2.9.0
		 * @todo    add options to import/export selected modules only
		 */
		public function __construct() {
			if ( is_admin() ) {
				if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
					return;
				}
				add_action( 'admin_post_wcj_save_general_settings', array( $this, 'wcj_manage_plugin_options' ) );
			}
		}

		/**
		 * Manage_options.
		 *
		 * @version 7.1.0
		 * @since   2.5.2
		 */
		public function wcj_manage_plugin_options() {
			if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$msg = __( 'Your settings have been saved.', 'woocommerce-jetpack' );

			$wpnonce = $this->can_manage_settings();

			if ( $wpnonce ) {

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['return_url'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					$return_url = sanitize_text_field( wp_unslash( $_POST['return_url'] ) ) . '&msg=' . $msg . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' );
				} else {
					$return_url = admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) );
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['booster_export_settings'] ) ) {
					$this->manage_options_export();
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['booster_import_settings'] ) ) {
					$msg        = $this->manage_options_import();
					$return_url = add_query_arg( 'msg', $msg, $return_url );
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					if ( isset( $_POST['wcj_quick_action'] ) ) {
						$return_url .= '#message';
					}
					wp_safe_redirect( $return_url );
					exit();
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['booster_reset_settings'] ) ) {
					$msg         = $this->manage_options_reset();
					$return_url .= '&msg=' . $msg;
					wp_safe_redirect( $return_url );
					exit();
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['booster_reset_settings_meta'] ) ) {
					$msg         = $this->manage_options_reset_meta();
					$return_url .= '&msg=' . $msg;
					wp_safe_redirect( $return_url );
					exit();
				}

				$general_setting_fields = array(
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					'wcj_autoload_options'       => isset( $_POST['wcj_autoload_options'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_autoload_options'] ) ) : null,
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					'wcj_load_modules_on_init'   => isset( $_POST['wcj_load_modules_on_init'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_load_modules_on_init'] ) ) : null,
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					'wcj_list_for_products'      => isset( $_POST['wcj_list_for_products'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products'] ) ) : null,
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					'wcj_list_for_products_cats' => isset( $_POST['wcj_list_for_products_cats'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products_cats'] ) ) : null,
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
					'wcj_list_for_products_tags' => isset( $_POST['wcj_list_for_products_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products_tags'] ) ) : null,
				);

				foreach ( $general_setting_fields as $setting_key => $setting_value ) {
					if ( ! empty( $setting_value ) && null !== $setting_value ) {
						update_option( $setting_key, $setting_value );
					}
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
				if ( isset( $_POST['wcj_site_key'] ) ) {
					if ( class_exists( 'WCJ_Plus_Site_Key_Manager' ) ) {
						$site_key_manager = new WCJ_Plus_Site_Key_Manager();
						$site_key_manager->check_site_key();
					}
				}

				$return_url .= '&msg=' . $msg;
				wp_safe_redirect( $return_url );
				exit();
			}
		}

		/**
		 * Authorize both the form handler and direct settings-manager calls.
		 *
		 * @return bool Whether the current administrator submitted the real form nonce.
		 */
		private function can_manage_settings() {
			return current_user_can( 'manage_options' ) && isset( $_POST['wcj-verify-manage-settings'] ) &&
				is_string( $_POST['wcj-verify-manage-settings'] ) &&
				false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcj-verify-manage-settings'] ) ), 'wcj-verify-manage-settings' );
		}

		/**
		 * Import a validated local upload, using the nonce emitted by both forms.
		 *
		 * @return string Import result for the administrator.
		 */
		public function manage_options_import() {
			if ( ! $this->can_manage_settings() ) {
				return __( 'You do not have permission to import settings.', 'woocommerce-jetpack' );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
			if ( ! isset( $_FILES['booster_import_settings_file'] ) ) {
				return __( 'Please upload a file to import!', 'woocommerce-jetpack' );
			}
			// PHP supplies this upload structure. Validate its types and upload provenance before reading it.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- can_manage_settings() verifies the actual POST form nonce and manage_options before this branch or upload read.
			$file = $_FILES['booster_import_settings_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- can_manage_settings() verified nonce/capability; upload validated below; sanitizing a native temporary path could change its identity.
			if ( ! is_array( $file ) || ! isset( $file['error'], $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_string( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				return __( 'Upload failed!', 'woocommerce-jetpack' );
			}
			$size = filesize( $file['tmp_name'] );
			if ( false === $size || $size > wp_max_upload_size() ) {
				return __( 'Upload failed!', 'woocommerce-jetpack' );
			}
			// Uploaded temporary files are local even when WordPress updates use FTP/SSH.
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$filesystem = new WP_Filesystem_Direct( null );
			$contents   = $filesystem->get_contents( $file['tmp_name'] );
			if ( ! is_string( $contents ) ) {
				return __( 'Upload failed!', 'woocommerce-jetpack' );
			}
			$contents = preg_replace( '/^\xEF\xBB\xBF/', '', $contents );
			$lines    = preg_split( '/\r\n|\n|\r/', rtrim( $contents, "\r\n" ), 2 );
			if ( 2 !== count( $lines ) || ! preg_match( '/^Booster for WooCommerce(?: v[^\r\n]+)?$/D', $lines[0] ) || '{' !== substr( ltrim( $lines[1] ), 0, 1 ) ) {
				return __( 'Wrong file format!', 'woocommerce-jetpack' );
			}
			$settings = json_decode( $lines[1], true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $settings ) ) {
				return __( 'Wrong file format!', 'woocommerce-jetpack' );
			}
			// Validate the complete document before changing any option. Never import non-Booster options.
			foreach ( array_keys( $settings ) as $key ) {
				if ( ! $this->is_backup_setting_key( $key ) ) {
					return __( 'Wrong file format!', 'woocommerce-jetpack' );
				}
			}
			foreach ( $settings as $key => $value ) {
				// Older exports used HTML field IDs for array settings. Restore their option instead.
				if ( preg_match( '/^([^\[]+)\[([^\]]+)\]$/D', $key, $parts ) ) {
					$option                = isset( $settings[ $parts[1] ] ) && is_array( $settings[ $parts[1] ] ) ? $settings[ $parts[1] ] : (array) get_option( $parts[1], array() );
					$option[ $parts[2] ]   = $value;
					$settings[ $parts[1] ] = $option;
					unset( $settings[ $key ] );
				}
			}
			foreach ( $settings as $key => $value ) {
				update_option( $key, $value );
			}
			/* translators: %d: Number of imported settings. */
			return sprintf( __( '%d options successfully imported.', 'woocommerce-jetpack' ), count( $settings ) );
		}

		/**
		 * Admit the legacy Booster backup option namespace, not arbitrary WordPress options.
		 *
		 * @param mixed $key Imported or exported option name.
		 * @return bool Whether both backup directions support the name.
		 */
		private function is_backup_setting_key( $key ) {
			return is_string( $key ) && ( 1 === preg_match( '/^wcj_[a-zA-Z0-9_-]+(?:\[[a-zA-Z0-9_-]+\])?$/D', $key ) || false );
		}

		/**
		 * Stream the backup without a shared, publicly addressable temporary file.
		 */
		public function manage_options_export() {
			if ( ! $this->can_manage_settings() ) {
				return;
			}
			$export_settings = array();
			$missing         = new stdClass();
			// Back up saved values only: writing UI defaults can change previously unset runtime behavior.
			// Some module settings render notices. Keep that HTML out of the backup.
			ob_start();
			foreach ( w_c_j()->modules as $module ) {
				foreach ( $module->get_settings() as $value ) {
					if ( isset( $value['default'], $value['id'] ) ) {
						$id = $value['id'];
						if ( false !== strpos( $id, '[' ) ) {
							$id = substr( $id, 0, strpos( $id, '[' ) );
						}
						$setting = get_option( $id, $missing );
						if ( $missing !== $setting ) {
							$export_settings[ $id ] = $setting;
						}
					}
				}
			}
			foreach ( array( 'wcj_autoload_options', 'wcj_load_modules_on_init', 'wcj_list_for_products', 'wcj_list_for_products_cats', 'wcj_list_for_products_tags' ) as $id ) {
				$setting = get_option( $id, $missing );
				if ( $missing !== $setting ) {
					$export_settings[ $id ] = $setting;
				}
			}
			ob_end_clean();
			foreach ( array_keys( $export_settings ) as $key ) {
				if ( ! $this->is_backup_setting_key( $key ) ) {
					wp_die( esc_html__( 'Settings could not be exported.', 'woocommerce-jetpack' ) );
				}
			}
			$json = wp_json_encode( (object) $export_settings );
			if ( false === $json ) {
				wp_die( esc_html__( 'Settings could not be exported.', 'woocommerce-jetpack' ) );
			}
			$backup = 'Booster for WooCommerce v' . wcj_get_option( WCJ_VERSION_OPTION, 'NA' ) . "\n" . $json;
			nocache_headers();
			header( 'Content-Type: text/plain; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="booster_settings.txt"' );
			header( 'X-Content-Type-Options: nosniff' );
			// A text/plain attachment is the backup format, not HTML. Escaping would corrupt saved settings.
			echo $backup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authorized, JSON-encoded text/plain download.
			exit;
		}

		/**
		 * Manage_options_reset_meta.
		 *
		 * @version 7.0.0
		 * @since   3.4.0
		 * @todo    order items meta
		 * @todo    `... LIKE 'wcj_%'`
		 */
		public function manage_options_reset_meta() {
			global $wpdb, $wcj_notice;
			$delete_counter_meta = 0;
			$plugin_meta         = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_wcj_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( $plugin_meta as $meta ) {
				delete_post_meta( $meta->post_id, $meta->meta_key );
				++$delete_counter_meta;
			}
			/* translators: %d: translation added */
			$wcj_notice .= sprintf( __( '%d meta successfully deleted.', 'woocommerce-jetpack' ), $delete_counter_meta );
			return $wcj_notice;
		}

		/**
		 * Manage_options_reset.
		 *
		 * @version 7.0.0
		 * @since   2.5.2
		 */
		public function manage_options_reset() {
			global $wpdb, $wcj_notice;
			$delete_counter_options = 0;
			$plugin_options         = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'wcj_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( $plugin_options as $option ) {
				delete_option( $option->option_name );
				delete_site_option( $option->option_name );
				++$delete_counter_options;
			}
			/* translators: %d: translation added */
			$wcj_notice .= sprintf( __( '%d options successfully deleted.', 'woocommerce-jetpack' ), $delete_counter_options );
			return $wcj_notice;
		}
	}

endif;

return new WCJ_Settings_Manager();
