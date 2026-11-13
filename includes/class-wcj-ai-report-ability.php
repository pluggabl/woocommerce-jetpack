<?php
/**
 * One finite exact-ID read boundary for an already owner-shared report.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Native validation/permissions remain intact; errors never echo inputs. */
final class WCJ_AI_Report_Ability extends WP_Ability {

	/**
	 * One finite error, never existence, payload or internal diagnostics.
	 *
	 * @return WP_Error Fixed error.
	 */
	public static function unavailable() {
		return new WP_Error( 'wcj_ai_report_unavailable', 'Shared receipt unavailable. Ask the owner to inspect retained receipts in WordPress. This tool cannot capture, refresh, list or share reports.' );
	}

	/**
	 * Native adapter checks permission separately before execution.
	 *
	 * @param mixed $input Native input.
	 * @return bool Strict native authorization, without exception details.
	 */
	public function check_permissions( $input = null ) {
		try {
			return true === parent::check_permissions( $input );
		} catch ( Throwable $exception ) {
			return false;
		}
	}

	/**
	 * Preserve native schema/permission validation and normalize failures only.
	 *
	 * @param mixed $input Native input.
	 * @return mixed|WP_Error Validated native output or fixed refusal.
	 */
	public function execute( $input = null ) {
		try {
			$result = parent::execute( $input );
			return is_wp_error( $result ) ? self::unavailable() : $result;
		} catch ( Throwable $exception ) {
			return self::unavailable();
		}
	}

	/**
	 * Exact server-derived journal lookup. No queries, listing or execution.
	 *
	 * @param mixed $input Closed receipt_id object.
	 * @return array|WP_Error Validated exact stored DTO or fixed refusal.
	 */
	public static function read( $input ) {
		try {
			require_once __DIR__ . '/admin/class-wcj-ai-report-service.php';
			return WCJ_AI_Report_Service::read_shared( $input );
		} catch ( Throwable $exception ) {
			return self::unavailable();
		}
	}

