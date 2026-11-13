<?php
/**
 * Native, capped, request-local financial observations. No route is registered.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-report-values.php';

/** First native spine, not a complete revenue/returns report or accounting ledger. */
final class WCJ_AI_Revenue_Observation {

	/**
	 * Fail with a finite reason; never export exception text or raw business data.
	 *
	 * @param string $reason Internal enum.
	 * @throws RuntimeException Always.
	 */
	private static function fail( $reason ) {
		throw new RuntimeException( esc_html( $reason ) );
	}

	/**
	 * Check between operations. One native query/hydration is not time-bounded here.
	 *
	 * @param float $started Monotonic start.
	 */
	private static function budget( $started ) {
		if ( hrtime( true ) / 1e9 - $started > 2 ) {
			self::fail( 'soft_time_limit' );
		}
	}

	/**
	 * Qualify only the native nonpersistent-cache data-store path.
	 *
	 * @return string Storage identity, used for final verification too.
	 */
	private static function storage() {
		$util = '\\Automattic\\WooCommerce\\Utilities\\OrderUtil';
		if ( ! function_exists( 'wc_get_orders' ) || ! class_exists( $util ) ||
			! method_exists( $util, 'get_order_type' ) || ! method_exists( $util, 'custom_orders_table_datastore_cache_enabled' ) ||
			wp_using_ext_object_cache() || $util::custom_orders_table_datastore_cache_enabled() ) {
			self::fail( 'freshness_unverified' );
		}
		$hpos = $util::custom_orders_table_usage_is_enabled();
		if ( $hpos || has_filter( 'get_post_metadata' ) || has_filter( 'default_post_metadata' ) ) {
			self::fail( 'freshness_unverified' );
		}
		$order           = WC_Data_Store::load( 'order' )->get_current_class_name();
		$refund          = WC_Data_Store::load( 'order-refund' )->get_current_class_name();
		$expected_order  = $hpos ? 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore' : 'WC_Order_Data_Store_CPT';
		$expected_refund = $hpos ? 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore' : 'WC_Order_Refund_Data_Store_CPT';
		if ( $order !== $expected_order || $refund !== $expected_refund ) {
			self::fail( 'freshness_unverified' );
		}
		return $hpos ? 'hpos_native_uncached' : 'legacy_native_uncached';
	}

	/**
	 * Query one fixed page plus a sentinel. All query arguments are server-owned.
	 *
	 * @param bool  $refund Refund stream selector.
	 * @param int   $start Inclusive UTC timestamp.
	 * @param int   $end Exclusive UTC timestamp.
	 * @param float $started Monotonic start.
	 * @return array Up to 26 ordered IDs; raw IDs never leave this class.
	 */
	private static function ids( $refund, $start, $end, $started ) {
		self::budget( $started );
		global $wpdb;
		$wpdb->last_error = '';
		$args             = array(
			'type'          => $refund ? 'shop_order_refund' : 'shop_order',
			'status'        => $refund ? array( 'wc-completed' ) : array( 'wc-processing', 'wc-completed', 'wc-refunded' ),
			'limit'         => 26,
			'return'        => 'ids',
			'orderby'       => 'ID',
			'order'         => 'ASC',
			'cache_results' => false,
		);
		$args[ $refund ? 'date_created' : 'date_paid' ] = $start . '...' . ( $end - 1 );
		$ids = wc_get_orders( $args );
		if ( '' !== $wpdb->last_error || ! is_array( $ids ) || array_values( $ids ) !== $ids || count( $ids ) > 26 ) {
			self::fail( 'query_unavailable' );
		}
		$last = 0;
		foreach ( $ids as $index => $id ) {
			if ( ! ( is_int( $id ) || ( is_string( $id ) && ctype_digit( $id ) ) ) || (int) $id <= $last ) {
				self::fail( 'query_unavailable' );
			}
			$ids[ $index ] = (int) $id;
			$last          = (int) $id;
		}
		return $ids;
	}

	/**
	 * Hydrate minimal current persisted facts, never customer text or line objects.
	 *
	 * @param int   $id Internal ID.
	 * @param bool  $refund Stream selector.
	 * @param int   $start Inclusive timestamp.
	 * @param int   $end Exclusive timestamp.
	 * @param float $started Monotonic start.
	 * @return array Minimal local semantic contribution.
	 */
	private static function fact( $id, $refund, $start, $end, $started ) {
		self::budget( $started );
		global $wpdb;
		$wpdb->last_error = '';
		wp_cache_delete( $id, 'posts' );
		wp_cache_delete( $id, 'post_meta' );
		$util = '\\Automattic\\WooCommerce\\Utilities\\OrderUtil';
		if ( $util::get_order_type( $id ) !== ( $refund ? 'shop_order_refund' : 'shop_order' ) ) {
			self::fail( 'stale_or_missing_entity' );
		}
		$entity   = $refund ? new WC_Order_Refund( $id ) : new WC_Order( $id );
		$date     = $refund ? $entity->get_date_created( 'edit' ) : $entity->get_date_paid( 'edit' );
		$status   = $entity->get_status( 'edit' );
		$currency = $entity->get_currency( 'edit' );
		$amount   = $refund ? $entity->get_amount( 'edit' ) : $entity->get_total( 'edit' );
		if ( '' !== $wpdb->last_error ) {
			self::fail( 'query_unavailable' );
		}
		if ( $entity->get_id() !== $id || ! $date || $date->getTimestamp() < $start || $date->getTimestamp() >= $end ||
			! in_array( $status, $refund ? array( 'completed' ) : array( 'processing', 'completed', 'refunded' ), true ) ) {
			self::fail( 'stale_or_missing_entity' );
		}
		if ( ! is_string( $currency ) || 1 !== preg_match( '/^[A-Z]{3}$/D', $currency ) ) {
			self::fail( 'unsupported_currency' );
		}
		$raw_currency = get_post_meta( $id, '_order_currency', false );
		$raw_amount   = get_post_meta( $id, $refund ? '_refund_amount' : '_order_total', false );
		if ( '' !== $wpdb->last_error || count( $raw_currency ) !== 1 || count( $raw_amount ) !== 1 ||
			! is_string( $raw_currency[0] ) || $raw_currency[0] !== $currency || ! is_string( $raw_amount[0] ) ) {
			self::fail( 'unsupported_persisted_facts' );
		}
		try {
			$units = WCJ_AI_Report_Values::decimal( $raw_amount[0] );
			if ( WCJ_AI_Report_Values::decimal( $amount ) !== $units ) {
				self::fail( 'unsupported_persisted_facts' );
			}
		} catch ( InvalidArgumentException $exception ) {
			self::fail( 'unsupported_amount' );
		}
		if ( $units < 0 ) {
			self::fail( 'unsupported_amount' );
		}
		return array( $date->getTimestamp(), $status, $currency, $units, $refund ? $entity->get_parent_id( 'edit' ) : 0 );
	}

