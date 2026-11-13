<?php
/**
 * Bounded request-local observations, never a replacement pricing engine.
 * No route, persistent receipt or remote export is registered here.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
if ( is_readable( __DIR__ . '/class-wcj-ai-price-value.php' ) ) {
	try {
		require_once __DIR__ . '/class-wcj-ai-price-value.php';
	} catch ( Throwable $exception ) {
		unset( $exception ); // Admission fails closed.
	}
}

/** Native role-method observation with explicit, local-cookie admission. */
final class WCJ_AI_Role_Observation {

	/** Request-local bounded state; raw aliases/keys never leave this class.
	 *
	 * @var array|null
	 */
	private static $capture = null;

	/**
	 * Admit one selected native product GET. A controller must reject unsafe GETs early.
	 *
	 * @param int    $product_id Exact resolved product page ID.
	 * @param string $nonce Current local merchant nonce.
	 * @return bool Whether capture was armed.
	 */
	public static function arm( $product_id, $nonce ) {
		if ( ! class_exists( 'WCJ_AI_Price_Value', false ) || ! function_exists( 'is_product' ) || ! function_exists( 'wcj_is_module_enabled' ) || ! function_exists( 'wcj_check_modules_by_user_roles' ) || null !== self::$capture || ! defined( 'WCJ_AI_PREVIEW' ) || true !== WCJ_AI_PREVIEW || headers_sent() ||
		! is_int( $product_id ) || $product_id < 1 || ! is_string( $nonce ) ||
		! is_product() || get_queried_object_id() !== $product_id || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
		'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! empty( $_POST ) || // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Exact enum comparison; transforming input would weaken admission.
		! get_current_user_id() || wp_validate_auth_cookie( '', 'logged_in' ) !== get_current_user_id() ||
		! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( $nonce, 'wcj_ai_role_capture_' . $product_id ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registered capability.
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified above; inspect only parameter names for strict admission.
		if ( array_diff( array_keys( $_GET ), array( 'p', 'post_type', 'wcj_ai_capture', '_wpnonce' ) ) ||
		( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( 'price_by_user_role' ) ) || ( wcj_is_module_enabled( 'modules_by_user_roles' ) && ! wcj_check_modules_by_user_roles( 'price_by_user_role' ) ) ) {
			return false;
		}
		nocache_headers();
		header( 'Cache-Control: private, no-store, max-age=0' );
		header( 'Referrer-Policy: no-referrer' );
		self::$capture = array(
			'selected'  => $product_id,
			'open'      => true,
			'events'    => array(),
			'stack'     => array(),
			'roles'     => array(),
			'origins'   => array(),
			'truncated' => false,
			'owner'     => null,
		);
		return true;
	}

	/**
	 * Begin from locals already consumed by the native method; no getters here.
	 *
	 * @param int    $product_id Native-resolved product ID.
	 * @param mixed  $input Original value.
	 * @param string $hook Native current filter name, reduced to an allowlist.
	 * @param int    $priority Native configured priority.
	 * @param object $owner Native module instance, retained privately to bind its cache.
	 * @return int|null Internal ticket.
	 */
	public static function begin( $product_id, $input, $hook, $priority, $owner ) {
		if ( ! self::$capture || ! self::$capture['open'] || self::$capture['truncated'] || self::$capture['selected'] !== $product_id ) {
			return null; }
		try {
			if ( ! is_object( $owner ) ) {
				return null; }
			if ( null === self::$capture['owner'] ) {
				self::$capture['owner'] = $owner; }
			if ( self::$capture['owner'] !== $owner ) {
				return null; }
			if ( count( self::$capture['events'] ) >= 64 || count( self::$capture['stack'] ) >= 16 ) {
				self::$capture['truncated'] = true;
				return null; }
			$ticket                    = count( self::$capture['events'] );
			$known                     = in_array( $hook, array( 'woocommerce_product_get_price', 'woocommerce_product_get_regular_price', 'woocommerce_product_get_sale_price', 'woocommerce_product_variation_get_price', 'woocommerce_product_variation_get_regular_price', 'woocommerce_product_variation_get_sale_price' ), true );
			self::$capture['events'][] = array(
				'sequence'     => $ticket,
				'parent'       => self::$capture['stack'] ? end( self::$capture['stack'] ) : null,
				'hook'         => $known ? $hook : 'unsupported_context',
				'priority'     => is_int( $priority ) ? $priority : null,
				'invocation'   => 'method_observed_dispatch_unqualified',
				'input'        => WCJ_AI_Price_Value::project( $input ),
				'output'       => null,
				'decisions'    => array(),
				'cache'        => 'unobserved',
				'cache_origin' => null,
				'cache_role'   => null,
			);
			self::$capture['stack'][]  = $ticket;
			return $ticket;
		} catch ( Throwable $exception ) {
			self::$capture['truncated'] = true;
			return null; }
	}

	/**
	 * Alias native role text only in bounded memory, without exporting a raw slug.
	 *
	 * @param mixed $role Already-consumed native role.
	 * @return string|null Generated label.
	 */
	private static function role( $role ) {
		if ( ! is_string( $role ) || strlen( $role ) > 128 ) {
			return null; }
		$key = array_search( $role, self::$capture['roles'], true );
		if ( false === $key ) {
			if ( count( self::$capture['roles'] ) >= 32 ) {
				self::$capture['truncated'] = true;
				return null; }
			self::$capture['roles'][] = $role;
			$key                      = count( self::$capture['roles'] ) - 1;
		}
		return 'role_' . ( $key + 1 );
	}

	/**
	 * Observe a native return and always return the exact original value.
	 *
	 * @param int|null $ticket Internal selected-call ticket.
	 * @param mixed    $value Native result, never modified.
	 * @param string   $reason Fixed source-branch enum.
	 * @param mixed    $role Native evaluator role.
	 * @param mixed    $factor Already-consumed multiplier, where applicable.
	 * @return mixed Original result.
	 */
	public static function decision( $ticket, $value, $reason, $role, $factor = null ) {
		if ( null === $ticket || ! self::$capture || ! self::$capture['open'] || self::$capture['truncated'] || ! self::$capture['stack'] || end( self::$capture['stack'] ) !== $ticket ) {
			return $value; }
		try {
			$allowed = array( 'extension_veto', 'changed_product', 'addon_guard', 'product_empty', 'regular_skip', 'product_multiplier', 'display_delegate', 'product_fixed_extension', 'product_regular', 'product_sale', 'on_sale_skip', 'category_empty', 'category_multiplier', 'tag_empty', 'tag_multiplier', 'global_empty', 'global_multiplier', 'unchanged' );
			$ticket  = end( self::$capture['stack'] );
			if ( ! in_array( $reason, $allowed, true ) || count( self::$capture['events'][ $ticket ]['decisions'] ) >= 8 ) {
				self::$capture['truncated'] = true;
				return $value; }
			self::$capture['events'][ $ticket ]['decisions'][] = array(
				'reason'         => $reason,
				'evaluator_role' => self::role( $role ),
				'role_source'    => 'booster_effective_role',
				'factor'         => null === $factor ? null : WCJ_AI_Price_Value::project( $factor ),
				'result'         => WCJ_AI_Price_Value::project( $value ),
			);
		} catch ( Throwable $exception ) {
			self::$capture['truncated'] = true; }
		return $value;
	}

	/**
	 * Finish a native method, recording cache origin only when actually observed.
	 *
	 * @param int|null $ticket Internal ticket.
	 * @param mixed    $value Exact native result.
	 * @param string   $mode Cache mode enum.
	 * @param mixed    $role Already-consumed cache-key role, separate from evaluator.
	 * @param string   $key Native cache key, memory-only.
	 * @return mixed Exact original result.
	 */
	public static function finish( $ticket, $value, $mode, $role = null, $key = '' ) {
		if ( null === $ticket || ! self::$capture || ! self::$capture['open'] ) {
			return $value; }
		try {
			if ( end( self::$capture['stack'] ) !== $ticket ) {
				self::$capture['truncated'] = true;
				return $value; }
			array_pop( self::$capture['stack'] );
			self::$capture['events'][ $ticket ]['output']     = WCJ_AI_Price_Value::project( $value );
			self::$capture['events'][ $ticket ]['cache']      = in_array( $mode, array( 'hit', 'miss', 'bypass' ), true ) ? $mode : 'unobserved';
			self::$capture['events'][ $ticket ]['cache_role'] = null === $role ? null : self::role( $role );
			if ( 'hit' === $mode ) {
				self::$capture['events'][ $ticket ]['cache_origin'] = self::$capture['origins'][ $key ] ?? null; }
			if ( 'miss' === $mode && count( self::$capture['origins'] ) < 64 ) {
				self::$capture['origins'][ $key ] = $ticket; }
		} catch ( Throwable $exception ) {
			self::$capture['truncated'] = true; }
		return $value;
	}

	/**
	 * Close before rendering; no roles/cache keys or object state escape.
	 *
	 * @return array|null Ephemeral local evidence, not a persisted shareable receipt.
	 */
	public static function close() {
		if ( ! self::$capture || ! self::$capture['open'] ) {
			return null; }
		self::$capture['open'] = false;
		$out                   = array(
			'schema'      => 1,
			'phase'       => 'product_render',
			'persistence' => 'none',
			'non_atomic'  => true,
			'truncated'   => self::$capture['truncated'] || (bool) self::$capture['stack'],
			'events'      => self::$capture['events'],
			'unobserved'  => array( 'earlier_phases', 'other_role_module_instances', 'wholesale', 'multicurrency', 'addons', 'fees', 'payment', 'shipping' ),
		);
		$json                  = wp_json_encode( $out );
		if ( ! is_string( $json ) || strlen( $json ) > 65536 ) {
			$out['events']    = array();
			$out['truncated'] = true; }
		self::$capture['owner']   = null;
		self::$capture['roles']   = array();
		self::$capture['origins'] = array();
		return $out;
	}
}
