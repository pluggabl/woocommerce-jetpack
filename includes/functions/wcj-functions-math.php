<?php
/**
 * Booster for WooCommerce - Functions - Math
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_round' ) ) {
	/**
	 * Wcj_round.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @param   int    $value defines the value.
	 * @param   int    $precision defines the precision.
	 * @param   string $rounding_function defines the rounding_function.
	 */
	function wcj_round( $value, $precision = 0, $rounding_function = 'round' ) {
		return $rounding_function( $value, $precision );
	}
}

if ( ! function_exists( 'wcj_ceil' ) ) {
	/**
	 * Wcj_ceil.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @param   int $value defines the value.
	 * @param   int $precision defines the precision.
	 */
	function wcj_ceil( $value, $precision = 0 ) {
		$fig = (int) str_pad( '1', $precision + 1, '0' );
		return ( ceil( $value * $fig ) / $fig );
	}
}

if ( ! function_exists( 'wcj_floor' ) ) {
	/**
	 * Wcj_floor.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @param   int $value defines the value.
	 * @param   int $precision defines the precision.
	 */
	function wcj_floor( $value, $precision = 0 ) {
		$fig = (int) str_pad( '1', $precision + 1, '0' );
		return ( floor( $value * $fig ) / $fig );
	}
}
