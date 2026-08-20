<?php
/**
 * Booster 8.4 option-shortcode adversarial permission regression for wp eval-file.
 *
 * The vulnerable path is WordPress shortcode parsing. These checks exercise both
 * do_shortcode() and the registered callbacks directly so a caller cannot bypass
 * the capability check by avoiding a normal page render.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function codex_booster_84_security_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function codex_booster_84_user_for_role( $role, &$created_users ) {
	$username = 'codex_booster_84_' . $role;
	$user_id  = username_exists( $username );
	if ( ! $user_id ) {
		$user_id = wp_create_user( $username, wp_generate_password(), $username . '@example.invalid' );
		codex_booster_84_security_assert( ! is_wp_error( $user_id ), 'Could not create the ' . $role . ' fixture.' );
		$created_users[] = (int) $user_id;
	}
	$user = new WP_User( $user_id );
	$user->set_role( $role );
	return (int) $user_id;
}

function codex_booster_84_shortcode_callback( $tag ) {
	global $shortcode_tags;
	codex_booster_84_security_assert( isset( $shortcode_tags[ $tag ] ) && is_callable( $shortcode_tags[ $tag ] ), $tag . ' callback is unavailable.' );
	return $shortcode_tags[ $tag ];
}

$original_user_id = get_current_user_id();
$created_users    = array();
$fixture_options  = array(
	'wcj_codex_84_private_setting' => 'codex-private-value',
	'prefix_wcj_codex_84_setting'  => 'codex-similar-name-value',
	'wcj_codex_84_array_setting'   => array(
		'first'  => 'alpha',
		'second' => 'beta',
	),
);
$results          = array();

try {
	codex_booster_84_security_assert( shortcode_exists( 'wcj_get_option' ), 'wcj_get_option shortcode is not registered.' );
	codex_booster_84_security_assert( shortcode_exists( 'wcj_wp_option' ), 'wcj_wp_option shortcode is not registered.' );

	foreach ( $fixture_options as $name => $value ) {
		update_option( $name, $value, false );
	}

	$roles = array( 'customer', 'subscriber', 'contributor', 'author', 'editor', 'shop_manager' );
	$users = array();
	foreach ( $roles as $role ) {
		$users[ $role ] = codex_booster_84_user_for_role( $role, $created_users );
	}
	$administrators = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	codex_booster_84_security_assert( ! empty( $administrators ), 'Administrator fixture missing.' );
	$users['administrator'] = (int) reset( $administrators );

	$get_option_callback = codex_booster_84_shortcode_callback( 'wcj_get_option' );
	$wp_option_callback  = codex_booster_84_shortcode_callback( 'wcj_wp_option' );
	$private_name        = 'wcj_codex_84_private_setting';
	$private_value       = $fixture_options[ $private_name ];
	$cases               = array( 'guest', 'customer', 'subscriber', 'contributor', 'author', 'editor', 'shop_manager', 'administrator' );

	foreach ( $cases as $role ) {
		wp_set_current_user( 'guest' === $role ? 0 : $users[ $role ] );
		$allowed  = current_user_can( 'manage_woocommerce' );
		$expected = $allowed ? $private_value : '';

		$shortcode_get = do_shortcode( '[wcj_get_option name="' . $private_name . '" default="must-not-leak"]' );
		$shortcode_wp  = do_shortcode( '[wcj_wp_option option="' . $private_name . '" default="must-not-leak"]' );
		$direct_get    = call_user_func( $get_option_callback, array( 'name' => $private_name, 'default' => 'must-not-leak' ) );
		$direct_wp     = call_user_func( $wp_option_callback, array( 'option' => $private_name, 'default' => 'must-not-leak' ) );

		codex_booster_84_security_assert( $expected === $shortcode_get, $role . ' shortcode wcj_get_option boundary failed.' );
		codex_booster_84_security_assert( $expected === $shortcode_wp, $role . ' shortcode wcj_wp_option boundary failed.' );
		codex_booster_84_security_assert( $expected === $direct_get, $role . ' direct wcj_get_option boundary failed.' );
		codex_booster_84_security_assert( $expected === $direct_wp, $role . ' direct wcj_wp_option boundary failed.' );
		$results[ $role ] = $allowed ? 'allowed' : 'denied';
	}

	// Contributor is the specifically reported incomplete-patch role.
	wp_set_current_user( $users['contributor'] );
	foreach (
		array(
			array( 'name' => 'prefix_wcj_codex_84_setting', 'default' => 'default-leak' ),
			array( 'name' => 'wcj_codex_84_array_setting', 'field' => 'second', 'default' => 'default-leak' ),
			array( 'name' => 'wcj_missing_codex_84_setting', 'default' => 'default-leak' ),
			array( 'name' => '' ),
			array(),
			array( 'name' => array( 'wcj_codex_84_private_setting' ) ),
			array( 'name' => 'wcj_' . str_repeat( 'x', 220 ), 'default' => 'default-leak' ),
		) as $atts
	) {
		codex_booster_84_security_assert( '' === call_user_func( $get_option_callback, $atts ), 'Contributor direct wcj_get_option malformed-input denial failed.' );
	}
	foreach (
		array(
			array( 'option' => 'prefix_wcj_codex_84_setting', 'default' => 'default-leak' ),
			array( 'option' => 'wcj_missing_codex_84_setting', 'default' => 'default-leak' ),
			array( 'option' => '' ),
			array(),
			array( 'option' => array( 'wcj_codex_84_private_setting' ) ),
			array( 'option' => 'wcj_' . str_repeat( 'x', 220 ), 'default' => 'default-leak' ),
		) as $atts
	) {
		codex_booster_84_security_assert( '' === call_user_func( $wp_option_callback, $atts ), 'Contributor direct wcj_wp_option malformed-input denial failed.' );
	}

	// Authorized users retain exact scalar, similar-name, array-field, and default behavior.
	foreach ( array( 'shop_manager', 'administrator' ) as $role ) {
		wp_set_current_user( $users[ $role ] );
		codex_booster_84_security_assert( $private_value === call_user_func( $get_option_callback, array( 'name' => $private_name ) ), $role . ' scalar access failed.' );
		codex_booster_84_security_assert( 'codex-similar-name-value' === call_user_func( $get_option_callback, array( 'name' => 'prefix_wcj_codex_84_setting' ) ), $role . ' similar-name access changed.' );
		codex_booster_84_security_assert( 'beta' === call_user_func( $get_option_callback, array( 'name' => 'wcj_codex_84_array_setting', 'field' => 'second' ) ), $role . ' array field access failed.' );
		codex_booster_84_security_assert( 'safe-default' === call_user_func( $get_option_callback, array( 'name' => 'wcj_missing_codex_84_setting', 'default' => 'safe-default' ) ), $role . ' default behavior failed.' );
		codex_booster_84_security_assert( $private_value === call_user_func( $wp_option_callback, array( 'option' => $private_name ) ), $role . ' wcj_wp_option missing-default behavior failed.' );
	}

	// The existing name restriction is independent of role/capability.
	wp_set_current_user( $users['administrator'] );
	foreach ( array( 'admin_email', 'woocommerce_stripe_settings', 'WCJ_UPPERCASE_LOOKALIKE' ) as $non_booster_option ) {
		codex_booster_84_security_assert( '' === call_user_func( $get_option_callback, array( 'name' => $non_booster_option, 'default' => 'must-not-leak' ) ), 'Non-wcj wcj_get_option access must remain denied.' );
		codex_booster_84_security_assert( '' === call_user_func( $wp_option_callback, array( 'option' => $non_booster_option, 'default' => 'must-not-leak' ) ), 'Non-wcj wcj_wp_option access must remain denied.' );
	}

	$results['direct_callback']      = 'passed';
	$results['malformed_inputs']     = 'passed';
	$results['default_nonleakage']   = 'passed';
	$results['name_restriction']     = 'passed';
	$results['dedicated_endpoint']   = 'not_applicable';
	$results['shortcode_nonce']      = 'not_applicable';
} finally {
	foreach ( array_keys( $fixture_options ) as $name ) {
		delete_option( $name );
	}
	wp_set_current_user( $original_user_id );
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	foreach ( $created_users as $user_id ) {
		wp_delete_user( $user_id );
	}
}

echo wp_json_encode(
	array(
		'ok'      => true,
		'results' => $results,
	)
);
