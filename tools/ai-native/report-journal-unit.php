<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Standalone fake native-class test harness, not a runtime class file.
/**
 * Fake-DB/CAS receipt state-machine tests. NOT native SQL, auth, UI or client proof.
 * Real journal, receipt, projection and gate; only database/CAS primitives faked.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName, Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.WP.GlobalVariablesOverride -- Standalone fake native seams, never a runtime bootstrap.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Squiz.Commenting.FunctionComment, Squiz.Commenting.VariableComment, Generic.Files.OneObjectStructurePerFile, WordPress.WP.AlternativeFunctions -- CLI-only deterministic fault harness with native-shaped test doubles.
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Test only: exact JSON payload equality, not code execution.
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );

/** Bounded SQL result model, not an SQL engine. */
final class WCJ_AI_Report_Test_DB {
	public $options    = 'test_options';
	public $last_error = '';
	public $rows       = array();
	public $writes     = array();
	public $queries    = array();
	public $fault      = null;
	public $read_fault = null;
	public function prepare( $sql, ...$args ) {
		return array( $sql, $args );
	}
	public function get_row( $query, $format ) {
		$key = $query[1][0];
		return isset( $this->rows[ $key ] ) ? array( 'option_value' => $this->rows[ $key ]['raw'] ) : null;
	}
	public function get_results( $query, $format ) {
		list( $cap, $key ) = $query[1];
		if ( ! str_contains( $query[0], 'CASE WHEN OCTET_LENGTH(option_value) <= %d THEN option_value ELSE NULL END AS raw' ) || ! str_contains( $query[0], 'LIMIT 2' ) || ! in_array( $key, array( WCJ_AI_Report_Journal::KEY, WCJ_AI_Report_Journal::MARKER ), true ) ) {
			throw new RuntimeException( 'unexpected_read_model' );
		}
		$this->queries[] = $key;
		if ( $this->read_fault ) {
			$result = ( $this->read_fault )( $key );
			if ( null !== $result ) {
				return $result;
			}
		}
		if ( ! isset( $this->rows[ $key ] ) ) {
			return array();
		}
		$row = $this->rows[ $key ];
		return array(
			array(
				'bytes'    => (string) strlen( $row['raw'] ),
				'raw'      => strlen( $row['raw'] ) <= $cap ? $row['raw'] : null,
				'autoload' => $row['autoload'],
			),
		);
	}
}

/** Exact CAS model with pre/post-write faults; intentionally not real SQL. */
final class WCJ_Job_Store {
	public static function compare_exchange( $key, $before, $after, $no_autoload = array() ) {
		global $wpdb;
		$current = array(
			'exists' => isset( $wpdb->rows[ $key ] ),
			'raw'    => $wpdb->rows[ $key ]['raw'] ?? '',
		);
		if ( $before === $after ) {
			throw new RuntimeException( 'forbidden_unbounded_noop_branch' );
		}
		if ( $before !== $current ) {
			return false;
		}
		$mode = $wpdb->fault ? ( $wpdb->fault )( $key, $before, $after ) : '';
		if ( 'reject' === $mode ) {
			return false;
		}
		$wpdb->writes[] = $key;
		if ( $after['exists'] ) {
			$wpdb->rows[ $key ] = array(
				'raw'      => $after['raw'],
				'autoload' => $wpdb->rows[ $key ]['autoload'] ?? ( in_array( $key, $no_autoload, true ) ? 'no' : 'yes' ),
			);
		} else {
			unset( $wpdb->rows[ $key ] );
		}
		if ( 'throw_after_write' === $mode ) {
			throw new RuntimeException( 'private_fault_details_not_for_output' );
		}
		return 'lost_ack' !== $mode;
	}
}

require_once __DIR__ . '/../../includes/admin/class-wcj-ai-report-journal.php';
require_once __DIR__ . '/class-wcj-ai-revenue-fixture.php';

