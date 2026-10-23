<?php
/**
 * Offline HPOS report dates using complete report classes and pinned date APIs.
 *
 * Usage: php -n fixture.php plugin-root pinned-date-reference-directory
 * Queries, products, options and hooks are synthetic. No WP/Woo boot or DB.
 * The query double deliberately does not implement date predicates.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) ) {
	exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required offline WP constant.
define( 'WCJ_IS_WC_VERSION_BELOW_3', false );

/**
 * Supply a typed synthetic option.
 *
 * @param mixed $key Synthetic fixture argument.
 * @param mixed $fallback Synthetic fixture argument.
 * @return mixed
 */
function get_option( $key, $fallback = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return $GLOBALS['wcj_report_options'][ $key ] ?? $fallback;
}

/**
 * Supply the existing Booster option API.
 *
 * @param mixed $key Synthetic fixture argument.
 * @param mixed $fallback Synthetic fixture argument.
 * @return mixed
 */
function wcj_get_option( $key, $fallback = false ) {
	return get_option( $key, $fallback );
}

/**
 * Supply synthetic post objects to the actual pinned WP date functions.
 *
 * @param mixed $post Synthetic fixture argument.
 * @return mixed
 */
function get_post( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	if ( is_object( $post ) ) {
		return $post;
	}
	++$GLOBALS['wcj_report_post_reads'];
	return $GLOBALS['wcj_report_posts'][ $post ] ?? false;
}

/**
 * Keep hooks read-only and allow one literal product-filter case.
 *
 * @param mixed $tag Synthetic fixture argument.
 * @param mixed $value Synthetic fixture argument.
 * @param mixed ...$args Synthetic fixture argument.
 * @return mixed
 */
function apply_filters( $tag, $value, ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	if ( 'wcj_reports_products_sales_check_product' === $tag && isset( $args[0] ) && 13 === $args[0] && ! empty( $GLOBALS['wcj_report_filter13'] ) ) {
		return false;
	}
	return $value;
}

/**
 * Reject accidental current-clock fallback in a date API.
 *
 * @param mixed $type Synthetic fixture argument.
 * @param mixed $gmt Synthetic fixture argument.
 * @return mixed
 * @throws RuntimeException On an unexpected current-clock fallback.
 */
function current_time( $type, $gmt = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter -- Exact offline WP API signature; every unexpected invocation fails.
	throw new RuntimeException( 'Unexpected current-clock fallback.' );
}

/**
 * Supply two populated synthetic query pages, then an empty page.
 *
 * @param mixed $args Synthetic fixture argument.
 * @return mixed
 */
function wc_get_orders( $args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline Woo API double.
	$GLOBALS['wcj_report_queries'][] = $args;
	$page                            = (int) ( $args['offset'] / $args['posts_per_page'] );
	return $GLOBALS['wcj_report_pages'][ $page ] ?? array();
}

/**
 * Supply the exact synthetic current order.
 *
 * @param mixed $id Synthetic fixture argument.
 * @return mixed
 */
function wc_get_order( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline Woo API double.
	return $GLOBALS['wcj_report_orders'][ $id ];
}

/** Minimal legacy query environment, without executing a database query. */
class WP_Query { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace -- Exact offline WP API double; only its naming justification follows the brace.
	/**
	 * Synthetic post identifiers.
	 *
	 * @var array
	 */
	public $posts;
	/**
	 * Supply the configured pages as post IDs.
	 *
	 * @param mixed $args Synthetic fixture argument.
	 * @return mixed
	 */
	public function __construct( $args ) {
		$this->posts = array_map(
			static function ( $order ) {
				return $order->get_id();
			},
			wc_get_orders( $args )
		);
	}
	/**
	 * Report whether this synthetic page contains posts.
	 *
	 * @return mixed
	 */
	public function have_posts() {
		return ! empty( $this->posts );
	}
}

