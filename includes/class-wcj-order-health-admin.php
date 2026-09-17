<?php
/**
 * Booster for WooCommerce - Order Health admin dashboard.
 *
 * @version 8.4.0
 * @since   8.4.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Order_Health_Admin' ) ) :
	/** Provides a capability-protected, read-only merchant dashboard. */
	class WCJ_Order_Health_Admin {

		/** Constructor. */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 98 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/** Registers Order Health below WooCommerce. */
		public function register_page() {
			add_submenu_page(
				'woocommerce',
				__( 'Order Health', 'woocommerce-jetpack' ),
				__( 'Order Health', 'woocommerce-jetpack' ),
				'manage_woocommerce', // phpcs:ignore
				'wcj-order-health',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Enqueues the scoped dashboard stylesheet only on the Order Health page.
		 *
		 * @param string $hook_suffix Current admin page hook suffix.
		 */
		public function enqueue_assets( $hook_suffix ) {
			if ( 'woocommerce_page_wcj-order-health' !== $hook_suffix ) {
				return;
			}
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ? WCJ_FREE_PLUGIN_FILE : '' );
			if ( '' !== $plugin_file ) {
				wp_enqueue_style( 'wcj-order-health', plugin_dir_url( $plugin_file ) . 'assets/css/admin/wcj-order-health.css', array(), '8.4.0' );
			}
		}

		/** Returns the active Booster package label for the shared UI. */
		private function get_package_label() {
			if ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ) {
				return __( 'Free', 'woocommerce-jetpack' );
			}
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? basename( WCJ_PLUGIN_FILE ) : '';
			if ( 'booster-plus-for-woocommerce.php' === $plugin_file ) {
				return __( 'Plus', 'woocommerce-jetpack' );
			}
			return __( 'Elite', 'woocommerce-jetpack' );
		}

		/**
		 * Returns a URL for an asset inside the active Booster package.
		 *
		 * @param string $relative_path Relative asset path.
		 * @return string Asset URL.
		 */
		private function get_asset_url( $relative_path ) {
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ? WCJ_FREE_PLUGIN_FILE : '' );
			return '' !== $plugin_file ? plugin_dir_url( $plugin_file ) . ltrim( $relative_path, '/' ) : '';
		}

		/** Renders the read-only dashboard and Morning Store Briefing. */
		public function render_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore
				wp_die( esc_html__( 'You do not have permission to view Order Health.', 'woocommerce-jetpack' ) );
			}

			$filters  = array(
				'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'cause'  => isset( $_GET['cause'] ) ? sanitize_key( wp_unslash( $_GET['cause'] ) ) : 'all', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'age'    => isset( $_GET['age'] ) ? sanitize_key( wp_unslash( $_GET['age'] ) ) : 'all', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
			$service  = new WCJ_Order_Health_Service();
			$data     = $service->get_dashboard_data( $filters );
			$is_full  = $service->is_full_experience();
			$briefing = array();
			if ( $is_full ) {
				$summary  = array(
					'bounded_query' => array(
						'attention_count' => $data['query']['attention_count'],
						'has_more'        => $data['query']['has_more'],
					),
				);
				$briefing = $service->get_morning_store_briefing( $summary );
			}

			$logo_url = $this->get_asset_url( 'assets/images/wcj-booster-icon.svg' );
			echo '<div class="wrap wcj-order-health">';
			echo '<div class="wcj-order-health__hero"><div class="wcj-order-health__hero-main"><div class="wcj-order-health__brand">';
			if ( '' !== $logo_url ) {
				echo '<img src="' . esc_url( $logo_url ) . '" alt="">';
			}
			echo '<div><span class="wcj-order-health__eyebrow">' . esc_html__( 'Booster for WooCommerce · 8.4', 'woocommerce-jetpack' ) . '</span><h1>' . esc_html__( 'Order Health', 'woocommerce-jetpack' ) . '</h1></div></div>';
			echo '<p>' . esc_html__( 'See which orders may need attention, why they were flagged, and the safest merchant-controlled next step.', 'woocommerce-jetpack' ) . '</p>';
			echo '<div class="wcj-order-health__hero-actions"><a class="wcj-order-health__hero-button" href="https://booster.io/docs/woocommerce-order-health/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Read the setup guide', 'woocommerce-jetpack' ) . '</a><a class="wcj-order-health__hero-link" href="https://booster.io/features/woocommerce-order-health/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View feature overview', 'woocommerce-jetpack' ) . ' <span aria-hidden="true">↗</span></a></div></div>';
			echo '<div class="wcj-order-health__meta"><div class="wcj-order-health__mode"><span>' . esc_html__( 'Package', 'woocommerce-jetpack' ) . '</span><strong>' . esc_html( $this->get_package_label() ) . '</strong></div><div class="wcj-order-health__mode"><span>' . esc_html__( 'Experience', 'woocommerce-jetpack' ) . '</span><strong>' . esc_html( $is_full ? __( 'Full', 'woocommerce-jetpack' ) : __( 'Light', 'woocommerce-jetpack' ) ) . '</strong></div><div class="wcj-order-health__mode"><span>' . esc_html__( 'Order storage', 'woocommerce-jetpack' ) . '</span><strong>' . esc_html( 'hpos' === $data['storage_mode'] ? __( 'HPOS', 'woocommerce-jetpack' ) : __( 'Legacy', 'woocommerce-jetpack' ) ) . '</strong></div></div></div>';

			if ( $is_full ) {
				$this->render_briefing( $briefing );
			}
			$this->render_summary_cards( $data );
			$this->render_age_buckets( $data['summary']['age_buckets'] );
			if ( $is_full ) {
				$this->render_filters( $data['filters'] );
			}

			if ( $data['query']['has_more'] ) {
				/* translators: %d: Maximum number of orders included in the bounded scan. */
				echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Bounded scan reached.', 'woocommerce-jetpack' ) . '</strong> ' . esc_html( sprintf( __( 'For store performance, Order Health reads at most %d of the oldest active orders across fixed status families. Refine filters or review WooCommerce Orders for the remaining records.', 'woocommerce-jetpack' ), $data['query']['query_limit'] ) ) . '</p></div>';
			}
			if ( $data['display_truncated'] ) {
				/* translators: 1: Number of orders displayed. 2: Total number of matching orders. */
				echo '<div class="notice notice-info inline"><p>' . esc_html( sprintf( __( 'Showing the %1$d oldest matching orders out of %2$d matches in this bounded scan.', 'woocommerce-jetpack' ), $data['display_limit'], $data['filtered_count'] ) ) . '</p></div>';
			}

			$this->render_orders_table( $data['orders'] );
			if ( $is_full ) {
				echo '<p class="description wcj-order-health__boundary">' . esc_html__( 'Order Health is read-only. It never changes order status, issues refunds, sends customer messages, or makes AI-generated business decisions.', 'woocommerce-jetpack' ) . ' <a href="https://booster.io/docs/woocommerce-order-health/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn how flags and safe actions work.', 'woocommerce-jetpack' ) . '</a></p>';
			} else {
				echo '<p class="description wcj-order-health__boundary"><strong>' . esc_html__( 'Light experience:', 'woocommerce-jetpack' ) . '</strong> ' . esc_html__( 'Free and Plus include the read-only attention list and aging overview. Advanced filters, custom-status and partial-refund reasons, Morning Store Briefing, and the Order Health Ability are available in Elite.', 'woocommerce-jetpack' ) . '</p>';
			}
			echo '</div>';
		}

		/**
		 * Renders the privacy-safe Morning Store Briefing.
		 *
		 * @param array $briefing Morning Store Briefing data.
		 */
		private function render_briefing( $briefing ) {
			echo '<section class="wcj-order-health__section"><div class="wcj-order-health__section-heading"><div><span class="wcj-order-health__eyebrow">' . esc_html__( 'Privacy-safe daily view', 'woocommerce-jetpack' ) . '</span><h2>' . esc_html__( 'Morning Store Briefing', 'woocommerce-jetpack' ) . '</h2></div><span class="wcj-order-health__privacy">' . esc_html__( 'Aggregate only', 'woocommerce-jetpack' ) . '</span></div>';
			echo '<div class="wcj-order-health__briefing">';
			$this->render_briefing_item(
				__( 'Orders', 'woocommerce-jetpack' ),
				(int) $briefing['order_health']['attention_count'],
				__( 'need attention in the bounded scan', 'woocommerce-jetpack' ),
				'action-needed' === $briefing['order_health']['state']
			);
			$this->render_briefing_item(
				__( 'Compatibility', 'woocommerce-jetpack' ),
				(int) $briefing['compatibility']['warning_count'],
				__( 'active module warnings', 'woocommerce-jetpack' ),
				'review' === $briefing['compatibility']['state']
			);
			$this->render_briefing_item(
				__( 'Background jobs', 'woocommerce-jetpack' ),
				(int) $briefing['background_jobs']['overdue'] + (int) $briefing['background_jobs']['failed'],
				__( 'overdue or failed jobs', 'woocommerce-jetpack' ),
				'review' === $briefing['background_jobs']['state']
			);
			echo '</div><p class="description">' . esc_html__( 'The briefing contains counts and diagnostic codes only—no customer details, order identifiers, raw settings, or job arguments.', 'woocommerce-jetpack' ) . '</p></section>';
		}

		/**
		 * Renders one briefing item.
		 *
		 * @param string $label Item label.
		 * @param int    $count Item count.
		 * @param string $description Item description.
		 * @param bool   $needs_review Whether the item needs review.
		 */
		private function render_briefing_item( $label, $count, $description, $needs_review ) {
			$class = $needs_review ? 'is-review' : 'is-clear';
			echo '<div class="wcj-order-health__briefing-item ' . esc_attr( $class ) . '"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( number_format_i18n( $count ) ) . '</strong><small>' . esc_html( $description ) . '</small></div>';
		}

		/**
		 * Renders the main dashboard cards.
		 *
		 * @param array $data Dashboard data.
		 */
		private function render_summary_cards( $data ) {
			$attention = (int) $data['query']['attention_count'];
			$oldest    = (int) $data['summary']['oldest_age_days'];
			echo '<div class="wcj-order-health__cards">';
			$this->render_card( __( 'Needs attention', 'woocommerce-jetpack' ), number_format_i18n( $attention ), __( 'flagged in this bounded scan', 'woocommerce-jetpack' ) );
			$this->render_card( __( 'Oldest wait', 'woocommerce-jetpack' ), sprintf( /* translators: %d: Number of days. */ _n( '%d day', '%d days', $oldest, 'woocommerce-jetpack' ), $oldest ), __( 'based on payment time or order creation', 'woocommerce-jetpack' ) );
			$this->render_card( __( 'Query ceiling', 'woocommerce-jetpack' ), number_format_i18n( $data['query']['query_limit'] ), __( 'orders across fixed status families', 'woocommerce-jetpack' ) );
			echo '</div>';
		}

		/**
		 * Renders one summary card.
		 *
		 * @param string $label Card label.
		 * @param string $value Card value.
		 * @param string $description Card description.
		 */
		private function render_card( $label, $value, $description ) {
			echo '<div class="wcj-order-health__card"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $value ) . '</strong><small>' . esc_html( $description ) . '</small></div>';
		}

		/**
		 * Renders fixed age buckets.
		 *
		 * @param array $buckets Age bucket counts.
		 */
		private function render_age_buckets( $buckets ) {
			$labels = array(
				'under_1_day'      => __( 'Under 1 day', 'woocommerce-jetpack' ),
				'from_1_to_3_days' => __( '1–3 days', 'woocommerce-jetpack' ),
				'from_4_to_7_days' => __( '4–7 days', 'woocommerce-jetpack' ),
				'over_7_days'      => __( 'Over 7 days', 'woocommerce-jetpack' ),
			);
			echo '<section class="wcj-order-health__section"><h2>' . esc_html__( 'Aging buckets', 'woocommerce-jetpack' ) . '</h2><div class="wcj-order-health__buckets">';
			foreach ( $labels as $key => $label ) {
				echo '<div><span>' . esc_html( $label ) . '</span><strong>' . esc_html( number_format_i18n( isset( $buckets[ $key ] ) ? $buckets[ $key ] : 0 ) ) . '</strong></div>';
			}
			echo '</div></section>';
		}

		/**
		 * Renders GET filters; no data is changed.
		 *
		 * @param array $filters Current filter values.
		 */
		private function render_filters( $filters ) {
			$statuses = array( 'all' => __( 'All statuses', 'woocommerce-jetpack' ) );
			foreach ( (array) wc_get_order_statuses() as $key => $label ) {
				$key              = 0 === strpos( $key, 'wc-' ) ? substr( $key, 3 ) : $key;
				$statuses[ $key ] = $label;
			}
			$causes = array(
				'all'                 => __( 'All likely causes', 'woocommerce-jetpack' ),
				'payment'             => __( 'Payment', 'woocommerce-jetpack' ),
				'fulfillment'         => __( 'Fulfillment', 'woocommerce-jetpack' ),
				'configuration'       => __( 'Configuration', 'woocommerce-jetpack' ),
				'incomplete_workflow' => __( 'Incomplete workflow', 'woocommerce-jetpack' ),
			);
			$ages   = array(
				'all'              => __( 'All ages', 'woocommerce-jetpack' ),
				'under_1_day'      => __( 'Under 1 day', 'woocommerce-jetpack' ),
				'from_1_to_3_days' => __( '1–3 days', 'woocommerce-jetpack' ),
				'from_4_to_7_days' => __( '4–7 days', 'woocommerce-jetpack' ),
				'over_7_days'      => __( 'Over 7 days', 'woocommerce-jetpack' ),
			);

			echo '<form class="wcj-order-health__filters" method="get"><input type="hidden" name="page" value="wcj-order-health">';
			$this->render_select( 'status', __( 'Status', 'woocommerce-jetpack' ), $statuses, $filters['status'] );
			$this->render_select( 'cause', __( 'Likely cause', 'woocommerce-jetpack' ), $causes, $filters['cause'] );
			$this->render_select( 'age', __( 'Age', 'woocommerce-jetpack' ), $ages, $filters['age'] );
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Apply filters', 'woocommerce-jetpack' ) . '</button>';
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=wcj-order-health' ) ) . '">' . esc_html_x( 'Clear', 'Reset filters', 'woocommerce-jetpack' ) . '</a></form>';
		}

		/**
		 * Renders one accessible select.
		 *
		 * @param string $name Select field name.
		 * @param string $label Select label.
		 * @param array  $options Select options.
		 * @param string $selected Currently selected value.
		 */
		private function render_select( $name, $label, $options, $selected ) {
			echo '<label><span>' . esc_html( $label ) . '</span><select name="' . esc_attr( $name ) . '">';
			foreach ( $options as $value => $option_label ) {
				echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>' . esc_html( $option_label ) . '</option>';
			}
			echo '</select></label>';
		}

		/**
		 * Renders explainable order rows without customer details.
		 *
		 * @param array $orders Orders to display.
		 */
		private function render_orders_table( $orders ) {
			echo '<section class="wcj-order-health__section"><div class="wcj-order-health__section-heading"><h2>' . esc_html__( 'Orders that may need attention', 'woocommerce-jetpack' ) . '</h2><span>' . esc_html( sprintf( /* translators: %d: Number of order results. */ _n( '%d result', '%d results', count( $orders ), 'woocommerce-jetpack' ), count( $orders ) ) ) . '</span></div>';
			if ( empty( $orders ) ) {
				echo '<div class="wcj-order-health__empty"><strong>' . esc_html__( 'No matching orders were flagged.', 'woocommerce-jetpack' ) . '</strong><p>' . esc_html__( 'Order Health only reports deterministic rules from the bounded active-order scan.', 'woocommerce-jetpack' ) . '</p></div></section>';
				return;
			}
			echo '<div class="wcj-order-health__table-wrap"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Order', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Status', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Waiting', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Likely cause', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Why flagged', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Safe merchant action', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
			foreach ( $orders as $order ) {
				$waiting = $this->format_waiting( $order );
				echo '<tr><td><a href="' . esc_url( $order['edit_url'] ) . '"><strong>#' . esc_html( $order['order_number'] ) . '</strong></a>';
				if ( 'partial' === $order['refund_state'] ) {
					echo '<span class="wcj-order-health__tag">' . esc_html__( 'Partial refund', 'woocommerce-jetpack' ) . '</span>';
				}
				echo '</td><td>' . esc_html( $order['status_label'] ) . '</td><td><strong>' . esc_html( $waiting ) . '</strong><small>' . esc_html( $this->waiting_basis_label( $order['waiting_basis'] ) ) . '</small></td><td><span class="wcj-order-health__cause wcj-order-health__cause--' . esc_attr( $order['cause'] ) . '">' . esc_html( $order['cause_label'] ) . '</span></td><td>' . esc_html( $order['reason_text'] ) . '<small>' . esc_html( implode( ', ', $order['reason_codes'] ) ) . '</small></td><td>' . esc_html( $order['safe_action'] ) . '</td></tr>';
			}
			echo '</tbody></table></div></section>';
		}

		/**
		 * Formats an order wait in merchant-friendly units.
		 *
		 * @param array $order Order data.
		 * @return string Formatted waiting time.
		 */
		private function format_waiting( $order ) {
			if ( $order['age_days'] > 0 ) {
				return sprintf( /* translators: %d: Number of days. */ _n( '%d day', '%d days', $order['age_days'], 'woocommerce-jetpack' ), $order['age_days'] );
			}
			$hours = max( 1, (int) floor( $order['age_seconds'] / HOUR_IN_SECONDS ) );
			return sprintf( /* translators: %d: Number of hours. */ _n( '%d hour', '%d hours', $hours, 'woocommerce-jetpack' ), $hours );
		}

		/**
		 * Explains which read-only timestamp supports the wait.
		 *
		 * @param string $basis Waiting time basis.
		 * @return string Waiting basis label.
		 */
		private function waiting_basis_label( $basis ) {
			$labels = array(
				'order_created'    => __( 'since order creation', 'woocommerce-jetpack' ),
				'payment_received' => __( 'since payment', 'woocommerce-jetpack' ),
				'unknown'          => __( 'wait start unavailable', 'woocommerce-jetpack' ),
			);
			return isset( $labels[ $basis ] ) ? $labels[ $basis ] : '';
		}
	}
endif;

if ( is_admin() ) {
	new WCJ_Order_Health_Admin();
}
