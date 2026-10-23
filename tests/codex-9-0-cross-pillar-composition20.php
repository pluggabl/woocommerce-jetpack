<?php
/**
 * New cross-pillar composition20 source checks. Not a native WP/WC installation.
 *
 * Actual product classes are imported. Only WP/WC/storage boundaries are inert.
 *
 * @package Booster_For_WooCommerce_Tests
 */

if ( PHP_SAPI !== 'cli' || $argc !== 2 || ! in_array( $argv[1], array( 'free', 'plus', 'elite' ), true ) ) {
	exit( 64 );
}
error_reporting( E_ALL );
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
define( 'ABSPATH', __DIR__ . '/' );
define(
	'WCJ_PLUGIN_FILE',
	array(
		'free'  => 'woocommerce-jetpack.php',
		'plus'  => 'booster-plus-for-woocommerce.php',
		'elite' => 'booster-elite-for-woocommerce.php',
	)[ $argv[1] ]
);
define( 'WC_VERSION', '11.1.0' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['c20_capable']        = true;
$GLOBALS['c20_options']        = array();
$GLOBALS['c20_modules']        = array();
$GLOBALS['c20_registered']     = array();
$GLOBALS['c20_order_queries']  = array();
$GLOBALS['c20_database_calls'] = 0;
$GLOBALS['c20_mutations']      = 0;
$GLOBALS['wp_filter']          = array();
$_GET                          = $_POST = $_REQUEST = array();
/** Synthetic WP primitive, not a production product class. */
class WP_Error {
	/**
	 * Synthetic fixed error code.
	 *
	 * @var string
	 */
	private $code;
	/**
	 * Retain the synthetic error code without invoking WordPress.
	 *
	 * @param string $code    Synthetic public error code.
	 * @param string $message Unused message accepted at the primitive boundary.
	 */
	public function __construct( $code, $message = '' ) {
		$this->code = $code;
	}
	/**
	 * Return the synthetic error code.
	 *
	 * @return string
	 */
	public function get_error_code() {
		return $this->code;
	}
}
/** Explicit unavailable storage boundary. No database handle or query exists. */
class C20_Unavailable_Database {
	/**
	 * Deliberately invalid prefix prevents real storage access.
	 *
	 * @var string
	 */
	public $prefix = 'c20-invalid!';
	/**
	 * Record and reject any attempt to use the unavailable database.
	 *
	 * @param string $name      Attempted database method.
	 * @param array  $arguments Unused attempted method arguments.
	 * @throws RuntimeException Always rejects unavailable storage.
	 * @return never
	 */
	public function __call( $name, $arguments ) {
		++$GLOBALS['c20_database_calls'];
		throw new RuntimeException( 'Unexpected database primitive' );
	}
}
$GLOBALS['wpdb'] = new C20_Unavailable_Database();
/**
 * Recognize only the local WordPress error primitive.
 *
 * @param mixed $value Value to inspect.
 * @return bool
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}
/**
 * Return literal translation text without a translation engine.
 *
 * @param string      $text   Source text.
 * @param string|null $domain Unused translation domain.
 * @return string
 */
function __( $text, $domain = null ) {
	return $text;
}
/**
 * Return the fixed synthetic authenticated actor.
 *
 * @return int
 */
function get_current_user_id() {
	return 71;
}
/**
 * Apply the local capability switch to the one admitted capability.
 *
 * @param string $capability Requested capability.
 * @return bool
 */
function current_user_can( $capability ) {
	return 'manage_woocommerce' === $capability && $GLOBALS['c20_capable'];
}
/**
 * Record an inert action without executing its callback.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback to record.
 * @param int      $priority Callback priority.
 * @param int      $accepted Accepted argument count.
 * @return bool
 */
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
	return add_filter( $hook, $callback, $priority, $accepted );
}
/**
 * Record an inert filter without executing its callback.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback to record.
 * @param int      $priority Callback priority.
 * @param int      $accepted Accepted argument count.
 * @return bool
 */
function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
	if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
		$GLOBALS['wp_filter'][ $hook ] = (object) array( 'callbacks' => array() );
	}
	$GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][] = array(
		'function'      => $callback,
		'accepted_args' => $accepted,
	);
	return true;
}
/**
 * Return a fixed non-WordPress lifecycle name.
 *
 * @return string
 */
function current_filter() {
	return 'c20_offline_source';
}
/**
 * Read the fixture-local option map only.
 *
 * @param string $key     Local option key.
 * @param mixed  $default Fallback for an absent local key.
 * @return mixed
 */
