<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Standalone fake native-class harness, never runtime source.
/**
 * Service integration model: real service/journal/connection/observer, fake native
 * auth, SQL, CAS and empty order store. NOT WordPress or actual client/UI proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Universal.Namespaces, WordPress.WP.GlobalVariablesOverride -- CLI-only native class doubles and isolated test process, not a plugin namespace or real WordPress globals.
namespace Automattic\WooCommerce\Utilities {
	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Squiz.Commenting.FunctionComment, Squiz.Commenting.VariableComment, Generic.Files.OneObjectStructurePerFile, Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.WP.AlternativeFunctions, Universal.Files.SeparateFunctionsFromOO.Mixed -- CLI-only fake native seam.
	/** Fake order storage eligibility only. */
	final class OrderUtil {
		public static function get_order_type( $id ) {
			return 'shop_order'; }
		public static function custom_orders_table_datastore_cache_enabled() {
			return false; }
		public static function custom_orders_table_usage_is_enabled() {
			return false; }
	}
}

namespace {
	if ( 'cli' !== PHP_SAPI ) {
		exit( 1 ); }
	define( 'WCJ_AI_TEST_BOOTSTRAP_ONLY', true );
	require __DIR__ . '/remote-prepare-unit.php';
	require_once __DIR__ . '/../../includes/admin/class-wcj-ai-report-service.php';
	require_once __DIR__ . '/../../includes/class-wcj-ai-report-ability.php';

	/** Fresh-capability test double, not a native user. */
	final class WCJ_AI_Service_Owner {
		public $ID;
		public function __construct( $id ) {
			$this->ID = $id; }
		public function has_cap( $cap ) {
			return user_can( $this->ID, $cap ); }
	}
	/** Fixed empty legacy store identity, not WooCommerce storage. */
	final class WC_Data_Store {
		private $kind;
		public function __construct( $kind ) {
			$this->kind = $kind; }
		public static function load( $kind ) {
			return new self( $kind ); }
		public function get_current_class_name() {
			return 'order' === $this->kind ? 'WC_Order_Data_Store_CPT' : 'WC_Order_Refund_Data_Store_CPT'; }
	}
	function clean_user_cache( $id ) {
		$GLOBALS['cache_reads'][] = $id;
		if ( isset( $GLOBALS['cache_fault'] ) ) {
			( $GLOBALS['cache_fault'] )( $id ); }
	}
	function wp_get_session_token() {
		return $GLOBALS['session']; }
	function wp_validate_auth_cookie( $cookie, $scheme ) {
		return $GLOBALS['cookie']; }
	function wp_salt( $scheme ) {
		return 'synthetic-unit-test-only-not-a-secret'; }
	function wp_create_nonce( $action ) {
		return substr( hash( 'sha256', $action . '|' . $GLOBALS['session'] ), 0, 10 ); }
	function wp_verify_nonce( $nonce, $action ) {
		return wp_create_nonce( $action ) === $nonce; }
	function wp_timezone_string() {
		return 'America/New_York'; }
	function wc_get_orders( $args ) {
		if ( isset( $GLOBALS['wcj_ai_db'][ WCJ_AI_Mutation_Gate::KEY ] ) ) {
			throw new RuntimeException( 'observer_gate_was_held' ); }
		++$GLOBALS['order_queries'];
		if ( isset( $GLOBALS['observe_fault'] ) ) {
			( $GLOBALS['observe_fault'] )(); }
		return array(); // Explicit fake empty observation, not real store evidence.
	}
	function service_owner( $id = 1 ) {
		$GLOBALS['wcj_ai_user']        = $id;
		$GLOBALS['cookie']             = $id;
		$GLOBALS['wcj_ai_current_app'] = null;
	}
	function service_remote( $id = 2 ) {
		wcj_ai_auth( $id );
		$GLOBALS['wcj_ai_current_app'] = $GLOBALS['wcj_ai_uuid'];
		$GLOBALS['cookie']             = 0;
	}
	function service_fixture( $initialize = true ) {
		wcj_ai_reset();
		foreach ( array( 'cache_fault', 'observe_fault' ) as $key ) {
			unset( $GLOBALS[ $key ] ); }
		$GLOBALS['session']              = 'synthetic-owner-session-one';
		$GLOBALS['cache_reads']          = array();
		$GLOBALS['order_queries']        = 0;
		$GLOBALS['wcj_ai_user_override'] = array(
			1 => new WCJ_AI_Service_Owner( 1 ),
			4 => new WCJ_AI_Service_Owner( 4 ),
		);
		service_owner();
		WCJ_AI_Connection::issue_report( 2, $GLOBALS['wcj_ai_uuid'] );
		if ( $initialize ) {
			$gate = WCJ_AI_Mutation_Gate::acquire();
			// Fake fresh storage only. This is NOT a production initialization path.
			WCJ_AI_Report_Journal::initialize( $gate, true );
			WCJ_AI_Mutation_Gate::release( $gate );
		}
	}
	function service_capture( $form = null ) {
		$form = $form ?? WCJ_AI_Report_Service::capture_form( 2 );
		return WCJ_AI_Report_Service::capture( $form['principal'], $form['digest'], $form['revision'], $form['nonce'], true );
	}
	function service_share( $id ) {
		$view = WCJ_AI_Report_Service::inspect( $id );
		WCJ_AI_Report_Service::share( $id, $view['revision'], $view['payload_sha256'], $view['share_nonce'], true );
		return $view;
	}
	function service_bad( $name, $call ) {
		wcj_ai_check( $name, wcj_ai_denied( $call ) ); }
	function service_journal() {
		return json_decode( $GLOBALS['wcj_ai_db'][ WCJ_AI_Report_Journal::KEY ], true ); }

