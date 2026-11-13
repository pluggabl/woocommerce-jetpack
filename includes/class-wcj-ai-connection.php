<?php
/**
 * Internal, dedicated-principal connection boundary. No remote business writes.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Quarantine stays registered even when the preview and its tools are off. */
final class WCJ_AI_Connection {

	/** The only admitted REST route. */
	const ROUTE = '/booster-ai/v1/mcp';
	/** Durable principal classification; revocation never deletes it. */
	const MARKER = '_wcj_ai_principal';
	/** Request-local admitted state.
	 *
	 * @var bool
	 */
	private static $admitted = false;
	/** Request-local hinted state.
	 *
	 * @var bool
	 */
	private static $hinted = false;
	/** Request-local rejected state.
	 *
	 * @var bool
	 */
	private static $rejected = false;
	/** Request-local authenticated state.
	 *
	 * @var array|null
	 */
	private static $authenticated = null;

	/**
	 * Enabled.
	 *
	 * @return bool Explicit preview switch, not a request parameter.
	 */
	public static function enabled() {
		return defined( 'WCJ_AI_PREVIEW' ) && true === WCJ_AI_PREVIEW;
	}

	/**
	 * Principal.
	 *
	 * @param int $id User ID.
	 * @return bool Durable identity, independent of roles or grant state.
	 */
	public static function principal( $id ) {
		return $id > 0 && '1' === get_user_meta( $id, self::MARKER, true );
	}

	/**
	 * Build a finite external error.
	 *
	 * @return WP_Error Finite external error.
	 */
	private static function error() {
		return new WP_Error( 'wcj_ai_connection_unavailable', 'Connection unavailable.', array( 'status' => 403 ) );
	}

	/** Stop before native business handlers. */
	private static function deny() {
		wp_die( 'Connection unavailable.', '', array( 'response' => 403 ) );
	}

	/**
	 * Username hints can only cause denial, never authenticate a request.
	 *
	 * @param string $name Untrusted username/email hint.
	 * @return bool Whether the hinted account is quarantined.
	 */
	private static function hinted_name( $name ) {
		if ( ! is_string( $name ) ) {
			return false;
		}
		// Native login lookup trims and sanitizes. Do not reject the hint before that normalization.
		if ( strlen( $name ) > 8192 ) {
			self::deny();
		}
		$user = get_user_by( 'login', $name );
		if ( ! $user && is_email( trim( $name ) ) ) {
			$user = get_user_by( 'email', trim( $name ) );
		}
		return $user && self::principal( $user->ID );
	}

