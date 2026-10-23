<?php
/**
 * Source-only HPOS invoice search regression. No WordPress, database or native requests.
 * Explicit doubles below model hook dispatch and the existing Classic query interface only.
 */
if ( 'cli' !== PHP_SAPI || ! isset( $argv[1], $argv[2] ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
define( 'ABSPATH', __DIR__ . '/' );
define( 'WC_VERSION', '11.1.0' );
$GLOBALS['hpos'] = true;
$GLOBALS['admin'] = true;
$GLOBALS['option'] = 'yes';
$GLOBALS['hooks'] = array();
function __( $text, $domain ) { return $text; }
function is_admin() { return $GLOBALS['admin']; }
function wcj_is_hpos_enabled() { return $GLOBALS['hpos']; }
function wcj_get_option( $key, $default = false ) {
	if ( 'wcj_invoicing_admin_search_by_invoice' !== $key ) { throw new RuntimeException( 'Unexpected option read' ); }
	return $GLOBALS['option'];
}
function add_action( $hook, $callback, $priority = 10, $argc = 1 ) { add_filter( $hook, $callback, $priority, $argc ); }
function add_filter( $hook, $callback, $priority = 10, $argc = 1 ) {
	$GLOBALS['hooks'][ $hook ][] = array( $priority, $callback, $argc );
}
function apply_filters( $hook, $value ) {
	$callbacks = $GLOBALS['hooks'][ $hook ] ?? array();
	usort( $callbacks, static function ( $a, $b ) { return $a[0] <=> $b[0]; } );
	foreach ( $callbacks as $row ) { $value = call_user_func( $row[1], $value ); }
	return $value;
}
class WCJ_Module {
	public $id;
	public $parent_id;
	public $short_desc;
	public function __construct( $type = 'module' ) {}
}
class Codex_Classic_Query {
	public $query;
	public $query_vars;
	public function __construct( $s = '0042' ) {
		$this->query = array( 's' => $s, 'post_type' => 'shop_order' );
		$this->query_vars = array( 'shop_order_search' => true, 'post__in' => array( 123 ) );
	}
	public function set( $key, $value ) { $this->query_vars[ $key ] = $value; }
}
$root = rtrim( $argv[1], '/\\' );
$base = rtrim( $argv[2], '/\\' );
$relative = '/includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-numbering.php';
$before = file_get_contents( $base . $relative );
$source = file_get_contents( $root . $relative );
$checks = array();
function check( $label, $actual, $expected ) {
	global $checks;
	$checks[] = array( 'case' => $label, 'pass' => $actual === $expected, 'actual' => $actual, 'expected' => $expected );
	if ( $actual !== $expected ) { throw new RuntimeException( 'Assertion failed: ' . $label ); }
}
$hook = 'woocommerce_order_list_table_prepare_items_query_args';
check( 'baseline lacks HPOS adapter hook (source proof, not baseline runtime)', strpos( $before, $hook ), false );
check( 'baseline contains Classic registration', false !== strpos( $before, "add_action( 'pre_get_posts', array( \$this, 'search_orders_by_invoice_number' ) );" ), true );
// Match the full method tail through the end of the file, avoiding doc/header edition differences.
$legacy_start = strpos( $before, 'public function search_orders_by_invoice_number( $query ) {' );
$current_legacy = strpos( $source, 'public function search_orders_by_invoice_number( $query ) {' );
check( 'Classic production method and file tail byte-identical', substr( $source, $current_legacy ), substr( $before, $legacy_start ) );
$module = require $root . $relative;
check( 'actual production class', get_class( $module ), 'WCJ_PDF_Invoicing_Numbering' );
check( 'enabled HPOS constructor adds one hook', count( $GLOBALS['hooks'][ $hook ] ?? array() ), 1 );
check( 'Classic registration retained in HPOS', count( $GLOBALS['hooks']['pre_get_posts'] ?? array() ), 1 );
foreach ( array( array( 'no', true, 0, 0 ), array( 'yes', false, 0, 1 ), array( 'yes', 1, 0, 1 ) ) as $index => $mode ) {
	$GLOBALS['option'] = $mode[0]; $GLOBALS['hpos'] = $mode[1]; $GLOBALS['hooks'] = array();
	new WCJ_PDF_Invoicing_Numbering();
	check( 'constructor native guard ' . $index, count( $GLOBALS['hooks'][ $hook ] ?? array() ), $mode[2] );
	check( 'constructor Classic guard ' . $index, count( $GLOBALS['hooks']['pre_get_posts'] ?? array() ), $mode[3] );
}
$GLOBALS['option'] = 'yes'; $GLOBALS['hpos'] = true; $GLOBALS['hooks'] = array();
new WCJ_PDF_Invoicing_Numbering();
$ordinary = array( 'type' => 'shop_order', 's' => '0042', 'status' => array( 'wc-processing' ), 'limit' => 20, 'page' => 2, 'paginate' => true, 'orderby' => 'date', 'order' => 'DESC', 'unknown_extension' => array( 'keep' => 1 ) );
$clause = array( 'key' => '_wcj_invoicing_invoice_number_id', 'value' => '0042', 'compare' => '=' );
$expected = $ordinary; $expected['s'] = ''; $expected['meta_query'] = array( 'relation' => 'AND', $clause );
check( 'actual registered hook changes ordinary invoice search', apply_filters( $hook, $ordinary ), $expected );
foreach ( array( '0', '42', '-4', '1.5', '2e3' ) as $search ) {
	$args = $ordinary; $args['s'] = $search;
	$wanted = $args; $wanted['s'] = ''; $wanted['meta_query'] = array( 'relation' => 'AND', array( 'key' => '_wcj_invoicing_invoice_number_id', 'value' => $search, 'compare' => '=' ) );
	check( 'numeric string preserved without cast ' . $search, $module->search_orders_by_invoice_number_hpos( $args ), $wanted );
}
$group = array( 'relation' => 'OR', 'existing_named' => array( 'key' => 'prior', 'value' => 'keep' ), array( 'relation' => 'AND', array( 'key' => 'a', 'value' => 1 ), array( 'key' => 'b', 'value' => 2 ) ) );
$args = $ordinary; $args['meta_query'] = $group; $args['orderby'] = 'existing_named'; $args['meta_key'] = 'shortcut'; $args['meta_value'] = 'untouched';
$wanted = $args; $wanted['s'] = ''; $wanted['meta_query'] = array( 'relation' => 'AND', $group, $clause );
check( 'complete OR group/named order/shortcut preserved', $module->search_orders_by_invoice_number_hpos( $args ), $wanted );
check( 'input group is not modified', $args['meta_query'], $group );
$args['orderby'] = 'meta_value_num'; $wanted['orderby'] = 'meta_value_num';
check( 'existing first meta clause stays first for implicit numeric order', $module->search_orders_by_invoice_number_hpos( $args ), $wanted );
foreach ( array( '', 'all' ) as $filter ) {
	$args = $ordinary; $args['search_filter'] = $filter; $wanted = $args; $wanted['s'] = ''; $wanted['meta_query'] = array( 'relation' => 'AND', $clause );
	check( 'default search filter ' . $filter, $module->search_orders_by_invoice_number_hpos( $args ), $wanted );
}
$negative = array( null, false, 'not-array', array(), array( 'type' => 'shop_order' ) );
foreach ( array( '', 'word', '42abc', array( '42' ), 42, 0, 1.5, true, null ) as $search ) { $args = $ordinary; $args['s'] = $search; $negative[] = $args; }
foreach ( array( 'shop_order_refund', 'shop_subscription', array( 'shop_order' ), null ) as $type ) { $args = $ordinary; $args['type'] = $type; $negative[] = $args; }
foreach ( array( 'order_id', 'customers', 'products', 'customer_email', 'transaction_id', array( 'all' ), false, null ) as $filter ) { $args = $ordinary; $args['search_filter'] = $filter; $negative[] = $args; }
foreach ( array( null, false, 'invalid', array( 'key' => 'atomic', 'value' => 'x' ), array( 'key' => null ), array( 'value' => null ) ) as $meta ) { $args = $ordinary; $args['meta_query'] = $meta; $negative[] = $args; }
foreach ( $negative as $i => $args ) { check( 'unsupported context untouched ' . $i, $module->search_orders_by_invoice_number_hpos( $args ), $args ); }
$GLOBALS['admin'] = false;
check( 'frontend unchanged', $module->search_orders_by_invoice_number_hpos( $ordinary ), $ordinary );
$GLOBALS['admin'] = true; $GLOBALS['hpos'] = false;
check( 'legacy storage unchanged', $module->search_orders_by_invoice_number_hpos( $ordinary ), $ordinary );
$GLOBALS['hpos'] = true;
$query = new Codex_Classic_Query();
$module->search_orders_by_invoice_number( $query );
check( 'Classic clears search', $query->query['s'], '' );
check( 'Classic sets same invoice meta key', $query->query_vars['meta_key'], '_wcj_invoicing_invoice_number_id' );
check( 'Classic preserves numeric string', $query->query_vars['meta_value'], '0042' );
check( 'Classic clears post IDs as before', $query->query_vars['post__in'], array() );
$query = new Codex_Classic_Query( 'ordinary text' ); $saved = clone $query;
$module->search_orders_by_invoice_number( $query );
check( 'Classic nonnumeric query untouched', array( $query->query, $query->query_vars ), array( $saved->query, $saved->query_vars ) );
check( 'WC support gate exact source', false !== strpos( $source, "version_compare( WC_VERSION, '8.2', '<' )" ), true );
check( 'no module SQL or persistence introduced', preg_match( '/\\$wpdb|update_meta_data|save\\(|update_post_meta|wp_delete|wc_get_orders\\(/', $source ), 0 );
echo json_encode( array( 'status' => 'PASS', 'assertions' => count( $checks ), 'checks' => $checks, 'source_sha256' => hash_file( 'sha256', $root . $relative ), 'baseline_sha256' => hash_file( 'sha256', $base . $relative ), 'limitations' => 'Explicit offline hook/module/query doubles; no native WordPress/WooCommerce, SQL, database, HPOS lifecycle, package or performance claim.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
