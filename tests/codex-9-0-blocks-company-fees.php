<?php
/**
 * Independent source regression for the opt-in native billing-company fee.
 * Loads the complete actual Booster fee class, module base, settings and paid filter.
 * WP/Woo boundaries below are explicit CLI doubles, not a native request claim.
 * Usage: php this-file.php SOURCE_ROOT free|plus|elite normal|no-wc|no-customer-class|no-getter|goldens
 */

$root = isset( $argv[1] ) ? realpath( $argv[1] ) : false;
$tier = $argv[2] ?? '';
$mode = $argv[3] ?? 'normal';
if ( ! $root || ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) || ! in_array( $mode, array( 'normal', 'no-wc', 'no-customer-class', 'no-getter', 'goldens' ), true ) ) {
	fwrite( STDERR, "Invalid source root, tier or finite mode.\n" );
	exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['probe_mode'] = $mode;
$GLOBALS['probe_tier'] = $tier;
$GLOBALS['probe_rows'] = array();
$GLOBALS['probe_hooks'] = array();
$GLOBALS['probe_options'] = array();
$GLOBALS['probe_store'] = true;
$GLOBALS['probe_frontend'] = true;
$GLOBALS['probe_did'] = array();
$GLOBALS['probe_nonce_reads'] = 0;
$GLOBALS['probe_writes'] = 0;
$GLOBALS['probe_wc_failure'] = '';
$GLOBALS['probe_wc'] = null;

function __( $text, $domain = '' ) { return $text; }
function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['probe_hooks'][ $name ][ $priority ][] = array( $callback, $accepted_args );
}
function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) { add_filter( $name, $callback, $priority, $accepted_args ); }
function apply_filters( $name, $value, ...$args ) {
	$hooks = $GLOBALS['probe_hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, $entry[1] ) );
		}
	}
	return $value;
}
function do_action( $name, ...$args ) {
	$hooks = $GLOBALS['probe_hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) { call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) ); }
	}
}
function current_filter() { return ''; }
function did_action( $name ) { return $GLOBALS['probe_did'][ $name ] ?? 0; }
function get_option( $name, $default = false ) { return array_key_exists( $name, $GLOBALS['probe_options'] ) ? $GLOBALS['probe_options'][ $name ] : $default; }
function wcj_get_option( $name, $default = false ) { return get_option( $name, $default ); }
function wcj_is_module_enabled( $name ) { return 'yes' === get_option( 'wcj_' . $name . '_enabled', 'no' ); }
function wcj_is_store_api_request() { return $GLOBALS['probe_store']; }
function wcj_is_frontend() { return $GLOBALS['probe_frontend']; }
function update_option( ...$args ) { ++$GLOBALS['probe_writes']; throw new RuntimeException( 'Forbidden option write' ); }
function add_option( ...$args ) { ++$GLOBALS['probe_writes']; throw new RuntimeException( 'Forbidden option add' ); }
function delete_option( ...$args ) { ++$GLOBALS['probe_writes']; throw new RuntimeException( 'Forbidden option delete' ); }
function wp_unslash( $value ) {
	++$GLOBALS['probe_nonce_reads'];
	if ( $GLOBALS['probe_store'] ) { throw new RuntimeException( 'Store API read Classic request data' ); }
	return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( $value );
}
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function wp_verify_nonce( $value, $action ) { ++$GLOBALS['probe_nonce_reads']; return 'valid' === $value && 'wcj-process_checkout' === $action ? 1 : false; }
function absint( $value ) { return abs( (int) $value ); }

