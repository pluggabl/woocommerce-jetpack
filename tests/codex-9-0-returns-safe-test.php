<?php
/**
 * Finite offline source regression for actual persisted Returns exercise paths.
 * Usage: php -n tests/codex-9-0-returns-safe-test.php PLUGIN [main|unknown|unknown-transition]
 * The protocol adapter records prepared statements; it is not a SQL/database.
 *
 * @package Booster_For_WooCommerce
 */

if ( 'cli' !== PHP_SAPI || extension_loaded( 'mysqli' ) ) {
	fwrite( STDERR, "Offline CLI with php -n required. No native database is permitted.\n" );
	exit( 2 );
}
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
$safe_root = $argv[1] ?? dirname( __DIR__ );
$safe_mode = $argv[2] ?? 'main';
if ( ! in_array( $safe_mode, array( 'main', 'unknown', 'unknown-transition' ), true ) || ! is_file( $safe_root . '/includes/returns/class-wcj-returns-store.php' ) ) {
	fwrite( STDERR, "Explicit permitted source folder and finite mode required.\n" );
	exit( 2 );
}
$GLOBALS['safe'] = array( 'checks' => array(), 'imports' => array(), 'user' => 7, 'cap' => true, 'roles' => array( 'shop_manager' ), 'now' => 1700000000, 'options' => array( 'wcj_returns_enabled' => 'yes', 'wcj_returns_guest' => 'no', 'wcj_returns_notifications' => 'yes' ), 'actions' => array(), 'facts_reads' => 0 );

/** Record strict typed comparisons, not inferred success. */
function safe_check( $name, $actual, $expected ) {
	$GLOBALS['safe']['checks'][] = array( 'name' => $name, 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected );
}
/** Finite error code primitive. */
class WP_Error {
	private $code;
	public function __construct( $code, $message = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
/** Controlled redirect stops before the actual controller exit. */
class Safe_Redirect extends RuntimeException {}
function __( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_current_user_id() { return $GLOBALS['safe']['user']; }
function get_current_blog_id() { return 1; }
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['safe']['cap']; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function wp_get_current_user() { return (object) array( 'roles' => $GLOBALS['safe']['roles'] ); }
function is_admin() { return true; }
function wcj_is_rest() { return false; }
function did_action( $name ) { return 'plugins_loaded' === $name ? 1 : 0; }
function sanitize_key( $text ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $text ) ); }
function apply_filters( $hook, $value ) { return 'wcj_enabled_module_allowlist' === $hook ? ( $GLOBALS['safe']['allowlist'] ?? $value ) : $value; }
function w_c_j() { return $GLOBALS['safe']['plugin']; }
function WCJ() { return w_c_j(); }
function wp_unslash( $value ) { return $value; }
function sanitize_textarea_field( $value ) {
	return trim( strip_tags( $value ) ); }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'valid:' . $action ? 1 : ( $nonce === 'old:' . $action ? 2 : false ); }
function wp_nonce_field( $action ) { echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( 'valid:' . $action ) . '">'; }
function add_action( $name, $callback, $priority = 10 ) { $GLOBALS['safe']['actions'][ $name ] = $callback; }
function admin_url( $path ) { return 'https://synthetic.invalid/wp-admin/' . $path; }
function wp_safe_redirect( $url ) { $GLOBALS['safe']['redirect'] = $url; throw new Safe_Redirect( 'controlled_redirect' ); }
function wp_die( $message ) { throw new RuntimeException( 'controlled_denial' ); }
/** Existing queue fixture convention: inject only the existing constructor seams. */
function get_option( $key, $default = false ) {
	if ( 'wcj_returns_enabled' === $key && isset( $GLOBALS['safe']['store'] ) ) {
		foreach ( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 4 ) as $frame ) {
			if ( isset( $frame['object'] ) && $frame['object'] instanceof WCJ_Returns_Service ) {
				$property = new ReflectionProperty( WCJ_Returns_Service::class, 'store' );
				if ( null === $property->getValue( $frame['object'] ) ) {
					$property->setValue( $frame['object'], $GLOBALS['safe']['store'] );
					( new ReflectionProperty( WCJ_Returns_Service::class, 'facts' ) )->setValue( $frame['object'], $GLOBALS['safe']['facts'] );
				( new ReflectionProperty( WCJ_Returns_Service::class, 'clock' ) )->setValue( $frame['object'], function () { return $GLOBALS['safe']['now']; } );
				}
			}
		}
	}
	return $GLOBALS['safe']['options'][ $key ] ?? $default;
}
/** Controlled query URL API with no browser or HTTP. */
function add_query_arg( $key, $value = null, $url = null ) {
	$args = is_array( $key ) ? $key : array( $key => $value );
	$url  = is_array( $key ) ? $value : $url;
	$parts = parse_url( $url );
	$query = array();
	parse_str( $parts['query'] ?? '', $query );
	return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query( array_merge( $query, $args ) );
}
/** Exact named-function extraction copied from the retained queue convention. */
function safe_function( $path, $name ) {
	$tokens = token_get_all( file_get_contents( $path ) );
	for ( $i = 0; $i < count( $tokens ); ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) { continue; }
		$j = $i + 1;
		while ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { ++$j; }
		if ( ! is_array( $tokens[ $j ] ) || $name !== $tokens[ $j ][1] ) { continue; }
		$body = ''; $depth = 0;
		for ( ; $i < count( $tokens ); ++$i ) {
			$token = $tokens[ $i ]; $body .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) { ++$depth; }
			if ( '}' === $token && 0 === --$depth ) {
				$GLOBALS['safe']['imports'][ $name ] = array( 'file' => $path, 'file_sha256' => hash_file( 'sha256', $path ), 'body_sha256' => hash( 'sha256', $body ) );
				return $body;
			}
		}
	}
	throw new RuntimeException( 'actual_function_missing:' . $name );
}
foreach ( array( 'wcj_get_option' => 'wcj-functions-core.php', 'wcj_is_module_enabled' => 'wcj-functions-booster-core.php', 'wcj_check_modules_by_user_roles' => 'wcj-functions-booster-core.php', 'wcj_get_enabled_module_allowlist' => 'wcj-functions-booster-core.php', 'wcj_is_module_allowed_by_allowlist' => 'wcj-functions-booster-core.php' ) as $name => $file ) {
	$path = $safe_root . '/includes/functions/' . $file;
	if ( false !== strpos( file_get_contents( $path ), 'function ' . $name . '(' ) ) { eval( safe_function( $path, $name ) ); }
}
eval( 'class WCJ_Module { public $id; public $type="module"; public $parent_id; public $short_desc; public $desc; public $desc_pro; public function __construct() {} public ' . safe_function( $safe_root . '/includes/classes/class-wcj-module.php', 'is_enabled' ) . ' }' );
$GLOBALS['safe']['plugin'] = (object) array( 'options' => array(), 'all_modules' => array() );
$safe_module = require $safe_root . '/includes/class-wcj-returns.php';
$GLOBALS['safe']['plugin']->all_modules['returns'] = $safe_module;

