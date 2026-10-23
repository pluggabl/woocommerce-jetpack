<?php
/** Offline ownership/opt-in negatives. No WordPress, Woo database or native cell. */

$fixture_path = __DIR__ . '/integration/codex-8-4-order-health-screenshot-fixtures.php';
$events = array();
$records = array();
$options = array();
$environment = 'development';
$assertions = array();

function check_guard930( $name, $expected, $actual ) {
	$GLOBALS['assertions'][] = array( 'name' => $name, 'pass' => $expected === $actual, 'expected' => $expected, 'actual' => $actual );
}
function invoke_guard930( $action_args ) {
	$args = $action_args;
	try {
		require $GLOBALS['fixture_path'];
		return null;
	} catch ( RuntimeException $error ) {
		return $error->getMessage();
	}
}
function refuse_guard930( $name, $args, $code ) {
	$GLOBALS['events'] = array();
	check_guard930( $name . '-reason', $code, invoke_guard930( $args ) );
	check_guard930( $name . '-zero-mutation', array(), $GLOBALS['events'] );
}
function wp_get_environment_type() { return $GLOBALS['environment']; }
function add_filter( $name, $callback ) { $GLOBALS['events'][] = array( 'filter', $name, $callback ); }
function get_option( $name, $default = false ) { return $GLOBALS['options'][ $name ] ?? $default; }
function delete_option( $name ) { $GLOBALS['events'][] = array( 'delete_option', $name ); unset( $GLOBALS['options'][ $name ] ); }
function update_option( $name, $value, $autoload = null ) { $GLOBALS['events'][] = array( 'update_option', $name, $value, $autoload ); $GLOBALS['options'][ $name ] = $value; }
function wc_get_order( $id ) { return $GLOBALS['records'][ $id ] ?? false; }
function is_wp_error( $value ) { return false; }
class WP_CLI { public static function success( $message ) {} }
class WC_DateTime extends DateTime {}
class ScreenshotOrder930 {
	public $id;
	public $type;
	public $parent;
	public $marker;
	public $refunds = array();
	public function __construct( $id, $type = 'shop_order', $marker = '8.4', $parent = 0 ) { $this->id = $id; $this->type = $type; $this->marker = $marker; $this->parent = $parent; }
	public function get_id() { return $this->id; }
	public function get_type() { return $this->type; }
	public function get_parent_id() { return $this->parent; }
	public function get_meta( $key, $single = true, $context = 'view' ) { return $this->marker; }
	public function get_refunds() { return $this->refunds; }
	public function delete( $force ) { $GLOBALS['events'][] = array( 'delete', $this->id, $force ); unset( $GLOBALS['records'][ $this->id ] ); }
	public function update_meta_data( $key, $value ) { $this->marker = $value; $GLOBALS['events'][] = array( 'mark', $this->id, $key, $value ); }
	public function save() { $GLOBALS['events'][] = array( 'save', $this->id ); }
	public function set_status( $status ) {}
	public function set_total( $total ) {}
	public function set_date_created( $date ) {}
	public function set_date_paid( $date ) {}
}
function wc_create_order() {
	$id = 100 + count( $GLOBALS['records'] );
	$GLOBALS['events'][] = array( 'create_order', $id );
	return $GLOBALS['records'][ $id ] = new ScreenshotOrder930( $id, 'shop_order', '' );
}
function wc_create_refund( $args ) {
	$id = 100 + count( $GLOBALS['records'] );
	$GLOBALS['events'][] = array( 'create_refund', $id, $args );
	$refund = new ScreenshotOrder930( $id, 'shop_order_refund', '', $args['order_id'] );
	$GLOBALS['records'][ $args['order_id'] ]->refunds[] = $refund;
	return $GLOBALS['records'][ $id ] = $refund;
}