if ( 'no-wc' !== $mode ) {
	function WC() {
		if ( 'exception' === $GLOBALS['probe_wc_failure'] ) { throw new RuntimeException( 'Synthetic WC failure' ); }
		if ( 'error' === $GLOBALS['probe_wc_failure'] ) { throw new Error( 'Synthetic WC error' ); }
		return $GLOBALS['probe_wc'];
	}
}
if ( 'no-customer-class' !== $mode ) {
	if ( 'no-getter' === $mode ) {
		class WC_Customer {}
	} else {
		class WC_Customer {
			public $company = 'Literal Company';
			public $contexts = array();
			public $failure = '';
			public function get_billing_company( $context = 'view' ) {
				$this->contexts[] = $context;
				if ( 'exception' === $this->failure ) { throw new RuntimeException( 'Synthetic getter failure' ); }
				if ( 'error' === $this->failure ) { throw new Error( 'Synthetic getter error' ); }
				return 'edit' === $context ? $this->company : 'VIEW_CONTEXT_POISON';
			}
			public function save() { ++$GLOBALS['probe_writes']; throw new RuntimeException( 'Forbidden customer save' ); }
		}
	}
}
class Codex_Fee_Sink {
	public $fees = array();
	public function add_fee( $fee ) { $this->fees[] = $fee; return (object) $fee; }
}
class WC_Cart {
	public $quantity = 2;
	public $total = 80.0;
	public $sink;
	public function __construct() { $this->sink = new Codex_Fee_Sink(); }
	public function get_cart_contents_count() { return $this->quantity; }
	public function get_cart_contents_total() { return $this->total; }
	public function fees_api() { return $this->sink; }
}

$loaded = array();
if ( 'goldens' !== $mode ) {
	foreach ( array( 'includes/classes/class-wcj-module.php', 'includes/plus/class-wcj-plus-filters.php', 'includes/class-wcj-checkout-fees.php' ) as $relative ) {
		if ( 'free' === $tier && false !== strpos( $relative, '/plus/' ) ) { continue; }
		$path = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
		if ( ! is_file( $path ) ) { throw new RuntimeException( 'Missing full source dependency: ' . $relative ); }
		$loaded[ $relative ] = hash_file( 'sha256', $path );
		require $path;
	}
}

function check( $id, $expected, $callback ) {
	$row = array( 'id' => $id, 'expected' => $expected );
	if ( 'goldens' !== $GLOBALS['probe_mode'] ) {
		try {
			$row['actual'] = $callback();
			$row['pass'] = $row['actual'] === $expected;
		} catch ( Throwable $error ) {
			$row['actual'] = null;
			$row['pass'] = false;
			$row['error_class'] = get_class( $error );
			$row['error_message'] = $error->getMessage();
		}
	}
	$GLOBALS['probe_rows'][] = $row;
}
function reset_case( $changes = array() ) {
	$GLOBALS['probe_options'] = array(
		'wcj_checkout_fees_enabled' => 'yes', 'wcj_load_modules_on_init' => 'no',
		'wcj_checkout_fees_blocks_billing_company_enabled' => 'yes', 'woocommerce_checkout_company_field' => 'optional',
		'wcj_checkout_fees_total_number' => 1, 'wcj_checkout_fees_data_titles' => array( 1 => 'Literal fee' ),
		'wcj_checkout_fees_data_enabled' => array( 1 => 'yes' ), 'wcj_checkout_fees_data_values' => array( 1 => 7.5 ),
		'wcj_checkout_fees_data_types' => array( 1 => 'fixed' ), 'wcj_checkout_fees_data_taxable' => array( 1 => 'yes' ),
		'wcj_checkout_fees_data_checkout_fields' => array( 1 => 'billing_company' ),
		'wcj_checkout_fees_cart_min_amount' => array( 1 => 1 ), 'wcj_checkout_fees_cart_max_amount' => array( 1 => 0 ),
		'wcj_checkout_fees_cart_min_total_amount' => array( 1 => 0 ), 'wcj_checkout_fees_cart_max_total_amount' => array( 1 => 0 ),
		'wcj_checkout_fees_overlap' => array( 1 => array() ), 'wcj_checkout_fees_priority' => array( 1 => 0 ),
	);
	foreach ( $changes as $name => $value ) { $GLOBALS['probe_options'][ $name ] = $value; }
	$GLOBALS['probe_hooks'] = array();
	if ( 'free' !== $GLOBALS['probe_tier'] ) { new WCJ_Plus_Filters(); }
	$GLOBALS['probe_store'] = true;
	$GLOBALS['probe_frontend'] = true;
	$GLOBALS['probe_did'] = array();
	$GLOBALS['probe_nonce_reads'] = 0;
	$GLOBALS['probe_wc_failure'] = '';
	$GLOBALS['probe_wc'] = (object) array( 'customer' => class_exists( 'WC_Customer' ) ? new WC_Customer() : (object) array( 'company' => 'Literal Company' ) );
	$_POST = array( 'billing_company' => 'POST_POISON', 'wcj-process-checkout-nonce' => 'invalid' );
	$_REQUEST = array( 'billing_company' => '', 'wcj-process-checkout-nonce' => 'invalid', 'post_data' => 'billing_company=POISON&wcj-process-checkout-nonce=invalid' );
	return array( new WCJ_Checkout_Fees(), new WC_Cart() );
}
function native_boolean( $changes = array(), $company = 'Literal Company', $extra = null ) {
	list( $module, $cart ) = reset_case( $changes );
	if ( property_exists( $GLOBALS['probe_wc']->customer, 'company' ) ) { $GLOBALS['probe_wc']->customer->company = $company; }
	if ( $extra ) { $extra( $module, $cart ); }
	return $module->is_fee_valid( 1, $cart );
}
function fixed_fee() { return array( 'id' => 'wcj_checkout_fee_1', 'name' => 'Literal fee', 'amount' => 7.5, 'taxable' => true, 'tax_class' => 'standard' ); }

