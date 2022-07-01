<?php
/**
 * Booster for WooCommerce - Shortcodes
 *
 * @version 5.6.1
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === wcj_get_option( 'wcj_general_shortcodes_disable_booster_shortcodes', 'no' ) ) ) {

	include_once WCJ_FREE_PLUGIN_PATH . '/includes/classes/class-wcj-shortcodes.php';

	$wcj_shortcodes_files = array(
		'general'               => 'class-wcj-general-shortcodes.php',
		'cart'                  => 'class-wcj-cart-shortcodes.php',
		'invoices'              => 'class-wcj-invoices-shortcodes.php',
		'orders'                => 'class-wcj-orders-shortcodes.php',
		'order_items'           => 'class-wcj-order-items-shortcodes.php',
		'products'              => 'class-wcj-products-shortcodes.php',
		'products_crowdfunding' => 'class-wcj-products-crowdfunding-shortcodes.php',
		'products_add_form'     => 'class-wcj-products-add-form-shortcodes.php',
		'input_field'           => 'class-wcj-input-field-shortcodes.php',
	);

	$wcj_shortcodes_dir = WCJ_FREE_PLUGIN_PATH . '/includes/shortcodes/';
	foreach ( $wcj_shortcodes_files as $wcj_shortcodes_file_id => $wcj_shortcodes_file ) {
		$this->shortcodes[ $wcj_shortcodes_file_id ] = include_once $wcj_shortcodes_dir . $wcj_shortcodes_file;
	}
}
