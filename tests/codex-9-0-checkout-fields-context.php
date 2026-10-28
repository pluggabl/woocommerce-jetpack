<?php
/**
 * Actual Blocks adapter cart-context regression fixture.
 *
 * CLI only. WordPress/WooCommerce boundaries are synthetic doubles, not native
 * Store API, browser, database or entitlement proof. The full actual adapter is
 * required unchanged and its public callbacks exercise its private predicates.
 *
 * @package Booster_Checkout_Field_Tests
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
$args = getopt( '', array( 'root:', 'wc-version:', 'mode:' ) );
if ( empty( $args['root'] ) ) {
	throw new RuntimeException( 'Missing --root actual source argument.' );
}
$mode = $args['mode'] ?? 'normal';
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
if ( 'undefined' !== ( $args['wc-version'] ?? '11.1.0' ) ) {
	define( 'WC_VERSION', $args['wc-version'] ?? '11.1.0' );
}
$GLOBALS['wcj_fields_test'] = array(
	'options'       => array(),
	'filters'       => array(),
	'cart'          => null,
	'terms'         => array(
		1 => array( 10 ),
		2 => array( 20 ),
		3 => array( 30 ),
	),
	'errors'        => array(),
	'term_calls'    => array(),
	'actions'       => array(),
	'registrations' => array(),
	'extensions'    => array(),
	'blocks_loaded' => 0,
	'mutations'     => 0,
);
$results                    = array();

/** Synthetic WordPress error, not native WP_Error validation. */
class WP_Error {
	public $code;
	public function __construct( $code ) {
		$this->code = $code;
	}
}
/** Synthetic cart exposes passive reads; business mutations fail immediately. */
class WCJ_Fields_Test_Cart {
	public $items          = array();
	public $total          = 75;
	public $total_contexts = array();
	public $cart_reads = 0;
	public $replace_after_first = null;
	public function __construct( $ids ) {
		$this->set_ids( $ids );
	}
	public function set_ids( $ids ) {
		$this->items = array_map(
			static function ( $id ) {
				return array(
					'product_id'   => $id,
					'variation_id' => 1000 + (int) $id,
				);
			},
			$ids
		);
	}
	public function get_cart() {
		++$this->cart_reads;
		$snapshot = $this->items;
		if ( 1 === $this->cart_reads && null !== $this->replace_after_first ) {
			$this->set_ids( $this->replace_after_first );
		}
		return $snapshot;
	}
	public function is_empty() {
		return empty( $this->items );
	}
	public function get_total( $context ) {
		$this->total_contexts[] = $context;
		return $this->total;
	}
	public function calculate_totals() {
		throw new RuntimeException( 'Unexpected total calculation.' );
	}
	public function set_session() {
		throw new RuntimeException( 'Unexpected session persistence.' );
	}
}
/** Synthetic order records metadata only; no native storage or HPOS proof. */
class WC_Order {
	public $meta = array();
	public function update_meta_data( $key, $value ) {
		++$GLOBALS['wcj_fields_test']['mutations'];
		$this->meta[ $key ] = $value;
	}
	public function save() {
		throw new RuntimeException( 'Unexpected order save.' );
	}
}
class WCJ_Fields_Test_Cart_Schema {
	const IDENTIFIER = 'cart';
}
if ( 'no-api' !== $mode ) {
	class_alias( 'WCJ_Fields_Test_Cart_Schema', 'Automattic\\WooCommerce\\StoreApi\\Schemas\\V1\\CartSchema' );
	function woocommerce_register_additional_checkout_field( $args ) {
		$GLOBALS['wcj_fields_test']['registrations'][] = $args;
	}
	function woocommerce_store_api_register_endpoint_data( $args ) {
		$GLOBALS['wcj_fields_test']['extensions'][] = $args;
	}
}
if ( 'no-wc' !== $mode ) {
	function WC() {
		return (object) array( 'cart' => $GLOBALS['wcj_fields_test']['cart'] );
	}
}
function __( $value, $domain = null ) {
	return $value;
}
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['wcj_fields_test']['actions'][] = array( $hook, $callback[1], $priority, $args );
}
function did_action( $hook ) {
	return $GLOBALS['wcj_fields_test']['blocks_loaded'];
}
function wcj_get_option( $name, $default = false ) {
	return $GLOBALS['wcj_fields_test']['options'][ $name ] ?? $default;
}
function apply_filters( $name, $value, ...$args ) {
	return $GLOBALS['wcj_fields_test']['filters'][ $name ] ?? $value;
}
function wp_get_post_terms( $id, $taxonomy, $args ) {
	$GLOBALS['wcj_fields_test']['term_calls'][] = array( $id, $taxonomy, $args );
	if ( ! empty( $GLOBALS['wcj_fields_test']['errors'][ $id ] ) ) {
		--$GLOBALS['wcj_fields_test']['errors'][ $id ];
		return new WP_Error( 'synthetic-transient-term-error' );
	}
	return $GLOBALS['wcj_fields_test']['terms'][ $id ] ?? array();
}
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}
function absint( $value ) {
	return abs( (int) $value );
}
function wp_list_pluck( $items, $key ) {
	return array_column( $items, $key );
}
function sanitize_title( $value ) {
	return strtolower( str_replace( ' ', '-', trim( $value ) ) );
}
function wp_unslash( $value ) {
	return stripslashes( $value );
}
function wc_clean( $value ) {
	return trim( strip_tags( $value ) );
}
function update_option( $name, $value ) {
	throw new RuntimeException( 'Unexpected option persistence.' );
}
function wcj_fields_test_expect( $id, $actual, $expected ) {
	global $results;
	$results[] = array(
		'id'       => $id,
		'pass'     => $actual === $expected,
		'actual'   => $actual,
		'expected' => $expected,
	);
}
function wcj_fields_test_adapter( $overrides = array(), $ids = array( 1 ), $limit = 1 ) {
	$GLOBALS['wcj_fields_test']['options'] = array(
		'wcj_checkout_custom_field_enabled_1'  => 'yes',
		'wcj_checkout_custom_field_label_1'    => 'Synthetic field',
		'wcj_checkout_custom_field_required_1' => 'yes',
		'wcj_checkout_custom_field_section_1'  => 'billing',
		'wcj_checkout_custom_field_enabled_2'  => 'yes',
	);
	foreach ( $overrides as $suffix => $value ) {
		$GLOBALS['wcj_fields_test']['options'][ 'wcj_checkout_custom_field_' . $suffix . '_1' ] = $value;
	}
	$GLOBALS['wcj_fields_test']['cart']          = null === $ids ? null : new WCJ_Fields_Test_Cart( $ids );
	$GLOBALS['wcj_fields_test']['filters']       = array();
	$GLOBALS['wcj_fields_test']['errors']        = array();
	$GLOBALS['wcj_fields_test']['term_calls']    = array();
	$GLOBALS['wcj_fields_test']['registrations'] = array();
	return new WCJ_Checkout_Custom_Fields_Blocks( $limit );
}
function wcj_fields_test_visible( $adapter ) {
	return $adapter->get_store_api_data()['checkout_field_visibility']['field_1'];
}
function wcj_fields_test_private( $adapter, $name ) {
	return ( new ReflectionMethod( $adapter, $name ) )->invoke( $adapter );
}
$source = rtrim( $args['root'], '/\\' ) . '/includes/class-wcj-checkout-custom-fields-blocks.php';
require $source;

