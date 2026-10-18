<?php
/**
 * Deterministic actual-class Returns regression tests. No WordPress bootstrap,
 * real database, HTTP, mail transport or native concurrency is exercised.
 *
 * Usage: php -n tests/codex-9-0-returns.php [absolute-plugin-folder]
 *
 * @package Booster_For_WooCommerce
 */

define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'DAY_IN_SECONDS', 86400 );
if ( extension_loaded( 'mysqli' ) ) {
	fwrite( STDERR, "Run this OFFLINE fixture with php -n. Its adapter doubles must never use native mysqli.\n" );
	exit( 2 );
}
define( 'MYSQLI_ASSOC', 1 );

/** Offline API double only; no native connection exists. */
class mysqli {
	/**
	 * Connection identity.
	 *
	 * @var int Connection identity.
	 */
	public $identity = 55;
	/**
	 * Query receipt.
	 *
	 * @var array Query receipt.
	 */
	public $queries = array();
	/**
	 * Single query outcome.
	 *
	 * @var mixed Single query outcome.
	 */
	public $result = true;
	/**
	 * Throw after recording one attempt.
	 *
	 * @var bool Throw after recording one attempt.
	 */
	public $throws = false;
	/**
	 * Mutation after one attempt.
	 *
	 * @var callable|null Mutation after one attempt.
	 */
	public $after;
}
/** Offline buffered result double. */
class mysqli_result {
	/**
	 * Fixed rows.
	 *
	 * @var array Fixed rows.
	 */
	public $rows = array();
	/**
	 * Freed result.
	 *
	 * @var bool Freed result.
	 */
	public $freed = false;
}
/**
 * Offline API identity.
 *
 * @return int Offline API identity.
 * @param mixed $handle Handle.
 */
function mysqli_thread_id( $handle ) {
	return $handle->identity; }
/**
 * Exactly one recorded attempt, never a network action.
 *
 * @return mixed Exactly one recorded attempt, never a network action.
 * @param mixed $handle Handle.
 * @param mixed $sql Sql.
 */
function mysqli_query( $handle, $sql ) {
	$handle->queries[] = $sql;
	if ( $handle->after ) {
		call_user_func( $handle->after ); }
	if ( $handle->throws ) {
		throw new RuntimeException( 'synthetic_connection_lost' ); }
	return $handle->result;
}
/**
 * Fixed affected rows.
 *
 * @return int Fixed affected rows.
 * @param mixed $handle Handle.
 */
function mysqli_affected_rows( $handle ) {
	return 1; }
/**
 * Fixed inserted identity.
 *
 * @return int Fixed inserted identity.
 * @param mixed $handle Handle.
 */
function mysqli_insert_id( $handle ) {
	return 17; }
/**
 * Offline buffered rows.
 *
 * @return array Offline buffered rows.
 * @param mixed $result Result.
 * @param mixed $format Format.
 */
function mysqli_fetch_all( $result, $format ) {
	return $result->rows; }
/**
 * Free an offline buffered result.
 *
 * @param mixed $result Result.
 */
function mysqli_free_result( $result ) {
	$result->freed = true; }

/** Finite error double. */
class WP_Error {
	/**
	 * Error code.
	 *
	 * @var string Error code.
	 */
	private $code;
	/**
	 * Error message.
	 *
	 * @var string Error message.
	 */
	private $message;
	/**
	 * Construct an error.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 */
	public function __construct( $code, $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
	/**
	 * Code.
	 *
	 * @return string Code.
	 */
	public function get_error_code() {
		return $this->code; }
	/**
	 * Message.
	 *
	 * @return string Message.
	 */
	public function get_error_message() {
		return $this->message; }
}

/**

 * Current synthetic actor.
 *
 * @return int Current synthetic actor.
 */
function get_current_user_id() {
	wcj_returns_test_tx_hook_guard();
	return $GLOBALS['wcj_returns_test_actor']; }
/**
 * Current synthetic capability.
 *
 * @return bool Current synthetic capability.
 * @param mixed $capability Capability.
 */
function current_user_can( $capability ) {
	wcj_returns_test_tx_hook_guard();
	return 'manage_woocommerce' === $capability && $GLOBALS['wcj_returns_test_capable']; }
/**
 * Stored synthetic option or explicit default.
 *
 * @return mixed Stored synthetic option or explicit default.
 * @param mixed $key Key.
 * @param mixed $default Default.
 */
function get_option( $key, $default = false ) {
	wcj_returns_test_tx_hook_guard();
	if ( $key === ( $GLOBALS['wcj_returns_test_option_fault'] ?? '' ) ) {
		throw new RuntimeException( 'synthetic_option_fault' );
	}
	if ( 'wcj_returns_enabled' === $key && ! empty( $GLOBALS['wcj_returns_test_handler_primitives'] ) ) {
		// Fixture-only injection of existing constructor primitives. The complete
		// controller and service methods execute unchanged; no native DB exists.
		foreach ( debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 4 ) as $frame ) {
			if ( isset( $frame['object'] ) && $frame['object'] instanceof WCJ_Returns_Service ) {
				foreach ( $GLOBALS['wcj_returns_test_handler_primitives'] as $property => $value ) {
					$slot = new ReflectionProperty( WCJ_Returns_Service::class, $property );
					$slot->setValue( $frame['object'], $value );
				}
				break;
			}
		}
	}
	return array_key_exists( $key, $GLOBALS['wcj_returns_test_options'] ) ? $GLOBALS['wcj_returns_test_options'][ $key ] : $default; }
/**
 * Translation passthrough.
 *
 * @return string Translation passthrough.
 * @param mixed $message Message.
 * @param mixed $domain Domain.
 */
function __( $message, $domain ) {
	wcj_returns_test_tx_hook_guard();
	return $message; }
/**
 * Error predicate.
 *
 * @return bool Error predicate.
 * @param mixed $value Value.
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error; }
/**
 * JSON encoding.
 *
 * @return string|false JSON encoding.
 * @param mixed $value Value.
 */
function wp_json_encode( $value ) {
	return json_encode( $value ); }
/**
 * Sanitized plain text.
 *
 * @return string Sanitized plain text.
 * @param mixed $value Value.
 */
function sanitize_textarea_field( $value ) {
	return trim( strip_tags( $value ) ); }
/**
 * User/action-bound synthetic nonce ticks.
 *
 * @return int|false User/action-bound synthetic nonce ticks.
 * @param mixed $nonce Nonce.
 * @param mixed $action Action.
 */
function wp_verify_nonce( $nonce, $action ) {
	wcj_returns_test_tx_hook_guard();
	foreach ( array( 1, 2 ) as $tick ) {
		if ( 'tick' . $tick . ':' . $action . ':' . get_current_user_id() === $nonce ) {
			return $tick;
		}
	}
	return false;
}
/**
 * Synthetic registered user, not customer data.
 *
 * @return object|false Synthetic registered user, not customer data.
 * @param mixed $id Id.
 */
function get_userdata( $id ) {
	wcj_returns_test_tx_hook_guard();
	return $GLOBALS['wcj_returns_test_users'][ $id ] ?? false; }
/**
 * Offline email syntax check.
 *
 * @return string|false Offline email syntax check.
 * @param mixed $value Value.
 */
function is_email( $value ) {
	return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
/**
 * Escaped synthetic output.
 *
 * @return string Escaped synthetic output.
 * @param mixed $value Value.
 */
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
/**
 * Escaped attribute.
 *
 * @return string Escaped attribute.
 * @param mixed $value Value.
 */
function esc_attr( $value ) {
	return esc_html( $value ); }
/**
 * Escaped URL.
 *
 * @return string Escaped URL.
 * @param mixed $value Value.
 */
function esc_url( $value ) {
	return esc_html( $value ); }
/**
 * Escaped translated text.
 *
 * @return string Escaped translated text.
 * @param mixed $value Value.
 * @param mixed $domain Domain.
 */
function esc_html__( $value, $domain ) {
	return esc_html( $value ); }
/**
 * Cache mode under test.
 *
 * @return bool Cache mode under test.
 */
function wp_using_ext_object_cache() {
	return $GLOBALS['wcj_returns_test_external_cache']; }
/**
 * Scoped cache invalidation.
 *
 * @return bool Scoped cache invalidation.
 * @param mixed $key Key.
 * @param mixed $group Group.
 */
function wp_cache_delete( $key, $group ) {
	$GLOBALS['wcj_returns_test_cache_deletes'][] = array( $key, $group );
	unset( $GLOBALS['wcj_returns_test_cache'][ $group ][ $key ] );
	return true;
}
/**
 * Bounded synthetic refund query.
 *
 * @return array Bounded synthetic refund query.
 * @param mixed $arguments Arguments.
 */
function wc_get_orders( $arguments ) {
	$GLOBALS['wcj_returns_test_refund_query'] = $arguments;
	return $GLOBALS['wcj_returns_test_refund_ids'];
}
/**
 * Logged-in predicate.
 *
 * @return bool Logged-in predicate.
 */
function is_user_logged_in() {
	return get_current_user_id() > 0; }
/**
 * Recursive ordinary WordPress unslash double, without a WordPress bootstrap.
 *
 * @param mixed $value Slashed scalar or array.
 * @return mixed Unslashed input.
 */
function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
}
/** @return bool Synthetic actual-account-page predicate. */
function is_account_page() {
	return $GLOBALS['wcj_returns_test_account_page'];
}
/**
 * Synthetic endpoint, independent of account-page context.
 *
 * @param string $endpoint Endpoint name.
 * @return bool Endpoint match.
 */
function is_wc_endpoint_url( $endpoint ) {
	return $endpoint === $GLOBALS['wcj_returns_test_endpoint'];
}
/**
 * Capture a real controller redirect before its following exit statement.
 *
 * @param string $url Server-built destination.
 */
function wp_safe_redirect( $url ) {
	$GLOBALS['wcj_returns_test_redirect'] = $url;
	throw new RuntimeException( 'wcj_returns_test_redirect_stop' );
}
/**
 * Fixed offline URL.
 *
 * @return string Fixed offline URL.
 * @param mixed $path Path.
 */
function admin_url( $path ) {
	return 'https://store.invalid/wp-admin/' . $path; }
/**
 * Fixed offline URL.
 *
 * @return string Fixed offline URL.
 * @param mixed $endpoint Endpoint.
 */
function wc_get_account_endpoint_url( $endpoint ) {
	return 'https://store.invalid/account/' . $endpoint; }
/**
 * Fixed offline URL.
 *
 * @return string Fixed offline URL.
 * @param mixed $page Page.
 */
function wc_get_page_permalink( $page ) {
	return 'https://store.invalid/account/'; }
/**
 * Fixed offline URL.
 *
 * @return string Fixed offline URL.
 * @param mixed $endpoint Endpoint.
 * @param mixed $value Value.
 * @param mixed $base Base.
 */
function wc_get_endpoint_url( $endpoint, $value, $base ) {
	return $base . $endpoint . '/' . $value; }
/**
 * Fixed query builder.
 *
 * @return string Fixed query builder.
 * @param mixed $key Key.
 * @param mixed $value Value.
 * @param mixed $url Url.
 */
function add_query_arg( $key, $value, $url ) {
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . rawurlencode( $key ) . '=' . rawurlencode( (string) $value ); }
/**
 * Render an offline user/action-bound field.
 *
 * @param mixed $action Action.
 */
function wp_nonce_field( $action ) {
	echo '<input name="_wpnonce" value="' . esc_attr( 'tick1:' . $action . ':' . get_current_user_id() ) . '">'; }
/**
 * Record module hooks without executing WordPress.
 *
 * @param mixed $hook Hook.
 * @param mixed $callback Callback.
 */
function add_action( $hook, $callback ) {
	$GLOBALS['wcj_returns_test_hooks'][ $hook ] = $callback; }
/**
 * Record the existing native filter contract for bounded bootstrap proof.
 *
 * @param string   $hook Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Existing ordering.
 * @param int      $accepted_args Accepted arguments.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wcj_returns_test_filters'][ $hook ][ $priority ][] = array( $callback, $accepted_args );
}
/**
 * Apply the actual included Mini Plugin closure on synthetic configuration.
 *
 * @param string $hook Hook name.
 * @param mixed  $value Filter value.
 * @param mixed  ...$arguments Additional native hook arguments.
 * @return mixed Filter result.
 */
function apply_filters( $hook, $value, ...$arguments ) {
	$priorities = $GLOBALS['wcj_returns_test_filters'][ $hook ] ?? array();
	ksort( $priorities );
	foreach ( $priorities as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$value = call_user_func_array( $callback[0], array_slice( array_merge( array( $value ), $arguments ), 0, $callback[1] ) ); }
	}
	return $value;
}
/**
 * Synthetic stored module flag used by the actual legacy filter.
 *
 * @param string $id Existing module ID.
 * @return bool Enabled flag.
 */
function wcj_is_module_enabled( $id ) {
	return 'yes' === get_option( 'wcj_' . $id . '_enabled', 'no' ); }
/**
 * Existing getter interface, using synthetic options only.
 *
 * @param string $key Option key.
 * @param mixed  $default Default.
 * @return mixed Synthetic stored option.
 */
function wcj_get_option( $key, $default = false ) {
	return get_option( $key, $default ); }
/** @return object Synthetic role context for actual Mini Plugin closure. */
function wp_get_current_user() {
	return (object) array( 'roles' => array( 'customer' ) ); }
/**
 * Record menu policy.
 *
 * @param mixed $parent Parent.
 * @param mixed $title Title.
 * @param mixed $label Label.
 * @param mixed $capability Capability.
 * @param mixed $slug Slug.
 * @param mixed $callback Callback.
 */
function add_submenu_page( $parent, $title, $label, $capability, $slug, $callback ) {
	$GLOBALS['wcj_returns_test_menu_capability'] = $capability; }
/**
 * Reject denied UI paths visibly.
 *
 * @param mixed $message Message.
 */
function wp_die( $message ) {
	throw new RuntimeException( 'wp_die' ); }

/** Woo cache feature double, aliased to its actual qualified name. */
class WCJ_Returns_Test_Order_Util {
	/**
	 * Synthetic HPOS datastore-cache flag.
	 *
	 * @return bool Synthetic HPOS datastore-cache flag.
	 */
	public static function custom_orders_table_datastore_cache_enabled() {
		return $GLOBALS['wcj_returns_test_hpos_cache']; }

	/**
	 * Persisted type, distinct from a constructed object's class identity.
	 *
	 * @param int $id Record ID.
	 * @return string|null Persisted type.
	 */
	public static function get_order_type( $id ) {
		if ( ! isset( $GLOBALS['wcj_returns_test_orders'][ $id ] ) ) {
			return null;
		}
		$row = $GLOBALS['wcj_returns_test_orders'][ $id ];
		return $row['type'] ?? ( isset( $row['parent'] ) ? 'shop_order_refund' : 'shop_order' );
	}
}
class_alias( 'WCJ_Returns_Test_Order_Util', 'Automattic\WooCommerce\Utilities\OrderUtil' );

