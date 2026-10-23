<?php
/**
 * Finite merchant-only overlap regressions on the actual Returns class.
 * Imports exact pinned public WP activation helpers with synthetic option state.
 * No plugin installation, native WordPress, database, HTTP or mail execution.
 *
 * Usage: php -n FILE ROOT PINNED_PLUGIN_PHP [normal|missing-active|missing-network].
 *
 * @package Booster_Tests
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) ) {
	exit( 2 );
}
$wcj_overlap_root = $argv[1] ?? '';
$wcj_overlap_wp   = $argv[2] ?? '';
$wcj_overlap_mode = $argv[3] ?? 'normal';
if ( ! is_file( $wcj_overlap_root . '/includes/class-wcj-returns.php' ) || ! is_file( $wcj_overlap_wp ) || ! in_array( $wcj_overlap_mode, array( 'normal', 'missing-active', 'missing-network' ), true ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['overlap_fixture'] = array( 'checks' => array(), 'reads' => array(), 'mutations' => array(), 'hooks' => array() );

/** Extract one complete public function, not a rewritten helper or expected output. */
function wcj_overlap_import( $path, $name ) {
	$tokens = token_get_all( file_get_contents( $path ) );
	$count  = count( $tokens );
	for ( $i = 0; $i < $count; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] || $name !== $tokens[ $j ][1] ) {
			continue;
		}
		$code    = '';
		$depth   = 0;
		$started = false;
		for ( $k = $i; $k < $count; ++$k ) {
			$token = $tokens[ $k ];
			$code .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) {
				++$depth;
				$started = true;
			} elseif ( '}' === $token ) {
				--$depth;
			}
			if ( $started && 0 === $depth ) {
				$GLOBALS['overlap_fixture']['imports'][ $name ] = hash( 'sha256', $code );
				eval( $code );
				return;
			}
		}
	}
	throw new RuntimeException( 'Pinned public function missing' );
}