function wcj_get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['c20_options'] ) ? $GLOBALS['c20_options'][ $key ] : $default;
}
/**
 * Delegate to the fixture-local option primitive.
 *
 * @param string $key     Local option key.
 * @param mixed  $default Fallback for an absent local key.
 * @return mixed
 */
function get_option( $key, $default = false ) {
	return wcj_get_option( $key, $default );
}
/**
 * Read the explicit local module flag without policy inference.
 *
 * @param string $id Module identifier.
 * @return bool
 */
function wcj_is_module_enabled( $id ) {
	return 'yes' === wcj_get_option( 'wcj_' . $id . '_enabled', 'no' );
}
/**
 * Supply the inert allow result when role filtering is disabled.
 *
 * @param string $id Unused module identifier.
 * @return bool
 */
function wcj_check_modules_by_user_roles( $id ) {
	return true;
}
/**
 * Supply the explicit inert module allowlist boundary.
 *
 * @param string $id Unused module identifier.
 * @return bool
 */
function wcj_is_module_allowed_by_allowlist( $id ) {
	return true;
}
/**
 * Return the synthetic HPOS storage-mode flag.
 *
 * @return bool
 */
function wcj_is_hpos_enabled() {
	return true;
}
/**
 * Expose the local graph of actual imported module instances.
 *
 * @return object
 */
function w_c_j() {
	return (object) array( 'all_modules' => $GLOBALS['c20_modules'] );
}
/**
 * Record an Ability declaration without executing callbacks.
 *
 * @param string $id       Ability identifier.
 * @param array  $metadata Complete production declaration.
 * @return void
 */
function wp_register_ability( $id, $metadata ) {
	$GLOBALS['c20_registered'][ $id ] = $metadata;
}
/**
 * Normalize a synthetic key at the WordPress primitive boundary.
 *
 * @param string $value Key to normalize.
 * @return string
 */
function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ) );
}
/**
 * Remove tags at the inert text primitive boundary.
 *
 * @param string $value Text to normalize.
 * @return string
 */
function wp_strip_all_tags( $value ) {
	return strip_tags( $value );
}
/**
 * Return a bounded synthetic excerpt without rendering.
 *
 * @param string $value Text to shorten.
 * @param int    $limit Maximum byte length.
 * @param string $more  Unused suffix accepted by the primitive.
 * @return string
 */
function wp_html_excerpt( $value, $limit, $more = '' ) {
	return substr( $value, 0, $limit );
}
/**
 * Supply an empty synthetic scheduler map.
 *
 * @return array
 */
function _get_cron_array() {
	return array();
}
/**
 * Supply fixed registered statuses for the empty OrderHealth query.
 *
 * @return array
 */
function wc_get_order_statuses() {
	return array(
		'wc-pending'    => 'Pending',
		'wc-on-hold'    => 'On hold',
		'wc-processing' => 'Processing',
		'wc-failed'     => 'Failed',
		'wc-completed'  => 'Completed',
	);
}
/**
 * Record public query arguments and supply synthetic empty orders.
 *
 * @param array $args Actual production query arguments.
 * @return array
 */
function wc_get_orders( $args ) {
	$GLOBALS['c20_order_queries'][] = $args;
	return array();
}
/**
 * Record and reject a business mutation attempt.
 *
 * @throws RuntimeException Always rejects the attempted mutation.
 * @return never
 */
function c20_forbidden_mutation() {
	++$GLOBALS['c20_mutations'];
	throw new RuntimeException( 'Mutation forbidden' );
}
/**
 * Reject option writes without modifying any option.
 *
 * @param mixed ...$arguments Unused attempted write arguments.
 * @return never
 */
function update_option( ...$arguments ) {
	return c20_forbidden_mutation();
}
/**
 * Reject metadata writes without modifying any metadata.
 *
 * @param mixed ...$arguments Unused attempted write arguments.
 * @return never
 */
function update_post_meta( ...$arguments ) {
	return c20_forbidden_mutation();
}
/**
 * Reject mail without contacting any transport.
 *
 * @param mixed ...$arguments Unused attempted mail arguments.
 * @return never
 */
function wp_mail( ...$arguments ) {
	return c20_forbidden_mutation();
}
/**
 * Reject order creation without creating an order.
 *
 * @param mixed ...$arguments Unused attempted order arguments.
 * @return never
 */
