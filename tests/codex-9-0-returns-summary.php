<?php
/**
 * Actual Returns summary/queue source checks, with explicit offline primitives.
 * No WordPress bootstrap, native SQL, HTTP, customer state or persistence.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
$args = getopt( '', array( 'root:', 'mode:', 'load:', 'api:', 'wp:', 'admin:' ) );
if ( empty( $args['root'] ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
define( 'ABSPATH', __DIR__ . '/' );
define( 'WCJ_FREE_PLUGIN_PATH', $args['root'] );
define( 'WCJ_PLUGIN_PATH', $args['root'] );
$tier = basename( $args['root'] );
define( 'WCJ_PLUGIN_FILE', 'elite' === $tier ? 'booster-elite-for-woocommerce.php' : ( 'plus' === $tier ? 'booster-plus-for-woocommerce.php' : 'woocommerce-jetpack.php' ) );
define( 'WCJ_FREE_PLUGIN_FILE', WCJ_PLUGIN_FILE );
$mode = $args['mode'] ?? 'main';
$GLOBALS['summary_test'] = array( 'checks' => array(), 'calls' => array(), 'context' => array(), 'actions' => array(), 'abilities' => array(), 'menus' => array() );
function check( $name, $pass ) { $GLOBALS['summary_test']['checks'][] = array( 'name' => $name, 'pass' => (bool) $pass ); }
function called( $name ) { $GLOBALS['summary_test']['calls'][ $name ] = ( $GLOBALS['summary_test']['calls'][ $name ] ?? 0 ) + 1; }
function calls( $name ) { return $GLOBALS['summary_test']['calls'][ $name ] ?? 0; }
function __( $text, $domain = null ) { return $text; }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function get_current_user_id() { called( 'user' ); return $GLOBALS['summary_test']['context']['user'] ?? 1; }
function current_user_can( $cap ) { called( 'capability' ); return 'manage_woocommerce' === $cap && ( $GLOBALS['summary_test']['context']['cap'] ?? true ); }
function wp_get_current_user() { called( 'role' ); return (object) array( 'roles' => $GLOBALS['summary_test']['context']['roles'] ?? array( 'shop_manager' ) ); }
function wcj_is_rest() { return $GLOBALS['summary_test']['context']['rest'] ?? false; }
function is_admin() { return (bool) ( $GLOBALS['summary_test']['context']['admin'] ?? true ); }
function get_option( $key, $default = false ) { called( 'option' ); return $GLOBALS['summary_test']['context']['options'][ $key ] ?? $default; }
function w_c_j() { called( 'plugin' ); return $GLOBALS['summary_test']['plugin']; }
function WCJ() { return w_c_j(); }
function apply_filters( $name, $value ) {
	return 'wcj_enabled_module_allowlist' === $name ? ( $GLOBALS['summary_test']['context']['allowlist'] ?? $value ) : $value;
}
// Controlled WordPress primitives, not a native filter/action registry.
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
function did_action( $name ) { return 'plugins_loaded' === $name ? 1 : 0; }
function add_action( $name, $callback, $priority = 10 ) { $GLOBALS['summary_test']['actions'][ $name ][ $priority ][] = $callback; }
function do_action( $name ) {
	$actions = $GLOBALS['summary_test']['actions'][ $name ] ?? array();
	ksort( $actions );
	foreach ( $actions as $callbacks ) {
		foreach ( $callbacks as $callback ) { call_user_func( $callback ); }
	}
}
function add_submenu_page( ...$arguments ) { $GLOBALS['summary_test']['menus'][] = $arguments; }
function wp_die( $message ) { throw new RuntimeException( 'synthetic_safe_wp_die' ); }
class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
if ( 'loader' !== $mode || '1' === ( $args['api'] ?? '1' ) ) {
	function wp_register_ability( $name, $definition ) { $GLOBALS['summary_test']['abilities'][ $name ] = $definition; }
}

/** Import complete actual named functions, never copied/reimplemented policy. */
function source_function( $file, $name ) {
	$tokens = token_get_all( file_get_contents( $file ) );
	for ( $i = 0; $i < count( $tokens ); ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) { continue; }
		$j = $i + 1;
		while ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { ++$j; }
		if ( ! is_array( $tokens[ $j ] ) || $tokens[ $j ][1] !== $name ) { continue; }
		$body = ''; $depth = 0; $started = false;
		for ( ; $i < count( $tokens ); ++$i ) {
			$token = $tokens[ $i ]; $body .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) { ++$depth; $started = true; }
			if ( '}' === $token && 0 === --$depth && $started ) { return $body; }
		}
	}
	throw new RuntimeException( 'Missing actual source function' );
}
$imports = array();
$helpers = $args['root'] . '/includes/functions/';
foreach ( array( 'wcj_get_option' => 'wcj-functions-core.php', 'wcj_is_module_enabled' => 'wcj-functions-booster-core.php', 'wcj_check_modules_by_user_roles' => 'wcj-functions-booster-core.php' ) as $name => $file ) {
	if ( 'missing-policy' === $mode && 'wcj_check_modules_by_user_roles' === $name ) { continue; }
	$body = source_function( $helpers . $file, $name );
	$imports[ $name ] = hash( 'sha256', $body );
	eval( $body );
}
// Elite's actual enabled helper also preserves its supported module allowlist.
$booster_core = file_get_contents( $helpers . 'wcj-functions-booster-core.php' );
foreach ( array( 'wcj_get_enabled_module_allowlist', 'wcj_is_module_allowed_by_allowlist' ) as $name ) {
	if ( false !== strpos( $booster_core, 'function ' . $name . '(' ) ) {
		$body = source_function( $helpers . 'wcj-functions-booster-core.php', $name );
		$imports[ $name ] = hash( 'sha256', $body );
		eval( $body );
	}
}
$enabled_body = source_function( $args['root'] . '/includes/classes/class-wcj-module.php', 'is_enabled' );
eval( 'class WCJ_Module { public $id; public $type="module"; public $parent_id; public function __construct() {} public ' . $enabled_body . ' }' );
$module_text = file_get_contents( $args['root'] . '/includes/class-wcj-returns.php' );
$module_body = substr( $module_text, strpos( $module_text, 'class WCJ_Returns extends WCJ_Module {' ) );
$module_body = substr( $module_body, 0, strrpos( $module_body, 'return new WCJ_Returns();' ) );
eval( $module_body );
// Import the complete current quantity class and exact pure payload validator.
// No Store/Connection constructor, private SQL, Woo bootstrap or auth job executes.
$quantity_file = file_get_contents( $args['root'] . '/includes/returns/class-wcj-returns-facts.php' );
$quantity_body = substr( $quantity_file, 5, strpos( $quantity_file, '/** Woo order data is read only.' ) - 5 );
$imports['actual_quantity_class_source'] = hash( 'sha256', $quantity_body );
eval( $quantity_body );
$payload_body = source_function( $args['root'] . '/includes/returns/class-wcj-returns-store.php', 'payload' );
$imports['actual_store_payload'] = hash( 'sha256', $payload_body );
eval( 'class WCJ_Summary_Actual_Payload { public ' . $payload_body . ' }' );

