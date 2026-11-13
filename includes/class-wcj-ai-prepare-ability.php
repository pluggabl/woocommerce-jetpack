<?php
/**
 * Finite errors at the native preparation Ability boundary.
 *
 * @package Booster_For_WooCommerce/includes
 */

defined( 'ABSPATH' ) || exit;

/** Preserve native validation/permissions; never export validation or exception text. */
final class WCJ_AI_Prepare_Ability extends WP_Ability {

	/**
	 * Fixed response for failed or uncertain proposal requests.
	 *
	 * @return WP_Error No caller-controlled content or internal diagnostics.
	 */
	public static function unavailable() {
		return new WP_Error( 'wcj_ai_prepare_unavailable', 'Proposal unavailable. Do not retry with a replacement idempotency key. Review the current state in WordPress.' );
	}

	/**
	 * The adapter checks permissions separately, before calling execute().
	 *
	 * @param mixed $input Native Ability input.
	 * @return bool Preserve strict native authorization without diagnostic text.
	 */
	public function check_permissions( $input = null ) {
		try {
			return true === parent::check_permissions( $input );
		} catch ( Throwable $exception ) {
			return false;
		}
	}

	/**
	 * Run the complete native pipeline and replace only failed results.
	 *
	 * @param mixed $input Native Ability input.
	 * @return mixed|WP_Error Native success or one finite failure.
	 */
	public function execute( $input = null ) {
		try {
			$result = parent::execute( $input );
			return is_wp_error( $result ) ? self::unavailable() : $result;
		} catch ( Throwable $exception ) {
			return self::unavailable();
		}
	}
}