/** Read-only synthetic order object with the actual pinned WC_DateTime value. */
class WCJ_Codex_Report_Order {
	/**
	 * Synthetic ID.
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Current order creation date.
	 *
	 * @var WC_DateTime|null
	 */
	private $date;
	/**
	 * Synthetic line items.
	 *
	 * @var array
	 */
	private $items;
	/**
	 * Assign the three literal inputs.
	 *
	 * @param mixed $id Synthetic fixture argument.
	 * @param mixed $date Synthetic fixture argument.
	 * @param mixed $items Synthetic fixture argument.
	 * @return mixed
	 */
	public function __construct( $id, $date, $items ) {
		$this->id    = $id;
		$this->date  = $date;
		$this->items = $items;
	}
	/**
	 * Get the ID.
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Count each date read and return the actual date object.
	 *
	 * @return mixed
	 */
	public function get_date_created() {
		$GLOBALS['wcj_report_date_reads'][ $this->id ] = ( $GLOBALS['wcj_report_date_reads'][ $this->id ] ?? 0 ) + 1;
		return $this->date;
	}
	/**
	 * Return only the configured synthetic items.
	 *
	 * @return mixed
	 */
	public function get_items() {
		return $this->items;
	}
	/**
	 * Supply the literal Woo status.
	 *
	 * @return mixed
	 */
	public function get_status() {
		return 'completed';
	}
}

/** Supply a synthetic product title without querying Woo storage. */
class WCJ_Codex_Report_Product {
	/**
	 * Literal title.
	 *
	 * @var string
	 */
	private $title;
	/**
	 * Store the literal title.
	 *
	 * @param mixed $title Synthetic fixture argument.
	 * @return mixed
	 */
	public function __construct( $title ) {
		$this->title = $title;
	}
	/**
	 * Return the literal title.
	 *
	 * @return mixed
	 */
	public function get_title() {
		return $this->title;
	}
}

/** Supply the precise class-name branch used by the unchanged report. */
class WC_Product_Variation extends WCJ_Codex_Report_Product { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace -- Exact offline Woo API class-name double; only its naming justification follows the brace.
	/**
	 * Return the configured synthetic parent ID.
	 *
	 * @return mixed
	 */
	public function get_parent_id() {
		return 12;
	}
}

/**
 * Return a known synthetic product or the deliberate deleted product.
 *
 * @param mixed $id Synthetic fixture argument.
 * @return mixed
 */
function wc_get_product( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline Woo API double.
	return $GLOBALS['wcj_report_products'][ $id ] ?? false;
}

/**
 * Supply a literal per-unit purchase cost.
 *
 * @param mixed $id Synthetic fixture argument.
 * @return mixed
 */
function wc_get_product_purchase_price( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Exact offline existing API, all synthetic products have cost2.
	return 2;
}

/**
 * Supply the unchanged synthetic variation description.
 *
 * @param mixed $product Synthetic fixture argument.
 * @param mixed $flat Synthetic fixture argument.
 * @return mixed
 */
function wcj_get_product_formatted_variation( $product, $flat ) {
	return $product->get_parent_id() . ( $flat ? ' variation' : '' );
}

/**
 * Translate without a locale file.
 *
 * @param mixed $text Synthetic fixture argument.
 * @param mixed $domain Synthetic fixture argument.
 * @return mixed
 */
function __( $text, $domain = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Exact offline translation API signature.
	return $text;
}

/**
 * Return the literal legacy status.
 *
 * @param mixed $id Synthetic fixture argument.
 * @return mixed
 */
function get_post_status( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return isset( $GLOBALS['wcj_report_orders'][ $id ] ) ? 'wc-completed' : false;
}

/**
 * Strip synthetic tags for the unchanged title sorter.
 *
 * @param mixed $text Synthetic fixture argument.
 * @return mixed
 */
function wp_strip_all_tags( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Exact offline wp_strip_all_tags double for synthetic titles.
}

/**
 * Format synthetic report money, including preserved malformed footer input.
 *
 * @param mixed $amount Synthetic fixture argument.
 * @return mixed
 */
function wc_price( $amount ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline Woo API double.
	return '$' . number_format( (float) $amount, 2, '.', '' );
}

/**
 * Capture the complete actual table rows, without claiming HTML rendering.
 *
 * @param mixed $data Synthetic fixture argument.
 * @param mixed $options Synthetic fixture argument.
 * @return mixed
 */
