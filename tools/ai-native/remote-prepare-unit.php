<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Standalone CLI harness includes fake native classes, not an autoloadable runtime class.
/**
 * Deterministic orchestration/fault model. NOT WordPress, SQL or client proof.
 * Real plan, gate and connection source; fake storage/authentication primitives.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName, Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.WP.GlobalVariablesOverride, WordPress.DB.SlowDBQuery, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Test-only fake native storage/auth signatures and deterministic fixtures; no WordPress bootstrap or real data.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Squiz.Commenting.FunctionComment, Squiz.Commenting.VariableComment, Generic.Files.OneObjectStructurePerFile, WordPress.WP.AlternativeFunctions -- Standalone fake WordPress primitives; never bootstrap a real store.
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'WCJ_AI_PREVIEW', true );
$wcj_ai_db     = array();
$wcj_ai_user   = 1;
$wcj_ai_fault  = null;
$wcj_ai_writes = array();
$wcj_ai_cases  = array();
$wcj_ai_apps   = array();
$wcj_ai_uuid   = '11111111-1111-4111-8111-111111111111';

/** Minimal SQL read model; no claim about the real database implementation. */
final class WCJ_AI_Prepare_DB {
	public $options    = 'test_options';
	public $usermeta   = 'test_usermeta';
	public $last_error = '';
	public function prepare( $query, ...$args ) {
		return array( $query, $args );
	}
	public function esc_like( $value ) {
		return $value;
	}
	public function get_row( $query, $format ) {
		if ( str_contains( $query[0], ' LIKE ' ) ) {
			$rows = $this->get_results( $query, $format );
			return $rows[0] ?? null;
		}
		return isset( $GLOBALS['wcj_ai_db'][ $query[1][0] ] ) ? array( 'option_value' => $GLOBALS['wcj_ai_db'][ $query[1][0] ] ) : null;
	}
	public function get_results( $query, $format ) {
		if ( str_contains( $query[0], 'meta_value' ) ) {
			return isset( $GLOBALS['wcj_ai_apps'][ $query[1][0] ] ) ? array( array( 'meta_value' => serialize( $GLOBALS['wcj_ai_apps'][ $query[1][0] ] ) ) ) : array();
		}
		$rows = array();
		foreach ( array_keys( $GLOBALS['wcj_ai_db'] ) as $key ) {
			if ( str_starts_with( $key, 'wcj_ai_invoice_plan_' ) && ! in_array( $key, array_slice( $query[1], 1 ), true ) ) {
				$rows[] = array( 'option_name' => $key );
			}
		}
		return array_slice( $rows, 0, 51 );
	}
}
$wpdb = new WCJ_AI_Prepare_DB();

/** CAS fault model, distinct from the existing actual SQL store implementation. */
final class WCJ_Job_Store {
	public static function compare_exchange( $key, $before, $after, $no_autoload = array() ) {
		global $wcj_ai_db, $wcj_ai_fault, $wcj_ai_writes;
		$state = array(
			'exists' => array_key_exists( $key, $wcj_ai_db ),
			'raw'    => $wcj_ai_db[ $key ] ?? '',
		);
		if ( $state !== $before ) {
			return false;
		}
		$mode = $wcj_ai_fault ? $wcj_ai_fault( $key, $before, $after ) : '';
		if ( 'reject' === $mode ) {
			return false;
		}
		$wcj_ai_writes[] = $key;
		if ( $after['exists'] ) {
			$wcj_ai_db[ $key ] = $after['raw'];
		} else {
			unset( $wcj_ai_db[ $key ] );
		}
		return 'lost_ack' !== $mode;
	}
	public static function lock( $key, $busy ) {
		if ( isset( $GLOBALS['wcj_ai_on_lock'] ) ) {
			$GLOBALS['wcj_ai_on_lock']();
		}
		if ( isset( $GLOBALS['wcj_ai_db'][ $key ] ) ) {
			throw new RuntimeException( esc_html( $busy ) );
		}
		$mine = array(
			'exists' => true,
			'raw'    => 'native-lock',
		);
		self::compare_exchange(
			$key,
			array(
				'exists' => false,
				'raw'    => '',
			),
			$mine
		);
		return $mine;
	}
	public static function unlock( $key, $mine ) {
		self::compare_exchange(
			$key,
			$mine,
			array(
				'exists' => false,
				'raw'    => '',
			)
		);
	}
}
/** Owner authorization stub, not the native capability/policy implementation. */
final class WCJ_Invoice_Job {
	const LOCK = 'wcj_invoice_job_lock';
	public static function authorize() {
		if ( ! in_array( get_current_user_id(), array( 1, 4 ), true ) ) {
			throw new RuntimeException( 'owner_only' );
		}
	}
}
function esc_html( $value ) {
	return $value; }