/** Complete minimal read interface of an actual order consumed by the facts class. */
class WC_Order {
	/**
	 * Synthetic ID.
	 *
	 * @var int Synthetic ID.
	 */
	protected $id;
	/**
	 * Persisted synthetic properties captured at construction.
	 *
	 * @var array Persisted synthetic properties captured at construction.
	 */
	protected $data;
	/**
	 * Synthetic order ID.
	 *
	 * @param int $id Synthetic order ID.
	 */
	public function __construct( $id ) {
		$this->id   = $id;
		$this->data = $GLOBALS['wcj_returns_test_cache']['posts'][ $id ] ?? $GLOBALS['wcj_returns_test_orders'][ $id ];
	}
	/**
	 * Order ID.
	 *
	 * @return int Order ID.
	 */
	public function get_id() {
		return $this->id; }
	/**
	 * Order type.
	 *
	 * @return string Order type.
	 */
	public function get_type() {
		return 'shop_order'; }
	/**
	 * Current owner.
	 *
	 * @return int Current owner.
	 * @param mixed $context Context.
	 */
	public function get_customer_id( $context ) {
		return $this->data['owner']; }
	/**
	 * Current status.
	 *
	 * @return string Current status.
	 * @param mixed $context Context.
	 */
	public function get_status( $context ) {
		return $this->data['status']; }
	/**
	 * Current completion timestamp.
	 *
	 * @return DateTime|null Current completion timestamp.
	 * @param mixed $context Context.
	 */
	public function get_date_completed( $context ) {
		return $this->data['completed'] ? new DateTime( '@' . $this->data['completed'] ) : null; }
	/**
	 * Item identity list, deliberately not fresh item properties.
	 *
	 * @return array Item identity list, deliberately not fresh item properties.
	 * @param mixed $type Type.
	 */
	public function get_items( $type ) {
		$out = array();
		foreach ( $this->data['items'] as $id ) {
			$out[ (string) $id ] = new WCJ_Returns_Test_Item_Identity( $GLOBALS['wcj_returns_test_items'][ $id ]['type'] ?? 'line_item' ); }
		return $out;
	}
}
/** Synthetic identity list never loads quantity properties. */
class WCJ_Returns_Test_Item_Identity {
	/**
	 * Persisted type.
	 *
	 * @var string Persisted type.
	 */
	private $type;
	/**
	 * Persisted type.
	 *
	 * @param string $type Persisted type.
	 */
	public function __construct( $type ) {
		$this->type = $type; }
	/**
	 * Type.
	 *
	 * @return string Type.
	 */
	public function get_type() {
		return $this->type; }
}
/** Synthetic refund with actual read interface. */
class WC_Order_Refund extends WC_Order {
	/**
	 * Refund type.
	 *
	 * @return string Refund type.
	 */
	public function get_type() {
		return 'shop_order_refund'; }
	/**
	 * Parent ID.
	 *
	 * @return int Parent ID.
	 * @param mixed $context Context.
	 */
	public function get_parent_id( $context ) {
		return $this->data['parent']; }
	/**
	 * Refund amount.
	 *
	 * @return string Refund amount.
	 * @param mixed $context Context.
	 */
	public function get_amount( $context ) {
		return $this->data['amount']; }
}
/** Synthetic item constructor models separately cached native quantity properties. */
class WC_Order_Item_Product {
	/**
	 * Item ID.
	 *
	 * @var int Item ID.
	 */
	private $id;
	/**
	 * Item properties.
	 *
	 * @var array Item properties.
	 */
	private $data;
	/**
	 * Synthetic item ID.
	 *
	 * @param int $id Synthetic item ID.
	 */
	public function __construct( $id ) {
		$this->id   = $id;
		$this->data = $GLOBALS['wcj_returns_test_cache']['order_item_meta'][ $id ] ?? $GLOBALS['wcj_returns_test_items'][ $id ];
	}
	/**
	 * Item ID.
	 *
	 * @return int Item ID.
	 */
	public function get_id() {
		return $this->id; }
	/**
	 * Parent ID.
	 *
	 * @return int Parent ID.
	 */
	public function get_order_id() {
		return $this->data['order']; }
	/**
	 * Persisted native type.
	 *
	 * @return string Persisted native type.
	 */
	public function get_type() {
		return $this->data['type'] ?? 'line_item'; }
	/**
	 * Quantity property is NOT changed by forced metadata read.
	 *
	 * @return mixed Quantity property is NOT changed by forced metadata read.
	 * @param mixed $context Context.
	 */
	public function get_quantity( $context ) {
		return $this->data['quantity']; }
	/**
	 * Item name.
	 *
	 * @return string Item name.
	 * @param mixed $context Context.
	 */
	public function get_name( $context ) {
		return $this->data['name']; }
	/**
	 * Product ID.
	 *
	 * @return int Product ID.
	 * @param mixed $context Context.
	 */
	public function get_product_id( $context ) {
		return $this->data['product']; }
	/**
	 * Variation ID.
	 *
	 * @return int Variation ID.
	 * @param mixed $context Context.
	 */
	public function get_variation_id( $context ) {
		return 0; }
	/**
	 * Subtotal.
	 *
	 * @return string Subtotal.
	 * @param mixed $context Context.
	 */
	public function get_subtotal( $context ) {
		return '5.00'; }
	/**
	 * Total.
	 *
	 * @return string Total.
	 * @param mixed $context Context.
	 */
	public function get_total( $context ) {
		return $this->data['total'] ?? '5.00'; }
	/**
	 * Taxes.
	 *
	 * @return array Taxes.
	 * @param mixed $context Context.
	 */
	public function get_taxes( $context ) {
		return $this->data['taxes'] ?? array(); }
	/**
	 * Refunded item reference.
	 *
	 * @return int Refunded item reference.
	 * @param mixed $key Key.
	 * @param mixed $single Single.
	 * @param mixed $context Context.
	 */
	public function get_meta( $key, $single, $context ) {
		return $this->data['refunded'] ?? 0; }
	/**
	 * Record raw-meta refresh, deliberately without refreshing properties.
	 *
	 * @param mixed $force Force.
	 */
	public function read_meta_data( $force ) {
		$GLOBALS['wcj_returns_test_meta_forced'][] = array( $this->id, $force ); }
}

/** Native shipping read interface consumed only for classification. */
class WC_Order_Item_Shipping extends WC_Order_Item_Product {}
/** Native fee read interface consumed only for classification. */
class WC_Order_Item_Fee extends WC_Order_Item_Product {}

/** Existing Booster base class interface, not a second module implementation. */
class WCJ_Module {
	/**
	 * Module ID.
	 *
	 * @var string Module ID.
	 */
	public $id;
	/**
	 * Short description.
	 *
	 * @var string Short description.
	 */
	public $short_desc;
	/**
	 * Description.
	 *
	 * @var string Description.
	 */
	public $desc;
	/**
	 * Description.
	 *
	 * @var string Description.
	 */
	public $desc_pro;
	/** Existing parent constructor is a noop in this offline fixture. */
	public function __construct() {}
	/**
	 * Current module setting.
	 *
	 * @return bool Current module setting.
	 */
	public function is_enabled() {
		return 'yes' === get_option( 'wcj_returns_enabled', 'no' ); }
}

$wcj_returns_test_root = $argv[1] ?? dirname( __DIR__ );
require_once $wcj_returns_test_root . '/includes/returns/class-wcj-returns-facts.php';
require_once $wcj_returns_test_root . '/includes/returns/class-wcj-returns-store.php';
require_once $wcj_returns_test_root . '/includes/returns/class-wcj-returns-service.php';

/** Memory storage runs the ACTUAL service without claiming native SQL isolation. */
class WCJ_Returns_Test_Store extends WCJ_Returns_Store {
	/**
	 * Private request rows.
	 *
	 * @var array Private request rows.
	 */
	public $rows = array();
	/**
	 * Exact reservation rows.
	 *
	 * @var array Exact reservation rows.
	 */
	public $claims = array();
	/**
	 * Lock trace.
	 *
	 * @var array Lock trace.
	 */
	public $locks = array();
	/**
	 * Write attempts.
	 *
	 * @var int Write attempts.
	 */
	public $writes = 0;
	/**
	 * Owned transaction state.
	 *
	 * @var bool Owned transaction state.
	 */
	public $active = false;
	/**
	 * Inject a commit-unknown state, without replay.
	 *
	 * @var bool Inject a commit-unknown state, without replay.
	 */
	public $unknown = false;
	/**
	 * First lock callback for race tests.
	 *
	 * @var callable|null First lock callback for race tests.
	 */
	public $on_lock;
	/** No external connection is created. */
	public function __construct() {}
	/** Memory schema deliberately separate from native schema tests below. */
	public function require_ready() {}
	/**
	 * Actual service operation with snapshot rollback.
	 *
	 * @return mixed Actual service operation with snapshot rollback.
	 * @param mixed $operation Operation.
	 * @param mixed $started Started.
	 */
	public function transaction( $operation, $started = null, $expiry = null ) {
		if ( null !== $started && hrtime( true ) - $started > 5000000000 ) {
			throw new RuntimeException( 'facts_stale' ); }
		if ( $this->active ) {
			throw new RuntimeException( 'transaction_unavailable' ); }
		$snapshot     = array( $this->rows, $this->claims );
		$this->active = true;
		try {
			$result = $operation();
		} catch ( Throwable $error ) {
			list( $this->rows, $this->claims ) = $snapshot;
			$this->active                      = false;
			throw $error;
		}
		$this->active = false;
		if ( $this->unknown ) {
			throw new RuntimeException( 'commit_unknown' ); }
		return $result;
	}
	/**
	 * Locked synthetic reservation.
	 *
	 * @return array Locked synthetic reservation.
	 * @param mixed $order_id Order id.
	 * @param mixed $item_id Item id.
	 * @param mixed $hash Hash.
	 */
	public function lock_claim( $order_id, $item_id, $hash ) {
		if ( ! $this->active ) {
			throw new RuntimeException( 'transaction_required' ); }
		$this->locks[] = array( $order_id, $item_id );
		$key           = $order_id . ':' . $item_id;
		if ( ! isset( $this->claims[ $key ] ) ) {
			++$this->writes;
			$this->claims[ $key ] = array(
				'order_id'     => $order_id,
				'item_id'      => $item_id,
				'reserved'     => 0,
				'facts_hash'   => $hash,
				'needs_review' => 0,
			);
		}
		if ( $this->on_lock ) {
			$callback      = $this->on_lock;
			$this->on_lock = null;
			$callback();
		}
		return $this->claims[ $key ];
	}
	/**
	 * Replace an already locked synthetic claim.
	 *
	 * @param mixed $order_id Order id.
	 * @param mixed $item_id Item id.
	 * @param mixed $reserved Reserved.
	 * @param mixed $review Review.
	 */
	public function claim( $order_id, $item_id, $reserved, $review = 0 ) {
		if ( ! $this->active || $reserved < 0 ) {
			throw new RuntimeException( 'claim_invalid' ); }
		++$this->writes;
		$key                                  = $order_id . ':' . $item_id;
		$this->claims[ $key ]['reserved']     = $reserved;
		$this->claims[ $key ]['needs_review'] = $review;
	}
	/**
	 * Read without writes.
	 *
	 * @return array|null Read without writes.
	 * @param mixed $order_id Order id.
	 * @param mixed $item_id Item id.
	 */
	public function read_claim( $order_id, $item_id ) {
		return $this->claims[ $order_id . ':' . $item_id ] ?? null; }
	/**
	 * Exact scoped replay.
	 *
	 * @return array|null Exact scoped replay.
	 * @param mixed $order_id Order id.
	 * @param mixed $actor_id Actor id.
	 * @param mixed $token Token.
	 */
	public function replay( $order_id, $actor_id, $token ) {
		foreach ( $this->rows as $row ) {
			if ( $row['order_id'] === $order_id && $row['actor_id'] === $actor_id && $row['idempotency'] === $token ) {
				return $row; }
		}
		return null;
	}
	/**
	 * Private row.
	 *
	 * @return array Private row.
	 * @param mixed $id Id.
	 * @param mixed $lock Lock.
	 */
	public function get( $id, $lock = false ) {
		if ( ! isset( $this->rows[ $id ] ) ) {
			throw new RuntimeException( 'request_unavailable' ); }
		return $this->rows[ $id ];
	}
	/**
	 * Inserted private row, checked by ACTUAL bounded decoder.
	 *
	 * @return array Inserted private row, checked by ACTUAL bounded decoder.
	 * @param mixed $row Row.
	 */
	public function insert( $row ) {
		if ( ! $this->active ) {
			throw new RuntimeException( 'transaction_required' ); }
		$row['id']      = count( $this->rows ) + 1;
		$row['payload'] = wp_json_encode( $row['payload'] );
		$this->payload( $row );
		++$this->writes;
		$this->rows[ $row['id'] ] = $row;
		return $row;
	}
	/**
	 * Exact revision compare-and-set.
	 *
	 * @return array Exact revision compare-and-set.
	 * @param mixed $row Row.
	 * @param mixed $payload Payload.
	 * @param mixed $status Status.
	 * @param mixed $now Now.
	 */
	public function save( $row, $payload, $status, $now ) {
		if ( ! $this->active || $this->rows[ $row['id'] ]['revision'] !== $row['revision'] ) {
			throw new RuntimeException( 'revision_conflict' ); }
		$row['payload'] = wp_json_encode( $payload );
		$this->payload( $row );
		$row['status'] = $status;
		++$row['revision'];
		$row['updated_gmt'] = gmdate( 'Y-m-d H:i:s', $now );
		++$this->writes;
		$this->rows[ $row['id'] ] = $row;
		return $row;
	}
	/**
	 * Private descending keyset page.
	 *
	 * @return array Private descending keyset page.
	 * @param mixed $actor Actor.
	 * @param mixed $order Order.
	 * @param mixed $before Before.
	 */
	public function listing( $actor = 0, $order = 0, $before = PHP_INT_MAX ) {
		$rows = array_filter(
			$this->rows,
			function ( $row ) use ( $actor, $order, $before ) {
				return $row['id'] < $before && ( ! $actor || $row['actor_id'] === $actor ) && ( ! $order || $row['order_id'] === $order );
			}
		);
		krsort( $rows );
		return array_slice( array_values( $rows ), 0, 50 );
	}
	/**
	 * Serialized order-level admission ceiling.
	 *
	 * @return bool Serialized order-level admission ceiling.
	 * @param mixed $order_id Order id.
	 */
	public function has_request_room( $order_id ) {
		if ( ! $this->active ) {
			throw new RuntimeException( 'transaction_required' ); }
		return $this->retained_count( $order_id ) < 100;
	}
	/**
	 * Bounded retained count for read-only UI.
	 *
	 * @return int Bounded retained count for read-only UI.
	 * @param mixed $order_id Order id.
	 */
	public function retained_count( $order_id ) {
		return min(
			101,
			count(
				array_filter(
					$this->rows,
					function ( $row ) use ( $order_id ) {
						return $row['order_id'] === $order_id;
					}
				)
			)
		);
	}
}

/** Injectable facts used to isolate the actual service's decisions. */
class WCJ_Returns_Test_Facts extends WCJ_Returns_Facts {
	/**
	 * Current synthetic snapshot.
	 *
	 * @var array Current synthetic snapshot.
	 */
	public $value;
	/**
	 * Fresh load count.
	 *
	 * @var int Fresh load count.
	 */
	public $loads = 0;
	/** @var string Explicit synthetic facts failure. */
	public $failure = '';
	/**
	 * Current snapshot at every actual load.
	 *
	 * @return array Current snapshot at every actual load.
	 * @param mixed $id Id.
	 */
	public function load( $id ) {
		wcj_returns_test_tx_hook_guard();
		++$this->loads;
		if ( '' !== $this->failure ) {
			throw new RuntimeException( $this->failure );
		}
		if ( 10 !== $id ) {
			throw new RuntimeException( 'order_unavailable' ); }
		return $this->value;
	}
}

