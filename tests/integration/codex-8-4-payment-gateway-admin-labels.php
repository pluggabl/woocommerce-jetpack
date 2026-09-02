<?php
/**
 * Booster 8.4 payment gateway admin-label regression for wp eval-file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures = array();
$assert   = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$assert( function_exists( 'wcj_get_payment_gateway_admin_title' ), 'Admin gateway label helper was not loaded.' );

$method_gateway = new class() {
	public $title = 'Property title';
	public function get_method_title() {
		return '<strong>Método traducido</strong>';
	}
	public function get_title() {
		return 'Checkout title';
	}
};
$title_method_gateway = new class() {
	public function get_title() {
		return 'Title method only';
	}
};
$property_gateway        = new stdClass();
$property_gateway->title = 'Public property only';
$id_gateway = new class() {
	public function get_id() {
		return 'gateway_method_id';
	}
};
$empty_gateway = new stdClass();

$assert( 'Método traducido' === wcj_get_payment_gateway_admin_title( $method_gateway, 'method-key' ), 'get_method_title was not the first nonempty label.' );
$assert( 'Checkout title' === $method_gateway->get_title(), 'Checkout title changed while resolving the admin label.' );
$assert( 'Title method only' === wcj_get_payment_gateway_admin_title( $title_method_gateway, 'title-key' ), 'get_title fallback failed.' );
$assert( 'Public property only' === wcj_get_payment_gateway_admin_title( $property_gateway, 'property-key' ), 'Public title property fallback failed.' );
$assert( 'gateway_method_id' === wcj_get_payment_gateway_admin_title( $id_gateway, 'id-key' ), 'Gateway ID fallback failed.' );
$assert( 'wcj_custom_payment_gateway_1' === wcj_get_payment_gateway_admin_title( $empty_gateway, 'wcj_custom_payment_gateway_1' ), 'Booster custom gateway key fallback failed.' );
$assert( 'mollie_wc_gateway_ideal' === wcj_get_payment_gateway_admin_title( $empty_gateway, 'mollie_wc_gateway_ideal' ), 'Mollie-like gateway key fallback failed.' );

$settings_dir = dirname( __DIR__, 2 ) . '/includes/settings';
$files = array(
	'wcj-settings-payment-gateways-by-country.php',
	'wcj-settings-payment-gateways-by-shipping.php',
	'wcj-settings-payment-gateways-by-currency.php',
	'wcj-settings-payment-gateways-currency.php',
	'wcj-settings-payment-gateways-by-user-role.php',
	'wcj-settings-payment-gateways-pdf-notes.php',
	'wcj-settings-payment-gateways-fees.php',
	'wcj-settings-payment-gateways-min-max.php',
	'wcj-settings-payment-gateways-icons.php',
	'wcj-settings-payment-gateways-per-category.php',
	'wcj-settings-pdf-invoicing-emails.php',
);
foreach ( $files as $file ) {
	$source = file_get_contents( $settings_dir . '/' . $file );
	$assert( false !== $source, 'Could not read ' . $file . '.' );
	$assert( false === strpos( $source, '$gateway->title' ), $file . ' still reads the direct title property.' );
	$assert( false !== strpos( $source, 'wcj_get_payment_gateway_admin_title' ), $file . ' does not use the shared admin label helper.' );
}

$result = array(
	'passed'   => empty( $failures ),
	'failures' => $failures,
);
if ( ! empty( $failures ) && class_exists( 'WP_CLI' ) ) {
	WP_CLI::error( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
}
echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;
