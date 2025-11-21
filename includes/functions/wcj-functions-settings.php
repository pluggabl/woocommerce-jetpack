<?php
/**
 * Booster for WooCommerce - Functions - Settings
 *
 * Helper functions for working with Booster settings metadata.
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_get_module_settings' ) ) {
	/**
	 * Get all settings for a specific module.
	 *
	 * This is an internal helper function that retrieves the complete settings array
	 * for a given module ID. It uses the same filter mechanism that modules use to
	 * load their settings.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $module_id Module identifier (e.g., "cart", "general", "checkout_customization").
	 *
	 * @return array Array of settings for the module, or empty array if module not found.
	 */
	function wcj_get_module_settings( $module_id ) {
		static $settings_cache = array();

		if ( isset( $settings_cache[ $module_id ] ) ) {
			return $settings_cache[ $module_id ];
		}

		$filename = wcj_free_plugin_path() . '/includes/settings/wcj-settings-' . str_replace( '_', '-', $module_id ) . '.php';
		
		if ( file_exists( $filename ) ) {
			$settings = require $filename;
			if ( is_array( $settings ) ) {
				$settings_cache[ $module_id ] = $settings;
				return $settings;
			}
		}

		$settings = apply_filters( 'wcj_' . $module_id . '_settings', array() );
		$settings_cache[ $module_id ] = $settings;
		
		return $settings;
	}
}

if ( ! function_exists( 'wcj_find_setting_in_array' ) ) {
	/**
	 * Find a specific setting in a settings array by its option ID.
	 *
	 * This is an internal helper function that searches through a settings array
	 * to find a setting with a matching 'id' key.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param array  $settings  Array of settings to search through.
	 * @param string $option_id Option/setting identifier to find.
	 *
	 * @return array|null The setting array if found, null otherwise.
	 */
	function wcj_find_setting_in_array( $settings, $option_id ) {
		if ( ! is_array( $settings ) ) {
			return null;
		}

		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) && $setting['id'] === $option_id ) {
				return $setting;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'wcj_get_setting_help_text' ) ) {
	/**
	 * Get help text for a Booster setting.
	 *
	 * This function retrieves the help text metadata for a specific setting within a module.
	 * Help text is intended to provide brief, contextual assistance to users about what
	 * a setting does or how to use it.
	 *
	 * The help text is defined in the setting's definition array using the 'help_text' key.
	 * If no help text is defined, the function returns the provided default value.
	 *
	 * Usage in future UI code:
	 * ```php
	 * $help = wcj_get_setting_help_text( 'cart', 'wcj_cart_custom_info_enabled', '' );
	 * if ( ! empty( $help ) ) {
	 *     echo '<span class="help-tooltip">' . esc_html( $help ) . '</span>';
	 * }
	 * ```
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $module_id Module identifier (e.g., "cart", "general", "checkout_customization").
	 * @param string $option_id Option/setting identifier (e.g., "wcj_cart_custom_info_enabled").
	 * @param string $default   Optional default value if help text is not defined. Default empty string.
	 *
	 * @return string The help text if defined, otherwise the default value.
	 */
	function wcj_get_setting_help_text( $module_id, $option_id, $default = '' ) {
		$settings = wcj_get_module_settings( $module_id );
		
		$setting = wcj_find_setting_in_array( $settings, $option_id );
		
		if ( 
			null !== $setting && 
			isset( $setting['help_text'] ) && 
			is_string( $setting['help_text'] ) && 
			'' !== trim( $setting['help_text'] ) 
		) {
			return $setting['help_text'];
		}
		
		return $default;
	}
}

if ( ! function_exists( 'wcj_get_setting_friendly_label' ) ) {
	/**
	 * Get friendly label for a Booster setting.
	 *
	 * This function retrieves the friendly label metadata for a specific setting within a module.
	 * Friendly labels provide alternative, more user-friendly names for settings that might
	 * have technical or abbreviated titles.
	 *
	 * The friendly label is defined in the setting's definition array using the 'friendly_label' key.
	 * If no friendly label is defined, the function returns the provided default value.
	 *
	 * Usage in future UI code:
	 * ```php
	 * $label = wcj_get_setting_friendly_label( 'cart', 'wcj_cart_custom_info_enabled', '' );
	 * if ( ! empty( $label ) ) {
	 *     echo '<span class="friendly-label">' . esc_html( $label ) . '</span>';
	 * } else {
	 *     // Fall back to the standard title
	 *     echo esc_html( $setting['title'] );
	 * }
	 * ```
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $module_id Module identifier (e.g., "cart", "general", "checkout_customization").
	 * @param string $option_id Option/setting identifier (e.g., "wcj_cart_custom_info_enabled").
	 * @param string $default   Optional default value if friendly label is not defined. Default empty string.
	 *
	 * @return string The friendly label if defined, otherwise the default value.
	 */
	function wcj_get_setting_friendly_label( $module_id, $option_id, $default = '' ) {
		$settings = wcj_get_module_settings( $module_id );
		
		$setting = wcj_find_setting_in_array( $settings, $option_id );
		
		if ( 
			null !== $setting && 
			isset( $setting['friendly_label'] ) && 
			is_string( $setting['friendly_label'] ) && 
			'' !== trim( $setting['friendly_label'] ) 
		) {
			return $setting['friendly_label'];
		}
		
		return $default;
	}
}
