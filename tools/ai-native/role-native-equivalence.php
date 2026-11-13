<?php
/**
 * Compare native values and observable hook counts with a recorded baseline.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
require __DIR__ . '/bootstrap.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-price-by-user-role.php';
$wcj_ai_instance                            = ( new ReflectionClass( WCJ_Price_By_User_Role::class ) )->newInstanceWithoutConstructor();
$wcj_ai_instance->price_hooks_priority      = 10;
$wcj_ai_instance->disable_for_regular_price = false;
$wcj_ai_product                             = new WC_Product_Simple();
$wcj_ai_counts                              = array();
$wcj_ai_counter                             = static function ( $wcj_ai_hook ) use ( &$wcj_ai_counts ) {
	if ( str_starts_with( $wcj_ai_hook, 'wcj_' ) || str_starts_with( $wcj_ai_hook, 'pre_option_wcj_' ) ) {
		$wcj_ai_counts[ $wcj_ai_hook ] = ( $wcj_ai_counts[ $wcj_ai_hook ] ?? 0 ) + 1;
	}
};
add_action( 'all', $wcj_ai_counter );
$wcj_ai_values = array();
foreach ( array( '20', '20', '', '0', 12.5 ) as $wcj_ai_input ) {
	$wcj_ai_result   = $wcj_ai_instance->change_price( $wcj_ai_input, $wcj_ai_product );
	$wcj_ai_values[] = array(
		'type'  => gettype( $wcj_ai_result ),
		'value' => $wcj_ai_result,
	);
}
$wcj_ai_product->set_price( '33' );
$wcj_ai_result   = $wcj_ai_instance->change_price( '33', $wcj_ai_product );
$wcj_ai_values[] = array(
	'type'  => gettype( $wcj_ai_result ),
	'value' => $wcj_ai_result,
);
remove_action( 'all', $wcj_ai_counter );
ksort( $wcj_ai_counts );
echo wp_json_encode(
	array(
		'values'                 => $wcj_ai_values,
		'observable_hook_counts' => $wcj_ai_counts,
	),
	JSON_PRETTY_PRINT
);
