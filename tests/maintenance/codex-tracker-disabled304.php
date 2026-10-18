<?php
// Offline source-bound regression only. No WordPress bootstrap or network execution.
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', '/fixture/' );
define( 'WCJ_FREE_PLUGIN_FILE', '/fixture/free-plugin.php' );
$GLOBALS['trace304'] = array();
$GLOBALS['hooks304'] = array();
$GLOBALS['http304'] = array();
$GLOBALS['reads304'] = array();
$GLOBALS['writes304'] = array();
function trailingslashit( $url ) {
 $GLOBALS['trace304'][] = array( 'constructor-entry', $url );
 throw new RuntimeException( 'tracker_constructor_probe304' );
}
function add_action() { $GLOBALS['hooks304'][] = 'action'; throw new RuntimeException( 'unexpected_hook' ); }
function add_filter() { $GLOBALS['hooks304'][] = 'filter'; throw new RuntimeException( 'unexpected_hook' ); }
function register_activation_hook() { $GLOBALS['hooks304'][] = 'activation'; throw new RuntimeException( 'unexpected_hook' ); }
function register_deactivation_hook() { $GLOBALS['hooks304'][] = 'deactivation'; throw new RuntimeException( 'unexpected_hook' ); }
function wp_remote_post() { $GLOBALS['http304'][] = 'post'; throw new RuntimeException( 'unexpected_http' ); }
function get_option() { $GLOBALS['reads304'][] = 'read'; throw new RuntimeException( 'unexpected_consent_read' ); }
function update_option() { $GLOBALS['writes304'][] = 'write'; throw new RuntimeException( 'unexpected_consent_write' ); }
function source_function304( $source, $name ) {
 if ( hash_file( 'sha256', $source['path'] ) !== $source['sha256'] ) { throw new RuntimeException( 'source_identity' ); }
 $tokens = token_get_all( file_get_contents( $source['path'] ) ); $found = array();
 for ( $i = 0, $n = count( $tokens ); $i < $n; ++$i ) {
  if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
  $j = $i + 1;
  while ( $j < $n && is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
  if ( ! is_array( $tokens[$j] ) || $name !== $tokens[$j][1] ) { continue; }
  $text = ''; $depth = 0; $started = false;
  for ( $k = $i; $k < $n; ++$k ) {
   $token = $tokens[$k]; $text .= is_array( $token ) ? $token[1] : $token;
   if ( ! is_array( $token ) && '{' === $token ) { ++$depth; $started = true; }
   if ( ! is_array( $token ) && '}' === $token ) { --$depth; if ( $started && 0 === $depth ) { break; } }
  }
  if ( ! $started || 0 !== $depth ) { throw new RuntimeException( 'function_boundary' ); }
  $found[] = $text;
 }
 if ( 1 !== count( $found ) ) { throw new RuntimeException( 'unique_function' ); }
 return $found[0];
}
if ( hash_file( 'sha256', $argv[1] ) !== $argv[2] ) { throw new RuntimeException( 'case_identity' ); }
$data = json_decode( file_get_contents( $argv[1] ), true ); $tier = $argv[3]; $variant = $argv[4];
if ( ! isset( $data['cases'][$tier] ) || ! in_array( $variant, array( 'before', 'after' ), true ) ) { throw new RuntimeException( 'arguments' ); }
$c = $data['cases'][$tier];
if ( hash_file( 'sha256', $c['library']['path'] ) !== $c['library']['sha256'] ) { throw new RuntimeException( 'library_identity' ); }
require $c['library']['path'];
$function = source_function304( $c[$variant], $c['function'] );
$has_new = false;
foreach ( token_get_all( '<?php ' . $function ) as $token ) { if ( is_array( $token ) && T_NEW === $token[0] ) { $has_new = true; } }
eval( $function );
$outcome = 'return'; $exception = null;
try { call_user_func( $c['function'] ); } catch ( Throwable $error ) { $outcome = 'throw'; $exception = array( get_class( $error ), $error->getMessage() ); }
$before = 'before' === $variant; $rows = array(); $failed = false;
$add = function ( $id, $actual, $expected ) use ( &$rows, &$failed ) {
 $rows[] = array( 'id' => $id, 'actual' => $actual, 'expected' => $expected );
 if ( $actual !== $expected ) { $failed = true; }
};
$add( 'source-constructor-token', $has_new, $before );
$add( 'startup-outcome', $outcome, $before ? 'throw' : 'return' );
$add( 'exact-probe-or-no-exception', $exception, $before ? array( 'RuntimeException', 'tracker_constructor_probe304' ) : null );
$add( 'constructor-entry-or-none', $GLOBALS['trace304'], $before ? array( array( 'constructor-entry', 'https://boosterio.bigscoots-staging.com' ) ) : array() );
$add( 'no-tracker-hooks', $GLOBALS['hooks304'], array() );
$add( 'no-http-send', $GLOBALS['http304'], array() );
$add( 'no-consent-read', $GLOBALS['reads304'], array() );
$add( 'no-consent-write', $GLOBALS['writes304'], array() );
$add( 'tracker-library-retained', class_exists( 'Plugin_Usage_Tracker', false ), true );
$add( 'startup-function-retained', function_exists( $c['function'] ), true );
echo json_encode( array( 'task' => 304, 'tier' => $tier, 'variant' => $variant, 'version' => PHP_VERSION, 'status' => $failed ? 'FAIL' : 'PASS', 'assertions' => count( $rows ), 'rows' => $rows ), JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );
