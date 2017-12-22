<?php
/**
 * Booster for WooCommerce - Shortcodes
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! wcj_is_module_enabled( 'general' ) || ( wcj_is_module_enabled( 'general' ) && 'no' === get_option( 'wcj_general_shortcodes_disable_booster_shortcodes', 'no' ) ) ) {

	include_once( WCJ_PLUGIN_PATH . '/includes/classes/class-wcj-shortcodes.php' );

	$wcj_shortcodes_files = array(
		'class-wcj-shortcodes-general.php',
		'class-wcj-shortcodes-invoices.php',
		'class-wcj-shortcodes-orders.php',
		'class-wcj-shortcodes-order-items.php',
		'class-wcj-shortcodes-products.php',
		'class-wcj-shortcodes-products-crowdfunding.php',
		'class-wcj-shortcodes-products-add-form.php',
		'class-wcj-shortcodes-input-field.php',
	);

	$wcj_shortcodes_dir = WCJ_PLUGIN_PATH . '/includes/shortcodes/';
	foreach ( $wcj_shortcodes_files as $wcj_shortcodes_file ) {
		include_once( $wcj_shortcodes_dir . $wcj_shortcodes_file );
	}

}
