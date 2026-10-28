<?php
/** Direct-root rejection tests in fresh offline CLI processes. @package Booster_For_WooCommerce */
if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	exit( 2 ); }
define( 'ABSPATH', __DIR__ );
$wcj_guest_root     = $argv[1] ?? '';
$wcj_guest_key_case = $argv[2] ?? 'normal';
$wcj_guest_key      = str_repeat( 'synthetic-key-', 6 );
$wcj_guest_salt     = str_repeat( 'synthetic-salt-', 6 );
if ( 'missing' !== $wcj_guest_key_case ) {
	define( 'AUTH_KEY', 'short' === $wcj_guest_key_case ? 'short' : ( 'default' === $wcj_guest_key_case ? 'put your unique phrase here' : ( 'translated' === $wcj_guest_key_case ? str_repeat( 'translated-default-', 5 ) : $wcj_guest_key ) ) ); }
define( 'AUTH_SALT', 'duplicate' === $wcj_guest_key_case ? $wcj_guest_key : $wcj_guest_salt );
if ( 'other-duplicate' === $wcj_guest_key_case ) {
	define( 'NONCE_SALT', $wcj_guest_key ); }
/** Offline default-domain translation, never a secret replacement. */
function __( $text, $domain ) {
	return 'translated' === $GLOBALS['wcj_guest_key_case'] && 'default' === $domain ? str_repeat( 'translated-default-', 5 ) : $text; }
/** Fixed captured blog. */
function get_current_blog_id() {
	return 1; }
require $wcj_guest_root . '/includes/returns/class-wcj-returns-guest.php';
try {
	$wcj_guest_keys   = WCJ_Returns_Guest::keys();
	$wcj_guest_actual = 'ready'; } catch ( Throwable $wcj_guest_error ) {
	$wcj_guest_actual = $wcj_guest_error->getMessage(); }
	$wcj_guest_expected = 'normal' === $wcj_guest_key_case ? 'ready' : 'auth_unavailable';
	echo json_encode(
		array(
			'case'     => $wcj_guest_key_case,
			'actual'   => $wcj_guest_actual,
			'expected' => $wcj_guest_expected,
			'pass'     => $wcj_guest_actual === $wcj_guest_expected,
		)
	) . PHP_EOL;
	exit( $wcj_guest_actual === $wcj_guest_expected ? 0 : 1 );
