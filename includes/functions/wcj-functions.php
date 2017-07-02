<?php
/**
 * Booster for WooCommerce - Functions
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_is_module_deprecated' ) ) {
	/**
	 * wcj_is_module_deprecated.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @return  array|false
	 */
	function wcj_is_module_deprecated( $module_id, $by_module_option = false, $check_for_disabled = false ) {
		if ( $check_for_disabled ) {
			$module_option = ( $by_module_option ? $module_id : 'wcj_' . $module_id . '_enabled' );
			if ( 'yes' === get_option( $module_option, 'no' ) ) {
				return false;
			}
		}
		if ( $by_module_option ) {
			$module_id = str_replace( array( 'wcj_', '_enabled' ), '', $module_id );
		}
		$deprecated_and_replacement_modules = array(
			'product_info' => array(
				'cat'    => 'products',
				'module' => 'product_custom_info',
				'title'  => __( 'Product Info', 'woocommerce-jetpack' ),
			),
		);
		if ( ! array_key_exists( $module_id, $deprecated_and_replacement_modules ) ) {
			return false;
		} else {
			return ( isset( $deprecated_and_replacement_modules[ $module_id ] ) ? $deprecated_and_replacement_modules[ $module_id ] : array() );
		}
	}
}

if ( ! function_exists( 'wcj_customer_get_country' ) ) {
	/**
	 * wcj_customer_get_country.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_customer_get_country() {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? WC()->customer->get_country() : WC()->customer->get_billing_country() );
	}
}

if ( ! function_exists( 'wcj_check_time_from' ) ) {
	/**
	 * wcj_check_time_from.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time_from( $time_from, $args ) {
		$time_from = explode( ':', $time_from );
		if ( isset( $time_from[0] ) && $args['hours_now'] < $time_from[0] ) {
			return false;
		}
		if ( isset( $time_from[1] ) && $time_from[0] == $args['hours_now'] && $args['minutes_now'] < $time_from[1] ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_time_to' ) ) {
	/**
	 * wcj_check_time_to.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time_to( $time_to, $args ) {
		$time_to = explode( ':', $time_to );
		if ( isset( $time_to[0] ) && $args['hours_now'] > $time_to[0] ) {
			return false;
		}
		if ( isset( $time_to[1] ) && $time_to[0] == $args['hours_now'] && $args['minutes_now'] > $time_to[1] ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_single_time' ) ) {
	/**
	 * wcj_check_single_time.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_single_time( $_time, $args ) {
		$_time = explode( '-', $_time );
		if ( isset( $_time[0] ) ) {
			if ( ! wcj_check_time_from( $_time[0], $args ) ) {
				return false;
			}
		}
		if ( isset( $_time[1] ) ) {
			if ( ! wcj_check_time_to( $_time[1], $args ) ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wcj_check_time' ) ) {
	/**
	 * wcj_check_time.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_check_time( $_time, $args = array() ) {
		if ( empty( $args ) ) {
			$time_now = current_time( 'timestamp' );
			$args['hours_now']   = intval( date( 'H', $time_now ) );
			$args['minutes_now'] = intval( date( 'i', $time_now ) );
		}
		$_time = explode( ',', $_time );
		foreach ( $_time as $_single_time ) {
			if ( wcj_check_single_time( $_single_time, $args ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_is_bot' ) ) {
	/**
	 * wcj_is_bot.
	 *
	 * @version 2.5.7
	 * @since   2.5.6
	 */
	function wcj_is_bot() {
		return ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/Google-Structured-Data-Testing-Tool|bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ) ? true : false;
	}
}

