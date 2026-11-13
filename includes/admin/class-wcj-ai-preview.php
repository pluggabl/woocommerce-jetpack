<?php
/**
 * Opt-in local owner preview. No remote route or AI connection is registered.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-report-receipt-view.php';

/** Cookie-only local integration for the three incomplete AI feature slices. */
final class WCJ_AI_Preview {

	/** Register only behind the explicit developer preview switch. */
	public static function register() {
		if ( ! defined( 'WCJ_AI_PREVIEW' ) || true !== WCJ_AI_PREVIEW ) {
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_wcj_ai_preview', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_wcj_ai_preview', array( __CLASS__, 'deny' ) );
		add_action( 'load-woocommerce_page_wcj-ai-preview', array( __CLASS__, 'authorize' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'early_guard' ), PHP_INT_MIN );
	}

	/**
	 * Closed forms for both the pre-construction guard and the later handler.
	 *
	 * @return array Operation-specific keys, never other native form commands.
	 */
	private static function operations() {
		return array_merge(
			WCJ_AI_Report_Receipt_View::operations(),
			array(
				'connection_read'    => array( 'principal', 'digest', 'confirm' ),
				'connection_prepare' => array( 'principal', 'digest', 'confirm' ),
				'connection_report'  => array( 'principal', 'digest', 'confirm' ),
				'connection_revoke'  => array( 'principal', 'digest', 'confirm' ),
				'initialize'         => array( 'confirm' ),
				'create'             => array( 'changes' ),
				'capture'            => array( 'product' ),
				'report'             => array(),
				'cancel'             => array( 'plan' ),
				'apply'              => array( 'plan', 'confirm' ),
				'recover'            => array( 'plan', 'confirm' ),
				'preview_now'        => array( 'plan' ),
				'preview_after'      => array( 'plan' ),
			)
		);
	}

