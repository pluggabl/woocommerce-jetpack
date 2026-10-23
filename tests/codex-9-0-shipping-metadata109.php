<?php
/**
 * Source-only metadata failure and caller regression, never a native transport test.
 *
 * @package Booster_For_WooCommerce/tests
 */
if ( PHP_SAPI !== 'cli' || ! isset( $argv[4] ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
$root   = $argv[1];
$config = json_decode( file_get_contents( $argv[2] ), true );
$mode   = $argv[3];
$epoch  = $argv[4];
if ( ! is_array( $config ) || ! in_array( $mode, $config['modes'], true ) ) {
	throw new RuntimeException( 'Invalid finite fixture configuration.' );
}
define( 'ABSPATH', $epoch . '/' );
define( 'WCJ_PLUGIN_FILE', $epoch . '/' . $config['plugin'] );
$_GET = array();
$_POST = array();
$_REQUEST = array();
$GLOBALS['wp_filter'] = array();
$GLOBALS['m103_cap'] = true;
$GLOBALS['m103_roles'] = false;
$GLOBALS['m103_calls'] = array();
$GLOBALS['m103_hooks'] = array();
$GLOBALS['m103_registry'] = array();
$GLOBALS['m103_historical_executed'] = false;
$checks = array();
$warnings = array();
/** Inert WordPress error value, not a product class. */
class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message = '' ) {
		$this->code = $code;
		$this->message = $message;
	}
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function __( $text, $domain = '' ) { return $text; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function current_user_can( $capability ) {
	$GLOBALS['m103_calls'][] = array( 'capability', $capability );
	return $GLOBALS['m103_cap'] && 'manage_woocommerce' === $capability;
}
function wcj_is_module_enabled( $id ) {
	$GLOBALS['m103_calls'][] = array( 'role_policy', $id );
	if ( 'modules_by_user_roles' !== $id ) {
		throw new RuntimeException( 'Unexpected enabled-module primitive.' );
	}
	return $GLOBALS['m103_roles'];
}
function wcj_get_option( $id, $default = false ) {
	$ids = array( 'wcj_shipping_by_order_amount_enabled', 'wcj_shipping_by_order_qty_enabled', 'wcj_shipping_by_user_role_enabled', 'wcj_shipping_by_products_enabled', 'wcj_shipping_by_cities_enabled', 'wcj_shipping_by_time_enabled', 'wcj_shipping_options_enabled' );
	$GLOBALS['m103_calls'][] = array( 'option', $id );
	if ( ! in_array( $id, $ids, true ) ) {
		throw new RuntimeException( 'Unexpected option primitive.' );
	}
	return $default;
}
function wcj_is_module_allowed_by_allowlist( $id ) {
	$GLOBALS['m103_calls'][] = array( 'allowlist', $id );
	return true;
}
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['m103_hooks'][] = array( $hook, $callback, $priority, $accepted );
}
function wp_register_ability( $id, $args ) {
	if ( isset( $GLOBALS['m103_registry'][ $id ] ) ) {
		throw new RuntimeException( 'Duplicate inert registration.' );
	}
	$GLOBALS['m103_registry'][ $id ] = $args;
}
function m103_value( $value ) {
	if ( is_array( $value ) ) {
		$serialized = serialize( $value );
		return array( 'type' => 'array', 'count' => count( $value ), 'serialized_bytes' => strlen( $serialized ), 'sha256' => hash( 'sha256', $serialized ) );
	}
	return $value;
}
function m103_check( $id, $actual, $expected ) {
	global $checks;
	$checks[] = array( 'id' => $id, 'pass' => $actual === $expected, 'actual' => m103_value( $actual ), 'expected' => m103_value( $expected ) );
	if ( $actual !== $expected ) {
		throw new RuntimeException( 'Assertion failed: ' . $id );
	}
}
function m103_error( $value ) { return is_wp_error( $value ) ? $value->get_error_code() : null; }
function m103_private( $service, $name, $args = array() ) {
	$method = new ReflectionMethod( 'WCJ_Rule_Explanations', $name );
	$method->setAccessible( true );
	return $method->invokeArgs( $service, $args );
}
function m103_schemas( $service, $config, $valid ) {
	$expected = $config['oracle']['closed_v1_schemas'];
	$canonical = json_decode( file_get_contents( $config['canonical'] ), true );
	foreach ( array( false, true ) as $compare ) {
		$i = $compare ? 'input_compare' : 'input_explain';
		$o = $compare ? 'output_compare' : 'output_explain';
		$input = $expected[ $i ];
		$output = $expected[ $o ];
		if ( $valid ) {
			$input = array( 'oneOf' => array( $input, $canonical['schemas']['input_schemas'][ $compare ? 'compare_v2' : 'explain_v2' ] ) );
			$output = array( 'oneOf' => array( $output, $canonical['schemas']['output_schemas'][ $compare ? 'comparison_v2' : 'single_v2' ] ) );
		}
		m103_check( 'dispatch-' . $i, $service->get_shipping_dispatch_input_schema( $compare ), $input );
		m103_check( 'dispatch-' . $o, $service->get_shipping_dispatch_output_schema( $compare ), $output );
	}
}
try {
	foreach ( $config['imports'] as $relative => $hash ) {
		m103_check( 'actual-import-' . $relative, hash_file( 'sha256', $epoch . '/' . $relative ), $hash );
	}
	m103_check( 'canonical-payload-length', filesize( $config['canonical'] ), 1985526 );
	m103_check( 'canonical-payload-hash', hash_file( 'sha256', $config['canonical'] ), '4308dc2237678cefb1ffb90b56c3970b2eaa508b916d3d8bf088195de30326c9' );
	$handler = function ( $severity, $message, $file, $line ) use ( &$warnings, $mode ) {
		$warnings[] = array( 'severity' => $severity, 'message_sha256' => hash( 'sha256', $message ) );
		if ( 'throwing-handler' === $mode ) {
			throw new ErrorException( $message, 0, $severity, $file, $line );
		}
		return true;
	};
	set_error_handler( $handler );
	require $epoch . '/includes/class-wcj-rule-explanations.php';
	$service = new WCJ_Rule_Explanations();
	m103_check( 'import-has-no-option-or-capability-read', $GLOBALS['m103_calls'], array() );
	m103_check( 'public-unarmed-observation-denied', m103_error( $service->get_shipping_observation() ), 'booster_shipping_observation_unavailable' );
	m103_check( 'unarmed-observation-no-loader-read', count( $warnings ), 0 );
	$canonical_raw = file_get_contents( $config['canonical'] );
	$historical_raw = file_get_contents( $config['historical_payload'] );
	m103_check( 'JSON-equals-statically-extracted-historical-payload', $canonical_raw === $historical_raw, true );
	$canonical = json_decode( $canonical_raw, true );
	m103_check( 'canonical-decodes-at-default-depth', json_last_error(), JSON_ERROR_NONE );
	$historical_metadata = json_decode( $historical_raw, true );
	m103_check( 'historical-decodes-at-default-depth', json_last_error(), JSON_ERROR_NONE );
	m103_check( 'strict-decoded-historical-metadata-equality', $canonical, $historical_metadata );
	$valid = in_array( $mode, array( 'loader-success', 'success-cache', 'callers-valid', 'registration-valid' ), true );
	$sidecar = $epoch . '/includes/data/wcj-shipping-v2-metadata.json';
	if ( 0 !== strpos( $mode, 'callers-' ) && 0 !== strpos( $mode, 'registration-' ) ) {
		$first = m103_private( $service, 'shipping_v2_metadata' );
		m103_check( 'first-loader-value', $first, $valid ? $canonical : false );
		if ( 'failure-cache' === $mode ) {
			m103_check( 'first-failure-observed', count( $warnings ) > 0, true );
			$before = count( $warnings );
			m103_check( 'restore-synthetic-file-bytes', file_put_contents( $sidecar, file_get_contents( $config['canonical'] ) ), 1985526 );
			m103_check( 'same-instance-failure-sticky', m103_private( $service, 'shipping_v2_metadata' ), false );
			m103_check( 'new-instance-failure-sticky', m103_private( new WCJ_Rule_Explanations(), 'shipping_v2_metadata' ), false );
			m103_check( 'no-repeated-warning-after-restore', count( $warnings ), $before );
		}
		if ( 'success-cache' === $mode ) {
			m103_check( 'corrupt-synthetic-file-bytes', file_put_contents( $sidecar, 'x' ), 1 );
			$first['schemas'] = array();
			m103_check( 'returned-value-mutation-isolated', m103_private( $service, 'shipping_v2_metadata' ), $canonical );
		}
		m103_schemas( $service, $config, $valid );
		m103_check( 'historical-PHP-not-executed', $GLOBALS['m103_historical_executed'], false );
		if ( in_array( $mode, array( 'missing', 'failure-cache', 'throwing-handler', 'observing-handler' ), true ) ) {
			m103_check( 'handler-can-observe-suppressed-warning', count( $warnings ) > 0, true );
		}
		$current = set_error_handler( $handler );
		m103_check( 'product-preserves-existing-handler', $current === $handler, true );
		restore_error_handler();
	} else {
		$oracle = $config['oracle'];
		$input = $oracle['scenario_input'];
		$compare = $oracle['comparison_input'];
		$GLOBALS['m103_cap'] = false;
		m103_check( 'capability-before-input', m103_error( $service->explain_shipping_options( null ) ), 'booster_shipping_capability_required' );
		m103_check( 'capability-before-comparison', m103_error( $service->compare_shipping_options( null ) ), 'booster_shipping_capability_required' );
		m103_check( 'denial-before-metadata-read', count( $warnings ), 0 );
		$GLOBALS['m103_cap'] = true;
		foreach ( array( 1, 3, '2', null ) as $index => $version ) {
			m103_check( 'invalid-version-' . $index, m103_error( $service->explain_shipping_options( array( 'contract_version' => $version ) ) ), 'elite' === $config['tier'] ? 'booster_shipping_invalid_input' : 'booster_shipping_elite_required' );
		}
		m103_check( 'version-and-edition-no-metadata-read', count( $warnings ), 0 );
		$v1 = $config['v1_input'];
		m103_check( 'unchanged-v1-outcome', 'elite' === $config['tier'] ? $service->explain_shipping_options( $v1 ) : m103_error( $service->explain_shipping_options( $v1 ) ), 'elite' === $config['tier'] ? $config['v1_expected'] : 'booster_shipping_elite_required' );
		m103_check( 'v1-does-not-load-v2', count( $warnings ), 0 );
		if ( 'elite' === $config['tier'] ) {
			if ( $valid ) {
				m103_check( 'literal-v2-scenario', $service->explain_shipping_options( $input ), $oracle['scenario_expected'] );
				m103_check( 'literal-v2-comparison', $service->compare_shipping_options( $compare ), $oracle['comparison_expected'] );
				m103_check( 'literal-v2-output-valid', $service->validate_shipping_output( $oracle['scenario_expected'] ), true );
				$GLOBALS['m103_roles'] = true;
				m103_check( 'valid-schema-role-denial', m103_error( $service->explain_shipping_options( $input ) ), 'booster_shipping_context_not_supported' );
				m103_check( 'invalid-input-before-role-policy', m103_error( $service->explain_shipping_options( array( 'contract_version' => 2 ) ) ), 'booster_shipping_invalid_input' );
				$GLOBALS['m103_roles'] = false;
			} else {
				m103_check( 'missing-v2-unavailable', m103_error( $service->explain_shipping_options( $input ) ), 'booster_shipping_diagnostic_unavailable' );
				m103_check( 'missing-compare-unavailable', m103_error( $service->compare_shipping_options( $compare ) ), 'booster_shipping_diagnostic_unavailable' );
				m103_check( 'missing-malformed-v2-unavailable', m103_error( $service->explain_shipping_options( array( 'contract_version' => 2 ) ) ), 'booster_shipping_diagnostic_unavailable' );
			}
		} else {
			m103_check( 'v2-edition-denial', m103_error( $service->explain_shipping_options( $input ) ), 'booster_shipping_elite_required' );
			m103_check( 'v2-comparison-edition-denial', m103_error( $service->compare_shipping_options( $compare ) ), 'booster_shipping_elite_required' );
			m103_check( 'edition-before-metadata-read', count( $warnings ), 0 );
		}
		if ( ! $valid ) {
			m103_check( 'private-input-fails-closed', m103_private( $service, 'shipping_v2_input_valid', array( $input ) ), false );
			m103_check( 'private-rows-fail-closed', m103_private( $service, 'shipping_v2_rows_valid', array( array(), 'explicit_merchant_scenario' ) ), false );
			m103_check( 'private-output-fails-closed', m103_private( $service, 'shipping_v2_output_valid', array( $oracle['scenario_expected'], false ) ), false );
			m103_check( 'private-row-null', m103_private( $service, 'shipping_v2_row', array( 'shipping_by_user_role', 'explicit_merchant_scenario', 'module_not_admitted' ) ), null );
			m103_check( 'direct-null-response-unavailable', m103_error( m103_private( $service, 'shipping_v2_response', array( array_fill( 0, 9, null ), 'explicit_merchant_scenario' ) ) ), 'booster_shipping_diagnostic_unavailable' );
			m103_check( 'public-output-fails-closed', $service->validate_shipping_output( $oracle['scenario_expected'] ), false );
		}
		if ( 0 === strpos( $mode, 'registration-' ) ) {
			foreach ( array( 'class-wcj-status-service.php', 'class-wcj-returns-summary.php', 'class-wcj-order-health-service.php', 'class-wcj-abilities.php' ) as $file ) {
				require $epoch . '/includes/' . $file;
			}
			$abilities = new WCJ_Abilities();
			$abilities->register_abilities();
			m103_check( 'literal-registration-IDs', array_keys( $GLOBALS['m103_registry'] ), $config['ability_ids'] );
			foreach ( $config['ability_pairs'] as $id => $method ) {
				$args = $GLOBALS['m103_registry'][ $id ];
				m103_check( 'execute-pair-' . $id, $args['execute_callback'], array( $abilities, $method ) );
				m103_check( 'permission-pair-' . $id, $args['permission_callback'], array( $abilities, 'check_permission' ) );
			}
			m103_schemas( $service, $config, $valid );
			$registry = $GLOBALS['m103_registry'];
			m103_check( 'registered-explain-input', $registry['booster/explain-shipping-options']['input_schema'], $service->get_shipping_dispatch_input_schema() );
			m103_check( 'registered-explain-output', $registry['booster/explain-shipping-options']['output_schema'], $service->get_shipping_dispatch_output_schema() );
			m103_check( 'registered-compare-input', $registry['booster/compare-shipping-options']['input_schema'], $service->get_shipping_dispatch_input_schema( true ) );
			m103_check( 'registered-compare-output', $registry['booster/compare-shipping-options']['output_schema'], $service->get_shipping_dispatch_output_schema( true ) );
			$GLOBALS['m103_cap'] = false;
			m103_check( 'actual-Ability-permission-denial', m103_error( $abilities->check_permission() ), 'booster_forbidden' );
			m103_check( 'actual-explain-Ability-denial', m103_error( $abilities->execute_shipping_explanation( $input ) ), 'booster_forbidden' );
			m103_check( 'actual-compare-Ability-denial', m103_error( $abilities->execute_shipping_comparison( $compare ) ), 'booster_forbidden' );
			$GLOBALS['m103_cap'] = true;
			$direct_explain = $abilities->execute_shipping_explanation( $input );
			$direct_compare = $abilities->execute_shipping_comparison( $compare );
			$direct_error = 'elite' === $config['tier'] ? 'booster_shipping_diagnostic_unavailable' : 'booster_shipping_elite_required';
			m103_check( 'direct-explain-callback-outcome', $valid && 'elite' === $config['tier'] ? $direct_explain : m103_error( $direct_explain ), $valid && 'elite' === $config['tier'] ? $oracle['scenario_expected'] : $direct_error );
			m103_check( 'direct-compare-callback-outcome', $valid && 'elite' === $config['tier'] ? $direct_compare : m103_error( $direct_compare ), $valid && 'elite' === $config['tier'] ? $oracle['comparison_expected'] : $direct_error );
			if ( ! $valid ) {
				m103_check( 'registration-failure-remains-sticky', file_put_contents( $sidecar, file_get_contents( $config['canonical'] ) ), 1985526 );
				m103_check( 'late-restoration-no-retry', m103_error( $abilities->execute_shipping_explanation( $input ) ), 'elite' === $config['tier'] ? 'booster_shipping_diagnostic_unavailable' : 'booster_shipping_elite_required' );
			}
		}
	}
	restore_error_handler();
	echo json_encode( array( 'status' => 'PASS', 'tier' => $config['tier'], 'mode' => $mode, 'checks' => $checks, 'warnings' => $warnings, 'native' => false ) ) . "\n";
} catch ( Throwable $error ) {
	restore_error_handler();
	fwrite( STDERR, get_class( $error ) . ': ' . $error->getMessage() . "\n" );
	echo json_encode( array( 'status' => 'FAIL', 'mode' => $mode, 'checks' => $checks ) ) . "\n";
	exit( 1 );
}
