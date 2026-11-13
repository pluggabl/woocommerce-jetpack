<?php
/**
 * Invoice-only durable local plans. No remote capability or route is registered.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/class-wcj-ai-invoice-presentation.php';
require_once __DIR__ . '/class-wcj-invoice-job.php';

/** Local-owner plan journal; deliberately not a generic options writer. */
final class WCJ_AI_Invoice_Plans {

	/** Fixed, bounded site index; no automatic deletion of uncertain journals. */
	const INDEX = 'wcj_ai_invoice_plan_index';

	/**
	 * Throw a finite error without exposing database details or other owners.
	 *
	 * @param string $code Developer-owned finite code.
	 * @throws RuntimeException Always.
	 */
	private static function fail( $code ) {
		throw new RuntimeException( esc_html( $code ) );
	}

	/**
	 * Read exact bytes and distinguish missing rows from database errors.
	 *
	 * @param string $key Internally selected option name, never an external key.
	 * @return array Exact persisted state.
	 */
	private static function raw( $key ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table identifier, prepared exact key; authoritative CAS read intentionally bypasses caches.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			self::fail( 'plan_storage_unavailable' );
		}
		return array(
			'exists' => null !== $row,
			'raw'    => null === $row ? '' : $row['option_value'],
		);
	}

	/**
	 * JSON encode a bounded local journal.
	 *
	 * @param array $record Closed internal record.
	 * @return string JSON bytes.
	 */
	private static function encode( array $record ) {
		$json = wp_json_encode( $record );
		if ( ! is_string( $json ) || strlen( $json ) > 32768 ) {
			self::fail( 'plan_storage_limit' );
		}
		return $json;
	}

	/**
	 * Save exactly once, verifying the bytes before any dependent action.
	 *
	 * @param string $key Internally selected journal/index name.
	 * @param array  $before Expected exact row state.
	 * @param array  $record New closed record.
	 * @return array New exact row state.
	 */
	private static function save( $key, array $before, array $record ) {
		$after = array(
			'exists' => true,
			'raw'    => self::encode( $record ),
		);
		if ( ! WCJ_Job_Store::compare_exchange( $key, $before, $after, array( $key ) ) || self::raw( $key ) !== $after ) {
			self::fail( 'plan_storage_outcome_unknown' );
		}
		return $after;
	}

	/**
	 * Resolve one fixed colour option and require a representable native state.
	 *
	 * @param string $field One allowlisted colour field.
	 * @return string Native option name.
	 */
	private static function field_key( $field ) {
		if ( ! in_array( $field, WCJ_AI_Invoice_Presentation::FIELDS, true ) ) {
			self::fail( 'plan_invalid_field' );
		}
		return 'wcj_invoicing_invoice_' . $field;
	}

	/**
	 * Validate a narrow exact native colour state.
	 *
	 * @param mixed $state Persisted state.
	 * @return bool Whether the state can be safely represented.
	 */
	private static function valid_state( $state ) {
		return is_array( $state ) && array_keys( $state ) === array( 'exists', 'raw' ) && is_bool( $state['exists'] ) && is_string( $state['raw'] ) &&
			( $state['exists'] ? 1 === preg_match( '/^#[0-9a-fA-F]{6}$/D', $state['raw'] ) : '' === $state['raw'] );
	}

	/**
	 * Load only the current owner's valid journal, never another owner's status.
	 *
	 * @param string $id Opaque plan ID.
	 * @return array Pair of exact row state and decoded journal.
	 */
	private static function load( $id ) {
		WCJ_Invoice_Job::authorize();
		if ( ! is_string( $id ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $id ) ) {
			self::fail( 'plan_unavailable' );
		}
		$raw  = self::raw( 'wcj_ai_invoice_plan_' . $id );
		$doc  = $raw['exists'] && strlen( $raw['raw'] ) <= 32768 ? json_decode( $raw['raw'], true ) : null;
		$keys = array( 'schema', 'id', 'site', 'owner', 'created', 'expires', 'revision', 'status', 'attempt', 'changes', 'before', 'written', 'restored', 'conflicts' );
		if ( ! is_array( $doc ) || array_keys( $doc ) !== $keys || 1 !== $doc['schema'] || $id !== $doc['id'] ||
			get_current_blog_id() !== $doc['site'] || get_current_user_id() !== $doc['owner'] ||
			! is_int( $doc['created'] ) || ! is_int( $doc['expires'] ) || $doc['expires'] !== $doc['created'] + 900 ||
			! is_int( $doc['revision'] ) || $doc['revision'] < 1 ||
			! in_array( $doc['status'], array( 'pending', 'applying', 'applied', 'failed', 'partial', 'stale', 'cancelled', 'recovering', 'recovered', 'recovery_partial' ), true ) ||
			! is_string( $doc['attempt'] ) || ( '' !== $doc['attempt'] && 1 !== preg_match( '/^[a-f0-9]{32}$/D', $doc['attempt'] ) ) ) {
			self::fail( 'plan_unavailable' );
		}
		try {
			$validated = WCJ_AI_Invoice_Presentation::validate(
				array(
					'schema_version' => 1,
					'task'           => 'invoice_presentation',
					'changes'        => $doc['changes'],
				)
			);
		} catch ( InvalidArgumentException $exception ) {
			self::fail( 'plan_unavailable' );
		}
		if ( $validated !== $doc['changes'] || ! is_array( $doc['before'] ) || array_keys( $doc['before'] ) !== array_keys( $validated ) ) {
			self::fail( 'plan_unavailable' );
		}
		foreach ( $doc['before'] as $state ) {
			if ( ! self::valid_state( $state ) ) {
				self::fail( 'plan_unavailable' ); }
		}
		foreach ( array( 'written', 'restored', 'conflicts' ) as $list ) {
			if ( ! is_array( $doc[ $list ] ) || array_values( $doc[ $list ] ) !== $doc[ $list ] || count( $doc[ $list ] ) > 4 ) {
				self::fail( 'plan_unavailable' );
			}
			foreach ( $doc[ $list ] as $entry ) {
				if ( ! is_string( $entry ) || ! array_key_exists( $entry, $validated ) ) {
					self::fail( 'plan_unavailable' ); }
			}
			if ( count( array_unique( $doc[ $list ] ) ) !== count( $doc[ $list ] ) ) {
				self::fail( 'plan_unavailable' ); }
		}
		return array( $raw, $doc );
	}

	/**
	 * Reserve capacity then persist immutable local intent, without setting writes.
	 *
	 * @param array $input Closed invoice task payload.
	 * @return array Local plan view.
	 */
	public static function create( array $input ) {
		WCJ_Invoice_Job::authorize();
		$changes = WCJ_AI_Invoice_Presentation::validate( $input );
		$lock    = WCJ_Job_Store::lock( self::INDEX . '_lock', 'plan_index_busy' );
		try {
			$index_raw = self::raw( self::INDEX );
			if ( strlen( $index_raw['raw'] ) > 4096 ) {
				self::fail( 'plan_capacity_or_index_unavailable' ); }
			$index = $index_raw['exists'] ? json_decode( $index_raw['raw'], true ) : array();
			if ( ! is_array( $index ) || count( $index ) >= 50 || array_values( $index ) !== $index ) {
				self::fail( 'plan_capacity_or_index_unavailable' ); }
			foreach ( $index as $entry ) {
				if ( ! is_string( $entry ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $entry ) ) {
					self::fail( 'plan_capacity_or_index_unavailable' ); }
			}
			$before = array();
			foreach ( $changes as $field => $value ) {
				$before[ $field ] = self::raw( self::field_key( $field ) );
				if ( ! self::valid_state( $before[ $field ] ) ) {
					self::fail( 'plan_unsupported_saved_colour' ); }
			}
			$id      = bin2hex( random_bytes( 16 ) );
			$index[] = $id;
			self::save( self::INDEX, $index_raw, $index ); // Reserve first: failure cannot cause unbounded unindexed journals.
			$now = time();
			$doc = array(
				'schema'    => 1,
				'id'        => $id,
				'site'      => get_current_blog_id(),
				'owner'     => get_current_user_id(),
				'created'   => $now,
				'expires'   => $now + 900,
				'revision'  => 1,
				'status'    => 'pending',
				'attempt'   => '',
				'changes'   => $changes,
				'before'    => $before,
				'written'   => array(),
				'restored'  => array(),
				'conflicts' => array(),
			);
			self::save(
				'wcj_ai_invoice_plan_' . $id,
				array(
					'exists' => false,
					'raw'    => '',
				),
				$doc
			);
		} finally {
			WCJ_Job_Store::unlock( self::INDEX . '_lock', $lock );
		}
		return self::inspect( $id );
	}

	/**
	 * Read only colour facts and finite journal state for the authorized local owner.
	 *
	 * @param string $id Plan ID.
	 * @return array Local review view, not a remote export contract.
	 */
	public static function inspect( $id ) {
		list( $raw, $doc ) = self::load( $id );
		$fields            = array();
		foreach ( $doc['changes'] as $field => $value ) {
			$current          = self::raw( self::field_key( $field ) );
			$fields[ $field ] = array(
				'before'                  => $doc['before'][ $field ]['exists'] ? $doc['before'][ $field ]['raw'] : null,
				'proposed'                => $value,
				'current'                 => self::valid_state( $current ) && $current['exists'] ? $current['raw'] : null,
				'unchanged_since_prepare' => $current === $doc['before'][ $field ],
			);
		}
		return array(
			'id'                => $id,
			'status'            => $doc['status'],
			'revision'          => $doc['revision'],
			'expired'           => time() >= $doc['expires'],
			'fields'            => $fields,
			'written'           => $doc['written'],
			'restored'          => $doc['restored'],
			'conflicts'         => $doc['conflicts'],
			'non_atomic'        => true,
			'outcome_uncertain' => in_array( $doc['status'], array( 'applying', 'recovering' ), true ),
		);
	}

	/**
	 * Cancel a pending plan. It cannot cancel or replay an uncertain write.
	 *
	 * @param string $id Plan ID.
	 * @return array Local review view.
	 */
	public static function cancel( $id ) {
		list( $raw, $doc ) = self::load( $id );
		if ( 'pending' !== $doc['status'] ) {
			self::fail( 'plan_not_pending' ); }
		$doc['status'] = 'cancelled';
		++$doc['revision'];
		self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
		return self::inspect( $id );
	}

	/**
	 * Execute immutable local intent, saving verified progress after each write.
	 *
	 * @param string $id Plan ID; no replacement body is accepted.
	 * @return array Readback view.
	 */
	public static function apply( $id ) {
		list( $raw, $doc ) = self::load( $id );
		if ( 'pending' !== $doc['status'] || time() >= $doc['expires'] ) {
			self::fail( 'plan_not_pending_or_expired' ); }
		$lock = WCJ_Job_Store::lock( WCJ_Invoice_Job::LOCK, 'plan_invoice_busy' );
		try {
			if ( time() >= $doc['expires'] ) {
				self::fail( 'plan_expired' ); }
			foreach ( $doc['before'] as $field => $state ) {
				if ( self::raw( self::field_key( $field ) ) !== $state ) {
					$doc['status'] = 'stale';
					++$doc['revision'];
					self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
					return self::inspect( $id );
				}
			}
			$doc['status']  = 'applying';
			$doc['attempt'] = bin2hex( random_bytes( 16 ) );
			++$doc['revision'];
			$raw = self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
			foreach ( $doc['changes'] as $field => $value ) {
				$after = array(
					'exists' => true,
					'raw'    => $value,
				);
				if ( $doc['before'][ $field ] === $after ) {
					continue; }
				if ( ! WCJ_Job_Store::compare_exchange( self::field_key( $field ), $doc['before'][ $field ], $after ) ) {
					// A failed/ambiguous database response must not masquerade as a known non-write.
					if ( self::raw( self::field_key( $field ) ) !== $doc['before'][ $field ] ) {
						self::fail( 'plan_setting_outcome_unknown' ); }
					foreach ( array_reverse( $doc['written'] ) as $written ) {
						$expected = array(
							'exists' => true,
							'raw'    => $doc['changes'][ $written ],
						);
						if ( self::restore( $written, $expected, $doc['before'][ $written ] ) ) {
							$doc['restored'][] = $written; } else {
							$doc['conflicts'][] = $written;
							}
							++$doc['revision'];
							$raw = self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
					}
					$doc['status'] = $doc['conflicts'] ? 'partial' : 'failed';
					++$doc['revision'];
					self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
					return self::inspect( $id );
				}
				if ( self::raw( self::field_key( $field ) ) !== $after ) {
					self::fail( 'plan_setting_outcome_unknown' ); }
				$doc['written'][] = $field;
				++$doc['revision'];
				$raw = self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
			}
			foreach ( $doc['changes'] as $field => $value ) {
				if ( self::raw( self::field_key( $field ) ) !== array(
					'exists' => true,
					'raw'    => $value,
				) ) {
					self::fail( 'plan_final_readback_changed' ); }
			}
			$doc['status'] = 'applied';
			++$doc['revision'];
			self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
		} finally {
			WCJ_Job_Store::unlock( WCJ_Invoice_Job::LOCK, $lock );
		}
		return self::inspect( $id );
	}

	/**
	 * Restore one known write, distinguishing conflicts from ambiguous DB results.
	 *
	 * @param string $field Fixed colour field.
	 * @param array  $expected Exact value written by this plan.
	 * @param array  $before Exact saved state before this plan.
	 * @return bool False only for a known pre-write conflict.
	 */
	private static function restore( $field, array $expected, array $before ) {
		$key = self::field_key( $field );
		if ( self::raw( $key ) !== $expected ) {
			return false;
		}
		if ( ! WCJ_Job_Store::compare_exchange( $key, $expected, $before ) || self::raw( $key ) !== $before ) {
			self::fail( 'plan_restore_outcome_unknown' );
		}
		return true;
	}

	/**
	 * Recover only this known completed attempt, preserving later edits.
	 *
	 * @param string $id Plan ID.
	 * @return array Recovery readback view.
	 */
	public static function recover( $id ) {
		list( $raw, $doc ) = self::load( $id );
		if ( 'applied' !== $doc['status'] ) {
			self::fail( 'plan_recovery_requires_known_applied_attempt' ); }
		$lock = WCJ_Job_Store::lock( WCJ_Invoice_Job::LOCK, 'plan_invoice_busy' );
		try {
			$doc['status'] = 'recovering';
			++$doc['revision'];
			$raw = self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
			foreach ( array_reverse( $doc['written'] ) as $field ) {
				$expected = array(
					'exists' => true,
					'raw'    => $doc['changes'][ $field ],
				);
				if ( self::restore( $field, $expected, $doc['before'][ $field ] ) ) {
					$doc['restored'][] = $field; } else {
					$doc['conflicts'][] = $field; }
					++$doc['revision'];
					$raw = self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
			}
			$doc['status'] = $doc['conflicts'] ? 'recovery_partial' : 'recovered';
			++$doc['revision'];
			self::save( 'wcj_ai_invoice_plan_' . $id, $raw, $doc );
		} finally {
			WCJ_Job_Store::unlock( WCJ_Invoice_Job::LOCK, $lock );
		}
		return self::inspect( $id );
	}
}