function wc_create_order( ...$arguments ) {
	return c20_forbidden_mutation();
}
$root = dirname( __DIR__ );
require_once $root . '/includes/class-wcj-status-service.php';
require_once $root . '/includes/class-wcj-order-health-service.php';
require_once $root . '/includes/class-wcj-rule-explanations.php';
require_once $root . '/includes/class-wcj-abilities.php';
$expected_json = <<<'C20_EXPECTED'
{"free":{"pre_registration_metadata_absent":false,"denied_returns_before_summary_import":"booster_forbidden","denied_shipping_before_summary_import":"booster_forbidden","authorized_missing_summary":"booster_returns_summary_unavailable","first_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":false,"elite_order_health":false},"registration_loads_metadata":true,"baseline_shipping_schema_pair_shape":[2,2,2,2],"second_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":true,"elite_order_health":false},"registered_callback_pairs":{"booster/explain-shipping-options":[true,true],"booster/compare-shipping-options":[true,true],"booster/returns-summary":[true,true],"booster/order-health-summary":null},"shipping_schemas_retained_after_import":true,"elite_briefing_returns_schema":null,"shipping_strict_input_after_import":"booster_shipping_elite_required","denied_order_health_after_import":"booster_forbidden","denied_returns_after_import":"booster_forbidden","denied_order_queries":0,"disabled_module_actual_flag":false,"disabled_returns_full_result":{"state":"unavailable","reason":"module_disabled","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_disabled_summary_projection":"booster_elite_required","order_health_top_level_required_keys":null,"strict_returns_input_after_import":"booster_invalid_returns_summary_input","enabled_module_actual_flag":true,"enabled_storage_unavailable_returns":{"state":"unavailable","reason":"queue_unavailable","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_enabled_unavailable_projection":"booster_elite_required","no_database_primitive_calls":0,"order_health_query_contract":[],"hpos_callable_source_coexistence":{"WCJ_PDF_Invoicing_Numbering::search_orders_by_invoice_number_hpos":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_order_by_column":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_invoice_sort_clauses":true,"WCJ_Orders::auto_complete_order":true,"WCJ_Order_Numbers::search_by_custom_number_hpos":true,"WCJ_PDF_Invoicing_Renumerate_Tool::renumerate_invoices":true},"no_business_mutation_calls":0,"all_predeclared_cases_reached":28},"plus":{"pre_registration_metadata_absent":false,"denied_returns_before_summary_import":"booster_forbidden","denied_shipping_before_summary_import":"booster_forbidden","authorized_missing_summary":"booster_returns_summary_unavailable","first_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":false,"elite_order_health":false},"registration_loads_metadata":true,"baseline_shipping_schema_pair_shape":[2,2,2,2],"second_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":true,"elite_order_health":false},"registered_callback_pairs":{"booster/explain-shipping-options":[true,true],"booster/compare-shipping-options":[true,true],"booster/returns-summary":[true,true],"booster/order-health-summary":null},"shipping_schemas_retained_after_import":true,"elite_briefing_returns_schema":null,"shipping_strict_input_after_import":"booster_shipping_elite_required","denied_order_health_after_import":"booster_forbidden","denied_returns_after_import":"booster_forbidden","denied_order_queries":0,"disabled_module_actual_flag":false,"disabled_returns_full_result":{"state":"unavailable","reason":"module_disabled","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_disabled_summary_projection":"booster_elite_required","order_health_top_level_required_keys":null,"strict_returns_input_after_import":"booster_invalid_returns_summary_input","enabled_module_actual_flag":true,"enabled_storage_unavailable_returns":{"state":"unavailable","reason":"queue_unavailable","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_enabled_unavailable_projection":"booster_elite_required","no_database_primitive_calls":0,"order_health_query_contract":[],"hpos_callable_source_coexistence":{"WCJ_PDF_Invoicing_Numbering::search_orders_by_invoice_number_hpos":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_order_by_column":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_invoice_sort_clauses":true,"WCJ_Orders::auto_complete_order":true,"WCJ_Order_Numbers::search_by_custom_number_hpos":true,"WCJ_PDF_Invoicing_Renumerate_Tool::renumerate_invoices":true},"no_business_mutation_calls":0,"all_predeclared_cases_reached":28},"elite":{"pre_registration_metadata_absent":false,"denied_returns_before_summary_import":"booster_forbidden","denied_shipping_before_summary_import":"booster_forbidden","authorized_missing_summary":"booster_returns_summary_unavailable","first_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":false,"elite_order_health":false},"registration_loads_metadata":true,"baseline_shipping_schema_pair_shape":[2,2,2,2],"second_registration_declarations":{"shipping_explain":true,"shipping_compare":true,"basic_returns":true,"elite_order_health":true},"registered_callback_pairs":{"booster/explain-shipping-options":[true,true],"booster/compare-shipping-options":[true,true],"booster/returns-summary":[true,true],"booster/order-health-summary":[true,true]},"shipping_schemas_retained_after_import":true,"elite_briefing_returns_schema":{"required":true,"schema_equal":true},"shipping_strict_input_after_import":"booster_shipping_invalid_input","denied_order_health_after_import":"booster_forbidden","denied_returns_after_import":"booster_forbidden","denied_order_queries":0,"disabled_module_actual_flag":false,"disabled_returns_full_result":{"state":"unavailable","reason":"module_disabled","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_disabled_summary_projection":{"bounded_query":{"query_limit":250,"candidate_count":0,"attention_count":0,"has_more":false},"returns":{"state":"unavailable","reason":"module_disabled","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"guidance_codes":["review-returns-availability"]},"order_health_top_level_required_keys":["tier","generated_at_gmt","storage_mode","bounded_query","age_buckets","cause_counts","reason_counts","oldest_age_days","morning_briefing"],"strict_returns_input_after_import":"booster_invalid_returns_summary_input","enabled_module_actual_flag":true,"enabled_storage_unavailable_returns":{"state":"unavailable","reason":"queue_unavailable","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"order_health_enabled_unavailable_projection":{"bounded_query":{"query_limit":250,"candidate_count":0,"attention_count":0,"has_more":false},"returns":{"state":"unavailable","reason":"queue_unavailable","basis":"latest_request_ids","bounded_sample":true,"sample_limit":50,"sample_size":null,"possibly_truncated":null,"counts":null},"guidance_codes":["review-returns-availability"]},"no_database_primitive_calls":0,"order_health_query_contract":[{"status":["pending"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["on-hold"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["processing"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["failed"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["pending"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["on-hold"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["processing"],"limit":51,"orderby":"date","order":"ASC","return":"objects"},{"status":["failed"],"limit":51,"orderby":"date","order":"ASC","return":"objects"}],"hpos_callable_source_coexistence":{"WCJ_PDF_Invoicing_Numbering::search_orders_by_invoice_number_hpos":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_order_by_column":true,"WCJ_PDF_Invoicing_Display::shop_order_hpos_invoice_sort_clauses":true,"WCJ_Orders::auto_complete_order":true,"WCJ_Order_Numbers::search_by_custom_number_hpos":true,"WCJ_PDF_Invoicing_Renumerate_Tool::renumerate_invoices":true},"no_business_mutation_calls":0,"all_predeclared_cases_reached":28}}
C20_EXPECTED;
$expected      = json_decode( $expected_json, true )[ $argv[1] ];
$seen          = array();
/**
 * Name only the finite value types used by this fixture.
 *
 * @param mixed $value Fixture assertion value.
 * @throws RuntimeException For a type outside the finite fixture domain.
 * @return string
 */
function c20_type_name( $value ) {
	$type_names = array(
		'NULL'    => 'null',
		'boolean' => 'bool',
		'integer' => 'int',
		'double'  => 'float',
		'string'  => 'string',
		'array'   => 'array',
	);
	$type       = gettype( $value );
	if ( ! array_key_exists( $type, $type_names ) ) {
		throw new RuntimeException( 'Unsupported fixture type.' );
	}
	return $type_names[ $type ];
}
/**
 * Emit one exact typed assertion against the predeclared literal.
 *
 * @param string $label  Unique planned case label.
 * @param mixed  $actual Actual production result or limited projection.
 * @throws RuntimeException On an unknown, repeated or failing planned case.
 * @return void
 */
function c20_check( $label, $actual ) {
	global $expected, $seen;
	if ( ! array_key_exists( $label, $expected ) || isset( $seen[ $label ] ) ) {
		throw new RuntimeException( 'Unknown or repeated case: ' . $label );
	}
	$want           = $expected[ $label ];
	$seen[ $label ] = true;
	$pass           = $actual === $want;
	echo json_encode(
		array(
			'case'          => $label,
			'actual'        => $actual,
			'actual_type'   => c20_type_name( $actual ),
			'expected'      => $want,
			'expected_type' => c20_type_name( $want ),
			'pass'          => $pass,
		),
		JSON_UNESCAPED_SLASHES
	) . "\n";
	if ( ! $pass ) {
		throw new RuntimeException( 'Literal case failed: ' . $label );
	}
}
/**
 * Project a synthetic error to its code, preserving other results.
 *
 * @param mixed $value Production result or primitive error.
 * @return mixed
 */
function c20_result( $value ) {
	return is_wp_error( $value ) ? $value->get_error_code() : $value;
}
/**
 * Inspect included-file membership without loading the metadata.
 *
 * @return bool
 */
function c20_metadata_loaded() {
	return in_array( 'wcj-shipping-v2-metadata.php', array_map( 'basename', get_included_files() ), true );
}
/**
 * Project only the four relevant registered Ability identities.
 *
 * @return array
 */
function c20_declarations() {
	$r = $GLOBALS['c20_registered'];
	return array(
		'shipping_explain'   => isset( $r['booster/explain-shipping-options'] ),
		'shipping_compare'   => isset( $r['booster/compare-shipping-options'] ),
		'basic_returns'      => isset( $r['booster/returns-summary'] ),
		'elite_order_health' => isset( $r['booster/order-health-summary'] ),
	);
}
/**
 * Project bounded OrderHealth and the complete Returns summary.
 *
 * @param array|WP_Error $result Actual OrderHealth result or primitive error.
 * @return array|string
 */
function c20_health_projection( $result ) {
	if ( is_wp_error( $result ) ) {
		return $result->get_error_code();
	}
	return array(
		'bounded_query'  => $result['bounded_query'],
		'returns'        => $result['morning_briefing']['returns'],
		'guidance_codes' => $result['morning_briefing']['guidance_codes'],
	);
}
$ability = new WCJ_Abilities();
$service = new WCJ_Rule_Explanations();
c20_check( 'pre_registration_metadata_absent', c20_metadata_loaded() );
$GLOBALS['c20_capable'] = false;
c20_check( 'denied_returns_before_summary_import', c20_result( $ability->execute_returns_summary( array( 'scope' => 'summary' ) ) ) );
c20_check(
	'denied_shipping_before_summary_import',
	c20_result(
		$ability->execute_shipping_explanation(
			array(
				'contract_version' => 2,
				'mode'             => 'scenario',
			)
		)
	)
);
$GLOBALS['c20_capable'] = true;
c20_check( 'authorized_missing_summary', c20_result( $ability->execute_returns_summary( array( 'scope' => 'summary' ) ) ) );
$ability->register_abilities();
c20_check( 'first_registration_declarations', c20_declarations() );
c20_check( 'registration_loads_metadata', c20_metadata_loaded() );
$before = array( $service->get_shipping_dispatch_input_schema( false ), $service->get_shipping_dispatch_output_schema( false ), $service->get_shipping_dispatch_input_schema( true ), $service->get_shipping_dispatch_output_schema( true ) );
c20_check(
	'baseline_shipping_schema_pair_shape',
	array_map(
		function ( $schema ) {
			return count( $schema['oneOf'] );
		},
		$before
	)
);
require_once $root . '/includes/classes/class-wcj-module.php';
require_once $root . '/includes/class-wcj-returns-summary.php';
$GLOBALS['c20_modules']['returns'] = require $root . '/includes/class-wcj-returns.php';
$numbering                         = require $root . '/includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-numbering.php';
$display                           = require $root . '/includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-display.php';
$orders                            = require $root . '/includes/class-wcj-orders.php';
$order_numbers                     = require $root . '/includes/class-wcj-order-numbers.php';
$renumerate                        = require $root . '/includes/pdf-invoices/class-wcj-pdf-invoicing-renumerate-tool.php';
$GLOBALS['c20_registered']         = array();
$ability->register_abilities();
c20_check( 'second_registration_declarations', c20_declarations() );
$registered = $GLOBALS['c20_registered'];
$callbacks  = array();
foreach ( array(
	'booster/explain-shipping-options' => 'execute_shipping_explanation',
	'booster/compare-shipping-options' => 'execute_shipping_comparison',
	'booster/returns-summary'          => 'execute_returns_summary',
	'booster/order-health-summary'     => 'execute_order_health_summary',
) as $id => $method ) {
	$callbacks[ $id ] = isset( $registered[ $id ] ) ? array( $registered[ $id ]['execute_callback'] === array( $ability, $method ), $registered[ $id ]['permission_callback'] === array( $ability, 'check_permission' ) ) : null;
}
c20_check( 'registered_callback_pairs', $callbacks );
$after = array( $service->get_shipping_dispatch_input_schema( false ), $service->get_shipping_dispatch_output_schema( false ), $service->get_shipping_dispatch_input_schema( true ), $service->get_shipping_dispatch_output_schema( true ) );
c20_check( 'shipping_schemas_retained_after_import', $before === $after && $registered['booster/explain-shipping-options']['input_schema'] === $before[0] && $registered['booster/compare-shipping-options']['output_schema'] === $before[3] );
$health_schema = isset( $registered['booster/order-health-summary'] ) ? $registered['booster/order-health-summary']['output_schema'] : null;
c20_check(
	'elite_briefing_returns_schema',
	null === $health_schema ? null : array(
		'required'     => in_array( 'returns', $health_schema['properties']['morning_briefing']['required'], true ),
		'schema_equal' => $health_schema['properties']['morning_briefing']['properties']['returns'] === ( new WCJ_Returns_Summary() )->get_output_schema(),
	)
);
c20_check(
	'shipping_strict_input_after_import',
	c20_result(
		$ability->execute_shipping_explanation(
			array(
				'contract_version' => 2,
				'mode'             => 'scenario',
			)
		)
	)
);
$GLOBALS['c20_capable'] = false;
c20_check( 'denied_order_health_after_import', c20_result( $ability->execute_order_health_summary() ) );
c20_check( 'denied_returns_after_import', c20_result( $ability->execute_returns_summary( array( 'scope' => 'summary' ) ) ) );
c20_check( 'denied_order_queries', count( $GLOBALS['c20_order_queries'] ) );
$GLOBALS['c20_capable'] = true;
c20_check( 'disabled_module_actual_flag', $GLOBALS['c20_modules']['returns']->is_enabled() );
c20_check( 'disabled_returns_full_result', c20_result( $ability->execute_returns_summary( array( 'scope' => 'summary' ) ) ) );
$disabled_health = $ability->execute_order_health_summary();
c20_check( 'order_health_disabled_summary_projection', c20_health_projection( $disabled_health ) );
c20_check( 'order_health_top_level_required_keys', is_wp_error( $disabled_health ) ? null : array_keys( $disabled_health ) );
c20_check(
	'strict_returns_input_after_import',
	c20_result(
		$ability->execute_returns_summary(
			array(
				'scope' => 'summary',
				'extra' => true,
			)
		)
	)
);
$GLOBALS['c20_options']['wcj_returns_enabled'] = 'yes';
c20_check( 'enabled_module_actual_flag', $GLOBALS['c20_modules']['returns']->is_enabled() );
c20_check( 'enabled_storage_unavailable_returns', c20_result( $ability->execute_returns_summary( array( 'scope' => 'summary' ) ) ) );
c20_check( 'order_health_enabled_unavailable_projection', c20_health_projection( $ability->execute_order_health_summary() ) );
c20_check( 'no_database_primitive_calls', $GLOBALS['c20_database_calls'] );
c20_check( 'order_health_query_contract', $GLOBALS['c20_order_queries'] );
$definitions = array();
foreach ( array( array( $numbering, 'search_orders_by_invoice_number_hpos', 'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-numbering.php' ), array( $display, 'shop_order_hpos_order_by_column', 'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-display.php' ), array( $display, 'shop_order_hpos_invoice_sort_clauses', 'includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-display.php' ), array( $orders, 'auto_complete_order', 'includes/class-wcj-orders.php' ), array( $order_numbers, 'search_by_custom_number_hpos', 'includes/class-wcj-order-numbers.php' ), array( $renumerate, 'renumerate_invoices', 'includes/pdf-invoices/class-wcj-pdf-invoicing-renumerate-tool.php' ) ) as $entry ) {
	$method = new ReflectionMethod( $entry[0], $entry[1] );
	$definitions[ get_class( $entry[0] ) . '::' . $entry[1] ] = is_callable( array( $entry[0], $entry[1] ) ) && $method->isPublic() && realpath( $method->getFileName() ) === realpath( $root . '/' . $entry[2] );
}
c20_check( 'hpos_callable_source_coexistence', $definitions );
c20_check( 'no_business_mutation_calls', $GLOBALS['c20_mutations'] );
c20_check( 'all_predeclared_cases_reached', count( $seen ) + 1 );
echo json_encode(
	array(
		'status'                         => 'PASS',
		'tier'                           => $argv[1],
		'assertions'                     => count( $seen ),
		'failures'                       => 0,
		'native'                         => false,
		'returns_available_zero'         => 'UNQUALIFIED',
		'included_files_membership_only' => get_included_files(),
	),
	JSON_UNESCAPED_SLASHES
) . "\n";
