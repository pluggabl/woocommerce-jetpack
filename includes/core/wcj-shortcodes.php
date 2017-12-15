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
		'class-wcj-general-shortcodes.php',
		'class-wcj-invoices-shortcodes.php',
		'class-wcj-orders-shortcodes.php',
		'class-wcj-order-items-shortcodes.php',
		'class-wcj-products-shortcodes.php',
		'class-wcj-products-crowdfunding-shortcodes.php',
		'class-wcj-products-add-form-shortcodes.php',
		'class-wcj-input-field-shortcodes.php',
	);

	$wcj_shortcodes_dir = WCJ_PLUGIN_PATH . '/includes/shortcodes/';
	foreach ( $wcj_shortcodes_files as $wcj_shortcodes_file ) {
		include_once( $wcj_shortcodes_dir . $wcj_shortcodes_file );
	}

}
