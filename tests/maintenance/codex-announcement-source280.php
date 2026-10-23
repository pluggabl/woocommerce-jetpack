<?php
/**
 * Offline source-bound announcement integration checks.
 * Synthetic WordPress primitives only; never bootstrap WordPress or use a database.
 */
if ( 'cli' !== PHP_SAPI || 4 !== $argc ) {
    throw new RuntimeException( 'CLI source fixture arguments required.' );
}
$root = $argv[1];
$tier = $argv[2];
$source = $root . '/' . $tier . '/includes/admin/class-wcj-admin-announcement.php';
if ( ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) || ! hash_equals( $argv[3], hash_file( 'sha256', $source ) ) ) {
    throw new RuntimeException( 'Source binding mismatch.' );
}
define( 'ABSPATH', '/' );
define( 'DAY_IN_SECONDS', 86400 );
$GLOBALS['qa279'] = array( 'allowed' => true, 'screen' => 'booster_page_wcj-dashboard', 'cache' => false, 'requests' => array(), 'writes' => array(), 'meta' => array(), 'user' => 7, 'hooks' => array(), 'body' => '', 'failure' => false );
$_GET = array( 'page' => 'wcj-dashboard' );
class WP_Error {}
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['qa279']['allowed']; }
function get_current_screen() { return (object) array( 'id' => $GLOBALS['qa279']['screen'] ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $s ) ); }
function wp_unslash( $s ) { return $s; }
function get_plugin_page_hookname( $page, $parent ) { return 'booster_page_' . $page; }
function add_action( $hook, $callback ) { $GLOBALS['qa279']['hooks'][] = array( $hook, $callback ); }
function get_transient( $key ) { return $GLOBALS['qa279']['cache']; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['qa279']['cache'] = $value; $GLOBALS['qa279']['ttl'] = $ttl; return true; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function wp_safe_remote_get( $url, $args ) {
    $GLOBALS['qa279']['requests'][] = array( $url, $args );
    return $GLOBALS['qa279']['failure'] ? new WP_Error() : array( 'response' => array( 'code' => 200 ), 'body' => $GLOBALS['qa279']['body'] );
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_remote_retrieve_response_code( $r ) { return $r['response']['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function get_current_user_id() { return $GLOBALS['qa279']['user']; }
function get_user_meta( $id, $key, $single ) { return isset( $GLOBALS['qa279']['meta'][$id][$key] ) ? $GLOBALS['qa279']['meta'][$id][$key] : ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['qa279']['writes'][] = array( $id, $key, $value ); $GLOBALS['qa279']['meta'][$id][$key] = $value; return true; }
function wp_verify_nonce( $nonce, $action ) { return 'nonce-' . get_current_user_id() === $nonce && 'wcj-dismiss-announcement-qa-v9-one' === $action; }
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url( $s ) { return esc_html( $s ); }
function esc_html_e( $s, $domain ) { echo esc_html( $s ); }
function admin_url( $path ) { return 'https://example.invalid/wp-admin/' . $path; }
function wp_nonce_field( $action, $name, $referer ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="synthetic">'; }
$cases = array();
function check279( $id, $actual, $expected ) {
    global $cases;
    $cases[] = array( 'id' => $id, 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected );
    if ( $actual !== $expected ) { throw new RuntimeException( 'Assertion failed: ' . $id ); }
}
function render279() { ob_start(); WCJ_Admin_Announcement::render(); return ob_get_clean(); }
require $source;
WCJ_Admin_Announcement::init();
WCJ_Admin_Announcement::init();
check279( 'idempotent-hooks', count( $GLOBALS['qa279']['hooks'] ), 2 );
$GLOBALS['qa279']['allowed'] = false;
check279( 'denied-render', render279(), '' );
check279( 'denied-no-fetch', count( $GLOBALS['qa279']['requests'] ), 0 );
$GLOBALS['qa279']['allowed'] = true;
$GLOBALS['qa279']['screen'] = 'edit-post';
check279( 'wrong-screen-render', render279(), '' );
check279( 'wrong-screen-no-fetch', count( $GLOBALS['qa279']['requests'] ), 0 );
$GLOBALS['qa279']['screen'] = 'booster_page_wcj-dashboard';
$valid = array( 'id' => 'qa-v9-one', 'message' => 'Useful & private news', 'url' => 'https://booster.io/', 'expires_at' => '2099-01-01T00:00:00Z' );
$GLOBALS['qa279']['cache'] = $valid;
$html = render279();
check279( 'escaped-visible-message', false !== strpos( $html, 'Useful &amp; private news' ), true );
check279( 'inline-notice', false !== strpos( $html, 'notice-info inline wcj-admin-announcement' ), true );
check279( 'cached-no-fetch', count( $GLOBALS['qa279']['requests'] ), 0 );
$GLOBALS['qa279']['cache']['message'] = '<script></script>';
check279( 'invalid-cache-hidden', render279(), '' );
$GLOBALS['qa279']['cache'] = $valid;
$GLOBALS['qa279']['cache']['expires_at'] = '2000-01-01T00:00:00Z';
check279( 'expired-cache-hidden', render279(), '' );
$GLOBALS['qa279']['cache'] = false;
$GLOBALS['qa279']['failure'] = true;
check279( 'failure-hidden', render279(), '' );
render279();
check279( 'failure-negative-cache', count( $GLOBALS['qa279']['requests'] ), 1 );
check279( 'bounded-fixed-request', $GLOBALS['qa279']['requests'][0], array( 'https://booster.io/announcements/booster-admin.json', array( 'timeout' => 2, 'redirection' => 0, 'limit_response_size' => 4097, 'user-agent' => 'Booster-Announcement-Reader/1.0', 'cookies' => array(), 'headers' => array( 'Accept' => 'application/json' ) ) ) );
check279( 'daily-negative-ttl', $GLOBALS['qa279']['ttl'], 86400 );
$GLOBALS['qa279']['cache'] = false;
$GLOBALS['qa279']['failure'] = false;
$GLOBALS['qa279']['body'] = json_encode( array( 'announcement' => $valid ) );
check279( 'valid-remote-body', false !== strpos( render279(), 'Useful &amp; private news' ), true );
check279( 'invalid-nonce-denied', WCJ_Admin_Announcement::dismiss( $valid['id'], 'wrong' ), false );
check279( 'own-dismissal', WCJ_Admin_Announcement::dismiss( $valid['id'], 'nonce-7' ), true );
check279( 'creator-only-fixed-write', $GLOBALS['qa279']['writes'], array( array( 7, 'wcj_announcement_dismissed_' . hash( 'sha256', 'qa-v9-one' ), 'yes' ) ) );
check279( 'dismissal-persistent-for-user', render279(), '' );
$GLOBALS['qa279']['user'] = 8;
check279( 'other-user-not-dismissed', false !== strpos( render279(), 'Useful &amp; private news' ), true );
$main = 'free' === $tier ? 'woocommerce-jetpack.php' : 'booster-' . $tier . '-for-woocommerce.php';
$main_source = file_get_contents( $root . '/' . $tier . '/' . $main );
$readme = file_get_contents( $root . '/' . $tier . '/readme.txt' );
$admin = file_get_contents( $root . '/' . $tier . '/includes/core/class-wcj-admin.php' );
check279( 'main-header-version', 1 === preg_match( '/\* Version: 9\.0\.0\r?\r?\n/', $main_source ), true );
check279( 'runtime-version', false !== strpos( $main_source, "public $". "version = '9.0.0';" ), true );
check279( 'php73-header-preserved', false !== strpos( $main_source, '* Requires PHP: 7.3' ), true );
check279( 'readme-stable-version', false !== strpos( $readme, 'Stable tag: 9.0.0' ), true );
check279( 'native-clock-entry-preserved', false !== strpos( $main_source, 'WCJ_Returns_Clock::read();' ), true );
check279( 'announcement-loader-present', 1 === substr_count( $admin, "require_once __DIR__ . '/../admin/class-wcj-admin-announcement.php';" ) && 1 === substr_count( $admin, 'WCJ_Admin_Announcement::init();' ), true );
echo json_encode( array( 'result' => 'PASS', 'tier' => $tier, 'cases' => $cases, 'scope' => 'Offline synthetic primitives and actual reader; no WordPress/DB/network or POST adapter execution.' ), JSON_PRETTY_PRINT ) . "\n";
