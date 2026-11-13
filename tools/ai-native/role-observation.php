<?php
/**
 * Native method/callback observation, not a browser capture-route test.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
require __DIR__ . '/bootstrap.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-ai-role-observation.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-price-by-user-role.php';
$wcj_ai_product = new WC_Product_Simple();
$wcj_ai_product->set_name( 'Synthetic observation product' );
$wcj_ai_product->set_regular_price( '20' );
$wcj_ai_product->set_status( 'publish' );
$wcj_ai_product->save();
$wcj_ai_product = new WC_Product_Simple( $wcj_ai_product->get_id() );
// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Native test query context, not a delivery route.
$GLOBALS['wp_query'] = new WP_Query(
	array(
		'post_type' => 'product',
		'p'         => $wcj_ai_product->get_id(),
	)
);
$_GET                = array();
$_POST               = array();
$wcj_ai_property     = new ReflectionProperty( WCJ_AI_Role_Observation::class, 'capture' );
$wcj_ai_property->setAccessible( true );
/**
 * Reset request-local state between synthetic subcases.
 */
function wcj_ai_role_reset() {
	global $wcj_ai_property;
	$wcj_ai_property->setValue( null, null ); }
/**
 * Construct native method subject without registering duplicate hooks.
 *
 * @return WCJ_Price_By_User_Role Native module.
 */