	/**
	 * Before wp_magic_quotes, PHP may include cookies in REQUEST. Permit only
	 * exact ordinary session/Booster wishlist cookies; never cookie commands.
	 *
	 * @param array $input The selected GET or POST input.
	 * @return bool Whether REQUEST contains only that input and known cookies.
	 */
	public static function request_matches( array $input ) {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Structural equality only; never render or execute these values.
		foreach ( $input as $key => $value ) {
			if ( ! array_key_exists( $key, $_REQUEST ) || $_REQUEST[ $key ] !== $value ) {
				return false;
			}
		}
		foreach ( $_REQUEST as $key => $value ) {
			if ( array_key_exists( $key, $input ) ) {
				continue;
			}
			if ( ! is_string( $key ) || 1 !== preg_match( '/^(?:wordpress_(?:logged_in_|sec_)?[a-f0-9]{32}|wordpress_test_cookie|wp-settings-(?:time-)?[0-9]+|PHPSESSID|woocommerce_items_in_cart|woocommerce_cart_hash|wp_woocommerce_session_[a-f0-9]{32}|wcj_guest_wishlist_str)$/D', $key ) ||
				! isset( $_COOKIE[ $key ] ) || $_COOKIE[ $key ] !== $value ) {
				return false;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		return true;
	}

	/** Reject native command smuggling before Booster constructors read REQUEST. */
	public static function early_guard() {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Structural guard before native constructors; cookie checked here and revision-bound nonce checked in handle.
		$page   = isset( $_GET['page'] ) && is_string( $_GET['page'] ) && 'wcj-ai-preview' === plugin_basename( $_GET['page'] );
		$action = false;
		foreach ( array( $_GET, $_POST, $_REQUEST ) as $source ) {
			$value = $source['action'] ?? null;
			if ( is_string( $value ) && ( 'wcj_ai_preview' === sanitize_text_field( $value ) || 'wcj_ai_preview' === sanitize_text_field( wp_slash( $value ) ) ) ) {
				$action = true;
			}
		}
		if ( ! $page && ! $action ) {
			return;
		}
		$method = $_SERVER['REQUEST_METHOD'] ?? '';
		$path   = explode( '?', $_SERVER['REQUEST_URI'] ?? '', 2 )[0];
		$body   = file_get_contents( 'php://input', false, null, 0, 8193 );
		if ( $_FILES || ! is_string( $body ) || strlen( $body ) > 8192 ) {
			self::deny();
		}
		if ( $page ) {
			if ( $action || 'wcj-ai-preview' !== $_GET['page'] || 'GET' !== $method || '' !== $body || $_POST || ! self::request_matches( $_GET ) ||
				wp_parse_url( admin_url( 'admin.php' ), PHP_URL_PATH ) !== $path || array_diff( array_keys( $_GET ), array( 'page', 'plan', 'connection', 'receipt' ) ) ||
				count( array_intersect( array_keys( $_GET ), array( 'plan', 'connection', 'receipt' ) ) ) > 1 ||
				( isset( $_GET['receipt'] ) && ( ! is_string( $_GET['receipt'] ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $_GET['receipt'] ) ) ) ) {
				self::deny();
			}
		} else {
			$op   = $_POST['op'] ?? null;
			$keys = self::operations();
			if ( 'POST' !== $method || 'wcj_ai_preview' !== ( $_POST['action'] ?? null ) || $_GET || ! self::request_matches( $_POST ) || wp_parse_url( admin_url( 'admin-post.php' ), PHP_URL_PATH ) !== $path ||
				! is_string( $op ) || ! isset( $keys[ $op ] ) || array_diff( array_keys( $_POST ), array_merge( array( 'action', 'op', '_wpnonce' ), $keys[ $op ] ) ) ) {
				self::deny();
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		self::authorize();
	}

	/** Fail without reflecting submitted data or exception text. */
	public static function deny() {
		self::private_headers();
		wp_die( esc_html__( 'This local preview request is unavailable. Return to the preview and review the current state before trying again.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
	}

	/** Private responses must not leak plan links through referrers. */
	public static function private_headers() {
		nocache_headers();
		header( 'Cache-Control: private, no-store, max-age=0' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Content-Type-Options: nosniff' );
	}

	/** Independently verify the local cookie, not merely the current REST user. */
	public static function authorize() {
		if ( ! defined( 'WCJ_AI_PREVIEW' ) || true !== WCJ_AI_PREVIEW || ! get_current_user_id() ||
			wp_validate_auth_cookie( '', 'logged_in' ) !== get_current_user_id() ||
			! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			self::deny();
		}
		self::private_headers();
	}

	/** Local menu; visibility is not the authorization boundary. */
	public static function menu() {
		add_submenu_page( 'woocommerce', __( 'Booster AI preview', 'woocommerce-jetpack' ), __( 'Booster AI preview', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-ai-preview', array( __CLASS__, 'page' ) );
	}

	/**
	 * Fixed local URL; never accept a redirect destination.
	 *
	 * @param string $id Optional opaque plan ID.
	 * @return string Local URL.
	 */
	private static function url( $id = '' ) {
		return admin_url( 'admin.php?page=wcj-ai-preview' ) . ( $id ? '&plan=' . rawurlencode( $id ) : '' );
	}

	/**
	 * A form nonce binds the exact immutable plan revision shown to the owner.
	 *
	 * @param string $op Fixed operation.
	 * @param array  $plan Optional authorized plan view.
	 */
	private static function form( $op, array $plan = array() ) {
		$id       = $plan['id'] ?? '';
		$revision = $plan['revision'] ?? 0;
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="wcj_ai_preview"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
		if ( $id ) {
			echo '<input type="hidden" name="plan" value="' . esc_attr( $id ) . '">';
		}
		wp_nonce_field( 'wcj_ai_preview_' . $op . '_' . $id . '_' . $revision, '_wpnonce', false );
	}

	/**
	 * Fixed four-colour labels.
	 *
	 * @return array Labels keyed by the closed contract.
	 */
	private static function labels() {
		return array(
			'header_text_color' => __( 'Header text', 'woocommerce-jetpack' ),
			'header_line_color' => __( 'Header line', 'woocommerce-jetpack' ),
			'footer_text_color' => __( 'Footer text', 'woocommerce-jetpack' ),
			'footer_line_color' => __( 'Footer line', 'woocommerce-jetpack' ),
		);
	}

	/**
	 * Local management of an operator-provisioned private synthetic client.
	 *
	 * @throws RuntimeException Internally raised and caught for an unsupported ID.
	 */
	private static function connection() {
		echo '<h2>' . esc_html__( 'Private test client access', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'Manage a pre-provisioned dedicated synthetic test account. This preview does not create accounts or ask for an API key or password. A grant is not proof that a client is online. Real-store use is not authorized by this preview.', 'woocommerce-jetpack' ) . '</p>';
		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="wcj-ai-preview"><label for="ai-connection">' . esc_html__( 'Dedicated test account ID', 'woocommerce-jetpack' ) . '</label> <input id="ai-connection" name="connection" type="text" inputmode="numeric" pattern="[1-9][0-9]{0,9}" maxlength="10" required> <button class="button">' . esc_html__( 'Review client access', 'woocommerce-jetpack' ) . '</button></form>';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only bounded ID, separately current-owner authorized in owner_view.
		$principal = isset( $_GET['connection'] ) && is_string( $_GET['connection'] ) ? wp_unslash( $_GET['connection'] ) : '';
		if ( '' === $principal ) {
			return;
		}
		try {
			if ( 1 !== preg_match( '/^[1-9][0-9]{0,9}$/D', $principal ) ) {
				throw new RuntimeException( 'connection_unavailable' );
			}
			$view = WCJ_AI_Connection::owner_view( (int) $principal );
			echo '<p>' . esc_html__( 'Grant enabled:', 'woocommerce-jetpack' ) . ' ' . esc_html( $view['active'] ? __( 'Yes', 'woocommerce-jetpack' ) : __( 'No', 'woocommerce-jetpack' ) ) . ' — ' . esc_html__( 'Expired:', 'woocommerce-jetpack' ) . ' ' . esc_html( $view['expired'] ? __( 'Yes', 'woocommerce-jetpack' ) : __( 'No', 'woocommerce-jetpack' ) ) . '</p>';
			echo '<p>' . esc_html__( 'Granted scopes:', 'woocommerce-jetpack' ) . ' ' . esc_html( implode( ', ', $view['scopes'] ) ) . ' — ' . esc_html__( 'Native application-password record present:', 'woocommerce-jetpack' ) . ' ' . esc_html( $view['native_password_present'] ? __( 'Yes', 'woocommerce-jetpack' ) : __( 'No', 'woocommerce-jetpack' ) ) . '</p>';
			echo '<p>' . esc_html__( 'Initialize the proposal journal before enabling proposals. Changing access replaces, never combines, invoice and report scopes. It creates a new grant generation and invalidates unapplied proposals and shared report access from the old generation. A report grant never authorizes capture, listing or sharing; separate owner consent is required. Disconnect disables Booster access but does not delete the native application password. Unknown or busy outcomes require reviewing current state, not blind retries.', 'woocommerce-jetpack' ) . '</p>';
			foreach ( array(
				'connection_read'    => __( 'Allow saved colours only — one hour', 'woocommerce-jetpack' ),
				'connection_prepare' => __( 'Allow saved colours and pending proposals — one hour', 'woocommerce-jetpack' ),
				'connection_report'  => __( 'Allow only owner-shared report receipts — one hour', 'woocommerce-jetpack' ),
				'connection_revoke'  => __( 'Disconnect this Booster client', 'woocommerce-jetpack' ),
			) as $op => $label ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcj_ai_preview"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="principal" value="' . esc_attr( $principal ) . '"><input type="hidden" name="digest" value="' . esc_attr( $view['digest'] ) . '">';
				wp_nonce_field( 'wcj_ai_connection_' . $op . '_' . $principal . '_' . $view['digest'], '_wpnonce', false );
				echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I deliberately authorize this access change for this synthetic test account. Proposals never authorize remote settings changes.', 'woocommerce-jetpack' ) . '</label></p><button class="button" type="submit">' . esc_html( $label ) . '</button></form>';
			}
			WCJ_AI_Report_Receipt_View::capture_form( (int) $principal );
		} catch ( Throwable $exception ) {
			echo '<p>' . esc_html__( 'Client access details are unavailable for this owner. No success or repair is assumed.', 'woocommerce-jetpack' ) . '</p>';
		}
	}

	/** Render controls only; a GET never prepares a plan or runs a report. */
	public static function page() {
		self::authorize();
		require_once __DIR__ . '/class-wcj-ai-invoice-plans.php';
		echo '<div class="wrap"><h1>' . esc_html__( 'Booster AI — local preview', 'woocommerce-jetpack' ) . '</h1>';
		echo '<p><strong>' . esc_html__( 'Private preview. Client access is optional and scoped. These are incomplete feature slices, not a released product.', 'woocommerce-jetpack' ) . '</strong></p>';
		self::connection();
		echo '<h2>' . esc_html__( '1. Review an invoice colour proposal', 'woocommerce-jetpack' ) . '</h2>';
		try {
			WCJ_Invoice_Job::authorize();
			echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="wcj-ai-preview"><label for="ai-plan-id">' . esc_html__( 'Saved proposal ID from your client', 'woocommerce-jetpack' ) . '</label> <input id="ai-plan-id" name="plan" type="text" pattern="[a-f0-9]{32}" minlength="32" maxlength="32" required> <button class="button">' . esc_html__( 'Open saved proposal', 'woocommerce-jetpack' ) . '</button></form>';
			echo '<details><summary>' . esc_html__( 'First-use proposal journal setup', 'woocommerce-jetpack' ) . '</summary><p>' . esc_html__( 'Only initialize a known fresh synthetic test store or migrate its retained local preview journal. This does not reset capacity, repair lost history, change invoice settings or connect an AI client. Do not use after deleting or restoring proposal history. Stop older preview writers before migration.', 'woocommerce-jetpack' ) . '</p>';
			self::form( 'initialize' );
			echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I confirm this synthetic store has known, retained proposal history and no older preview writer is running.', 'woocommerce-jetpack' ) . '</label></p><button class="button" type="submit">' . esc_html__( 'Initialize or preserve the journal', 'woocommerce-jetpack' ) . '</button></form></details>';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only opaque lookup, independently owner-authorized by the plan service.
			$id = isset( $_GET['plan'] ) && is_string( $_GET['plan'] ) ? wp_unslash( $_GET['plan'] ) : '';
			if ( $id ) {
				self::plan( WCJ_AI_Invoice_Plans::inspect( $id ) );
			}
			echo '<p>' . esc_html__( 'Enter one or more six-digit hex colours. Blank fields stay untouched. Preparing a proposal does not change settings.', 'woocommerce-jetpack' ) . '</p>';
			self::form( 'create' );
			foreach ( self::labels() as $key => $label ) {
				echo '<p><label for="ai-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label> <input id="ai-' . esc_attr( $key ) . '" name="changes[' . esc_attr( $key ) . ']" type="text" pattern="#[0-9a-fA-F]{6}" maxlength="7" placeholder="#334455" autocomplete="off"></p>';
			}
			echo '<button class="button button-primary" type="submit">' . esc_html__( 'Prepare for review — do not apply', 'woocommerce-jetpack' ) . '</button></form>';
		} catch ( Throwable $exception ) {
			echo '<p>' . esc_html__( 'Invoice proposal unavailable. Check native module access and the plan link. No success is assumed.', 'woocommerce-jetpack' ) . '</p>';
		}
		echo '<h2>' . esc_html__( '2. Observe native role pricing', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'Open a published product as your current merchant account. Only actual role-price callbacks during that page render are observed. No customer impersonation; no saved or shared report.', 'woocommerce-jetpack' ) . '</p>';
		self::form( 'capture' );
		echo '<label for="ai-product">' . esc_html__( 'Product ID', 'woocommerce-jetpack' ) . '</label> <input id="ai-product" name="product" type="text" inputmode="numeric" pattern="[1-9][0-9]{0,9}" maxlength="10" required> <button class="button" type="submit">' . esc_html__( 'Open observed product render', 'woocommerce-jetpack' ) . '</button></form>';
		echo '<h2>' . esc_html__( '3. Inspect recorded amounts', 'woocommerce-jetpack' ) . '</h2><p>' . esc_html__( 'Last seven completed local days and the preceding seven. A bounded, non-atomic subset only, currently limited to supported legacy order storage. Not a complete revenue report.', 'woocommerce-jetpack' ) . '</p>';
		self::form( 'report' );
		echo '<button class="button" type="submit">' . esc_html__( 'Run local comparison', 'woocommerce-jetpack' ) . '</button></form>';
		WCJ_AI_Report_Receipt_View::catalog();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Opaque read-only locator; service independently validates current cookie owner and journal.
		$receipt = isset( $_GET['receipt'] ) && is_string( $_GET['receipt'] ) ? wp_unslash( $_GET['receipt'] ) : '';
		if ( '' !== $receipt ) {
			WCJ_AI_Report_Receipt_View::receipt( $receipt );
		}
		echo '</div>';
	}

	/**
	 * Render the immutable proposal and current readback, without inferring success.
	 *
	 * @param array $plan Authorized plan view.
	 */
	private static function plan( array $plan ) {
		echo '<h3>' . esc_html__( 'Saved proposal', 'woocommerce-jetpack' ) . '</h3><p>' . esc_html__( 'State:', 'woocommerce-jetpack' ) . ' <strong>' . esc_html( $plan['status'] ) . '</strong></p>';
		if ( 'remote' === $plan['origin']['kind'] ) {
			echo '<p><strong>' . esc_html__( 'Origin: remote client request', 'woocommerce-jetpack' ) . '</strong></p><p>' . esc_html__( 'Bound connection account ID:', 'woocommerce-jetpack' ) . ' ' . esc_html( $plan['origin']['principal'] ) . '<br>' . esc_html__( 'Saved grant generation:', 'woocommerce-jetpack' ) . ' <code>' . esc_html( $plan['origin']['generation'] ) . '</code></p>';
			echo '<p>' . esc_html__( 'This saved origin does not identify the client application or prove the connection is still authorized. Review the proposed changes independently; current authority is checked again before applying.', 'woocommerce-jetpack' ) . '</p>';
		} else {
			echo '<p><strong>' . esc_html__( 'Origin: local owner preparation', 'woocommerce-jetpack' ) . '</strong></p>';
		}
		echo '<p>' . esc_html__( 'Writes are conditional per setting, not a transaction. Current values are a new readback, not proof of an atomic snapshot.', 'woocommerce-jetpack' ) . '</p>';
		if ( $plan['expired'] ) {
			echo '<p><strong>' . esc_html__( 'The preparation window has expired; this proposal cannot be applied.', 'woocommerce-jetpack' ) . '</strong></p>';
		}
		if ( $plan['outcome_uncertain'] ) {
			echo '<p><strong>' . esc_html__( 'Outcome uncertain. Inspect saved settings manually; automatic retry and recovery are disabled.', 'woocommerce-jetpack' ) . '</strong></p>';
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Colour', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Before preparation', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Proposed', 'woocommerce-jetpack' ) . '</th><th>' . esc_html__( 'Current saved value', 'woocommerce-jetpack' ) . '</th></tr></thead><tbody>';
		foreach ( $plan['fields'] as $key => $field ) {
			echo '<tr><th scope="row">' . esc_html( self::labels()[ $key ] ) . '</th><td>' . esc_html( $field['before'] ?? __( 'Not stored', 'woocommerce-jetpack' ) ) . '</td><td>' . esc_html( $field['proposed'] ) . '</td><td>' . esc_html( $field['current'] ?? __( 'Not stored or unsupported — no value inferred', 'woocommerce-jetpack' ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		foreach ( array( 'written', 'restored', 'conflicts' ) as $key ) {
			echo '<p>' . esc_html( $key ) . ': ' . esc_html( $plan[ $key ] ? implode( ', ', $plan[ $key ] ) : __( 'None recorded', 'woocommerce-jetpack' ) ) . '</p>';
		}
		if ( ! $plan['outcome_uncertain'] ) {
			foreach ( array(
				'preview_now'   => __( 'Preview current PDF', 'woocommerce-jetpack' ),
				'preview_after' => __( 'Preview proposed PDF', 'woocommerce-jetpack' ),
			) as $op => $label ) {
				self::form( $op, $plan );
				echo '<p><button class="button" type="submit">' . esc_html( $label ) . '</button></p></form>';
			}
		}
		$ops = array();
		if ( 'pending' === $plan['status'] ) {
			$ops['cancel'] = __( 'Cancel proposal — keep settings', 'woocommerce-jetpack' );
			if ( ! $plan['expired'] ) {
				$ops['apply'] = __( 'Apply this saved proposal', 'woocommerce-jetpack' );
			}
		} elseif ( 'applied' === $plan['status'] ) {
			$ops['recover'] = __( 'Restore this proposal’s known writes', 'woocommerce-jetpack' );
		}
		foreach ( $ops as $op => $label ) {
			self::form( $op, $plan );
			if ( in_array( $op, array( 'apply', 'recover' ), true ) ) {
				echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I reviewed this proposal and deliberately approve this settings change.', 'woocommerce-jetpack' ) . '</label></p>';
			}
			echo '<p><button class="button" type="submit">' . esc_html( $label ) . '</button></p></form>';
		}
	}

	/**
	 * Standalone POST response shell; no result is stored in a URL or transient.
	 *
	 * @param string $title Localized fixed heading.
	 * @param string $id Optional authorized plan ID.
	 */
	private static function document( $title, $id = '' ) {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $title ) . '</title></head><body><main><h1>' . esc_html( $title ) . '</h1><p><a href="' . esc_url( self::url( $id ) ) . '">' . esc_html__( 'Back to local preview', 'woocommerce-jetpack' ) . '</a></p>';
	}

	/** Closed POST operations, with independent cookie and revision-bound CSRF checks. */
	public static function handle() {
		self::authorize();
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Strict types/closed keys below; revision-bound nonce is verified before any operation.
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || $_GET || $_FILES || strlen( file_get_contents( 'php://input', false, null, 0, 8193 ) ) > 8192 ) {
			self::deny();
		}
		$input = wp_unslash( $_POST );
		$op    = $input['op'] ?? null;
		$keys  = self::operations();
		if ( ! is_string( $op ) || ! isset( $keys[ $op ] ) || ( $input['action'] ?? null ) !== 'wcj_ai_preview' ||
			array_diff( array_keys( $input ), array_merge( array( 'action', 'op', '_wpnonce' ), $keys[ $op ] ) ) ||
			! isset( $input['_wpnonce'] ) || ! is_string( $input['_wpnonce'] ) || strlen( $input['_wpnonce'] ) > 20 ) {
			self::deny();
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		try {
			$plan          = array();
			$connection_op = in_array( $op, array( 'connection_read', 'connection_prepare', 'connection_report', 'connection_revoke' ), true );
			if ( $connection_op ) {
				$principal = $input['principal'] ?? null;
				$digest    = $input['digest'] ?? null;
				if ( ! is_string( $principal ) || 1 !== preg_match( '/^[1-9][0-9]{0,9}$/D', $principal ) ||
					! is_string( $digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $digest ) || '1' !== ( $input['confirm'] ?? null ) ||
					! wp_verify_nonce( $input['_wpnonce'], 'wcj_ai_connection_' . $op . '_' . $principal . '_' . $digest ) ) {
					self::deny();
				}
				if ( 'connection_revoke' === $op ) {
					WCJ_AI_Connection::revoke( (int) $principal, $digest );
				} elseif ( 'connection_report' === $op ) {
					WCJ_AI_Connection::owner_rotate_report( (int) $principal, $digest );
				} else {
					WCJ_AI_Connection::owner_rotate( (int) $principal, $digest, 'connection_prepare' === $op );
				}
				wp_safe_redirect( add_query_arg( 'connection', $principal, self::url() ), 303 );
				exit;
			}
			if ( isset( WCJ_AI_Report_Receipt_View::operations()[ $op ] ) ) {
				WCJ_AI_Report_Receipt_View::handle( $op, $input );
			}
			if ( in_array( 'plan', $keys[ $op ], true ) ) {
				require_once __DIR__ . '/class-wcj-ai-invoice-plans.php';
				$plan = WCJ_AI_Invoice_Plans::inspect( $input['plan'] ?? null );
			}
			$id = $plan['id'] ?? '';
			if ( ! wp_verify_nonce( $input['_wpnonce'], 'wcj_ai_preview_' . $op . '_' . $id . '_' . ( $plan['revision'] ?? 0 ) ) ||
				( in_array( $op, array( 'initialize', 'apply', 'recover' ), true ) && ( $input['confirm'] ?? null ) !== '1' ) ) {
				self::deny();
			}
			if ( 'initialize' === $op ) {
				require_once __DIR__ . '/class-wcj-ai-invoice-plans.php';
				WCJ_AI_Invoice_Plans::initialize();
			} elseif ( 'create' === $op ) {
				require_once __DIR__ . '/class-wcj-ai-invoice-plans.php';
				$changes = $input['changes'] ?? null;
				if ( ! is_array( $changes ) || count( $changes ) > 4 || array_diff( array_keys( $changes ), WCJ_AI_Invoice_Presentation::FIELDS ) ) {
					self::deny();
				}
				$changes = array_filter(
					$changes,
					static function ( $value ) {
						return '' !== $value;
					}
				);
				$plan    = WCJ_AI_Invoice_Plans::create(
					array(
						'schema_version' => 1,
						'task'           => 'invoice_presentation',
						'changes'        => $changes,
					)
				);
				$id      = $plan['id'];
			} elseif ( in_array( $op, array( 'apply', 'recover', 'cancel' ), true ) ) {
				WCJ_AI_Invoice_Plans::$op( $id );
			} elseif ( 'capture' === $op ) {
				$product = $input['product'] ?? null;
				if ( ! is_string( $product ) || 1 !== preg_match( '/^[1-9][0-9]{0,9}$/D', $product ) || 'product' !== get_post_type( (int) $product ) || 'publish' !== get_post_status( (int) $product ) ) {
					self::deny();
				}
				wp_safe_redirect(
					add_query_arg(
						array(
							'p'              => $product,
							'post_type'      => 'product',
							'wcj_ai_capture' => '1',
							'_wpnonce'       => wp_create_nonce( 'wcj_ai_role_capture_' . $product ),
						),
						home_url( '/' )
					),
					303
				);
				exit;
			} elseif ( 'report' === $op ) {
				require_once dirname( __DIR__ ) . '/class-wcj-ai-revenue-observation.php';
				require_once __DIR__ . '/class-wcj-ai-report-view.php';
				$report = WCJ_AI_Revenue_Observation::observe();
				self::document( __( 'Recorded amounts — observed subset', 'woocommerce-jetpack' ) );
				WCJ_AI_Report_View::render( $report );
				echo '</main></body></html>';
				exit;
			} else {
				if ( $plan['outcome_uncertain'] ) {
					self::deny();
				}
				$changes = array_column( $plan['fields'], 'proposed' );
				$changes = array_combine( array_keys( $plan['fields'] ), $changes );
				$pdf     = WCJ_Invoice_Job::preview_draft( $changes, 'preview_after' === $op ? 'after' : 'now' );
				if ( ! isset( $pdf['pdf'], $pdf['label'] ) ) {
					self::deny();
				}
				self::document( __( 'Local sample PDF — no settings saved', 'woocommerce-jetpack' ), $id );
				echo '<p>' . esc_html( $pdf['label'] ) . '</p><p>' . esc_html__( 'Sample only, not an issued invoice. Uses current native settings with the selected colour overlay. Custom-body, logo and font limitations may apply. Business identity stays in this local preview.', 'woocommerce-jetpack' ) . '</p>';
				echo '<pre>' . esc_html( wp_json_encode( $pdf['limits'], JSON_PRETTY_PRINT ) ) . '</pre>';
				echo '<iframe title="' . esc_attr__( 'Sample invoice PDF', 'woocommerce-jetpack' ) . '" width="100%" height="700" src="data:application/pdf;base64,' . esc_attr( $pdf['pdf'] ) . '"></iframe></main></body></html>';
				exit;
			}
			wp_safe_redirect( self::url( $id ), 303 );
			exit;
		} catch ( Throwable $exception ) {
			self::deny();
		}
	}
}

WCJ_AI_Preview::register();
