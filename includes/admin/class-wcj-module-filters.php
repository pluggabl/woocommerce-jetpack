<?php
/**
 * Booster for WooCommerce - Module Filters
 *
 * Provides filter functionality for modules (Recommended, Active, Recently Used).
 * Part of Session C: Navigation (P7).
 *
 * @version 7.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Module_Filters' ) ) :

	/**
	 * WCJ_Module_Filters Class.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 */
	class WCJ_Module_Filters {

		/**
		 * Recommended modules list.
		 *
		 * @var array
		 */
		private $recommended_modules = array(
			'pdf_invoicing',
			'multicurrency',
			'product_addons',
			'checkout_customization',
			'checkout_custom_fields',
			'order_numbers',
			'product_input_fields',
			'currency_exchange_rates',
			'shipping',
			'payment_gateways',
		);

		/**
		 * Constructor.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function __construct() {
			add_action( 'wcj_module_settings_loaded', array( $this, 'track_module_access' ) );
			add_action( 'admin_init', array( $this, 'maybe_track_module_access' ) );
		}

		/**
		 * Get recommended modules list.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @return array
		 */
		public function get_recommended_modules() {
			return apply_filters( 'wcj_recommended_modules', $this->recommended_modules );
		}

		/**
		 * Get recently used modules.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @return array
		 */
		public function get_recent_modules() {
			$recent = get_user_meta( get_current_user_id(), 'wcj_recent_modules', true );
			return is_array( $recent ) ? $recent : array();
		}

		/**
		 * Track module access for "Recently Used" filter.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @param string $module_id Module ID.
		 */
		public function track_module_access( $module_id ) {
			if ( empty( $module_id ) ) {
				return;
			}

			$recent = $this->get_recent_modules();

			// Remove if already in list.
			$recent = array_diff( $recent, array( $module_id ) );

			// Add to front.
			array_unshift( $recent, $module_id );

			// Keep only last 10.
			$recent = array_slice( $recent, 0, 10 );

			update_user_meta( get_current_user_id(), 'wcj_recent_modules', $recent );
		}

		/**
		 * Maybe track module access on admin init.
		 *
		 * Tracks module access when viewing module settings page.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function maybe_track_module_access() {
			if ( ! is_admin() ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$module = isset( $_GET['module'] ) ? sanitize_text_field( wp_unslash( $_GET['module'] ) ) : '';

			// Track when viewing module settings on the plugins page.
			if ( 'wcj-plugins' === $page && ! empty( $section ) && 'active' !== $section ) {
				$this->track_module_access( $section );
				do_action( 'wcj_module_settings_loaded', $section );
			}
		}

		/**
		 * Check if module is recommended.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @param string $module_id Module ID.
		 * @return bool
		 */
		public function is_recommended( $module_id ) {
			return in_array( $module_id, $this->get_recommended_modules(), true );
		}

		/**
		 * Check if module is active.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @param string $module_id Module ID.
		 * @return bool
		 */
		public function is_active( $module_id ) {
			return 'yes' === wcj_get_option( 'wcj_' . $module_id . '_enabled', 'no' );
		}

		/**
		 * Check if module is recently used.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @param string $module_id Module ID.
		 * @return bool
		 */
		public function is_recent( $module_id ) {
			return in_array( $module_id, $this->get_recent_modules(), true );
		}

		/**
		 * Get module data attributes.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @param string $module_id Module ID.
		 * @return string HTML attributes.
		 */
		public function get_module_data_attributes( $module_id ) {
			$attributes = array(
				'data-module'      => esc_attr( $module_id ),
				'data-recommended' => $this->is_recommended( $module_id ) ? 'yes' : 'no',
				'data-active'      => $this->is_active( $module_id ) ? 'yes' : 'no',
				'data-recent'      => $this->is_recent( $module_id ) ? 'yes' : 'no',
			);

			$attr_string = '';
			foreach ( $attributes as $key => $value ) {
				$attr_string .= ' ' . $key . '="' . $value . '"';
			}

			return $attr_string;
		}

		/**
		 * Render filter buttons.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function render_filter_buttons() {
			?>
			<div class="wcj-module-filters" id="wcj-module-filters">
				<button class="wcj-filter-btn active" data-filter="all">
					<?php esc_html_e( 'All', 'woocommerce-jetpack' ); ?>
				</button>
				<button class="wcj-filter-btn" data-filter="recommended">
					<?php esc_html_e( 'Recommended', 'woocommerce-jetpack' ); ?>
				</button>
				<button class="wcj-filter-btn" data-filter="active">
					<?php esc_html_e( 'Active', 'woocommerce-jetpack' ); ?>
				</button>
				<button class="wcj-filter-btn" data-filter="recent">
					<?php esc_html_e( 'Recently Used', 'woocommerce-jetpack' ); ?>
				</button>
			</div>
			<?php
		}
	}

endif;
