<?php
/**
 * Booster for WooCommerce - Core - Admin
 *
 * @version 7.2.5
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
		 * @version 7.1.6
		 * @since   3.2.4
		 */
	class WCJ_Admin {

		/**
		 * The module cats
		 *
		 * @var varchar $cats Module.
		 */
		public $cats;
		/**
		 * The module site_url
		 *
		 * @var varchar $site_url Module.
		 */
		public $site_url;
		/**
		 * The module module_statuses
		 *
		 * @var varchar $module_statuses Module.
		 */
		public $module_statuses;
		/**
		 * The module custom_dashboard_modules
		 *
		 * @var varchar $custom_dashboard_modules Module.
		 */
		public $custom_dashboard_modules;
		/**
		 * Constructor.
		 *
		 * @version 7.2.5
		 * @since   3.2.4
		 */
		public function __construct() {

			$this->cats                     = include WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-modules-cats.php';
			$this->custom_dashboard_modules = apply_filters( 'wcj_custom_dashboard_modules', array() );

			if ( is_admin() ) {
				add_filter( 'booster_message', 'wcj_get_plus_message', 100, 3 );

				if ( apply_filters( 'wcj_can_create_admin_interface', true ) ) {
					add_filter( 'plugin_action_links_' . plugin_basename( WCJ_FREE_PLUGIN_FILE ), array( $this, 'action_links' ) );
					add_action( 'admin_menu', array( $this, 'booster_menu' ), 100 );
					add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 2 );
					if ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE ) ) {
						add_action( 'admin_notices', array( $this, 'check_plus_version' ) );
					}

					add_action( 'admin_enqueue_scripts', array( $this, 'wcj_new_desing_dashboard' ) );
				}

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ), PHP_INT_MAX );

				add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'unclean_custom_textarea' ), PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'maybe_unclean_field' ), PHP_INT_MAX, 3 );
				add_action( 'admin_post_wcj_save_module_settings', array( $this, 'wcj_save_module_settings' ) );
			}
		}

		/**
		 * Save module settings.
		 *
		 * @version 7.1.0
		 */
		public function wcj_save_module_settings() {
			$wpnonce = isset( $_POST['wcj-verify-save-module-settings'] ) ? wp_verify_nonce( sanitize_key( $_POST['wcj-verify-save-module-settings'] ), 'wcj-verify-save-module-settings' ) : false;
			if ( $wpnonce ) {

				if ( isset( $_POST['section'] ) ) {
					$settings = $this->get_settings( sanitize_key( $_POST['section'] ) );
					WC_Admin_Settings::save_fields( $settings );
					$this->wcj_save_other_modules_key_settings();
					$this->disable_autoload_options_from_section( $settings );
					if ( isset( $_POST['wcj_template_editor_templates_content'] ) ) {
						$wcj_template_editor_templates_content = wp_unslash( $_POST['wcj_template_editor_templates_content'] ); // phpcs:ignore	
						update_option( 'wcj_template_editor_templates_content', $wcj_template_editor_templates_content );
					}
					do_action( 'woojetpack_after_settings_save', $this->get_sections(), sanitize_key( $_POST['section'] ) );
				} else {
					$this->wcj_save_other_modules_key_settings();
				}

				$active_tab = isset( $_POST['wcj_setting_active_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_setting_active_tab'] ) ) : '';

				if ( isset( $_POST['return_url'] ) ) {
					$return_url = sanitize_text_field( wp_unslash( $_POST['return_url'] ) ) . '&active_tab=' . $active_tab . '&success=1&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' );
					wp_safe_redirect( $return_url );
					exit();
				} else {
					wp_safe_redirect( admin_url( 'admin.php?page=wcj-plugins&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) ) );
					exit();
				}
			}
		}

		/**
		 * Wcj_save_other_modules_key_settings.
		 *
		 * @version 7.1.0
		 * @since  7.1.0
		 */
		public function wcj_save_other_modules_key_settings() {
			if ( is_admin() ) {
				$wpnonce = isset( $_POST['wcj-verify-save-module-settings'] ) ? wp_verify_nonce( sanitize_key( $_POST['wcj-verify-save-module-settings'] ), 'wcj-verify-save-module-settings' ) : false;
				if ( $wpnonce ) {
					$module_keys             = array_column( w_c_j()->module_statuses, 'id' );
					$post_keys               = array_keys( $_POST );
					$other_modules_to_enable = array_intersect( $module_keys, $post_keys );

					foreach ( $other_modules_to_enable as $key => $module_key ) {
						$module_enable_setting = isset( $_POST[ $module_key ] ) && '' !== $_POST[ $module_key ] ? sanitize_text_field( wp_unslash( $_POST[ $module_key ] ) ) : null;
						if ( ! empty( $module_enable_setting ) && null !== $module_enable_setting ) {
							update_option( $module_key, wp_unslash( $module_enable_setting ) );
						}
					}
				}
			}
		}

		/**
		 * Format_wcj_number_plus_checkbox_end.
		 *
		 * @version 7.0.0
		 * @since  1.0.0
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function format_wcj_number_plus_checkbox_end( $value, $option, $raw_value ) {
			return ( 'wcj_number_plus_checkbox_end' === $option['type'] ) ? ( '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no' ) : $value;
		}

		/**
		 * Unclean_custom_textarea.
		 *
		 * @version 7.0.0
		 * @since  1.0.0
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function unclean_custom_textarea( $value, $option, $raw_value ) {
			return ( 'custom_textarea' === $option['type'] ) ? $raw_value : $value;
		}

		/**
		 * Maybe_unclean_field.
		 *
		 * @version 7.0.0
		 * @since  1.0.
		 * @param  Array $value Get values.
		 * @param  Array $option Get options.
		 * @param  Array $raw_value Get raw value.
		 */
		public function maybe_unclean_field( $value, $option, $raw_value ) {
			return ( isset( $option['wcj_raw'] ) && $option['wcj_raw'] ? $raw_value : $value );
		}

		/**
		 * Disable_autoload_options.
		 *
		 * @version 7.0.0
		 * @since   1.0.0
		 *
		 * @param array $settings defines the settings.
		 */
		public function disable_autoload_options_from_section( $settings ) {
			$fields         = wp_list_filter( $settings, array( 'autoload' => false ) );
			$fields         = wp_list_filter( $fields, array( 'type' => 'title' ), 'NOT' );
			$fields         = wp_list_filter( $fields, array( 'type' => 'sectionend' ), 'NOT' );
			$field_ids      = wp_list_pluck( $fields, 'id' );
			$fields_ids_str = '"' . implode( '","', $field_ids ) . '"';
			global $wpdb;

			if ( count( $field_ids ) > 0 ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name IN (" . implode( ', ', array_fill( 0, count( $field_ids ), '%s' ) ) . ") AND autoload != 'no'", $field_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		/**
		 * New desing dashboard style script.
		 *
		 * @version 7.0.0
		 */
		public function wcj_new_desing_dashboard() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;

			$active_page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );

			if ( 'wcj-dashboard' === $active_page || 'wcj-plugins' === $active_page || 'wcj-general-settings' === $active_page || 'wcj-license' === $active_page || 'wcj-tools' === $active_page ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wcj-admin-select2', esc_url( wcj_plugin_url() ) . '/includes/lib/select2/css/select2.min.css', array(), w_c_j()->version );
				wp_enqueue_style( 'wcj-admin-new-style', esc_url( wcj_plugin_url() ) . '/includes/css/admin-style.css', array(), w_c_j()->version );
				wp_enqueue_script( 'wcc-admin-select2', esc_url( wcj_plugin_url() ) . '/includes/lib/select2/js/select2.min.js', array(), w_c_j()->version, true );
				wp_enqueue_script( 'wcj-admin-new-script', esc_url( wcj_plugin_url() ) . '/includes/js/admin-script.js', array( 'jquery' ), w_c_j()->version, true );

				wp_localize_script(
					'wcj-admin-new-script',
					'wcj_admin_ajax_obj',
					array(
						'ajax_url'                        => admin_url( 'admin-ajax.php' ),
						'search_products_nonce'           => wp_create_nonce( 'search-products' ),
						'search_customers_nonce'          => wp_create_nonce( 'search-customers' ),
						'search_categories_nonce'         => wp_create_nonce( 'search-categories' ),
						'search_taxonomy_terms_nonce'     => wp_create_nonce( 'search-taxonomy-terms' ),
						'search_product_attributes_nonce' => wp_create_nonce( 'search-product-attributes' ),
						'search_pages_nonce'              => wp_create_nonce( 'search-pages' ),
					)
				);
			}
		}

		/**
		 * Enqueue_admin_script.
		 *
		 * @version 7.2.5
		 * @since  1.0.0
		 */
		public function enqueue_admin_script() {
			wp_enqueue_script( 'wcj-admin-js', trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-admin.js', array( 'jquery' ), w_c_j()->version, true );
			wp_localize_script(
				'wcj-admin-js',
				'admin_object',
				array(
					'resetSettings' => __( 'This will reset settings to defaults for all Booster modules.', 'woocommerce-jetpack' ),
					'deleteMeta'    => __( 'This will delete all Booster meta. Are you sure?', 'woocommerce-jetpack' ),
				)
			);
		}

		/**
		 * Check_plus_version.
		 *
		 * @version 7.0.0
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
						$is_deprecated_plus_active = true; // can't `brake` because of possible active `booster-elite-for-woocommerce.php`.
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
					'https://booster.io/booster-elite-for-woocommerce-update/'
				);
				echo '<div class="' . esc_html( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
			}
		}

		/**
		 * Admin_footer_text
		 *
		 * @version 7.0.0
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
						<span class="wcj_admin_span wcj-woo-star">
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
		 * @version 7.0.0
		 */
		public function booster_menu() {
			add_menu_page(
				__( 'Booster', 'woocommerce-jetpack' ),
				__( 'Booster', 'woocommerce-jetpack' ),
				( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
				'wcj-dashboard',
				'',
				'dashicons-admin-settings',
				26
			);

			add_submenu_page(
				'wcj-dashboard',
				__( 'Dashboard', 'woocommerce-jetpack' ),
				__( 'Dashboard', 'woocommerce-jetpack' ),
				( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
				'wcj-dashboard',
				array( $this, 'wcj_dashboard_page' )
			);

			add_submenu_page(
				'wcj-dashboard',
				__( 'Plugins', 'woocommerce-jetpack' ),
				__( 'Plugins', 'woocommerce-jetpack' ),
				( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
				'wcj-plugins',
				array( $this, 'wcj_plugins_page' )
			);

			add_submenu_page(
				'wcj-dashboard',
				__( 'General Settings', 'woocommerce-jetpack' ),
				__( 'General Settings', 'woocommerce-jetpack' ),
				( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce' ),
				'wcj-general-settings',
				array( $this, 'wcj_general_settings_page' )
			);
		}

		/**
		 * Wcj-settings-dashboard
		 *
		 * @version 7.0.0
		 */
		public function wcj_dashboard_page() {
			include WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-dashboard.php';
		}

		/**
		 * Wcj-settings-plugins
		 *
		 * @version 7.0.0
		 */
		public function wcj_plugins_page() {
			$this->add_module_statuses( w_c_j()->module_statuses );
			include WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-plugins.php';
		}

		/**
		 * Wcj-settings-general
		 *
		 * @version 7.0.0
		 */
		public function wcj_general_settings_page() {
			include WCJ_FREE_PLUGIN_PATH . '/includes/admin/wcj-settings-general.php';
		}

		/**
		 * Show action links on the plugin screen
		 *
		 * @version 6.0.0
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
		 * Add_module_statuses
		 *
		 * @version 7.0.0
		 * @param   array $statuses All module statuses.
		 */
		public function add_module_statuses( $statuses ) {
			$this->module_statuses = $statuses;
		}

		/**
		 * Compare_for_usort.
		 *
		 * @version 7.0.0
		 * @param   string $a this defines title.
		 * @param   string $b this defines title.
		 */
		private function compare_for_usort( $a, $b ) {
			return strcmp( $a['title'], $b['title'] );
		}

		/**
		 * Is_dashboard_section.
		 *
		 * @version 7.0.0
		 * @param   array $current_section defines the current section.
		 */
		public function is_dashboard_section( $current_section ) {
			return in_array( $current_section, array_merge( array( '', 'all_module', 'by_category', 'active', 'manager' ), array_keys( $this->custom_dashboard_modules ) ), true );
		}

		/**
		 * Active.
		 *
		 * @version 7.0.0
		 * @param   array $active Check.
		 */
		public function active( $active ) {
			return ( 'yes' === $active ) ? 'active' : 'inactive';
		}

		/**
		 * Output_cats_submenu.
		 *
		 * @version 7.0.0
		 */
		public function output_cats_submenu() {
			global $current_section;
			$wpnonce     = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			$current_cat = isset( $_REQUEST['wcj-cat'] ) && ! empty( $_REQUEST['wcj-cat'] ) ? sanitize_title( wp_unslash( $_REQUEST['wcj-cat'] ) ) : 'prices_and_currencies';

			$section    = ( isset( $_REQUEST['section'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) : '' );
			$wcj_search = ( isset( $_REQUEST['wcj_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj_search'] ) ) : '' );

			if ( 'active' === $section || '' !== $wcj_search ) {
				$current_cat = 'active';
			}

			if ( empty( $this->cats ) ) {
				return;
			}
			echo '<ul>';
			$array_keys = array_keys( $this->cats );
			foreach ( $this->cats as $id => $label_info ) {
				$dashboard_section = '';
				if ( 'dashboard' === $id ) {
					continue;
				}
				echo wp_kses_post(
					'<li ' . $id . '>
	          			<a href="' . admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . sanitize_title( $id ) . '' . $dashboard_section ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '" class="' . ( $current_cat === $id ? 'active' : '' ) . '">
	          				<img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/' . $label_info['icon'] . '">
	          				<span class="wcj_admin_span">' . $label_info['label'] . '<span>
	          			</a>
	          		</li>'
				);
			}
			echo '</ul>';
		}

		/**
		 * Get_cat_by_section.
		 *
		 * @version 7.0.0
		 * @param   array $section An collection of settings key value pairs.
		 */
		public function get_cat_by_section( $section ) {
			foreach ( $this->cats as $id => $label_info ) {
				if ( ! empty( $label_info['all_cat_ids'] ) ) {
					if ( in_array( $section, $label_info['all_cat_ids'], true ) ) {
						return $id;
					}
				}
			}
			return '';
		}

		/**
		 * Get_manager_settings.
		 *
		 * @version 7.2.5
		 * @since   1.0.0
		 * @param   string $section defines the section type.
		 * @return  array
		 */
		public function get_manager_settings( $section = '' ) {
			if ( 'site_key' === $section ) {
				return array(
					array(
						'type'  => 'module_head',
						'title' => __( 'Site Key Settings', 'woocommerce-jetpack' ),
						'desc'  => __( 'This section lets you manage site key for paid Booster Plus for WooCommerce plugin.', 'woocommerce-jetpack' ),
						'icon'  => 'menu-icn4.png',
					),
					array(
						'title'   => __( 'Site Key', 'woocommerce-jetpack' ),
						'type'    => 'text',
						'id'      => 'wcj_site_key',
						'default' => '',
					),
				);
			} else {
				return array(
					array(
						'type'  => 'module_head',
						'title' => __( 'General Settings', 'woocommerce-jetpack' ),
						'desc'  => __( 'This section lets you export, import or reset all Booster\'s modules settings.', 'woocommerce-jetpack' ),
						'icon'  => 'menu-icn3.png',
					),
					array(
						'type'  => 'button',
						'title' => __( 'Export all Booster\'s options to a file.', 'woocommerce-jetpack' ),
						'html'  => '<button style="width:100px;" class="wcj_manage_settting_btn" type="submit" name="booster_export_settings">' . __( 'Export', 'woocommerce-jetpack' ) . '</button>',
					),
					array(
						'type'  => 'button',
						'title' => __( 'Import all Booster\'s options from a file.', 'woocommerce-jetpack' ),
						'html'  => '<input style="display: block;margin-bottom: 10px;" type="file" name="booster_import_settings_file"><button style="width:100px;" class="wcj_manage_settting_btn" type="submit" name="booster_import_settings">' . __( 'Import', 'woocommerce-jetpack' ) . '</button>',
					),
					array(
						'type'  => 'button',
						'title' => __( 'Reset all Booster\'s options.', 'woocommerce-jetpack' ),
						'html'  => '<button style="width:100px;" class="wcj_manage_settting_btn" type="submit" name="booster_reset_settings">' .
						__( 'Reset', 'woocommerce-jetpack' ) . '</button>',
					),
					array(
						'type'  => 'button',
						'title' => __( 'Reset all Booster\'s meta.', 'woocommerce-jetpack' ),
						'html'  => '<button style="width:100px;" class="wcj_manage_settting_btn" type="submit" name="booster_reset_settings_meta">' .
						__( 'Reset meta', 'woocommerce-jetpack' ) . '</button>',
					),
					array(
						'title'   => __( 'Autoload Booster\'s Options', 'woocommerce-jetpack' ),
						'type'    => 'checkbox',
						'desc'    => __( 'Choose if you want Booster\'s options to be autoloaded when calling add_option. After saving this option, you need to Reset all Booster\'s settings. Leave default value (i.e. Enabled) if not sure.', 'woocommerce-jetpack' ),
						'id'      => 'wcj_autoload_options',
						'default' => 'yes',
					),
					array(
						'title'   => __( 'Load Modules on Init Hook', 'woocommerce-jetpack' ),
						'type'    => 'checkbox',
						'desc'    => __( 'Choose if you want to load Booster Modules on Init hook.', 'woocommerce-jetpack' ) . ' ' . __( 'It will load the locale appropriately if users change it from the profile page.', 'woocommerce-jetpack' ),
						'id'      => 'wcj_load_modules_on_init',
						'default' => 'no',
					),
					array(
						'title'   => __( 'Use List Instead of Comma Separated Text for Products in Settings', 'woocommerce-jetpack' ),
						'type'    => 'checkbox',
						'desc'    => sprintf(
									/* translators: %s: search term */
							__( 'Supported modules: %s.', 'woocommerce-jetpack' ),
							implode(
								', ',
								array(
									__( 'Gateways per Product or Category', 'woocommerce-jetpack' ),
									__( 'Global Discount', 'woocommerce-jetpack' ),
									__( 'Product Info', 'woocommerce-jetpack' ),
									__( 'Product Input Fields', 'woocommerce-jetpack' ),
									__( 'Products XML', 'woocommerce-jetpack' ),
									__( 'Related Products', 'woocommerce-jetpack' ),
								)
							)
						),
						'id'      => 'wcj_list_for_products',
						'default' => 'yes',
					),
					array(
						'title'   => __( 'Use List Instead of Comma Separated Text for Products Categories in Settings', 'woocommerce-jetpack' ),
						'type'    => 'checkbox',
						'desc'    => sprintf(
									/* translators: %s: search term */
							__( 'Supported modules: %s.', 'woocommerce-jetpack' ),
							implode(
								', ',
								array(
									__( 'Product Info', 'woocommerce-jetpack' ),
								)
							)
						),
						'id'      => 'wcj_list_for_products_cats',
						'default' => 'yes',
					),
					array(
						'title'   => __( 'Use List Instead of Comma Separated Text for Products Tags in Settings', 'woocommerce-jetpack' ),
						'type'    => 'checkbox',
						'desc'    => sprintf(
									/* translators: %s: search term */
							__( 'Supported modules: %s.', 'woocommerce-jetpack' ),
							implode(
								', ',
								array(
									__( 'Product Info', 'woocommerce-jetpack' ),
								)
							)
						),
						'id'      => 'wcj_list_for_products_tags',
						'default' => 'yes',
					),
				);
			}
		}

		/**
		 * Get_settings.
		 *
		 * @version 7.0.0
		 * @param   array $current_section defines the current section.
		 */
		public function get_settings( $current_section = '' ) {
			if ( ! $this->is_dashboard_section( $current_section ) ) {
				return apply_filters( 'wcj_settings_' . $current_section, array() );
			} else {
				$wpnonce    = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
				$cat_id     = ( isset( $_GET['wcj-cat'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['wcj-cat'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['wcj-cat'] ) ) : 'dashboard';
				$wcj_search = ( isset( $_REQUEST['wcj_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj_search'] ) ) : '' );
				$settings[] = array(
					'title' => __( 'Booster Elite for WooCommerce', 'woocommerce-jetpack' ) . ' - ' . $this->cats[ $cat_id ]['label'],
					'type'  => 'title',
					'desc'  => $this->cats[ $cat_id ]['desc'],
					'id'    => 'wcj_' . $cat_id . '_options',
				);
				if ( 'dashboard' === $cat_id ) {
					if ( isset( w_c_j()->module_statuses ) ) {
						$settings = array_merge( $settings, w_c_j()->module_statuses );
					}
				} elseif ( '' !== $wcj_search ) {
					if ( isset( w_c_j()->module_statuses ) ) {
						$settings = array_merge( $settings, w_c_j()->module_statuses );
					}
				} else {
					$cat_module_statuses = array();
					foreach ( $this->module_statuses as $module_status ) {
						$section = $module_status['id'];
						$section = str_replace( 'wcj_', '', $section );
						$section = str_replace( '_enabled', '', $section );
						if ( $cat_id === $this->get_cat_by_section( $section ) ) {
							$cat_module_statuses[] = $module_status;
						}
					}
					$settings = array_merge( $settings, $cat_module_statuses );
				}
				$settings[] = array(
					'type'  => 'sectionend',
					'id'    => 'wcj_' . $cat_id . '_options',
					'title' => '',
				);
				return $settings;
			}
		}

		/**
		 * Wcj_array_search.
		 *
		 * @param   array  $arr    defines the search array.
		 * @param   string $patron defines the search string.
		 * @version 6.0.1
		 */
		public function wcj_array_search( array $arr, string $patron ): array {
			return array_filter(
				$arr,
				static function ( mixed $value ) use ( $patron ): bool {
					return 1 === preg_match( sprintf( '/^%s$/i', preg_replace( '/(^%)|(%$)/', '.*', $patron ) ), $value );
				}
			);
		}

		/**
		 * Get sections (modules)
		 *
		 * @return array
		 */
		public function get_sections() {
			return apply_filters( 'wcj_settings_sections', array( '' => __( 'Dashboard', 'woocommerce-jetpack' ) ) );
		}

		/**
		 * Output_modules.
		 *
		 * @param   string $skip_section defines the skip section.
		 * @version 7.0.0
		 */
		public function output_modules( $skip_section = '' ) {
			$html            = '';
			$wpnonce         = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			$settings        = $this->get_settings( 'all_module' );
			$cat_id          = ( isset( $_GET['wcj-cat'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['wcj-cat'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['wcj-cat'] ) ) : 'prices_and_currencies';
			$setting_section = ( isset( $_GET['section'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['section'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
			$wcj_search      = ( isset( $_REQUEST['wcj_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wcj_search'] ) ) : '' );
			usort( $settings, array( $this, 'compare_for_usort' ) );

			$wcj_search_results = array();
			if ( '' !== $wcj_search ) {
				$wcj_search_results = $this->wcj_array_search( array_column( $settings, 'title' ), '%' . $wcj_search . '%' );
				if ( empty( $wcj_search_results ) ) {
					$search_result_msg = __( 'Your search', 'woocommerce-jetpack' ) . ': ' . $wcj_search . ' ' . __( 'did not match with any plugins. please try different keywords.', 'woocommerce-jetpack' );
					$html             .= wp_kses_post( '<div style="padding:15px;"><p><strong>' . $search_result_msg . '</strong></p></div>' );

				}
			}

			$total_modules = 0;
			foreach ( $settings as $key => $the_feature ) {
				$section = $the_feature['id'];
				$section = str_replace( 'wcj_', '', $section );
				$section = str_replace( '_enabled', '', $section );

				if ( '' !== $wcj_search ) {
					if ( ! array_key_exists( $key, $wcj_search_results ) ) {
						continue;
					}
					if ( wcj_is_module_deprecated( $section, false, true ) ) {
						continue;
					}
				} else {
					if ( 'checkbox' !== $the_feature['type'] ) {
						continue;
					}

					if ( $section === $skip_section ) {
						continue;
					}
					if ( wcj_is_module_deprecated( $section, false, true ) ) {
						continue;
					}
					if ( '' !== $cat_id ) {
						if ( 'active_modules_only' === $cat_id || 'active' === $skip_section ) {

							if ( 'no' === wcj_get_option( $the_feature['id'], 'no' ) ) {
								continue;
							}
						} elseif ( $cat_id !== $this->get_cat_by_section( $section ) ) {
							continue;
						}
					}
				}

				$total_modules++;

				if ( 'pdf_invoicing' !== $cat_id ) {
					$html .= '
						<div class="wcj-plugins-sing-acc-box-head">
							<div class="wcj-plugins-sing-head-lf">
								<span class="wcj_admin_span">
									<img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pr-sm-icn.png">
								</span>
								<div class="wcj-plugins-sing-head-rh">
									<a href="' . admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . sanitize_title( $cat_id ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '&section=' . $section ) . '"><h5>' . $the_feature['title'] . '</h5></a>
									<p>' . ( ( isset( $the_feature['wcj_desc'] ) ) ? $the_feature['wcj_desc'] : $the_feature['desc_tip'] ) . '</p>
								</div>
							</div>
							<div class="wcj-plugins-sing-head-right">
								<div class="wcj-plugins-border-sm-btn">
									<a target="_blank" href="' . $the_feature['wcj_link'] . '?utm_source=module_documentation&utm_medium=dashboard_link&utm_campaign=booster_documentation"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pdw-download.png"></a>
								</div>
								<div class="wcj-plugins-button-tp">
									<button id="disable_' . $the_feature['id'] . '" data-type="disable" data-id="' . $the_feature['id'] . '" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( $the_feature['id'] ) ) ? 'wcj-disable' : '' ) . '">' . __( 'Disable', 'woocommerce-jetpack' ) . '</button>
									<button id="enable_' . $the_feature['id'] . '" data-type="enable" data-id="' . $the_feature['id'] . '" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( $the_feature['id'] ) ) ? '' : 'wcj-disable' ) . '">' . __( 'Enable', 'woocommerce-jetpack' ) . '</button>
									<input id="' . $the_feature['id'] . '" type="hidden" name="' . $the_feature['id'] . '" value="' . ( 'yes' === ( wcj_get_option( $the_feature['id'] ) ) ? 'yes' : 'no' ) . '">
								</div>
								<div class="wcj-plugins-acc-arw">
									<a href="' . admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . $this->get_cat_by_section( $section ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '&section=' . $section ) . '"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/down-arw2.png"></a>
								</div>
							</div>
						</div>
					';
				}
			}

			if ( 'pdf_invoicing' === $cat_id ) {
				$sections = $this->get_sections();

				$pdf_invoicing_menu = array();
				foreach ( $this->cats[ $cat_id ]['all_cat_ids'] as $id ) {
					$pdf_invoicing_menu[ $id ] = $sections[ $id ];
				}

				foreach ( $pdf_invoicing_menu as $id => $label ) {
					if ( $id === $skip_section ) {
						continue;
					}

					$total_modules++;
					$url      = admin_url( 'admin.php?page=wcj-plugins&wcj-cat=' . sanitize_title( $cat_id ) . '&wcj-cat-nonce=' . wp_create_nonce( 'wcj-cat-nonce' ) . '&section=' . $id );
					$sub_desc = __( 'PDF Invoicing', 'woocommerce-jetpack' ) . ': ' . $label . ' ' . __( 'Settings', 'woocommerce-jetpack' );
					$html    .= '
                    <div class="wcj-plugins-sing-acc-box-head">
                        <div class="wcj-plugins-sing-head-lf">
                            <span class="wcj_admin_span">
                                <img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pr-sm-icn.png">
                            </span>
                            <div class="wcj-plugins-sing-head-rh">
                                <a href="' . $url . '"><h5>' . $label . '</h5></a>
                                <p>' . $sub_desc . '</p>
                            </div>
                        </div>
                        <div class="wcj-plugins-sing-head-right">
                            <div class="wcj-plugins-acc-arw" style="width: 100%;margin-right: 45px;">
                                <a style="float: right;text-align: right;" href="' . $url . '"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/down-arw2.png"></a>
                            </div>
                        </div>
                    </div>
                    ';
				}
			}

			echo wp_kses_post( $html );

			if ( 0 === $total_modules && 'active' === $skip_section ) {
				echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated wcj_setting_updated_cat" bis_skin_checked="1"><p><strong>' . __( 'No Active module found.', 'woocommerce-jetpack' ) . '</strong></p></div>' );
			} elseif ( 0 === $total_modules && '' === $setting_section ) {
				echo wp_kses_post( '<div id="message" class="updated inline wcj_setting_updated wcj_setting_updated_cat" bis_skin_checked="1"><p><strong>' . __( 'No Modules available.', 'woocommerce-jetpack' ) . '</strong></p></div>' );
			}

		}

		/**
		 * Output_settings.
		 *
		 * @version 7.1.8
		 * @param   array $current_section defines the current section.
		 */
		public function output_settings( $current_section = '' ) {

			$settings          = $this->get_settings( $current_section );
			$final_html        = '';
			$tab_ids_key       = '';
			$active_tab        = '';
			$tab_ids_titles    = array();
			$module_reset_link = '';

			if ( ! empty( $settings ) ) {
				if ( 'title' === $settings[0]['type'] || 'checkbox' === $settings[0]['type'] ) {
					unset( $settings[0] );
				}
				if ( 'title' === $settings[1]['type'] || 'checkbox' === $settings[1]['type'] ) {
					unset( $settings[1] );
				}

				foreach ( $settings as $setting ) {
					$type               = isset( $setting['type'] ) ? $setting['type'] : '';
					$id                 = isset( $setting['id'] ) ? $setting['id'] : '';
					$default_setting    = isset( $setting['default'] ) ? $setting['default'] : '';
					$option_value       = get_option( $id, $default_setting, true );
					$is_template_editor = isset( $setting['is_template_editor'] ) ? $setting['is_template_editor'] : '';

					if ( false !== strpos( $id, '[' ) ) {
						$id_arr = explode( '[', $id );
						if ( ! empty( $id_arr ) && 2 <= count( $id_arr ) ) {
							$temp_id = $id_arr[0];

							$id_key            = preg_replace( '/]/', '', $id_arr[1] );
							$id                = $temp_id . '[' . $id_key . ']';
							$temp_option_value = wcj_get_option( $temp_id, $default_setting, true );

							if ( is_array( $temp_option_value ) && ! empty( $temp_option_value ) && isset( $temp_option_value[ $id_key ] ) && '' !== $temp_option_value[ $id_key ] ) {
								$option_value = $temp_option_value[ $id_key ];
							}
						}
					}

					$title            = isset( $setting['title'] ) ? $setting['title'] : '';
					$desc             = isset( $setting['desc'] ) ? $setting['desc'] : '';
					$module_desc      = isset( $setting['module_desc'] ) ? $setting['module_desc'] : '';
					$css              = isset( $setting['css'] ) ? $setting['css'] : '';
					$ele_class        = isset( $setting['class'] ) ? $setting['class'] : '';
					$placeholder      = isset( $setting['placeholder'] ) ? $setting['placeholder'] : '';
					$checked          = ( 'yes' === $option_value ) ? 'checked' : '';
					$custom_link_link = isset( $setting['link'] ) ? $setting['link'] : '';
					$desc_tip_str     = isset( $setting['desc_tip'] ) ? $setting['desc_tip'] : '';

					if ( '' !== $desc_tip_str ) {
						$desc_tip = isset( $setting['desc_tip'] ) && '' !== $setting['desc_tip'] ? "<div class='wcj_help_tooltip_main'><div class='wcj_help_tooltip'><p class=''>" . $setting['desc_tip'] . '</p></div></div>' : '';
					} else {
						$desc_tip = '';
					}

					$custom_attributes = array();
					if ( ! empty( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] ) ) {
						foreach ( $setting['custom_attributes'] as $attribute => $attribute_value ) {
							$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
						}
					}

					switch ( $type ) {
						case 'module_head':
							$wcj_link          = isset( $setting['wcj_link'] ) ? $setting['wcj_link'] : '';
							$module_reset_link = isset( $setting['module_reset_link'] ) ? $setting['module_reset_link'] : '';
							$final_html       .= '
                                <div class="wcj-plugins-sing-acc-box-head">
                                    <div class="wcj-plugins-sing-head-lf">
                                        <span class="wcj_admin_span">
                                            <img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pr-sm-icn.png">
                                        </span>
                                        <div class="wcj-plugins-sing-head-rh">
                                            <h5>' . $title . '</h5>
                                            <p>' . $desc . '</p>
                                        </div>
                                    </div>
                                    <div class="wcj-plugins-sing-head-right">';
							if ( '' !== $id ) {
								$final_html .= '<div class="wcj-plugins-border-sm-btn">
                                                <a target="_blank" href="' . $wcj_link . '?utm_source=module_documentation&utm_medium=dashboard_link&utm_campaign=booster_documentation"><img src="' . esc_url( wcj_plugin_url() ) . '/assets/images/pdw-download.png"></a>
                                            </div>
                                            <div class="wcj-plugins-button-tp">
                                                <button id="disable_' . $id . '" data-type="disable" data-id="' . $id . '" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( $id ) ) ? 'wcj-disable' : '' ) . '">' . __( 'Disable', 'woocommerce-jetpack' ) . '</button>
                                                <button id="enable_' . $id . '" data-type="enable" data-id="' . $id . '" type="button" class="wcj_enable_plugin wcj-enble-btn ' . ( 'yes' === ( wcj_get_option( $id ) ) ? '' : 'wcj-disable' ) . '">' . __( 'Enable', 'woocommerce-jetpack' ) . '</button>
                                                <input id="' . $id . '" type="hidden" name="' . $id . '" value="' . ( 'yes' === ( wcj_get_option( $id ) ) ? 'yes' : 'no' ) . '">
                                            </div>
                                            <div class="wcj-plugins-acc-arw">
                                                <img data-img_path="' . esc_url( wcj_plugin_url() ) . '/assets/images/" class="wcj_closing_accordion_form" src="' . esc_url( wcj_plugin_url() ) . '/assets/images/up-arw-new.png">
                                            </div>';
							} else {
								$final_html .= '<div class="wcj-plugins-acc-arw" style="width: 100%;margin-right: 45px;">
                                                <img data-img_path="' . esc_url( wcj_plugin_url() ) . '/assets/images/" class="wcj_closing_accordion_form" style="float: right;text-align: right;" src="' . esc_url( wcj_plugin_url() ) . '/assets/images/up-arw-new.png"></div>';
							}
								$final_html .= '</div>
                                    ' . $module_desc . '
                                </div>
                                <div class="wcj-plugins-sing-acc-sub-cnt">
                            ';
							break;
						case 'tab_ids':
							$final_html .= '<div class="wcj-tab-menu"><ul class="wcj-nav wcj-nav-tabs">';
							if ( isset( $setting['tab_ids'] ) ) {
								$tab_ids_key = key( $setting['tab_ids'] );
								$wpnonce     = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
								$active_tab  = ( isset( $_GET['active_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['active_tab'] ) ) : $tab_ids_key );
								$active_tab  = esc_attr( $active_tab );
								foreach ( $setting['tab_ids'] as $key => $option ) {
									$tab_ids_titles[] = $option;
									$final_html      .= '<li class="wcj-nav-item">
                                        <a href="#" class="wcj-nav-link ' . ( $active_tab === $key ? 'active' : '' ) . '" data-rel="' . $key . '">' . $option . '</a>
                                    </li>';
								}
								$final_html .= '<input type="hidden" class="wcj_setting_active_tab" name="wcj_setting_active_tab" value="' . $active_tab . '">';
							}
							$final_html .= '</ul></div><div class="wcj-tab-main-box">';
							break;
						case 'tab_start':
							$final_html .= '<div class="wcj-tab-box" id="' . $id . '" style="' . ( $active_tab === $id ? 'display:block;' : '' ) . '">
                                                <div class="wcj-tab-cnt-main">
                            ';
							break;
						case 'tab_end':
							$final_html .= '</div><div class="wcj_tab_end_submit" style="margin-top:25px;"> 
                                <input tt style="width:auto;" type="submit" class="wcj_manage_settting_btn wcj_tab_end_save_btn" name="wcj_save_module_settings" value="' . __( 'Save Changes', 'woocommerce-jetpack' ) . '">
                                    ' . $module_reset_link . '
                                </div>
                            </div>';
							break;
						case 'title':
							if ( ! in_array( $title, $tab_ids_titles, false ) || $desc != '' ) // phpcs:ignore
							{
								$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                    <div class="wcj-plugins-form-inp-lf" style="width:100%;">
                                        <label>' . $title . '</label>
                                    </div>
                                    <p>' . $desc_tip . $desc . '</p>
                                </div>';
							}
							break;
						case 'text':
							$val_str     = "value='" . $option_value . "'";
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input id="' . $id . '" type="text" name="' . $id . '" placeholder="" class="wcj-plu-inp wcj_setting_text" ' . $val_str . ' ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'checkbox':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <label for="' . $id . '">
                                    <input type="hidden" id="' . $id . '" name="' . $id . '" value="' . $option_value . '">
                                    <input class="wcj_setting_checkbox_key" data-rel_id="' . $id . '" type="checkbox" ' . $checked . ' ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '></input>
                                    	' . $desc . '
                                    </label>
                                    <span class="wcj_admin_span"><em>' . $desc_tip_str . '</em></span>
                                </div>
                            </div>';
							break;
						case 'number':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input id="' . $id . '" custom_attributes type="number" name="' . $id . '" placeholder="" class="wcj-plu-inp wcj_setting_number" value="' . $option_value . '" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'select':
							$options = '';
							foreach ( $setting['options'] as $key => $option ) {
								$selected = ( $key === $option_value ) ? 'selected' : '';
								$options .= '<option ' . $selected . ' value="' . $key . '">' . $option . '</option>';
							}
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <select ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . ' class="wcj_setting_select wcj-plu-inp ' . esc_attr( $ele_class ) . '" id="' . $id . '" name="' . $id . '">
                                        ' . $options . '
                                    </select>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>
                            ';
							break;
						case 'multiselect':
							$options = '';
							foreach ( $setting['options'] as $key => $option ) {
								$selected = '';
								if ( is_array( $option_value ) && ! empty( $option_value ) ) {
									if ( in_array( $key, $option_value, false ) ) // phpcs:ignore
									{
										$selected = 'selected';
									}
								}
								$options .= '<option ' . $key . ' ' . $selected . ' value="' . $key . '">' . $option . '</option>';
							}

							$final_html .= '<div class="wcj-tab-plugins-form-inp wcj_setting_multiselect_main">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    
                                        <select ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . ' multiple="multiple" class="wcj_setting_multiselect wcj-plu-inp ' . esc_attr( $ele_class ) . '" id="' . $id . '" name="' . $id . '[]">' . $options . '
                                        </select>
                                        <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                    
                                </div>
                            </div>
                            ';
							break;
						case 'textarea':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <textarea style="' . $css . '" class="wcj_setting_textarea wcj-plu-inp" id="' . $id . '" name="' . $id . '" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>' . wp_unslash( $option_value ) . '</textarea>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'custom_textarea':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <textarea style="' . $css . '" class="wcj_setting_custom_textarea wcj-plu-inp" id="' . $id . '" name="' . $id . '" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>' . wp_unslash( $option_value ) . '</textarea>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'color':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input type="text" id="' . $id . '" name="' . $id . '" class="wcj-setting-color-picker" value="' . $option_value . '" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'custom_number':
							$save_button = apply_filters(
								'booster_option',
								'',
								' <input name="wcj_save_module_settings" class="wcj-btn-sm wcj_custom_number_save_btn" type="submit" value="' . __( 'Save changes', 'woocommerce' ) . '">'
							);
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <div class="wcj_custom_number_row">
                                        <div class="wcj_custom_number_input">
                                            <input id="' . $id . '" custom_attributes type="number" name="' . $id . '" placeholder="" class="wcj-plu-inp wcj_setting_number" value="' . $option_value . '" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>
                                        </div>
                                        <div class="wcj_custom_number_save">
                                            ' . $save_button . '
                                        </div>
                                    </div>
                                    <div class="wcj_custom_number_row">
                                        <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                    </div>
                                </div>
                            </div>';
							break;
						case 'time':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input name="' . $id . '" id="' . $id . '" type="time" value="' . $option_value . '" class="wcj-plu-inp" placeholder="" ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'exchange_rate':
							$setting['type'] = 'number';

							// Custom attribute handling.
							$custom_attributes = array();
							if ( ! empty( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] ) ) {
								foreach ( $setting['custom_attributes'] as $attribute => $attribute_value ) {
									$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
								}
							} else {
								if ( ! w_c_j()->all_modules['currency_exchange_rates']->is_enabled()
								|| 'yes' !== wcj_get_option( 'wcj_currency_exchange_rates_point_decimal_separator', 'no' )
								) {
									$custom_attributes = array( 'step="' . sprintf( '%.12f', 1 / pow( 10, 12 ) ) . '"', 'min="0"' );
								} else {
									$custom_attributes = array( 'step="0.00000001"', 'min="0"' );
								}
							}
							$custom_attributes_button = array();
							if ( ! empty( $setting['custom_attributes_button'] ) && is_array( $setting['custom_attributes_button'] ) ) {
								foreach ( $setting['custom_attributes_button'] as $attribute => $attribute_value ) {
									$custom_attributes_button[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
								}
							}
							$tip                  = '';
							$description          = '';
							$exchange_rate_server = wcj_get_currency_exchange_rate_server_name( $setting['custom_attributes_button']['currency_from'], $setting['custom_attributes_button']['currency_to'] );
							/* translators: %s: translation added */
							$value_title = sprintf( __( 'Grab raw %1$s rate from %2$s.', 'woocommerce-jetpack' ), $setting['value'], $exchange_rate_server ) .
							' ' . __( 'Doesn\'t apply rounding, offset etc.', 'woocommerce-jetpack' );

							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . esc_html( $title ) . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input
                                    name="' . esc_attr( $id ) . '"
                                    id="' . esc_attr( $id ) . '"
                                    type="' . esc_attr( $setting['type'] ) . '"
                                    style="width:79%;' . esc_attr( $css ) . '"
                                    value="' . esc_attr( $option_value ) . '"
                                    class="wcj-plu-inp ' . esc_attr( $ele_class ) . '"
                                    ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '/>
                                    <input
                                    name="' . esc_attr( $id . '_button' ) . '"
                                    id="' . esc_attr( $id . '_button' ) . '"
                                    type="button"
                                    value="' . esc_attr( $setting['value'] ) . '"
                                    title="' . esc_attr( $value_title ) . '"
                                    class="exchage_rate_button"
                                    ' . wp_kses_post( implode( ' ', $custom_attributes_button ) ) . '/>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'custom_link':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh" style="margin-top:10px;">
                                    ' . $custom_link_link . '
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'wcj_save_settings_button':
							$final_html .= '<div class="wcj_tab_end_submit"> 
                                <input type="submit" class="wcj-btn-sm wcj_tab_end_save_btn" name="wcj_save_module_settings" value="' . $title . '"> 
                            </div>';
							break;
						case 'wcj_number_plus_checkbox_start':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                            <div class="wcj-plugins-form-inp-lf">
                                <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                            </div>
                            <div class="wcj-plugins-form-inp-rh">
                                <input
                                name="' . esc_attr( $id ) . '"
                                id="' . esc_attr( $id ) . '"
                                type="number"
                                style="width:80%;' . esc_attr( $css ) . '"
                                value="' . esc_attr( $option_value ) . '"
                                class="wcj-plu-inp ' . esc_attr( $ele_class ) . '"
                                placeholder="' . esc_attr( $placeholder ) . '"
                                ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '/>
                            ';
							break;
						case 'wcj_number_plus_checkbox_end':
							$final_html .= '<input
                                data-rel_id="' . $id . '"
                                type="checkbox"
                                class="wcj_setting_checkbox_key ' . esc_attr( $ele_class ) . '"
                                value="yes"
                                ' . $checked . '
                                ' . wp_kses_post( implode( ' ', $custom_attributes ) ) . '/> 
                                <input type="hidden" id="' . $id . '" name="' . $id . '" value="' . $option_value . '">
                                <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'radio':
							$options = '';
							foreach ( $setting['options'] as $key => $option ) {
								$selected = ( $key === $option_value ) ? 'selected' : '';
								$options .= '<option ' . $selected . ' value="' . $key . '">' . $option . '</option>';
							}
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <div class="wcj_help_tooltip_label"><label for="' . $id . '"> ' . $title . '</label></div>
                                    ' . $desc_tip . '
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <select ' . wcj_get_option( $id ) . ' class="wcj_setting_select wcj-plu-inp" id="' . $id . '" name="' . $id . '">
                                        ' . $options . '
                                    </select>
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>
                            ';
							break;
						case 'information':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <label>' . $title . '</label>
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <p>' . $desc . '</p>
                                </div>
                            </div>
                            ';
							break;
					}
				}
			}

			$final_html .= '</div></div>';

			echo $final_html; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Output_general_settings.
		 *
		 * @version 7.0.0
		 * @param   array $current_section defines the current section.
		 */
		public function output_general_settings( $current_section = '' ) {

			$settings   = $this->get_manager_settings( $current_section );
			$final_html = '';

			if ( ! empty( $settings ) ) {
				foreach ( $settings as $setting ) {
					$type            = isset( $setting['type'] ) ? $setting['type'] : '';
					$id              = isset( $setting['id'] ) ? $setting['id'] : '';
					$title           = isset( $setting['title'] ) ? $setting['title'] : '';
					$html            = isset( $setting['html'] ) ? $setting['html'] : '';
					$icon            = isset( $setting['icon'] ) ? $setting['icon'] : '';
					$desc            = isset( $setting['desc'] ) ? $setting['desc'] : '';
					$default_setting = isset( $setting['default'] ) ? $setting['default'] : '';
					$option_value    = wcj_get_option( $id, $default_setting );
					$checked         = ( 'yes' === $option_value ) ? 'checked' : '';

					switch ( $type ) {
						case 'module_head':
							$icon = esc_url( wcj_plugin_url() ) . '/assets/images/' . $icon;

							$final_html .= '
								<div class="wcj-plugins-sing-acc-box-head">
									<div class="wcj-plugins-sing-head-lf">
										<span class="wcj_admin_span">
											<img src="' . $icon . '">
										</span>
										<div class="wcj-plugins-sing-head-rh">
											<h5>' . $title . '</h5>
											<p>' . $desc . '</p>
										</div>
									</div>
									<div class="wcj-plugins-sing-head-right" style="width:auto;">
										<div class="wcj-btn-main">
											<input type="submit" class="wcj-btn-sm" name="wcj_save_module_settings" value="' . __( 'Save Changes', 'woocommerce-jetpack' ) . '">
										</div>
									</div>
								</div>
								<div class="wcj-plugins-sing-acc-sub-cnt">
									<div class="wcj-tab-main-box">
										<div class="wcj-tab-box" id="tab-1" style="display:block;">
											<div class="wcj-tab-cnt-main">
							';
							break;
						case 'checkbox':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
	    						<div class="wcj-plugins-form-inp-lf">
	    							<label for="' . $id . '">' . $title . '</label>
	    						</div>
	    						<div class="wcj-plugins-form-inp-rh">
	    							<input type="hidden" id="' . $id . '" name="' . $id . '" value="' . $option_value . '">
                                    <input class="wcj_setting_checkbox_key" data-rel_id="' . $id . '" type="checkbox" ' . $checked . '></input>
	    							<span class="wcj_admin_span"><em>' . $desc . '</em></span>
	    						</div>
	    					</div>';
							break;
						case 'text':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <label for="' . $id . '">' . $title . '</label>
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    <input type="text" name="' . $id . '" placeholder="" class="wcj-plu-inp wcj_setting_text" value="' . $option_value . '">
                                    <span class="wcj_admin_span"><em>' . $desc . '</em></span>
                                </div>
                            </div>';
							break;
						case 'button':
							$final_html .= '<div class="wcj-tab-plugins-form-inp">
                                <div class="wcj-plugins-form-inp-lf">
                                    <label>' . $title . '</label>
                                </div>
                                <div class="wcj-plugins-form-inp-rh">
                                    ' . $html . '
                                </div>
                            </div>';
							break;
					}
				}
			}

			$final_html .= '</div></div></div></div>';

			echo $final_html; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Version_details
		 *
		 * @version 7.0.0
		 */
		public function version_details() {

			$file = wcj_plugin_url() . '/version-details.json';

			$response = wp_remote_get(
				$file,
				array(
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);
			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$response_body = json_decode( $response['body'] );
				foreach ( $response_body as $key => $lines ) {
					echo wp_kses_post( $lines . '<br>' );
				}
			}
		}
	}

endif;

return new WCJ_Admin();