/** Unchanged Facts dependency spy, never an alternative synthetic fact engine. */
class Safe_Facts extends WCJ_Returns_Facts {
	public function load( $id ) { ++$GLOBALS['safe']['facts_reads']; throw new RuntimeException( 'native_facts_forbidden_in_exercise' ); }
}
/** Prepare API returns a lossless receipt, not executable SQL. */
class Safe_Database {
	public $prefix = 'wp_';
	public function prepare( $sql, ...$args ) { return json_encode( array( 'sql' => $sql, 'args' => $args ) ); }
	public function esc_like( $text ) { return $text; }
	public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4'; }
}
/**
 * Fixed protocol double for only these Store statements. No SQL is executed.
 * Raw storage effects are modeled here; quantity/lifecycle remain actual Service.
 */
class Safe_Connection extends WCJ_Returns_Connection {
	public $requests = array();
	public $claims = array();
	public $queries = array();
	public $present = array( 'wp_wcj_return_requests' => 'new', 'wp_wcj_return_claims' => 'new' );
	public $next = 1;
	public $snapshot;
	public $force_drift = false;
	public $unknown = false;
	public $commit_persisted = false;
	public function __construct() {}
	public function insert_id() { return $this->next - 1; }
	public function unpack( $text ) {
		$data = json_decode( $text, true );
		if ( is_array( $data ) ) { return array( $data['sql'], $data['args'] ); }
		$prefix = 'SELECT * FROM wp_wcj_return_requests WHERE ';
		$suffix = ' ORDER BY id DESC LIMIT 50';
		$first = '(\{"sql":"purpose=%d AND id < %d","args":\[[12],[1-9][0-9]{0,18}\]\})';
		$actor = '(\{"sql":" AND actor_id=%d","args":\[[1-9][0-9]{0,17}\]\})';
		$order = '(\{"sql":" AND order_id=%d","args":\[[1-9][0-9]{0,17}\]\})';
		$status = '(\{"sql":" AND status=%s","args":\["(?:pending|approved|declined|cancelled)"\]\})';
		foreach ( array( '', $actor, $order, $actor . $order, $status, $status . $order ) as $tail ) {
			if ( 1 !== preg_match( '/^' . preg_quote( $prefix, '/' ) . $first . $tail . preg_quote( $suffix, '/' ) . '$/D', $text, $matches ) ) { continue; }
			$sql = $prefix; $args = array();
			foreach ( array_slice( $matches, 1 ) as $fragment ) {
				$receipt = json_decode( $fragment, true );
				$sql .= $receipt['sql']; $args = array_merge( $args, $receipt['args'] );
			}
			return array( $sql . $suffix, $args );
		}
		return array( $text, array() );
	}
	public function query( $text ) {
		list( $sql, $a ) = $this->unpack( $text ); $this->queries[] = array( $sql, $a );
		if ( 'START TRANSACTION' === $sql ) { $this->snapshot = array( $this->requests, $this->claims, $this->next ); return 0; }
		if ( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' === $sql ) { return 0; }
		if ( 'ROLLBACK' === $sql ) {
			if ( $this->snapshot ) { list( $this->requests, $this->claims, $this->next ) = $this->snapshot; }
			$this->snapshot = null; return 0;
		}
		if ( 'COMMIT' === $sql ) {
			if ( $this->commit_persisted || ! $this->unknown ) { $this->snapshot = null; }
			if ( $this->unknown ) { throw new RuntimeException( 'storage_failure' ); }
			return 0;
		}
		if ( 0 === strpos( $sql, 'CREATE TABLE IF NOT EXISTS ' ) ) {
			$table = explode( ' ', $sql )[5];
			if ( ! isset( $this->present[ $table ] ) ) { $this->present[ $table ] = 'new'; }
			return 0;
		}
		if ( 0 === strpos( $sql, 'INSERT INTO wp_wcj_return_claims (purpose,order_id,item_id,' ) ) {
			$upsert = false !== strpos( $sql, 'ON DUPLICATE KEY' );
			$id = $upsert ? $a[2] : 0; $hash = $upsert ? $a[3] : $a[2]; $key = $a[0] . ':' . $a[1] . ':' . $id;
			if ( isset( $this->claims[ $key ] ) ) {
				if ( ! $upsert ) { throw new RuntimeException( 'storage_failure' ); }
				if ( $this->force_drift && 0 === $id ) { $this->claims[ $key ]['facts_hash'] = str_repeat( '0', 64 ); }
				return 0;
			}
			$this->claims[ $key ] = array( 'purpose' => (string) $a[0], 'order_id' => (string) $a[1], 'item_id' => (string) $id, 'reserved' => '0', 'facts_hash' => $hash, 'needs_review' => '0' ); return 1;
		}
		if ( 0 === strpos( $sql, 'UPDATE wp_wcj_return_claims SET reserved=%d,needs_review=%d WHERE purpose=%d' ) ) {
			$key = $a[2] . ':' . $a[3] . ':' . $a[4];
			if ( ! isset( $this->claims[ $key ] ) ) { return 0; }
			$this->claims[ $key ]['reserved'] = (string) $a[0]; $this->claims[ $key ]['needs_review'] = (string) $a[1]; return 1;
		}
		if ( 0 === strpos( $sql, 'INSERT INTO wp_wcj_return_requests (purpose,' ) ) {
			$keys = array( 'purpose', 'order_id', 'actor_id', 'idempotency', 'input_hash', 'status', 'revision', 'created_gmt', 'updated_gmt', 'payload' );
			$row = array_combine( $keys, $a ); $row['id'] = $this->next++;
			foreach ( array( 'purpose', 'order_id', 'actor_id', 'revision', 'id' ) as $key ) { $row[ $key ] = (string) $row[ $key ]; }
			foreach ( $this->requests as $old ) { if ( array_intersect_key( $old, array_flip( array( 'purpose', 'order_id', 'actor_id', 'idempotency' ) ) ) === array_intersect_key( $row, array_flip( array( 'purpose', 'order_id', 'actor_id', 'idempotency' ) ) ) ) { throw new RuntimeException( 'storage_failure' ); } }
			$this->requests[ (int) $row['id'] ] = $row; return 1;
		}
		if ( 0 === strpos( $sql, 'UPDATE wp_wcj_return_requests SET payload=%s,status=%s,revision=revision+1,updated_gmt=%s WHERE purpose=%d' ) ) {
			$id = $a[4]; $row = $this->requests[ $id ] ?? null;
			if ( ! $row || (int) $row['purpose'] !== $a[3] || (int) $row['revision'] !== (int) $a[5] ) { return 0; }
			$row['payload'] = $a[0]; $row['status'] = $a[1]; $row['revision'] = (string) ( (int) $row['revision'] + 1 ); $row['updated_gmt'] = $a[2]; $this->requests[ $id ] = $row; return 1;
		}
		throw new RuntimeException( 'unsupported_offline_statement:' . $sql );
	}
	public function rows( $text ) {
		list( $sql, $a ) = $this->unpack( $text ); $this->queries[] = array( $sql, $a );
		if ( 'SELECT @@SESSION.autocommit AS autocommit' === $sql ) { return array( array( 'autocommit' => '1' ) ); }
		if ( 'SHOW TABLE STATUS LIKE %s' === $sql ) { return isset( $this->present[ $a[0] ] ) ? array( array( 'Name' => $a[0], 'Engine' => 'InnoDB' ) ) : array(); }
		if ( 0 === strpos( $sql, 'SHOW COLUMNS FROM ' ) ) {
			$table = substr( $sql, 18 ); $request = 'wp_wcj_return_requests' === $table;
			$fields = $request ? array( 'id' => 'bigint unsigned', 'purpose' => 'tinyint unsigned', 'order_id' => 'bigint unsigned', 'actor_id' => 'bigint unsigned', 'idempotency' => 'char(64)', 'input_hash' => 'char(64)', 'status' => 'varchar(16)', 'revision' => 'bigint unsigned', 'created_gmt' => 'datetime', 'updated_gmt' => 'datetime', 'payload' => 'longtext' ) : array( 'purpose' => 'tinyint unsigned', 'order_id' => 'bigint unsigned', 'item_id' => 'bigint unsigned', 'reserved' => 'bigint unsigned', 'facts_hash' => 'char(64)', 'needs_review' => 'tinyint unsigned' );
			if ( 'old' === ( $this->present[ $table ] ?? '' ) ) { unset( $fields['purpose'] ); }
			$rows = array(); foreach ( $fields as $name => $type ) { $rows[] = array( 'Field' => $name, 'Type' => $type, 'Null' => 'NO', 'Extra' => $request && 'id' === $name ? 'auto_increment' : '' ); } return $rows;
		}
		if ( 0 === strpos( $sql, 'SHOW INDEX FROM ' ) ) {
			$table = substr( $sql, 16 ); $request = 'wp_wcj_return_requests' === $table;
			$keys = $request ? array( 'PRIMARY' => array( 'id' ), 'request_token' => array( 'purpose', 'order_id', 'actor_id', 'idempotency' ) ) : array( 'PRIMARY' => array( 'purpose', 'order_id', 'item_id' ) );
			$rows = array(); foreach ( $keys as $key => $fields ) { foreach ( $fields as $position => $name ) { $rows[] = array( 'Non_unique' => '0', 'Sub_part' => null, 'Key_name' => $key, 'Seq_in_index' => $position + 1, 'Column_name' => $name ); } } return $rows;
		}
		if ( preg_match( '/^SELECT order_id FROM (wp_wcj_return_requests|wp_wcj_return_claims) LIMIT 1$/D', $sql, $m ) ) {
			$rows = 'wp_wcj_return_requests' === $m[1] ? $this->requests : $this->claims; $row = reset( $rows ); return $row ? array( array( 'order_id' => $row['order_id'] ) ) : array();
		}
		if ( 0 === strpos( $sql, 'SELECT * FROM wp_wcj_return_claims WHERE purpose=%d AND order_id=%d' ) ) {
			$rows = array_values( array_filter( $this->claims, function ( $row ) use ( $a ) { return (int) $row['purpose'] === $a[0] && (int) $row['order_id'] === $a[1]; } ) );
			if ( false !== strpos( $sql, 'item_id=0' ) ) { $rows = array_values( array_filter( $rows, function ( $row ) { return '0' === $row['item_id']; } ) ); }
			elseif ( false !== strpos( $sql, 'item_id=%d' ) ) { $rows = array_values( array_filter( $rows, function ( $row ) use ( $a ) { return (int) $row['item_id'] === $a[2]; } ) ); }
			usort( $rows, function ( $x, $y ) { return (int) $x['item_id'] <=> (int) $y['item_id']; } ); return array_slice( $rows, 0, false !== strpos( $sql, 'LIMIT 3' ) ? 3 : 1 );
		}
		if ( 0 === strpos( $sql, 'SELECT * FROM wp_wcj_return_requests WHERE purpose=%d AND id=%d' ) ) { $row = $this->requests[ $a[1] ] ?? null; return $row && (int) $row['purpose'] === $a[0] ? array( $row ) : array(); }
		if ( 0 === strpos( $sql, 'SELECT * FROM wp_wcj_return_requests WHERE purpose=%d AND order_id=%d' ) || 0 === strpos( $sql, 'SELECT id FROM wp_wcj_return_requests WHERE purpose=%d AND order_id=%d' ) ) {
			$rows = array_values( array_filter( $this->requests, function ( $row ) use ( $a ) { return (int) $row['purpose'] === $a[0] && (int) $row['order_id'] === $a[1] && ( count( $a ) < 4 || ( (int) $row['actor_id'] === $a[2] && $row['idempotency'] === $a[3] ) ); } ) ); return array_slice( $rows, 0, false !== strpos( $sql, 'LIMIT 2' ) ? 2 : 101 );
		}
		if ( 0 === strpos( $sql, 'SELECT * FROM wp_wcj_return_requests WHERE purpose=%d AND id < %d' ) ) {
			$rows = array_values( array_filter( $this->requests, function ( $row ) use ( $a, $sql ) {
				if ( (int) $row['purpose'] !== $a[0] || (int) $row['id'] >= $a[1] ) { return false; }
				$pos = 2; if ( false !== strpos( $sql, ' AND status=%s' ) && $row['status'] !== $a[ $pos++ ] ) { return false; }
				if ( false !== strpos( $sql, ' AND actor_id=%d' ) && (int) $row['actor_id'] !== $a[ $pos++ ] ) { return false; }
				if ( false !== strpos( $sql, ' AND order_id=%d' ) && (int) $row['order_id'] !== $a[ $pos ] ) { return false; } return true;
			} ) ); usort( $rows, function ( $x, $y ) { return (int) $y['id'] <=> (int) $x['id']; } ); return array_slice( $rows, 0, 50 );
		}
		if ( 0 === strpos( $sql, 'SELECT * FROM wp_wcj_return_requests WHERE purpose=%d AND actor_id=0' ) ) { return array_values( array_filter( $this->requests, function ( $row ) use ( $a ) { return (int) $row['purpose'] === $a[0] && '0' === $row['actor_id'] && (int) $row['order_id'] === $a[1] && (int) $row['id'] < $a[2]; } ) ); }
		throw new RuntimeException( 'unsupported_offline_read:' . $sql );
	}
}
/** Fresh admitted fixture with actual Store/Service, protocol only. */
function safe_reset( $purpose = 2 ) {
	$GLOBALS['safe']['user'] = 7; $GLOBALS['safe']['cap'] = true; $GLOBALS['safe']['roles'] = array( 'shop_manager' ); $GLOBALS['safe']['now'] = 1700000000;
	$GLOBALS['safe']['options'] = array( 'wcj_returns_enabled' => 'yes', 'wcj_returns_guest' => 'no', 'wcj_returns_notifications' => 'yes' );
	$GLOBALS['safe']['plugin']->options = array(); unset( $GLOBALS['wcj_modules_by_user_roles_data'], $GLOBALS['safe']['allowlist'] );
	$GLOBALS['safe']['plugin']->all_modules['returns'] = $GLOBALS['safe']['module'];
	$db = new Safe_Database(); $conn = new Safe_Connection(); $store = new WCJ_Returns_Store( $db, $conn, $purpose ); $facts = new Safe_Facts();
	$GLOBALS['safe']['store'] = $store; $GLOBALS['safe']['facts'] = $facts; $GLOBALS['safe']['facts_reads'] = 0;
	$service = new WCJ_Returns_Service( $store, $facts, function () { return $GLOBALS['safe']['now']; } );
	$_GET = array(); $_POST = array(); return array( $store, $conn, $service );
}
function safe_code( $result ) { return is_wp_error( $result ) ? $result->get_error_code() : 'ok'; }
function safe_thrown( $operation ) { try { $operation(); } catch ( Throwable $e ) { return $e->getMessage(); } return 'none'; }
function safe_token( $number ) { return str_pad( dechex( $number ), 64, '0', STR_PAD_LEFT ); }
function safe_submit( $service, $number = 1, $quantity = '1' ) { return $service->synthetic_submit( $quantity, safe_token( $number ), 'valid:wcj_returns_synthetic_create' ); }
function safe_snapshot( $conn ) { return json_encode( array( $conn->requests, $conn->claims, $conn->next ) ); }
function safe_html( $module ) { ob_start(); $module->admin(); return ob_get_clean(); }
function safe_finish() {
	$checks = $GLOBALS['safe']['checks']; $failures = count( array_filter( $checks, function ( $x ) { return ! $x['pass']; } ) );
	echo json_encode( array( 'mode' => $GLOBALS['safe']['mode'], 'assertions' => count( $checks ), 'failures' => $failures, 'checks' => $checks, 'imports' => $GLOBALS['safe']['imports'], 'native_sql_or_http' => false ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	exit( $failures ? 1 : 0 );
}
$GLOBALS['safe']['module'] = $safe_module; $GLOBALS['safe']['mode'] = $safe_mode;
foreach ( array( 'includes/class-wcj-returns.php', 'includes/returns/class-wcj-returns-store.php', 'includes/returns/class-wcj-returns-service.php', 'includes/returns/class-wcj-returns-facts.php', 'includes/returns/class-wcj-returns-guest.php' ) as $file ) { $GLOBALS['safe']['imports'][ $file ] = array( 'file' => $safe_root . '/' . $file, 'file_sha256' => hash_file( 'sha256', $safe_root . '/' . $file ), 'complete_unchanged_import' => true ); }

if ( 'unknown' === $safe_mode ) {
	list( $store, $conn ) = safe_reset(); $conn->unknown = true; $conn->commit_persisted = true;
	$_POST = array( 'quantity' => '1', 'token' => safe_token( 1 ), '_wpnonce' => 'valid:wcj_returns_synthetic_create' );
	ob_start(); register_shutdown_function( function () use ( $conn ) {
		$html = ob_get_clean();
		safe_check( 'unknown: fixed unknown feedback', false !== strpos( $html, 'previous outcome is unknown' ), true );
		safe_check( 'unknown: only read recovery action', substr_count( $html, 'value="wcj_returns_synthetic_recover"' ), 1 );
		safe_check( 'unknown: no create action', strpos( $html, 'value="wcj_returns_synthetic_create"' ), false );
		safe_check( 'unknown: original token retained', false !== strpos( $html, 'value="' . safe_token( 1 ) . '"' ), true );
		safe_check( 'unknown: one persisted private row', count( $conn->requests ), 1 );
		safe_check( 'unknown: native facts never read', $GLOBALS['safe']['facts_reads'], 0 );
		safe_check( 'unknown: no decision request link', strpos( $html, 'Read current retained request only' ), false );
		safe_check( 'unknown: no decision action', strpos( $html, 'value="wcj_returns_synthetic_transition"' ), false );
		safe_check( 'unknown: no dispatch action', strpos( $html, 'value="wcj_returns_dispatch"' ), false );
		safe_check( 'unknown: no token URL', strpos( $html, '?token=' ), false );
		safe_check( 'unknown: no Woo order URL', strpos( $html, 'view-order' ), false );
		safe_check( 'unknown: no saved query inference', strpos( $html, 'wcj_returns_result=saved' ), false );
		safe_finish();
	} );
	$safe_module->synthetic_create();
	throw new RuntimeException( 'controller_exit_expected' );
}

if ( 'unknown-transition' === $safe_mode ) {
	list( $store, $conn, $service ) = safe_reset();
	$row = safe_submit( $service );
	if ( is_wp_error( $row ) ) { throw new RuntimeException( 'transition_seed_unavailable' ); }
	$conn->unknown = true; $conn->commit_persisted = true;
	$_POST = array( 'request_id' => '1', 'revision' => '1', 'status' => 'declined', '_wpnonce' => 'valid:wcj_returns_synthetic_transition_1' );
	ob_start(); register_shutdown_function( function () use ( $conn, $service, $row ) {
		$html = ob_get_clean();
		$link = '<a href="https://synthetic.invalid/wp-admin/admin.php?page=wcj-returns&amp;wcj_returns_view=synthetic&amp;request_id=1">Read current retained request only</a>';
		safe_check( 'unknown transition: fixed unknown warning', false !== strpos( $html, 'previous outcome is unknown' ), true );
		safe_check( 'unknown transition: exact escaped fixed-admin observation link', false !== strpos( $html, $link ), true );
		safe_check( 'unknown transition: one observation link', substr_count( $html, 'Read current retained request only' ), 1 );
		safe_check( 'unknown transition: no saved query inference', strpos( $html, 'wcj_returns_result=saved' ), false );
		safe_check( 'unknown transition: no successful feedback inference', strpos( $html, 'result was retained' ), false );
		safe_check( 'unknown transition: original recovery form retained', substr_count( $html, 'value="wcj_returns_synthetic_recover"' ), 1 );
		safe_check( 'unknown transition: no invented original token', false !== strpos( $html, 'name="token" maxlength="64" required value=""' ), true );
		safe_check( 'unknown transition: no token URL', strpos( $html, '?token=' ), false );
		safe_check( 'unknown transition: no Woo order URL', strpos( $html, 'view-order' ), false );
		safe_check( 'unknown transition: no create action', strpos( $html, 'value="wcj_returns_synthetic_create"' ), false );
		safe_check( 'unknown transition: no decision action', strpos( $html, 'value="wcj_returns_synthetic_transition"' ), false );
		safe_check( 'unknown transition: no dispatch action', strpos( $html, 'value="wcj_returns_dispatch"' ), false );
		safe_check( 'unknown transition: no automatic redirect', isset( $GLOBALS['safe']['redirect'] ), false );
		$commit = -1;
		foreach ( $conn->queries as $index => $query ) { if ( 'COMMIT' === $query[0] ) { $commit = $index; } }
		$reads = array_filter( array_slice( $conn->queries, $commit + 1 ), function ( $query ) { return 0 === strpos( $query[0], 'SELECT * FROM wp_wcj_return_requests' ); } );
		safe_check( 'unknown transition: no request fetch to render link', count( $reads ), 0 );
		safe_check( 'unknown transition: one modeled private row', count( $conn->requests ), 1 );
		safe_check( 'unknown transition: modeled committed status retained', $conn->requests[1]['status'], 'declined' );
		safe_check( 'unknown transition: zero live Facts calls', $GLOBALS['safe']['facts_reads'], 0 );
		// Explicit simulated link-follow reads, not automatic product actions.
		$conn->unknown = false; $conn->commit_persisted = false;
		$before = safe_snapshot( $conn );
		$view = $service->synthetic_view( 1 );
		safe_check( 'unknown transition: existing creator-bound observation admitted', safe_code( $view ), 'ok' );
		safe_check( 'unknown transition: observation returns retained status', $view['status'] ?? null, 'declined' );
		$GLOBALS['safe']['user'] = 8;
		safe_check( 'unknown transition: observation does not bypass creator', safe_code( $service->synthetic_view( 1 ) ), 'forbidden' );
		$GLOBALS['safe']['user'] = 7;
		$page = new ReflectionMethod( WCJ_Returns::class, 'synthetic_page' );
		foreach ( array( 0, -1, '1', PHP_INT_MAX ) as $id ) {
			ob_start(); $page->invoke( $GLOBALS['safe']['module'], '', true, $id ); $denied = ob_get_clean();
			safe_check( 'unknown transition: invalid ID link absent ' . json_encode( $id ), strpos( $denied, 'Read current retained request only' ), false );
		}
		$GLOBALS['safe']['cap'] = false; $queries = count( $conn->queries );
		ob_start(); $page->invoke( $GLOBALS['safe']['module'], '', true, 1 ); $denied = ob_get_clean();
		safe_check( 'unknown transition: fresh capability denied before link', strpos( $denied, 'Read current retained request only' ), false );
		safe_check( 'unknown transition: fresh capability denial before SQL', count( $conn->queries ), $queries );
		safe_check( 'unknown transition: observations never mutate retained rows', safe_snapshot( $conn ), $before );
		safe_finish();
	} );
	$safe_module->synthetic_transition();
	throw new RuntimeException( 'controller_exit_expected' );
}

// Literal outcomes below are frozen before the first child execution.
list( $store, $conn, $service ) = safe_reset();
$row = safe_submit( $service );
safe_check( 'create returns persisted row', safe_code( $row ), 'ok' );
safe_check( 'create status', $row['status'] ?? null, 'pending' );
safe_check( 'create revision', $row['revision'] ?? null, '1' );
safe_check( 'create stored purpose', $row['purpose'] ?? null, '2' );
safe_check( 'create fixed quantity', $store->payload( $row )['items'] ?? null, array( 1 => 1000000 ) );
safe_check( 'create fixed notification policy', $store->payload( $row )['policy'] ?? null, array( 'days' => 30, 'notify' => false, 'version' => 1 ) );
safe_check( 'create empty outbox with merchant opt-in', $store->payload( $row )['outbox'] ?? null, array() );
safe_check( 'create exact two claims', count( $conn->claims ), 2 );
safe_check( 'create reserved quantity', $conn->claims[ '2:' . $row['order_id'] . ':1' ]['reserved'], '1000000' );
$before = safe_snapshot( $conn ); $GLOBALS['safe']['now'] += 10;
$replay = safe_submit( $service );
safe_check( 'retained birth wins after provisional clock change', safe_code( $replay ), 'ok' );
safe_check( 'same-token replay same ID', $replay['id'] ?? null, $row['id'] );
safe_check( 'replay no storage mutation', safe_snapshot( $conn ), $before );
safe_check( 'incoming quantity mismatch', safe_code( safe_submit( $service, 1, '2' ) ), 'idempotency_conflict' );
safe_check( 'mismatch no storage mutation', safe_snapshot( $conn ), $before );
safe_check( 'actual same-token read-only recovery', safe_code( $service->synthetic_recover( safe_token( 1 ), 'valid:wcj_returns_synthetic_recover' ) ), 'ok' );
safe_check( 'recovery no mutation', safe_snapshot( $conn ), $before );
safe_check( 'read-only absent stays unknown', safe_code( $service->synthetic_recover( safe_token( 99 ), 'valid:wcj_returns_synthetic_recover' ) ), 'commit_unknown' );
safe_check( 'absent recovery no mutation', safe_snapshot( $conn ), $before );
safe_check( 'synthetic facts never loaded native', $GLOBALS['safe']['facts_reads'], 0 );
$binding_guard = new ReflectionMethod( WCJ_Returns_Service::class, 'synthetic_retained' );
safe_check( 'full digest mismatch at actual collision guard', safe_thrown( function () use ( $store, $service, $row, $binding_guard ) {
	$store->transaction( function () use ( $store, $service, $row, $binding_guard ) { $binding_guard->invoke( $service, $store->existing_synthetic_anchor( (int) $row['order_id'] ), (int) $row['order_id'], 7, 1, hash( 'sha256', safe_token( 2 ) ) ); } );
} ), 'synthetic_conflict' );
safe_check( 'collision guard no mutation', safe_snapshot( $conn ), $before );

foreach ( array( 'approved' => '1000000', 'declined' => '0', 'cancelled' => '0' ) as $status => $units ) {
	list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service );
	$decision = $service->synthetic_transition( (int) $row['id'], 1, $status, 'valid:wcj_returns_synthetic_transition_' . $row['id'] );
	safe_check( $status . ' actual shared transition', safe_code( $decision ), 'ok' );
	safe_check( $status . ' terminal status', $decision['status'] ?? null, $status );
	safe_check( $status . ' revision', $decision['revision'] ?? null, '2' );
	safe_check( $status . ' reservation', $conn->claims[ '2:' . $row['order_id'] . ':1' ]['reserved'], $units );
	safe_check( $status . ' retained history count', count( $store->payload( $decision )['history'] ), 2 );
	safe_check( $status . ' no notification intents', $store->payload( $decision )['outbox'], array() );
	safe_check( $status . ' view through actual retained validator', safe_code( $service->synthetic_view( (int) $row['id'] ) ), 'ok' );
	safe_check( $status . ' no reopen', safe_code( $service->synthetic_transition( (int) $row['id'], 2, 'approved', 'valid:wcj_returns_synthetic_transition_' . $row['id'] ) ), 'transition_invalid' );
}

foreach ( array( 'anchor_only', 'zero_item_only', 'request_without_anchor', 'wrong_full_token', 'unexpected_item' ) as $case ) {
	list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service );
	if ( 'anchor_only' === $case ) { $conn->requests = array(); unset( $conn->claims[ '2:' . $row['order_id'] . ':1' ] ); }
	if ( 'zero_item_only' === $case ) { $conn->requests = array(); unset( $conn->claims[ '2:' . $row['order_id'] . ':0' ] ); $conn->claims[ '2:' . $row['order_id'] . ':1' ]['reserved'] = '0'; }
	if ( 'request_without_anchor' === $case ) { $conn->claims = array(); }
	if ( 'wrong_full_token' === $case ) { $conn->requests[1]['idempotency'] = str_repeat( 'f', 64 ); }
	if ( 'unexpected_item' === $case ) { $x = $conn->claims[ '2:' . $row['order_id'] . ':1' ]; $x['item_id'] = '2'; $conn->claims[ '2:' . $row['order_id'] . ':2' ] = $x; }
	$before = safe_snapshot( $conn ); $result = safe_submit( $service );
	safe_check( $case . ' rejected', is_wp_error( $result ), true );
	safe_check( $case . ' byte-identical modeled storage', safe_snapshot( $conn ), $before );
}
list( $store, $conn, $service ) = safe_reset(); $conn->force_drift = true;
safe_check( 'synthetic kernel error becomes throw', safe_code( safe_submit( $service ) ), 'needs_review' );
safe_check( 'kernel error rollback leaves no anchor/items', count( $conn->claims ), 0 );
safe_check( 'kernel error rollback leaves no request', count( $conn->requests ), 0 );
safe_check( 'kernel error never COMMIT', count( array_filter( $conn->queries, function ( $q ) { return 'COMMIT' === $q[0]; } ) ), 0 );

foreach ( array( 'missing_witness', 'flipped_purpose', 'changed_birth', 'outbox_nonempty' ) as $case ) {
	list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service ); $data = $store->payload( $row );
	if ( 'missing_witness' === $case ) { unset( $data['synthetic'] ); }
	if ( 'flipped_purpose' === $case ) { $row['purpose'] = '1'; }
	if ( 'changed_birth' === $case ) { ++$data['synthetic']['birth']; }
	if ( 'outbox_nonempty' === $case ) { $data['outbox'] = array( 'bad' => array() ); }
	$row['payload'] = json_encode( $data );
	safe_check( $case . ' payload rejected', safe_thrown( function () use ( $store, $row ) { $store->payload( $row ); } ), 'payload_invalid' );
}
list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service ); $data = $store->payload( $row ); ++$data['synthetic']['birth'];
$before = safe_snapshot( $conn );
safe_check( 'save witness immutable', safe_thrown( function () use ( $store, $row, $data ) { $store->transaction( function () use ( $store, $row, $data ) { $store->save( $row, $data, 'pending', 1700000000 ); } ); } ), 'payload_invalid' );
safe_check( 'save witness failure no mutation', safe_snapshot( $conn ), $before );

