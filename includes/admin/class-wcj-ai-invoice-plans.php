<?php
/**
 * Invoice-only durable local plans. No remote capability or route is registered.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/class-wcj-ai-invoice-presentation.php';
require_once __DIR__ . '/class-wcj-invoice-job.php';
require_once __DIR__ . '/class-wcj-ai-mutation-gate.php';

/** Local-owner plan journal; deliberately not a generic options writer. */
final class WCJ_AI_Invoice_Plans {

	/** Fixed, bounded site index; no automatic deletion of uncertain journals. */
	const INDEX = 'wcj_ai_invoice_plan_index';
	/** Permanent initialization marker; absence must not reset consumed capacity. */
	const INITIALIZED = 'wcj_ai_invoice_index_initialized';

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
		$wpdb->last_error = '';
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
	 * Read and validate the entire bounded index, retaining legacy/abandoned IDs.
	 * A marker without an index is unavailable, never a new empty quota window.
	 *
	 * @param array $gate Verified mutation-gate token.
	 * @param bool  $initialize Explicit native-owner allocation may initialize/migrate.
	 * @return array Exact old bytes and canonical version-two index.
	 */
	private static function index( array $gate, $initialize = false ) {
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		$raw    = self::raw( self::INDEX );
		$marker = self::raw( self::INITIALIZED );
		if ( ( $marker['exists'] && '1' !== $marker['raw'] ) || strlen( $raw['raw'] ) > 32768 ) {
			self::fail( 'plan_capacity_or_index_unavailable' );
		}
		$index  = $raw['exists'] ? json_decode( $raw['raw'], true ) : array();
		$legacy = is_array( $index ) && array_values( $index ) === $index;
		if ( $legacy && ( ! $initialize || $marker['exists'] ) ) {
			self::fail( 'plan_index_owner_initialization_required' );
		}
		if ( ! $raw['exists'] ) {
			global $wpdb;
			$wpdb->last_error = '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Bounded fixed-prefix existence check; no journal contents are read.
			$orphan = $wpdb->get_row( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1", $wpdb->esc_like( 'wcj_ai_invoice_plan_' ) . '%' ), ARRAY_A );
			if ( $marker['exists'] || '' !== $wpdb->last_error || null !== $orphan ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
		}
		if ( $legacy ) {
			if ( count( $index ) > 50 ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
			$entries = array();
			foreach ( $index as $id ) {
				if ( ! is_string( $id ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $id ) ) {
					self::fail( 'plan_capacity_or_index_unavailable' );
				}
				$entries[] = array(
					'kind' => 'legacy',
					'id'   => $id,
				);
			}
			$index = array(
				'schema'  => 2,
				'entries' => $entries,
			);
			global $wpdb;
			$wpdb->last_error = '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Bounded fixed-prefix orphan check; never reads journal content.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name <> %s AND option_name <> %s LIMIT 51", $wpdb->esc_like( 'wcj_ai_invoice_plan_' ) . '%', self::INDEX, self::INDEX . '_lock' ), ARRAY_A );
			if ( '' !== $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 50 ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
			foreach ( $rows as $row ) {
				// The index and its former lock share the prefix but are not plan rows.
				if ( in_array( $row['option_name'], array( self::INDEX, self::INDEX . '_lock' ), true ) ) {
					continue;
				}
				if ( ! in_array( substr( $row['option_name'], strlen( 'wcj_ai_invoice_plan_' ) ), array_column( $entries, 'id' ), true ) ) {
					self::fail( 'plan_capacity_or_index_unavailable' );
				}
			}
		} elseif ( ! $marker['exists'] ) {
			self::fail( 'plan_capacity_or_index_unavailable' );
		}
		self::check_index( $index );
		if ( ! $marker['exists'] ) {
			$after = array(
				'exists' => true,
				'raw'    => '1',
			);
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( self::INITIALIZED, $marker, $after, array( self::INITIALIZED ) ) || self::raw( self::INITIALIZED ) !== $after ) {
				self::fail( 'plan_storage_outcome_unknown' );
			}
		}
		if ( $legacy ) {
			$raw = self::save( self::INDEX, $raw, $index );
		}
		return array( $raw, $index );
	}

	/**
	 * Closed version-two index validation, including permanent remote ceilings.
	 *
	 * @param mixed $index Decoded index.
	 */
	private static function check_index( $index ) {
		if ( ! is_array( $index ) || array_keys( $index ) !== array( 'schema', 'entries' ) || 2 !== $index['schema'] ||
			! is_array( $index['entries'] ) || array_values( $index['entries'] ) !== $index['entries'] || count( $index['entries'] ) > 50 ) {
			self::fail( 'plan_capacity_or_index_unavailable' );
		}
		$ids         = array();
		$remote      = 0;
		$generations = array();
		$retries     = array();
		foreach ( $index['entries'] as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['kind'], $entry['id'] ) || ! is_string( $entry['id'] ) ||
				1 !== preg_match( '/^[a-f0-9]{32}$/D', $entry['id'] ) || in_array( $entry['id'], $ids, true ) ||
				! in_array( $entry['kind'], array( 'legacy', 'local', 'remote' ), true ) ||
				array_keys( $entry ) !== ( 'legacy' === $entry['kind'] ? array( 'kind', 'id' ) : ( 'local' === $entry['kind'] ? array( 'kind', 'id', 'fingerprint' ) : array( 'kind', 'id', 'fingerprint', 'connection', 'key_digest', 'payload_digest' ) ) ) ||
				( 'legacy' !== $entry['kind'] && ( ! is_string( $entry['fingerprint'] ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $entry['fingerprint'] ) ) ) ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
			$ids[] = $entry['id'];
			if ( 'remote' === $entry['kind'] ) {
				if ( ! self::valid_connection( $entry['connection'] ) || ! is_string( $entry['key_digest'] ) || ! is_string( $entry['payload_digest'] ) ||
					1 !== preg_match( '/^[a-f0-9]{64}$/D', $entry['key_digest'] ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $entry['payload_digest'] ) ) {
					self::fail( 'plan_capacity_or_index_unavailable' );
				}
				$generation                 = $entry['connection']['grant'];
				$retry                      = $generation . ':' . $entry['key_digest'];
				$generations[ $generation ] = ( $generations[ $generation ] ?? 0 ) + 1;
				if ( ++$remote > 12 || $generations[ $generation ] > 8 || in_array( $retry, $retries, true ) ) {
					self::fail( 'plan_capacity_or_index_unavailable' );
				}
				$retries[] = $retry;
			}
		}
	}

	/**
	 * Validate stored provenance shape without requiring an active grant.
	 * Recovery of a known write must remain possible after revocation.
	 *
	 * @param mixed $context Decoded immutable provenance.
	 * @return bool Closed shape.
	 */
	private static function valid_connection( $context ) {
		return is_array( $context ) && array_keys( $context ) === array( 'grant', 'principal', 'owner', 'site', 'app_uuid', 'grant_digest' ) &&
			is_string( $context['grant'] ) && 1 === preg_match( '/^[a-f0-9]{32}$/D', $context['grant'] ) &&
			is_int( $context['principal'] ) && $context['principal'] > 0 && is_int( $context['owner'] ) && $context['owner'] > 0 && $context['owner'] !== $context['principal'] &&
			is_int( $context['site'] ) && $context['site'] > 0 && is_string( $context['app_uuid'] ) &&
			1 === preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $context['app_uuid'] ) &&
			is_string( $context['grant_digest'] ) && 1 === preg_match( '/^[a-f0-9]{64}$/D', $context['grant_digest'] );
	}

	/**
	 * Immutable intent fingerprint; mutable status/progress are not authority.
	 *
	 * @param array $doc Server-constructed, validated journal.
	 * @return string Exact canonical SHA-256.
	 */
	private static function fingerprint( array $doc ) {
		$intent = array();
		foreach ( array( 'schema', 'id', 'site', 'owner', 'created', 'expires', 'changes', 'before' ) as $key ) {
			$intent[ $key ] = $doc[ $key ];
		}
		if ( 2 === $doc['schema'] ) {
			$intent['connection'] = $doc['connection'];
		}
		return hash( 'sha256', self::encode( $intent ) );
	}

	/**
	 * Explicit owner-only first-use/migration; never resets existing history.
	 * The controller must explain that absent history requires known provenance.
	 *
	 * @return bool Initialized or already valid, with all previous slots retained.
	 */
	public static function initialize() {
		WCJ_Invoice_Job::authorize();
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			if ( self::raw( self::INDEX . '_lock' )['exists'] ) {
				self::fail( 'plan_legacy_allocator_busy' );
			}
			self::index( $gate, true );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return true;
	}

	/**
	 * Load only the current owner's valid journal, never another owner's status.
	 *
	 * @param string $id Opaque plan ID.
	 * @return array Pair of exact row state and decoded journal.
	 */
	private static function load( $id ) {
		WCJ_Invoice_Job::authorize();
		$result = self::read_plan( $id );
		if ( get_current_user_id() !== $result[1]['owner'] ) {
			self::fail( 'plan_unavailable' );
		}
		return $result;
	}

	/**
	 * Private canonical journal reader. Authorization is supplied by load/prepare.
	 *
	 * @param string $id Validated opaque ID.
	 * @return array Exact row and validated document.
	 */
	private static function read_plan( $id ) {
		if ( ! is_string( $id ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $id ) ) {
			self::fail( 'plan_unavailable' );
		}
		$raw  = self::raw( 'wcj_ai_invoice_plan_' . $id );
		$doc  = $raw['exists'] && strlen( $raw['raw'] ) <= 32768 ? json_decode( $raw['raw'], true ) : null;
		$keys = array( 'schema', 'id', 'site', 'owner', 'created', 'expires', 'revision', 'status', 'attempt', 'changes', 'before', 'written', 'restored', 'conflicts' );
		if ( is_array( $doc ) && 2 === ( $doc['schema'] ?? null ) ) {
			$keys[] = 'connection';
		}
		if ( ! is_array( $doc ) || array_keys( $doc ) !== $keys || ! in_array( $doc['schema'], array( 1, 2 ), true ) || $id !== $doc['id'] ||
			get_current_blog_id() !== $doc['site'] || ! is_int( $doc['owner'] ) || $doc['owner'] < 1 ||
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
		self::check_reservation( $doc );
		return array( $raw, $doc );
	}

	/**
	 * The index, not a journal's claimed schema, determines its origin.
	 * A remote journal cannot downgrade to the legacy/local authorization path.
	 *
	 * @param array $doc Validated journal shape.
	 */
	private static function check_reservation( array $doc ) {
		$raw    = self::raw( self::INDEX );
		$marker = self::raw( self::INITIALIZED );
		if ( ! $raw['exists'] || strlen( $raw['raw'] ) > 32768 ) {
			self::fail( 'plan_unavailable' );
		}
		$index = json_decode( $raw['raw'], true );
		if ( is_array( $index ) && array_values( $index ) === $index ) {
			if ( $marker['exists'] || 1 !== $doc['schema'] || count( $index ) > 50 ) {
				self::fail( 'plan_unavailable' );
			}
			$seen = array();
			foreach ( $index as $id ) {
				if ( ! is_string( $id ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $id ) || in_array( $id, $seen, true ) ) {
					self::fail( 'plan_unavailable' );
				}
				$seen[] = $id;
			}
			if ( ! in_array( $doc['id'], $seen, true ) ) {
				self::fail( 'plan_unavailable' );
			}
			return;
		}
		if ( array(
			'exists' => true,
			'raw'    => '1',
		) !== $marker ) {
			self::fail( 'plan_unavailable' );
		}
		self::check_index( $index );
		foreach ( $index['entries'] as $entry ) {
			if ( $entry['id'] !== $doc['id'] ) {
				continue;
			}
			if ( 'legacy' === $entry['kind'] && 1 === $doc['schema'] ) {
				return;
			}
			if ( 'local' === $entry['kind'] && 1 === $doc['schema'] && self::fingerprint( $doc ) === $entry['fingerprint'] ) {
				return;
			}
			if ( 'remote' === $entry['kind'] && 2 === $doc['schema'] && self::valid_connection( $doc['connection'] ) &&
				$doc['connection']['owner'] === $doc['owner'] && $doc['connection']['site'] === $doc['site'] &&
				$entry['connection'] === $doc['connection'] && self::fingerprint( $doc ) === $entry['fingerprint'] &&
				hash( 'sha256', self::encode( $doc['changes'] ) ) === $entry['payload_digest'] ) {
				return;
			}
			self::fail( 'plan_unavailable' );
		}
		self::fail( 'plan_unavailable' );
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
		$gate    = WCJ_AI_Mutation_Gate::acquire();
		try {
			if ( self::raw( self::INDEX . '_lock' )['exists'] ) {
				self::fail( 'plan_legacy_allocator_busy' );
			}
			list( $index_raw, $index ) = self::index( $gate );
			if ( count( $index['entries'] ) >= 50 ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
			$doc = self::document( $changes, get_current_user_id() );
			$id  = $doc['id'];
			if ( self::raw( 'wcj_ai_invoice_plan_' . $id )['exists'] || in_array( $id, array_column( $index['entries'], 'id' ), true ) ) {
				self::fail( 'plan_identifier_unavailable' );
			}
			$index['entries'][] = array(
				'kind'        => 'local',
				'id'          => $id,
				'fingerprint' => self::fingerprint( $doc ),
			);
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			self::save( self::INDEX, $index_raw, $index ); // One quota charge precedes any journal insertion.
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			self::save(
				'wcj_ai_invoice_plan_' . $id,
				array(
					'exists' => false,
					'raw'    => '',
				),
				$doc
			);
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return self::inspect( $id );
	}

	/**
	 * Prepare a typed remote proposal; never impersonates or writes settings.
	 * Not registered as a tool until the independent exposure gates are met.
	 *
	 * @param mixed $input Closed task plus a caller-generated idempotency key.
	 * @return array Minimal handoff; approval stays in the local owner UI.
	 */
	public static function prepare( $input ) {
		if ( ! class_exists( 'WCJ_AI_Connection', false ) || ! is_array( $input ) || count( $input ) !== 4 ||
			! isset( $input['idempotency_key'] ) || ! is_string( $input['idempotency_key'] ) ||
			1 !== preg_match( '/^[A-Za-z0-9_-]{16,64}$/D', $input['idempotency_key'] ) ) {
			self::fail( 'plan_prepare_unavailable' );
		}
		$key_digest = hash( 'sha256', $input['idempotency_key'] );
		unset( $input['idempotency_key'] );
		$changes = WCJ_AI_Invoice_Presentation::validate( $input );
		$context = WCJ_AI_Connection::prepare_context();
		if ( ! self::valid_connection( $context ) ) {
			self::fail( 'plan_prepare_unavailable' );
		}
		$payload_digest = hash( 'sha256', self::encode( $changes ) );
		$gate           = WCJ_AI_Mutation_Gate::acquire();
		try {
			if ( WCJ_AI_Connection::prepare_context() !== $context ) {
				self::fail( 'plan_prepare_unavailable' );
			}
			list( $index_raw, $index ) = self::index( $gate );
			$remote                    = 0;
			$generation                = 0;
			foreach ( $index['entries'] as $entry ) {
				if ( 'remote' !== $entry['kind'] ) {
					continue;
				}
				++$remote;
				$generation += $context['grant'] === $entry['connection']['grant'] ? 1 : 0;
				if ( $context['grant'] === $entry['connection']['grant'] && $key_digest === $entry['key_digest'] ) {
					if ( $context !== $entry['connection'] || $payload_digest !== $entry['payload_digest'] ) {
						self::fail( 'plan_idempotency_conflict' );
					}
					// Retry lookup precedes quotas. Missing/uncertain plans are never recreated.
					list( $unused, $doc ) = self::read_plan( $entry['id'] );
					if ( 2 !== $doc['schema'] || $doc['connection'] !== $context || self::fingerprint( $doc ) !== $entry['fingerprint'] ) {
						self::fail( 'plan_prepare_unavailable' );
					}
					return self::handoff( $doc );
				}
			}
			if ( count( $index['entries'] ) >= 50 || $remote >= 12 || $generation >= 8 ) {
				self::fail( 'plan_capacity_or_index_unavailable' );
			}
			$doc = self::document( $changes, $context['owner'], $context );
			$id  = $doc['id'];
			if ( self::raw( 'wcj_ai_invoice_plan_' . $id )['exists'] || in_array( $id, array_column( $index['entries'], 'id' ), true ) ) {
				self::fail( 'plan_identifier_unavailable' );
			}
			$index['entries'][] = array(
				'kind'           => 'remote',
				'id'             => $id,
				'fingerprint'    => self::fingerprint( $doc ),
				'connection'     => $context,
				'key_digest'     => $key_digest,
				'payload_digest' => $payload_digest,
			);
			self::check_index( $index );
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			self::save( self::INDEX, $index_raw, $index );
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			self::save(
				'wcj_ai_invoice_plan_' . $id,
				array(
					'exists' => false,
					'raw'    => '',
				),
				$doc
			);
			return self::handoff( $doc );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
	}

	/**
	 * Construct intent from fresh bounded saved fields, never from a client base.
	 *
	 * @param array      $changes Canonical validated changes.
	 * @param int        $owner Authorized local owner or server-bound grant owner.
	 * @param array|null $context Optional authenticated immutable provenance.
	 * @return array New canonical journal.
	 */
	private static function document( array $changes, $owner, $context = null ) {
		$before = array();
		foreach ( $changes as $field => $value ) {
			$before[ $field ] = self::raw( self::field_key( $field ) );
			if ( ! self::valid_state( $before[ $field ] ) ) {
				self::fail( 'plan_unsupported_saved_colour' );
			}
		}
		$now = time();
		$doc = array(
			'schema'    => null === $context ? 1 : 2,
			'id'        => bin2hex( random_bytes( 16 ) ),
			'site'      => get_current_blog_id(),
			'owner'     => $owner,
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
		if ( null !== $context ) {
			$doc['connection'] = $context;
		}
		return $doc;
	}

	/**
	 * Minimal remote receipt; no raw saved states, identities or authority token.
	 *
	 * @param array $doc Verified journal bound to this request's grant.
	 * @return array Closed handoff view.
	 */
	private static function handoff( array $doc ) {
		return array(
			'schema_version'          => 1,
			'plan_id'                 => $doc['id'],
			'status'                  => $doc['status'],
			'expires_at'              => $doc['expires'],
			'requires_local_approval' => true,
			'non_atomic'              => true,
		);
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
			'origin'            => array(
				'kind'       => 2 === $doc['schema'] ? 'remote' : 'local',
				'principal'  => 2 === $doc['schema'] ? $doc['connection']['principal'] : null,
				'generation' => 2 === $doc['schema'] ? $doc['connection']['grant'] : null,
			),
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
		if ( 2 !== $doc['schema'] ) {
			return self::execute( $id, $raw, $doc );
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			list( $raw, $doc ) = self::load( $id );
			if ( 'pending' !== $doc['status'] || time() >= $doc['expires'] || ! class_exists( 'WCJ_AI_Connection', false ) ||
				! WCJ_AI_Connection::eligible_context( $doc['connection'] ) ) {
				self::fail( 'plan_connection_unavailable' );
			}
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			return self::execute( $id, $raw, $doc );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
	}

	/**
	 * Execute once under the native invoice lock and any required connection gate.
	 *
	 * @param string $id Plan ID.
	 * @param array  $raw Exact journal bytes.
	 * @param array  $doc Authorized validated intent.
	 * @return array Local review view.
	 */
	private static function execute( $id, array $raw, array $doc ) {
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
			// Fresh admission immediately before the intent transition. This is not
			// continuous time fencing after a process is suspended mid-attempt.
			if ( time() >= $doc['expires'] || ( 2 === $doc['schema'] && ! WCJ_AI_Connection::eligible_context( $doc['connection'] ) ) ) {
				self::fail( 'plan_connection_or_window_unavailable' );
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