/** Synthetic mailer, never sends a network message. */
class WCJ_Returns_Test_Mailer {
	/**
	 * Configured transport outcome.
	 *
	 * @return mixed Configured transport outcome.
	 * @param mixed $email Email.
	 * @param mixed $subject Subject.
	 * @param mixed $body Body.
	 */
	public function send( $email, $subject, $body ) {
		if ( $GLOBALS['wcj_returns_test_test_store']->active ) {
			throw new RuntimeException( 'mail_inside_transaction' ); }
		$GLOBALS['wcj_returns_test_messages'][] = array( $email, $subject, $body );
		if ( $GLOBALS['wcj_returns_test_mail_callback'] ) {
			call_user_func( $GLOBALS['wcj_returns_test_mail_callback'] ); }
		if ( $GLOBALS['wcj_returns_test_mail_throw'] ) {
			throw new RuntimeException( 'synthetic_after_attempt' ); }
		return $GLOBALS['wcj_returns_test_mail_result'];
	}
}
/** Synthetic Woo application. */
class WCJ_Returns_Test_Woo {
	/**
	 * Offline mailer.
	 *
	 * @return WCJ_Returns_Test_Mailer Offline mailer.
	 */
	public function mailer() {
		return new WCJ_Returns_Test_Mailer(); }
}
/**
 * Offline application.
 *
 * @return WCJ_Returns_Test_Woo Offline application.
 */
function WC() {
	return new WCJ_Returns_Test_Woo(); }

/** Fixed-schema SQL protocol double, not a native database. */
class WCJ_Returns_Test_DB {
	/**
	 * Safe table prefix.
	 *
	 * @var string Safe table prefix.
	 */
	public $prefix = 'wp_';
	/**
	 * Last error.
	 *
	 * @var string Last error.
	 */
	public $last_error = '';
	/**
	 * SQL command trace.
	 *
	 * @var array SQL command trace.
	 */
	public $sql = array();
	/**
	 * Native-shaped schema rows.
	 *
	 * @var array Native-shaped schema rows.
	 */
	public $columns = array();
	/**
	 * Native-shaped index rows.
	 *
	 * @var array Native-shaped index rows.
	 */
	public $indexes = array();
	/**
	 * Engine.
	 *
	 * @var string Engine.
	 */
	public $engine = 'InnoDB';
	/**
	 * Simulated ambient transaction.
	 *
	 * @var bool Simulated ambient transaction.
	 */
	public $active = false;
	/**
	 * Next-transaction isolation pending.
	 *
	 * @var bool Next-transaction isolation pending.
	 */
	public $next_isolation = false;
	/**
	 * Fixed command prefix that fails.
	 *
	 * @var string Fixed command prefix that fails.
	 */
	public $fail = '';
	/**
	 * Session autocommit admission.
	 *
	 * @var bool Session autocommit admission.
	 */
	public $autocommit = true;
	/**
	 * All direct-read trace.
	 *
	 * @var array All direct-read trace.
	 */
	public $read_sql = array();
	/**
	 * Private-query completion callback.
	 *
	 * @var callable|null Private-query completion callback.
	 */
	public $after_query;
	/** @var array Existing fixed tables in the offline protocol. */
	public $present = array(
		'wp_wcj_return_requests' => true,
		'wp_wcj_return_claims'   => true,
	);
	/** @var array Fixed bounded row counts, not real private data. */
	public $data_count = array(
		'wp_wcj_return_requests' => 0,
		'wp_wcj_return_claims'   => 0,
	);
	/** Construct exact fixed schema. */
	public function __construct() {
		$schemas = array(
			'wp_wcj_return_requests' => array(
				'id'          => 'bigint(20) unsigned',
				'order_id'    => 'bigint(20) unsigned',
				'actor_id'    => 'bigint(20) unsigned',
				'idempotency' => 'char(64)',
				'input_hash'  => 'char(64)',
				'status'      => 'varchar(16)',
				'revision'    => 'bigint(20) unsigned',
				'created_gmt' => 'datetime',
				'updated_gmt' => 'datetime',
				'payload'     => 'longtext',
			),
			'wp_wcj_return_claims'   => array(
				'order_id'     => 'bigint unsigned',
				'item_id'      => 'bigint unsigned',
				'reserved'     => 'bigint unsigned',
				'facts_hash'   => 'char(64)',
				'needs_review' => 'tinyint(3) unsigned',
			),
		);
		foreach ( $schemas as $table => $fields ) {
			foreach ( $fields as $field => $type ) {
				$this->columns[ $table ][] = array(
					'Field' => $field,
					'Type'  => $type,
					'Null'  => 'NO',
					'Extra' => 'id' === $field ? 'auto_increment' : '',
				);
			}
		}
		$this->index( 'wp_wcj_return_requests', 'PRIMARY', array( 'id' ) );
		$this->index( 'wp_wcj_return_requests', 'request_token', array( 'order_id', 'actor_id', 'idempotency' ) );
		$this->index( 'wp_wcj_return_claims', 'PRIMARY', array( 'order_id', 'item_id' ) );
	}
	/**
	 * Add a native-shaped full unique index.
	 *
	 * @param mixed $table Table.
	 * @param mixed $name Name.
	 * @param mixed $fields Fields.
	 */
	private function index( $table, $name, $fields ) {
		foreach ( $fields as $position => $field ) {
			$this->indexes[ $table ][] = array(
				'Non_unique'   => 0,
				'Sub_part'     => null,
				'Key_name'     => $name,
				'Seq_in_index' => $position + 1,
				'Column_name'  => $field,
			);
		}
	}
	/**
	 * Prepared deterministic SQL.
	 *
	 * @return string Prepared deterministic SQL.
	 * @param mixed $sql Sql.
	 * @param mixed $values Values.
	 */
	public function prepare( $sql, ...$values ) {
		$position = 0;
		return preg_replace_callback(
			'/%[ds]/',
			function ( $match ) use ( $values, &$position ) {
				$value = $values[ $position++ ];
				return '%d' === $match[0] ? (string) (int) $value : "'" . str_replace( "'", "''", $value ) . "'";
			},
			$sql
		);
	}
	/**
	 * LIKE literal escaping.
	 *
	 * @return string LIKE literal escaping.
	 * @param mixed $value Value.
	 */
	public function esc_like( $value ) {
		return addcslashes( $value, '_%\\' ); }
	/**
	 * Fixed charset declaration.
	 *
	 * @return string Fixed charset declaration.
	 */
	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'; }
	/**
	 * Transaction admission protocol.
	 *
	 * @return int|false Transaction admission protocol.
	 * @param mixed $sql Sql.
	 */
	public function query( $sql ) {
		$this->sql[]      = $sql;
		$this->last_error = '';
		if ( ( '' !== $this->fail && 0 === strpos( $sql, $this->fail ) ) || ( 0 === strpos( $sql, 'SET TRANSACTION' ) && $this->active ) ) {
			$this->last_error = 'synthetic_failure';
			return false;
		}
		if ( 0 === strpos( $sql, 'SET TRANSACTION' ) ) {
			$this->next_isolation = true; }
		if ( 'START TRANSACTION' === $sql ) {
			$this->active         = true;
			$this->next_isolation = false; }
		if ( in_array( $sql, array( 'ROLLBACK', 'COMMIT' ), true ) ) {
			$this->active = false; }
		if ( 0 === strpos( $sql, 'CREATE TABLE' ) ) {
			$table                   = false !== strpos( $sql, 'requests' ) ? 'wp_wcj_return_requests' : 'wp_wcj_return_claims';
			$this->present[ $table ] = true;
		}
		if ( $this->after_query ) {
			call_user_func( $this->after_query, $sql ); }
		return 1;
	}
	/**
	 * Native-shaped table status.
	 *
	 * @return array Native-shaped table status.
	 * @param mixed $sql Sql.
	 * @param mixed $format Format.
	 */
	public function get_row( $sql, $format ) {
		$this->last_error = '';
		$table            = false !== strpos( $sql, 'requests' ) ? 'wp_wcj_return_requests' : 'wp_wcj_return_claims';
		return array(
			'Name'   => $table,
			'Engine' => $this->engine,
		);
	}
	/**
	 * Native-shaped columns or indexes.
	 *
	 * @return array Native-shaped columns or indexes.
	 * @param mixed $sql Sql.
	 * @param mixed $format Format.
	 */
	public function get_results( $sql, $format ) {
		$this->last_error = '';
		$table            = false !== strpos( $sql, 'requests' ) ? 'wp_wcj_return_requests' : 'wp_wcj_return_claims';
		return 0 === strpos( $sql, 'SHOW COLUMNS' ) ? $this->columns[ $table ] : $this->indexes[ $table ];
	}
}

/** Named stock-wpdb-shaped owner ONLY for the offline adapter API test. */
class wpdb extends WCJ_Returns_Test_DB {
	/**
	 * Offline handle, never native.
	 *
	 * @var mysqli Offline handle, never native.
	 */
	public $dbh;
	/**
	 * Placeholder escape removal count.
	 *
	 * @var int Placeholder escape removal count.
	 */
	public $removals = 0;
	/**
	 * Exact prepared placeholder removal.
	 *
	 * @return string Exact prepared placeholder removal.
	 * @param mixed $sql Sql.
	 */
	public function remove_placeholder_escape( $sql ) {
		++$this->removals;
		return str_replace( '{placeholder}', '%', $sql ); }
	/**
	 * Must never route any query through retrying wpdb in adapter tests.
	 *
	 * @param mixed $sql Sql.
	 */
	public function query( $sql ) {
		throw new RuntimeException( 'wpdb_query_must_not_run' ); }
}

/**
 * Bind offline API doubles to ACTUAL adapter methods, not constructor admission.
 * Native mysqlnd admission and killed-connection behavior remain native gates.
 *
 * @param wpdb $owner Offline owner.
 * @return WCJ_Returns_Connection Actual adapter with synthetic API dependencies.
 */
function wcj_returns_test_adapter_double( $owner ) {
	$adapter = ( new ReflectionClass( 'WCJ_Returns_Connection' ) )->newInstanceWithoutConstructor();
	foreach ( array(
		'db'     => $owner,
		'handle' => $owner->dbh,
		'thread' => $owner->dbh->identity,
	) as $name => $value ) {
		$property = new ReflectionProperty( 'WCJ_Returns_Connection', $name );
		$property->setAccessible( true );
		$property->setValue( $adapter, $value );
	}
	return $adapter;
}

/** Explicit offline direct-connection protocol double, never production admitted. */
class WCJ_Returns_Test_Connection extends WCJ_Returns_Connection {
	/**
	 * Offline schema/protocol state.
	 *
	 * @var WCJ_Returns_Test_DB Offline schema/protocol state.
	 */
	private $database;
	/** @var bool Simulate an original handle lost at COMMIT. */
	public $disconnect_on_commit = false;
	/** @var bool Original handle is no longer usable. */
	public $unusable = false;
	/** @var array Every direct adapter attempt, including failed cleanup. */
	public $attempts = array();
	/**
	 * Explicit offline dependency.
	 *
	 * @param WCJ_Returns_Test_DB $database Explicit offline dependency.
	 */
	public function __construct( $database ) {
		$this->database = $database; }
	/**
	 * Actual-store private command result, no retry.
	 *
	 * @return int Actual-store private command result, no retry.
	 * @param mixed $sql Sql.
	 */
	public function query( $sql ) {
		$this->attempts[] = $sql;
		if ( $this->unusable ) {
			throw new RuntimeException( 'storage_failure' );
		}
		$result = $this->database->query( $sql );
		if ( 'COMMIT' === $sql && $this->disconnect_on_commit ) {
			$this->unusable = true;
			throw new RuntimeException( 'storage_failure' );
		}
		if ( false === $result ) {
			throw new RuntimeException( 'storage_failure' ); }
		return $result;
	}
	/**
	 * Direct native-shaped results, not wpdb query routing.
	 *
	 * @return array Direct native-shaped results, not wpdb query routing.
	 * @param mixed $sql Sql.
	 */
	public function rows( $sql ) {
		$this->database->read_sql[] = $sql;
		$table                      = false !== strpos( $sql, 'requests' ) ? 'wp_wcj_return_requests' : 'wp_wcj_return_claims';
		if ( 'SELECT @@SESSION.autocommit AS autocommit' === $sql ) {
			return array( array( 'autocommit' => $this->database->autocommit ? '1' : '0' ) ); }
		if ( 0 === strpos( $sql, 'SHOW TABLE STATUS' ) ) {
			if ( ! $this->database->present[ $table ] ) {
				return array();
			}
			return array( $this->database->get_row( $sql, ARRAY_A ) ); }
		if ( 0 === strpos( $sql, 'SELECT order_id' ) || 0 === strpos( $sql, 'SELECT id' ) ) {
			return array_fill(
				0,
				min( 0 === strpos( $sql, 'SELECT order_id' ) ? 1 : 101, $this->database->data_count[ $table ] ),
				array(
					'order_id' => '10',
					'id'       => '1',
				)
			);
		}
		return $this->database->get_results( $sql, ARRAY_A );
	}
}

/**

 * Actual store with explicit offline SQL dependency.
 *
 * @return WCJ_Returns_Store Actual store with explicit offline SQL dependency.

 * @param mixed $database Database.
 */
function wcj_returns_test_sql_store( $database ) {
	return new WCJ_Returns_Store( $database, new WCJ_Returns_Test_Connection( $database ) ); }
/** Fail any WordPress/Woo authorization/option/translation/facts hook under TX. */
function wcj_returns_test_tx_hook_guard() {
	if ( isset( $GLOBALS['wcj_returns_test_test_store'] ) && $GLOBALS['wcj_returns_test_test_store']->active ) {
		throw new RuntimeException( 'forbidden_hook_inside_transaction' ); }
}

