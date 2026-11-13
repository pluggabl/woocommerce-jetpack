<?php
/**
 * Guarded local product render. No remote arming, persistence or export.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Two-stage admission before native mutation hooks, then exact product binding. */
final class WCJ_AI_Capture {

	/** Selected product admitted before native modules construct.
	 *
	 * @var int
	 */
	private static $product = 0;

	/** Early admission must not call wcj_get_option: it constructs Booster. */
	public static function guard() {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Exact closed raw admission below, then local cookie/nonce verification.
		if ( ! array_key_exists( 'wcj_ai_capture', $_GET ) && ! array_key_exists( 'wcj_ai_capture', $_POST ) ) {
			return;
		}
		$keys = array( 'p', 'post_type', 'wcj_ai_capture', '_wpnonce' );
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || $_POST || $_FILES ||
			'' !== file_get_contents( 'php://input', false, null, 0, 1 ) ||
			array_diff( array_keys( $_GET ), $keys ) || count( $_GET ) !== 4 || ! WCJ_AI_Preview::request_matches( $_GET ) ) {
			WCJ_AI_Preview::deny();
		}
		foreach ( $keys as $key ) {
			if ( ! isset( $_GET[ $key ], $_REQUEST[ $key ] ) || ! is_string( $_GET[ $key ] ) || $_GET[ $key ] !== $_REQUEST[ $key ] ) {
				WCJ_AI_Preview::deny();
			}
		}
		// Reject duplicate, encoded and ambiguous parameter spellings before PHP normalization can hide them.
		$query = $_SERVER['QUERY_STRING'] ?? '';
		$path  = explode( '?', $_SERVER['REQUEST_URI'] ?? '', 2 )[0];
		if ( ! is_string( $query ) || strlen( $query ) > 180 ||
			wp_parse_url( home_url( '/' ), PHP_URL_PATH ) !== $path ||
			1 !== preg_match( '/^p=[1-9][0-9]{0,9}&post_type=product&wcj_ai_capture=1&_wpnonce=[a-zA-Z0-9]{10}$/D', $query ) ||
			'product' !== $_GET['post_type'] || '1' !== $_GET['wcj_ai_capture'] ||
			1 !== preg_match( '/^[1-9][0-9]{0,9}$/D', $_GET['p'] ) ) {
			WCJ_AI_Preview::deny();
		}
		WCJ_AI_Preview::authorize();
		self::$product = (int) $_GET['p'];
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcj_ai_role_capture_' . self::$product ) ||
			'no' !== get_option( 'wcj_url_coupons_enabled', 'no' ) ) {
			WCJ_AI_Preview::deny();
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		add_action( 'wp_loaded', array( __CLASS__, 'native_guard' ), PHP_INT_MIN );
		add_action( 'wp', array( __CLASS__, 'query_guard' ), PHP_INT_MIN );
		add_filter( 'template_include', array( __CLASS__, 'arm' ), PHP_INT_MAX );
		add_filter( 'redirect_canonical', '__return_false' );
	}

	/** Detect effective native URL-coupon activation, including dynamic option filters. */
	public static function native_guard() {
		global $wp_filter;
		foreach ( $wp_filter['wp_loaded']->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if ( is_array( $function ) && isset( $function[0], $function[1] ) &&
					$function[0] instanceof WCJ_URL_Coupons && 'maybe_apply_url_coupon' === $function[1] ) {
					WCJ_AI_Preview::deny();
				}
			}
		}
	}

	/** Bind before native wp actions; do not yet observe pre-template callbacks. */
	public static function query_guard() {
		if ( ! is_product() || get_queried_object_id() !== self::$product || 'publish' !== get_post_status( self::$product ) ) {
			WCJ_AI_Preview::deny();
		}
	}

	/**
	 * Arm just before the native template. Never replace or replay that template.
	 *
	 * @param string $template Native template path.
	 * @return string Identical template path.
	 */
	public static function arm( $template ) {
		require_once __DIR__ . '/class-wcj-ai-role-observation.php';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- Guard verified exact nonce shape; collector independently verifies cookie, nonce and product.
		if ( ! WCJ_AI_Role_Observation::arm( self::$product, $_GET['_wpnonce'] ) ) {
			WCJ_AI_Preview::deny();
		}
		add_action( 'wp_footer', array( __CLASS__, 'render' ), PHP_INT_MIN );
		return $template;
	}

	/** Close observation before formatting, so evidence rendering does not observe itself. */
	public static function render() {
		$view = WCJ_AI_Role_Observation::close();
		if ( ! $view ) {
			return;
		}
		echo '<section aria-labelledby="wcj-ai-capture-title"><h2 id="wcj-ai-capture-title">' . esc_html__( 'Native role-price observations — local preview', 'woocommerce-jetpack' ) . '</h2>';
		echo '<p>' . esc_html__( 'Only this merchant’s selected product render, from template entry to footer entry. No role impersonation. Other phases, module instances, wholesale, multicurrency, add-ons, fees, payment and shipping are unobserved. Ordinary native page behavior still runs; this is not a zero-side-effect sandbox.', 'woocommerce-jetpack' ) . '</p>';
		echo '<p>' . esc_html__( 'Ephemeral: not saved or shared. An empty list means no callback was observed, not that no rule applies. Dispatch registration is unqualified; unknown cache origins remain unknown.', 'woocommerce-jetpack' ) . '</p>';
		if ( $view['truncated'] ) {
			echo '<p><strong>' . esc_html__( 'Observation truncated or incomplete.', 'woocommerce-jetpack' ) . '</strong></p>';
		}
		echo '<pre>' . esc_html( wp_json_encode( $view, JSON_PRETTY_PRINT ) ) . '</pre></section>';
	}
}

add_action( 'plugins_loaded', array( 'WCJ_AI_Capture', 'guard' ), PHP_INT_MIN );
