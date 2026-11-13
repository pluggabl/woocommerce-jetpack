<?php
/**
 * Dormant, internal exact-byte receipt journal. NOT an authorization service.
 *
 * No bootstrap, route, hook or ability includes this file. Future callers must
 * establish fresh native owner or application-password authority under the same
 * gate, supply server-derived bindings/times, and successfully release the gate
 * before observing a store or emitting any copied receipt. A token or tuple alone
 * is not authority. Unknown outcomes require inspection, never retry/recapture.
 *
 * @package Booster_For_WooCommerce/admin
 */

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Bounded canonical transport of exact validated JSON, never executable code.
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-mutation-gate.php';
require_once dirname( __DIR__ ) . '/class-wcj-ai-report-receipt.php';

/** Fixed site-local, non-autoload storage; expired entries retain their slots. */
final class WCJ_AI_Report_Journal {

	const KEY          = 'wcj_ai_report_journal_v1';
	const MARKER       = 'wcj_ai_report_journal_initialized_v1';
	const MAX_BYTES    = 786432;
	const MAX_ENTRIES  = 16;
	const MAX_METADATA = 2048;
	const MAX_BASE64   = 43692;
	const MAX_REVISION = 2147483647;

	/**
	 * Finite internal failure; never include input, SQL or exception details.
	 *
	 * @param string $code Fixed internal code.
	 * @throws RuntimeException Always.
	 */
	private static function fail( $code = 'ai_report_unavailable' ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Private method called only with fixed literal enums; no user data or raw exceptions.
		throw new RuntimeException( $code );
	}

	/**
	 * Require a closed, ordered plain array.
	 *
	 * @param mixed $value Candidate.
	 * @param array $keys Exact keys.
	 */
	private static function keys( $value, array $keys ) {
		if ( ! is_array( $value ) || array_keys( $value ) !== $keys ) {
			self::fail();
		}
	}

	/**
	 * Strict integer bound, before arithmetic.
	 *
	 * @param mixed $value Candidate.
	 * @param int   $min Minimum.
	 * @param int   $max Maximum.
	 */
	private static function integer( $value, $min = 1, $max = self::MAX_REVISION ) {
		if ( ! is_int( $value ) || $value < $min || $value > $max ) {
			self::fail();
		}
	}

	/**
	 * Strict timestamp bound shared with the receipt envelope.
	 *
	 * @param mixed $value Trusted server timestamp.
	 */
	private static function time( $value ) {
		self::integer( $value, 1, 253402297199 );
	}

	/**
	 * Validate an opaque lowercase digest or identifier, never a raw credential.
	 *
	 * @param mixed $value Candidate.
	 * @param int   $length Fixed hex length.
	 */
	private static function hex( $value, $length = 64 ) {
		if ( ! is_string( $value ) || strlen( $value ) !== $length || 1 !== preg_match( '/^[a-f0-9]+$/D', $value ) ) {
			self::fail();
		}
	}

