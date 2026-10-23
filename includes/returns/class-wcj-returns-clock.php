<?php
/**
 * Internal native monotonic clock admission for Booster.
 *
 * @package Booster_For_WooCommerce
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCJ_Returns_Clock', false ) ) :
final class WCJ_Returns_Clock {
	/** @var int|null Last admitted request-local nanosecond sample. */
	private static $last = null;
	/** @var bool Sticky request-local failure; deliberately no reset. */
	private static $failed = false;

	/**
	 * Internal cross-class bridge, not a WordPress API or configurable provider.
	 *
	 * @return int Nonnegative native monotonic nanoseconds.
	 * @throws RuntimeException Native admission or sample validation failed.
	 */
	public static function read() {
		if ( self::$failed ) {
			throw new RuntimeException( 'clock_unavailable' );
		}
		try {
			if ( PHP_VERSION_ID < 70300 || PHP_INT_SIZE < 8 || ! function_exists( 'hrtime' ) ) {
				throw new RuntimeException( 'clock_unavailable' );
			}
			$native = new ReflectionFunction( 'hrtime' );
			if ( ! $native->isInternal() ) {
				throw new RuntimeException( 'clock_unavailable' );
			}
			return self::accept( \hrtime( true ) );
		} catch ( Throwable $error ) {
			self::$failed = true;
			throw new RuntimeException( 'clock_unavailable' );
		}
	}

	/**
	 * Validate a native sample without coercion. Equal samples are allowed.
	 *
	 * @param mixed $sample Actual native return value.
	 * @return int Admitted sample.
	 * @throws RuntimeException Invalid or regressed sample.
	 */
	private static function accept( $sample ) {
		if ( self::$failed || ! is_int( $sample ) || $sample < 0 || ( null !== self::$last && $sample < self::$last ) ) {
			self::$failed = true;
			throw new RuntimeException( 'clock_unavailable' );
		}
		self::$last = $sample;
		return $sample;
	}
}
endif;