$cases   = array();
$time    = 1794564000;
$binding = array(
	'recipient'            => array(
		'grant_schema'     => 2,
		'owner'            => 1,
		'site'             => 1,
		'principal'        => 2,
		'app_uuid'         => '11111111-1111-4111-8111-111111111111',
		'grant_generation' => str_repeat( 'a', 32 ),
		'grant_digest'     => str_repeat( 'b', 64 ),
		'grant_expires'    => $time + 900,
		'kind'             => 'revenue_observation',
		'scope'            => 'report:read_shared',
	),
	'owner_session_digest' => str_repeat( 'c', 64 ),
	'source_build'         => str_repeat( 'd', 64 ),
	'projection_build'     => str_repeat( 'e', 64 ),
);
$owner   = array(
	'owner'                => 1,
	'site'                 => 1,
	'owner_session_digest' => $binding['owner_session_digest'],
);
$bytes   = WCJ_AI_Report_Receipt::build( WCJ_AI_Revenue_Fixture::source(), $time, $time + 900, $time + 6 );
$digest  = hash( 'sha256', $bytes );

function check( $name, $ok ) {
	$GLOBALS['cases'][ $name ] = true === $ok;
	if ( true !== $ok ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Fixed CLI test names, not HTML or request input.
		throw new RuntimeException( 'assertion_failed:' . $name );
	}
}
function denied( $name, $callback, $code = 'ai_report_unavailable' ) {
	try {
		$callback();
	} catch ( RuntimeException $exception ) {
		check( $name, $exception->getMessage() === $code );
		return;
	}
	check( $name, false );
}
function fresh( $initialize = true ) {
	$GLOBALS['wpdb'] = new WCJ_AI_Report_Test_DB();
	$gate            = WCJ_AI_Mutation_Gate::acquire();
	if ( $initialize ) {
		WCJ_AI_Report_Journal::initialize( $gate, true );
	}
	return $gate;
}
function reserve( $gate ) {
	return WCJ_AI_Report_Journal::reserve( $gate, $GLOBALS['binding'], $GLOBALS['time'] )['entry'];
}
function pending( $gate ) {
	$entry = reserve( $gate );
	return WCJ_AI_Report_Journal::finish( $gate, $GLOBALS['binding'], $entry['id'], 1, $GLOBALS['bytes'], $GLOBALS['time'] + 7 );
}
function shared( $gate ) {
	$entry = pending( $gate );
	return WCJ_AI_Report_Journal::share( $gate, $GLOBALS['binding'], $entry['id'], 2, $GLOBALS['digest'], $GLOBALS['time'] + 8 );
}
function journal() {
	return json_decode( $GLOBALS['wpdb']->rows[ WCJ_AI_Report_Journal::KEY ]['raw'], true );
}
function corrupt( $value ) {
	$GLOBALS['wpdb']->rows[ WCJ_AI_Report_Journal::KEY ]['raw'] = json_encode( $value, JSON_UNESCAPED_SLASHES );
}