$adapter = wcj_fields_test_adapter( array( 'products_in' => array( '2' ) ) );
wcj_fields_test_expect(
	'constructor-hooks',
	array_slice( $GLOBALS['wcj_fields_test']['actions'], -3 ),
	array(
		array( 'woocommerce_init', 'register_blocks_checkout_fields', 10, 1 ),
		array( 'woocommerce_set_additional_field_value', 'bridge_additional_field_value', 10, 4 ),
		array( 'woocommerce_blocks_loaded', 'register_store_api_data', 10, 1 ),
	)
);
if ( 'no-wc' === $mode ) {
	wcj_fields_test_expect( 'missing-WC-conditioned-unavailable', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'missing-WC-unconditioned-visible', wcj_fields_test_visible( wcj_fields_test_adapter() ), true );
} else {
	$adapter = wcj_fields_test_adapter( array( 'products_in' => array( '1' ), 'categories_in' => array( '10' ) ), array( 1 ) );
	$cart = $GLOBALS['wcj_fields_test']['cart'];
	$cart->replace_after_first = array( 2 );
	wcj_fields_test_expect( 'one-snapshot-first-visible', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'one-snapshot-first-read-count', $cart->cart_reads, 1 );
	wcj_fields_test_expect( 'one-snapshot-first-categories', $GLOBALS['wcj_fields_test']['term_calls'], array( array( 1, 'product_cat', array( 'fields' => 'ids' ) ) ) );
	wcj_fields_test_expect( 'one-snapshot-next-call-fresh', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'one-snapshot-next-read-count', $cart->cart_reads, 2 );
	wcj_fields_test_expect( 'one-snapshot-next-categories', $GLOBALS['wcj_fields_test']['term_calls'], array( array( 1, 'product_cat', array( 'fields' => 'ids' ) ), array( 2, 'product_cat', array( 'fields' => 'ids' ) ) ) );
	foreach ( array(
		'products_in'   => array( '2' ),
		'categories_in' => array( '20' ),
		'categories_ex' => array( '20' ),
	) as $rule => $list ) {
		$adapter  = wcj_fields_test_adapter( array( $rule => $list ) );
		$states   = array( array( 1 ), array( 1, 2 ), array( 1 ), array( 2 ), array(), array( 2 ) );
		$expected = 'categories_ex' === $rule ? array( true, false, true, false, true, false ) : array( false, true, false, true, false, true );
		foreach ( $states as $i => $ids ) {
			$GLOBALS['wcj_fields_test']['cart']->set_ids( $ids );
			wcj_fields_test_expect( $rule . '-same-object-transition-' . $i, wcj_fields_test_visible( $adapter ), $expected[ $i ] );
		}
	}
	$adapter = wcj_fields_test_adapter( array( 'products_ex' => array( '2' ) ), array( 1 ) );
	wcj_fields_test_expect( 'product-exclude-before-add', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['cart']->set_ids( array( 1, 2 ) );
	wcj_fields_test_expect( 'product-exclude-after-add', wcj_fields_test_visible( $adapter ), false );
	$GLOBALS['wcj_fields_test']['cart']->set_ids( array( 1 ) );
	wcj_fields_test_expect( 'product-exclude-after-remove', wcj_fields_test_visible( $adapter ), true );

	$adapter = wcj_fields_test_adapter( array( 'categories_in' => array( '20' ) ), null );
	wcj_fields_test_expect( 'absent-cart-conditioned', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'absent-cart-unconditioned', wcj_fields_test_visible( wcj_fields_test_adapter( array(), null ) ), true );
	$GLOBALS['wcj_fields_test']['cart'] = new WCJ_Fields_Test_Cart( array() );
	wcj_fields_test_expect( 'present-empty-before-fill', wcj_fields_test_visible( $adapter ), false );
	$GLOBALS['wcj_fields_test']['cart']->set_ids( array( 2 ) );
	wcj_fields_test_expect( 'empty-to-filled', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['cart'] = null;
	wcj_fields_test_expect( 'present-to-absent', wcj_fields_test_visible( $adapter ), false );
	$GLOBALS['wcj_fields_test']['cart'] = new WCJ_Fields_Test_Cart( array( 1 ) );
	wcj_fields_test_expect( 'new-cart-different-products', wcj_fields_test_visible( $adapter ), false );
	$GLOBALS['wcj_fields_test']['cart'] = new WCJ_Fields_Test_Cart( array( 2 ) );
	wcj_fields_test_expect( 'new-cart-restored-products', wcj_fields_test_visible( $adapter ), true );

	$adapter = wcj_fields_test_adapter( array( 'categories_in' => array( '10' ) ), array( 1, 1, 2 ) );
	wcj_fields_test_expect( 'duplicate-products-first-read', wcj_fields_test_private( $adapter, 'get_cart_product_ids' ), array( '1', '2' ) );
	wcj_fields_test_expect( 'duplicate-products-repeat-read', wcj_fields_test_private( $adapter, 'get_cart_product_ids' ), array( '1', '2' ) );
	wcj_fields_test_expect( 'category-query-unique-first', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'unique-category-query-count', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 2 );
	wcj_fields_test_expect( 'unchanged-repeat-visible', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['cart']->set_ids( array( 2, 1, 1 ) );
	wcj_fields_test_expect( 'reordered-duplicate-set-visible', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['cart'] = new WCJ_Fields_Test_Cart( array( 2, 1 ) );
	wcj_fields_test_expect( 'same-products-new-instance-visible', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['cart']->items[0]['variation_id'] = 9999;
	$GLOBALS['wcj_fields_test']['cart']->items[0]['quantity']     = 12;
	wcj_fields_test_expect( 'variation-and-quantity-do-not-widen-parent-identities', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'equivalent-identity-no-category-requery', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 2 );
	wcj_fields_test_expect( 'supported-term-query-arguments', $GLOBALS['wcj_fields_test']['term_calls'][0], array( 1, 'product_cat', array( 'fields' => 'ids' ) ) );
	$GLOBALS['wcj_fields_test']['cart']->set_ids( array( 3 ) );
	wcj_fields_test_expect( 'different-set-invalidates-category-visible', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'different-set-query-count', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 3 );

	$adapter                                 = wcj_fields_test_adapter( array( 'categories_in' => array( '10' ) ) );
	$GLOBALS['wcj_fields_test']['errors'][1] = 1;
	wcj_fields_test_expect( 'term-error-current-partial-legacy-result', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'term-error-next-read-recovered', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'successful-recovery-cache-reused', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'transient-term-error-retries-once', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 2 );
	$adapter                                 = wcj_fields_test_adapter( array( 'categories_in' => array( '20' ) ), array( 1, 2 ) );
	$GLOBALS['wcj_fields_test']['errors'][2] = 1;
	wcj_fields_test_expect( 'partial-category-result-not-poisoned', wcj_fields_test_private( $adapter, 'get_cart_category_ids' ), array( '10' ) );
	wcj_fields_test_expect( 'partial-category-next-read-complete', wcj_fields_test_private( $adapter, 'get_cart_category_ids' ), array( '10', '20' ) );
	wcj_fields_test_expect( 'complete-category-cache-stable', wcj_fields_test_private( $adapter, 'get_cart_category_ids' ), array( '10', '20' ) );
	wcj_fields_test_expect( 'partial-category-query-count', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 4 );
	$adapter = wcj_fields_test_adapter( array( 'categories_in' => array( '99' ) ), array( 4 ) );
	wcj_fields_test_expect( 'successful-empty-category-result', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'successful-empty-category-repeat', wcj_fields_test_visible( $adapter ), false );
	wcj_fields_test_expect( 'successful-empty-category-is-cacheable', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 1 );

	$adapter = wcj_fields_test_adapter(
		array(
			'min_cart_amount' => 50,
			'max_cart_amount' => 100,
		)
	);
	foreach ( array(
		40  => false,
		50  => true,
		100 => true,
		101 => false,
	) as $total => $visible ) {
		$GLOBALS['wcj_fields_test']['cart']->total = $total;
		wcj_fields_test_expect( 'fresh-total-boundary-' . $total, wcj_fields_test_visible( $adapter ), $visible );
	}
	wcj_fields_test_expect( 'totals-edit-context', $GLOBALS['wcj_fields_test']['cart']->total_contexts, array( 'edit', 'edit', 'edit', 'edit' ) );
	wcj_fields_test_expect( 'total-only-change-no-category-requery', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 1 );

	$adapter = wcj_fields_test_adapter(
		array(
			'products_in'     => array( '1' ),
			'min_cart_amount' => 100,
			'max_cart_amount' => 1,
		)
	);
	$GLOBALS['wcj_fields_test']['filters']['wcj_checkout_custom_field_visible'] = false;
	wcj_fields_test_expect( 'product-include-early-return-before-totals-and-final-filter', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'product-include-no-total-read', $GLOBALS['wcj_fields_test']['cart']->total_contexts, array() );
	foreach ( array(
		'categories_ex' => array( '10' ),
		'categories_in' => array( '20' ),
		'products_ex'   => array( '1' ),
	) as $rule => $ids ) {
		$adapter = wcj_fields_test_adapter(
			array(
				'products_in' => array( '1' ),
				$rule         => $ids,
			)
		);
		wcj_fields_test_expect( $rule . '-precedes-product-include', wcj_fields_test_visible( $adapter ), false );
	}
	$adapter = wcj_fields_test_adapter();
	wcj_fields_test_expect( 'ordinary-final-filter-default', wcj_fields_test_visible( $adapter ), true );
	$GLOBALS['wcj_fields_test']['filters']['wcj_checkout_custom_field_visible'] = false;
	wcj_fields_test_expect( 'ordinary-final-filter-veto', wcj_fields_test_visible( $adapter ), false );
	$adapter = wcj_fields_test_adapter( array( 'products_in' => array( '2' ) ), array() );
	$GLOBALS['wcj_fields_test']['filters']['wcj_checkout_custom_field_always_visible_on_empty_cart'] = true;
	wcj_fields_test_expect( 'empty-cart-filter-override', wcj_fields_test_visible( $adapter ), true );
	wcj_fields_test_expect( 'empty-override-no-category-query', count( $GLOBALS['wcj_fields_test']['term_calls'] ), 0 );
	$adapter                                   = wcj_fields_test_adapter( array( 'products_in' => array( '1' ) ) );
	$GLOBALS['wcj_fields_test']['cart']->items = array( array( 'variation_id' => 1 ), array( 'product_id' => 0 ), array( 'product_id' => 1 ) );
	wcj_fields_test_expect( 'missing-id-skipped-zero-parent-preserved', wcj_fields_test_private( $adapter, 'get_cart_product_ids' ), array( '0', '1' ) );
	wcj_fields_test_expect( 'projection-does-not-mutate-order-or-options', $GLOBALS['wcj_fields_test']['mutations'], 0 );
}

$adapter = wcj_fields_test_adapter( array( 'products_in' => array( '2' ) ) );
$adapter->register_blocks_checkout_fields();
$conditionals = defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '9.9.0', '>=' );
wcj_fields_test_expect( 'conditioned-registration-version-and-api-gate', count( $GLOBALS['wcj_fields_test']['registrations'] ), 'no-api' !== $mode && $conditionals ? 1 : 0 );
if ( 'no-api' !== $mode && $conditionals ) {
	$field = $GLOBALS['wcj_fields_test']['registrations'][0];
	$path  = 'booster-wcj';
	wcj_fields_test_expect( 'hidden-condition-current-extension-false', $field['hidden']['cart']['properties']['extensions']['properties'][ $path ]['properties']['checkout_field_visibility']['properties']['field_1'], array( 'const' => false ) );
	wcj_fields_test_expect( 'required-condition-current-extension-true', $field['required']['cart']['properties']['extensions']['properties'][ $path ]['properties']['checkout_field_visibility']['properties']['field_1'], array( 'const' => true ) );
	wcj_fields_test_expect( 'existing-order-location', $field['location'], 'order' );
	wcj_fields_test_expect( 'existing-field-id', $field['id'], 'booster-wcj/checkout-field-1' );
}
$adapter = wcj_fields_test_adapter();
$adapter->register_blocks_checkout_fields();
wcj_fields_test_expect( 'unconditioned-registration-api-gate', count( $GLOBALS['wcj_fields_test']['registrations'] ), 'no-api' !== $mode ? 1 : 0 );
if ( 'no-api' !== $mode ) {
	wcj_fields_test_expect( 'unconditioned-required-boolean', $GLOBALS['wcj_fields_test']['registrations'][0]['required'], true );
	wcj_fields_test_expect( 'unconditioned-has-no-hidden-schema', isset( $GLOBALS['wcj_fields_test']['registrations'][0]['hidden'] ), false );
}
$adapter = wcj_fields_test_adapter( array(), array( 1 ), 0 );
wcj_fields_test_expect( 'zero-configured-field-bound', $adapter->get_store_api_data(), array( 'checkout_field_visibility' => array() ) );
$adapter->register_blocks_checkout_fields();
wcj_fields_test_expect( 'zero-configured-registration-bound', count( $GLOBALS['wcj_fields_test']['registrations'] ), 0 );
$adapter = wcj_fields_test_adapter( array(), array( 1 ), 1 );
wcj_fields_test_expect( 'caller-one-field-bound-not-expanded', array_keys( $adapter->get_store_api_schema()['checkout_field_visibility']['properties'] ), array( 'field_1' ) );
wcj_fields_test_expect( 'schema-readonly', $adapter->get_store_api_schema()['checkout_field_visibility']['readonly'], true );
$GLOBALS['wcj_fields_test']['extensions'] = array();
$adapter->register_store_api_data();
wcj_fields_test_expect( 'store-api-registration-availability', count( $GLOBALS['wcj_fields_test']['extensions'] ), 'no-api' !== $mode ? 1 : 0 );
if ( 'no-api' !== $mode ) {
	$extension = $GLOBALS['wcj_fields_test']['extensions'][0];
	wcj_fields_test_expect( 'store-api-endpoint-namespace-and-shape', array( $extension['endpoint'], $extension['namespace'], $extension['schema_type'] ), array( 'cart', 'booster-wcj', ARRAY_A ) );
}
$GLOBALS['wcj_fields_test']['blocks_loaded'] = 1;
$GLOBALS['wcj_fields_test']['extensions']    = array();
$adapter                                     = wcj_fields_test_adapter();
wcj_fields_test_expect( 'already-loaded-blocks-register-immediately', count( $GLOBALS['wcj_fields_test']['extensions'] ), 'no-api' !== $mode ? 1 : 0 );
$GLOBALS['wcj_fields_test']['blocks_loaded'] = 0;

foreach ( array( 'text', 'checkbox', 'select', 'radio', 'file', 'datepicker' ) as $type ) {
	$adapter = wcj_fields_test_adapter(
		array(
			'type'           => $type,
			'select_options' => "Red\nBlue",
		)
	);
	$adapter->register_blocks_checkout_fields();
	$supported = in_array( $type, array( 'text', 'checkbox', 'select', 'radio' ), true );
	wcj_fields_test_expect( 'existing-field-type-' . $type, count( $GLOBALS['wcj_fields_test']['registrations'] ), $supported && 'no-api' !== $mode ? 1 : 0 );
	if ( 'no-api' !== $mode && in_array( $type, array( 'select', 'radio' ), true ) ) {
		$field = $GLOBALS['wcj_fields_test']['registrations'][0];
		wcj_fields_test_expect( $type . '-native-type', $field['type'], 'select' );
		wcj_fields_test_expect(
			$type . '-options',
			$field['options'],
			array(
				array(
					'label' => 'Red',
					'value' => 'red',
				),
				array(
					'label' => 'Blue',
					'value' => 'blue',
				),
			)
		);
		wcj_fields_test_expect( $type . '-valid-option', call_user_func( $field['validate_callback'], 'red' ), null );
		wcj_fields_test_expect( $type . '-invalid-option', call_user_func( $field['validate_callback'], 'unknown' )->code, 'wcj_invalid_checkout_field' );
	}
}
$adapter = wcj_fields_test_adapter();
wcj_fields_test_expect( 'sanitize-boolean-unchanged', $adapter->sanitize_field_value( true ), true );
wcj_fields_test_expect( 'sanitize-string-existing-boundary', $adapter->sanitize_field_value( ' <b>Hello</b> ' ), 'Hello' );
$order = new WC_Order();
$adapter->bridge_additional_field_value( 'other/field', 'ignored', 'other', $order );
$adapter->bridge_additional_field_value( 'booster-wcj/checkout-field-1', 'ignored', 'billing', $order );
$adapter->bridge_additional_field_value( 'booster-wcj/checkout-field-1', 'ignored', 'other', new stdClass() );
wcj_fields_test_expect( 'bridge-unrelated-key-group-object-no-mutation', $order->meta, array() );
$adapter->bridge_additional_field_value( 'booster-wcj/checkout-field-1', 'value', 'other', $order );
wcj_fields_test_expect(
	'legacy-text-meta-shape',
	$order->meta,
	array(
		'_billing_wcj_checkout_field_1'       => 'value',
		'_billing_wcj_checkout_field_label_1' => 'Synthetic field',
		'_billing_wcj_checkout_field_type_1'  => 'text',
	)
);
foreach ( array( 'select', 'radio' ) as $type ) {
	$adapter = wcj_fields_test_adapter(
		array(
			'type'           => $type,
			'section'        => 'shipping',
			'select_options' => "Red\nBlue",
		)
	);
	$order   = new WC_Order();
	$adapter->bridge_additional_field_value( 'booster-wcj/checkout-field-1', 'blue', 'other', $order );
	wcj_fields_test_expect(
		'legacy-' . $type . '-meta-shape',
		$order->meta,
		array(
			'_shipping_wcj_checkout_field_1'       => 'Blue',
			'_shipping_wcj_checkout_field_label_1' => 'Synthetic field',
			'_shipping_wcj_checkout_field_type_1'  => $type,
			'_shipping_wcj_checkout_field_select_options_1' => "Red\nBlue",
		)
	);
}
foreach ( array( false, 'false', '0', true, '1' ) as $i => $value ) {
	$adapter = wcj_fields_test_adapter(
		array(
			'type'         => 'checkbox',
			'checkbox_yes' => 'Yes',
			'checkbox_no'  => 'No',
		)
	);
	$order   = new WC_Order();
	$adapter->bridge_additional_field_value( 'booster-wcj/checkout-field-1', $value, 'other', $order );
	$truthy = $i >= 3;
	wcj_fields_test_expect(
		'legacy-checkbox-' . $i,
		$order->meta,
		array(
			'_billing_wcj_checkout_field_1'                => $truthy ? 1 : 0,
			'_billing_wcj_checkout_field_label_1'          => 'Synthetic field',
			'_billing_wcj_checkout_field_type_1'           => 'checkbox',
			'_billing_wcj_checkout_field_checkbox_value_1' => $truthy ? 'Yes' : 'No',
		)
	);
}
$fails = array_values(
	array_filter(
		$results,
		static function ( $row ) {
			return ! $row['pass'];
		}
	)
);
echo json_encode(
	array(
		'kind'           => 'actual_adapter_source_synthetic_context_not_native_runtime',
		'source_sha256'  => hash_file( 'sha256', $source ),
		'fixture_sha256' => hash_file( 'sha256', __FILE__ ),
		'php'            => PHP_VERSION,
		'mode'           => $mode,
		'wc_version'     => defined( 'WC_VERSION' ) ? WC_VERSION : null,
		'pass'           => count( $results ) - count( $fails ),
		'fail'           => count( $fails ),
		'assertions'     => $results,
	),
	JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
), "\n";
exit( empty( $fails ) ? 0 : 1 );