	/**
	 * One fixed synchronous observation; every invocation starts afresh.
	 *
	 * No user-supplied date, query, cursor, actor, identifier or continuation state.
	 * Stable filtered membership is not proof of store completeness, even when empty.
	 *
	 * @return array Authorized local aggregate DTO, not a remote export contract.
	 */
	public static function observe() {
		if ( ! get_current_user_id() || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce; no remote principal uses this service yet.
			self::fail( 'not_authorized' );
		}
		$started = hrtime( true ) / 1e9;
		$out     = array(
			'schema'                 => 1,
			'overall_complete'       => false,
			'non_atomic'             => true,
			'membership_complete'    => false,
			'query_basis'            => 'native_filtered_query_subset',
			'resumable'              => false,
			'periods'                => null,
			'storage'                => null,
			'streams'                => array(),
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
		);
		try {
			$out['storage'] = self::storage();
			$timezone       = wp_timezone_string();
			$out['periods'] = WCJ_AI_Report_Values::completed_week_pair( time(), $timezone );
			$periods        = $out['periods']['periods'];
			$start          = ( new DateTimeImmutable( $periods[0]['utc_start'] ) )->getTimestamp();
			$middle         = ( new DateTimeImmutable( $periods[1]['utc_start'] ) )->getTimestamp();
			$end            = ( new DateTimeImmutable( $periods[1]['utc_end'] ) )->getTimestamp();
			foreach ( array( false, true ) as $refund ) {
				$name                    = $refund ? 'recorded_refund_events' : 'paid_cohort_order_totals';
				$out['streams'][ $name ] = array(
					'status'       => 'unavailable',
					'amount_basis' => $refund ? 'recorded_refund_amount_not_return_requests' : 'recorded_order_total_including_tax_shipping_fees_before_refunds',
					'groups'       => null,
				);
				try {
					$ids    = self::ids( $refund, $start, $end, $started );
					$facts  = array();
					$groups = array();
					foreach ( array_slice( $ids, 0, 25 ) as $id ) {
						$fact         = self::fact( $id, $refund, $start, $end, $started );
						$facts[ $id ] = $fact;
						$period       = $fact[0] < $middle ? 0 : 1;
						$key          = $period . ':' . $fact[2];
						if ( ! isset( $groups[ $key ] ) ) {
							$groups[ $key ] = array(
								'period'   => $period,
								'currency' => $fact[2],
								'count'    => 0,
								'units'    => 0,
							);
						}
						++$groups[ $key ]['count'];
						$groups[ $key ]['units'] = WCJ_AI_Report_Values::add( $groups[ $key ]['units'], $fact[3] );
					}
					foreach ( $facts as $id => $fact ) {
						if ( self::fact( $id, $refund, $start, $end, $started ) !== $fact ) {
							self::fail( 'stale_or_missing_entity' );
						}
					}
					if ( self::ids( $refund, $start, $end, $started ) !== $ids || self::storage() !== $out['storage'] || wp_timezone_string() !== $timezone ) {
						self::fail( 'stale_membership_or_context' );
					}
					ksort( $groups, SORT_STRING );
					foreach ( $groups as &$group ) {
						$group['amount'] = WCJ_AI_Report_Values::format( $group['units'] );
						unset( $group['units'] );
					}
					unset( $group );
					$out['streams'][ $name ]['status']      = 'observed_subset';
					$out['streams'][ $name ]['cap_reached'] = count( $ids ) > 25;
					$out['streams'][ $name ]['groups']      = array_values( $groups );
				} catch ( Throwable $exception ) {
					$out['streams'][ $name ]['reason'] = self::reason( $exception );
				}
			}
		} catch ( Throwable $exception ) {
			$out['reason'] = self::reason( $exception );
		}
		$out['observed_until_utc'] = gmdate( 'Y-m-d\TH:i:s\Z' );
		return $out;
	}

	/**
	 * Reduce even third-party exceptions to a closed reason, never raw text.
	 *
	 * @param Throwable $exception Caught failure.
	 * @return string Finite reason.
	 */
	private static function reason( Throwable $exception ) {
		$known = array( 'freshness_unverified', 'query_unavailable', 'stale_or_missing_entity', 'unsupported_currency', 'unsupported_persisted_facts', 'unsupported_amount', 'soft_time_limit', 'stale_membership_or_context' );
		return in_array( $exception->getMessage(), $known, true ) ? $exception->getMessage() : 'observation_unavailable';
	}
}
