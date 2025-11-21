<?php
/**
 * Booster for WooCommerce - Functions - Admin UI
 *
 * Helper functions for rendering admin UI elements like help tooltips.
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_render_help_icon' ) ) {
	/**
	 * Render a help icon with tooltip.
	 *
	 * This function generates a WooCommerce-style help tip icon that displays
	 * help text in a tooltip when hovered. It uses WooCommerce's existing
	 * `woocommerce-help-tip` class for consistent styling and behavior.
	 *
	 * The function returns an empty string if no help text is provided, ensuring
	 * backward compatibility with settings that don't have help text defined.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $help_text The help text to display in the tooltip.
	 *
	 * @return string HTML markup for the help icon, or empty string if no help text.
	 */
	function wcj_render_help_icon( $help_text ) {
		if ( empty( $help_text ) || ! is_string( $help_text ) ) {
			return '';
		}

		return '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $help_text ) . '"></span>';
	}
}

if ( ! function_exists( 'wcj_get_setting_label_with_help' ) ) {
	/**
	 * Get a setting label with optional help icon.
	 *
	 * This function retrieves the label for a setting and optionally appends
	 * a help icon if help text is defined. It also supports using a friendly
	 * label instead of the default title if one is defined.
	 *
	 * The function maintains backward compatibility by returning just the
	 * label when no help text or friendly label is defined.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $module_id      Module identifier (e.g., "cart", "general").
	 * @param string $option_id      Option/setting identifier (e.g., "wcj_cart_custom_info_enabled").
	 * @param string $default_label  The default label/title for the setting.
	 *
	 * @return array Array with 'label' and 'help_icon' keys.
	 */
	function wcj_get_setting_label_with_help( $module_id, $option_id, $default_label ) {
		$friendly_label = wcj_get_setting_friendly_label( $module_id, $option_id, '' );
		$label = ! empty( $friendly_label ) ? $friendly_label : $default_label;

		$help_text = wcj_get_setting_help_text( $module_id, $option_id, '' );
		$help_icon = wcj_render_help_icon( $help_text );

		return array(
			'label'     => $label,
			'help_icon' => $help_icon,
		);
	}
}

if ( ! function_exists( 'wcj_enhance_field_description_with_help' ) ) {
	/**
	 * Enhance a field description with help text.
	 *
	 * This function can be used to add help text as an inline description
	 * below a field, as an alternative to the tooltip approach.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @param string $module_id          Module identifier.
	 * @param string $option_id          Option/setting identifier.
	 * @param string $existing_desc      Existing description text.
	 * @param bool   $prepend            Whether to prepend help text before existing description.
	 *
	 * @return string Enhanced description with help text.
	 */
	function wcj_enhance_field_description_with_help( $module_id, $option_id, $existing_desc = '', $prepend = false ) {
		$help_text = wcj_get_setting_help_text( $module_id, $option_id, '' );

		if ( empty( $help_text ) ) {
			return $existing_desc;
		}

		$help_desc = '<span class="wcj-help-description">' . esc_html( $help_text ) . '</span>';

		if ( empty( $existing_desc ) ) {
			return $help_desc;
		}

		return $prepend ? $help_desc . ' ' . $existing_desc : $existing_desc . ' ' . $help_desc;
	}
}
