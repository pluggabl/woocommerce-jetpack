<?php
/**
 * WooCommerce Jetpack Shortcodes
 *
 * The WooCommerce Jetpack Shortcodes class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Shortcodes' ) ) :

class WCJ_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {

		foreach( $this->the_shortcodes as $the_shortcode ) {
			add_shortcode( $the_shortcode, array( $this, 'wcj_shortcode' ) );
		}

		add_filter( 'wcj_shortcodes_list', array( $this, 'add_shortcodes_to_the_list' ) );
	}

	/**
	 * add_extra_atts.
	 *
	 * @version 2.5.2
	 */
	function add_extra_atts( $atts ) {
		if ( ! isset( $this->the_atts ) ) {
			$this->the_atts = array();
		}
		$final_atts = array_merge( $this->the_atts, $atts );
		return $final_atts;
	}

	/**
	 * init_atts.
	 */
	function init_atts( $atts ) {
		return $atts;
	}

	/**
	 * add_shortcodes_to_the_list.
	 */
	function add_shortcodes_to_the_list( $shortcodes_list ) {
		foreach( $this->the_shortcodes as $the_shortcode ) {
			$shortcodes_list[] = $the_shortcode;
		}
		return $shortcodes_list;
	}

	/**
	 * wcj_shortcode.
	 *
	 * @version 2.5.2
	 */
	function wcj_shortcode( $atts, $content, $shortcode ) {

		// Init
		if ( empty( $atts ) ) $atts = array();

		// Add child class specific atts
		$atts = $this->add_extra_atts( $atts );

		// Merge atts with global defaults
		$global_defaults = array(
			'before'              => '',
			'after'               => '',
			'visibility'          => '',//user_visibility
//			'login_text'          => __( 'Login', 'woocommerce-jetpack' ),
			'site_visibility'     => '',
			'location'            => '',//user_location
			'wpml_language'       => '',
			'wpml_not_language'   => '',
			'billing_country'     => '',
			'not_billing_country' => '',
			'module'              => '',
		);
		$atts = array_merge( $global_defaults, $atts );

		// Check for required atts
		if ( false === ( $atts = $this->init_atts( $atts ) ) ) return '';

		// Check for module enabled
		if ( '' != $atts['module'] && ! wcj_is_module_enabled( $atts['module'] ) ) {
			return '<p>' . sprintf( __( '%s module not enabled!', 'woocommerce-jetpack' ), $atts['module_name'] ) . '</p>';
		}

		// Check if privileges are ok
		if ( '' != $atts['visibility'] ) {
			$visibilities = str_replace( ' ', '', $atts['visibility'] );
			$visibilities = explode( ',', $visibilities );
			$is_iser_visibility_ok = false;
			foreach ( $visibilities as $visibility ) {
				if ( 'admin' === $visibility ) {
					$visibility = 'administrator';
				}
				if ( wcj_is_user_role( $visibility ) ) {
					$is_iser_visibility_ok = true;
					break;
				}
			}
			if ( ! $is_iser_visibility_ok ) {
//				return '<p><a href="' . wp_login_url( get_permalink() ) . '" title="' . $atts['login_text'] . '">' . $atts['login_text'] . '</a></p>';
				if ( ! is_user_logged_in() ) {
					ob_start();
					woocommerce_login_form();
					return ob_get_clean();
				} else {
					return '<p>' . __( 'Wrong user role!', 'woocommerce-jetpack' ) . '</p>';
				}
			}
		}

		// Check if site visibility is ok
		if ( '' != $atts['site_visibility'] ) {
			if (
				( 'single'  === $atts['site_visibility'] && ! is_single() ) ||
				( 'page'    === $atts['site_visibility'] && ! is_page() ) ||
				( 'archive' === $atts['site_visibility'] && ! is_archive() )
			) {
				return '';
			}

		}

		// Check if location is ok
		if ( '' != $atts['location'] && 'all' != $atts['location'] && $atts['location'] != $this->wcj_get_user_location() ) return '';

		// Check if language is ok
		if ( 'wcj_wpml' === $shortcode || 'wcj_wpml_translate' === $shortcode ) $atts['wpml_language']     = isset( $atts['lang'] ) ? $atts['lang'] : '';
		if ( 'wcj_wpml' === $shortcode || 'wcj_wpml_translate' === $shortcode ) $atts['wpml_not_language'] = isset( $atts['not_lang'] ) ? $atts['not_lang'] : '';
		if ( '' != $atts['wpml_language'] ) {
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) return '';
			if ( ! in_array( ICL_LANGUAGE_CODE, $this->custom_explode( $atts['wpml_language'] ) ) ) return '';
		}
		// Check if language is ok (not in...)
		if ( '' != $atts['wpml_not_language'] ) {
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				if ( in_array( ICL_LANGUAGE_CODE, $this->custom_explode( $atts['wpml_not_language'] ) ) ) return '';
			}
		}

		// Check if billing country by arg is ok
		if ( '' != $atts['billing_country'] ) {
			if ( ! isset( $_GET['billing_country'] ) ) return '';
			if ( ! in_array( $_GET['billing_country'], $this->custom_explode( $atts['billing_country'] ) ) ) return '';
		}
		// Check if billing country by arg is ok (not in...)
		if ( '' != $atts['not_billing_country'] ) {
			if ( isset( $_GET['billing_country'] ) ) {
				if ( in_array( $_GET['billing_country'], $this->custom_explode( $atts['not_billing_country'] ) ) ) return '';
			}
		}

		// Run the shortcode function
		$shortcode_function = $shortcode;
		if ( '' !== ( $result = $this->$shortcode_function( $atts, $content ) ) )
			return $atts['before'] . $result . $atts['after'];
		return '';
	}

	/**
	 * custom_explode.
	 *
	 * @since 2.2.9
	 */
	function custom_explode( $string_to_explode ) {
		$string_to_explode = str_replace( ' ', '', $string_to_explode );
		$string_to_explode = trim( $string_to_explode, ',' );
		return explode( ',', $string_to_explode );
	}

	/**
	 * wcj_get_user_location.
	 *
	 * @version 2.5.0
	 */
	function wcj_get_user_location() {
		$country = '';
		if ( isset( $_GET['country'] ) && '' != $_GET['country'] && wcj_is_user_role( 'administrator' ) ) {
			$country = $_GET['country'];
		} else {
			// Get the country by IP
			$location = WC_Geolocation::geolocate_ip();
			// Base fallback
			if ( empty( $location['country'] ) ) {
				$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
			}
			$country = ( isset( $location['country'] ) ) ? $location['country'] : '';
		}
		return $country;
	}
}

endif;
