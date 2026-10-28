<?php
/**
 * Source-only HPOS invoice sorting regression, using actual complete classes.
 * Explicit WP hooks/options and DB prepare boundaries below are synthetic.
 * No WordPress bootstrap, database, invoices, network or native UI execution.
 *
 * @package Booster_Tests
 */

if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || empty( $argv[2] ) || empty( $argv[3] ) ) {
	exit( 2 );
}
$root     = realpath( $argv[1] );
$tier     = $argv[2];
$upstream = realpath( $argv[3] );
if ( ! $root || ! $upstream || ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) ) {
	exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' );
$mode = $argv[4] ?? 'normal';
if ( ! in_array( $mode, array( 'normal', 'version-old', 'version-floor', 'version-missing' ), true ) ) { exit( 2 ); }
if ( 'version-missing' !== $mode ) { define( 'WC_VERSION', 'version-old' === $mode ? '7.8.9' : ( 'version-floor' === $mode ? '7.9.0' : '11.1.0' ) ); }
$GLOBALS['codex_hooks']   = array();
$GLOBALS['codex_options'] = array();
$GLOBALS['codex_hpos']    = true;
$GLOBALS['codex_writes']  = array();
$GLOBALS['codex_rows']    = array();

function __( $text, $domain = '' ) { return $text; }
function add_filter( $name, $callback, $priority = 10, $accepted = 1 ) { $GLOBALS['codex_hooks'][ $name ][ $priority ][] = array( $callback, $accepted ); }
function add_action( $name, $callback, $priority = 10, $accepted = 1 ) { add_filter( $name, $callback, $priority, $accepted ); }
function apply_filters( $name, $value, ...$args ) {
	$hooks = $GLOBALS['codex_hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, $entry[1] ) );
		}
	}
	return $value;
}
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['codex_options'] ) ? $GLOBALS['codex_options'][ $key ] : $default; }
function wcj_get_option( $key, $default = false ) { return get_option( $key, $default ); }
function wcj_is_module_enabled( $id ) { return 'yes' === get_option( 'wcj_' . $id . '_enabled', 'no' ); }
function wcj_is_hpos_enabled() { return $GLOBALS['codex_hpos']; }
function current_filter() { return ''; }
function did_action( $name ) { return 0; }
function wp_parse_args( $custom, $defaults = array() ) { return array_merge( $defaults, $custom ); }
function update_option( $key, $value ) { $GLOBALS['codex_writes'][] = array( $key, $value ); $GLOBALS['codex_options'][ $key ] = $value; return true; }

/** No query execution is available, only a visible synthetic SQL formatter. */
class Codex_Invoice_Sort_DB {
	public function prepare( $format, ...$values ) {
		return preg_replace_callback( '/%s/', function () use ( &$values ) { return "'" . str_replace( "'", "''", (string) array_shift( $values ) ) . "'"; }, $format );
	}
}
$GLOBALS['wpdb'] = new Codex_Invoice_Sort_DB();
require $upstream . '/woocommerce/src/Internal/DataStores/Orders/OrdersTableQuery.php';
require $upstream . '/woocommerce/src/Internal/DataStores/Orders/OrdersTableMetaQuery.php';
require $root . '/includes/classes/class-wcj-module.php';
if ( 'free' !== $tier ) {
	require $root . '/includes/plus/class-wcj-plus-filters.php';
}
require $root . '/includes/functions/wcj-functions-invoicing.php';
require $root . '/includes/pdf-invoices/submodules/class-wcj-pdf-invoicing-display.php';