function wp_json_encode( $value ) {
	return json_encode( $value ); }
function get_current_user_id() {
	return $GLOBALS['wcj_ai_user']; }
function get_current_blog_id() {
	return 1; }
function is_multisite() {
	return false; }
function wp_using_ext_object_cache() {
	return false; }
function has_filter( $name ) {
	return false; }
function wcj_is_module_enabled( $name ) {
	return false; }
function user_can( $id, $cap ) {
	return in_array( $id, array( 1, 4 ), true ); }
function get_user_meta( $id, $key, $single ) {
	return in_array( $id, array( 2, 3 ), true ) && '_wcj_ai_principal' === $key ? '1' : ''; }
function get_userdata( $id ) {
	return (object) array(
		'ID'    => $id,
		'roles' => array(),
		'caps'  => array(
			'read'          => true,
			'wcj_ai_client' => true,
		),
	); }
function rest_get_authenticated_app_password() {
	return $GLOBALS['wcj_ai_uuid']; }
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
require_once dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-connection.php';
require_once dirname( __DIR__, 2 ) . '/includes/admin/class-wcj-ai-invoice-plans.php';

function wcj_ai_check( $name, $value ) {
	$GLOBALS['wcj_ai_cases'][ $name ] = true === $value;
}
function wcj_ai_denied( $callback ) {
	try {
		$callback();
	} catch ( RuntimeException | InvalidArgumentException $e ) {
		return true; }
	return false;
}
function wcj_ai_reset() {
	unset( $GLOBALS['wcj_ai_on_lock'] );
	$GLOBALS['wcj_ai_db']     = array();
	$GLOBALS['wcj_ai_user']   = 1;
	$GLOBALS['wcj_ai_fault']  = null;
	$GLOBALS['wcj_ai_writes'] = array();
	$GLOBALS['wcj_ai_apps']   = array(
		2 => array( array( 'uuid' => $GLOBALS['wcj_ai_uuid'] ) ),
		3 => array( array( 'uuid' => $GLOBALS['wcj_ai_uuid'] ) ),
	);
}
function wcj_ai_auth( $id = 2 ) {
	$GLOBALS['wcj_ai_user'] = $id;
	foreach ( array(
		'admitted'      => true,
		'rejected'      => false,
		'authenticated' => array(
			'id'   => $id,
			'uuid' => $GLOBALS['wcj_ai_uuid'],
		),
	) as $key => $value ) {
		$property = new ReflectionProperty( WCJ_AI_Connection::class, $key );
		$property->setValue( null, $value );
	}
}
function wcj_ai_input( $key = 'synthetic-request-0001' ) {
	return array(
		'schema_version'  => 1,
		'task'            => 'invoice_presentation',
		'changes'         => array( 'header_text_color' => '#445566' ),
		'idempotency_key' => $key,
	);
}
function wcj_ai_local() {
	$input = wcj_ai_input();
	unset( $input['idempotency_key'] );
	return WCJ_AI_Invoice_Plans::create( $input );
}
function wcj_ai_fixture( $prepare = true ) {
	wcj_ai_reset();
	WCJ_AI_Invoice_Plans::initialize();
	WCJ_AI_Connection::issue( 2, $GLOBALS['wcj_ai_uuid'], $prepare );
	wcj_ai_auth();
}
function wcj_ai_index() {
	return json_decode( $GLOBALS['wcj_ai_db'][ WCJ_AI_Invoice_Plans::INDEX ], true ); }
