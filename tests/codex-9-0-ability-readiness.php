<?php
/**
 * Source-only local readiness regressions with actual pinned public WP helpers.
 * Usage: php -n FILE ROOT WP_SOURCE [normal|missing-api|missing-has|missing-ssl|missing-app|malformed-helpers|helper-exception].
 * No native user, credential, transport, database or Ability execution.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) ) {
	exit( 2 );
}
$root = $argv[1] ?? '';
$wp   = $argv[2] ?? '';
$mode = $argv[3] ?? 'normal';
if ( ! is_file( $root . '/includes/class-wcj-rule-explanations-admin.php' ) || ! is_file( $wp . '/wp-includes/abilities-api.php' ) || ! in_array( $mode, array( 'normal', 'missing-api', 'missing-has', 'missing-ssl', 'missing-app', 'malformed-helpers', 'helper-exception' ), true ) ) {
	exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' );
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
$GLOBALS['readiness_test'] = array( 'checks' => array(), 'imports' => array(), 'mutations' => 0, 'execution' => 0, 'permissions' => 0, 'core_actions' => array(), 'wp_args' => array(), 'credential_reads' => 0, 'http' => 0 );

/** Import the entire actual public function body, not rewritten expectations. */
function readiness_import( $path, $name ) {
	$tokens = token_get_all( file_get_contents( $path ) );
	for ( $i = 0, $count = count( $tokens ); $i < $count; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) { continue; }
		$j = $i + 1;
		while ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { ++$j; }
		if ( ! is_array( $tokens[ $j ] ) || $name !== $tokens[ $j ][1] ) { continue; }
		$code = ''; $depth = 0; $started = false;
		for ( $k = $i; $k < $count; ++$k ) {
			$token = $tokens[ $k ]; $code .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) { ++$depth; $started = true; } elseif ( '}' === $token ) { --$depth; }
			if ( $started && 0 === $depth ) { $GLOBALS['readiness_test']['imports'][ $name ] = hash( 'sha256', $code ); eval( $code ); return; }
		}
	}
	throw new RuntimeException( 'Pinned public helper missing' );
}
function readiness_check( $name, $actual, $expected ) {
	$GLOBALS['readiness_test']['checks'][] = array( 'name' => $name, 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected );
}
function readiness_reset() {
	$GLOBALS['readiness_case'] = array( 'init' => true, 'admin' => true, 'logged' => true, 'capable' => true, 'rest' => false, 'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false ) ), 'name' => null, 'throw_meta' => false, 'bad_meta' => false, 'hostile_translation' => false, 'app_filter' => true, 'choices' => true );
	$GLOBALS['readiness_test']['meta_calls'] = array(); $GLOBALS['readiness_test']['user_reads'] = 0; $GLOBALS['readiness_test']['ssl_calls'] = 0; $GLOBALS['readiness_test']['app_calls'] = 0; $GLOBALS['readiness_test']['probes'] = array(); $GLOBALS['readiness_test']['fetches'] = array(); $GLOBALS['readiness_test']['service_calls'] = array();
	$_SERVER = array( 'HTTPS' => 'on', 'REQUEST_METHOD' => 'GET' ); $_POST = array();
}
function is_admin() { return $GLOBALS['readiness_case']['admin']; }
function is_user_logged_in() { return $GLOBALS['readiness_case']['logged']; }
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['readiness_case']['capable']; }
function wcj_is_rest() { return $GLOBALS['readiness_case']['rest']; }
class WP_User { public function exists() { return true; } }
function wp_get_current_user() { ++$GLOBALS['readiness_test']['user_reads']; return new WP_User(); }
function wp_get_environment_type() { return 'production'; }
function get_userdata( $id ) { throw new RuntimeException( 'Unexpected user lookup' ); }
function __( $text, $domain = '' ) { return $GLOBALS['readiness_case']['hostile_translation'] ? '<script>translation</script>' : $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = '' ) { return esc_html( __( $text, $domain ) ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, $args ); }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_unslash( $value ) { return $value; }
function add_action( ...$args ) {}
function add_submenu_page( ...$args ) {}
function admin_url( $path = '' ) { return 'https://example.invalid/wp-admin/' . $path; }
function wp_nonce_field( $action ) { echo '<input name="_wpnonce" value="synthetic">'; }
function wp_verify_nonce( $nonce, $action ) { return 'synthetic' === $nonce && 'wcj_explain_gateway_fee' === $action; }
function selected( $a, $b, $echo = true ) { return $a === $b ? 'selected' : ''; }
function wp_die( $text ) { throw new RuntimeException( 'denied' ); }
class WP_Error { private $message; public function __construct( $code, $message ) { $this->message = $message; } public function get_error_message() { return $this->message; } }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function _doing_it_wrong( ...$args ) { throw new RuntimeException( 'Unexpected core incorrect-use notice' ); }
function did_action( $action ) { return 'init' === $action ? ( $GLOBALS['readiness_case']['init'] ? 1 : 0 ) : count( array_keys( $GLOBALS['readiness_test']['core_actions'], $action, true ) ); }
function doing_action( $action ) { return $GLOBALS['readiness_test']['current_action'] === $action; }
function apply_filters( $name, $value, ...$args ) {
	if ( 'wp_is_application_passwords_available_for_user' === $name ) { ++$GLOBALS['readiness_test']['app_calls']; return $GLOBALS['readiness_case']['app_filter']; }
	return $value;
}
function do_action( $name, ...$args ) {
	$GLOBALS['readiness_test']['core_actions'][] = $name; $GLOBALS['readiness_test']['current_action'] = $name;
	if ( 'wp_abilities_api_categories_init' === $name ) { wp_register_ability_category( 'fixture', array( 'label' => 'Fixture', 'description' => 'Synthetic prerequisites' ) ); }
	if ( 'wp_abilities_api_init' === $name ) { readiness_register(); }
	$GLOBALS['readiness_test']['current_action'] = '';
}
function readiness_execute() { ++$GLOBALS['readiness_test']['execution']; throw new RuntimeException( 'Ability must not execute' ); }
function readiness_permission() { ++$GLOBALS['readiness_test']['permissions']; throw new RuntimeException( 'Permission callback must not execute' ); }
function update_option( ...$args ) { ++$GLOBALS['readiness_test']['mutations']; throw new RuntimeException( 'No writes' ); }
function wp_remote_get( ...$args ) { ++$GLOBALS['readiness_test']['http']; throw new RuntimeException( 'No HTTP' ); }
class WP_Application_Passwords { public static function get_user_application_passwords( ...$args ) { ++$GLOBALS['readiness_test']['credential_reads']; throw new RuntimeException( 'No credential enumeration' ); } }

