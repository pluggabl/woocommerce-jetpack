<?php
/**
 * Non-stealable mutation gate for the finite, opt-in AI prototype.
 *
 * An abandoned or uncertain gate requires supervised reconciliation. Time is
 * never authority to take over a suspended writer. This is not a lease/fence.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';

/** One site-local gate; acquire before any index or native invoice lock. */
final class WCJ_AI_Mutation_Gate {

	/** Fixed key, never supplied by an external caller. */
	const KEY = 'wcj_ai_mutation_gate';

	/**
	 * Fresh fixed-key read, distinguishing absence from database failure.
	 *
	 * @return array Exact state.
	 * @throws RuntimeException On an unavailable database.
	 */
	private static function read() {
		global $wpdb;
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table; fixed prepared key; authoritative uncached read.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::KEY ), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			throw new RuntimeException( 'ai_gate_unavailable' );
		}
		return array(
			'exists' => null !== $row,
			'raw'    => null === $row ? '' : $row['option_value'],
		);
	}

	/**
	 * Acquire once, with no stale-age or automatic recovery path.
	 *
	 * @return array Exact secret ownership token, never a client response.
	 * @throws RuntimeException On busy or uncertain persistence.
	 */
	public static function acquire() {
		$absent = array(
			'exists' => false,
			'raw'    => '',
		);
		if ( self::read() !== $absent ) {
			throw new RuntimeException( 'ai_gate_busy' );
		}
		$mine = array(
			'exists' => true,
			'raw'    => bin2hex( random_bytes( 32 ) ),
		);
		if ( ! WCJ_Job_Store::compare_exchange( self::KEY, $absent, $mine, array( self::KEY ) ) || self::read() !== $mine ) {
			// Do not release after a lost acknowledgement: ownership was not established.
			throw new RuntimeException( 'ai_gate_outcome_unknown' );
		}
		return $mine;
	}

	/**
	 * Verify the exact token before any protected mutation.
	 *
	 * @param array $mine Token returned by acquire, never a request field.
	 * @throws RuntimeException On token mismatch or unavailable state.
	 */
	public static function assert_held( array $mine ) {
		if ( array_keys( $mine ) !== array( 'exists', 'raw' ) || true !== $mine['exists'] || ! is_string( $mine['raw'] ) ||
			1 !== preg_match( '/^[a-f0-9]{64}$/D', $mine['raw'] ) || self::read() !== $mine ) {
			throw new RuntimeException( 'ai_gate_not_owned' );
		}
	}

	/**
	 * Release only a verified matching token, once, with authoritative readback.
	 *
	 * @param array $mine Acquired token.
	 * @throws RuntimeException On an unknown release outcome.
	 */
	public static function release( array $mine ) {
		self::assert_held( $mine );
		$absent = array(
			'exists' => false,
			'raw'    => '',
		);
		if ( ! WCJ_Job_Store::compare_exchange( self::KEY, $mine, $absent ) || self::read() !== $absent ) {
			throw new RuntimeException( 'ai_gate_release_unknown' );
		}
	}
}