function wcj_ai_set_index( $index ) {
	$GLOBALS['wcj_ai_db'][ WCJ_AI_Invoice_Plans::INDEX ] = wp_json_encode( $index ); }
function wcj_ai_writes_without_gate() {
	return array_values( array_filter( $GLOBALS['wcj_ai_writes'], static fn( $key ) => WCJ_AI_Mutation_Gate::KEY !== $key ) );
}

wcj_ai_reset();
wcj_ai_check( 'local_create_requires_explicit_initialize', wcj_ai_denied( 'wcj_ai_local' ) && ! isset( $wcj_ai_db[ WCJ_AI_Invoice_Plans::INITIALIZED ] ) );
wcj_ai_check(
	'explicit_initialize',
	WCJ_AI_Invoice_Plans::initialize() && array(
		'schema'  => 2,
		'entries' => array(),
	) === wcj_ai_index()
);
wcj_ai_check( 'initialize_reentry_preserves_bytes', WCJ_AI_Invoice_Plans::initialize() && array() === wcj_ai_index()['entries'] );
$wcj_ai_plan = wcj_ai_local();
wcj_ai_check( 'local_allocation_integrated', 1 === count( wcj_ai_index()['entries'] ) && 'pending' === $wcj_ai_plan['status'] && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );
wcj_ai_check( 'local_apply_and_recover', 'applied' === WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] )['status'] && 'recovered' === WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['id'] )['status'] );

foreach ( array( 'marker_without_index', 'orphan_without_index', 'v2_without_marker', 'duplicate_legacy', 'marker_with_legacy', 'orphan_with_legacy', 'malformed_index' ) as $wcj_ai_bad ) {
	wcj_ai_reset();
	$id = str_repeat( 'a', 32 );
	switch ( $wcj_ai_bad ) {
		case 'marker_without_index':
			$wcj_ai_db[ WCJ_AI_Invoice_Plans::INITIALIZED ] = '1';
			break;
		case 'orphan_without_index':
			$wcj_ai_db[ 'wcj_ai_invoice_plan_' . $id ] = '{}';
			break;
		case 'v2_without_marker':
			wcj_ai_set_index(
				array(
					'schema'  => 2,
					'entries' => array(),
				)
			);
			break;
		case 'duplicate_legacy':
			wcj_ai_set_index( array( $id, $id ) );
			break;
		case 'marker_with_legacy':
			$wcj_ai_db[ WCJ_AI_Invoice_Plans::INITIALIZED ] = '1';
			wcj_ai_set_index( array( $id ) );
			break;
		case 'orphan_with_legacy':
			wcj_ai_set_index( array() );
			$wcj_ai_db[ 'wcj_ai_invoice_plan_' . $id ] = '{}';
			break;
		case 'malformed_index':
			$wcj_ai_db[ WCJ_AI_Invoice_Plans::INDEX ] = '{bad';
			break;
	}
	$before = $wcj_ai_db;
	wcj_ai_check( $wcj_ai_bad, wcj_ai_denied( array( WCJ_AI_Invoice_Plans::class, 'initialize' ) ) && $before === $wcj_ai_db );
}
wcj_ai_reset();
wcj_ai_set_index( array_map( static fn( $n ) => str_pad( dechex( $n ), 32, '0', STR_PAD_LEFT ), range( 1, 49 ) ) );
WCJ_AI_Invoice_Plans::initialize();
wcj_ai_local();
wcj_ai_check( 'legacy_49_migrated_and_50_retained', 50 === count( wcj_ai_index()['entries'] ) && 'legacy' === wcj_ai_index()['entries'][0]['kind'] );
wcj_ai_check( '51st_allocation_denied', wcj_ai_denied( 'wcj_ai_local' ) && 50 === count( wcj_ai_index()['entries'] ) );

