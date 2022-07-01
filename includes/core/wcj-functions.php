<?php
/**
 * Booster for WooCommerce - Functions
 *
 * @version 5.6.1
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcj_function_files = array(
	'wcj-functions-booster-core.php',
	'wcj-functions-debug.php',
	'wcj-functions-admin.php',
	'wcj-functions-general.php',
	'wcj-functions-math.php',
	'wcj-functions-shipping.php',
	'wcj-functions-date-time.php',
	'wcj-functions-crons.php',
	'wcj-functions-products.php',
	'wcj-functions-orders.php',
	'wcj-functions-eu-vat.php',
	'wcj-functions-price-currency.php',
	'wcj-functions-users.php',
	'wcj-functions-exchange-rates.php',
	'wcj-functions-number-to-words.php',
	'wcj-functions-number-to-words-bg.php',
	'wcj-functions-number-to-words-lt.php',
	'wcj-functions-html.php',
	'wcj-functions-country.php',
	'wcj-functions-invoicing.php',
	'wcj-functions-reports.php',
);

$wcj_functions_dir = WCJ_FREE_PLUGIN_PATH . '/includes/functions/';
foreach ( $wcj_function_files as $wcj_function_file ) {
	include_once $wcj_functions_dir . $wcj_function_file;
}
