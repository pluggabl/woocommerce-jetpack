<?php
/**
 * Booster for WooCommerce - Getting Started Hub
 *
 * Provides a dismissible Getting Started hub with 4 job cards on the dashboard.
 * Part of Session C: Navigation (P6).
 *
 * @version 7.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Getting_Started_Hub' ) ) :

	/**
	 * WCJ_Getting_Started_Hub Class.
	 *
	 * @version 7.9.0
	 * @since   7.9.0
	 */
	class WCJ_Getting_Started_Hub {

		/**
		 * Constructor.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function __construct() {
			add_action( 'wp_ajax_wcj_dismiss_getting_started', array( $this, 'ajax_dismiss_getting_started' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Check if hub should be displayed.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @return bool
		 */
		public function should_display() {
			return 'yes' !== get_user_meta( get_current_user_id(), 'wcj_getting_started_dismissed', true );
		}

		/**
		 * Get job cards data.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 * @return array
		 */
		private function get_jobs() {
			return array(
				array(
					'id'          => 'pdf_invoicing',
					'title'       => __( 'PDF Invoicing', 'woocommerce-jetpack' ),
					'description' => __( 'Generate invoices and packing slips', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-media-document',
					'link'        => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=pdf_invoicing&section=pdf_invoicing' ),
					'preset_link' => wp_nonce_url( admin_url( 'admin.php?page=wcj-dashboard&apply_preset=pdf_invoicing' ), 'wcj_apply_preset' ),
				),
				array(
					'id'          => 'multicurrency',
					'title'       => __( 'Multicurrency', 'woocommerce-jetpack' ),
					'description' => __( 'Sell in multiple currencies', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-money-alt',
					'link'        => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_currencies&section=multicurrency' ),
					'preset_link' => wp_nonce_url( admin_url( 'admin.php?page=wcj-dashboard&apply_preset=multicurrency' ), 'wcj_apply_preset' ),
				),
				array(
					'id'          => 'product_addons',
					'title'       => __( 'Product Addons', 'woocommerce-jetpack' ),
					'description' => __( 'Add extra options to products', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-plus-alt',
					'link'        => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=products&section=product_addons' ),
					'preset_link' => wp_nonce_url( admin_url( 'admin.php?page=wcj-dashboard&apply_preset=product_addons' ), 'wcj_apply_preset' ),
				),
				array(
					'id'          => 'checkout',
					'title'       => __( 'Checkout', 'woocommerce-jetpack' ),
					'description' => __( 'Customize checkout experience', 'woocommerce-jetpack' ),
					'icon'        => 'dashicons-cart',
					'link'        => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=cart_checkout&section=checkout_customization' ),
					'preset_link' => wp_nonce_url( admin_url( 'admin.php?page=wcj-dashboard&apply_preset=checkout' ), 'wcj_apply_preset' ),
				),
			);
		}

		/**
		 * Render the Getting Started hub.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function render() {
			if ( ! $this->should_display() ) {
				return;
			}

			$jobs = $this->get_jobs();
			?>
			<div class="wcj-getting-started-hub" id="wcj-getting-started-hub">
				<div class="wcj-container">
					<div class="wcj-row">
						<div class="wcj-hub-wrapper">
							<div class="wcj-hub-header">
								<h2><?php esc_html_e( 'Getting Started', 'woocommerce-jetpack' ); ?></h2>
								<p><?php esc_html_e( 'Choose what you want to accomplish with Booster:', 'woocommerce-jetpack' ); ?></p>
								<button class="wcj-hub-dismiss" id="wcj-hub-dismiss" title="<?php esc_attr_e( 'Dismiss', 'woocommerce-jetpack' ); ?>">&times;</button>
							</div>
							<div class="wcj-hub-cards">
								<?php foreach ( $jobs as $job ) : ?>
									<a href="<?php echo esc_url( $job['preset_link'] ); ?>" class="wcj-hub-card" data-job-id="<?php echo esc_attr( $job['id'] ); ?>">
										<span class="dashicons <?php echo esc_attr( $job['icon'] ); ?>"></span>
										<h3><?php echo esc_html( $job['title'] ); ?></h3>
										<p><?php echo esc_html( $job['description'] ); ?></p>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * AJAX handler for dismissing Getting Started hub.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function ajax_dismiss_getting_started() {
			check_ajax_referer( 'wcj-hub-nonce', 'nonce' );

			// phpcs:ignore WordPress.WP.Capabilities.Unknown
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( 'Permission denied.' );
			}

			update_user_meta( get_current_user_id(), 'wcj_getting_started_dismissed', 'yes' );
			wp_send_json_success();
		}

		/**
		 * Enqueue assets.
		 *
		 * @version 7.9.0
		 * @since   7.9.0
		 */
		public function enqueue_assets() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			if ( 'wcj-dashboard' === $active_page || 'wcj-plugins' === $active_page ) {

				$version = defined( 'WCJ_VERSION' );

				wp_enqueue_style(
					'wcj-hub-css',
					wcj_plugin_url() . '/assets/css/wcj-hub.css',
					array(),
					$version
				);

				wp_enqueue_script(
					'wcj-module-filters',
					wcj_plugin_url() . '/assets/js/wcj-module-filters.js',
					array( 'jquery' ),
					$version,
					true
				);

				wp_localize_script(
					'wcj-module-filters',
					'wcj_hub_params',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'wcj-hub-nonce' ),
					)
				);
			}
		}
	}

endif;