$gate = fresh( false );
denied( 'freshness_attestation_required', fn() => WCJ_AI_Report_Journal::initialize( $gate, false ) );
WCJ_AI_Report_Journal::initialize( $gate, true );
check(
	'fixed_rows_nonautoload_and_empty',
	'no' === $wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['autoload'] && 'no' === $wpdb->rows[ WCJ_AI_Report_Journal::MARKER ]['autoload'] && array(
		'schema'   => 1,
		'revision' => 0,
		'entries'  => array(),
	) === journal()
);
denied( 'no_repeat_initialization', fn() => WCJ_AI_Report_Journal::initialize( $gate, true ) );
$first = reserve( $gate );
check( 'reserve_exact_limits', 64 === strlen( $first['id'] ) && 'collecting' === $first['state'] && $time + 300 === $first['review_deadline'] && $time + 600 === $first['expires'] );
$before = $wpdb->rows;
$again  = WCJ_AI_Report_Journal::reserve( $gate, $binding, $time + 1 );
check( 'exact_duplicate_no_write_or_recapture', false === $again['created'] && $first === $again['entry'] && $before === $wpdb->rows );
foreach ( array( 'owner_session_digest', 'source_build', 'projection_build' ) as $field ) {
	$other           = $binding;
	$other[ $field ] = str_repeat( 'f', 64 );
	denied( 'different_' . $field . '_busy', fn() => WCJ_AI_Report_Journal::reserve( $gate, $other, $time + 1 ), 'ai_report_busy' );
}
foreach ( array(
	'grant_digest'  => str_repeat( 'f', 64 ),
	'grant_expires' => $time + 901,
	'owner'         => 3,
) as $field => $changed ) {
	$other                        = $binding;
	$other['recipient'][ $field ] = $changed;
	denied( 'same_grant_changed_' . $field . '_busy', fn() => WCJ_AI_Report_Journal::reserve( $gate, $other, $time + 1 ), 'ai_report_busy' );
}
denied( 'collecting_not_readable', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $first['id'], $time + 1 ) );
denied( 'finish_future_created', fn() => WCJ_AI_Report_Journal::finish( $gate, $binding, $first['id'], 1, $bytes, $time + 5 ) );
denied( 'finish_stale_revision', fn() => WCJ_AI_Report_Journal::finish( $gate, $binding, $first['id'], 2, $bytes, $time + 7 ) );
denied( 'finish_review_equality_denied', fn() => WCJ_AI_Report_Journal::finish( $gate, $binding, $first['id'], 1, $bytes, $time + 300 ) );
$entry = WCJ_AI_Report_Journal::finish( $gate, $binding, $first['id'], 1, $bytes, $time + 7 );
check( 'pending_freezes_entire_envelope', 'pending_review' === $entry['state'] && base64_decode( $entry['payload_b64'], true ) === $bytes && $digest === $entry['payload_sha256'] );
denied( 'pending_not_readable', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 7 ) );
denied( 'finish_never_replaces_pending', fn() => WCJ_AI_Report_Journal::finish( $gate, $binding, $entry['id'], 1, $bytes, $time + 8 ) );
denied( 'share_wrong_digest', fn() => WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, str_repeat( 'f', 64 ), $time + 8 ) );
denied( 'share_review_equality_denied', fn() => WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, $digest, $time + 300 ) );
$entry = WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, $digest, $time + 8 );
check( 'shared_bytes_equal_review', WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 ) === $bytes );
$before = $wpdb->rows;
$again  = WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, $digest, $time + 400 );
check( 'shared_replay_no_write_no_extension', $again === $entry && $before === $wpdb->rows );
denied( 'read_expiry_equality_denied', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 600 ) );
denied( 'read_time_rollback_denied', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 7 ) );
foreach ( array(
	'owner'            => 3,
	'site'             => 2,
	'principal'        => 4,
	'app_uuid'         => '22222222-2222-4222-8222-222222222222',
	'grant_generation' => str_repeat( 'f', 32 ),
	'grant_digest'     => str_repeat( 'f', 64 ),
	'grant_expires'    => $time + 901,
	'scope'            => 'invoice:read',
	'grant_schema'     => 1,
	'kind'             => 'invoice',
) as $field => $changed ) {
	$recipient           = $binding['recipient'];
	$recipient[ $field ] = $changed;
	denied( 'read_wrong_recipient_' . $field, fn() => WCJ_AI_Report_Journal::read_shared( $gate, $recipient, $entry['id'], $time + 9 ) );
}
check( 'owner_inspect_after_expiry_retains_bytes', WCJ_AI_Report_Journal::inspect( $gate, $owner, $entry['id'] ) === $entry );
$wrong_owner                         = $owner;
$wrong_owner['owner_session_digest'] = str_repeat( 'f', 64 );
denied( 'inspect_wrong_session', fn() => WCJ_AI_Report_Journal::inspect( $gate, $wrong_owner, $entry['id'] ) );
denied( 'purge_wrong_revision', fn() => WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 2 ) );
WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 3 );
check( 'purge_retains_marker_monotonic_revision', array() === journal()['entries'] && 4 === journal()['revision'] && '1' === $wpdb->rows[ WCJ_AI_Report_Journal::MARKER ]['raw'] );
denied( 'purged_id_never_readable', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 ) );
denied( 'absent_purge_not_fabricated_success', fn() => WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 3 ) );
check( 'new_reservation_new_random_id', reserve( $gate )['id'] !== $entry['id'] );

