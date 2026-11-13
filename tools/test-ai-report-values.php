<?php
/**
 * Synthetic value-contract tests, not financial report/query/HPOS proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standalone core constant substitute.
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/class-wcj-ai-report-values.php';

/**
 * Run one pure contract assertion.
 *
 * @param string $name Finite test identifier.
 * @param bool   $condition Expected true result.
 * @throws RuntimeException On failed assertion.
 */
function wcj_ai_value_check( $name, $condition ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'Value assertion failed.' ); }
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI finite developer-authored test identifier, not HTML.
	echo $name . ": pass\n";
}

/**
 * Assert rejection without reflecting arbitrary values into logs.
 *
 * @param string   $name Finite test identifier.
 * @param callable $callback Invalid operation.
 * @throws RuntimeException On missing rejection.
 */
function wcj_ai_value_denied( $name, $callback ) {
	try {
		$callback(); } catch ( InvalidArgumentException $exception ) {
		wcj_ai_value_check( $name, true );
		return;
		}
		throw new RuntimeException( 'Expected value rejection.' );
}

wcj_ai_value_check( 'decimal_01_plus_02', '0.3' === WCJ_AI_Report_Values::format( WCJ_AI_Report_Values::add( WCJ_AI_Report_Values::decimal( '0.1' ), WCJ_AI_Report_Values::decimal( '0.2' ) ) ) );
wcj_ai_value_check( 'negative_fee', -3000000 === WCJ_AI_Report_Values::decimal( '-3' ) );
wcj_ai_value_check( 'six_places', 1 === WCJ_AI_Report_Values::decimal( '0.000001' ) );
wcj_ai_value_check( 'negative_zero', '0' === WCJ_AI_Report_Values::format( WCJ_AI_Report_Values::decimal( '-0.000000' ) ) );
wcj_ai_value_check( 'max_input', 999999999999999999 === WCJ_AI_Report_Values::decimal( '999999999999.999999' ) );
wcj_ai_value_check( 'min_integer_format', '-9223372036854.775808' === WCJ_AI_Report_Values::format( PHP_INT_MIN ) );
wcj_ai_value_check( 'max_integer_format', '9223372036854.775807' === WCJ_AI_Report_Values::format( PHP_INT_MAX ) );
wcj_ai_value_check( 'mixed_sign_safe', -1 === WCJ_AI_Report_Values::add( PHP_INT_MIN, PHP_INT_MAX ) );
foreach ( array( 0.1, 1, null, true, array(), '1e3', ' 1', '1 ', '01', '.5', '1.', '0.0000001', '1000000000000', "1\n", 'NaN', 'INF', '<script>' ) as $wcj_ai_index => $wcj_ai_input ) {
	wcj_ai_value_denied( 'reject_decimal_' . $wcj_ai_index, static fn() => WCJ_AI_Report_Values::decimal( $wcj_ai_input ) );
}
wcj_ai_value_denied( 'positive_overflow', static fn() => WCJ_AI_Report_Values::add( PHP_INT_MAX, 1 ) );
wcj_ai_value_denied( 'negative_overflow', static fn() => WCJ_AI_Report_Values::add( PHP_INT_MIN, -1 ) );
wcj_ai_value_denied( 'non_integer_add', static fn() => WCJ_AI_Report_Values::add( '1', 2 ) );
wcj_ai_value_denied( 'invalid_timezone', static fn() => WCJ_AI_Report_Values::completed_week_pair( 1794747600, 'No/Such_Zone' ) );
wcj_ai_value_denied( 'coerced_cutoff', static fn() => WCJ_AI_Report_Values::completed_week_pair( '1794747600', 'UTC' ) );
wcj_ai_value_denied( 'missing_civil_date_midnight', static fn() => WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2012-01-12T12:00:00Z' ) )->getTimestamp(), 'Pacific/Apia' ) );
wcj_ai_value_denied( 'whole_day_jump_inside_period', static fn() => WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2012-01-06T12:00:00Z' ) )->getTimestamp(), 'Pacific/Apia' ) );
wcj_ai_value_denied( 'ambiguous_midnight', static fn() => WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2025-11-16T12:00:00Z' ) )->getTimestamp(), 'America/Havana' ) );
wcj_ai_value_denied( 'nonexistent_midnight', static fn() => WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2025-03-23T12:00:00Z' ) )->getTimestamp(), 'America/Havana' ) );
wcj_ai_value_denied( 'whole_day_jump_at_end', static fn() => WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2011-12-30T22:00:00Z' ) )->getTimestamp(), 'Pacific/Apia' ) );
$wcj_ai_pair = WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2026-11-15T12:00:00Z' ) )->getTimestamp(), 'America/New_York' );
wcj_ai_value_check( 'fall_local_dates', '2026-11-01' === $wcj_ai_pair['periods'][0]['local_start'] && '2026-11-15' === $wcj_ai_pair['periods'][1]['local_end'] );
wcj_ai_value_check( 'fall_169_hours', 169 * 3600 === $wcj_ai_pair['periods'][0]['elapsed_seconds'] );
wcj_ai_value_check( 'fall_following_168_hours', 168 * 3600 === $wcj_ai_pair['periods'][1]['elapsed_seconds'] );
wcj_ai_value_check( 'fall_utc_boundaries', '2026-11-01T04:00:00Z' === $wcj_ai_pair['periods'][0]['utc_start'] && '2026-11-08T05:00:00Z' === $wcj_ai_pair['periods'][0]['utc_end'] );
wcj_ai_value_check( 'half_open_non_atomic', true === $wcj_ai_pair['non_atomic'] && 'start_inclusive_end_exclusive' === $wcj_ai_pair['interval'] );
$wcj_ai_spring = WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2026-03-15T12:00:00Z' ) )->getTimestamp(), 'America/New_York' );
wcj_ai_value_check( 'spring_167_hours', 167 * 3600 === $wcj_ai_spring['periods'][1]['elapsed_seconds'] );
$wcj_ai_offset = WCJ_AI_Report_Values::completed_week_pair( ( new DateTimeImmutable( '2026-11-15T12:00:00Z' ) )->getTimestamp(), '+05:30' );
wcj_ai_value_check( 'fixed_offset_labeled', 'fixed_offset' === $wcj_ai_offset['timezone_kind'] && 168 * 3600 === $wcj_ai_offset['periods'][0]['elapsed_seconds'] );
// FIN-01 primitives only: report source qualification and refund allocation are not implemented here.
wcj_ai_value_check( 'fin01_cohort_net_including_tax', '135.75' === WCJ_AI_Report_Values::format( WCJ_AI_Report_Values::add( WCJ_AI_Report_Values::decimal( '170.50' ), WCJ_AI_Report_Values::decimal( '-34.75' ) ) ) );
wcj_ai_value_check( 'fin01_newer_cohort_net_including_tax', '100.5' === WCJ_AI_Report_Values::format( WCJ_AI_Report_Values::add( WCJ_AI_Report_Values::decimal( '199.50' ), WCJ_AI_Report_Values::decimal( '-99' ) ) ) );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI runtime version metadata, not HTML.
echo 'php=' . PHP_VERSION . ';tzdb=' . timezone_version_get() . "\n";