function codex_check( $name, $expected, $actual ) {
	$GLOBALS['codex_rows'][] = array( 'name' => $name, 'expected' => $expected, 'actual' => $actual, 'pass' => $expected === $actual );
}
function codex_reset( $hpos = true, $enabled = true ) {
	$GLOBALS['codex_hooks']   = array();
	$GLOBALS['codex_writes']  = array();
	$GLOBALS['codex_hpos']    = $hpos;
	$GLOBALS['codex_options'] = array(
		'wcj_pdf_invoicing_enabled' => $enabled ? 'yes' : 'no',
		'wcj_load_modules_on_init' => 'no',
		'wcj_invoicing_invoice_create_on' => array( 'woocommerce_order_status_completed' ),
		'wcj_invoicing_proforma_invoice_create_on' => array( 'manual' ),
		'wcj_invoicing_custom_doc_create_on' => array( 'manual' ),
	);
	if ( 'free' !== $GLOBALS['tier'] ) { new WCJ_Plus_Filters(); }
	return new WCJ_PDF_Invoicing_Display();
}
function codex_sort( $args ) { return apply_filters( 'woocommerce_shop_order_list_table_prepare_items_query_args', $args ); }
function codex_query( $args ) {
	$ref   = new ReflectionClass( 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableQuery' );
	$query = $ref->newInstanceWithoutConstructor();
	$ref->getProperty( 'args' )->setValue( $query, $args );
	$ref->getProperty( 'tables' )->setValue( $query, array( 'orders' => 'synthetic_orders', 'meta' => 'synthetic_meta' ) );
	// Run the actual remapping that generation01 omitted, then its complete metadata/orderby methods.
	$ref->getMethod( 'maybe_remap_args' )->invoke( $query );
	$mq = new Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableMetaQuery( $query );
	$ref->getProperty( 'meta_query' )->setValue( $query, $mq );
	$ref->getMethod( 'process_orderby' )->invoke( $query );
	$compiled_args = $ref->getProperty( 'args' )->getValue( $query );
	$clauses = array( 'fields' => 'sentinel fields', 'join' => 'sentinel join', 'where' => 'sentinel where', 'groupby' => 'sentinel group', 'orderby' => implode( ', ', $ref->getProperty( 'orderby' )->getValue( $query ) ), 'limits' => 'sentinel limits' );
	// Public hook dispatch is a synthetic boundary. Actual Woo uses this exact boolean filter intent.
	if ( ! (bool) ( $args['suppress_filters'] ?? false ) ) { $clauses = apply_filters( 'woocommerce_orders_table_query_clauses', $clauses, $query, $compiled_args ); }
	return array( $ref, $query, $mq, $clauses, $compiled_args );
}
function codex_finish() {
	$failed = array_filter( $GLOBALS['codex_rows'], function ( $row ) { return ! $row['pass']; } );
	echo json_encode( array( 'tier' => $GLOBALS['tier'], 'mode' => $GLOBALS['mode'], 'assertions' => count( $GLOBALS['codex_rows'] ), 'failures' => count( $failed ), 'source_only' => true, 'historical_numeric_values' => array( '1.2', '1.8', '1e3', '1000', '9223372036854775808' ), 'numeric_boundary' => 'Exact generated SQL +0 expression only, not native SQL value ordering.', 'checks' => $GLOBALS['codex_rows'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
	exit( empty( $failed ) ? 0 : 1 );
}
if ( 'normal' !== $mode ) {
	codex_reset();
	$expected = 'version-floor' === $mode;
	foreach ( array( 'woocommerce_shop_order_list_table_sortable_columns', 'woocommerce_shop_order_list_table_prepare_items_query_args', 'woocommerce_orders_table_query_clauses' ) as $hook ) { codex_check( 'version-hook-' . $hook, $expected, isset( $GLOBALS['codex_hooks'][ $hook ] ) ); }
	$input = array( 'orderby' => 'wcj_invoicing_invoice_number_id' );
	if ( $expected ) { codex_check( 'version-floor-numeric-expression', 'meta0.meta_value+0 DESC', codex_query( codex_sort( $input ) )[3]['orderby'] ); }
	else { codex_check( 'unsupported-version-no-adaptation', $input, codex_sort( $input ) ); }
	codex_finish();
}

$module = codex_reset();
codex_check( 'hpos-sort-hook', true, isset( $GLOBALS['codex_hooks']['woocommerce_shop_order_list_table_sortable_columns'] ) );
codex_check( 'hpos-query-hook', true, isset( $GLOBALS['codex_hooks']['woocommerce_shop_order_list_table_prepare_items_query_args'] ) );
$base_columns = array( 'order_date' => 'date', 'other_extension' => 'other_key' );
$expected_columns = $base_columns + array( 'invoice' => 'wcj_invoicing_invoice_number_id' );
if ( 'free' !== $tier ) {
	$expected_columns += array( 'proforma_invoice' => 'wcj_invoicing_proforma_invoice_number_id', 'custom_doc' => 'wcj_invoicing_custom_doc_number_id' );
}
codex_check( 'hpos-columns-tier-rights', $expected_columns, apply_filters( 'woocommerce_shop_order_list_table_sortable_columns', $base_columns ) );
codex_check( 'other-order-type-columns-unchanged', $base_columns, apply_filters( 'woocommerce_shop_order_refund_list_table_sortable_columns', $base_columns ) );

foreach ( array( 'ASC', 'DESC' ) as $direction ) {
	$input = array( 'type' => 'shop_order', 'status' => array( 'wc-completed' ), 's' => 'literal search', 'date_created' => '2026-01-01...2026-12-31', 'customer_id' => 73, 'limit' => 19, 'page' => 3, 'paginate' => true, 'orderby' => 'wcj_invoicing_invoice_number_id', 'order' => $direction );
	$expected = $input;
	$expected['orderby'] = 'wcj_invoice_number_sort';
	$expected['_wcj_hpos_invoice_sort'] = '_' . $input['orderby'];
	$expected['meta_query'] = array( 'relation' => 'AND', 'wcj_invoice_number_sort' => array( 'key' => '_wcj_invoicing_invoice_number_id', 'compare' => 'EXISTS', 'type' => 'CHAR' ) );
	codex_check( 'basic-sort-' . $direction, $expected, codex_sort( $input ) );
	foreach ( array( 'OR', 'AND' ) as $relation ) {
		$old = array( 'relation' => $relation, 'other_first' => array( 'key' => '_other_a', 'compare' => 'EXISTS' ), 'other_second' => array( 'key' => '_other_b', 'compare' => 'EXISTS' ) );
		$input['meta_query'] = $old;
		$expected = $input;
		$expected['orderby'] = 'wcj_invoice_number_sort';
		$expected['_wcj_hpos_invoice_sort'] = '_' . $input['orderby'];
		$expected['meta_query'] = array( 'relation' => 'AND', 'wcj_invoice_number_sort' => array( 'key' => '_wcj_invoicing_invoice_number_id', 'compare' => 'EXISTS', 'type' => 'CHAR' ), $old );
		$actual = codex_sort( $input );
		codex_check( 'preserve-nested-' . $direction . '-' . $relation, $expected, $actual );
		// Exercise actual pinned Woo metadata compiler even on baseline, with its actual returned args.
		list( $ref, $query, $meta_query, $clauses ) = codex_query( $actual );
		$sql = $meta_query->get_sql_clauses();
		codex_check( 'actual-Woo-invoice-first-' . $direction . '-' . $relation, true, str_contains( $sql['where'], "meta0.meta_key = '_wcj_invoicing_invoice_number_id'" ) );
		codex_check( 'actual-Woo-inner-relation-' . $direction . '-' . $relation, true, str_contains( $sql['where'], "(meta1.meta_key = '_other_a') " . $relation . " (meta2.meta_key = '_other_b')" ) );
		codex_check( 'actual-Woo-no-postmeta-' . $direction . '-' . $relation, false, str_contains( implode( ' ', $sql['join'] ), 'postmeta' ) );
		codex_check( 'actual-Woo-number-plus-zero-' . $direction . '-' . $relation, 'meta0.meta_value+0 ' . $direction, $clauses['orderby'] );
		codex_check( 'actual-Woo-direction-' . $direction . '-' . $relation, array( 'wcj_invoice_number_sort' => $direction ), $ref->getMethod( 'sanitize_order_orderby' )->invoke( $query, $actual['orderby'] ) );
	}
}
foreach ( array( 'packing_slip', 'proforma_invoice', 'custom_doc', 'unknown', 'invoice_number_id_spoof' ) as $id ) {
	$input = array( 'orderby' => 'wcj_invoicing_' . $id . '_number_id', 'order' => 'DESC' );
	$expected = $input;
	if ( 'free' !== $tier && in_array( $id, array( 'proforma_invoice', 'custom_doc' ), true ) ) {
		$expected['orderby'] = 'wcj_invoice_number_sort';
	$expected['_wcj_hpos_invoice_sort'] = '_' . $input['orderby'];
		$expected['meta_query'] = array( 'relation' => 'AND', 'wcj_invoice_number_sort' => array( 'key' => '_wcj_invoicing_' . $id . '_number_id', 'compare' => 'EXISTS', 'type' => 'CHAR' ) );
	}
	codex_check( 'allowed-type-' . $id, $expected, codex_sort( $input ) );
}
foreach ( array( null, false, 3, '', 'date', '_wcj_invoicing_invoice_number_id', array( 'wcj_invoicing_invoice_number_id' => 'ASC' ), "wcj_invoicing_invoice_number_id\0" ) as $i => $value ) {
	$input = array( 'orderby' => $value, 'marker' => 'unchanged' );
	codex_check( 'invalid-orderby-' . $i, $input, codex_sort( $input ) );
}
foreach ( array( null, false, 'bad', new stdClass() ) as $i => $value ) {
	$input = array( 'orderby' => 'wcj_invoicing_invoice_number_id', 'meta_query' => $value );
	codex_check( 'malformed-meta-' . $i, $input, codex_sort( $input ) );
}
foreach ( array( null, false, 'bad', new stdClass(), array() ) as $i => $input ) {
	codex_check( 'malformed-args-' . $i, $input, codex_sort( $input ) );
}
codex_check( 'modern-options-no-writes', array(), $GLOBALS['codex_writes'] );
codex_reset( true, false );
codex_check( 'disabled-no-hpos-hook', false, isset( $GLOBALS['codex_hooks']['woocommerce_shop_order_list_table_sortable_columns'] ) );
codex_check( 'disabled-no-query-hook', false, isset( $GLOBALS['codex_hooks']['woocommerce_shop_order_list_table_prepare_items_query_args'] ) );
$module = codex_reset( false );
codex_check( 'legacy-columns-preserved', $expected_columns, apply_filters( 'manage_edit-shop_order_sortable_columns', $base_columns ) );
codex_check( 'legacy-hpos-query-not-registered', false, isset( $GLOBALS['codex_hooks']['woocommerce_shop_order_list_table_prepare_items_query_args'] ) );

/** Minimal legacy query boundary, not a native WP_Query. */
class Codex_Invoice_Sort_Legacy_Query {
	public $query = array( 'post_type' => 'shop_order' );
	public $is_admin = true;
	public $main = true;
	public $args = array( 'orderby' => 'wcj_invoicing_invoice_number_id', 'order' => 'ASC' );
	public function get( $key ) { return $this->args[ $key ] ?? null; }
	public function set( $key, $value ) { $this->args[ $key ] = $value; }
	public function is_main_query() { return $this->main; }
}
$query = new Codex_Invoice_Sort_Legacy_Query();
$module->shop_order_pre_get_posts_order_by_column( $query );
codex_check( 'legacy-query-exact', array( 'orderby' => 'meta_value_num', 'order' => 'ASC', 'meta_key' => '_wcj_invoicing_invoice_number_id' ), $query->args );
foreach ( array( 'main', 'is_admin' ) as $key ) {
	$query = new Codex_Invoice_Sort_Legacy_Query(); $query->$key = false; $before = $query->args;
	$module->shop_order_pre_get_posts_order_by_column( $query );
	codex_check( 'legacy-scope-' . $key, $before, $query->args );
}
// Existing <=3.1.3 helper migration is inherited, not new invoice/history mutation.
$GLOBALS['codex_options']['wcj_invoicing_invoice_create_on'] = 'manual';
$module->shop_order_sortable_columns( array() );
codex_check( 'inherited-old-option-normalization', array( array( 'wcj_invoicing_invoice_create_on', array( 'manual' ) ) ), $GLOBALS['codex_writes'] );
// C-1: literal shortcuts both before and after the adapter, through actual maybe_remap_args.
$module = codex_reset();
$original = array( 'relation' => 'OR', 'other_a' => array( 'key' => '_other_a', 'compare' => 'EXISTS' ), 'other_b' => array( 'key' => '_other_b', 'compare' => 'EXISTS' ) );
foreach ( array( 'before', 'after' ) as $when ) {
	foreach ( array( false, true ) as $with_value ) {
		foreach ( array( 'ASC', 'DESC' ) as $direction ) {
			$input = array( 'type' => 'shop_order', 'orderby' => 'wcj_invoicing_invoice_number_id', 'order' => $direction, 'meta_query' => $original, 's' => 'preserve search', 'page' => 4 );
			$shortcut = array( 'meta_key' => '_shortcut' );
			if ( $with_value ) { $shortcut['meta_value'] = 'literal value'; }
			$actual = codex_sort( 'before' === $when ? $input + $shortcut : $input );
			if ( 'after' === $when ) { $actual += $shortcut; }
			list( $ref, $query, $mq, $pieces, $compiled ) = codex_query( $actual );
			$tag = $when . '-' . (int) $with_value . '-' . $direction;
			codex_check( 'shortcut-original-group-' . $tag, $original, $compiled['meta_query'][1][0] ?? null );
			codex_check( 'shortcut-invoice-not-first-alias-' . $tag, true, str_contains( $mq->get_sql_clauses()['where'], "meta1.meta_key = '_wcj_invoicing_invoice_number_id'" ) );
			codex_check( 'shortcut-filter-preserved-' . $tag, true, str_contains( $mq->get_sql_clauses()['where'], "meta0.meta_key = '_shortcut'" ) && ( ! $with_value || str_contains( $mq->get_sql_clauses()['where'], "meta0.meta_value = 'literal value'" ) ) );
			codex_check( 'shortcut-correct-number-expression-' . $tag, 'meta1.meta_value+0 ' . $direction, $pieces['orderby'] );
			codex_check( 'shortcut-other-clauses-unchanged-' . $tag, array( 'fields' => 'sentinel fields', 'join' => 'sentinel join', 'where' => 'sentinel where', 'groupby' => 'sentinel group', 'limits' => 'sentinel limits' ), array_diff_key( $pieces, array( 'orderby' => true ) ) );
		}
	}
}
// The adapter's name precedes a same-name clause in the original group; Woo suffixes the latter.
$collision = array( 'relation' => 'OR', 'wcj_invoice_number_sort' => array( 'key' => '_conflicting_name', 'compare' => 'EXISTS' ), 'other' => array( 'key' => '_other', 'compare' => 'EXISTS' ) );
$adapted = codex_sort( array( 'type' => 'shop_order', 'orderby' => 'wcj_invoicing_invoice_number_id', 'meta_query' => $collision, 'meta_key' => '_shortcut', 'order' => 'ASC' ) );
list( $ref, $query, $mq, $pieces, $compiled ) = codex_query( $adapted );
$flat = ( new ReflectionClass( $mq ) )->getProperty( 'flattened_clauses' )->getValue( $mq );
codex_check( 'collision-original-group-intact', $collision, $compiled['meta_query'][1][0] ?? null );
codex_check( 'collision-own-name-first', '_wcj_invoicing_invoice_number_id', $flat['wcj_invoice_number_sort']['key'] ?? null );
codex_check( 'collision-original-name-suffixed', '_conflicting_name', $flat['wcj_invoice_number_sort-1']['key'] ?? null );
codex_check( 'collision-original-filter-retained', true, str_contains( $mq->get_sql_clauses()['where'], "meta2.meta_key = '_conflicting_name'" ) );
codex_check( 'collision-numeric-expression', 'meta1.meta_value+0 ASC', $pieces['orderby'] );
foreach ( array( array( 'key' => '_atomic' ), array( 'value' => 'atomic' ), array( 'key' => '_atomic', 'value' => 'atomic', 'relation' => 'OR' ) ) as $i => $meta ) {
	$input = array( 'orderby' => 'wcj_invoicing_invoice_number_id', 'meta_query' => $meta );
	codex_check( 'atomic-top-level-unchanged-' . $i, $input, codex_sort( $input ) );
}
foreach ( array( true, 1, 'false', 'yes', array( 1 ), new stdClass(), false, 0, '0', '', array(), null ) as $i => $suppressed ) {
	$input = array( 'orderby' => 'wcj_invoicing_invoice_number_id', 'suppress_filters' => $suppressed );
	$expected = $input;
	if ( ! (bool) $suppressed ) {
		$expected['orderby'] = 'wcj_invoice_number_sort';
		$expected['_wcj_hpos_invoice_sort'] = '_wcj_invoicing_invoice_number_id';
		$expected['meta_query'] = array( 'relation' => 'AND', 'wcj_invoice_number_sort' => array( 'key' => '_wcj_invoicing_invoice_number_id', 'compare' => 'EXISTS', 'type' => 'CHAR' ) );
	}
	codex_check( 'suppression-Woo-boolean-' . $i, $expected, codex_sort( $input ) );
}
$valid = codex_sort( array( 'type' => 'shop_order', 'orderby' => 'wcj_invoicing_invoice_number_id', 'order' => 'ASC' ) );
list( $ref, $query, $mq, $pieces, $compiled ) = codex_query( $valid );
$raw = array( 'fields' => 'F', 'join' => 'J', 'where' => 'W', 'groupby' => 'G', 'orderby' => 'CAST(meta0.meta_value AS CHAR) ASC', 'limits' => 'L' );
foreach ( array( 'ASC', 'DESC' ) as $direction ) {
	$raw['orderby'] = 'CAST(meta0.meta_value AS CHAR) ' . $direction;
	codex_check( 'scoped-compiler-expression-' . $direction, 'meta0.meta_value+0 ' . $direction, apply_filters( 'woocommerce_orders_table_query_clauses', $raw, $query, $compiled )['orderby'] );
}
foreach ( array( 'CAST(meta0.meta_value AS SIGNED) ASC', 'CAST(meta0.meta_value AS CHAR) ASC, id DESC', 'CAST(meta0.meta_value AS CHAR) ASC; DROP TABLE orders', "CAST(meta0.meta_value AS CHAR) ASC\n", "CAST(meta0.meta_value AS CHAR) ASC\0", 'CAST(metaA.meta_value AS CHAR) ASC', 'CAST(meta٠.meta_value AS CHAR) ASC', 'CAST(meta0.meta_value AS CHAR) asc', 'CAST(meta0.meta_value AS CHAR) RANDOM', ' CAST(meta0.meta_value AS CHAR) ASC', 'CAST(`meta0`.meta_value AS CHAR) ASC', 'CAST(meta0.meta_value AS CHAR) ASC -- comment' ) as $i => $sql ) {
	$raw['orderby'] = $sql;
	codex_check( 'altered-SQL-untouched-' . $i, $raw, apply_filters( 'woocommerce_orders_table_query_clauses', $raw, $query, $compiled ) );
}
$raw['orderby'] = 'CAST(meta0.meta_value AS CHAR) ASC';
foreach ( array( null, false, 1, array(), "_wcj_invoicing_invoice_number_id' OR 1=1", '_wcj_invoicing_unknown_number_id' ) as $i => $marker ) {
	$changed = $compiled; $changed['_wcj_hpos_invoice_sort'] = $marker;
	codex_check( 'hostile-marker-untouched-' . $i, $raw, apply_filters( 'woocommerce_orders_table_query_clauses', $raw, $query, $changed ) );
}
foreach ( array( 'missing-marker', 'other-orderby', 'other-key', 'other-type', 'extra-alias', 'name-not-first', 'other-query-object', 'suppressed', 'other-order-type' ) as $case ) {
	$changed = $compiled; $object = $query;
	switch ( $case ) {
		case 'missing-marker': unset( $changed['_wcj_hpos_invoice_sort'] ); break;
		case 'other-orderby': $changed['orderby'] = 'date'; break;
		case 'other-key': $changed['meta_query']['wcj_invoice_number_sort']['key'] = '_wrong'; break;
		case 'other-type': $changed['meta_query']['wcj_invoice_number_sort']['type'] = 'SIGNED'; break;
		case 'extra-alias': $changed['meta_query']['wcj_invoice_number_sort']['alias'] = 'evil'; break;
		case 'name-not-first': $changed['meta_query'] = array( 'relation' => 'AND', array( 'key' => '_early' ), 'wcj_invoice_number_sort' => ( $changed['meta_query']['wcj_invoice_number_sort'] ?? array() ) ); break;
		case 'other-query-object': $object = new stdClass(); break;
		case 'suppressed': $changed['suppress_filters'] = 'false'; break;
		case 'other-order-type': $changed['type'] = 'shop_order_refund'; break;
	}
	codex_check( 'foreign-context-untouched-' . $case, $raw, apply_filters( 'woocommerce_orders_table_query_clauses', $raw, $object, $changed ) );
}
foreach ( array( '1.2', '1.8', '1e3', '1000', '9223372036854775808' ) as $literal ) {
	// Values are not coerced in PHP or queried here. The exact old SQL numeric operator is the invariant.
	codex_check( 'historical-plus-zero-expression-' . $literal, 'meta0.meta_value+0 ASC', codex_query( $valid )[3]['orderby'] );
}
codex_check( 'correction-options-no-writes', array(), $GLOBALS['codex_writes'] );
codex_reset( true, false );
codex_check( 'disabled-no-clauses-hook', false, isset( $GLOBALS['codex_hooks']['woocommerce_orders_table_query_clauses'] ) );
codex_reset( false );
codex_check( 'legacy-no-clauses-hook', false, isset( $GLOBALS['codex_hooks']['woocommerce_orders_table_query_clauses'] ) );
codex_finish();
