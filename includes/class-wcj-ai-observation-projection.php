<?php
/**
 * Pure, closed observation projections; no hook, route, receipt or authorization.
 *
 * A valid shape is NOT proof of origin, owner consent, freshness or permission to
 * share. Nothing includes this file at runtime yet. Never pass untrusted browser
 * data to a future sharing operation without independently authenticated origin.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-price-value.php';
require_once __DIR__ . '/class-wcj-ai-report-values.php';

/** Reconstruct bounded payloads from already closed, supported source DTOs. */
final class WCJ_AI_Observation_Projection {

	/**
	 * Reject without echoing any input or underlying exception.
	 *
	 * @throws InvalidArgumentException Always.
	 */
	private static function reject() {
		throw new InvalidArgumentException( 'Unsupported observation projection.' );
	}

	/**
	 * Require an exact key set before touching values, without recursive traversal.
	 *
	 * @param mixed $value Record.
	 * @param array $keys Required keys.
	 */
	private static function keys( $value, array $keys ) {
		if ( ! is_array( $value ) || count( $value ) !== count( $keys ) || array_diff( $keys, array_keys( $value ) ) ) {
			self::reject();
		}
	}

	/**
	 * Strictly match a bounded trusted template, never converting source objects.
	 *
	 * @param mixed $value Input.
	 * @param mixed $expected Finite trusted shape.
	 */
	private static function same( $value, $expected ) {
		if ( is_array( $expected ) ) {
			self::keys( $value, array_keys( $expected ) );
			foreach ( $expected as $key => $item ) {
				self::same( $value[ $key ], $item );
			}
		} elseif ( $value !== $expected ) {
			self::reject();
		}
	}

	/**
	 * Require a bounded ordered list; do not visit rejected entries.
	 *
	 * @param mixed $value Input list.
	 * @param int   $maximum Maximum length.
	 */
	private static function sequence( $value, $maximum ) {
		if ( ! is_array( $value ) || count( $value ) > $maximum || array_values( $value ) !== $value ) {
			self::reject();
		}
	}

