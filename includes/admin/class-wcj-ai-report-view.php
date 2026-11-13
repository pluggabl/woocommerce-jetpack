<?php
/**
 * Local-only view for the native bounded aggregate observation.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;

/** No storage, query or AI export; preserve exact decimal text and incomplete scope. */
final class WCJ_AI_Report_View {

	/**
	 * Show a finite error label, never third-party exception text.
	 *
	 * @param mixed $reason Native finite reason.
	 */
	private static function unavailable( $reason ) {
		$known = array( 'freshness_unverified', 'query_unavailable', 'stale_or_missing_entity', 'unsupported_currency', 'unsupported_persisted_facts', 'unsupported_amount', 'soft_time_limit', 'stale_membership_or_context' );
		echo '<p>' . esc_html__( 'Unavailable:', 'woocommerce-jetpack' ) . ' ' . esc_html( in_array( $reason, $known, true ) ? $reason : 'observation_unavailable' ) . '</p>';
	}

	/**
	 * Render a trusted service DTO. No monetary coercion or inferred empty groups.
	 *
	 * @param array $report Authorized native observation.
	 */
	public static function render( array $report ) {
		echo '<p><strong>' . esc_html__( 'Incomplete, non-atomic observation. Store membership is unknown, even if no cap is reached. These amounts cannot establish growth, decline or complete store totals.', 'woocommerce-jetpack' ) . '</strong></p>';
		echo '<p>' . esc_html__( 'At most 25 selected entities per stream across BOTH periods, in native ID order; not a representative sample. This report is not saved or shared. Refreshing the POST repeats the observation.', 'woocommerce-jetpack' ) . '</p>';
		$periods = $report['periods']['periods'] ?? array();
		$names   = array( __( 'Preceding seven completed days', 'woocommerce-jetpack' ), __( 'Last seven completed days', 'woocommerce-jetpack' ) );
		if ( $periods ) {
			echo '<p>' . esc_html__( 'Frozen timezone:', 'woocommerce-jetpack' ) . ' ' . esc_html( $report['periods']['timezone'] ) . '</p>';
			echo '<p>' . esc_html__( 'Selection cutoff (UTC):', 'woocommerce-jetpack' ) . ' ' . esc_html( $report['periods']['cutoff_utc'] ) . '</p><ul>';
			foreach ( $periods as $index => $period ) {
				echo '<li>' . esc_html( $names[ $index ] ) . ': ' . esc_html( $period['local_start'] ) . ' — ' . esc_html( $period['local_end'] ) . ' ' . esc_html__( '(local midnight; start included, end excluded)', 'woocommerce-jetpack' ) . '<br>' . esc_html( $period['utc_start'] ) . ' — ' . esc_html( $period['utc_end'] ) . '</li>';
			}
			echo '</ul>';
		}
		echo '<p>' . esc_html__( 'Observation finished (UTC), not an atomic snapshot:', 'woocommerce-jetpack' ) . ' ' . esc_html( $report['observed_until_utc'] ?? '' ) . '</p>';
		if ( isset( $report['reason'] ) ) {
			self::unavailable( $report['reason'] );
		}
		$streams = array(
			'paid_cohort_order_totals' => array(
				__( 'Observed paid-cohort order totals', 'woocommerce-jetpack' ),
				__( 'Selected by paid date and current processing/completed/refunded status. Recorded totals include tax, shipping and fees, before refunds.', 'woocommerce-jetpack' ),
			),
			'recorded_refund_events'   => array(
				__( 'Observed recorded refund-event amounts', 'woocommerce-jetpack' ),
				__( 'Selected independently by refund creation date. Parent orders may fall outside these periods. Not allocated cohort refunds and not return requests.', 'woocommerce-jetpack' ),
			),
		);
		foreach ( $streams as $key => $labels ) {
			echo '<h2>' . esc_html( $labels[0] ) . '</h2><p>' . esc_html( $labels[1] ) . '</p>';
			$stream = $report['streams'][ $key ] ?? null;
			if ( ! is_array( $stream ) || 'observed_subset' !== $stream['status'] || ! is_array( $stream['groups'] ) ) {
				self::unavailable( $stream['reason'] ?? null );
				continue;
			}
			if ( $stream['cap_reached'] ) {
				echo '<p><strong>' . esc_html__( 'Entity cap reached. Additional rows were not observed.', 'woocommerce-jetpack' ) . '</strong></p>';
			}
			if ( ! $stream['groups'] ) {
				echo '<p>' . esc_html__( 'No rows observed; store completeness unknown.', 'woocommerce-jetpack' ) . '</p>';
				continue;
			}
			echo '<table><caption>' . esc_html__( 'Exact recorded decimal amounts, separated by currency', 'woocommerce-jetpack' ) . '</caption><thead><tr><th>' . esc_html__( 'Period', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Currency', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Observed rows', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Recorded amount', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
			foreach ( $stream['groups'] as $group ) {
				echo '<tr><th scope="row">' . esc_html( $names[ $group['period'] ] ) . '</th><td>' . esc_html( $group['currency'] ) . '</td><td>' . esc_html( (string) $group['count'] ) . '</td><td>' . esc_html( $group['amount'] ) . '</td></tr>';
			}
			echo '</tbody></table><p>' . esc_html__( 'For any absent period/currency combination: no matching row observed in this subset — not zero.', 'woocommerce-jetpack' ) . '</p>';
		}
		echo '<h2>' . esc_html__( 'Not available in this slice', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'Net revenue; refunds allocated to paid cohorts; product/variation breakdown; return requests; resumable queries; HPOS. Return requests are not refunds. No cross-currency total or performance comparison is calculated.', 'woocommerce-jetpack' ) . '</p>';
	}
}
