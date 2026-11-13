<?php
/**
 * Closed numeric evidence projection for AI-02; never a price calculator.
 *
 * Unregistered and not used by native callbacks yet. It does not arm a capture,
 * authorize a request, retain data or claim native observation provenance.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Project an already-consumed scalar without invoking arbitrary conversion. */
final class WCJ_AI_Price_Value {

	/**
	 * Return only finite numeric/empty evidence, not private source strings.
	 *
	 * Float text is an observation encoding, not money arithmetic or a value to
	 * feed back into pricing. Native callbacks must always return their original
	 * input/output independently of this projection's success or refusal.
	 *
	 * @param mixed $value Already-consumed native scalar; objects are never cast.
	 * @return array Closed scalar evidence.
	 */
	public static function project( $value ) {
		$unsupported = array(
			'kind'   => 'unsupported',
			'reason' => 'unsupported_numeric_value',
		);
		if ( '' === $value ) {
			return array(
				'kind'        => 'empty_price',
				'native_type' => 'string',
			);
		}
		if ( is_string( $value ) ) {
			if ( strlen( $value ) > 64 || 1 !== preg_match( '/^-?(0|[1-9][0-9]*)(?:\.([0-9]+))?$/D', $value, $parts ) ) {
				return $unsupported;
			}
			if ( strlen( $parts[1] ) > 10 || ( 10 === strlen( $parts[1] ) &&
				( strcmp( $parts[1], '1000000000' ) > 0 || '' !== trim( $parts[2] ?? '', '0' ) ) ) ) {
				return $unsupported;
			}
			return array(
				'kind'        => 'numeric',
				'native_type' => 'string',
				'encoding'    => 'ascii_decimal',
				'value'       => $value,
			);
		}
		if ( is_int( $value ) ) {
			return $value < -1000000000 || $value > 1000000000 ? $unsupported :
				array(
					'kind'        => 'numeric',
					'native_type' => 'integer',
					'encoding'    => 'ascii_decimal',
					'value'       => (string) $value,
				);
		}
		if ( ! is_float( $value ) || ! is_finite( $value ) || $value < -1000000000 || $value > 1000000000 ) {
			return $unsupported;
		}
		// sprintf's decimal separator follows locale; normalize text without changing process locale.
		$locale = localeconv();
		$text   = str_replace( $locale['decimal_point'], '.', sprintf( '%.17g', $value ) );
		if ( strlen( $text ) > 32 || 1 !== preg_match( '/^-?[0-9]+(?:\.[0-9]+)?(?:e[+-]?[0-9]+)?$/D', $text ) ) {
			return $unsupported;
		}
		return array(
			'kind'        => 'numeric',
			'native_type' => 'float',
			'encoding'    => 'binary64_g17_v1',
			'value'       => $text,
		);
	}
}