$gate = fresh();
for ( $i = 0; $i < 16; ++$i ) {
	$other                                  = $binding;
	$other['recipient']['grant_generation'] = str_pad( dechex( $i ), 32, '0', STR_PAD_LEFT );
	WCJ_AI_Report_Journal::reserve( $gate, $other, $time );
}
$before = $wpdb->rows;
denied( 'seventeenth_slot_denied', fn() => reserve( $gate ) );
check( 'sixteen_retained_no_write_on_full', 16 === count( journal()['entries'] ) && $before === $wpdb->rows );
$again = WCJ_AI_Report_Journal::reserve( $gate, $other, $time + 1000 );
check( 'expired_duplicate_still_occupied_no_recapture', false === $again['created'] && $before === $wpdb->rows );

foreach ( array( 'marker_only', 'journal_only', 'missing_marker', 'missing_journal', 'bad_marker', 'oversize_marker', 'oversize_journal', 'autoload_yes', 'invalid_json', 'duplicate_key', 'unknown_key', 'duplicate_id', 'duplicate_slot', 'bad_revision', 'overflow_revision', 'wrong_entry_revision', 'unknown_nested', 'wrong_binding_scalar' ) as $mode ) {
	$gate  = fresh();
	$entry = reserve( $gate );
	$value = journal();
	switch ( $mode ) {
		case 'marker_only':
		case 'missing_journal':
			unset( $wpdb->rows[ WCJ_AI_Report_Journal::KEY ] );
			break;
		case 'journal_only':
		case 'missing_marker':
			unset( $wpdb->rows[ WCJ_AI_Report_Journal::MARKER ] );
			break;
		case 'bad_marker':
			$wpdb->rows[ WCJ_AI_Report_Journal::MARKER ]['raw'] = '0';
			break;
		case 'oversize_marker':
			$wpdb->rows[ WCJ_AI_Report_Journal::MARKER ]['raw'] = '11';
			break;
		case 'oversize_journal':
			$wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['raw'] = str_repeat( 'x', WCJ_AI_Report_Journal::MAX_BYTES + 1 );
			break;
		case 'autoload_yes':
			$wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['autoload'] = 'yes';
			break;
		case 'invalid_json':
			$wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['raw'] = '{';
			break;
		case 'duplicate_key':
			$wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['raw'] = str_replace( '"schema":1', '"schema":1,"schema":1', $wpdb->rows[ WCJ_AI_Report_Journal::KEY ]['raw'] );
			break;
		case 'unknown_key':
			$value['x'] = 'private';
			corrupt( $value );
			break;
		case 'duplicate_id':
			$value['entries'][] = $value['entries'][0];
			corrupt( $value );
			break;
		case 'duplicate_slot':
			$value['entries'][]        = $value['entries'][0];
			$value['entries'][1]['id'] = str_repeat( 'f', 64 );
			corrupt( $value );
			break;
		case 'bad_revision':
			$value['revision'] = '1';
			corrupt( $value );
			break;
		case 'overflow_revision':
			$value['revision'] = WCJ_AI_Report_Journal::MAX_REVISION + 1;
			corrupt( $value );
			break;
		case 'wrong_entry_revision':
			$value['entries'][0]['revision'] = 2;
			corrupt( $value );
			break;
		case 'unknown_nested':
			$value['entries'][0]['binding']['customer_id'] = 4;
			corrupt( $value );
			break;
		case 'wrong_binding_scalar':
			$value['entries'][0]['binding']['recipient']['owner'] = true;
			corrupt( $value );
			break;
	}
	$before = $wpdb->rows;
	denied( 'deny_corrupt_' . $mode, fn() => WCJ_AI_Report_Journal::inspect( $gate, $owner, $entry['id'] ) );
	denied( 'no_reinitialize_' . $mode, fn() => WCJ_AI_Report_Journal::initialize( $gate, true ) );
	check( 'no_repair_' . $mode, $before === $wpdb->rows );
}
foreach ( array( 'bad_padding', 'whitespace', 'invalid', 'too_large_base64', 'wrong_digest', 'wrong_length', 'too_large_decoded', 'changed_envelope', 'wrong_created', 'wrong_shared_time' ) as $mode ) {
	$gate  = fresh();
	$entry = shared( $gate );
	$value = journal();
	$bad   = &$value['entries'][0];
	switch ( $mode ) {
		case 'bad_padding':
			$bad['payload_b64'] .= '=';
			break;
		case 'whitespace':
			$bad['payload_b64'] .= "\n";
			break;
		case 'invalid':
			$bad['payload_b64'][0] = '!';
			break;
		case 'too_large_base64':
			$bad['payload_b64'] = str_repeat( 'A', WCJ_AI_Report_Journal::MAX_BASE64 + 1 );
			break;
		case 'wrong_digest':
			$bad['payload_sha256'] = str_repeat( 'f', 64 );
			break;
		case 'wrong_length':
			++$bad['payload_bytes'];
			break;
		case 'too_large_decoded':
			$bad['payload_bytes'] = WCJ_AI_Report_Receipt::MAX_BYTES + 1;
			break;
		case 'changed_envelope':
			$bad['payload_b64']    = base64_encode( $bytes . ' ' );
			$bad['payload_bytes']  = strlen( $bytes ) + 1;
			$bad['payload_sha256'] = hash( 'sha256', $bytes . ' ' );
			break;
		case 'wrong_created':
			++$bad['review_created'];
			break;
		case 'wrong_shared_time':
			$bad['shared_at'] = $time + 300;
			break;
	}
	unset( $bad );
	corrupt( $value );
	denied( 'stored_payload_' . $mode, fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 10 ) );
}