	// Schema fixtures only, still no native validator or adapter claim.
	if ( in_array( '--schema', $argv, true ) ) {
		require_once __DIR__ . '/class-wcj-ai-revenue-fixture.php';
		$source   = WCJ_AI_Revenue_Fixture::source();
		$examples = array();
		foreach ( array( 'complete_shape_subset', 'root_unavailable', 'observed_empty', 'maximum_decimal' ) as $variant ) {
			$copy = $source;
			if ( 'root_unavailable' === $variant ) {
				$copy['periods'] = null;
				$copy['storage'] = null;
				$copy['streams'] = array();
				$copy['reason']  = 'freshness_unverified'; }
			if ( 'observed_empty' === $variant ) {
				$copy['streams']['paid_cohort_order_totals']['groups'] = array(); }
			if ( 'maximum_decimal' === $variant ) {
				$copy['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = '9223372036854.775807'; }
			$examples[ $variant ] = json_decode( WCJ_AI_Report_Receipt::build( $copy, 1794564000, 1794564900, 1794564007 ), true );
		}
		echo json_encode(
			array(
				'input_schema'  => WCJ_AI_Report_Ability::input_schema(),
				'output_schema' => WCJ_AI_Report_Ability::output_schema(),
				'examples'      => $examples,
			)
		), "\n";
		exit;
	}

	service_fixture( false );
	$before = $GLOBALS['wcj_ai_db'];
	service_bad( 'missing_history_catalog_fails', fn() => WCJ_AI_Report_Service::catalog() );
	service_bad( 'missing_history_form_fails', fn() => WCJ_AI_Report_Service::capture_form( 2 ) );
	wcj_ai_check( 'no_auto_initialize', $before === $GLOBALS['wcj_ai_db'] );
	service_fixture();
	$form = WCJ_AI_Report_Service::capture_form( 2 );
	wcj_ai_check( 'form_no_collection', 0 === $GLOBALS['order_queries'] && 0 === $form['revision'] );
	foreach ( array( 'confirm', 'nonce', 'revision', 'digest' ) as $bad ) {
		service_bad( 'capture_bad_' . $bad, fn() => WCJ_AI_Report_Service::capture( 2, 'digest' === $bad ? str_repeat( 'f', 64 ) : $form['digest'], 'revision' === $bad ? 99 : $form['revision'], 'nonce' === $bad ? 'wrong' : $form['nonce'], 'confirm' !== $bad ) );
	}
	wcj_ai_check( 'bad_capture_did_not_allocate_or_observe', array() === service_journal()['entries'] && 0 === $GLOBALS['order_queries'] );
	$id   = service_capture( $form );
	$view = WCJ_AI_Report_Service::inspect( $id );
	wcj_ai_check( 'one_native_observer_with_fake_empty_store', 4 === $GLOBALS['order_queries'] && 'pending_review' === $view['state'] && true === $view['can_share'] );
	wcj_ai_check( 'whole_payload_hash_and_length', strlen( $view['json'] ) === $view['payload_bytes'] && hash( 'sha256', $view['json'] ) === $view['payload_sha256'] );
	wcj_ai_check( 'read_only_review_not_recollected', 4 === $GLOBALS['order_queries'] && str_contains( $view['json'], 'America\\/New_York' ) );
	service_bad( 'capture_post_replay_stale_revision', fn() => service_capture( $form ) );
	service_remote();
	service_bad( 'pending_not_remote_readable', fn() => WCJ_AI_Report_Service::read_shared( array( 'receipt_id' => $id ) ) );
	service_bad( 'remote_cannot_capture_form', fn() => WCJ_AI_Report_Service::capture_form( 2 ) );
	service_bad( 'remote_cannot_catalog', fn() => WCJ_AI_Report_Service::catalog() );
	service_bad( 'remote_cannot_inspect', fn() => WCJ_AI_Report_Service::inspect( $id ) );
	service_owner();
	service_bad( 'share_requires_confirm', fn() => WCJ_AI_Report_Service::share( $id, $view['revision'], $view['payload_sha256'], $view['share_nonce'], false ) );
	service_bad( 'share_wrong_digest', fn() => WCJ_AI_Report_Service::share( $id, $view['revision'], str_repeat( 'f', 64 ), $view['share_nonce'], true ) );
	service_bad( 'capture_nonce_cannot_share', fn() => WCJ_AI_Report_Service::share( $id, $view['revision'], $view['payload_sha256'], $form['nonce'], true ) );
	service_share( $id );
	service_remote();
	$out = WCJ_AI_Report_Service::read_shared( array( 'receipt_id' => $id ) );
	wcj_ai_check( 'remote_default_codec_exact_review_bytes', json_encode( $out ) === $view['json'] );
	wcj_ai_check( 'remote_never_observes_or_impersonates', 4 === $GLOBALS['order_queries'] && 2 === get_current_user_id() );
	wcj_ai_check( 'released_before_output', ! isset( $GLOBALS['wcj_ai_db'][ WCJ_AI_Mutation_Gate::KEY ] ) );
	foreach ( array(
		array(),
		array(
			'receipt_id' => $id,
			'owner'      => 1,
		),
		array( 'receipt_id' => strtoupper( $id ) ),
		array( 'receipt_id' => str_repeat( 'f', 64 ) ),
		array( 'receipt_id' => array( $id ) ),
	) as $n => $input ) {
		service_bad( 'remote_closed_input_' . $n, fn() => WCJ_AI_Report_Service::read_shared( $input ) );
	}
	service_owner( 4 );
	wcj_ai_check( 'other_owner_index_empty', array() === WCJ_AI_Report_Service::catalog()['entries'] );
	service_bad( 'other_owner_no_inspection', fn() => WCJ_AI_Report_Service::inspect( $id ) );
	service_owner();
	$GLOBALS['session'] = 'synthetic-owner-session-two';
	$relogin            = WCJ_AI_Report_Service::inspect( $id );
	wcj_ai_check( 'relogin_recovers_bytes_not_share_authority', $relogin['json'] === $view['json'] && false === $relogin['can_share'] );
	service_bad( 'old_session_share_nonce_denied', fn() => WCJ_AI_Report_Service::share( $id, $view['revision'], $view['payload_sha256'], $view['share_nonce'], true ) );
	WCJ_AI_Connection::revoke( 2, WCJ_AI_Connection::owner_view( 2 )['digest'] );
	service_remote();
	service_bad( 'revoke_denies_read', fn() => WCJ_AI_Report_Service::read_shared( array( 'receipt_id' => $id ) ) );
	service_owner();
	$cleanup = WCJ_AI_Report_Service::inspect( $id );
	wcj_ai_check( 'revoked_connection_owner_cleanup_available', false === $cleanup['can_share'] && $cleanup['json'] === $view['json'] );
	WCJ_AI_Report_Service::purge( $id, $cleanup['revision'], $cleanup['purge_nonce'], true );
	wcj_ai_check( 'purge_retains_monotonic_revision_and_marker', array() === service_journal()['entries'] && $form['revision'] < service_journal()['revision'] && isset( $GLOBALS['wcj_ai_db'][ WCJ_AI_Report_Journal::MARKER ] ) );

	foreach ( array( 'cookie', 'session', 'capability', 'app', 'site', 'cache' ) as $fault ) {
		service_fixture();
		switch ( $fault ) {
			case 'cookie':
				$GLOBALS['cookie'] = 4;
				break;
			case 'session':
				$GLOBALS['session'] = '';
				break;
			case 'capability':
				$GLOBALS['wcj_ai_denied_owners'] = array( 1 );
				break;
			case 'app':
				$GLOBALS['wcj_ai_current_app'] = $GLOBALS['wcj_ai_uuid'];
				break;
			case 'site':
				$GLOBALS['wcj_ai_site_id'] = 2;
				break;
			case 'cache':
				$GLOBALS['wcj_ai_ext_cache'] = true;
				break;
		}
		service_bad( 'fresh_owner_denial_' . $fault, fn() => WCJ_AI_Report_Service::capture_form( 2 ) );
	}
	foreach ( array( 'revoke', 'session', 'capability', 'app_deleted' ) as $fault ) {
		service_fixture();
		$GLOBALS['observe_fault'] = static function () use ( $fault ) {
			switch ( $fault ) {
				case 'revoke':
					WCJ_AI_Connection::revoke( 2, WCJ_AI_Connection::owner_view( 2 )['digest'] );
					break;
				case 'session':
					$GLOBALS['session'] = 'changed-during-observation';
					break;
				case 'capability':
					$GLOBALS['wcj_ai_denied_owners'] = array( 1 );
					break;
				case 'app_deleted':
					$GLOBALS['wcj_ai_apps'][2] = array();
					break;
			}
			unset( $GLOBALS['observe_fault'] );
		};
		service_bad( 'post_observation_reauth_' . $fault, fn() => service_capture() );
		$retained = service_journal()['entries'][0];
		wcj_ai_check( 'no_payload_after_reauth_failure_' . $fault, 'collecting' === $retained['state'] && null === $retained['payload_b64'] );
	}
	service_fixture();
	$form                    = WCJ_AI_Report_Service::capture_form( 2 );
	$GLOBALS['wcj_ai_fault'] = static fn( $key ) => WCJ_AI_Report_Journal::KEY === $key ? 'lost_ack' : '';
	service_bad( 'lost_reservation_ack_no_success', fn() => service_capture( $form ) );
	$GLOBALS['wcj_ai_fault'] = null;
	$index                   = WCJ_AI_Report_Service::catalog();
	wcj_ai_check( 'lost_id_recovered_without_observation', 0 === $GLOBALS['order_queries'] && 1 === count( $index['entries'] ) && 'collecting' === $index['entries'][0]['state'] );
	$GLOBALS['session'] = 'fresh-cleanup-session';
	$v                  = WCJ_AI_Report_Service::inspect( $index['entries'][0]['id'] );
	WCJ_AI_Report_Service::purge( $v['id'], $v['revision'], $v['purge_nonce'], true );
	$GLOBALS['session'] = 'synthetic-owner-session-one';
	service_bad( 'old_capture_form_cannot_reallocate_after_purge', fn() => service_capture( $form ) );
	wcj_ai_check( 'no_reallocation_after_purge', array() === service_journal()['entries'] && 0 === $GLOBALS['order_queries'] );

	foreach ( array( 'cookie', 'session' ) as $fault ) {
		service_fixture();
		$form                    = WCJ_AI_Report_Service::capture_form( 2 );
		$GLOBALS['wcj_ai_fault'] = static function ( $key, $before, $after ) use ( $fault ) {
			if ( WCJ_AI_Mutation_Gate::KEY === $key && ! $after['exists'] && isset( $GLOBALS['wcj_ai_db'][ WCJ_AI_Report_Journal::KEY ] ) && service_journal()['entries'] ) {
				if ( 'cookie' === $fault ) {
					$GLOBALS['cookie'] = 0;
				} else {
					$GLOBALS['session'] = 'session-replaced-during-successful-release'; }
			}
			return ''; // Release CAS succeeds; this is NOT a lost acknowledgement.
		};
		service_bad( 'successful_release_reauth_before_observe_' . $fault, fn() => service_capture( $form ) );
		$retained = service_journal()['entries'][0];
		wcj_ai_check( 'successful_release_revocation_zero_queries_' . $fault, 0 === $GLOBALS['order_queries'] && 'collecting' === $retained['state'] && null === $retained['payload_b64'] && ! isset( $GLOBALS['wcj_ai_db'][ WCJ_AI_Mutation_Gate::KEY ] ) );
	}

	foreach ( array( 'reservation_release', 'remote_release' ) as $fault ) {
		service_fixture();
		if ( 'remote_release' === $fault ) {
			$id = service_capture();
			service_share( $id );
			service_remote(); }
		$form                    = 'reservation_release' === $fault ? WCJ_AI_Report_Service::capture_form( 2 ) : null;
		$observations            = $GLOBALS['order_queries'];
		$GLOBALS['wcj_ai_fault'] = static fn( $key, $before, $after ) => WCJ_AI_Mutation_Gate::KEY === $key && ! $after['exists'] ? 'lost_ack' : '';
		service_bad( 'release_failure_no_output_' . $fault, fn() => 'reservation_release' === $fault ? service_capture( $form ) : WCJ_AI_Report_Service::read_shared( array( 'receipt_id' => $id ) ) );
		wcj_ai_check( 'no_extra_observation_on_release_failure_' . $fault, $GLOBALS['order_queries'] === $observations );
	}

	service_fixture();
	WCJ_AI_Abilities::register();
	$tool = $GLOBALS['wcj_ai_registered']['booster-ai/read-shared-report'];
	wcj_ai_check( 'separate_report_permission_and_closed_input', array( 'WCJ_AI_Connection', 'report_allowed' ) === $tool['permission_callback'] && false === $tool['input_schema']['additionalProperties'] && array( 'receipt_id' ) === $tool['input_schema']['required'] );
	$boundary = new WCJ_AI_Report_Ability();
	foreach ( array( new WP_Error( 'private', 'PRIVATE_MARKER' ), new RuntimeException( 'PRIVATE_MARKER' ) ) as $n => $bad ) {
		$error = $boundary->execute( $bad );
		wcj_ai_check( 'finite_boundary_' . $n, 'wcj_ai_report_unavailable' === $error->code && ! str_contains( $error->message, 'PRIVATE_MARKER' ) && false === $boundary->check_permissions( $bad ) );
	}
	echo json_encode(
		array(
			'scope'      => 'Real service orchestration with FAKE native auth/SQL/CAS/order store. No native WordPress, consent, adapter, browser or client proof.',
			'passed'     => ! in_array( false, $GLOBALS['wcj_ai_cases'], true ),
			'assertions' => count( $GLOBALS['wcj_ai_cases'] ),
			'cases'      => $GLOBALS['wcj_ai_cases'],
		),
		JSON_PRETTY_PRINT
	), "\n";
	exit( in_array( false, $GLOBALS['wcj_ai_cases'], true ) ? 1 : 0 );
}
