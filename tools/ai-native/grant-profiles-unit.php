<?php
/**
 * Closed grant/profile orchestration contracts with fake native auth/SQL/CAS.
 * No WordPress runtime, real AP, cookie/consent, adapter or client proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.GlobalVariablesOverride, WordPress.WP.AlternativeFunctions, Squiz.Commenting.FunctionComment -- Standalone fake fixture, never deployed or registered.
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
define( 'WCJ_AI_TEST_BOOTSTRAP_ONLY', true );
require __DIR__ . '/remote-prepare-unit.php';
require_once __DIR__ . '/class-wcj-ai-projection-poison.php';

function profile_fixture( $profile = 'report' ) {
	wcj_ai_reset();
	if ( 'report' === $profile ) {
		WCJ_AI_Connection::issue_report( 2, $GLOBALS['wcj_ai_uuid'] );
	} else {
		WCJ_AI_Connection::issue( 2, $GLOBALS['wcj_ai_uuid'], 'prepare' === $profile );
	}
	wcj_ai_auth();
	return json_decode( $GLOBALS['wcj_ai_db']['wcj_ai_client_grant_2'], true );
}
function profile_raw() {
	return $GLOBALS['wcj_ai_db']['wcj_ai_client_grant_2'];
}
function profile_set( $grant ) {
	$GLOBALS['wcj_ai_db']['wcj_ai_client_grant_2'] = wp_json_encode( $grant );
}
function profile_denied( $name, $call ) {
	wcj_ai_check( $name, wcj_ai_denied( $call ) );
}

foreach ( array( 'read', 'prepare', 'report' ) as $profile ) {
	$grant = profile_fixture( $profile );
	$raw   = profile_raw();
	wcj_ai_check( 'exact_profile_' . $profile, WCJ_AI_Grant_Profile::decode( $raw ) === $grant );
	wcj_ai_check( 'transport_' . $profile, WCJ_AI_Connection::transport_authenticated() );
	wcj_ai_check( 'invoice_predicate_' . $profile, ( 'report' !== $profile ) === WCJ_AI_Connection::allowed() );
	wcj_ai_check( 'report_predicate_' . $profile, ( 'report' === $profile ) === WCJ_AI_Connection::report_allowed() );
	wcj_ai_check( 'prepare_predicate_' . $profile, ( 'prepare' === $profile ) === ( null !== WCJ_AI_Connection::prepare_context() ) );
	$gate           = WCJ_AI_Mutation_Gate::acquire();
	$report_context = WCJ_AI_Connection::report_read_context( $gate );
	wcj_ai_check( 'under_gate_context_' . $profile, ( 'report' === $profile ) === ( null !== $report_context ) );
	WCJ_AI_Mutation_Gate::release( $gate );
	$wcj_ai_user = 1;
	$view        = WCJ_AI_Connection::owner_view( 2 );
	wcj_ai_check( 'owner_profile_generation_' . $profile, $view['generation'] === $grant['id'] && WCJ_AI_Grant_Profile::profile( $grant ) === $view['profile'] && false === strpos( wp_json_encode( $view ), $wcj_ai_uuid ) );
}

$valid = profile_fixture();
foreach ( array(
	'unknown_schema'                     => array( 'schema' => 3 ),
	'wrong_schema_scalar'                => array( 'schema' => '2' ),
	'invoice_scope_under_report_version' => array( 'scopes' => array( 'invoice:read' ) ),
	'mixed_scopes'                       => array( 'scopes' => array( 'report:read_shared', 'invoice:read' ) ),
	'empty_scopes'                       => array( 'scopes' => array() ),
	'duplicate_scope'                    => array( 'scopes' => array( 'report:read_shared', 'report:read_shared' ) ),
	'wildcard_scope'                     => array( 'scopes' => array( '*' ) ),
	'wrong_scopes_type'                  => array( 'scopes' => 'report:read_shared' ),
	'owner_principal_equal'              => array( 'owner' => 2 ),
	'zero_owner'                         => array( 'owner' => 0 ),
	'float_principal'                    => array( 'principal' => 2.0 ),
	'bool_site'                          => array( 'site' => true ),
	'wrong_id'                           => array( 'id' => str_repeat( 'A', 32 ) ),
	'wrong_uuid'                         => array( 'app_uuid' => 'not-native-uuid' ),
	'integer_active'                     => array( 'active' => 1 ),
	'zero_created'                       => array( 'created' => 0 ),
	'equal_expiry'                       => array( 'expires' => $valid['created'] ),
	'overlong_lifetime'                  => array( 'expires' => $valid['created'] + 3601 ),
	'integer_overflow_time'              => array( 'created' => PHP_INT_MAX ),
	'unknown_key'                        => array( 'customer' => 'SYNTHETIC_PRIVATE' ),
) as $name => $delta ) {
	$bad     = array_replace( $valid, $delta );
	$decoded = WCJ_AI_Grant_Profile::decode( json_encode( $bad, JSON_PRESERVE_ZERO_FRACTION ) );
	wcj_ai_check( 'parser_reject_' . $name, null === $decoded );
}
foreach ( array(
	'pretty'        => json_encode( $valid, JSON_PRETTY_PRINT ),
	'duplicate_key' => str_replace( '"schema":2', '"schema":2,"schema":2', json_encode( $valid ) ),
	'reordered'     => json_encode( array_reverse( $valid, true ) ),
	'trailing'      => json_encode( $valid ) . ' ',
	'oversize'      => str_repeat( 'x', 4097 ),
	'invalid_utf8'  => "\xff",
	'invalid_json'  => '{',
	'deep'          => str_repeat( '[', 12 ) . '0' . str_repeat( ']', 12 ),
	'not_string'    => $valid,
	'poison'        => new WCJ_AI_Projection_Poison(),
) as $name => $raw ) {
	$decoded = WCJ_AI_Grant_Profile::decode( $raw );
	wcj_ai_check( 'raw_reject_' . $name, null === $decoded );
}
wcj_ai_check( 'parser_no_conversion_callbacks', 0 === WCJ_AI_Projection_Poison::$calls );
$inactive            = $valid;
$inactive['active']  = false;
$inactive['created'] = 1;
$inactive['expires'] = 2;
wcj_ai_check( 'inactive_expired_shape_remains_manageable', WCJ_AI_Grant_Profile::decode( json_encode( $inactive ) ) === $inactive );
$bad           = $valid;
$bad['schema'] = 1;
wcj_ai_check( 'report_scope_under_legacy_version_rejected', null === WCJ_AI_Grant_Profile::decode( json_encode( $bad ) ) );
$bad['scopes'] = array( 'invoice:prepare', 'invoice:read' );
wcj_ai_check( 'reordered_invoice_scopes_rejected', null === WCJ_AI_Grant_Profile::decode( json_encode( $bad ) ) );

$grant        = profile_fixture();
$wcj_ai_reads = array();
$before       = $wcj_ai_db;
wcj_ai_check( 'report_invoice_schema_denied', is_wp_error( WCJ_AI_Abilities::schema() ) );
wcj_ai_check( 'report_invoice_context_denied', is_wp_error( WCJ_AI_Abilities::context() ) );
wcj_ai_check( 'report_invoice_prepare_denied', is_wp_error( WCJ_AI_Abilities::prepare( wcj_ai_input() ) ) );
profile_denied( 'report_direct_invoice_service_denied', fn() => WCJ_AI_Invoice_Plans::prepare( wcj_ai_input() ) );
$invoice_reads = array_filter( $wcj_ai_reads, static fn( $key ) => is_string( $key ) && str_starts_with( $key, 'wcj_invoicing_invoice_' ) );
wcj_ai_check( 'no_invoice_colour_reads_allocations_or_writes', array() === $invoice_reads && $before === $wcj_ai_db );
$gate    = WCJ_AI_Mutation_Gate::acquire();
$context = WCJ_AI_Connection::report_read_context( $gate );
wcj_ai_check( 'closed_report_context', array_keys( $context ) === array( 'grant_schema', 'owner', 'site', 'principal', 'app_uuid', 'grant_generation', 'grant_digest', 'grant_expires', 'kind', 'scope' ) && hash( 'sha256', profile_raw() ) === $context['grant_digest'] && $context['grant_expires'] === $grant['expires'] );
wcj_ai_check( 'report_context_current_eligible', WCJ_AI_Connection::report_context_eligible( $context, $gate ) );
$invoice_context = array(
	'grant'        => $grant['id'],
	'principal'    => 2,
	'owner'        => 1,
	'site'         => 1,
	'app_uuid'     => $wcj_ai_uuid,
	'grant_digest' => hash( 'sha256', profile_raw() ),
);
wcj_ai_check( 'report_cannot_satisfy_saved_invoice_provenance', ! WCJ_AI_Connection::eligible_context( $invoice_context ) );
foreach ( array(
	'owner'            => 4,
	'site'             => 2,
	'principal'        => 3,
	'app_uuid'         => '22222222-2222-4222-8222-222222222222',
	'grant_generation' => str_repeat( 'f', 32 ),
	'grant_digest'     => str_repeat( 'f', 64 ),
	'grant_expires'    => $grant['expires'] + 1,
	'kind'             => 'invoice',
	'scope'            => 'invoice:read',
	'grant_schema'     => 1,
) as $field => $changed ) {
	$wrong           = $context;
	$wrong[ $field ] = $changed;
	wcj_ai_check( 'wrong_report_context_' . $field, ! WCJ_AI_Connection::report_context_eligible( $wrong, $gate ) );
}
$wcj_ai_user = 1;
wcj_ai_check( 'native_owner_context_without_impersonation', WCJ_AI_Connection::owner_report_context( 2, $gate ) === $context && 1 === get_current_user_id() );
$wcj_ai_user = 4;
profile_denied( 'other_owner_context_denied', fn() => WCJ_AI_Connection::owner_report_context( 2, $gate ) );
$wcj_ai_user = 2;
profile_denied( 'remote_principal_cannot_owner_context', fn() => WCJ_AI_Connection::owner_report_context( 2, $gate ) );
WCJ_AI_Mutation_Gate::release( $gate );
profile_denied( 'context_requires_live_gate', fn() => WCJ_AI_Connection::report_read_context( $gate ) );

foreach ( array( 'native_ap_removed', 'owner_capability_lost', 'principal_extra_cap', 'principal_role_drift', 'inactive', 'expired', 'future_created', 'wrong_native_uuid', 'wrong_current_user', 'ext_cache', 'multisite', 'not_admitted', 'rejected' ) as $mode ) {
	$grant = profile_fixture();
	switch ( $mode ) {
		case 'native_ap_removed':
			$wcj_ai_apps[2] = array();
			break;
		case 'owner_capability_lost':
			$wcj_ai_denied_owners = array( 1 );
			break;
		case 'principal_extra_cap':
			$wcj_ai_user_override[2]                             = get_userdata( 2 );
			$wcj_ai_user_override[2]->caps['manage_woocommerce'] = true;
			break;
		case 'principal_role_drift':
			$wcj_ai_user_override[2]        = get_userdata( 2 );
			$wcj_ai_user_override[2]->roles = array( 'administrator' );
			break;
		case 'inactive':
			$grant['active'] = false;
			profile_set( $grant );
			break;
		case 'expired':
			$grant['created'] = time() - 3600;
			$grant['expires'] = time();
			profile_set( $grant );
			break;
		case 'future_created':
			$grant['created'] = time() + 100;
			$grant['expires'] = time() + 200;
			profile_set( $grant );
			break;
		case 'wrong_native_uuid':
			$property = new ReflectionProperty( WCJ_AI_Connection::class, 'authenticated' );
			$property->setValue(
				null,
				array(
					'id'   => 2,
					'uuid' => '22222222-2222-4222-8222-222222222222',
				)
			);
			break;
		case 'wrong_current_user':
			$wcj_ai_user = 3;
			break;
		case 'ext_cache':
			$wcj_ai_ext_cache = true;
			break;
		case 'multisite':
			$wcj_ai_multisite = true;
			break;
		case 'not_admitted':
			$property = new ReflectionProperty( WCJ_AI_Connection::class, 'admitted' );
			$property->setValue( null, false );
			break;
		case 'rejected':
			$property = new ReflectionProperty( WCJ_AI_Connection::class, 'rejected' );
			$property->setValue( null, true );
			break;
	}
	$gate = WCJ_AI_Mutation_Gate::acquire();
	wcj_ai_check( 'fresh_request_deny_' . $mode, ! WCJ_AI_Connection::report_allowed() && null === WCJ_AI_Connection::report_read_context( $gate ) );
	WCJ_AI_Mutation_Gate::release( $gate );
}

// Native owner report policy remains distinct from the stricter invoice policy.
wcj_ai_reset();
$wcj_ai_invoice_denied        = true;
$wcj_ai_filters['pre_option'] = 10;
wcj_ai_check( 'report_issue_not_invoice_authorize_or_policy', WCJ_AI_Connection::issue_report( 2, $wcj_ai_uuid ) );
$view = WCJ_AI_Connection::owner_view( 2 );
profile_denied( 'report_does_not_restore_denied_invoice_issue', fn() => WCJ_AI_Connection::owner_rotate( 2, $view['digest'], true ) );
wcj_ai_auth();
wcj_ai_check( 'report_policy_separate_without_invoice_leak', WCJ_AI_Connection::report_allowed() && ! WCJ_AI_Connection::allowed() );
$wcj_ai_user      = 1;
$grant            = json_decode( profile_raw(), true );
$grant['created'] = 1;
$grant['expires'] = 2;
profile_set( $grant );
$wcj_ai_apps[2]                 = array();
$wcj_ai_user_override[2]        = get_userdata( 2 );
$wcj_ai_user_override[2]->roles = array( 'shop_manager' );
$view                           = WCJ_AI_Connection::owner_view( 2 );
wcj_ai_check( 'expired_ap_missing_cap_drift_owner_management', true === $view['expired'] && false === $view['native_password_present'] && WCJ_AI_Connection::revoke( 2, $view['digest'] ) );
$view          = WCJ_AI_Connection::owner_view( 2 );
$before        = profile_raw();
$wcj_ai_writes = array();
wcj_ai_check( 'already_inactive_bounded_replay', WCJ_AI_Connection::revoke( 2, $view['digest'] ) && profile_raw() === $before && array() === wcj_ai_writes_without_gate() );
wcj_ai_auth();
$wcj_ai_apps[2] = array( array( 'uuid' => $wcj_ai_uuid ) );
unset( $wcj_ai_user_override[2] );
wcj_ai_check( 'ap_recreation_cannot_revive_inactive_grant', ! WCJ_AI_Connection::report_allowed() );

$legacy         = profile_fixture( 'prepare' );
$legacy_context = WCJ_AI_Connection::prepare_context();
$wcj_ai_user    = 1;
$old_view       = WCJ_AI_Connection::owner_view( 2 );
wcj_ai_check( 'explicit_cross_profile_replacement', WCJ_AI_Connection::owner_rotate_report( 2, $old_view['digest'] ) && WCJ_AI_Grant_Profile::REPORT_READ === WCJ_AI_Connection::owner_view( 2 )['profile'] );
$report = json_decode( profile_raw(), true );
wcj_ai_check( 'replacement_new_generation_not_union', $report['id'] !== $legacy['id'] && array( 'report:read_shared' ) === $report['scopes'] && ! WCJ_AI_Connection::eligible_context( $legacy_context ) );
profile_denied( 'stale_view_cannot_revoke_replacement', fn() => WCJ_AI_Connection::revoke( 2, $old_view['digest'] ) );
$view = WCJ_AI_Connection::owner_view( 2 );
wcj_ai_check( 'explicit_return_to_legacy_invoice_profile', WCJ_AI_Connection::owner_rotate( 2, $view['digest'], false ) && WCJ_AI_Grant_Profile::INVOICE_READ === WCJ_AI_Connection::owner_view( 2 )['profile'] );

foreach ( array( 'issue', 'rotate', 'revoke' ) as $operation ) {
	foreach ( array( 'reject', 'lost_ack' ) as $fault ) {
		profile_fixture();
		$wcj_ai_user  = 1;
		$view         = WCJ_AI_Connection::owner_view( 2 );
		$wcj_ai_fault = static fn( $key ) => 'wcj_ai_client_grant_2' === $key ? $fault : '';
		$call         = match ( $operation ) {
			'issue' => fn() => WCJ_AI_Connection::issue_report( 2, $wcj_ai_uuid ),
			'rotate' => fn() => WCJ_AI_Connection::owner_rotate_report( 2, $view['digest'] ),
			'revoke' => fn() => WCJ_AI_Connection::revoke( 2, $view['digest'] ),
		};
		profile_denied( 'unknown_' . $operation . '_' . $fault, $call );
	}
}

foreach ( array( 'oversize', 'sql_error', 'duplicate_rows', 'wrong_type' ) as $mode ) {
	profile_fixture();
	if ( 'oversize' === $mode ) {
		$wcj_ai_db['wcj_ai_client_grant_2'] = str_repeat( 'x', 4097 );
	} else {
		$wcj_ai_read_fault = static function ( $key, $db ) use ( $mode ) {
			if ( 'sql_error' === $mode ) {
				$db->last_error = 'synthetic_private_sql_error';
				return array(); }
			$row = array( 'option_value' => 'wrong_type' === $mode ? array() : profile_raw() );
			return 'duplicate_rows' === $mode ? array( $row, $row ) : array( $row );
		};
	}
	$before = $wcj_ai_db;
	wcj_ai_check( 'bounded_grant_read_denied_' . $mode, ! WCJ_AI_Connection::report_allowed() );
	$wcj_ai_user = 1;
	profile_denied( 'bounded_management_denied_' . $mode, fn() => WCJ_AI_Connection::owner_view( 2 ) );
	wcj_ai_check( 'no_corrupt_grant_repair_' . $mode, $before === $wcj_ai_db );
}

profile_fixture();
$wcj_ai_user = 1;
profile_denied( 'invoice_rotation_requires_view_digest', fn() => WCJ_AI_Connection::owner_rotate( 2, null, false ) );
profile_denied( 'report_rotation_requires_view_digest', fn() => WCJ_AI_Connection::owner_rotate_report( 2, null ) );

profile_fixture();
$adapter = new class() {
	/** Captured server registration.
	 *
	 * @var array Test-only arguments.
	 */
	public $args;
	public function create_server( ...$args ) {
		$this->args = $args;
	}
};
WCJ_AI_Abilities::server( $adapter );
wcj_ai_check( 'server_uses_transport_not_task_predicate', array( 'WCJ_AI_Connection', 'transport_authenticated' ) === $adapter->args[12] );
WCJ_AI_Abilities::register();
wcj_ai_check( 'invoice_callbacks_stay_invoice_scoped', array( 'WCJ_AI_Connection', 'allowed' ) === $wcj_ai_registered['booster-ai/schema']['permission_callback'] && array( 'WCJ_AI_Connection', 'allowed' ) === $wcj_ai_registered['booster-ai/context']['permission_callback'] );
wcj_ai_check( 'report_reader_separately_registered', array_keys( $wcj_ai_registered ) === array( 'booster-ai/schema', 'booster-ai/context', 'booster-ai/prepare', 'booster-ai/read-shared-report' ) && array( 'WCJ_AI_Connection', 'report_allowed' ) === $wcj_ai_registered['booster-ai/read-shared-report']['permission_callback'] );
wcj_ai_check( 'rest_auth_transport_accepts_report_without_invoice', null === WCJ_AI_Connection::rest_errors( null ) && ! WCJ_AI_Connection::allowed() );
$request = new class() {
	public function get_route() {
		return WCJ_AI_Connection::ROUTE;
	}
};
wcj_ai_check( 'rest_dispatch_transport_accepts_report', 'sentinel' === WCJ_AI_Connection::rest_dispatch( 'sentinel', null, $request ) );
$wrong_request = new class() {
	public function get_route() {
		return '/wp/v2/users';
	}
};
wcj_ai_check( 'rest_dispatch_wrong_route_denied', is_wp_error( WCJ_AI_Connection::rest_dispatch( null, null, $wrong_request ) ) );


