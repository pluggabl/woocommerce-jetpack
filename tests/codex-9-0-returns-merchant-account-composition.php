<?php
/**
 * Composition06 full retained account428 in explicit synthetic admin context.
 * The original account fixture and every literal expectation remain unchanged.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	exit( 2 );
}
/** Explicit synthetic merchant-admin rendering context. */
function is_admin() {
	return true;
}
$wcj_composition_account_root = $argv[1] ?? '';
if ( ! is_file( $wcj_composition_account_root . '/tests/codex-9-0-returns.php' ) ) {
	exit( 2 );
}
require $wcj_composition_account_root . '/tests/codex-9-0-returns.php';
if ( 428 !== $GLOBALS['wcj_returns_test_count'] ) {
	exit( 2 );
}
