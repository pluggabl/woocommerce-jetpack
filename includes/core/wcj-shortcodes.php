<?php
/**
 * Booster for WooCommerce - Shortcodes
 *
 * @version 3.3.0
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_shortcodes_disable_booster_shortcodes', 'no' ) ) ) {

	include_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-shortcodes.php' );

	$wcj_shortcodes_files = array(
		'general'                   => 'class-wcj-shortcodes-general.php',
		'invoices'                  => 'class-wcj-shortcodes-invoices.php',
		'orders'                    => 'class-wcj-shortcodes-orders.php',
		'order_items'               => 'class-wcj-shortcodes-order-items.php',
		'products'                  => 'class-wcj-shortcodes-products.php',
		'products_crowdfunding'     => 'class-wcj-shortcodes-products-crowdfunding.php',
		'products_add_form'         => 'class-wcj-shortcodes-products-add-form.php',
		'input_field'               => 'class-wcj-shortcodes-input-field.php',
	);

	$wcj_shortcodes_dir = WCJ_PLUGIN_PATH . '/includes/shortcodes/';
	foreach ( $wcj_shortcodes_files as $wcj_shortcodes_file_id => $wcj_shortcodes_file ) {
		$this->shortcodes[ $wcj_shortcodes_file_id ] = include_once( $wcj_shortcodes_dir . $wcj_shortcodes_file );
	}

}