putenv( 'CODEX_QA_ISOLATED=1' );
putenv( 'WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES=1' );
refuse_guard930( 'no-abspath', array( 'delete' ), 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
define( 'ABSPATH', __DIR__ . '/' );
refuse_guard930( 'no-wp-cli', array( 'delete' ), 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
define( 'WP_CLI', true );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
putenv( 'CODEX_QA_ISOLATED' );
refuse_guard930( 'no-isolation', array( 'delete' ), 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
putenv( 'CODEX_QA_ISOLATED=1' );
putenv( 'WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES' );
refuse_guard930( 'no-opt-in', array( 'create' ), 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
putenv( 'WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES=1' );
$environment = 'production';
refuse_guard930( 'production', array( 'delete' ), 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
$environment = 'development';
refuse_guard930( 'missing-action', array(), 'SCREENSHOT_EXPLICIT_ACTION_REQUIRED' );
refuse_guard930( 'unknown-action', array( 'delete!' ), 'SCREENSHOT_EXPLICIT_ACTION_REQUIRED' );
refuse_guard930( 'extra-action', array( 'delete', 'create' ), 'SCREENSHOT_EXPLICIT_ACTION_REQUIRED' );
$records = array( 1 => new ScreenshotOrder930( 1 ), 2 => new ScreenshotOrder930( 2, 'shop_order', '' ) );
$options['wcj_order_health_screenshot_fixture_ids'] = array( 1, 2 );
refuse_guard930( 'mixed-owned-delete', array( 'delete' ), 'SCREENSHOT_ORDER_NOT_OWNED' );
refuse_guard930( 'mixed-owned-create', array( 'create' ), 'SCREENSHOT_ORDER_NOT_OWNED' );
$options['wcj_order_health_screenshot_fixture_ids'] = array( 1, 1 );
refuse_guard930( 'duplicate-ids', array( 'delete' ), 'SCREENSHOT_INVALID_OWNED_IDS' );
$options['wcj_order_health_screenshot_fixture_ids'] = array( '1' );
refuse_guard930( 'coerced-id', array( 'delete' ), 'SCREENSHOT_INVALID_OWNED_ID' );
$options['wcj_order_health_screenshot_fixture_ids'] = array( 999 );
refuse_guard930( 'missing-record', array( 'delete' ), 'SCREENSHOT_MISSING_OWNED_RECORD' );
$records[1]->marker = true;
$options['wcj_order_health_screenshot_fixture_ids'] = array( 1 );
refuse_guard930( 'typed-marker', array( 'delete' ), 'SCREENSHOT_ORDER_NOT_OWNED' );
$records[1]->marker = '8.4';
$refund = new ScreenshotOrder930( 3, 'shop_order_refund', '', 1 );
$records[3] = $refund;
$records[1]->refunds = array( $refund );
refuse_guard930( 'unselected-cascade', array( 'delete' ), 'SCREENSHOT_UNSELECTED_REFUND' );
$options['wcj_order_health_screenshot_fixture_ids'] = array( 3 );
refuse_guard930( 'unselected-parent', array( 'delete' ), 'SCREENSHOT_REFUND_PARENT_NOT_OWNED' );
$options['wcj_order_health_screenshot_fixture_ids'] = array( 1, 3 );
$refund->marker = 'foreign';
refuse_guard930( 'foreign-refund-marker', array( 'delete' ), 'SCREENSHOT_REFUND_MARKER_MISMATCH' );
$refund->marker = '';
$events = array();
check_guard930( 'legacy-refund-positive', null, invoke_guard930( array( 'delete' ) ) );
check_guard930( 'children-before-parent', array( array( 'delete', 3, true ), array( 'delete', 1, true ) ), array_values( array_filter( $events, static function ( $event ) { return 'delete' === $event[0]; } ) ) );
check_guard930( 'foreign-record-not-deleted', true, isset( $records[2] ) );
$records = array();
$options = array();
$events = array();
check_guard930( 'explicit-create-positive', null, invoke_guard930( array( 'create' ) ) );
check_guard930( 'six-fixtures', 6, count( $options['wcj_order_health_screenshot_fixture_ids'] ) );
check_guard930( 'all-created-directly-marked', array_fill( 0, 6, '8.4' ), array_values( array_map( static function ( $order ) { return $order->marker; }, $records ) ) );
$events = array();
check_guard930( 'created-records-owned-delete', null, invoke_guard930( array( 'delete' ) ) );
check_guard930( 'all-created-deleted', array(), $records );
$failed = array_values( array_filter( $assertions, static function ( $check ) { return ! $check['pass']; } ) );
echo json_encode( array( 'task' => 930, 'assertions' => count( $assertions ), 'failed' => count( $failed ), 'checks' => $assertions, 'native' => 0 ) ) . PHP_EOL;
exit( count( $failed ) ? 1 : 0 );
