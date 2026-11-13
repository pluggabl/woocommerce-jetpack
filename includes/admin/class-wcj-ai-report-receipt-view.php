<?php
/**
 * Cookie-owner receipt review. GET only reads retained state; never collects.
 *
 * @package Booster_For_WooCommerce/admin
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-ai-report-service.php';

/** Separate capture, exact-byte review, share and cleanup forms. */
final class WCJ_AI_Report_Receipt_View {

	/**
	 * Closed POST fields shared by the early guard and the handler.
	 *
	 * @return array Fixed operations.
	 */
	public static function operations() {
		return array(
			'report_capture' => array( 'principal', 'digest', 'revision', 'confirm' ),
			'report_share'   => array( 'receipt', 'revision', 'digest', 'confirm' ),
			'report_purge'   => array( 'receipt', 'revision', 'confirm' ),
		);
	}

	/**
	 * Private same-site locator, not a bearer credential.
	 *
	 * @param string $id Optional receipt locator.
	 * @return string Local preview URL.
	 */
	private static function url( $id = '' ) {
		$args = array( 'page' => 'wcj-ai-preview' );
		if ( '' !== $id ) {
			$args['receipt'] = $id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Form uses a service-created native purpose/session/revision-bound nonce.
	 *
	 * @param string $op Fixed operation.
	 * @param string $nonce Native nonce.
	 * @param array  $fields Closed server-generated expectations.
	 */
	private static function form( $op, $nonce, array $fields ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		foreach ( array_merge(
			array(
				'action'   => 'wcj_ai_preview',
				'op'       => $op,
				'_wpnonce' => $nonce,
			),
			$fields
		) as $key => $value ) {
			echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '">';
		}
	}

	/**
	 * Show a capture intent only for this eligible native report-only connection.
	 * Journal absence/corruption is not permission to initialize or repair it.
	 *
	 * @param int $principal Explicit native account locator.
	 */
	public static function capture_form( $principal ) {
		try {
			$form = WCJ_AI_Report_Service::capture_form( $principal );
			echo '<h3>' . esc_html__( 'Capture privately before sharing', 'woocommerce-jetpack' ) . '</h3><p>' . esc_html__( 'One bounded observation of the last two completed seven-day periods. Capture saves a private, immutable receipt; it does not share it. Review closes within five minutes. Shared access expires within ten minutes of capture, or sooner when the grant expires. No automatic refresh or retry.', 'woocommerce-jetpack' ) . '</p>';
			self::form(
				'report_capture',
				$form['nonce'],
				array(
					'principal' => $form['principal'],
					'digest'    => $form['digest'],
					'revision'  => $form['revision'],
				)
			);
			echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'Capture and retain this synthetic report privately for my review, bound to this connection and session.', 'woocommerce-jetpack' ) . '</label></p><button class="button" type="submit">' . esc_html__( 'Capture for private review — do not share', 'woocommerce-jetpack' ) . '</button></form>';
		} catch ( Throwable $exception ) {
			echo '<p>' . esc_html__( 'Report capture is unavailable. A current report-only connection and separately provisioned report journal are required. Missing history must not be reset or silently recreated.', 'woocommerce-jetpack' ) . '</p>';
		}
	}

	/** Read-only bounded recovery index, including lost-result and expired entries. */
	public static function catalog() {
		echo '<h3>' . esc_html__( 'Retained private report receipts', 'woocommerce-jetpack' ) . '</h3><p>' . esc_html__( 'Use this list to reconcile an unknown capture/share outcome, or inspect and purge old receipts after signing in again. Reloading never captures or shares. An expired or changed connection cannot be rebound to old consent.', 'woocommerce-jetpack' ) . '</p>';
		try {
			$index = WCJ_AI_Report_Service::catalog();
			if ( ! $index['entries'] ) {
				echo '<p>' . esc_html__( 'No retained receipts for this owner.', 'woocommerce-jetpack' ) . '</p>';
			}
			echo '<ul>';
			foreach ( $index['entries'] as $entry ) {
				echo '<li><a href="' . esc_url( self::url( $entry['id'] ) ) . '"><code>' . esc_html( $entry['id'] ) . '</code></a> — ' . esc_html( $entry['state'] ) . ' — ' . esc_html__( 'Connection account:', 'woocommerce-jetpack' ) . ' ' . esc_html( $entry['principal'] ) . '</li>';
			}
			echo '</ul>';
		} catch ( Throwable $exception ) {
			echo '<p>' . esc_html__( 'Retained receipt history is unavailable. No empty-history, successful operation or repair is assumed.', 'woocommerce-jetpack' ) . '</p>';
		}
	}

	/**
	 * Render stored bytes without recollection, formatting or appending metadata.
	 *
	 * @param string $id Exact lookup, independently cookie-owner authorized.
	 */
	public static function receipt( $id ) {
		try {
			$view = WCJ_AI_Report_Service::inspect( $id );
			echo '<h3>' . esc_html__( 'Review exact saved report', 'woocommerce-jetpack' ) . '</h3><p><code>' . esc_html( $view['id'] ) . '</code> — ' . esc_html( $view['state'] ) . '</p>';
			echo '<p>' . esc_html__( 'Recipient account ID:', 'woocommerce-jetpack' ) . ' ' . esc_html( $view['principal'] ) . '<br>' . esc_html__( 'Grant generation:', 'woocommerce-jetpack' ) . ' <code>' . esc_html( $view['generation'] ) . '</code><br>' . esc_html__( 'Review deadline (UTC):', 'woocommerce-jetpack' ) . ' ' . esc_html( gmdate( 'Y-m-d H:i:s', $view['review_deadline'] ) ) . '<br>' . esc_html__( 'Access expiry (UTC):', 'woocommerce-jetpack' ) . ' ' . esc_html( gmdate( 'Y-m-d H:i:s', $view['expires'] ) ) . '</p>';
			echo '<p>' . esc_html__( 'This is a bounded, non-atomic subset, not complete revenue. Currency groups remain separate. Recorded refunds are not return requests or paid-cohort refund allocation. Product breakdown, returns and net revenue are unavailable. Missing data is not zero. Sharing exposes these exact bytes to the bound connection, not live store queries.', 'woocommerce-jetpack' ) . '</p>';
			if ( null === $view['json'] ) {
				echo '<p><strong>' . esc_html__( 'Collection is incomplete or its outcome is unknown. No payload is available. Do not recapture automatically; inspect retained state or deliberately purge it.', 'woocommerce-jetpack' ) . '</strong></p>';
			} else {
				echo '<p>' . esc_html__( 'UTF-8 bytes:', 'woocommerce-jetpack' ) . ' ' . esc_html( $view['payload_bytes'] ) . '<br>SHA-256: <code>' . esc_html( $view['payload_sha256'] ) . '</code></p><pre style="white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html( $view['json'] ) . '</pre>';
			}
			if ( $view['can_share'] && 'pending_review' === $view['state'] ) {
				self::form(
					'report_share',
					$view['share_nonce'],
					array(
						'receipt'  => $view['id'],
						'revision' => $view['revision'],
						'digest'   => $view['payload_sha256'],
					)
				);
				echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'I reviewed these exact bytes and deliberately authorize this recipient to read them until the displayed expiry.', 'woocommerce-jetpack' ) . '</label></p><button class="button" type="submit">' . esc_html__( 'Share this exact receipt', 'woocommerce-jetpack' ) . '</button></form>';
			} elseif ( 'shared' === $view['state'] ) {
				echo '<p>' . esc_html__( 'Sharing was recorded. This does not prove current access or client delivery. Give the client this receipt ID; it can request only this stored receipt while the original grant remains eligible. Expiry, disconnect or purge denies future reads but cannot retract bytes already received.', 'woocommerce-jetpack' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'This receipt cannot currently be shared. No consent, session or connection is renewed.', 'woocommerce-jetpack' ) . '</p>';
			}
			self::form(
				'report_purge',
				$view['purge_nonce'],
				array(
					'receipt'  => $view['id'],
					'revision' => $view['revision'],
				)
			);
			echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html__( 'Permanently remove this retained receipt and deny future reads. This cannot retract prior client copies.', 'woocommerce-jetpack' ) . '</label></p><button class="button" type="submit">' . esc_html__( 'Purge this receipt', 'woocommerce-jetpack' ) . '</button></form>';
		} catch ( Throwable $exception ) {
			echo '<p>' . esc_html__( 'This receipt is unavailable to the current owner. Review retained history; no successful operation is assumed.', 'woocommerce-jetpack' ) . '</p>';
		}
	}

