<?php
/**
 * Booster for WooCommerce - bounded, explainable Order Health service.
 *
 * @version 8.4.0
 * @since   8.4.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Order_Health_Service' ) ) :
	/**
	 * Finds orders that may need merchant attention without changing orders.
	 *
	 * All order access goes through WooCommerce CRUD/query APIs so the same code
	 * runs against HPOS and legacy post storage. Queries are deliberately capped.
	 */
	class WCJ_Order_Health_Service {

		/** Maximum records read from one status family. */
		const STATUS_QUERY_LIMIT = 50;

		/** Maximum records rendered after filters are applied. */
		const DISPLAY_LIMIT = 50;

		/** Maximum records rendered by the light Free/Plus experience. */
		const LIGHT_DISPLAY_LIMIT = 20;

		/** Maximum records read across the five bounded status families. */
		const MAX_QUERY_ORDERS = 250;

		/** Maximum records read across the four light-experience status families. */
		const LIGHT_MAX_QUERY_ORDERS = 200;

		/** Fixed age bucket keys returned by the UI and Ability. */
		const AGE_BUCKETS = array( 'under_1_day', 'from_1_to_3_days', 'from_4_to_7_days', 'over_7_days' );

		/** Fixed likely-cause keys. */
		const CAUSES = array( 'payment', 'fulfillment', 'configuration', 'incomplete_workflow' );

		/** Fixed explainable reason keys. */
		const REASONS = array( 'payment_pending', 'payment_failed', 'fulfillment_delayed', 'workflow_incomplete', 'configuration_review', 'partial_refund_open' );

		/**
		 * Returns aggregate and merchant-facing rows for the Order Health dashboard.
		 *
		 * @param array $filters Optional status, cause, and age filters.
		 * @return array
		 */
		public function get_dashboard_data( $filters = array() ) {
			$filters       = $this->normalize_filters( $filters );
			$query_result  = $this->get_candidate_orders();
			$display_limit = $this->get_display_limit();
			$health_orders = array();

			foreach ( $query_result['orders'] as $order ) {
				$health = $this->classify_order( $order );
				if ( ! empty( $health ) ) {
					$health_orders[] = $health;
				}
			}

			usort(
				$health_orders,
				function ( $first, $second ) {
					return $second['age_seconds'] - $first['age_seconds'];
				}
			);

			$summary  = $this->summarize_orders( $health_orders );
			$filtered = array_values(
				array_filter(
					$health_orders,
					function ( $order ) use ( $filters ) {
						return $this->matches_filters( $order, $filters );
					}
				)
			);

			return array(
				'generated_at_gmt' => gmdate( 'c' ),
				'storage_mode'     => $this->get_storage_mode(),
				'filters'          => $filters,
				'query'            => array(
					'query_limit'    => $this->get_query_limit(),
					'candidate_count' => count( $query_result['orders'] ),
					'attention_count' => count( $health_orders ),
					'has_more'        => $query_result['has_more'],
					'status_families' => $query_result['status_families'],
				),
				'summary'          => $summary,
				'filtered_count'   => count( $filtered ),
				'display_limit'     => $display_limit,
				'display_truncated' => count( $filtered ) > $display_limit,
				'orders'           => array_slice( $filtered, 0, $display_limit ),
			);
		}

		/** Returns whether the active package receives the full Elite experience. */
		public function is_full_experience() {
			return 'elite' === $this->get_tier();
		}

		/** Returns the tier-appropriate bounded query ceiling. */
		public function get_query_limit() {
			return $this->is_full_experience() ? self::MAX_QUERY_ORDERS : self::LIGHT_MAX_QUERY_ORDERS;
		}

		/** Returns the tier-appropriate dashboard row ceiling. */
		public function get_display_limit() {
			return $this->is_full_experience() ? self::DISPLAY_LIMIT : self::LIGHT_DISPLAY_LIMIT;
		}

		/**
		 * Returns the privacy-safe aggregate used by booster/order-health-summary.
		 *
		 * @return array|WP_Error
		 */
		public function get_order_health_summary() {
			if ( ! $this->is_full_experience() ) {
				return new WP_Error( 'booster_elite_required', __( 'The Order Health summary Ability is available in Booster Elite.', 'woocommerce-jetpack' ) );
			}
			$data    = $this->get_dashboard_data();
			$summary = array(
				'tier'             => $this->get_tier(),
				'generated_at_gmt' => $data['generated_at_gmt'],
				'storage_mode'     => $data['storage_mode'],
				'bounded_query'    => array(
					'query_limit'     => $data['query']['query_limit'],
					'candidate_count' => $data['query']['candidate_count'],
					'attention_count' => $data['query']['attention_count'],
					'has_more'        => $data['query']['has_more'],
				),
				'age_buckets'      => $data['summary']['age_buckets'],
				'cause_counts'     => $data['summary']['cause_counts'],
				'reason_counts'    => $data['summary']['reason_counts'],
				'oldest_age_days'  => $data['summary']['oldest_age_days'],
			);
			$summary['morning_briefing'] = $this->get_morning_store_briefing( $summary );

			return $summary;
		}

		/**
		 * Combines aggregate Order Health, compatibility, and background-job state.
		 *
		 * The briefing intentionally contains no order IDs, customer fields, action
		 * arguments, raw options, or autonomous recommendations.
		 *
		 * @param array $order_summary Optional already-computed order summary.
		 * @return array|WP_Error
		 */
		public function get_morning_store_briefing( $order_summary = array() ) {
			if ( ! $this->is_full_experience() ) {
				return new WP_Error( 'booster_elite_required', __( 'Morning Store Briefing is available in Booster Elite.', 'woocommerce-jetpack' ) );
			}
			if ( empty( $order_summary ) ) {
				$order_summary = $this->get_order_health_summary_without_briefing();
			}

			$compatibility_warning_count = 0;
			$jobs                        = array(
				'counts'           => array(
					'overdue' => 0,
					'failed'  => 0,
				),
				'diagnostic_codes' => array( 'unavailable' ),
			);
			if ( class_exists( 'WCJ_Status_Service' ) ) {
				$status_service = new WCJ_Status_Service();
				$compatibility  = $status_service->get_compatibility_status();
				$jobs           = $status_service->get_background_jobs_status();
				foreach ( $compatibility['modules'] as $module ) {
					if ( empty( $module['enabled'] ) ) {
						continue;
					}
					if ( 'partial' === $module['hpos'] || in_array( $module['checkout_blocks'], array( 'partial', 'classic-only' ), true ) ) {
						++$compatibility_warning_count;
					}
				}
			}

			$attention_count = isset( $order_summary['bounded_query']['attention_count'] ) ? (int) $order_summary['bounded_query']['attention_count'] : 0;
			$truncated       = ! empty( $order_summary['bounded_query']['has_more'] );
			$overdue         = isset( $jobs['counts']['overdue'] ) ? (int) $jobs['counts']['overdue'] : 0;
			$failed          = isset( $jobs['counts']['failed'] ) ? (int) $jobs['counts']['failed'] : 0;
			$guidance        = array();
			if ( $attention_count > 0 ) {
				$guidance[] = 'review-order-health';
			}
			if ( $compatibility_warning_count > 0 ) {
				$guidance[] = 'review-compatibility-status';
			}
			if ( $overdue > 0 || $failed > 0 ) {
				$guidance[] = 'review-background-jobs';
			}
			if ( empty( $guidance ) ) {
				$guidance[] = 'no-aggregate-issue-detected';
			}

			return array(
				'privacy'         => 'aggregate-only',
				'order_health'    => array(
					'state'           => $attention_count > 0 ? 'action-needed' : 'clear',
					'attention_count' => $attention_count,
					'truncated'       => $truncated,
				),
				'compatibility'   => array(
					'state'         => $compatibility_warning_count > 0 ? 'review' : 'clear',
					'warning_count' => $compatibility_warning_count,
				),
				'background_jobs' => array(
					'state'            => ( $overdue > 0 || $failed > 0 ) ? 'review' : 'clear',
					'overdue'          => $overdue,
					'failed'           => $failed,
					'diagnostic_codes' => array_slice( array_values( (array) $jobs['diagnostic_codes'] ), 0, 4 ),
				),
				'guidance_codes'  => $guidance,
			);
		}

		/**
		 * Classifies one current order state. Returns an empty array when healthy.
		 *
		 * @param WC_Order $order WooCommerce order object.
		 * @return array
		 */
		public function classify_order( $order ) {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return array();
			}

			$status  = sanitize_key( $order->get_status() );
			$is_full = $this->is_full_experience();
			if ( in_array( $status, array( 'completed', 'cancelled', 'refunded', 'trash', 'auto-draft', 'checkout-draft' ), true ) ) {
				return array();
			}

			$total          = abs( (float) $order->get_total() );
			$total_refunded = abs( (float) $order->get_total_refunded() );
			$fully_refunded = $total_refunded > 0 && $total_refunded + 0.00001 >= $total;
			if ( $fully_refunded ) {
				return array();
			}

			$waiting       = $this->get_waiting_since( $order, $status );
			$age_seconds   = max( 0, time() - $waiting['timestamp'] );
			$partial       = $total_refunded > 0 && ! $fully_refunded;
			$reason_codes  = array();
			$cause         = '';
			$safe_action   = '';
			$reason_text   = '';

			if ( 'failed' === $status ) {
				$cause        = 'payment';
				$reason_codes = array( 'payment_failed' );
				$reason_text  = __( 'Payment failed and the order has not moved to a completed payment state.', 'woocommerce-jetpack' );
				$safe_action  = __( 'Review the payment result and order notes before asking the customer to retry.', 'woocommerce-jetpack' );
			} elseif ( 'pending' === $status && $age_seconds >= 2 * HOUR_IN_SECONDS ) {
				$cause        = 'payment';
				$reason_codes = array( 'payment_pending' );
				$reason_text  = __( 'Payment has remained pending for at least two hours.', 'woocommerce-jetpack' );
				$safe_action  = __( 'Confirm the gateway result and order notes before changing the status.', 'woocommerce-jetpack' );
			} elseif ( 'processing' === $status && $age_seconds >= 2 * DAY_IN_SECONDS ) {
				$cause        = 'fulfillment';
				$reason_codes = array( 'fulfillment_delayed' );
				$reason_text  = __( 'A paid order has remained in processing for at least two days.', 'woocommerce-jetpack' );
				$safe_action  = __( 'Review fulfillment progress, shipment details, and order notes.', 'woocommerce-jetpack' );
			} elseif ( 'on-hold' === $status && $age_seconds >= DAY_IN_SECONDS ) {
				$cause        = 'incomplete_workflow';
				$reason_codes = array( 'workflow_incomplete' );
				$reason_text  = __( 'The order has remained on hold for at least one day.', 'woocommerce-jetpack' );
				$safe_action  = __( 'Review the hold reason and order notes, then choose the appropriate merchant-controlled next step.', 'woocommerce-jetpack' );
			} elseif ( $is_full && ! in_array( $status, array( 'pending', 'processing', 'on-hold', 'failed' ), true ) && $age_seconds >= 3 * DAY_IN_SECONDS ) {
				$cause        = 'configuration';
				$reason_codes = array( 'configuration_review' );
				$reason_text  = __( 'A non-standard active status has not advanced for at least three days.', 'woocommerce-jetpack' );
				$safe_action  = __( 'Review the workflow or integration that owns this status before changing the order.', 'woocommerce-jetpack' );
			}

			if ( $partial && $is_full ) {
				$reason_codes[] = 'partial_refund_open';
				if ( '' === $cause ) {
					$cause       = 'incomplete_workflow';
					$reason_text = __( 'A partially refunded order remains in an active workflow status.', 'woocommerce-jetpack' );
					$safe_action = __( 'Confirm the remaining fulfillment and refund intent before changing the status or issuing another refund.', 'woocommerce-jetpack' );
				} else {
					$reason_text .= ' ' . __( 'The order is also partially refunded.', 'woocommerce-jetpack' );
				}
			}

			if ( '' === $cause ) {
				return array();
			}

			return array(
				'order_id'        => (int) $order->get_id(),
				'order_number'    => (string) $order->get_order_number(),
				'edit_url'        => $order->get_edit_order_url(),
				'status'          => $status,
				'status_label'    => function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : ucfirst( str_replace( '-', ' ', $status ) ),
				'waiting_since_gmt' => gmdate( 'c', $waiting['timestamp'] ),
				'waiting_basis'   => $waiting['basis'],
				'age_seconds'     => $age_seconds,
				'age_days'        => (int) floor( $age_seconds / DAY_IN_SECONDS ),
				'age_bucket'      => $this->get_age_bucket( $age_seconds ),
				'cause'           => $cause,
				'cause_label'     => $this->get_cause_label( $cause ),
				'reason_codes'    => array_values( array_unique( $reason_codes ) ),
				'reason_text'     => $reason_text,
				'safe_action'     => $safe_action,
				'refund_state'    => $partial ? 'partial' : 'none',
			);
		}

		/** Returns a summary without recursively building a briefing. */
		private function get_order_health_summary_without_briefing() {
			$data = $this->get_dashboard_data();
			return array(
				'bounded_query' => array(
					'attention_count' => $data['query']['attention_count'],
					'has_more'        => $data['query']['has_more'],
				),
			);
		}

		/**
		 * Executes a small fixed set of capped WooCommerce order queries.
		 *
		 * @return array
		 */
		private function get_candidate_orders() {
			if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_order_statuses' ) ) {
				return array(
					'orders'          => array(),
					'has_more'        => false,
					'status_families' => array(),
				);
			}

			$registered = array_keys( wc_get_order_statuses() );
			$registered = array_map(
				function ( $status ) {
					return 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
				},
				$registered
			);
			$terminal   = array( 'completed', 'cancelled', 'refunded', 'trash', 'auto-draft', 'checkout-draft' );
			$core       = array( 'pending', 'on-hold', 'processing', 'failed' );
			$custom     = array_values( array_diff( $registered, $terminal, $core ) );
			$families   = array(
				'pending'    => array( 'pending' ),
				'on-hold'    => array( 'on-hold' ),
				'processing' => array( 'processing' ),
				'failed'     => array( 'failed' ),
			);
			if ( $this->is_full_experience() && ! empty( $custom ) ) {
				$families['custom'] = $custom;
			}

			$orders          = array();
			$seen            = array();
			$has_more        = false;
			$status_families = array();
			foreach ( $families as $family => $statuses ) {
				$found = wc_get_orders(
					array(
						'status'  => $statuses,
						'limit'   => self::STATUS_QUERY_LIMIT + 1,
						'orderby' => 'date',
						'order'   => 'ASC',
						'return'  => 'objects',
					)
				);
				$found = is_array( $found ) ? $found : array();
				if ( count( $found ) > self::STATUS_QUERY_LIMIT ) {
					$has_more = true;
				}
				$status_families[ $family ] = min( count( $found ), self::STATUS_QUERY_LIMIT );
				foreach ( array_slice( $found, 0, self::STATUS_QUERY_LIMIT ) as $order ) {
					$order_id = is_a( $order, 'WC_Order' ) ? (int) $order->get_id() : 0;
					if ( $order_id > 0 && ! isset( $seen[ $order_id ] ) ) {
						$seen[ $order_id ] = true;
						$orders[]          = $order;
					}
				}
			}

			return array(
				'orders'          => $orders,
				'has_more'        => $has_more,
				'status_families' => $status_families,
			);
		}

		/** Builds fixed aggregate counters. */
		private function summarize_orders( $orders ) {
			$age_buckets   = array_fill_keys( self::AGE_BUCKETS, 0 );
			$cause_counts  = array_fill_keys( self::CAUSES, 0 );
			$reason_counts = array_fill_keys( self::REASONS, 0 );
			$oldest        = 0;
			foreach ( $orders as $order ) {
				++$age_buckets[ $order['age_bucket'] ];
				++$cause_counts[ $order['cause'] ];
				foreach ( $order['reason_codes'] as $reason ) {
					if ( isset( $reason_counts[ $reason ] ) ) {
						++$reason_counts[ $reason ];
					}
				}
				$oldest = max( $oldest, $order['age_days'] );
			}
			return array(
				'age_buckets'     => $age_buckets,
				'cause_counts'    => $cause_counts,
				'reason_counts'   => $reason_counts,
				'oldest_age_days' => $oldest,
			);
		}

		/** Returns a stable read-only wait basis with HPOS/legacy parity. */
		private function get_waiting_since( $order, $status ) {
			$date  = $order->get_date_created();
			$basis = 'order_created';
			if ( 'processing' === $status && $order->get_date_paid() ) {
				$date  = $order->get_date_paid();
				$basis = 'payment_received';
			}
			if ( ! $date ) {
				$basis = 'unknown';
			}
			return array(
				'timestamp' => $date ? (int) $date->getTimestamp() : time(),
				'basis'     => $basis,
			);
		}

		/** Normalizes dashboard filters to fixed public values. */
		private function normalize_filters( $filters ) {
			if ( ! $this->is_full_experience() ) {
				return array(
					'status' => 'all',
					'cause'  => 'all',
					'age'    => 'all',
				);
			}
			$status = isset( $filters['status'] ) ? sanitize_key( $filters['status'] ) : 'all';
			$cause  = isset( $filters['cause'] ) ? sanitize_key( $filters['cause'] ) : 'all';
			$age    = isset( $filters['age'] ) ? sanitize_key( $filters['age'] ) : 'all';
			return array(
				'status' => 'all' === $status || array_key_exists( 'wc-' . $status, (array) wc_get_order_statuses() ) ? $status : 'all',
				'cause'  => 'all' === $cause || in_array( $cause, self::CAUSES, true ) ? $cause : 'all',
				'age'    => 'all' === $age || in_array( $age, self::AGE_BUCKETS, true ) ? $age : 'all',
			);
		}

		/** Returns whether one health row matches all selected filters. */
		private function matches_filters( $order, $filters ) {
			return ( 'all' === $filters['status'] || $filters['status'] === $order['status'] )
				&& ( 'all' === $filters['cause'] || $filters['cause'] === $order['cause'] )
				&& ( 'all' === $filters['age'] || $filters['age'] === $order['age_bucket'] );
		}

		/** Returns a fixed age bucket. */
		private function get_age_bucket( $age_seconds ) {
			if ( $age_seconds < DAY_IN_SECONDS ) {
				return 'under_1_day';
			}
			if ( $age_seconds < 4 * DAY_IN_SECONDS ) {
				return 'from_1_to_3_days';
			}
			if ( $age_seconds < 8 * DAY_IN_SECONDS ) {
				return 'from_4_to_7_days';
			}
			return 'over_7_days';
		}

		/** Human-readable fixed cause labels. */
		private function get_cause_label( $cause ) {
			$labels = array(
				'payment'             => __( 'Payment', 'woocommerce-jetpack' ),
				'fulfillment'         => __( 'Fulfillment', 'woocommerce-jetpack' ),
				'configuration'       => __( 'Configuration', 'woocommerce-jetpack' ),
				'incomplete_workflow' => __( 'Incomplete workflow', 'woocommerce-jetpack' ),
			);
			return isset( $labels[ $cause ] ) ? $labels[ $cause ] : $cause;
		}

		/** Returns HPOS or legacy without reading either storage implementation directly. */
		private function get_storage_mode() {
			return function_exists( 'wcj_is_hpos_enabled' ) && wcj_is_hpos_enabled() ? 'hpos' : 'legacy';
		}

		/** Returns the installed Booster tier. */
		public function get_tier() {
			if ( class_exists( 'WCJ_Status_Service' ) ) {
				return ( new WCJ_Status_Service() )->get_tier();
			}
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ? WCJ_FREE_PLUGIN_FILE : '' );
			$basename    = basename( $plugin_file );
			return false !== strpos( $basename, 'elite' ) ? 'elite' : ( false !== strpos( $basename, 'plus' ) ? 'plus' : 'free' );
		}
	}
endif;