wcj_ai_reset();
$wcj_ai_gate = WCJ_AI_Mutation_Gate::acquire();
wcj_ai_check( 'gate_second_holder_denied', wcj_ai_denied( array( WCJ_AI_Mutation_Gate::class, 'acquire' ) ) );
wcj_ai_check(
	'gate_wrong_token_no_release',
	wcj_ai_denied(
		static fn() => WCJ_AI_Mutation_Gate::release(
			array(
				'exists' => true,
				'raw'    => str_repeat( 'f', 64 ),
			)
		)
	) && $wcj_ai_gate['raw'] === $wcj_ai_db[ WCJ_AI_Mutation_Gate::KEY ]
);
WCJ_AI_Mutation_Gate::release( $wcj_ai_gate );
$wcj_ai_db[ WCJ_AI_Mutation_Gate::KEY ] = serialize(
	array(
		't'  => time() - 86400,
		'id' => 'old',
	)
);
wcj_ai_check( 'old_gate_never_age_reclaimed', wcj_ai_denied( array( WCJ_AI_Mutation_Gate::class, 'acquire' ) ) && str_contains( $wcj_ai_db[ WCJ_AI_Mutation_Gate::KEY ], 'old' ) );
wcj_ai_reset();
$wcj_ai_fault = static fn( $key ) => WCJ_AI_Mutation_Gate::KEY === $key ? 'lost_ack' : '';
wcj_ai_check( 'lost_gate_ack_no_body_and_persisted_gate', wcj_ai_denied( array( WCJ_AI_Invoice_Plans::class, 'initialize' ) ) && isset( $wcj_ai_db[ WCJ_AI_Mutation_Gate::KEY ] ) && ! isset( $wcj_ai_db[ WCJ_AI_Invoice_Plans::INDEX ] ) );
$wcj_ai_fault = null;
wcj_ai_check( 'unknown_gate_stays_busy', wcj_ai_denied( array( WCJ_AI_Mutation_Gate::class, 'acquire' ) ) );

wcj_ai_fixture( false );
$before = $wcj_ai_db;
wcj_ai_check( 'read_scope_cannot_prepare', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) ) && $before === $wcj_ai_db );
wcj_ai_check( 'dedicated_principal_cannot_local_create', wcj_ai_denied( 'wcj_ai_local' ) );
wcj_ai_fixture();
$wcj_ai_plan = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
wcj_ai_check( 'prepare_no_impersonation_or_settings', 2 === get_current_user_id() && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) && 'pending' === $wcj_ai_plan['status'] );
wcj_ai_check( 'closed_remote_handoff', array_keys( $wcj_ai_plan ) === array( 'schema_version', 'plan_id', 'status', 'expires_at', 'requires_local_approval', 'non_atomic' ) );
wcj_ai_check( 'dedicated_principal_cannot_inspect_apply_recover', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['plan_id'] ) ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['plan_id'] ) ) );
$wcj_ai_writes = array();
wcj_ai_check( 'identical_retry_same_plan_read_only', WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) === $wcj_ai_plan && array() === wcj_ai_writes_without_gate() );
$changed                                 = wcj_ai_input();
$changed['changes']['header_text_color'] = '#123456';
wcj_ai_check( 'same_key_different_payload_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( $changed ) ) && 1 === count( wcj_ai_index()['entries'] ) );
foreach ( array( 'owner', 'site', 'before', 'status', 'expires', 'grant' ) as $wcj_ai_extra ) {
	$input                  = wcj_ai_input();
	$input[ $wcj_ai_extra ] = 1;
	wcj_ai_check( 'reject_caller_' . $wcj_ai_extra, wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( $input ) ) );
}
for ( $i = 2; $i <= 8; ++$i ) {
	WCJ_AI_Invoice_Plans::prepare( wcj_ai_input( 'synthetic-request-' . str_pad( (string) $i, 4, '0', STR_PAD_LEFT ) ) ); }