foreach ( array( 'anonymous', 'revoked', 'disabled', 'excluded_role', 'stale_role', 'missing_module', 'nonce_missing', 'nonce_wrong' ) as $case ) {
	list( $store, $conn, $service ) = safe_reset(); $nonce = 'valid:wcj_returns_synthetic_create';
	if ( 'anonymous' === $case ) { $GLOBALS['safe']['user'] = 0; }
	if ( 'revoked' === $case ) { $GLOBALS['safe']['cap'] = false; }
	if ( 'disabled' === $case ) { $GLOBALS['safe']['options']['wcj_returns_enabled'] = 'no'; }
	if ( 'excluded_role' === $case ) { $GLOBALS['safe']['options']['wcj_modules_by_user_roles_enabled'] = 'yes'; $GLOBALS['safe']['options']['wcj_modules_by_user_roles_excl_shop_manager'] = array( 'returns' ); }
	if ( 'stale_role' === $case ) { $GLOBALS['safe']['options']['wcj_modules_by_user_roles_enabled'] = 'yes'; $GLOBALS['wcj_modules_by_user_roles_data'] = array( 'role' => 'administrator' ); }
	if ( 'missing_module' === $case ) { unset( $GLOBALS['safe']['plugin']->all_modules['returns'] ); }
	if ( 'nonce_missing' === $case ) { $nonce = ''; }
	if ( 'nonce_wrong' === $case ) { $nonce = 'valid:wcj_returns_submit_1'; }
	safe_check( $case . ' denied', is_wp_error( $service->synthetic_submit( '1', safe_token( 1 ), $nonce ) ), true );
	safe_check( $case . ' before any private SQL', count( $conn->queries ), 0 );
	safe_check( $case . ' before native facts', $GLOBALS['safe']['facts_reads'], 0 );
}
list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service ); $GLOBALS['safe']['user'] = 8;
safe_check( 'other capable staff cannot view', safe_code( $service->synthetic_view( (int) $row['id'] ) ), 'forbidden' );
safe_check( 'other capable staff cannot decide', safe_code( $service->synthetic_transition( (int) $row['id'], 1, 'approved', 'valid:wcj_returns_synthetic_transition_' . $row['id'] ) ), 'forbidden' );
safe_check( 'other actor read recovery stays unknown', safe_code( $service->synthetic_recover( safe_token( 1 ), 'valid:wcj_returns_synthetic_recover' ) ), 'commit_unknown' );
safe_check( 'other actor no native reads', $GLOBALS['safe']['facts_reads'], 0 );