if ( ! in_array( $mode, array( 'normal', 'goldens' ), true ) ) {
	check( 'dependency-' . $mode . '-conditional-denied', false, function () { return native_boolean(); } );
	check( 'dependency-' . $mode . '-unconditional-preserved', true, function () { return native_boolean( array( 'wcj_checkout_fees_data_checkout_fields' => array( 1 => '' ) ) ); } );
} else {
	// Literal financial and safety expectations are frozen before baseline execution.
	check( 'native-optional-filled', true, function () { return native_boolean(); } );
	check( 'native-required-filled', true, function () { return native_boolean( array( 'woocommerce_checkout_company_field' => 'required' ) ); } );
	check( 'native-whitespace-nonempty', true, function () { return native_boolean( array(), '   ' ); } );
	check( 'native-string-zero-empty', false, function () { return native_boolean( array(), '0' ); } );
	check( 'native-empty', false, function () { return native_boolean( array(), '' ); } );
	foreach ( array( false, null, 0, 1, 1.5, array(), array( 'Literal Company' ), new stdClass() ) as $i => $value ) {
		check( 'native-nonstring-' . $i, false, function () use ( $value ) { return native_boolean( array(), $value ); } );
	}
	foreach ( array( 'no', '', 'YES', true, 1, null, array( 'yes' ) ) as $i => $value ) {
		check( 'native-nonexplicit-optin-' . $i, false, function () use ( $value ) { return native_boolean( array( 'wcj_checkout_fees_blocks_billing_company_enabled' => $value ) ); } );
	}
	check( 'native-optin-absent-default-off', false, function () { return native_boolean( array(), 'Literal Company', function () { unset( $GLOBALS['probe_options']['wcj_checkout_fees_blocks_billing_company_enabled'] ); } ); } );
	foreach ( array( 'hidden', '', 'unknown', 'OPTIONAL', null, false, array( 'optional' ) ) as $i => $value ) {
		check( 'native-visibility-denied-' . $i, false, function () use ( $value ) { return native_boolean( array( 'woocommerce_checkout_company_field' => $value ) ); } );
	}
	check( 'native-visibility-absent-retained-company-denied', false, function () { return native_boolean( array(), 'Retained Company', function () { unset( $GLOBALS['probe_options']['woocommerce_checkout_company_field'] ); } ); } );
	foreach ( array( 'shipping_company', 'billing_wcj_checkout_field_1', 'billing_email', 'Billing_company', 'billing_company ' ) as $i => $field ) {
		check( 'native-unsupported-key-' . $i, false, function () use ( $field ) { return native_boolean( array( 'wcj_checkout_fees_data_checkout_fields' => array( 1 => $field ) ) ); } );
	}
	foreach ( array( null, false, new stdClass() ) as $i => $customer ) {
		check( 'native-malformed-customer-' . $i, false, function () use ( $customer ) { return native_boolean( array(), 'Literal Company', function () use ( $customer ) { $GLOBALS['probe_wc']->customer = $customer; } ); } );
	}
	check( 'native-customer-property-absent', false, function () { return native_boolean( array(), 'Literal Company', function () { unset( $GLOBALS['probe_wc']->customer ); } ); } );
	foreach ( array( null, false, array(), 'malformed' ) as $i => $wc ) {
		check( 'native-malformed-wc-' . $i, false, function () use ( $wc ) { return native_boolean( array(), 'Literal Company', function () use ( $wc ) { $GLOBALS['probe_wc'] = $wc; } ); } );
	}
	foreach ( array( 'exception', 'error' ) as $failure ) {
		check( 'native-wc-' . $failure . '-denied', false, function () use ( $failure ) { return native_boolean( array(), 'Literal Company', function () use ( $failure ) { $GLOBALS['probe_wc_failure'] = $failure; } ); } );
		check( 'native-getter-' . $failure . '-denied', false, function () use ( $failure ) { return native_boolean( array(), 'Literal Company', function () use ( $failure ) { $GLOBALS['probe_wc']->customer->failure = $failure; } ); } );
	}
	check( 'native-edit-getter-context', array( true, array( 'edit' ) ), function () { list( $m, $c ) = reset_case(); $valid = $m->is_fee_valid( 1, $c ); return array( $valid, $GLOBALS['probe_wc']->customer->contexts ); } );
	check( 'native-post-poison-does-not-qualify-empty-customer', false, function () { return native_boolean( array(), '' ); } );
	check( 'native-no-classic-nonce-read', array( true, 0 ), function () { list( $m, $c ) = reset_case(); return array( $m->is_fee_valid( 1, $c ), $GLOBALS['probe_nonce_reads'] ); } );
	check( 'native-same-module-dynamic-current-customer', array( true, false, false, true, true, false ), function () {
		list( $m, $c ) = reset_case(); $result = array();
		foreach ( array( 'Initial Company', '', '0', ' ' ) as $value ) { $GLOBALS['probe_wc']->customer->company = $value; $result[] = $m->is_fee_valid( 1, $c ); }
		$GLOBALS['probe_wc']->customer = new WC_Customer(); $GLOBALS['probe_wc']->customer->company = 'Replacement Company'; $result[] = $m->is_fee_valid( 1, $c );
		$GLOBALS['probe_wc']->customer = null; $result[] = $m->is_fee_valid( 1, $c ); return $result;
	} );
	check( 'native-same-module-explicit-optin-toggle', array( true, false, true ), function () {
		list( $m, $c ) = reset_case(); $result = array();
		foreach ( array( 'yes', 'no', 'yes' ) as $value ) { $GLOBALS['probe_options']['wcj_checkout_fees_blocks_billing_company_enabled'] = $value; $result[] = $m->is_fee_valid( 1, $c ); } return $result;
	} );
	check( 'native-fee-disabled', false, function () { return native_boolean( array( 'wcj_checkout_fees_data_enabled' => array( 1 => 'no' ) ) ); } );
	check( 'native-zero-fee', false, function () { return native_boolean( array( 'wcj_checkout_fees_data_values' => array( 1 => 0 ) ) ); } );
	check( 'native-quantity-below-min', false, function () { return native_boolean( array( 'wcj_checkout_fees_cart_min_amount' => array( 1 => 3 ) ) ); } );
	check( 'native-quantity-above-max', false, function () { return native_boolean( array( 'wcj_checkout_fees_cart_max_amount' => array( 1 => 1 ) ) ); } );
	check( 'native-total-below-min', false, function () { return native_boolean( array( 'wcj_checkout_fees_cart_min_total_amount' => array( 1 => 81 ) ) ); } );
	check( 'native-total-above-max', false, function () { return native_boolean( array( 'wcj_checkout_fees_cart_max_total_amount' => array( 1 => 79 ) ) ); } );
	check( 'native-boundaries-inclusive', true, function () { return native_boolean( array( 'wcj_checkout_fees_cart_min_amount' => array( 1 => 2 ), 'wcj_checkout_fees_cart_max_amount' => array( 1 => 2 ), 'wcj_checkout_fees_cart_min_total_amount' => array( 1 => 80 ), 'wcj_checkout_fees_cart_max_total_amount' => array( 1 => 80 ) ) ); } );
	check( 'native-unconditional-optin-off-preserved', true, function () { return native_boolean( array( 'wcj_checkout_fees_data_checkout_fields' => array( 1 => '' ), 'wcj_checkout_fees_blocks_billing_company_enabled' => 'no' ), '' ); } );
	check( 'module-disabled-no-fee-hook', array(), function () { list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_enabled' => 'no' ) ); do_action( 'woocommerce_cart_calculate_fees', $c ); return $c->sink->fees; } );

	foreach ( array(
		array( 'classic-valid-nonce-filled', 'valid', 'Literal Company', '', 0, true ),
		array( 'classic-valid-nonce-empty', 'valid', '', '', 0, false ),
		array( 'classic-valid-nonce-zero', 'valid', '0', '', 0, false ),
		array( 'classic-valid-nonce-whitespace', 'valid', '   ', '', 0, true ),
		array( 'classic-invalid-nonce', 'invalid', 'Literal Company', '', 0, false ),
		array( 'classic-final-created-account-fallback', 'invalid', 'Literal Company', 'checkout', 1, true ),
		array( 'classic-final-no-process-action', 'invalid', 'Literal Company', 'checkout', 0, false ),
		array( 'classic-update-review-no-fallback', 'invalid', 'Literal Company', 'update_order_review', 1, false ),
	) as $case ) {
		check( $case[0], $case[5], function () use ( $case ) { list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_blocks_billing_company_enabled' => 'no', 'woocommerce_checkout_company_field' => 'hidden' ) ); $GLOBALS['probe_store'] = false; $_REQUEST = array( 'wcj-process-checkout-nonce' => $case[1], 'billing_company' => $case[2] ); if ( $case[3] ) { $_REQUEST['wc-ajax'] = $case[3]; } $GLOBALS['probe_did']['woocommerce_checkout_process'] = $case[4]; return $m->is_fee_valid( 1, $c ); } );
	}
	check( 'classic-serialized-post-data-valid', true, function () { list( $m, $c ) = reset_case(); $GLOBALS['probe_store'] = false; $_REQUEST = array( 'post_data' => 'wcj-process-checkout-nonce=valid&billing_company=Literal+Company' ); return $m->is_fee_valid( 1, $c ); } );
	check( 'classic-custom-field-preserved', true, function () { list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_data_checkout_fields' => array( 1 => 'billing_wcj_checkout_field_1' ) ) ); $GLOBALS['probe_store'] = false; $_REQUEST = array( 'wcj-process-checkout-nonce' => 'valid', 'billing_wcj_checkout_field_1' => 'checked' ); return $m->is_fee_valid( 1, $c ); } );

	check( 'native-fixed-actual-fee', array( 1 => fixed_fee() ), function () { list( $m, $c ) = reset_case(); return $m->get_valid_fees( $c ); } );
	check( 'native-percent-actual-fee-tax-disabled', array( 1 => array( 'id' => 'wcj_checkout_fee_1', 'name' => 'Literal fee', 'amount' => 8.0, 'taxable' => false, 'tax_class' => 'standard' ) ), function () { list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_data_types' => array( 1 => 'percent' ), 'wcj_checkout_fees_data_values' => array( 1 => 10.0 ), 'wcj_checkout_fees_data_taxable' => array( 1 => 'no' ) ) ); return $m->get_valid_fees( $c ); } );
	check( 'native-hook-fee-api-payload', array( fixed_fee() ), function () { list( $m, $c ) = reset_case(); $GLOBALS['probe_frontend'] = false; do_action( 'woocommerce_cart_calculate_fees', $c ); return $c->sink->fees; } );
	check( 'native-current-company-clear-removes-fee-on-same-module', array( array( 1 => fixed_fee() ), array(), array( 1 => fixed_fee() ) ), function () { list( $m, $c ) = reset_case(); $rows = array( $m->get_valid_fees( $c ) ); $GLOBALS['probe_wc']->customer->company = ''; $rows[] = $m->get_valid_fees( $c ); $GLOBALS['probe_wc']->customer->company = 'Restored Company'; $rows[] = $m->get_valid_fees( $c ); return $rows; } );
	check( 'actual-tier-gate', 'free' === $tier ? 1 : 3, function () { list( $m, $c ) = reset_case(); return apply_filters( 'booster_option', 1, 3 ); } );
	check( 'classic-paid-overlap-priority-entitlement', 'free' === $tier ? array( 1 => 7.5 ) : array( 1 => 7.5, 3 => 2.0 ), function () {
		list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_total_number' => 3, 'wcj_checkout_fees_data_titles' => array( 1 => 'One', 2 => 'Two', 3 => 'Three' ), 'wcj_checkout_fees_data_enabled' => array( 1 => 'yes', 2 => 'yes', 3 => 'yes' ), 'wcj_checkout_fees_data_values' => array( 1 => 7.5, 2 => 10.0, 3 => 2.0 ), 'wcj_checkout_fees_data_types' => array( 1 => 'fixed', 2 => 'percent', 3 => 'fixed' ), 'wcj_checkout_fees_data_checkout_fields' => array(), 'wcj_checkout_fees_overlap' => array( 1 => array( 2 ), 2 => array(), 3 => array() ), 'wcj_checkout_fees_priority' => array( 1 => 10, 2 => 5, 3 => 1 ) ) ); $GLOBALS['probe_store'] = false; return array_map( function ( $fee ) { return $fee['amount']; }, $m->get_valid_fees( $c ) );
	} );
	check( 'classic-paid-ignore-overlap-order-and-amount', 'free' === $tier ? array( 1 => 7.5 ) : array( 2 => 8.0, 1 => 7.5, 3 => 2.0 ), function () {
		list( $m, $c ) = reset_case( array( 'wcj_checkout_fees_total_number' => 3, 'wcj_checkout_fees_data_values' => array( 1 => 7.5, 2 => 10.0, 3 => 2.0 ), 'wcj_checkout_fees_data_types' => array( 1 => 'fixed', 2 => 'percent', 3 => 'fixed' ), 'wcj_checkout_fees_data_checkout_fields' => array(), 'wcj_checkout_fees_overlap' => array( 1 => array( 2 ) ), 'wcj_checkout_fees_priority' => array( 1 => 5, 2 => 10, 3 => 1 ) ) ); $GLOBALS['probe_store'] = false; return array_map( function ( $fee ) { return $fee['amount']; }, $m->get_valid_fees( $c, false ) );
	} );
	check( 'merchant-setting-explicit-default-no', array( 'checkbox', 'no' ), function () use ( $root ) {
		reset_case(); $settings = include $root . '/includes/settings/wcj-settings-checkout-fees.php';
		foreach ( $settings as $setting ) { if ( 'wcj_checkout_fees_blocks_billing_company_enabled' === ( $setting['id'] ?? '' ) ) { return array( $setting['type'] ?? null, $setting['default'] ?? null ); } } return null;
	} );
}
check( 'no-business-option-customer-writes', 0, function () { return $GLOBALS['probe_writes']; } );
$rows = $GLOBALS['probe_rows'];
$failures = 'goldens' === $mode ? array() : array_values( array_filter( $rows, function ( $row ) { return ! $row['pass']; } ) );
echo json_encode( array( 'tier' => $tier, 'mode' => $mode, 'source_only' => true, 'actual_complete_sources' => $loaded, 'fixture_sha256' => hash_file( 'sha256', __FILE__ ), 'cases' => count( $rows ), 'passed' => 'goldens' === $mode ? null : count( $rows ) - count( $failures ), 'failed' => 'goldens' === $mode ? null : count( $failures ), 'rows' => $rows, 'limits' => array( 'WP_Woo_boundaries_are_doubles', 'no_native_request_timing', 'no_third_party_UI_visibility_proof', 'no_native_browser_payment_or_package_acceptance' ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR ) . "\n";
exit( count( $failures ) ? 1 : 0 );