if ( ! function_exists( 'wcj_add_files_upload_form_to_checkout_frontend' ) ) {
	/**
	 * wcj_add_files_upload_form_to_checkout_frontend.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function wcj_add_files_upload_form_to_checkout_frontend() {
		WCJ()->modules['checkout_files_upload']->add_files_upload_form_to_checkout_frontend_all( true );
	}
}

if ( ! function_exists( 'wcj_variation_radio_button' ) ) {
	/**
	 * wcj_variation_radio_button.
	 *
	 * @version 2.5.0
	 * @since   2.4.8
	 */
	function wcj_variation_radio_button( $_product, $variation ) {
		$attributes_html = '';
		$variation_attributes_display_values = array();
		$is_checked = true;
		foreach ( $variation['attributes'] as $attribute_full_name => $attribute_value ) {

			$attributes_html .= ' ' . $attribute_full_name . '="' . $attribute_value . '"';

			$attribute_name = $attribute_full_name;
			$prefix = 'attribute_';
			if ( substr( $attribute_full_name, 0, strlen( $prefix ) ) === $prefix ) {
				$attribute_name = substr( $attribute_full_name, strlen( $prefix ) );
			}

			$checked = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) : $_product->get_variation_default_attribute( $attribute_name );
			if ( $checked != $attribute_value ) $is_checked = false;

			$terms = get_terms( $attribute_name );
			foreach ( $terms as $term ) {
				if ( is_object( $term ) && isset( $term->slug ) && $term->slug === $attribute_value && isset( $term->name ) ) {
					$attribute_value = $term->name;
				}
			}

			$variation_attributes_display_values[] = $attribute_value;

		}
		$variation_title = implode( ', ', $variation_attributes_display_values ) . ' (' . wc_price( $variation['display_price'] ) . ')';
		$variation_id    = $variation['variation_id'];
		$is_checked = checked( $is_checked, true, false );

		echo '<td style="width:10%;">';
		echo '<input id="wcj_variation_' . $variation_id . '" name="wcj_variations" type="radio"' . $attributes_html . ' variation_id="' . $variation_id . '"' . $is_checked . '>';
		echo '</td>';
		echo '<td>';
		echo '<label for="wcj_variation_' . $variation_id . '">';
		echo $variation_title;
		if ( '' != ( $variation_description = get_post_meta( $variation_id, '_variation_description', true ) ) ) {
			echo '<br>';
//			echo '<small>' . $variation['variation_description'] . '</small>';
			echo '<small>' . $variation_description . '</small>';
		}
		echo '</label>';
		echo '</td>';
	}
}

if ( ! function_exists( 'wcj_current_filter_priority' ) ) {
	/*
	 * wcj_current_filter_priority.
	 *
	 * @version 2.5.8
	 * @since   2.4.6
	 */
	function wcj_current_filter_priority() {
		global $wp_filter;
		$current_filter_data = $wp_filter[ current_filter() ];
		if ( class_exists( 'WP_Hook' ) && is_a( $current_filter_data, 'WP_Hook' ) ) {
			// since WordPress v4.7
			return $current_filter_data->current_priority();
		} else {
			// before WordPress v4.7
			return key( $current_filter_data );
		}
	}
}

