<?php
/**
 * Booster 8.3 integration regression suite for `wp eval-file`.
 *
 * Run once with HPOS disabled and once with HPOS enabled. Set
 * CODEX_BOOSTER_EXPECTED_TIER to free, plus, or elite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function codex_booster_83_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$administrators = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
codex_booster_83_assert( ! empty( $administrators ), 'Administrator fixture missing.' );
$administrator_id = (int) reset( $administrators );

// Ability registration, tier contract, strict input, permission, and bounded output.
wp_set_current_user( $administrator_id );
$booster_abilities = array_filter(
	array_keys( wp_get_abilities() ),
	function ( $name ) {
		return 0 === strpos( $name, 'booster/' );
	}
);
sort( $booster_abilities );
$expected_abilities = array( 'booster/background-jobs-status', 'booster/compatibility-status', 'booster/module-status' );
codex_booster_83_assert( $expected_abilities === array_values( $booster_abilities ), 'Booster must register exactly the three approved abilities.' );

$module_ability = wp_get_ability( 'booster/module-status' );
$module_result  = $module_ability->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( ! is_wp_error( $module_result ), 'Administrator module-status execution failed.' );
codex_booster_83_assert( $module_result['count'] > 0, 'Active tier module inventory is empty.' );
codex_booster_83_assert( $module_result['count'] <= WCJ_Status_Service::MAX_MODULES, 'Module status exceeded its bound.' );
codex_booster_83_assert( $module_result['count'] === count( $module_result['modules'] ), 'Module count does not match output.' );
$expected_tier = getenv( 'CODEX_BOOSTER_EXPECTED_TIER' );
if ( $expected_tier ) {
	codex_booster_83_assert( $expected_tier === $module_result['tier'], 'Tier output does not match the active plugin.' );
}
$invalid = $module_ability->execute( array( 'scope' => 'summary', 'unknown' => true ) );
codex_booster_83_assert( is_wp_error( $invalid ) && 'ability_invalid_input' === $invalid->get_error_code(), 'Unknown input was not rejected by the schema.' );

$subscriber_id = username_exists( 'codex_booster_83_subscriber' );
if ( ! $subscriber_id ) {
	$subscriber_id = wp_create_user( 'codex_booster_83_subscriber', wp_generate_password(), 'codex-booster-83@example.invalid' );
	( new WP_User( $subscriber_id ) )->set_role( 'subscriber' );
}
$customer_id = username_exists( 'codex_booster_83_customer' );
if ( ! $customer_id ) {
	$customer_id = wp_create_user( 'codex_booster_83_customer', wp_generate_password(), 'codex-booster-83-customer@example.invalid' );
	( new WP_User( $customer_id ) )->set_role( 'customer' );
}
$manager_id = username_exists( 'codex_booster_83_manager' );
if ( ! $manager_id ) {
	$manager_id = wp_create_user( 'codex_booster_83_manager', wp_generate_password(), 'codex-booster-83-manager@example.invalid' );
	( new WP_User( $manager_id ) )->set_role( 'shop_manager' );
}
wp_set_current_user( 0 );
$guest = $module_ability->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( is_wp_error( $guest ) && 'ability_invalid_permissions' === $guest->get_error_code(), 'Guest execution was not rejected.' );
wp_set_current_user( $subscriber_id );
$subscriber = $module_ability->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( is_wp_error( $subscriber ) && 'ability_invalid_permissions' === $subscriber->get_error_code(), 'Subscriber execution was not rejected.' );
wp_set_current_user( $customer_id );
$customer = $module_ability->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( is_wp_error( $customer ) && 'ability_invalid_permissions' === $customer->get_error_code(), 'Customer execution was not rejected.' );
wp_set_current_user( $manager_id );
$manager = $module_ability->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( ! is_wp_error( $manager ), 'Shop manager execution was rejected.' );
wp_set_current_user( $administrator_id );

$compatibility = wp_get_ability( 'booster/compatibility-status' )->execute( array( 'scope' => 'summary' ) );
$jobs          = wp_get_ability( 'booster/background-jobs-status' )->execute( array( 'scope' => 'summary' ) );
codex_booster_83_assert( ! is_wp_error( $compatibility ) && $compatibility['count'] === count( $compatibility['modules'] ), 'Compatibility output failed.' );
codex_booster_83_assert( ! is_wp_error( $jobs ) && array( 'pending', 'overdue', 'failed', 'recent_success' ) === array_keys( $jobs['counts'] ), 'Background job aggregate contract failed.' );

// Store API requests must reach the same server-side gateway rules as Classic Checkout.
if ( ! defined( 'REST_REQUEST' ) ) {
	define( 'REST_REQUEST', true );
}
$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1/cart';
wc_load_cart();
WC()->cart->empty_cart();
$term = wp_insert_term( 'Booster 8.3 allowed category', 'product_cat' );
$term_id = is_wp_error( $term ) ? get_term_by( 'name', 'Booster 8.3 allowed category', 'product_cat' )->term_id : $term['term_id'];
$allowed_product = new WC_Product_Simple();
$allowed_product->set_name( 'Booster 8.3 allowed gateway product' );
$allowed_product->set_regular_price( '10' );
$allowed_product->set_category_ids( array( $term_id ) );
$allowed_product_id = $allowed_product->save();
$denied_product = new WC_Product_Simple();
$denied_product->set_name( 'Booster 8.3 denied gateway product' );
$denied_product->set_regular_price( '10' );
$denied_product_id = $denied_product->save();
$rule_key      = 'wcj_gateways_per_category_codex83';
$previous_rule = get_option( $rule_key, null );
update_option( $rule_key, array( (string) $term_id ) );
$gateway_module                    = new WCJ_Payment_Gateways_Per_Category();
$gateway_module->do_use_variations = false;
$gateways = array( 'codex83' => (object) array( 'id' => 'codex83' ) );
WC()->cart->add_to_cart( $allowed_product_id );
$allowed_gateways = $gateway_module->filter_available_payment_gateways_per_category( $gateways );
WC()->cart->empty_cart();
WC()->cart->add_to_cart( $denied_product_id );
$denied_gateways = $gateway_module->filter_available_payment_gateways_per_category( $gateways );
codex_booster_83_assert( isset( $allowed_gateways['codex83'] ) && ! isset( $denied_gateways['codex83'] ), 'Store API gateway product/category boundary failed.' );
if ( null === $previous_rule ) {
	delete_option( $rule_key );
} else {
	update_option( $rule_key, $previous_rule );
}
WC()->cart->empty_cart();

// Partial-refund and zero-refund totals must not emit undefined accumulator notices.
$product = new WC_Product_Simple();
$product->set_name( 'Booster 8.3 regression product' );
$product->set_regular_price( '10' );
$product_id = $product->save();
$order      = wc_create_order();
$order->add_product( wc_get_product( $product_id ), 2 );
$order->calculate_totals();
$order->save();
$item_ids = array_keys( $order->get_items() );
$item_id  = reset( $item_ids );
$refund  = wc_create_refund(
	array(
		'order_id'   => $order->get_id(),
		'amount'     => 5,
		'reason'     => 'Booster 8.3 partial refund regression',
		'line_items' => array( $item_id => array( 'qty' => -1, 'refund_total' => -5, 'refund_tax' => array() ) ),
	)
);
codex_booster_83_assert( ! is_wp_error( $refund ), 'Partial refund fixture failed.' );
$empty_order = wc_create_order();
$empty_order->calculate_totals();
$empty_order->save();
$shortcodes = new WCJ_Orders_Shortcodes();
$property   = new ReflectionProperty( get_parent_class( $shortcodes ), 'the_order' );
$property->setAccessible( true );
$atts = array( 'excl_tax' => false, 'currency' => '', 'hide_currency' => 'no', 'hide_if_zero' => 'no' );
$undefined_accumulator = false;
set_error_handler(
	function ( $severity, $message ) use ( &$undefined_accumulator ) {
		if ( false !== strpos( $message, 'refund_total' ) ) {
			$undefined_accumulator = true;
		}
		return false;
	}
);
$property->setValue( $shortcodes, $order );
$partial_total = $shortcodes->wcj_order_item_total_refunded( $atts );
$property->setValue( $shortcodes, $empty_order );
$zero_total = $shortcodes->wcj_order_item_total_refunded( $atts );
restore_error_handler();
codex_booster_83_assert( ! $undefined_accumulator && false !== strpos( $partial_total, '5.000' ) && false !== strpos( $zero_total, '0.000' ), 'Refund shortcode regression failed.' );

// Order metadata and save behavior are shared across HPOS and legacy storage.
$addons = new WCJ_Product_Addons();
$addons->maybe_reduce_addons_qty( $order->get_id() );
$order = wc_get_order( $order->get_id() );
codex_booster_83_assert( 'yes' === $order->get_meta( '_wcj_product_addons_qty_reduced', true ), 'Product Addons order marker was not persisted through order CRUD.' );

// Bulk regeneration must consume IDs correctly in both order stores.
$orders_module = new WCJ_Orders();
$bulk_count    = $orders_module->bulk_regenerate_download_permissions_all_orders();
codex_booster_83_assert( $bulk_count >= 2, 'Bulk download permission regeneration did not process the fixtures.' );

echo wp_json_encode(
	array(
		'ok'             => true,
		'tier'           => $module_result['tier'],
		'hpos'           => function_exists( 'wcj_is_hpos_enabled' ) && wcj_is_hpos_enabled(),
		'module_count'   => $module_result['count'],
		'bulk_count'     => $bulk_count,
		'ability_names'  => $expected_abilities,
		'diagnostics'    => $jobs['diagnostic_codes'],
	)
);
