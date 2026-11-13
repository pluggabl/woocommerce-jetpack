<?php
/**
 * Native owner consent and exact-ID report retrieval. No initialization/reset.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-report-journal.php';
require_once dirname( __DIR__ ) . '/class-wcj-ai-connection.php';

/** One direct native observation, then immutable local review and explicit share. */
final class WCJ_AI_Report_Service {

	/**
	 * Finite refusal. Retained state must be inspected, never blindly replayed.
	 *
	 * @throws RuntimeException Always.
	 */
	private static function fail() {
		throw new RuntimeException( 'ai_report_unavailable' );
	}

	/**
	 * Refresh native owner identity without replacing the current user.
	 * Cookie verification checks core session validity after user-meta cache eviction.
	 *
	 * @return array Private current owner/site/session identity.
	 */
	private static function owner() {
		$id = get_current_user_id();
		if ( ! WCJ_AI_Connection::enabled() || is_multisite() || wp_using_ext_object_cache() ||
			! is_int( $id ) || $id < 1 || $id > 2147483647 ||
			( function_exists( 'rest_get_authenticated_app_password' ) && rest_get_authenticated_app_password() ) ) {
			self::fail();
		}
		clean_user_cache( $id );
		$user  = get_userdata( $id );
		$token = wp_get_session_token();
		if ( ! $user || WCJ_AI_Connection::principal( $id ) || ! $user->has_cap( 'manage_woocommerce' ) || // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Fresh explicit native WooCommerce owner, no impersonation.
			wp_validate_auth_cookie( '', 'logged_in' ) !== $id || ! is_string( $token ) || '' === $token || strlen( $token ) > 512 ) {
			self::fail();
		}
		$site = get_current_blog_id();
		if ( ! is_int( $site ) || $site < 1 || $site > 2147483647 ) {
			self::fail();
		}
		return array(
			'owner'                => $id,
			'site'                 => $site,
			'owner_session_digest' => hash_hmac( 'sha256', 'wcj-report-owner-v1|' . $site . '|' . $id . '|' . $token, wp_salt( 'auth' ) ),
		);
	}

	/**
	 * The cleanup journal never needs the private session digest.
	 *
	 * @param array $owner Fresh service identity.
	 * @return array Minimal ownership tuple.
	 */
	private static function identity( array $owner ) {
		return array(
			'owner' => $owner['owner'],
			'site'  => $owner['site'],
		);
	}

	/**
	 * Verify a fresh native session/capability snapshot before any output phase.
	 *
	 * @param array $owner Original request identity.
	 */
	private static function same_owner( array $owner ) {
		if ( self::owner() !== $owner ) {
			self::fail();
		}
	}

	/**
	 * Domain-separated fingerprint of explicit server-owned source files.
	 *
	 * @param string $domain Fixed internal purpose.
	 * @param array  $files Fixed absolute source paths, never request fields.
	 * @return string SHA-256 build identity.
	 */
	private static function fingerprint( $domain, array $files ) {
		$parts = array( $domain );
		foreach ( $files as $file ) {
			$digest = hash_file( 'sha256', $file );
			if ( ! is_string( $digest ) || 64 !== strlen( $digest ) ) {
				self::fail();
			}
			$parts[] = $digest;
		}
		return hash( 'sha256', implode( '|', $parts ) );
	}

	/**
	 * Resolve native recipient and build identities while holding the caller gate.
	 *
	 * @param int   $principal Dedicated native principal, only a locator.
	 * @param array $owner Fresh owner identity.
	 * @param array $gate Held gate.
	 * @return array Internal original capture binding.
	 */
	private static function binding( $principal, array $owner, array $gate ) {
		self::same_owner( $owner );
		if ( ! is_int( $principal ) || $principal < 1 || $principal > 2147483647 ) {
			self::fail();
		}
		clean_user_cache( $principal );
		$recipient = WCJ_AI_Connection::owner_report_context( $principal, $gate );
		if ( ! is_array( $recipient ) || $recipient['owner'] !== $owner['owner'] || $recipient['site'] !== $owner['site'] ) {
			self::fail();
		}
		$base = dirname( __DIR__ );
		return array(
			'recipient'            => $recipient,
			'owner_session_digest' => $owner['owner_session_digest'],
			'source_build'         => self::fingerprint( 'native-revenue-observation-v1', array( $base . '/class-wcj-ai-revenue-observation.php', $base . '/class-wcj-ai-report-values.php' ) ),
			'projection_build'     => self::fingerprint( 'report-codec-' . WCJ_AI_Report_Receipt::SCHEMA, array( __FILE__, $base . '/class-wcj-ai-observation-projection.php', $base . '/class-wcj-ai-report-receipt.php', $base . '/class-wcj-ai-price-value.php', $base . '/class-wcj-ai-report-values.php' ) ),
		);
	}