	/**
	 * Report-only tuple, supplied after separate fresh native authorization.
	 *
	 * @param mixed $value Server-derived recipient.
	 */
	private static function recipient( $value ) {
		self::keys( $value, array( 'grant_schema', 'owner', 'site', 'principal', 'app_uuid', 'grant_generation', 'grant_digest', 'grant_expires', 'kind', 'scope' ) );
		if ( 2 !== $value['grant_schema'] || 'revenue_observation' !== $value['kind'] || 'report:read_shared' !== $value['scope'] ) {
			self::fail();
		}
		foreach ( array( 'owner', 'site', 'principal' ) as $key ) {
			self::integer( $value[ $key ] );
		}
		if ( $value['owner'] === $value['principal'] || ! is_string( $value['app_uuid'] ) || 1 !== preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $value['app_uuid'] ) ) {
			self::fail();
		}
		self::hex( $value['grant_generation'], 32 );
		self::hex( $value['grant_digest'] );
		self::time( $value['grant_expires'] );
	}

	/**
	 * Local owner-session/build binding never appears in the export.
	 *
	 * @param mixed $value Trusted local binding, not posted JSON.
	 */
	private static function binding( $value ) {
		self::keys( $value, array( 'recipient', 'owner_session_digest', 'source_build', 'projection_build' ) );
		self::recipient( $value['recipient'] );
		foreach ( array( 'owner_session_digest', 'source_build', 'projection_build' ) as $key ) {
			self::hex( $value[ $key ] );
		}
	}

	/**
	 * Immutable grant/kind identity; changed digest, expiry or session cannot rebind.
	 *
	 * @param array $recipient Validated recipient.
	 * @return string Internal uniqueness key.
	 */
	private static function slot( array $recipient ) {
		return implode( ':', array( $recipient['site'], $recipient['principal'], $recipient['app_uuid'], $recipient['grant_generation'], $recipient['kind'] ) );
	}

	/**
	 * Encode only closed, validated scalars/arrays, never callback-bearing objects.
	 *
	 * @param array $value Validated structure.
	 * @return string Canonical JSON.
	 */
	private static function encode( array $value ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Pure canonical storage, after closed scalar validation.
		$json = json_encode( $value, JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) || strlen( $json ) > self::MAX_BYTES ) {
			self::fail();
		}
		return $json;
	}

	/**
	 * Bound the database result BEFORE PHP receives option_value; never unserialize.
	 *
	 * @param string $key One of this class's fixed keys.
	 * @return array Exact raw state.
	 */
	private static function raw( $key ) {
		global $wpdb;
		if ( ! in_array( $key, array( self::KEY, self::MARKER ), true ) ) {
			self::fail();
		}
		$cap              = self::MARKER === $key ? 1 : self::MAX_BYTES;
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fixed prepared keys; core table; bounded uncached authoritative read.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT OCTET_LENGTH(option_value) AS bytes, CASE WHEN OCTET_LENGTH(option_value) <= %d THEN option_value ELSE NULL END AS raw, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 2", $cap, $key ), ARRAY_A );
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) {
			self::fail();
		}
		if ( array() === $rows ) {
			return array(
				'exists' => false,
				'raw'    => '',
			);
		}
		$row = $rows[0];
		self::keys( $row, array( 'bytes', 'raw', 'autoload' ) );
		if ( ( ! is_int( $row['bytes'] ) && ( ! is_string( $row['bytes'] ) || 1 !== preg_match( '/^[0-9]{1,7}$/D', $row['bytes'] ) ) ) ||
			(int) $row['bytes'] > $cap || (int) $row['bytes'] < 0 || ! is_string( $row['raw'] ) || strlen( $row['raw'] ) !== (int) $row['bytes'] || 'no' !== $row['autoload'] ) {
			self::fail();
		}
		return array(
			'exists' => true,
			'raw'    => $row['raw'],
		);
	}

	/**
	 * Revalidate one retained entry, including expired/collecting entries.
	 *
	 * @param mixed $entry Stored entry.
	 */
	private static function entry( $entry ) {
		self::keys( $entry, array( 'id', 'revision', 'state', 'binding', 'reserved', 'review_deadline', 'expires', 'review_created', 'shared_at', 'payload_bytes', 'payload_sha256', 'payload_b64' ) );
		self::hex( $entry['id'] );
		self::integer( $entry['revision'] );
		self::binding( $entry['binding'] );
		self::time( $entry['reserved'] );
		$expiry = $entry['binding']['recipient']['grant_expires'];
		if ( $expiry <= $entry['reserved'] || $expiry > $entry['reserved'] + 3600 ||
			min( $entry['reserved'] + WCJ_AI_Report_Receipt::REVIEW_SECONDS, $expiry ) !== $entry['review_deadline'] ||
			min( $entry['reserved'] + WCJ_AI_Report_Receipt::SHARE_SECONDS, $expiry ) !== $entry['expires'] ) {
			self::fail();
		}
		if ( 'collecting' === $entry['state'] ) {
			if ( 1 !== $entry['revision'] || null !== $entry['review_created'] || null !== $entry['shared_at'] || 0 !== $entry['payload_bytes'] || null !== $entry['payload_sha256'] || null !== $entry['payload_b64'] ) {
				self::fail();
			}
		} else {
			if ( ! in_array( $entry['state'], array( 'pending_review', 'shared' ), true ) ||
				( 'pending_review' === $entry['state'] ? 2 : 3 ) !== $entry['revision'] ) {
				self::fail();
			}
			self::time( $entry['review_created'] );
			self::integer( $entry['payload_bytes'], 1, WCJ_AI_Report_Receipt::MAX_BYTES );
			self::hex( $entry['payload_sha256'] );
			if ( ! is_string( $entry['payload_b64'] ) || strlen( $entry['payload_b64'] ) > self::MAX_BASE64 ) {
				self::fail();
			}
			$bytes = base64_decode( $entry['payload_b64'], true );
			if ( ! is_string( $bytes ) || strlen( $bytes ) !== $entry['payload_bytes'] || base64_encode( $bytes ) !== $entry['payload_b64'] || hash( 'sha256', $bytes ) !== $entry['payload_sha256'] ) {
				self::fail();
			}
			try {
				$envelope = WCJ_AI_Report_Receipt::decode( $bytes, $entry['reserved'], $expiry );
			} catch ( Throwable $exception ) {
				self::fail();
			}
			if ( gmdate( 'Y-m-d\TH:i:s\Z', $entry['review_created'] ) !== $envelope['review_created_at_utc'] ) {
				self::fail();
			}
			if ( 'shared' === $entry['state'] ) {
				self::time( $entry['shared_at'] );
				if ( $entry['shared_at'] < $entry['review_created'] || $entry['shared_at'] >= $entry['review_deadline'] ) {
					self::fail();
				}
			} elseif ( null !== $entry['shared_at'] ) {
				self::fail();
			}
		}
		unset( $entry['payload_b64'] );
		if ( strlen( self::encode( $entry ) ) > self::MAX_METADATA ) {
			self::fail();
		}
	}

	/**
	 * Validate the entire journal; do not salvage individual corrupt entries.
	 *
	 * @param mixed $value Journal.
	 */
	private static function validate( $value ) {
		self::keys( $value, array( 'schema', 'revision', 'entries' ) );
		if ( 1 !== $value['schema'] || ! is_array( $value['entries'] ) || array_values( $value['entries'] ) !== $value['entries'] || count( $value['entries'] ) > self::MAX_ENTRIES ) {
			self::fail();
		}
		self::integer( $value['revision'], 0 );
		$ids   = array();
		$slots = array();
		foreach ( $value['entries'] as $entry ) {
			self::entry( $entry );
			$slot = self::slot( $entry['binding']['recipient'] );
			if ( isset( $ids[ $entry['id'] ] ) || isset( $slots[ $slot ] ) || $entry['revision'] > $value['revision'] ) {
				self::fail();
			}
			$ids[ $entry['id'] ] = true;
			$slots[ $slot ]      = true;
		}
	}

	/**
	 * Read the complete initialized journal under an already-held gate.
	 *
	 * @param array $gate Internal token, never request data.
	 * @return array Raw state and decoded journal.
	 */
	private static function load( array $gate ) {
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		if ( self::raw( self::MARKER ) !== array(
			'exists' => true,
			'raw'    => '1',
		) ) {
			self::fail();
		}
		$state = self::raw( self::KEY );
		if ( ! $state['exists'] ) {
			self::fail();
		}
		try {
			$value = json_decode( $state['raw'], true, 16, JSON_THROW_ON_ERROR );
			self::validate( $value );
			if ( self::encode( $value ) !== $state['raw'] ) {
				self::fail();
			}
		} catch ( Throwable $exception ) {
			self::fail();
		}
		return array( $state, $value );
	}

	/**
	 * One changed-state CAS and exact bounded readback; never retry on uncertainty.
	 *
	 * @param array $gate Held token.
	 * @param array $before Exact prior raw journal.
	 * @param array $value Intended journal with prior global revision.
	 */
	private static function save( array $gate, array $before, array $value ) {
		self::integer( $value['revision'], 0, self::MAX_REVISION - 1 );
		++$value['revision'];
		self::validate( $value );
		$after = array(
			'exists' => true,
			'raw'    => self::encode( $value ),
		);
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		try {
			if ( ! WCJ_Job_Store::compare_exchange( self::KEY, $before, $after, array( self::KEY ) ) || self::load( $gate )[0] !== $after ) {
				self::fail();
			}
		} catch ( Throwable $exception ) {
			self::fail( 'ai_report_outcome_unknown' );
		}
	}

	/**
	 * Explicit known-fresh initialization ONLY; partial history is never reset.
	 * Both rows being deleted out of band cannot prove historical freshness.
	 *
	 * @param array $gate Held token.
	 * @param bool  $known_fresh Trusted owner-service attestation, not a request flag.
	 */
	public static function initialize( array $gate, $known_fresh ) {
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		$absent = array(
			'exists' => false,
			'raw'    => '',
		);
		if ( true !== $known_fresh || self::raw( self::MARKER ) !== $absent || self::raw( self::KEY ) !== $absent ) {
			self::fail();
		}
		$marker  = array(
			'exists' => true,
			'raw'    => '1',
		);
		$journal = array(
			'exists' => true,
			'raw'    => self::encode(
				array(
					'schema'   => 1,
					'revision' => 0,
					'entries'  => array(),
				)
			),
		);
		try {
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( self::MARKER, $absent, $marker, array( self::MARKER ) ) || self::raw( self::MARKER ) !== $marker || self::raw( self::KEY ) !== $absent ) {
				self::fail();
			}
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( self::KEY, $absent, $journal, array( self::KEY ) ) || self::load( $gate )[0] !== $journal ) {
				self::fail();
			}
		} catch ( Throwable $exception ) {
			self::fail( 'ai_report_outcome_unknown' );
		}
	}

	/**
	 * Reserve once. Only created:true followed by successful gate release permits
	 * the future service's single capture; existing/uncertain is never recapture.
	 *
	 * @param array $gate Held token.
	 * @param array $binding Trusted server binding.
	 * @param int   $now Fresh server time.
	 * @return array Internal created flag and entry; never a remote response.
	 */
	public static function reserve( array $gate, array $binding, $now ) {
		self::binding( $binding );
		self::time( $now );
		list( $before, $value ) = self::load( $gate );
		$slot                   = self::slot( $binding['recipient'] );
		foreach ( $value['entries'] as $entry ) {
			if ( self::slot( $entry['binding']['recipient'] ) === $slot ) {
				if ( $entry['binding'] !== $binding || $now < $entry['reserved'] ) {
					self::fail( 'ai_report_busy' );
				}
				return array(
					'created' => false,
					'entry'   => $entry,
				);
			}
		}
		$expiry = $binding['recipient']['grant_expires'];
		if ( count( $value['entries'] ) >= self::MAX_ENTRIES || $expiry <= $now || $expiry > $now + 3600 ) {
			self::fail();
		}
		try {
			$id = bin2hex( random_bytes( 32 ) );
		} catch ( Throwable $exception ) {
			self::fail();
		}
		foreach ( $value['entries'] as $entry ) {
			if ( $entry['id'] === $id ) {
				self::fail();
			}
		}
		$entry              = array(
			'id'              => $id,
			'revision'        => 1,
			'state'           => 'collecting',
			'binding'         => $binding,
			'reserved'        => $now,
			'review_deadline' => min( $now + WCJ_AI_Report_Receipt::REVIEW_SECONDS, $expiry ),
			'expires'         => min( $now + WCJ_AI_Report_Receipt::SHARE_SECONDS, $expiry ),
			'review_created'  => null,
			'shared_at'       => null,
			'payload_bytes'   => 0,
			'payload_sha256'  => null,
			'payload_b64'     => null,
		);
		$value['entries'][] = $entry;
		self::save( $gate, $before, $value );
		return array(
			'created' => true,
			'entry'   => $entry,
		);
	}

	/**
	 * Read-only reconciliation when a reservation acknowledgement lost its ID.
	 * Absence describes current state, NOT proof of prior failure or retry authority.
	 *
	 * @param array $gate Held token after fresh owner-service authorization.
	 * @param array $binding Exact original server-derived local binding.
	 * @return array|null Retained entry or current absence; never allocates/captures.
	 */
	public static function lookup_reserved( array $gate, array $binding ) {
		self::binding( $binding );
		$value = self::load( $gate )[1];
		$slot  = self::slot( $binding['recipient'] );
		foreach ( $value['entries'] as $entry ) {
			if ( self::slot( $entry['binding']['recipient'] ) === $slot ) {
				if ( $entry['binding'] !== $binding ) {
					self::fail( 'ai_report_busy' );
				}
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Find an exact opaque ID without a caller-controlled storage key.
	 *
	 * @param array  $value Validated journal.
	 * @param string $id Receipt ID.
	 * @return int Entry index.
	 */
	private static function index( array $value, $id ) {
		self::hex( $id );
		foreach ( $value['entries'] as $index => $entry ) {
			if ( $entry['id'] === $id ) {
				return $index;
			}
		}
		self::fail();
	}

	/**
	 * Seal one direct observation's frozen bytes into its exact reservation.
	 * Native origin/fresh authority are the future service's responsibility.
	 *
	 * @param array  $gate Held token after capture and fresh authorization.
	 * @param array  $binding Exact original local binding.
	 * @param string $id Receipt ID.
	 * @param int    $revision Expected collecting revision.
	 * @param string $bytes Complete exact envelope, never browser input.
	 * @param int    $now Fresh server time after capture.
	 * @return array Internal pending entry.
	 */
	public static function finish( array $gate, array $binding, $id, $revision, $bytes, $now ) {
		self::binding( $binding );
		self::time( $now );
		self::integer( $revision );
		list( $before, $value ) = self::load( $gate );
		$index                  = self::index( $value, $id );
		$entry                  = $value['entries'][ $index ];
		if ( $entry['binding'] !== $binding || 'collecting' !== $entry['state'] || $entry['revision'] !== $revision || $now < $entry['reserved'] || $now >= $entry['review_deadline'] ) {
			self::fail();
		}
		try {
			$envelope = WCJ_AI_Report_Receipt::decode( $bytes, $entry['reserved'], $binding['recipient']['grant_expires'] );
			$created  = ( new DateTimeImmutable( $envelope['review_created_at_utc'] ) )->getTimestamp();
		} catch ( Throwable $exception ) {
			self::fail();
		}
		if ( $created > $now ) {
			self::fail();
		}
		$entry['revision']          = 2;
		$entry['state']             = 'pending_review';
		$entry['review_created']    = $created;
		$entry['payload_bytes']     = strlen( $bytes );
		$entry['payload_sha256']    = hash( 'sha256', $bytes );
		$entry['payload_b64']       = base64_encode( $bytes );
		$value['entries'][ $index ] = $entry;
		self::save( $gate, $before, $value );
		return $entry;
	}

	/**
	 * Publish exact reviewed bytes to the bound recipient; no changed expiry.
	 * Caller must first verify current owner cookie/session/capability/nonce/intent.
	 *
	 * @param array  $gate Held token.
	 * @param array  $binding Exact local binding.
	 * @param string $id Receipt ID.
	 * @param int    $revision Exact expected revision (2, or 3 for shared replay).
	 * @param string $digest Digest of the whole bytes the owner reviewed.
	 * @param int    $now Fresh server time.
	 * @return array Internal entry. Never grant remote access from this return alone.
	 */
	public static function share( array $gate, array $binding, $id, $revision, $digest, $now ) {
		self::binding( $binding );
		self::time( $now );
		self::integer( $revision );
		self::hex( $digest );
		list( $before, $value ) = self::load( $gate );
		$index                  = self::index( $value, $id );
		$entry                  = $value['entries'][ $index ];
		if ( $entry['binding'] !== $binding || $entry['payload_sha256'] !== $digest || $now >= $entry['expires'] || null === $entry['review_created'] || $now < $entry['review_created'] ) {
			self::fail();
		}
		if ( 'shared' === $entry['state'] && in_array( $revision, array( 2, 3 ), true ) && $now >= $entry['shared_at'] ) {
			return $entry;
		}
		if ( 'pending_review' !== $entry['state'] || $entry['revision'] !== $revision || $now >= $entry['review_deadline'] ) {
			self::fail();
		}
		$entry['state']             = 'shared';
		$entry['revision']          = 3;
		$entry['shared_at']         = $now;
		$value['entries'][ $index ] = $entry;
		self::save( $gate, $before, $value );
		return $entry;
	}

	/**
	 * Copy already-shared bytes only; separate native authorization is mandatory.
	 * The caller MUST release the gate successfully before emitting these bytes.
	 *
	 * @param array  $gate Held token also covering fresh native AP/grant checks.
	 * @param array  $recipient Fresh server-derived recipient; no owner session.
	 * @param string $id Exact receipt ID.
	 * @param int    $now Fresh server time.
	 * @return string Internal bounded copy, not a registered transport response.
	 */
	public static function read_shared( array $gate, array $recipient, $id, $now ) {
		self::recipient( $recipient );
		self::time( $now );
		$value = self::load( $gate )[1];
		$entry = $value['entries'][ self::index( $value, $id ) ];
		if ( 'shared' !== $entry['state'] || $entry['binding']['recipient'] !== $recipient || $now < $entry['shared_at'] || $now >= $entry['expires'] ) {
			self::fail();
		}
		return base64_decode( $entry['payload_b64'], true );
	}

	/**
	 * Match local cleanup authority; an active old grant is deliberately unnecessary.
	 *
	 * @param array $entry Validated entry.
	 * @param array $owner Fresh owner-service identity, not posted fields.
	 */
	private static function owner( array $entry, array $owner ) {
		self::keys( $owner, array( 'owner', 'site', 'owner_session_digest' ) );
		self::integer( $owner['owner'] );
		self::integer( $owner['site'] );
		self::hex( $owner['owner_session_digest'] );
		if ( $owner['owner'] !== $entry['binding']['recipient']['owner'] || $owner['site'] !== $entry['binding']['recipient']['site'] || $owner['owner_session_digest'] !== $entry['binding']['owner_session_digest'] ) {
			self::fail();
		}
	}

	/**
	 * Inspect exact retained state after uncertainty; no success fabrication/reset.
	 *
	 * @param array  $gate Held token.
	 * @param array  $owner Fresh owner-service identity.
	 * @param string $id Receipt ID.
	 * @return array Private entry, including current revision for deliberate recovery.
	 */
	public static function inspect( array $gate, array $owner, $id ) {
		$value = self::load( $gate )[1];
		$entry = $value['entries'][ self::index( $value, $id ) ];
		self::owner( $entry, $owner );
		return $entry;
	}

	/**
	 * Deliberate exact owner purge, including revoked/expired bindings. Never reset.
	 *
	 * @param array  $gate Held token.
	 * @param array  $owner Fresh owner authority/session after separate checks.
	 * @param string $id Exact receipt ID.
	 * @param int    $revision Expected current entry revision.
	 */
	public static function purge( array $gate, array $owner, $id, $revision ) {
		self::integer( $revision );
		list( $before, $value ) = self::load( $gate );
		$index                  = self::index( $value, $id );
		self::owner( $value['entries'][ $index ], $owner );
		if ( $value['entries'][ $index ]['revision'] !== $revision ) {
			self::fail();
		}
		array_splice( $value['entries'], $index, 1 );
		self::save( $gate, $before, $value );
	}
}
