<?php
/**
 * Maintained revision-3 source-isolated regression, CORR-S1-001. CLI only.
 * Executes the tier module and its real postcode/country helpers.
 * Only WC context, options, hook registration and WP cleaning are stubs.
 * Exit 1 means the approved customer-fallback/REST contract is not satisfied.
 */
if ( 'cli' !== PHP_SAPI ) {
    http_response_code( 403 );
    exit;
}
define( 'ABSPATH', __DIR__ );
$predicate_mode = $argv[3] ?? 'primary';
if ( ! in_array( $predicate_mode, array( 'primary', 'fallback-classic', 'fallback-rest', 'fallback-undefined' ), true ) ) {
    fwrite( STDERR, "Unknown predicate mode\n" );
    exit( 2 );
}
$endpoint_context = false;
$pay_page_context = false;
$pay_predicate_calls = 0;
function is_checkout_pay_page() { ++$GLOBALS['pay_predicate_calls']; return $GLOBALS['pay_page_context']; }
if ( 'primary' === $predicate_mode ) {
    define( 'REST_REQUEST', false );
    // API boundary stub. Not execution of WordPress REST dispatch.
    function wp_is_rest_endpoint() { return $GLOBALS['endpoint_context']; }
} elseif ( 'fallback-undefined' !== $predicate_mode ) {
    define( 'REST_REQUEST', 'fallback-rest' === $predicate_mode );
}
define( 'WCJ_IS_WC_VERSION_BELOW_3', false );
$source_root = $argv[1] ?? dirname( __DIR__ );
$tier        = $argv[2] ?? basename( $source_root );
$paths       = array(
    'module'  => $source_root . '/includes/class-wcj-payment-gateways-by-country.php',
    'general' => $source_root . '/includes/functions/wcj-functions-general.php',
    'country' => $source_root . '/includes/functions/wcj-functions-country.php',
    'settings_read_only' => $source_root . '/includes/settings/wcj-settings-payment-gateways-by-country.php',
);
foreach ( $paths as $path ) {
    if ( ! is_file( $path ) ) {
        fwrite( STDERR, "Expected tier source root with module and helpers: $path\n" );
        exit( 2 );
    }
}
if ( ! function_exists( 'fnmatch' ) ) {
    fwrite( STDERR, "The actual wildcard helper requires fnmatch.\n" );
    exit( 2 );
}
set_error_handler( static function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );

class WCJ_Module {
    public $id, $short_desc, $desc, $link_slug;
    public function __construct() {}
    public function is_enabled() { return $GLOBALS['enabled']; }
}
class SyntheticCustomer {
    public $billing_postcode, $shipping_postcode;
    public function __construct( $billing, $shipping ) {
        $this->billing_postcode  = $billing;
        $this->shipping_postcode = $shipping;
    }
    public function get_billing_postcode() { return $this->billing_postcode; }
    public function get_shipping_postcode() { return $this->shipping_postcode; }
    public function get_billing_country() { return 'US'; }
    public function get_shipping_country() { return 'CA'; }
    public function get_billing_state() { return 'NY'; }
    public function get_shipping_state() { return 'ON'; }
    public function __call( $name, $args ) { throw new LogicException( 'Unexpected customer operation: ' . $name ); }
}
class SyntheticCountries {
    public $postcode;
    public function __construct( $postcode ) { $this->postcode = $postcode; }
    public function get_base_postcode() { return $this->postcode; }
}
class SyntheticWoo { public $customer, $countries; }
function WC() { return $GLOBALS['woo']; }
function __( $text, $domain ) { return $text; }
function wcj_get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['options'] ) ? $GLOBALS['options'][ $key ] : $default;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['filters'][ $hook ] = array( 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args );
}
// Deliberately small WP boundary stubs, not a claim of WP sanitizer parity.
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value ); }
function sanitize_text_field( $value ) {
    if ( ! is_scalar( $value ) ) { return ''; }
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $value ) ) );
}
function wc_clean( $value ) { return is_array( $value ) ? array_map( 'wc_clean', $value ) : sanitize_text_field( $value ); }