readiness_reset();
foreach ( array( 'class-wp-ability.php', 'class-wp-abilities-registry.php', 'class-wp-ability-category.php', 'class-wp-ability-categories-registry.php' ) as $file ) { require $wp . '/wp-includes/abilities-api/' . $file; }
/** Exact normal public getter methods, overridden only for adversarial metadata boundaries. */
class Readiness_Test_Ability extends WP_Ability {
	public function get_name(): string { return null === $GLOBALS['readiness_case']['name'] ? parent::get_name() : $GLOBALS['readiness_case']['name']; }
	public function get_meta(): array {
		$GLOBALS['readiness_test']['meta_calls'][] = parent::get_name();
		if ( $GLOBALS['readiness_case']['throw_meta'] ) { throw new RuntimeException( 'private-exception-detail' ); }
		if ( $GLOBALS['readiness_case']['bad_meta'] ) { return 'false'; }
		return array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false ) ) === $GLOBALS['readiness_case']['meta'] ? parent::get_meta() : $GLOBALS['readiness_case']['meta'];
	}
}
function readiness_register() {
	foreach ( array( 'booster/explain-gateway-fee', 'booster/returns-summary' ) as $name ) {
		wp_register_ability( $name, array( 'label' => 'Fixture', 'description' => 'Synthetic declarations only', 'category' => 'fixture', 'execute_callback' => 'readiness_execute', 'permission_callback' => 'readiness_permission', 'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false ) ), 'ability_class' => Readiness_Test_Ability::class ) );
	}
}
foreach ( array( 'wp_register_ability', 'wp_unregister_ability', 'wp_register_ability_category', 'wp_has_ability_category' ) as $name ) { readiness_import( $wp . '/wp-includes/abilities-api.php', $name ); }
if ( ! in_array( $mode, array( 'missing-api', 'malformed-helpers', 'helper-exception' ), true ) ) { readiness_import( $wp . '/wp-includes/abilities-api.php', 'wp_get_ability' ); }
if ( ! in_array( $mode, array( 'missing-api', 'missing-has', 'malformed-helpers', 'helper-exception' ), true ) ) { readiness_import( $wp . '/wp-includes/abilities-api.php', 'wp_has_ability' ); }
if ( 'malformed-helpers' === $mode || 'helper-exception' === $mode ) {
	function wp_has_ability( $name ) { $GLOBALS['readiness_test']['probes'][] = $name; if ( 'helper-exception' === $GLOBALS['mode'] ) { throw new RuntimeException( 'private-probe-detail' ); } return 'false'; }
	function wp_get_ability( $name ) { $GLOBALS['readiness_test']['fetches'][] = $name; throw new RuntimeException( 'Malformed probe must prevent fetch' ); }
}
if ( 'missing-ssl' !== $mode && 'malformed-helpers' !== $mode ) { readiness_import( $wp . '/wp-includes/load.php', 'is_ssl' ); }
if ( 'malformed-helpers' === $mode ) { function is_ssl() { return 'false'; } }
if ( 'missing-app' !== $mode && 'malformed-helpers' !== $mode ) {
	foreach ( array( 'wp_is_application_passwords_supported', 'wp_is_application_passwords_available', 'wp_is_application_passwords_available_for_user' ) as $name ) { readiness_import( $wp . '/wp-includes/user.php', $name ); }
}
if ( 'malformed-helpers' === $mode ) { function wp_is_application_passwords_available_for_user( $user ) { return 'false'; } }
$GLOBALS['mode'] = $mode;
/** Only the existing admin dependency is doubled here; owner actual-service fixture is retained separately. */
class WCJ_Rule_Explanations {
	public function get_scenario_choices() { return array( 'gateways' => $GLOBALS['readiness_case']['choices'] ? array( 'cod' ) : array(), 'roles' => array( 'guest' ) ); }
	public function is_full_experience() { return 'elite' === basename( $GLOBALS['root'] ); }
	public function explain( $input ) { $GLOBALS['readiness_test']['service_calls'][] = $input; return array( 'state' => 'unavailable', 'reason' => 'no_evaluation', 'amount' => null, 'conditions' => array(), 'context' => 'merchant_supplied_scenario' ); }
}
require $root . '/includes/class-wcj-rule-explanations-admin.php';
$admin = new WCJ_Rule_Explanations_Admin();
$facts_method = method_exists( $admin, 'readiness_facts' ) ? new ReflectionMethod( $admin, 'readiness_facts' ) : null;
$render_method = method_exists( $admin, 'render_readiness' ) ? new ReflectionMethod( $admin, 'render_readiness' ) : null;
function readiness_facts( $admin, $method ) { return $method ? $method->invoke( $admin ) : null; }
function readiness_render( $admin, $method ) { ob_start(); if ( $method ) { $method->invoke( $admin ); } return ob_get_clean(); }
function readiness_project( $html ) { return preg_replace( '/<section class="wcj-ability-readiness">.*?<\/section>/s', '', $html ); }
$ids = array( 'booster/explain-gateway-fee', 'booster/returns-summary' );
$facts = readiness_facts( $admin, $facts_method );
$api = 'missing-api' === $mode ? 'missing' : ( 'missing-has' === $mode ? 'unknown' : 'available' );
readiness_check( 'core API exact state', $facts['api'] ?? null, $api );
readiness_check( 'exact two fixed names only', isset( $facts['abilities'] ) ? array_keys( $facts['abilities'] ) : null, $ids );
if ( in_array( $mode, array( 'normal', 'missing-ssl', 'missing-app' ), true ) ) {
	readiness_check( 'core lazy initialization exactly once', $GLOBALS['readiness_test']['core_actions'], array( 'wp_abilities_api_categories_init', 'wp_abilities_api_init' ) );
	readiness_check( 'metadata getter names exactly two', $GLOBALS['readiness_test']['meta_calls'], $ids );
	foreach ( $ids as $id ) {
		readiness_check( $id . ' exact object and strict declarations', $facts['abilities'][ $id ] ?? null, array( 'registration' => 'available', 'name' => $id, 'show_in_rest' => true, 'readonly' => true, 'non_destructive' => true ) );
	}
	readiness_reset(); $GLOBALS['readiness_case']['init'] = false;
	$facts = readiness_facts( $admin, $facts_method );
	readiness_check( 'before core init cannot certify missing registration', $facts['api'] ?? null, 'unknown' );
	readiness_check( 'before core init performs no metadata lookup', $GLOBALS['readiness_test']['meta_calls'], array() );
	readiness_reset();
	// Missing registration must not call wp_get_ability and cause _doing_it_wrong.
	wp_unregister_ability( $ids[1] );
	readiness_reset(); $facts = readiness_facts( $admin, $facts_method );
	readiness_check( 'missing registration not zero/ready', $facts['abilities'][ $ids[1] ]['registration'] ?? null, 'missing' );
	readiness_check( 'missing object has unknown declarations', $facts['abilities'][ $ids[1] ] ?? null, array( 'registration' => 'missing', 'name' => null, 'show_in_rest' => null, 'readonly' => null, 'non_destructive' => null ) );
	// Re-register on the synthetic core action only, outside the runtime observation.
	$GLOBALS['readiness_test']['current_action'] = 'wp_abilities_api_init';
	wp_register_ability( $ids[1], array( 'label' => 'Fixture', 'description' => 'Synthetic', 'category' => 'fixture', 'execute_callback' => 'readiness_execute', 'permission_callback' => 'readiness_permission', 'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false ) ), 'ability_class' => Readiness_Test_Ability::class ) );
	$GLOBALS['readiness_test']['current_action'] = '';
	foreach ( array( 'show_in_rest', 'readonly', 'destructive' ) as $key ) {
		readiness_reset();
		if ( 'show_in_rest' === $key ) { $GLOBALS['readiness_case']['meta'][ $key ] = 'false'; } else { $GLOBALS['readiness_case']['meta']['annotations'][ $key ] = 'false'; }
		$facts = readiness_facts( $admin, $facts_method );
		readiness_check( $key . ' stringfalse not boolean', $facts['abilities'][ $ids[0] ][ 'destructive' === $key ? 'non_destructive' : $key ] ?? null, null );
	}
	foreach ( array( 'throw_meta', 'bad_meta', 'wrong_name', 'malformed_annotations', 'missing_annotations', 'false_booleans' ) as $case ) {
		readiness_reset();
		if ( 'throw_meta' === $case || 'bad_meta' === $case ) { $GLOBALS['readiness_case'][ $case ] = true; }
		if ( 'wrong_name' === $case ) { $GLOBALS['readiness_case']['name'] = '<script>untrusted-name</script>'; }
		if ( 'malformed_annotations' === $case ) { $GLOBALS['readiness_case']['meta']['annotations'] = 'false'; }
		if ( 'missing_annotations' === $case ) { unset( $GLOBALS['readiness_case']['meta']['annotations'] ); }
		if ( 'false_booleans' === $case ) { $GLOBALS['readiness_case']['meta'] = array( 'show_in_rest' => false, 'annotations' => array( 'readonly' => false, 'destructive' => true ) ); }
		$facts = readiness_facts( $admin, $facts_method );
		readiness_check( $case . ' exact metadata boundary', $facts['abilities'][ $ids[0] ] ?? null,
			in_array( $case, array( 'throw_meta', 'bad_meta', 'wrong_name' ), true ) ? array( 'registration' => 'unknown', 'name' => null, 'show_in_rest' => null, 'readonly' => null, 'non_destructive' => null ) : array( 'registration' => 'available', 'name' => $ids[0], 'show_in_rest' => 'false_booleans' !== $case, 'readonly' => 'false_booleans' === $case ? false : null, 'non_destructive' => 'false_booleans' === $case ? false : null ) );
	}
} else {
	foreach ( $ids as $id ) { readiness_check( $id . ' unsupported helper state unknown', $facts['abilities'][ $id ]['registration'] ?? null, 'unknown' ); }
	readiness_check( 'no metadata fetch after invalid helper', $GLOBALS['readiness_test']['fetches'], array() );
	if ( in_array( $mode, array( 'malformed-helpers', 'helper-exception' ), true ) ) { readiness_check( 'two fixed existence probes only', $GLOBALS['readiness_test']['probes'], $ids ); }
}
readiness_reset(); $facts = readiness_facts( $admin, $facts_method );
readiness_check( 'local HTTPS exact boolean or unknown', $facts['https'] ?? null, in_array( $mode, array( 'missing-ssl', 'malformed-helpers' ), true ) ? null : true );
readiness_check( 'current user availability not credential proof', $facts['application_passwords'] ?? null, in_array( $mode, array( 'missing-app', 'malformed-helpers', 'missing-ssl' ), true ) ? null : true );
readiness_reset(); $_SERVER['HTTPS'] = 'off'; $_SERVER['SERVER_PORT'] = '443';
$facts = readiness_facts( $admin, $facts_method );
readiness_check( 'HTTPS explicit off overrides port443', $facts['https'] ?? null, in_array( $mode, array( 'missing-ssl', 'malformed-helpers' ), true ) ? null : false );
if ( ! in_array( $mode, array( 'missing-app', 'malformed-helpers', 'missing-ssl' ), true ) ) { readiness_check( 'application availability false on nonSSL production', $facts['application_passwords'] ?? null, false ); }
readiness_reset(); $GLOBALS['readiness_case']['app_filter'] = 'false'; $facts = readiness_facts( $admin, $facts_method );
readiness_check( 'availability stringfalse remains unknown', $facts['application_passwords'] ?? null, null );
foreach ( array( 'admin', 'logged', 'capable', 'rest' ) as $deny ) {
	readiness_reset(); $GLOBALS['readiness_case'][ $deny ] = 'rest' === $deny;
	readiness_check( $deny . ' denied before observation', readiness_facts( $admin, $facts_method ), null );
	readiness_check( $deny . ' private panel suppressed', readiness_render( $admin, $render_method ), '' );
	readiness_check( $deny . ' zero metadata/user/probe reads', array( $GLOBALS['readiness_test']['meta_calls'], $GLOBALS['readiness_test']['user_reads'], $GLOBALS['readiness_test']['probes'] ), array( array(), 0, array() ) );
}
readiness_reset(); $html = readiness_render( $admin, $render_method );
readiness_check( 'readiness useful heading not connected badge', strpos( $html, 'Local Ability connection prerequisites' ) !== false && strpos( $html, 'Connected' ) === false, true );
foreach ( array( 'declarations only', 'permission certification', 'authentication remain untested', 'credentials into AI prompts', 'existing credential controls', 'disconnecting the external client', 'not Ability-scoped' ) as $copy ) { readiness_check( 'fixed truthful guidance ' . $copy, strpos( $html, $copy ) !== false, true ); }
readiness_reset(); $GLOBALS['readiness_case']['hostile_translation'] = true; $html = readiness_render( $admin, $render_method );
readiness_check( 'fixed translated output escaped', strpos( $html, '<script>' ) === false && strpos( $html, '&lt;script&gt;') !== false, true );
$projection = array();
foreach ( array( 'empty', 'configured', 'bad_nonce', 'good_nonce', 'array_input' ) as $case ) {
	readiness_reset(); $GLOBALS['readiness_case']['choices'] = 'empty' !== $case;
	if ( in_array( $case, array( 'bad_nonce', 'good_nonce', 'array_input' ), true ) ) { $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array( '_wpnonce' => 'bad_nonce' === $case ? 'bad' : 'synthetic', 'gateway' => 'cod', 'subtotal' => '100', 'shipping' => '10', 'tax' => '5', 'shipping_tax' => '1' ); }
	if ( 'array_input' === $case ) { $_POST['subtotal'] = array( 'bad' ); }
	ob_start(); $admin->render_page(); $html = ob_get_clean();
	readiness_check( $case . ' actual admin connects prerequisites', strpos( $html, 'Local Ability connection prerequisites' ) !== false, true );
	readiness_check( $case . ' old form count preserved', substr_count( $html, '<form ' ), 'empty' === $case ? 0 : 1 );
	readiness_check( $case . ' nonce/evaluation calls preserved', count( $GLOBALS['readiness_test']['service_calls'] ), 'empty' === $case ? 0 : ( 'good_nonce' === $case ? 2 : 1 ) );
	$projection[ $case ] = readiness_project( $html );
}
readiness_check( 'no Ability execution or permission callbacks', array( $GLOBALS['readiness_test']['execution'], $GLOBALS['readiness_test']['permissions'] ), array( 0, 0 ) );
readiness_check( 'no credentials, transport or writes', array( $GLOBALS['readiness_test']['credential_reads'], $GLOBALS['readiness_test']['http'], $GLOBALS['readiness_test']['mutations'] ), array( 0, 0, 0 ) );
$checks = $GLOBALS['readiness_test']['checks']; $failed = count( array_filter( $checks, static function ( $c ) { return ! $c['pass']; } ) );
echo json_encode( array( 'mode' => $mode, 'tier' => basename( $root ), 'assertions' => count( $checks ), 'failed' => $failed, 'checks' => $checks, 'imports' => $GLOBALS['readiness_test']['imports'], 'old_ui_projection' => $projection, 'limits' => 'Actual public WP helpers, registry and Ability classes; synthetic WP hooks/options/users/authorization/translations, adversarial Ability subclass, existing explanation-service double. No native lifecycle, credential, auth/connector or execution proof.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );
