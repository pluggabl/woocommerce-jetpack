<?php
/**
 * Internal, dedicated-principal connection boundary. No remote business writes.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-grant-profile.php';

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
	 * Bounded uncached grant read; oversized values never cross into PHP.
	 *
	 * @param int $principal Server-derived native user ID.
	 * @return array|null Exact state or unavailable, never repaired/reset.
	 */
	private static function grant_raw( $principal ) {
		global $wpdb;
		if ( ! is_int( $principal ) || $principal < 1 ) {
			return null;
		}
		$wpdb->last_error = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table, fixed prefix and checked integer, bounded authoritative read.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT CASE WHEN OCTET_LENGTH(option_value) <= 4096 THEN option_value ELSE NULL END AS option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 2", 'wcj_ai_client_grant_' . $principal ), ARRAY_A );
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) {
			return null;
		}
		if ( array() === $rows ) {
			return array(
				'exists' => false,
				'raw'    => '',
			);
		}
		if ( ! is_array( $rows[0] ) || array_keys( $rows[0] ) !== array( 'option_value' ) || ! is_string( $rows[0]['option_value'] ) || strlen( $rows[0]['option_value'] ) > WCJ_AI_Grant_Profile::MAX_BYTES ) {
			return null;
		}
		return array(
			'exists' => true,
			'raw'    => $rows[0]['option_value'],
		);
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
		if ( ! $user || $user->roles || count( $user->caps ) !== 2 || true !== ( $user->caps['read'] ?? null ) || true !== ( $user->caps['wcj_ai_client'] ?? null ) ) {
			return null;
		}
		$raw   = self::grant_raw( $id );
		$grant = $raw && $raw['exists'] ? WCJ_AI_Grant_Profile::decode( $raw['raw'] ) : null;
		$now   = time();
		if ( ! $grant || $id !== $grant['principal'] || get_current_blog_id() !== $grant['site'] || ! is_string( $uuid ) || $uuid !== $grant['app_uuid'] ||
			true !== $grant['active'] || $grant['created'] > $now || $grant['expires'] <= $now ||
			self::principal( $grant['owner'] ) || ! user_can( $grant['owner'], 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Explicit bound native owner, never user substitution.
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
		return $grant && WCJ_AI_Grant_Profile::INVOICE_PREPARE === WCJ_AI_Grant_Profile::profile( $grant ) && self::app_exists( $grant['principal'], $grant['app_uuid'] ) ? self::context( $grant ) : null;
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
		return $grant && WCJ_AI_Grant_Profile::INVOICE_PREPARE === WCJ_AI_Grant_Profile::profile( $grant ) && self::context( $grant ) === $context &&
			self::owner_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] );
	}

	/**
	 * Read only a structurally valid grant belonging to the current native owner.
	 * Revoked/expired records remain visible for explicit local management.
	 *
	 * @param int $principal Pre-provisioned dedicated principal ID.
	 * @return array Exact state and closed grant; never an external tool response.
	 * @throws RuntimeException On absent, malformed or other-owner state.
	 */
	private static function owner_record( $principal ) {
		self::authorize_owner();
		if ( ! self::enabled() || is_multisite() || ! is_int( $principal ) || $principal < 1 || self::principal( get_current_user_id() ) || ! self::principal( $principal ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		$raw   = self::grant_raw( $principal );
		$grant = $raw && $raw['exists'] ? WCJ_AI_Grant_Profile::decode( $raw['raw'] ) : null;
		if ( ! $grant || $principal !== $grant['principal'] || get_current_user_id() !== $grant['owner'] || get_current_blog_id() !== $grant['site'] ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		return array( $raw, $grant );
	}

	/**
	 * Grant management is not an invoice settings write. A native owner must
	 * still be able to disconnect after the invoice module policy changes.
	 *
	 * @throws RuntimeException On missing native owner capability.
	 */
	private static function authorize_owner() {
		if ( ! get_current_user_id() || self::principal( get_current_user_id() ) || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Native WooCommerce owner capability, no user substitution.
			throw new RuntimeException( 'connection_unavailable' );
		}
	}

	/**
	 * Minimal owner connection card. No AP password, UUID or arbitrary user data.
	 *
	 * @param int $principal Dedicated principal.
	 * @return array Native-owner-only management view.
	 */
	public static function owner_view( $principal ) {
		list( $raw, $grant ) = self::owner_record( $principal );
		return array(
			'principal'               => $principal,
			'profile'                 => WCJ_AI_Grant_Profile::profile( $grant ),
			'generation'              => $grant['id'],
			'digest'                  => hash( 'sha256', $raw['raw'] ),
			'active'                  => $grant['active'],
			'expired'                 => time() >= $grant['expires'],
			'expires'                 => $grant['expires'],
			'scopes'                  => $grant['scopes'],
			'native_password_present' => self::app_exists( $principal, $grant['app_uuid'] ),
		);
	}

	/**
	 * Deliberate owner scope rotation, bound to the exact reviewed grant bytes.
	 * Resolve the native UUID on the server, never from a hidden request field.
	 *
	 * @param int    $principal Dedicated principal.
	 * @param string $digest Expected owner-view digest.
	 * @param bool   $prepare Explicit proposal scope selection.
	 * @return bool New generation verified by issue.
	 * @throws RuntimeException On invalid digest or unavailable connection.
	 */
	public static function owner_rotate( $principal, $digest, $prepare ) {
		if ( ! is_string( $digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $digest ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		list( $raw, $grant ) = self::owner_record( $principal );
		return self::issue( $principal, $grant['app_uuid'], $prepare, $digest );
	}

	/**
	 * Explicit local-owner grant rotation for a pre-provisioned dedicated account.
	 * No route/UI is registered here. The local controller must bind its nonce.
	 *
	 * @param int         $principal Dedicated principal, never the owner account.
	 * @param string      $uuid Existing native AP UUID (not the password).
	 * @param bool        $prepare Explicit opt-in, never inferred from invoice:read.
	 * @param string|null $expected_digest Optional exact owner-view binding for UI commands.
	 * @return bool Verified new generation stored, not a credential response.
	 * @throws RuntimeException On unavailable authority or persistence.
	 */
	public static function issue( $principal, $uuid, $prepare = false, $expected_digest = null ) {
		if ( ! is_bool( $prepare ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		return self::issue_profile( $principal, $uuid, $prepare ? WCJ_AI_Grant_Profile::INVOICE_PREPARE : WCJ_AI_Grant_Profile::INVOICE_READ, $expected_digest );
	}

	/**
	 * Internal report-only issuance; no controller or remote issuance route.
	 * A future owner controller must require native cookie/session/nonce/consent.
	 *
	 * @param int         $principal Pre-provisioned report principal.
	 * @param string      $uuid Native existing AP UUID, never a browser-posted value.
	 * @param string|null $expected_digest Exact prior owner view, if rotating.
	 * @return bool Verified new report-only generation.
	 */
	public static function issue_report( $principal, $uuid, $expected_digest = null ) {
		return self::issue_profile( $principal, $uuid, WCJ_AI_Grant_Profile::REPORT_READ, $expected_digest );
	}

	/**
	 * Explicit replacement, not additive scope; no AP credential is returned.
	 *
	 * @param int    $principal Dedicated principal.
	 * @param string $digest Expected raw owner-view digest.
	 * @return bool Verified report-only generation.
	 * @throws RuntimeException On invalid digest or unavailable connection.
	 */
	public static function owner_rotate_report( $principal, $digest ) {
		if ( ! is_string( $digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $digest ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		list( $raw, $grant ) = self::owner_record( $principal );
		return self::issue_report( $principal, $grant['app_uuid'], $digest );
	}

	/**
	 * One closed-profile writer with shared gate/CAS and distinct native task policy.
	 *
	 * @param int         $principal Dedicated principal.
	 * @param string      $uuid Native AP UUID.
	 * @param string      $profile Fixed private enum from explicit public wrappers.
	 * @param string|null $expected_digest Optional exact prior view.
	 * @return bool Verified new generation.
	 * @throws RuntimeException On unavailable authority or uncertain persistence.
	 */
	private static function issue_profile( $principal, $uuid, $profile, $expected_digest ) {
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		self::authorize_owner();
		$report = WCJ_AI_Grant_Profile::REPORT_READ === $profile;
		if ( ! in_array( $profile, array( WCJ_AI_Grant_Profile::INVOICE_READ, WCJ_AI_Grant_Profile::INVOICE_PREPARE, WCJ_AI_Grant_Profile::REPORT_READ ), true ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		if ( ! $report ) {
			require_once __DIR__ . '/admin/class-wcj-invoice-job.php';
			WCJ_Invoice_Job::authorize();
		}
		if ( ! self::enabled() || is_multisite() || ! is_int( $principal ) || $principal < 1 || ( $report && $principal > 2147483647 ) ||
			! is_string( $uuid ) || 1 !== preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $uuid ) ||
			get_current_user_id() === $principal || ( $report ? ! self::report_policy() : ! self::owner_policy() ) ||
			( null !== $expected_digest && ( ! is_string( $expected_digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $expected_digest ) ) ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::authorize_owner();
			$user = get_userdata( $principal );
			if ( ( $report ? ! self::report_policy() : ! self::owner_policy() ) || ! self::principal( $principal ) || ! $user || $user->roles || count( $user->caps ) !== 2 || true !== ( $user->caps['read'] ?? null ) || true !== ( $user->caps['wcj_ai_client'] ?? null ) || ! self::app_exists( $principal, $uuid ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			if ( ! $report ) {
				WCJ_Invoice_Job::authorize();
			}
			$key      = 'wcj_ai_client_grant_' . $principal;
			$before   = self::grant_raw( $principal );
			$previous = $before && $before['exists'] ? WCJ_AI_Grant_Profile::decode( $before['raw'] ) : null;
			if ( ! $before || ( $before['exists'] && ( ! $previous || $previous['principal'] !== $principal || get_current_user_id() !== $previous['owner'] || get_current_blog_id() !== $previous['site'] ) ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			if ( null !== $expected_digest && ( ! $before['exists'] || hash( 'sha256', $before['raw'] ) !== $expected_digest ) ) {
				throw new RuntimeException( 'connection_view_changed' );
			}
			$now   = time();
			$grant = array(
				'schema'    => $report ? 2 : 1,
				'id'        => bin2hex( random_bytes( 16 ) ),
				'principal' => $principal,
				'owner'     => get_current_user_id(),
				'site'      => get_current_blog_id(),
				'app_uuid'  => $uuid,
				'scopes'    => $report ? array( 'report:read_shared' ) : ( WCJ_AI_Grant_Profile::INVOICE_PREPARE === $profile ? array( 'invoice:read', 'invoice:prepare' ) : array( 'invoice:read' ) ),
				'created'   => $now,
				'expires'   => $now + 3600,
				'active'    => true,
			);
			$after = array(
				'exists' => true,
				'raw'    => wp_json_encode( $grant ),
			);
			if ( ! WCJ_AI_Grant_Profile::decode( $after['raw'] ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			if ( ! WCJ_Job_Store::compare_exchange( $key, $before, $after, array( $key ) ) || self::grant_raw( $principal ) !== $after ) {
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
	 * @param int         $principal Dedicated principal bound to the current owner.
	 * @param string|null $expected_digest Optional exact owner-view binding.
	 * @return bool Verified inactive grant.
	 * @throws RuntimeException On busy/unknown state; never report false success.
	 */
	public static function revoke( $principal, $expected_digest = null ) {
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		self::authorize_owner();
		if ( ! is_int( $principal ) || $principal < 1 || ( null !== $expected_digest && ( ! is_string( $expected_digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $expected_digest ) ) ) ) {
			throw new RuntimeException( 'connection_unavailable' );
		}
		$gate = WCJ_AI_Mutation_Gate::acquire();
		try {
			self::authorize_owner();
			$key    = 'wcj_ai_client_grant_' . $principal;
			$before = self::grant_raw( $principal );
			$grant  = $before && $before['exists'] ? WCJ_AI_Grant_Profile::decode( $before['raw'] ) : null;
			if ( ! $grant || $principal !== $grant['principal'] || get_current_user_id() !== $grant['owner'] || get_current_blog_id() !== $grant['site'] ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			if ( null !== $expected_digest && hash( 'sha256', $before['raw'] ) !== $expected_digest ) {
				throw new RuntimeException( 'connection_view_changed' );
			}
			$grant['active'] = false;
			$after           = array(
				'exists' => true,
				'raw'    => wp_json_encode( $grant ),
			);
			WCJ_AI_Mutation_Gate::assert_held( $gate );
			// Exact already-inactive replay uses bounded readback, not Job Store's no-op read.
			if ( ( $before !== $after && ! WCJ_Job_Store::compare_exchange( $key, $before, $after, array( $key ) ) ) || self::grant_raw( $principal ) !== $after ) {
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
		if ( ! self::transport_authenticated() ) {
			return false;
		}
		$grant = self::grant( self::$authenticated['id'], self::$authenticated['uuid'] );
		return $grant && in_array( WCJ_AI_Grant_Profile::profile( $grant ), array( WCJ_AI_Grant_Profile::INVOICE_READ, WCJ_AI_Grant_Profile::INVOICE_PREPARE ), true ) && self::owner_policy();
	}

	/**
	 * Common transport identity is not permission to invoke any task callback.
	 *
	 * @return bool Native AP identity, supported active grant and profile environment.
	 */
	public static function transport_authenticated() {
		$id = get_current_user_id();
		if ( self::$rejected || ! self::$authenticated || self::$authenticated['id'] !== $id || ! function_exists( 'rest_get_authenticated_app_password' ) || rest_get_authenticated_app_password() !== self::$authenticated['uuid'] ) {
			return false;
		}
		$grant = self::grant( $id, self::$authenticated['uuid'] );
		return $grant && ( WCJ_AI_Grant_Profile::REPORT_READ === WCJ_AI_Grant_Profile::profile( $grant ) ? ( self::report_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] ) ) : self::owner_policy() );
	}

	/**
	 * Qualified report environment; native owner capability is checked separately.
	 * No invoice module/task policy is weakened or borrowed for reporting.
	 *
	 * @return bool Supported site/cache environment.
	 */
	private static function report_policy() {
		return self::enabled() && ! is_multisite() && ! wp_using_ext_object_cache();
	}

	/**
	 * Exact report recipient stored by the journal; not accepted from remote input.
	 *
	 * @param array $grant Validated report-only grant.
	 * @return array Closed immutable recipient.
	 */
	private static function report_context( array $grant ) {
		return array(
			'grant_schema'     => 2,
			'owner'            => $grant['owner'],
			'site'             => $grant['site'],
			'principal'        => $grant['principal'],
			'app_uuid'         => $grant['app_uuid'],
			'grant_generation' => $grant['id'],
			'grant_digest'     => hash( 'sha256', wp_json_encode( $grant ) ),
			'grant_expires'    => $grant['expires'],
			'kind'             => 'revenue_observation',
			'scope'            => 'report:read_shared',
		);
	}

	/**
	 * Report request precheck only, not receipt access or a gate substitute.
	 *
	 * @return bool Fresh supported report grant and native AP membership snapshot.
	 */
	public static function report_allowed() {
		if ( ! self::transport_authenticated() ) {
			return false;
		}
		$grant = self::grant( self::$authenticated['id'], self::$authenticated['uuid'] );
		return $grant && WCJ_AI_Grant_Profile::REPORT_READ === WCJ_AI_Grant_Profile::profile( $grant ) && self::report_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] );
	}

	/**
	 * Fresh native request binding under the caller's already-held gate.
	 * No receipt data is read; caller must revalidate and release before emitting it.
	 *
	 * @param array $gate Internal acquired token, never request input.
	 * @return array|null Server-derived report recipient.
	 */
	public static function report_read_context( array $gate ) {
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		if ( ! self::report_allowed() ) {
			return null;
		}
		$grant = self::grant( self::$authenticated['id'], self::$authenticated['uuid'] );
		return $grant && WCJ_AI_Grant_Profile::REPORT_READ === WCJ_AI_Grant_Profile::profile( $grant ) && self::report_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] ) ? self::report_context( $grant ) : null;
	}

	/**
	 * Validate a saved report recipient without fabricating remote authentication.
	 *
	 * @param array $context Server-retained recipient.
	 * @param array $gate Held token, shared with the enclosing operation.
	 * @return bool Fresh report grant, explicit owner, policy and AP membership.
	 */
	public static function report_context_eligible( array $context, array $gate ) {
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		if ( array_keys( $context ) !== array( 'grant_schema', 'owner', 'site', 'principal', 'app_uuid', 'grant_generation', 'grant_digest', 'grant_expires', 'kind', 'scope' ) || ! is_int( $context['principal'] ) || ! is_string( $context['app_uuid'] ) ) {
			return false;
		}
		$grant = self::stored_grant( $context['principal'], $context['app_uuid'] );
		return $grant && WCJ_AI_Grant_Profile::REPORT_READ === WCJ_AI_Grant_Profile::profile( $grant ) && self::report_context( $grant ) === $context && self::report_policy() && self::app_exists( $grant['principal'], $grant['app_uuid'] );
	}

	/**
	 * Owner-service binding. Future controller must separately bind current cookie,
	 * session/nonce/explicit consent; this internal method never impersonates a user.
	 *
	 * @param int   $principal Dedicated report principal.
	 * @param array $gate Held token after the controller's owner authorization.
	 * @return array|null Exact active report recipient owned by the native user.
	 */
	public static function owner_report_context( $principal, array $gate ) {
		require_once __DIR__ . '/admin/class-wcj-ai-mutation-gate.php';
		WCJ_AI_Mutation_Gate::assert_held( $gate );
		list( $raw, $grant ) = self::owner_record( $principal );
		if ( WCJ_AI_Grant_Profile::REPORT_READ !== WCJ_AI_Grant_Profile::profile( $grant ) ) {
			return null;
		}
		$context = self::report_context( $grant );
		return self::report_context_eligible( $context, $gate ) ? $context : null;
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
		return ( self::$admitted || self::$hinted || self::$rejected || self::principal( get_current_user_id() ) ) && ! self::transport_authenticated() ? self::error() : $result;
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
			if ( self::ROUTE !== $request->get_route() || ! self::transport_authenticated() ) {
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