	/**
	 * Purpose, immutable expectations and current session bind the native nonce.
	 *
	 * @param string $op Fixed operation.
	 * @param array  $owner Fresh native owner.
	 * @param array  $parts Server-derived immutable expectations.
	 * @return string Native nonce action, not a credential or access token.
	 */
	private static function action( $op, array $owner, array $parts ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Closed server-derived scalar/array nonce inputs only.
		return 'wcj_ai_report_' . $op . '_' . hash( 'sha256', json_encode( array( $owner, $parts ) ) );
	}

	/**
	 * Exact displayed capture intent, including the consumed global revision.
	 *
	 * @param array $owner Fresh owner.
	 * @param array $binding Server-derived binding.
	 * @param int   $revision Displayed journal revision.
	 * @return string Native nonce action.
	 */
	private static function capture_action( array $owner, array $binding, $revision ) {
		return self::action( 'capture', $owner, array( $binding, $revision ) );
	}

	/**
	 * Consent is explicit and independently verified, not inferred from scope.
	 *
	 * @param mixed  $nonce Submitted native nonce.
	 * @param string $action Exact server-derived action.
	 * @param mixed  $confirmed Explicit checkbox decision.
	 */
	private static function intent( $nonce, $action, $confirmed ) {
		if ( true !== $confirmed || ! is_string( $nonce ) || strlen( $nonce ) > 64 || ! wp_verify_nonce( $nonce, $action ) ) {
			self::fail();
		}
	}

	/**
	 * Read-only local recovery index; does not require any current report grant.
	 *
	 * @return array Bounded metadata, no payload or private binding.
	 */
	public static function catalog() {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::same_owner( $owner );
			$out = WCJ_AI_Report_Journal::owner_index( $gate, self::identity( $owner ) );
			self::same_owner( $owner );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return $out;
	}

	/**
	 * Display a deliberate capture form for one eligible report-only connection.
	 *
	 * @param int $principal Native dedicated account locator.
	 * @return array Closed form expectations; no AP/session/build secret.
	 */
	public static function capture_form( $principal ) {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			$binding = self::binding( $principal, $owner, $gate );
			$index   = WCJ_AI_Report_Journal::owner_index( $gate, self::identity( $owner ) );
			$out     = array(
				'principal' => $principal,
				'digest'    => $binding['recipient']['grant_digest'],
				'revision'  => $index['revision'],
				'nonce'     => wp_create_nonce( self::capture_action( $owner, $binding, $index['revision'] ) ),
			);
			self::same_owner( $owner );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return $out;
	}

	/**
	 * One explicit capture. Never retry, rebind or initialize on a failure.
	 *
	 * @param int    $principal Dedicated native locator.
	 * @param string $digest Expected grant digest, never authority.
	 * @param int    $revision Expected global journal revision.
	 * @param string $nonce Native purpose-bound nonce.
	 * @param bool   $confirmed Explicit owner confirmation.
	 * @return string Exact receipt locator after successful release.
	 */
	public static function capture( $principal, $digest, $revision, $nonce, $confirmed ) {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			$binding = self::binding( $principal, $owner, $gate );
			if ( ! is_string( $digest ) || $binding['recipient']['grant_digest'] !== $digest ) {
				self::fail();
			}
			self::intent( $nonce, self::capture_action( $owner, $binding, $revision ), $confirmed );
			$reservation = WCJ_AI_Report_Journal::reserve_at( $gate, $binding, time(), $revision );
			self::same_owner( $owner );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		$entry = $reservation['entry'];
		if ( ! $reservation['created'] ) {
			return $entry['id']; // Existing state only; never another observation.
		}
		require_once dirname( __DIR__ ) . '/class-wcj-ai-revenue-observation.php';
		// Successful release may span native session/capability changes.
		// Refresh at the observation phase boundary, without holding the gate.
		self::same_owner( $owner );
		$source = WCJ_AI_Revenue_Observation::observe();
		$bytes  = WCJ_AI_Report_Receipt::build( $source, $entry['reserved'], $binding['recipient']['grant_expires'], time() );
		unset( $source );
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			if ( self::binding( $principal, $owner, $gate ) !== $binding ) {
				self::fail();
			}
			WCJ_AI_Report_Journal::finish( $gate, $binding, $entry['id'], $entry['revision'], $bytes, time() );
			if ( self::binding( $principal, $owner, $gate ) !== $binding || time() >= $entry['review_deadline'] ) {
				self::fail();
			}
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return $entry['id'];
	}

