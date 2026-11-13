<?php
/**
 * Closed grant storage profiles. Shape is not authentication or active authority.
 *
 * Invoice schema 1 remains invoice-only; schema 2 is exclusively saved-report read.
 * No migration, additive scope union, clock, database, native user or AP lookup.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Pure parser for exact server-generated grant bytes, including inactive grants. */
final class WCJ_AI_Grant_Profile {

	const MAX_BYTES       = 4096;
	const INVOICE_READ    = 'invoice_read';
	const INVOICE_PREPARE = 'invoice_prepare';
	const REPORT_READ     = 'report_read_shared';

	/**
	 * Recognize only three exact version/scope pairs, never a subset/wildcard match.
	 *
	 * @param array $grant Structurally checked grant or a candidate version/profile.
	 * @return string|null Closed internal profile.
	 */
	public static function profile( array $grant ) {
		if ( 1 === ( $grant['schema'] ?? null ) ) {
			if ( array( 'invoice:read' ) === ( $grant['scopes'] ?? null ) ) {
				return self::INVOICE_READ;
			}
			if ( array( 'invoice:read', 'invoice:prepare' ) === ( $grant['scopes'] ?? null ) ) {
				return self::INVOICE_PREPARE;
			}
		}
		return 2 === ( $grant['schema'] ?? null ) && array( 'report:read_shared' ) === ( $grant['scopes'] ?? null ) ? self::REPORT_READ : null;
	}

	/**
	 * Strict canonical closed JSON. Unknown/duplicate keys or coercions never repair.
	 * The existing issuer writes compact canonical bytes with these ASCII scalars.
	 *
	 * @param mixed $raw Bounded stored bytes, not an authorization claim.
	 * @return array|null Structurally valid grant; inactive/expired stays manageable.
	 */
	public static function decode( $raw ) {
		if ( ! is_string( $raw ) || strlen( $raw ) > self::MAX_BYTES ) {
			return null;
		}
		try {
			$grant = json_decode( $raw, true, 8, JSON_THROW_ON_ERROR );
		} catch ( Throwable $exception ) {
			return null;
		}
		if ( ! is_array( $grant ) || array_keys( $grant ) !== array( 'schema', 'id', 'principal', 'owner', 'site', 'app_uuid', 'scopes', 'created', 'expires', 'active' ) ||
			null === self::profile( $grant ) || ! is_string( $grant['id'] ) || 1 !== preg_match( '/^[a-f0-9]{32}$/D', $grant['id'] ) ||
			! is_string( $grant['app_uuid'] ) || 1 !== preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $grant['app_uuid'] ) || ! is_bool( $grant['active'] ) ) {
			return null;
		}
		foreach ( array( 'owner', 'site', 'principal' ) as $key ) {
			if ( ! is_int( $grant[ $key ] ) || $grant[ $key ] < 1 || ( 2 === $grant['schema'] && $grant[ $key ] > 2147483647 ) ) {
				return null;
			}
		}
		if ( $grant['owner'] === $grant['principal'] ) {
			return null;
		}
		foreach ( array( 'created', 'expires' ) as $key ) {
			if ( ! is_int( $grant[ $key ] ) || $grant[ $key ] < 1 || $grant[ $key ] > 253402297199 ) {
				return null;
			}
		}
		if ( $grant['expires'] <= $grant['created'] || $grant['expires'] > $grant['created'] + 3600 ) {
			return null;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Closed pure ASCII scalar/array profile; no hooks, objects or callbacks.
		return json_encode( $grant ) === $raw ? $grant : null;
	}
}
