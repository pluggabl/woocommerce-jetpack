<?php
/**
 * Pure exact-byte revenue receipt envelope. Dormant: no route, storage or grants.
 *
 * Shape validation never establishes native origin, consent or access authority.
 * The future owner service must bind one direct observation and its reservation.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-observation-projection.php';

/** Freeze the entire bounded export before owner review; never append share time. */
final class WCJ_AI_Report_Receipt {

	/** Engineering transport cap, not a commercial entitlement. */
	const MAX_BYTES = 32768;
	/** Review closes five minutes after the original reservation, never renewed. */
	const REVIEW_SECONDS = 300;
	/** Access closes ten minutes after reservation, or at earlier grant expiry. */
	const SHARE_SECONDS = 600;

	/**
	 * One finite refusal, without input or third-party exception details.
	 *
	 * @throws InvalidArgumentException Always.
	 */
	private static function reject() {
		throw new InvalidArgumentException( 'Unsupported report receipt.' );
	}

	/**
	 * Parse one exact UTC instant without consulting the current clock.
	 *
	 * @param mixed $value UTC text.
	 * @return int Timestamp.
	 */
	private static function instant( $value ) {
		if ( ! is_string( $value ) || 20 !== strlen( $value ) || 1 !== preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value ) ) {
			self::reject();
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $value, new DateTimeZone( 'UTC' ) );
		if ( false === $date || $date->format( 'Y-m-d\TH:i:s\Z' ) !== $value ) {
			self::reject();
		}
		return $date->getTimestamp();
	}

	/**
	 * Validate frozen server-owned times before any addition or encoding.
	 *
	 * @param mixed $reserved Original reservation timestamp.
	 * @param mixed $grant_expires Immutable grant expiry.
	 * @param mixed $created Private review construction timestamp.
	 * @param int   $finished Native observation completion.
	 */
	private static function times( $reserved, $grant_expires, $created, $finished ) {
		foreach ( array( $reserved, $grant_expires, $created ) as $time ) {
			if ( ! is_int( $time ) || $time < 1 || $time > 253402297199 ) {
				self::reject();
			}
		}
		if ( $grant_expires <= $reserved || $grant_expires > $reserved + 3600 ||
			$finished < $reserved || $finished > $created || $created >= min( $reserved + self::REVIEW_SECONDS, $grant_expires ) ) {
			self::reject();
		}
	}

	/**
	 * Encode only a constructed closed envelope, never arbitrary caller objects.
	 *
	 * @param array $envelope Validated complete envelope.
	 * @return string Exact export bytes.
	 */
	private static function encode( array $envelope ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Pure canonical serialization of already validated scalars and bounded arrays; no callbacks.
		$json = json_encode( $envelope, JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) || strlen( $json ) > self::MAX_BYTES ) {
			self::reject();
		}
		return $json;
	}

	/**
	 * Construct the one canonical export with all timestamps frozen before review.
	 *
	 * @param array $snapshot Validated canonical projection.
	 * @param int   $reserved Original reservation timestamp.
	 * @param int   $grant_expires Immutable grant expiry.
	 * @param int   $created Private review construction timestamp.
	 * @return array Complete closed envelope.
	 */
	private static function envelope( array $snapshot, $reserved, $grant_expires, $created ) {
		self::times( $reserved, $grant_expires, $created, self::instant( $snapshot['observed_until_utc'] ) );
		return array(
			'schema_version'         => 1,
			'kind'                   => 'revenue_observation',
			'captured_closed_at_utc' => $snapshot['observed_until_utc'],
			'review_created_at_utc'  => gmdate( 'Y-m-d\TH:i:s\Z', $created ),
			'expires_at_utc'         => gmdate( 'Y-m-d\TH:i:s\Z', min( $reserved + self::SHARE_SECONDS, $grant_expires ) ),
			'source_schema'          => 'wcj.ai.revenue_observation.v1',
			'snapshot'               => $snapshot,
		);
	}

	/**
	 * Build from one direct native observation; this helper does not recollect it.
	 *
	 * @param mixed $source Closed native observation, never a browser-posted DTO.
	 * @param int   $reserved Original server reservation timestamp.
	 * @param int   $grant_expires Bound grant expiry.
	 * @param int   $created Server time after projection construction.
	 * @return string Exact bytes for private preview, hashing and later unchanged read.
	 */
	public static function build( $source, $reserved, $grant_expires, $created ) {
		try {
			$snapshot = WCJ_AI_Observation_Projection::revenue( $source );
		} catch ( Throwable $exception ) {
			self::reject();
		}
		return self::encode( self::envelope( $snapshot, $reserved, $grant_expires, $created ) );
	}

	/**
	 * Revalidate complete stored bytes against immutable local reservation metadata.
	 * Expired bytes remain well-formed; the separate authorizer must deny access.
	 *
	 * @param mixed $bytes Complete stored envelope bytes.
	 * @param int   $reserved Original server reservation timestamp.
	 * @param int   $grant_expires Bound grant expiry.
	 * @return array Exact canonical envelope; no fresh fields or clock reads.
	 */
	public static function decode( $bytes, $reserved, $grant_expires ) {
		if ( ! is_string( $bytes ) || strlen( $bytes ) > self::MAX_BYTES ) {
			self::reject();
		}
		try {
			$input = json_decode( $bytes, true, 16, JSON_THROW_ON_ERROR );
			if ( ! is_array( $input ) || array_keys( $input ) !== array( 'schema_version', 'kind', 'captured_closed_at_utc', 'review_created_at_utc', 'expires_at_utc', 'source_schema', 'snapshot' ) ) {
				self::reject();
			}
			$out = self::envelope( WCJ_AI_Observation_Projection::revenue_payload( $input['snapshot'] ), $reserved, $grant_expires, self::instant( $input['review_created_at_utc'] ) );
			// Re-encoding detects duplicate keys, reordered/noncanonical JSON, invalid
			// constants/times, and hidden fields; the original bytes never get repaired.
			if ( self::encode( $out ) !== $bytes ) {
				self::reject();
			}
			return $out;
		} catch ( Throwable $exception ) {
			self::reject();
		}
	}
}
