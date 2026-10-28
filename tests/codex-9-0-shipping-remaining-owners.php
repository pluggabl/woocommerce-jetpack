<?php
/**
 * New remaining-shipping-owner source regression. No WordPress/Woo runtime or armed observation.
 * Public scenarios use actual whole classes; pure closed-profile methods use Reflection, never state.
 *
 * @package Booster_For_WooCommerce/tests
 */
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line );
} );
$plan_path = isset( $argv[1] ) ? $argv[1] : '';
if ( ! is_file( $plan_path ) || 'c794333336565234e5738476a177de06a57ae61c3f81336bbbdd5b2712447dd0' !== hash_file( 'sha256', $plan_path ) ) {
	throw new RuntimeException( 'Frozen new literal plan mismatch.' ); }
$plan = json_decode( file_get_contents( $plan_path ), true );
$root = dirname( __DIR__ );
$tier = basename( $root );
define( 'ABSPATH', $root . '/' );
define( 'WCJ_PLUGIN_FILE', array( 'free' => 'woocommerce-jetpack.php', 'plus' => 'booster-plus-for-woocommerce.php', 'elite' => 'booster-elite-for-woocommerce.php' )[ $tier ] );
$GLOBALS['codex_cap']          = true;
$GLOBALS['codex_options']      = array();
$GLOBALS['codex_reads']        = array();
$GLOBALS['codex_filter_reads'] = array();
$GLOBALS['codex_checks']       = array();
$GLOBALS['wp_filter']          = array();
$_GET                          = array();
$_POST                         = array();
$_REQUEST                      = array();
function codex_check( $value, $expected, $label ) { if ( $value !== $expected ) {
		throw new RuntimeException( $label . ': ' . json_encode( array( 'actual' => $value, 'expected' => $expected ) ) );
} $GLOBALS['codex_checks'][] = array( 'label' => $label, 'actual' => $value, 'expected' => $expected ); }
function __( $text, $domain = '' ) { return $text; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $v ) { return esc_html( $v ); }
function esc_url( $v ) { return esc_html( $v ); }
function esc_html__( $v, $domain = '' ) { return esc_html( $v ); }
function current_user_can( $cap ) { return 'manage_woocommerce' === $cap && $GLOBALS['codex_cap']; }
function is_user_logged_in() { return true; }
function is_admin() { return true; }
function wcj_is_rest_request() { return false; }
function wcj_is_module_enabled( $id ) { return false; }
function wcj_is_module_allowed_by_allowlist( $id ) { return true; }
function wcj_get_option( $key, $default = false ) { $GLOBALS['codex_reads'][] = $key;
	return array_key_exists( $key, $GLOBALS['codex_options'] ) ? $GLOBALS['codex_options'][ $key ] : $default; }
function get_option( $key, $default = false ) { return wcj_get_option( $key, $default ); }
function apply_filters( $hook, $value, ...$args ) { $GLOBALS['codex_filter_reads'][] = $hook;
	return 'booster_option' === $hook && isset( $args[0] ) ? $args[0] : $value; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
		$GLOBALS['wp_filter'][ $hook ] = (object) array( 'callbacks' => array() );
} $GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][] = array( 'function' => $callback, 'accepted_args' => $args );
ksort( $GLOBALS['wp_filter'][ $hook ]->callbacks ); }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { add_filter( $hook, $callback, $priority, $args ); }
function admin_url( $path = '' ) { return 'https://example.invalid/wp-admin/' . $path; }
function wcj_admin_tab_url() { return 'admin.php?page=wcj'; }
function current_time( $format ) { return '2000-01-01 00:00:00'; }
function wcj_get_timestamp_date_from_gmt() { return 946684800; }
function wcj_get_current_user_first_role() { return 'customer'; }
function wcj_get_current_user_all_roles() { return array( 'customer' ); }
function get_current_user_id() { return 7; }
function get_current_blog_id() { return 1; }
function wp_unslash( $v ) { return stripslashes( $v ); }
function wp_create_nonce( $action ) { $GLOBALS['codex_nonce_action'] = $action;
	return 'nonce7'; }
