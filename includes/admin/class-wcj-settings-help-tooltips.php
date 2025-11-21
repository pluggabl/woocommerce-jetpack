<?php
/**
 * Booster for WooCommerce - Settings Help Tooltips Integration
 *
 * Integrates help tooltips into WooCommerce settings rendering.
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Settings_Help_Tooltips' ) ) :
	/**
	 * WCJ_Settings_Help_Tooltips.
	 *
	 * Integrates help tooltips into WooCommerce settings rendering by filtering
	 * the settings arrays before they are rendered, adding desc_tip for fields
	 * that have help_text defined.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	class WCJ_Settings_Help_Tooltips {

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function __construct() {
			// Hook into settings retrieval to enhance fields with help tooltips.
			add_filter( 'woocommerce_get_settings_jetpack', array( $this, 'enhance_settings_array' ), 999, 2 );
		}

		/**
		 * Enhance settings array with help tooltips.
		 *
		 * This method is called via filter hook when settings are retrieved.
		 * It processes the settings array and adds desc_tip for fields that
		 * have help_text defined, and uses friendly_label if defined.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array  $settings      Settings array for the module.
		 * @param string $current_section Current section/module ID.
		 *
		 * @return array Enhanced settings array.
		 */
		public function enhance_settings_array( $settings, $current_section ) {
			if ( ! is_array( $settings ) || empty( $settings ) ) {
				return $settings;
			}

			$module_id = ! empty( $current_section ) ? $current_section : '';

			if ( empty( $module_id ) ) {
				return $settings;
			}

			foreach ( $settings as $key => $setting ) {
				if ( ! is_array( $setting ) || empty( $setting['id'] ) ) {
					continue;
				}

				if ( 0 !== strpos( $setting['id'], 'wcj_' ) ) {
					continue;
				}

				$help_text = wcj_get_setting_help_text( $module_id, $setting['id'], '' );

				$friendly_label = wcj_get_setting_friendly_label( $module_id, $setting['id'], '' );

				if ( ! empty( $friendly_label ) && isset( $setting['title'] ) ) {
					$settings[ $key ]['title'] = $friendly_label;
				}

				if ( ! empty( $help_text ) ) {
					if ( ! empty( $setting['desc_tip'] ) ) {
						$settings[ $key ]['desc_tip'] = $setting['desc_tip'] . ' ' . $help_text;
					} else {
						$settings[ $key ]['desc_tip'] = $help_text;
					}
				}
			}

			return $settings;
		}
	}

endif;

return new WCJ_Settings_Help_Tooltips();