$enabled = true;
$filters = $options = array();
$woo     = new SyntheticWoo();
require $paths['general'];
require $paths['country'];
$module = require $paths['module'];
$results = array();
function record_check( $scenario, $assertion, $expected, $actual ) {
    $GLOBALS['results'][] = array(
        'scenario' => $scenario,
        'assertion' => $assertion,
        'status' => $expected === $actual ? 'PASS' : 'FAIL',
        'expected' => $expected,
        'actual' => $actual,
    );
}
record_check( 'registration', 'enabled module registers the real filter with existing priority and arity',
    array( PHP_INT_MAX, 1 ),
    isset( $filters['woocommerce_available_payment_gateways'] )
        ? array( $filters['woocommerce_available_payment_gateways']['priority'], $filters['woocommerce_available_payment_gateways']['accepted_args'] )
        : null
);
$enabled = false;
$filters = array();
new WCJ_Payment_Gateways_By_Country();
record_check( 'registration', 'disabled module registers no filter', array(), $filters );
$enabled = true;

// Expectations are approved revision-2 source acceptance, not runtime proof.
// Existing !empty() precedence on Classic fields is intentionally retained.
// A present customer's empty/"0" postcode is not silently replaced by shop data.
$cases = array(
    array( 'id' => 'billing_customer_no_classic_fields', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD' ),
    array( 'id' => 'shipping_customer_no_classic_fields', 'type' => 'shipping', 'shipping' => 'k1a 0b1', 'expected' => 'K1A 0B1' ),
    array( 'id' => 'unknown_type_keeps_billing_default', 'type' => 'unrecognized', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD' ),
    array( 'id' => 'billing_primary_wins', 'request' => array( 'postcode' => 'ab1 2cd', 'billing_postcode' => 'secondary' ), 'expected' => 'AB1 2CD' ),
    array( 'id' => 'shipping_primary_wins', 'type' => 'shipping', 'request' => array( 's_postcode' => 'k1a 0b1', 'shipping_postcode' => 'secondary' ), 'expected' => 'K1A 0B1' ),
    array( 'id' => 'billing_secondary_wins', 'request' => array( 'billing_postcode' => 'ab1 2cd' ), 'expected' => 'AB1 2CD' ),
    array( 'id' => 'shipping_secondary_wins', 'type' => 'shipping', 'request' => array( 'shipping_postcode' => 'k1a 0b1' ), 'expected' => 'K1A 0B1' ),
    array( 'id' => 'billing_empty_primary_secondary_wins', 'request' => array( 'postcode' => '', 'billing_postcode' => 'ab1 2cd' ), 'expected' => 'AB1 2CD' ),
    array( 'id' => 'shipping_empty_primary_secondary_wins', 'type' => 'shipping', 'request' => array( 's_postcode' => '', 'shipping_postcode' => 'k1a 0b1' ), 'expected' => 'K1A 0B1' ),
    array( 'id' => 'billing_zero_primary_retains_classic_empty_semantics', 'request' => array( 'postcode' => '0', 'billing_postcode' => 'ab1 2cd' ), 'expected' => 'AB1 2CD' ),
    array( 'id' => 'shipping_zero_primary_retains_classic_empty_semantics', 'type' => 'shipping', 'request' => array( 's_postcode' => '0', 'shipping_postcode' => 'k1a 0b1' ), 'expected' => 'K1A 0B1' ),
    array( 'id' => 'zero_like_nonempty_classic_value_retained', 'request' => array( 'postcode' => '00000' ), 'expected' => '00000' ),
    array( 'id' => 'classic_trim_uppercase_boundary', 'request' => array( 'postcode' => ' <b>ab1</b> 2cd ' ), 'expected' => 'AB1 2CD' ),
    array( 'id' => 'classic_whitespace_primary_stays_empty_not_secondary', 'request' => array( 'postcode' => '  ', 'billing_postcode' => 'ab1 2cd' ), 'expected' => '' ),
    array( 'id' => 'billing_zero_customer_retained', 'billing' => '0', 'expected' => '0', 'include' => '0*' ),
    array( 'id' => 'shipping_zero_customer_retained', 'type' => 'shipping', 'shipping' => '0', 'expected' => '0', 'exclude' => '0*', 'target' => false ),
    array( 'id' => 'billing_empty_customer_skips_postcode_rules', 'billing' => '', 'expected' => '', 'include' => 'AB*' ),
    array( 'id' => 'shipping_empty_customer_skips_postcode_rules', 'type' => 'shipping', 'shipping' => '', 'expected' => '', 'exclude' => '90210' ),
    array( 'id' => 'both_classic_values_empty_fall_back_to_customer', 'request' => array( 'postcode' => '', 'billing_postcode' => '' ), 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD' ),
    array( 'id' => 'both_classic_zero_values_fall_back_to_customer', 'request' => array( 'postcode' => '0', 'billing_postcode' => '0' ), 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD' ),
    array( 'id' => 'missing_customer_preserves_legacy_unjustified_store_fallback', 'customer' => false, 'store' => 'base1', 'expected' => 'BASE1', 'include' => 'BASE*' ),
    array( 'id' => 'missing_customer_shipping_preserves_store_fallback', 'customer' => false, 'type' => 'shipping', 'store' => 'base1', 'expected' => 'BASE1' ),
    array( 'id' => 'missing_customer_empty_base_preserved_legacy', 'customer' => false, 'store' => '', 'expected' => '', 'include' => 'AB*' ),
    array( 'id' => 'customer_include_exact_keeps_target', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD', 'include' => "ab12 3cd\nxy99" ),
    array( 'id' => 'customer_include_store_only_removes_target', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD', 'include' => '90210', 'target' => false ),
    array( 'id' => 'customer_exclude_exact_removes_target', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD', 'exclude' => 'ab12 3cd', 'target' => false ),
    array( 'id' => 'customer_exclude_store_only_keeps_target', 'billing' => 'ab12 3cd', 'expected' => 'AB12 3CD', 'exclude' => '90210' ),
    array( 'id' => 'shipping_customer_include_wildcard_keeps_target', 'type' => 'shipping', 'shipping' => 'k1a 0b1', 'expected' => 'K1A 0B1', 'include' => 'k1a*' ),
    array( 'id' => 'shipping_customer_exclude_wildcard_removes_target', 'type' => 'shipping', 'shipping' => 'k1a 0b1', 'expected' => 'K1A 0B1', 'exclude' => 'k1a*', 'target' => false ),
    array( 'id' => 'customer_include_range_keeps_target', 'billing' => '15000', 'expected' => '15000', 'include' => '10000...19999' ),
    array( 'id' => 'customer_exclude_range_removes_target', 'billing' => '15000', 'expected' => '15000', 'exclude' => '10000...19999', 'target' => false ),
    array( 'id' => 'customer_range_lower_bound_included', 'billing' => '10000', 'expected' => '10000', 'include' => '10000...19999' ),
    array( 'id' => 'customer_range_upper_bound_included', 'billing' => '19999', 'expected' => '19999', 'include' => '10000...19999' ),
    array( 'id' => 'customer_range_outside_removed', 'billing' => '20000', 'expected' => '20000', 'include' => '10000...19999', 'target' => false ),
    array( 'id' => 'classic_include_wildcard_unchanged', 'request' => array( 'postcode' => 'ab12 3cd' ), 'expected' => 'AB12 3CD', 'include' => 'ab*' ),
    array( 'id' => 'classic_exclude_wildcard_unchanged', 'request' => array( 'postcode' => 'ab12 3cd' ), 'expected' => 'AB12 3CD', 'exclude' => 'ab*', 'target' => false ),
    array( 'id' => 'classic_include_range_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'include' => '10000...19999' ),
    array( 'id' => 'classic_exclude_range_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'exclude' => '10000...19999', 'target' => false ),
    array( 'id' => 'include_match_does_not_override_exclusion', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'include' => '10000...19999', 'exclude' => '15*', 'target' => false ),
    array( 'id' => 'country_include_guard_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'extra' => array( 'wcj_gateways_countries_include_target' => array( 'CA' ) ), 'target' => false ),
    array( 'id' => 'country_exclude_guard_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'extra' => array( 'wcj_gateways_countries_exclude_target' => array( 'US' ) ), 'target' => false ),
    array( 'id' => 'state_include_guard_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'extra' => array( 'wcj_gateways_states_include_target' => array( 'CA' ) ), 'target' => false ),
    array( 'id' => 'state_exclude_guard_unchanged', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'extra' => array( 'wcj_gateways_states_exclude_target' => array( 'NY' ) ), 'target' => false ),
    // Existing literal "0" rule truthiness is recorded, not changed by this slice.
    array( 'id' => 'literal_zero_include_rule_is_existing_empty_option', 'request' => array( 'postcode' => '15000' ), 'expected' => '15000', 'include' => '0' ),
    array( 'id' => 'literal_zero_line_is_existing_array_filter_removal', 'customer' => false, 'store' => '0', 'expected' => '0', 'include' => "0\nX", 'target' => false ),
);
$original_case_count = count( $cases );
$original_assertion_count = 2 + 4 * $original_case_count;
if ( 'fallback-rest' === $predicate_mode ) { $cases = array(); }
else {
    $cases[] = array( 'id' => 'classic_final_billing_postcode_empty_intended_skip', 'request' => array( 'billing_postcode' => '' ), 'billing' => '', 'expected' => '', 'include' => 'AB*' );
    $cases[] = array( 'id' => 'classic_final_shipping_fields_empty_intended_customer_fallback', 'type' => 'shipping', 'request' => array( 's_postcode' => '', 'shipping_postcode' => '' ), 'shipping' => '15000', 'expected' => '15000', 'include' => '10000...19999' );
    $cases[] = array( 'id' => 'spoofed_rest_route_string_does_not_change_classic_predicate', 'request' => array( 'rest_route' => '/wc/store/v1/checkout', 'postcode' => 'ab12 3cd' ), 'expected' => 'AB12 3CD' );
}
if ( in_array( $predicate_mode, array( 'primary', 'fallback-rest' ), true ) ) {
    $rest_cases = array(
        array( 'id' => 'synthetic_rest_cart_billing_primary_conflict', 'request' => array( 'postcode' => '90210' ), 'include' => '10000...19999' ),
        array( 'id' => 'synthetic_rest_checkout_billing_secondary_conflict', 'request' => array( 'billing_postcode' => '90210' ), 'exclude' => '10000...19999', 'target' => false ),
        array( 'id' => 'synthetic_rest_shipping_primary_conflict', 'type' => 'shipping', 'request' => array( 's_postcode' => '90210' ), 'include' => '10000...19999' ),
        array( 'id' => 'synthetic_rest_shipping_secondary_conflict', 'type' => 'shipping', 'request' => array( 'shipping_postcode' => '90210' ), 'exclude' => '10000...19999', 'target' => false ),
        array( 'id' => 'synthetic_rest_all_four_conflicting_fields', 'request' => array( 'postcode' => '90210', 'billing_postcode' => '90210', 's_postcode' => '90210', 'shipping_postcode' => '90210' ), 'include' => '10000...19999' ),
        array( 'id' => 'synthetic_rest_billing_whitespace_does_not_skip_rules', 'request' => array( 'postcode' => '  ', 'billing_postcode' => '90210' ), 'exclude' => '15000', 'target' => false ),
        array( 'id' => 'synthetic_rest_shipping_whitespace_does_not_skip_rules', 'type' => 'shipping', 'request' => array( 's_postcode' => '  ', 'shipping_postcode' => '90210' ), 'exclude' => '15000', 'target' => false ),
        array( 'id' => 'synthetic_rest_array_billing_query_ignored', 'request' => array( 'postcode' => array( '90210' ), 'billing_postcode' => array( '90210' ) ), 'include' => '10000...19999' ),
        array( 'id' => 'synthetic_rest_array_shipping_query_ignored', 'type' => 'shipping', 'request' => array( 's_postcode' => array( '90210' ), 'shipping_postcode' => array( '90210' ) ), 'exclude' => '15000', 'target' => false ),
        array( 'id' => 'synthetic_rest_empty_customer_ignores_conflicting_query', 'billing' => '', 'expected' => '', 'request' => array( 'postcode' => '90210', 'billing_postcode' => '90210' ), 'include' => 'AB*' ),
        array( 'id' => 'synthetic_rest_zero_customer_ignores_conflicting_query', 'billing' => '0', 'expected' => '0', 'request' => array( 'postcode' => '90210' ), 'exclude' => '0*', 'target' => false ),
        array( 'id' => 'synthetic_rest_missing_customer_preserves_unjustified_store', 'customer' => false, 'store' => 'base1', 'expected' => 'BASE1', 'request' => array( 'postcode' => '90210' ), 'include' => 'BASE*' ),
    );
    foreach ( $rest_cases as $rest_case ) {
        $cases[] = array_merge( array( 'rest' => true, 'billing' => '15000', 'shipping' => '15000', 'expected' => '15000' ), $rest_case );
    }
}
if ( 'fallback-rest' !== $predicate_mode ) {
    $pay_cases = array(
        array( 'id' => 'pay_billing_preserves_store_fallback', 'billing' => '11111', 'store' => 'base1', 'expected' => 'BASE1', 'include' => 'BASE*' ),
        array( 'id' => 'pay_shipping_preserves_store_fallback_not_prior_session', 'type' => 'shipping', 'shipping' => '55555', 'store' => '16000', 'expected' => '16000', 'include' => '16000' ),
        array( 'id' => 'pay_nonempty_hydrated_billing_intentionally_returns_legacy_store', 'billing' => '15000', 'store' => '90210', 'expected' => '90210', 'include' => '15000', 'target' => false ),
        array( 'id' => 'pay_empty_customer_billing_uses_legacy_store', 'billing' => '', 'store' => '90210', 'expected' => '90210', 'exclude' => '90210', 'target' => false ),
        array( 'id' => 'pay_zero_customer_billing_uses_legacy_store', 'billing' => '0', 'store' => '90210', 'expected' => '90210', 'include' => '0*', 'target' => false ),
        array( 'id' => 'pay_empty_customer_shipping_uses_legacy_store', 'type' => 'shipping', 'shipping' => '', 'store' => '16000', 'expected' => '16000', 'exclude' => '16000', 'target' => false ),
        array( 'id' => 'pay_billing_primary_precedes_secondary', 'request' => array( 'postcode' => 'ab1 2cd', 'billing_postcode' => 'secondary' ), 'expected' => 'AB1 2CD' ),
        array( 'id' => 'pay_billing_secondary_preserved', 'request' => array( 'postcode' => '', 'billing_postcode' => 'secondary' ), 'expected' => 'SECONDARY' ),
        array( 'id' => 'pay_shipping_primary_precedes_secondary', 'type' => 'shipping', 'request' => array( 's_postcode' => 'firstship', 'shipping_postcode' => 'secondary' ), 'expected' => 'FIRSTSHIP' ),
        array( 'id' => 'pay_shipping_secondary_preserved', 'type' => 'shipping', 'request' => array( 's_postcode' => '', 'shipping_postcode' => 'secondaryship' ), 'expected' => 'SECONDARYSHIP' ),
        array( 'id' => 'pay_billing_empty_zero_request_arms_miss_to_store', 'request' => array( 'postcode' => '', 'billing_postcode' => '0' ), 'store' => 'base1', 'expected' => 'BASE1' ),
        array( 'id' => 'pay_shipping_zero_request_arms_miss_to_store', 'type' => 'shipping', 'request' => array( 's_postcode' => '0', 'shipping_postcode' => '0' ), 'store' => 'base1', 'expected' => 'BASE1' ),
        array( 'id' => 'request_strings_with_false_stubbed_pay_predicate_use_customer', 'pay' => false, 'request' => array( 'pay_for_order' => '1', 'order-pay' => '123' ), 'billing' => '11111', 'expected' => '11111' ),
        array( 'id' => 'pay_missing_customer_keeps_legacy_store', 'customer' => false, 'store' => 'base1', 'expected' => 'BASE1' ),
    );
    foreach ( $pay_cases as $pay_case ) { $cases[] = array_merge( array( 'pay' => true ), $pay_case ); }
}
if ( in_array( $predicate_mode, array( 'primary', 'fallback-rest' ), true ) ) {
    $rest_pay_cases = array(
        array( 'id' => 'rest_pay_true_billing_ignores_pay_and_all_classic_fields', 'request' => array( 'postcode' => '90210', 'billing_postcode' => '90210', 's_postcode' => '90210', 'shipping_postcode' => '90210' ), 'include' => '10000...19999' ),
        array( 'id' => 'rest_pay_true_shipping_ignores_pay_and_all_classic_fields', 'type' => 'shipping', 'request' => array( 'postcode' => '90210', 'billing_postcode' => '90210', 's_postcode' => '90210', 'shipping_postcode' => '90210' ), 'exclude' => '15000', 'target' => false ),
        array( 'id' => 'rest_pay_true_empty_customer_still_skips_postcode_rules', 'billing' => '', 'expected' => '', 'include' => 'AB*' ),
        array( 'id' => 'rest_pay_true_zero_customer_still_evaluated', 'billing' => '0', 'expected' => '0', 'exclude' => '0*', 'target' => false ),
        array( 'id' => 'rest_pay_true_missing_customer_keeps_v2_store_fallback', 'customer' => false, 'store' => 'base1', 'expected' => 'BASE1' ),
    );
    foreach ( $rest_pay_cases as $pay_case ) { $cases[] = array_merge( array( 'rest' => true, 'pay' => true, 'billing' => '15000', 'shipping' => '15000', 'expected' => '15000' ), $pay_case ); }
}
foreach ( $cases as $case ) {
    $endpoint_context = $case['rest'] ?? false;
    $pay_page_context = $case['pay'] ?? false;
    $pay_predicate_before = $pay_predicate_calls;
    $id       = $case['id'];
    $_REQUEST = $case['request'] ?? array();
    $options  = $case['extra'] ?? array();
    if ( isset( $case['type'] ) ) { $options['wcj_gateways_by_location_postcodes_type'] = $case['type']; }
    if ( isset( $case['include'] ) ) { $options['wcj_gateways_postcodes_include_target'] = $case['include']; }
    if ( isset( $case['exclude'] ) ) { $options['wcj_gateways_postcodes_exclude_target'] = $case['exclude']; }
    $woo->customer = ( $case['customer'] ?? true )
        ? new SyntheticCustomer( $case['billing'] ?? '11111', $case['shipping'] ?? '22222' )
        : null;
    $woo->countries = new SyntheticCountries( $case['store'] ?? '90210' );
    $gateways = array( 'target' => (object) array( 'id' => 'target' ), 'control' => (object) array( 'id' => 'control' ) );
    $before   = hash( 'sha256', serialize( array( $_REQUEST, $options, $woo, $gateways ) ) );
    try {
        $actual = $module->get_location( 'postcode' );
        record_check( $id, 'actual get_location postcode', $case['expected'], $actual );
        $actual_gateways = $module->available_payment_gateways( $gateways );
        $expected_keys  = ( $case['target'] ?? true ) ? array( 'target', 'control' ) : array( 'control' );
        record_check( $id, 'actual available_payment_gateways keys', $expected_keys, array_keys( $actual_gateways ) );
        $identity_ok = true;
        foreach ( $actual_gateways as $key => $gateway ) { $identity_ok = $identity_ok && isset( $gateways[ $key ] ) && $gateway === $gateways[ $key ]; }
        record_check( $id, 'retained gateway objects are unchanged', true, $identity_ok );
        record_check( $id, 'request options customer countries and input gateways are not mutated', $before, hash( 'sha256', serialize( array( $_REQUEST, $options, $woo, $gateways ) ) ) );
        if ( ! empty( $case['rest'] ) && ! empty( $case['pay'] ) ) { record_check( $id, 'REST short-circuits Classic pay predicate', $pay_predicate_before, $pay_predicate_calls ); }
    } catch ( Throwable $error ) {
        record_check( $id, 'no warning or exception', null, get_class( $error ) . ': ' . $error->getMessage() );
    }
}
$pay_page_context = false;
$endpoint_context = 'fallback-rest' === $predicate_mode || 'primary' === $predicate_mode;
$_REQUEST = array();
foreach ( array( 'billing', 'shipping' ) as $type ) {
    $options = array( 'wcj_gateways_by_location_postcodes_type' => $type, 'wcj_gateways_postcodes_include_target' => '10000...19999' );
    $woo->customer = new SyntheticCustomer( '11111', '11111' );
    $woo->countries = new SyntheticCountries( '90210' );
    $same_customer = $woo->customer;
    $gateways = array( 'target' => (object) array( 'id' => 'target' ), 'control' => (object) array( 'id' => 'control' ) );
    foreach ( array( '11111', '15000', '20000' ) as $index => $postcode ) {
        if ( $index > 0 ) { $woo->customer->{ $type . '_postcode' } = $postcode; }
        record_check( 'same_object_' . $type . '_change_' . $index, 'current postcode read without request cache', $postcode, $module->get_location( 'postcode' ) );
        record_check( 'same_object_' . $type . '_change_' . $index, 'actual gateway result follows current postcode', '20000' === $postcode ? array( 'control' ) : array( 'target', 'control' ), array_keys( $module->available_payment_gateways( $gateways ) ) );
    }
    record_check( 'same_object_' . $type, 'same customer instance retained across two mutations', true, $same_customer === $woo->customer );
}
$failed = count( array_filter( $results, static function ( $row ) { return 'FAIL' === $row['status']; } ) );
$source_evidence = array();
foreach ( $paths as $name => $path ) {
    $tokens = '';
    foreach ( token_get_all( file_get_contents( $path ) ) as $token ) {
        if ( is_array( $token ) ) {
            if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ), true ) ) { continue; }
            $tokens .= $token[1];
        } else { $tokens .= $token; }
    }
    $source_evidence[ $name ] = array( 'path' => $path, 'sha256' => hash_file( 'sha256', $path ), 'code_sha256' => hash( 'sha256', $tokens ) );
}
echo json_encode( array(
    'run_id' => 'booster-9.0.0-20261008-01',
    'contract' => 'implementation-02 / revision3 / CORR-S1-001',
    'tier' => $tier,
    'mode' => 'REVISION3 SOURCE-ISOLATED CLI REGRESSION, NOT RUNTIME OR INTEGRATION APPROVAL',
    'predicate_mode' => $predicate_mode,
    'predicate_function_defined' => function_exists( 'wp_is_rest_endpoint' ),
    'rest_request_constant' => defined( 'REST_REQUEST' ) ? REST_REQUEST : null,
    'original_assertions_preserved' => 'fallback-rest' === $predicate_mode ? 2 : $original_assertion_count,
    'php_version' => PHP_VERSION,
    'status' => $failed ? 'FAIL' : 'PASS_SOURCE_ISOLATION_ONLY',
    'scenarios' => count( $cases ) + 2,
    'assertions' => count( $results ),
    'passed' => count( $results ) - $failed,
    'failed' => $failed,
    'limitations' => array(
        'No WordPress, WooCommerce, Store API request, browser, real gateway, payment or session boot.',
        'Synthetic WC customer/countries and minimal WP sanitization stubs do not prove runtime ordering or full sanitizer equivalence.',
        'Missing-customer tests retain a valid WC countries object. Missing WC singleton/countries is outside this proposed correction.',
        'Present empty-customer skip semantics are approved; absent-customer store fallback is preserved legacy and unjustified, not safe.',
        'Nonempty non-REST request precedence remains. Missing/empty/zero Classic fields reach customer except when the public Classic pay predicate is true, which preserves the legacy shop fallback. REST ignores Classic pay context. The pay predicate is stubbed; the request-string case proves only that Booster does not parse those strings itself. Real WC_Query::parse_request copies the configured client GET order-pay endpoint into parsed context and can make the Classic pay predicate true on wc-ajax. Query parsing and spoof resistance are not tested here. Order-address correctness, literal zero rules and Classic query/whitespace issues remain unresolved.',
    ),
    'sources' => $source_evidence,
    'results' => $results,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( $failed ? 1 : 0 );