/** Run the finite offline fixture without unprefixed top-level test globals. */
function wcj_returns_test_run() {
	global $wcj_returns_test_root;
	$GLOBALS['wcj_returns_test_count'] = 0;
	/**
	 * Check an exact result and emit a named assertion.
	 *
	 * @param mixed $name Name.
	 * @param mixed $actual Actual.
	 * @param mixed $expected Expected.
	 */
	function wcj_returns_test_check( $name, $actual, $expected ) {
		++$GLOBALS['wcj_returns_test_count'];
		if ( $actual !== $expected ) {
			fwrite( STDERR, 'FAIL ' . $name . ': ' . json_encode( array( $actual, $expected ) ) . PHP_EOL );
			exit( 1 );
		}
		echo 'PASS ' . $name . PHP_EOL;
	}
	/**
	 * Error code or actual result.
	 *
	 * @return string|mixed Error code or actual result.
	 * @param mixed $value Value.
	 */
	function wcj_returns_test_code( $value ) {
		return is_wp_error( $value ) ? $value->get_error_code() : $value; }
	/**
	 * Exception code or explicit no-error marker.
	 *
	 * @return string Exception code or explicit no-error marker.
	 * @param mixed $operation Operation.
	 */
	function wcj_returns_test_thrown( $operation ) {
		try {
			$operation();
			return 'none';
		} catch ( Throwable $error ) {
			return $error->getMessage(); }
	}
	/**
	 * Synthetic user/action-bound nonce.
	 *
	 * @return string Synthetic user/action-bound nonce.
	 * @param mixed $action Action.
	 * @param mixed $tick Tick.
	 */
	function wcj_returns_test_nonce( $action, $tick = 1 ) {
		return 'tick' . $tick . ':' . $action . ':' . get_current_user_id(); }
	/**
	 * Fixed distinct idempotency tokens.
	 *
	 * @return string Fixed distinct idempotency tokens.
	 * @param mixed $number Number.
	 */
	function wcj_returns_test_token( $number ) {
		return str_pad( dechex( $number ), 64, '0', STR_PAD_LEFT ); }
	/** Reset all synthetic state and return a runnable actual service. */
	function wcj_returns_test_scenario() {
		$GLOBALS['wcj_returns_test_handler_primitives'] = null;
		$GLOBALS['wcj_returns_test_option_fault']       = '';
		$GLOBALS['wcj_returns_test_account_page']       = true;
		$GLOBALS['wcj_returns_test_endpoint']           = 'view-order';
		$GLOBALS['wcj_returns_test_redirect']           = '';
		$_POST = array();
		$_GET  = array();
		$GLOBALS['wcj_returns_test_actor']             = 7;
		$GLOBALS['wcj_returns_test_capable']           = false;
		$GLOBALS['wcj_returns_test_options']           = array(
			'wcj_returns_enabled'       => 'yes',
			'wcj_returns_window_days'   => '30',
			'wcj_returns_notifications' => 'no',
			'admin_email'               => 'merchant@example.invalid',
		);
		$GLOBALS['wcj_returns_test_users']             = array( 7 => (object) array( 'user_email' => 'owner@example.invalid' ) );
		$GLOBALS['wcj_returns_test_now']               = 2000000000;
		$GLOBALS['wcj_returns_test_messages']          = array();
		$GLOBALS['wcj_returns_test_mail_result']       = true;
		$GLOBALS['wcj_returns_test_mail_throw']        = false;
		$GLOBALS['wcj_returns_test_mail_callback']     = null;
		$GLOBALS['wcj_returns_test_external_cache']    = false;
		$GLOBALS['wcj_returns_test_hpos_cache']        = false;
		$GLOBALS['wcj_returns_test_hooks']             = array();
		$GLOBALS['wcj_returns_test_cache']             = array();
		$GLOBALS['wcj_returns_test_cache_deletes']     = array();
		$GLOBALS['wcj_returns_test_meta_forced']       = array();
		$GLOBALS['wcj_returns_test_test_store']        = new WCJ_Returns_Test_Store();
		$GLOBALS['wcj_returns_test_test_facts']        = new WCJ_Returns_Test_Facts();
		$GLOBALS['wcj_returns_test_test_facts']->value = array(
			'owner'       => 7,
			'status'      => 'completed',
			'completed'   => $GLOBALS['wcj_returns_test_now'] - 86400,
			'capacities'  => array(
				11 => 2000000,
				12 => 1000000,
			),
			'names'       => array(
				11 => 'Synthetic A',
				12 => 'Synthetic B',
			),
			'hash'        => str_repeat( 'a', 64 ),
			'amount_only' => false,
		);
		return new WCJ_Returns_Service(
			$GLOBALS['wcj_returns_test_test_store'],
			$GLOBALS['wcj_returns_test_test_facts'],
			function () {
				return $GLOBALS['wcj_returns_test_now'];
			}
		);
	}
	/**
	 * Normal actual customer intake.
	 *
	 * @return array|WP_Error Normal actual customer intake.
	 * @param mixed $service Service.
	 * @param mixed $number Number.
	 * @param mixed $items Items.
	 */
	function wcj_returns_test_submit_request( $service, $number = 1, $items = array( 11 => '1' ), $reason = 'Synthetic reason' ) {
		return $service->submit( 10, $items, $reason, wcj_returns_test_token( $number ), wcj_returns_test_nonce( 'wcj_returns_submit_10' ) );
	}
	/** Select a synthetic manager after customer intake. */
	function wcj_returns_test_manager() {
		$GLOBALS['wcj_returns_test_actor']   = 9;
		$GLOBALS['wcj_returns_test_capable'] = true; }

	if ( isset( $GLOBALS['argv'][2] ) && preg_match( '/^--focused=(B1|R1|R2|R3)$/D', $GLOBALS['argv'][2], $focus ) ) {
		wcj_returns_test_scenario();
		require $wcj_returns_test_root . '/includes/class-wcj-returns.php';
		wcj_returns_test_focus( $focus[1] );
		echo 'FOCUSED TOTAL ' . $GLOBALS['wcj_returns_test_count'] . ' passed.' . PHP_EOL;
		return;
	}

	wcj_returns_test_scenario();
	foreach ( array(
		'0'        => 0,
		'1'        => 1000000,
		'0.000001' => 1,
		'1.234567' => 1234567,
		'1000000'  => 1000000000000,
	) as $decimal => $units ) {
		wcj_returns_test_check( 'decimal6 exact ' . $decimal, WCJ_Returns_Quantity::parse( (string) $decimal ), $units );
		wcj_returns_test_check( 'decimal6 roundtrip ' . $decimal, WCJ_Returns_Quantity::parse( WCJ_Returns_Quantity::display( $units ) ), $units );
	}
	foreach ( array( 0.000001, abs( -0.000001 ), 1.234567, 0.1, 1000000.0 ) as $quantity ) {
		wcj_returns_test_check( 'native float canonical ' . $quantity, WCJ_Returns_Quantity::parse( $quantity ), (int) round( $quantity * 1000000 ) );
	}
	foreach ( array( '1e-6', '-1', '+1', '01', '1.', '.1', ' 1', '1 ', '1.0000001', '1000000.000001', INF, NAN, -0.000001, 0.0000001, true, null, array() ) as $position => $value ) {
		wcj_returns_test_check(
			'invalid quantity ' . $position,
			wcj_returns_test_thrown(
				function () use ( $value ) {
					WCJ_Returns_Quantity::parse( $value );
				}
			),
			'quantity_invalid'
		);
	}

	$GLOBALS['wcj_returns_test_orders']                       = array(
		10 => array(
			'owner'     => 7,
			'status'    => 'completed',
			'completed' => 1999990000,
			'items'     => array( 11, 12 ),
		),
		20 => array(
			'parent' => 10,
			'amount' => '0.01',
			'items'  => array( 21 ),
		),
	);
	$GLOBALS['wcj_returns_test_items']                        = array(
		11 => array(
			'order'    => 10,
			'quantity' => 0.000002,
			'name'     => 'Synthetic micro',
			'product'  => 1,
		),
		12 => array(
			'order'    => 10,
			'quantity' => '2.5',
			'name'     => 'Synthetic whole',
			'product'  => 2,
		),
		21 => array(
			'order'    => 20,
			'quantity' => -0.000001,
			'name'     => 'Synthetic refund',
			'product'  => 1,
			'refunded' => 11,
			'total'    => '-0.01',
		),
	);
	$GLOBALS['wcj_returns_test_refund_ids']                   = array( 20 );
	$GLOBALS['wcj_returns_test_cache']['posts'][10]           = array(
		'owner'     => 99,
		'status'    => 'pending',
		'completed' => 0,
		'items'     => array(),
	);
	$GLOBALS['wcj_returns_test_cache']['order_item_meta'][11] = array(
		'order'    => 10,
		'quantity' => 99,
		'name'     => 'Stale',
		'product'  => 1,
	);
	$real_facts = new WCJ_Returns_Facts();
	$facts      = $real_facts->load( 10 );
	wcj_returns_test_check( 'facts fresh owner avoids stale post properties', $facts['owner'], 7 );
	wcj_returns_test_check( 'facts fresh quantity property before constructor and negative microrefund', $facts['capacities'][11], 1 );
	wcj_returns_test_check( 'facts partial refund does not remove other item capacity', $facts['capacities'][12], 2500000 );
	wcj_returns_test_check( 'facts raw metadata reads are forced', array_column( $GLOBALS['wcj_returns_test_meta_forced'], 1 ), array( true, true, true ) );
	wcj_returns_test_check( 'refund query bounded ids', array( $GLOBALS['wcj_returns_test_refund_query']['limit'], $GLOBALS['wcj_returns_test_refund_query']['return'], $GLOBALS['wcj_returns_test_refund_query']['cache_results'] ), array( 101, 'ids', false ) );
	// Constructed WC_Order::get_type() alone cannot establish stored record type.
	$GLOBALS['wcj_returns_test_orders'][10]['type'] = 'shop_order_refund';
	wcj_returns_test_check(
		'persisted refund masquerading as order fails before item reads',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'order_unavailable'
	);
	unset( $GLOBALS['wcj_returns_test_orders'][10]['type'] );
	$GLOBALS['wcj_returns_test_orders'][20]['type'] = 'shop_order';
	wcj_returns_test_check(
		'persisted order masquerading as refund fails before refund lines',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'order_unavailable'
	);
	unset( $GLOBALS['wcj_returns_test_orders'][20]['type'] );
	$first_hash                                      = $facts['hash'];
	$GLOBALS['wcj_returns_test_orders'][10]['owner'] = 8;
	wcj_returns_test_check( 'facts hash binds current owner', $real_facts->load( 10 )['hash'] === $first_hash, false );
	$GLOBALS['wcj_returns_test_orders'][10]['owner']   = 7;
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = 0;
	wcj_returns_test_check( 'amount-only refund fails eligibility facts', $real_facts->load( 10 )['amount_only'], true );
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = '1';
	wcj_returns_test_check(
		'positive refund quantity invalid',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'refund_invalid'
	);
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = '-0.000003';
	wcj_returns_test_check(
		'refund greater than purchase invalid',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'refund_invalid'
	);
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = -0.000001;
	$GLOBALS['wcj_returns_test_items'][21]['refunded'] = 999;
	wcj_returns_test_check(
		'foreign refund item invalid',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'refund_invalid'
	);
	$GLOBALS['wcj_returns_test_items'][21]['refunded'] = 11;
	wcj_returns_test_check( 'fully allocated quantity refund no amount-only hold', $real_facts->load( 10 )['amount_only'], false );
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '0.02';
	wcj_returns_test_check( 'mixed quantity plus unallocated amount holds whole order', $real_facts->load( 10 )['amount_only'], true );
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = 0.01;
	$GLOBALS['wcj_returns_test_items'][21]['total']   = -0.01;
	wcj_returns_test_check( 'native amount floats roundtrip exactly', $real_facts->load( 10 )['amount_only'], false );
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '0.011';
	$GLOBALS['wcj_returns_test_items'][21]['total']   = '-0.01';
	$GLOBALS['wcj_returns_test_items'][21]['taxes']   = array(
		'total'    => array( '2' => '-0.001' ),
		'subtotal' => array( '2' => '-0.001' ),
	);
	wcj_returns_test_check( 'signed total taxes count once not subtotal duplicate', $real_facts->load( 10 )['amount_only'], false );
	$GLOBALS['wcj_returns_test_items'][21]['taxes']['total'][2] = '0.001';
	wcj_returns_test_check( 'positive refund tax is uncertain and holds', $real_facts->load( 10 )['amount_only'], true );
	$GLOBALS['wcj_returns_test_items'][21]['taxes']   = array();
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '0.01';
	foreach ( array( 'shipping', 'fee' ) as $line_type ) {
		$GLOBALS['wcj_returns_test_items'][21]['type'] = $line_type;
		wcj_returns_test_check( 'refund nonquantity class holds ' . $line_type, $real_facts->load( 10 )['amount_only'], true );
		wcj_returns_test_check( 'refund nonquantity preserves item capacity ' . $line_type, $real_facts->load( 10 )['capacities'][11], 2 );
	}
	$GLOBALS['wcj_returns_test_items'][21]['type']    = 'line_item';
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '0';
	$GLOBALS['wcj_returns_test_items'][21]['total']   = '-0.00';
	wcj_returns_test_check( 'zero amount quantity refund exact', $real_facts->load( 10 )['amount_only'], false );
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = -0.0;
	wcj_returns_test_check( 'native negative zero quantity is amount-only', $real_facts->load( 10 )['amount_only'], true );
	$GLOBALS['wcj_returns_test_items'][21]['quantity'] = -0.000001;
	$GLOBALS['wcj_returns_test_orders'][20]['amount']  = '0.01';
	$GLOBALS['wcj_returns_test_items'][21]['total']    = '-0.01';
	foreach ( array( '0.0000001', '1e-6', '1000000000000', INF, 0.0000001 ) as $position => $invalid_amount ) {
		$GLOBALS['wcj_returns_test_orders'][20]['amount'] = $invalid_amount;
		wcj_returns_test_check(
			'unrepresentable amount not rounded ' . $position,
			wcj_returns_test_thrown(
				function () use ( $real_facts ) {
					$real_facts->load( 10 );
				}
			),
			'refund_invalid'
		);
	}
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '1';
	$GLOBALS['wcj_returns_test_items'][21]['total']   = '-999999999999.999999';
	$GLOBALS['wcj_returns_test_items'][21]['taxes']   = array( 'total' => array( '1' => '-0.000001' ) );
	wcj_returns_test_check(
		'signed allocation overflow checked before addition',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'refund_invalid'
	);
	$GLOBALS['wcj_returns_test_items'][21]['total']   = '-0.01';
	$GLOBALS['wcj_returns_test_items'][21]['taxes']   = array();
	$GLOBALS['wcj_returns_test_orders'][20]['amount'] = '0.01';
	$GLOBALS['wcj_returns_test_external_cache']       = true;
	wcj_returns_test_check(
		'persistent cache freshness fails closed',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'fresh_cache_unavailable'
	);
	$GLOBALS['wcj_returns_test_external_cache'] = false;
	$GLOBALS['wcj_returns_test_hpos_cache']     = true;
	wcj_returns_test_check(
		'HPOS datastore cache freshness fails closed',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'fresh_cache_unavailable'
	);
	$GLOBALS['wcj_returns_test_hpos_cache'] = false;
	$GLOBALS['wcj_returns_test_refund_ids'] = range( 1, 101 );
	wcj_returns_test_check(
		'refund count bounded',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'facts_limit'
	);
	$GLOBALS['wcj_returns_test_refund_ids']          = array();
	$GLOBALS['wcj_returns_test_orders'][10]['items'] = range( 1, 201 );
	wcj_returns_test_check(
		'order item iteration count bounded',
		wcj_returns_test_thrown(
			function () use ( $real_facts ) {
				$real_facts->load( 10 );
			}
		),
		'facts_limit'
	);

	// Actual private-store schema and transaction code, with explicit SQL doubles.
	$owner      = new wpdb();
	$owner->dbh = new mysqli();
	$adapter    = wcj_returns_test_adapter_double( $owner );
	wcj_returns_test_check( 'actual adapter sends one direct command', $adapter->query( 'SELECT {placeholder} literal' ), 1 );
	wcj_returns_test_check( 'actual adapter removes placeholder escape before send', $owner->dbh->queries, array( 'SELECT % literal' ) );
	wcj_returns_test_check( 'actual adapter exactly one placeholder removal', $owner->removals, 1 );
	$owner->dbh->result = false;
	wcj_returns_test_check(
		'actual adapter false normalized no retry',
		wcj_returns_test_thrown(
			function () use ( $adapter ) {
				$adapter->query( 'PRIVATE WRITE' );
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'actual adapter false exactly one additional attempt', count( $owner->dbh->queries ), 2 );
	$owner->dbh->throws = true;
	wcj_returns_test_check(
		'actual adapter Throwable normalized no retry',
		wcj_returns_test_thrown(
			function () use ( $adapter ) {
				$adapter->query( 'PRIVATE WRITE2' );
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'actual adapter Throwable exactly one additional attempt', count( $owner->dbh->queries ), 3 );
	$owner      = new wpdb();
	$original   = new mysqli();
	$owner->dbh = $original;
	$adapter    = wcj_returns_test_adapter_double( $owner );
	$owner->dbh = new mysqli();
	wcj_returns_test_check(
		'actual adapter changed handle rejected before SQL',
		wcj_returns_test_thrown(
			function () use ( $adapter ) {
				$adapter->query( 'MUST NOT SEND' );
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'actual adapter changed handle zero original attempts', $original->queries, array() );
	$owner             = new wpdb();
	$owner->dbh        = new mysqli();
	$adapter           = wcj_returns_test_adapter_double( $owner );
	$owner->dbh->after = function () use ( $owner ) {
		++$owner->dbh->identity;
	};
	wcj_returns_test_check(
		'actual adapter changed thread after attempt rejected',
		wcj_returns_test_thrown(
			function () use ( $adapter ) {
				$adapter->query( 'PRIVATE WRITE' );
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'actual adapter changed thread no repeat', count( $owner->dbh->queries ), 1 );
	$owner              = new wpdb();
	$owner->dbh         = new mysqli();
	$adapter            = wcj_returns_test_adapter_double( $owner );
	$buffer             = new mysqli_result();
	$buffer->rows       = array( array( 'value' => 'synthetic' ) );
	$owner->dbh->result = $buffer;
	wcj_returns_test_check( 'actual adapter direct buffered rows', $adapter->rows( 'SELECT PRIVATE ROW' ), $buffer->rows );
	wcj_returns_test_check( 'actual adapter frees direct buffered result', $buffer->freed, true );
	wcj_returns_test_check( 'actual adapter direct insert identity', $adapter->insert_id(), 17 );
	wcj_returns_test_check(
		'constructor native admission is NOT bypassed by test double',
		wcj_returns_test_thrown(
			function () use ( $owner ) {
				new WCJ_Returns_Connection( $owner );
			}
		),
		'storage_unavailable'
	);
	$db = new WCJ_Returns_Test_DB();
	wcj_returns_test_check(
		'actual store exact schema admitted',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( wcj_returns_test_sql_store( $db ) )->require_ready();
			}
		),
		'none'
	);
	foreach ( array( 'engine', 'digest_width', 'digest_nullable', 'signed_quantity', 'small_quantity', 'missing_request_primary', 'nonunique_token', 'digest_prefix', 'claim_prefix', 'id_extra' ) as $change ) {
		$db = new WCJ_Returns_Test_DB();
		switch ( $change ) {
			case 'engine':
				$db->engine = 'MyISAM';
				break;
			case 'digest_width':
				$db->columns['wp_wcj_return_requests'][3]['Type'] = 'char(16)';
				break;
			case 'digest_nullable':
				$db->columns['wp_wcj_return_requests'][3]['Null'] = 'YES';
				break;
			case 'signed_quantity':
				$db->columns['wp_wcj_return_claims'][2]['Type'] = 'bigint';
				break;
			case 'small_quantity':
				$db->columns['wp_wcj_return_claims'][2]['Type'] = 'int unsigned';
				break;
			case 'missing_request_primary':
				array_shift( $db->indexes['wp_wcj_return_requests'] );
				break;
			case 'nonunique_token':
				$db->indexes['wp_wcj_return_requests'][3]['Non_unique'] = 1;
				break;
			case 'digest_prefix':
				$db->indexes['wp_wcj_return_requests'][3]['Sub_part'] = 16;
				break;
			case 'claim_prefix':
				$db->indexes['wp_wcj_return_claims'][0]['Sub_part'] = 4;
				break;
			case 'id_extra':
				$db->columns['wp_wcj_return_requests'][0]['Extra'] = '';
				break;
		}
		wcj_returns_test_check(
			'schema drift denied ' . $change,
			wcj_returns_test_thrown(
				function () use ( $db ) {
					( wcj_returns_test_sql_store( $db ) )->require_ready();
				}
			),
			'storage_unavailable'
		);
	}
	$db         = new WCJ_Returns_Test_DB();
	$db->active = true;
	wcj_returns_test_check(
		'ambient transaction is not joined',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( wcj_returns_test_sql_store( $db ) )->transaction(
					function () {
						throw new RuntimeException( 'must_not_run' );
					}
				);
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'ambient admission did not start commit or rollback', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' ) );
	$db    = new WCJ_Returns_Test_DB();
	$store = wcj_returns_test_sql_store( $db );
	wcj_returns_test_check(
		'owned rollback preserves original exception',
		wcj_returns_test_thrown(
			function () use ( $store ) {
				$store->transaction(
					function () {
						throw new RuntimeException( 'original_failure' );
					}
				);
			}
		),
		'original_failure'
	);
	wcj_returns_test_check( 'owned rollback sequence', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' ) );
	$db       = new WCJ_Returns_Test_DB();
	$db->fail = 'COMMIT';
	wcj_returns_test_check(
		'commit unknown explicitly reported',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( wcj_returns_test_sql_store( $db ) )->transaction(
					function () {
						return 'ok';
					}
				);
			}
		),
		'commit_unknown'
	);
	wcj_returns_test_check( 'unknown COMMIT cleanup only no retry or business replay', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ) );
	wcj_returns_test_check( 'live handle COMMIT false leaves no active transaction after cleanup', $db->active, false );
	$db = new WCJ_Returns_Test_DB(); $db->fail = 'COMMIT';
	$lost_connection = new WCJ_Returns_Test_Connection( $db ); $lost_connection->disconnect_on_commit = true;
	$lost_store = new WCJ_Returns_Store( $db, $lost_connection );
	wcj_returns_test_check( 'lost original handle cleanup failure preserves commit_unknown', wcj_returns_test_thrown( function () use ( $lost_store ) { $lost_store->transaction( function () { return 'done'; } ); } ), 'commit_unknown' );
	wcj_returns_test_check( 'lost original handle attempts bounded one cleanup no reconnect', $lost_connection->attempts, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'COMMIT', 'ROLLBACK' ) );
	wcj_returns_test_check( 'lost original handle cleanup transmits no query on replacement', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'COMMIT' ) );
	$db                                  = new WCJ_Returns_Test_DB();
	$GLOBALS['wcj_returns_test_capable'] = true;
	$db->fail                            = 'CREATE TABLE';
	$db->present                         = array(
		'wp_wcj_return_requests' => false,
		'wp_wcj_return_claims'   => false,
	);
	wcj_returns_test_check(
		'DDL failure explicit',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( wcj_returns_test_sql_store( $db ) )->install();
			}
		),
		'storage_failure'
	);
	wcj_returns_test_check( 'install isolation consumed before DDL', array_slice( $db->sql, 0, 3 ), array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' ) );
	wcj_returns_test_check( 'DDL failure leaves no pending next isolation', $db->next_isolation, false );
	$db                                  = new WCJ_Returns_Test_DB();
	$GLOBALS['wcj_returns_test_capable'] = false;
	wcj_returns_test_check(
		'installation current capability required',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( wcj_returns_test_sql_store( $db ) )->install();
			}
		),
		'forbidden'
	);
	wcj_returns_test_check( 'denied install zero SQL', $db->sql, array() );
	$GLOBALS['wcj_returns_test_capable'] = true;
	$db                                  = new WCJ_Returns_Test_DB();
	wcj_returns_test_check(
		'both tables existing setup readiness only',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				wcj_returns_test_sql_store( $db )->install();
			}
		),
		'none'
	);
	wcj_returns_test_check( 'both tables existing setup no DDL or transaction', $db->sql, array() );
	foreach ( array( 'wp_wcj_return_requests', 'wp_wcj_return_claims' ) as $surviving ) {
		$db                           = new WCJ_Returns_Test_DB();
		$db->present                  = array(
			'wp_wcj_return_requests' => false,
			'wp_wcj_return_claims'   => false,
		);
		$db->present[ $surviving ]    = true;
		$db->data_count[ $surviving ] = 1;
		wcj_returns_test_check(
			'populated partial setup denied ' . $surviving,
			wcj_returns_test_thrown(
				function () use ( $db ) {
					wcj_returns_test_sql_store( $db )->install();
				}
			),
			'storage_unavailable'
		);
		wcj_returns_test_check( 'populated partial setup no state mutation ' . $surviving, $db->sql, array() );
		$db->data_count[ $surviving ] = 0;
		wcj_returns_test_check(
			'empty exact partial setup retry accepted ' . $surviving,
			wcj_returns_test_thrown(
				function () use ( $db ) {
					wcj_returns_test_sql_store( $db )->install();
				}
			),
			'none'
		);
		wcj_returns_test_check( 'empty partial setup both tables present ' . $surviving, array_values( $db->present ), array( true, true ) );
		$db                                   = new WCJ_Returns_Test_DB();
		$db->present                          = array(
			'wp_wcj_return_requests' => false,
			'wp_wcj_return_claims'   => false,
		);
		$db->present[ $surviving ]            = true;
		$db->columns[ $surviving ][0]['Type'] = 'int unsigned';
		wcj_returns_test_check(
			'drifted surviving schema denied before retry ' . $surviving,
			wcj_returns_test_thrown(
				function () use ( $db ) {
					wcj_returns_test_sql_store( $db )->install();
				}
			),
			'storage_unavailable'
		);
		wcj_returns_test_check( 'drifted surviving schema no DDL ' . $surviving, $db->sql, array() );
	}
	$db          = new WCJ_Returns_Test_DB();
	$db->present = array(
		'wp_wcj_return_requests' => false,
		'wp_wcj_return_claims'   => false,
	);
	wcj_returns_test_check(
		'fresh explicit setup creates exact pair',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				wcj_returns_test_sql_store( $db )->install();
			}
		),
		'none'
	);
	wcj_returns_test_check( 'fresh explicit setup pair admitted', array_values( $db->present ), array( true, true ) );
	$db = new WCJ_Returns_Test_DB();
	wcj_returns_test_check(
		'store constructor no SQL or handle admission',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				new WCJ_Returns_Store( $db );
			}
		),
		'none'
	);
	wcj_returns_test_check(
		'unqualified wpdb replacement fails closed at use',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				( new WCJ_Returns_Store( $db ) )->require_ready();
			}
		),
		'storage_unavailable'
	);
	wcj_returns_test_check( 'unqualified wpdb replacement no command emitted', $db->sql, array() );
	$db             = new WCJ_Returns_Test_DB();
	$db->autocommit = false;
	wcj_returns_test_check(
		'autocommit disabled admission fails closed',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				wcj_returns_test_sql_store( $db )->transaction(
					function () {
						throw new RuntimeException( 'must_not_run' );
					}
				);
			}
		),
		'transaction_unavailable'
	);
	wcj_returns_test_check( 'autocommit disabled no BEGIN COMMIT', $db->sql, array() );
	$db = new WCJ_Returns_Test_DB();
	wcj_returns_test_check(
		'aged Phase A fails before private schema SQL',
		wcj_returns_test_thrown(
			function () use ( $db ) {
				wcj_returns_test_sql_store( $db )->transaction(
					function () {
						throw new RuntimeException( 'must_not_run' );
					},
					hrtime( true ) - 5000000001
				);
			}
		),
		'facts_stale'
	);
	wcj_returns_test_check( 'aged Phase A no SQL', array( $db->sql, $db->read_sql ), array( array(), array() ) );
	$db               = new WCJ_Returns_Test_DB();
	$sql_store        = wcj_returns_test_sql_store( $db );
	$started_property = new ReflectionProperty( 'WCJ_Returns_Store', 'started' );
	$started_property->setAccessible( true );
	$db->after_query = function ( $statement ) use ( $sql_store, $started_property ) {
		if ( 'START TRANSACTION' === $statement ) {
			$started_property->setValue( $sql_store, hrtime( true ) - 5000000001 ); }
	};
	wcj_returns_test_check(
		'snapshot expires after START rolls back before business operation',
		wcj_returns_test_thrown(
			function () use ( $sql_store ) {
				$sql_store->transaction(
					function () {
						throw new RuntimeException( 'must_not_run' );
					},
					hrtime( true )
				);
			}
		),
		'facts_stale'
	);
	wcj_returns_test_check( 'expired START consumes isolation and rolls back', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' ) );
	wcj_returns_test_check( 'expired START isolation not left pending', $db->next_isolation, false );
	$db        = new WCJ_Returns_Test_DB();
	$sql_store = wcj_returns_test_sql_store( $db );
	wcj_returns_test_check(
		'snapshot expires before COMMIT rolls back not unknown',
		wcj_returns_test_thrown(
			function () use ( $sql_store, $started_property ) {
				$sql_store->transaction(
					function () use ( $sql_store, $started_property ) {
						$started_property->setValue( $sql_store, hrtime( true ) - 5000000001 );
						return 'done';
					},
					hrtime( true )
				);
			}
		),
		'facts_stale'
	);
	wcj_returns_test_check( 'expired precommit no COMMIT emitted', $db->sql, array( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED', 'START TRANSACTION', 'ROLLBACK' ) );
	$db        = new WCJ_Returns_Test_DB();
	$sql_store = wcj_returns_test_sql_store( $db );
	wcj_returns_test_check(
		'snapshot stale immediately before private write blocks it',
		wcj_returns_test_thrown(
			function () use ( $sql_store, $started_property ) {
				$sql_store->transaction(
					function () use ( $sql_store, $started_property ) {
						$started_property->setValue( $sql_store, hrtime( true ) - 5000000001 );
						$sql_store->claim( 10, 11, 1 );
					},
					hrtime( true )
				);
			}
		),
		'facts_stale'
	);
	wcj_returns_test_check(
		'snapshot stale before write zero UPDATE attempts',
		count(
			array_filter(
				$db->sql,
				function ( $statement ) {
					return 0 === strpos( $statement, 'UPDATE' );
				}
			)
		),
		0
	);
	$db              = new WCJ_Returns_Test_DB();
	$sql_store       = wcj_returns_test_sql_store( $db );
	$db->after_query = function ( $statement ) use ( $sql_store, $started_property ) {
		if ( 0 === strpos( $statement, 'UPDATE' ) ) {
			$started_property->setValue( $sql_store, hrtime( true ) - 5000000001 ); }
	};
	wcj_returns_test_check(
		'snapshot stale after simulated private lock wait rolls back',
		wcj_returns_test_thrown(
			function () use ( $sql_store ) {
				$sql_store->transaction(
					function () use ( $sql_store ) {
						$sql_store->claim( 10, 11, 1 );
					},
					hrtime( true )
				);
			}
		),
		'facts_stale'
	);
	wcj_returns_test_check( 'snapshot stale after wait rollback final command', end( $db->sql ), 'ROLLBACK' );
	wcj_returns_test_check( 'snapshot stale after wait no COMMIT', in_array( 'COMMIT', $db->sql, true ), false );

	// Actual service: explicit authorization, replay, capacity and finite state tests.
	foreach ( array( 0, 8 ) as $denied_actor ) {
		$service                           = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_actor'] = $denied_actor;
		wcj_returns_test_check( 'owner denied ' . $denied_actor, wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'forbidden' );
		wcj_returns_test_check( 'owner denied no private writes ' . $denied_actor, $GLOBALS['wcj_returns_test_test_store']->writes, 0 );
	}
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_enabled'] = 'no';
	wcj_returns_test_check( 'disabled intake', wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'module_disabled' );
	wcj_returns_test_check( 'disabled no facts query', $GLOBALS['wcj_returns_test_test_facts']->loads, 0 );
	$service                           = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_actor'] = 0;
	wcj_returns_test_check( 'anonymous customer GET early denied', wcj_returns_test_code( $service->customer_view( 10 ) ), 'forbidden' );
	wcj_returns_test_check( 'anonymous customer GET zero Woo facts reads', $GLOBALS['wcj_returns_test_test_facts']->loads, 0 );
	foreach ( array( '', 'wrong', 'tick1:wcj_returns_submit_11:7', 'tick1:wcj_returns_submit_10:8' ) as $position => $bad_nonce ) {
		$service = wcj_returns_test_scenario();
		wcj_returns_test_check( 'nonce denied ' . $position, wcj_returns_test_code( $service->submit( 10, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), $bad_nonce ) ), 'nonce_invalid' );
		wcj_returns_test_check( 'nonce denied no facts query ' . $position, $GLOBALS['wcj_returns_test_test_facts']->loads, 0 );
	}
	foreach ( array( 1, 2 ) as $tick ) {
		$service = wcj_returns_test_scenario();
		$row     = $service->submit( 10, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), wcj_returns_test_nonce( 'wcj_returns_submit_10', $tick ) );
		wcj_returns_test_check( 'WP nonce tick accepted ' . $tick, is_wp_error( $row ), false );
	}
	foreach ( array( 0, -1, '10', 10.0, null ) as $position => $bad_id ) {
		$service = wcj_returns_test_scenario();
		wcj_returns_test_check( 'service identifier strict ' . $position, wcj_returns_test_code( $service->submit( $bad_id, array( 11 => '1' ), '', wcj_returns_test_token( 1 ), 'ignored' ) ), 'input_invalid' );
	}
	foreach ( array( '', '0', 0, '366', '30.0', false, null, '030' ) as $position => $bad_policy ) {
		$service = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_options']['wcj_returns_window_days'] = $bad_policy;
		wcj_returns_test_check( 'invalid policy no fallback ' . $position, wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'policy_invalid' );
		wcj_returns_test_check( 'invalid policy no retained claims ' . $position, $GLOBALS['wcj_returns_test_test_store']->claims, array() );
	}
	foreach ( array( 0, 30 * DAY_IN_SECONDS, 30 * DAY_IN_SECONDS + 1, -1 ) as $elapsed ) {
		$service = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_test_facts']->value['completed'] = $GLOBALS['wcj_returns_test_now'] - $elapsed;
		$result = wcj_returns_test_submit_request( $service );
		wcj_returns_test_check( 'elapsed UTC eligibility ' . $elapsed, is_wp_error( $result ) ? wcj_returns_test_code( $result ) : 'saved', $elapsed >= 0 && $elapsed <= 30 * DAY_IN_SECONDS ? 'saved' : 'not_eligible' );
	}
	foreach ( array( 'pending', 'processing', 'cancelled', 'refunded' ) as $status ) {
		$service = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_test_facts']->value['status'] = $status;
		wcj_returns_test_check( 'completed only ' . $status, wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'not_eligible' );
	}
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_test_facts']->value['amount_only'] = true;
	wcj_returns_test_check( 'unallocated refund needs review', wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'needs_review' );
	wcj_returns_test_check( 'review marker retained but no item claims', $GLOBALS['wcj_returns_test_test_store']->claims['10:0']['needs_review'], 1 );
	foreach ( array( array(), array( 11 => '0' ), array( 11 => '1e-6' ), array( '011' => '1' ), array( 999 => '1' ), array_fill_keys( range( 1, 21 ), '1' ) ) as $position => $items ) {
		$service = wcj_returns_test_scenario();
		wcj_returns_test_check( 'invalid selection denied ' . $position, is_wp_error( wcj_returns_test_submit_request( $service, 1, $items ) ), true );
		wcj_returns_test_check( 'invalid selection rolls back all rows ' . $position, count( $GLOBALS['wcj_returns_test_test_store']->rows ), 0 );
	}
	$service = wcj_returns_test_scenario();
	wcj_returns_test_check( 'markup reason rejected not silently rewritten', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 1, array( 11 => '1' ), '<b>reason</b>' ) ), 'input_invalid' );
	wcj_returns_test_check( 'reason character limit', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 1, array( 11 => '1' ), str_repeat( 'x', 501 ) ) ), 'input_invalid' );
	wcj_returns_test_check( 'random token shape required', wcj_returns_test_code( $service->submit( 10, array( 11 => '1' ), '', 'short', wcj_returns_test_nonce( 'wcj_returns_submit_10' ) ) ), 'input_invalid' );
	$row = wcj_returns_test_submit_request(
		$service,
		1,
		array(
			12 => '0.5',
			11 => '1.5',
		)
	);
	wcj_returns_test_check( 'intake pending revision1', array( $row['status'], $row['revision'] ), array( 'pending', 1 ) );
	wcj_returns_test_check( 'intake sorted locks anchor first', array_slice( $GLOBALS['wcj_returns_test_test_store']->locks, -2 ), array( array( 10, 11 ), array( 10, 12 ) ) );
	wcj_returns_test_check( 'intake holds exact decimal quantities', array( $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], $GLOBALS['wcj_returns_test_test_store']->claims['10:12']['reserved'] ), array( 1500000, 500000 ) );
	wcj_returns_test_check( 'intake optout no notifications', $GLOBALS['wcj_returns_test_test_store']->payload( $row )['outbox'], array() );
	wcj_returns_test_check( 'intake no mail side effect', $GLOBALS['wcj_returns_test_messages'], array() );
	$replay = wcj_returns_test_submit_request(
		$service,
		1,
		array(
			11 => '1.500000',
			12 => '0.500000',
		)
	);
	wcj_returns_test_check( 'canonical equivalent authorized replay same request', $replay['id'], $row['id'] );
	wcj_returns_test_check( 'replay does not reserve twice', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 1500000 );
	wcj_returns_test_check( 'token changed input conflict', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 1, array( 11 => '0.5' ) ) ), 'idempotency_conflict' );
	$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 8;
	wcj_returns_test_check(
		'changed owner denied before replay',
		wcj_returns_test_code(
			wcj_returns_test_submit_request(
				$service,
				1,
				array(
					11 => '1.5',
					12 => '0.5',
				)
			)
		),
		'forbidden'
	);
	$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 7;
	wcj_returns_test_check(
		'multi item insufficient capacity',
		wcj_returns_test_code(
			wcj_returns_test_submit_request(
				$service,
				2,
				array(
					11 => '0.5',
					12 => '1',
				)
			)
		),
		'capacity_exceeded'
	);
	wcj_returns_test_check( 'multi item capacity rollback first item', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 1500000 );
	wcj_returns_test_check( 'multi item failure no new request', count( $GLOBALS['wcj_returns_test_test_store']->rows ), 1 );
	$view_writes = $GLOBALS['wcj_returns_test_test_store']->writes;
	$view        = $service->customer_view( 10 );
	wcj_returns_test_check( 'owner history contains own row', count( $view['rows'] ), 1 );
	wcj_returns_test_check(
		'GET view exact available quantities',
		$view['available'],
		array(
			11 => 500000,
			12 => 500000,
		)
	);
	wcj_returns_test_check( 'GET view performs no private writes', $GLOBALS['wcj_returns_test_test_store']->writes, $view_writes );
	$GLOBALS['wcj_returns_test_actor'] = 8;
	wcj_returns_test_check( 'unowned history denied', wcj_returns_test_code( $service->customer_view( 10 ) ), 'forbidden' );
	wcj_returns_test_check( 'nonmanager queue denied', wcj_returns_test_code( $service->queue() ), 'forbidden' );
	wcj_returns_test_manager();
	$queue_writes = $GLOBALS['wcj_returns_test_test_store']->writes;
	wcj_returns_test_check( 'manager queue admitted', count( $service->queue() ), 1 );
	wcj_returns_test_check( 'queue GET performs no private writes', $GLOBALS['wcj_returns_test_test_store']->writes, $queue_writes );
	$valid_nonce                         = wcj_returns_test_nonce( 'wcj_returns_transition_1' );
	$GLOBALS['wcj_returns_test_capable'] = false;
	wcj_returns_test_check( 'revoked current capability still valid nonce denied', wcj_returns_test_code( $service->transition( 1, 1, 'approved', $valid_nonce ) ), 'forbidden' );
	$GLOBALS['wcj_returns_test_capable'] = true;
	wcj_returns_test_check( 'transition bad nonce', wcj_returns_test_code( $service->transition( 1, 1, 'approved', 'wrong' ) ), 'nonce_invalid' );
	wcj_returns_test_check( 'transition expected revision conflict', wcj_returns_test_code( $service->transition( 1, 2, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'revision_conflict' );
	$approved = $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1', 2 ) );
	wcj_returns_test_check( 'manager approved decision', array( $approved['status'], $approved['revision'] ), array( 'approved', 2 ) );
	wcj_returns_test_check( 'approval retains claims', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 1500000 );
	wcj_returns_test_check( 'history pending then approved', array_column( $GLOBALS['wcj_returns_test_test_store']->payload( $approved )['history'], 'status' ), array( 'pending', 'approved' ) );
	wcj_returns_test_check( 'approved cannot reopen', wcj_returns_test_code( $service->transition( 1, 2, 'cancelled', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'transition_invalid' );
	wcj_returns_test_check( 'approved no notification optout', $GLOBALS['wcj_returns_test_messages'], array() );

	foreach ( array( 'declined', 'cancelled' ) as $status ) {
		$service = wcj_returns_test_scenario();
		$row     = wcj_returns_test_submit_request( $service );
		wcj_returns_test_manager();
		$terminal = $service->transition( 1, 1, $status, wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
		wcj_returns_test_check( 'terminal decision ' . $status, $terminal['status'], $status );
		wcj_returns_test_check( 'terminal releases quantity exactly once ' . $status, $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 0 );
		wcj_returns_test_check( 'terminal cannot release twice ' . $status, wcj_returns_test_code( $service->transition( 1, 2, $status, wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'transition_invalid' );
		wcj_returns_test_check( 'released counter remains zero ' . $status, $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 0 );
	}
	$service = wcj_returns_test_scenario();
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_check( 'customer cannot approve', wcj_returns_test_code( $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'forbidden' );
	wcj_returns_test_check( 'customer own cancel allowed', $service->transition( 1, 1, 'cancelled', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) )['status'], 'cancelled' );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_test_facts']->value['capacities'][11] = 1;
	wcj_returns_test_check( 'last micro unit first serialized request', is_wp_error( wcj_returns_test_submit_request( $service, 1, array( 11 => '0.000001' ) ) ), false );
	wcj_returns_test_check( 'last micro unit second serialized request denied', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 2, array( 11 => '0.000001' ) ) ), 'capacity_exceeded' );
	wcj_returns_test_check( 'last micro unit conserved', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 1 );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_test_store']->on_lock = function () {
		$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 8;
		$GLOBALS['wcj_returns_test_test_facts']->value['hash']  = str_repeat( 'b', 64 );
	};
	$captured_row                                    = wcj_returns_test_submit_request( $service );
	wcj_returns_test_check( 'Phase A explicitly not atomic with external owner changes', $captured_row['actor_id'], 7 );
	wcj_returns_test_check( 'Phase A no Woo reread under lock', $GLOBALS['wcj_returns_test_test_facts']->loads, 1 );
	wcj_returns_test_manager();
	wcj_returns_test_check( 'external owner change surfaces on next approval', wcj_returns_test_code( $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'needs_review' );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_test_store']->on_lock = function () {
		$GLOBALS['wcj_returns_test_actor']                      = 8;
		$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 8;
	};
	wcj_returns_test_check( 'Phase B retains captured authorized actor no hook read', wcj_returns_test_submit_request( $service )['actor_id'], 7 );

	foreach ( array( 'refund', 'status', 'completion', 'owner' ) as $change ) {
		$service = wcj_returns_test_scenario();
		wcj_returns_test_submit_request( $service );
		$GLOBALS['wcj_returns_test_test_facts']->value['hash'] = str_repeat( 'b', 64 );
		if ( 'refund' === $change ) {
			$GLOBALS['wcj_returns_test_test_facts']->value['capacities'][11] = 500000; }
		if ( 'status' === $change ) {
			$GLOBALS['wcj_returns_test_test_facts']->value['status'] = 'processing'; }
		if ( 'completion' === $change ) {
			$GLOBALS['wcj_returns_test_test_facts']->value['completed'] -= 1; }
		if ( 'owner' === $change ) {
			$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 8; }
		wcj_returns_test_manager();
		wcj_returns_test_check( 'changed facts approval held ' . $change, wcj_returns_test_code( $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'needs_review' );
		wcj_returns_test_check( 'changed facts marker durable ' . $change, $GLOBALS['wcj_returns_test_test_store']->claims['10:0']['needs_review'], 1 );
		wcj_returns_test_check( 'changed facts preserve claims ' . $change, $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 1000000 );
		wcj_returns_test_check( 'changed facts decline still allowed ' . $change, $service->transition( 1, 1, 'declined', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) )['status'], 'declined' );
	}
	$service = wcj_returns_test_scenario();
	wcj_returns_test_submit_request( $service );
	$GLOBALS['wcj_returns_test_test_facts']->value['hash'] = str_repeat( 'b', 64 );
	wcj_returns_test_check( 'changed facts new intake held', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 2 ) ), 'needs_review' );
	$view_writes = $GLOBALS['wcj_returns_test_test_store']->writes;
	wcj_returns_test_check( 'changed facts GET review derived', $service->customer_view( 10 )['needs_review'], true );
	wcj_returns_test_check( 'changed facts GET still no writes', $GLOBALS['wcj_returns_test_test_store']->writes, $view_writes );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_test_store']->unknown = true;
	wcj_returns_test_check( 'unknown completion visible not replayed', wcj_returns_test_code( wcj_returns_test_submit_request( $service ) ), 'commit_unknown' );
	wcj_returns_test_check( 'unknown completion exactly one insert attempt', count( $GLOBALS['wcj_returns_test_test_store']->rows ), 1 );

	$service = wcj_returns_test_scenario();
	for ( $number = 1; $number <= 99; ++$number ) {
		$row = wcj_returns_test_submit_request( $service, $number, array( 11 => '0.000001' ) );
		if ( is_wp_error( $row ) ) {
			wcj_returns_test_check( '100 request setup must succeed', wcj_returns_test_code( $row ), 'saved' ); }
		wcj_returns_test_manager();
		$status   = array( 'approved', 'declined', 'cancelled' )[ $number % 3 ];
		$terminal = $service->transition( $number, 1, $status, wcj_returns_test_nonce( 'wcj_returns_transition_' . $number ) );
		if ( is_wp_error( $terminal ) ) {
			wcj_returns_test_check( '100 request setup transition must succeed', wcj_returns_test_code( $terminal ), 'saved' ); }
		$GLOBALS['wcj_returns_test_actor']   = 7;
		$GLOBALS['wcj_returns_test_capable'] = false;
	}
	wcj_returns_test_check( '99 retained requests all statuses count', $GLOBALS['wcj_returns_test_test_store']->retained_count( 10 ), 99 );
	$hundredth = wcj_returns_test_submit_request( $service, 100, array( 11 => '0.000001' ) );
	wcj_returns_test_check( 'serialized boundary99 permits request100', $hundredth['id'], 100 );
	$held = $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'];
	wcj_returns_test_check( 'serialized boundary100 rejects request101', wcj_returns_test_code( wcj_returns_test_submit_request( $service, 101, array( 11 => '0.000001' ) ) ), 'request_limit' );
	wcj_returns_test_check( 'request ceiling no extra quantity reserved', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], $held );
	wcj_returns_test_check( 'request ceiling no extra header', count( $GLOBALS['wcj_returns_test_test_store']->rows ), 100 );
	wcj_returns_test_check( 'ceiling replay first returns original request', wcj_returns_test_submit_request( $service, 100, array( 11 => '0.000001' ) )['id'], 100 );
	$limited_view = $service->customer_view( 10 );
	wcj_returns_test_check( 'ceiling customer UI state explicit', array( $limited_view['eligible'], $limited_view['request_limit'] ), array( false, true ) );
	wcj_returns_test_check( 'history first page bounded50', count( $limited_view['rows'] ), 50 );
	wcj_returns_test_check( 'history keyset second page preserves older records', count( $service->customer_view( 10, 51 )['rows'] ), 50 );

	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	$selected = array();
	$GLOBALS['wcj_returns_test_test_facts']->value['capacities'] = array_fill_keys( range( 100000000000000001, 100000000000000020 ), WCJ_Returns_Quantity::MAX );
	foreach ( $GLOBALS['wcj_returns_test_test_facts']->value['capacities'] as $id => $capacity ) {
		$selected[ $id ] = '1000000'; }
	$worst = wcj_returns_test_submit_request( $service, 1, $selected, str_repeat( "\xF0\x9F\x98\x80", 500 ) );
	wcj_returns_test_check( '20items18digit IDs 500 astral chars accepted bounded', is_wp_error( $worst ), false );
	wcj_returns_test_manager();
	$worst = $service->transition( 1, 1, 'declined', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
	wcj_returns_test_check( 'worst-case terminal payload remains below32KiB', strlen( $worst['payload'] ) < 32768, true );
	wcj_returns_test_check( 'worst-case retained one reason not per-item duplication', strlen( $GLOBALS['wcj_returns_test_test_store']->payload( $worst )['reason'] ), 2000 );

	// End-to-end notification path, definite pre-transport retry and unknown blocking.
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	$row = wcj_returns_test_submit_request( $service );
	wcj_returns_test_check( 'optin intake finite two notification intents', count( $GLOBALS['wcj_returns_test_test_store']->payload( $row )['outbox'] ), 2 );
	wcj_returns_test_check( 'optin intake never auto sends', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	wcj_returns_test_manager();
	$row = $service->transition( 1, 1, 'approved', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
	wcj_returns_test_check( 'decision finite four notification intents', count( $GLOBALS['wcj_returns_test_test_store']->payload( $row )['outbox'] ), 4 );
	wcj_returns_test_check( 'decision never auto sends', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	$sent = $service->dispatch( 1, 'decision_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_returns_test_check( 'explicit dispatch records acceptance', $GLOBALS['wcj_returns_test_test_store']->payload( $sent )['outbox']['decision_customer']['state'], 'accepted' );
	wcj_returns_test_check( 'explicit dispatch outside transaction one send', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	wcj_returns_test_check( 'recipient from current registered account', $GLOBALS['wcj_returns_test_messages'][0][0], 'owner@example.invalid' );
	wcj_returns_test_check( 'mail body does not disclose raw reason', false !== strpos( $GLOBALS['wcj_returns_test_messages'][0][2], 'Synthetic reason' ), false );
	wcj_returns_test_check( 'accepted never resends', wcj_returns_test_code( $service->dispatch( 1, 'decision_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_ambiguous' );
	wcj_returns_test_check( 'accepted no second send', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_manager();
	$GLOBALS['wcj_returns_test_mail_callback'] = function () use ( $service ) {
		$current  = $GLOBALS['wcj_returns_test_test_store']->rows[1];
		$decision = $service->transition( 1, $current['revision'], 'declined', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
		if ( is_wp_error( $decision ) ) {
			throw new RuntimeException( 'decision_race_failed' ); }
	};
	$raced                                     = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_returns_test_check( 'finalizer rereads row after concurrent decision preserves status', $raced['status'], 'declined' );
	wcj_returns_test_check( 'finalizer matches lease not stale caller revision', $GLOBALS['wcj_returns_test_test_store']->payload( $raced )['outbox']['submitted_customer']['state'], 'accepted' );
	wcj_returns_test_check( 'finalizer preserves raced decision history', array_column( $GLOBALS['wcj_returns_test_test_store']->payload( $raced )['history'], 'status' ), array( 'pending', 'declined' ) );
	wcj_returns_test_check( 'finalizer preserves raced four intents', count( $GLOBALS['wcj_returns_test_test_store']->payload( $raced )['outbox'] ), 4 );

	foreach ( array( 'false', 'throw', 'nontrue' ) as $outcome ) {
		$service = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
		wcj_returns_test_submit_request( $service );
		wcj_returns_test_manager();
		$GLOBALS['wcj_returns_test_mail_result'] = 'nontrue' === $outcome ? 1 : false;
		$GLOBALS['wcj_returns_test_mail_throw']  = 'throw' === $outcome;
		$sent                                    = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
		wcj_returns_test_check( 'after attempt unknown state ' . $outcome, $GLOBALS['wcj_returns_test_test_store']->payload( $sent )['outbox']['submitted_customer']['state'], 'ambiguous' );
		$GLOBALS['wcj_returns_test_now'] += 1000;
		wcj_returns_test_check( 'after attempt never queues retry ' . $outcome, wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_ambiguous' );
		wcj_returns_test_check( 'after attempt exactly one mail attempt ' . $outcome, count( $GLOBALS['wcj_returns_test_messages'] ), 1 );
	}
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_manager();
	$GLOBALS['wcj_returns_test_users'][7]->user_email = 'not-an-email';
	$failed = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	$event  = $GLOBALS['wcj_returns_test_test_store']->payload( $failed )['outbox']['submitted_customer'];
	wcj_returns_test_check( 'pretransport invalid recipient definite failure', $event['state'], 'failed' );
	wcj_returns_test_check( 'pretransport zero transport attempts', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	wcj_returns_test_check( 'pretransport first backoff60', $event['due'] - $GLOBALS['wcj_returns_test_now'], 60 );
	wcj_returns_test_check( 'pretransport retry not yet due', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_not_due' );
	$GLOBALS['wcj_returns_test_now'] += 60;
	$failed                           = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_returns_test_check( 'pretransport second backoff300', $GLOBALS['wcj_returns_test_test_store']->payload( $failed )['outbox']['submitted_customer']['due'] - $GLOBALS['wcj_returns_test_now'], 300 );
	$GLOBALS['wcj_returns_test_now'] += 300;
	$failed                           = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_returns_test_check( 'pretransport fixed three attempt ceiling', $GLOBALS['wcj_returns_test_test_store']->payload( $failed )['outbox']['submitted_customer']['attempts'], 3 );
	$GLOBALS['wcj_returns_test_now'] += 300;
	wcj_returns_test_check( 'pretransport fourth attempt blocked', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_not_due' );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_manager();
	$GLOBALS['wcj_returns_test_users'][7]->user_email = 'invalid';
	$service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	$GLOBALS['wcj_returns_test_now']                 += 60;
	$GLOBALS['wcj_returns_test_users'][7]->user_email = 'owner@example.invalid';
	$retry = $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) );
	wcj_returns_test_check( 'definite failed validation retry succeeds once', $GLOBALS['wcj_returns_test_test_store']->payload( $retry )['outbox']['submitted_customer']['state'], 'accepted' );
	wcj_returns_test_check( 'definite failed validation retry does not duplicate', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );

	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_manager();
	$valid_dispatch_nonce                = wcj_returns_test_nonce( 'wcj_returns_dispatch_1' );
	$GLOBALS['wcj_returns_test_capable'] = false;
	wcj_returns_test_check( 'dispatch revoked capability valid nonce denied', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', $valid_dispatch_nonce ) ), 'forbidden' );
	wcj_returns_test_check( 'dispatch revoked capability zero mail', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	$GLOBALS['wcj_returns_test_capable']                              = true;
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'no';
	wcj_returns_test_check( 'dispatch current optout denied', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_disabled' );
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_check( 'dispatch missing nonce denied', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', '' ) ), 'nonce_invalid' );
	wcj_returns_test_check( 'dispatch arbitrary intent denied', wcj_returns_test_code( $service->dispatch( 1, 'unknown', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'input_invalid' );
	$data = $GLOBALS['wcj_returns_test_test_store']->payload( $GLOBALS['wcj_returns_test_test_store']->rows[1] );
	$data['outbox']['submitted_customer']['state']              = 'sending';
	$data['outbox']['submitted_customer']['lease']              = str_repeat( 'c', 64 );
	$GLOBALS['wcj_returns_test_test_store']->rows[1]['payload'] = wp_json_encode( $data );
	$GLOBALS['wcj_returns_test_now']                           += 999999;
	wcj_returns_test_check( 'stale sending never reclaimed', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_ambiguous' );
	wcj_returns_test_check( 'stale sending zero transport attempts', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
	$service = wcj_returns_test_scenario();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
	wcj_returns_test_submit_request( $service );
	wcj_returns_test_manager();
	$GLOBALS['wcj_returns_test_mail_callback'] = function () {
		$data = $GLOBALS['wcj_returns_test_test_store']->payload( $GLOBALS['wcj_returns_test_test_store']->rows[1] );
		$data['outbox']['submitted_customer']['lease']              = str_repeat( 'd', 64 );
		$GLOBALS['wcj_returns_test_test_store']->rows[1]['payload'] = json_encode( $data );
	};
	wcj_returns_test_check( 'finalizer changed lease fails closed', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'notification_ambiguous' );
	wcj_returns_test_check( 'finalizer changed lease does not overwrite current owner token', $GLOBALS['wcj_returns_test_test_store']->payload( $GLOBALS['wcj_returns_test_test_store']->rows[1] )['outbox']['submitted_customer']['lease'], str_repeat( 'd', 64 ) );
	wcj_returns_test_check( 'finalizer changed lease exactly one transport attempt', count( $GLOBALS['wcj_returns_test_messages'] ), 1 );

	// Stored payload and module integration negative boundaries.
	$decoder = new WCJ_Returns_Test_Store();
	foreach ( array( 'oversize', 'not_json', 'scalar_items', 'finer_units', 'unknown_state', 'lease_missing', 'too_many_history', 'bad_policy' ) as $change ) {
		$service = wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
		$row  = wcj_returns_test_submit_request( $service );
		$data = $decoder->payload( $row );
		switch ( $change ) {
			case 'oversize':
				$row['payload'] = str_repeat( 'x', 32769 );
				break;
			case 'not_json':
				$row['payload'] = 'not-json';
				break;
			case 'scalar_items':
				$data['items'] = 'invalid';
				break;
			case 'finer_units':
				$data['items'][11] = 0.5;
				break;
			case 'unknown_state':
				$data['outbox']['submitted_customer']['state'] = 'retry_unknown';
				break;
			case 'lease_missing':
				$data['outbox']['submitted_customer']['state'] = 'sending';
				break;
			case 'too_many_history':
				$data['history'] = array_fill( 0, 3, $data['history'][0] );
				break;
			case 'bad_policy':
				$data['policy']['days'] = 0;
				break;
		}
		if ( ! in_array( $change, array( 'oversize', 'not_json' ), true ) ) {
			$row['payload'] = wp_json_encode( $data ); }
		wcj_returns_test_check(
			'bounded private payload failclosed ' . $change,
			wcj_returns_test_thrown(
				function () use ( $decoder, $row ) {
					$decoder->payload( $row );
				}
			),
			'payload_invalid'
		);
	}
	$service = wcj_returns_test_scenario();
	require $wcj_returns_test_root . '/includes/class-wcj-returns.php';
	wcj_returns_test_check( 'module exact enabled hooks no guest routes', array_keys( $GLOBALS['wcj_returns_test_hooks'] ), array( 'admin_menu', 'admin_post_wcj_returns_submit', 'admin_post_wcj_returns_transition', 'admin_post_wcj_returns_storage', 'admin_post_wcj_returns_dispatch', 'woocommerce_order_details_after_order_table', 'woocommerce_account_content' ) );
	$module = new WCJ_Returns();
	$module->menu();
	wcj_returns_test_check( 'merchant menu existing minimum capability', $GLOBALS['wcj_returns_test_menu_capability'], 'manage_woocommerce' );
	$GLOBALS['wcj_returns_test_hooks']                          = array();
	$GLOBALS['wcj_returns_test_options']['wcj_returns_enabled'] = 'no';
	new WCJ_Returns();
	wcj_returns_test_check( 'module disabled registers no runtime hooks', $GLOBALS['wcj_returns_test_hooks'], array() );
	$service = wcj_returns_test_scenario();
	wcj_returns_test_manager();
	$GLOBALS['wpdb'] = new WCJ_Returns_Test_DB();
	wcj_returns_test_check( 'default service unsupported DB returns bounded error not fatal', wcj_returns_test_code( ( new WCJ_Returns_Service() )->queue() ), 'returns_unavailable' );
	$GLOBALS['wcj_returns_test_actor'] = 0;
	ob_start();
	$module->customer( new WC_Order( 10 ) );
	$guest_output = ob_get_clean();
	wcj_returns_test_check( 'guest UI not enabled and no fallback proof', $guest_output, '' );
	$GLOBALS['wcj_returns_test_capable'] = false;
	wcj_returns_test_check(
		'merchant UI direct call capability denied',
		wcj_returns_test_thrown(
			function () use ( $module ) {
				$module->admin();
			}
		),
		'wp_die'
	);
	$modules_source = file_get_contents( $wcj_returns_test_root . '/includes/core/wcj-modules.php' );
	wcj_returns_test_check( 'actual existing bootstrap one returns row', substr_count( $modules_source, "'class-wcj-returns.php'" ), 1 );
	foreach ( array( 'normal', 'exclude', 'include_other', 'include_returns' ) as $role_policy ) {
		wcj_returns_test_scenario();
		$GLOBALS['wcj_returns_test_filters'] = array();
		if ( 'normal' !== $role_policy ) {
			$GLOBALS['wcj_returns_test_options']['wcj_modules_by_user_roles_enabled']       = 'yes';
			$GLOBALS['wcj_returns_test_options']['wcj_modules_by_user_roles_excl_customer'] = 'exclude' === $role_policy ? array( 'returns' ) : array();
			$GLOBALS['wcj_returns_test_options']['wcj_modules_by_user_roles_incl_customer'] = 'include_other' === $role_policy ? array( 'orders' ) : ( 'include_returns' === $role_policy ? array( 'returns' ) : array() );
		}
		require $wcj_returns_test_root . '/includes/mini-plugin/wcj-mini-plugin.php';
		$categories = require $wcj_returns_test_root . '/includes/admin/wcj-modules-cats.php';
		$retained   = apply_filters(
			'wcj_modules_loaded',
			array(
				'returns' => 'synthetic-return-module',
				'orders'  => 'synthetic-order-module',
			)
		);
		$allowed    = in_array( $role_policy, array( 'normal', 'include_returns' ), true );
		wcj_returns_test_check( 'actual category discovery respects role policy ' . $role_policy, in_array( 'returns', $categories['shipping_and_orders']['all_cat_ids'], true ), $allowed );
		wcj_returns_test_check( 'actual module loaded filter respects role policy ' . $role_policy, isset( $retained['returns'] ), $allowed );
	}
	$settings = require $wcj_returns_test_root . '/includes/settings/wcj-settings-returns.php';
	$defaults = array();
	foreach ( $settings as $setting ) {
		if ( isset( $setting['id'], $setting['default'] ) ) {
			$defaults[ $setting['id'] ] = $setting['default']; }
	}
	wcj_returns_test_check( 'settings default30 elapsed days', $defaults['wcj_returns_window_days'], '30' );
	wcj_returns_test_check( 'settings notifications explicit optin defaultno', $defaults['wcj_returns_notifications'], 'no' );

	wcj_returns_test_focus( 'all' );
	echo 'TOTAL ' . $GLOBALS['wcj_returns_test_count'] . ' actual-class deterministic assertions passed. Native DB/HTTP/concurrency/cache/browser qualification remains open.' . PHP_EOL;
}


/**
 * Select explicit offline constructor primitives for complete controller calls.
 * No source rewriting, session, notices, native connection or transport exists.
 *
 * @return WCJ_Returns_Service Actual service using deterministic primitives.
 */
function wcj_returns_test_controller_scenario() {
	$service = wcj_returns_test_scenario();
	$GLOBALS['wpdb'] = new WCJ_Returns_Test_DB();
	$GLOBALS['wcj_returns_test_handler_primitives'] = array(
		'store' => $GLOBALS['wcj_returns_test_test_store'],
		'facts' => $GLOBALS['wcj_returns_test_test_facts'],
		'clock' => function () { return $GLOBALS['wcj_returns_test_now']; },
	);
	$GLOBALS['wcj_returns_test_orders'] = array(
		10 => array( 'owner' => 7, 'status' => 'completed', 'completed' => 1999990000, 'items' => array( 11 ) ),
	);
	$GLOBALS['wcj_returns_test_items'] = array(
		11 => array( 'order' => 10, 'quantity' => '2', 'name' => 'Synthetic A', 'product' => 1 ),
	);
	$GLOBALS['wcj_returns_test_refund_ids'] = array();
	return $service;
}
/**
 * Capture full actual handler execution, stopping only at the redirect double.
 *
 * @param callable $operation Actual controller operation.
 * @return string Redirect, or explicit unexpected exception identity.
 */
function wcj_returns_test_redirect_call( $operation ) {
	$GLOBALS['wcj_returns_test_redirect'] = '';
	try {
		$operation();
		return 'no_redirect';
	} catch ( Throwable $error ) {
		return 'wcj_returns_test_redirect_stop' === $error->getMessage() ? $GLOBALS['wcj_returns_test_redirect'] : get_class( $error ) . ':' . $error->getMessage();
	}
}
/**
 * Capture escaped output from an actual render method.
 *
 * @param callable $operation Render operation.
 * @return string Captured HTML.
 */
function wcj_returns_test_output( $operation ) {
	ob_start();
	try {
		$operation();
		return ob_get_contents();
	} finally {
		ob_end_clean();
	}
}
/**
 * Focused accepted R1 regressions. Existing 347 assertions are retained.
 *
 * @param string $mode B1, R1, R2, R3, or all.
 */
function wcj_returns_test_focus( $mode ) {
	$module = new WCJ_Returns();
	if ( in_array( $mode, array( 'all', 'B1' ), true ) ) {
		$service = wcj_returns_test_controller_scenario();
		$_POST = array( 'order_id' => '10', 'quantities' => array( 11 => '1' ), 'reason' => 'Synthetic reason', 'token' => wcj_returns_test_token( 1 ), '_wpnonce' => wcj_returns_test_nonce( 'wcj_returns_submit_10' ), 'redirect' => 'https://attacker.invalid/private' );
		wcj_returns_test_check( 'B1 actual submit redirects after commit without notices or session', wcj_returns_test_redirect_call( array( $module, 'submit' ) ), 'https://store.invalid/account/view-order/10?wcj_returns_result=saved' );
		wcj_returns_test_check( 'B1 committed submit retained despite redirect interception', count( $GLOBALS['wcj_returns_test_test_store']->rows ), 1 );
		wcj_returns_test_check( 'B1 wc_add_notice deliberately undefined', function_exists( 'wc_add_notice' ), false );
		$_POST = array( 'request_id' => '1', 'revision' => '1', 'status' => 'cancelled', '_wpnonce' => wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
		wcj_returns_test_check( 'B1 actual owner transition returns corresponding own order', wcj_returns_test_redirect_call( array( $module, 'transition' ) ), 'https://store.invalid/account/view-order/10?wcj_returns_result=saved' );
		wcj_returns_test_check( 'B1 cancelled claim actually released', $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 0 );
		$_POST = array( 'order_id' => '10', 'quantities' => array( 11 => '3' ), 'reason' => 'Private synthetic reason', 'token' => wcj_returns_test_token( 2 ), '_wpnonce' => wcj_returns_test_nonce( 'wcj_returns_submit_10' ) );
		wcj_returns_test_check( 'B1 capacity failure finite result on own order', wcj_returns_test_redirect_call( array( $module, 'submit' ) ), 'https://store.invalid/account/view-order/10?wcj_returns_result=capacity_exceeded' );
		$GLOBALS['wcj_returns_test_orders'][10]['owner'] = 8;
		$GLOBALS['wcj_returns_test_test_facts']->value['owner'] = 8;
		wcj_returns_test_check( 'B1 changed owner fallback without order redirect disclosure', wcj_returns_test_redirect_call( array( $module, 'submit' ) ), 'https://store.invalid/account/orders?wcj_returns_result=unavailable' );
		$_POST['order_id'] = '999';
		$_POST['_wpnonce'] = wcj_returns_test_nonce( 'wcj_returns_submit_999' );
		wcj_returns_test_check( 'B1 unavailable order safe orders fallback', wcj_returns_test_redirect_call( array( $module, 'submit' ) ), 'https://store.invalid/account/orders?wcj_returns_result=unavailable' );
		$_POST['order_id'] = '10evil';
		wcj_returns_test_check( 'B1 invalid ID cannot be truncated into destination', wcj_returns_test_redirect_call( array( $module, 'submit' ) ), 'https://store.invalid/account/orders?wcj_returns_result=unavailable' );
		$finish = new ReflectionMethod( WCJ_Returns::class, 'finish' );
		wcj_returns_test_check( 'B1 raw exception text never reaches redirect', wcj_returns_test_redirect_call( function () use ( $finish, $module ) { $finish->invoke( $module, new WP_Error( 'raw-secret', 'private@example.invalid SQL secret' ) ); } ), 'https://store.invalid/account/orders?wcj_returns_result=unavailable' );
		$service = wcj_returns_test_controller_scenario();
		wcj_returns_test_submit_request( $service );
		wcj_returns_test_manager();
		$_POST = array( 'request_id' => '1', 'revision' => '1', 'status' => 'approved', '_wpnonce' => wcj_returns_test_nonce( 'wcj_returns_transition_1', 2 ) );
		wcj_returns_test_check( 'B1 actual merchant decision finite redirect with nonce tick2', wcj_returns_test_redirect_call( array( $module, 'transition' ) ), 'https://store.invalid/wp-admin/admin.php?page=wcj-returns&wcj_returns_result=saved' );
		wcj_returns_test_check( 'B1 actual merchant approval retained with claim unchanged', array( $GLOBALS['wcj_returns_test_test_store']->rows[1]['status'], $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'] ), array( 'approved', 1000000 ) );
		$GLOBALS['wcj_returns_test_actor'] = 7;
		$GLOBALS['wcj_returns_test_capable'] = false;

		foreach ( array( 'orders', 'view-order' ) as $endpoint ) {
			$GLOBALS['wcj_returns_test_endpoint'] = $endpoint;
			$_GET = array( 'wcj_returns_result' => 'saved' );
			wcj_returns_test_check( 'B1 fixed feedback on actual account destination ' . $endpoint, wcj_returns_test_output( array( $module, 'account_feedback' ) ), '<p>Your return request was saved. View the order for its current status.</p>' );
		}
		foreach ( array( 'private@example.invalid', array( 'saved' ), str_repeat( 'x', 5000 ) ) as $position => $input ) {
			$_GET = array( 'wcj_returns_result' => $input );
			wcj_returns_test_check( 'B1 unknown or non-scalar feedback ignored ' . $position, wcj_returns_test_output( array( $module, 'account_feedback' ) ), '' );
		}
		$_GET = array( 'wcj_returns_result' => 'commit_unknown' );
		wcj_returns_test_check( 'B1 uncertain outcome fixed feedback', strpos( wcj_returns_test_output( array( $module, 'account_feedback' ) ), 'outcome is unknown' ) !== false, true );
		$GLOBALS['wcj_returns_test_account_page'] = false;
		wcj_returns_test_check( 'B1 no feedback off account destination', wcj_returns_test_output( array( $module, 'account_feedback' ) ), '' );
		$GLOBALS['wcj_returns_test_account_page'] = true;
		$GLOBALS['wcj_returns_test_actor'] = 0;
		wcj_returns_test_check( 'B1 anonymous feedback absent', wcj_returns_test_output( array( $module, 'account_feedback' ) ), '' );
	}
	if ( in_array( $mode, array( 'all', 'R1' ), true ) ) {
		$service = wcj_returns_test_controller_scenario();
		$GLOBALS['wcj_returns_test_options']['wcj_returns_notifications'] = 'yes';
		$GLOBALS['wcj_returns_test_now'] = time() - 1;
		$GLOBALS['wcj_returns_test_test_facts']->value['completed'] = $GLOBALS['wcj_returns_test_now'] - DAY_IN_SECONDS;
		wcj_returns_test_submit_request( $service );
		wcj_returns_test_manager();
		$GLOBALS['wcj_returns_test_test_facts']->failure = 'order_unavailable';
		$before = serialize( array( $GLOBALS['wcj_returns_test_test_store']->rows, $GLOBALS['wcj_returns_test_test_store']->claims ) );
		$row = $service->queue()[0];
		wcj_returns_test_check( 'R1 queue distinct unavailable facts flag', $row['facts_unavailable'] ?? false, true );
		wcj_returns_test_check( 'R1 unavailable retains conservative needs review', $row['needs_review'], true );
		$html = wcj_returns_test_output( array( $module, 'admin' ) );
		wcj_returns_test_check( 'R1 unavailable suppresses all row decision and dispatch forms', substr_count( $html, '<form' ), 0 );
		wcj_returns_test_check( 'R1 unavailable explains blocked actions', strpos( $html, 'Order facts are unavailable' ) !== false, true );
		wcj_returns_test_check( 'R1 unavailable still displays bounded private history', strpos( $html, 'Synthetic reason' ) !== false, true );
		wcj_returns_test_check( 'R1 unavailable still displays notification state', strpos( $html, 'submitted_customer: pending' ) !== false, true );
		wcj_returns_test_check( 'R1 unavailable render does not release or alter claims', serialize( array( $GLOBALS['wcj_returns_test_test_store']->rows, $GLOBALS['wcj_returns_test_test_store']->claims ) ), $before );
		wcj_returns_test_check( 'R1 unavailable direct transition remains denied', wcj_returns_test_code( $service->transition( 1, 1, 'declined', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) ) ), 'returns_unavailable' );
		wcj_returns_test_check( 'R1 unavailable direct dispatch remains denied', wcj_returns_test_code( $service->dispatch( 1, 'submitted_customer', wcj_returns_test_nonce( 'wcj_returns_dispatch_1' ) ) ), 'returns_unavailable' );
		$GLOBALS['wcj_returns_test_test_facts']->failure = '';
		$GLOBALS['wcj_returns_test_test_facts']->value['hash'] = str_repeat( 'b', 64 );
		$row = $service->queue()[0];
		wcj_returns_test_check( 'R1 ordinary drift distinct from unavailable', array( $row['needs_review'], $row['facts_unavailable'] ), array( true, false ) );
		$html = wcj_returns_test_output( array( $module, 'admin' ) );
		wcj_returns_test_check( 'R1 ordinary drift retains due notification dispatch forms', substr_count( $html, 'value="wcj_returns_dispatch"' ), 2 );
		wcj_returns_test_check( 'R1 ordinary drift omits approval only', strpos( $html, 'value="approved"' ), false );
		wcj_returns_test_check( 'R1 ordinary drift retains decline and cancellation', array( strpos( $html, 'value="declined"' ) !== false, strpos( $html, 'value="cancelled"' ) !== false ), array( true, true ) );
		$_GET = array( 'wcj_returns_result' => 'revision_conflict' );
		wcj_returns_test_check( 'R1 merchant finite revision feedback', strpos( wcj_returns_test_output( array( $module, 'admin' ) ), 'request changed' ) !== false, true );
		$_GET = array( 'wcj_returns_result' => 'commit_unknown' );
		wcj_returns_test_check( 'R1 merchant unknown outcome explicit', strpos( wcj_returns_test_output( array( $module, 'admin' ) ), 'outcome is unknown' ) !== false, true );
		$_GET = array( 'wcj_returns_result' => 'private@example.invalid SQL' );
		wcj_returns_test_check( 'R1 merchant never echoes raw result input', strpos( wcj_returns_test_output( array( $module, 'admin' ) ), 'private@example.invalid' ), false );
		$GLOBALS['wcj_returns_test_handler_primitives'] = null;
		$_GET = array( 'wcj_returns_result' => 'commit_unknown' );
		wcj_returns_test_check( 'R1 unknown outcome remains visible when queue unavailable', strpos( wcj_returns_test_output( array( $module, 'admin' ) ), 'outcome is unknown' ) !== false, true );
	}
	if ( in_array( $mode, array( 'all', 'R2' ), true ) ) {
		foreach ( array( '', '0', '366', array( 30 ) ) as $position => $policy ) {
			$service = wcj_returns_test_controller_scenario();
			wcj_returns_test_submit_request( $service );
			$GLOBALS['wcj_returns_test_options']['wcj_returns_window_days'] = $policy;
			$view = $service->customer_view( 10 );
			wcj_returns_test_check( 'R2 invalid policy preserves own history ' . $position, is_array( $view ) ? count( $view['rows'] ) : wcj_returns_test_code( $view ), 1 );
			wcj_returns_test_check( 'R2 invalid policy blocks new intake only ' . $position, array( $view['policy_invalid'], $view['eligible'] ), array( true, false ) );
			$html = wcj_returns_test_output( function () use ( $module ) { $module->customer( new WC_Order( 10 ) ); } );
			wcj_returns_test_check( 'R2 invalid policy cancellation form visible ' . $position, strpos( $html, 'value="cancelled"' ) !== false, true );
			wcj_returns_test_check( 'R2 invalid policy no submit form ' . $position, strpos( $html, 'value="wcj_returns_submit"' ), false );
			wcj_returns_test_check( 'R2 invalid policy fixed explanation ' . $position, strpos( $html, 'store return policy is invalid' ) !== false, true );
			wcj_returns_test_check( 'R2 new request still rejected ' . $position, wcj_returns_test_code( wcj_returns_test_submit_request( $service, 2 ) ), 'policy_invalid' );
			$result = $service->transition( 1, 1, 'cancelled', wcj_returns_test_nonce( 'wcj_returns_transition_1' ) );
			wcj_returns_test_check( 'R2 cancellation works without current intake policy ' . $position, $result['status'], 'cancelled' );
			wcj_returns_test_check( 'R2 cancellation releases exact claim ' . $position, $GLOBALS['wcj_returns_test_test_store']->claims['10:11']['reserved'], 0 );
		}
		$service = wcj_returns_test_controller_scenario();
		$GLOBALS['wcj_returns_test_option_fault'] = 'wcj_returns_window_days';
		wcj_returns_test_check( 'R2 unrelated option fault not masked as invalid policy', wcj_returns_test_code( $service->customer_view( 10 ) ), 'returns_unavailable' );
		$GLOBALS['wcj_returns_test_option_fault'] = '';
		$GLOBALS['wcj_returns_test_actor'] = 8;
		wcj_returns_test_check( 'R2 invalid policy does not relax ownership', wcj_returns_test_code( $service->customer_view( 10 ) ), 'forbidden' );
	}
	if ( in_array( $mode, array( 'all', 'R3' ), true ) ) {
		foreach ( array( array( false, 'order-received' ), array( false, 'tracking' ), array( false, 'view-order' ), array( true, 'orders' ) ) as $position => $context ) {
			wcj_returns_test_controller_scenario();
			$GLOBALS['wcj_returns_test_account_page'] = $context[0];
			$GLOBALS['wcj_returns_test_endpoint'] = $context[1];
			$html = wcj_returns_test_output( function () use ( $module ) { $module->customer( new WC_Order( 10 ) ); } );
			wcj_returns_test_check( 'R3 no form or history outside actual account view-order ' . $position, $html, '' );
			wcj_returns_test_check( 'R3 non-view context denies before facts reads ' . $position, $GLOBALS['wcj_returns_test_test_facts']->loads, 0 );
		}
		$service = wcj_returns_test_controller_scenario();
		wcj_returns_test_submit_request( $service );
		$html = wcj_returns_test_output( function () use ( $module ) { $module->customer( new WC_Order( 10 ) ); } );
		wcj_returns_test_check( 'R3 actual account view-order retains own history', strpos( $html, 'Synthetic reason' ) !== false, true );
		wcj_returns_test_check( 'R3 actual account view-order retains intake form', strpos( $html, 'value="wcj_returns_submit"' ) !== false, true );
	}
}

wcj_returns_test_run();