wcj_ai_check( 'grant_eight_cap', 8 === count( wcj_ai_index()['entries'] ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input( 'synthetic-request-0009' ) ) ) );
wcj_ai_check( 'retry_precedes_full_quota', WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) === $wcj_ai_plan );
$wcj_ai_user = 1;
WCJ_AI_Connection::issue( 3, $wcj_ai_uuid, true );
wcj_ai_auth( 3 );
for ( $i = 1; $i <= 4; ++$i ) {
	WCJ_AI_Invoice_Plans::prepare( wcj_ai_input( 'second-request-key-' . $i ) ); }
wcj_ai_check( 'site_twelve_cap', 12 === count( wcj_ai_index()['entries'] ) && wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input( 'second-request-key-5' ) ) ) );

foreach ( array( 'index_lost_ack', 'plan_rejected', 'plan_lost_ack' ) as $wcj_ai_mode ) {
	wcj_ai_fixture();
	$wcj_ai_fault = static function ( $key, $before, $after ) use ( $wcj_ai_mode ) {
		if ( 'index_lost_ack' === $wcj_ai_mode && WCJ_AI_Invoice_Plans::INDEX === $key ) {
			return 'lost_ack'; }
		if ( str_starts_with( $key, 'wcj_ai_invoice_plan_' ) && ! in_array( $key, array( WCJ_AI_Invoice_Plans::INDEX, WCJ_AI_Invoice_Plans::INDEX . '_lock' ), true ) ) {
			return 'plan_rejected' === $wcj_ai_mode ? 'reject' : 'lost_ack'; }
		return '';
	};
	wcj_ai_check( $wcj_ai_mode . '_reports_unknown', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) ) );
	$wcj_ai_fault  = null;
	$wcj_ai_writes = array();
	if ( 'plan_lost_ack' === $wcj_ai_mode ) {
		$retry = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
		$ok    = wcj_ai_index()['entries'][0]['id'] === $retry['plan_id'];
	} else {
		$ok = wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) );
	}
	wcj_ai_check( $wcj_ai_mode . '_retry_never_rewrites', $ok && array() === wcj_ai_writes_without_gate() && 1 === count( wcj_ai_index()['entries'] ) );
}

wcj_ai_fixture();
$wcj_ai_plan = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
$wcj_ai_user = 1;
$wcj_ai_gate = WCJ_AI_Mutation_Gate::acquire();
wcj_ai_check( 'supported_revoke_busy_not_success', wcj_ai_denied( static fn() => WCJ_AI_Connection::revoke( 2 ) ) && true === json_decode( $wcj_ai_db['wcj_ai_client_grant_2'], true )['active'] );
wcj_ai_check( 'remote_apply_requires_gate', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );
WCJ_AI_Mutation_Gate::release( $wcj_ai_gate );
wcj_ai_check( 'remote_apply_intended_only', 'applied' === WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] )['status'] && '#445566' === $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] );
wcj_ai_check( 'supported_revoke_readback', WCJ_AI_Connection::revoke( 2 ) && false === json_decode( $wcj_ai_db['wcj_ai_client_grant_2'], true )['active'] );
wcj_ai_check( 'known_recovery_after_revoke', 'recovered' === WCJ_AI_Invoice_Plans::recover( $wcj_ai_plan['plan_id'] )['status'] && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );

