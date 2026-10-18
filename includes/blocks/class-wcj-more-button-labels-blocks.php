<?php
/**
 * More Button Labels frontend Checkout block integration.
 *
 * @since   9.0.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_More_Button_Labels_Blocks' ) ) :
	/**
	 * Supply the existing label options through Woo's native integration registry.
	 */
	class WCJ_More_Button_Labels_Blocks implements \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface {

		/**
		 * Get the integration's settings namespace.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'wcj_more_button_labels';
		}

		/**
		 * Register only the frontend asset and its shipped public API dependencies.
		 *
		 * @return void
		 */
		public function initialize() {
			wp_register_script(
				'wcj-more-button-labels-blocks',
				plugins_url( 'includes/js/wcj-more-button-labels-blocks.js', WCJ_PLUGIN_FILE ),
				array( 'wc-blocks-checkout', 'wc-settings', 'wc-blocks-data-store', 'wc-blocks-registry', 'wp-data' ),
				'9.0.0',
				true
			);
		}

		/**
		 * Get the frontend script handle.
		 *
		 * @return string[]
		 */
		public function get_script_handles() {
			return array( 'wcj-more-button-labels-blocks' );
		}

		/**
		 * Leave the editor unchanged.
		 *
		 * @return string[]
		 */
		public function get_editor_script_handles() {
			return array();
		}

		/**
		 * Supply typed settings for Woo's safe settings-data serialization.
		 *
		 * @return array
		 */
		public function get_script_data() {
			$text = wcj_get_option( 'wcj_checkout_place_order_button_text', '' );
			return array(
				'text'     => is_string( $text ) ? $text : '',
				'override' => 'yes' === wcj_get_option( 'wcj_checkout_place_order_button_override', 'no' ),
			);
		}
	}
endif;
