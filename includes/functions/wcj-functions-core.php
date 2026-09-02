<?php
/**
 * Booster for WooCommerce - Functions - Core
 *
 * @version 3.4.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_is_plugin_active_simple' ) ) {
	/**
	 * Wcj_is_plugin_active_simple.
	 *
	 * @version 3.4.0
	 * @since   2.8.0
	 * @param string $plugin defines the plugin.
	 * @return  bool
	 */
	function wcj_is_plugin_active_simple( $plugin ) {
		return (
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		);
	}
}

if ( ! function_exists( 'wcj_get_active_plugins' ) ) {
	/**
	 * Wcj_get_active_plugins.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  array
	 */
	function wcj_get_active_plugins() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		return $active_plugins;
	}
}

if ( ! function_exists( 'wcj_is_plugin_active_by_file' ) ) {
	/**
	 * Wcj_is_plugin_active_by_file.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  bool
	 * @param string $plugin_file defines the plugin_file.
	 */
	function wcj_is_plugin_active_by_file( $plugin_file ) {
		foreach ( wcj_get_active_plugins() as $active_plugin ) {
			$active_plugin = explode( '/', $active_plugin );
			if ( isset( $active_plugin[1] ) && $plugin_file === $active_plugin[1] ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_is_plugin_activated' ) ) {
	/**
	 * Wcj_is_plugin_activated.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 * @return  bool
	 * @param string $plugin_folder defines the plugin_folder.
	 * @param string $plugin_file defines the plugin_file.
	 */
	function wcj_is_plugin_activated( $plugin_folder, $plugin_file ) {
		if ( wcj_is_plugin_active_simple( $plugin_folder . '/' . $plugin_file ) ) {
			return true;
		} else {
			return wcj_is_plugin_active_by_file( $plugin_file );
		}
	}
}

if ( ! function_exists( 'wcj_get_option' ) ) {
	/**
	 * Wcj_get_option.
	 *
	 * @version 5.3.3
	 * @since   5.3.3
	 *
	 * @param string $option_name define option_name.
	 * @param null   $default Get defult null value.
	 *
	 * @return  bool
	 */
	function wcj_get_option( $option_name, $default = null ) {
		if ( ! isset( w_c_j()->options[ $option_name ] ) ) {
			w_c_j()->options[ $option_name ] = get_option( $option_name, $default );
		}
		return apply_filters( $option_name, w_c_j()->options[ $option_name ] );
	}
}

if ( ! function_exists( 'wcj_get_payment_gateway_admin_title' ) ) {
	/**
	 * Returns a safe, nonempty payment gateway label for Booster admin settings.
	 *
	 * Gateway extensions do not always populate the public title property. Prefer
	 * WooCommerce's label methods, then the public property, then a stable ID.
	 * This helper is for admin settings only and does not change checkout titles.
	 *
	 * @version 8.4.0
	 * @since   8.4.0
	 *
	 * @param object $gateway    Payment gateway object.
	 * @param string $gateway_id Gateway key supplied by WooCommerce.
	 * @return string
	 */
	function wcj_get_payment_gateway_admin_title( $gateway, $gateway_id = '' ) {
		$candidates = array();

		if ( is_object( $gateway ) ) {
			foreach ( array( 'get_method_title', 'get_title' ) as $method ) {
				if ( is_callable( array( $gateway, $method ) ) ) {
					try {
						$candidates[] = call_user_func( array( $gateway, $method ) );
					} catch ( Throwable $error ) {
						// Continue to the next stable fallback.
					}
				}
			}

			$public_properties = get_object_vars( $gateway );
			if ( array_key_exists( 'title', $public_properties ) ) {
				$candidates[] = $public_properties['title'];
			}

			if ( is_callable( array( $gateway, 'get_id' ) ) ) {
				try {
					$candidates[] = $gateway->get_id();
				} catch ( Throwable $error ) {
					// Continue to the public ID or supplied gateway key.
				}
			}
			if ( array_key_exists( 'id', $public_properties ) ) {
				$candidates[] = $public_properties['id'];
			}
		}

		$candidates[] = $gateway_id;
		foreach ( $candidates as $candidate ) {
			if ( ! is_scalar( $candidate ) ) {
				continue;
			}
			$label = sanitize_text_field( wp_strip_all_tags( (string) $candidate ) );
			if ( '' !== trim( $label ) ) {
				return trim( $label );
			}
		}

		return __( 'Payment gateway', 'woocommerce-jetpack' );
	}
}
