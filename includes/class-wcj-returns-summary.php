<?php
/**
 * Booster for WooCommerce: bounded, read-only Returns summary.
 *
 * @package Booster_For_WooCommerce/includes
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCJ_Returns_Summary' ) ) :
	/** Aggregates only the existing queue, without storing or recomputing facts. */
	class WCJ_Returns_Summary {
		/**
		 * Read the latest bounded request page, never full-store totals.
		 *
		 * @param mixed $input Exactly scope=summary.
		 * @return array|WP_Error Safe counts or a fixed authorization/input error.
		 */
		public function get_summary( $input = null ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain least-privilege access.
			if ( get_current_user_id() < 1 || ! current_user_can( 'manage_woocommerce' ) ) {
				return new WP_Error( 'booster_forbidden', __( 'You do not have permission to view the Returns summary.', 'woocommerce-jetpack' ) );
			}
			if ( ! is_array( $input ) || array( 'scope' ) !== array_keys( $input ) || 'summary' !== $input['scope'] ) {
				return new WP_Error( 'booster_invalid_returns_summary_input', __( 'Request exactly scope=summary, without additional fields.', 'woocommerce-jetpack' ) );
			}
			try {
				$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
				$module = isset( $plugin->all_modules['returns'] ) ? $plugin->all_modules['returns'] : null;
				if ( ! $module instanceof WCJ_Returns || ! method_exists( $module, 'is_enabled' )
					|| ! class_exists( 'WCJ_Returns_Service' ) || ! method_exists( 'WCJ_Returns_Service', 'queue' )
					|| ! class_exists( 'WCJ_Returns_Store' ) || ! class_exists( 'WCJ_Returns_Facts' )
					|| ! function_exists( 'wcj_is_module_enabled' ) || ! function_exists( 'wcj_check_modules_by_user_roles' ) ) {
					return $this->response( 'dependency_unavailable' );
				}
				// The generic module helper bypasses role filtering on REST requests.
				$role_policy = wcj_is_module_enabled( 'modules_by_user_roles' );
				if ( ! is_bool( $role_policy ) ) {
					return $this->response( 'policy_unavailable' );
				}
				if ( $role_policy ) {
					if ( isset( $GLOBALS['wcj_modules_by_user_roles_data'] ) ) {
						$cached_policy = $GLOBALS['wcj_modules_by_user_roles_data'];
						if ( ! is_array( $cached_policy ) || ! isset( $cached_policy['role'] ) || ! function_exists( 'wp_get_current_user' ) ) {
							return $this->response( 'policy_unavailable' );
						}
						$current_user  = wp_get_current_user();
						$current_roles = isset( $current_user->roles ) && is_array( $current_user->roles ) ? $current_user->roles : array();
						$current_role  = empty( $current_roles ) ? 'guest' : reset( $current_roles );
						$current_role  = '' === $current_role ? 'guest' : $current_role;
						if ( ! is_string( $current_role ) || $cached_policy['role'] !== $current_role ) {
							return $this->response( 'policy_unavailable' );
						}
					}
					$allowed = wcj_check_modules_by_user_roles( 'returns' );
					if ( ! is_bool( $allowed ) ) {
						return $this->response( 'policy_unavailable' );
					}
					if ( ! $allowed ) {
						return $this->response( 'role_restricted' );
					}
				}
				$enabled = $module->is_enabled();
				if ( true !== $enabled ) {
					return $this->response( false === $enabled ? 'module_disabled' : 'policy_unavailable' );
				}
			} catch ( Throwable $error ) {
				return $this->response( 'policy_unavailable' );
			}
			try {
				$rows = ( new WCJ_Returns_Service() )->queue();
			} catch ( Throwable $error ) {
				return $this->response( 'queue_unavailable' );
			}
			if ( is_wp_error( $rows ) ) {
				return $this->response( 'queue_unavailable' );
			}
			if ( ! is_array( $rows ) || count( $rows ) > 50
				|| ( array() !== $rows && array_keys( $rows ) !== range( 0, count( $rows ) - 1 ) ) ) {
				return $this->response( 'invalid_queue' );
			}
			$counts = array(
				'pending'           => 0,
				'approved'          => 0,
				'declined'          => 0,
				'cancelled'         => 0,
				'needs_review'      => 0,
				'facts_unavailable' => 0,
			);
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['status'], $row['needs_review'], $row['facts_unavailable'] )
					|| ! in_array( $row['status'], array( 'pending', 'approved', 'declined', 'cancelled' ), true )
					|| ! is_bool( $row['needs_review'] ) || ! is_bool( $row['facts_unavailable'] )
					|| ( $row['facts_unavailable'] && ! $row['needs_review'] ) ) {
					return $this->response( 'invalid_queue' );
				}
				++$counts[ $row['status'] ];
				$counts['needs_review']      += $row['needs_review'] ? 1 : 0;
				$counts['facts_unavailable'] += $row['facts_unavailable'] ? 1 : 0;
			}
			return $this->response( 'summary_ready', $counts, count( $rows ) );
		}

		/**
		 * Construct the fixed privacy boundary. Unknown values never become zeros.
		 *
		 * @param string     $reason Internal finite result code.
		 * @param array|null $counts Validated aggregate counts, or unavailable.
		 * @param int|null   $size Actual sampled rows, or unavailable.
		 * @return array
		 */
		private function response( $reason, $counts = null, $size = null ) {
			return array(
				'state'              => 'summary_ready' === $reason ? 'available' : 'unavailable',
				'reason'             => $reason,
				'basis'              => 'latest_request_ids',
				'bounded_sample'     => true,
				'sample_limit'       => 50,
				'sample_size'        => $size,
				'possibly_truncated' => null === $size ? null : 50 === $size,
				'counts'             => $counts,
			);
		}

		/** Fixed metadata only: no options, dependencies or business reads. */
		public function get_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'scope' => array(
						'type' => 'string',
						'enum' => array( 'summary' ),
					),
				),
				'required'             => array( 'scope' ),
				'additionalProperties' => false,
			);
		}

		/** Fixed typed sample output, never order identifiers or stored payloads. */
		public function get_output_schema() {
			$properties = array();
			foreach ( array( 'pending', 'approved', 'declined', 'cancelled', 'needs_review', 'facts_unavailable' ) as $key ) {
				$properties[ $key ] = array(
					'type'    => 'integer',
					'minimum' => 0,
					'maximum' => 50,
				);
			}
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'state'              => array(
						'type' => 'string',
						'enum' => array( 'available', 'unavailable' ),
					),
					'reason'             => array(
						'type' => 'string',
						'enum' => array( 'summary_ready', 'dependency_unavailable', 'policy_unavailable', 'role_restricted', 'module_disabled', 'queue_unavailable', 'invalid_queue' ),
					),
					'basis'              => array(
						'type' => 'string',
						'enum' => array( 'latest_request_ids' ),
					),
					'bounded_sample'     => array(
						'type' => 'boolean',
						'enum' => array( true ),
					),
					'sample_limit'       => array(
						'type' => 'integer',
						'enum' => array( 50 ),
					),
					'sample_size'        => array(
						'type'    => array( 'integer', 'null' ),
						'minimum' => 0,
						'maximum' => 50,
					),
					'possibly_truncated' => array(
						'type'        => array( 'boolean', 'null' ),
						'description' => 'True at the fifty-row limit means possibly more rows, not definitely more.',
					),
					'counts'             => array(
						'type'                 => array( 'object', 'null' ),
						'properties'           => $properties,
						'required'             => array_keys( $properties ),
						'additionalProperties' => false,
						'description'          => 'Latest sampled request counts only, not full-store totals. Unavailable facts are included in needs_review. Null means unavailable, not zero. Current flags are sequential reads, not an atomic store snapshot.',
					),
				),
				'required'             => array( 'state', 'reason', 'basis', 'bounded_sample', 'sample_limit', 'sample_size', 'possibly_truncated', 'counts' ),
			);
		}
	}
endif;
