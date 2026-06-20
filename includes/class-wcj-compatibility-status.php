<?php
/**
 * Booster for WooCommerce - Compatibility Status
 *
 * @version 8.1.0
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
			$conditional_fields_note = defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '9.9.0', '>=' )
				? __( 'Text, select, checkbox, and radio-as-select fields support cart-aware visibility. Other types and Classic placement sections remain Classic-only.', 'woocommerce-jetpack' )
				: __( 'Conditioned fields require WooCommerce 9.9 or later and are skipped in Blocks on this WooCommerce version. Unconditioned supported types remain available.', 'woocommerce-jetpack' );
			$modules = array(
				array(
					'id'    => 'checkout_fees',
					'label' => __( 'Checkout Fees', 'woocommerce-jetpack' ),
					'status' => __( 'Partial', 'woocommerce-jetpack' ),
					'note'  => __( 'Simple fixed and percentage fees are maintained for Blocks; checkout-field-conditional fees remain a Classic Checkout/staging item.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'checkout_custom_fields',
					'label' => __( 'Checkout Custom Fields', 'woocommerce-jetpack' ),
					'status' => __( 'Supported types', 'woocommerce-jetpack' ),
					'note'  => $conditional_fields_note,
				),
				array(
					'id'    => 'checkout_files_upload',
					'label' => __( 'Checkout Files Upload', 'woocommerce-jetpack' ),
					'status' => __( 'Classic-only input', 'woocommerce-jetpack' ),
					'note'  => __( 'Checkout file inputs require Classic Checkout. Existing post-order account and order-association paths remain available where configured.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'product_addons',
					'label' => __( 'Product Addons', 'woocommerce-jetpack' ),
					'status' => __( 'Cart and order supported', 'woocommerce-jetpack' ),
					'note'  => __( 'Addon inputs render on product pages and their cart data is persisted through Classic and Blocks checkout. They are not checkout form fields.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'product_input_fields',
					'label'  => __( 'Product Input Fields', 'woocommerce-jetpack' ),
					'status' => __( 'Cart and order supported', 'woocommerce-jetpack' ),
					'note'   => __( 'Product-page values, including supported files, travel with cart items through Classic and Blocks checkout. They are not checkout form fields.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'payment_gateways_fees',
					'label'  => __( 'Gateway Fees and Discounts', 'woocommerce-jetpack' ),
					'status' => __( 'Supported', 'woocommerce-jetpack' ),
					'note'   => __( 'Uses the WooCommerce chosen-payment session and cart fee API. Test guest and signed-in gateway switching with every configured rule.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'checkout_custom_info',
					'label'  => __( 'Checkout Custom Info', 'woocommerce-jetpack' ),
					'status' => __( 'Classic-only', 'woocommerce-jetpack' ),
					'note'   => __( 'Shortcode checkout placement hooks do not have equivalent supported Checkout block positions.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'more_button_labels',
					'label'  => __( 'Checkout button labels', 'woocommerce-jetpack' ),
					'status' => __( 'Classic-only', 'woocommerce-jetpack' ),
					'note'   => __( 'Classic button-label filters do not change the Checkout block submit button.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'eu_vat_number',
					'label' => __( 'EU VAT Number', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'VAT capture and validation remain checkout-flow sensitive; Classic Checkout is recommended unless the exact Blocks flow has been staged.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways',
					'label' => __( 'Custom Payment Gateways', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway visibility and order data should be checked with the active checkout architecture before release.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_country',
					'label' => __( 'Payment Gateways by Country', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on checkout customer data; test the live shipping/billing scenario in staging.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_currency',
					'label' => __( 'Payment Gateways by Currency', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on the active currency; test the currency switcher and checkout together.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_shipping',
					'label' => __( 'Payment Gateways by Shipping', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on selected shipping methods; test after shipping rates are available in the checkout flow.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_by_user_role',
					'label' => __( 'Payment Gateways by User Role', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on customer session state; test guest and signed-in roles separately.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_min_max',
					'label' => __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
					'note'  => __( 'Gateway filters depend on recalculated cart totals; test below, inside, and above each configured threshold.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'payment_gateways_per_category',
					'label' => __( 'Gateways by Product or Category', 'woocommerce-jetpack' ),
					'status' => __( 'Needs exact-flow testing', 'woocommerce-jetpack' ),
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
					'status' => __( 'Order CRUD', 'woocommerce-jetpack' ),
					'note'  => __( 'Creation, display, search, and renumeration use maintained WooCommerce order paths in the audited workflows.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'pdf_invoicing',
					'label' => __( 'PDF Invoicing', 'woocommerce-jetpack' ),
					'status' => __( 'Order CRUD', 'woocommerce-jetpack' ),
					'note'  => __( 'Invoice document metadata, order-owner checks, admin numbering, and audited report fields use WooCommerce order APIs.', 'woocommerce-jetpack' ),
				),
				array(
					'id'    => 'checkout_files_upload',
					'label' => __( 'Checkout Files Upload', 'woocommerce-jetpack' ),
					'status' => __( 'Order CRUD', 'woocommerce-jetpack' ),
					'note'  => __( 'Order attachment, admin, download, deletion, email, and account metadata use WooCommerce order APIs in audited paths.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'checkout_custom_fields',
					'label'  => __( 'Checkout Custom Fields', 'woocommerce-jetpack' ),
					'status' => __( 'Order CRUD', 'woocommerce-jetpack' ),
					'note'   => __( 'Classic and Blocks field updates are batched on the order object and saved once per logical update.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'product_addons',
					'label'  => __( 'Product Addons', 'woocommerce-jetpack' ),
					'status' => __( 'Order-item CRUD', 'woocommerce-jetpack' ),
					'note'   => __( 'Addon values are persisted while WooCommerce creates the order line item, without legacy item properties or extra saves.', 'woocommerce-jetpack' ),
				),
				array(
					'id'     => 'product_input_fields',
					'label'  => __( 'Product Input Fields', 'woocommerce-jetpack' ),
					'status' => __( 'Order-item CRUD', 'woocommerce-jetpack' ),
					'note'   => __( 'Input values and file references use WooCommerce order-item APIs in maintained checkout paths.', 'woocommerce-jetpack' ),
				),
			);

			return $this->filter_active_modules( $modules );
		}

		/**
		 * Filters module definitions to active modules only.
		 *
		 * @version 8.1.0
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
			echo '<table class="widefat striped" style="margin:8px 0 12px;max-width:1100px;">';
			echo '<thead><tr><th>' . esc_html__( 'Module', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Status', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Guidance', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';

			foreach ( $modules as $module ) {
				echo '<tr><td><strong>' . esc_html( $module['label'] ) . '</strong></td><td>' . esc_html( isset( $module['status'] ) ? $module['status'] : __( 'Review', 'woocommerce-jetpack' ) ) . '</td><td>' . esc_html( $module['note'] ) . '</td></tr>';
			}

			echo '</tbody></table>';
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
