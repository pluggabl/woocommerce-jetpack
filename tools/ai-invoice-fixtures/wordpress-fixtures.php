<?php
/**
 * Synthetic CLI fixture only. Never loaded in a WordPress request.
 *
 * @package Booster_For_WooCommerce/tools
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Core constant substitute.
define( 'ABSPATH', __DIR__ . '/synthetic-wordpress/' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Core constant substitute.
define( 'ARRAY_A', 'ARRAY_A' );
/**
 * Synthetic translation passthrough.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function __( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return $value; }
/**
 * Synthetic esc html   fixture helper.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function esc_html__( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return $value; }
/**
 * Synthetic esc html fixture helper.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function esc_html( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return htmlspecialchars( $value, ENT_QUOTES ); }
/**
 * Synthetic add action fixture helper.
 *
 * @param mixed ...$args Synthetic hook arguments.
 * @return mixed
 */
function add_action( ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return count( $args ) > 0;
}
/**
 * Synthetic wp unslash fixture helper.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function wp_unslash( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return $value; }
/**
 * Synthetic get current user id helper.
 *
 * @return mixed
 */
function get_current_user_id() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return 71; }
/**
 * Synthetic wcj get invoicing default images directory helper.
 *
 * @return mixed
 */
function wcj_get_invoicing_default_images_directory() {
	return false; }
/**
 * Synthetic maybe serialize fixture helper.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function maybe_serialize( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Match native option bytes using synthetic values only.
	return is_array( $value ) ? serialize( $value ) : $value; }
/**
 * Synthetic maybe unserialize fixture helper.
 *
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function maybe_unserialize( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Synthetic fixtures only, with objects disabled.
	return str_starts_with( $value, 'a:' ) ? unserialize( $value, array( 'allowed_classes' => false ) ) : $value; }
/**
 * Synthetic get option fixture helper.
 *
 * @param mixed $key Synthetic key input.
 * @param mixed $fallback Synthetic fallback input.
 * @return mixed
 */
function get_option( $key, $fallback = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return isset( WCJ_Job_Store::$rows[ $key ] ) ? WCJ_Job_Store::state_value( WCJ_Job_Store::$rows[ $key ] ) : $fallback; }
/**
 * Synthetic update option fixture helper.
 *
 * @param mixed $key Synthetic key input.
 * @param mixed $value Synthetic value input.
 * @return mixed
 */
function update_option( $key, $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	WCJ_Job_Store::$rows[ $key ] = WCJ_Job_Store::value_state( $value );
	return true; }
/**
 * Synthetic wp date fixture helper.
 *
 * @param mixed $format Synthetic format input.
 * @param mixed $time Synthetic time input.
 * @return mixed
 */
function wp_date( $format, $time ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return gmdate( $format, $time ); }
/**
 * Synthetic wp list pluck fixture helper.
 *
 * @param mixed $rows Synthetic rows input.
 * @param mixed $key Synthetic key input.
 * @return mixed
 */
function wp_list_pluck( $rows, $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute required by the isolated native class.
	return array_column( $rows, $key ); }

/**
 * Encode a synthetic value without the WordPress runtime.
 *
 * @param mixed $value Fixture value.
 * @param int   $flags JSON flags.
 * @return string|false
 */
function wp_json_encode( $value, $flags = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Core API substitute.
    // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implementation of the core substitute.
	return json_encode( $value, $flags );
}
