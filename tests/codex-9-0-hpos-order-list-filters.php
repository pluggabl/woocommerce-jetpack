<?php
/**
 * Offline actual-source country/currency order-list regression.
 *
 * @package Booster_Tests
 */
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
$args = getopt( '', array( 'root:', 'upstream:' ) );
if ( empty( $args['root'] ) || empty( $args['upstream'] ) ) {
	exit( 2 );
}
error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['wcj_test'] = array(
	'checks'  => array(),
	'hooks'   => array(),
	'imports' => array(),
	'calls'   => array(),
);

/** Record one fixed named expectation. */
function wcj_test_check( $name, $expected, $actual ) {
	$GLOBALS['wcj_test']['checks'][] = array(
		'name'     => $name,
		'pass'     => $expected === $actual,
		'expected' => $expected,
		'actual'   => $actual,
	);
}
/** Record one controlled boundary call. */
function wcj_test_call( $name ) {
	$GLOBALS['wcj_test']['calls'][ $name ] = ( $GLOBALS['wcj_test']['calls'][ $name ] ?? 0 ) + 1;
}
/** Extract a whole actual method/function, never reimplement its policy. */
function wcj_test_source_function( $file, $name ) {
	$tokens = token_get_all( file_get_contents( $file ) );
	for ( $i = 0; $i < count( $tokens ); ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}
		$j = $i + 1;
		while ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( ! is_array( $tokens[ $j ] ) || $tokens[ $j ][1] !== $name ) {
			continue;
		}
		$body    = '';
		$depth   = 0;
		$started = false;
		for ( ; $i < count( $tokens ); ++$i ) {
			$token = $tokens[ $i ];
			$body .= is_array( $token ) ? $token[1] : $token;
			if ( '{' === $token ) {
				++$depth;
				$started = true;
			}
			if ( '}' === $token && 0 === --$depth && $started ) {
				$GLOBALS['wcj_test']['imports'][ $name ] = hash( 'sha256', $body );
				return $body;
			}
		}
	}
	throw new RuntimeException( 'Missing whole source function: ' . $name );
}
// Explicit offline WordPress primitives, not a native WordPress bootstrap.
function __( $text, $domain = null ) {
	return $text; }
function wcj_get_option( $key, $default = false ) {
	return $GLOBALS['wcj_test']['options'][ $key ] ?? $default; }
function wcj_is_hpos_enabled() {
	return $GLOBALS['wcj_test']['hpos']; }
function wcj_is_rest() {
	return false; }
function wp_get_current_user() {
	return (object) array( 'roles' => array( 'shop_manager' ) ); }
function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
function sanitize_text_field( $value ) {
	return trim( strip_tags( $value ) ); }
function wp_unslash( $value ) {
	return stripslashes( $value ); }
function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8', false ); }
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8', false ); }
function selected( $value, $selected, $echo = true ) {
	$out = (string) $value === (string) $selected ? " selected='selected'" : '';
	if ( $echo ) {
		echo $out; }
	return $out;
}
function wp_kses_post( $value ) {
	return $value; }
function wc_get_order_types( $context ) {
	return array( 'shop_order' ); }
function did_action( $name ) {
	return 0; }
function current_user_can( $capability ) {
	return false; }
function get_woocommerce_currencies() {
	wcj_test_call( 'currencies' );
	return $GLOBALS['wcj_test']['currencies']; }
function get_woocommerce_currency_symbol( $code ) {
	return $GLOBALS['wcj_test']['symbols'][ $code ] ?? ''; }
function wcj_get_countries() {
	wcj_test_call( 'countries' );
	return $GLOBALS['wcj_test']['countries'] ?? wcj_test_actual_countries();
}
function add_filter( $name, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['wcj_test']['hooks'][ $name ][ $priority ][] = array( $callback, $accepted );
}
function add_action( $name, $callback, $priority = 10, $accepted = 1 ) {
	add_filter( $name, $callback, $priority, $accepted ); }