list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service );
foreach ( array( 'transition', 'dispatch', 'customer_view', 'queue' ) as $method ) {
	$live = new WCJ_Returns_Service( $store, new Safe_Facts() );
	$args = array( 'transition' => array( (int) $row['id'], 1, 'approved', 'valid:wcj_returns_transition_1' ), 'dispatch' => array( (int) $row['id'], 'submitted_customer', 'valid:wcj_returns_dispatch_1' ), 'customer_view' => array( (int) $row['order_id'] ), 'queue' => array() );
	safe_check( 'injected synthetic Store denied by live ' . $method, is_wp_error( call_user_func_array( array( $live, $method ), $args[ $method ] ) ), true );
}
safe_check( 'injected synthetic live entries before Facts', $GLOBALS['safe']['facts_reads'], 0 );
$live_store = new WCJ_Returns_Store( new Safe_Database(), $conn, 1 );
safe_check( 'live get rejects synthetic global request ID', safe_thrown( function () use ( $live_store, $row ) { $live_store->get( (int) $row['id'] ); } ), 'request_unavailable' );
safe_check( 'live retained count excludes synthetic target', $live_store->retained_count( (int) $row['order_id'] ), 0 );
safe_check( 'live queue excludes before limit', count( $live_store->listing() ), 0 );
safe_check( 'live actor history excludes synthetic', count( $live_store->listing( 7, (int) $row['order_id'] ) ), 0 );
safe_check( 'live guest history excludes synthetic', count( $live_store->guest_listing( (int) $row['order_id'] ) ), 0 );
if ( method_exists( $live_store, 'staff_listing' ) ) { safe_check( 'Elite actual filtered query excludes synthetic', count( $live_store->staff_listing( 'pending', (int) $row['order_id'] ) ), 0 ); }

