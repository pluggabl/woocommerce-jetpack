<?php
/**
 * Booster for WooCommerce: read-only merchant Returns sample.
 *
 * @package Booster_For_WooCommerce/includes
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCJ_Returns_Summary_Admin' ) ) :
	/** Capability-protected aggregate view, with no customer actions. */
	class WCJ_Returns_Summary_Admin {
		/** Register only the existing admin menu hook. */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 98 );
		}

		/** Register the safe WooCommerce submenu. */
		public function register_page() {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain least-privilege access.
			add_submenu_page( 'woocommerce', __( 'Return Requests summary', 'woocommerce-jetpack' ), __( 'Return Requests summary', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-returns-summary', array( $this, 'render_page' ) );
		}

		/** Current authorization comes before dependency construction or output. */
		public function render_page() {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain least-privilege access.
			if ( get_current_user_id() < 1 || ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to view the Returns summary.', 'woocommerce-jetpack' ) );
			}
			$result = class_exists( 'WCJ_Returns_Summary' ) ? ( new WCJ_Returns_Summary() )->get_summary( array( 'scope' => 'summary' ) ) : null;
			echo '<div class="wrap"><h1>' . esc_html__( 'Return Requests summary', 'woocommerce-jetpack' ) . '</h1>';
			echo '<p><a href="' . esc_url( 'https://booster.io/docs/woocommerce-rule-explanations/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Rule Explanations and Returns Summary guide', 'woocommerce-jetpack' ) . '</a></p>';
			echo '<p>' . esc_html__( 'This read-only sample covers the latest up to 50 return requests by request ID, not full-store totals or the most recently updated requests. Current order flags are sequential reads, not an atomic snapshot. No refund, stock, notification, order or setting is changed.', 'woocommerce-jetpack' ) . '</p>';
			if ( ! is_array( $result ) || 'available' !== $result['state'] ) {
				$messages = array(
					'module_disabled'        => __( 'Return Requests is not enabled for this request.', 'woocommerce-jetpack' ),
					'role_restricted'        => __( 'Your current role is excluded from Return Requests.', 'woocommerce-jetpack' ),
					'dependency_unavailable' => __( 'Return Requests dependencies are unavailable.', 'woocommerce-jetpack' ),
					'policy_unavailable'     => __( 'The current module or role policy could not be verified.', 'woocommerce-jetpack' ),
					'queue_unavailable'      => __( 'The private queue could not be read. Its storage may be unavailable or incompatible.', 'woocommerce-jetpack' ),
					'invalid_queue'          => __( 'The queue returned unsupported data.', 'woocommerce-jetpack' ),
				);
				$reason   = is_array( $result ) && isset( $result['reason'] ) ? $result['reason'] : '';
				echo '<p><strong>' . esc_html__( 'Summary unavailable. Counts are unknown, not zero.', 'woocommerce-jetpack' ) . '</strong></p>';
				if ( isset( $messages[ $reason ] ) ) {
					echo '<p>' . esc_html( $messages[ $reason ] ) . '</p>';
				}
				echo '</div>';
				return;
			}
			echo '<p>' . esc_html__( 'Requests in this sample:', 'woocommerce-jetpack' ) . ' <strong>' . esc_html( $result['sample_size'] ) . '</strong></p>';
			if ( $result['possibly_truncated'] ) {
				echo '<p><strong>' . esc_html__( 'The 50-row limit was reached. There may be older requests; this does not prove that more exist.', 'woocommerce-jetpack' ) . '</strong></p>';
			}
			$labels = array(
				'pending'           => __( 'Pending', 'woocommerce-jetpack' ),
				'approved'          => __( 'Approved', 'woocommerce-jetpack' ),
				'declined'          => __( 'Declined', 'woocommerce-jetpack' ),
				'cancelled'         => __( 'Cancelled', 'woocommerce-jetpack' ),
				'needs_review'      => __( 'Needs review', 'woocommerce-jetpack' ),
				'facts_unavailable' => __( 'Current facts unavailable', 'woocommerce-jetpack' ),
			);
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Sample status', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Requests', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
			foreach ( $labels as $key => $label ) {
				echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $result['counts'][ $key ] ) . '</td></tr>';
			}
			echo '</tbody></table><p>' . esc_html__( 'Unavailable facts are included in Needs review, not an additional disjoint status. Approved requests are decisions only, not proof of a refund or restocking.', 'woocommerce-jetpack' ) . '</p></div>';
		}
	}
endif;

new WCJ_Returns_Summary_Admin();
