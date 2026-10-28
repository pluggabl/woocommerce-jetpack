<?php
/**
 * Announcement regression tests for an isolated synthetic WordPress database.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'DB_NAME' ) || 0 !== strpos( DB_NAME, 'qa851_' ) ) {
	throw new RuntimeException( 'This suite requires an isolated qa851_ database and WP-CLI.' );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$wcj_qa_root = defined( 'WCJ_FREE_PLUGIN_PATH' ) ? WCJ_FREE_PLUGIN_PATH : WCJ_PLUGIN_PATH;
require_once $wcj_qa_root . '/includes/admin/class-wcj-admin-announcement.php';
WCJ_Admin_Announcement::init();
global $wcj_qa_cases;
$GLOBALS['admin_page_hooks']['wcj-dashboard'] = 'booster'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Synthetic CLI screen fixture only.
$wcj_qa_cases                                 = array();
$wcj_qa_users                                 = array();
$wcj_qa_requests                              = array();
$wcj_qa_cache                                 = WCJ_Admin_Announcement::CACHE_KEY;
$wcj_qa_valid                                 = array(
	'id'         => 'qa-851-one',
	'message'    => 'QA announcement & store news',
	'url'        => 'https://booster.io/',
	'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 86400 ),
);
$wcj_qa_body                                  = wp_json_encode( array( 'announcement' => $wcj_qa_valid ) );
$wcj_qa_response_code                         = 200;
$wcj_qa_fail_network                          = false;
$wcj_qa_filter                                = function ( $wcj_qa_pre, $wcj_qa_args, $wcj_qa_url ) use ( &$wcj_qa_requests, &$wcj_qa_body, &$wcj_qa_response_code, &$wcj_qa_fail_network ) {
	$wcj_qa_requests[] = array(
		'url'  => $wcj_qa_url,
		'args' => $wcj_qa_args,
	);
	if ( $wcj_qa_fail_network ) {
		return new WP_Error( 'qa-timeout', 'Synthetic failure' );
	}
	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => $wcj_qa_body,
		'response' => array(
			'code'    => $wcj_qa_response_code,
			'message' => 'Synthetic',
		),
		'cookies'  => array(),
		'filename' => null,
	);
};
add_filter( 'pre_http_request', $wcj_qa_filter, 999, 3 );
/**
 * Record a strict assertion in the CLI result.
 *
 * @param string $wcj_qa_id Case ID.
 * @param mixed  $wcj_qa_actual Actual value.
 * @param mixed  $wcj_qa_expected Expected value.
 * @throws RuntimeException When an assertion fails.
 */
function wcj_qa_assert( $wcj_qa_id, $wcj_qa_actual, $wcj_qa_expected = true ) {
	global $wcj_qa_cases;
	$wcj_qa_pass    = $wcj_qa_actual === $wcj_qa_expected;
	$wcj_qa_cases[] = array(
		'id'       => $wcj_qa_id,
		'expected' => $wcj_qa_expected,
		'actual'   => $wcj_qa_actual,
		'status'   => $wcj_qa_pass ? 'PASS' : 'FAIL',
	);
	if ( ! $wcj_qa_pass ) {
		throw new RuntimeException( 'Assertion failed: ' . esc_html( $wcj_qa_id ) );
	}
}
/**
 * Capture the announcement markup.
 *
 * @return string Rendered HTML.
 */
function wcj_qa_render() {
	ob_start();
	WCJ_Admin_Announcement::render();
	return ob_get_clean();
}
/**
 * Select a synthetic WordPress screen.
 *
 * @param string      $wcj_qa_page Page slug.
 * @param string|null $wcj_qa_screen Explicit screen ID.
 */
