<?php
/**
 * Offline guest capability tests around the actual production classes.
 * Existing account fixture is included unchanged and must pass first.
 * No native WordPress, SQL, HTTP, scheduler or transport is executed.
 *
 * @package Booster_For_WooCommerce
 */
if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	fwrite( STDERR, "Offline CLI only, use php -n.\n" );
	exit( 2 );
}
define( 'WCJ_RETURNS_GUEST_OFFLINE', true );
$wcj_guest_root = $argv[1] ?? '';
if ( ! is_file( $wcj_guest_root . '/tests/codex-9-0-returns.php' ) ) {
	exit( 2 );
}
define( 'AUTH_KEY', str_repeat( 'test-key-', 8 ) );
define( 'AUTH_SALT', str_repeat( 'test-salt-', 8 ) );
ob_start();
require $wcj_guest_root . '/tests/codex-9-0-returns.php';
$wcj_guest_account_output = ob_get_clean();
if ( 428 !== $GLOBALS['wcj_returns_test_count'] ) {
	fwrite( STDERR, "Unchanged account assertions were not all run.\n" );
	exit( 2 );
}
require_once $wcj_guest_root . '/includes/returns/class-wcj-returns-guest.php';

/** Fixed blog double. */
function get_current_blog_id() {
	wcj_returns_test_tx_hook_guard();
	return $GLOBALS['wcj_guest_blog'] ?? 1; }
/** Exact sanitization double. */
function sanitize_email( $email ) {
	wcj_returns_test_tx_hook_guard();
	return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : ''; }
/** Text double. */
function sanitize_text_field( $value ) {
	wcj_returns_test_tx_hook_guard();
	return trim( strip_tags( $value ) ); }
/** URL parser double. */
function wp_parse_url( $url ) {
	wcj_returns_test_tx_hook_guard();
	return parse_url( $url ); }
/** HTTPS admission double. */
function is_ssl() {
	wcj_returns_test_tx_hook_guard();
	return $GLOBALS['wcj_guest_ssl']; }
/** Preview context double, never a production admission flag. */
function is_preview() {
	return $GLOBALS['wcj_guest_preview'] ?? false;
}
/** Trusted home double. */
function home_url( $path = '' ) {
	wcj_returns_test_tx_hook_guard();
	return 'https://store.example.invalid' . $path; }
/** Published page double. */
function get_post( $id ) {
	wcj_returns_test_tx_hook_guard();
	return 88 === $id ? (object) array(
		'post_type'     => 'page',
		'post_status'   => 'publish',
		'post_password' => '',
		'post_content'  => '[wcj_returns_guest]',
	) : null; }
/** Trusted page URL double. */
function get_permalink( $id ) {
	wcj_returns_test_tx_hook_guard();
	return 88 === $id ? 'https://store.example.invalid/returns/' : ''; }
/** Shortcode presence double. */
function has_shortcode( $text, $tag ) {
	wcj_returns_test_tx_hook_guard();
	return false !== strpos( $text, '[' . $tag . ']' ); }
/** Configured page context double. */
function is_page( $id ) {
	wcj_returns_test_tx_hook_guard();
	return 88 === $id && $GLOBALS['wcj_guest_page_context']; }
/** No transport/scheduler: capture opaque arguments only. */
function as_enqueue_async_action( $hook, $args, $group ) {
	wcj_returns_test_tx_hook_guard();
	$GLOBALS['wcj_guest_jobs'][] = array( $hook, $args, $group );
	return count( $GLOBALS['wcj_guest_jobs'] ); }
/** Shortcode registry double. */
function add_shortcode( $tag, $callback ) {
	wcj_returns_test_tx_hook_guard();
	$GLOBALS['wcj_guest_shortcodes'][ $tag ] = $callback; }
/** Header helper double. */
function nocache_headers() {
	wcj_returns_test_tx_hook_guard(); }