foreach ( array( 'revoked', 'rotated', 'ap_deleted', 'expired', 'context_tampered', 'intent_tampered', 'other_owner' ) as $wcj_ai_mode ) {
	wcj_ai_fixture();
	$wcj_ai_plan = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
	$wcj_ai_user = 1;
	if ( 'revoked' === $wcj_ai_mode ) {
		WCJ_AI_Connection::revoke( 2 ); }
	if ( 'rotated' === $wcj_ai_mode ) {
		WCJ_AI_Connection::issue( 2, $wcj_ai_uuid, true ); }
	if ( 'ap_deleted' === $wcj_ai_mode ) {
		$wcj_ai_apps[2] = array(); }
	if ( 'expired' === $wcj_ai_mode ) {
		$g                                  = json_decode( $wcj_ai_db['wcj_ai_client_grant_2'], true );
		$g['expires']                       = time() - 1;
		$wcj_ai_db['wcj_ai_client_grant_2'] = wp_json_encode( $g ); }
	if ( in_array( $wcj_ai_mode, array( 'context_tampered', 'intent_tampered' ), true ) ) {
		$key = 'wcj_ai_invoice_plan_' . $wcj_ai_plan['plan_id'];
		$doc = json_decode( $wcj_ai_db[ $key ], true );
		if ( 'context_tampered' === $wcj_ai_mode ) {
			$doc['connection']['site'] = 2;
		} else {
			$doc['changes']['header_text_color'] = '#aabbcc'; }
		$wcj_ai_db[ $key ] = wp_json_encode( $doc );
	}
	if ( 'other_owner' === $wcj_ai_mode ) {
		$wcj_ai_user = 4; }
	wcj_ai_check( $wcj_ai_mode . '_apply_denied_without_setting_write', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );
}
wcj_ai_fixture();
$wcj_ai_plan = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
$wcj_ai_user = 1;
WCJ_AI_Connection::revoke( 2 );
$key           = 'wcj_ai_invoice_plan_' . $wcj_ai_plan['plan_id'];
$doc           = json_decode( $wcj_ai_db[ $key ], true );
$doc['schema'] = 1;
unset( $doc['connection'] );
$wcj_ai_db[ $key ] = wp_json_encode( $doc );
wcj_ai_check( 'remote_schema_downgrade_after_revoke_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );
wcj_ai_reset();
WCJ_AI_Invoice_Plans::initialize();
$wcj_ai_plan                         = wcj_ai_local();
$key                                 = 'wcj_ai_invoice_plan_' . $wcj_ai_plan['id'];
$doc                                 = json_decode( $wcj_ai_db[ $key ], true );
$doc['changes']['header_text_color'] = '#aabbcc';
$wcj_ai_db[ $key ]                   = wp_json_encode( $doc );
wcj_ai_check( 'local_immutable_intent_tampering_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['id'] ) ) );
// Simulate an original schema-one list for compatibility, not a history reset API.
wcj_ai_set_index( array( $wcj_ai_plan['id'] ) );
unset( $wcj_ai_db[ WCJ_AI_Invoice_Plans::INITIALIZED ] );
wcj_ai_check( 'legacy_plan_readable_before_migration', 'pending' === WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['id'] )['status'] );
WCJ_AI_Invoice_Plans::initialize();
wcj_ai_check( 'legacy_plan_readable_after_migration', 'pending' === WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['id'] )['status'] );

foreach ( array( 'marker_ack', 'initial_index_ack', 'release_ack' ) as $wcj_ai_mode ) {
	wcj_ai_reset();
	$wcj_ai_fault = static function ( $key, $before, $after ) use ( $wcj_ai_mode ) {
		return ( ( 'marker_ack' === $wcj_ai_mode && WCJ_AI_Invoice_Plans::INITIALIZED === $key ) ||
			( 'initial_index_ack' === $wcj_ai_mode && WCJ_AI_Invoice_Plans::INDEX === $key ) ||
			( 'release_ack' === $wcj_ai_mode && WCJ_AI_Mutation_Gate::KEY === $key && ! $after['exists'] ) ) ? 'lost_ack' : '';
	};
	wcj_ai_check( $wcj_ai_mode . '_initialization_unknown', wcj_ai_denied( array( WCJ_AI_Invoice_Plans::class, 'initialize' ) ) );
	$wcj_ai_fault = null;
	if ( 'marker_ack' === $wcj_ai_mode ) {
		wcj_ai_check( 'marker_ack_no_reset', wcj_ai_denied( array( WCJ_AI_Invoice_Plans::class, 'initialize' ) ) && ! isset( $wcj_ai_db[ WCJ_AI_Invoice_Plans::INDEX ] ) );
	} else {
		wcj_ai_check(
			$wcj_ai_mode . '_persisted_index_not_lost',
			array(
				'schema'  => 2,
				'entries' => array(),
			) === wcj_ai_index()
		);
	}
}

