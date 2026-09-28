<?php
/**
 * Booster 8.4 shipping Include/Exclude semantics regression for wp eval-file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures       = array();
$created_ids    = array();
$saved_options  = array();
$missing_marker = new stdClass();
$assert         = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};
$booster = function () {
	return function_exists( 'w_c_j' ) ? w_c_j() : WCJ();
};
$remember_option = function ( $name ) use ( &$saved_options, $missing_marker ) {
	if ( ! array_key_exists( $name, $saved_options ) ) {
		$saved_options[ $name ] = get_option( $name, $missing_marker );
	}
};
$set_option = function ( $name, $value ) use ( $remember_option, $booster ) {
	$remember_option( $name );
	update_option( $name, $value, false );
	if ( isset( $booster()->options[ $name ] ) ) {
		unset( $booster()->options[ $name ] );
	}
};

try {
	$assert( isset( $booster()->all_modules['shipping_by_products'] ), 'Shipping by Products module is unavailable.' );
	$module = $booster()->all_modules['shipping_by_products'];
	$module->use_shipping_instances = false;

	$product = new WC_Product_Simple();
	$product->set_name( 'Codex shipping match fixture' );
	$product->set_regular_price( '25' );
	$product->save();
	$created_ids[] = $product->get_id();

	$other = new WC_Product_Simple();
	$other->set_name( 'Codex shipping nonmatch fixture' );
	$other->set_regular_price( '40' );
	$other->save();
	$created_ids[] = $other->get_id();

	$term = wp_insert_term( 'Codex shipping category ' . wp_generate_password( 6, false ), 'product_cat' );
	$assert( ! is_wp_error( $term ), 'Could not create the category fixture.' );
	if ( ! is_wp_error( $term ) ) {
		$created_ids[] = array( 'term' => (int) $term['term_id'] );
		wp_set_object_terms( $product->get_id(), array( (int) $term['term_id'] ), 'product_cat' );
	}

	$package = array(
		'contents' => array(
			'fixture' => array(
				'product_id'  => $product->get_id(),
				'variation_id' => 0,
				'data'        => $product,
				'quantity'    => 1,
			),
		),
	);
	$rate = new WC_Shipping_Rate( 'free_shipping:17', 'Free shipping', 0, array(), 'free_shipping', 17 );
	$rates = array( 'free_shipping:17' => $rate );

	foreach ( array( 'products', 'product_cats', 'product_tags', 'classes' ) as $condition ) {
		$set_option( 'wcj_shipping_by_' . $condition . '_section_enabled', 'yes' );
		$set_option( 'wcj_shipping_' . $condition . '_include_free_shipping', '' );
		$set_option( 'wcj_shipping_' . $condition . '_exclude_free_shipping', '' );
	}
	$set_option( 'wcj_shipping_by_products_cart_not_package', 'no' );
	$set_option( 'wcj_shipping_by_product_cats_cart_not_package', 'no' );

	$result = $module->available_shipping_methods( $rates, $package );
	$assert( isset( $result['free_shipping:17'] ), 'Empty Include unexpectedly removed Free shipping.' );

	$set_option( 'wcj_shipping_products_include_free_shipping', array( (string) $product->get_id() ) );
	$result = $module->available_shipping_methods( $rates, $package );
	$assert( isset( $result['free_shipping:17'] ), 'Matching product Include did not preserve Free shipping.' );

	$set_option( 'wcj_shipping_products_include_free_shipping', array( (string) $other->get_id() ) );
	$result = $module->available_shipping_methods( $rates, $package );
	$assert( ! isset( $result['free_shipping:17'] ), 'Nonmatching product Include did not remove Free shipping.' );

	$set_option( 'wcj_shipping_products_include_free_shipping', '' );
	$set_option( 'wcj_shipping_products_exclude_free_shipping', array( (string) $product->get_id() ) );
	$result = $module->available_shipping_methods( $rates, $package );
	$assert( ! isset( $result['free_shipping:17'] ), 'Matching product Exclude did not remove Free shipping.' );

	$set_option( 'wcj_shipping_products_exclude_free_shipping', array( (string) $other->get_id() ) );
	$result = $module->available_shipping_methods( $rates, $package );
	$assert( isset( $result['free_shipping:17'] ), 'Nonmatching product Exclude unexpectedly removed Free shipping.' );

	if ( ! is_wp_error( $term ) ) {
		$set_option( 'wcj_shipping_products_exclude_free_shipping', '' );
		$set_option( 'wcj_shipping_product_cats_include_free_shipping', array( (string) $term['term_id'] ) );
		$result = $module->available_shipping_methods( $rates, $package );
		$assert( isset( $result['free_shipping:17'] ), 'Matching category Include did not preserve Free shipping.' );

		$set_option( 'wcj_shipping_products_include_free_shipping', array( (string) $other->get_id() ) );
		$result = $module->available_shipping_methods( $rates, $package );
		$assert( ! isset( $result['free_shipping:17'] ), 'Product and category Include rules did not compose deterministically.' );
	}

	$instance_rate = new WC_Shipping_Rate( 'free_shipping:17', 'Free shipping', 0, array(), 'free_shipping', 17 );
	$module->use_shipping_instances = true;
	$set_option( 'wcj_shipping_products_include_instance_17', array( (string) $product->get_id() ) );
	$set_option( 'wcj_shipping_product_cats_include_instance_17', '' );
	$set_option( 'wcj_shipping_product_tags_include_instance_17', '' );
	$set_option( 'wcj_shipping_classes_include_instance_17', '' );
	$set_option( 'wcj_shipping_products_exclude_instance_17', '' );
	$set_option( 'wcj_shipping_product_cats_exclude_instance_17', '' );
	$set_option( 'wcj_shipping_product_tags_exclude_instance_17', '' );
	$set_option( 'wcj_shipping_classes_exclude_instance_17', '' );
	$result = $module->available_shipping_methods( array( 'free_shipping:17' => $instance_rate ), $package );
	$assert( isset( $result['free_shipping:17'] ), 'Instance-specific matching Include did not preserve Free shipping.' );

	$module->use_shipping_instances = false;
	$set_option( 'wcj_shipping_products_include_free_shipping', array( (string) $product->get_id() ) );
	$set_option( 'wcj_shipping_product_cats_include_free_shipping', '' );
	$set_option( 'wcj_shipping_products_exclude_free_shipping', '' );
	$product_result = $module->available_shipping_methods( $rates, $package );
	$assert( isset( $product_result['free_shipping:17'] ), 'Product rule did not preserve the rate before composition checks.' );

	$assert( isset( $booster()->all_modules['shipping_by_user_role'] ), 'Shipping by User Role module is unavailable.' );
	$role_module = $booster()->all_modules['shipping_by_user_role'];
	$role_module->use_shipping_instances = false;
	$role_module->customer_roles = array( 'shop_manager' );
	foreach ( array( 'user_roles', 'user_id', 'user_membership' ) as $condition ) {
		$set_option( 'wcj_shipping_by_' . $condition . '_section_enabled', 'yes' );
		$set_option( 'wcj_shipping_' . $condition . '_include_free_shipping', '' );
		$set_option( 'wcj_shipping_' . $condition . '_exclude_free_shipping', '' );
	}
	$set_option( 'wcj_shipping_user_roles_include_free_shipping', array( 'shop_manager' ) );
	$role_result = $role_module->available_shipping_methods( $product_result, $package );
	$assert( isset( $role_result['free_shipping:17'] ), 'Matching role Include did not preserve the product-matched rate.' );
	$set_option( 'wcj_shipping_user_roles_include_free_shipping', array( 'customer' ) );
	$role_denied = $role_module->available_shipping_methods( $product_result, $package );
	$assert( ! isset( $role_denied['free_shipping:17'] ), 'Nonmatching role Include did not remove the product-matched rate.' );

	$assert( isset( $booster()->all_modules['shipping_by_order_amount'] ), 'Shipping by Order Amount module is unavailable.' );
	$amount_module = $booster()->all_modules['shipping_by_order_amount'];
	$amount_module->use_shipping_instances = false;
	$original_cart = WC()->cart;
	WC()->cart = new class() {
		public $cart_contents_total = 25;
		public function is_empty() {
			return false;
		}
	};
	$set_option( 'wcj_shipping_by_order_amount_min_free_shipping', 20 );
	$set_option( 'wcj_shipping_by_order_amount_max_free_shipping', 30 );
	$amount_result = $amount_module->available_shipping_methods( $role_result, $package );
	$assert( isset( $amount_result['free_shipping:17'] ), 'Matching amount did not preserve the product-and-role-matched rate.' );
	$set_option( 'wcj_shipping_by_order_amount_min_free_shipping', 26 );
	$amount_denied = $amount_module->available_shipping_methods( $role_result, $package );
	$assert( ! isset( $amount_denied['free_shipping:17'] ), 'Nonmatching amount did not remove the product-and-role-matched rate.' );

	// A newly added method has no saved instance limits. Missing options must mean unlimited.
	$amount_module->use_shipping_instances = true;
	$min_key = 'wcj_shipping_by_order_amount_min_instance_17';
	$max_key = 'wcj_shipping_by_order_amount_max_instance_17';
	$remember_option( $min_key );
	$remember_option( $max_key );
	delete_option( $min_key );
	delete_option( $max_key );
	$amount_default = $amount_module->available_shipping_methods( $rates, $package );
	$assert( isset( $amount_default['free_shipping:17'] ), 'Missing instance limits unexpectedly removed a new shipping method.' );
	$set_option( $min_key, '0' );
	$set_option( $max_key, '0' );
	$amount_zero = $amount_module->available_shipping_methods( $rates, $package );
	$assert( isset( $amount_zero['free_shipping:17'] ), 'Explicit zero instance limits unexpectedly removed a shipping method.' );
	$set_option( $min_key, '26' );
	$amount_min_denied = $amount_module->available_shipping_methods( $rates, $package );
	$assert( ! isset( $amount_min_denied['free_shipping:17'] ), 'Nonzero minimum instance limit did not remove a below-threshold rate.' );
	$set_option( $min_key, '0' );
	$set_option( $max_key, '24' );
	$amount_max_denied = $amount_module->available_shipping_methods( $rates, $package );
	$assert( ! isset( $amount_max_denied['free_shipping:17'] ), 'Nonzero maximum instance limit did not remove an above-threshold rate.' );
	WC()->cart = $original_cart;

	$settings_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/settings/wcj-settings-shipping-by-condition.php' );
	$assert( false !== strpos( $settings_source, 'Include limits this shipping method to carts that match' ), 'Include guidance is missing from settings.' );
	$assert( false !== strpos( $settings_source, 'Exclude hides this shipping method when the cart matches' ), 'Exclude guidance is missing from settings.' );
	$assert( false !== strpos( $settings_source, 'This Include rule narrows WooCommerce Free shipping' ), 'Free shipping narrowing warning is missing from settings.' );
} catch ( Throwable $error ) {
	$failures[] = 'Unexpected exception: ' . $error->getMessage();
} finally {
	foreach ( $saved_options as $name => $value ) {
		if ( $missing_marker === $value ) {
			delete_option( $name );
		} else {
			update_option( $name, $value, false );
		}
		if ( isset( $booster()->options[ $name ] ) ) {
			unset( $booster()->options[ $name ] );
		}
	}
	foreach ( array_reverse( $created_ids ) as $created ) {
		if ( is_array( $created ) && isset( $created['term'] ) ) {
			wp_delete_term( $created['term'], 'product_cat' );
		} else {
			wp_delete_post( $created, true );
		}
	}
}

$result = array(
	'passed'   => empty( $failures ),
	'failures' => $failures,
);
if ( ! empty( $failures ) && class_exists( 'WP_CLI' ) ) {
	WP_CLI::error( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
}
echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;