/** Record every literal assertion. */
function wcj_overlap_check( $name, $actual, $expected ) {
	$GLOBALS['overlap_fixture']['checks'][] = array( 'name' => $name, 'pass' => $actual === $expected, 'actual' => $actual, 'expected' => $expected );
}
/** Reset offline primitives only. */
function wcj_overlap_reset() {
	$GLOBALS['overlap_fixture']['context'] = array( 'admin' => true, 'logged_in' => true, 'capable' => true, 'rest' => false, 'multisite' => false, 'throw_active' => false, 'hostile_translation' => false );
	$GLOBALS['overlap_fixture']['options'] = array( 'wcj_returns_enabled' => 'yes', 'wcj_modules_by_user_roles_enabled' => 'no', 'active_plugins' => array() );
	$GLOBALS['overlap_fixture']['network'] = array();
	$GLOBALS['overlap_fixture']['roles']   = array( 'administrator' );
	$GLOBALS['overlap_fixture']['reads']   = array();
	$GLOBALS['overlap_fixture']['business'] = array( 'orders' => 'unchanged', 'notifications' => 'unchanged', 'settings' => 'unchanged' );
	unset( $GLOBALS['wcj_modules_by_user_roles_data'] );
	$_GET  = array();
	$_POST = array();
}
function is_admin() { return $GLOBALS['overlap_fixture']['context']['admin']; }
function is_user_logged_in() { return $GLOBALS['overlap_fixture']['context']['logged_in']; }
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['overlap_fixture']['context']['capable']; }
function wcj_is_rest() { return $GLOBALS['overlap_fixture']['context']['rest']; }
function wp_get_current_user() { return (object) array( 'roles' => $GLOBALS['overlap_fixture']['roles'] ); }
function is_multisite() { return $GLOBALS['overlap_fixture']['context']['multisite']; }
function get_option( $key, $default = false ) {
	$GLOBALS['overlap_fixture']['reads'][] = $key;
	if ( 'active_plugins' === $key && $GLOBALS['overlap_fixture']['context']['throw_active'] ) { throw new RuntimeException( 'private-exception-detail-must-not-render' ); }
	return $GLOBALS['overlap_fixture']['options'][ $key ] ?? $default;
}
function get_site_option( $key, $default = false ) {
	$GLOBALS['overlap_fixture']['reads'][] = $key;
	return 'active_sitewide_plugins' === $key ? $GLOBALS['overlap_fixture']['network'] : $default;
}
function wcj_get_option( $key, $default = '' ) { return get_option( $key, $default ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function apply_filters( $hook, $value ) { return $value; }
function __( $text, $domain = '' ) { return $GLOBALS['overlap_fixture']['context']['hostile_translation'] ? '<script>alert("translation")</script>' : $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = '' ) { return esc_html( __( $text, $domain ) ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function admin_url( $path = '' ) { return 'https://example.invalid/wp-admin/' . $path; }
function wp_nonce_field( $action ) { echo '<input type="hidden" name="_wpnonce" value="synthetic-' . esc_html( $action ) . '">'; }
function add_action( $name, $callback, $priority = 10 ) { $GLOBALS['overlap_fixture']['hooks'][] = $name; }
function wp_die( $message ) { throw new RuntimeException( 'denied' ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error {
	private $code;
	public function __construct( $code, $message = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
/** Parent bootstrap double delegating to the actual Booster module gate below. */
class WCJ_Module {
	public $id;
	public $short_desc;
	public $desc;
	public $desc_pro;
	public $type = 'module';
	public $parent_id;
	public function __construct() {}
	public function is_enabled() { return wcj_is_module_enabled( 'module' === $this->type ? $this->id : $this->parent_id ); }
}
wcj_overlap_reset();
if ( 'missing-active' !== $wcj_overlap_mode ) {
	wcj_overlap_import( $wcj_overlap_wp, 'is_plugin_active' );
}
if ( 'missing-network' !== $wcj_overlap_mode ) {
	wcj_overlap_import( $wcj_overlap_wp, 'is_plugin_active_for_network' );
}
require $wcj_overlap_root . '/includes/functions/wcj-functions-booster-core.php';
$GLOBALS['overlap_fixture']['options']['wcj_returns_enabled'] = 'no';
$GLOBALS['wpdb'] = (object) array( 'prefix' => '!' ); // Actual service returns unavailable before any SQL is possible.
$wcj_overlap_module = require $wcj_overlap_root . '/includes/class-wcj-returns.php';
$wcj_overlap_method = method_exists( $wcj_overlap_module, 'overlap_warning' ) ? new ReflectionMethod( $wcj_overlap_module, 'overlap_warning' ) : null;

/** Invoke the real private renderer; absent method is the literal pre-implementation red. */
function wcj_overlap_render( $module, $method ) {
	ob_start();
	if ( $method ) { $method->invoke( $module ); }
	return ob_get_clean();
}
function wcj_overlap_lookup_count() {
	return count( array_filter( $GLOBALS['overlap_fixture']['reads'], static function ( $name ) { return in_array( $name, array( 'active_plugins', 'active_sitewide_plugins' ), true ); } ) );
}
$wcj_overlap_basename = 'woo-refund-and-exchange-lite/woocommerce-refund-and-exchange-lite.php';
$wcj_overlap_warning = '<div class="notice notice-warning inline"><p>Return Refund and Exchange for WooCommerce is active on this site. Review overlapping customer return requests, notifications and refund workflows before using both plugins. This best-effort check does not confirm an enabled workflow, an actual conflict or tested interoperability, and does not cover every extension. Booster does not disable, import, migrate, refund or configure the other plugin.</p></div>';
$wcj_overlap_unknown = '<div class="notice notice-info inline"><p>Third-party return and refund overlap checking is unavailable. Review any other return or refund extensions before using Booster Return Requests. No absence or interoperability conclusion is available.</p></div>';

if ( 'normal' === $wcj_overlap_mode ) {
	foreach ( array( 'site', 'network', 'both' ) as $wcj_overlap_activation ) {
		wcj_overlap_reset();
		$GLOBALS['overlap_fixture']['context']['multisite'] = 'site' !== $wcj_overlap_activation;
		$GLOBALS['overlap_fixture']['options']['active_plugins'] = 'network' === $wcj_overlap_activation ? array() : array( $wcj_overlap_basename );
		$GLOBALS['overlap_fixture']['network'] = array( $wcj_overlap_basename => 123 );
		$wcj_overlap_snapshot = serialize( array( $GLOBALS['overlap_fixture']['options'], $GLOBALS['overlap_fixture']['network'], $GLOBALS['overlap_fixture']['business'], $_POST ) );
		wcj_overlap_check( $wcj_overlap_activation . ' exact warning', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), $wcj_overlap_warning );
		wcj_overlap_check( $wcj_overlap_activation . ' bounded lookups', wcj_overlap_lookup_count(), 'network' === $wcj_overlap_activation ? 2 : 1 );
		wcj_overlap_check( $wcj_overlap_activation . ' no business mutation', serialize( array( $GLOBALS['overlap_fixture']['options'], $GLOBALS['overlap_fixture']['network'], $GLOBALS['overlap_fixture']['business'], $_POST ) ), $wcj_overlap_snapshot );
	}
	foreach ( array( array(), array( 'unrelated/plugin.php' ), array( strtoupper( $wcj_overlap_basename ) ), array( $wcj_overlap_basename . '.bak' ), array( 'prefix/' . $wcj_overlap_basename ) ) as $wcj_overlap_index => $wcj_overlap_plugins ) {
		wcj_overlap_reset();
		$GLOBALS['overlap_fixture']['options']['active_plugins'] = $wcj_overlap_plugins;
		wcj_overlap_check( 'inactive/exact-basename negative ' . $wcj_overlap_index, wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), '' );
		wcj_overlap_check( 'inactive single-site no network option read ' . $wcj_overlap_index, wcj_overlap_lookup_count(), 1 );
	}
	wcj_overlap_reset();
	$GLOBALS['overlap_fixture']['context']['multisite'] = true;
	$GLOBALS['overlap_fixture']['network'] = array( strtoupper( $wcj_overlap_basename ) => 123, $wcj_overlap_basename . '.bak' => 124 );
	wcj_overlap_check( 'network keys exact and case-sensitive', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), '' );
	wcj_overlap_check( 'network negative bounded two reads', wcj_overlap_lookup_count(), 2 );
	wcj_overlap_reset();
	$GLOBALS['overlap_fixture']['context']['throw_active'] = true;
	wcj_overlap_check( 'activation read error closed text only', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), $wcj_overlap_unknown );
	wcj_overlap_check( 'unknown activation read attempted once', wcj_overlap_lookup_count(), 1 );
	wcj_overlap_reset();
	$GLOBALS['overlap_fixture']['options']['active_plugins'] = array( $wcj_overlap_basename );
	$GLOBALS['overlap_fixture']['context']['hostile_translation'] = true;
	$wcj_overlap_html = wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method );
	wcj_overlap_check( 'fixed translated message HTML escaped', $wcj_overlap_html, '<div class="notice notice-warning inline"><p>&lt;script&gt;alert(&quot;translation&quot;)&lt;/script&gt;</p></div>' );
} else {
	wcj_overlap_reset();
	$GLOBALS['overlap_fixture']['options']['active_plugins'] = array( $wcj_overlap_basename );
	wcj_overlap_check( $wcj_overlap_mode . ' explicit unavailable not no-overlap', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), $wcj_overlap_unknown );
	wcj_overlap_check( $wcj_overlap_mode . ' no partial activation lookup', wcj_overlap_lookup_count(), 0 );
}

foreach ( array( 'frontend', 'logged-out', 'no-capability', 'rest', 'disabled', 'role-excluded', 'primed-other-role' ) as $wcj_overlap_denial ) {
	wcj_overlap_reset();
	$GLOBALS['overlap_fixture']['options']['active_plugins'] = array( $wcj_overlap_basename );
	if ( 'frontend' === $wcj_overlap_denial ) { $GLOBALS['overlap_fixture']['context']['admin'] = false; }
	if ( 'logged-out' === $wcj_overlap_denial ) { $GLOBALS['overlap_fixture']['context']['logged_in'] = false; }
	if ( 'no-capability' === $wcj_overlap_denial ) { $GLOBALS['overlap_fixture']['context']['capable'] = false; }
	if ( 'rest' === $wcj_overlap_denial ) { $GLOBALS['overlap_fixture']['context']['rest'] = true; }
	if ( 'disabled' === $wcj_overlap_denial ) { $GLOBALS['overlap_fixture']['options']['wcj_returns_enabled'] = 'no'; }
	if ( in_array( $wcj_overlap_denial, array( 'role-excluded', 'primed-other-role' ), true ) ) {
		$GLOBALS['overlap_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes';
		$GLOBALS['overlap_fixture']['options']['wcj_modules_by_user_roles_excl_administrator'] = array( 'returns' );
	}
	if ( 'primed-other-role' === $wcj_overlap_denial ) { $GLOBALS['wcj_modules_by_user_roles_data'] = array( 'role' => 'shop_manager', 'modules_incl' => '', 'modules_excl' => '' ); }
	wcj_overlap_check( $wcj_overlap_denial . ' warning suppressed', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), '' );
	wcj_overlap_check( $wcj_overlap_denial . ' zero activation reads', wcj_overlap_lookup_count(), 0 );
	if ( 'primed-other-role' === $wcj_overlap_denial ) { wcj_overlap_check( 'primed role cache not mutated', $GLOBALS['wcj_modules_by_user_roles_data']['role'], 'shop_manager' ); }
}
wcj_overlap_reset();
$GLOBALS['overlap_fixture']['options']['wcj_modules_by_user_roles_enabled'] = 'yes';
$GLOBALS['overlap_fixture']['options']['active_plugins'] = array( $wcj_overlap_basename );
$GLOBALS['wcj_modules_by_user_roles_data'] = array( 'role' => 'administrator', 'modules_incl' => '', 'modules_excl' => '' );
wcj_overlap_check( 'same primed allowed role', wcj_overlap_render( $wcj_overlap_module, $wcj_overlap_method ), 'normal' === $wcj_overlap_mode ? $wcj_overlap_warning : $wcj_overlap_unknown );

// Actual admin/service error branch retains the complete old storage form.
wcj_overlap_reset();
ob_start();
$wcj_overlap_module->admin();
$wcj_overlap_admin = ob_get_clean();
wcj_overlap_check( 'retained admin unavailable form exact', $wcj_overlap_admin, '<div class="wrap"><h1>Return Requests</h1><p>Approval records a decision only. Refunds, restocking and payments remain separate merchant actions. Guest access requires a separately enabled, compatible HTTPS verification page and private auth storage.</p>' . ( 'normal' === $wcj_overlap_mode ? '' : $wcj_overlap_unknown ) . '<p>Private storage must be initialized and compatible with this store before requests can be used.</p><form method="post" action="https://example.invalid/wp-admin/admin-post.php"><input type="hidden" name="action" value="wcj_returns_storage"><input type="hidden" name="_wpnonce" value="synthetic-wcj_returns_storage"><button class="button" type="submit">Initialize private return storage</button></form></div>' );
wcj_overlap_reset();
$GLOBALS['overlap_fixture']['options']['active_plugins'] = array( $wcj_overlap_basename );
ob_start();
$wcj_overlap_module->admin();
$wcj_overlap_admin = ob_get_clean();
wcj_overlap_check( 'actual merchant admin connects warning', strpos( $wcj_overlap_admin, 'normal' === $wcj_overlap_mode ? $wcj_overlap_warning : $wcj_overlap_unknown ) !== false, true );
wcj_overlap_check( 'warning occurs before unchanged storage action', false !== strpos( $wcj_overlap_admin, 'notice notice-' ) && strpos( $wcj_overlap_admin, 'notice notice-' ) < strpos( $wcj_overlap_admin, 'Initialize private return storage' ), true );
wcj_overlap_check( 'notification/payment actions not added', substr_count( $wcj_overlap_admin, '<form' ), 1 );
wcj_overlap_reset();
$GLOBALS['overlap_fixture']['context']['capable'] = false;
$wcj_overlap_error = '';
try { $wcj_overlap_module->admin(); } catch ( RuntimeException $wcj_overlap_exception ) { $wcj_overlap_error = $wcj_overlap_exception->getMessage(); }
wcj_overlap_check( 'retained admin denial', $wcj_overlap_error, 'denied' );
wcj_overlap_check( 'admin denial no activation or option reads', $GLOBALS['overlap_fixture']['reads'], array() );
$wcj_overlap_checks = $GLOBALS['overlap_fixture']['checks'];
$wcj_overlap_failed = count( array_filter( $wcj_overlap_checks, static function ( $check ) { return ! $check['pass']; } ) );
echo json_encode( array( 'mode' => $wcj_overlap_mode, 'tier' => basename( $wcj_overlap_root ), 'assertions' => count( $wcj_overlap_checks ), 'failed' => $wcj_overlap_failed, 'imports' => $GLOBALS['overlap_fixture']['imports'], 'checks' => $wcj_overlap_checks, 'limits' => 'Synthetic activation/permissions/translation primitives; actual Returns method/public WP helper. No installed third-party/native UI/interoperability proof.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $wcj_overlap_failed ? 1 : 0 );