// A live seed through actual Store plus sixty actual synthetic submissions.
list( $live_store, $conn ) = safe_reset( 1 );
$live_header = array( 'order_id' => 10, 'actor_id' => 7, 'idempotency' => str_repeat( 'b', 64 ), 'input_hash' => str_repeat( 'c', 64 ), 'status' => 'pending', 'revision' => 1, 'created_gmt' => '2023-11-14 22:13:20', 'updated_gmt' => '2023-11-14 22:13:20', 'payload' => array( 'items' => array( 1 => 1000000 ), 'reason' => 'Private synthetic fixture live reason', 'policy' => array( 'days' => 30, 'notify' => true, 'version' => 1 ), 'history' => array( array( 'status' => 'pending', 'actor' => 7, 'time' => 1700000000 ) ), 'outbox' => array() ) );
$live_store->transaction( function () use ( $live_store, $live_header ) { return $live_store->insert( $live_header ); } );
$synthetic_store = new WCJ_Returns_Store( new Safe_Database(), $conn, 2 );
$synthetic_service = new WCJ_Returns_Service( $synthetic_store, new Safe_Facts(), function () { return $GLOBALS['safe']['now']; } );
for ( $number = 1; $number <= 60; ++$number ) { $result = safe_submit( $synthetic_service, $number ); if ( is_wp_error( $result ) ) { throw new RuntimeException( 'bounded_reader_seed_failed' ); } }
safe_check( 'synthetic sample cannot crowd live first page', array_column( $live_store->listing(), 'id' ), array( '1' ) );
safe_check( 'actual live count unaffected by sixty exercises', $live_store->retained_count( 10 ), 1 );
$GLOBALS['safe']['store'] = $live_store; $GLOBALS['safe']['facts_reads'] = 0;
require_once $safe_root . '/includes/class-wcj-returns-summary.php';
$GLOBALS['safe']['imports']['includes/class-wcj-returns-summary.php'] = array( 'file' => $safe_root . '/includes/class-wcj-returns-summary.php', 'file_sha256' => hash_file( 'sha256', $safe_root . '/includes/class-wcj-returns-summary.php' ), 'complete_unchanged_import' => true );
$summary = ( new WCJ_Returns_Summary() )->get_summary( array( 'scope' => 'summary' ) );
safe_check( 'actual Summary admitted live sample only', $summary['sample_size'] ?? null, 1 );
safe_check( 'actual Summary exact live counts', $summary['counts'] ?? null, array( 'pending' => 1, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'needs_review' => 1, 'facts_unavailable' => 1 ) );
safe_check( 'actual queue performs only one live Facts call', $GLOBALS['safe']['facts_reads'], 1 );
safe_check( 'actual Summary excludes private text', strpos( json_encode( $summary ), 'Private synthetic fixture live reason' ), false );