wcj_ai_fixture();
$wcj_ai_plan  = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
$wcj_ai_user  = 1;
$wcj_ai_fault = static fn( $key, $before, $after ) => WCJ_AI_Mutation_Gate::KEY === $key && ! $after['exists'] ? 'lost_ack' : '';
wcj_ai_check( 'known_apply_release_unknown_reported', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) );
$wcj_ai_fault = null;
wcj_ai_check( 'known_applied_state_survives_release_unknown', 'applied' === WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['plan_id'] )['status'] && '#445566' === $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] );
wcj_ai_check( 'known_apply_never_replayed_after_release_unknown', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) );
$wcj_ai_fault = static fn( $key, $before, $after ) => WCJ_AI_Mutation_Gate::KEY === $key && ! $after['exists'] ? 'lost_ack' : '';
wcj_ai_check( 'known_revoke_release_unknown_reported', wcj_ai_denied( static fn() => WCJ_AI_Connection::revoke( 2 ) ) );
$wcj_ai_fault = null;
wcj_ai_check( 'known_inactive_state_survives_release_unknown', false === json_decode( $wcj_ai_db['wcj_ai_client_grant_2'], true )['active'] );
wcj_ai_auth();
wcj_ai_check( 'native_ap_still_present_does_not_revive_inactive_grant', null === WCJ_AI_Connection::prepare_context() );

foreach ( array( 'grant_expires', 'ap_removed' ) as $wcj_ai_mode ) {
	wcj_ai_fixture();
	if ( 'grant_expires' === $wcj_ai_mode ) {
		$grant                              = json_decode( $wcj_ai_db['wcj_ai_client_grant_2'], true );
		$grant['expires']                   = time() + 2;
		$wcj_ai_db['wcj_ai_client_grant_2'] = wp_json_encode( $grant );
	}
	$grant_before   = $wcj_ai_db['wcj_ai_client_grant_2'];
	$wcj_ai_plan    = WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() );
	$wcj_ai_user    = 1;
	$reached_lock   = false;
	$wcj_ai_on_lock = static function () use ( $wcj_ai_mode, &$reached_lock ) {
		$reached_lock = true;
		if ( 'ap_removed' === $wcj_ai_mode ) {
			$GLOBALS['wcj_ai_apps'][2] = array();
		} else {
			sleep( 3 ); // Advance actual time past expiry without changing grant/digest.
		}
	};
	wcj_ai_check( $wcj_ai_mode . '_after_native_lock_before_intent_denied', wcj_ai_denied( static fn() => WCJ_AI_Invoice_Plans::apply( $wcj_ai_plan['plan_id'] ) ) && $reached_lock && $grant_before === $wcj_ai_db['wcj_ai_client_grant_2'] && 'pending' === WCJ_AI_Invoice_Plans::inspect( $wcj_ai_plan['plan_id'] )['status'] && ! isset( $wcj_ai_db['wcj_invoicing_invoice_header_text_color'] ) );
}

echo json_encode(
	array(
		'scope'  => 'Real source with fake CAS/auth/SQL primitives; not native, HTTP, concurrency or client evidence.',
		'cases'  => $wcj_ai_cases,
		'passed' => count( array_filter( $wcj_ai_cases ) ),
		'total'  => count( $wcj_ai_cases ),
	),
	JSON_PRETTY_PRINT
) . "\n";
exit( in_array( false, $wcj_ai_cases, true ) ? 1 : 0 );
