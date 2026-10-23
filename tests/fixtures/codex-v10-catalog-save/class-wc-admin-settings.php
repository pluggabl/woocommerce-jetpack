<?php
/**
 * Bounded checkbox serializer double, not the full WooCommerce serializer.
 *
 * @package Booster_For_WooCommerce/Tests
 */

/**
 * Expose unintended global-schema use and absent-checkbox persistence.
 */
class WC_Admin_Settings {

	/**
	 * Save fixture checkboxes after a verified synthetic nonce.
	 *
	 * Invalid-nonce cases also require this method is never called by production.
	 *
	 * @param array $settings Schema supplied by the real Save method.
	 * @throws LogicException If production calls the serializer without a valid nonce.
	 * @return void
	 */
	public static function save_fields( $settings ) {
		++$GLOBALS['codex_catalog_save']['field_saves'];
		$nonce = isset( $_POST['wcj-verify-save-module-settings'] ) ? sanitize_key( $_POST['wcj-verify-save-module-settings'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcj-verify-save-module-settings' ) ) {
			throw new LogicException( 'Serializer reached without the verified Save nonce.' );
		}
		foreach ( $settings as $setting ) {
			update_option( $setting['id'], isset( $_POST[ $setting['id'] ] ) ? 'yes' : 'no' );
		}
	}
}