class WCJ_Returns_Store {
	public function __construct() { called( 'store_constructor' ); }
	public function listing( $actor, $order, $before ) {
		called( 'listing' );
		check( 'queue uses default fixed latest page', 0 === $actor && 0 === $order && PHP_INT_MAX === $before );
		if ( ! empty( $GLOBALS['summary_test']['context']['storage_error'] ) ) { throw new RuntimeException( 'SYNTHETIC_PRIVATE_MARKER_SQL' ); }
		$rows = $GLOBALS['summary_test']['context']['rows'] ?? array();
		foreach ( $rows as &$row ) {
			// Original account doubles now supply the actual persisted actor field.
			$row += array( 'actor_id' => 17 );
		}
		unset( $row );
		return $rows;
	}
	public function payload( $row ) {
		called( 'payload' );
		if ( ! empty( $GLOBALS['summary_test']['context']['payload_error'] ) ) { throw new RuntimeException( 'SYNTHETIC_PRIVATE_MARKER_PAYLOAD' ); }
		if ( ! empty( $GLOBALS['summary_test']['context']['validate_payload'] ) ) {
			called( 'actual_payload' );
			return ( new WCJ_Summary_Actual_Payload() )->payload( $row );
		}
		return array( 'reason' => 'SYNTHETIC_PRIVATE_MARKER_REASON', 'outbox' => array(), 'items' => array(), 'history' => array() );
	}
	public function read_claim( $order, $item ) { called( 'read_claim' ); return $GLOBALS['summary_test']['context']['anchors'][ $order ] ?? null; }
}
class WCJ_Returns_Facts {
	public function __construct() { called( 'facts_constructor' ); }
	public function load( $order ) {
		called( 'facts' );
		if ( in_array( $order, $GLOBALS['summary_test']['context']['unavailable_orders'] ?? array(), true ) ) { throw new RuntimeException( 'SYNTHETIC_PRIVATE_MARKER_FACTS' ); }
		if ( isset( $GLOBALS['summary_test']['context']['facts_by_order'][ $order ] ) ) {
			return $GLOBALS['summary_test']['context']['facts_by_order'][ $order ];
		}
		return array( 'hash' => 'synthetic-current', 'amount_only' => in_array( $order, $GLOBALS['summary_test']['context']['amount_only_orders'] ?? array(), true ) );
	}
}
if ( 'contract' === $mode ) {
	class WCJ_Returns_Service {
		public function queue() {
			called( 'contract_queue' );
			if ( ! empty( $GLOBALS['summary_test']['context']['throw'] ) ) { throw new RuntimeException( 'SYNTHETIC_PRIVATE_MARKER_EXCEPTION' ); }
			return $GLOBALS['summary_test']['context']['response'];
		}
	}
} elseif ( 'missing-service' !== $mode ) {
	require $args['root'] . '/includes/returns/class-wcj-returns-service.php';
}
function context( $override = array() ) {
	unset( $GLOBALS['wcj_modules_by_user_roles_data'] );
	$GLOBALS['summary_test']['context'] = array_replace_recursive( array( 'user' => 1, 'cap' => true, 'options' => array( 'wcj_returns_enabled' => 'yes', 'wcj_modules_by_user_roles_enabled' => 'no' ), 'rows' => array() ), $override );
	$module = ( new ReflectionClass( 'WCJ_Returns' ) )->newInstanceWithoutConstructor();
	$module->id = 'returns';
	$GLOBALS['summary_test']['plugin'] = (object) array( 'options' => array(), 'all_modules' => array( 'returns' => $module ) );
	$GLOBALS['summary_test']['calls'] = array();
}
function finished() {
	$checks = $GLOBALS['summary_test']['checks'];
	$failed = count( array_filter( $checks, static function ( $row ) { return ! $row['pass']; } ) );
	echo json_encode( array( 'mode' => $GLOBALS['mode'], 'kind' => 'actual_source_synthetic_not_native', 'checks' => count( $checks ), 'failed' => $failed, 'assertions' => $checks, 'imports' => $GLOBALS['imports'], 'actual_queue_loaded' => 'contract' !== $GLOBALS['mode'] && class_exists( 'WCJ_Returns_Service' ), 'boundary' => 'Actual queue class with controlled storage/facts primitives; contract mode replaces queue only to test malformed dependency outputs. Loader mode selectively no-ops unrelated require statements, not a native bootstrap.' ), JSON_PRETTY_PRINT ) . "\n";
	exit( $failed ? 1 : 0 );
}
context();
$service_file = $args['root'] . '/includes/class-wcj-returns-summary.php';
$admin_file = $args['root'] . '/includes/class-wcj-returns-summary-admin.php';
$loader_file = $args['root'] . '/includes/core/wcj-loader.php';
check( 'new summary source exists', is_file( $service_file ) );
check( 'new admin source exists', is_file( $admin_file ) );
check( 'loader wires summary definition', false !== strpos( file_get_contents( $loader_file ), '/includes/class-wcj-returns-summary.php' ) );
check( 'loader wires summary admin', false !== strpos( file_get_contents( $loader_file ), '/includes/class-wcj-returns-summary-admin.php' ) );
check( 'Ability has Returns summary registration', false !== strpos( file_get_contents( $args['root'] . '/includes/class-wcj-abilities.php' ), "'booster/returns-summary'" ) );
if ( ! is_file( $service_file ) || ! is_file( $admin_file ) ) {
	// Expected feature-absence red, not a fabricated execution of missing methods.
	finished();
}
// Existing metadata schemas refer to the actual Status Service constant.
require $args['root'] . '/includes/class-wcj-status-service.php';
if ( 'loader' === $mode ) {
	$GLOBALS['summary_test']['context']['admin'] = '1' === ( $args['admin'] ?? '1' );
	$GLOBALS['summary_test']['context']['options']['wcj_load_modules_on_init'] = $args['load'] ?? 'no';
	$GLOBALS['wp_version'] = $args['wp'] ?? '6.9';
	$loader = file_get_contents( $loader_file );
	$loader = preg_replace_callback( '/require_once\s+[^;]+;/', static function ( $m ) {
		return preg_match( '/class-wcj-(returns-summary(?:-admin)?|abilities)\.php/', $m[0] ) ? $m[0] : 'null;';
	}, $loader );
	$loader = preg_replace( '/new WCJ_Localization\([^;]+;/', 'null;', $loader );
	eval( '?>' . $loader );
	do_action( 'plugins_loaded' );
	do_action( 'wp_abilities_api_init' );
	do_action( 'admin_menu' );
	check( 'both loader timings define adapter', class_exists( 'WCJ_Returns_Summary' ) );
	check( 'admin source only under admin guard', class_exists( 'WCJ_Returns_Summary_Admin' ) === is_admin() );
	$expected_api = function_exists( 'wp_register_ability' ) && version_compare( $GLOBALS['wp_version'], '6.9', '>=' );
	check( 'API version/function guard', isset( $GLOBALS['summary_test']['abilities']['booster/returns-summary'] ) === $expected_api );
	check( 'no Returns reads at loader/schema declaration', 0 === calls( 'store_constructor' ) && 0 === calls( 'facts_constructor' ) && 0 === calls( 'listing' ) );
	finished();
}
require $service_file;
require $admin_file;
require $args['root'] . '/includes/class-wcj-abilities.php';
$admin = ( new ReflectionClass( 'WCJ_Returns_Summary_Admin' ) )->newInstanceWithoutConstructor();
$adapter = new WCJ_Returns_Summary();
$ability = new WCJ_Abilities();
context();
$ability->register_abilities();
check( 'metadata makes no options/dependency calls', array() === $GLOBALS['summary_test']['calls'] );
$definition = $GLOBALS['summary_test']['abilities']['booster/returns-summary'] ?? array();
check( 'read-only annotations', isset( $definition['meta']['annotations'] ) && array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) === $definition['meta']['annotations'] );
check( 'strict input schema', isset( $definition['input_schema'] ) && array( 'scope' ) === $definition['input_schema']['required'] && false === $definition['input_schema']['additionalProperties'] );
check( 'closed output schema', isset( $definition['output_schema'] ) && false === $definition['output_schema']['additionalProperties'] );
foreach ( array( array( 'user' => 0 ), array( 'cap' => false ) ) as $deny ) {
	context( $deny );
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'denied entry is fixed error', is_wp_error( $r ) && 'booster_forbidden' === $r->get_error_code() );
	check( 'denied before options/plugin/dependency reads', 0 === calls( 'option' ) && 0 === calls( 'plugin' ) && 0 === calls( 'facts_constructor' ) && 0 === calls( 'store_constructor' ) );
	context( $deny );
	$r = $ability->execute_returns_summary( array( 'scope' => 'summary' ) );
	check( 'denied Ability is fixed error', is_wp_error( $r ) && 'booster_forbidden' === $r->get_error_code() );
	check( 'denied Ability before options/plugin/dependency', 0 === calls( 'option' ) && 0 === calls( 'plugin' ) && 0 === calls( 'facts_constructor' ) && 0 === calls( 'store_constructor' ) );
}
foreach ( array( null, array(), array( 'scope' => 'other' ), array( 'scope' => 'summary', 'before' => 2 ), array( 'scope' => array( 'summary' ) ), 'summary' ) as $input ) {
	context(); $r = $adapter->get_summary( $input );
	check( 'strict direct invalid input', is_wp_error( $r ) && 'booster_invalid_returns_summary_input' === $r->get_error_code() );
	check( 'invalid before any options/dependency', 0 === calls( 'option' ) && 0 === calls( 'plugin' ) && 0 === calls( 'facts_constructor' ) );
	context(); $r = $ability->execute_returns_summary( $input );
	check( 'strict Ability invalid input', is_wp_error( $r ) && 'booster_invalid_returns_summary_input' === $r->get_error_code() );
	check( 'Ability invalid before options/dependency', 0 === calls( 'option' ) && 0 === calls( 'plugin' ) && 0 === calls( 'facts_constructor' ) );
}
context();
$r = $ability->execute_returns_summary();
check( 'missing Ability input never defaults to summary', is_wp_error( $r ) && 'booster_invalid_returns_summary_input' === $r->get_error_code() && 0 === calls( 'option' ) );
if ( 'missing-policy' === $mode || 'missing-service' === $mode ) {
	context(); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'missing dependency unavailable not empty', 'unavailable' === $r['state'] && null === $r['counts'] && null === $r['sample_size'] );
	check( 'missing dependency no queue/storage', 0 === calls( 'listing' ) && 0 === calls( 'facts_constructor' ) );
	finished();
}
if ( 'contract' === $mode ) {
	$good = array( 'status' => 'pending', 'needs_review' => false, 'facts_unavailable' => false );
	foreach ( array( null, false, 'SYNTHETIC_PRIVATE_MARKER', array_fill( 0, 51, $good ), array( 7 => $good ), array( array() ), array( array_replace( $good, array( 'status' => 'unknown' ) ) ), array( array_replace( $good, array( 'needs_review' => 0 ) ) ), array( array_replace( $good, array( 'facts_unavailable' => 'no' ) ) ), array( array_replace( $good, array( 'needs_review' => false, 'facts_unavailable' => true ) ) ) ) as $bad ) {
		context( array( 'response' => $bad ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		check( 'malformed queue unavailable with nulls', 'unavailable' === $r['state'] && 'invalid_queue' === $r['reason'] && null === $r['counts'] && null === $r['sample_size'] && null === $r['possibly_truncated'] );
		check( 'malformed queue called once and private marker absent', 1 === calls( 'contract_queue' ) && false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE_MARKER' ) );
	}
	context( array( 'response' => new WP_Error( 'private', 'SYNTHETIC_PRIVATE_MARKER' ) ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'service error safe unavailable', 'queue_unavailable' === $r['reason'] && null === $r['counts'] && false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE_MARKER' ) );
	context( array( 'throw' => true ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'service exception safe unavailable once', 'queue_unavailable' === $r['reason'] && 1 === calls( 'contract_queue' ) && null === $r['counts'] );
	finished();
}

// Accepted local identity guard: actual helper cache remains untouched.
foreach ( array( false, true ) as $identity_rest ) {
	$identity_options = array( 'wcj_returns_enabled' => 'yes', 'wcj_modules_by_user_roles_enabled' => 'yes', 'wcj_modules_by_user_roles_excl_administrator' => array(), 'wcj_modules_by_user_roles_excl_shop_manager' => array( 'returns' ) );
	foreach ( array( 1, 2 ) as $next_actor ) {
		context( array( 'rest' => $identity_rest, 'roles' => array( 'administrator' ), 'options' => $identity_options ) );
		check( 'prime actual allowed prior-role helper', true === wcj_check_modules_by_user_roles( 'returns' ) );
		$prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
		$GLOBALS['summary_test']['context']['user'] = $next_actor;
		$GLOBALS['summary_test']['context']['roles'] = array( 'shop_manager' );
		$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		check( 'same or different capable actor role switch fails closed', 'policy_unavailable' === $r['reason'] && 'unavailable' === $r['state'] && null === $r['counts'] && null === $r['sample_size'] && null === $r['possibly_truncated'] );
		check( 'mismatch before queue construction and no helper cache rewrite', 0 === calls( 'store_constructor' ) && 0 === calls( 'facts_constructor' ) && 0 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	}
	context( array( 'rest' => $identity_rest, 'roles' => array( 'administrator' ), 'options' => $identity_options ) );
	wcj_check_modules_by_user_roles( 'returns' ); $prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
	$GLOBALS['summary_test']['context']['user'] = 2;
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'same first role different capable actor preserves policy', 'available' === $r['state'] && 1 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	foreach ( array( array( 'administrator' ), array( 'shop_manager' ) ) as $fresh_roles ) {
		context( array( 'rest' => $identity_rest, 'roles' => $fresh_roles, 'options' => $identity_options ) );
		$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		$allowed = 'administrator' === $fresh_roles[0];
		check( 'unprimed actual helper uses current first role', ( $allowed ? 'summary_ready' : 'role_restricted' ) === $r['reason'] && ( $allowed ? 1 : 0 ) === calls( 'listing' ) );
	}
	foreach ( array( array(), array( '' ) ) as $guest_roles ) {
		context( array( 'rest' => $identity_rest, 'roles' => $guest_roles, 'options' => $identity_options ) );
		check( 'actual roleless or empty first role normalizes guest', true === wcj_check_modules_by_user_roles( 'returns' ) && 'guest' === $GLOBALS['wcj_modules_by_user_roles_data']['role'] );
		$prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
		$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		check( 'matching guest identity preserves original policy', 'available' === $r['state'] && 1 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	}
	context( array( 'rest' => $identity_rest, 'roles' => array( 'administrator' ), 'options' => $identity_options ) );
	wcj_check_modules_by_user_roles( 'returns' ); $prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
	$GLOBALS['summary_test']['context']['roles'] = array();
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'prior role to guest is unknown not stale counts', 'policy_unavailable' === $r['reason'] && null === $r['counts'] && 0 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	foreach ( array( 'invalid-cache', array(), array( 'role' => null ), array( 'role' => 0 ) ) as $bad_cache ) {
		context( array( 'rest' => $identity_rest, 'options' => $identity_options ) );
		$GLOBALS['wcj_modules_by_user_roles_data'] = $bad_cache;
		$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		check( 'malformed or missing-role cache fails closed without rewrite', 'policy_unavailable' === $r['reason'] && null === $r['counts'] && 0 === calls( 'listing' ) && $bad_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	}
	context( array( 'rest' => $identity_rest, 'roles' => array( 'administrator', 'shop_manager' ), 'options' => $identity_options ) );
	wcj_check_modules_by_user_roles( 'returns' ); $prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'matching first role preserves multi-role policy', 'available' === $r['state'] && 1 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	$GLOBALS['summary_test']['context']['roles'] = array( 'shop_manager', 'administrator' );
	$GLOBALS['summary_test']['calls'] = array();
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'first-role reordering cannot reuse other held role', 'policy_unavailable' === $r['reason'] && null === $r['counts'] && 0 === calls( 'listing' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	context( array( 'rest' => $identity_rest, 'roles' => array( 'administrator' ) ) );
	wcj_check_modules_by_user_roles( 'returns' ); $prior_cache = $GLOBALS['wcj_modules_by_user_roles_data'];
	$GLOBALS['summary_test']['context']['roles'] = array( 'shop_manager' );
	$GLOBALS['summary_test']['calls'] = array();
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'disabled role policy adds no role reads or cache changes', 'available' === $r['state'] && 1 === calls( 'listing' ) && 0 === calls( 'role' ) && $prior_cache === $GLOBALS['wcj_modules_by_user_roles_data'] );
	foreach ( array( array( 'cap' => false ), array( 'user' => 0 ) ) as $identity_deny ) {
		context( array( 'rest' => $identity_rest, 'options' => $identity_options ) );
		$GLOBALS['wcj_modules_by_user_roles_data'] = array( 'role' => 'administrator' );
		$GLOBALS['summary_test']['context'] = array_replace( $GLOBALS['summary_test']['context'], $identity_deny );
		$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
		check( 'denied entry before identity role or any dependency read', is_wp_error( $r ) && 'booster_forbidden' === $r->get_error_code() && 0 === calls( 'role' ) && 0 === calls( 'option' ) && 0 === calls( 'plugin' ) && 0 === calls( 'listing' ) );
	}
	context( array( 'rest' => $identity_rest, 'options' => $identity_options ) );
	$GLOBALS['wcj_modules_by_user_roles_data'] = array( 'role' => 'administrator' );
	$r = $adapter->get_summary( array( 'scope' => 'summary', 'extra' => true ) );
	check( 'strict input before identity role and dependencies', is_wp_error( $r ) && 'booster_invalid_returns_summary_input' === $r->get_error_code() && 0 === calls( 'role' ) && 0 === calls( 'option' ) && 0 === calls( 'listing' ) );
}

foreach ( array( false, true ) as $rest ) {
	context( array( 'rest' => $rest, 'options' => array( 'wcj_modules_by_user_roles_enabled' => 'yes', 'wcj_modules_by_user_roles_excl_shop_manager' => array( 'returns' ) ) ) );
	if ( $rest ) { check( 'actual generic enabled helper bypasses REST role restriction', true === wcj_is_module_enabled( 'returns' ) ); }
	$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'explicit current role exclusion applies on both routes', 'unavailable' === $r['state'] && 'role_restricted' === $r['reason'] && null === $r['counts'] );
	check( 'role exclusion no data/dependency construction', 0 === calls( 'listing' ) && 0 === calls( 'facts_constructor' ) );
}
context( array( 'options' => array( 'wcj_modules_by_user_roles_enabled' => 'yes', 'wcj_modules_by_user_roles_excl_shop_manager' => 'bad-nonarray-policy' ) ) );
$r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'indeterminate policy fails closed', 'policy_unavailable' === $r['reason'] && null === $r['counts'] && 0 === calls( 'listing' ) );
if ( function_exists( 'wcj_get_enabled_module_allowlist' ) ) {
	context( array( 'allowlist' => array( 'modules_by_user_roles' ) ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'actual tier allowlist exclusion preserved', 'module_disabled' === $r['reason'] && null === $r['counts'] && 0 === calls( 'listing' ) );
	context( array( 'allowlist' => array( 'returns', 'modules_by_user_roles' ) ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'actual tier allowlist inclusion uses queue once', 'available' === $r['state'] && 1 === calls( 'listing' ) );
}
context( array( 'options' => array( 'wcj_returns_enabled' => 'no' ) ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'disabled module no queue', 'module_disabled' === $r['reason'] && null === $r['counts'] && 0 === calls( 'facts_constructor' ) );
context(); unset( $GLOBALS['summary_test']['plugin']->all_modules['returns'] ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'missing module unavailable no zero', 'dependency_unavailable' === $r['reason'] && null === $r['counts'] && 0 === calls( 'option' ) );
$zero = array( 'pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'needs_review' => 0, 'facts_unavailable' => 0 );
foreach ( array( 0, 49, 50 ) as $size ) {
	$rows = array();
	for ( $i = 0; $i < $size; ++$i ) { $rows[] = array( 'id' => $i + 1, 'order_id' => $i + 101, 'status' => 'pending', 'secret' => 'SYNTHETIC_PRIVATE_MARKER' ); }
	context( array( 'rows' => $rows ) ); $before_business = $GLOBALS['summary_test']['context']; $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'controlled business inputs unchanged ' . $size, $before_business === $GLOBALS['summary_test']['context'] );
	$expected = $zero; $expected['pending'] = $size;
	check( 'literal sample/status counts ' . $size, 'available' === $r['state'] && 'summary_ready' === $r['reason'] && $size === $r['sample_size'] && ( 50 === $size ) === $r['possibly_truncated'] && $expected === $r['counts'] );
	check( 'closed bounded basis ' . $size, 'latest_request_ids' === $r['basis'] && true === $r['bounded_sample'] && 50 === $r['sample_limit'] && array( 'state', 'reason', 'basis', 'bounded_sample', 'sample_limit', 'sample_size', 'possibly_truncated', 'counts' ) === array_keys( $r ) );
	check( 'actual queue once and one facts read per sample ' . $size, 1 === calls( 'listing' ) && 1 === calls( 'facts_constructor' ) && 1 === calls( 'store_constructor' ) && $size === calls( 'facts' ) );
	check( 'private row fields absent ' . $size, false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE_MARKER' ) && false === strpos( json_encode( $r ), 'order_id' ) );
}
$rows = array();
foreach ( array( 'pending', 'approved', 'declined', 'cancelled' ) as $i => $status ) { $rows[] = array( 'id' => $i + 1, 'order_id' => $i + 101, 'status' => $status ); }
$input = array( 'rows' => $rows, 'amount_only_orders' => array( 101 ), 'unavailable_orders' => array( 102 ), 'anchors' => array( 103 => array( 'facts_hash' => 'stale', 'needs_review' => 0 ) ) );
context( $input ); $actual_queue = ( new WCJ_Returns_Service() )->queue();
check( 'actual queue current flags literal parity basis', true === $actual_queue[0]['needs_review'] && true === $actual_queue[1]['facts_unavailable'] && true === $actual_queue[1]['needs_review'] && true === $actual_queue[2]['needs_review'] && false === $actual_queue[3]['needs_review'] );
context( $input ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'literal mixed counts preserve subset overlap', array( 'pending' => 1, 'approved' => 1, 'declined' => 1, 'cancelled' => 1, 'needs_review' => 3, 'facts_unavailable' => 1 ) === $r['counts'] );
check( 'no second facts evaluator', 4 === calls( 'facts' ) && 3 === calls( 'read_claim' ) && 1 === calls( 'listing' ) );
context( $input ); $ability_result = $ability->execute_returns_summary( array( 'scope' => 'summary' ) );
check( 'Ability/direct full typed result parity', $ability_result === $r && 1 === calls( 'listing' ) );
foreach ( array( 'storage_error', 'payload_error' ) as $error ) {
	context( array( 'rows' => $rows, $error => true ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'actual queue failure not empty counts ' . $error, 'queue_unavailable' === $r['reason'] && null === $r['counts'] && null === $r['sample_size'] && false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE_MARKER' ) );
}
context( $input );
ob_start(); $admin->render_page(); $html = ob_get_clean();
check( 'useful safe admin current sample', false !== strpos( $html, 'latest' ) && false !== strpos( $html, 'Needs review' ) && false !== strpos( $html, 'not full-store totals' ) && false === strpos( $html, 'SYNTHETIC_PRIVATE_MARKER' ) && false === strpos( $html, '<form' ) && 1 === calls( 'listing' ) );
context( array( 'storage_error' => true ) ); ob_start(); $admin->render_page(); $html = ob_get_clean();
check( 'admin unavailable not zero', false !== strpos( $html, 'unavailable' ) && false === strpos( $html, 'SYNTHETIC_PRIVATE_MARKER' ) && false === strpos( $html, '<td>0</td>' ) );
foreach ( array( array( 'cap' => false ), array( 'user' => 0 ) ) as $deny ) {
	context( $deny ); ob_start();
	try { $admin->render_page(); check( 'admin denied', false ); } catch ( RuntimeException $error ) { check( 'admin denied', 'synthetic_safe_wp_die' === $error->getMessage() ); }
	ob_end_clean();
	check( 'admin denial before data reads', 0 === calls( 'option' ) && 0 === calls( 'listing' ) && 0 === calls( 'plugin' ) );
}
do_action( 'admin_menu' );
check( 'real capability-protected submenu wiring', 1 === count( array_filter( $GLOBALS['summary_test']['menus'], static function ( $m ) { return 'woocommerce' === $m[0] && 'manage_woocommerce' === $m[3] && 'wcj-returns-summary' === $m[4] && is_callable( $m[5] ); } ) ) );

// New dependency-only cases. Every prior named expectation above is unchanged.
// Literal eight retained histories: one account and one guest of every status.
$guest_digest = str_repeat( 'd', 64 );
$mixed_rows = array();
$mixed_facts = array();
foreach ( array( 17, 0 ) as $request_actor ) {
	foreach ( array( 'pending', 'approved', 'declined', 'cancelled' ) as $status ) {
		$order_id = 1801 + count( $mixed_rows );
		$history = array( array( 'status' => 'pending', 'actor' => $request_actor, 'time' => 1700000000 ) );
		if ( 'pending' !== $status ) {
			$history[] = array( 'status' => $status, 'actor' => 'cancelled' === $status ? $request_actor : 77, 'time' => 1700000001 );
		}
		$data = array( 'items' => array( 42 => 1000000 ), 'history' => $history, 'outbox' => array(), 'policy' => array( 'days' => 30, 'notify' => false, 'version' => 1 ), 'reason' => 'SYNTHETIC_PRIVATE_REASON customer@example.test' );
		if ( 0 === $request_actor ) {
			$data['guest'] = array( 'v' => 1, 'owner' => $guest_digest );
		}
		$mixed_rows[] = array( 'id' => $order_id - 1000, 'order_id' => $order_id, 'actor_id' => (string) $request_actor, 'status' => $status, 'payload' => json_encode( $data ), 'secret' => 'SYNTHETIC_PRIVATE_COOKIE_AND_TOKEN' );
		$mixed_facts[ $order_id ] = array( 'hash' => 'synthetic-current', 'amount_only' => false, 'owner' => $request_actor, 'guest' => array( 'valid' => 0 === $request_actor, 'digest' => $guest_digest ) );
	}
}
$mixed = array( 'rows' => $mixed_rows, 'facts_by_order' => $mixed_facts, 'validate_payload' => true );
$literal_counts = array( 'pending' => 2, 'approved' => 2, 'declined' => 2, 'cancelled' => 2, 'needs_review' => 0, 'facts_unavailable' => 0 );
context( $mixed ); $before_business = $GLOBALS['summary_test']['context'];
$queue = ( new WCJ_Returns_Service() )->queue();
check( 'guest dependency actual merchant queue retains eight account and actor-zero rows', is_array( $queue ) && 8 === count( $queue ) && array( '17', '17', '17', '17', '0', '0', '0', '0' ) === array_column( $queue, 'actor_id' ) && array( 'pending', 'approved', 'declined', 'cancelled', 'pending', 'approved', 'declined', 'cancelled' ) === array_column( $queue, 'status' ) );
check( 'guest dependency actual payload validator retains fourteen history events', 14 === array_sum( array_map( static function ( $row ) { return count( $row['data']['history'] ); }, $queue ) ) && 0 === $queue[4]['data']['history'][0]['actor'] && 77 === $queue[5]['data']['history'][1]['actor'] && 77 === $queue[6]['data']['history'][1]['actor'] && 0 === $queue[7]['data']['history'][1]['actor'] );
check( 'guest dependency current guest binding has no spurious review hold', array_fill( 0, 8, false ) === array_column( $queue, 'needs_review' ) && array_fill( 0, 8, false ) === array_column( $queue, 'facts_unavailable' ) );
check( 'guest dependency pure read uses actual payload eight times without business changes', 8 === calls( 'actual_payload' ) && 8 === calls( 'facts' ) && 8 === calls( 'read_claim' ) && $before_business === $GLOBALS['summary_test']['context'] );
context( $mixed ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'guest dependency literal mixed status counts through actual Summary', 'available' === $r['state'] && $literal_counts === $r['counts'] && 8 === $r['sample_size'] && false === $r['possibly_truncated'] && 50 === $r['sample_limit'] && 1 === calls( 'listing' ) && 8 === calls( 'actual_payload' ) );
check( 'guest dependency Summary omits private reason identity digest history and token', false === strpos( json_encode( $r ), 'customer@example.test' ) && false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE' ) && false === strpos( json_encode( $r ), $guest_digest ) && false === strpos( json_encode( $r ), 'actor_id' ) && false === strpos( json_encode( $r ), 'order_id' ) && false === strpos( json_encode( $r ), 'history' ) && false === strpos( json_encode( $r ), 'guest' ) );
context( $mixed ); $ability_result = $ability->execute_returns_summary( array( 'scope' => 'summary' ) );
check( 'guest dependency actual Ability preserves complete typed mixed Summary parity', $r === $ability_result && 1 === calls( 'listing' ) && 8 === calls( 'actual_payload' ) );
context( $mixed ); ob_start(); $admin->render_page(); $html = ob_get_clean();
check( 'guest dependency actual merchant admin renders six counts without private fields or forms', 6 === substr_count( $html, '<td>' ) && 4 === substr_count( $html, '<td>2</td>' ) && 2 === substr_count( $html, '<td>0</td>' ) && false === strpos( $html, 'customer@example.test' ) && false === strpos( $html, 'SYNTHETIC_PRIVATE' ) && false === strpos( $html, $guest_digest ) && false === strpos( $html, '<form' ) && 1 === calls( 'listing' ) );
$drifted = $mixed;
$drifted['facts_by_order'][1805]['guest']['digest'] = str_repeat( 'e', 64 );
$drifted['facts_by_order'][1806]['owner'] = 17;
$drifted['unavailable_orders'] = array( 1807 );
context( $drifted ); $queue = ( new WCJ_Returns_Service() )->queue();
check( 'guest dependency changed binding reassigned owner and unavailable facts hold respective rows', true === $queue[4]['needs_review'] && false === $queue[4]['facts_unavailable'] && true === $queue[5]['needs_review'] && false === $queue[5]['facts_unavailable'] && true === $queue[6]['needs_review'] && true === $queue[6]['facts_unavailable'] && false === $queue[7]['needs_review'] );
context( $drifted ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
$drift_counts = array( 'pending' => 2, 'approved' => 2, 'declined' => 2, 'cancelled' => 2, 'needs_review' => 3, 'facts_unavailable' => 1 );
check( 'guest dependency drift counts preserve status totals and unavailable subset overlap', $drift_counts === $r['counts'] && 8 === $r['sample_size'] && 8 === calls( 'actual_payload' ) && 8 === calls( 'facts' ) && 7 === calls( 'read_claim' ) );
foreach ( array( 'missing_binding', 'guest_zero_approval', 'account_with_guest', 'negative_actor' ) as $invalid ) {
	$bad = $mixed;
	$position = 'account_with_guest' === $invalid ? 0 : 5;
	$data = json_decode( $bad['rows'][ $position ]['payload'], true );
	if ( 'missing_binding' === $invalid ) { unset( $data['guest'] ); }
	if ( 'guest_zero_approval' === $invalid ) { $data['history'][1]['actor'] = 0; }
	if ( 'account_with_guest' === $invalid ) { $data['guest'] = array( 'v' => 1, 'owner' => $guest_digest ); }
	if ( 'negative_actor' === $invalid ) { $bad['rows'][ $position ]['actor_id'] = '-1'; }
	$bad['rows'][ $position ]['payload'] = json_encode( $data );
	context( $bad ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'guest dependency invalid persisted history fails closed without partial counts ' . $invalid, 'unavailable' === $r['state'] && 'queue_unavailable' === $r['reason'] && null === $r['counts'] && null === $r['sample_size'] && null === $r['possibly_truncated'] && false === strpos( json_encode( $r ), 'SYNTHETIC_PRIVATE' ) && 1 === calls( 'listing' ) );
}
foreach ( array( array( 'cap' => false ), array( 'user' => 0 ) ) as $deny ) {
	context( array_replace( $mixed, $deny ) ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
	check( 'guest dependency merchant denial still precedes mixed history and identity reads', is_wp_error( $r ) && 'booster_forbidden' === $r->get_error_code() && 0 === calls( 'listing' ) && 0 === calls( 'actual_payload' ) && 0 === calls( 'facts' ) && 0 === calls( 'option' ) );
}
$bounded = $mixed;
$bounded['rows'] = array();
for ( $i = 0; $i < 50; ++$i ) { $bounded['rows'][] = array_replace( $mixed_rows[ $i % 8 ], array( 'id' => 900 - $i ) ); }
context( $bounded ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'guest dependency mixed fifty sample retains existing bounded truncation semantics', 50 === $r['sample_size'] && true === $r['possibly_truncated'] && array( 'pending' => 13, 'approved' => 13, 'declined' => 12, 'cancelled' => 12, 'needs_review' => 0, 'facts_unavailable' => 0 ) === $r['counts'] && 50 === calls( 'actual_payload' ) );
$bounded['rows'][] = $mixed_rows[2];
context( $bounded ); $r = $adapter->get_summary( array( 'scope' => 'summary' ) );
check( 'guest dependency oversized supplier still yields unknown counts not partial totals', 'invalid_queue' === $r['reason'] && null === $r['counts'] && null === $r['sample_size'] && null === $r['possibly_truncated'] );
finished();
