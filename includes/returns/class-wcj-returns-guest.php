<?php
/**
 * Short, order-bound guest capability for the existing Returns claims engine.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wcj-returns-clock.php';

/** Email possession and a browser cookie are authentication; a nonce is not. */
class WCJ_Returns_Guest {
	/**
	 * Private owned storage.
	 *
	 * @var WCJ_Returns_Store Private owned storage.
	 */
	private $store;
	/**
	 * Fresh edit-context identity.
	 *
	 * @var WCJ_Returns_Facts Fresh edit-context identity.
	 */
	private $facts;
	/**
	 * UTC epoch provider, captured only outside transactions.
	 *
	 * @var callable UTC epoch provider, captured only outside transactions.
	 */
	private $clock;
	/**
	 * Separately proven context, never caller-set actor authority.
	 *
	 * @var array|null Separately proven context, never caller-set actor authority.
	 */
	private $capability;
	/**
	 * Request-local header admission only, never bearer or actor authority.
	 *
	 * @var array|null Configured page and blog admitted before output.
	 */
	private static $view_receipt;

	/**
	 * Constructor performs no SQL, hook, transport or scheduler action.
	 *
	 * @param WCJ_Returns_Store|null $store Shared private store.
	 * @param WCJ_Returns_Facts|null $facts Fresh identity reader.
	 * @param callable|null          $clock Offline epoch provider.
	 */
	public function __construct( $store = null, $facts = null, $clock = null ) {
		$this->store = $store ? $store : new WCJ_Returns_Store();
		$this->facts = $facts ? $facts : new WCJ_Returns_Facts();
		$this->clock = $clock ? $clock : 'time';
	}

