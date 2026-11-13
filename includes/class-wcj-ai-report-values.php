<?php
/**
 * Exact value and calendar primitives for the AI-03 report contract.
 *
 * Unregistered, pure functions only: no queries, capabilities, report totals or
 * remote export. A caller must separately qualify persisted source facts.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Closed exact-decimal and completed-calendar-day primitives. */
final class WCJ_AI_Report_Values {

	/** Six decimal places; not a claim about a currency's display precision. */
	const SCALE = 1000000;

	/**
	 * Parse exact persisted decimal text without binary floating point arithmetic.
	 *
	 * Float inputs need a separately qualified source adapter and are not accepted.
	 * Maximum input magnitude is less than one trillion, with six decimal places.
	 *
	 * @param mixed $value Persisted decimal string.
	 * @return int Signed millionths.
	 * @throws InvalidArgumentException For unsupported platform, precision or input.
	 */
	public static function decimal( $value ) {
		if ( PHP_INT_SIZE < 8 || ! is_string( $value ) || strlen( $value ) > 20 ||
			1 !== preg_match( '/^(-?)(0|[1-9][0-9]{0,11})(?:\.([0-9]{1,6}))?$/D', $value, $parts ) ) {
			throw new InvalidArgumentException( 'Unsupported exact decimal.' );
		}
		$units = (int) $parts[2] * self::SCALE + (int) str_pad( $parts[3] ?? '', 6, '0' );
		return '-' === $parts[1] ? -$units : $units;
	}

	/**
	 * Add scaled integers with a pre-operation overflow check.
	 *
	 * @param int $left Scaled value.
	 * @param int $right Scaled value.
	 * @return int Exact sum.
	 * @throws InvalidArgumentException On invalid type, platform or overflow.
	 */
	public static function add( $left, $right ) {
		if ( PHP_INT_SIZE < 8 || ! is_int( $left ) || ! is_int( $right ) ||
			( $right > 0 && $left > PHP_INT_MAX - $right ) ||
			( $right < 0 && $left < PHP_INT_MIN - $right ) ) {
			throw new InvalidArgumentException( 'Exact decimal overflow or invalid input.' );
		}
		return $left + $right;
	}

	/**
	 * Format a scaled integer exactly, including PHP_INT_MIN without abs().
	 *
	 * @param int $units Signed millionths.
	 * @return string Canonical decimal text, no exponent or insignificant zeroes.
	 * @throws InvalidArgumentException On invalid platform/type.
	 */
	public static function format( $units ) {
		if ( PHP_INT_SIZE < 8 || ! is_int( $units ) ) {
			throw new InvalidArgumentException( 'Invalid scaled decimal.' );
		}
		$negative = $units < 0;
		$digits   = ltrim( (string) $units, '-' );
		$digits   = str_pad( $digits, 7, '0', STR_PAD_LEFT );
		$whole    = substr( $digits, 0, -6 );
		$fraction = rtrim( substr( $digits, -6 ), '0' );
		return ( $negative ? '-' : '' ) . $whole . ( '' === $fraction ? '' : '.' . $fraction );
	}

	/**
	 * Resolve exactly one local midnight; never silently normalize a civil date.
	 *
	 * Checking all nearby offsets rejects both nonexistent and repeated midnight,
	 * independently of PHP's ambiguous-time selection policy.
	 *
	 * @param DateTimeImmutable $calendar UTC calendar carrying the wanted date.
	 * @param DateTimeZone      $zone Frozen report timezone.
	 * @return DateTimeImmutable Unique local midnight.
	 * @throws InvalidArgumentException On a missing or ambiguous midnight.
	 */
	private static function midnight( DateTimeImmutable $calendar, DateTimeZone $zone ) {
		$naive       = $calendar->getTimestamp();
		$wanted      = $calendar->format( 'Y-m-d' ) . ' 00:00:00';
		$transitions = $zone->getTransitions( $naive - 172800, $naive + 172800 );
		$offsets     = false === $transitions ? array( $zone->getOffset( $calendar ) ) : array_column( $transitions, 'offset' );
		$matches     = array();
		foreach ( array_unique( $offsets ) as $offset ) {
			$candidate = ( new DateTimeImmutable( '@' . ( $naive - $offset ) ) )->setTimezone( $zone );
			if ( $wanted === $candidate->format( 'Y-m-d H:i:s' ) ) {
				$matches[] = $candidate;
			}
		}
		if ( 1 !== count( $matches ) ) {
			throw new InvalidArgumentException( 'Unsupported missing or repeated local midnight.' );
		}
		return $matches[0];
	}