foreach ( array( 'absent', 'new_empty_partial', 'old', 'old_empty_partial', 'mixed', 'populated_partial' ) as $case ) {
	list( $store, $conn ) = safe_reset( 1 );
	if ( 'absent' === $case ) { $conn->present = array(); }
	if ( 'new_empty_partial' === $case || 'populated_partial' === $case ) { $conn->present = array( 'wp_wcj_return_requests' => 'new' ); }
	if ( 'old' === $case ) { $conn->present = array( 'wp_wcj_return_requests' => 'old', 'wp_wcj_return_claims' => 'old' ); }
	if ( 'old_empty_partial' === $case ) { $conn->present = array( 'wp_wcj_return_requests' => 'old' ); }
	if ( 'mixed' === $case ) { $conn->present = array( 'wp_wcj_return_requests' => 'new', 'wp_wcj_return_claims' => 'old' ); }
	if ( 'populated_partial' === $case ) { $conn->requests[1] = array( 'purpose' => '1', 'order_id' => '10' ); }
	$expected = in_array( $case, array( 'absent', 'new_empty_partial' ), true ) ? 'initializable' : 'incompatible';
	safe_check( $case . ' observed state', $store->setup_state(), $expected );
	$before = json_encode( array( $conn->present, $conn->requests, $conn->claims ) ); $conn->queries = array(); $result = safe_thrown( function () use ( $store ) { $store->install(); } );
	safe_check( $case . ' installation outcome', $result, 'initializable' === $expected ? 'none' : 'storage_unavailable' );
	if ( 'incompatible' === $expected ) {
		safe_check( $case . ' tables/data preserved', json_encode( array( $conn->present, $conn->requests, $conn->claims ) ), $before );
		safe_check( $case . ' no DDL', count( array_filter( $conn->queries, function ( $q ) { return 0 === strpos( $q[0], 'CREATE ' ); } ) ), 0 );
	}
}
list( $store, $conn ) = safe_reset(); $conn->queries = array(); $store->install();
safe_check( 'both present readiness only no DDL', count( array_filter( $conn->queries, function ( $q ) { return 0 === strpos( $q[0], 'CREATE ' ); } ) ), 0 );

