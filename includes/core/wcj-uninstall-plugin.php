<?php
/**
 * Booster for WooCommerce - Core - Uninstall plugin
 *
 * @version 6.0.3-dev
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wcj_delete_plugin_database_option
 *
 * @version 6.0.3-dev
 * @since   6.0.3
 */
function wcj_delete_plugin_database_option() {
	global $wpdb;

	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'wcj_%' OR option_name LIKE '_transient_timeout_wcj%' OR option_name LIKE '_transient_wcj%' OR option_name LIKE 'woocommerce_wcj_%' OR option_name LIKE 'widget_wcj_widget_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $plugin_options as $option ) {
		delete_option( $option->option_name );
		delete_site_option( $option->option_name );
	}

	$plugin_meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_wcj_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $plugin_meta as $meta ) {
		delete_post_meta( $meta->post_id, $meta->meta_key );
	}
}

register_uninstall_hook( __FILE__, 'wcj_delete_plugin_database_option' );