function apply_filters( $name, $value, ...$extra ) {
	if ( 'wcj_enabled_module_allowlist' === $name ) {
		return $GLOBALS['wcj_test']['allowlist'] ?? $value; }
	$groups = $GLOBALS['wcj_test']['hooks'][ $name ] ?? array();
	ksort( $groups );
	foreach ( $groups as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $extra ), 0, $entry[1] ) );
		}
	}
	return $value;
}
function do_action( $name, ...$arguments ) {
	$groups = $GLOBALS['wcj_test']['hooks'][ $name ] ?? array();
	ksort( $groups );
	foreach ( $groups as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			call_user_func_array( $entry[0], array_slice( $arguments, 0, $entry[1] ) ); }
	}
}
function wc_get_orders( $query ) {
	$GLOBALS['wcj_test']['queries'][] = $query;
	return (object) array(
		'orders'        => array(),
		'total'         => 3,
		'max_num_pages' => 1,
	);
}
function submit_button( ...$arguments ) {
	echo '<button>Filter</button>'; }

$core = $args['root'] . '/includes/functions/wcj-functions-booster-core.php';
foreach ( array( 'wcj_check_modules_by_user_roles', 'wcj_is_module_enabled', 'wcj_get_enabled_module_allowlist', 'wcj_is_module_allowed_by_allowlist' ) as $name ) {
	if ( false !== strpos( file_get_contents( $core ), 'function ' . $name . '(' ) ) {
		eval( wcj_test_source_function( $core, $name ) );
	}
}
$enabled = wcj_test_source_function( $args['root'] . '/includes/classes/class-wcj-module.php', 'is_enabled' );
eval( 'class WCJ_Module { public $id; public $short_desc; public $desc; public $desc_pro; public $link_slug; public $type="module"; public $parent_id; public function __construct() {} public ' . $enabled . ' }' );
$countries = wcj_test_source_function( $args['root'] . '/includes/functions/wcj-functions-country.php', 'wcj_get_countries' );
eval( str_replace( 'function wcj_get_countries(', 'function wcj_test_actual_countries(', $countries ) );
eval( wcj_test_source_function( $args['root'] . '/includes/functions/wcj-functions-price-currency.php', 'wcj_get_woocommerce_currencies_and_symbols' ) );

/** Reset controlled request and current options, not stored merchant state. */
function wcj_test_reset( $options = array(), $get = array(), $hpos = true ) {
	unset( $GLOBALS['wcj_modules_by_user_roles_data'] );
	$GLOBALS['wcj_test']['hooks']      = array();
	$GLOBALS['wcj_test']['calls']      = array();
	$GLOBALS['wcj_test']['queries']    = array();
	$GLOBALS['wcj_test']['allowlist']  = null;
	$GLOBALS['wcj_test']['hpos']       = $hpos;
	$GLOBALS['wcj_test']['options']    = array_replace(
		array(
			'wcj_admin_orders_list_enabled'               => 'yes',
			'wcj_order_admin_list_custom_columns_enabled' => 'yes',
			'wcj_orders_list_custom_columns_country'      => 'yes',
			'wcj_orders_list_custom_columns_currency'     => 'yes',
		),
		$options
	);
	$GLOBALS['wcj_test']['countries']  = array(
		'US' => 'United "States" & <tag>',
		'BR' => 'Brazil',
	);
	$GLOBALS['wcj_test']['currencies'] = array(
		'USD'   => 'Dollar "quoted" & <tag>',
		'EUR'   => 'Euro',
		'X-OLD' => 'Old custom',
	);
	$GLOBALS['wcj_test']['symbols']    = array(
		'USD'   => '$',
		'EUR'   => '&euro;',
		'X-OLD' => '¤',
	);
	$_GET                              = $get;
	$_REQUEST                          = $get;
	$GLOBALS['typenow']                = 'shop_order';
	$GLOBALS['wp_query']               = (object) array();
}
wcj_test_reset();
$module  = require $args['root'] . '/includes/class-wcj-admin-orders-list.php';
$table   = $args['upstream'] . '/src/Internal/Admin/Orders/ListTable.php';
$prepare = wcj_test_source_function( $table, 'prepare_items' );
$nav     = wcj_test_source_function( $table, 'extra_tablenav' );
// Two complete pinned Woo caller methods. Only surrounding framework/data primitives are controlled.
eval(
	'class WCJ_Test_Order_List_Caller {
 public $order_type="shop_order"; public $order_query_args=array(); public $request=array(); public $items=array(); public $is_trash=false;
 public function get_items_per_page($key) { return 20; } public function get_pagenum() { return 2; }
 public function set_status_args() { $this->order_query_args["status"]=array("wc-completed"); }
 public function set_order_args() { $this->order_query_args["orderby"]="id"; $this->order_query_args["order"]="DESC"; }
 public function set_date_args() {} public function set_customer_args() {} public function set_search_args() {} public function set_created_via_args() {}
 public function get_max_num_pages(&$orders) { return 1; } public function set_pagination_args($args) {} public function months_filter() {} public function has_items() { return false; }
 public ' . $prepare . ' public ' . $nav . ' }'
);