	/** Register only through the existing enabled, role-filtered Returns module. */
	public function register() {
		add_shortcode( 'wcj_returns_guest', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'uncache' ) );
		foreach ( array( 'challenge', 'verify', 'submit', 'cancel' ) as $operation ) {
			add_action( 'admin_post_wcj_returns_guest_' . $operation, array( $this, $operation . '_handler' ) );
			add_action( 'admin_post_nopriv_wcj_returns_guest_' . $operation, array( $this, $operation . '_handler' ) );
		}
		add_action( 'admin_post_wcj_returns_guest_storage', array( $this, 'storage_handler' ) );
		add_action( 'wcj_returns_guest_send_challenge', array( $this, 'send_challenge' ) );
	}

	/**
	 * Current direct configured roots only. No filterable or database salt fallback.
	 *
	 * @return array Fixed-purpose subkeys and rotation tag.
	 */
	public static function keys() {
		$names  = array( 'AUTH_KEY', 'AUTH_SALT', 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT', 'LOGGED_IN_KEY', 'LOGGED_IN_SALT', 'NONCE_KEY', 'NONCE_SALT' );
		$values = array();
		foreach ( $names as $name ) {
			if ( defined( $name ) ) {
				$values[ $name ] = constant( $name );
			}
		}
		$default = __( 'put your unique phrase here', 'default' );
		foreach ( array( 'AUTH_KEY', 'AUTH_SALT' ) as $name ) {
			$value = $values[ $name ] ?? null;
			if ( ! is_string( $value ) || strlen( $value ) < 64 || 'put your unique phrase here' === $value || $default === $value ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			foreach ( $values as $other => $candidate ) {
				if ( $other !== $name && $candidate === $value ) {
					throw new RuntimeException( 'auth_unavailable' );
				}
			}
		}
		$blog = get_current_blog_id();
		if ( ! is_int( $blog ) || $blog < 1 ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$keys = array( 'blog' => $blog );
		foreach ( array( 'proof', 'rate', 'csrf', 'key_tag' ) as $purpose ) {
			$keys[ $purpose ] = hash_hmac( 'sha256', 'wcj_return_guest|v1|' . $purpose . '|' . $blog, $values['AUTH_KEY'] . "\0" . $values['AUTH_SALT'], 'key_tag' !== $purpose );
		}
		return $keys;
	}

	/**
	 * Normalize an origin, ignoring paths, never forwarded request headers.
	 *
	 * @param string $url Server-configured URL or Origin header.
	 * @return string Canonical HTTPS origin or empty.
	 */
	private static function origin( $url ) {
		$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		return 'https://' . strtolower( $parts['host'] ) . ( isset( $parts['port'] ) && 443 !== $parts['port'] ? ':' . $parts['port'] : '' );
	}

	/**
	 * Published same-origin HTTPS page, with no implicit page creation.
	 *
	 * @return array Page ID and trusted URL.
	 */
	private function page() {
		$id = get_option( 'wcj_returns_guest_page', '' );
		if ( ( ! is_string( $id ) && ! is_int( $id ) ) || ! preg_match( '/^[1-9][0-9]{0,17}$/D', (string) $id ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$post = get_post( (int) $id );
		$url  = get_permalink( (int) $id );
		$site = self::origin( home_url( '/' ) );
		if ( ! $post || 'page' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password || ! has_shortcode( $post->post_content, 'wcj_returns_guest' ) || '' === $site || self::origin( $url ) !== $site ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return array(
			'id'     => (int) $id,
			'url'    => $url,
			'origin' => $site,
		);
	}

	/**
	 * Phase A admission. Every external API runs before the owned transaction.
	 *
	 * @return array Captured keys, epoch, monotonic start and trusted destination.
	 */
	private function ready() {
		$started = WCJ_Returns_Clock::read();
		if ( 'yes' !== get_option( 'wcj_returns_enabled', 'no' ) || ! wcj_is_module_enabled( 'returns' ) || 'yes' !== get_option( 'wcj_returns_guest', 'no' ) || ! function_exists( 'as_enqueue_async_action' ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$phase          = self::keys();
		$phase['page']  = $this->page();
		$phase['start'] = $started;
		$phase['now']   = call_user_func( $this->clock );
		if ( ! is_int( $phase['now'] ) || $phase['now'] < 1 ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$this->store->require_auth_ready();
		self::epoch( $phase );
		return $phase;
	}

	/** Browser admission is stricter than the private pending-mail callback. */
	private function gate() {
		$started = WCJ_Returns_Clock::read();
		if ( ! is_ssl() || headers_sent() ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$phase          = $this->ready();
		$phase['start'] = $started;
		self::epoch( $phase );
		return $phase;
	}

	/**
	 * Read-only page admission after theme output, with current readiness checks.
	 * Only this controller's early no-store receipt can survive sent headers.
	 *
	 * @return array Captured external facts, not guest identity.
	 * @throws RuntimeException On unsafe response or unavailable current page.
	 * @throws Throwable When current readiness or freshness cannot be established.
	 */
	private function view_gate() {
		$started = WCJ_Returns_Clock::read();
		try {
			if ( ! is_ssl() || is_preview() ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			$phase = $this->ready();
			if ( ! is_page( $phase['page']['id'] ) ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			if ( ! headers_sent() ) {
				// Admit actual no-store headers, not just an unrelated cache constant.
				$this->uncache();
			}
			$receipt = array(
				'page' => $phase['page']['id'],
				'blog' => $phase['blog'],
			);
			if ( ! defined( 'DONOTCACHEPAGE' ) || ! DONOTCACHEPAGE || $receipt !== self::$view_receipt ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			$phase['start'] = $started;
			self::epoch( $phase );
			return $phase;
		} catch ( Throwable $error ) {
			self::$view_receipt = null;
			throw $error;
		}
	}

	/**
	 * Expiry clock advances over lock waits. Never calls an external clock in TX.
	 *
	 * @param array $phase Captured Phase A.
	 * @return int UTC epoch plus elapsed whole seconds.
	 */
	private static function epoch( $phase ) {
		$elapsed = WCJ_Returns_Clock::read() - $phase['start'];
		if ( $elapsed < 0 || $elapsed > 5000000000 ) {
			throw new RuntimeException( 'facts_stale' );
		}
		return $phase['now'] + intdiv( $elapsed, 1000000000 );
	}

	/**
	 * Per-blog Host-prefixed cookie name; cookie values never enter a URL.
	 *
	 * @param string $kind gc or gg.
	 * @param int    $blog Captured blog ID.
	 * @return string Cookie name.
	 */
	private static function cookie_name( $kind, $blog ) {
		return '__Host-wcj_return_' . $kind . '_' . $blog;
	}

	/**
	 * Canonical full entropy cookie only, not an order key or owner digest.
	 *
	 * @param mixed $cookie Cookie value.
	 * @return string Lookup digest.
	 */
	private static function cookie_key( $cookie ) {
		if ( ! is_string( $cookie ) || ! preg_match( '/^[a-f0-9]{64}$/D', $cookie ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return hash( 'sha256', $cookie );
	}

	/**
	 * Bind a guest CSRF nonce to operation/order and the held cookie digest.
	 *
	 * @param string $operation Fixed operation.
	 * @param int    $order Server-resolved order ID, zero for verification.
	 * @param string $key Cookie lookup digest.
	 * @param array  $phase Captured subkeys.
	 * @return string Nonce action, never authentication.
	 */
	private static function action( $operation, $order, $key, $phase ) {
		return 'wcj_returns_guest_' . $operation . '_' . $order . '_' . substr( hash_hmac( 'sha256', $key, $phase['csrf'] ), 0, 32 );
	}

	/**
	 * Current WP nonce ticks1/2, separate from possession authentication.
	 *
	 * @param mixed  $nonce Submitted nonce.
	 * @param string $action Captured binding.
	 */
	private static function nonce( $nonce, $action ) {
		if ( ! is_string( $nonce ) || strlen( $nonce ) > 128 || ! in_array( wp_verify_nonce( $nonce, $action ), array( 1, 2 ), true ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
	}

	/**
	 * Exact peer address, IPv6 grouped by /64. No proxy header trust.
	 *
	 * @param mixed $address REMOTE_ADDR only.
	 * @return string Canonical nonpersisted subject.
	 */
	public static function peer( $address ) {
		$packed = is_string( $address ) && strlen( $address ) <= 45 && filter_var( $address, FILTER_VALIDATE_IP ) ? inet_pton( $address ) : false;
		if ( false === $packed ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		if ( 16 === strlen( $packed ) && ( str_repeat( "\0", 10 ) . "\xff\xff" ) === substr( $packed, 0, 12 ) ) {
			$packed = substr( $packed, 12 );
		}
		return 4 === strlen( $packed ) ? 'v4|' . bin2hex( $packed ) : 'v6|' . bin2hex( substr( $packed, 0, 8 ) );
	}

	/**
	 * Capture one fixed-window rate specification before SQL.
	 *
	 * @param array  $phase Captured keys and epoch.
	 * @param string $scope Fixed scope.
	 * @param string $subject Nonpersisted subject.
	 * @param int    $seconds Window.
	 * @param int    $limit Ceiling.
	 * @return array Digest-only row identity.
	 */
	private static function rate_spec( $phase, $scope, $subject, $seconds, $limit ) {
		$bucket = intdiv( $phase['now'], $seconds );
		return array(
			'key'    => hash_hmac( 'sha256', $scope . '|' . $subject . '|' . $bucket, $phase['rate'] ),
			'expiry' => gmdate( 'Y-m-d H:i:s', ( $bucket + 1 ) * $seconds ),
			'limit'  => $limit,
		);
	}

	/**
	 * Charge fixed counters under anchor and sorted rate-row locks.
	 *
	 * @param array  $anchor Locked anchor by reference.
	 * @param array  $specs Precomputed rate rows.
	 * @param array  $phase Captured external facts.
	 * @param string $counter cg, vg or empty for matched subject only.
	 * @return bool Admission, denied counters still commit.
	 */
	private function charge( &$anchor, $specs, $phase, $counter = '' ) {
		if ( '' !== $counter ) {
			$limit  = 'cg' === $counter ? 500 : 1000;
			$bucket = intdiv( $phase['now'], 900 );
			$data   = $anchor['data'];
			if ( $data[ $counter ][0] !== $bucket ) {
				$data[ $counter ] = array( $bucket, 0 );
			}
			if ( $data[ $counter ][1] >= $limit ) {
				return false;
			}
			++$data[ $counter ][1];
			$anchor = $this->store->auth_save( $anchor, $data );
		}
		usort(
			$specs,
			function ( $left, $right ) {
				return strcmp( $left['key'], $right['key'] );
			}
		);
		foreach ( $specs as $spec ) {
			$row = $this->store->auth_get( $spec['key'], true );
			if ( $row ) {
				if ( 'rate' !== $row['kind'] || $row['data']['n'] >= $spec['limit'] ) {
					return false;
				}
				$data = $row['data'];
				++$data['n'];
				$this->store->auth_save( $row, $data );
			} else {
				$this->store->auth_insert(
					$anchor,
					array(
						'row_key'     => $spec['key'],
						'kind'        => 'rate',
						'order_id'    => 0,
						'expires_gmt' => $spec['expiry'],
						'payload'     => json_encode(
							array(
								'v' => 1,
								'n' => 1,
							)
						),
					)
				);
			}
		}
		return true;
	}

	/**
	 * Check identity equality only after possession is separately proven.
	 *
	 * @param array  $identity Fresh light/full facts.
	 * @param string $owner Immutable digest.
	 */
	private static function identity( $identity, $owner ) {
		if ( 0 !== $identity['owner'] || empty( $identity['guest']['valid'] ) || ! hash_equals( $identity['guest']['digest'], $owner ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
	}

	/**
	 * Current-key revocation and monotonic expiry before every bearer use.
	 *
	 * @param array|null $row Strict row.
	 * @param string     $kind challenge or grant.
	 * @param array      $phase Current captured keys/clock.
	 */
	private static function valid_row( $row, $kind, $phase ) {
		if ( ! $row || $kind !== $row['kind'] || ! hash_equals( $phase['key_tag'], $row['data']['key_tag'] ) || $row['data']['issued'] > self::epoch( $phase ) || $row['expires_gmt'] <= gmdate( 'Y-m-d H:i:s', self::epoch( $phase ) ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
	}

	/**
	 * Translate UTC expiry, subtracting one full second of epoch-floor uncertainty.
	 * Deny when no positive safe interval remains. No wall-clock atomicity claim.
	 *
	 * @param array $row   Strict challenge or grant.
	 * @param array $phase Captured epoch and monotonic start.
	 * @return int Conservatively tightened monotonic expiry.
	 * @throws RuntimeException On short interval, excessive TTL or integer overflow.
	 */
	private static function deadline( $row, $phase ) {
		$expiry    = ( new DateTimeImmutable( $row['expires_gmt'], new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		$remaining = $expiry - $phase['now'];
		if ( $remaining <= 1 || $remaining > 900 ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$interval = ( $remaining - 1 ) * 1000000000;
		if ( $phase['start'] > PHP_INT_MAX - $interval ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return $phase['start'] + $interval;
	}

	/**
	 * Rebuild one email proof, without raw cookie, separate seed or proof hash.
	 *
	 * @param array $row Current strict challenge row.
	 * @param array $phase Current proof key and blog.
	 * @return string Sensitive emailed possession factor, never logged.
	 */
	private static function proof( $row, $phase ) {
		$expiry = ( new DateTimeImmutable( $row['expires_gmt'], new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		return hash_hmac( 'sha256', 'proof|v1|' . $phase['blog'] . '|' . $row['row_key'] . '|' . $row['order_id'] . '|' . $row['data']['owner'] . '|' . $expiry, $phase['proof'] );
	}

	/**
	 * Admit a challenge request without revealing match or transport status.
	 *
	 * @param mixed $reference Bounded order reference.
	 * @param mixed $email Bounded submitted email.
	 * @param mixed $nonce Current guest nonce.
	 * @param mixed $old_cookie Previous challenge cookie, not authorization.
	 * @return array Cookie instructions only after known commit, or empty denial.
	 */
	public function challenge( $reference, $email, $nonce, $old_cookie = '' ) {
		try {
			$phase = $this->gate();
			self::nonce( $nonce, 'wcj_returns_guest_challenge' );
			if ( isset( $_SERVER['HTTP_ORIGIN'] ) && ( ! is_string( $_SERVER['HTTP_ORIGIN'] ) || rtrim( $_SERVER['HTTP_ORIGIN'], '/' ) !== $phase['page']['origin'] ) ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			if ( isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) && 'same-origin' !== $_SERVER['HTTP_SEC_FETCH_SITE'] ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			$peer  = self::rate_spec( $phase, 'challenge_peer', self::peer( $_SERVER['REMOTE_ADDR'] ?? '' ), 900, 10 );
			$allow = $this->store->transaction(
				function () use ( $phase, $peer ) {
					$anchor = $this->store->auth_anchor( self::epoch( $phase ) );
					return $this->charge( $anchor, array( $peer ), $phase, 'cg' );
				},
				$phase['start']
			);
			if ( ! $allow ) {
				return array();
			}
			$cookie = bin2hex( random_bytes( 32 ) );
			$key    = self::cookie_key( $cookie );
			$match  = null;
			if ( is_string( $reference ) && strlen( $reference ) > 0 && strlen( $reference ) <= 64 && sanitize_text_field( $reference ) === $reference && is_string( $email ) && strlen( $email ) <= 254 ) {
				$id = apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $reference );
				if ( ( is_string( $id ) || is_int( $id ) ) && preg_match( '/^[1-9][0-9]{0,17}$/D', (string) $id ) ) {
					try {
						$identity = $this->facts->guest_identity( (int) $id );
						if ( 0 === $identity['owner'] && $identity['guest']['valid'] && strtolower( $email ) === strtolower( $identity['guest']['email'] ) ) {
							$match = $identity;
						}
					} catch ( Throwable $error ) {
						// Missing/unsupported identity is indistinguishable from mismatch.
					}
				}
			}
			$created = false;
			if ( $match ) {
				$specs = array( self::rate_spec( $phase, 'order_email_900', $match['guest']['digest'], 900, 3 ), self::rate_spec( $phase, 'order_email_86400', $match['guest']['digest'], 86400, 10 ) );
				try {
					$created = $this->store->transaction(
						function () use ( $phase, $specs, $match, $key, $old_cookie ) {
							$anchor = $this->store->auth_anchor( self::epoch( $phase ) );
							if ( ! $this->charge( $anchor, $specs, $phase ) ) {
									return false;
							}
							if ( is_string( $old_cookie ) && preg_match( '/^[a-f0-9]{64}$/D', $old_cookie ) ) {
								$old = $this->store->auth_get( self::cookie_key( $old_cookie ), true );
								if ( $old && 'challenge' === $old['kind'] && hash_equals( $phase['key_tag'], $old['data']['key_tag'] ) ) {
									$this->store->auth_delete( $anchor, $old['row_key'] );
								}
							}
							$now = self::epoch( $phase );
							$this->store->auth_insert(
								$anchor,
								array(
									'row_key'     => $key,
									'kind'        => 'challenge',
									'order_id'    => $match['id'],
									'expires_gmt' => gmdate( 'Y-m-d H:i:s', $now + 600 ),
									'payload'     => json_encode(
										array(
											'v'        => 1,
											'owner'    => $match['guest']['digest'],
											'issued'   => $now,
											'attempts' => 0,
											'delivery' => 'pending',
											'key_tag'  => $phase['key_tag'],
										)
									),
								)
							);
							return true;
						},
						$phase['start']
					);
				} catch ( RuntimeException $error ) {
					if ( 'auth_capacity' !== $error->getMessage() ) {
						throw $error;
					}
					// Known rollback at capacity: still issue the same nonauthorizing
					// random challenge cookie as an admitted unmatched request.
				}
			}
			if ( $created ) {
				try {
					as_enqueue_async_action( 'wcj_returns_guest_send_challenge', array( $key ), 'wcj-returns-guest' );
				} catch ( Throwable $error ) {
					// Unknown scheduling never permits replay; a new request is recovery.
				}
			}
			return array(
				'kind'    => 'gc',
				'cookie'  => $cookie,
				'seconds' => 600,
				'blog'    => $phase['blog'],
			);
		} catch ( Throwable $error ) {
			return array();
		}
	}

	/**
	 * Atomically consume cookie plus emailed proof and issue one short grant.
	 *
	 * @param mixed $cookie Per-challenge cookie.
	 * @param mixed $code Emailed sensitive possession factor.
	 * @param mixed $nonce Cookie-bound nonce.
	 * @return array Cookie instructions only after a known commit.
	 */
	public function verify( $cookie, $code, $nonce ) {
		try {
			$phase = $this->gate();
			$key   = self::cookie_key( $cookie );
			self::nonce( $nonce, self::action( 'verify', 0, $key, $phase ) );
			$peer  = self::rate_spec( $phase, 'verify_peer', self::peer( $_SERVER['REMOTE_ADDR'] ?? '' ), 900, 30 );
			$allow = $this->store->transaction(
				function () use ( $phase, $peer ) {
					$anchor = $this->store->auth_anchor( self::epoch( $phase ) );
					return $this->charge( $anchor, array( $peer ), $phase, 'vg' );
				},
				$phase['start']
			);
			if ( ! $allow ) {
				return array(); }

			$row      = $this->store->auth_get( $key );
			$identity = null;
			try {
				self::valid_row( $row, 'challenge', $phase );
				$identity = $this->facts->guest_identity( (int) $row['order_id'] );
				self::identity( $identity, $row['data']['owner'] );
			} catch ( Throwable $error ) {
				$identity = null;
			}
			if ( ! $identity ) {
				return array(); }
			$deadline     = self::deadline( $row, $phase );
			$grant_cookie = bin2hex( random_bytes( 32 ) );
			$grant_key    = self::cookie_key( $grant_cookie );
			$granted      = $this->store->transaction(
				function () use ( $phase, $key, $code, $identity, $grant_key ) {
					$anchor  = $this->store->auth_anchor( self::epoch( $phase ) );
					$current = $this->store->auth_get( $key, true );
					if ( ! $identity ) {
							return false;
					}
					self::valid_row( $current, 'challenge', $phase );
					if ( (int) $current['order_id'] !== $identity['id'] || 'pending' === $current['data']['delivery'] ) {
						return false;
					}
					self::identity( $identity, $current['data']['owner'] );
					$expected = self::proof( $current, $phase );
					if ( ! is_string( $code ) || ! preg_match( '/^[a-f0-9]{64}$/D', $code ) || ! hash_equals( $expected, $code ) ) {
						$data = $current['data'];
						++$data['attempts'];
						if ( 5 === $data['attempts'] ) {
							$this->store->auth_delete( $anchor, $key );
						} else {
							$this->store->auth_save( $current, $data );
						}
						return false;
					}
					$now = self::epoch( $phase );
					$this->store->auth_delete( $anchor, $key );
					$this->store->auth_insert(
						$anchor,
						array(
							'row_key'     => $grant_key,
							'kind'        => 'grant',
							'order_id'    => $identity['id'],
							'expires_gmt' => gmdate( 'Y-m-d H:i:s', $now + 900 ),
							'payload'     => json_encode(
								array(
									'v'       => 1,
									'owner'   => $current['data']['owner'],
									'issued'  => $now,
									'writes'  => 0,
									'key_tag' => $phase['key_tag'],
								)
							),
						)
					);
					return true;
				},
				$phase['start'],
				$deadline
			);
			return $granted ? array(
				'kind'            => 'gg',
				'cookie'          => $grant_cookie,
				'seconds'         => 900,
				'blog'            => $phase['blog'],
				'clear_challenge' => true,
			) : array();
		} catch ( Throwable $error ) {
			return array();
		}
	}

	/**
	 * Prepare a separate proven guest capability, never a staff/actor-zero flag.
	 *
	 * @param mixed  $cookie Held grant cookie.
	 * @param string $operation view, submit or cancel.
	 * @param mixed  $nonce Current nonce for writes.
	 * @return array Captured verified bearer and identity.
	 */
	public function context( $cookie, $operation = 'view', $nonce = null ) {
		$this->capability = null;
		$phase            = 'view' === $operation ? $this->view_gate() : $this->gate();
		$key              = self::cookie_key( $cookie );
		$row              = $this->store->auth_get( $key );
		self::valid_row( $row, 'grant', $phase );
		$order = (int) $row['order_id'];
		if ( ! in_array( $operation, array( 'view', 'submit', 'cancel' ), true ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$action = self::action( $operation, $order, $key, $phase );
		if ( 'view' !== $operation ) {
			self::nonce( $nonce, $action );
		}
		$identity = $this->facts->guest_identity( $order );
		self::identity( $identity, $row['data']['owner'] );
		$this->capability = array(
			'phase'  => $phase,
			'key'    => $key,
			'order'  => $order,
			'owner'  => $row['data']['owner'],
			'action' => $action,
			'until'  => self::deadline( $row, $phase ),
		);
		return $this->capability;
	}

	/** Require the proven private context to remain valid before rendering. */
	public function recheck() {
		if ( ! $this->capability || WCJ_Returns_Clock::read() >= $this->capability['until'] ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		self::epoch( $this->capability['phase'] );
	}

	/**
	 * Render fixed nonce actions from a separately proven server context.
	 * These are CSRF bindings, never a replacement for the held grant.
	 *
	 * @param array $context Server-built capability context.
	 * @return array Two finite nonce actions.
	 */
	public function actions( $context ) {
		return array(
			'submit' => self::action( 'submit', $context['order'], $context['key'], $context['phase'] ),
			'cancel' => self::action( 'cancel', $context['order'], $context['key'], $context['phase'] ),
		);
	}

	/**
	 * Charge one bounded write before reloading business facts; no claims touched.
	 *
	 * @param mixed  $cookie Held grant cookie.
	 * @param string $operation submit or cancel.
	 * @param mixed  $nonce Current operation-bound nonce.
	 */
	public function charge_write( $cookie, $operation, $nonce ) {
		$context = $this->context( $cookie, $operation, $nonce );
		$this->store->transaction(
			function () use ( $context ) {
				$row = $this->lock();
				if ( $row['data']['writes'] >= 20 ) {
						throw new RuntimeException( 'auth_unavailable' );
				}
				$data = $row['data'];
				++$data['writes'];
				$this->store->auth_save( $row, $data );
			},
			$context['phase']['start'],
			$context['until']
		);
	}

	/**
	 * Revalidate the actual locked grant before replay or any business write.
	 * All external auth/keys/options/facts were captured outside this TX.
	 *
	 * @return array Locked strict grant.
	 */
	public function lock() {
		$context = $this->capability;
		if ( ! $context ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		$row = $this->store->auth_get( $context['key'], true );
		self::valid_row( $row, 'grant', $context['phase'] );
		if ( (int) $row['order_id'] !== $context['order'] || ! hash_equals( $row['data']['owner'], $context['owner'] ) ) {
			throw new RuntimeException( 'auth_unavailable' );
		}
		return $row;
	}

	/**
	 * One async mail attempt, no automatic retry or replay after uncertain outcome.
	 *
	 * @param mixed $key Opaque challenge row key only.
	 */
	public function send_challenge( $key ) {
		try {
			$phase = $this->ready();
			$row   = $this->store->auth_get( $key );
			self::valid_row( $row, 'challenge', $phase );
			if ( 'pending' !== $row['data']['delivery'] ) {
				return;
			}
			$identity = $this->facts->guest_identity( (int) $row['order_id'] );
			self::identity( $identity, $row['data']['owner'] );
			$deadline = self::deadline( $row, $phase );
			$code     = self::proof( $row, $phase );
			$subject  = __( 'Verify your return-request access', 'woocommerce-jetpack' );
			/* translators: 1: Short-lived verification code, 2: Configured HTTPS page. */
			$body   = '<p>' . esc_html( sprintf( __( 'Enter this code in the browser where you requested it: %1$s. It expires in 10 minutes. Verification page: %2$s', 'woocommerce-jetpack' ), $code, $phase['page']['url'] ) ) . '</p>';
			$mailer = WC()->mailer();
			if ( ! is_object( $mailer ) || ! is_callable( array( $mailer, 'send' ) ) ) {
				return;
			}
			$this->store->transaction(
				function () use ( $phase, $key, $row ) {
					$current = $this->store->auth_get( $key, true );
					self::valid_row( $current, 'challenge', $phase );
					if ( 'pending' !== $current['data']['delivery'] || (int) $current['order_id'] !== (int) $row['order_id'] || ! hash_equals( $current['data']['owner'], $row['data']['owner'] ) ) {
							throw new RuntimeException( 'auth_unavailable' );
					}
					$data             = $current['data'];
					$data['delivery'] = 'sending';
					$this->store->auth_save( $current, $data );
				},
				$phase['start'],
				$deadline
			);
			// Known sending commit first. Generic false/throw is never retryable.
			self::epoch( $phase );
			try {
				if ( WCJ_Returns_Clock::read() >= $deadline ) {
					return;
				}
				$state = true === $mailer->send( $identity['guest']['email'], $subject, $body ) ? 'accepted' : 'ambiguous';
			} catch ( Throwable $error ) {
				$state = 'ambiguous';
			}
			$final    = $this->ready();
			$deadline = self::deadline( $row, $final );
			$this->store->transaction(
				function () use ( $final, $key, $state ) {
					$current = $this->store->auth_get( $key, true );
					self::valid_row( $current, 'challenge', $final );
					if ( 'sending' !== $current['data']['delivery'] ) {
							return;
					}
					$data             = $current['data'];
					$data['delivery'] = $state;
					$this->store->auth_save( $current, $data );
				},
				$final['start'],
				$deadline
			);
		} catch ( Throwable $error ) {
			// No transport on failed admission/CAS/unknown commit. A sent message
			// with lost finalization stays sending and cannot be resent.
		}
	}

	/** Configured HTTPS page only, before output. GET never sets a cookie. */
	public function uncache() {
		self::$view_receipt = null;
		try {
			if ( ! is_ssl() || headers_sent() || is_preview() ) {
				return;
			}
			$page = $this->page();
			$blog = get_current_blog_id();
			if ( ! is_int( $blog ) || $blog < 1 || ! is_page( $page['id'] ) || ( defined( 'DONOTCACHEPAGE' ) && ! DONOTCACHEPAGE ) ) {
				return;
			}
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
			if ( ! headers_sent() ) {
				header( 'Cache-Control: no-store, private', true );
				self::$view_receipt = array(
					'page' => $page['id'],
					'blog' => $blog,
				);
			}
		} catch ( Throwable $error ) {
			self::$view_receipt = null;
			// Invalid/unconfigured page has no private rendering fallback.
		}
	}

	/**
	 * One bounded POST scalar, no submitted recipient/destination/actor.
	 *
	 * @param string $name Field.
	 * @return string Submitted scalar or empty.
	 */
	private function posted( $name ) {
		return isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) && strlen( $_POST[ $name ] ) <= 4096 ? wp_unslash( $_POST[ $name ] ) : '';
	}

	/**
	 * Read only a bounded named cookie; account handlers never call this.
	 *
	 * @param string $kind gc or gg.
	 * @return string Cookie or empty.
	 */
	private function cookie( $kind ) {
		$name = self::cookie_name( $kind, get_current_blog_id() );
		return isset( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ) && strlen( $_COOKIE[ $name ] ) <= 64 ? $_COOKIE[ $name ] : '';
	}

	/**
	 * Known-commit cookie only, then one generic, server-built redirect.
	 *
	 * @param array $result Internal cookie instruction, never request inputs.
	 */
	private function finish( $result = array() ) {
		$url = home_url( '/' );
		try {
			$url = $this->page()['url'];
			if ( $result && is_ssl() && ! headers_sent() ) {
				$options = array(
					'expires'  => time() + $result['seconds'],
					'path'     => '/',
					'secure'   => true,
					'httponly' => true,
					'samesite' => 'Lax',
				);
				setcookie( self::cookie_name( $result['kind'], $result['blog'] ), $result['cookie'], $options );
				if ( ! empty( $result['clear_challenge'] ) ) {
					$options['expires'] = time() - 3600;
					setcookie( self::cookie_name( 'gc', $result['blog'] ), '', $options );
				}
			}
		} catch ( Throwable $error ) {
			// No untrusted redirect and no cookie on invalid destination admission.
		}
		wp_safe_redirect( add_query_arg( 'wcj_returns_guest_result', 'check', $url ) );
		exit;
	}

	/** Generic handler, also registered for logged-in callers without privilege use. */
	public function challenge_handler() {
		$this->finish( $this->challenge( $this->posted( 'reference' ), $this->posted( 'email' ), $this->posted( '_wpnonce' ), $this->cookie( 'gc' ) ) );
	}

	/** Verification response never emits a proof or bearer in its body/URL. */
	public function verify_handler() {
		$this->finish( $this->verify( $this->cookie( 'gc' ), $this->posted( 'code' ), $this->posted( '_wpnonce' ) ) );
	}

	/** Existing quantity engine receives only a separately verified guest cookie. */
	public function submit_handler() {
		$selected = isset( $_POST['quantities'] ) && is_array( $_POST['quantities'] ) && count( $_POST['quantities'] ) <= 200 ? wp_unslash( $_POST['quantities'] ) : array();
		$selected = array_filter(
			$selected,
			function ( $value ) {
				return '' !== $value;
			}
		);
		( new WCJ_Returns_Service( $this->store, $this->facts, $this->clock ) )->guest_submit( $this->cookie( 'gg' ), $selected, $this->posted( 'reason' ), $this->posted( 'token' ), $this->posted( '_wpnonce' ) );
		$this->finish();
	}

	/** A proven guest can cancel only their bound pending request. */
	public function cancel_handler() {
		$id       = $this->posted( 'request_id' );
		$revision = $this->posted( 'revision' );
		if ( preg_match( '/^[1-9][0-9]{0,17}$/D', $id ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $revision ) ) {
			( new WCJ_Returns_Service( $this->store, $this->facts, $this->clock ) )->guest_cancel( $this->cookie( 'gg' ), (int) $id, (int) $revision, $this->posted( '_wpnonce' ) );
		}
		$this->finish();
	}

	/** Explicit separate auth setup/cleanup, never a public or ordinary GET write. */
	public function storage_handler() {
		$code = 'unavailable';
		try {
			if ( ! wcj_is_module_enabled( 'returns' ) || 'yes' !== get_option( 'wcj_returns_guest', 'no' ) || ! is_ssl() || headers_sent() ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
			self::keys();
			$this->store->install_auth( $this->posted( '_wpnonce' ), call_user_func( $this->clock ) );
			$code = 'saved';
		} catch ( Throwable $error ) {
			// Merchant sees fixed feedback, never SQL/key data or automatic repair.
		}
		wp_safe_redirect( add_query_arg( 'wcj_returns_result', $code, admin_url( 'admin.php?page=wcj-returns' ) ) );
		exit;
	}

	/**
	 * Only the configured uncached page renders public proof or private forms.
	 *
	 * @return string Escaped bounded HTML, never raw authentication data.
	 */
	public function render() {
		try {
			$phase = $this->view_gate();
			if ( ! is_page( $phase['page']['id'] ) || ! defined( 'DONOTCACHEPAGE' ) || ! DONOTCACHEPAGE ) {
				throw new RuntimeException( 'auth_unavailable' );
			}
		} catch ( Throwable $error ) {
			return '<p>' . esc_html__( 'Guest return-request access is unavailable. Please contact the store.', 'woocommerce-jetpack' ) . '</p>';
		}
		ob_start();
		echo '<section><h2>' . esc_html__( 'Guest Return Requests', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'If the details can be verified, check your email and enter the code in this browser. A request or approval does not issue a refund or restock products.', 'woocommerce-jetpack' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_guest_challenge">';
		wp_nonce_field( 'wcj_returns_guest_challenge' );
		echo '<p><label>' . esc_html__( 'Order reference', 'woocommerce-jetpack' ) . '<input name="reference" maxlength="64" required></label></p><p><label>' . esc_html__( 'Billing email', 'woocommerce-jetpack' ) . '<input type="email" name="email" maxlength="254" required></label></p><button>' . esc_html__( 'Request a verification code', 'woocommerce-jetpack' ) . '</button></form>';
		$challenge = $this->cookie( 'gc' );
		if ( preg_match( '/^[a-f0-9]{64}$/D', $challenge ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_guest_verify">';
			wp_nonce_field( self::action( 'verify', 0, self::cookie_key( $challenge ), $phase ) );
			echo '<p><label>' . esc_html__( 'Verification code', 'woocommerce-jetpack' ) . '<input name="code" maxlength="64" autocomplete="off" required></label></p><button>' . esc_html__( 'Verify access', 'woocommerce-jetpack' ) . '</button></form>';
		}
		$data = ( new WCJ_Returns_Service( $this->store, $this->facts, $this->clock ) )->guest_view( $this->cookie( 'gg' ), $this->before() );
		if ( ! is_wp_error( $data ) ) {
			$this->private_forms( $data, $phase );
		}
		echo '</section>';
		return ob_get_clean();
	}

	/** Read one strict optional history cursor, never a submitted order ID. */
	private function before() {
		$value = $_GET['wcj_returns_guest_before'] ?? '';
		return is_string( $value ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $value ) ? (int) $value : PHP_INT_MAX;
	}

	/**
	 * Render only capability-authorized rows, sharing actual service calculations.
	 *
	 * @param array $data Private verified view.
	 * @param array $phase Trusted configured destination.
	 */
	private function private_forms( $data, $phase ) {
		if ( $data['request_limit'] ) {
			echo '<p>' . esc_html__( 'This order has reached the limit of 100 retained return requests. Please contact the store.', 'woocommerce-jetpack' ) . '</p>';
		}
		if ( $data['needs_review'] || $data['policy_invalid'] ) {
			echo '<p>' . esc_html__( 'New requests are unavailable. Existing history and eligible cancellation remain available. Please contact the store for changed order details.', 'woocommerce-jetpack' ) . '</p>';
		}
		foreach ( $data['rows'] as $row ) {
			$payload = $this->store->payload( $row );
			echo '<h3>' . esc_html( '#' . $row['id'] . ': ' . $row['status'] ) . '</h3><ul>';
			foreach ( $payload['items'] as $id => $units ) {
				echo '<li>' . esc_html( '#' . $id . ': ' . WCJ_Returns_Quantity::display( $units ) ) . '</li>';
			}
			foreach ( $payload['history'] as $event ) {
				echo '<li>' . esc_html( $event['status'] . ': ' . gmdate( 'Y-m-d H:i:s', $event['time'] ) . ' UTC' ) . '</li>';
			}
			echo '</ul><p>' . esc_html( $payload['reason'] ) . '</p>';
			if ( 'pending' === $row['status'] ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_guest_cancel"><input type="hidden" name="request_id" value="' . esc_attr( $row['id'] ) . '"><input type="hidden" name="revision" value="' . esc_attr( $row['revision'] ) . '">';
				wp_nonce_field( $data['cancel_action'] );
				echo '<button>' . esc_html__( 'Cancel request', 'woocommerce-jetpack' ) . '</button></form>';
			}
		}
		if ( $data['has_more'] ) {
			echo '<a href="' . esc_url( add_query_arg( 'wcj_returns_guest_before', $data['next_cursor'], $phase['page']['url'] ) ) . '">' . esc_html__( 'Older requests', 'woocommerce-jetpack' ) . '</a>';
		}
		if ( $data['eligible'] ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_returns_guest_submit"><input type="hidden" name="token" value="' . esc_attr( bin2hex( random_bytes( 32 ) ) ) . '">';
			wp_nonce_field( $data['submit_action'] );
			foreach ( $data['available'] as $id => $units ) {
				if ( $units > 0 ) {
					echo '<p><label>' . esc_html( $data['facts']['names'][ $id ] ) . ' (' . esc_html( WCJ_Returns_Quantity::display( $units ) ) . ')<input name="quantities[' . esc_attr( $id ) . ']" maxlength="14" inputmode="decimal" autocomplete="off"></label></p>';
				}
			}
			echo '<p><label>' . esc_html__( 'Reason (optional, maximum 500 characters)', 'woocommerce-jetpack' ) . '<textarea name="reason" maxlength="500"></textarea></label></p><button>' . esc_html__( 'Request a return', 'woocommerce-jetpack' ) . '</button></form>';
		}
	}
}