function wcj_ai_role_instance() {
	$wcj_ai_instance                            = ( new ReflectionClass( WCJ_Price_By_User_Role::class ) )->newInstanceWithoutConstructor();
	$wcj_ai_instance->price_hooks_priority      = 10;
	$wcj_ai_instance->disable_for_regular_price = false;
	return $wcj_ai_instance;
}
$wcj_ai_nonce = wp_create_nonce( 'wcj_ai_role_capture_' . $wcj_ai_product->get_id() );
wcj_ai_check( 'nonce_without_cookie_denied', ! WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce ) );
$_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $wcj_ai_owner->ID, time() + 600, 'logged_in' );
$wcj_ai_nonce                = wp_create_nonce( 'wcj_ai_role_capture_' . $wcj_ai_product->get_id() );
$_GET['add-to-cart']         = $wcj_ai_product->get_id();
wcj_ai_check( 'mutation_parameter_admission_denied_not_early_route_proof', ! WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce ) );
$_GET = array();
wcj_ai_check( 'wrong_product_denied', ! WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id() + 1, $wcj_ai_nonce ) );
wcj_ai_check( 'wrong_nonce_denied', ! WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), 'invalid' ) );
update_option( 'wcj_price_by_user_role_administrator', '0.5' );
update_option( 'wcj_price_by_user_role_per_product_enabled', 'no' );
update_option( 'wcj_price_by_user_role_by_category_enabled', 'no' );
update_option( 'wcj_price_by_user_role_by_tag_enabled', 'no' );
$wcj_ai_off     = wcj_ai_role_instance();
$wcj_ai_on      = wcj_ai_role_instance();
$wcj_ai_count   = 0;
$wcj_ai_counter = static function ( $wcj_ai_allow ) use ( &$wcj_ai_count ) {
	++$wcj_ai_count;
	return $wcj_ai_allow;
};
add_filter( 'wcj_price_by_user_role_do_change_price', $wcj_ai_counter );
$wcj_ai_off_values = array( $wcj_ai_off->change_price( '20', $wcj_ai_product ), $wcj_ai_off->change_price( '20', $wcj_ai_product ), $wcj_ai_off->change_price( '', $wcj_ai_product ), $wcj_ai_off->change_price( '0', $wcj_ai_product ) );
$wcj_ai_off_count  = $wcj_ai_count;
$wcj_ai_count      = 0;
wcj_ai_check( 'own_cookie_native_admission', WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce ) );
$wcj_ai_on_values = array( $wcj_ai_on->change_price( '20', $wcj_ai_product ), $wcj_ai_on->change_price( '20', $wcj_ai_product ), $wcj_ai_on->change_price( '', $wcj_ai_product ), $wcj_ai_on->change_price( '0', $wcj_ai_product ) );
wcj_ai_check( 'native_on_off_values_and_types', $wcj_ai_off_values === $wcj_ai_on_values );
wcj_ai_check( 'native_on_off_callback_counts', $wcj_ai_off_count === $wcj_ai_count && 3 === $wcj_ai_count );
$wcj_ai_other = wcj_ai_role_instance();
$wcj_ai_other->change_price( '20', $wcj_ai_product );
$wcj_ai_events = WCJ_AI_Role_Observation::close();
wcj_ai_check( 'one_native_instance_only', 4 === count( $wcj_ai_events['events'] ) );
wcj_ai_check( 'cache_miss_observed', 'miss' === $wcj_ai_events['events'][0]['cache'] && 'global_multiplier' === $wcj_ai_events['events'][0]['decisions'][0]['reason'] );
wcj_ai_check( 'cache_hit_origin_same_instance', 'hit' === $wcj_ai_events['events'][1]['cache'] && 0 === $wcj_ai_events['events'][1]['cache_origin'] );
wcj_ai_check( 'direct_call_not_claimed_registered_dispatch', 'method_observed_dispatch_unqualified' === $wcj_ai_events['events'][0]['invocation'] );
wcj_ai_check( 'raw_role_and_product_name_absent', ! str_contains( wp_json_encode( $wcj_ai_events ), 'administrator' ) && ! str_contains( wp_json_encode( $wcj_ai_events ), 'Synthetic observation' ) );
wcj_ai_check( 'closed_cannot_add_events', null === WCJ_AI_Role_Observation::begin( $wcj_ai_product->get_id(), '20', '', 10, $wcj_ai_on ) );
wcj_ai_role_reset();
WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce );
$wcj_ai_on->change_price( '20', $wcj_ai_product );
$wcj_ai_events = WCJ_AI_Role_Observation::close();
wcj_ai_check( 'prearm_cache_origin_unknown', null === $wcj_ai_events['events'][0]['cache_origin'] && 'hit' === $wcj_ai_events['events'][0]['cache'] );
wcj_ai_role_reset();
WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce );
$wcj_ai_ticket = WCJ_AI_Role_Observation::begin( $wcj_ai_product->get_id(), '20', '', 10, $wcj_ai_on );
$wcj_ai_nested = WCJ_AI_Role_Observation::begin( $wcj_ai_product->get_id() + 1, '10', '', 10, $wcj_ai_on );
WCJ_AI_Role_Observation::decision( $wcj_ai_nested, '5', 'unchanged', 'private-role' );
WCJ_AI_Role_Observation::finish( $wcj_ai_ticket, '20', 'bypass' );
$wcj_ai_events = WCJ_AI_Role_Observation::close();
wcj_ai_check( 'nested_other_product_not_attributed', array() === $wcj_ai_events['events'][0]['decisions'] );
wcj_ai_role_reset();
WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce );
for ( $wcj_ai_i = 0; $wcj_ai_i < 70; ++$wcj_ai_i ) {
	$wcj_ai_ticket = WCJ_AI_Role_Observation::begin( $wcj_ai_product->get_id(), '20', '', 10, $wcj_ai_on );
	WCJ_AI_Role_Observation::finish( $wcj_ai_ticket, '20', 'bypass' ); }
$wcj_ai_events = WCJ_AI_Role_Observation::close();
wcj_ai_check( 'bounded_events', $wcj_ai_events['truncated'] && 64 === count( $wcj_ai_events['events'] ) );
wcj_ai_role_reset();
WCJ_AI_Role_Observation::arm( $wcj_ai_product->get_id(), $wcj_ai_nonce );
$wcj_ai_hook_instance = wcj_ai_role_instance();
add_filter( 'woocommerce_product_get_price', array( $wcj_ai_hook_instance, 'change_price' ), 10, 2 );
$wcj_ai_hook_price = $wcj_ai_product->get_price();
remove_filter( 'woocommerce_product_get_price', array( $wcj_ai_hook_instance, 'change_price' ), 10 );
$wcj_ai_events = WCJ_AI_Role_Observation::close();
wcj_ai_check( 'real_woo_getter_native_callback', 10.0 === $wcj_ai_hook_price && 'woocommerce_product_get_price' === $wcj_ai_events['events'][0]['hook'] );
remove_filter( 'wcj_price_by_user_role_do_change_price', $wcj_ai_counter );
$wcj_ai_product->delete( true );
wcj_ai_done( 'native synthetic role methods and cookie admission only; no early route or browser proof' );