foreach ( array( WCJ_AI_Report_Journal::MARKER, WCJ_AI_Report_Journal::KEY ) as $target ) {
	foreach ( array( 'reject', 'lost_ack', 'throw_after_write', 'readback_error' ) as $fault ) {
		$gate        = fresh( false );
		$wpdb->fault = function ( $key ) use ( $target, $fault ) {
			if ( $key !== $target ) {
				return ''; }
			if ( 'readback_error' === $fault ) {
				$GLOBALS['wpdb']->read_fault = function () {
					$GLOBALS['wpdb']->last_error = 'synthetic_sql_error';
					return array();
				};
				return '';
			}
			return $fault;
		};
		denied( 'init_' . $target . '_' . $fault, fn() => WCJ_AI_Report_Journal::initialize( $gate, true ), 'ai_report_outcome_unknown' );
		check( 'init_no_retry_' . $target . '_' . $fault, count( $wpdb->writes ) <= 3 );
	}
}
foreach ( array( 'reserve', 'finish', 'share', 'purge' ) as $operation ) {
	foreach ( array( 'reject', 'lost_ack', 'throw_after_write', 'readback_error', 'readback_missing', 'readback_oversize' ) as $fault ) {
		$gate         = fresh();
		$entry        = 'reserve' === $operation ? null : ( 'finish' === $operation ? reserve( $gate ) : pending( $gate ) );
		$wpdb->writes = array();
		$attempts     = 0;
		$wpdb->fault  = function ( $key ) use ( $fault, &$attempts ) {
			if ( WCJ_AI_Report_Journal::KEY !== $key ) {
				return ''; }
			++$attempts;
			if ( str_starts_with( $fault, 'readback_' ) ) {
				$GLOBALS['wpdb']->read_fault = function ( $read_key ) use ( $fault ) {
					if ( WCJ_AI_Report_Journal::KEY !== $read_key ) {
						return null; }
					if ( 'readback_error' === $fault ) {
						$GLOBALS['wpdb']->last_error = 'synthetic_sql_error'; }
					return 'readback_oversize' === $fault ? array(
						array(
							'bytes'    => '786433',
							'raw'      => null,
							'autoload' => 'no',
						),
					) : array();
				};
				return '';
			}
			return $fault;
		};
		$call         = match ( $operation ) {
			'reserve' => fn() => reserve( $gate ),
			'finish' => fn() => WCJ_AI_Report_Journal::finish( $gate, $binding, $entry['id'], 1, $bytes, $time + 7 ),
			'share' => fn() => WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, $digest, $time + 8 ),
			'purge' => fn() => WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 2 ),
		};
		denied( $operation . '_' . $fault, $call, 'ai_report_outcome_unknown' );
		check( $operation . '_one_attempt_' . $fault, 1 === $attempts && count( $wpdb->writes ) <= 1 );
	}
}

