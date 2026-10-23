<?php
/**
 * Bounded Save-routing API doubles, not native WordPress integration.
 *
 * @package Booster_For_WooCommerce/Tests
 */

/**
 * Reset observable request state.
 *
 * @param array $options Initial options.
 * @return void
 */
function codex_catalog_save_reset( $options ) {
	$GLOBALS['codex_catalog_save'] = array(
		'options'           => $options,
		'updates'           => array(),
		'actions'           => array(),
		'redirects'         => array(),
		'denials'           => array(),
		'field_saves'       => 0,
		'schema_reads'      => 0,
		'schema_sections'   => array(),
		'autoload_calls'    => 0,
		'autoload_settings' => array(),
		'capabilities'      => array(
			'manage_woocommerce' => true,
			'manage_options'     => true,
		),
		'capability_reads'  => array(),
		'nonce_actions'     => array(),
		'translations'      => array(),
	);
	$_POST                         = array();
}

/**
 * Sanitize the bounded ASCII key fixtures.
 *
 * @param string $value Fixture input.
 * @return string
 */
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) );
}

/**
 * Sanitize bounded scalar fixtures, not a complete WP sanitizer clone.
 *
 * @param string $value Fixture input.
 * @return string
 */
function sanitize_text_field( $value ) {
	return trim( preg_replace( '/<[^>]*>/', '', $value ) );
}

/**
 * Unslash the fixture strings.
 *
 * @param string $value Fixture string.
 * @return string
 */
function wp_unslash( $value ) {
	return stripslashes( $value );
}

/**
 * Verify an action-bound synthetic nonce, not a native WP nonce.
 *
 * @param string $value Synthetic nonce.
 * @param string $action Requested action.
 * @return int|false
 */
function wp_verify_nonce( $value, $action ) {
	$GLOBALS['codex_catalog_save']['nonce_actions'][] = $action;
	return hash_equals( wp_create_nonce( $action ), $value ) ? 1 : false;
}

/**
 * Create a deterministic action-bound fixture nonce.
 *
 * @param string $action Requested action.
 * @return string
 */
function wp_create_nonce( $action ) {
	return hash( 'sha256', 'codex-catalog-save-fixture:' . $action );
}

/**
 * Model the admin context covered by this suite.
 *
 * @return bool
 */
function is_admin() {
	return true;
}

/**
 * Check the exact requested capability, not an unrestricted allow flag.
 *
 * @param string $capability Requested capability.
 * @return bool
 */
function current_user_can( $capability ) {
	$GLOBALS['codex_catalog_save']['capability_reads'][] = $capability;
	return ! empty( $GLOBALS['codex_catalog_save']['capabilities'][ $capability ] );
}

/**
 * Read the isolated option store.
 *
 * @param string $key Option key.
 * @param mixed  $fallback Missing-value fallback.
 * @return mixed
 */
function wcj_get_option( $key, $fallback = '' ) {
	return $GLOBALS['codex_catalog_save']['options'][ $key ] ?? $fallback;
}

/**
 * Record the translation domain and return source text.
 *
 * @param string $value Source text.
 * @param string $domain Translation domain.
 * @return string
 */
function __( $value, $domain = '' ) {
	$GLOBALS['codex_catalog_save']['translations'][] = $domain;
	return $value;
}

/**
 * Escape diagnostic text without loading WordPress.
 *
 * @param string $value Diagnostic text.
 * @return string
 */
function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * Translate and escape the production denial message.
 *
 * @param string $value Source text.
 * @param string $domain Translation domain.
 * @return string
 */
function esc_html__( $value, $domain = '' ) {
	$GLOBALS['codex_catalog_save']['translations'][] = $domain;
	return esc_html( $value );
}

/**
 * Record the real denial arguments and stop this request.
 *
 * @param string $message Denial message.
 * @param string $title Denial title.
 * @param array  $args Response arguments.
 * @throws DomainException Always, to stop this fixture request.
 * @return never
 */
function wp_die( $message, $title = '', $args = array() ) {
	$GLOBALS['codex_catalog_save']['denials'][] = array( $message, $title, $args );
	throw new DomainException( 'denied' );
}

/**
 * Provide two known module status keys, not an invented settings universe.
 *
 * @return object
 */
function w_c_j() {
	return (object) array(
		'module_statuses' => array(
			array( 'id' => 'wcj_add_to_cart_enabled' ),
			array( 'id' => 'wcj_price_labels_enabled' ),
		),
	);
}

/**
 * Match the existing paid-edition API exactly.
 *
 * @return object
 */
function WCJ() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Exact existing paid-edition API name.
	return w_c_j();
}

/**
 * Record every persistence call, including no-op writes.
 *
 * @param string $key Option key.
 * @param mixed  $value Option value.
 * @return void
 */
function update_option( $key, $value ) {
	$GLOBALS['codex_catalog_save']['options'][ $key ] = $value;
	$GLOBALS['codex_catalog_save']['updates'][]       = array( $key, $value );
}

/**
 * Record the complete hook payload.
 *
 * @param string $name Action name.
 * @param mixed  ...$args Action arguments.
 * @return void
 */
function do_action( $name, ...$args ) {
	$GLOBALS['codex_catalog_save']['actions'][] = array( $name, $args );
}

/**
 * Build a non-routable admin URL.
 *
 * @param string $path Admin path.
 * @return string
 */
function admin_url( $path = '' ) {
	return 'https://fixture.invalid/wp-admin/' . $path;
}

/**
 * Record a redirect before the production method exits.
 *
 * @param string $url Destination URL.
 * @throws RuntimeException Always, to intercept the production exit.
 * @return never
 */
function wp_safe_redirect( $url ) {
	$GLOBALS['codex_catalog_save']['redirects'][] = $url;
	throw new RuntimeException( 'redirect' );
}

/**
 * Assert and retain a named check ledger.
 *
 * @param bool   $condition Required condition.
 * @param string $label Unique diagnostic label.
 * @throws LogicException When the required condition is false.
 * @return void
 */
function codex_catalog_save_check( $condition, $label ) {
	if ( ! $condition ) {
		throw new LogicException( esc_html( $label ) );
	}
	$GLOBALS['codex_catalog_save_checks'][] = $label;
}

/**
 * Call the inherited real method and require its redirect boundary.
 *
 * @param Catalog_Save_Fixture $fixture Source-bound subclass.
 * @param string               $label Case label.
 * @throws RuntimeException When an unexpected runtime exception is encountered.
 * @return void
 */
function codex_catalog_save_expect_redirect( $fixture, $label ) {
	$redirected = false;
	try {
		$fixture->wcj_save_module_settings();
	} catch ( RuntimeException $exception ) {
		if ( 'redirect' !== $exception->getMessage() ) {
			throw $exception;
		}
		$redirected = true;
	}
	codex_catalog_save_check( $redirected, $label . ': real save reached redirect boundary' );
}