function wcj_get_table_html( $data, $options ) {
	$GLOBALS['wcj_report_tables'][] = array( $data, $options );
	return '<synthetic-table>' . wp_json_encode( $data ) . '</synthetic-table>';
}

/**
 * Return the fixed synthetic settings URL.
 *
 * @param mixed $path Synthetic fixture argument.
 * @return mixed
 */
function admin_url( $path ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return 'https://example.test/wp-admin/' . $path;
}

/**
 * Return the fixed Booster settings route.
 *
 * @return mixed
 */
function wcj_admin_tab_url() {
	return 'admin.php?page=wc-settings';
}

/**
 * Preserve synthetic URL arguments without a real request.
 *
 * @param mixed $key Synthetic fixture argument.
 * @param mixed $value Synthetic fixture argument.
 * @return mixed
 */
function add_query_arg( $key, $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return 'https://example.test/reports?' . $key . '=' . $value;
}

/**
 * Keep a synthetic URL unchanged.
 *
 * @param mixed $value Synthetic fixture argument.
 * @return mixed
 */
function esc_url( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return $value;
}

/**
 * Escape synthetic report attributes without a WordPress environment.
 *
 * @param mixed $value Synthetic fixture argument.
 * @return mixed
 */
function esc_attr( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * Encode fixture values without WP bootstrap.
 *
 * @param mixed $data Synthetic fixture argument.
 * @param mixed $flags Synthetic fixture argument.
 * @return mixed
 */
function wp_json_encode( $data, $flags = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WP API double.
	return json_encode( $data, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native serializer within the offline API double.
}

/**
 * Return fixed daily columns sufficient to inspect every total/date row.
 *
 * @return mixed
 */
function wcj_get_product_sales_daily_report_columns() {
	return array_combine( array( 'date', 'item_quantity', 'sum', 'profit', 'last_sale' ), array( 'Date', 'Qty', 'Sum', 'Profit', 'Last' ) );
}

/**
 * Capture an exact typed assertion.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $actual Synthetic fixture argument.
 * @param mixed $expected Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_check( $name, $actual, $expected ) {
	$GLOBALS['wcj_report_checks'][] = array(
		'name'     => $name,
		'actual'   => $actual,
		'expected' => $expected,
		'pass'     => $actual === $expected,
	);
}

/**
 * Build a literal line item.
 *
 * @param mixed $product Synthetic fixture argument.
 * @param mixed $variation Synthetic fixture argument.
 * @param mixed $qty Synthetic fixture argument.
 * @param mixed $sum Synthetic fixture argument.
 * @param mixed $tax Synthetic fixture argument.
 * @param mixed $name Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_item( $product = 11, $variation = 0, $qty = 2, $sum = 10, $tax = 1, $name = 'Alpha' ) {
	return array(
		'product_id'   => $product,
		'variation_id' => $variation,
		'qty'          => $qty,
		'line_total'   => $sum,
		'line_tax'     => $tax,
		'name'         => $name,
	);
}

/**
 * Reset per-method counters without changing inputs.
 *
 * @return mixed
 */
function wcj_codex_reset() {
	$GLOBALS['wcj_report_post_reads'] = 0;
	$GLOBALS['wcj_report_date_reads'] = array();
	$GLOBALS['wcj_report_queries']    = array();
	$GLOBALS['wcj_report_tables']     = array();
}

/**
 * Build a stock collector without invoking unrelated constructor work.
 *
 * @param mixed $variation Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_stock( $variation = false ) {
	$stock                           = ( new ReflectionClass( 'WCJ_Reports_Stock' ) )->newInstanceWithoutConstructor();
	$stock->range_days               = 30;
	$stock->product_type             = $variation ? 'variation' : 'product';
	$stock->include_deleted_products = false;
	return $stock;
}

/**
 * Supply complete expected HPOS query arguments for each original method.
 *
 * @param mixed $method Synthetic fixture argument.
 * @param mixed $offset Synthetic fixture argument.
 * @param mixed $year Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_query( $method, $offset, $year = '2026' ) {
	$query = array(
		'type'           => 'shop_order',
		'status'         => 'wc-completed',
		'posts_per_page' => 'stock' === $method ? 1024 : 512,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'offset'         => $offset,
		'fields'         => 'ids',
	);
	if ( 'daily' === $method ) {
		$query['status']     = 'any';
		$query['date_query'] = array(
			array(
				'after'     => '2025-01-01',
				'before'    => '2026-12-31',
				'inclusive' => true,
			),
		);
	} elseif ( 'sales' === $method ) {
		$query['date_query'] = array( array( 'year' => $year ) );
	} else {
		$query['date_query'] = array(
			array(
				'column' => 'post_date_gmt',
				'after'  => '30 days ago',
			),
		);
	}
	return $query;
}

/**
 * Compare query key/value types irrespective of original declaration order.
 *
 * @param mixed $label Synthetic fixture argument.
 * @param mixed $method Synthetic fixture argument.
 * @param mixed $year Synthetic fixture argument.
 * @param mixed $pages Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_queries( $label, $method, $year, $pages ) {
	$expected = array();
	for ( $page = 0; $page <= $pages; $page++ ) {
		$query = wcj_codex_query( $method, $page * ( 'stock' === $method ? 1024 : 512 ), $year );
		ksort( $query );
		$expected[] = $query;
	}
	$actual = $GLOBALS['wcj_report_queries'];
	foreach ( $actual as &$query ) {
		ksort( $query );
	}
	unset( $query );
	wcj_codex_check( $label . ' complete query args', $actual, $expected );
}

/**
 * Get the observable full monthly table using the actual report method.
 *
 * @param mixed $year Synthetic fixture argument.
 * @param mixed $title Synthetic fixture argument.
 * @param mixed $legacy Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_sales( $year, $title = '', $legacy = false ) {
	$sales                = new WCJ_Reports_Sales();
	$sales->year          = $year;
	$sales->product_title = $title;
	$html                 = $legacy ? $sales->get_products_sales() : $sales->get_products_sales_hpos();
	return array( $GLOBALS['wcj_report_tables'][0][0], $html );
}

/**
 * Run literal date vectors and actual method/renderer cases.
 *
 * @param mixed $root Synthetic fixture argument.
 * @param mixed $references Synthetic fixture argument.
 * @return mixed
 * @throws RuntimeException On a pinned reference mismatch.
 */
function wcj_codex_main( $root, $references ) {
	foreach ( array(
		'class-wc-datetime.php'            => '1fa32f308786fa66284c673984e33602dae1d642255f923b16551e874828d6df',
		'codex-primary-date-functions.php' => 'c509df2d45e491342cddd549befca55d6e5b8708967ea386385e3ed32d80f530',
	) as $name => $hash ) {
		if ( hash_file( 'sha256', $references . '/' . $name ) !== $hash ) {
			throw new RuntimeException( 'Pinned primary date reference mismatch.' );
		}
		require $references . '/' . $name;
	}
	foreach ( array( 'product-sales-daily', 'sales', 'stock' ) as $name ) {
		require $root . '/includes/reports/class-wcj-reports-' . $name . '.php';
	}
	$golden                         = json_decode( file_get_contents( $root . '/tests/fixtures/codex-9-0-hpos-report-dates.json' ), true, 512, JSON_THROW_ON_ERROR ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read immutable local test goldens only.
	$GLOBALS['wp_locale']           = (object) array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required offline locale environment for actual pinned WP date functions.
		'month'   => array(),
		'weekday' => array(),
	); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Exact locale global consumed by pinned WP date functions.
	$GLOBALS['wcj_report_checks']   = array();
	$GLOBALS['wcj_report_products'] = array(
		11 => new WCJ_Codex_Report_Product( 'Alpha' ),
		12 => new WCJ_Codex_Report_Product( 'Beta' ),
		22 => new WC_Product_Variation( 'Beta variation' ),
	);
	$_GET                           = array();
	foreach ( $golden['cases'] as $case ) {
		$GLOBALS['wcj_report_options'] = array(
			'timezone_string' => isset( $case['fixed_offset'] ) ? '' : $case['zone'],
			'gmt_offset'      => isset( $case['fixed_offset'] ) ? 5.75 : 0,
			'date_format'     => 'Y-m-d',
		);
		$date                          = null === $case['input'] ? null : new WC_DateTime( $case['input'], new DateTimeZone( $case['zone'] ) );
		if ( isset( $case['fixed_offset'] ) ) {
			$date->set_utc_offset( $case['fixed_offset'] );
		}
		if ( null !== $date ) {
			wcj_codex_check( $case['name'] . ' actual WC epoch', $date->getTimestamp(), $case['epoch'] );
			wcj_codex_check( $case['name'] . ' actual WC offset', $date->getOffset(), $case['offset'] );
			wcj_codex_check( $case['name'] . ' actual WC offset timestamp', $date->getOffsetTimestamp(), $case['offset_timestamp'] );
			$GLOBALS['wcj_report_posts'] = array( 901 => (object) array( 'post_date' => $case['full'] ) );
			wcj_codex_reset();
			wcj_codex_check( $case['name'] . ' actual WP local U parity', get_the_time( 'U', 901 ), $case['offset_timestamp'] );
			wcj_codex_check( $case['name'] . ' actual WP date parity', get_the_date( 'Y-m-d', 901 ), $case['day'] );
		}
		foreach ( array( 'bogus', 'missing' ) as $post_mode ) {
			$label                        = $case['name'] . '/' . $post_mode;
			$GLOBALS['wcj_report_posts']  = 'bogus' === $post_mode ? array( 901 => (object) array( 'post_date' => '1999-04-05 06:07:08' ) ) : array();
			$order                        = new WCJ_Codex_Report_Order( 901, $date, array( wcj_codex_item() ) );
			$GLOBALS['wcj_report_orders'] = array( 901 => $order );
			$GLOBALS['wcj_report_pages']  = array( array( $order ) );
			wcj_codex_reset();
			$daily                = new WCJ_Reports_Product_Sales_Daily();
			$daily->start_date    = '2025-01-01';
			$daily->end_date      = '2026-12-31';
			$daily->product_title = '';
			$daily->get_report_data_hpos();
			wcj_codex_check( $label . ' daily exact date key', array_keys( $daily->sales_by_day ), array( $case['day'] ) );
			wcj_codex_check( $label . ' daily latest display', $daily->last_sale_data[11]['date'], $case['full'] );
			wcj_codex_check(
				$label . ' daily quantity and money retained',
				array_values( $daily->total_sales_by_day ),
				array(
					array(
						'qty' => 2,
						'sum' => 10,
					),
				)
			);
			wcj_codex_check( $label . ' daily order count', $daily->total_orders, 1 );
			wcj_codex_check( $label . ' daily no post reads', $GLOBALS['wcj_report_post_reads'], 0 );
			wcj_codex_check( $label . ' daily one date read', $GLOBALS['wcj_report_date_reads'], array( 901 => 1 ) );
			wcj_codex_queries( $label, 'daily', '2026', 1 );
			$daily->output_report_results();
			wcj_codex_check( $label . ' daily rendered unknown totals', $GLOBALS['wcj_report_tables'][0][0][2], array( '', '<strong>Total: 2</strong>', '<strong>Total: $10.00</strong>', '<strong>Total: $6.00</strong>', '' ) );
			$hpos_daily_table = $GLOBALS['wcj_report_tables'][0][0];

			wcj_codex_reset();
			$year    = '' !== $case['year'] ? $case['year'] : '2026';
			$monthly = wcj_codex_sales( $year );
			wcj_codex_check( $label . ' monthly latest display', $monthly[0][1][2], $case['full'] );
			wcj_codex_check( $label . ' monthly product quantities and money retained', array( $monthly[0][1][3], $monthly[0][2][3] ), array( '<strong>2</strong>', '<strong>$10.00</strong>' ) );
			wcj_codex_check( $label . ' monthly calendar header', $monthly[0][0][4] ?? null, '' !== $case['year'] ? $case['year'] . '.12' : null );
			$column = 4 + 12 - (int) $case['month'];
			wcj_codex_check( $label . ' monthly local calendar bucket', '' !== $case['month'] ? ( $monthly[0][1][ $column ] ?? null ) : count( $monthly[0][1] ), '' !== $case['month'] ? '<span style="color:green;">2</span>' : 4 );
			wcj_codex_check( $label . ' monthly footer unknown year excluded', $monthly[0][3][3], '' !== $case['year'] ? '<strong>2</strong>' : '<strong>0</strong>' );
			wcj_codex_check( $label . ' monthly no post reads', $GLOBALS['wcj_report_post_reads'], 0 );
			wcj_codex_check( $label . ' monthly one date read', $GLOBALS['wcj_report_date_reads'], array( 901 => 1 ) );
			wcj_codex_queries( $label, 'sales', $year, 1 );

			wcj_codex_reset();
			$stock = wcj_codex_stock();
			$info  = array(
				11 => array(
					'last_sale'       => 0,
					'sales_in_period' => array( 30 => 0 ),
				),
			);
			$stock->gather_orders_data_hpos( $info );
			wcj_codex_check( $label . ' stock local offset timestamp or integer0', $info[11]['last_sale'], $case['offset_timestamp'] );
			wcj_codex_check( $label . ' stock quantity retained', $info[11]['sales_in_period'][30], 2 );
			wcj_codex_check( $label . ' stock no post reads', $GLOBALS['wcj_report_post_reads'], 0 );
			wcj_codex_check( $label . ' stock one date read', $GLOBALS['wcj_report_date_reads'], array( 901 => 1 ) );
			wcj_codex_queries( $label, 'stock', '2026', 1 );
			if ( null !== $date ) {
				$GLOBALS['wcj_report_posts'] = array( 901 => (object) array( 'post_date' => $case['full'] ) );
				wcj_codex_reset();
				$legacy_daily                = new WCJ_Reports_Product_Sales_Daily();
				$legacy_daily->start_date    = '2025-01-01';
				$legacy_daily->end_date      = '2026-12-31';
				$legacy_daily->product_title = '';
				$legacy_daily->get_report_data();
				$legacy_daily->output_report_results();
				wcj_codex_check( $label . ' full daily legacy table parity', $hpos_daily_table, $GLOBALS['wcj_report_tables'][0][0] );
				wcj_codex_check( $label . ' legacy daily reads posts not WC date', array( $GLOBALS['wcj_report_post_reads'] > 0, $GLOBALS['wcj_report_date_reads'] ), array( true, array() ) );
				wcj_codex_reset();
				$legacy_monthly = wcj_codex_sales( $year, '', true );
				wcj_codex_check( $label . ' full monthly legacy table parity', $monthly[0], $legacy_monthly[0] );
				wcj_codex_check( $label . ' monthly returned synthetic HTML parity', $monthly[1], $legacy_monthly[1] );
				wcj_codex_check( $label . ' legacy sales reads posts not WC date', array( $GLOBALS['wcj_report_post_reads'] > 0, $GLOBALS['wcj_report_date_reads'] ), array( true, array() ) );
				wcj_codex_reset();
				$legacy_info = array(
					11 => array(
						'last_sale'       => 0,
						'sales_in_period' => array( 30 => 0 ),
					),
				);
				$stock->gather_orders_data( $legacy_info );
				wcj_codex_check( $label . ' full stock legacy state parity', $info, $legacy_info );
				wcj_codex_check( $label . ' legacy stock reads posts not WC date', array( $GLOBALS['wcj_report_post_reads'] > 0, $GLOBALS['wcj_report_date_reads'] ), array( true, array() ) );
			}
		}
	}
	wcj_codex_multi();
	$failures = array_filter(
		$GLOBALS['wcj_report_checks'],
		static function ( $check ) {
			return ! $check['pass'];
		}
	);
	echo wp_json_encode(
		array(
			'assertions' => count( $GLOBALS['wcj_report_checks'] ),
			'failures'   => count( $failures ),
			'checks'     => $GLOBALS['wcj_report_checks'],
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	) . PHP_EOL;
	exit( empty( $failures ) ? 0 : 1 );
}

/**
 * Freeze literal multi-page, variation, title, tax and null-fill outcomes.
 *
 * @return mixed
 */
function wcj_codex_multi() {
	$first                        = new WC_DateTime( '2026-10-09 12:34:56', new DateTimeZone( 'UTC' ) );
	$second                       = new WC_DateTime( '2026-03-08 03:30:00', new DateTimeZone( 'America/New_York' ) );
	$orders                       = array(
		901 => new WCJ_Codex_Report_Order( 901, $first, array( wcj_codex_item( 11, 0, 2, 20, 2 ) ) ),
		902 => new WCJ_Codex_Report_Order( 902, $second, array( wcj_codex_item( 11, 0, 3, 30, 3 ), wcj_codex_item( 12, 22, 1, 12, 1, 'Beta' ) ) ),
	);
	$GLOBALS['wcj_report_orders'] = $orders;
	$GLOBALS['wcj_report_pages']  = array( array( $orders[901] ), array( $orders[902] ) );
	$GLOBALS['wcj_report_posts']  = array();
	foreach ( array( false, true ) as $tax ) {
		foreach ( array( false, true ) as $variation ) {
			foreach ( array( '', 'Alpha' ) as $title ) {
				$label                         = 'multi/' . wp_json_encode( array( $tax, $variation, $title ) );
				$GLOBALS['wcj_report_options'] = array(
					'timezone_string' => 'UTC',
					'wcj_reports_products_sales_daily_include_taxes' => $tax ? 'yes' : 'no',
					'wcj_reports_products_sales_include_taxes' => $tax ? 'yes' : 'no',
					'wcj_reports_products_sales_daily_count_variations' => $variation ? 'yes' : 'no',
					'wcj_reports_products_sales_count_variations' => $variation ? 'yes' : 'no',
					'wcj_reports_products_sales_display_profit' => 'yes',
				);
				wcj_codex_reset();
				$daily                = new WCJ_Reports_Product_Sales_Daily();
				$daily->start_date    = '2025-01-01';
				$daily->end_date      = '2026-12-31';
				$daily->product_title = $title;
				$daily->get_report_data_hpos();
				wcj_codex_check( $label . ' daily distinct dates across populated pages', array_keys( $daily->sales_by_day ), array( '2026-10-09', '2026-03-08' ) );
				wcj_codex_check( $label . ' daily latest first order wins', $daily->last_sale_data[11]['date'], '2026-10-09 12:34:56' );
				wcj_codex_check( $label . ' daily order count unchanged', $daily->total_orders, 2 );
				wcj_codex_check(
					$label . ' daily date read exactly once/order',
					$GLOBALS['wcj_report_date_reads'],
					array(
						901 => 1,
						902 => 1,
					)
				);
				wcj_codex_queries( $label, 'daily', '2026', 2 );
				$daily->output_report_results();
				$table = $GLOBALS['wcj_report_tables'][0][0];
				$qty   = '' === $title ? 6 : 5;
				$sum   = '' === $title ? ( $tax ? 68 : 62 ) : ( $tax ? 55 : 50 );
				wcj_codex_check( $label . ' daily literal totals and profit', end( $table ), array( '', '<strong>Total: ' . $qty . '</strong>', '<strong>Total: $' . $sum . '.00</strong>', '<strong>Total: $' . ( $sum - 2 * $qty ) . '.00</strong>', '' ) );
				if ( '' === $title ) {
					wcj_codex_check( $label . ' daily variation key policy retained', array_keys( $daily->sales_by_day['2026-03-08'] ?? array() ), array( 11, $variation ? 22 : 12 ) );
				}

				wcj_codex_reset();
				$monthly = wcj_codex_sales( '2026', $title )[0];
				wcj_codex_check( $label . ' monthly latest first order wins', $monthly[1][2], '2026-10-09 12:34:56' );
				wcj_codex_check( $label . ' monthly Alpha literal all-order totals', array( $monthly[1][3], $monthly[2][3] ), array( '<strong>5</strong>', $tax ? '<strong>$55.00</strong>' : '<strong>$50.00</strong>' ) );
				wcj_codex_check( $label . ' monthly October and March quantities', array( $monthly[1][6] ?? null, $monthly[1][13] ?? null ), array( '<span style="color:green;">2</span>', '<span style="color:green;">3</span>' ) );
				// The unchanged monthly report also counts both parent and variation when enabled.
				$monthly_qty = '' === $title ? ( $variation ? 7 : 6 ) : 5;
				$monthly_sum = '' === $title ? ( $variation ? ( $tax ? 81 : 74 ) : ( $tax ? 68 : 62 ) ) : ( $tax ? 55 : 50 );
				wcj_codex_check( $label . ' monthly existing summary counting policy', $monthly[ count( $monthly ) - 3 ][3], '<strong>' . $monthly_qty . '</strong>' );
				wcj_codex_check( $label . ' monthly existing malformed footer value unchanged', $monthly[ count( $monthly ) - 2 ][3], '<strong>$' . $monthly_sum . '.00' );
				wcj_codex_check(
					$label . ' monthly read exactly once/order',
					$GLOBALS['wcj_report_date_reads'],
					array(
						901 => 1,
						902 => 1,
					)
				);
				wcj_codex_queries( $label, 'sales', '2026', 2 );

				wcj_codex_reset();
				$stock = wcj_codex_stock( $variation );
				$beta  = $variation ? 22 : 12;
				$info  = array(
					11    => array(
						'last_sale'       => 0,
						'sales_in_period' => array( 30 => 0 ),
					),
					$beta => array(
						'last_sale'       => 0,
						'sales_in_period' => array( 30 => 0 ),
					),
				);
				$stock->gather_orders_data_hpos( $info );
				wcj_codex_check( $label . ' stock latest first order wins', $info[11]['last_sale'], 1791549296 );
				wcj_codex_check( $label . ' stock distinct offset timestamp across pages', $info[ $beta ]['last_sale'], 1772940600 );
				wcj_codex_check( $label . ' stock unchanged quantities/no title filter', array( $info[11]['sales_in_period'][30], $info[ $beta ]['sales_in_period'][30] ), array( 5, 1 ) );
				wcj_codex_check(
					$label . ' stock read once/order',
					$GLOBALS['wcj_report_date_reads'],
					array(
						901 => 1,
						902 => 1,
					)
				);
				wcj_codex_queries( $label, 'stock', '2026', 2 );
			}
		}
	}
	$GLOBALS['wcj_report_options'] = array(
		'timezone_string'                           => 'UTC',
		'wcj_reports_products_sales_display_profit' => 'yes',
	);
	$unknown                       = new WCJ_Codex_Report_Order( 901, null, array( wcj_codex_item() ) );
	$known                         = new WCJ_Codex_Report_Order( 902, $first, array( wcj_codex_item( 11, 0, 3, 30, 3 ) ) );
	$GLOBALS['wcj_report_orders']  = array(
		901 => $unknown,
		902 => $known,
	);
	$GLOBALS['wcj_report_pages']   = array( array( $unknown ), array( $known ) );
	wcj_codex_reset();
	$monthly = wcj_codex_sales( '2026' )[0];
	wcj_codex_check( 'mixed null monthly first unknown last label retained', $monthly[1][2], '' );
	wcj_codex_check( 'mixed null monthly per-product all-order qty/money retained', array( $monthly[1][3], $monthly[2][3] ), array( '<strong>5</strong>', '<strong>$40.00</strong>' ) );
	wcj_codex_check( 'mixed null monthly calendar footer excludes unknown qty/money', array( $monthly[4][3], $monthly[5][3] ), array( '<strong>3</strong>', '<strong>$30.00' ) );
	wcj_codex_check( 'mixed null monthly overall profit retains all orders', $monthly[6][3], '<strong>$30.00' );
	wcj_codex_reset();
	$stock = wcj_codex_stock();
	$info  = array(
		11 => array(
			'last_sale'       => 0,
			'sales_in_period' => array( 30 => 0 ),
		),
	);
	$stock->gather_orders_data_hpos( $info );
	wcj_codex_check( 'mixed null stock preserves possible later-known fill', $info[11]['last_sale'], 1791549296 );
	wcj_codex_check( 'mixed null stock quantity retained', $info[11]['sales_in_period'][30], 5 );
}

wcj_codex_main( $argv[1], $argv[2] );
