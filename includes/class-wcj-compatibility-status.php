<?php
/**
 * Booster for WooCommerce - Compatibility Status
 *
 * @version 8.0.2
 * @since   8.0.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Compatibility_Status' ) ) :
	/**
	 * Admin-only compatibility status guidance for checkout architecture and HPOS.
	 */
	class WCJ_Compatibility_Status {

		/**
		 * Constructor.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'maybe_show_admin_notice' ) );
		}

		/**
		 * Shows module-specific compatibility guidance on Booster admin screens only.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 */
		public function maybe_show_admin_notice() {
			if ( ! $this->is_booster_admin_screen() ) {
				return;
			}

			$checkout_status = $this->detect_checkout_architecture();
			$blocks_modules  = $this->get_active_checkout_modules();
			$hpos_modules    = $this->get_active_hpos_modules();

			if ( 'blocks' === $checkout_status && ! empty( $blocks_modules ) ) {
				$this->render_notice(
					'warning',
					__( 'Booster checkout compatibility status', 'woocommerce-jetpack' ),
					__( 'Booster detected WooCommerce Checkout Blocks on this store. The active modules below are module-specific; keep using Classic Checkout where noted or test the flow in staging before release.', 'woocommerce-jetpack' ),
					$blocks_modules
				);
			}

			if ( $this->is_hpos_enabled() && ! empty( $hpos_modules ) ) {
				$this->render_notice(
					'info',
					__( 'Booster HPOS compatibility status', 'woocommerce-jetpack' ),
					__( 'High-Performance Order Storage is enabled. These active order modules use WooCommerce order APIs in the maintained paths, but custom order workflows should still be tested in staging.', 'woocommerce-jetpack' ),
					$hpos_modules
				);
			}
		}

		/**
		 * Detects if the current checkout page uses Checkout Blocks, Classic Checkout, or cannot be detected.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return string
		 */
		protected function detect_checkout_architecture() {
			if ( ! function_exists( 'wc_get_page_id' ) ) {
				return 'unknown';
			}

			$checkout_page_id = wc_get_page_id( 'checkout' );
			if ( $checkout_page_id <= 0 ) {
				return 'unknown';
			}

			$post = get_post( $checkout_page_id );
			if ( ! $post || empty( $post->post_content ) ) {
				return 'unknown';
			}

			if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $post ) ) {
				return 'blocks';
			}

			if ( false !== strpos( $post->post_content, '<!-- wp:woocommerce/checkout' ) || false !== strpos( $post->post_content, 'wp:woocommerce/checkout' ) ) {
				return 'blocks';
			}

			if ( has_shortcode( $post->post_content, 'woocommerce_checkout' ) ) {
				return 'classic';
			}

			return 'unknown';
		}

		/**
		 * Checks whether HPOS is enabled.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return bool
		 */
		protected function is_hpos_enabled() {
			return function_exists( 'wcj_is_hpos_enabled' ) && true === wcj_is_hpos_enabled();
		}

		/**
		 * Returns active Checkout Blocks sensitive modules.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return array
		 */
		protected function get_active_checkout_modules() {
			$modules = array(
				array(
					'id'    => 'checkout_fees',
					'label' => __( 'Checkout Fees', 'woocommerce-jetpack' ),
					'note'  => __( 'Simple fixed and percentage fees are maintained for Blocks; checkout-field-conditional fees remain a Classic Checkout/staging item.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'checkout_custom_fields',
					'label' => __( 'Checkout Custom Fields', 'woocommerce-jetpack' ),
					'note'  => __( 'Basic fields are Blocks-aware; richer visibility and placement rules should be confirmed on Classic Checkout or in staging.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'checkout_files_upload',
					'label' => __( 'Checkout Files Upload', 'woocommerce-jetpack' ),
					'note'  => __( 'Store API order attachment paths are maintained; file upload placement and validation should be tested with the exact checkout flow.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'product_addons',
					'label' => __( 'Product Addons', 'woocommerce-jetpack' ),
					'note'  => __( 'Addon choices now use safer submitted values and structured cart data; test add-to-cart, checkout, and order meta with your enabled tier limits.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'eu_vat_number',
					'label' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
					'note'  => __( 'VAT capture and validation remain checkout-flow sensitive; Classic Checkout is recommended unless the exact Blocks flow has been staged.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways',
					'label' => __( 'Custom Payment Gateways', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway visibility and order data should be checked with the active checkout architecture before release.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_country',
					'label' => __( 'Payment Gateways by Country', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on checkout customer data; test the live shipping/billing scenario in staging.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_currency',
					'label' => __( 'Payment Gateways by Currency', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on the active currency; test the currency switcher and checkout together.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_shipping',
					'label' => __( 'Payment Gateways by Shipping', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on selected shipping methods; test after shipping rates are available in the checkout flow.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_user_role',
					'label' => __( 'Payment Gateways by User Role', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on customer session state; test guest and signed-in roles separately.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_min_max',
					'label' => __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on recalculated cart totals; test below, inside, and above each configured threshold.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_per_category',
					'label' => __( 'Gateways by Product or Category', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on cart contents; test carts with matching and non-matching products/categories.', 'woocommerce-jetpack' ),
				),
			);

			return $this->filter_active_modules( $modules );
		}

		/**
		 * Returns active HPOS-sensitive modules.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return array
		 */
		protected function get_active_hpos_modules() {
			$modules = array(
				array(
					'id'    => 'order_numbers',
					'label' => __( 'Order Numbers', 'woocommerce-jetpack' ),
					'note'  => __( 'Order-number reads and writes now prefer WooCommerce order meta APIs with a legacy fallback.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'pdf_invoicing',
					'label' => __( 'PDF Invoicing', 'woocommerce-jetpack' ),
					'note'  => __( 'Order-owner checks now read the customer through WooCommerce order APIs with a legacy fallback.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'checkout_files_upload',
					'label' => __( 'Checkout Files Upload', 'woocommerce-jetpack' ),
					'note'  => __( 'Order attachment paths should be checked against the store\'s upload hooks and order emails.', 'woocommerce-jetpack' ),
				),
			);

			return $this->filter_active_modules( $modules );
		}

		/**
		 * Filters module definitions to active modules only.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @param array $modules defines module definitions.
		 * @return array
		 */
		protected function filter_active_modules( $modules ) {
			$active = array();

			foreach ( $modules as $module ) {
				if ( $this->is_module_enabled( $module['id'] ) ) {
					$active[] = $module;
				}
			}

			return $active;
		}

		/**
		 * Checks whether a module is enabled without changing tier limits.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @param string $module_id defines the module ID.
		 * @return bool
		 */
		protected function is_module_enabled( $module_id ) {
			if ( function_exists( 'wcj_is_module_enabled' ) ) {
				return wcj_is_module_enabled( $module_id );
			}

			return function_exists( 'wcj_get_option' ) && 'yes' === wcj_get_option( 'wcj_' . $module_id . '_enabled', 'no' );
		}

		/**
		 * Renders a compatibility notice.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @param string $type defines the notice type.
		 * @param string $title defines the notice title.
		 * @param string $intro defines the notice intro.
		 * @param array  $modules defines active module rows.
		 */
		protected function render_notice( $type, $title, $intro, $modules ) {
			$notice_class = ( 'warning' === $type ) ? 'notice-warning' : 'notice-info';

			echo '<div class="notice ' . esc_attr( $notice_class ) . ' wcj-compatibility-status">';
			echo '<p><strong>' . esc_html( $title ) . '</strong></p>';
			echo '<p>' . esc_html( $intro ) . '</p>';
			echo '<ul style="list-style:disc;margin-left:20px;">';

			foreach ( $modules as $module ) {
				echo '<li><strong>' . esc_html( $module['label'] ) . ':</strong> ' . esc_html( $module['note'] ) . '</li>';
			}

			echo '</ul>';
			echo '<p>' . esc_html( $this->get_tier_note() ) . '</p>';
			echo '</div>';
		}

		/**
		 * Returns tier-specific compatibility wording.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return string
		 */
		protected function get_tier_note() {
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ? WCJ_FREE_PLUGIN_FILE : '' );
			$basename    = basename( $plugin_file );

			if ( 'woocommerce-jetpack.php' === $basename ) {
				return __( 'Free tier note: this status reflects the documented Free feature set and limits; it does not unlock paid-only controls.', 'woocommerce-jetpack' );
			}

			if ( 'booster-plus-for-woocommerce.php' === $basename ) {
				return __( 'Plus tier note: this status reflects the Plus feature set; Elite-only controls remain outside Plus.', 'woocommerce-jetpack' );
			}

			if ( 'booster-elite-for-woocommerce.php' === $basename ) {
				return __( 'Elite tier note: this status reflects the full Elite feature set, with Blocks and HPOS guidance kept module-specific.', 'woocommerce-jetpack' );
			}

			return __( 'Tier note: compatibility guidance is module-specific and does not change enabled features or limits.', 'woocommerce-jetpack' );
		}

		/**
		 * Checks whether the current admin screen belongs to Booster.
		 *
		 * @version 8.0.2
		 * @since   8.0.2
		 * @return bool
		 */
		protected function is_booster_admin_screen() {
			if ( ! is_admin() ) {
				return false;
			}

			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 0 === strpos( $page, 'wcj-' ) ) {
				return true;
			}

			if ( 'wc-settings' === $page && 'jetpack' === $tab ) {
				return true;
			}

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'wcj' ) ) {
				return true;
			}

			return false;
		}
	}
endif;

if ( is_admin() ) {
	new WCJ_Compatibility_Status();
}