// Legacy schema 1 preserves native positive integer IDs, including 64-bit IDs.
// Report schema 2 remains inside the receipt journal's explicit 31-bit bound.
if ( PHP_INT_SIZE >= 8 ) {
	foreach ( array( 2147483648, PHP_INT_MAX ) as $large ) {
		foreach ( array( 'owner', 'site', 'principal' ) as $field ) {
			$report           = profile_fixture();
			$report[ $field ] = $large;
			$decoded          = WCJ_AI_Grant_Profile::decode( json_encode( $report ) );
			wcj_ai_check( 'report_reject_high_' . $field . '_' . $large, null === $decoded );
			foreach ( array( false, true ) as $prepare ) {
				wcj_ai_reset();
				$principal            = 'principal' === $field ? $large : 2;
				$owner                = 'owner' === $field ? $large : 1;
				$wcj_ai_owner_ids     = array( $owner );
				$wcj_ai_principal_ids = array( $principal );
				$wcj_ai_site_id       = 'site' === $field ? $large : 1;
				$wcj_ai_user          = $owner;
				$wcj_ai_apps          = array( $principal => array( array( 'uuid' => $wcj_ai_uuid ) ) );
				$key                  = 'wcj_ai_client_grant_' . $principal;
				$name                 = 'legacy_high_' . $field . '_' . $large . ( $prepare ? '_prepare' : '_read' );
				WCJ_AI_Connection::issue( $principal, $wcj_ai_uuid, $prepare );
				$raw   = $wcj_ai_db[ $key ];
				$grant = json_decode( $raw, true );
				wcj_ai_check( $name . '_canonical', WCJ_AI_Grant_Profile::decode( $raw ) === $grant && $grant[ $field ] === $large );
				wcj_ai_auth( $principal );
				$context = WCJ_AI_Connection::prepare_context();
				wcj_ai_check( $name . '_authority', WCJ_AI_Connection::allowed() && ( ( null !== $context ) === $prepare ) && ! WCJ_AI_Connection::report_allowed() );
				$wcj_ai_user = $owner;
				$view        = WCJ_AI_Connection::owner_view( $principal );
				wcj_ai_check( $name . '_owner_view', $view['principal'] === $principal && hash( 'sha256', $raw ) === $view['digest'] && $view['active'] );
				WCJ_AI_Connection::owner_rotate( $principal, $view['digest'], $prepare );
				$rotated = WCJ_AI_Connection::owner_view( $principal );
				wcj_ai_check( $name . '_rotation', $rotated['generation'] !== $view['generation'] && $rotated['profile'] === $view['profile'] );
				if ( $context ) {
					$gate = WCJ_AI_Mutation_Gate::acquire();
					wcj_ai_check( $name . '_old_provenance_denied', ! WCJ_AI_Connection::eligible_context( $context ) );
					WCJ_AI_Mutation_Gate::release( $gate );
				}
				WCJ_AI_Connection::revoke( $principal, $rotated['digest'] );
				$revoked = WCJ_AI_Connection::owner_view( $principal );
				wcj_ai_check( $name . '_revoked_manageable', ! $revoked['active'] && $rotated['generation'] === $revoked['generation'] );
				$before = $wcj_ai_db;
				profile_denied( $name . '_report_replacement_denied', fn() => WCJ_AI_Connection::owner_rotate_report( $principal, $revoked['digest'] ) );
				wcj_ai_check( $name . '_denial_preserves_legacy', $before === $wcj_ai_db );
				wcj_ai_auth( $principal );
				wcj_ai_check( $name . '_revoked_request_denied', ! WCJ_AI_Connection::allowed() && ! WCJ_AI_Connection::transport_authenticated() );
			}
		}
	}
}

echo json_encode(
	array(
		'scope'  => 'Pure closed grant parser + real source with fake DB/CAS/native authority; not real AP/HTTP/adapter/cookie/consent/client proof.',
		'cases'  => $wcj_ai_cases,
		'passed' => count( array_filter( $wcj_ai_cases ) ),
		'total'  => count( $wcj_ai_cases ),
	),
	JSON_PRETTY_PRINT
), "\n";
exit( in_array( false, $wcj_ai_cases, true ) ? 1 : 0 );
