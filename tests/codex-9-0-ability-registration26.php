<?php
/**
 * Registration-only coverage for the remaining C-2 and C-3 review notes.
 *
 * Run with php -n followed by this file and its tier. This fixture records inert
 * WordPress registration primitives. It never executes an ability or permission
 * callback, loads WordPress, or connects to WooCommerce, storage or transport.
 *
 * @package Booster_For_WooCommerce/Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

/**
 * Reject an unexpected business or infrastructure operation.
 *
 * @param mixed ...$args Unused arguments from the prohibited operation.
 * @return never
 * @throws RuntimeException Always, because this is registration-only evidence.
 */
function wcj_registration26_unexpected( ...$args ) {
	throw new RuntimeException( 'Unexpected business or infrastructure operation.' );
}

/**
 * Record only the three source-declared initialization hooks without firing them.
 *
 * @param string   $hook_name     Hook name.
 * @param callable $callback      Callback declaration, never invoked here.
 * @param int      $priority      Declared priority.
 * @param int      $accepted_args Declared argument count.
 * @return bool
 * @throws RuntimeException If a different hook is registered.
 */
function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	if ( ! in_array( $hook_name, array( 'wcj_loaded', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ), true ) ) {
		throw new RuntimeException( 'Unexpected initialization hook.' );
	}
	$GLOBALS['wcj_registration26_hooks'][ $hook_name ][] = array( $callback, $priority, $accepted_args );
	return true;
}

/**
 * Capture a complete ability declaration without executing its callbacks.
 *
 * @param string $name       Ability name.
 * @param array  $properties Complete registration properties.
 * @return void
 * @throws RuntimeException If a registration is duplicated.
 */
function wp_register_ability( $name, $properties ) {
	if ( isset( $GLOBALS['wcj_registration26_registry'][ $name ] ) ) {
		throw new RuntimeException( 'Duplicate ability registration.' );
	}
	$GLOBALS['wcj_registration26_registry'][ $name ] = $properties;
}

/**
 * Preserve registration labels without loading translation infrastructure.
 *
 * @param string $text   Source text.
 * @param string $domain Text domain.
 * @return string
 */
function __( $text, $domain = 'default' ) {
	return $text;
}

/**
 * Forbid hooks beyond the inert initialization declarations.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function add_filter( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid a WooCommerce runtime connection.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function WC( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid order queries.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function wc_get_orders( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid order reads.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function wc_get_order( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid option reads.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function get_option( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid option mutations.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function update_option( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid outbound requests.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function wp_remote_request( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid mail attempts.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function wp_mail( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Forbid permission execution; this fixture checks the declaration only.
 *
 * @param mixed ...$args Prohibited arguments.
 * @return never
 */
function current_user_can( ...$args ) {
	wcj_registration26_unexpected( ...$args );
}

/**
 * Preserve a strict full-array comparison, including lossless PHP representations.
 *
 * JSON values aid inspection; the serialized representations retain nested PHP
 * key/value types and ordering. Both arrays are complete, not selected subtrees.
 *
 * @param string $label    Frozen case label.
 * @param array  $actual   Registered schema.
 * @param array  $expected Schema from the actual public getter.
 * @param string $oracle   Exact public getter and argument.
 * @return array
 */
function wcj_registration26_schema_case( $label, $actual, $expected, $oracle ) {
	$actual_serialized   = serialize( $actual );
	$expected_serialized = serialize( $expected );
	return array(
		'label'                          => $label,
		'passed'                         => $actual === $expected,
		'actual_type'                    => gettype( $actual ),
		'expected_type'                  => gettype( $expected ),
		'actual'                         => $actual,
		'expected'                       => $expected,
		'actual_php_serialized_base64'   => base64_encode( $actual_serialized ),
		'expected_php_serialized_base64' => base64_encode( $expected_serialized ),
		'actual_serialized_sha256'       => hash( 'sha256', $actual_serialized ),
		'expected_serialized_sha256'     => hash( 'sha256', $expected_serialized ),
		'oracle'                         => $oracle,
	);
}

/**
 * Check exact callback arrays against the actual registering object.
 *
 * No callback is invoked. The strict comparison includes array keys, object
 * identity and literal method strings, not merely callability or class names.
 *
 * @param string        $label       Frozen case label.
 * @param array         $declaration Actual registration.
 * @param WCJ_Abilities $owner       Actual hook-registered object.
 * @param string        $execute     Literal expected execute method.
 * @return array
 */
function wcj_registration26_callback_case( $label, $declaration, $owner, $execute ) {
	$actual   = array( $declaration['execute_callback'], $declaration['permission_callback'] );
	$expected = array( array( $owner, $execute ), array( $owner, 'check_permission' ) );
	$view     = array();
	foreach ( $actual as $pair ) {
		$view[] = array(
			'keys'              => array_keys( $pair ),
			'object_class'      => get_class( $pair[0] ),
			'same_owner_object' => $owner === $pair[0],
			'method'            => $pair[1],
		);
	}
	return array(
		'label'         => $label,
		'passed'        => $actual === $expected,
		'actual_type'   => gettype( $actual ),
		'expected_type' => gettype( $expected ),
		'actual'        => $view,
		'expected'      => array(
			array(
				'keys'              => array( 0, 1 ),
				'object_class'      => 'WCJ_Abilities',
				'same_owner_object' => true,
				'method'            => $execute,
			),
			array(
				'keys'              => array( 0, 1 ),
				'object_class'      => 'WCJ_Abilities',
				'same_owner_object' => true,
				'method'            => 'check_permission',
			),
		),
	);
}

