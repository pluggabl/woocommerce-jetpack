<?php
/**
 * Pure presentation fixtures; not WordPress HTTP/authentication proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 2 );
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Isolated WordPress presentation stubs only.
define( 'ABSPATH', __DIR__ );
/** Escape a synthetic value.
 *
 * @param mixed $value Fixture text.
 * @return string Escaped text.
 */
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
/** Keep fixture labels untranslated.
 *
 * @param string $value Fixture text.
 * @param string $domain Unused translation domain.
 * @return string Text.
 */
function __( $value, $domain = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve WordPress stub signature.
	return $value;
}
/** Escape an untranslated fixture label.
 *
 * @param string $value Fixture text.
 * @param string $domain Unused translation domain.
 * @return string Escaped text.
 */
function esc_html__( $value, $domain = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve WordPress stub signature.
	return esc_html( $value );
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
require dirname( __DIR__ ) . '/includes/admin/class-wcj-ai-report-view.php';

/** Render one synthetic DTO into a string.
 *
 * @param array $report Synthetic data.
 * @return string HTML.
 */
function wcj_ai_render_fixture( $report ) {
	ob_start();
	WCJ_AI_Report_View::render( $report );
	return ob_get_clean();
}

$wcj_ai_report = array(
	'periods' => null,
	'streams' => array(
		'paid_cohort_order_totals' => array(
			'status'      => 'observed_subset',
			'cap_reached' => true,
			'groups'      => array(
				array(
					'period'   => 0,
					'currency' => 'USD',
					'count'    => 1,
					'amount'   => '0.000001',
				),
				array(
					'period'   => 1,
					'currency' => 'EUR',
					'count'    => 1,
					'amount'   => '1234567890123.123456',
				),
				array(
					'period'   => 1,
					'currency' => 'USD',
					'count'    => 1,
					'amount'   => '0',
				),
			),
		),
		'recorded_refund_events'   => array(
			'status' => 'unavailable',
			'groups' => null,
			'reason' => '<script>leak</script>',
		),
	),
);
$wcj_ai_html   = wcj_ai_render_fixture( $wcj_ai_report );
preg_match_all( '/<tr><th scope="row">([^<]+)<\/th><td>([^<]+)<\/td><td>([^<]+)<\/td><td>([^<]+)<\/td><\/tr>/', $wcj_ai_html, $wcj_ai_rows, PREG_SET_ORDER );
$wcj_ai_tuples = array_map(
	static function ( $row ) {
		return array_slice( $row, 1 );
	},
	$wcj_ai_rows
);
$wcj_ai_cases  = array(
	'exact_period_currency_count_amount_rows' => array(
		array( 'Preceding seven completed days', 'USD', '1', '0.000001' ),
		array( 'Last seven completed days', 'EUR', '1', '1234567890123.123456' ),
		array( 'Last seven completed days', 'USD', '1', '0' ),
	) === $wcj_ai_tuples && 4 === substr_count( $wcj_ai_html, '<tr>' ),
	'exact_small_decimal'                     => str_contains( $wcj_ai_html, '>0.000001<' ),
	'exact_large_decimal'                     => str_contains( $wcj_ai_html, '>1234567890123.123456<' ),
	'observed_zero_not_missing'               => str_contains( $wcj_ai_html, '<td>0</td>' ),
	'currencies_separate'                     => str_contains( $wcj_ai_html, '<td>USD</td>' ) && str_contains( $wcj_ai_html, '<td>EUR</td>' ),
	'absent_not_zero'                         => str_contains( $wcj_ai_html, 'not zero' ),
	'cap_and_incomplete'                      => str_contains( $wcj_ai_html, 'Entity cap reached' ) && str_contains( $wcj_ai_html, 'Incomplete, non-atomic' ),
	'unknown_exception_not_reflected'         => ! str_contains( $wcj_ai_html, 'leak' ) && str_contains( $wcj_ai_html, 'observation_unavailable' ),
	'refund_basis_distinct'                   => str_contains( $wcj_ai_html, 'refund creation date' ) && str_contains( $wcj_ai_html, 'paid date' ),
);
$wcj_ai_report['streams']['paid_cohort_order_totals']['groups'] = array();
$wcj_ai_cases['empty_unknown_not_zero']                         = str_contains( wcj_ai_render_fixture( $wcj_ai_report ), 'No rows observed; store completeness unknown.' );
$wcj_ai_cases['empty_has_no_amount_rows']                       = ! str_contains( wcj_ai_render_fixture( $wcj_ai_report ), '<td>' );
$wcj_ai_report['streams']                                       = array();
$wcj_ai_cases['missing_streams_unavailable']                    = 2 === substr_count( wcj_ai_render_fixture( $wcj_ai_report ), 'Unavailable:' );
$wcj_ai_cases['unavailable_has_no_amount_rows']                 = ! str_contains( wcj_ai_render_fixture( $wcj_ai_report ), '<td>' );
$wcj_ai_report['periods']                                       = array(
	'timezone'   => '<img src=x onerror=bad>',
	'cutoff_utc' => '2026-11-13T12:00:00Z',
	'periods'    => array(
		array(
			'local_start' => '2026-10-30',
			'local_end'   => '2026-11-06',
			'utc_start'   => '2026-10-30T04:00:00Z',
			'utc_end'     => '2026-11-06T05:00:00Z',
		),
		array(
			'local_start' => '2026-11-06',
			'local_end'   => '2026-11-13',
			'utc_start'   => '2026-11-06T05:00:00Z',
			'utc_end'     => '2026-11-13T05:00:00Z',
		),
	),
);
$wcj_ai_html                                    = wcj_ai_render_fixture( $wcj_ai_report );
$wcj_ai_cases['displayed_dynamic_text_escaped'] = str_contains( $wcj_ai_html, '&lt;img src=x onerror=bad&gt;' ) && ! str_contains( $wcj_ai_html, '<img' );
$wcj_ai_cases['frozen_half_open_windows']       = str_contains( $wcj_ai_html, '2026-10-30T04:00:00Z' ) && str_contains( $wcj_ai_html, '2026-11-13T05:00:00Z' ) && str_contains( $wcj_ai_html, 'start included, end excluded' );
foreach ( $wcj_ai_cases as $wcj_ai_name => $wcj_ai_pass ) {
	if ( ! $wcj_ai_pass ) {
		throw new RuntimeException( esc_html( $wcj_ai_name ) );
	}
}
echo json_encode( $wcj_ai_cases, JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WordPress intentionally absent.
