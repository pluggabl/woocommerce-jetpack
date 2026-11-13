<?php
/**
 * Native pricing with intentionally absent optional observation dependencies.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
require __DIR__ . '/bootstrap.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-price-by-user-role.php';
$wcj_ai_missing = getenv( 'WCJ_AI_EXPECT_MISSING' );
wcj_ai_check( 'expected_optional_dependency_absent', 'collector' === $wcj_ai_missing ? ! class_exists( 'WCJ_AI_Role_Observation', false ) : ! class_exists( 'WCJ_AI_Price_Value', false ) );
$wcj_ai_instance                            = ( new ReflectionClass( WCJ_Price_By_User_Role::class ) )->newInstanceWithoutConstructor();
$wcj_ai_instance->price_hooks_priority      = 10;
$wcj_ai_instance->disable_for_regular_price = false;
$wcj_ai_product                             = new WC_Product_Simple();
$wcj_ai_product->set_regular_price( '20' );
wcj_ai_check( 'native_number_survives_missing_optional_dependency', 10.0 === $wcj_ai_instance->change_price( '20', $wcj_ai_product ) );
wcj_ai_check( 'native_empty_survives_missing_optional_dependency', '' === $wcj_ai_instance->change_price( '', $wcj_ai_product ) );
wcj_ai_check( 'native_zero_survives_missing_optional_dependency', 0.0 === $wcj_ai_instance->change_price( '0', $wcj_ai_product ) );
if ( 'projector' === $wcj_ai_missing ) {
	wcj_ai_check( 'capture_refuses_missing_projector', false === WCJ_AI_Role_Observation::arm( 1, 'invalid' ) );
}
wcj_ai_done( 'native optional dependency failure only; fixture multiplier 0.5' );
