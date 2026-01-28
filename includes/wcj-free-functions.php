<?php
/**
 * Procedural functions for Booster for WooCommerce (Free).
 *
 * @package Booster_For_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main WC_Jetpack instance.
 *
 * @return WC_Jetpack
 */
function w_c_j() {
	return WC_Jetpack::instance();
}

/**
 * Delete plugin options and meta on uninstall.
 *
 * Direct database queries are intentional here as this runs
 * only on uninstall and cleans up plugin-specific data.
 */
function wcj_delete_free_plugin_database_option() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$plugin_options = $wpdb->get_results(
		"SELECT option_name FROM {$wpdb->options}
		 WHERE option_name LIKE 'wcj_%'
		 OR option_name LIKE '_transient_timeout_wcj%'
		 OR option_name LIKE '_transient_wcj%'
		 OR option_name LIKE 'woocommerce_wcj_%'
		 OR option_name LIKE 'widget_wcj_widget_%'"
	);

	foreach ( $plugin_options as $option ) {
		delete_option( $option->option_name );
		delete_site_option( $option->option_name );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$plugin_meta = $wpdb->get_results(
		"SELECT post_id, meta_key FROM {$wpdb->postmeta}
		 WHERE meta_key LIKE '_wcj_%'"
	);

	foreach ( $plugin_meta as $meta ) {
		delete_post_meta( $meta->post_id, $meta->meta_key );
	}
}

/**
 * Set redirect flag after first activation only.
 */
function wcj_set_activation_redirect_free() {
	if ( get_option( 'wcj_plugin_activated_once', false ) ) {
		return;
	}

	add_option( 'wcj_plugin_activated_once', true );
	set_transient( 'wcj_activation_redirect', true, 60 );
}

/**
 * Redirect admin after first activation.
 */
function wcj_redirect_after_first_activation_free() {
	if ( ! get_transient( 'wcj_activation_redirect' ) ) {
		return;
	}

	delete_transient( 'wcj_activation_redirect' );

	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wp_safe_redirect(
		admin_url( 'admin.php?page=wcj-getting-started&modal=onboarding' )
	);
	exit;
}

/**
 * Start plugin usage tracking.
 */
function woocommerce_jetpack_start_plugin_tracking() {
	new Plugin_Usage_Tracker(
		WCJ_FREE_PLUGIN_FILE,
		'https://boosterio.bigscoots-staging.com',
		array(),
		true,
		true,
		1
	);
}