if ( ! function_exists( 'wcj_maybe_unserialize_and_implode' ) ) {
	/*
	 * wcj_maybe_unserialize_and_implode.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @return  string
	 * @todo    `if ( ! is_array() )`
	 */
	function wcj_maybe_unserialize_and_implode( $value, $glue = ' ' ) {
		if ( is_serialized( $value ) ) {
			$value = unserialize( $value );
			if ( is_array( $value ) ) {
				$value = implode( $glue, $value );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'wcj_get_left_to_free_shipping' ) ) {
	/*
	 * wcj_get_left_to_free_shipping.
	 *
	 * @version 2.5.8
	 * @since   2.4.4
	 * @return  string
	 */
	function wcj_get_left_to_free_shipping( $content, $multiply_by = 1 ) {
		if ( '' == $content ) {
			$content = __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' );
		}
		$min_free_shipping_amount = 0;
		$current_wc_version = get_option( 'woocommerce_version', null );
		if ( version_compare( $current_wc_version, '2.6.0', '<' ) ) {
			$free_shipping = new WC_Shipping_Free_Shipping();
			if ( in_array( $free_shipping->requires, array( 'min_amount', 'either', 'both' ) ) ) {
				$min_free_shipping_amount = $free_shipping->min_amount;
			}
		} else {
			$legacy_free_shipping = new WC_Shipping_Legacy_Free_Shipping();
			if ( 'yes' === $legacy_free_shipping->enabled ) {
				if ( in_array( $legacy_free_shipping->requires, array( 'min_amount', 'either', 'both' ) ) ) {
					$min_free_shipping_amount = $legacy_free_shipping->min_amount;
				}
			}
			if ( 0 == $min_free_shipping_amount ) {
				if ( function_exists( 'WC' ) && ( $wc_shipping = WC()->shipping ) && ( $wc_cart = WC()->cart ) ) {
					if ( $wc_shipping->enabled ) {
						if ( $packages = $wc_cart->get_shipping_packages() ) {
							$shipping_methods = $wc_shipping->load_shipping_methods( $packages[0] );
							foreach ( $shipping_methods as $shipping_method ) {
								if ( 'yes' === $shipping_method->enabled && 0 != $shipping_method->instance_id ) {
									if ( 'WC_Shipping_Free_Shipping' === get_class( $shipping_method ) ) {
										if ( in_array( $shipping_method->requires, array( 'min_amount', 'either', 'both' ) ) ) {
											$min_free_shipping_amount = $shipping_method->min_amount;
											break;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if ( 0 != $min_free_shipping_amount ) {
			if ( isset( WC()->cart->cart_contents_total ) ) {
				$total = ( WC()->cart->prices_include_tax ) ? WC()->cart->cart_contents_total + array_sum( WC()->cart->taxes ) : WC()->cart->cart_contents_total;
				if ( $total >= $min_free_shipping_amount ) {
					return do_shortcode( get_option( 'wcj_shipping_left_to_free_info_content_reached', __( 'You have Free delivery', 'woocommerce-jetpack' ) ) );
				} else {
					$content = str_replace( '%left_to_free%',             wc_price( ( $min_free_shipping_amount - $total ) * $multiply_by ), $content );
					$content = str_replace( '%free_shipping_min_amount%', wc_price( ( $min_free_shipping_amount )          * $multiply_by ), $content );
					return $content;
				}
			}
		}
	}
}

if ( ! function_exists( 'wcj_get_cart_filters' ) ) {
	/*
	 * wcj_get_cart_filters()
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 * @return  array
	 */
	function wcj_get_cart_filters() {
		return array(
			'woocommerce_before_cart'                    => __( 'Before cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_table'              => __( 'Before cart table', 'woocommerce-jetpack' ),
			'woocommerce_before_cart_contents'           => __( 'Before cart contents', 'woocommerce-jetpack' ),
			'woocommerce_cart_contents'                  => __( 'Cart contents', 'woocommerce-jetpack' ),
			'woocommerce_cart_coupon'                    => __( 'Cart coupon', 'woocommerce-jetpack' ),
			'woocommerce_cart_actions'                   => __( 'Cart actions', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_contents'            => __( 'After cart contents', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_table'               => __( 'After cart table', 'woocommerce-jetpack' ),
			'woocommerce_cart_collaterals'               => __( 'Cart collaterals', 'woocommerce-jetpack' ),
			'woocommerce_after_cart'                     => __( 'After cart', 'woocommerce-jetpack' ),

			'woocommerce_before_cart_totals'             => __( 'Before cart totals', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'woocommerce-jetpack' ),
			'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'woocommerce-jetpack' ),
			'woocommerce_proceed_to_checkout'            => __( 'Proceed to checkout', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_totals'              => __( 'After cart totals', 'woocommerce-jetpack' ),

			'woocommerce_before_shipping_calculator'     => __( 'Before shipping calculator', 'woocommerce-jetpack' ),
			'woocommerce_after_shipping_calculator'      => __( 'After shipping calculator', 'woocommerce-jetpack' ),

			'woocommerce_cart_is_empty'                  => __( 'If cart is empty', 'woocommerce-jetpack' ),
		);
	}
}

if ( ! function_exists( 'wcj_get_rates_for_tax_class' ) ) {
	/* Used by admin settings page.
	 *
	 * @param string $tax_class
	 *
	 * @return array|null|object
	 *
	 * @version 2.3.10
	 * @since   2.3.10
	 */
	function wcj_get_rates_for_tax_class( $tax_class ) {
		global $wpdb;

		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$rates     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rates` WHERE `tax_rate_class` = %s ORDER BY `tax_rate_order`;", sanitize_title( $tax_class ) ) );
		$locations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rate_locations`" );

		// Set the rates keys equal to their ids.
		$rates = array_combine( wp_list_pluck( $rates, 'tax_rate_id' ), $rates );

		// Drop the locations into the rates array.
		foreach ( $locations as $location ) {
			// Don't set them for unexistent rates.
			if ( ! isset( $rates[ $location->tax_rate_id ] ) ) {
				continue;
			}
			// If the rate exists, initialize the array before appending to it.
			if ( ! isset( $rates[ $location->tax_rate_id ]->{$location->location_type} ) ) {
				$rates[ $location->tax_rate_id ]->{$location->location_type} = array();
			}
			$rates[ $location->tax_rate_id ]->{$location->location_type}[] = $location->location_code;
		}

		return $rates;
	}
}

if ( ! function_exists( 'wcj_get_select_options' ) ) {
	/*
	 * wcj_get_select_options()
	 *
	 * @version  2.5.5
	 * @since    2.3.0
	 * @return   array
	 */
	function wcj_get_select_options( $select_options_raw, $do_sanitize = true ) {
		$select_options_raw = explode( PHP_EOL, $select_options_raw );
		$select_options = array();
		foreach ( $select_options_raw as $select_options_title ) {
			$select_options_key = ( $do_sanitize ) ? sanitize_title( $select_options_title ) : $select_options_title;
			$select_options[ $select_options_key ] = $select_options_title;
		}
		return $select_options;
	}
}

if ( ! function_exists( 'wcj_is_frontend' ) ) {
	/*
	 * is_frontend()
	 *
	 * @since  2.2.6
	 * @return boolean
	 */
	function wcj_is_frontend() {
		return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ? true : false;
	}
}

if ( ! function_exists( 'wcj_get_wcj_uploads_dir' ) ) {
	/**
	 * wcj_get_wcj_uploads_dir.
	 *
	 * @version 2.9.0
	 * @todo    no need to `mkdir` after `wcj_get_wcj_uploads_dir`
	 */
	function wcj_get_wcj_uploads_dir( $subdir = '' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		$upload_dir = $upload_dir . '/woocommerce_uploads';
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$upload_dir = $upload_dir . '/wcj_uploads';
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		if ( '' != $subdir ) {
			$upload_dir = $upload_dir . '/' . $subdir;
			if ( ! file_exists( $upload_dir ) ) {
				mkdir( $upload_dir, 0755, true );
			}
		}
		return $upload_dir;
	}
}

if ( ! function_exists( 'wcj_date_format_php_to_js_v2' ) ) {
	/*
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 * http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
	 *
	 * @author  Tristan Jahier
	 * @version 2.4.0
	 * @since   2.4.0
	 */
	function wcj_date_format_php_to_js_v2( $php_format ) {
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for ( $i = 0; $i < strlen( $php_format ); $i++ ) {
			$char = $php_format[ $i ];
			if ( $char === '\\' ) { // PHP date format escaping character
				$i++;
				$jqueryui_format .= ( $escaping ) ? $php_format[ $i ] : '\'' . $php_format[ $i ];
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping = false;
				}
				$jqueryui_format .= ( isset( $SYMBOLS_MATCHING[ $char ] ) ) ? $SYMBOLS_MATCHING[ $char ] : $char;
			}
		}
		return $jqueryui_format;
	}
}

if ( ! function_exists( 'wcj_hex2rgb' ) ) {
	/**
	 * wcj_hex2rgb.
	 */
	function wcj_hex2rgb( $hex ) {
		return sscanf( $hex, "#%2x%2x%2x" );
	}
}

if ( ! function_exists( 'wcj_get_the_ip' ) ) {
	/**
	 * wcj_get_the_ip.
	 *
	 * @see http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
	 */
	function wcj_get_the_ip( ) {
		$ip = null;
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}

if ( ! function_exists( 'wcj_get_shortcodes_list' ) ) {
	/**
	 * wcj_get_shortcodes_list.
	 */
	function wcj_get_shortcodes_list() {
		$the_array = apply_filters( 'wcj_shortcodes_list', array() );
		return implode( ', ', $the_array )/*  . ' (' . count( $the_array ) . ')' */;
	}
}