/** Memory storage for service tests, not a native schema/locking qualification. */
class WCJ_Guest_Test_Store extends WCJ_Returns_Test_Store {
	public $auth       = array();
	public $auth_locks = array();
	public function __construct() {
		$key                = self::auth_anchor_key();
		$this->auth[ $key ] = array(
			'row_key'     => $key,
			'kind'        => 'anchor',
			'order_id'    => 0,
			'expires_gmt' => '9999-12-31 23:59:59',
			'payload'     => json_encode(
				array(
					'v'    => 1,
					'rows' => 1,
					'cg'   => array( 0, 0 ),
					'vg'   => array( 0, 0 ),
				)
			),
		);
	}
	public function transaction( $operation, $started = null, $expiry = null ) {
		$snapshot = $this->auth;
		try {
			if ( null !== $expiry && hrtime( true ) >= $expiry ) {
				throw new RuntimeException( 'auth_expired' ); }
			return parent::transaction( $operation, $started, $expiry );
		} catch ( Throwable $error ) {
			if ( 'commit_unknown' !== $error->getMessage() ) {
				$this->auth = $snapshot;
			} throw $error; }
	}
	public function require_auth_ready() {
		$row = $this->auth_get( self::auth_anchor_key() );
		if ( ! $row ) {
			throw new RuntimeException( 'auth_unavailable' );
		} return $row; }
	public function auth_get( $key, $lock = false ) {
		if ( $lock && ! $this->active ) {
			throw new RuntimeException( 'transaction_required' ); }
		if ( $lock ) {
			$this->auth_locks[] = $key; }
		$row = $this->auth[ $key ] ?? null;
		if ( $row ) {
			$row['data'] = $this->auth_payload( $row ); }
		return $row;
	}
	public function auth_save( $row, $data ) {
		if ( ! $this->active || $this->auth[ $row['row_key'] ]['payload'] !== $row['payload'] ) {
			throw new RuntimeException( 'auth_conflict' ); }
		$row['payload']                = json_encode( $data );
		$row['data']                   = $this->auth_payload( $row );
		$this->auth[ $row['row_key'] ] = $row;
		return $row;
	}
	public function auth_anchor( $now ) {
		$anchor = $this->auth_get( self::auth_anchor_key(), true );
		if ( ! $anchor ) {
			throw new RuntimeException( 'auth_unavailable' ); }
		$deleted = 0;
		foreach ( $this->auth as $key => $row ) {
			if ( 50 === $deleted ) {
				break; }
			if ( 'anchor' !== $row['kind'] && $row['expires_gmt'] < gmdate( 'Y-m-d H:i:s', $now ) ) {
				unset( $this->auth[ $key ] );
				++$deleted; }
		}
		$data          = $anchor['data'];
		$data['rows'] -= $deleted;
		return $this->auth_save( $anchor, $data );
	}
	public function auth_insert( &$anchor, $row ) {
		$this->auth_payload( $row );
		if ( ! $this->active || isset( $this->auth[ $row['row_key'] ] ) || $anchor['data']['rows'] >= 10000 ) {
			throw new RuntimeException( 'auth_capacity' ); }
		$this->auth[ $row['row_key'] ] = $row;
		$data                          = $anchor['data'];
		++$data['rows'];
		$anchor = $this->auth_save( $anchor, $data );
	}
	public function auth_delete( &$anchor, $key ) {
		if ( ! $this->active || self::auth_anchor_key() === $key ) {
			throw new RuntimeException( 'auth_invalid' ); }
		$data = $anchor['data'];
		if ( isset( $this->auth[ $key ] ) ) {
			unset( $this->auth[ $key ] );
			--$data['rows']; }
		$anchor = $this->auth_save( $anchor, $data );
	}
	public function guest_listing( $order, $before = PHP_INT_MAX ) {
		$rows = array_filter(
			$this->rows,
			function ( $row ) use ( $order, $before ) {
				return 0 === $row['actor_id'] && $order === $row['order_id'] && $row['id'] < $before;
			}
		);
		krsort( $rows );
		return array_slice( array_values( $rows ), 0, 50 );
	}
}
/** Fresh identity double around unchanged business facts. */
class WCJ_Guest_Test_Facts extends WCJ_Returns_Test_Facts {
	public $identity_loads = 0;
	public function guest_identity( $id ) {
		wcj_returns_test_tx_hook_guard();
		++$this->identity_loads;
		if ( 10 !== $id ) {
			throw new RuntimeException( 'order_unavailable' ); }
		return array(
			'id'    => 10,
			'owner' => $this->value['owner'],
			'guest' => $this->value['guest'],
		);
	}
}
/** Reset fixed synthetic state. */
function wcj_guest_scenario() {
	wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_actor']                             = 0;
	$GLOBALS['wcj_returns_test_options']['wcj_returns_guest']      = 'yes';
	$GLOBALS['wcj_returns_test_options']['wcj_returns_guest_page'] = '88';
	$GLOBALS['wcj_guest_ssl']                                      = true;
	$GLOBALS['wcj_guest_page_context']                             = true;
	$GLOBALS['wcj_guest_jobs']                                     = array();
	$_SERVER['REMOTE_ADDR']                                        = '192.0.2.1';
	$_SERVER['HTTP_ORIGIN']                                        = 'https://store.example.invalid';
	$_SERVER['HTTP_SEC_FETCH_SITE']                                = 'same-origin';
	$store                                  = new WCJ_Guest_Test_Store();
	$facts                                  = new WCJ_Guest_Test_Facts();
	$facts->value                           = $GLOBALS['wcj_returns_test_test_facts']->value;
	$facts->value['owner']                  = 0;
	$facts->value['guest']                  = array(
		'digest' => str_repeat( 'b', 64 ),
		'email'  => 'guest@example.invalid',
		'valid'  => true,
	);
	$GLOBALS['wcj_returns_test_test_store'] = $store;
	$GLOBALS['wcj_returns_test_test_facts'] = $facts;
	$clock                                  = function () {
		return $GLOBALS['wcj_returns_test_now'];
	};
	return array( $store, $facts, new WCJ_Returns_Guest( $store, $facts, $clock ), new WCJ_Returns_Service( $store, $facts, $clock ) );
}
/** Cookie-bound nonce action computed literally from the accepted contract. */
function wcj_guest_nonce( $op, $cookie, $order = 0 ) {
	$key  = hash( 'sha256', $cookie );
	$keys = WCJ_Returns_Guest::keys();
	return wcj_returns_test_nonce( 'wcj_returns_guest_' . $op . '_' . $order . '_' . substr( hash_hmac( 'sha256', $key, $keys['csrf'] ), 0, 32 ) );
}
/** Seed only private grant state for deterministic business entry tests. */
function wcj_guest_grant( $store, $owner = null, $expiry = 900 ) {
	$cookie = str_repeat( 'd', 64 );
	$key    = hash( 'sha256', $cookie );
	$now    = $GLOBALS['wcj_returns_test_now'];
	$keys   = WCJ_Returns_Guest::keys();
	$store->transaction(
		function () use ( $store, $owner, $expiry, $key, $now, $keys ) {
			$anchor = $store->auth_anchor( $now );
			$store->auth_insert(
				$anchor,
				array(
					'row_key'     => $key,
					'kind'        => 'grant',
					'order_id'    => 10,
					'expires_gmt' => gmdate( 'Y-m-d H:i:s', $now + $expiry ),
					'payload'     => json_encode(
						array(
							'v'       => 1,
							'owner'   => $owner ?: str_repeat( 'b', 64 ),
							'issued'  => $now + $expiry - 900,
							'writes'  => 0,
							'key_tag' => $keys['key_tag'],
						)
					),
				)
			);
		}
	);
	return $cookie;
}
/** Collect failures so the same frozen tests can run before implementation. */
function wcj_guest_check( $name, $actual, $expected ) {
	++$GLOBALS['wcj_guest_count'];
	$ok = $actual === $expected;
	if ( ! $ok ) {
		++$GLOBALS['wcj_guest_fail']; }
	$GLOBALS['wcj_guest_output'][] = array(
		'name'     => $name,
		'pass'     => $ok,
		'actual'   => $actual,
		'expected' => $expected,
	);
}
/** Absent first-slice methods are a literal red, not a fixture fatal. */
function wcj_guest_call( $service, $method, ...$args ) {
	return is_callable( array( $service, $method ) ) ? $service->$method( ...$args ) : new WP_Error( 'missing_guest_entry' );
}
/** Run the finite guest suite with local variables, not WordPress globals. */
function wcj_guest_run() {
	global $wcj_guest_root;
	ob_start();
	$GLOBALS['wcj_guest_count']              = 0;
	$GLOBALS['wcj_guest_fail']               = 0;
	$GLOBALS['wcj_guest_output']             = array();
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	$token                                   = wcj_returns_test_token( 1 );
	$submitted                               = wcj_guest_call( $service, 'guest_submit', $cookie, array( 11 => '1' ), 'Guest reason', $token, wcj_guest_nonce( 'submit', $cookie, 10 ) );
	wcj_guest_check( 'proven guest submits through actual shared engine', is_array( $submitted ) ? $submitted['status'] : wcj_returns_test_code( $submitted ), 'pending' );
	wcj_guest_check( 'guest is actor zero, never current login', is_array( $submitted ) ? $submitted['actor_id'] : null, 0 );
	wcj_guest_check( 'one exact quantity claim', $store->claims['10:11']['reserved'] ?? null, 1000000 );
	$replay = wcj_guest_call( $service, 'guest_submit', $cookie, array( 11 => '1' ), 'Guest reason', $token, wcj_guest_nonce( 'submit', $cookie, 10 ) );
	wcj_guest_check( 'authenticated identical replay returns original', is_array( $replay ) ? $replay['id'] : wcj_returns_test_code( $replay ), 1 );
	$mismatch = wcj_guest_call( $service, 'guest_submit', $cookie, array( 11 => '2' ), 'Guest reason', $token, wcj_guest_nonce( 'submit', $cookie, 10 ) );
	wcj_guest_check( 'same token different input denies', wcj_returns_test_code( $mismatch ), 'idempotency_conflict' );
	$view = wcj_guest_call( $service, 'guest_view', $cookie );
	wcj_guest_check( 'proven guest sees exact private history', is_array( $view ) ? count( $view['rows'] ) : wcj_returns_test_code( $view ), 1 );
	$cancel = wcj_guest_call( $service, 'guest_cancel', $cookie, 1, 1, wcj_guest_nonce( 'cancel', $cookie, 10 ) );
	wcj_guest_check( 'proven guest cancels own pending request', is_array( $cancel ) ? $cancel['status'] : wcj_returns_test_code( $cancel ), 'cancelled' );
	wcj_guest_check( 'cancellation releases exact private quantity', $store->claims['10:11']['reserved'] ?? null, 0 );
	wcj_guest_check( 'account actor zero remains forbidden', wcj_returns_test_code( $service->customer_view( 10 ) ), 'forbidden' );
	// Literal protocol and authorization cases. All data is synthetic and memory-only.
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$keys                                    = WCJ_Returns_Guest::keys();
	wcj_guest_check( 'direct-root tag has distinct blog purpose', $keys['key_tag'], hash_hmac( 'sha256', 'wcj_return_guest|v1|key_tag|1', AUTH_KEY . "\0" . AUTH_SALT ) );
	wcj_guest_check( 'proof and csrf purpose keys differ', $keys['proof'] !== $keys['csrf'], true );
	wcj_guest_check( 'IPv6 changing low bits shares /64', WCJ_Returns_Guest::peer( '2001:db8:abcd:1234::1' ), WCJ_Returns_Guest::peer( '2001:db8:abcd:1234:ffff:ffff:ffff:ffff' ) );
	wcj_guest_check( 'IPv6 changing prefix does not share /64', WCJ_Returns_Guest::peer( '2001:db8:abcd:1234::1' ) !== WCJ_Returns_Guest::peer( '2001:db8:abcd:1235::1' ), true );
	wcj_guest_check( 'IPv4 is whole address', WCJ_Returns_Guest::peer( '192.0.2.1' ), 'v4|c0000201' );
	wcj_guest_check(
		'peer rejects malformed address',
		wcj_returns_test_thrown(
			function () {
				WCJ_Returns_Guest::peer( '127.0.0.1,192.0.2.1' );
			}
		),
		'auth_unavailable'
	);
	wcj_guest_check( 'stable owner exact encoding', WCJ_Returns_Facts::guest_digest( 10, 'Guest@example.invalid', 'synthetic-order-key' ), hash( 'sha256', "wcj_return_guest_owner_v1\n" . json_encode( array( 1, 10, 'guest@example.invalid', 'synthetic-order-key' ) ) ) );
	wcj_guest_check( 'owner digest changes with stored order key', WCJ_Returns_Facts::guest_digest( 10, 'Guest@example.invalid', 'one' ) !== WCJ_Returns_Facts::guest_digest( 10, 'Guest@example.invalid', 'two' ), true );
	wcj_guest_check( 'invalid root-free digest input remains deterministic', strlen( WCJ_Returns_Facts::guest_digest( 10, null, false ) ), 64 );

	$matched   = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$row_key   = hash( 'sha256', $matched['cookie'] ?? '' );
	$challenge = $store->auth_get( $row_key );
	wcj_guest_check( 'challenge cookie has 256 random bits as hex', isset( $matched['cookie'] ) && 1 === preg_match( '/^[a-f0-9]{64}$/D', $matched['cookie'] ), true );
	wcj_guest_check( 'challenge fixed TTL and per-blog kind', array( $matched['kind'] ?? '', $matched['seconds'] ?? 0, $matched['blog'] ?? 0 ), array( 'gc', 600, 1 ) );
	wcj_guest_check( 'one opaque async action only', $GLOBALS['wcj_guest_jobs'], array( array( 'wcj_returns_guest_send_challenge', array( $row_key ), 'wcj-returns-guest' ) ) );
	wcj_guest_check( 'challenge persists no raw email cookie proof or key', isset( $challenge['payload'] ) && false === strpos( $challenge['payload'], 'guest@example.invalid' ) && false === strpos( $challenge['payload'], $matched['cookie'] ) && false === strpos( $challenge['payload'], AUTH_KEY ), true );
	$unknown = $guest->challenge( '999', 'unknown@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	wcj_guest_check( 'unmatched admitted request same instruction keys', array_keys( $unknown ), array_keys( $matched ) );
	wcj_guest_check( 'unmatched admitted request same kind TTL blog', array( $unknown['kind'], $unknown['seconds'], $unknown['blog'] ), array( 'gc', 600, 1 ) );
	wcj_guest_check( 'unmatched causes no extra job', count( $GLOBALS['wcj_guest_jobs'] ), 1 );
	wcj_guest_check( 'both admitted attempts charged globally', $store->require_auth_ready()['data']['cg'][1], 2 );
	wcj_guest_check( 'pending cannot grant even with literal correct code', $guest->verify( $matched['cookie'], hash_hmac( 'sha256', 'proof|v1|1|' . $row_key . '|10|' . str_repeat( 'b', 64 ) . '|' . ( $GLOBALS['wcj_returns_test_now'] + 600 ), $keys['proof'] ), wcj_guest_nonce( 'verify', $matched['cookie'] ) ), array() );
	wcj_guest_check( 'pending proof rejection does not increment wrong-code attempts', $store->auth_get( $row_key )['data']['attempts'], 0 );
	$GLOBALS['wcj_guest_ssl'] = false;
	$guest->send_challenge( $row_key );
	wcj_guest_check( 'internal scheduler callback works without browser SSL context', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	wcj_guest_check( 'mail recipient freshly stored billing only', $GLOBALS['wcj_returns_test_messages'][0][0], 'guest@example.invalid' );
	wcj_guest_check( 'one known mail result accepted', $store->auth_get( $row_key )['data']['delivery'], 'accepted' );
	$guest->send_challenge( $row_key );
	wcj_guest_check( 'duplicate accepted job never resends', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	wcj_guest_check( 'browser challenge denied without SSL', $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) ), array() );
	wcj_guest_check( 'browser proof denied without SSL', $guest->verify( $matched['cookie'], str_repeat( 'a', 64 ), wcj_guest_nonce( 'verify', $matched['cookie'] ) ), array() );
	$GLOBALS['wcj_guest_ssl'] = true;
	$proof                    = hash_hmac( 'sha256', 'proof|v1|1|' . $row_key . '|10|' . str_repeat( 'b', 64 ) . '|' . ( $GLOBALS['wcj_returns_test_now'] + 600 ), $keys['proof'] );
	wcj_guest_check( 'exact derived proof appears in synthetic email', false !== strpos( $GLOBALS['wcj_returns_test_messages'][0][2], $proof ), true );
	wcj_guest_check( 'bad verify nonce does not issue grant', $guest->verify( $matched['cookie'], $proof, 'wrong' ), array() );
	$grant = $guest->verify( $matched['cookie'], $proof, wcj_guest_nonce( 'verify', $matched['cookie'] ) );
	wcj_guest_check( 'valid proof plus cookie issues short grant', array( $grant['kind'] ?? '', $grant['seconds'] ?? 0, $grant['clear_challenge'] ?? false ), array( 'gg', 900, true ) );
	wcj_guest_check( 'successful challenge consumed atomically', $store->auth_get( $row_key ), null );
	wcj_guest_check( 'used challenge cannot issue second grant', $guest->verify( $matched['cookie'], $proof, wcj_guest_nonce( 'verify', $matched['cookie'] ) ), array() );
	wcj_guest_check( 'code alone in another browser is not proof', $guest->verify( str_repeat( 'f', 64 ), $proof, wcj_guest_nonce( 'verify', str_repeat( 'f', 64 ) ) ), array() );
	wcj_guest_check( 'grant persisted current rotation tag', $store->auth_get( hash( 'sha256', $grant['cookie'] ) )['data']['key_tag'], $keys['key_tag'] );

	foreach ( array( 'false', 'throw', 'unknown' ) as $transport ) {
		list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
		$result                                  = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
		$key                                     = hash( 'sha256', $result['cookie'] );
		$GLOBALS['wcj_returns_test_mail_result'] = 'false' === $transport ? false : true;
		$GLOBALS['wcj_returns_test_mail_throw']  = 'throw' === $transport;
		if ( 'unknown' === $transport ) {
			$GLOBALS['wcj_returns_test_mail_callback'] = function () use ( $store ) {
				$store->unknown = true;
			}; }
		$guest->send_challenge( $key );
		$guest->send_challenge( $key );
		wcj_guest_check( 'transport ' . $transport . ' attempted once, no resend', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
		wcj_guest_check( 'transport ' . $transport . ' never returns pending', $store->auth_get( $key )['data']['delivery'], 'unknown' === $transport ? 'accepted' : 'ambiguous' );
	}

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$result                                  = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$key                                     = hash( 'sha256', $result['cookie'] );
	$guest->send_challenge( $key );
	for ( $i = 1; $i <= 5; ++$i ) {
		wcj_guest_check( 'wrong code ' . $i . ' no grant', $guest->verify( $result['cookie'], str_repeat( '0', 64 ), wcj_guest_nonce( 'verify', $result['cookie'] ) ), array() );
		wcj_guest_check( 'wrong code ' . $i . ' exact attempt count or consumed', $store->auth_get( $key ) ? $store->auth_get( $key )['data']['attempts'] : null, 5 === $i ? null : $i );
	}

	foreach ( array( 'off', 'http', 'page', 'role', 'origin', 'fetch', 'nonce', 'anchor' ) as $denial ) {
		list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
		if ( 'off' === $denial ) {
			$GLOBALS['wcj_returns_test_options']['wcj_returns_guest'] = 'no'; }
		if ( 'http' === $denial ) {
			$GLOBALS['wcj_guest_ssl'] = false; }
		if ( 'page' === $denial ) {
			$GLOBALS['wcj_returns_test_options']['wcj_returns_guest_page'] = '99'; }
		if ( 'role' === $denial ) {
			$GLOBALS['wcj_returns_test_options']['wcj_returns_enabled'] = 'no'; }
		if ( 'origin' === $denial ) {
			$_SERVER['HTTP_ORIGIN'] = 'https://other.example.invalid'; }
		if ( 'fetch' === $denial ) {
			$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site'; }
		if ( 'anchor' === $denial ) {
			unset( $store->auth[ WCJ_Returns_Store::auth_anchor_key() ] ); }
		$nonce = 'nonce' === $denial ? 'wrong' : wcj_returns_test_nonce( 'wcj_returns_guest_challenge' );
		wcj_guest_check( 'challenge gate ' . $denial . ' cookie denied', $guest->challenge( '10', 'guest@example.invalid', $nonce ), array() );
		wcj_guest_check( 'challenge gate ' . $denial . ' no job', count( $GLOBALS['wcj_guest_jobs'] ), 0 );
		wcj_guest_check( 'challenge gate ' . $denial . ' no business rows', count( $store->rows ), 0 );
	}

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	for ( $i = 1; $i <= 11; ++$i ) {
		$result = $guest->challenge( 'not-an-order', 'nobody@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
		wcj_guest_check( 'unmatched peer bounded attempt ' . $i, ! empty( $result ), $i <= 10 );
	}
	wcj_guest_check( 'pre-match denied peer still charges global', $store->require_auth_ready()['data']['cg'][1], 11 );
	wcj_guest_check( 'unmatched global abuse never resolves an order', $facts->identity_loads, 0 );
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	for ( $i = 1; $i <= 4; ++$i ) {
		$result = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
		wcj_guest_check( 'matched subject limit generic cookie ' . $i, ! empty( $result ), true );
	}
	wcj_guest_check( 'matched subject fourth creates no job', count( $GLOBALS['wcj_guest_jobs'] ), 3 );
	wcj_guest_check( 'matched subject fourth charges global', $store->require_auth_ready()['data']['cg'][1], 4 );

	foreach ( array( 'challenge', 'grant' ) as $kind ) {
		list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
		if ( 'challenge' === $kind ) {
			$result = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
			$cookie = $result['cookie']; } else {
			$cookie = wcj_guest_grant( $store ); }
			$key             = hash( 'sha256', $cookie );
			$row             = $store->auth_get( $key );
			$data            = $row['data'];
			$data['key_tag'] = str_repeat( 'e', 64 );
			$store->transaction(
				function () use ( $store, $row, $data ) {
					$store->auth_save( $row, $data );
				}
			);
		if ( 'challenge' === $kind ) {
			$guest->send_challenge( $key );
			wcj_guest_check( 'root rotation revokes queued proof mail', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
			wcj_guest_check( 'root rotation revokes proof verification', $guest->verify( $cookie, str_repeat( 'a', 64 ), wcj_guest_nonce( 'verify', $cookie ) ), array() );
		} else {
			wcj_guest_check( 'root rotation revokes grant reads', is_wp_error( $service->guest_view( $cookie ) ), true );
			wcj_guest_check( 'root rotation revokes grant writes', is_wp_error( $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) ) ), true );
			wcj_guest_check( 'rotated grant creates no business claims', $store->claims, array() );
		}
	}

	foreach ( array( 'expired', 'owner', 'login', 'http', 'nonce' ) as $denial ) {
		list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
		$cookie                                  = wcj_guest_grant( $store, null, 'expired' === $denial ? 0 : 900 );
		$nonce                                   = wcj_guest_nonce( 'submit', $cookie, 10 );
		if ( 'owner' === $denial ) {
			$facts->value['guest']['digest'] = str_repeat( 'c', 64 ); }
		if ( 'login' === $denial ) {
			wcj_returns_test_manager(); }
		if ( 'http' === $denial ) {
			$GLOBALS['wcj_guest_ssl'] = false; }
		if ( 'nonce' === $denial ) {
			$nonce = wcj_guest_nonce( 'cancel', $cookie, 10 ); }
		wcj_guest_check( 'guest write ' . $denial . ' denied', is_wp_error( $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), $nonce ) ), true );
		wcj_guest_check( 'guest write ' . $denial . ' no claims', $store->claims, array() );
	}

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	wcj_returns_test_manager();
	$cookie = wcj_guest_grant( $store );
	$result = $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
	wcj_guest_check( 'logged-in manager still needs grant and remains guest actor', $result['actor_id'], 0 );
	wcj_guest_check( 'staff approval of fresh bound guest is supported', $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) )['status'], 'approved' );
	wcj_guest_check( 'approval retains claim, no automatic restock', $store->claims['10:11']['reserved'], 1000000 );
	$GLOBALS['wcj_returns_test_options']['wcj_returns_guest'] = 'no';
	wcj_guest_check( 'merchant queue retains actor-zero rows while guest disabled', count( $service->queue() ), 1 );

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	$row = $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
	wcj_returns_test_manager();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_guest'] = 'no';
	$sent = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_guest_check( 'guest status outbox works while guest verification disabled', is_array( $sent ), true );
	wcj_guest_check( 'guest status recipient is fresh billing email', $GLOBALS['wcj_returns_test_messages'][0][0] ?? '', 'guest@example.invalid' );
	$facts->value['guest']['digest'] = str_repeat( 'c', 64 );
	$blocked                         = $service->transition( 1, 3, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
	wcj_guest_check( 'changed guest identity blocks staff approval', wcj_returns_test_code( $blocked ), 'needs_review' );
	wcj_guest_check( 'changed guest identity retains claim', $store->claims['10:11']['reserved'], 1000000 );
	$blocked = $service->dispatch( 1, 'submitted_merchant', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_guest_check( 'changed identity blocks status dispatch before mail', wcj_returns_test_code( $blocked ), 'recipient_unavailable' );
	wcj_guest_check( 'changed identity causes no additional mail', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	wcj_guest_check( 'staff decline still releases stale guest claim', $service->transition( 1, 3, 'declined', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) )['status'], 'declined' );
	wcj_guest_check( 'staff decline exact release', $store->claims['10:11']['reserved'], 0 );

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	$row                                     = $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
	$data                                    = $store->payload( $row );
	$data['guest']['owner']                  = str_repeat( 'c', 64 );
	$store->rows[1]['payload']               = json_encode( $data );
	wcj_guest_check( 'replay across old owner digest returns conflict not old row', wcj_returns_test_code( $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) ) ), 'idempotency_conflict' );
	wcj_guest_check( 'old digest filtered from private history', count( $service->guest_view( $cookie )['rows'] ), 0 );
	wcj_guest_check( 'cannot cancel other guest digest', wcj_returns_test_code( $service->guest_cancel( $cookie, 1, 1, wcj_guest_nonce( 'cancel', $cookie, 10 ) ) ), 'forbidden' );
	wcj_guest_check( 'other digest remains reserved', $store->claims['10:11']['reserved'], 1000000 );

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	for ( $i = 1; $i <= 21; ++$i ) {
		$result = $service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
		wcj_guest_check( 'grant write budget counts replay ' . $i, is_array( $result ), $i <= 20 );
	}
	wcj_guest_check( 'grant exact twenty writes retained', $store->auth_get( hash( 'sha256', $cookie ) )['data']['writes'], 20 );
	wcj_guest_check( 'write-budget replay never duplicates quantity', $store->claims['10:11']['reserved'], 1000000 );

	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$store->unknown                          = true;
	wcj_guest_check( 'unknown challenge commit returns no cookie', $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) ), array() );
	wcj_guest_check( 'unknown challenge commit enqueues nothing', $GLOBALS['wcj_guest_jobs'], array() );
	wcj_guest_check( 'unknown challenge commit not automatically replayed', $store->require_auth_ready()['data']['cg'][1], 1 );

	// Actual handlers and shortcode: no native HTTP/header/cookie claim.
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	$service->guest_submit( $cookie, array( 11 => '1' ), 'Private guest history', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
	$_COOKIE = array( '__Host-wcj_return_gg_1' => $cookie );
	$guest->uncache();
	$html = $guest->render();
	wcj_guest_check( 'configured guest shortcode renders authorized history', false !== strpos( $html, 'Private guest history' ), true );
	wcj_guest_check( 'guest shortcode renders intake and cancellation', false !== strpos( $html, 'value="wcj_returns_guest_submit"' ) && false !== strpos( $html, 'value="wcj_returns_guest_cancel"' ), true );
	wcj_guest_check( 'guest shortcode does not echo bearer or email', false === strpos( $html, $cookie ) && false === strpos( $html, 'guest@example.invalid' ), true );
	$GLOBALS['wcj_guest_page_context'] = false;
	wcj_guest_check( 'shortcode outside configured page no private output', false === strpos( $guest->render(), 'Private guest history' ), true );
	$GLOBALS['wcj_guest_page_context'] = true;
	$GLOBALS['wcj_guest_ssl']          = false;
	wcj_guest_check( 'shortcode HTTP no private output', false === strpos( $guest->render(), 'Private guest history' ), true );
	$GLOBALS['wcj_guest_ssl'] = true;
	$_COOKIE                  = array( '__Host-wcj_return_gc_1' => $cookie );
	wcj_guest_check( 'challenge cookie is never a grant cookie', false === strpos( $guest->render(), 'Private guest history' ), true );
	$_COOKIE = array( '__Host-wcj_return_gg_1' => $cookie );
	$_POST   = array(
		'quantities' => array( 12 => '1' ),
		'reason'     => 'Handler guest history',
		'token'      => wcj_returns_test_token( 2 ),
		'_wpnonce'   => wcj_guest_nonce( 'submit', $cookie, 10 ),
	);
	wcj_guest_check(
		'real submit handler ends with safe fixed redirect',
		wcj_returns_test_thrown(
			function () use ( $guest ) {
				$guest->submit_handler();
			}
		),
		'wcj_returns_test_redirect_stop'
	);
	wcj_guest_check( 'real submit handler wrote exact shared-engine request', count( $store->rows ), 2 );
	wcj_guest_check( 'handler feedback has no bearer email proof or request input', $GLOBALS['wcj_returns_test_redirect'], 'https://store.example.invalid/returns/?wcj_returns_guest_result=check' );
	$_POST = array(
		'request_id' => '2',
		'revision'   => '1',
		'_wpnonce'   => wcj_guest_nonce( 'cancel', $cookie, 10 ),
	);
	wcj_guest_check(
		'real cancel handler ends with same fixed redirect',
		wcj_returns_test_thrown(
			function () use ( $guest ) {
				$guest->cancel_handler();
			}
		),
		'wcj_returns_test_redirect_stop'
	);
	wcj_guest_check( 'real cancel handler releases only its request', $store->rows[2]['status'], 'cancelled' );
	wcj_guest_check( 'real cancel handler exact private quantity release', $store->claims['10:12']['reserved'], 0 );
	// Fifty scanned rows with one matching owner: cursor remains last scanned.
	$row                     = $store->rows[1];
	$other                   = $store->payload( $row );
	$other['guest']['owner'] = str_repeat( 'c', 64 );
	$store->rows             = array();
	for ( $i = 1; $i <= 51; ++$i ) {
		$copy       = $row;
		$copy['id'] = $i;
		if ( 51 !== $i ) {
			$copy['payload'] = json_encode( $other );
		} $store->rows[ $i ] = $copy; }
	$view = $service->guest_view( $cookie );
	wcj_guest_check( 'guest keyset filters other owner before rendering', count( $view['rows'] ), 1 );
	wcj_guest_check( 'guest keyset next cursor is last SCANNED not shown', $view['next_cursor'], 2 );
	wcj_guest_check( 'guest fifty-scan has_more stays true even if one visible', $view['has_more'], true );
	wcj_guest_check( 'guest fifty-scan next page skips none', $service->guest_view( $cookie, 2 )['next_cursor'], 1 );
	// Invalid policy blocks only new intake, preserving proved history/cancel.
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$cookie                                  = wcj_guest_grant( $store );
	$service->guest_submit( $cookie, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $cookie, 10 ) );
	$GLOBALS['wcj_returns_test_options']['wcj_returns_window_days'] = 'invalid';
	$view = $service->guest_view( $cookie );
	wcj_guest_check( 'guest invalid policy preserves history', count( $view['rows'] ), 1 );
	wcj_guest_check( 'guest invalid policy blocks new form', $view['eligible'], false );
	wcj_guest_check( 'guest invalid policy preserves cancellation', $service->guest_cancel( $cookie, 1, 1, wcj_guest_nonce( 'cancel', $cookie, 10 ) )['status'], 'cancelled' );
	// Unknown sending commit is not permission to attempt transport.
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$result                                  = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$store->unknown                          = true;
	$guest->send_challenge( hash( 'sha256', $result['cookie'] ) );
	wcj_guest_check( 'unknown sending CAS commit makes no mail attempt', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	wcj_guest_check( 'unknown sending CAS remains nonresendable', $store->auth_get( hash( 'sha256', $result['cookie'] ) )['data']['delivery'], 'sending' );
	// Literal global ceilings, no large loop or full table mutation required.
	foreach ( array(
		'cg' => 500,
		'vg' => 1000,
	) as $counter => $limit ) {
		list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
		$anchor                                  = $store->require_auth_ready();
		$data                                    = $anchor['data'];
		$data[ $counter ]                        = array( intdiv( $GLOBALS['wcj_returns_test_now'], 900 ), $limit );
		$store->transaction(
			function () use ( $store, $anchor, $data ) {
				$store->auth_save( $anchor, $data );
			}
		);
		$result = 'cg' === $counter ? $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) ) : $guest->verify( str_repeat( 'a', 64 ), 'wrong', wcj_guest_nonce( 'verify', str_repeat( 'a', 64 ) ) );
		wcj_guest_check( 'global ' . $counter . ' ceiling denies without cookie', $result, array() );
		wcj_guest_check( 'global ' . $counter . ' ceiling denies before order facts', $facts->identity_loads, 0 );
	}

	// Matched full-capacity denial is not an order/email response-header oracle.
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$guest->challenge( 'missing', 'nobody@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$anchor_key = WCJ_Returns_Store::auth_anchor_key();
	for ( $i = 1; count( $store->auth ) < 10000; ++$i ) {
		$key                 = hash( 'sha256', 'full_' . $i );
		$store->auth[ $key ] = array(
			'row_key'     => $key,
			'kind'        => 'rate',
			'order_id'    => 0,
			'expires_gmt' => gmdate( 'Y-m-d H:i:s', $GLOBALS['wcj_returns_test_now'] + 900 ),
			'payload'     => '{"v":1,"n":1}',
		);
	}
	$data                                  = json_decode( $store->auth[ $anchor_key ]['payload'], true );
	$data['rows']                          = 10000;
	$store->auth[ $anchor_key ]['payload'] = json_encode( $data );
	$matched                               = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$unmatched                             = $guest->challenge( 'missing', 'nobody@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	wcj_guest_check( 'full-capacity matched and unmatched same cookie shape', array_keys( $matched ), array_keys( $unmatched ) );
	wcj_guest_check( 'full-capacity matched still generic challenge cookie', array( $matched['kind'] ?? '', $matched['seconds'] ?? 0 ), array( 'gc', 600 ) );
	wcj_guest_check( 'full-capacity rollback does not overflow count', $store->require_auth_ready()['data']['rows'], 10000 );
	wcj_guest_check( 'full-capacity creates no verification job', count( $GLOBALS['wcj_guest_jobs'] ), 0 );
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$GLOBALS['wcj_returns_test_now']         = intdiv( $GLOBALS['wcj_returns_test_now'], 86400 ) * 86400 + 60;
	for ( $i = 1; $i <= 11; ++$i ) {
		$guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
		$GLOBALS['wcj_returns_test_now'] += 901;
	}
	wcj_guest_check( 'matched daily subject limit exactly ten jobs', count( $GLOBALS['wcj_guest_jobs'] ), 10 );
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	for ( $i = 1; $i <= 31; ++$i ) {
		$guest->verify( str_repeat( 'a', 64 ), 'wrong', wcj_guest_nonce( 'verify', str_repeat( 'a', 64 ) ) ); }
	wcj_guest_check( 'invalid verify attempts charged before identity resolution', $store->require_auth_ready()['data']['vg'][1], 31 );
	$rates = array_values(
		array_filter(
			$store->auth,
			function ( $row ) {
				return 'rate' === $row['kind'];
			}
		)
	);
	wcj_guest_check( 'verify peer count stops at thirty', json_decode( $rates[0]['payload'], true )['n'], 30 );
	wcj_guest_check( 'invalid verify never loads Woo identity', $facts->identity_loads, 0 );
	list( $store, $facts, $guest, $service ) = wcj_guest_scenario();
	$result                                  = $guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
	$facts->value['guest']['digest']         = str_repeat( 'c', 64 );
	$guest->send_challenge( hash( 'sha256', $result['cookie'] ) );
	wcj_guest_check( 'pending challenge identity change blocks transport', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );

	require $wcj_guest_root . '/tests/fixtures/codex-returns-guest-sql.php';
	wcj_guest_sql_cases();
	wcj_guest_expiry_cases();

	ob_end_clean();
}
if ( defined( 'WCJ_RETURNS_GUEST_LIBRARY_ONLY' ) && WCJ_RETURNS_GUEST_LIBRARY_ONLY ) {
	return;
}
wcj_guest_run();

echo json_encode(
	array(
		'account_assertions' => 428,
		'assertions'         => $GLOBALS['wcj_guest_count'],
		'failures'           => $GLOBALS['wcj_guest_fail'],
		'cases'              => $GLOBALS['wcj_guest_output'],
	),
	JSON_PRETTY_PRINT
) . PHP_EOL;
exit( $GLOBALS['wcj_guest_fail'] ? 1 : 0 );