	/** Classify aliases conservatively, then require one canonical HTTP shape. */
	public static function early_guard() {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Raw closed protocol admission; native Application Password authentication follows.
		self::$hinted = self::hinted_name( $_SERVER['PHP_AUTH_USER'] ?? '' ) || self::hinted_name( $_POST['log'] ?? '' ) || self::hinted_name( $_POST['username'] ?? '' );
		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			$header = $_SERVER[ $key ] ?? '';
			if ( is_string( $header ) && strlen( $header ) <= 4096 && preg_match( '/^Basic ([A-Za-z0-9+\/=]+)$/iD', $header, $match ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Bounded HTTP Basic username hint used only to deny, never to authenticate.
				$decoded = base64_decode( $match[1], true );
				if ( is_string( $decoded ) ) {
					self::$hinted = self::$hinted || self::hinted_name( explode( ':', $decoded, 2 )[0] );
				}
			}
		}
		foreach ( $_COOKIE as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'wordpress_' ) && is_string( $value ) ) {
				self::$hinted = self::$hinted || self::hinted_name( explode( '|', $value, 2 )[0] );
			}
		}
		$uri      = $_SERVER['REQUEST_URI'] ?? '';
		$home     = wp_parse_url( home_url( '/' ) );
		$homepath = trim( $home['path'] ?? '', '/' );
		$target   = false;
		// Mirror native outer-slash/home-prefix normalization only to classify denial.
		// PATH_INFO can be the native rewrite target even when the URI names a script.
		foreach ( array( $uri, $_SERVER['PATH_INFO'] ?? '', $_SERVER['PHP_SELF'] ?? '' ) as $candidate ) {
			if ( ! is_string( $candidate ) ) {
				continue;
			}
			$candidate = explode( '?', $candidate, 2 )[0];
			foreach ( array( array( $candidate, $homepath ), array( rawurldecode( $candidate ), rawurldecode( $homepath ) ) ) as $parts ) {
				$path = trim( $parts[0], '/' );
				if ( '' !== $parts[1] ) {
					$path = preg_replace( '~^' . preg_quote( $parts[1], '~' ) . '~i', '', $path );
				}
				$path   = trim( $path, '/' );
				$prefix = '(?:index\\.php/)?' . preg_quote( rest_get_url_prefix(), '~' ) . '/booster-ai(?:/|$)';
				// Native rewriting also decodes after removing the raw home prefix.
				foreach ( array( $path, rawurldecode( $path ), urldecode( $path ) ) as $match ) {
					$target = $target || 1 === preg_match( '~^' . $prefix . '~iD', trim( $match, '/' ) );
				}
			}
		}
		foreach ( array( $_GET['rest_route'] ?? '', $_POST['rest_route'] ?? '' ) as $route ) {
			$target = $target || ( is_string( $route ) && 1 === preg_match( '~^/*booster-ai(?:/|$)~iD', trim( $route ) ) );
		}
		if ( ! $target && ! self::$hinted ) {
			return;
		}
		$home      = wp_parse_url( home_url( '/' ) );
		$host      = $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );
		$canonical = ( $home['path'] ?? '/' ) . '?rest_route=' . self::ROUTE;
		$method    = $_SERVER['REQUEST_METHOD'] ?? '';
		$body      = file_get_contents( 'php://input', false, null, 0, 16385 );
		$local     = 'local' === wp_get_environment_type() && in_array( $home['host'], array( '127.0.0.1', 'localhost', '::1' ), true );
		if ( ! self::enabled() || $uri !== $canonical || ( $_SERVER['HTTP_HOST'] ?? '' ) !== $host ||
			( ! is_ssl() && ! $local ) || ! in_array( $method, array( 'POST', 'GET', 'DELETE' ), true ) ||
			array( 'rest_route' => self::ROUTE ) !== $_GET || $_POST || $_FILES || $_COOKIE ||
			$_REQUEST !== $_GET || isset( $_SERVER['HTTP_ORIGIN'] ) || ! is_string( $body ) || strlen( $body ) > 16384 ||
			( 'POST' === $method ? 'application/json' !== strtolower( trim( explode( ';', $_SERVER['CONTENT_TYPE'] ?? '', 2 )[0] ) ) : '' !== $body ) ) {
			self::deny();
		}
		self::$admitted = true;
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Fresh, fixed-key read. Missing is distinct from invalid/unavailable data.
	 *
	 * @param string $key Server-owned option key.
	 * @return array|null Raw state, or null on database failure.
	 */
	public static function raw( $key ) {
		global $wpdb;
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table; prepared server-owned key. Avoid cached authorization and colour state.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ), ARRAY_A );
		return '' === $wpdb->last_error ? array(
			'exists' => null !== $row,
			'raw'    => null === $row ? '' : $row['option_value'],
		) : null;
	}

	/**
	 * One-hour server-owned grant. No current-user substitution.
	 *
	 * @param int    $id Principal ID.
	 * @param string $uuid Native authenticated application-password UUID.
	 * @return array|null Valid current grant.
	 */
	private static function grant( $id, $uuid ) {
		return self::$admitted ? self::stored_grant( $id, $uuid ) : null;
	}

	/**
	 * Stored eligibility, deliberately independent of live remote authentication.
	 *
	 * @param int    $id Bound dedicated principal.
	 * @param string $uuid Bound native application password UUID.
	 * @return array|null Fresh eligible grant, not request authorization.
	 */
	private static function stored_grant( $id, $uuid ) {
		if ( ! self::enabled() || is_multisite() || ! self::principal( $id ) ) {
			return null;
		}
		$user = get_userdata( $id );
		if ( ! $user || $user->roles || count( $user->caps ) !== 2 ||
			true !== ( $user->caps['read'] ?? null ) || true !== ( $user->caps['wcj_ai_client'] ?? null ) ) {
			return null;
		}
		$raw   = self::raw( 'wcj_ai_client_grant_' . $id );
		$grant = $raw && $raw['exists'] && strlen( $raw['raw'] ) <= 4096 ? json_decode( $raw['raw'], true ) : null;
		$keys  = array( 'schema', 'id', 'principal', 'owner', 'site', 'app_uuid', 'scopes', 'created', 'expires', 'active' );
		if ( ! is_array( $grant ) || array_keys( $grant ) !== $keys || 1 !== $grant['schema'] ||
			! is_string( $grant['id'] ) || ! preg_match( '/^[a-f0-9]{32}$/D', $grant['id'] ) ||
			$id !== $grant['principal'] || ! is_int( $grant['owner'] ) || $id === $grant['owner'] ||
			get_current_blog_id() !== $grant['site'] || ! is_string( $uuid ) || '' === $uuid || $uuid !== $grant['app_uuid'] ||
			! in_array( $grant['scopes'], array( array( 'invoice:read' ), array( 'invoice:read', 'invoice:prepare' ) ), true ) || true !== $grant['active'] ||
			! is_int( $grant['created'] ) || ! is_int( $grant['expires'] ) || $grant['created'] > time() ||
			$grant['created'] < 1 || $grant['expires'] > $grant['created'] + 3600 || $grant['expires'] <= time() ||
			self::principal( $grant['owner'] ) || ! user_can( $grant['owner'], 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce native capability, checked on explicit bound owner.
			return null;
		}
		return $grant;
	}

	/**
	 * Exact immutable grant provenance; not accepted from a tool payload.
	 *
	 * @param array $grant Validated server-owned grant.
	 * @return array Closed context.
	 */
	private static function context( array $grant ) {
		return array(
			'grant'        => $grant['id'],
			'principal'    => $grant['principal'],
			'owner'        => $grant['owner'],
			'site'         => $grant['site'],
			'app_uuid'     => $grant['app_uuid'],
			'grant_digest' => hash( 'sha256', wp_json_encode( $grant ) ),
		);
	}

	/**
	 * Fresh native AP membership snapshot, with no cached meta/filter authority.
	 * Native AP edits are not serialized Booster disconnect; supported revoke is.
	 *
	 * @param int    $id Principal ID.
	 * @param string $uuid Native UUID.
	 * @return bool Exact UUID still present in a bounded native AP array.
	 */
	private static function app_exists( $id, $uuid ) {
		global $wpdb;
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Native fixed meta key, uncached and bounded; never return credential material.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 2", $id, '_application_passwords' ), ARRAY_A );
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) || 1 !== count( $rows ) || strlen( $rows[0]['meta_value'] ) > 65536 ) {
			return false;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- Native bounded array, no classes; malformed data is denied without disclosing bytes.
		$apps = @unserialize(
			$rows[0]['meta_value'],
			array(
				'allowed_classes' => false,
				'max_depth'       => 8,
			)
		);
		if ( ! is_array( $apps ) || count( $apps ) > 100 ) {
			return false;
		}
		$matches = 0;
		foreach ( $apps as $app ) {
			if ( ! is_array( $app ) || ! isset( $app['uuid'] ) || ! is_string( $app['uuid'] ) ) {
				return false;
			}
			$matches += $uuid === $app['uuid'] ? 1 : 0;
		}
		return 1 === $matches;
	}

	/**
	 * Separate prepare authority derived only from authenticated native state.
	 *
	 * @return array|null Immutable context; never changes the current user.
	 */
	public static function prepare_context() {
		if ( ! self::allowed() ) {
			return null;
		}
		$grant = self::grant( self::$authenticated['id'], self::$authenticated['uuid'] );
		return $grant && in_array( 'invoice:prepare', $grant['scopes'], true ) && self::app_exists( $grant['principal'], $grant['app_uuid'] ) ? self::context( $grant ) : null;
	}

	/**
	 * Local apply checks stored provenance, never fabricates remote authentication.
	 * Caller must hold the mutation gate for the whole attempt.
	 *
	 * @param array $context Immutable journal provenance.
	 * @return bool Current grant, policy and native AP remain eligible.
	 */
	public static function eligible_context( array $context ) {
		if ( array_keys( $context ) !== array( 'grant', 'principal', 'owner', 'site', 'app_uuid', 'grant_digest' ) ||
			! is_int( $context['principal'] ) || ! is_string( $context['app_uuid'] ) ) {
			return false;
		}
		$grant = self::stored_grant( $context['principal'], $context['app_uuid'] );
		return $grant && in_array( 'invoice:prepare', $grant['scopes'], true ) && self::context( $grant ) === $context &&
			self::owner_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] );
	}

	/**
	 * Explicit local-owner grant rotation for a pre-provisioned dedicated account.
	 * No route/UI is registered here. The local controller must bind its nonce.
	 *
	 * @param int    $principal Dedicated principal, never the owner account.
	 * @param string $uuid Existing native AP UUID (not the password).
	 * @param bool   $prepare Explicit opt-in, never inferred from invoice:read.
	 * @return bool Verified new generation stored, not a credential response.
	 * @throws RuntimeException On unavailable authority or persistence.
	 */
	public static function issue( $principal, $uuid, $prepare = false ) {
		require_once __DIR__ . '/admin/class-wcj-invoice-job.php';
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		WCJ_Invoice_Job::authorize();
		if ( ! self::enabled() || is_multisite() || ! is_int( $principal ) || $principal < 1 || ! is_bool( $prepare ) ||
			! is_string( $uuid ) || 1 !== preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $uuid ) ||
			get_current_user_id() === $principal || self::principal( get_current_user_id() ) || ! self::owner_policy() ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			$user = get_userdata( $principal );
			if ( ! self::principal( $principal ) || ! $user || $user->roles || ( count( $user->caps ) !== 2 || true !== ( $user->caps['read'] ?? null ) || true !== ( $user->caps['wcj_ai_client'] ?? null ) ) || ! self::app_exists( $principal, $uuid ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			$key      = 'wcj_ai_client_grant_' . $principal;
			$before   = self::raw( $key );
			$previous = $before && $before['exists'] ? json_decode( $before['raw'], true ) : null;
			if ( ! $before || ( $before['exists'] && ( ! is_array( $previous ) || ( $previous['owner'] ?? null ) !== get_current_user_id() || ( $previous['site'] ?? null ) !== get_current_blog_id() ) ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			$now   = time();
			$grant = array(
				'schema'    => 1,
				'id'        => bin2hex( random_bytes( 16 ) ),
				'principal' => $principal,
				'owner'     => get_current_user_id(),
				'site'      => get_current_blog_id(),
				'app_uuid'  => $uuid,
				'scopes'    => $prepare ? array( 'invoice:read', 'invoice:prepare' ) : array( 'invoice:read' ),
				'created'   => $now,
				'expires'   => $now + 3600,
				'active'    => true,
			);
			$after = array(
				'exists' => true,
				'raw'    => wp_json_encode( $grant ),
			);
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( $key, $before, $after, array( $key ) ) || self::raw( $key ) !== $after ) {
				throw new RuntimeException( 'connection_outcome_unknown' );
			}
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return true;
	}

	/**
	 * Supported serialized disconnect; inactive grant is authoritative even if
	 * native AP bookkeeping races a separate core password deletion.
	 *
	 * @param int $principal Dedicated principal bound to the current owner.
	 * @return bool Verified inactive grant.
	 * @throws RuntimeException On busy/unknown state; never report false success.
	 */
	public static function revoke( $principal ) {
		require_once __DIR__ . '/admin/class-wcj-invoice-job.php';
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		WCJ_Invoice_Job::authorize();
		if ( ! is_int( $principal ) || $principal < 1 || self::principal( get_current_user_id() ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			$key    = 'wcj_ai_client_grant_' . $principal;
			$before = self::raw( $key );
			$grant  = $before && $before['exists'] && strlen( $before['raw'] ) <= 4096 ? json_decode( $before['raw'], true ) : null;
			if ( ! is_array( $grant ) || array_keys( $grant ) !== array( 'schema', 'id', 'principal', 'owner', 'site', 'app_uuid', 'scopes', 'created', 'expires', 'active' ) ||
				1 !== $grant['schema'] || $principal !== $grant['principal'] || get_current_user_id() !== $grant['owner'] || get_current_blog_id() !== $grant['site'] || ! is_bool( $grant['active'] ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			$grant['active'] = false;
			$after           = array(
				'exists' => true,
				'raw'    => wp_json_encode( $grant ),
			);
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( $key, $before, $after, array( $key ) ) || self::raw( $key ) !== $after ) {
				throw new RuntimeException( 'connection_outcome_unknown' );
			}
		} finally {
			WCJ_AI_Mutation_Gate::release( $gate );
		}
		return true;
	}

	/**
	 * Native task policy is qualified only when its role/allowlist restrictions are absent.
	 *
	 * @return bool Exact unrestricted native policy can be established without impersonation.
	 */
	private static function owner_policy() {
		if ( wp_using_ext_object_cache() || function_exists( 'wcj_is_module_allowed_by_allowlist' ) || ! function_exists( 'wcj_is_module_enabled' ) ) {
			return false;
		}
		foreach ( array( 'pre_option', 'pre_wp_load_alloptions', 'pre_cache_alloptions', 'alloptions', 'wcj_modules_by_user_roles_enabled', 'pre_option_wcj_modules_by_user_roles_enabled', 'option_wcj_modules_by_user_roles_enabled', 'default_option_wcj_modules_by_user_roles_enabled' ) as $filter ) {
			if ( false !== has_filter( $filter ) ) {
				return false;
			}
		}
		$raw = self::raw( 'wcj_modules_by_user_roles_enabled' );
		return $raw && ( ! $raw['exists'] || 'no' === $raw['raw'] ) && ! wcj_is_module_enabled( 'modules_by_user_roles' );
	}

	/**
	 * App errors.
	 *
	 * @param WP_Error $errors Native error accumulator.
	 * @param WP_User  $user Native verified user.
	 * @param array    $item Native verified app-password item.
	 */
	public static function app_errors( $errors, $user, $item ) {
		if ( self::principal( $user->ID ) && ! self::grant( $user->ID, $item['uuid'] ?? '' ) ) {
			self::$rejected = true;
			$errors->add( 'wcj_ai_connection_unavailable', 'Connection unavailable.' );
		}
	}

	/**
	 * App authenticated.
	 *
	 * @param WP_User $user Successfully authenticated native user.
	 * @param array   $item Native app-password item.
	 */
	public static function app_authenticated( $user, $item ) {
		if ( self::principal( $user->ID ) ) {
			self::$authenticated = array(
				'id'   => $user->ID,
				'uuid' => $item['uuid'] ?? '',
			);
		}
	}

	/**
	 * Password user.
	 *
	 * @param mixed $user Native authentication result.
	 * @return mixed No interactive password login for dedicated principals.
	 */
	public static function password_user( $user ) {
		if ( $user instanceof WP_User && self::principal( $user->ID ) ) {
			self::$rejected = true;
			return self::error();
		}
		return $user;
	}

	/**
	 * Final authenticate.
	 *
	 * @param mixed $user Native authentication result.
	 * @return mixed Reject alternative authentication paths.
	 */
	public static function final_authenticate( $user ) {
		if ( $user instanceof WP_User && self::principal( $user->ID ) &&
			( ! self::$authenticated || self::$authenticated['id'] !== $user->ID || ! self::grant( $user->ID, self::$authenticated['uuid'] ) ) ) {
			self::$rejected = true;
			return self::error();
		}
		return $user;
	}

	/**
	 * Current user.
	 *
	 * @param int|false $id Native current-user resolution.
	 * @return int|false Cookie/alternative resolution never grants access.
	 */
	public static function current_user( $id ) {
		if ( $id && self::principal( $id ) && ( ! self::$authenticated || self::$authenticated['id'] !== $id || ! self::grant( $id, self::$authenticated['uuid'] ) ) ) {
			self::$rejected = true;
			return 0;
		}
		return $id;
	}

	/**
	 * Capabilities.
	 *
	 * @param array   $allcaps Resolved capabilities.
	 * @param array   $caps Requested caps.
	 * @param array   $args Capability context.
	 * @param WP_User $user Evaluated user.
	 * @return array Dedicated users never acquire business-write privileges through role drift.
	 */
	public static function capabilities( $allcaps, $caps, $args, $user ) {
		return self::principal( $user->ID ) ? array(
			'read'          => true,
			'wcj_ai_client' => true,
		) : $allcaps;
	}

	/**
	 * Allowed.
	 *
	 * @return bool Per-request and per-tool native authentication and grant check.
	 */
	public static function allowed() {
		$id = get_current_user_id();
		return ! self::$rejected && self::$authenticated && self::$authenticated['id'] === $id &&
			function_exists( 'rest_get_authenticated_app_password' ) && rest_get_authenticated_app_password() === self::$authenticated['uuid'] &&
			self::grant( $id, self::$authenticated['uuid'] ) && self::owner_policy();
	}

	/**
	 * Rest errors.
	 *
	 * @param mixed $result Prior REST auth result.
	 * @return mixed Enforce even after another handler returned true.
	 */
	public static function rest_errors( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return ( self::$admitted || self::$hinted || self::$rejected || self::principal( get_current_user_id() ) ) && ! self::allowed() ? self::error() : $result;
	}

	/**
	 * Rest dispatch.
	 *
	 * @param mixed           $result Prior result.
	 * @param WP_REST_Server  $server Native server.
	 * @param WP_REST_Request $request Parsed request.
	 * @return mixed Enforce the parsed route as well as raw admission.
	 */
	public static function rest_dispatch( $result, $server, $request ) {
		if ( self::$admitted || self::$hinted || self::$rejected || self::principal( get_current_user_id() ) ) {
			if ( self::ROUTE !== $request->get_route() || ! self::allowed() ) {
				return self::error();
			}
		}
		return $result;
	}
}

add_action( 'plugins_loaded', array( 'WCJ_AI_Connection', 'early_guard' ), PHP_INT_MIN );
add_action( 'wp_authenticate_application_password_errors', array( 'WCJ_AI_Connection', 'app_errors' ), PHP_INT_MAX, 3 );
add_action( 'application_password_did_authenticate', array( 'WCJ_AI_Connection', 'app_authenticated' ), PHP_INT_MAX, 2 );
add_filter( 'wp_authenticate_user', array( 'WCJ_AI_Connection', 'password_user' ), PHP_INT_MAX );
add_filter( 'authenticate', array( 'WCJ_AI_Connection', 'final_authenticate' ), PHP_INT_MAX );
add_filter( 'determine_current_user', array( 'WCJ_AI_Connection', 'current_user' ), PHP_INT_MAX );
add_filter( 'rest_authentication_errors', array( 'WCJ_AI_Connection', 'rest_errors' ), PHP_INT_MAX );
add_filter( 'rest_pre_dispatch', array( 'WCJ_AI_Connection', 'rest_dispatch' ), PHP_INT_MAX, 3 );

add_filter( 'user_has_cap', array( 'WCJ_AI_Connection', 'capabilities' ), PHP_INT_MAX, 4 );
