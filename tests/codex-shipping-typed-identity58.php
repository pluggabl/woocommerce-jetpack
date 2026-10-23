<?php
/**
 * Source-bound complete-input M3 correction qualification. CLI only, not native checkout.
 *
 * @package Booster_For_WooCommerce/tests
 */
if ( 'cli' !== PHP_SAPI || count( $argv ) !== 4 ) {
	exit( 2 );
}
$codex58_root = realpath( $argv[1] );
$codex58_tier = $argv[2];
$codex58_plan = json_decode( file_get_contents( $argv[3] ), true );
$codex58_rows = array();
$codex58_observations = array();
$codex58_state = array( 'capability' => true, 'roles_gate' => false, 'cap_calls' => 0, 'module_calls' => array(), 'option_calls' => array(), 'hooks' => array() );
/** Minimal WordPress error primitive, not a production service double. */
class WP_Error {
	public $code;
	public function __construct( $code, $message = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
function __( $text, $domain = '' ) { return $text; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function current_user_can( $capability ) {
	global $codex58_state;
	if ( 'manage_woocommerce' !== $capability ) { throw new RuntimeException( 'Unexpected capability' ); }
	++$codex58_state['cap_calls'];
	return $codex58_state['capability'];
}
function wcj_is_module_enabled( $module ) {
	global $codex58_state;
	$codex58_state['module_calls'][] = $module;
	if ( 'modules_by_user_roles' !== $module ) { throw new RuntimeException( 'Unexpected module read' ); }
	return $codex58_state['roles_gate'];
}
function wcj_get_option( $key, $default = false ) {
	global $codex58_state;
	$codex58_state['option_calls'][] = $key;
	if ( 'no' !== $default ) { throw new RuntimeException( 'Unexpected option default' ); }
	return $default;
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $codex58_state;
	$codex58_state['hooks'][] = array( $hook, $callback, $priority, $accepted_args );
}
function codex58_typed( $value ) {
	if ( is_array( $value ) ) {
		$pairs = array();
		foreach ( $value as $key => $item ) { $pairs[] = array( codex58_typed( $key ), codex58_typed( $item ) ); }
		return array( 'type' => 'array', 'pairs' => $pairs );
	}
	if ( is_string( $value ) ) { return array( 'type' => 'string', 'base64' => base64_encode( $value ) ); }
	return array( 'type' => gettype( $value ), 'value' => $value );
}
function codex58_assert( $case, $actual, $expected ) {
	global $codex58_rows;
	$codex58_rows[] = array( 'case' => $case, 'actual' => codex58_typed( $actual ), 'expected' => codex58_typed( $expected ), 'pass' => $actual === $expected );
	if ( $actual !== $expected ) { throw new RuntimeException( 'Literal assertion failed: ' . $case ); }
}
function codex58_snapshot() {
	global $codex58_state;
	return array( 'cap_calls' => $codex58_state['cap_calls'], 'module_calls' => $codex58_state['module_calls'], 'option_calls' => $codex58_state['option_calls'] );
}
function codex58_reset( $capability, $roles_gate ) {
	global $codex58_state;
	$codex58_state['capability'] = $capability;
	$codex58_state['roles_gate'] = $roles_gate;
	$codex58_state['cap_calls'] = 0;
	$codex58_state['module_calls'] = array();
	$codex58_state['option_calls'] = array();
}
set_error_handler( function( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );
$codex58_exit = 0;
try {
	if ( ! is_array( $codex58_plan ) || ! isset( $codex58_plan['source_bindings'][ $codex58_tier ] ) ) { throw new RuntimeException( 'Invalid bound plan' ); }
	foreach ( $codex58_plan['source_bindings'][ $codex58_tier ] as $relative => $identity ) {
		$path = $codex58_root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
		if ( filesize( $path ) !== $identity['bytes'] || hash_file( 'sha256', $path ) !== $identity['sha256'] ) { throw new RuntimeException( 'Import identity mismatch: ' . $relative ); }
	}
	$_GET = array();
	$_POST = array();
	$_REQUEST = array();
	$wp_filter = array();
	define( 'ABSPATH', $codex58_root . DIRECTORY_SEPARATOR );
	define( 'WCJ_PLUGIN_FILE', $codex58_root . DIRECTORY_SEPARATOR . $codex58_plan['plugin_files'][ $codex58_tier ] );
	require $codex58_root . '/includes/class-wcj-rule-explanations.php';
	$reflection = new ReflectionClass( 'WCJ_Rule_Explanations' );
	$service = $reflection->newInstanceWithoutConstructor();
	$validator = $reflection->getMethod( 'shipping_v2_input_valid' );
	$validator->setAccessible( true );
	codex58_assert( 'actual_production_declaring_class', $validator->getDeclaringClass()->getName(), 'WCJ_Rule_Explanations' );
	codex58_assert( 'actual_production_file', realpath( $validator->getFileName() ), realpath( $codex58_root . '/includes/class-wcj-rule-explanations.php' ) );
	codex58_assert( 'unarmed_transport_no_cache_constant', defined( 'DONOTCACHEPAGE' ), false );
	codex58_assert( 'nonexecuting_initial_hook_registration_count', count( $codex58_state['hooks'] ), 2 );
	foreach ( $codex58_plan['validator_cases'] as $case ) {
		codex58_reset( true, false );
		$before = serialize( $case['input'] );
		$actual = $validator->invoke( $service, $case['input'], $case['compare'] );
		codex58_assert( 'validator/' . $case['id'], $actual, $case['expected'] );
		codex58_assert( 'input_unchanged/' . $case['id'], serialize( $case['input'] ), $before );
		codex58_assert( 'no_business_primitives/' . $case['id'], codex58_snapshot(), array( 'cap_calls' => 0, 'module_calls' => array(), 'option_calls' => array() ) );
	}
	foreach ( $codex58_plan['public_cases'] as $case ) {
		codex58_reset( $case['capability'], $case['roles_gate'] );
		$before = serialize( $case['input'] );
		$out = call_user_func( array( $service, $case['method'] ), $case['input'] );
		$expected = $case['expected'][ $codex58_tier ];
		$actual = is_wp_error( $out ) ? array( 'error' => $out->get_error_code() ) : $out;
		codex58_assert( 'public/' . $case['id'], $actual, $expected['value'] );
		codex58_assert( 'precedence/' . $case['id'], codex58_snapshot(), $expected['calls'] );
		codex58_assert( 'public_input_unchanged/' . $case['id'], serialize( $case['input'] ), $before );
		$codex58_observations[] = array( 'id' => $case['id'], 'method' => $case['method'], 'actual' => codex58_typed( $actual ), 'calls' => codex58_snapshot() );
	}
	$imports = array();
	foreach ( get_included_files() as $included ) {
		if ( strpos( $included, $codex58_root . DIRECTORY_SEPARATOR ) === 0 ) {
			$imports[] = array( 'path' => $included, 'bytes' => filesize( $included ), 'sha256' => hash_file( 'sha256', $included ) );
		}
	}
	codex58_assert( 'three_production_imports_plus_fixture', count( $imports ), 4 );
	$result = array( 'status' => 'AFFECTED_FULL_INPUT_SOURCE_PASS', 'tier' => $codex58_tier, 'php_version' => PHP_VERSION, 'assertions' => $codex58_rows, 'public_observations' => $codex58_observations, 'imports' => $imports, 'limitations' => $codex58_plan['fixture_limits'] );
} catch ( Throwable $error ) {
	$codex58_exit = 1;
	$result = array( 'status' => 'STOP', 'tier' => $codex58_tier, 'php_version' => PHP_VERSION, 'error_class' => get_class( $error ), 'error' => $error->getMessage(), 'error_file' => $error->getFile(), 'error_line' => $error->getLine(), 'assertions' => $codex58_rows, 'public_observations' => $codex58_observations );
}
echo json_encode( $result ) . "\n";
exit( $codex58_exit );
