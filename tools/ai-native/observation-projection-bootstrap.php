<?php
/**
 * Configured-R2 projection test bootstrap. Never a web endpoint or client.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.WP.AlternativeFunctions.parse_url_parse_url -- WordPress has not loaded; only parse the local fixture environment.
if ( 'cli' !== PHP_SAPI || 'yes-loopback' !== getenv( 'WCJ_AI_NATIVE_TESTS' ) ||
	'127.0.0.1' !== parse_url( getenv( 'AI_ORIGIN' ), PHP_URL_HOST ) ) {
	exit( 2 ); }
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Bootstrap native WordPress context.
define( 'WP_ADMIN', true );
$_SERVER['HTTP_HOST']       = parse_url( getenv( 'AI_ORIGIN' ), PHP_URL_HOST ) . ':' . parse_url( getenv( 'AI_ORIGIN' ), PHP_URL_PORT );
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
require '/var/www/html/wp-load.php';
// phpcs:enable WordPress.WP.AlternativeFunctions.parse_url_parse_url
if ( ! defined( 'WCJ_AI_PREVIEW' ) || true !== WCJ_AI_PREVIEW || 'local' !== wp_get_environment_type() || 'synthetic_ai' !== DB_NAME || ! WP_HTTP_BLOCK_EXTERNAL || ! DISABLE_WP_CRON || home_url() !== getenv( 'AI_ORIGIN' ) ) {
	exit( 3 ); }
$wcj_ai_owner = get_user_by( 'login', 'store_owner' );
if ( ! $wcj_ai_owner || ! user_can( $wcj_ai_owner, 'manage_woocommerce' ) ) {
	exit( 4 ); }
wp_set_current_user( $wcj_ai_owner->ID );
$wcj_ai_cases = array();
/**
 * Record a native fixture assertion.
 *
 * @param string $wcj_ai_name Finite test name.
 * @param bool   $wcj_ai_condition Expected condition.
 * @throws RuntimeException When the assertion fails.
 */
function wcj_ai_check( $wcj_ai_name, $wcj_ai_condition ) {
	global $wcj_ai_cases;
	$wcj_ai_cases[] = array(
		'case' => $wcj_ai_name,
		'pass' => (bool) $wcj_ai_condition,
	);
	if ( ! $wcj_ai_condition ) {
		throw new RuntimeException( esc_html( $wcj_ai_name ) ); }
}
