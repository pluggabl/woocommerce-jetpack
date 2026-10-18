<?php
/** Source-only fixture. No native authorization, browser, database or payment proof. */
if ( 'cli' !== PHP_SAPI || count( $argv ) < 3 ) {
	echo "Usage: php fixture.php plugin-root upstream-unpacked [ordinary|rest|late|no-api|route-parity]\n";
	exit( 2 );
}
$root = rtrim( $argv[1], '/\\' );
$upstream = rtrim( $argv[2], '/\\' );
$mode = isset( $argv[3] ) ? $argv[3] : 'ordinary';
if ( ! in_array( $mode, array( 'ordinary', 'rest', 'late', 'no-api', 'route-parity' ), true ) ) { exit( 2 ); }
define( 'ABSPATH', $upstream . '/wordpress/' );
if ( 'rest' === $mode || 'no-api' === $mode || 'route-parity' === $mode ) { define( 'REST_REQUEST', true ); }
$cases = array();
function check( $name, $actual, $expected ) {
	global $cases;
	$cases[] = array( 'name' => $name, 'pass' => $actual === $expected, 'actual' => $actual, 'expected' => $expected );
	if ( 'route-parity' === $GLOBALS['mode'] ) { $cases[count( $cases ) - 1]['changed_input'] = 'Remove D only from actual checkout route guard; focused route-parity fixture mode'; $cases[count( $cases ) - 1]['unresolved_finding'] = 'F1-R'; $cases[count( $cases ) - 1]['acceptance_requirement'] = 'Actual pinned WP router parity, no final-POST validation or stale-clear bypass'; }
}
function result() {
	global $cases, $mode;
	$bad = count( array_filter( $cases, function ( $c ) { return ! $c['pass']; } ) );
	echo json_encode( array( 'mode' => $mode, 'assertions' => count( $cases ), 'failures' => $bad, 'cases' => $cases, 'limitations' => array( 'Synthetic WP/Woo primitives; production methods and complete pinned shortcode parser executed', 'Not native role/nonce/cart-token/browser/storage proof' ) ), JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE ) . "\n";
	exit( $bad ? 1 : 0 );
}
/** Import a complete actual function, token-delimited, not a rewritten expectation. */
function import_function( $path, $name ) {
	if ( function_exists( $name ) ) { return; }
	$tokens = token_get_all( file_get_contents( $path ) ); $offset = 0;
	foreach ( $tokens as $i => $token ) {
		$text = is_array( $token ) ? $token[1] : $token;
		if ( is_array( $token ) && T_FUNCTION === $token[0] ) {
			$j = $i + 1;
			while ( is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
			if ( is_array( $tokens[$j] ) && $tokens[$j][1] === $name ) {
				$code = ''; $depth = 0; $opened = false;
				for ( $k = $i; $k < count( $tokens ); ++$k ) {
					$t = $tokens[$k]; $s = is_array( $t ) ? $t[1] : $t; $code .= $s;
					if ( '{' === $t ) { ++$depth; $opened = true; }
					if ( '}' === $t && 0 === --$depth && $opened ) { eval( $code ); return; }
				}
			}
		}
		$offset += strlen( $text );
	}
	throw new RuntimeException( 'Missing pinned function: ' . $name );
}
// Actual WP_Hook and actual shortcode parser. Supporting environment primitives below are synthetic.
function _wp_filter_build_unique_id( $hook, $callback, $priority ) {
	if ( is_string( $callback ) ) { return $callback; }
	if ( is_object( $callback ) ) { return spl_object_hash( $callback ); }
	return ( is_object( $callback[0] ) ? spl_object_hash( $callback[0] ) : $callback[0] ) . $callback[1];
}
require $upstream . '/wordpress/wp-includes/class-wp-hook.php';
$wp_filter = array(); $shortcode_tags = array(); $action_counts = array();
function add_filter( $tag, $callback, $priority = 10, $args = 1 ) { global $wp_filter; if ( ! isset( $wp_filter[$tag] ) ) { $wp_filter[$tag] = new WP_Hook(); } $wp_filter[$tag]->add_filter( $tag, $callback, $priority, $args ); return true; }
function add_action( $tag, $callback, $priority = 10, $args = 1 ) { return add_filter( $tag, $callback, $priority, $args ); }
function remove_filter( $tag, $callback, $priority = 10 ) { global $wp_filter; return isset( $wp_filter[$tag] ) ? $wp_filter[$tag]->remove_filter( $tag, $callback, $priority ) : false; }
function has_filter( $tag, $callback = false ) { global $wp_filter; return isset( $wp_filter[$tag] ) ? $wp_filter[$tag]->has_filter( $tag, $callback ) : false; }
function apply_filters( $tag, $value, ...$args ) { global $wp_filter; return isset( $wp_filter[$tag] ) ? $wp_filter[$tag]->apply_filters( $value, array_merge( array( $value ), $args ) ) : $value; }
function do_action( $tag, ...$args ) { global $wp_filter, $action_counts; $action_counts[$tag] = isset( $action_counts[$tag] ) ? $action_counts[$tag] + 1 : 1; if ( isset( $wp_filter[$tag] ) ) { $wp_filter[$tag]->do_action( $args ); } }
function do_action_ref_array( $tag, $args ) { global $wp_filter; if ( isset( $wp_filter[$tag] ) ) { $wp_filter[$tag]->do_action( $args ); } }
function did_action( $tag ) { global $action_counts; return isset( $action_counts[$tag] ) ? $action_counts[$tag] : 0; }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function admin_url( $path = '' ) { return 'https://synthetic.invalid/wp-admin/' . $path; }
function wp_nonce_field( $action ) {}
function has_action( $tag, $callback = false ) { return has_filter( $tag, $callback ); }
function wp_json_encode( $x, $flags = 0, $depth = 512 ) { return json_encode( $x, $flags, $depth ); }
function wp_unslash( $x ) { return is_array( $x ) ? array_map( 'wp_unslash', $x ) : ( is_string( $x ) ? stripslashes( $x ) : $x ); }
function wp_parse_url( $x ) { return parse_url( $x ); }
function rest_get_url_prefix() { return 'wp-json'; }
function site_url( $x = '' ) { return 'https://synthetic.invalid/' . $x; }
function add_query_arg( $x ) { return 'https://synthetic.invalid/checkout'; }
function wp_check_invalid_utf8( $x ) { return 1 === preg_match( '//u', (string) $x ) ? (string) $x : ''; }
function get_option( $key, $default = false ) { global $options, $option_reads; $option_reads[] = $key; return array_key_exists( $key, $options ) ? $options[$key] : $default; }
function wcj_get_option( $key, $default = false ) { return get_option( $key, $default ); }
function wc_clean( $x ) { return is_array( $x ) ? array_map( 'wc_clean', $x ) : ( is_scalar( $x ) ? sanitize_text_field( $x ) : $x ); }
function wp_get_current_user() { global $role; return (object) array( 'roles' => $role ? array( $role ) : array() ); }
function is_admin() { global $admin; return $admin; }
function current_user_can( $cap ) { global $capability; return $capability; }
function get_current_screen() { global $screen; return null === $screen ? null : (object) array( 'id' => $screen ); }
function wcj_is_hpos_enabled() { return true; }
function wp_verify_nonce( $x, $action ) { return 'synthetic-valid' === $x; }
function wp_kses_post( $x ) { return strip_tags( $x, '<p><br><b><strong><em><i><a><input><form><script><style><iframe>' ); }
function wpautop( $x ) { return $x; }
function wptexturize( $x ) { return $x; }
function wcj_get_data_attributes_html( $x ) { return ''; }
function get_query_var( $x ) { return 0; }
function is_page( $x ) { return false; }
function wc_get_page_id( $x ) { return 1; }
function plugins_url( $path, $file ) { return 'https://synthetic.invalid/plugins/includes/' . $path; }
$scripts = array();
function wp_script_is( $handle, $state = '' ) { global $scripts; return isset( $scripts[$handle] ); }
function wp_register_script( $handle, $url, $deps, $version, $footer ) { global $scripts; $scripts[$handle] = array( $url, $deps, $version, $footer ); }
function _doing_it_wrong( $x, $y, $z ) { throw new RuntimeException( 'Unexpected registry misuse' ); }
require $upstream . '/wordpress/wp-includes/shortcodes.php';
foreach ( array( 'wp_strip_all_tags', 'sanitize_key', '_sanitize_text_fields', 'sanitize_text_field', 'wp_html_split', 'get_html_split_regex', 'wp_pre_kses_less_than', 'wp_pre_kses_less_than_callback' ) as $f ) { import_function( $upstream . '/wordpress/wp-includes/formatting.php', $f ); }
import_function( $root . '/includes/functions/wcj-functions-general.php', 'wcj_sanitize_input_attribute_values' );
foreach ( array( 'wcj_is_rest', 'wcj_check_modules_by_user_roles', 'wcj_get_enabled_module_allowlist', 'wcj_is_module_allowed_by_allowlist', 'wcj_is_module_enabled' ) as $f ) {
	if ( false !== strpos( file_get_contents( $root . '/includes/functions/wcj-functions-booster-core.php' ), 'function ' . $f . '(' ) ) { import_function( $root . '/includes/functions/wcj-functions-booster-core.php', $f ); }
}
class WCJ_Module { public $id; public $short_desc; public $desc; public $desc_pro; public $link_slug; public function __construct() {} public function is_enabled() { return wcj_is_module_enabled( $this->id ); } }
class WC_Payment_Gateway {
	public $id; public $settings = array(); public $form_fields = array(); public $title; public $description; public $icon; public $method_title; public $method_description; public $has_fields; public $enabled = 'yes';
	public function init_settings() { $this->settings = get_option( 'woocommerce_' . $this->id . '_settings', array() ); $this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no'; }
	public function get_option( $key, $default = null ) { if ( ! $this->settings ) { $this->init_settings(); } return isset( $this->settings[$key] ) ? $this->settings[$key] : ( null === $default ? ( isset( $this->form_fields[$key]['default'] ) ? $this->form_fields[$key]['default'] : '' ) : $default ); }
	public function get_title() { return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id ); }
	public function is_available() { return 'yes' === $this->enabled; }
	public function validate_fields() { return true; }
	public function get_return_url( $order ) { return '/synthetic-order-received'; }
}
class WP_REST_Request implements ArrayAccess {
	private $method; private $route; private $data;
	public function __construct( $method, $route, $data = array() ) { $this->method = $method; $this->route = $route; $this->data = $data; }
	public function get_method() { return $this->method; } public function get_route() { return $this->route; }
	public function set_url_params( $args ) {} public function set_attributes( $attributes ) {} public function set_default_params( $defaults ) {}
	public function offsetExists( $k ): bool { return isset( $this->data[$k] ); } public function offsetGet( $k ): mixed { return isset( $this->data[$k] ) ? $this->data[$k] : null; }
	public function offsetSet( $k, $v ): void { $this->data[$k] = $v; } public function offsetUnset( $k ): void { unset( $this->data[$k] ); }
}
class WC_Order {
	public $method; public $meta = array( '_wcj_custom_payment_gateway_input_fields' => array( 'old' => 'stale' ) ); public $writes = 0; public $deletes = 0; public $saves = 0; public $status_updates = array();
	public function __construct( $id = 1 ) { $this->method = 'jetpack_custom_gateway'; }
	public function get_payment_method() { return $this->method; } public function update_meta_data( $k, $v ) { ++$this->writes; $this->meta[$k] = $v; }
	public function delete_meta_data( $k ) { ++$this->deletes; unset( $this->meta[$k] ); } public function save() { ++$this->saves; }
	public function get_id() { return 1; } public function update_status( $status, $note ) { $this->status_updates[] = array( $status, $note ); }
}
class Fixture_Cart { public $total = 100; public $fee_total = 5; public $empties = 0; public function needs_shipping() { return true; } public function empty_cart() { ++$this->empties; } }
class Fixture_Pool {
	public $gateways = null;
	public function payment_gateways() { if ( null === $this->gateways ) { $this->gateways = array(); foreach ( apply_filters( 'woocommerce_payment_gateways', array() ) as $g ) { $this->gateways[$g->id] = $g; } } return $this->gateways; }
	public function get_available_payment_gateways() { return array_filter( $this->payment_gateways(), function ( $g ) { return $g->is_available(); } ); }
}
class Fixture_Woo { public $pool; public $payment_gateways; public $cart; public $singleton_reads = 0; public function __construct() { $this->pool = new Fixture_Pool(); $this->payment_gateways = $this->pool; $this->cart = new Fixture_Cart(); } public function payment_gateways() { ++$this->singleton_reads; return $this->pool; } }
$woo = new Fixture_Woo(); function WC() { global $woo; return $woo; }
$options = array( 'wcj_payment_gateways_enabled' => 'yes', 'wcj_modules_by_user_roles_enabled' => 'no', 'wcj_custom_payment_gateways_number' => 3, 'wcj_load_modules_on_init' => 'late' === $mode ? 'yes' : 'no' );
$description = 'Before [wcj_input_field name="ref" label="Reference" required="yes"] After';
foreach ( array( '', '_2', '_3' ) as $suffix ) { $options['woocommerce_jetpack_custom_gateway' . $suffix . '_settings'] = array( 'enabled' => 'yes', 'title' => 'Synthetic gateway', 'description' => $description ); }
$role = 'shop_manager'; $admin = false; $capability = true; $screen = 'woocommerce_page_wc-settings'; $option_reads = array();
if ( 'free' !== basename( $root ) ) { require $root . '/includes/plus/class-wcj-plus-filters.php'; }
require $root . '/includes/classes/class-wcj-shortcodes.php';
$renderer = require $root . '/includes/shortcodes/class-wcj-input-field-shortcodes.php';
$module = require $root . '/includes/class-wcj-payment-gateways.php';
do_action( 'plugins_loaded' );
require $upstream . '/woocommerce/src/StoreApi/Exceptions/RouteException.php';
if ( 'route-parity' === $mode ) { require_once $root . '/includes/gateways/class-wcj-custom-payment-gateway-input-schema.php'; }
if ( 'route-parity' !== $mode ) {
// Classic is intentionally unchanged: nested missing required does not generate errors.
$errors = new class { public $errors = array(); public function add( $a, $b ) { $this->errors[] = $b; } };
$_POST = array( 'wcj_input_field_jetpack_custom_gateway' => array( 'ref' => array( 'value' => '', 'label' => 'Reference' ) ), 'for_wcj_input_field_ref' => 'jetpack_custom_gateway', 'wcj_input_field_ref_required' => 'yes' );
$_REQUEST = array( 'woocommerce-process-checkout-nonce' => 'synthetic-valid' );
$module->check_required_wcj_input_fields( array( 'payment_method' => 'jetpack_custom_gateway' ), $errors );
check( 'C2 Classic nested required stays unenforced', count( $errors->errors ), 0 );
check( 'no early gateway singleton', $woo->singleton_reads, 0 );
check( 'actual final POST callback exists', false !== has_filter( 'woocommerce_store_api_checkout_update_order_from_request' ), true );
check( 'actual registration callback exists', false !== has_filter( 'woocommerce_blocks_payment_method_type_registration' ), true );
$helper = $root . '/includes/gateways/class-wcj-custom-payment-gateway-input-schema.php';
check( 'schema production file exists', is_file( $helper ), true );
if ( ! is_file( $helper ) ) {
	$o = new WC_Order(); $q = new WP_REST_Request( 'POST', '/wc/store/v1/checkout', array( 'payment_method' => 'jetpack_custom_gateway' ) ); $denied = false;
	try { do_action( 'woocommerce_store_api_checkout_update_order_from_request', $o, $q ); } catch ( Throwable $e ) { $denied = true; }
	check( 'missing required native input is rejected', $denied, true ); check( 'rejected native input clears stale meta', isset( $o->meta['_wcj_custom_payment_gateway_input_fields'] ), false ); result();
}
if ( 'no-api' === $mode ) {
	check( 'POST pure helper not loaded by registration', class_exists( 'WCJ_Custom_Payment_Gateway_Input_Schema', false ), false );
	$module->register_custom_gateway_blocks( new stdClass() );
	$o = new WC_Order(); $r = new WP_REST_Request( 'POST', '/wc/store/v1/checkout', array( 'payment_method' => 'jetpack_custom_gateway' ) ); $caught = false;
	try { $module->update_custom_gateway_blocks_fields( $o, $r ); } catch ( Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) { $caught = true; }
	check( 'POST requires pure helper without Blocks API', class_exists( 'WCJ_Custom_Payment_Gateway_Input_Schema', false ), true ); check( 'POST cannot bypass input without Blocks API', $caught, true ); check( 'no Blocks class loaded', class_exists( 'WCJ_Custom_Payment_Gateway_Blocks', false ), false );
}
require_once $helper;
if ( 'no-api' !== $mode ) {
	foreach ( array( 'Integrations/IntegrationInterface.php', 'Payments/PaymentMethodTypeInterface.php', 'Payments/Integrations/AbstractPaymentMethodType.php', 'Integrations/IntegrationRegistry.php', 'Payments/PaymentMethodRegistry.php' ) as $file ) { require $upstream . '/woocommerce/src/Blocks/' . $file; }
	$registry = new Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry();
	do_action( 'woocommerce_blocks_payment_method_type_registration', $registry );
	check( 'registration did not build singleton', $woo->singleton_reads, 0 );
	check( 'existing edition count', count( $registry->get_all_registered() ), 'free' === basename( $root ) ? 1 : 3 );
	do_action( 'woocommerce_blocks_payment_method_type_registration', $registry );
	check( 'registers each candidate once', count( $registry->get_all_registered() ), 'free' === basename( $root ) ? 1 : 3 );
	$integration = $registry->get_registered( 'jetpack_custom_gateway' ); $integration->initialize();
	check( 'options-only active', $integration->is_active(), true ); check( 'active did not build singleton', $woo->singleton_reads, 0 );
	$data = $integration->get_payment_method_data(); check( 'actual integration data', $data['profile'], 1 ); check( 'actual fields returned', $data['fields'][0]['name'], 'ref' );
	$integration->get_payment_method_script_handles(); $integration->get_payment_method_script_handles(); check( 'script registered once', count( $scripts ), 1 );
}
}
function schema( $description, $id = 'jetpack_custom_gateway' ) { return WCJ_Custom_Payment_Gateway_Input_Schema::build( $id, $description ); }
function unsupported( $name, $description ) { $s = schema( $description ); check( $name, isset( $s['unsupported'] ), true ); }
if ( 'route-parity' !== $mode ) {
$s = schema( $description );
check( 'canonical name', $s['fields'][0]['name'], 'ref' ); check( 'canonical required', $s['fields'][0]['required'], true );
check( 'canonical identity hash', $s['hash'], hash( 'sha256', json_encode( array( 'jetpack_custom_gateway', array( array( 'ref', 'Reference', true, 'text' ) ) ) ) ) );
check( 'segments preserve order', $s['segments'], array( 'Before ', ' After' ) );
foreach ( array( 'file', 'email', 'hidden', 'checkbox', 'password', 'number', 'TEXT' ) as $type ) { unsupported( 'unsupported type ' . $type, '[wcj_input_field name="ref" type="' . $type . '"]' ); }
foreach ( array( 'visibility', 'before', 'after', 'find', 'replace', 'time', 'module', 'data_attributes', 'plus', 'strip_tags' ) as $key ) { unsupported( 'wrapper attribute ' . $key, '[wcj_input_field name="ref" ' . $key . '="x"]' ); }
foreach ( array( 'form', 'input', 'select', 'option', 'textarea', 'button', 'script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'base', 'svg', 'math', 'template' ) as $tag ) { unsupported( 'raw tag ' . $tag, '<' . strtoupper( $tag ) . '>unsafe</' . $tag . '>' ); }
foreach ( array( '[wcj_input_field_x name="x"]', '[wcj_input_fields name="x"]', '[[wcj_input_field name="x"]]', '[wcj_input_field name="x"]content[/wcj_input_field]', '[wcj_input_field name="x"][/wcj_input_field]', '<p title="[wcj_input_field name=x]">Text</p>', '<!-- [wcj_input_field name=x] -->', '[wcj_input_field name="x"', '[wcj_input_field "x"]', '[wcj_input_field name="x" required="maybe"]', '[wcj_input_field name="x" attach_to="other"]', '[wcj_input_field name="x" name_array="other"]', '[wcj_input_field name="<x>"]' ) as $i => $bad ) { unsupported( 'malformed/ambiguous/config ' . $i, $bad ); }
unsupported( 'duplicate normalized name', '[wcj_input_field name="a!"][wcj_input_field name="a"]' );
unsupported( 'duplicate storage label', '[wcj_input_field name="a" label="L"][wcj_input_field name="b" label="L"]' );
unsupported( 'missing name', '[wcj_input_field]' ); unsupported( 'configured zero name collapses', '[wcj_input_field name="0"]' );
unsupported( 'description byte limit', str_repeat( 'x', 32769 ) ); unsupported( 'invalid UTF8 description', "\xff" ); unsupported( 'name byte limit', '[wcj_input_field name="' . str_repeat( 'x', 65 ) . '"]' );
foreach ( array( 'label', 'placeholder', 'value', 'class' ) as $attr ) { unsupported( 'attribute limit ' . $attr, '[wcj_input_field name="x" ' . $attr . '="' . str_repeat( 'x', 257 ) . '"]' ); }
$many = ''; for ( $i = 0; $i < 21; ++$i ) { $many .= '[wcj_input_field name="x' . $i . '"]'; }
unsupported( 'field limit', $many );
$z = schema( '[wcj_input_field name="a" value="0" placeholder="0" label="0"]' ); check( 'existing config zero normalization', array( $z['fields'][0]['default'], $z['fields'][0]['placeholder'], $z['fields'][0]['label'] ), array( '', '', '' ) );
foreach ( array( 'wcj_shortcodes_kses', 'pre_do_shortcode_tag', 'do_shortcode_tag', 'woocommerce_gateway_description' ) as $hook ) { $cb = function ( $x ) { return $x; }; add_filter( $hook, $cb ); unsupported( 'hook rejection ' . $hook, $description ); remove_filter( $hook, $cb ); }
class Fixture_Override extends WCJ_Shortcodes { public function add_result_key_param_to_shortcode_result( $a, $b, $c, $d ) { return $a; } }
$over = new Fixture_Override(); unsupported( 'same-name overriding result callback', $description ); remove_filter( 'wcj_shortcode_result', array( $over, 'add_result_key_param_to_shortcode_result' ) );
$old_renderer = $shortcode_tags['wcj_input_field']; $shortcode_tags['wcj_input_field'] = function () { return 'fake'; }; unsupported( 'replaced renderer', $description ); $shortcode_tags['wcj_input_field'] = $old_renderer;
add_shortcode( 'foreign', function () { return 'dynamic'; } ); unsupported( 'other registered shortcode', '[foreign]' ); remove_shortcode( 'foreign' );
}
// F1: instantiate the complete pinned WordPress router, not a rewritten matcher.
require $upstream . '/wordpress/wp-includes/class-wp-error.php';
require $upstream . '/wordpress/wp-includes/class-wp-list-util.php';
foreach ( array( 'wp_parse_args', 'wp_filter_object_list', 'wp_list_filter' ) as $f ) { import_function( $upstream . '/wordpress/wp-includes/functions.php', $f ); }
import_function( $upstream . '/wordpress/wp-includes/formatting.php', 'untrailingslashit' );
import_function( $upstream . '/wordpress/wp-includes/formatting.php', 'trailingslashit' );
require $upstream . '/wordpress/wp-includes/rest-api/class-wp-rest-server.php';
$router = new WP_REST_Server();
foreach ( array( 'wc/store', 'wc/store/v1' ) as $namespace ) { $router->register_route( $namespace, '/' . $namespace . '/checkout', array( array( 'methods' => 'POST', 'callback' => function () {}, 'args' => array() ) ) ); }
$match_request = new ReflectionMethod( WP_REST_Server::class, 'match_request_to_handler' ); $match_request->setAccessible( true );
// Actual final POST handler; only Woo primitives are synthetic.
$pool = $woo->pool->payment_gateways(); $gateway = $pool['jetpack_custom_gateway']; $gateway->settings['description'] = $description; $gateway->min_amount = 0;
function envelope( $schema, $pairs, $id = 'jetpack_custom_gateway' ) { return json_encode( array( 1, $id, $schema['hash'], $pairs ) ); }
function post_case( $name, $raw, $reject = true, $method = 'POST', $route = '/wc/store/v1/checkout', $id = 'jetpack_custom_gateway', $order_id = null, $extra = array() ) {
	global $module; $o = new WC_Order(); $o->method = null === $order_id ? $id : $order_id;
	$d = array( 'payment_method' => $id, 'payment_data' => $extra ); if ( null !== $raw ) { $d['payment_data'][] = array( 'key' => 'wcj_custom_gateway_fields', 'value' => $raw ); }
	$r = new WP_REST_Request( $method, $route, $d ); $caught = false;
	try { $module->update_custom_gateway_blocks_fields( $o, $r ); } catch ( Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) { $caught = true; check( $name . ' status', $e->getCode(), 400 ); }
	check( $name . ' rejection', $caught, $reject ); check( $name . ' no own save', $o->saves, 0 );
	if ( $reject ) { check( $name . ' clears stale meta', isset( $o->meta['_wcj_custom_payment_gateway_input_fields'] ), false ); check( $name . ' no write', $o->writes, 0 ); }
	return $o;
}
$s = schema( $description );
if ( 'route-parity' === $mode ) {
	// F1-R only: real pinned router matching, actual module hook, synthetic Woo-owned order.
	$routes = array(
		"/wc/store/v1/checkout\n" => '/wc/store/v1/checkout',
		"/wc/store/v1/Checkout\n" => '/wc/store/v1/checkout',
		"/WC/STORE/CHECKOUT\n" => '/wc/store/checkout',
		"/wc/store/checkout\n" => '/wc/store/checkout',
	);
	foreach ( $routes as $route => $canonical ) {
		$name = 'F1-R ' . json_encode( $route );
		$matched = $match_request->invoke( $router, new WP_REST_Request( 'POST', $route ) );
		check( $name . ' actual public router accepts', is_array( $matched ) ? $matched[0] : null, $canonical );
		$o = post_case( $name . ' valid', envelope( $s, array( array( 'ref', '0' ) ) ), false, 'POST', $route );
		check( $name . ' exact server-labelled single write', array( $o->writes, $o->deletes, $o->meta ), array( 1, 0, array( '_wcj_custom_payment_gateway_input_fields' => array( 'Reference' => '0' ) ) ) );
		post_case( $name . ' missing required', null, true, 'POST', $route );
		$options['wcj_modules_by_user_roles_enabled'] = 'yes';
		$options['wcj_modules_by_user_roles_excl_shop_manager'] = array( 'payment_gateways' );
		unset( $wcj_modules_by_user_roles_data );
		post_case( $name . ' current role denied', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route );
		unset( $wcj_modules_by_user_roles_data );
		$options['wcj_modules_by_user_roles_enabled'] = 'no';
		$options['wcj_modules_by_user_roles_excl_shop_manager'] = array();
	}
	$route = "/WC/STORE/V1/CHECKOUT\n";
	post_case( 'F1-R order selected gateway mismatch', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route, 'jetpack_custom_gateway', 'other' );
	$options['wcj_modules_by_user_roles_enabled'] = 'yes';
	$wcj_modules_by_user_roles_data = array( 'role' => 'administrator', 'modules_incl' => '', 'modules_excl' => '' );
	post_case( 'F1-R stale first-role context denied', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route );
	unset( $wcj_modules_by_user_roles_data );
	$options['wcj_modules_by_user_roles_enabled'] = 'no';
	$options['wcj_payment_gateways_enabled'] = 'no';
	post_case( 'F1-R current module disabled', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route );
	$options['wcj_payment_gateways_enabled'] = 'yes';
	$gateway->settings['enabled'] = 'no'; $gateway->enabled = 'yes';
	post_case( 'F1-R currently configured gateway disabled', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route );
	$gateway->settings['enabled'] = 'yes';
	$saved_pool = $woo->pool->gateways; unset( $woo->pool->gateways['jetpack_custom_gateway'] );
	post_case( 'F1-R current gateway absent', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $route );
	$woo->pool->gateways = $saved_pool;
	foreach ( array( '/wc/store/v1/checkout/1', "/wc/store/v1/checkout/1\n", '/wc/store/checkout/1', "/WC/STORE/CHECKOUT/1\n", '/wc/store/v1/checkout/', "/wc/store/v1/checkout/\n", "/WC/STORE/CHECKOUT\n\n", "/wc/store/v1/checkout\r", '/wc/store/v1/checkoutextra', '/foreign/checkout' ) as $excluded ) {
		$name = 'F1-R excluded ' . json_encode( $excluded );
		$matched = $match_request->invoke( $router, new WP_REST_Request( 'POST', $excluded ) );
		check( $name . ' actual public router rejects', $matched instanceof WP_Error, true );
		$o = post_case( $name, null, false, 'POST', $excluded );
		check( $name . ' no metadata mutation', array( $o->writes, $o->deletes, $o->meta ), array( 0, 0, array( '_wcj_custom_payment_gateway_input_fields' => array( 'old' => 'stale' ) ) ) );
	}
	foreach ( array( 'GET', 'PATCH' ) as $method ) {
		$matched = $match_request->invoke( $router, new WP_REST_Request( $method, $route ) );
		check( 'F1-R non-POST ' . $method . ' actual public router rejects', $matched instanceof WP_Error, true );
		$o = post_case( 'F1-R non-POST ' . $method, null, false, $method, $route );
		check( 'F1-R non-POST ' . $method . ' no metadata mutation', array( $o->writes, $o->deletes, $o->meta ), array( 0, 0, array( '_wcj_custom_payment_gateway_input_fields' => array( 'old' => 'stale' ) ) ) );
	}
	$o = new WC_Order(); $reads = $woo->singleton_reads;
	$module->update_custom_gateway_blocks_fields( $o, new stdClass() );
	$module->update_custom_gateway_blocks_fields( new stdClass(), new WP_REST_Request( 'POST', $route ) );
	check( 'F1-R wrong callback object types leave order untouched', array( $o->writes, $o->deletes, $o->saves, $o->meta ), array( 0, 0, 0, array( '_wcj_custom_payment_gateway_input_fields' => array( 'old' => 'stale' ) ) ) );
	check( 'F1-R wrong callback object types do not read gateway singleton', $woo->singleton_reads, $reads );
	check( 'F1-R no cart/processor mutations', array( $woo->cart->empties, $woo->cart->total, $woo->cart->fee_total ), array( 0, 100, 5 ) );
	result();
}
$o = post_case( 'zero string accepted', envelope( $s, array( array( 'ref', '0' ) ) ), false ); check( 'server label and zero storage', $o->meta['_wcj_custom_payment_gateway_input_fields'], array( 'Reference' => '0' ) ); check( 'single atomic write', $o->writes, 1 );
foreach ( array( '/wc/store/v1/Checkout' => '/wc/store/v1/checkout', '/WC/STORE/V1/CHECKOUT' => '/wc/store/v1/checkout', '/wc/STORE/Checkout' => '/wc/store/checkout', '/WC/STORE/CHECKOUT' => '/wc/store/checkout' ) as $case_route => $canonical ) {
	$matched = $match_request->invoke( $router, new WP_REST_Request( 'POST', $case_route ) );
	check( 'F1 actual WP router mixed case ' . $case_route, is_array( $matched ) ? $matched[0] : null, $canonical );
	$o = post_case( 'F1 mixed valid ' . $case_route, envelope( $s, array( array( 'ref', '0' ) ) ), false, 'POST', $case_route );
	check( 'F1 mixed exact write ' . $case_route, $o->writes, 1 ); check( 'F1 mixed server label ' . $case_route, $o->meta['_wcj_custom_payment_gateway_input_fields'], array( 'Reference' => '0' ) );
	post_case( 'F1 mixed required ' . $case_route, null, true, 'POST', $case_route );
}
$case_route = '/WC/STORE/V1/CHECKOUT';
$gateway->settings['description'] = '[wcj_input_field name="x" type="file"]'; post_case( 'F1 mixed unsupported', null, true, 'POST', $case_route ); $gateway->settings['description'] = $description;
$options['wcj_modules_by_user_roles_enabled'] = 'yes'; $options['wcj_modules_by_user_roles_excl_shop_manager'] = array( 'payment_gateways' ); unset( $wcj_modules_by_user_roles_data ); post_case( 'F1 mixed role excluded', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $case_route ); unset( $wcj_modules_by_user_roles_data ); $options['wcj_modules_by_user_roles_enabled'] = 'no'; $options['wcj_modules_by_user_roles_excl_shop_manager'] = array();
$gateway->settings['enabled'] = 'no'; $gateway->enabled = 'yes'; post_case( 'F1 mixed configured disabled', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $case_route ); $gateway->settings['enabled'] = 'yes';
$case_saved = $woo->pool->gateways; unset( $woo->pool->gateways['jetpack_custom_gateway'] ); post_case( 'F1 mixed absent WCJ', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', $case_route ); $woo->pool->gateways = $case_saved;
foreach ( array( '/WC/STORE/V1/CHECKOUT/1', '/wc/store/Checkout/1', '/WC/STORE/V1/CHECKOUTextra', '/WC/STORE/V1/CHECKOUT/', '/foreign/Checkout' ) as $excluded ) { $o = post_case( 'F1 exact exclusion ' . $excluded, null, false, 'POST', $excluded ); check( 'F1 excluded untouched ' . $excluded, array( $o->writes, $o->deletes ), array( 0, 0 ) ); }
$o = post_case( 'F1 mixed GET unchanged', null, false, 'GET', $case_route ); check( 'F1 mixed GET untouched', array( $o->writes, $o->deletes ), array( 0, 0 ) );
post_case( 'required absent envelope', null ); post_case( 'required empty', envelope( $s, array( array( 'ref', '' ) ) ) ); post_case( 'required sanitizes to empty', envelope( $s, array( array( 'ref', '<b></b>' ) ) ) );
post_case( 'wrong gateway', envelope( $s, array( array( 'ref', 'value' ) ), 'jetpack_custom_gateway_2' ) ); post_case( 'order selected identity mismatch', envelope( $s, array( array( 'ref', 'value' ) ) ), true, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', 'other' );
post_case( 'extra pair', envelope( $s, array( array( 'ref', 'value' ), array( 'extra', 'value' ) ) ) ); post_case( 'wrong name', envelope( $s, array( array( 'unknown', 'value' ) ) ) ); post_case( 'object pair value', envelope( $s, array( array( 'ref', (object) array( '0' => 'x' ) ) ) ) );
post_case( 'numeric key JSON object', '{"0":1,"1":"jetpack_custom_gateway","2":"' . $s['hash'] . '","3":[["ref","x"]]}' );
post_case( 'version string rejected', '["1","jetpack_custom_gateway","' . $s['hash'] . '",[["ref","x"]]]' ); post_case( 'wrong hash', '[1,"jetpack_custom_gateway","' . str_repeat( 'a', 64 ) . '",[["ref","x"]]]' ); post_case( 'wire oversized', str_repeat( ' ', 98305 ) );
foreach ( array( "\0", "\t", "\n", "\x7f", "\xff", str_repeat( 'x', 2049 ) ) as $i => $bad ) { $raw = false === json_encode( $bad ) ? '[1,"jetpack_custom_gateway","' . $s['hash'] . '",[["ref","' . $bad . '"]]]' : envelope( $s, array( array( 'ref', $bad ) ) ); post_case( 'value guard ' . $i, $raw ); }
$extra = array( array( 'key' => 'wcj_custom_gateway_fields', 'value' => envelope( $s, array( array( 'ref', 'x' ) ) ) ) ); post_case( 'duplicate raw envelope', envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', null, $extra );
foreach ( array( 'WCJ_CUSTOM_GATEWAY_FIELDS', 'wcj_custom_gateway_fields!' ) as $alias ) { post_case( 'aliased raw key ' . $alias, envelope( $s, array( array( 'ref', 'x' ) ) ), true, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', null, array( array( 'key' => $alias, 'value' => 'x' ) ) ); }
$o = post_case( 'Woo own boolean entry allowed', envelope( $s, array( array( 'ref', 'x' ) ) ), false, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', null, array( array( 'key' => 'wc-jetpack_custom_gateway-new-payment-method', 'value' => false ) ) ); check( 'Woo boolean entry not copied', $o->meta['_wcj_custom_payment_gateway_input_fields'], array( 'Reference' => 'x' ) );
foreach ( array( array( 'PATCH', '/wc/store/v1/checkout' ), array( 'GET', '/wc/store/v1/checkout' ), array( 'POST', '/wc/store/v1/checkout/order/1' ), array( 'POST', '/foreign/checkout' ) ) as $i => $guard ) { $o = post_case( 'route/method guard ' . $i, null, false, $guard[0], $guard[1] ); check( 'guard no deletion ' . $i, $o->deletes, 0 ); check( 'guard no write ' . $i, $o->writes, 0 ); }
$o = post_case( 'no method clears stale', null, false, 'POST', '/wc/store/v1/checkout', '' ); check( 'no method stale absent', isset( $o->meta['_wcj_custom_payment_gateway_input_fields'] ), false );
$gateway->settings['description'] = 'Static description'; $zero = schema( 'Static description' );
$o = post_case( 'zero fields absent envelope allowed', null, false ); check( 'zero fields clears stale', isset( $o->meta['_wcj_custom_payment_gateway_input_fields'] ), false );
post_case( 'zero fields exact envelope allowed', envelope( $zero, array() ), false ); post_case( 'zero fields extra values reject', envelope( $zero, array( array( 'ref', 'x' ) ) ) );
$gateway->settings['description'] = '[wcj_input_field name="x" type="file"]'; post_case( 'unsupported gateway denied', null );
$gateway->settings['description'] = $description;
$options['wcj_modules_by_user_roles_enabled'] = 'yes'; $options['wcj_modules_by_user_roles_excl_shop_manager'] = array( 'payment_gateways' ); unset( $wcj_modules_by_user_roles_data ); post_case( 'actual role exclusion', envelope( $s, array( array( 'ref', 'x' ) ) ) );
$options['wcj_modules_by_user_roles_excl_shop_manager'] = array(); $wcj_modules_by_user_roles_data = array( 'role' => 'administrator', 'modules_incl' => '', 'modules_excl' => '' ); post_case( 'stale first role denied', envelope( $s, array( array( 'ref', 'x' ) ) ) );
unset( $wcj_modules_by_user_roles_data ); $options['wcj_modules_by_user_roles_enabled'] = 'no'; $options['wcj_payment_gateways_enabled'] = 'no'; post_case( 'module disabled after registration', envelope( $s, array( array( 'ref', 'x' ) ) ) ); $options['wcj_payment_gateways_enabled'] = 'yes';
if ( function_exists( 'wcj_is_module_allowed_by_allowlist' ) ) { $allow = function () { return array( 'other' ); }; add_filter( 'wcj_enabled_module_allowlist', $allow ); post_case( 'Elite allowlist denied', envelope( $s, array( array( 'ref', 'x' ) ) ) ); remove_filter( 'wcj_enabled_module_allowlist', $allow ); }
check( 'POST never mutates cart', $woo->cart->empties, 0 );
// Finite profile/transport boundaries, separate from any native claim.
$twodesc = '[wcj_input_field name="a" label="A"][wcj_input_field name="b" label="B" required="yes"]';
$two = schema( $twodesc ); $gateway->settings['description'] = $twodesc;
$o = post_case( 'two configured pairs', envelope( $two, array( array( 'a', '' ), array( 'b', '<b>0</b>' ) ) ), false ); check( 'optional empty and sanitized server labels', $o->meta['_wcj_custom_payment_gateway_input_fields'], array( 'A' => '', 'B' => '0' ) );
post_case( 'wrong pair ordering', envelope( $two, array( array( 'b', 'v' ), array( 'a', 'v' ) ) ) );
post_case( 'pair object refused', '[1,"jetpack_custom_gateway","' . $two['hash'] . '",[{"0":"a","1":"v"},["b","v"]]]' );
post_case( 'pair list object refused', '[1,"jetpack_custom_gateway","' . $two['hash'] . '",{"0":["a","v"],"1":["b","v"]}]' );
$gateway->settings['description'] = $description;
post_case( 'boolean envelope refused', null, true, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', null, array( array( 'key' => 'wcj_custom_gateway_fields', 'value' => false ) ) );
post_case( 'own row extra key refused', null, true, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway', null, array( array( 'key' => 'wcj_custom_gateway_fields', 'value' => envelope( $s, array( array( 'ref', 'v' ) ) ), 'extra' => true ) ) );
$o = post_case( 'valid Unicode accepted', envelope( $s, array( array( 'ref', '界😀' ) ) ), false ); check( 'Unicode stored without truncation', $o->meta['_wcj_custom_payment_gateway_input_fields'], array( 'Reference' => '界😀' ) );
$o = post_case( 'unversioned checkout route supported', envelope( $s, array( array( 'ref', 'v' ) ) ), false, 'POST', '/wc/store/checkout' ); check( 'unversioned single write', $o->writes, 1 );
$wire = envelope( $s, array( array( 'ref', 'v' ) ) ); json_decode( $wire, false, 3 ); check( 'canonical depth3 insufficient', json_last_error(), JSON_ERROR_DEPTH ); json_decode( $wire, false, 4 ); check( 'canonical depth4 sufficient', json_last_error(), JSON_ERROR_NONE ); check( 'production minimal decode depth', WCJ_Custom_Payment_Gateway_Input_Schema::JSON_DEPTH, 4 );
$maxdesc = ''; $pairs = array(); for ( $i = 0; $i < 20; ++$i ) { $name = str_pad( 'n' . $i, 64, 'x' ); $maxdesc .= '[wcj_input_field name="' . $name . '" label="' . str_pad( 'L' . $i, 256, 'x' ) . '" placeholder="' . str_repeat( 'x', 256 ) . '" value="' . str_repeat( 'x', 256 ) . '" class="' . str_repeat( 'x', 256 ) . '"]'; $pairs[] = array( $name, str_repeat( '"', 2048 ) ); }
$max = schema( $maxdesc ); check( 'complete canonical maximum20 fields', count( $max['fields'] ), 20 ); $maxwire = envelope( $max, $pairs ); check( 'canonical escaped maximum fits wire', strlen( $maxwire ) <= 98304, true );
$gateway->settings['description'] = $maxdesc; $o = post_case( 'canonical max wire accepted', $maxwire, false ); check( 'max complete atomic storage', count( $o->meta['_wcj_custom_payment_gateway_input_fields'] ), 20 );
$ascii = str_replace( '\\"', '\\u0022', $maxwire ); check( 'equivalent headless escaping exceeds wire cap', strlen( $ascii ) > 98304, true ); post_case( 'headless escaping oversized rejects', $ascii );
$gateway->settings['description'] = $description;
// Actual available-map identity, including explicit stale configured-disabled object.
$gateway->settings['enabled'] = 'no'; $gateway->enabled = 'yes'; check( 'synthetic stale available disabled object exists', isset( $woo->pool->get_available_payment_gateways()['jetpack_custom_gateway'] ), true ); post_case( 'configured-disabled native profile rejects stale available object', $wire );
if ( 'no-api' !== $mode ) { check( 'configured-disabled data absent', $integration->get_payment_method_data(), array() ); $options['woocommerce_jetpack_custom_gateway_settings']['enabled'] = 'no'; $integration->initialize(); check( 'configured-disabled options-only inactive', $integration->is_active(), false ); $options['woocommerce_jetpack_custom_gateway_settings']['enabled'] = 'yes'; $integration->initialize(); }
$gateway->settings['enabled'] = 'yes'; $gateway->enabled = 'no'; post_case( 'unavailable WCJ rejects', $wire ); $gateway->enabled = 'yes';
$saved = $woo->pool->gateways; unset( $woo->pool->gateways['jetpack_custom_gateway'] ); post_case( 'absent exact WCJ rejects', $wire ); $woo->pool->gateways = $saved;
$o = post_case( 'unrelated prefix is nonWCJ', null, false, 'POST', '/wc/store/v1/checkout', 'jetpack_custom_gateway_x' ); check( 'unrelated prefix cleanup only', $o->deletes, 1 );
$o = post_case( 'ordinary nonWCJ cleanup', null, false, 'POST', '/wc/store/v1/checkout', 'bacs' ); check( 'ordinary nonWCJ cleanup only', $o->deletes, 1 );
$module->update_custom_gateway_blocks_fields( new stdClass(), new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) ); check( 'nonorder guard completed safely', true, true );
$o = new WC_Order(); $module->update_custom_gateway_blocks_fields( $o, new stdClass() ); check( 'nonrequest guard untouched', array( $o->writes, $o->deletes ), array( 0, 0 ) );
foreach ( array( null, array( 'bad' ) ) as $badid ) { post_case( 'nonscalar method ' . gettype( $badid ), $wire, is_array( $badid ), 'POST', '/wc/store/v1/checkout', $badid ); }
$options['wcj_modules_by_user_roles_enabled'] = 'yes';
foreach ( array( 'bad-cache', array(), array( 'role' => 'shop_manager' ) ) as $badcache ) { $wcj_modules_by_user_roles_data = $badcache; post_case( 'malformed role cache ' . gettype( $badcache ) . count( (array) $badcache ), $wire ); }
$wcj_modules_by_user_roles_data = array( 'role' => 'shop_manager', 'modules_incl' => array(), 'modules_excl' => array() ); post_case( 'matching cached role allowed', $wire, false );
$role = ''; unset( $wcj_modules_by_user_roles_data ); $options['wcj_modules_by_user_roles_excl_guest'] = array( 'payment_gateways' ); post_case( 'guest excluded actual policy', $wire );
$role = 'shop_manager'; unset( $wcj_modules_by_user_roles_data ); $options['wcj_modules_by_user_roles_incl_shop_manager'] = array( 'other' ); post_case( 'include whitelist actual policy denied', $wire ); $options['wcj_modules_by_user_roles_incl_shop_manager'] = array( 'payment_gateways' ); unset( $wcj_modules_by_user_roles_data ); post_case( 'include whitelist actual policy allowed', $wire, false ); unset( $wcj_modules_by_user_roles_data ); $options['wcj_modules_by_user_roles_enabled'] = 'no';
if ( 'no-api' !== $mode ) {
	check( 'late gateway absent before added filter', isset( $woo->pool->gateways['late_foreign'] ), false );
	// Registration had no singleton reads; use a fresh synthetic singleton to demonstrate late filter visibility.
	$woo->pool->gateways = null; $latecb = function ( $items ) { $x = new WC_Payment_Gateway(); $x->id = 'late_foreign'; $items[] = $x; return $items; }; add_filter( 'woocommerce_payment_gateways', $latecb, 500 );
	check( 'later gateway filter remains visible', isset( $woo->pool->payment_gateways()['late_foreign'] ), true ); remove_filter( 'woocommerce_payment_gateways', $latecb, 500 ); $woo->pool->gateways = $saved;
}
// Permission check precedes every admin options/dependency read.
$current_tab = 'checkout'; $_GET = array( 'tab' => 'checkout' ); $admin = true; $capability = false; $option_reads = array(); $before = $woo->singleton_reads; ob_start(); $module->custom_gateway_blocks_admin_notice(); $notice = ob_get_clean(); check( 'admin denied no output', $notice, '' ); check( 'admin denied no option reads', $option_reads, array() ); check( 'admin denied no gateway singleton', $woo->singleton_reads, $before );
$capability = true; $screen = 'other'; $option_reads = array(); ob_start(); $module->custom_gateway_blocks_admin_notice(); check( 'admin wrong screen no output', ob_get_clean(), '' ); check( 'admin wrong screen no options', $option_reads, array() );
$screen = 'woocommerce_page_wc-settings'; $gateway->settings['description'] = '[wcj_input_field name="sensitive-config" type="file"]'; $gateway->method_title = '<b>Authorized title</b>'; ob_start(); $module->custom_gateway_blocks_admin_notice(); $notice = ob_get_clean(); check( 'admin fixed complete rejection notice', strpos( $notice, 'Classic checkout is unaffected.' ) !== false, true ); check( 'admin title escaped', strpos( $notice, '&lt;b&gt;Authorized title&lt;/b&gt;' ) !== false, true ); check( 'admin no raw configured input names', strpos( $notice, 'sensitive-config' ), false ); $gateway->settings['description'] = $description; $admin = false;
// F2: verify the actual Woo view hook survives generic admin-notice suppression.
$admin = true; $capability = true; $screen = 'woocommerce_page_wc-settings'; $current_tab = 'checkout';
$gateway->settings['description'] = '[wcj_input_field name="sensitive-config" type="file"]';
check( 'F2 notice registered on actual Payments view hook', has_filter( 'woocommerce_before_settings_checkout', array( $module, 'custom_gateway_blocks_admin_notice' ) ) !== false, true );
$_GET = array(); ob_start(); $module->custom_gateway_blocks_admin_notice(); $notice = ob_get_clean(); check( 'F2 verified context needs no raw GET tab', strpos( $notice, 'Classic checkout is unaffected.' ) !== false, true );
foreach ( array( 'general', '', null, array( 'checkout' ) ) as $wrong_tab ) { $current_tab = $wrong_tab; $_GET = array( 'tab' => 'checkout' ); $before = $woo->singleton_reads; $option_reads = array(); ob_start(); $module->custom_gateway_blocks_admin_notice(); check( 'F2 wrong Woo tab no output ' . gettype( $wrong_tab ) . serialize( $wrong_tab ), ob_get_clean(), '' ); check( 'F2 wrong Woo tab no reads ' . gettype( $wrong_tab ) . serialize( $wrong_tab ), array( $woo->singleton_reads, $option_reads ), array( $before, array() ) ); }
$current_tab = 'checkout'; $screen = null; $before = $woo->singleton_reads; ob_start(); $module->custom_gateway_blocks_admin_notice(); check( 'F2 absent screen untouched', array( ob_get_clean(), $woo->singleton_reads ), array( '', $before ) );
$screen = 'woocommerce_page_wc-settings'; $admin = false; ob_start(); $module->custom_gateway_blocks_admin_notice(); check( 'F2 nonadmin untouched', array( ob_get_clean(), $woo->singleton_reads ), array( '', $before ) ); $admin = true;
require $upstream . '/woocommerce/includes/admin/class-wc-admin-settings.php';
$generic_notices = $wp_filter['admin_notices'] ?? null; unset( $wp_filter['admin_notices'] );
$tabs = array( 'checkout' => 'Payments' ); $current_section = ''; $GLOBALS['hide_save_button'] = true;
ob_start(); include $upstream . '/woocommerce/includes/admin/views/html-admin-settings.php'; $view = ob_get_clean();
check( 'F2 actual complete Woo view emits notice after generic suppression', substr_count( $view, 'Classic checkout is unaffected.' ), 1 );
check( 'F2 actual view retains escaped title', strpos( $view, '&lt;b&gt;Authorized title&lt;/b&gt;' ) !== false, true );
if ( null !== $generic_notices ) { $wp_filter['admin_notices'] = $generic_notices; }
$gateway->settings['description'] = $description; $admin = false;
check( 'all validation leaves cart intact', $woo->cart->empties, 0 );
// Actual Woo Legacy -> unchanged actual template processor exactly once. Side effects below are counters, not native proof.
define( 'WCJ_IS_WC_VERSION_BELOW_3', false );
$processing_order = new WC_Order(); $stock_calls = 0;
function wc_get_order( $id ) { global $processing_order; return $processing_order; }
function wc_reduce_stock_levels( $id ) { global $stock_calls; ++$stock_calls; }
function wc_get_order_statuses() { return array( 'wc-pending' => 'Pending' ); }
function wc_maybe_define_constant( $name, $value ) { if ( ! defined( $name ) ) { define( $name, $value ); } }
function wc_notice_count( $type ) { return 0; } function wc_clear_notices() {} function esc_url_raw( $x ) { return $x; }
foreach ( array( 'Payments/PaymentContext.php', 'Payments/PaymentResult.php', 'Utilities/NoticeHandler.php', 'Legacy.php' ) as $file ) { require $upstream . '/woocommerce/src/StoreApi/' . $file; }
$context = new Automattic\WooCommerce\StoreApi\Payments\PaymentContext(); $context->set_order( $processing_order ); $context->set_payment_method( 'jetpack_custom_gateway' ); $context->set_payment_data( array( 'wcj_custom_gateway_fields' => $wire ) ); $payment_result = new Automattic\WooCommerce\StoreApi\Payments\PaymentResult(); $legacy = new Automattic\WooCommerce\StoreApi\Legacy(); $legacy->init(); $_POST = array( 'old' => 'post' ); do_action_ref_array( 'woocommerce_rest_checkout_process_payment_with_context', array( $context, &$payment_result ) );
check( 'actual Legacy one stock call', $stock_calls, 1 ); check( 'actual Legacy one cart empty', $woo->cart->empties, 1 ); check( 'actual Legacy one status update', $processing_order->status_updates, array( array( 'pending', 'Pending' ) ) ); check( 'actual Legacy successful payment result', $payment_result->status, 'success' ); check( 'actual Legacy unchanged redirect', $payment_result->redirect_url, '/synthetic-order-received' ); check( 'actual Legacy restores prior POST', $_POST, array( 'old' => 'post' ) ); check( 'actual Legacy no own order save', $processing_order->saves, 0 );
result();
