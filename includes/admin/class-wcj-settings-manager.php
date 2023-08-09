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

			require_once ABSPATH . '/wp-admin/includes/file.php';
			global $wp_filesystem;
			WP_Filesystem();
			$file_name = 'booster_settings.txt';
			$file_path = wcj_get_wcj_uploads_dir() . DIRECTORY_SEPARATOR . $file_name;
			if ( $wp_filesystem->exists( $file_path ) ) {
				$wp_filesystem->delete( $file_path, true );
			}
			$wpnonce = isset( $_REQUEST['wcj-verify-manage-settings'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-verify-manage-settings'] ), 'wcj-verify-manage-settings' ) : false;

			if ( $wpnonce ) {

				if ( isset( $_POST['return_url'] ) ) {
					$return_url = sanitize_text_field( wp_unslash( $_POST['return_url'] ) ) . '&msg=' . $msg . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' );
				} else {
					$return_url = admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) );
				}

				if ( isset( $_POST['booster_export_settings'] ) ) {
					$this->manage_options_export();
				}

				if ( isset( $_POST['booster_import_settings'] ) ) {
					$msg         = $this->manage_options_import();
					$return_url .= '&msg=' . $msg;
					if ( isset( $_POST['wcj_quick_action'] ) ) {
						$return_url .= '#message';
					}
					wp_safe_redirect( $return_url );
					exit();
				}

				if ( isset( $_POST['booster_reset_settings'] ) ) {
					$msg         = $this->manage_options_reset();
					$return_url .= '&msg=' . $msg;
					wp_safe_redirect( $return_url );
					exit();
				}
				if ( isset( $_POST['booster_reset_settings_meta'] ) ) {
					$msg         = $this->manage_options_reset_meta();
					$return_url .= '&msg=' . $msg;
					wp_safe_redirect( $return_url );
					exit();
				}

				$general_setting_fields = array(
					'wcj_autoload_options'       => isset( $_POST['wcj_autoload_options'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_autoload_options'] ) ) : null,
					'wcj_load_modules_on_init'   => isset( $_POST['wcj_load_modules_on_init'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_load_modules_on_init'] ) ) : null,
					'wcj_list_for_products'      => isset( $_POST['wcj_list_for_products'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products'] ) ) : null,
					'wcj_list_for_products_cats' => isset( $_POST['wcj_list_for_products_cats'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products_cats'] ) ) : null,
					'wcj_list_for_products_tags' => isset( $_POST['wcj_list_for_products_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_list_for_products_tags'] ) ) : null,
				);

				foreach ( $general_setting_fields as $setting_key => $setting_value ) {
					if ( ! empty( $setting_value ) && null !== $setting_value ) {
						update_option( $setting_key, $setting_value );
					}
				}

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
		 * Manage_options_import.
		 *
		 * @version 7.1.0
		 * @since   2.5.2
		 */
		public function manage_options_import() {
			global $wcj_notice;
			if ( ! isset( $_FILES['booster_import_settings_file']['tmp_name'] ) || '' === $_FILES['booster_import_settings_file']['tmp_name'] ) {
				$wcj_notice     .= __( 'Please upload a file to import!', 'woocommerce-jetpack' );
				$import_settings = array();
				$wpnonce         = isset( $_REQUEST['wcj-verify-manage-settings'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-verify-manage-settings'] ), 'wcj-verify-manage-settings' ) : false;
				if ( $wpnonce ) {
					unset( $_POST['booster_import_settings'] );
				}
				return $wcj_notice;
			} else {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				global $wp_filesystem;
				WP_Filesystem();
				$import_counter  = 0;
				$import_settings = $wp_filesystem->get_contents( sanitize_text_field( wp_unslash( $_FILES['booster_import_settings_file']['tmp_name'] ) ) );
				$bom             = pack( 'H*', 'EFBBBF' );
				$import_settings = preg_replace( "/^$bom/", '', $import_settings );
				$import_settings = explode( PHP_EOL, preg_replace( '~(*BSR_ANYCRLF)\R~', PHP_EOL, $import_settings ) );
				if ( ! is_array( $import_settings ) || 2 !== count( $import_settings ) ) {
					$wcj_notice .= __( 'Wrong file format!', 'woocommerce-jetpack' );
					return $wcj_notice;
				} else {
					$import_header   = $import_settings[0];
					$required_header = 'Booster for WooCommerce';
					if ( substr( $import_header, 0, strlen( $required_header ) ) !== $required_header ) {
						$wcj_notice .= __( 'Wrong file format!', 'woocommerce-jetpack' );
						return $wcj_notice;
					} else {
						$import_settings = json_decode( $import_settings[1], true );
						foreach ( $import_settings as $import_key => $import_setting ) {
							if ( strlen( $import_key ) > 4 && 'wcj_' === substr( $import_key, 0, 4 ) ) {
								update_option( $import_key, $import_setting );
								$import_counter++;
							}
						}
						/* translators: %d: translation added */
						$wcj_notice .= sprintf( __( '%d options successfully imported.', 'woocommerce-jetpack' ), $import_counter );
						return $wcj_notice;
					}
				}
			}
		}

		/**
		 * Manage_options_export.
		 *
		 * @version 7.0.0
		 * @since   2.5.2
		 * @see     http://php.net/manual/en/function.header.php
		 */
		public function manage_options_export() {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			global $wp_filesystem;
			WP_Filesystem();
			$export_settings = array();
			$export_counter  = array();
			$wpnonce         = isset( $_REQUEST['wcj-verify-manage-settings'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-verify-manage-settings'] ), 'wcj-verify-manage-settings' ) : false;
			foreach ( w_c_j()->modules as $module ) {
				$values = $module->get_settings();
				foreach ( $values as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						if ( isset( $_POST['booster_export_settings'] ) ) {
							$export_settings[ $value['id'] ] = wcj_get_option( $value['id'], $value['default'] );
							if ( ! isset( $export_counter[ $module->short_desc ] ) ) {
								$export_counter[ $module->short_desc ] = 0;
							}
							$export_counter[ $module->short_desc ]++;
						}
					}
				}
			}
			$export_settings = wp_json_encode( $export_settings );
			$export_settings = 'Booster for WooCommerce v' . wcj_get_option( WCJ_VERSION_OPTION, 'NA' ) . PHP_EOL . $export_settings;
			$file_name       = 'booster_settings.txt';
			$file_path       = wcj_get_wcj_uploads_dir() . DIRECTORY_SEPARATOR . $file_name;
			$wp_filesystem->put_contents( $file_path, $export_settings, FS_CHMOD_FILE );
			WC_Download_Handler::download_file_force( $file_path, $file_name );
			die();
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
				$delete_counter_meta++;
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
				$delete_counter_options++;
			}
			/* translators: %d: translation added */
			$wcj_notice .= sprintf( __( '%d options successfully deleted.', 'woocommerce-jetpack' ), $delete_counter_options );
			return $wcj_notice;
		}

	}

endif;

return new WCJ_Settings_Manager();