$wcj_registration26_tier = isset( $argv[1] ) ? $argv[1] : '';
if ( ! in_array( $wcj_registration26_tier, array( 'free', 'plus', 'elite' ), true ) ) {
	throw new RuntimeException( 'Supply an explicit valid tier.' );
}
define( 'ABSPATH', __DIR__ . '/' );
$wcj_registration26_files = array(
	'free'  => 'woocommerce-jetpack.php',
	'plus'  => 'booster-plus-for-woocommerce.php',
	'elite' => 'booster-elite-for-woocommerce.php',
);
define( 'WCJ_PLUGIN_FILE', $wcj_registration26_files[ $wcj_registration26_tier ] );
$GLOBALS['wcj_registration26_hooks']    = array();
$GLOBALS['wcj_registration26_registry'] = array();
$_GET                                   = array();

require_once dirname( __DIR__ ) . '/includes/class-wcj-status-service.php';
require_once dirname( __DIR__ ) . '/includes/class-wcj-rule-explanations.php';
require_once dirname( __DIR__ ) . '/includes/class-wcj-abilities.php';

$wcj_registration26_hook  = $GLOBALS['wcj_registration26_hooks']['wp_abilities_api_init'];
$wcj_registration26_owner = $wcj_registration26_hook[0][0][0];
if ( 1 !== count( $wcj_registration26_hook ) || 'WCJ_Abilities' !== get_class( $wcj_registration26_owner ) || 'register_abilities' !== $wcj_registration26_hook[0][0][1] ) {
	throw new RuntimeException( 'Actual registration hook identity changed.' );
}
$wcj_registration26_owner->register_abilities();
$wcj_registration26_service  = new WCJ_Rule_Explanations();
$wcj_registration26_registry = $GLOBALS['wcj_registration26_registry'];
$wcj_registration26_cases    = array(
	wcj_registration26_schema_case(
		'explain_shipping_registered_output_exact',
		$wcj_registration26_registry['booster/explain-shipping-options']['output_schema'],
		$wcj_registration26_service->get_shipping_dispatch_output_schema( false ),
		'WCJ_Rule_Explanations::get_shipping_dispatch_output_schema(false)'
	),
	wcj_registration26_schema_case(
		'compare_shipping_registered_input_exact',
		$wcj_registration26_registry['booster/compare-shipping-options']['input_schema'],
		$wcj_registration26_service->get_shipping_dispatch_input_schema( true ),
		'WCJ_Rule_Explanations::get_shipping_dispatch_input_schema(true)'
	),
	wcj_registration26_callback_case(
		'payment_explain_callback_pair_exact',
		$wcj_registration26_registry['booster/explain-payment-options'],
		$wcj_registration26_owner,
		'execute_payment_explanation'
	),
	wcj_registration26_callback_case(
		'payment_compare_callback_pair_exact',
		$wcj_registration26_registry['booster/compare-payment-options'],
		$wcj_registration26_owner,
		'execute_payment_comparison'
	),
	wcj_registration26_callback_case(
		'gateway_fee_callback_pair_exact',
		$wcj_registration26_registry['booster/explain-gateway-fee'],
		$wcj_registration26_owner,
		'execute_rule_explanation'
	),
);
$wcj_registration26_imports  = array();
foreach ( get_included_files() as $wcj_registration26_path ) {
	$wcj_registration26_imports[] = array(
		'path'   => $wcj_registration26_path,
		'bytes'  => filesize( $wcj_registration26_path ),
		'sha256' => hash_file( 'sha256', $wcj_registration26_path ),
	);
}
$wcj_registration26_pass = true;
foreach ( $wcj_registration26_cases as $wcj_registration26_case ) {
	if ( true !== $wcj_registration26_case['passed'] ) {
		$wcj_registration26_pass = false;
	}
}
$wcj_registration26_encoded = json_encode(
	array(
		'tier'              => $wcj_registration26_tier,
		'status'            => $wcj_registration26_pass ? 'PASS' : 'FAIL',
		'cases'             => $wcj_registration26_cases,
		'imports'           => $wcj_registration26_imports,
		'callbacks_invoked' => 0,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
if ( JSON_ERROR_NONE !== json_last_error() || ! is_string( $wcj_registration26_encoded ) ) {
	throw new RuntimeException( 'Unable to encode registration fixture output.' );
}
echo $wcj_registration26_encoded;
echo PHP_EOL;
exit( $wcj_registration26_pass ? 0 : 1 );