	/**
	 * Extract validated immutable stored bytes; never reconstruct from observations.
	 *
	 * @param array $entry Validated private journal entry.
	 * @return string|null Canonical bytes, including supported old-codec history.
	 */
	private static function bytes( array $entry ) {
		if ( 'collecting' === $entry['state'] ) {
			return null;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Bounded canonical validated receipt bytes, not code.
		$bytes = base64_decode( $entry['payload_b64'], true );
		WCJ_AI_Report_Receipt::decode( $bytes, $entry['reserved'], $entry['binding']['recipient']['grant_expires'] );
		return $bytes;
	}

	/**
	 * Original capture/session/build/grant consent is never rebound after recovery.
	 *
	 * @param array $entry Retained entry.
	 * @param array $owner Fresh owner.
	 * @param array $gate Held gate.
	 * @return bool Whether this exact original receipt may still be shared.
	 */
	private static function shareable( array $entry, array $owner, array $gate ) {
		if ( 'collecting' === $entry['state'] || time() < $entry['review_created'] || time() >= $entry['expires'] ||
			( 'pending_review' === $entry['state'] && time() >= $entry['review_deadline'] ) ) {
			return false;
		}
		$bytes = self::bytes( $entry );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_decode_json_decode -- Pure validated bounded canonical bytes.
		$envelope = json_decode( $bytes, true, 16, JSON_THROW_ON_ERROR );
		return WCJ_AI_Report_Receipt::SCHEMA === $envelope['schema_version'] && self::binding( $entry['binding']['recipient']['principal'], $owner, $gate ) === $entry['binding'];
	}

	/**
	 * Exact retained share action; includes full original binding and payload hash.
	 *
	 * @param array $owner Current native session identity.
	 * @param array $entry Validated stored receipt.
	 * @return string Native nonce action.
	 */
	private static function share_action( array $owner, array $entry ) {
		return self::action( 'share', $owner, array( $entry['id'], $entry['revision'], $entry['payload_sha256'], $entry['binding'] ) );
	}

	/**
	 * Private exact-byte review, including history retained after access expires.
	 *
	 * @param string $id Exact locator.
	 * @return array Closed owner view, no private recipient/session binding.
	 */
	public static function inspect( $id ) {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::same_owner( $owner );
			$entry = WCJ_AI_Report_Journal::inspect_owned( $gate, self::identity( $owner ), $id );
			$bytes = self::bytes( $entry );
			$share = false;
			try {
				$share = self::shareable( $entry, $owner, $gate );
			} catch ( Throwable $exception ) {
				$share = false; // Old grant/build loss prevents share, not owner cleanup.
			}
			$out = array(
				'id'              => $entry['id'],
				'revision'        => $entry['revision'],
				'state'           => $entry['state'],
				'principal'       => $entry['binding']['recipient']['principal'],
				'generation'      => $entry['binding']['recipient']['grant_generation'],
				'review_deadline' => $entry['review_deadline'],
				'expires'         => $entry['expires'],
				'payload_bytes'   => $entry['payload_bytes'],
				'payload_sha256'  => $entry['payload_sha256'],
				'json'            => $bytes,
				'can_share'       => $share,
				'share_nonce'     => $share ? wp_create_nonce( self::share_action( $owner, $entry ) ) : '',
				'purge_nonce'     => wp_create_nonce( self::action( 'purge', $owner, array( $entry['id'], $entry['revision'] ) ) ),
			);
			self::same_owner( $owner );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return $out;
	}

	/**
	 * Distinct explicit owner share, with no capture, refreshed expiry or new bytes.
	 *
	 * @param string $id Exact locator.
	 * @param int    $revision Reviewed entry revision.
	 * @param string $digest Whole reviewed payload SHA-256.
	 * @param string $nonce Native exact-intent nonce.
	 * @param bool   $confirmed Explicit owner confirmation.
	 */
	public static function share( $id, $revision, $digest, $nonce, $confirmed ) {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::same_owner( $owner );
			$entry = WCJ_AI_Report_Journal::inspect_owned( $gate, self::identity( $owner ), $id );
			if ( $entry['revision'] !== $revision || $entry['payload_sha256'] !== $digest || ! self::shareable( $entry, $owner, $gate ) ) {
				self::fail();
			}
			self::intent( $nonce, self::share_action( $owner, $entry ), $confirmed );
			WCJ_AI_Report_Journal::share( $gate, $entry['binding'], $id, $revision, $digest, time() );
			if ( ! self::shareable( $entry, $owner, $gate ) ) {
				self::fail();
			}
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
	}

	/**
	 * Deliberate purge from a fresh owner session, not an old sharing grant.
	 *
	 * @param string $id Exact retained locator.
	 * @param int    $revision Reviewed entry revision.
	 * @param string $nonce Native purpose-bound nonce.
	 * @param bool   $confirmed Explicit owner confirmation.
	 */
	public static function purge( $id, $revision, $nonce, $confirmed ) {
		$owner = self::owner();
		$gate  = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::same_owner( $owner );
			$entry = WCJ_AI_Report_Journal::inspect_owned( $gate, self::identity( $owner ), $id );
			if ( $entry['revision'] !== $revision ) {
				self::fail();
			}
			self::intent( $nonce, self::action( 'purge', $owner, array( $id, $revision ) ), $confirmed );
			WCJ_AI_Report_Journal::purge_owned( $gate, self::identity( $owner ), $id, $revision );
			self::same_owner( $owner );
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
	}

	/**
	 * Refresh both explicit native identities, then re-resolve actual AP context.
	 *
	 * @param array $gate Held gate.
	 * @return array Fresh actual request context, not fabricated from stored data.
	 */
	private static function remote_context( array $gate ) {
		$context = WCJ_AI_Connection::report_read_context( $gate );
		if ( ! $context ) {
			self::fail();
		}
		clean_user_cache( $context['owner'] );
		clean_user_cache( $context['principal'] );
		if ( WCJ_AI_Connection::report_read_context( $gate ) !== $context ) {
			self::fail();
		}
		return $context;
	}

	/**
	 * The only remote operation: copy one previously owner-shared exact receipt.
	 * No observer, order query, listing, capture, refresh, share or purge call.
	 *
	 * @param mixed $input Closed receipt_id object.
	 * @return array Already validated immutable DTO for the pinned adapter codec.
	 */
	public static function read_shared( $input ) {
		if ( $input instanceof stdClass ) {
			$input = get_object_vars( $input );
		}
		if ( ! is_array( $input ) || array_keys( $input ) !== array( 'receipt_id' ) || ! is_string( $input['receipt_id'] ) ||
			1 !== preg_match( '/^[a-f0-9]{64}$/D', $input['receipt_id'] ) || ! WCJ_AI_Connection::report_allowed() ) {
			self::fail();
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			$context = self::remote_context( $gate );
			$bytes   = WCJ_AI_Report_Journal::read_shared( $gate, $context, $input['receipt_id'], time() );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_decode_json_decode -- Journal revalidated the entire canonical envelope against immutable metadata.
			$out = json_decode( $bytes, true, 16, JSON_THROW_ON_ERROR );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Match pinned native adapter default flags; no semantic-only equality claim.
			if ( WCJ_AI_Report_Receipt::SCHEMA !== $out['schema_version'] || json_encode( $out ) !== $bytes || strlen( $bytes ) > WCJ_AI_Report_Receipt::MAX_BYTES ||
				self::remote_context( $gate ) !== $context || time() >= ( new DateTimeImmutable( $out['expires_at_utc'] ) )->getTimestamp() ) {
				self::fail();
			}
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return $out;
	}
}