	/**
	 * Freeze last seven completed local days versus the preceding seven.
	 *
	 * This is a selection cutoff, not an atomic historical database snapshot.
	 * No WordPress option lookup or new clock read occurs inside this function.
	 *
	 * @param int    $cutoff Explicit UTC Unix timestamp, supplied by trusted code.
	 * @param string $timezone Explicit site timezone identifier or fixed offset.
	 * @return array Closed metadata for the two adjacent half-open periods.
	 * @throws InvalidArgumentException For invalid or unsupported date inputs.
	 */
	public static function completed_week_pair( $cutoff, $timezone ) {
		if ( ! is_int( $cutoff ) || $cutoff < 0 || $cutoff > 4102444800 ||
			! is_string( $timezone ) || strlen( $timezone ) > 64 || '' === $timezone ) {
			throw new InvalidArgumentException( 'Unsupported report calendar.' );
		}
		$named = in_array( $timezone, timezone_identifiers_list( DateTimeZone::ALL_WITH_BC ), true );
		if ( ! $named && 1 !== preg_match( '/^[+-](?:0[0-9]|1[0-4]):[0-5][0-9]$/D', $timezone ) ) {
			throw new InvalidArgumentException( 'Use a named timezone or explicit offset.' );
		}
		try {
			$zone = new DateTimeZone( $timezone );
			$now  = ( new DateTimeImmutable( '@' . $cutoff ) )->setTimezone( $zone );
		} catch ( Exception $exception ) {
			throw new InvalidArgumentException( 'Unsupported report timezone.' );
		}
		$utc_zone = new DateTimeZone( 'UTC' );
		$calendar = new DateTimeImmutable( $now->format( 'Y-m-d' ) . 'T00:00:00Z' );
		$end      = self::midnight( $calendar, $zone );
		$middle   = self::midnight( $calendar->modify( '-7 days' ), $zone );
		$start    = self::midnight( $calendar->modify( '-14 days' ), $zone );
		// Include a jump exactly at end: a skipped date may lie inside the civil interval.
		$changes  = $zone->getTransitions( $start->getTimestamp(), $end->getTimestamp() + 1 );
		$previous = null;
		foreach ( false === $changes ? array() : $changes as $change ) {
			if ( null !== $previous && abs( $change['offset'] - $previous ) >= 86400 ) {
				throw new InvalidArgumentException( 'Unsupported whole-day calendar transition.' );
			}
			$previous = $change['offset'];
		}
		$periods = array();
		foreach ( array( array( $start, $middle ), array( $middle, $end ) ) as $pair ) {
			$periods[] = array(
				'local_start'     => $pair[0]->format( 'Y-m-d' ),
				'local_end'       => $pair[1]->format( 'Y-m-d' ),
				'utc_start'       => $pair[0]->setTimezone( $utc_zone )->format( 'Y-m-d\TH:i:s\Z' ),
				'utc_end'         => $pair[1]->setTimezone( $utc_zone )->format( 'Y-m-d\TH:i:s\Z' ),
				'local_days'      => 7,
				'elapsed_seconds' => $pair[1]->getTimestamp() - $pair[0]->getTimestamp(),
			);
		}
		return array(
			'schema_version' => 1,
			'basis'          => 'paid_date_current_status_cohort',
			'interval'       => 'start_inclusive_end_exclusive',
			'timezone'       => $zone->getName(),
			'timezone_kind'  => $named ? 'named' : 'fixed_offset',
			'cutoff_utc'     => $now->setTimezone( $utc_zone )->format( 'Y-m-d\TH:i:s\Z' ),
			'non_atomic'     => true,
			'periods'        => $periods,
		);
	}
}