/** Call the registered public Woo render hook, not a guessed module method. */
function wcj_test_render( $type = 'shop_order', $which = 'top' ) {
	ob_start();
	do_action( 'woocommerce_order_list_table_restrict_manage_orders', $type, $which );
	return ob_get_clean();
}
/** Query arguments after existing unrelated filters and the actual Woo producer. */
function wcj_test_query( $type = 'shop_order' ) {
	add_filter(
		'woocommerce_order_list_table_prepare_items_query_args',
		static function ( $args ) {
			$args['external']   = array( 'keep' => 7 );
			$args['meta_query'] = array(
				array(
					'key'   => 'external',
					'value' => 'preserved',
				),
			);
			return $args;
		}
	);
	$table             = new WCJ_Test_Order_List_Caller();
	$table->order_type = $type;
	$table->prepare_items();
	return end( $GLOBALS['wcj_test']['queries'] );
}
$incoming     = array(
	'limit'      => 20,
	'page'       => 2,
	'paginate'   => true,
	'type'       => 'shop_order',
	'status'     => array( 'wc-completed' ),
	'orderby'    => 'id',
	'order'      => 'DESC',
	'external'   => array( 'keep' => 7 ),
	'meta_query' => array(
		array(
			'key'   => 'external',
			'value' => 'preserved',
		),
	),
);
$country_all  = '<select id="country" name="country"><option value="all" selected=\'selected\'>All countries</option><option value="US">United &quot;States&quot; &amp; &lt;tag&gt;</option><option value="BR">Brazil</option></select>';
$country_us   = '<select id="country" name="country"><option value="all">All countries</option><option value="US" selected=\'selected\'>United &quot;States&quot; &amp; &lt;tag&gt;</option><option value="BR">Brazil</option></select>';
$currency_all = '<select id="currency" name="currency"><option value="all" selected=\'selected\'>All currencies</option><option value="USD">Dollar &quot;quoted&quot; &amp; &lt;tag&gt; ($)</option><option value="EUR">Euro (&euro;)</option><option value="X-OLD">Old custom (¤)</option></select>';
$currency_usd = '<select id="currency" name="currency"><option value="all">All currencies</option><option value="USD" selected=\'selected\'>Dollar &quot;quoted&quot; &amp; &lt;tag&gt; ($)</option><option value="EUR">Euro (&euro;)</option><option value="X-OLD">Old custom (¤)</option></select>';