$gate     = fresh();
$entry    = shared( $gate );
$before   = $wpdb->rows;
$bad_gate = array(
	'exists' => true,
	'raw'    => str_repeat( 'f', 64 ),
);
denied( 'wrong_gate_no_access', fn() => WCJ_AI_Report_Journal::read_shared( $bad_gate, $binding['recipient'], $entry['id'], $time + 9 ), 'ai_gate_not_owned' );
check( 'wrong_gate_no_write', $before === $wpdb->rows );
$emitted     = null;
$wpdb->fault = fn( $key ) => WCJ_AI_Mutation_Gate::KEY === $key ? 'lost_ack' : '';
denied(
	'release_failure_no_emit_caller_contract',
	function () use ( $gate, $binding, $entry, $time, &$emitted ) {
		$copy = WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 );
		WCJ_AI_Mutation_Gate::release( $gate );
		$emitted = $copy;
	},
	'ai_gate_release_unknown'
);
check( 'caller_did_not_emit_on_release_failure', null === $emitted );

$gate              = fresh();
$entry             = reserve( $gate );
$value             = journal();
$value['revision'] = WCJ_AI_Report_Journal::MAX_REVISION;
corrupt( $value );
$before = $wpdb->rows;
denied( 'global_revision_overflow_no_mutation', fn() => WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 1 ) );
check( 'overflow_state_unchanged', $before === $wpdb->rows );

foreach ( array( 'sql_error', 'null_rows', 'duplicate_rows', 'wrong_raw_type', 'wrong_length_type', 'length_mismatch' ) as $mode ) {
	$gate             = fresh();
	$entry            = reserve( $gate );
	$before           = $wpdb->rows;
	$wpdb->read_fault = function ( $key ) use ( $mode ) {
		if ( WCJ_AI_Report_Journal::KEY !== $key ) {
			return null; }
		$row = array(
			'bytes'    => '2',
			'raw'      => '{}',
			'autoload' => 'no',
		);
		switch ( $mode ) {
			case 'sql_error':
				$GLOBALS['wpdb']->last_error = 'private_sql_not_echoed';
				return array();
			case 'null_rows':
				return false;
			case 'duplicate_rows':
				return array( $row, $row );
			case 'wrong_raw_type':
				$row['raw'] = array();
				break;
			case 'wrong_length_type':
				$row['bytes'] = 2.0;
				break;
			case 'length_mismatch':
				$row['bytes'] = '3';
				break;
		}
		return array( $row );
	};
	denied( 'reader_' . $mode, fn() => WCJ_AI_Report_Journal::purge( $gate, $owner, $entry['id'], 1 ) );
	check( 'reader_no_write_' . $mode, $before === $wpdb->rows );
}