function wcj_qa_page( $wcj_qa_page = 'wcj-dashboard', $wcj_qa_screen = null ) {
	$_GET = array( 'page' => $wcj_qa_page );
	set_current_screen( $wcj_qa_screen ?? get_plugin_page_hookname( $wcj_qa_page, 'wcj-dashboard' ) );
}
$wcj_qa_original = get_current_user_id();
try {
	$wcj_qa_role_users = array();
	foreach ( array( 'administrator', 'shop_manager', 'editor', 'author', 'contributor', 'subscriber', 'customer' ) as $wcj_qa_role ) {
		$wcj_qa_id = wp_insert_user(
			array(
				'user_login' => 'qa851_' . $wcj_qa_role . '_' . wp_generate_password( 6, false ),
				'user_pass'  => wp_generate_password( 32, true ),
				'role'       => $wcj_qa_role,
			)
		);
		if ( is_wp_error( $wcj_qa_id ) ) {
			throw new RuntimeException( 'Synthetic user creation failed' );
		}
		$wcj_qa_users[]                    = $wcj_qa_id;
		$wcj_qa_role_users[ $wcj_qa_role ] = $wcj_qa_id;
	}
	$wcj_qa_second  = wp_insert_user(
		array(
			'user_login' => 'qa851_second_' . wp_generate_password( 6, false ),
			'user_pass'  => wp_generate_password( 32, true ),
			'role'       => 'administrator',
		)
	);
	$wcj_qa_users[] = $wcj_qa_second;
	foreach ( array_merge( array( 'guest' => 0 ), $wcj_qa_role_users ) as $wcj_qa_role => $wcj_qa_id ) {
		wp_set_current_user( $wcj_qa_id );
		wcj_qa_page();
		delete_transient( $wcj_qa_cache );
		$wcj_qa_requests = array();
		$wcj_qa_html     = wcj_qa_render();
		$wcj_qa_allowed  = current_user_can( 'manage_woocommerce' );
		wcj_qa_assert( 'role-visible-' . $wcj_qa_role, strpos( $wcj_qa_html, 'wcj-admin-announcement' ) !== false, $wcj_qa_allowed );
		wcj_qa_assert( 'role-fetch-' . $wcj_qa_role, count( $wcj_qa_requests ), $wcj_qa_allowed ? 1 : 0 );
		set_transient( $wcj_qa_cache, $wcj_qa_valid, DAY_IN_SECONDS );
		$wcj_qa_nonce = wp_create_nonce( 'wcj-dismiss-announcement-' . $wcj_qa_valid['id'] );
		wcj_qa_assert( 'role-direct-sink-' . $wcj_qa_role, WCJ_Admin_Announcement::dismiss( $wcj_qa_valid['id'], $wcj_qa_nonce ), $wcj_qa_allowed );
	}
	wp_set_current_user( $wcj_qa_second );
	wcj_qa_page();
	delete_transient( $wcj_qa_cache );
	$wcj_qa_requests = array();
	wcj_qa_assert( 'valid-feed-visible', strpos( wcj_qa_render(), 'QA announcement &amp; store news' ) !== false );
	wcj_qa_assert( 'inline-notice-placement', strpos( wcj_qa_render(), 'notice-info inline wcj-admin-announcement' ) !== false );
	wcj_qa_assert( 'fetch-fixed-url', $wcj_qa_requests[0]['url'], WCJ_Admin_Announcement::ENDPOINT );
	wcj_qa_assert( 'fetch-timeout', $wcj_qa_requests[0]['args']['timeout'], 2 );
	wcj_qa_assert( 'fetch-no-redirect', $wcj_qa_requests[0]['args']['redirection'], 0 );
	wcj_qa_assert( 'fetch-size-bound', $wcj_qa_requests[0]['args']['limit_response_size'], 4097 );
	wcj_qa_assert( 'fetch-no-site-user-agent', $wcj_qa_requests[0]['args']['user-agent'], 'Booster-Announcement-Reader/1.0' );
	wcj_qa_assert( 'fetch-no-cookies', $wcj_qa_requests[0]['args']['cookies'], array() );
	wcj_qa_render();
	wcj_qa_assert( 'daily-cache-one-request', count( $wcj_qa_requests ), 1 );
	$wcj_qa_remaining = (int) get_option( '_transient_timeout_' . $wcj_qa_cache ) - time();
	wcj_qa_assert( 'daily-cache-ttl', $wcj_qa_remaining >= 86395 && $wcj_qa_remaining <= 86400 );
	foreach ( array(
		array(
			'page'   => 'wcj-dashboard',
			'screen' => 'edit-post',
		),
		array(
			'page'   => 'unrelated',
			'screen' => 'toplevel_page_unrelated',
		),
		array(
			'page'   => 'wc-settings',
			'screen' => 'woocommerce_page_wc-settings',
			'tab'    => 'checkout',
		),
	) as $wcj_qa_i => $wcj_qa_page ) {
		$_GET = array( 'page' => $wcj_qa_page['page'] );
		if ( isset( $wcj_qa_page['tab'] ) ) {
			$_GET['tab'] = $wcj_qa_page['tab'];
		}set_current_screen( $wcj_qa_page['screen'] );
		delete_transient( $wcj_qa_cache );
		$wcj_qa_requests = array();
		wcj_qa_assert( 'unrelated-page-' . $wcj_qa_i, wcj_qa_render(), '' );
		wcj_qa_assert( 'unrelated-no-network-' . $wcj_qa_i, count( $wcj_qa_requests ), 0 );
	}
	$_GET = array(
		'page' => 'wc-settings',
		'tab'  => 'jetpack',
	);
	set_current_screen( 'woocommerce_page_wc-settings' );
	delete_transient( $wcj_qa_cache );
	wcj_qa_assert( 'booster-wc-tab-visible', strpos( wcj_qa_render(), 'wcj-admin-announcement' ) !== false );
	foreach ( array( 'wcj-dashboard', 'wcj-plugins', 'wcj-general-settings', 'wcj-getting-started', 'wcj-tools', 'wcj-license' ) as $wcj_qa_page ) {
		wcj_qa_page( $wcj_qa_page );
		wcj_qa_assert( 'owned-page-' . $wcj_qa_page, strpos( wcj_qa_render(), 'wcj-admin-announcement' ) !== false );}
	wcj_qa_page();
	set_transient( $wcj_qa_cache, $wcj_qa_valid, DAY_IN_SECONDS );
	foreach ( array( null, '', array(), str_repeat( 'x', 33 ), 'wrong' ) as $wcj_qa_i => $wcj_qa_nonce ) {
		wcj_qa_assert( 'invalid-nonce-' . $wcj_qa_i, WCJ_Admin_Announcement::dismiss( $wcj_qa_valid['id'], $wcj_qa_nonce ), false );
	}
	$wcj_qa_nonce = wp_create_nonce( 'wcj-dismiss-announcement-' . $wcj_qa_valid['id'] );
	foreach ( array( null, '', array(), str_repeat( 'a', 81 ), '../id', 'QA-BAD', 'inactive-id' ) as $wcj_qa_i => $wcj_qa_id ) {
		wcj_qa_assert( 'invalid-id-' . $wcj_qa_i, WCJ_Admin_Announcement::dismiss( $wcj_qa_id, $wcj_qa_nonce ), false );
	}
	wp_set_current_user( $wcj_qa_role_users['administrator'] );
	$wcj_qa_foreign = wp_create_nonce( 'wcj-dismiss-announcement-' . $wcj_qa_valid['id'] );
	wp_set_current_user( $wcj_qa_second );
	wcj_qa_assert( 'foreign-user-nonce', WCJ_Admin_Announcement::dismiss( $wcj_qa_valid['id'], $wcj_qa_foreign ), false );
	$wcj_qa_nonce = wp_create_nonce( 'wcj-dismiss-announcement-' . $wcj_qa_valid['id'] );
	wcj_qa_assert( 'own-dismissal', WCJ_Admin_Announcement::dismiss( $wcj_qa_valid['id'], $wcj_qa_nonce ) );
	wcj_qa_assert( 'dismissal-reload', wcj_qa_render(), '' );
	$wcj_qa_edited            = $wcj_qa_valid;
	$wcj_qa_edited['message'] = 'Edited message';
	set_transient( $wcj_qa_cache, $wcj_qa_edited, DAY_IN_SECONDS );
	wcj_qa_assert( 'same-id-edit-stays-dismissed', wcj_qa_render(), '' );
	wp_set_current_user( $wcj_qa_role_users['shop_manager'] );
	$wcj_qa_new       = $wcj_qa_valid;
	$wcj_qa_new['id'] = 'qa-851-new';
	set_transient( $wcj_qa_cache, $wcj_qa_new, DAY_IN_SECONDS );
	wcj_qa_assert( 'new-id-not-dismissed', strpos( wcj_qa_render(), 'wcj-admin-announcement' ) !== false );
	wp_set_current_user( $wcj_qa_second );
	wcj_qa_assert( 'other-id-does-not-reset-old', WCJ_Admin_Announcement::dismiss( $wcj_qa_valid['id'], $wcj_qa_nonce ), false );
	$wcj_qa_expired               = $wcj_qa_valid;
	$wcj_qa_expired['expires_at'] = gmdate( 'Y-m-d\TH:i:s\Z', time() - 1 );
	set_transient( $wcj_qa_cache, $wcj_qa_expired, DAY_IN_SECONDS );
	wcj_qa_assert( 'expired-cache-hidden', wcj_qa_render(), '' );
	$wcj_qa_bad = array();
	foreach ( array(
		'id'         => array( null, array(), str_repeat( 'x', 81 ), '../bad' ),
		'message'    => array( null, array(), str_repeat( 'x', 501 ), '<script>alert(1)</script>', "bad\ntext", '   ' ),
		'url'        => array( null, array(), str_repeat( 'x', 201 ), 'http://booster.io/', 'https://booster.io.evil.invalid/', 'https://booster.io@evil.invalid/', 'https://booster.io/?utm_source=test', 'https://booster.io:443/', 'https://booster.io/%2f', 'javascript:alert(1)' ),
		'expires_at' => array( null, array(), '2099-02-31T00:00:00Z', gmdate( 'Y-m-d\TH:i:s\Z', time() - 1 ) ),
	) as $wcj_qa_field => $wcj_qa_values ) {
		foreach ( $wcj_qa_values as $wcj_qa_value ) {
			$wcj_qa_x                  = $wcj_qa_valid;
			$wcj_qa_x[ $wcj_qa_field ] = $wcj_qa_value;
			$wcj_qa_bad[]              = $wcj_qa_x;}
	}
	$wcj_qa_x         = $wcj_qa_valid;
	$wcj_qa_x['code'] = 'must not execute';
	$wcj_qa_bad[]     = $wcj_qa_x;
	foreach ( $wcj_qa_bad as $wcj_qa_i => $wcj_qa_item ) {
		$wcj_qa_body = wp_json_encode( array( 'announcement' => $wcj_qa_item ) );
		delete_transient( $wcj_qa_cache );
		wcj_qa_assert( 'malformed-field-' . $wcj_qa_i, wcj_qa_render(), '' );}
	foreach ( array(
		'',
		'{bad',
		'[]',
		'{"announcement":null}',
		'{"announcement":{}}',
		str_repeat( ' ', 4097 ),
		wp_json_encode(
			array(
				'announcement' => $wcj_qa_valid,
				'unexpected'   => true,
			)
		),
	) as $wcj_qa_i => $wcj_qa_payload ) {
		$wcj_qa_body = $wcj_qa_payload;
		delete_transient( $wcj_qa_cache );
		wcj_qa_assert( 'malformed-envelope-' . $wcj_qa_i, wcj_qa_render(), '' );}
	$wcj_qa_body = wp_json_encode( array( 'announcement' => $wcj_qa_new ) );
	foreach ( array( 301, 404, 500 ) as $wcj_qa_code ) {
		$wcj_qa_response_code = $wcj_qa_code;
		delete_transient( $wcj_qa_cache );
		wcj_qa_assert( 'http-' . $wcj_qa_code, wcj_qa_render(), '' );
	}$wcj_qa_response_code = 200;
	$wcj_qa_fail_network   = true;
	delete_transient( $wcj_qa_cache );
	$wcj_qa_requests = array();
	wcj_qa_assert( 'network-failure-silent', wcj_qa_render(), '' );
	wcj_qa_render();
	wcj_qa_assert( 'negative-cache-one-request', count( $wcj_qa_requests ), 1 );
	$wcj_qa_fail_network = false;
	set_transient( $wcj_qa_cache, array(), DAY_IN_SECONDS );
	wcj_qa_assert( 'empty-feed-no-notice', wcj_qa_render(), '' );
	WCJ_Admin_Announcement::init();
	wcj_qa_assert( 'single-render-hook', isset( $GLOBALS['wp_filter']['admin_notices'] ) && false !== has_action( 'admin_notices', array( 'WCJ_Admin_Announcement', 'render' ) ) );
	wcj_qa_assert( 'complete-assertion-ledger', count( $wcj_qa_cases ) >= 100 );
	echo wp_json_encode(
		array(
			'result' => 'PASS',
			'wp'     => get_bloginfo( 'version' ),
			'php'    => PHP_VERSION,
			'wc'     => defined( 'WC_VERSION' ) ? WC_VERSION : null,
			'cases'  => $wcj_qa_cases,
		),
		JSON_PRETTY_PRINT
	);
} catch ( Throwable $wcj_qa_error ) {
	echo wp_json_encode(
		array(
			'result' => 'FAIL',
			'error'  => $wcj_qa_error->getMessage(),
			'cases'  => $wcj_qa_cases,
		),
		JSON_PRETTY_PRINT
	);
	throw $wcj_qa_error;
} finally {
	delete_transient( $wcj_qa_cache );
	remove_filter( 'pre_http_request', $wcj_qa_filter, 999 );
	wp_set_current_user( $wcj_qa_original );
	foreach ( $wcj_qa_users as $wcj_qa_id ) {
		wp_delete_user( $wcj_qa_id );
	}
}
