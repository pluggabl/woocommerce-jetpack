<?php
/**
 * Booster for WooCommerce - Shortcodes
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Shortcodes' ) ) :
		/**
		 * WCJ_Shortcodes.
		 *
		 * @version 5.4.8
		 */
	class WCJ_Shortcodes {


		/**
		 * Constructor.
		 *
		 * @version 5.4.8
		 */
		public function __construct() {
			foreach ( $this->the_shortcodes as $the_shortcode ) {
				add_shortcode( $the_shortcode, array( $this, 'wcj_shortcode' ) );
			}

			add_filter( 'wcj_shortcodes_list', array( $this, 'add_shortcodes_to_the_list' ) );
			add_filter( 'wcj_shortcode_result', array( $this, 'add_result_key_param_to_shortcode_result' ), 10, 4 );
		}

		/**
		 * Add_result_key_param_to_shortcode_result.
		 *
		 * @version 4.7.1
		 * @since   4.7.1
		 * @param mixed $result Get result.
		 * @param Array $atts Get shortcode attributes.
		 * @param  Array $content get content.
		 * @param  Array $shortcode define shortcodes.
		 *
		 * @return mixed
		 */
		public function add_result_key_param_to_shortcode_result( $result, $atts, $content, $shortcode ) {
			if ( is_array( $result ) && isset( $atts['result_key'] ) && ! empty( $atts['result_key'] ) ) {
				$result = $result[ $atts['result_key'] ];
			}
			return $result;
		}

		/**
		 * Add_extra_atts.
		 *
		 * @version 2.5.2
		 * @param Array $atts Get shortcode attributes.
		 */
		public function add_extra_atts( $atts ) {
			if ( ! isset( $this->the_atts ) ) {
				$this->the_atts = array();
			}
			$final_atts = array_merge( $this->the_atts, $atts );
			return $final_atts;
		}

		/**
		 * Init_atts.
		 *
		 * @param Array $atts Get shortcode attributes.
		 */
		public function init_atts( $atts ) {
			return $atts;
		}

		/**
		 * Add_shortcodes_to_the_list.
		 *
		 * @param Array $shortcodes_list Define shortcode list.
		 */
		public function add_shortcodes_to_the_list( $shortcodes_list ) {
			foreach ( $this->the_shortcodes as $the_shortcode ) {
				$shortcodes_list[] = $the_shortcode;
			}
			return $shortcodes_list;
		}

		/**
		 * Wcj_shortcode.
		 *
		 * @version 6.0.0
		 * @todo    `time` - weekly, e.g. 8:00-19:59;8:00-19:59;8:00-19:59;8:00-19:59;8:00-9:59,12:00-17:59;-;-;
		 * @todo    (maybe) - `return $atts['on_empty'];` everywhere instead of `return '';`
		 * @todo    (maybe) - add `$atts['function']` and `$atts['function_args']` - if set, will be run on shortcode's result
		 * @param Array $atts Get shortcode attributes.
		 * @param  Array $content get content.
		 * @param  Array $shortcode define shortcodes.
		 */
		public function wcj_shortcode( $atts, $content, $shortcode ) {

			// Init.
			if ( empty( $atts ) ) {
				$atts = array();
			}

			// Add child class specific atts.
			$atts = $this->add_extra_atts( $atts );

			// Merge atts with global defaults.
			$global_defaults = array(
				'plus'                          => 1,
				'before'                        => '',
				'after'                         => '',
				'visibility'                    => '', // user_visibility.
				'wrong_user_text'               => '', // '<p>' . __( 'Wrong user role!', 'woocommerce-jetpack' ) . '</p>'.
				'wrong_user_text_not_logged_in' => '',
				'site_visibility'               => '',
				'location'                      => '', // user_location.
				'not_location'                  => '', // user_location.
				'wpml_language'                 => '',
				'wpml_not_language'             => '',
				'billing_country'               => '',
				'not_billing_country'           => '',
				'payment_method'                => '',
				'not_payment_method'            => '',
				'module'                        => '',
				'find'                          => '',
				'replace'                       => '',
				'strip_tags'                    => 'no',
				'on_empty'                      => '',
				'on_zero'                       => 0,
				'time'                          => '',
				'multiply'                      => 1,
			);
			$atts            = array_merge( $global_defaults, $atts );

			// Check for required atts.
			$atts = $this->init_atts( $atts );
			if ( false === ( $atts ) ) {
				return '';
			}

			if (
				false === filter_var( $atts['plus'], FILTER_VALIDATE_BOOLEAN )
				&& class_exists( 'WCJ_Plus' )
			) {
				return '';
			}

			// Check for module enabled.
			if ( '' !== $atts['module'] && ! wcj_is_module_enabled( $atts['module'] ) ) {
				/* translators: %s: search term */
				return '<p>' . sprintf( __( '"%s" module is not enabled!', 'woocommerce-jetpack' ), $atts['module_name'] ) . '</p>';
			}

			// Check if time is ok.
			if ( '' !== $atts['time'] && ! wcj_check_time( $atts['time'] ) ) {
				return '';
			}

			// Check if privileges are ok.
			if ( '' !== $atts['visibility'] ) {
				global $wcj_pdf_invoice_data;
				$visibilities          = str_replace( ' ', '', $atts['visibility'] );
				$visibilities          = explode( ',', $visibilities );
				$is_iser_visibility_ok = false;
				foreach ( $visibilities as $visibility ) {
					if ( 'admin' === $visibility ) {
						$visibility = 'administrator';
					}
					if ( isset( $wcj_pdf_invoice_data['user_id'] ) && 0 === $wcj_pdf_invoice_data['user_id'] ) {
						if ( 'guest' === $visibility ) {
							$is_iser_visibility_ok = true;
							break;
						}
					} else {
						$user_id = ( isset( $wcj_pdf_invoice_data['user_id'] ) ? $wcj_pdf_invoice_data['user_id'] : 0 );
						if ( wcj_is_user_role( $visibility, $user_id ) ) {
							$is_iser_visibility_ok = true;
							break;
						}
					}
				}
				if ( ! $is_iser_visibility_ok ) {
					if ( ! is_user_logged_in() ) {
						$login_form = '';
						$login_url  = '';
						if ( false !== strpos( $atts['wrong_user_text_not_logged_in'], '%login_form%' ) ) {
							ob_start();
							woocommerce_login_form();
							$login_form = ob_get_clean();
						}
						if ( false !== strpos( $atts['wrong_user_text_not_logged_in'], '%login_url%' ) ) {
							$login_url = wp_login_url( get_permalink() );
						}
						return str_replace( array( '%login_form%', '%login_url%' ), array( $login_form, $login_url ), $atts['wrong_user_text_not_logged_in'] );
					} else {
						return $atts['wrong_user_text'];
					}
				}
			}

			// Check if site visibility is ok.
			if ( '' !== $atts['site_visibility'] ) {
				if (
					( 'single' === $atts['site_visibility'] && ! is_single() ) ||
					( 'page' === $atts['site_visibility'] && ! is_page() ) ||
					( 'archive' === $atts['site_visibility'] && ! is_archive() ) ||
					( 'front_page' === $atts['site_visibility'] && ! is_front_page() )
				) {
					return '';
				}
			}

			// Check if location is ok.
			if (
				'' !== $atts['location'] &&
				'all' !== $atts['location'] &&
				( false === strpos( $atts['location'], ',' ) && $atts['location'] !== $this->wcj_get_user_location() ||
					false !== strpos( $atts['location'], ',' ) && ! in_array( $this->wcj_get_user_location(), array_map( 'trim', explode( ',', $atts['location'] ) ), true ) )
			) {
				return '';
			}
			if (
				'' !== $atts['not_location'] &&
				( false === strpos( $atts['not_location'], ',' ) && $atts['not_location'] === $this->wcj_get_user_location() ||
					false !== strpos( $atts['not_location'], ',' ) && in_array( $this->wcj_get_user_location(), array_map( 'trim', explode( ',', $atts['not_location'] ) ), true ) )
			) {
				return '';
			}

			// Check if language is ok.
			if ( 'wcj_wpml' === $shortcode || 'wcj_wpml_translate' === $shortcode ) {
				if ( isset( $atts['lang'] ) ) {
					$atts['wpml_language'] = $atts['lang'];
				}
				if ( isset( $atts['not_lang'] ) ) {
					$atts['wpml_not_language'] = $atts['not_lang'];
				}
			}
			if ( '' !== $atts['wpml_language'] ) {
				if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
					return '';
				}
				if ( ! in_array( ICL_LANGUAGE_CODE, $this->custom_explode( $atts['wpml_language'] ), true ) ) {
					return '';
				}
			}
			// Check if language is ok (not in...).
			if ( '' !== $atts['wpml_not_language'] ) {
				if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
					if ( in_array( ICL_LANGUAGE_CODE, $this->custom_explode( $atts['wpml_not_language'] ), true ) ) {
						return '';
					}
				}
			}

			// Check if billing country by arg is ok.
			// phpcs:disable WordPress.Security.NonceVerification
			if ( '' !== $atts['billing_country'] ) {
				if ( ! isset( $_GET['order_id'] ) ) {
					return '';
				}
				$order_id       = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
				$orders         = new WC_Order( $order_id );
				$billing_contry = $orders->get_billing_country();
				if ( ! isset( $billing_contry ) ) {

					return '';
				}
				if ( ! in_array( $billing_contry, $this->custom_explode( $atts['billing_country'] ), true ) ) {

					return '';
				}
			}
			// Check if billing country by arg is ok (not in...).
			if ( '' !== $atts['not_billing_country'] ) {
				if ( ! isset( $_GET['order_id'] ) ) {
					return '';
				}
				$order_id       = sanitize_text_field( wp_unslash( $_GET['order_id'] ) );
				$orders         = new WC_Order( $order_id );
				$billing_contry = $orders->get_billing_country();
				if ( isset( $billing_contry ) ) {
					if ( in_array( $billing_contry, $this->custom_explode( $atts['not_billing_country'] ), true ) ) {

						return '';
					}
				}
			}

			// Check if payment method by arg is ok.
			if ( '' !== $atts['payment_method'] ) {
				if ( ! isset( $_GET['payment_method'] ) ) {
					return '';
				}
				if ( ! in_array( $_GET['payment_method'], $this->custom_explode( $atts['payment_method'] ), true ) ) {
					return '';
				}
			}
			// Check if payment method by arg is ok (not in...).
			if ( '' !== $atts['not_payment_method'] ) {
				if ( isset( $_GET['payment_method'] ) ) {
					if ( in_array( $_GET['payment_method'], $this->custom_explode( $atts['not_payment_method'] ), true ) ) {
						return '';
					}
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification

			// Additional (child class specific) checks.
			if ( ! $this->extra_check( $atts ) ) {
				return '';
			}

			// Run the shortcode function.
			$shortcode_function = $shortcode;
			$result             = $this->$shortcode_function( $atts, $content );
			if ( '' !== ( $result ) ) {
				if ( 0 === $result && 0 !== $atts['on_zero'] ) {
					return $atts['on_zero'];
				}
				if ( '' !== $atts['find'] ) {
					if ( false !== strpos( $atts['find'], ',' ) && strlen( $atts['find'] ) > 2 ) {
						$find    = explode( ',', $atts['find'] );
						$replace = explode( ',', $atts['replace'] );
						if ( count( $find ) === count( $replace ) ) {
							$atts['find']    = $find;
							$atts['replace'] = $replace;
						}
					}
					$result = str_replace( $atts['find'], $atts['replace'], $result );
				}
				if ( 'yes' === $atts['strip_tags'] ) {
					$result = wp_strip_all_tags( $result );
				}
				if ( 1 !== $atts['multiply'] ) {
					$result = $result * $atts['multiply'];
				}
				return $atts['before'] . apply_filters( 'wcj_shortcode_result', $result, $atts, $content, $shortcode ) . $atts['after'];
			}
			return $atts['on_empty'];
		}



		/**
		 * Extra_check.
		 *
		 * @version 2.6.0
		 * @since   2.6.0
		 * @param Array $atts Get shortcode attributes.
		 */
		public function extra_check( $atts ) {
			return true;
		}

		/**
		 * Custom_explode.
		 *
		 * @since 2.2.9
		 * @param string $string_to_explode Define string to explode.
		 */
		public function custom_explode( $string_to_explode ) {
			$string_to_explode = str_replace( ' ', '', $string_to_explode );
			$string_to_explode = trim( $string_to_explode, ',' );
			return explode( ',', $string_to_explode );
		}

		/**
		 * Wcj_get_user_location.
		 *
		 * @version 5.6.2
		 * @todo    (maybe) move this to global functions
		 */
		public function wcj_get_user_location() {
			return ( isset( $_GET['country'] ) && '' !== $_GET['country'] && wcj_is_user_role( 'administrator' ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : wcj_get_country_by_ip() ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

endif;