foreach ( array( 'root_unavailable', 'stream_unavailable', 'observed_empty', 'decimal_max' ) as $mode ) {
	$gate   = fresh();
	$entry  = reserve( $gate );
	$source = WCJ_AI_Revenue_Fixture::source();
	switch ( $mode ) {
		case 'root_unavailable':
			$source['storage'] = null;
			$source['periods'] = null;
			$source['streams'] = array();
			$source['reason']  = 'observation_unavailable';
			break;
		case 'stream_unavailable':
			$source['streams']['recorded_refund_events'] = array(
				'status'       => 'unavailable',
				'amount_basis' => 'recorded_refund_amount_not_return_requests',
				'groups'       => null,
				'reason'       => 'query_unavailable',
			);
			break;
		case 'observed_empty':
			$source['streams']['recorded_refund_events']['groups'] = array();
			break;
		case 'decimal_max':
			$source['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = '9223372036854.775807';
			break;
	}
	$variant = WCJ_AI_Report_Receipt::build( $source, $time, $time + 900, $time + 6 );
	WCJ_AI_Report_Journal::finish( $gate, $binding, $entry['id'], 1, $variant, $time + 7 );
	WCJ_AI_Report_Journal::share( $gate, $binding, $entry['id'], 2, hash( 'sha256', $variant ), $time + 8 );
	check( 'unchanged_variant_' . $mode, WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 ) === $variant );
}

$gate  = fresh();
$entry = shared( $gate );
$copy  = WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 );
WCJ_AI_Mutation_Gate::release( $gate );
check( 'successful_release_then_emit_caller_contract', $copy === $bytes && ! isset( $wpdb->rows[ WCJ_AI_Mutation_Gate::KEY ] ) );
denied( 'released_gate_denies_access', fn() => WCJ_AI_Report_Journal::read_shared( $gate, $binding['recipient'], $entry['id'], $time + 9 ), 'ai_gate_not_owned' );
// Capacity arithmetic only: this is deliberately NOT a valid 32768-byte envelope.
check( 'decoded_vs_base64_capacity_arithmetic_only', 43692 === strlen( base64_encode( str_repeat( 'x', WCJ_AI_Report_Receipt::MAX_BYTES ) ) ) && 43692 === WCJ_AI_Report_Journal::MAX_BASE64 );

foreach ( array( 'reject', 'lost_ack' ) as $fault ) {
	$gate        = fresh();
	$wpdb->fault = fn( $key ) => WCJ_AI_Report_Journal::KEY === $key ? $fault : '';
	denied( 'reservation_reconcile_' . $fault, fn() => reserve( $gate ), 'ai_report_outcome_unknown' );
	$wpdb->fault = null;
	$before      = $wpdb->rows;
	$reconciled  = WCJ_AI_Report_Journal::lookup_reserved( $gate, $binding );
	check( 'lookup_only_no_write_' . $fault, $before === $wpdb->rows );
	if ( 'reject' === $fault ) {
		check( 'lookup_absence_not_retry_authority', null === $reconciled );
	} else {
		check( 'lookup_recovers_lost_id_no_recapture', is_array( $reconciled ) && 'collecting' === $reconciled['state'] && 64 === strlen( $reconciled['id'] ) );
		$other                         = $binding;
		$other['owner_session_digest'] = str_repeat( 'f', 64 );
		denied( 'lookup_other_session_no_payload', fn() => WCJ_AI_Report_Journal::lookup_reserved( $gate, $other ), 'ai_report_busy' );
	}
}

echo json_encode(
	array(
		'scope'  => 'fake DB/CAS only; no native SQL/auth/consent/client or runtime inclusion',
		'php'    => PHP_VERSION,
		'cases'  => $cases,
		'passed' => count( $cases ),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
), "\n";