	/**
	 * Closed object, all properties required at every level.
	 *
	 * @param array $properties Static schemas.
	 * @return array JSON schema.
	 */
	private static function object_schema( array $properties ) {
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => array_keys( $properties ),
			'additionalProperties' => false,
		);
	}

	/**
	 * One fixed scalar value, not coercible or optional.
	 *
	 * @param mixed $value Static supported scalar.
	 * @return array JSON schema.
	 */
	private static function fixed( $value ) {
		return array(
			'type' => is_bool( $value ) ? 'boolean' : ( is_int( $value ) ? 'integer' : 'string' ),
			'enum' => array( $value ),
		);
	}

	/**
	 * Explicit null differs from an empty observed result or zero.
	 *
	 * @param array $schema Non-null schema.
	 * @return array JSON schema.
	 */
	private static function nullable( array $schema ) {
		return array( 'oneOf' => array( array( 'type' => 'null' ), $schema ) );
	}

	/**
	 * Only one opaque receipt locator; no browser-supplied snapshot or authority.
	 *
	 * @return array JSON schema.
	 */
	public static function input_schema() {
		return self::object_schema(
			array(
				'receipt_id' => array(
					'type'      => 'string',
					'pattern'   => '^[a-f0-9]{64}$',
					'minLength' => 64,
					'maxLength' => 64,
				),
			)
		);
	}

	/**
	 * Complete bounded DTO shape; service additionally validates semantic equality,
	 * canonical bytes, times, identities and the 32768-byte whole-envelope ceiling.
	 *
	 * @return array Closed schema, no open nested objects or arbitrary diagnostics.
	 */
	public static function output_schema() {
		$instant  = array(
			'type'      => 'string',
			'pattern'   => '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$',
			'minLength' => 20,
			'maxLength' => 20,
		);
		$date     = array(
			'type'      => 'string',
			'pattern'   => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
			'minLength' => 10,
			'maxLength' => 10,
		);
		$reason   = self::nullable(
			array(
				'type' => 'string',
				'enum' => array( 'freshness_unverified', 'query_unavailable', 'stale_or_missing_entity', 'unsupported_currency', 'unsupported_persisted_facts', 'unsupported_amount', 'soft_time_limit', 'stale_membership_or_context', 'observation_unavailable' ),
			)
		);
		$calendar = self::object_schema(
			array(
				'schema_version' => self::fixed( 1 ),
				'basis'          => self::fixed( 'paid_date_current_status_cohort' ),
				'interval'       => self::fixed( 'start_inclusive_end_exclusive' ),
				'timezone'       => array(
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 64,
				),
				'timezone_kind'  => array(
					'type' => 'string',
					'enum' => array( 'named', 'fixed_offset' ),
				),
				'cutoff_utc'     => $instant,
				'non_atomic'     => self::fixed( true ),
				'periods'        => array(
					'type'     => 'array',
					'minItems' => 2,
					'maxItems' => 2,
					'items'    => self::object_schema(
						array(
							'local_start'     => $date,
							'local_end'       => $date,
							'utc_start'       => $instant,
							'utc_end'         => $instant,
							'local_days'      => self::fixed( 7 ),
							'elapsed_seconds' => array(
								'type'    => 'integer',
								'minimum' => 1,
								'maximum' => 1209600,
							),
						)
					),
				),
			)
		);
		$group    = self::object_schema(
			array(
				'period'   => array(
					'type' => 'integer',
					'enum' => array( 0, 1 ),
				),
				'currency' => array(
					'type'      => 'string',
					'pattern'   => '^[A-Z]{3}$',
					'minLength' => 3,
					'maxLength' => 3,
				),
				'count'    => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 25,
				),
				'amount'   => array(
					'type'      => 'string',
					'pattern'   => '^(0|[1-9][0-9]{0,12})(\.[0-9]{0,5}[1-9])?$',
					'maxLength' => 20,
				),
			)
		);
		$streams  = array();
		foreach ( array(
			'paid_cohort_order_totals' => 'recorded_order_total_including_tax_shipping_fees_before_refunds',
			'recorded_refund_events'   => 'recorded_refund_amount_not_return_requests',
		) as $name => $basis ) {
			$streams[ $name ] = self::object_schema(
				array(
					'status'       => array(
						'type' => 'string',
						'enum' => array( 'unavailable', 'observed_subset' ),
					),
					'amount_basis' => self::fixed( $basis ),
					'groups'       => self::nullable(
						array(
							'type'     => 'array',
							'maxItems' => 25,
							'items'    => $group,
						)
					),
					'cap_reached'  => self::nullable( array( 'type' => 'boolean' ) ),
					'reason'       => $reason,
				)
			);
		}
		$snapshot = self::object_schema(
			array(
				'schema_version'         => self::fixed( 1 ),
				'overall_complete'       => self::fixed( false ),
				'non_atomic'             => self::fixed( true ),
				'membership_complete'    => self::fixed( false ),
				'query_basis'            => self::fixed( 'native_filtered_query_subset' ),
				'resumable'              => self::fixed( false ),
				'cohort_refunds'         => array( 'type' => 'null' ),
				'product_breakdown'      => array( 'type' => 'null' ),
				'return_requests'        => array( 'type' => 'null' ),
				'net_revenue'            => array( 'type' => 'null' ),
				'unavailable_components' => array(
					'type' => 'array',
					'enum' => array( array( 'cohort_refund_allocation', 'product_variation_breakdown', 'return_requests', 'net_revenue' ) ),
				),
				'periods'                => self::nullable( $calendar ),
				'storage'                => self::nullable( self::fixed( 'legacy_native_uncached' ) ),
				'streams'                => self::object_schema( $streams ),
				'reason'                 => $reason,
				'observed_until_utc'     => $instant,
				'limits'                 => self::object_schema(
					array(
						'ids_per_stream'          => self::fixed( 25 ),
						'wc_get_orders_calls_max' => self::fixed( 4 ),
						'hydrations_max'          => self::fixed( 100 ),
						'soft_seconds'            => self::fixed( 2 ),
						'native_operation_duration_bounded' => self::fixed( false ),
					)
				),
			)
		);
		return self::object_schema(
			array(
				'schema_version'         => self::fixed( 2 ),
				'kind'                   => self::fixed( 'revenue_observation' ),
				'captured_closed_at_utc' => $instant,
				'review_created_at_utc'  => $instant,
				'expires_at_utc'         => $instant,
				'source_schema'          => self::fixed( 'wcj.ai.revenue_observation.v1' ),
				'snapshot'               => $snapshot,
			)
		);
	}
}
