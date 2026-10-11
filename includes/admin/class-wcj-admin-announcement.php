<?php
/**
 * Contextual, text-only Booster announcements. No executable remote content.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Admin_Announcement' ) ) :
	/** Fixed feed and persistent, user-owned dismissal. */
	class WCJ_Admin_Announcement {
		const ENDPOINT  = 'https://booster.io/announcements/booster-admin.json';
		const CACHE_KEY = 'wcj_admin_announcement_v1';

		/**
		 * Prevent duplicate hook registration.
		 *
		 * @var bool
		 */
		private static $registered = false;

		/** Register local admin hooks once, including admin-post requests. */
		public static function init() {
			if ( self::$registered ) {
				return;
			}
			self::$registered = true;
			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'admin_post_wcj_dismiss_announcement', array( __CLASS__, 'handle_dismissal' ) );
		}

		/**
		 * Require a real Booster screen, not just a forged query parameter.
		 *
		 * @return bool Whether this is a supported Booster page.
		 */
		private static function is_booster_page() {
			$screen = get_current_screen();
			if ( ! $screen || ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-mutating screen selection, checked against the actual screen.
				return false;
			}
			$page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Actual screen identity is required below.
			if ( 'wc-settings' === $page ) {
				return 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only exact tab restriction.
			}
			if ( ! in_array( $page, array( 'wcj-dashboard', 'wcj-plugins', 'wcj-general-settings', 'wcj-getting-started', 'wcj-tools', 'wcj-license' ), true ) ) {
				return false;
			}
			return in_array( $screen->id, array( get_plugin_page_hookname( $page, 'wcj-dashboard' ), get_plugin_page_hookname( $page, '' ) ), true );
		}

		/**
		 * Validate an ID without transforming its dismissal identity.
		 *
		 * @param mixed $id Proposed ID.
		 * @return bool Valid ID.
		 */
		private static function valid_id( $id ) {
			return is_string( $id ) && 1 === preg_match( '/\A[a-z0-9][a-z0-9_-]{0,79}\z/', $id );
		}

		/**
		 * Validate all fields, including cached values, before display or storage.
		 *
		 * @param mixed $item Proposed announcement.
		 * @return array Valid announcement or empty array.
		 */
		private static function validate( $item ) {
			if ( ! is_array( $item ) || array_diff( array_keys( $item ), array( 'id', 'message', 'url', 'expires_at' ) ) || ! isset( $item['id'], $item['message'], $item['expires_at'] ) ) {
				return array();
			}
			if ( ! self::valid_id( $item['id'] ) || ! is_string( $item['message'] ) || '' === trim( $item['message'] ) || strlen( $item['message'] ) > 500 || 1 !== preg_match( '//u', $item['message'] ) || preg_match( '/[<>\x00-\x1F\x7F]/', $item['message'] ) ) {
				return array();
			}
			if ( ! is_string( $item['expires_at'] ) || ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/', $item['expires_at'] ) ) {
				return array();
			}
			$expiry = strtotime( $item['expires_at'] );
			if ( false === $expiry || gmdate( 'Y-m-d\TH:i:s\Z', $expiry ) !== $item['expires_at'] || $expiry <= time() ) {
				return array();
			}
			$url = array_key_exists( 'url', $item ) ? $item['url'] : '';
			if ( ! is_string( $url ) || strlen( $url ) > 200 ) {
				return array();
			}
			if ( '' !== $url ) {
				$parts = wp_parse_url( $url );
				if ( ! is_array( $parts ) || array_diff( array_keys( $parts ), array( 'scheme', 'host', 'path' ) ) || ! isset( $parts['scheme'], $parts['host'], $parts['path'] ) || 'https' !== $parts['scheme'] || 'booster.io' !== $parts['host'] || ! in_array( $parts['path'], array( '/', '/buy-booster/', '/free-vs-elite/' ), true ) || 'https://booster.io' . $parts['path'] !== $url ) {
					return array();
				}
			}
			return array(
				'id'         => $item['id'],
				'message'    => $item['message'],
				'url'        => $url,
				'expires_at' => $item['expires_at'],
			);
		}

		/**
		 * Cache success and failure for 24 hours. Never serve stale expired copy.
		 *
		 * @return array Valid announcement or empty array.
		 */
		private static function read() {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return self::validate( $cached );
			}
			$response = wp_safe_remote_get(
				self::ENDPOINT,
				array(
					'timeout'             => 2,
					'redirection'         => 0,
					'limit_response_size' => 4097,
					'user-agent'          => 'Booster-Announcement-Reader/1.0',
					'cookies'             => array(),
					'headers'             => array( 'Accept' => 'application/json' ),
				)
			);
			$item     = array();
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if ( is_string( $body ) && strlen( $body ) <= 4096 ) {
					$decoded = json_decode( $body, true, 4 );
					if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) && array( 'announcement' ) === array_keys( $decoded ) ) {
						$item = self::validate( $decoded['announcement'] );
					}
				}
			}
			set_transient( self::CACHE_KEY, $item, DAY_IN_SECONDS );
			return $item;
		}

		/**
		 * Shared key across editions and upgrades. Copy edits do not change it.
		 *
		 * @param string $id Valid announcement ID.
		 * @return string User meta key.
		 */
		private static function dismissal_key( $id ) {
			return 'wcj_announcement_dismissed_' . hash( 'sha256', $id );
		}

		/** Display only to WooCommerce managers on Booster-owned screens. */
		public static function render() {
			if ( ! current_user_can( 'manage_woocommerce' ) || ! self::is_booster_page() ) {
				return;
			}
			$item = self::read();
			if ( ! $item || 'yes' === get_user_meta( get_current_user_id(), self::dismissal_key( $item['id'] ), true ) ) {
				return;
			}
			?>
			<div class="notice notice-info wcj-admin-announcement">
				<p><?php echo esc_html( $item['message'] ); ?>
				<?php if ( $item['url'] ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>"><?php esc_html_e( 'Learn more', 'woocommerce-jetpack' ); ?></a>
				<?php endif; ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wcj_dismiss_announcement">
					<input type="hidden" name="announcement_id" value="<?php echo esc_attr( $item['id'] ); ?>">
					<?php wp_nonce_field( 'wcj-dismiss-announcement-' . $item['id'], '_wcj_announcement_nonce', false ); ?>
					<p><button type="submit" class="button"><?php esc_html_e( 'Dismiss announcement', 'woocommerce-jetpack' ); ?></button></p>
				</form>
			</div>
			<?php
		}

		/**
		 * Sensitive sink: capability, user-bound nonce, active ID and own user only.
		 *
		 * @param mixed $id Proposed ID.
		 * @param mixed $nonce User-bound nonce.
		 * @return bool Whether this user's dismissal is saved.
		 */
		public static function dismiss( $id, $nonce ) {
			if ( ! current_user_can( 'manage_woocommerce' ) || ! self::valid_id( $id ) || ! is_string( $nonce ) || strlen( $nonce ) > 32 || ! wp_verify_nonce( $nonce, 'wcj-dismiss-announcement-' . $id ) ) {
				return false;
			}
			$item = self::validate( get_transient( self::CACHE_KEY ) );
			if ( ! $item || $id !== $item['id'] ) {
				return false;
			}
			$key = self::dismissal_key( $id );
			update_user_meta( get_current_user_id(), $key, 'yes' );
			return 'yes' === get_user_meta( get_current_user_id(), $key, true );
		}

		/** POST adapter. No guest registration and no remote request here. */
		public static function handle_dismissal() {
			$id    = isset( $_POST['announcement_id'] ) ? wp_unslash( $_POST['announcement_id'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Raw types and nonce are validated at the sink.
			$nonce = isset( $_POST['_wcj_announcement_nonce'] ) ? wp_unslash( $_POST['_wcj_announcement_nonce'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Sink checks user-bound nonce and capability.
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! self::dismiss( $id, $nonce ) ) {
				wp_die( esc_html__( 'Unable to dismiss this announcement.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			$referer = wp_get_referer();
			wp_safe_redirect( $referer ? $referer : admin_url( 'admin.php?page=wcj-dashboard' ) );
			exit;
		}
	}
endif;
