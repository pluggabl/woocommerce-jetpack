<?php
/**
 * Booster 8.4 option-shortcode permission regression for `wp eval-file`.
 *
 * Verifies the sensitive function itself denies low-privilege execution, so
 * indirect callers such as WordPress AJAX shortcode parsing cannot bypass it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function codex_booster_84_security_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function codex_booster_84_user_for_role( $role ) {
	$username = 'codex_booster_84_' . $role;
	$user_id  = username_exists( $username );
	if ( ! $user_id ) {
		$user_id = wp_create_user( $username, wp_generate_password(), $username . '@example.invalid' );
		codex_booster_84_security_assert( ! is_wp_error( $user_id ), 'Could not create the ' . $role . ' fixture.' );
	}
	$user = new WP_User( $user_id );
	$user->set_role( $role );
	return (int) $user_id;
}

codex_booster_84_security_assert( shortcode_exists( 'wcj_get_option' ), 'wcj_get_option shortcode is not registered.' );
codex_booster_84_security_assert( shortcode_exists( 'wcj_wp_option' ), 'wcj_wp_option shortcode is not registered.' );

$option_name  = 'wcj_codex_84_private_setting';
$option_value = 'codex-private-value';
update_option( $option_name, $option_value, false );

$roles = array( 'customer', 'subscriber', 'contributor', 'author', 'editor', 'shop_manager' );
$users = array();
foreach ( $roles as $role ) {
	$users[ $role ] = codex_booster_84_user_for_role( $role );
}
$administrators = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
codex_booster_84_security_assert( ! empty( $administrators ), 'Administrator fixture missing.' );
$users['administrator'] = (int) reset( $administrators );

$results = array();
$cases   = array( 'guest', 'customer', 'subscriber', 'contributor', 'author', 'editor', 'shop_manager', 'administrator' );
foreach ( $cases as $role ) {
	wp_set_current_user( 'guest' === $role ? 0 : $users[ $role ] );
	$get_option = do_shortcode( '[wcj_get_option name="' . $option_name . '"]' );
	$wp_option  = do_shortcode( '[wcj_wp_option option="' . $option_name . '"]' );
	$allowed    = current_user_can( 'manage_woocommerce' );
	$expected   = $allowed ? $option_value : '';
	codex_booster_84_security_assert( $expected === $get_option, $role . ' wcj_get_option boundary failed.' );
	codex_booster_84_security_assert( $expected === $wp_option, $role . ' wcj_wp_option boundary failed.' );
	$results[ $role ] = $allowed ? 'allowed' : 'denied';
}

wp_set_current_user( $users['administrator'] );
codex_booster_84_security_assert( '' === do_shortcode( '[wcj_get_option name="admin_email"]' ), 'Non-Booster options must remain denied.' );
codex_booster_84_security_assert( '' === do_shortcode( '[wcj_wp_option option="admin_email"]' ), 'Non-Booster wp_option access must remain denied.' );

delete_option( $option_name );
wp_set_current_user( 0 );

echo wp_json_encode(
	array(
		'ok'      => true,
		'results' => $results,
	)
);
