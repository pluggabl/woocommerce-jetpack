<?php
/**
 * Booster for WooCommerce - Presets System
 *
 * Provides one-click preset configurations for 4 core jobs:
 * - PDF Invoicing
 * - Multicurrency
 * - Product Addons
 * - Checkout Customization
 *
 * @version 7.5.1
 * @since   7.5.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes/presets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Presets' ) ) :

	/**
	 * WCJ_Presets Class.
	 *
	 * Handles preset definitions and application for dashboard-level quick setup.
	 */
	class WCJ_Presets {

		/**
		 * Instance of this class.
		 *
		 * @var WCJ_Presets
		 */
		private static $instance = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_wcj_apply_preset', array( $this, 'ajax_apply_preset' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Get instance of this class.
		 *
		 * @return WCJ_Presets
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Get all available presets.
		 *
		 * @return array
		 */
		public static function get_presets() {
			$presets = array(
				'pdf_invoicing'  => array(
					'id'          => 'pdf_invoicing',
					'title'       => __( 'PDF Invoicing', 'woocommerce-jetpack' ),
					'description' => __( 'Generate professional invoices and packing slips for your orders.', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-media-document',
					'modules'     => array(
						'pdf_invoicing' => true,
					),
					'settings'    => array(
						'wcj_pdf_invoicing_enabled' => 'yes',
					),
					'first_win'   => array(
						'action' => __( 'View an order and click "Create Invoice"', 'woocommerce-jetpack' ),
						'link'   => admin_url( 'edit.php?post_type=shop_order' ),
					),
					'next_step'   => array(
						'label' => __( 'Customize invoice template', 'woocommerce-jetpack' ),
						'link'  => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing' ),
					),
				),
				'multicurrency'  => array(
					'id'          => 'multicurrency',
					'title'       => __( 'Multicurrency', 'woocommerce-jetpack' ),
					'description' => __( 'Let customers shop and pay in their local currency.', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-money-alt',
					'modules'     => array(
						'multicurrency' => true,
					),
					'settings'    => array(
						'wcj_multicurrency_enabled' => 'yes',
					),
					'first_win'   => array(
						'action' => __( 'Add a second currency and view your shop', 'woocommerce-jetpack' ),
						'link'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_currencies&section=multicurrency' ),
					),
					'next_step'   => array(
						'label' => __( 'Configure exchange rates', 'woocommerce-jetpack' ),
						'link'  => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_currencies&section=multicurrency' ),
					),
				),
				'product_addons' => array(
					'id'          => 'product_addons',
					'title'       => __( 'Product Addons', 'woocommerce-jetpack' ),
					'description' => __( 'Add extra options like gift wrapping or engraving to products.', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-plus-alt',
					'modules'     => array(
						'product_addons' => true,
					),
					'settings'    => array(
						'wcj_product_addons_enabled' => 'yes',
					),
					'first_win'   => array(
						'action' => __( 'Edit a product and add your first addon', 'woocommerce-jetpack' ),
						'link'   => admin_url( 'edit.php?post_type=product' ),
					),
					'next_step'   => array(
						'label' => __( 'Configure global addons', 'woocommerce-jetpack' ),
						'link'  => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=products&section=product_addons' ),
					),
				),
				'checkout'       => array(
					'id'          => 'checkout',
					'title'       => __( 'Checkout Customization', 'woocommerce-jetpack' ),
					'description' => __( 'Customize checkout fields, add custom buttons, streamline the process.', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-cart',
					'modules'     => array(
						'checkout_customization' => true,
						'checkout_custom_fields' => true,
					),
					'settings'    => array(
						'wcj_checkout_customization_enabled' => 'yes',
						'wcj_checkout_custom_fields_enabled' => 'yes',
					),
					'first_win'   => array(
						'action' => __( 'Go to checkout and see the changes', 'woocommerce-jetpack' ),
						'link'   => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
					),
					'next_step'   => array(
						'label' => __( 'Add custom checkout fields', 'woocommerce-jetpack' ),
						'link'  => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=cart_checkout&section=checkout_custom_fields' ),
					),
				),
			);

			/**
			 * Filter the dashboard presets array.
			 *
			 * @since 7.5.1
			 * @param array $presets Array of preset configurations.
			 */
			return apply_filters( 'wcj_dashboard_presets', $presets );
		}

		/**
		 * Apply a preset.
		 *
		 * @param string $preset_id The preset ID to apply.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		public static function apply_preset( $preset_id ) {
			$presets = self::get_presets();

			if ( ! isset( $presets[ $preset_id ] ) ) {
				return new WP_Error( 'invalid_preset', __( 'Preset not found.', 'woocommerce-jetpack' ) );
			}

			$preset = $presets[ $preset_id ];

			// DATA LOSS PREVENTION: Save current state for undo.
			// Store BOTH module states AND settings values.
			$undo_state = array(
				'modules'  => self::get_current_module_states( array_keys( $preset['modules'] ) ),
				'settings' => self::get_current_settings_values( array_keys( $preset['settings'] ) ),
			);
			update_user_meta( get_current_user_id(), 'wcj_preset_undo_state', $undo_state );
			update_user_meta( get_current_user_id(), 'wcj_last_preset_applied', $preset_id );

			// Enable modules.
			foreach ( $preset['modules'] as $module => $enabled ) {
				if ( $enabled ) {
					update_option( 'wcj_' . $module . '_enabled', 'yes' );
				}
			}

			// Apply settings - ONLY set defaults when option is empty/not set.
			// This prevents overwriting user's existing configurations.
			foreach ( $preset['settings'] as $option => $value ) {
				$existing = get_option( $option, '' );
				if ( empty( $existing ) ) {
					update_option( $option, $value );
				}
			}

			// Track telemetry event.
			do_action( 'wcj_preset_applied', $preset_id );

			return true;
		}

		/**
		 * Get current module states for undo.
		 *
		 * @param array $module_ids Array of module IDs.
		 * @return array Module states keyed by module ID.
		 */
		private static function get_current_module_states( $module_ids ) {
			$states = array();
			foreach ( $module_ids as $module ) {
				$states[ $module ] = get_option( 'wcj_' . $module . '_enabled', 'no' );
			}
			return $states;
		}

		/**
		 * Get current settings values for undo.
		 *
		 * @param array $option_keys Array of option keys.
		 * @return array Option values keyed by option name.
		 */
		private static function get_current_settings_values( $option_keys ) {
			$values = array();
			foreach ( $option_keys as $option ) {
				$values[ $option ] = get_option( $option, '' );
			}
			return $values;
		}

		/**
		 * AJAX handler for applying a preset.
		 */
		public function ajax_apply_preset() {
			check_ajax_referer( 'wcj-preset-nonce', 'nonce' );

			// phpcs:ignore WordPress.WP.Capabilities.Unknown
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'woocommerce-jetpack' ) );
			}

			$preset_id = isset( $_POST['preset_id'] ) ? sanitize_key( $_POST['preset_id'] ) : '';

			if ( empty( $preset_id ) ) {
				wp_send_json_error( __( 'No preset specified.', 'woocommerce-jetpack' ) );
			}

			$result = self::apply_preset( $preset_id );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			$presets = self::get_presets();
			$preset  = $presets[ $preset_id ];

			wp_send_json_success(
				array(
					'message'   => sprintf(
						/* translators: %s: preset title */
						__( '%s preset applied successfully!', 'woocommerce-jetpack' ),
						$preset['title']
					),
					'first_win' => $preset['first_win'],
					'next_step' => $preset['next_step'],
				)
			);
		}

		/**
		 * Enqueue scripts for the presets system.
		 */
		public function enqueue_scripts() {
			// Only load on Booster pages.
			$screen = get_current_screen();
			if ( ! $screen || strpos( $screen->id, 'wcj' ) === false ) {
				return;
			}

			wp_enqueue_style(
				'wcj-presets',
				wcj_plugin_url() . '/assets/css/admin/wcj-presets.css',
				array(),
				defined( 'WCJ_VERSION' ) ? WCJ_VERSION : '7.5.1'
			);

			wp_enqueue_script(
				'wcj-presets',
				wcj_plugin_url() . '/assets/js/admin/wcj-presets.js',
				array( 'jquery' ),
				defined( 'WCJ_VERSION' ) ? WCJ_VERSION : '7.5.1',
				true
			);

			wp_localize_script(
				'wcj-presets',
				'wcj_preset_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'wcj-preset-nonce' ),
					'strings'  => array(
						'applying'     => __( 'Applying...', 'woocommerce-jetpack' ),
						'apply_preset' => __( 'Apply Preset', 'woocommerce-jetpack' ),
						'error'        => __( 'Error occurred. Please try again.', 'woocommerce-jetpack' ),
						'go_there_now' => __( 'Go there now', 'woocommerce-jetpack' ),
						'first_win'    => __( 'First win:', 'woocommerce-jetpack' ),
					),
				)
			);
		}

		/**
		 * Render preset cards HTML.
		 */
		public static function render_preset_cards() {
			$presets = self::get_presets();
			?>
			<div class="wcj-preset-cards">
				<?php foreach ( $presets as $preset ) : ?>
					<div class="wcj-preset-card" data-preset-id="<?php echo esc_attr( $preset['id'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $preset['icon'] ); ?>"></span>
						<h3><?php echo esc_html( $preset['title'] ); ?></h3>
						<p><?php echo esc_html( $preset['description'] ); ?></p>
						<button class="button button-primary wcj-apply-preset">
							<?php esc_html_e( 'Apply Preset', 'woocommerce-jetpack' ); ?>
						</button>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		}

		/**
		 * Render a single preset card for a specific preset ID.
		 *
		 * @param string $preset_id The preset ID to render.
		 */
		public static function render_preset_card( $preset_id ) {
			$presets = self::get_presets();
			if ( ! isset( $presets[ $preset_id ] ) ) {
				return;
			}
			$preset = $presets[ $preset_id ];
			?>
			<div class="wcj-preset-card" data-preset-id="<?php echo esc_attr( $preset['id'] ); ?>">
				<span class="dashicons <?php echo esc_attr( $preset['icon'] ); ?>"></span>
				<h3><?php echo esc_html( $preset['title'] ); ?></h3>
				<p><?php echo esc_html( $preset['description'] ); ?></p>
				<button class="button button-primary wcj-apply-preset">
					<?php esc_html_e( 'Apply Preset', 'woocommerce-jetpack' ); ?>
				</button>
			</div>
			<?php
		}
	}

endif;

// Initialize the presets class.
WCJ_Presets::instance();