list( $store, $conn, $service ) = safe_reset(); $row = safe_submit( $service ); $_GET = array( 'wcj_returns_view' => 'synthetic', 'request_id' => $row['id'] );
$html = safe_html( $safe_module );
safe_check( 'actual merchant subview labeled', false !== strpos( $html, 'Synthetic Returns workflow exercise' ), true );
safe_check( 'actual subview retains history', false !== strpos( $html, 'pending' ) && false !== strpos( $html, 'Synthetic workflow exercise' ), true );
safe_check( 'actual subview three distinct decisions', substr_count( $html, 'value="wcj_returns_synthetic_transition"' ), 3 );
safe_check( 'actual subview no live order forms', strpos( $html, 'value="wcj_returns_transition"' ), false );
safe_check( 'actual subview no dispatch form', strpos( $html, 'value="wcj_returns_dispatch"' ), false );
safe_check( 'actual subview before any native reads', $GLOBALS['safe']['facts_reads'], 0 );
safe_check( 'actual subview no Woo order URL', strpos( $html, 'view-order' ), false );
safe_check( 'actual subview no catalogue diagnosis', strpos( $html, 'wcj-returns-policy' ), false );
$_GET = array( 'wcj_returns_view' => 'synthetic' ); $html = safe_html( $safe_module );
safe_check( 'actual private create form', substr_count( $html, 'value="wcj_returns_synthetic_create"' ), 1 );
safe_check( 'actual private form literal default quantity', false !== strpos( $html, 'value="1" required' ), true );
$_POST = array( 'quantity' => '1', 'token' => safe_token( 2 ), '_wpnonce' => 'valid:wcj_returns_synthetic_create' );
safe_check( 'actual create controller redirects', safe_thrown( function () use ( $safe_module ) { $safe_module->synthetic_create(); } ), 'controlled_redirect' );
safe_check( 'actual redirect is fixed synthetic admin page', $GLOBALS['safe']['redirect'], 'https://synthetic.invalid/wp-admin/admin.php?page=wcj-returns&wcj_returns_view=synthetic&request_id=2&wcj_returns_result=saved' );
safe_check( 'actual controller token absent from URL', strpos( $GLOBALS['safe']['redirect'], safe_token( 2 ) ), false );
$_POST = array( 'request_id' => '2', 'revision' => '1', 'status' => 'declined', '_wpnonce' => 'valid:wcj_returns_synthetic_transition_2' );
safe_check( 'actual decision controller redirects', safe_thrown( function () use ( $safe_module ) { $safe_module->synthetic_transition(); } ), 'controlled_redirect' );
safe_check( 'actual controller retained terminal status', $conn->requests[2]['status'], 'declined' );
safe_check( 'actual controller still zero native reads', $GLOBALS['safe']['facts_reads'], 0 );
safe_check( 'actual separate action registrations', array_keys( array_intersect_key( $GLOBALS['safe']['actions'], array_flip( array( 'admin_post_wcj_returns_synthetic_create', 'admin_post_wcj_returns_synthetic_transition', 'admin_post_wcj_returns_synthetic_recover' ) ) ) ), array( 'admin_post_wcj_returns_synthetic_create', 'admin_post_wcj_returns_synthetic_transition', 'admin_post_wcj_returns_synthetic_recover' ) );
safe_finish();
