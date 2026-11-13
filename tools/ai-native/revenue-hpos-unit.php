<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- CLI-only native doubles.
/**
 * HPOS observer model tests. Native classes, queries and storage are FAKE.
 * No WordPress/bootstrap, real SQL, installation, authority or client proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Universal.Namespaces, WordPress.WP.GlobalVariablesOverride, Squiz.Commenting, Generic.Files.OneObjectStructurePerFile, Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.WP.AlternativeFunctions, Universal.Files.SeparateFunctionsFromOO.Mixed, WordPress.Files.FileName.InvalidClassFileName -- CLI-only native doubles, never included by runtime source.
namespace Automattic\WooCommerce\Utilities {

	final class OrderUtil {
		public static function get_order_type( $id ) {
			return 'shop_order'; }
		public static function custom_orders_table_datastore_cache_enabled() {
			return $GLOBALS['model']['cache']; }
		public static function custom_orders_table_usage_is_enabled() {
			return true; }
		public static function custom_orders_table_data_sync_is_enabled() {
			return $GLOBALS['model']['sync']; }
	}
}

namespace Automattic\WooCommerce\Internal\DataStores\Orders {

	final class OrdersTableDataStore {
		public static function get_orders_table_name() {
			return 'fake_wc_orders'; }
		public static function get_meta_table_name() {
			return 'fake_wc_orders_meta'; }
		public static function get_operational_data_table_name() {
			return 'fake_wc_order_operational_data'; }
	}
}

namespace {
	if ( 'cli' !== PHP_SAPI ) {
		exit( 1 ); }
	define( 'ABSPATH', __DIR__ );
	define( 'WC_VERSION', '11.1.0' );
	define( 'ARRAY_A', 'ARRAY_A' );
	require_once dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-revenue-observation.php';
	function esc_html( $s ) {
		return $s; }
	function get_current_user_id() {
		return 1; }
	function current_user_can( $cap ) {
		return true; }
	function wp_timezone_string() {
		return 'America/New_York'; }
	function wp_using_ext_object_cache() {
		return $GLOBALS['model']['external']; }
	function has_filter( $hook ) {
		return in_array( $hook, $GLOBALS['model']['hooks'], true ); }
	function wc_get_orders( $args ) {
		++$GLOBALS['queries'];
		$GLOBALS['arguments'][] = $args;
		$ids                    = 'shop_order' === $args['type'] ? $GLOBALS['model']['orders'] : $GLOBALS['model']['refunds'];
		if ( $GLOBALS['model']['query_error'] ) {
			$GLOBALS['wpdb']->last_error = 'FAKE SQL failure'; }
		return $GLOBALS['model']['membership_drift'] && 2 === $GLOBALS['queries'] ? array() : $ids;
	}
	final class WC_Data_Store {
		private $kind;
		public function __construct( $kind ) {
			$this->kind = $kind; }
		public static function load( $kind ) {
			return new self( $kind ); }
		public function get_current_class_name() {
			return $GLOBALS['model']['wrong_store'] ? 'CustomStore' : 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\' . ( 'order' === $this->kind ? 'OrdersTableDataStore' : 'OrdersTableRefundDataStore' );
		}
	}
	class WC_Order {
		protected $id;
		protected $row;
		public function __construct( $id ) {
			$this->id  = $id;
			$this->row = $GLOBALS['model']['rows'][ $id ];
			++$GLOBALS['hydrations'];
			if ( $GLOBALS['model']['fact_drift'] && 1 === $GLOBALS['hydrations'] ) {
				$GLOBALS['model']['rows'][ $id ]['total_amount'] = '13.34000000'; }
		}
		public function get_id() {
			return $this->id; }
		public function get_type() {
			return $this->row['type']; }
		public function get_status( $context ) {
			return substr( $this->row['status'], 3 ); }
		public function get_currency( $context ) {
			return $GLOBALS['model']['native_currency'] ?? $this->row['currency']; }
		public function get_total( $context ) {
			return $GLOBALS['model']['native_total'] ?? $this->row['total_amount']; }
		public function get_date_paid( $context ) {
			return new DateTimeImmutable( $GLOBALS['model']['dates'][0]['date_paid_gmt'], new DateTimeZone( 'UTC' ) ); }
		public function get_date_created( $context ) {
			return new DateTimeImmutable( $this->row['date_created_gmt'], new DateTimeZone( 'UTC' ) ); }
		public function get_parent_id( $context ) {
			return (int) $this->row['parent_order_id']; }
	}
	final class WC_Order_Refund extends WC_Order {
		public function get_status( $context ) {
			return 'completed'; }
		public function get_amount( $context ) {
			return $GLOBALS['model']['amounts'][0]; }
	}
	// phpcs:ignore PEAR.NamingConventions.ValidClassName.StartWithCapital -- Deliberate exact native class-name double.
	final class wpdb {
		public $last_error = '';
		public function prepare( $query, ...$args ) {
			return array( $query, $args ); }
		public function get_results( $request, $format ) {
			$GLOBALS['raw_queries'][] = $request;
			if ( $GLOBALS['model']['raw_error'] ) {
				$this->last_error = 'FAKE SQL failure';
				return null; }
			return str_contains( $request[0], 'date_paid_gmt' ) ? $GLOBALS['model']['dates'] : ( isset( $GLOBALS['model']['rows'][ $request[1][1] ] ) ? array( $GLOBALS['model']['rows'][ $request[1][1] ] ) : array() );
		}
		public function get_col( $request ) {
			$GLOBALS['raw_queries'][] = $request;
			return $GLOBALS['model']['amounts'];
		}
	}
	final class WP_Hook {
		public $callbacks = array();
	}
	$calendar = WCJ_AI_Report_Values::completed_week_pair( time(), wp_timezone_string() );
	$older    = strtotime( $calendar['periods'][0]['utc_start'] ) + 3600;
	$newer    = strtotime( $calendar['periods'][1]['utc_start'] ) + 3600;
	$baseline = array(
		'cache'            => false,
		'sync'             => false,
		'external'         => false,
		'hooks'            => array(),
		'wrong_store'      => false,
		'query_error'      => false,
		'raw_error'        => false,
		'membership_drift' => false,
		'fact_drift'       => false,
		'orders'           => array( 101 ),
		'refunds'          => array( 202 ),
		'amounts'          => array( '2.50000000' ),
		'dates'            => array(
			array(
				'order_id'      => '101',
				'date_paid_gmt' => gmdate( 'Y-m-d H:i:s', $older ),
			),
		),
		'rows'             => array(
			101 => array(
				'id'               => '101',
				'type'             => 'shop_order',
				'status'           => 'wc-completed',
				'currency'         => 'USD',
				'total_amount'     => '12.34000000',
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s', $older - 100 ),
				'parent_order_id'  => '0',
			),
			202 => array(
				'id'               => '202',
				'type'             => 'shop_order_refund',
				'status'           => 'wc-completed',
				'currency'         => 'EUR',
				'total_amount'     => '-2.50000000',
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s', $newer ),
				'parent_order_id'  => '303',
			),
		),
	);
	$cases    = array();
	function reset_model() {
		$GLOBALS['wp_filter']   = array();
		$GLOBALS['model']       = $GLOBALS['baseline'];
		$GLOBALS['wp_filter']   = array();
		$GLOBALS['wpdb']        = new wpdb();
		$GLOBALS['queries']     = 0;
		$GLOBALS['hydrations']  = 0;
		$GLOBALS['arguments']   = array();
		$GLOBALS['raw_queries'] = array();
	}
	function check( $name, $ok ) {
		$GLOBALS['cases'][ $name ] = true === $ok; }
	function invoke_private( $method, ...$args ) {
		return ( new ReflectionMethod( WCJ_AI_Revenue_Observation::class, $method ) )->invoke( null, ...$args ); }
	function refuses( $name, $method, $value ) {
		try {
			invoke_private( $method, $value );
			check( $name, false ); } catch ( RuntimeException $e ) {
			check( $name, in_array( $e->getMessage(), array( 'unsupported_amount', 'unsupported_persisted_facts' ), true ) ); }
	}
	reset_model();
	$before = $model;
	$out    = WCJ_AI_Revenue_Observation::observe();
	check(
		'model_hpos_order_total',
		array(
			array(
				'period'   => 0,
				'currency' => 'USD',
				'count'    => 1,
				'amount'   => '12.34',
			),
		) === $out['streams']['paid_cohort_order_totals']['groups']
	);
	check(
		'model_hpos_refund_own_currency_period',
		array(
			array(
				'period'   => 1,
				'currency' => 'EUR',
				'count'    => 1,
				'amount'   => '2.5',
			),
		) === $out['streams']['recorded_refund_events']['groups']
	);
	check( 'model_storage_and_limits', 'hpos_native_uncached' === $out['storage'] && false === $out['overall_complete'] && false === $out['resumable'] && null === $out['net_revenue'] );
	check( 'model_two_fact_passes_four_membership_queries', 4 === $hydrations && 4 === $queries && 8 === count( $raw_queries ) );
	check( 'model_no_mutation', $before === $model );
	check( 'model_fixed_query', 26 === $arguments[0]['limit'] && 'ids' === $arguments[0]['return'] && false === $arguments[0]['cache_results'] && 'ASC' === $arguments[0]['order'] );
	foreach ( array( 'cache', 'sync', 'external', 'wrong_store' ) as $fault ) {
		reset_model();
		$model[ $fault ] = true;
		$out             = WCJ_AI_Revenue_Observation::observe();
		check( 'before_query_denial_' . $fault, isset( $out['reason'] ) && 'freshness_unverified' === $out['reason'] && 0 === $queries && 0 === $hydrations );
	}
	foreach ( array( 'woocommerce_order_query_args', 'woocommerce_order_query', 'woocommerce_orders_table_datastore_get_orders_query', 'woocommerce_hpos_pre_query', 'woocommerce_orders_table_query_clauses', 'woocommerce_orders_table_query_sql', 'woocommerce_orders_table_query_count_sql', 'get_date_sql', 'date_query_valid_columns', 'wc_order_statuses', 'all', 'query' ) as $hook ) {
		reset_model();
		$model['hooks'] = array( $hook );
		if ( 'query' === $hook ) {
			$wp_filter['query']            = new WP_Hook();
			$wp_filter['query']->callbacks = array(
				10 => array(
					'custom' => array(
						'function'      => static fn( $sql ) => 'REWRITE_MARKER',
						'accepted_args' => 1,
					),
				),
			); }
		$out = WCJ_AI_Revenue_Observation::observe();
		check( 'before_query_denial_' . $hook, isset( $out['reason'] ) && 'freshness_unverified' === $out['reason'] && 0 === $queries && 0 === $hydrations );
	}

	reset_model();
	$model['hooks']                = array( 'query' );
	$wp_filter['query']            = new WP_Hook();
	$wp_filter['query']->callbacks = array(
		0 => array(
			'native' => array(
				'function'      => array( $wpdb, 'remove_placeholder_escape' ),
				'accepted_args' => 1,
			),
		),
	);
	$out                           = WCJ_AI_Revenue_Observation::observe();
	check( 'exact_native_placeholder_callback_allowed', 'observed_subset' === $out['streams']['paid_cohort_order_totals']['status'] );
	foreach ( array( 'wrong_object', 'wrong_method', 'wrong_priority', 'wrong_arity', 'extra_callback' ) as $fault ) {
		reset_model();
		$model['hooks']     = array( 'query' );
		$wp_filter['query'] = new WP_Hook();
		$entry              = array(
			'function'      => array( $wpdb, 'remove_placeholder_escape' ),
			'accepted_args' => 1,
		);
		if ( 'wrong_object' === $fault ) {
			$entry['function'][0] = new wpdb(); }
		if ( 'wrong_method' === $fault ) {
			$entry['function'][1] = 'query'; }
		if ( 'wrong_arity' === $fault ) {
			$entry['accepted_args'] = 2; }
		$wp_filter['query']->callbacks = array( 'wrong_priority' === $fault ? 1 : 0 => array( 'native' => $entry ) );
		if ( 'extra_callback' === $fault ) {
			$wp_filter['query']->callbacks[0]['another'] = $entry; }
		$out = WCJ_AI_Revenue_Observation::observe();
		check( 'placeholder_callback_refusal_' . $fault, isset( $out['reason'] ) && 'freshness_unverified' === $out['reason'] && 0 === $queries );
	}

	reset_model();
	$wp_filter['query'] = new WP_Hook();
	$out                = WCJ_AI_Revenue_Observation::observe();
	check( 'empty_native_hook_allowed', 'observed_subset' === $out['streams']['paid_cohort_order_totals']['status'] );
	reset_model();
	$wp_filter['query'] = new class() {
		public function has_filter() {
			return false; }
		public function apply_filters( $value ) {
			return 'SQL_REWRITE_MARKER'; }
	};
	$out                = WCJ_AI_Revenue_Observation::observe();
	check( 'false_reporting_custom_hook_refused', isset( $out['reason'] ) && 'freshness_unverified' === $out['reason'] && 0 === $queries );
	foreach ( array( null, false, 'malformed', new stdClass() ) as $n => $malformed ) {
		reset_model();
		$wp_filter['query']            = new WP_Hook();
		$wp_filter['query']->callbacks = $malformed;
		$out                           = WCJ_AI_Revenue_Observation::observe();
		check( 'malformed_callback_registry_' . $n, isset( $out['reason'] ) && 'freshness_unverified' === $out['reason'] && 0 === $queries );
	}
	foreach ( array( 'query_error', 'raw_error', 'membership_drift', 'fact_drift' ) as $fault ) {
		reset_model();
		$model[ $fault ] = true;
		$out             = WCJ_AI_Revenue_Observation::observe();
		check( 'stream_refusal_' . $fault, 'unavailable' === $out['streams']['paid_cohort_order_totals']['status'] && null === $out['streams']['paid_cohort_order_totals']['groups'] );
	}
	foreach ( array(
		'status'           => 'wc-pending',
		'currency'         => 'eur',
		'type'             => 'shop_order',
		'parent_order_id'  => '0',
		'id'               => '203',
		'total_amount'     => '2.50000000',
		'date_created_gmt' => '2026-02-30 00:00:00',
	) as $field => $value ) {
		reset_model();
		$model['rows'][202][ $field ] = $value;
		$out                          = WCJ_AI_Revenue_Observation::observe();
		check( 'raw_refund_reject_' . $field, 'unavailable' === $out['streams']['recorded_refund_events']['status'] && 'observed_subset' === $out['streams']['paid_cohort_order_totals']['status'] );
	}
	foreach ( array( array(), array( '2.5', '2.5' ), array( null ), array( '-2.5' ), array( '2.50000001' ), array( str_repeat( '1', 29 ) ) ) as $n => $value ) {
		reset_model();
		$model['amounts'] = $value;
		$out              = WCJ_AI_Revenue_Observation::observe();
		check( 'raw_refund_metadata_' . $n, 'unavailable' === $out['streams']['recorded_refund_events']['status'] );
	}
	foreach ( array( null, '0000-00-00 00:00:00', '2026-02-30 00:00:00', gmdate( 'Y-m-d H:i:s', strtotime( $calendar['periods'][1]['utc_end'] ) ) ) as $n => $value ) {
		reset_model();
		$model['dates'][0]['date_paid_gmt'] = $value;
		$out                                = WCJ_AI_Revenue_Observation::observe();
		check( 'paid_date_refusal_' . $n, 'unavailable' === $out['streams']['paid_cohort_order_totals']['status'] );
	}
	reset_model();
	$model['native_currency'] = 'GBP';
	$out                      = WCJ_AI_Revenue_Observation::observe();
	check( 'raw_native_currency_mismatch', 'unavailable' === $out['streams']['paid_cohort_order_totals']['status'] );
	reset_model();
	$model['native_total'] = 12.34;
	$out                   = WCJ_AI_Revenue_Observation::observe();
	check( 'native_float_refused', 'unavailable' === $out['streams']['paid_cohort_order_totals']['status'] );
	reset_model();
	$model['orders']  = array();
	$model['refunds'] = array();
	$out              = WCJ_AI_Revenue_Observation::observe();
	check( 'model_empty_subset_not_complete', array() === $out['streams']['paid_cohort_order_totals']['groups'] && false === $out['membership_complete'] && 4 === $queries );
	foreach ( array(
		'12.34000000' => 12340000,
		'-2.50000000' => -2500000,
		'0.00000000'  => 0,
		'0.00000100'  => 1,
	) as $value => $units ) {
		check( 'decimal_' . $value, invoke_private( 'sql_decimal', $value ) === $units ); }
	foreach ( array( '0.00000001', '1e2', ' 1', '01.00', '1000000000000', 'NaN', 12.34, null, '1.000000000' ) as $n => $value ) {
		refuses( 'decimal_bad_' . $n, 'sql_decimal', $value ); }
	check( 'id_above_32bit', 2147483648 === invoke_private( 'identifier', '2147483648' ) );
	check( 'id_max', PHP_INT_MAX === invoke_private( 'identifier', (string) PHP_INT_MAX ) );
	foreach ( array( '9223372036854775808', '01', 0, -1, 1.0, null, '1e2' ) as $n => $value ) {
		refuses( 'id_bad_' . $n, 'identifier', $value ); }
	echo json_encode(
		array(
			'scope'      => 'FAKE native/storage model only; no native HPOS, SQL, browser or client proof.',
			'assertions' => count( $cases ),
			'cases'      => $cases,
		),
		JSON_PRETTY_PRINT
	), "\n";
	exit( in_array( false, $cases, true ) ? 1 : 0 );
}