	/**
	 * Validate one exact UTC instant, with no current clock read.
	 *
	 * @param mixed $value UTC text.
	 * @return int Unix timestamp.
	 */
	private static function instant( $value ) {
		if ( ! is_string( $value ) || 20 !== strlen( $value ) || 1 !== preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value ) ) {
			self::reject();
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $value, new DateTimeZone( 'UTC' ) );
		if ( false === $date || $date->format( 'Y-m-d\TH:i:s\Z' ) !== $value ) {
			self::reject();
		}
		return $date->getTimestamp();
	}

	/**
	 * Bound the constructed payload only, never encode arbitrary source objects.
	 * The local producer permits 65536 bytes; allow 1024 bytes for the proven 573-byte maximum output overhead.
	 * A future smaller shared-receipt limit belongs to that separate consent flow.
	 *
	 * @param array $payload Reconstructed validated fields.
	 * @return array Same payload.
	 */
	private static function bounded( array $payload ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Pure helper; only already validated scalars and bounded arrays, no WordPress callbacks.
		$json = json_encode( $payload, JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) || strlen( $json ) > 66560 ) {
			self::reject();
		}
		return $payload;
	}

	/**
	 * Allow only native finite failure reasons.
	 *
	 * @param mixed $value Reason.
	 * @return string Reason.
	 */
	private static function reason( $value ) {
		if ( ! in_array( $value, array( 'freshness_unverified', 'query_unavailable', 'stale_or_missing_entity', 'unsupported_currency', 'unsupported_persisted_facts', 'unsupported_amount', 'soft_time_limit', 'stale_membership_or_context', 'observation_unavailable' ), true ) ) {
			self::reject();
		}
		return $value;
	}

	/**
	 * Validate aggregate magnitude separately from the narrower source-input parser.
	 *
	 * @param mixed $value Canonical nonnegative aggregate text.
	 * @return string Exact input, no float conversion or money arithmetic.
	 */
	private static function aggregate( $value ) {
		if ( ! is_string( $value ) || strlen( $value ) > 20 || 1 !== preg_match( '/^(0|[1-9][0-9]{0,12})(?:\.([0-9]{0,5}[1-9]))?$/D', $value, $parts ) ) {
			self::reject();
		}
		$scaled = ltrim( $parts[1] . str_pad( $parts[2] ?? '', 6, '0' ), '0' );
		if ( strlen( $scaled ) > 19 || ( 19 === strlen( $scaled ) && strcmp( $scaled, '9223372036854775807' ) > 0 ) ) {
			self::reject();
		}
		return $value;
	}

	/**
	 * Project one existing financial observation, never recollecting facts.
	 *
	 * @param mixed $source Closed WCJ_AI_Revenue_Observation::observe() result.
	 * @return array Strict payload; not a receipt or authenticated evidence.
	 */
	public static function revenue( $source ) {
		$fixed  = array(
			'schema'                 => 1,
			'overall_complete'       => false,
			'non_atomic'             => true,
			'membership_complete'    => false,
			'query_basis'            => 'native_filtered_query_subset',
			'resumable'              => false,
			'cohort_refunds'         => null,
			'product_breakdown'      => null,
			'return_requests'        => null,
			'net_revenue'            => null,
			'unavailable_components' => array( 'cohort_refund_allocation', 'product_variation_breakdown', 'return_requests', 'net_revenue' ),
			'limits'                 => array(
				'ids_per_stream'                    => 25,
				'queries'                           => 4,
				'hydrations'                        => 100,
				'soft_seconds'                      => 2,
				'native_operation_duration_bounded' => false,
			),
		);
		$failed = is_array( $source ) && array_key_exists( 'reason', $source );
		self::keys( $source, array_merge( array_keys( $fixed ), array( 'periods', 'storage', 'streams', 'observed_until_utc' ), $failed ? array( 'reason' ) : array() ) );
		foreach ( $fixed as $key => $value ) {
			self::same( $source[ $key ], $value );
		}
		$finished = self::instant( $source['observed_until_utc'] );
		if ( ! in_array( $source['storage'], array( null, 'legacy_native_uncached' ), true ) ) {
			self::reject();
		}
		$periods = null;
		if ( null !== $source['periods'] ) {
			self::keys( $source['periods'], array( 'schema_version', 'basis', 'interval', 'timezone', 'timezone_kind', 'cutoff_utc', 'non_atomic', 'periods' ) );
			$cutoff = self::instant( $source['periods']['cutoff_utc'] );
			try {
				$periods = WCJ_AI_Report_Values::completed_week_pair( $cutoff, $source['periods']['timezone'] );
			} catch ( Throwable $exception ) {
				self::reject();
			}
			self::same( $source['periods'], $periods );
			if ( $finished < $cutoff || 'legacy_native_uncached' !== $source['storage'] ) {
				self::reject();
			}
		}
		$reason = $failed ? self::reason( $source['reason'] ) : null;
		$names  = array(
			'paid_cohort_order_totals' => 'recorded_order_total_including_tax_shipping_fees_before_refunds',
			'recorded_refund_events'   => 'recorded_refund_amount_not_return_requests',
		);
		if ( $failed ) {
			self::same( $source['streams'], array() );
		} else {
			if ( null === $periods ) {
				self::reject();
			}
			self::keys( $source['streams'], array_keys( $names ) );
		}
		$streams = array();
		foreach ( $names as $name => $basis ) {
			$stream = array(
				'status'       => 'unavailable',
				'amount_basis' => $basis,
				'groups'       => null,
				'cap_reached'  => null,
				'reason'       => $reason,
			);
			if ( ! $failed ) {
				$raw = $source['streams'][ $name ];
				if ( ! is_array( $raw ) || ! isset( $raw['status'] ) || ! in_array( $raw['status'], array( 'unavailable', 'observed_subset' ), true ) ) {
					self::reject();
				}
				$observed = 'observed_subset' === $raw['status'];
				self::keys( $raw, array( 'status', 'amount_basis', 'groups', $observed ? 'cap_reached' : 'reason' ) );
				self::same( $raw['amount_basis'], $basis );
				if ( ! $observed ) {
					self::same( $raw['groups'], null );
					$stream['reason'] = self::reason( $raw['reason'] );
				} else {
					if ( ! is_bool( $raw['cap_reached'] ) ) {
						self::reject();
					}
					self::sequence( $raw['groups'], 25 );
					$groups = array();
					$seen   = array();
					$total  = 0;
					$last   = null;
					foreach ( $raw['groups'] as $group ) {
						self::keys( $group, array( 'period', 'currency', 'count', 'amount' ) );
						if ( ! in_array( $group['period'], array( 0, 1 ), true ) || ! is_string( $group['currency'] ) || 1 !== preg_match( '/^[A-Z]{3}$/D', $group['currency'] ) || ! is_int( $group['count'] ) || $group['count'] < 1 || $group['count'] > 25 ) {
							self::reject();
						}
						$key    = $group['period'] . ':' . $group['currency'];
						$total += $group['count'];
						if ( isset( $seen[ $key ] ) || $total > 25 || ( null !== $last && strcmp( $last, $key ) >= 0 ) ) {
							self::reject();
						}
						$last         = $key;
						$seen[ $key ] = true;
						$groups[]     = array(
							'period'   => $group['period'],
							'currency' => $group['currency'],
							'count'    => $group['count'],
							'amount'   => self::aggregate( $group['amount'] ),
						);
					}
					if ( $raw['cap_reached'] && 25 !== $total ) {
						self::reject();
					}
					$stream = array(
						'status'       => 'observed_subset',
						'amount_basis' => $basis,
						'groups'       => $groups,
						'cap_reached'  => $raw['cap_reached'],
						'reason'       => null,
					);
				}
			}
			$streams[ $name ] = $stream;
		}
		unset( $fixed['schema'], $fixed['limits'] );
		return self::bounded(
			array_merge(
				array( 'schema_version' => 1 ),
				$fixed,
				array(
					'periods'            => $periods,
					'storage'            => $source['storage'],
					'streams'            => $streams,
					'reason'             => $reason,
					'observed_until_utc' => $source['observed_until_utc'],
					'limits'             => array(
						'ids_per_stream'          => 25,
						'wc_get_orders_calls_max' => 4,
						'hydrations_max'          => 100,
						'soft_seconds'            => 2,
						'native_operation_duration_bounded' => false,
					),
				)
			)
		);
	}

	/**
	 * Validate an already projected scalar; never coerce unsupported source data.
	 *
	 * @param mixed $value Scalar record.
	 * @return array Reconstructed scalar.
	 */
	private static function scalar( $value ) {
		if ( ! is_array( $value ) || ! isset( $value['kind'] ) ) {
			self::reject();
		}
		if ( 'unsupported' === $value['kind'] ) {
			$out = array(
				'kind'   => 'unsupported',
				'reason' => 'unsupported_numeric_value',
			);
		} elseif ( 'empty_price' === $value['kind'] ) {
			$out = array(
				'kind'        => 'empty_price',
				'native_type' => 'string',
			);
		} else {
			self::keys( $value, array( 'kind', 'native_type', 'encoding', 'value' ) );
			if ( ! is_string( $value['value'] ) || strlen( $value['value'] ) > 64 ) {
				self::reject();
			}
			$text = $value['value'];
			if ( 'string' === $value['native_type'] ) {
				$out = WCJ_AI_Price_Value::project( $text );
			} elseif ( 'integer' === $value['native_type'] && 1 === preg_match( '/^-?(0|[1-9][0-9]{0,9})$/D', $text ) ) {
				$out = WCJ_AI_Price_Value::project( (int) $text );
			} elseif ( 'float' === $value['native_type'] && strlen( $text ) <= 32 && 1 === preg_match( '/^-?[0-9]+(?:\.[0-9]+)?(?:e[+-]?[0-9]+)?$/D', $text ) ) {
				$out = WCJ_AI_Price_Value::project( (float) $text );
			} else {
				self::reject();
			}
		}
		self::same( $value, $out );
		return $out;
	}

	/**
	 * Validate a source-local alias, never a raw role slug.
	 *
	 * @param mixed $value Alias.
	 * @return string|null Alias.
	 */
	private static function alias( $value ) {
		if ( null !== $value && ( ! is_string( $value ) || 1 !== preg_match( '/^role_(?:[1-9]|[12][0-9]|3[0-2])$/D', $value ) ) ) {
			self::reject();
		}
		return $value;
	}

	/**
	 * Project one closed request-local trace, without arming or rendering anything.
	 *
	 * @param mixed $source Closed WCJ_AI_Role_Observation::close() result.
	 * @return array|null Strict payload or no observation, not an authenticated receipt.
	 */
	public static function role( $source ) {
		if ( null === $source ) {
			return null;
		}
		$fixed = array(
			'schema'      => 1,
			'phase'       => 'product_render',
			'persistence' => 'none',
			'non_atomic'  => true,
			'unobserved'  => array( 'earlier_phases', 'other_role_module_instances', 'wholesale', 'multicurrency', 'addons', 'fees', 'payment', 'shipping' ),
		);
		self::keys( $source, array_merge( array_keys( $fixed ), array( 'truncated', 'events' ) ) );
		foreach ( $fixed as $key => $value ) {
			self::same( $source[ $key ], $value );
		}
		if ( ! is_bool( $source['truncated'] ) ) {
			self::reject();
		}
		self::sequence( $source['events'], 64 );
		$events  = array();
		$depths  = array();
		$stack   = array();
		$hooks   = array( 'woocommerce_product_get_price', 'woocommerce_product_get_regular_price', 'woocommerce_product_get_sale_price', 'woocommerce_product_variation_get_price', 'woocommerce_product_variation_get_regular_price', 'woocommerce_product_variation_get_sale_price', 'unsupported_context' );
		$reasons = array( 'extension_veto', 'changed_product', 'addon_guard', 'product_empty', 'regular_skip', 'product_multiplier', 'display_delegate', 'product_fixed_extension', 'product_regular', 'product_sale', 'on_sale_skip', 'category_empty', 'category_multiplier', 'tag_empty', 'tag_multiplier', 'global_empty', 'global_multiplier', 'unchanged' );
		foreach ( $source['events'] as $index => $event ) {
			self::keys( $event, array( 'sequence', 'parent', 'hook', 'priority', 'invocation', 'input', 'output', 'decisions', 'cache', 'cache_origin', 'cache_role' ) );
			if ( $event['sequence'] !== $index || ( null !== $event['parent'] && ( ! is_int( $event['parent'] ) || $event['parent'] < 0 || $event['parent'] >= $index ) ) || ! in_array( $event['hook'], $hooks, true ) || ( null !== $event['priority'] && ! is_int( $event['priority'] ) ) || 'method_observed_dispatch_unqualified' !== $event['invocation'] || ! in_array( $event['cache'], array( 'hit', 'miss', 'bypass', 'unobserved' ), true ) ) {
				self::reject();
			}
			if ( null !== $event['parent'] && ! in_array( $event['parent'], $stack, true ) ) {
				self::reject();
			}
			while ( $stack && ( null === $event['parent'] || end( $stack ) !== $event['parent'] ) ) {
				array_pop( $stack );
			}
			$stack[]          = $index;
			$depths[ $index ] = null === $event['parent'] ? 1 : $depths[ $event['parent'] ] + 1;
			if ( $depths[ $index ] > 16 || ( null === $event['output'] && ! $source['truncated'] ) ) {
				self::reject();
			}
			self::sequence( $event['decisions'], 8 );
			$decisions = array();
			foreach ( $event['decisions'] as $decision ) {
				self::keys( $decision, array( 'reason', 'evaluator_role', 'role_source', 'factor', 'result' ) );
				if ( ! in_array( $decision['reason'], $reasons, true ) || 'booster_effective_role' !== $decision['role_source'] ) {
					self::reject();
				}
				$decisions[] = array(
					'reason'         => $decision['reason'],
					'evaluator_role' => self::alias( $decision['evaluator_role'] ),
					'role_source'    => 'booster_effective_role',
					'factor'         => null === $decision['factor'] ? null : self::scalar( $decision['factor'] ),
					'result'         => self::scalar( $decision['result'] ),
				);
			}
			$origin = $event['cache_origin'];
			if ( null !== $origin && ( 'hit' !== $event['cache'] || ! is_int( $origin ) || $origin < 0 || $origin >= count( $source['events'] ) || $origin === $index ) ) {
				self::reject();
			}
			$events[] = array(
				'sequence'      => $index,
				'parent'        => $event['parent'],
				'hook'          => $event['hook'],
				'priority_text' => null === $event['priority'] ? null : (string) $event['priority'],
				'invocation'    => 'method_observed_dispatch_unqualified',
				'input'         => self::scalar( $event['input'] ),
				'output'        => null === $event['output'] ? null : self::scalar( $event['output'] ),
				'decisions'     => $decisions,
				'cache'         => $event['cache'],
				'cache_origin'  => $origin,
				'cache_role'    => self::alias( $event['cache_role'] ),
			);
		}
		foreach ( $events as $index => $event ) {
			$origin = $event['cache_origin'];
			if ( null === $origin ) {
				continue;
			}
			if ( 'miss' !== $events[ $origin ]['cache'] || null === $events[ $origin ]['output'] ) {
				self::reject();
			}
			// A later miss must complete inside this call; an earlier ancestor cannot have finished yet.
			$ancestor = $events[ max( $index, $origin ) ]['parent'];
			$target   = min( $index, $origin );
			while ( null !== $ancestor && $ancestor !== $target ) {
				$ancestor = $events[ $ancestor ]['parent'];
			}
			if ( ( $origin > $index ) !== ( $ancestor === $target ) ) {
				self::reject();
			}
			// This structural check still does not prove key equality, dispatch or pricing causality.
		}
		return self::bounded(
			array(
				'schema_version'             => 1,
				'phase'                      => 'product_render',
				'non_atomic'                 => true,
				'dispatch'                   => 'method_observed_dispatch_unqualified',
				'currency_context'           => 'unqualified',
				'effective_storefront_price' => null,
				'persistence'                => 'none',
				'truncated'                  => $source['truncated'],
				'unobserved'                 => $fixed['unobserved'],
				'events'                     => $events,
			)
		);
	}
}