	/**
	 * Execute one closed deliberate POST; never infer success after an exception.
	 *
	 * @param string $op Fixed operation.
	 * @param array  $input Strictly closed unslashed form input.
	 * @throws RuntimeException On malformed expectations or unavailable operation.
	 */
	public static function handle( $op, array $input ) {
		$keys = self::operations();
		if ( ! isset( $keys[ $op ] ) || array_diff( array_keys( $input ), array_merge( array( 'action', 'op', '_wpnonce' ), $keys[ $op ] ) ) ||
			! is_string( $input['revision'] ?? null ) || 1 !== preg_match( '/^(?:0|[1-9][0-9]{0,9})$/D', $input['revision'] ) || (int) $input['revision'] > 2147483647 ||
			! is_string( $input['_wpnonce'] ?? null ) || '1' !== ( $input['confirm'] ?? null ) ) {
			throw new RuntimeException( 'ai_report_unavailable' );
		}
		$revision = (int) $input['revision'];
		$nonce    = $input['_wpnonce'];
		if ( 'report_purge' !== $op && ( ! is_string( $input['digest'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $input['digest'] ) ) ) {
			throw new RuntimeException( 'ai_report_unavailable' );
		}
		if ( 'report_capture' === $op ) {
			if ( ! is_string( $input['principal'] ?? null ) || 1 !== preg_match( '/^[1-9][0-9]{0,9}$/D', $input['principal'] ) || (int) $input['principal'] > 2147483647 ) {
				throw new RuntimeException( 'ai_report_unavailable' );
			}
			$id = WCJ_AI_Report_Service::capture( (int) $input['principal'], $input['digest'], $revision, $nonce, true );
		} else {
			$id = $input['receipt'] ?? null;
			if ( ! is_string( $id ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $id ) ) {
				throw new RuntimeException( 'ai_report_unavailable' );
			}
			if ( 'report_share' === $op ) {
				WCJ_AI_Report_Service::share( $id, $revision, $input['digest'], $nonce, true );
			} else {
				WCJ_AI_Report_Service::purge( $id, $revision, $nonce, true );
				$id = '';
			}
		}
		wp_safe_redirect( self::url( $id ), 303 );
		exit;
	}
}
