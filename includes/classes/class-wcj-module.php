<?php
/**
 * Booster for WooCommerce Module
 *
 * @version 7.2.5
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @todo    [dev] maybe should be `abstract` ?
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Module' ) ) :
	/**
	 * WCJ_Module contains all the functions for single module setting page.
	 *
	 * @version 7.1.6
	 * @since   5.1.0
	 */
	class WCJ_Module {
		/**
		 * The ID of the module.
		 *
		 * @var int $id ID
		 */
		public $id;
		/**
		 * The Short description of the module.
		 *
		 * @var varchar $short_desc Short Descrption
		 */

		public $short_desc;
		/**
		 * The description of the module.
		 *
		 * @var varchar $desc Descrption
		 */
		public $desc;
		/**
		 * The Extra description of the module.
		 *
		 * @var varchar $desc_pro Extra Descrption
		 */
		public $desc_pro;
		/**
		 * The Extra description of the module.
		 *
		 * @var varchar $extra_desc Extra Descrption
		 */

		public $extra_desc;
		/**
		 * The Extra description of the module.
		 *
		 * @var varchar $extra_desc_pro Extra Descrption
		 */
		public $extra_desc_pro;
		/**
		 * The parent of the module.
		 *
		 * @var int $parent_id Parent Module ID for sub module.
		 */
		public $parent_id;

		/**
		 * The module type
		 *
		 * @var varchar $type Module or sub module.
		 */
		public $type;

		/**
		 * The module link
		 *
		 * @var varchar $link Module link.
		 */
		public $link;

		/**
		 * The module link_slug
		 *
		 * @var varchar $link_slug Module link_slug.
		 */
		public $link_slug;

		/**
		 * The module options
		 *
		 * @var array
		 */
		public $options = array();

		/**
		 * The module tools_array
		 *
		 * @var array
		 */
		public $tools_array = array();

		/**
		 * The module title
		 *
		 * @var varchar $title Module title.
		 */
		public $title;

		/**
		 * The module time_now
		 *
		 * @var varchar $time_now Module time_now.
		 */
		public $time_now;

		/**
		 * The module use_shipping_instances
		 *
		 * @var varchar $use_shipping_instances Module use_shipping_instances.
		 */
		public $use_shipping_instances;

		/**
		 * The module condition_options
		 *
		 * @var array
		 */
		public $condition_options = array();

		/**
		 * The module meta_box_screen
		 *
		 * @var varchar $meta_box_screen Module meta_box_screen.
		 */
		public $meta_box_screen;

		/**
		 * The module meta_box_context
		 *
		 * @var varchar $meta_box_context Module meta_box_context.
		 */
		public $meta_box_context;

		/**
		 * The module meta_box_priority
		 *
		 * @var varchar $meta_box_priority Module meta_box_priority.
		 */
		public $meta_box_priority;

		/**
		 * The module price_hooks_priority
		 *
		 * @var varchar $price_hooks_priority Module price_hooks_priority.
		 */
		public $price_hooks_priority;

		/**
		 * The module calculated_products_prices
		 *
		 * @var varchar $calculated_products_prices Module calculated_products_prices.
		 */
		public $calculated_products_prices;

		/**
		 * The module active_currencies
		 *
		 * @var varchar $active_currencies Module active_currencies.
		 */
		public $active_currencies;

		/**
		 * Constructor.
		 *
		 * @version 6.0.6
		 * @param varchar $type Module is main module or sub-module.
		 */
		public function __construct( $type = 'module' ) {
			add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
			add_filter( 'wcj_settings_' . $this->id, array( $this, 'get_settings' ), 100 );
			$this->type = $type;
			if ( 'module' === $this->type ) {
				$this->parent_id = '';
			}

			if ( 'no' === wcj_get_option( 'wcj_load_modules_on_init', 'no' ) ) {
				add_action( 'init', array( $this, 'add_settings' ) );
				add_action( 'init', array( $this, 'reset_settings' ), PHP_INT_MAX );
			} else {
				if ( 'init' === current_filter() || 'plugins_loaded' === current_filter() ) {
					$this->add_settings();
					$this->reset_settings();
				}
			}

			// Handle WPML hooks.
			if ( $this->is_enabled() ) {
				add_action( 'wcj_before_get_terms', array( $this, 'remove_wpml_functions_before_get_terms' ) );
				add_action( 'wcj_after_get_terms', array( $this, 'restore_wpml_functions_after_get_terms' ) );
				add_action( 'wcj_before_get_products', array( $this, 'add_wpml_args_on_get_products' ) );
				add_action( 'wcj_after_get_products', array( $this, 'restore_wpml_args_on_get_products' ) );
				add_action( 'admin_init', array( $this, 'remove_wpml_hooks' ) );
			}

			// Handle Price Functions.
			add_filter( 'wc_price', array( $this, 'handle_price' ), 10, 4 );
		}

		/**
		 * Handle_price.
		 *
		 * @version 5.1.0
		 * @since   5.1.0
		 *
		 * @param string $return Price HTML markup.
		 * @param string $price Unformatted price.
		 * @param array  $args Pass on the args.
		 * @param float  $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
		 *
		 * @return mixed
		 */
		public function handle_price( $return, $price, $args, $unformatted_price ) {
			if ( isset( $args['add_html_on_price'] ) && ! filter_var( $args['add_html_on_price'], FILTER_VALIDATE_BOOLEAN ) ) {
				$return = $price;
			}
			return $return;
		}

		/**
		 * Remove_wpml_hooks.
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 */
		public function remove_wpml_hooks() {
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_wpml_get_products_all_lang', 'no' ) ) {
				return;
			}

			// Remove a WPML filter that filters products by language.
			wcj_remove_class_filter( 'woocommerce_json_search_found_products', 'WCML_Products', 'filter_wc_searched_products_on_admin' );
		}

		/**
		 * Restore_wpml_args_on_get_products.
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 *
		 * @param null $module_id Current Module ID.
		 */
		public function restore_wpml_args_on_get_products( $module_id = null ) {
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_wpml_get_products_all_lang', 'no' ) ) {
				return;
			}
			remove_action( 'pre_get_posts', array( $this, 'suppress_filters' ) );
		}

		/**
		 * Add_wpml_args_on_get_products.
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 *
		 * It's necessary to take 2 steps:
		 * 1. Add `do_action('wcj_before_get_products', $this->id )` before get_product, wcj_get_products, or wp_query.
		 * 2. Add a setting using `$this->get_wpml_products_in_all_languages_setting()`
		 *
		 * @param null $module_id Module ID for translation.
		 */
		public function add_wpml_args_on_get_products( $module_id = null ) {
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_wpml_get_products_all_lang', 'no' ) ) {
				return;
			}
			add_action( 'pre_get_posts', array( $this, 'suppress_filters' ) );
		}

		/**
		 * Suppress_filters.
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 *
		 * @param varchar $query Query object to process.
		 */
		public function suppress_filters( $query ) {
			$query->query_vars['suppress_filters'] = true;
		}

		/**
		 * Get_wpml_terms_in_all_languages_setting.
		 *
		 * @see WCJ_Module::remove_wpml_functions_before_get_terms().
		 * @see WCJ_Module::restore_wpml_args_on_get_products().
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 *
		 * @return array
		 */
		public function get_wpml_terms_in_all_languages_setting() {
			return array(
				'title'    => __( 'WPML: Get Terms in All Languages', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Get tags and taxonomies in all languages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_wpml_get_terms_all_lang',
				'default'  => 'no',
				'type'     => 'checkbox',
			);
		}

		/**
		 * Get_wpml_products_in_all_languages_setting.
		 *
		 * @see WCJ_Module::add_wpml_args_on_get_products().
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 *
		 * @return array
		 */
		public function get_wpml_products_in_all_languages_setting() {
			return array(
				'title'    => __( 'WPML: Get Products in All Languages', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Get products in all languages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_wpml_get_products_all_lang',
				'default'  => 'no',
				'type'     => 'checkbox',
			);
		}

		/**
		 * Remove_wpml_functions_before_get_terms.
		 *
		 * It's necessary to take 2 steps:
		 * 1. Add `do_action('wcj_before_get_terms', $this->id )` before get_terms.
		 * 2. Add a setting using `$this->get_wpml_terms_in_all_languages_setting()`
		 *
		 * @see "settings/wcj-settings-global-discount.php"
		 *
		 * @version 4.7.0
		 * @since   4.6.0
		 * @param string $module_id Module ID.
		 */
		public function remove_wpml_functions_before_get_terms( $module_id = null ) {
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_wpml_get_terms_all_lang', 'no' ) ) {
				return;
			}
			wcj_remove_wpml_terms_filters();
		}

		/**
		 * Restore_wpml_functions_after_get_terms
		 *
		 * It's necessary to take 2 steps:
		 * 1. Add `do_action('wcj_after_get_terms', $this->id )` after get_terms.
		 * 2. Add a setting using `$this->get_wpml_terms_in_all_languages_setting()`
		 *
		 * @see "settings/wcj-settings-global-discount.php"
		 *
		 * @version 4.7.0
		 * @since   4.6.0
		 * @param string $module_id Module ID.
		 */
		public function restore_wpml_functions_after_get_terms( $module_id = null ) {
			if ( 'no' === wcj_get_option( 'wcj_' . $this->id . '_wpml_get_terms_all_lang', 'no' ) ) {
				return;
			}
			wcj_add_wpml_terms_filters();
		}

		/**
		 * Get_deprecated_options.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 */
		public function get_deprecated_options() {
			return false;
		}

		/**
		 * Handle_deprecated_options.
		 *
		 * @version 5.6.1
		 * @since   3.8.0
		 */
		public function handle_deprecated_options() {
			$deprecated_options = $this->get_deprecated_options();
			if ( $deprecated_options ) {
				foreach ( $deprecated_options as $new_option => $old_options ) {
					$new_value = wcj_get_option( $new_option, array() );
					foreach ( $old_options as $new_key => $old_option ) {
						$old_value = wcj_get_option( $old_option, null );
						if ( null !== ( $old_value ) ) {
							$new_value[ $new_key ] = $old_value;
							delete_option( $old_option );
						}
					}
					update_option( $new_option, $new_value );
				}
			}
		}

		/**
		 * Save_meta_box_validate_value.
		 *
		 * @version 6.0.0
		 * @since   2.9.1
		 * @param string $option_value Get option value.
		 * @param string $option_name Get option name.
		 * @param int    $module_id Get option id.
		 */
		public function save_meta_box_validate_value( $option_value, $option_name, $module_id ) {
			if ( true === apply_filters( 'booster_option', false, true ) ) {
				return $option_value;
			}
			if ( 'no' === $option_value ) {
				return $option_value;
			}
			if ( $this->id === $module_id && $this->meta_box_validate_value === $option_name ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_key'       => '_' . $this->meta_box_validate_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post__not_in'   => array( get_the_ID() ),
				);
				$loop = new WP_Query( $args );
				$c    = $loop->found_posts + 1;
				if ( $c >= 2 ) {
					add_filter( 'redirect_post_location', array( $this, 'validate_value_add_notice_query_var' ), 99 );
					return 'no';
				}
			}
			return $option_value;
		}

		/**
		 * Validate_value_add_notice_query_var.
		 *
		 * @version 6.0.0
		 * @since   2.9.1
		 * @param String $location Get location.
		 */
		public function validate_value_add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'validate_value_add_notice_query_var' ), 99 );
			return esc_url_raw(
				add_query_arg(
					array(
						'wcj_' . $this->id . '_meta_box_admin_notice' => true,
						'wcj_' . $this->id . '_meta_box_admin_notice_nonce' => wp_create_nonce( 'wcj_' . $this->id . '_meta_box_admin_notice' ),
					),
					$location
				)
			);
		}

		/**
		 * Validate_value_admin_notices.
		 *
		 * @version 6.0.0
		 * @since   2.9.1
		 */
		public function validate_value_admin_notices() {
			$wpnonce = isset( $_REQUEST[ 'wcj_' . $this->id . '_meta_box_admin_notice_nonce' ] ) ? wp_verify_nonce( sanitize_key( $_REQUEST[ 'wcj_' . $this->id . '_meta_box_admin_notice_nonce' ] ), 'wcj_' . $this->id . '_meta_box_admin_notice' ) : false;
			if ( ! $wpnonce || ! isset( $_GET[ 'wcj_' . $this->id . '_meta_box_admin_notice' ] ) ) {
				return;
			}
			echo '<div class="error"><p><div class="message">' .
			sprintf(
				/* translators: %1$s,%2$s: translators Added */
				wp_kses_post( __( 'Booster: Free plugin\'s version is limited to only one "%1$s" product with settings on per product basis enabled at a time. You will need to get <a href="%2$s" target="_blank">Booster Plus</a> to add unlimited number of "%1$s" products.', 'woocommerce-jetpack' ) ),
				wp_kses_post( $this->short_desc ),
				'https://booster.io/buy-booster/'
			) .
			'</div></p></div>';
		}

		/**
		 * Get_meta_box_options.
		 *
		 * @version 5.6.1
		 * @since   2.8.0
		 */
		public function get_meta_box_options() {
			$filename = wcj_free_plugin_path() . '/includes/settings/meta-box/wcj-settings-meta-box-' . str_replace( '_', '-', $this->id ) . '.php';
			return ( file_exists( $filename ) ? require $filename : array() );
		}

		/**
		 * Maybe_fix_settings.
		 *
		 * @version 4.3.0
		 * @since   3.2.1
		 * @param Array $settings get settings.
		 */
		public function maybe_fix_settings( $settings ) {
			if ( ! WCJ_IS_WC_VERSION_BELOW_3_2_0 ) {
				foreach ( $settings as &$setting ) {
					if ( isset( $setting['type'] ) && 'select' === $setting['type'] ) {
						if (
						! isset( $setting['ignore_enhanced_select_class'] ) ||
						( isset( $setting['ignore_enhanced_select_class'] ) && false === $setting['ignore_enhanced_select_class'] )
						) {
							if ( ! isset( $setting['class'] ) || '' === $setting['class'] ) {
								$setting['class'] = 'wc-enhanced-select';
							} else {
								$setting['class'] .= '  wc-enhanced-select';
							}
						}
					}
					if ( isset( $setting['type'] ) && 'text' === $setting['type'] && isset( $setting['class'] ) && 'widefat' === $setting['class'] ) {
						if ( ! isset( $setting['css'] ) || '' === $setting['css'] ) {
							$setting['css'] = 'width:100%;';
						} else {
							$setting['css'] .= '  width:100%;';
						}
					}
				}
			}
			return $settings;
		}

		/**
		 * Add_settings_from_file.
		 *
		 * @version 5.6.1
		 * @since   2.8.0
		 * @param Array $settings get settings.
		 */
		public function add_settings_from_file( $settings ) {
			$filename = wcj_free_plugin_path() . '/includes/settings/wcj-settings-' . str_replace( '_', '-', $this->id ) . '.php';
			$settings = ( file_exists( $filename ) ? require $filename : $settings );
			return $this->maybe_fix_settings( $settings );
		}

		/**
		 * Add_settings.
		 *
		 * @version 2.8.0
		 * @since   2.8.0
		 */
		public function add_settings() {
			add_filter( 'wcj_' . $this->id . '_settings', array( $this, 'add_settings_from_file' ) );
		}

		/**
		 * Save_meta_box_value.
		 *
		 * @version 6.0.0
		 * @since   2.5.3
		 * @param string $option_value Get option value.
		 * @param string $option_name Get option name.
		 * @param int    $module_id Get module ID.
		 */
		public function save_meta_box_value( $option_value, $option_name, $module_id ) {
			if ( true === apply_filters( 'booster_option', false, true ) ) {
				return $option_value;
			}
			if ( 'no' === $option_value ) {
				return $option_value;
			}
			if ( $this->id === $module_id && $this->co === $option_name ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 3,
					'meta_key'       => '_' . $this->co, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post__not_in'   => array( get_the_ID() ),
				);
				$loop = new WP_Query( $args );
				$c    = $loop->found_posts + 1;
				if ( $c >= 4 ) {
					add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
					return 'no';
				}
			}
			return $option_value;
		}

		/**
		 * Add_notice_query_var.
		 *
		 * @version 5.6.3
		 * @since   2.5.3
		 *  @param String $location Get location.
		 */
		public function add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
			return esc_url_raw( add_query_arg( array( 'wcj_' . $this->id . '_admin_notice' => true ), $location ) );
		}

		/**
		 * Admin_notices.
		 *
		 * @version 6.0.0
		 * @since   2.5.3
		 */
		public function admin_notices() {
			$_get = array();
			parse_str( isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '', $_get );
			if ( ! isset( $_get[ 'wcj_' . $this->id . '_admin_notice' ] ) ) {
				return;
			}
			echo '<div class="error"><p><div class="message">' . wp_kses_post( $this->get_the_notice() ) . '</div></p></div>';
		}

		/**
		 * Reset_settings.
		 *
		 * @version 5.6.7
		 * @since   2.4.0
		 * @todo    (maybe) always `delete_option()` (instead of `update_option()`)
		 */
		public function reset_settings() {
			$wpnonce = isset( $_REQUEST[ 'wcj_reset_settings-' . $this->id . '-nonce' ] ) ? wp_verify_nonce( sanitize_key( $_REQUEST[ 'wcj_reset_settings-' . $this->id . '-nonce' ] ), 'wcj_reset_settings' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_reset_settings'] ) && $this->id === $_GET['wcj_reset_settings'] && wcj_is_user_role( 'administrator' ) && ! isset( $_POST['save'] ) ) {
				foreach ( $this->get_settings() as $settings ) {
					if ( false !== strpos( $settings['id'], '[' ) ) {
						$id = explode( '[', $settings['id'] );
						$id = $id[0];
						delete_option( $id );
					} else {
						$default_value = isset( $settings['default'] ) ? $settings['default'] : '';
						update_option( $settings['id'], $default_value );
					}
				}
				wp_safe_redirect( remove_query_arg( array( 'wcj_reset_settings', 'wcj_reset_settings-' . $this->id . '-nonce' ) ) );
				exit();
			}
		}

		/**
		 * Add_standard_settings.
		 *
		 * @version 2.4.0
		 * @since   2.3.10
		 * @param Array  $settings Get settings.
		 * @param string $module_desc get module desc.
		 */
		public function add_standard_settings( $settings = array(), $module_desc = '' ) {
			$settings = $this->setup_default_autoload( $settings );
			return $this->add_enable_module_setting( $settings, $module_desc );
		}

		/**
		 * Setup_default_autoload.
		 *
		 * @version 5.3.3
		 * @since   5.3.3
		 *
		 * @param Array $settings Get settings.
		 *
		 * @return array
		 */
		public function setup_default_autoload( $settings ) {
			$settings = array_map(
				function ( $item ) {
					if ( ! isset( $item['autoload'] ) ) {
						$item['autoload'] = false;
					}
					return $item;
				},
				$settings
			);
			return $settings;
		}

		/**
		 * Get_settings.
		 *
		 * @version 5.3.6
		 * @since   2.2.6
		 */
		public function get_settings() {
			$settings = apply_filters( 'wcj_' . $this->id . '_settings', array() );
			$settings = $this->add_standard_settings( $settings );
			$settings = $this->handle_hide_on_free_parameter( $settings );
			return $settings;
		}

		/**
		 * Handle_hide_on_free_parameter.
		 *
		 * @version 5.6.1
		 * @since   5.3.6
		 *
		 * @param Array $settings Get settings.
		 *
		 * @return array
		 */
		public function handle_hide_on_free_parameter( $settings ) {
			if ( 'woocommerce-jetpack.php' !== basename( WCJ_FREE_PLUGIN_FILE ) ) {
				return $settings;
			}
			$settings = wp_list_filter( $settings, array( 'hide_on_free' => true ), 'NOT' );
			return $settings;
		}

		/**
		 * Save_meta_box.
		 *
		 * @version 6.0.3
		 * @since   2.5.0
		 * @todo    (maybe) also order_id in `$the_post_id = ...`
		 * @param int   $post_id Get post id.
		 * @param Array $__post Get post.
		 */
		public function save_meta_box( $post_id, $__post ) {
			// Check that we are saving with current metabox displayed.
			if ( ! isset( $_POST[ 'woojetpack_' . $this->id . '_save_post' ] ) ) {
				return;
			}
			// Setup post (just in case...).
			global $post;
			$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			// Save options.
			foreach ( $this->get_meta_box_options() as $option ) {
				if ( isset( $option['type'] ) && 'title' === $option['type'] ) {
					continue;
				}
				$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
				if ( $is_enabled ) {
					$option_value = '';
					$wpnonce      = wp_verify_nonce( wp_unslash( isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '' ), 'woocommerce_save_data' );
					$option_name  = isset( $option['name'] ) ? $option['name'] : '';
					if ( $wpnonce && isset( $_POST[ $option_name ] ) ) {
						$option_value = is_array( $_POST[ $option_name ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $option_name ] ) ) : wp_kses_post( wp_unslash( $_POST[ $option_name ] ) );
					} elseif ( isset( $option['default'] ) ) {
						$option_value = $option['default'];
					}
					$the_post_id   = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $post_id;
					$the_meta_name = ( isset( $option['meta_name'] ) ) ? $option['meta_name'] : '_' . $option_name;
					if ( isset( $option['convert'] ) && 'from_date_to_timestamp' === $option['convert'] ) {
						$option_value = strtotime( $option_value );
						if ( empty( $option_value ) ) {
							continue;
						}
					}
					delete_post_meta( $the_post_id, $the_meta_name ); // solves lowercase/uppercase issue.
					update_post_meta( $the_post_id, $the_meta_name, apply_filters( 'wcj_save_meta_box_value', $option_value, $option_name, $this->id ) );
				}
			}
			// Reset post.
			wp_reset_postdata();
		}

		/**
		 * Save_meta_box_hpos.
		 *
		 * @version 7.1.4
		 * @since  1.0.0
		 * @todo    (maybe) also order_id in `$the_post_id = ...`
		 * @param int   $order_id Get order id.
		 * @param Array $order Get order.
		 */
		public function save_meta_box_hpos( $order_id, $order ) {
			// Check that we are saving with current metabox displayed.
			if ( ! isset( $_POST[ 'woojetpack_' . $this->id . '_save_post' ] ) ) {
				return;
			}
			// Save options.
			foreach ( $this->get_meta_box_options() as $option ) {
				if ( isset( $option['type'] ) && 'title' === $option['type'] ) {
					continue;
				}
				$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
				if ( $is_enabled ) {
					$option_value = '';
					$wpnonce      = wp_verify_nonce( wp_unslash( isset( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '' ), 'woocommerce_save_data' );
					$option_name  = isset( $option['name'] ) ? $option['name'] : '';
					if ( $wpnonce && isset( $_POST[ $option_name ] ) ) {
						$option_value = is_array( $_POST[ $option_name ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $option_name ] ) ) : wp_kses_post( wp_unslash( $_POST[ $option_name ] ) );
					} elseif ( isset( $option['default'] ) ) {
						$option_value = $option['default'];
					}
					$the_post_id   = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $order_id;
					$the_meta_name = ( isset( $option['meta_name'] ) ) ? $option['meta_name'] : '_' . $option_name;
					if ( isset( $option['convert'] ) && 'from_date_to_timestamp' === $option['convert'] ) {
						$option_value = strtotime( $option_value );
						if ( empty( $option_value ) ) {
							continue;
						}
					}

					$order->update_meta_data( $the_meta_name, apply_filters( 'wcj_save_meta_box_value', $option_value, $option_name, $this->id ) );
				}
			}
			$order->save();
		}

		/**
		 * Add_meta_box.
		 *
		 * @version 7.1.4
		 * @since   2.2.6
		 */
		public function add_meta_box() {
			if ( true === wcj_is_hpos_enabled() && 'woocommerce_page_wc-orders' === get_current_screen()->id ) {
				$screen   = ( isset( $this->meta_box_screen ) ) ? $this->meta_box_screen : 'product';
				$context  = ( isset( $this->meta_box_context ) ) ? $this->meta_box_context : 'normal';
				$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
					array( $this, 'create_meta_box_hpos' ),
					$screen,
					$context,
					$priority
				);
			} else {
				$screen   = ( isset( $this->meta_box_screen ) ) ? $this->meta_box_screen : 'product';
				$context  = ( isset( $this->meta_box_context ) ) ? $this->meta_box_context : 'normal';
				$priority = ( isset( $this->meta_box_priority ) ) ? $this->meta_box_priority : 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . $this->short_desc,
					array( $this, 'create_meta_box' ),
					$screen,
					$context,
					$priority
				);
			}

		}

		/**
		 * Create_meta_box.
		 *
		 * @version 4.6.1
		 * @todo    `placeholder` for textarea
		 * @todo    `class` for all types (now only for select)
		 * @todo    `show_value` for all types (now only for multiple select)
		 * @todo    `$the_post_id` maybe also `order_id` (i.e. not only `product_id`)?
		 */
		public function create_meta_box() {
			$current_post_id = get_the_ID();
			$html            = '';
			$html           .= '<table class="widefat striped">';
			foreach ( $this->get_meta_box_options() as $option ) {
				$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
				if ( is_array( $option ) && $is_enabled ) {
					if ( 'title' === $option['type'] ) {
						$html .= '<tr>';
						$html .= '<th colspan="3" style="' . ( isset( $option['css'] ) ? $option['css'] : 'text-align:left;font-weight:bold;' ) . '">' . $option['title'] . '</th>';
						$html .= '</tr>';
					} else {
						$custom_attributes = '';
						$the_post_id       = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $current_post_id;
						$the_meta_name     = ( isset( $option['meta_name'] ) ) ? $option['meta_name'] : '_' . $option['name'];
						if ( get_post_meta( $the_post_id, $the_meta_name ) ) {
							$option_value = get_post_meta( $the_post_id, $the_meta_name, true );
						} else {
							$option_value = ( isset( $option['default'] ) ) ? $option['default'] : '';
						}
						$css          = ( isset( $option['css'] ) ? $option['css'] : '' );
						$class        = ( isset( $option['class'] ) ? $option['class'] : '' );
						$show_value   = ( isset( $option['show_value'] ) && $option['show_value'] );
						$input_ending = '';
						if ( 'select' === $option['type'] ) {
							if ( isset( $option['multiple'] ) ) {
								$custom_attributes = ' multiple';
								$option_name       = $option['name'] . '[]';
							} else {
								$option_name = $option['name'];
							}
							if ( isset( $option['custom_attributes'] ) ) {
								$custom_attributes .= ' ' . $option['custom_attributes'];
							}
							$options = '';
							foreach ( $option['options'] as $select_option_key => $select_option_value ) {
								$selected = '';
								if ( is_array( $option_value ) ) {
									foreach ( $option_value as $single_option_value ) {
										$selected = selected( $single_option_value, $select_option_key, false );
										if ( '' !== $selected ) {
											break;
										}
									}
								} else {
									$selected = selected( $option_value, $select_option_key, false );
								}
								$options .= '<option value="' . $select_option_key . '" ' . $selected . '>' . $select_option_value . '</option>';
							}
						} elseif ( 'textarea' === $option['type'] ) {
							if ( '' === $css ) {
								$css = 'min-width:300px;';
							}
						} else {
							$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
							if ( isset( $option['custom_attributes'] ) ) {
								$input_ending = ' ' . $option['custom_attributes'] . $input_ending;
							}
							if ( isset( $option['placeholder'] ) ) {
								$input_ending = ' placeholder="' . $option['placeholder'] . '"' . $input_ending;
							}
						}
						switch ( $option['type'] ) {
							case 'price':
								$field_html = '<input style="' . $css . '" class="short wc_input_price" type="number" step="' .
								apply_filters( 'wcj_get_meta_box_options_type_price_step', '0.0001' ) . '"' . $input_ending;
								break;
							case 'date':
								$field_html = '<input style="' . $css . '" class="input-text" display="date" type="text"' . $input_ending;
								break;
							case 'textarea':
								$field_html = '<textarea style="' . $css . '" id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
								break;
							case 'select':
								$field_html = '<select' . $custom_attributes . ' class="' . $class . '" style="' . $css . '" id="' . $option['name'] . '" name="' .
								$option_name . '">' . $options . '</select>' .
								/* translators: %s: search term */
								( $show_value && ! empty( $option_value ) ? sprintf( '<em>' . __( 'Selected: %s.', 'woocommerce-jetpack' ), implode( ', ', $option_value ) ) . '</em>' : '' );
								break;
							default:
								$field_html = '<input style="' . $css . '" class="short" type="' . $option['type'] . '"' . $input_ending;
								break;
						}
						$html         .= '<tr>';
						$maybe_tooltip = ( isset( $option['tooltip'] ) && '' !== $option['tooltip'] ) ? '<span style="float:right;">' . wc_help_tip( $option['tooltip'], true ) . '</span>' : '';
						$html         .= '<th style="text-align:left;width:25%;font-weight:bold;">' . $option['title'] . $maybe_tooltip . '</th>';
						if ( isset( $option['desc'] ) && '' !== $option['desc'] ) {
							$html .= '<td style="font-style:italic;width:25%;">' . $option['desc'] . '</td>';
						}
						$html .= '<td>' . $field_html . '</td>';
						$html .= '</tr>';
					}
				}
			}
			$html .= '</table>';
			$html .= '<input type="hidden" name="woojetpack_' . $this->id . '_save_post" value="woojetpack_' . $this->id . '_save_post">';
			echo wp_kses_post( $html );
		}

		/**
		 * Create_meta_box_hpos.
		 *
		 * @version 7.1.4
		 * @todo    `placeholder` for textarea
		 * @todo    `class` for all types (now only for select)
		 * @todo    `show_value` for all types (now only for multiple select)
		 * @todo    `$the_post_id` maybe also `order_id` (i.e. not only `product_id`)?
		 */
		public function create_meta_box_hpos() {
			$current_post_id = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
			$order           = wc_get_order( $current_post_id );

			$html  = '';
			$html .= '<table class="widefat striped">';
			foreach ( $this->get_meta_box_options() as $option ) {
				$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
				if ( is_array( $option ) && $is_enabled ) {
					if ( 'title' === $option['type'] ) {
						$html .= '<tr>';
						$html .= '<th colspan="3" style="' . ( isset( $option['css'] ) ? $option['css'] : 'text-align:left;font-weight:bold;' ) . '">' . $option['title'] . '</th>';
						$html .= '</tr>';
					} else {
						$custom_attributes = '';
						$the_post_id       = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $current_post_id;
						$the_meta_name     = ( isset( $option['meta_name'] ) ) ? $option['meta_name'] : '_' . $option['name'];
						if ( $order->get_meta( $the_meta_name ) ) {
							$option_value = $order->get_meta( $the_meta_name );
						} else {
							$option_value = ( isset( $option['default'] ) ) ? $option['default'] : '';
						}
						$css          = ( isset( $option['css'] ) ? $option['css'] : '' );
						$class        = ( isset( $option['class'] ) ? $option['class'] : '' );
						$show_value   = ( isset( $option['show_value'] ) && $option['show_value'] );
						$input_ending = '';
						if ( 'select' === $option['type'] ) {
							if ( isset( $option['multiple'] ) ) {
								$custom_attributes = ' multiple';
								$option_name       = $option['name'] . '[]';
							} else {
								$option_name = $option['name'];
							}
							if ( isset( $option['custom_attributes'] ) ) {
								$custom_attributes .= ' ' . $option['custom_attributes'];
							}
							$options = '';
							foreach ( $option['options'] as $select_option_key => $select_option_value ) {
								$selected = '';
								if ( is_array( $option_value ) ) {
									foreach ( $option_value as $single_option_value ) {
										$selected = selected( $single_option_value, $select_option_key, false );
										if ( '' !== $selected ) {
											break;
										}
									}
								} else {
									$selected = selected( $option_value, $select_option_key, false );
								}
								$options .= '<option value="' . $select_option_key . '" ' . $selected . '>' . $select_option_value . '</option>';
							}
						} elseif ( 'textarea' === $option['type'] ) {
							if ( '' === $css ) {
								$css = 'min-width:300px;';
							}
						} else {
							$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
							if ( isset( $option['custom_attributes'] ) ) {
								$input_ending = ' ' . $option['custom_attributes'] . $input_ending;
							}
							if ( isset( $option['placeholder'] ) ) {
								$input_ending = ' placeholder="' . $option['placeholder'] . '"' . $input_ending;
							}
						}
						switch ( $option['type'] ) {
							case 'price':
								$field_html = '<input style="' . $css . '" class="short wc_input_price" type="number" step="' .
								apply_filters( 'wcj_get_meta_box_options_type_price_step', '0.0001' ) . '"' . $input_ending;
								break;
							case 'date':
								$field_html = '<input style="' . $css . '" class="input-text" display="date" type="text"' . $input_ending;
								break;
							case 'textarea':
								$field_html = '<textarea style="' . $css . '" id="' . $option['name'] . '" name="' . $option['name'] . '">' . $option_value . '</textarea>';
								break;
							case 'select':
								$field_html = '<select' . $custom_attributes . ' class="' . $class . '" style="' . $css . '" id="' . $option['name'] . '" name="' .
								$option_name . '">' . $options . '</select>' .
								/* translators: %s: search term */
								( $show_value && ! empty( $option_value ) ? sprintf( '<em>' . __( 'Selected: %s.', 'woocommerce-jetpack' ), implode( ', ', $option_value ) ) . '</em>' : '' );
								break;
							default:
								$field_html = '<input style="' . $css . '" class="short" type="' . $option['type'] . '"' . $input_ending;
								break;
						}
						$html         .= '<tr>';
						$maybe_tooltip = ( isset( $option['tooltip'] ) && '' !== $option['tooltip'] ) ? '<span style="float:right;">' . wc_help_tip( $option['tooltip'], true ) . '</span>' : '';
						$html         .= '<th style="text-align:left;width:25%;font-weight:bold;">' . $option['title'] . $maybe_tooltip . '</th>';
						if ( isset( $option['desc'] ) && '' !== $option['desc'] ) {
							$html .= '<td style="font-style:italic;width:25%;">' . $option['desc'] . '</td>';
						}
						$html .= '<td>' . $field_html . '</td>';
						$html .= '</tr>';
					}
				}
			}
			$html .= '</table>';
			$html .= '<input type="hidden" name="woojetpack_' . $this->id . '_save_post" value="woojetpack_' . $this->id . '_save_post">';
			echo wp_kses_post( $html );
		}

		/**
		 * Is_enabled.
		 *
		 * @version 3.3.0
		 */
		public function is_enabled() {
			return wcj_is_module_enabled( ( 'module' === $this->type ? $this->id : $this->parent_id ) );
		}

		/**
		 * Settings_section.
		 *
		 * @version 2.3.0
		 * @param Array $sections Get sections.
		 */
		public function settings_section( $sections ) {
			$sections[ $this->id ] = isset( $this->section_title ) ? $this->section_title : $this->short_desc;
			return $sections;
		}

		/**
		 * Get_cat_by_section.
		 *
		 * @version 5.6.2
		 * @since   2.2.3
		 * @param Array $section Get sections.
		 */
		public function get_cat_by_section( $section ) {
			$cats = include wcj_free_plugin_path() . '/includes/admin/wcj-modules-cats.php';
			foreach ( $cats as $id => $label_info ) {
				if ( ( ! empty( $label_info['all_cat_ids'] ) ) &&
				( is_array( $label_info['all_cat_ids'] ) ) &&
				( in_array( $section, $label_info['all_cat_ids'], true ) )
				) {
					return $id;
				}
			}
			return '';
		}

		/**
		 * Get_back_to_settings_link_html.
		 *
		 * @version 5.6.8
		 * @since   2.2.3
		 */
		public function get_back_to_settings_link_html() {
			$cat_id   = $this->get_cat_by_section( $this->id );
			$the_link = admin_url( wcj_admin_tab_url() . '&wcj-cat=' . $cat_id . '&section=' . $this->id );
			return '<a href="' . $the_link . '"><< ' . __( 'Back to Module Settings', 'woocommerce-jetpack' ) . '</a>';
		}

		/**
		 * Add_tools_list.
		 *
		 * @version 2.3.8
		 * @since   2.2.3
		 * @param Array $settings Get settings.
		 */
		public function add_tools_list( $settings ) {
			return array_merge(
				$settings,
				array(
					array(
						'title' => __( 'Tools', 'woocommerce-jetpack' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wcj_' . $this->id . '_tools_options',
					),
					array(
						'title' => __( 'Module Tools', 'woocommerce-jetpack' ),
						'id'    => 'wcj_' . $this->id . '_module_tools',
						'type'  => 'module_tools',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wcj_' . $this->id . '_tools_options',
					),
				)
			);
		}

		/**
		 * Get_tool_header_html.
		 *
		 * @version 5.5.6
		 * @since   2.3.10
		 * @param int $tool_id Get tool id.
		 */
		public function get_tool_header_html( $tool_id ) {
			$html = '';
			if ( isset( $this->tools_array[ $tool_id ] ) ) {
				$html .= '<p class="wcj-backlink">' . $this->get_back_to_settings_link_html() . '</p>';
				$html .= '<h3 class="wcj-tools-title">' . __( 'Booster', 'woocommerce-jetpack' ) . ' - ' . $this->tools_array[ $tool_id ]['title'] . '</h3>';
				$html .= '<p class="wcj-tools-desc" style="font-style:italic;">' . $this->tools_array[ $tool_id ]['desc'] . '</p>';
			}
			return $html;
		}

		/**
		 * Add_tools.
		 *
		 * @version 2.3.10
		 * @since   2.2.3
		 * @param Array $tools_array Get Tools array.
		 * @param Array $args Get args.
		 */
		public function add_tools( $tools_array, $args = array() ) {
			$this->tools_array = $tools_array;
			add_action( 'wcj_module_tools_' . $this->id, array( $this, 'add_tool_link' ), PHP_INT_MAX );
			$hook_priority = isset( $args['tools_dashboard_hook_priority'] ) ? $args['tools_dashboard_hook_priority'] : 10;
			if ( $this->is_enabled() ) {
				add_filter( 'wcj_tools_tabs', array( $this, 'add_module_tools_tabs' ), $hook_priority );
				foreach ( $this->tools_array as $tool_id => $tool_data ) {
					add_action( 'wcj_tools_' . $tool_id, array( $this, 'create_' . $tool_id . '_tool' ) );
				}
			}
			add_action( 'wcj_tools_dashboard', array( $this, 'add_module_tools_info_to_tools_dashboard' ), $hook_priority );
		}

		/**
		 * Add_module_tools_tabs.
		 *
		 * @version 2.3.10
		 * @since   2.3.10
		 * @param Array $tabs Get tabs.
		 */
		public function add_module_tools_tabs( $tabs ) {
			foreach ( $this->tools_array as $tool_id => $tool_data ) {
				$tool_title = ( isset( $tool_data['tab_title'] ) ) ?
				$tool_data['tab_title'] :
				$tool_data['title'];
				$tabs[]     = array(
					'id'    => $tool_id,
					'title' => $tool_title,
				);
			}
			return $tabs;
		}

		/**
		 * Add_module_tools_info_to_tools_dashboard.
		 *
		 * @version 2.3.10
		 * @since   2.3.10
		 */
		public function add_module_tools_info_to_tools_dashboard() {
			$is_enabled_html = ( $this->is_enabled() ) ?
			'<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>' :
			'<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
			foreach ( $this->tools_array as $tool_id => $tool_data ) {
				$tool_title            = $tool_data['title'];
				$tool_desc             = $tool_data['desc'];
				$additional_style_html = '';
				$additional_info_html  = '';
				if ( isset( $tool_data['deprecated'] ) && true === $tool_data['deprecated'] ) {
					$additional_style_html = 'color:gray;font-style:italic;';
					$additional_info_html  = ' - ' . __( 'Deprecated', 'woocommerce-jetpack' );
				}
				echo '<tr>';
				echo '<td style="' . wp_kses_post( $additional_style_html ) . '">' . wp_kses_post( $tool_title ) . wp_kses_post( $additional_info_html ) . '</td>';
				echo '<td style="' . wp_kses_post( $additional_style_html ) . '">' . wp_kses_post( $this->short_desc ) . '</td>';
				echo '<td style="' . wp_kses_post( $additional_style_html ) . '">' . wp_kses_post( $tool_desc ) . '</td>';
				echo '<td style="' . wp_kses_post( $additional_style_html ) . '">' . wp_kses_post( $is_enabled_html ) . '</td>';
				echo '</tr>';
			}
		}

		/**
		 * Add_tool_link.
		 *
		 * @version 5.6.7
		 * @since   2.2.3
		 */
		public function add_tool_link() {
			foreach ( $this->tools_array as $tool_id => $tool_data ) {
				$tool_title = $tool_data['title'];
				echo '<p>';
				echo ( $this->is_enabled() ) ?
				'<a href="' . esc_url_raw( admin_url( 'admin.php?page=wcj-tools&tab=' . $tool_id . '&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) ) ) . '"><code>' . wp_kses_post( $tool_title ) . '</code></a>' :
				'<code>' . wp_kses_post( $tool_title ) . '</code>';
				echo '</p>';
			}
		}

		/**
		 * Add_reset_settings_button.
		 *
		 * @version 5.6.7
		 * @since   2.4.0
		 * @param Array $settings Get settings.
		 */
		public function add_reset_settings_button( $settings ) {
			$reset_button_style     = 'background: red; border-color: red; box-shadow: 0 1px 0 red; text-shadow: 0 -1px 1px #a00,1px 0 1px #a00,0 1px 1px #a00,-1px 0 1px #a00;';
			$reset_settings_setting = array(
				array(
					'title' => __( 'Reset Settings', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'id'    => 'wcj_' . $this->id . '_reset_settings_options',
				),
				array(
					'title' => ( 'module' === $this->type ) ?
						__( 'Reset Module to Default Settings', 'woocommerce-jetpack' ) :
						__( 'Reset Submodule to Default Settings', 'woocommerce-jetpack' ),
					'id'    => 'wcj_' . $this->id . '_reset_settings',
					'type'  => 'custom_link',
					'link'  => '<a onclick="return confirm(\'' . __( 'Are you sure?', 'woocommerce-jetpack' ) . '\')" class="button-primary" style="' .
						$reset_button_style . '" href="' . add_query_arg(
							array(
								'wcj_reset_settings' => $this->id,
								'wcj_reset_settings-' . $this->id . '-nonce' => wp_create_nonce( 'wcj_reset_settings' ),
							)
						) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_' . $this->id . '_reset_settings_options',
				),
			);
			return array_merge( $settings, $reset_settings_setting );
		}

		/**
		 * Settings_section.
		 * Only for `module`.
		 *
		 * @version 7.2.5
		 * @param Array  $settings Get settings.
		 * @param string $module_desc Get module_desc.
		 */
		public function add_enable_module_setting( $settings, $module_desc = '' ) {
			if ( 'module' !== $this->type ) {
				return $settings;
			}
			if ( '' === $module_desc && ! empty( $this->get_extra_desc() ) ) {
				$module_desc = '<div class="wcj-plugins-sing-acc-sub-cnt" style="margin-top:10px;">' . $this->get_extra_desc() . '</div>';
			}
			if ( ! isset( $this->link ) && isset( $this->link_slug ) && '' !== $this->link_slug ) {

				$this->link = 'https://booster.io/docs/' . $this->link_slug . '/';

			}
			$the_link = '';
			if ( isset( $this->link ) && '' !== $this->link ) {
				$the_link = '<p><a class="button-primary"' .
				' style="background: green; border-color: green; box-shadow: 0 1px 0 green; text-shadow: 0 -1px 1px #0a0,1px 0 1px #0a0,0 1px 1px #0a0,-1px 0 1px #0a0;"' .
				' href="' . $this->link . '?utm_source=module_documentation&utm_medium=module_button&utm_campaign=booster_documentation" target="_blank">' . __( 'Documentation', 'woocommerce-jetpack' ) . '</a></p>';
			}
			$enable_module_setting = array(
				array(
					'title' => $this->short_desc . ' ' . __( 'Module Options', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'desc'  => $module_desc,
					'id'    => 'wcj_' . $this->id . '_module_options',
				),
				array(
					'title'    => $this->short_desc,
					'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
					'desc_tip' => $this->get_desc() . $the_link,
					'id'       => 'wcj_' . $this->id . '_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
					'wcj_desc' => $this->get_desc(),
					'wcj_link' => ( isset( $this->link ) ? $this->link : '' ),
				),
				array(
					'type'              => 'module_head',
					'title'             => $this->short_desc,
					'desc'              => $this->get_desc(),
					'id'                => 'wcj_' . $this->id . '_enabled',
					'key'               => $this->id,
					'default'           => 'no',
					'wcj_link'          => ( isset( $this->link ) ? $this->link : '' ),
					'module_desc'       => $module_desc,
					'module_reset_link' => '<a style="width:auto;" onclick="return confirm(\'' . __( 'Are you sure? This will reset module to default settings.', 'woocommerce-jetpack' ) . '\')" class="wcj_manage_settting_btn wcj_tab_end_save_btn" href="' . esc_url_raw(
						add_query_arg(
							array(
								'wcj_reset_settings' => $this->id,
								'wcj_reset_settings-' . $this->id . '-nonce' => wp_create_nonce( 'wcj_reset_settings' ),
							)
						)
					) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_' . $this->id . '_module_options',
				),
			);
			return array_merge( $enable_module_setting, $settings );
		}

		/**
		 * Get_desc.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 *
		 * @return mixed
		 */
		public function get_desc() {
			if (
			empty( $this->desc_pro )
			|| ! class_exists( 'WCJ_Plus' )
			) {
				return $this->desc;
			}
			return $this->desc_pro;
		}

		/**
		 * Get_extra_desc.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 *
		 * @return mixed
		 */
		public function get_extra_desc() {
			if (
			empty( $this->extra_desc_pro )
			|| ! class_exists( 'WCJ_Plus' )
			) {
				return $this->extra_desc;
			}
			return $this->extra_desc_pro;
		}
	}

endif;