function wp_verify_nonce( $nonce, $action ) { return 'nonce7' === $nonce && isset( $GLOBALS['codex_expected_nonce_action'] ) && $GLOBALS['codex_expected_nonce_action'] === $action ? 1 : false; }
function wp_nonce_field( $action, $name ) { echo '<input name="' . esc_attr( $name ) . '" value="nonce7">'; }
function wc_get_checkout_url() { return 'https://example.invalid/checkout/'; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wcj_check_postcode( $value, $values ) { return in_array( $value, $values, true ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error { private $code;
	private $message;
	public function __construct( $code, $message = '' ) { $this->code = $code;
		$this->message = $message;
	} public function get_error_code() { return $this->code;
	} public function get_error_message() { return $this->message; } }
#[AllowDynamicProperties]
class WCJ_Module { public function __construct( $type = 'module' ) {} public function is_enabled() { return false; } }
class WC_Shipping_Rate { public $id;
	public $method_id;
	public $instance_id = 1;
	public $cost;
	public function __construct( $id, $method, $cost ) { $this->id = $id;
		$this->method_id = $method;
		$this->cost      = $cost; } }
$GLOBALS['codex_wc'] = (object) array( 'cart' => new class { public function is_empty() { return false;
} public function get_cart() { return array();
} } );
function WC() { return $GLOBALS['codex_wc']; }
function w_c_j() { return $GLOBALS['codex_plugin']; }
require $root . '/includes/class-wcj-rule-explanations.php';
require $root . '/includes/classes/class-wcj-module-shipping-by-condition.php';
$modules = array();
foreach ( array( 'order-amount', 'order-qty', 'user-role', 'products', 'cities', 'time' ) as $name ) {
	$module                         = require $root . '/includes/class-wcj-shipping-by-' . $name . '.php';
	$module->use_shipping_instances = false;
	$modules[ $module->id ]         = $module;
	add_filter( 'woocommerce_package_rates', array( $module, 'available_shipping_methods' ), 100, 2 );
	$GLOBALS['codex_options'][ 'wcj_' . $module->id . '_enabled' ] = 'yes'; }
$module                      = require $root . '/includes/class-wcj-shipping-options.php';
$modules['shipping_options'] = $module;
$GLOBALS['codex_options']['wcj_shipping_options_enabled'] = 'yes';
foreach ( array( 'show_most_expensive_shipping', 'hide_shipping_when_free_is_available' ) as $callback ) {
	add_filter( 'woocommerce_package_rates', array( $module, $callback ), 10, 2 ); }
add_filter( 'woocommerce_shipping_free_shipping_is_available', array( $module, 'free_shipping_by_product' ), PHP_INT_MAX, 2 );
$GLOBALS['codex_plugin'] = (object) array( 'all_modules' => $modules );
$service                 = new WCJ_Rule_Explanations();
function codex_pure( $name, ...$args ) { $method = new ReflectionMethod( 'WCJ_Rule_Explanations', $name );
	return $method->invokeArgs( $GLOBALS['codex_service'], $args ); }
$GLOBALS['codex_service'] = $service;
$schemas                  = $plan['schema_subtrees'];
codex_check( $service->get_shipping_dispatch_input_schema()['oneOf'][1], $schemas['input_schemas']['explain_v2'], 'exact_explain_input_schema' );
codex_check( $service->get_shipping_dispatch_input_schema( true )['oneOf'][1], $schemas['input_schemas']['compare_v2'], 'exact_compare_input_schema' );
codex_check( $service->get_shipping_dispatch_output_schema()['oneOf'][1], $schemas['output_schemas']['single_v2'], 'exact_single_output_schema' );
codex_check( $service->get_shipping_dispatch_output_schema( true )['oneOf'][1], $schemas['output_schemas']['comparison_v2'], 'exact_comparison_output_schema' );
foreach ( $plan['profiles'] as $case ) {
	$p        = $case['profile'];
	$r        = $case['row'];
	$observed = 'normal_shipping_callback' === $p['provenance'] ? array( 'entry_before' => $r['entry_before'], 'entry_after' => $r['entry_after'], 'call_ordinal' => 1 ) : null;
	codex_check( codex_pure( 'shipping_v2_row', $p['owner'], $p['context'], $p['reason'], $r['sections'], $observed ), $r, $case['label'] ); }
foreach ( array( array( '{"x":1,"x":2}', false ), array( '{"x":1,"\\u0078":2}', false ), array( '{"x":{"y":1,"y":2}}', false ), array( '{"x":1,"y":2}', true ), array( '[1,2]', true ), array( str_repeat( ' ', 8192 ) . '{}', false ), array( '{"x":', false ) ) as $i => $case ) {
	codex_check( ! is_wp_error( $service->parse_shipping_v2_json( $case[0] ) ), $case[1], 'raw_json_' . $i ); }
foreach ( array( array( '1', false ), array( '0', false ), array( '00', true ), array( '-0', true ), array( '-05', true ), array( 'flat_rate:1', true ) ) as $case ) {
	codex_check( false !== $service->shipping_transport_selector( 'string', $case[0] ), $case[1], 'typed_key_' . $case[0] ); }
$in = $plan['input_unavailable'];
codex_check( codex_pure( 'shipping_v2_input_valid', $in ), true, 'closed_unavailable_input_valid' );
$bad          = $in;
$bad['extra'] = 1;
codex_check( codex_pure( 'shipping_v2_input_valid', $bad ), false, 'unknown_input_key' );
$bad                   = $in;
$bad['facts']['actor'] = array( 'status' => 'supplied', 'first_roles' => array(), 'all_roles' => array( 'customer' ), 'actor_id' => 7, 'membership_extension_present' => false, 'membership_calls' => array() );
codex_check( codex_pure( 'shipping_v2_input_valid', $bad ), false, 'first_roles_requires_exactly_one' );
$bad['facts']['actor']['first_roles'] = array( 'customer' );
$bad['facts']['actor']['all_roles']   = array();
codex_check( codex_pure( 'shipping_v2_input_valid', $bad ), false, 'all_roles_nonempty' );
$GLOBALS['codex_cap']   = false;
$GLOBALS['codex_reads'] = array();
$denied                 = $service->explain_shipping_options( $in );
codex_check( $denied->get_error_code(), 'booster_shipping_capability_required', 'public_capability_denial' );
codex_check( $GLOBALS['codex_reads'], array(), 'denied_zero_private_reads' );
$GLOBALS['codex_cap'] = true;
$result               = $service->explain_shipping_options( $in );
if ( 'elite' === $tier ) {
	codex_check( is_array( $result ), true, 'public_elite_connected_result' );
	codex_check( array_column( $result['rows'], 'reason' ), array_fill( 0, 9, 'missing_supplied_facts' ), 'public_missing_facts_all_nine' );
	codex_check( $service->validate_shipping_output( $result ), true, 'public_full_output_schema' );
	$compare = $service->compare_shipping_options( array( 'contract_version' => 2, 'mode' => 'compare', 'scenarios' => array( $in, $in ) ) );
	codex_check( is_array( $compare ) && $service->validate_shipping_output( $compare, true ), true, 'public_independent_compare' ); } else {
	codex_check( $result->get_error_code(), 'booster_shipping_elite_required', 'public_basic_no_scenario_entitlement' ); }
	$key = array( 'kind' => 'method', 'value' => 'flat_rate' );
	// Real whole owner and service paths, with finite request-local doubles only.
	$facts = array( 'cart_present' => true, 'cart_empty' => false, 'package_contents_present' => true, 'variable_expansions' => array(), 'cart_items' => array( array( 'product_id' => array( 'kind' => 'int', 'value' => 5 ), 'variation_id' => array( 'kind' => 'int', 'value' => 0 ), 'category_ids' => array(), 'tag_ids' => array(), 'shipping_class_id' => array( 'kind' => 'int', 'value' => 0 ) ) ), 'package_items' => array() );
	foreach ( array( 'product_cats', 'product_tags', 'classes' ) as $section ) {
		$GLOBALS['codex_options'][ 'wcj_shipping_by_' . $section . '_section_enabled' ] = 'no'; }
	$GLOBALS['codex_options']['wcj_shipping_products_include_flat_rate'] = array( '5' );
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_products', $modules['shipping_by_products'], $key, $facts );
	codex_check( $r[0], 'conditions_retained', 'product_string_config_matches_cast_item' );
	$GLOBALS['codex_options']['wcj_shipping_products_include_flat_rate'] = array( 5 );
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_products', $modules['shipping_by_products'], $key, $facts );
	codex_check( $r[0], 'include_rejected', 'product_int_config_strict_mismatch' );
	$facts['cart_present'] = false;
	$facts['cart_empty']   = true;
	$facts['cart_items']   = array();
	$GLOBALS['codex_options']['wcj_shipping_products_exclude_flat_rate'] = array( 5 );
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_products', $modules['shipping_by_products'], $key, $facts );
	codex_check( $r[0], 'exclude_matched', 'missing_product_data_exclude_removes' );
	codex_check( $r[1][0]['exclude_state'], 'data_unavailable_bypass', 'missing_data_not_successful_match' );
	$GLOBALS['codex_options']['wcj_shipping_time_include_flat_rate'] = 'invalid';
	$GLOBALS['codex_options']['wcj_shipping_time_exclude_flat_rate'] = '2001-01-01 ~ 2001-01-02';
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_time', $modules['shipping_by_time'], $key, array() );
	codex_check( $r[0], 'invalid_time_bypass', 'N9_invalid_include_retained' );
	$GLOBALS['codex_options']['wcj_shipping_time_include_flat_rate'] = '2001-01-01 ~ 2001-01-02';
	$GLOBALS['codex_options']['wcj_shipping_time_exclude_flat_rate'] = 'invalid';
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_time', $modules['shipping_by_time'], $key, array() );
	codex_check( $r[0], 'include_rejected', 'N9_valid_nonmatch_include_first' );
	$GLOBALS['codex_options']['wcj_shipping_cities_include_flat_rate']     = 'NEW YORK';
	$GLOBALS['codex_options']['wcj_shipping_by_postcodes_section_enabled'] = 'no';
	$r = codex_pure( 'shipping_v2_condition_scenario', 'shipping_by_cities', $modules['shipping_by_cities'], $key, array( 'city' => ' New York ', 'postcode' => '' ) );
	codex_check( $r[0], 'include_rejected', 'supplied_city_not_trimmed' );
	$GLOBALS['codex_options']['wcj_shipping_free_shipping_by_product_products'] = array( '5' );
	$r = codex_pure( 'shipping_v2_options_scenario', 'shipping_options_free_by_product', array( 'package_contents_present' => true, 'items' => array( array( 'product_id' => array( 'kind' => 'int', 'value' => 5 ) ) ) ) );
	codex_check( $r, 'all_nonmatch_preserved', 'free_grant_int_does_not_match_string' );
	$GLOBALS['codex_options']['wcj_shipping_free_shipping_by_product_products'] = array( 5 );
	$r = codex_pure( 'shipping_v2_options_scenario', 'shipping_options_free_by_product', array( 'package_contents_present' => true, 'items' => array( array( 'product_id' => array( 'kind' => 'int', 'value' => 5 ) ) ) ) );
	codex_check( $r, 'all_items_grant', 'free_grant_exact_int_match' );
	$GLOBALS['codex_options']['wcj_shipping_most_expensive_enabled']         = 'yes';
	$GLOBALS['codex_options']['wcj_shipping_most_expensive_ignored_methods'] = array();
	$ordered = array( 'selected_key' => array( 'kind' => 'string', 'value' => 'old' ), 'rates' => array( array( 'key' => array( 'kind' => 'string', 'value' => 'old' ), 'id' => array( 'kind' => 'string', 'value' => 'new' ), 'method_id' => 'flat_rate', 'cost' => 10 ), array( 'key' => array( 'kind' => 'string', 'value' => 'second' ), 'id' => array( 'kind' => 'string', 'value' => 'second' ), 'method_id' => 'flat_rate', 'cost' => 10 ) ) );
	codex_check( codex_pure( 'shipping_v2_options_scenario', 'shipping_options_most_expensive', $ordered ), 'selected_object_rekeyed', 'most_first_tie_and_rekey' );
	$ordered['rates'][1]['method_id']                                        = 'free_shipping';
	$ordered['rates'][1]['id']['value']                                      = 'new';
	$GLOBALS['codex_options']['wcj_shipping_most_expensive_ignored_methods'] = array( 'free_shipping' );
	codex_check( codex_pure( 'shipping_v2_options_scenario', 'shipping_options_most_expensive', $ordered ), 'id_collision_overwrite', 'later_ignored_id_collision' );
	// Unarmed actual callbacks are normal calls, not fake genuine Woo observations.
	$rates = array( 'flat_rate:1' => new WC_Shipping_Rate( 'flat_rate:1', 'flat_rate', 10 ) );
	$GLOBALS['codex_options']['wcj_shipping_most_expensive_enabled'] = 'no';
	$before_reads = count( $GLOBALS['codex_reads'] );
	codex_check( $module->show_most_expensive_shipping( $rates, array() ), $rates, 'unarmed_most_disabled_identity' );
	codex_check( count( $GLOBALS['codex_reads'] ) - $before_reads, 1, 'unarmed_most_exact_one_option_read' );
	codex_check( WCJ_Rule_Explanations::shipping_v2_free_result( null, false, 'empty_grant_list' ), false, 'unarmed_free_false_preserved' );
	require $root . '/includes/class-wcj-rule-explanations-admin.php';
	$admin                     = new WCJ_Rule_Explanations_Admin();
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST                     = array( 'wcj_shipping_action' => 'observe', 'wcj_shipping_contract_version' => '2', 'wcj_shipping_form_nonce' => 'wrong' );
	$GLOBALS['codex_reads']    = array();
	ob_start();
	$admin->render_shipping_admin();
	$html = ob_get_clean();
	codex_check( false !== strpos( $html, 'booster_expired_rule_form' ), true, 'v2_admin_wrong_nonce' );
	codex_check( $GLOBALS['codex_reads'], array(), 'wrong_nonce_zero_private_reads' );
	$package         = array( 'kind' => 'int', 'value' => 0 );
	$rate            = array( 'kind' => 'string', 'value' => 'flat_rate:1' );
	$link            = $service->shipping_v2_observation_link( $package, $rate, '3' );
	$expected_action = 'wcj_shipping_observe_v2:1:7:' . hash( 'sha256', json_encode( array( 2, 'shipping_conditions_and_options', 'int', 0, 'string', 'flat_rate:1', 3 ) ) );
	codex_check( $GLOBALS['codex_nonce_action'], $expected_action, 'v2_ordered_native_nonce_tuple' );
	codex_check( is_string( $link ) && false !== strpos( $link, 'wcj_shipping_contract_version=2' ), true, 'v2_link_explicit_version' );
	$unavailable = $service->get_shipping_observation();
	codex_check( $unavailable->get_error_code(), 'booster_shipping_observation_unavailable', 'no_genuine_generation_unavailable' );
	echo json_encode( array( 'status' => 'PASS_NEW_SOURCE_ONLY', 'tier' => $tier, 'assertions' => count( $GLOBALS['codex_checks'] ), 'checks' => $GLOBALS['codex_checks'], 'native_armed' => 'UNQUALIFIED', 'doubles' => 'Request-local API/registry/clock/postcode exact-match doubles; no native stack or private state injection.' ), JSON_PRETTY_PRINT ) . "\n";
