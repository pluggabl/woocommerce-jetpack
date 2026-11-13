<?php
/**
 * Explicit synthetic report DTO for pure receipt contracts, not native evidence.
 *
 * @package Booster_For_WooCommerce/tools
 */

defined( 'ABSPATH' ) || exit;
/** Construct no customer data, hooks or queries. */
final class WCJ_AI_Revenue_Fixture {
	/**
	 * Fixed synthetic source, intentionally not a store query.
	 *
	 * @return array Closed producer-shaped data.
	 */
	public static function source() {
		$calendar = WCJ_AI_Report_Values::completed_week_pair( 1794564000, 'America/New_York' );
		return array(
			'schema'                 => 1,
			'overall_complete'       => false,
			'non_atomic'             => true,
			'membership_complete'    => false,
			'query_basis'            => 'native_filtered_query_subset',
			'resumable'              => false,
			'periods'                => $calendar,
			'storage'                => 'legacy_native_uncached',
			'streams'                => array(
				'paid_cohort_order_totals' => array(
					'status'       => 'observed_subset',
					'amount_basis' => 'recorded_order_total_including_tax_shipping_fees_before_refunds',
					'cap_reached'  => false,
					'groups'       => array(
						array(
							'period'   => 0,
							'currency' => 'USD',
							'count'    => 2,
							'amount'   => '100.35',
						),
						array(
							'period'   => 1,
							'currency' => 'EUR',
							'count'    => 1,
							'amount'   => '70.5',
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
					'status'       => 'observed_subset',
					'amount_basis' => 'recorded_refund_amount_not_return_requests',
					'cap_reached'  => false,
					'groups'       => array(
						array(
							'period'   => 1,
							'currency' => 'USD',
							'count'    => 1,
							'amount'   => '5.25',
						),
					),
				),
			),
			'cohort_refunds'         => null,
			'product_breakdown'      => null,
			'return_requests'        => null,
			'net_revenue'            => null,
			'unavailable_components' => array( 'cohort_refund_allocation', 'product_variation_breakdown', 'return_requests', 'net_revenue' ),
			'limits'                 => array(
				'ids_per_stream'                    => 25,
				'queries'                           => 4,
				'hydrations'                        => 100,
				'soft_seconds'                      => 2,
				'native_operation_duration_bounded' => false,
			),
			'observed_until_utc'     => gmdate( 'Y-m-d\TH:i:s\Z', 1794564000 + 5 ),
		);
	}
}
