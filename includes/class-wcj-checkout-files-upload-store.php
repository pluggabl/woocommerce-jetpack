<?php
/**
 * Module-specific encrypted storage for native checkout uploads.
 *
 * @package Booster_For_WooCommerce
 * @since 9.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Serialized private catalog. No order metadata or cache is a commit witness. */
class WCJ_Checkout_Files_Upload_Store {
	/**
	 * Canonical private directory.
	 *
	 * @var string
	 */
	private $root;
	/**
	 * Encryption key, never sent to the browser.
	 *
	 * @var string
	 */
	private $key;
	/**
	 * Canonical installation-private base directory.
	 *
	 * @var string
	 */
	private $base;
	/**
	 * Captured WordPress blog domain.
	 *
	 * @var string
	 */
	private $domain;

	/**
	 * Require an explicitly configured directory outside all known public roots.
	 * Ciphertext remains authenticated/encrypted even if server aliases are wrong.
	 * Native filesystem isolation and crash durability still need deployment QA.
	 *
	 * @throws RuntimeException When storage cannot be admitted.
	 */
	public function __construct() {
		if ( ! defined( 'WCJ_CHECKOUT_FILES_PRIVATE_DIR' ) || ! is_string( WCJ_CHECKOUT_FILES_PRIVATE_DIR ) || ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'openssl_decrypt' ) ) {
			throw new RuntimeException( 'private_storage_unavailable' );
		}
		$given = rtrim( WCJ_CHECKOUT_FILES_PRIVATE_DIR, '/\\' );
		$root  = realpath( $given );
		if ( false === $root || is_link( $given ) || ! is_dir( $root ) || ! is_writable( $root ) || strtolower( str_replace( '\\', '/', $given ) ) !== strtolower( str_replace( '\\', '/', $root ) ) ) {
			throw new RuntimeException( 'private_storage_unavailable' );
		}
		$public = array( ABSPATH );
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$public[] = WP_CONTENT_DIR;
		}
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$public[] = $_SERVER['DOCUMENT_ROOT'];
		}
		foreach ( $public as $path ) {
			$path = realpath( $path );
			if ( false !== $path ) {
				$prefix = strtolower( rtrim( str_replace( '\\', '/', $path ), '/' ) );
				$check  = strtolower( str_replace( '\\', '/', $root ) );
				if ( $prefix === $check || 0 === strpos( $check, $prefix . '/' ) ) {
					throw new RuntimeException( 'private_storage_is_public' );
				}
			}
		}
		if ( '\\' !== DIRECTORY_SEPARATOR && 0 !== ( fileperms( $root ) & 0077 ) ) {
			throw new RuntimeException( 'private_storage_permissions' );
		}
		$this->base   = $root;
		$this->domain = self::current_domain();
		$this->assert_domain();
		$scoped = $root . '/' . $this->domain;
		if ( ! is_dir( $scoped ) && ! mkdir( $scoped, 0700 ) && ! is_dir( $scoped ) ) {
			throw new RuntimeException( 'private_domain_unavailable' );
		}
		$canonical = realpath( $scoped );
		if ( is_link( $scoped ) || false === $canonical || strtolower( str_replace( '\\', '/', $canonical ) ) !== strtolower( str_replace( '\\', '/', $scoped ) ) || ! is_writable( $scoped ) || ( '\\' !== DIRECTORY_SEPARATOR && 0 !== ( fileperms( $scoped ) & 0077 ) ) ) {
			throw new RuntimeException( 'private_domain_unavailable' );
		}
		$this->root = $canonical;
		$this->key  = hash_hmac( 'sha256', 'wcj-checkout-files-upload/v2/' . $this->domain, wp_salt( 'auth' ), true );
	}

	/**
	 * Bind the private catalog to the current WordPress site.
	 *
	 * @return string Validated site domain.
	 * @throws RuntimeException When no positive site identity is available.
	 */
	public static function current_domain() {
		$blog = get_current_blog_id();
		if ( ! is_int( $blog ) || $blog < 1 ) {
			throw new RuntimeException( 'private_domain_unavailable' );
		}
		return 'blog-' . $blog;
	}

	/**
	 * Reject switched-site reuse and ambiguous legacy catalogs without migration.
	 *
	 * @throws RuntimeException When site binding or legacy admission fails.
	 */
	private function assert_domain() {
		if ( self::current_domain() !== $this->domain ) {
			throw new RuntimeException( 'private_domain_changed' );
		}
		$legacy = $this->base . '/checkout-upload.catalog';
		if ( file_exists( $legacy ) || is_link( $legacy ) ) {
			throw new RuntimeException( 'private_legacy_domain_held' );
		}
	}

	/**
	 * Execute one module transaction under a real, non-reclaimed OS lock.
	 *
	 * @param callable $callback Receives catalog by reference and this store.
	 * @param bool     $write Commit changed catalog before retirement.
	 * @return mixed
	 * @throws RuntimeException On lock/read/commit uncertainty. Files are retained.
	 */
	public function transact( $callback, $write = false ) {
		$this->assert_domain();
		// Keep the existing base lock so legacy writers cannot race this admission.
		$lock_path = $this->base . '/checkout-upload.lock';
		if ( is_link( $lock_path ) ) {
			throw new RuntimeException( 'private_lock_unavailable' );
		}
		$lock = fopen( $lock_path, 'c+b' );
		if ( false === $lock ) {
			throw new RuntimeException( 'private_lock_unavailable' );
		}
		chmod( $lock_path, 0600 );
		if ( ! flock( $lock, LOCK_EX | LOCK_NB ) ) {
			fclose( $lock );
			throw new RuntimeException( 'private_upload_busy' );
		}
		try {
			$this->assert_domain();
			$path    = $this->root . '/checkout-upload.catalog';
			$catalog = array(
				'schema'  => 2,
				'domain'  => $this->domain,
				'owners'  => array(),
				'retired' => array(),
			);
			if ( file_exists( $path ) || is_link( $path ) ) {
				$catalog = json_decode( $this->read_encrypted( $path, 'catalog', 8388608 ), true );
				if ( ! is_array( $catalog ) || 2 !== ( $catalog['schema'] ?? null ) || $this->domain !== ( $catalog['domain'] ?? null ) || ! is_array( $catalog['owners'] ?? null ) || ! is_array( $catalog['retired'] ?? null ) ) {
					throw new RuntimeException( 'private_catalog_invalid' );
				}
			}
			$this->active_staging_units( $catalog );
			$before       = wp_json_encode( $catalog );
			$this->locked = true;
			$result       = $callback( $catalog, $this );
			$this->assert_domain();
			if ( $write && $before !== wp_json_encode( $catalog ) ) {
				$this->checkpoint( $catalog );
			}
			return $result;
		} finally {
			$this->locked = false;
			flock( $lock, LOCK_UN );
			fclose( $lock );
		}
	}

	/**
	 * Encode the complete catalog using the authoritative byte limit.
	 *
	 * @param array $catalog Complete prospective or committed catalog.
	 * @param bool  $reserve_claims Retain worst-case room for every staged claim.
	 * @return string Encoded catalog.
	 * @throws RuntimeException When encoding or the finite byte bound fails.
	 */
	public function encode_catalog( $catalog, $reserve_claims = true ) {
		$json  = wp_json_encode( $catalog );
		$limit = 8388608;
		if ( $reserve_claims ) {
			// Every staged owner can claim, even an empty historical fence. Reserve
			// state +9, order-key +64, and two zero-to-signed-integer widths.
			// PHP_INT_MIN is wider than any positive principal/order ID (111 on64).
			$growth = 9 + 64 + 2 * ( strlen( (string) PHP_INT_MIN ) - 1 );
			foreach ( $catalog['owners'] as $owner ) {
				if ( is_array( $owner ) && 'staged' === ( $owner['state'] ?? null ) ) {
					$limit -= $growth;
				} elseif ( is_array( $owner ) && 'claimed-unknown' === ( $owner['state'] ?? null ) && 0 === ( $owner['principal'] ?? null ) ) {
					// A same-order replay may still adopt a guest principal.
					$limit -= strlen( (string) PHP_INT_MIN ) - 1;
				}
				if ( $limit < 0 ) {
					throw new RuntimeException( 'private_catalog_capacity' );
				}
			}
		}
		if ( ! is_string( $json ) || strlen( $json ) > $limit ) {
			throw new RuntimeException( 'private_catalog_capacity' );
		}
		return $json;
	}

	/**
	 * True only while this instance holds the module OS lock.
	 *
	 * @var bool
	 */
	private $locked = false;

	/**
	 * Commit, retire selected IDs, then authenticate the exact acknowledgement.
	 *
	 * @param array      $catalog Complete catalog, updated only after acknowledgement.
	 * @param array|null $selected Selected retired IDs, or all retired IDs when null.
	 * @throws RuntimeException When locked publication or acknowledgement fails.
	 */
	private function checkpoint( &$catalog, $selected = null ) {
		if ( ! $this->locked ) {
			throw new RuntimeException( 'private_lock_unavailable' );
		}
		$this->assert_domain();
		$this->active_staging_units( $catalog );
		$path = $this->root . '/checkout-upload.catalog';
		$this->atomic_write( $path, $this->seal( $this->encode_catalog( $catalog ), 'catalog' ) );
		$retained = array();
		foreach ( $catalog['owners'] as $owner ) {
			foreach ( $owner['files'] as $file ) {
				$retained[ $file['id'] ] = true;
			}
		}
		$ids = null === $selected ? array_keys( $catalog['retired'] ) : $selected;
		foreach ( $ids as $id ) {
			$file = $this->root . '/' . $id . '.blob';
			clearstatcache( true, $file );
			if ( isset( $catalog['retired'][ $id ] ) && ! isset( $retained[ $id ] ) && ! is_link( $file ) && ( ! file_exists( $file ) || unlink( $file ) ) ) {
				unset( $catalog['retired'][ $id ] );
			}
		}
		$json = $this->encode_catalog( $catalog );
		$this->atomic_write( $path, $this->seal( $json, 'catalog' ) );
		$this->assert_domain();
		if ( ! hash_equals( $json, $this->read_encrypted( $path, 'catalog', 8388608 ) ) ) {
			throw new RuntimeException( 'private_acknowledgement_held' );
		}
		if ( null !== $selected ) {
			foreach ( $selected as $id ) {
				$file = $this->root . '/' . $id . '.blob';
				clearstatcache( true, $file );
				if ( isset( $catalog['retired'][ $id ] ) || is_link( $file ) || file_exists( $file ) ) {
					throw new RuntimeException( 'private_acknowledgement_held' );
				}
			}
		}
	}

	/**
	 * Admit only this module's full prospective staged publication under its lock.
	 *
	 * @param array  $catalog Current catalog, updated after acknowledged publication.
	 * @param array  $next Full prospective catalog.
	 * @param string $current Current owner key, excluded from pressure retirement.
	 * @throws RuntimeException When catalog admission or publication is uncertain.
	 */
	public function admit_staging( &$catalog, $next, $current ) {
		if ( ! $this->locked ) {
			throw new RuntimeException( 'private_lock_unavailable' );
		}
		$this->active_staging_units( $catalog );
		$this->active_staging_units( $next );
		$checkpoint = $catalog;
		$selected   = array();
		$victims    = array();
		$references = array();
		foreach ( $catalog['owners'] as $owner ) {
			foreach ( $owner['files'] as $file ) {
				$references[ $file['id'] ] = ( $references[ $file['id'] ] ?? 0 ) + 1;
			}
		}
		$eligible = array();
		foreach ( $catalog['owners'] as $key => $owner ) {
			if ( $key === $current || 'staged' !== $owner['state'] || isset( $owner['successor'] ) || PHP_INT_MAX === $owner['revision'] ) {
				continue;
			}
			$safe = true;
			foreach ( $owner['files'] as $file ) {
				$path = $this->root . '/' . $file['id'] . '.blob';
				clearstatcache( true, $path );
				if ( 1 !== $references[ $file['id'] ] || isset( $catalog['retired'][ $file['id'] ] ) || is_link( $path ) ) {
					$safe = false;
				}
			}
			if ( $safe ) {
				$eligible[] = $key;
			}
		}
		$index = 0;
		while ( true ) {
			$ack = $checkpoint;
			foreach ( $selected as $id ) {
				unset( $ack['retired'][ $id ] );
			}
			$fits = $this->active_staging_units( $next ) <= 1000;
			foreach ( array( $checkpoint, $ack, $next ) as $stage ) {
				try {
					$this->encode_catalog( $stage );
				} catch ( RuntimeException $error ) {
					if ( 'private_catalog_capacity' !== $error->getMessage() ) {
						throw $error;
					}
					$fits = false;
				}
			}
			if ( $fits ) {
				break;
			}
			if ( ! isset( $eligible[ $index ] ) ) {
				throw new RuntimeException( 'private_staging_capacity' );
			}
			$key       = $eligible[ $index++ ];
			$victims[] = $key;
			foreach ( $catalog['owners'][ $key ]['files'] as $file ) {
				$id                           = $file['id'];
				$checkpoint['retired'][ $id ] = $catalog['owners'][ $key ]['revision'] + 1;
				$selected[]                   = $id;
				unset( $next['retired'][ $id ] );
			}
			unset( $checkpoint['owners'][ $key ], $next['owners'][ $key ] );
		}
		if ( $victims ) {
			// Only victim removal and its markers are committed, never requester data.
			$this->checkpoint( $checkpoint, $selected );
			// Any later failure is held uncertainty, not a pre-write capacity refusal.
			if ( $this->active_staging_units( $next ) > 1000 ) {
				throw new RuntimeException( 'private_acknowledgement_held' );
			}
			$this->encode_catalog( $next );
		}
		$catalog = $next;
	}

	/**
	 * Validate complete metadata before using any record for pressure retirement.
	 *
	 * @param array $catalog Complete domain-bound private catalog.
	 * @return int Number of retained files and retirement markers.
	 * @throws RuntimeException When any catalog metadata is invalid.
	 */
	public function active_staging_units( $catalog ) {
		if ( ! is_array( $catalog ) || 2 !== ( $catalog['schema'] ?? null ) || $this->domain !== ( $catalog['domain'] ?? null ) || ! is_array( $catalog['owners'] ?? null ) || ! is_array( $catalog['retired'] ?? null ) ) {
			throw new RuntimeException( 'private_catalog_invalid' );
		}
		$units = count( $catalog['retired'] );
		foreach ( $catalog['retired'] as $id => $revision ) {
			if ( ! is_string( $id ) || ! preg_match( '/^[a-f0-9]{64}$/D', $id ) || ! is_int( $revision ) || $revision < 1 ) {
				throw new RuntimeException( 'private_catalog_invalid' );
			}
		}
		foreach ( $catalog['owners'] as $key => $owner ) {
			if ( ! is_string( $key ) || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) || ! is_array( $owner ) || ! is_string( $owner['attempt'] ?? null ) || ! preg_match( '/^[a-f0-9]{32}$/D', $owner['attempt'] ) || ! is_int( $owner['revision'] ?? null ) || $owner['revision'] < 0 || ! is_int( $owner['principal'] ?? null ) || $owner['principal'] < 0 || ! is_string( $owner['cart'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/D', $owner['cart'] ) || ! in_array( $owner['state'] ?? null, array( 'staged', 'claimed-unknown' ), true ) || ! is_int( $owner['order_id'] ?? null ) || ! is_string( $owner['order_key'] ?? null ) || ! is_array( $owner['files'] ?? null ) ) {
				throw new RuntimeException( 'private_catalog_invalid' );
			}
			if ( 'staged' === $owner['state'] ) {
				if ( 0 !== $owner['order_id'] || '' !== $owner['order_key'] || isset( $owner['successor'] ) ) {
					throw new RuntimeException( 'private_catalog_invalid' );
				}
				++$units;
			} elseif ( $owner['order_id'] < 1 || ! preg_match( '/^[a-f0-9]{64}$/D', $owner['order_key'] ) || ( isset( $owner['successor'] ) && ( ! is_string( $owner['successor'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $owner['successor'] ) ) ) ) {
				throw new RuntimeException( 'private_catalog_invalid' );
			}
			foreach ( $owner['files'] as $slot => $file ) {
				if ( ! preg_match( '/^[1-9][0-9]*$/D', (string) $slot ) || (int) $slot < 1 || ! is_array( $file ) || ! is_string( $file['id'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/D', $file['id'] ) || ! is_string( $file['name'] ?? null ) || '' === $file['name'] || strlen( $file['name'] ) > 255 || ! is_int( $file['size'] ?? null ) || $file['size'] < 0 || ! is_string( $file['type'] ?? null ) || '' === $file['type'] || ! is_string( $file['sha256'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/D', $file['sha256'] ) ) {
					throw new RuntimeException( 'private_catalog_invalid' );
				}
			}
		}
		return $units;
	}

	/**
	 * Store content before publishing its reference. Orphans are held on failure.
	 *
	 * @param string $bytes Validated original content.
	 * @return string Opaque immutable file ID.
	 * @throws RuntimeException When private storage or content validation fails.
	 */
	public function put( $bytes ) {
		$this->assert_domain();
		$id = bin2hex( random_bytes( 32 ) );
		$this->atomic_write( $this->root . '/' . $id . '.blob', $this->seal( $bytes, $id ) );
		return $id;
	}

	/**
	 * Read an immutable encrypted blob, authenticating its exact ID.
	 *
	 * @param string $id File ID from the private catalog only.
	 * @param int    $size Expected exact plaintext size.
	 * @return string
	 * @throws RuntimeException When private storage or content validation fails.
	 */
	public function get( $id, $size ) {
		$this->assert_domain();
		if ( ! is_string( $id ) || ! preg_match( '/^[a-f0-9]{64}$/D', $id ) || ! is_int( $size ) || $size < 0 ) {
			throw new RuntimeException( 'private_file_invalid' );
		}
		$bytes = $this->read_encrypted( $this->root . '/' . $id . '.blob', $id, $size + 64 );
		if ( strlen( $bytes ) !== $size ) {
			throw new RuntimeException( 'private_file_invalid' );
		}
		return $bytes;
	}

	/**
	 * Create a private validation scratch path, not a public upload URL.
	 *
	 * @return string Private scratch path.
	 * @throws RuntimeException When private scratch admission fails.
	 */
	public function scratch() {
		$this->assert_domain();
		$path = tempnam( $this->root, 'validate-' );
		if ( false === $path || dirname( $path ) !== $this->root ) {
			throw new RuntimeException( 'private_scratch_unavailable' );
		}
		chmod( $path, 0600 );
		return $path;
	}

	/**
	 * Encrypt content with a fresh nonce and authenticated purpose.
	 *
	 * @param string $bytes Original plaintext content.
	 * @param string $purpose Authenticated internal purpose.
	 * @return string Authenticated encrypted content.
	 * @throws RuntimeException When encryption fails.
	 */
	private function seal( $bytes, $purpose ) {
		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $bytes, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag, $purpose, 16 );
		if ( false === $cipher || 16 !== strlen( $tag ) ) {
			throw new RuntimeException( 'private_encryption_failure' );
		}
		return 'WCJU1' . $iv . $tag . $cipher;
	}

	/**
	 * Authenticate and decrypt only a bounded regular file under this root.
	 *
	 * @param string $path Exact private file path.
	 * @param string $purpose Expected authenticated internal purpose.
	 * @param int    $limit Maximum plaintext length.
	 * @return string Authenticated plaintext.
	 * @throws RuntimeException When bounded reading or authentication fails.
	 */
	private function read_encrypted( $path, $purpose, $limit ) {
		clearstatcache( true, $path );
		if ( is_link( $path ) || ! is_file( $path ) || filesize( $path ) > $limit + 33 ) {
			throw new RuntimeException( 'private_read_failure' );
		}
		$raw = file_get_contents( $path, false, null, 0, $limit + 34 );
		if ( false === $raw || strlen( $raw ) > $limit + 33 || strlen( $raw ) < 33 || 'WCJU1' !== substr( $raw, 0, 5 ) ) {
			throw new RuntimeException( 'private_read_failure' );
		}
		$bytes = openssl_decrypt( substr( $raw, 33 ), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr( $raw, 5, 12 ), substr( $raw, 17, 16 ), $purpose );
		if ( false === $bytes ) {
			throw new RuntimeException( 'private_authentication_failure' );
		}
		return $bytes;
	}

	/**
	 * Perform checked write, flush and rename under the held module lock.
	 * No lock-free CAS or power-loss durability claim is made by rename alone.
	 *
	 * @param string $path Exact destination in the private directory.
	 * @param string $bytes Exact authenticated bytes to publish.
	 * @throws RuntimeException When writing or publication is uncertain.
	 */
	private function atomic_write( $path, $bytes ) {
		if ( is_link( $path ) ) {
			throw new RuntimeException( 'private_write_failure' );
		}
		$tmp = $this->root . '/pending-' . bin2hex( random_bytes( 16 ) );
		$out = fopen( $tmp, 'x+b' );
		if ( false === $out ) {
			throw new RuntimeException( 'private_write_failure' );
		}
		try {
			chmod( $tmp, 0600 );
			$length = strlen( $bytes );
			$offset = 0;
			while ( $offset < $length ) {
				$written = fwrite( $out, substr( $bytes, $offset ) );
				if ( false === $written || 0 === $written ) {
					throw new RuntimeException( 'private_write_failure' );
				}
				$offset += $written;
			}
			if ( ! fflush( $out ) || ( function_exists( 'fsync' ) && ! fsync( $out ) ) ) {
				throw new RuntimeException( 'private_flush_unknown' );
			}
		} finally {
			fclose( $out );
		}
		if ( ! rename( $tmp, $path ) ) {
			throw new RuntimeException( 'private_commit_unknown' );
		}
	}
}