foreach ( array( array( 'yes', 'yes' ), array( 'yes', 'no' ), array( 'no', 'yes' ), array( 'no', 'no' ) ) as $settings ) {
	wcj_test_reset(
		array(
			'wcj_orders_list_custom_columns_country'  => $settings[0],
			'wcj_orders_list_custom_columns_currency' => $settings[1],
		),
		array(
			'country'  => 'US',
			'currency' => 'USD',
		)
	);
	$module   = new WCJ_Admin_Orders_List();
	$name     = implode( '/', $settings );
	$html     = ( 'yes' === $settings[0] ? $country_us : '' ) . ( 'yes' === $settings[1] ? $currency_usd : '' );
	$expected = $incoming;
	if ( 'yes' === $settings[0] ) {
		$expected['billing_country'] = 'US'; }
	if ( 'yes' === $settings[1] ) {
		$expected['currency'] = 'USD'; }
	wcj_test_check( 'literal HPOS filters setting ' . $name, $html, wcj_test_render() );
	wcj_test_check( 'literal typed HPOS query setting ' . $name, $expected, wcj_test_query() );
	wcj_test_check( 'other order type render setting ' . $name, '', wcj_test_render( 'shop_order_refund' ) );
	wcj_test_check( 'bottom navigation render setting ' . $name, '', wcj_test_render( 'shop_order', 'bottom' ) );
	wcj_test_check( 'HPOS has no country/currency legacy hook setting ' . $name, false, isset( $GLOBALS['wcj_test']['hooks']['restrict_manage_posts'] ) || isset( $GLOBALS['wcj_test']['hooks']['parse_query'] ) );
}
foreach ( array( 'absent', 'all', 'empty', 'array', 'integer', 'boolean', 'null', 'unknown', 'lowercase', 'space', 'html', 'sql' ) as $kind ) {
	$values = array(
		'all'       => 'all',
		'empty'     => '',
		'array'     => array( 'US' ),
		'integer'   => 12,
		'boolean'   => false,
		'null'      => null,
		'unknown'   => 'ZZ',
		'lowercase' => 'us',
		'space'     => ' US ',
		'html'      => '<b>US</b>',
		'sql'       => "US' OR 1=1",
	);
	$get    = 'absent' === $kind ? array() : array(
		'country'  => $values[ $kind ],
		'currency' => $values[ $kind ],
	);
	wcj_test_reset( array(), $get );
	$module = new WCJ_Admin_Orders_List();
	wcj_test_check( 'invalid/all selector HTML ' . $kind, $country_all . $currency_all, wcj_test_render() );
	wcj_test_check( 'invalid/all selector preserves query ' . $kind, $incoming, wcj_test_query() );
}
foreach ( array(
	'country'  => 'BR',
	'currency' => 'X-OLD',
) as $selector => $code ) {
	wcj_test_reset( array(), array( $selector => $code ) );
	$module   = new WCJ_Admin_Orders_List();
	$expected = $incoming;
	$expected[ 'country' === $selector ? 'billing_country' : 'currency' ] = $code;
	wcj_test_check( 'helper-supported configured code query ' . $selector, $expected, wcj_test_query() );
	wcj_test_check( 'helper-supported configured code selected ' . $selector, true, false !== strpos( wcj_test_render(), 'value="' . $code . '" selected=\'selected\'' ) );
}
wcj_test_reset();
$module                                   = new WCJ_Admin_Orders_List();
$GLOBALS['wcj_test']['countries']['X"<']  = '<img src=x> & "Country"';
$GLOBALS['wcj_test']['currencies']['C"<'] = '<svg> & "Currency"';
$html                                     = wcj_test_render();
wcj_test_check( 'helper codes and labels escaped as text', true, false !== strpos( $html, 'value="X&quot;&lt;"' ) && false !== strpos( $html, '&lt;img src=x&gt; &amp; &quot;Country&quot;' ) && false !== strpos( $html, 'value="C&quot;&lt;"' ) && false === strpos( $html, '<img' ) && false === strpos( $html, '<svg' ) );
wcj_test_reset();
$module = new WCJ_Admin_Orders_List();
unset( $GLOBALS['wcj_test']['countries'] );
$_GET = array( 'country' => 'JP' );
wcj_test_check( 'actual unchanged country helper supported code is selected', true, false !== strpos( wcj_test_render(), 'value="JP" selected=\'selected\'' ) );
$expected                    = $incoming;
$expected['billing_country'] = 'JP';
wcj_test_check( 'actual unchanged country helper validates public country arg', $expected, wcj_test_query() );
foreach ( array( 'wcj_admin_orders_list_enabled', 'wcj_order_admin_list_custom_columns_enabled' ) as $gate ) {
	wcj_test_reset(
		array( $gate => 'no' ),
		array(
			'country'  => 'US',
			'currency' => 'USD',
		)
	);
	$module = new WCJ_Admin_Orders_List();
	wcj_test_check( 'disabled initial render ' . $gate, '', wcj_test_render() );
	wcj_test_check( 'disabled initial query ' . $gate, $incoming, wcj_test_query() );
	wcj_test_reset(
		array(),
		array(
			'country'  => 'US',
			'currency' => 'USD',
		)
	);
	$module                                  = new WCJ_Admin_Orders_List();
	$GLOBALS['wcj_test']['options'][ $gate ] = 'no';
	wcj_test_check( 'live disabled render ' . $gate, '', wcj_test_render() );
	wcj_test_check( 'live disabled query ' . $gate, $incoming, wcj_test_query() );
}
wcj_test_reset(
	array(),
	array(
		'country'  => 'US',
		'currency' => 'USD',
	)
);
$module                      = new WCJ_Admin_Orders_List();
$GLOBALS['wcj_test']['hpos'] = false;
wcj_test_check( 'live legacy switch render unchanged', '', wcj_test_render() );
wcj_test_check( 'live legacy switch query unchanged', $incoming, wcj_test_query() );
wcj_test_reset(
	array(),
	array(
		'country'  => 'US',
		'currency' => 'USD',
	),
	false
);
$module = new WCJ_Admin_Orders_List();
wcj_test_check( 'Classic original two hooks retained', true, isset( $GLOBALS['wcj_test']['hooks']['restrict_manage_posts'], $GLOBALS['wcj_test']['hooks']['parse_query'] ) );
wcj_test_check( 'Classic does not register HPOS country/currency hooks', false, isset( $GLOBALS['wcj_test']['hooks']['woocommerce_order_list_table_restrict_manage_orders'] ) || isset( $GLOBALS['wcj_test']['hooks']['woocommerce_shop_order_list_table_prepare_items_query_args'] ) );
$q = (object) array(
	'query_vars' => array(
		'meta_query' => array(
			array(
				'key'   => 'existing',
				'value' => 7,
			),
		),
	),
);
do_action( 'parse_query', $q );
wcj_test_check(
	'Classic exact existing meta args retained and appended',
	array(
		'meta_query' => array(
			array(
				'key'   => 'existing',
				'value' => 7,
			),
			array(
				'key'   => '_billing_country',
				'value' => 'US',
			),
			array(
				'key'   => '_order_currency',
				'value' => 'USD',
			),
		),
	),
	$q->query_vars
);
wcj_test_reset(
	array(
		'wcj_modules_by_user_roles_enabled'           => 'yes',
		'wcj_modules_by_user_roles_excl_shop_manager' => array( 'admin_orders_list' ),
	)
);
$module = new WCJ_Admin_Orders_List();
wcj_test_check( 'actual role helper keeps excluded module inactive', '', wcj_test_render() );
wcj_test_check( 'actual role helper does not register query adapter', false, isset( $GLOBALS['wcj_test']['hooks']['woocommerce_shop_order_list_table_prepare_items_query_args'] ) );
if ( function_exists( 'wcj_get_enabled_module_allowlist' ) ) {
	wcj_test_reset();
	$GLOBALS['wcj_test']['allowlist'] = array( 'other_module' );
	$module                           = new WCJ_Admin_Orders_List();
	wcj_test_check( 'existing Elite allowlist exclusion retained', '', wcj_test_render() );
	wcj_test_reset();
	$GLOBALS['wcj_test']['allowlist'] = array( 'admin_orders_list' );
	$module                           = new WCJ_Admin_Orders_List();
	wcj_test_check( 'existing Elite allowlist inclusion retains filters', $country_all . $currency_all, wcj_test_render() );
}
wcj_test_reset(
	array(),
	array(
		'country'  => 'US',
		'currency' => 'USD',
	)
);
$module = new WCJ_Admin_Orders_List();
$caller = new WCJ_Test_Order_List_Caller();
ob_start();
$caller->extra_tablenav( 'top' );
$actual_nav = ob_get_clean();
wcj_test_check( 'actual pinned Woo top producer supplies two selectors and Filter', '<div class="alignleft actions">' . $country_us . $currency_usd . '<button>Filter</button></div>', $actual_nav );
ob_start();
$caller->extra_tablenav( 'bottom' );
$actual_nav = ob_get_clean();
wcj_test_check( 'actual pinned Woo bottom producer avoids duplicate selectors', '<div class="alignleft actions"></div>', $actual_nav );
foreach ( array( 'shop_order_refund', 'custom_order' ) as $type ) {
	$expected         = $incoming;
	$expected['type'] = $type;
	wcj_test_check( 'actual pinned Woo type-specific query preserves unrelated type ' . $type, $expected, wcj_test_query( $type ) );
}
$checks = $GLOBALS['wcj_test']['checks'];
$failed = count(
	array_filter(
		$checks,
		static function ( $check ) {
			return ! $check['pass'];
		}
	)
);
echo json_encode(
	array(
		'kind'       => 'actual_module_and_two_pinned_Woo_callers_controlled_primitives_not_native',
		'checks'     => count( $checks ),
		'failed'     => $failed,
		'imports'    => $GLOBALS['wcj_test']['imports'],
		'assertions' => $checks,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
) . "\n";
exit( $failed ? 1 : 0 );
