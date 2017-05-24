<?php
/**
 * Booster for WooCommerce - Functions
 *
 * @version 2.8.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

if ( ! function_exists( 'wcj_order_get_payment_method' ) ) {
	/**
	 * wcj_order_get_payment_method.
	 *
	 * @version 2.8.2
	 * @since   2.8.0
	 */
	function wcj_order_get_payment_method( $_order ) {
		if ( ! $_order || ! is_object( $_order ) ) {
			return null;
		}
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return ( isset( $_order->payment_method ) ? $_order->payment_method : null );
		} else {
			return ( method_exists( $_order, 'get_payment_method' ) ? $_order->get_payment_method() : null );
		}
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

if ( ! function_exists( 'wcj_get_product_id' ) ) {
	/**
	 * wcj_get_product_id.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_product_id( $_product ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return ( isset( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
		} else {
			return $_product->get_id();
		}
	}
}

if ( ! function_exists( 'wcj_get_product_id_or_variation_parent_id' ) ) {
	/**
	 * wcj_get_product_id_or_variation_parent_id.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_product_id_or_variation_parent_id( $_product ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->id;
		} else {
			return ( $_product->is_type( 'variation' ) ) ? $_product->get_parent_id() : $_product->get_id();
		}
	}
}

if ( ! function_exists( 'wcj_get_product_status' ) ) {
	/**
	 * wcj_get_product_status.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_product_status( $_product ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ) ? $_product->post->post_status : $_product->get_status();
	}
}

if ( ! function_exists( 'wcj_get_product_total_stock' ) ) {
	/**
	 * wcj_get_product_total_stock.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_product_total_stock( $_product ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->get_total_stock();
		} else {
			if ( $_product->is_type( array( 'variable', 'grouped' ) ) ) {
				$total_stock = 0;
				foreach ( $_product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					$total_stock += $child->get_stock_quantity();
				}
				return $total_stock;
			} else {
				return $_product->get_stock_quantity();
			}
		}
	}
}

if ( ! function_exists( 'wcj_get_product_display_price' ) ) {
	/**
	 * wcj_get_product_display_price.
	 *
	 * @version 2.8.0
	 * @since   2.7.0
	 */
	function wcj_get_product_display_price( $_product, $price = '', $qty = 1 ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $_product->get_display_price( $price, $qty );
		} else {
			$minus_sign = '';
			if ( $price < 0 ) {
				$minus_sign = '-';
				$price *= -1;
			}
			return $minus_sign . wc_get_price_to_display( $_product, array( 'price' => $price, 'qty' => $qty ) );
		}
	}
}

if ( ! function_exists( 'wcj_get_product_formatted_variation' ) ) {
	/**
	 * wcj_get_product_formatted_variation.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_product_formatted_variation( $variation, $flat = false, $include_names = true ) {
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			return $variation->get_formatted_variation_attributes( $flat );
		} else {
			return wc_get_formatted_variation( $variation, $flat, $include_names );
		}
	}
}

if ( ! function_exists( 'wcj_get_order_id' ) ) {
	/**
	 * wcj_get_order_id.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_order_id( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ) ? $_order->id : $_order->get_id();
	}
}

if ( ! function_exists( 'wcj_get_order_currency' ) ) {
	/**
	 * wcj_get_order_currency.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_get_order_currency( $_order ) {
		return ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->get_order_currency() : $_order->get_currency() );
	}
}

if ( ! function_exists( 'wcj_get_order_item_meta_info' ) ) {
	/**
	 * wcj_get_order_item_meta_info.
	 *
	 * from woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
	 *
	 * @version 2.8.0
	 * @since   2.5.9
	 */
	function wcj_get_order_item_meta_info( $item_id, $item, $_order, $exclude_wcj_meta = false, $_product = null ) {
		$meta_info = '';
		$metadata = ( WCJ_IS_WC_VERSION_BELOW_3 ? $_order->has_meta( $item_id ) : $item->get_meta_data() );
		if ( $metadata ) {
			$meta_info = array();
			foreach ( $metadata as $meta ) {

				$_meta_key   = ( WCJ_IS_WC_VERSION_BELOW_3 ? $meta['meta_key']   : $meta->key );
				$_meta_value = ( WCJ_IS_WC_VERSION_BELOW_3 ? $meta['meta_value'] : $meta->value );

				// Skip hidden core fields
				if ( in_array( $_meta_key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'method_id',
					'cost'
				) ) ) ) {
					continue;
				}

				if ( $exclude_wcj_meta && ( 'wcj' === substr( $_meta_key, 0, 3 ) || '_wcj' === substr( $_meta_key, 0, 4 ) ) ) {
					continue;
				}

				if ( $exclude_wcj_meta && 'is_custom' === $_meta_key ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $_meta_value ) || is_array( $_meta_value ) ) {
					continue;
				}

				// Get attribute data
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $_meta_key ) ) ) {
					$term        = get_term_by( 'slug', $_meta_value, wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_value = isset( $term->name ) ? $term->name : $_meta_value;
				} else {
					$the_product = null;
					if ( is_object( $_product ) ) {
						$the_product = $_product;
					} elseif ( is_object( $item ) ) {
						$the_product = $_order->get_product_from_item( $item );
					}
					$_meta_key   = ( is_object( $the_product ) ) ? wc_attribute_label( $_meta_key, $the_product ) : $_meta_key;
				}
				$meta_info[] = wp_kses_post( rawurldecode( $_meta_key ) ) . ': ' . wp_kses_post( rawurldecode( $_meta_value ) );
			}
			$meta_info = implode( ', ', $meta_info );
		}
		return $meta_info;
	}
}

if ( ! function_exists( 'wcj_get_product_image_url' ) ) {
 	/**
	 * wcj_get_product_image_url.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @todo    placeholder
	 */
	function wcj_get_product_image_url( $product_id, $image_size = 'shop_thumbnail' ) {
		if ( has_post_thumbnail( $product_id ) ) {
			$image_url = get_the_post_thumbnail_url( $product_id, $image_size );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_url = get_the_post_thumbnail_url( $parent_id, $image_size );
		} else {
			$image_url = '';
		}
		return $image_url;
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

if ( ! function_exists( 'wcj_get_rocket_icon' ) ) {
	/**
	 * wcj_get_rocket_icon.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function wcj_get_rocket_icon() {
		return '<img class="wcj-rocket-icon" src="' . wcj_plugin_url() . '/assets/images/rocket-icon.png' . '" title="">';
	}
}

if ( ! function_exists( 'wcj_get_5_rocket_image' ) ) {
	/**
	 * wcj_get_5_rocket_image.
	 *
	 * @version 2.5.5
	 * @since   2.5.3
	 */
	function wcj_get_5_rocket_image() {
		return '<img class="wcj-rocket-icon" src="' . wcj_plugin_url() . '/assets/images/5-rockets.png' . '" title="">';
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

if ( ! function_exists( 'wcj_get_product_input_fields' ) ) {
	/*
	 * wcj_get_product_input_fields.
	 *
	 * @version 2.8.0
	 * @since   2.4.4
	 * @return  string
	 */
	function wcj_get_product_input_fields( $item ) {
		$product_input_fields = array();
		if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
			foreach ( $item as $key => $value ) {
				if ( false !== strpos( $key, 'wcj_product_input_fields_' ) ) {
					$product_input_fields[] = wcj_maybe_unserialize_and_implode( $value );
				}
			}
		} else {
			foreach ( $item->get_meta_data() as $value ) {
				if ( isset( $value->key ) && isset( $value->value ) && false !== strpos( $value->key, 'wcj_product_input_fields_' ) ) {
					$product_input_fields[] = wcj_maybe_unserialize_and_implode( $value->value );
				}
			}
		}
		return ( ! empty( $product_input_fields ) ) ? implode( ', ', $product_input_fields ) : '';
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

/*
 * wcj_get_cart_filters()
 *
 * @version 2.4.4
 * @since   2.4.4
 * @return  array
 */
if ( ! function_exists( 'wcj_get_cart_filters' ) ) {
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

/*
 * wcj_is_module_enabled()
 *
 * @version 2.4.0
 * @since   2.4.0
 * @return  boolean
 */
if ( ! function_exists( 'wcj_is_module_enabled' ) ) {
	function wcj_is_module_enabled( $module_id ) {
		return ( 'yes' === get_option( 'wcj_' . $module_id . '_enabled', 'no' ) ) ? true : false;
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

/*
 * wcj_get_select_options()
 *
 * @version  2.5.5
 * @since    2.3.0
 * @return   array
 */
if ( ! function_exists( 'wcj_get_select_options' ) ) {
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

/*
 * is_frontend()
 *
 * @since  2.2.6
 * @return boolean
 */
if ( ! function_exists( 'wcj_is_frontend' ) ) {
	function wcj_is_frontend() {
		return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ? true : false;
	}
}

/**
 * wcj_get_wcj_uploads_dir.
 */
if ( ! function_exists( 'wcj_get_wcj_uploads_dir' ) ) {
	function wcj_get_wcj_uploads_dir( $subdir = '' ) {
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		$upload_dir = $upload_dir . '/woocommerce_uploads/wcj_uploads';
		if ( '' != $subdir ) $upload_dir = $upload_dir . '/' . $subdir;
		return $upload_dir;
	}
}

/**
 * wcj_is_product_wholesale_enabled_per_product.
 *
 * @version 2.5.0
 * @since   2.5.0
 */
if ( ! function_exists( 'wcj_is_product_wholesale_enabled_per_product' ) ) {
	function wcj_is_product_wholesale_enabled_per_product( $product_id ) {
		return (
			'yes' === get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) &&
			'yes' === get_post_meta( $product_id, '_' . 'wcj_wholesale_price_per_product_enabled', true )
		) ? true : false;
	}
}

/**
 * wcj_is_product_wholesale_enabled.
 *
 * @version 2.5.4
 */
if ( ! function_exists( 'wcj_is_product_wholesale_enabled' ) ) {
	function wcj_is_product_wholesale_enabled( $product_id ) {
		if ( wcj_is_module_enabled( 'wholesale_price' ) ) {
			if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
				return true;
			} else {
				$products_to_include_passed = false;
				$products_to_include = get_option( 'wcj_wholesale_price_products_to_include', array() );
				if ( empty ( $products_to_include ) ) {
					$products_to_include_passed = true;
				} else {
					foreach ( $products_to_include as $id ) {
						if ( $product_id == $id ) {
							$products_to_include_passed = true;
						}
					}
				}
				$products_to_exclude_passed = false;
				$products_to_exclude = get_option( 'wcj_wholesale_price_products_to_exclude', array() );
				if ( empty ( $products_to_exclude ) ) {
					$products_to_exclude_passed = true;
				} else {
					foreach ( $products_to_exclude as $id ) {
						if ( $product_id == $id ) {
							$products_to_exclude_passed = false;
						}
					}
				}
				return ( $products_to_include_passed && $products_to_exclude_passed );
			}
		}
		return false;
	}
 }

/**
 * add_wcj_get_products_filter.
 */
add_action( 'init', 'add_wcj_get_products_filter' );
if ( ! function_exists( 'add_wcj_get_products_filter' ) ) {
	function add_wcj_get_products_filter() {
		add_filter( 'wcj_get_products_filter', 'wcj_get_products' );
	}
}

if ( ! function_exists( 'wcj_get_products' ) ) {
	/**
	 * wcj_get_products.
	 *
	 * @version 2.8.0
	 */
	function wcj_get_products( $products = array(), $post_status = 'any', $block_size = 256, $add_variations = false ) {
		$offset = 0;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => $post_status,
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				$products[ $post_id ] = get_the_title( $post_id );
				if ( $add_variations ) {
					$_product = wc_get_product( $post_id );
					if ( $_product->is_type( 'variable' ) ) {
						foreach ( $_product->get_children() as $child_id ) {
							$products[ $child_id ] = get_the_title( $child_id );
						}
					}
				}
			}
			$offset += $block_size;
		}
		return $products;
	}
}

if ( ! function_exists( 'wcj_product_has_terms' ) ) {
	/**
	 * wcj_product_has_terms.
	 *
	 * @version 2.8.2
	 * @version 2.8.2
	 */
	function wcj_product_has_terms( $_product, $_values, $_term ) {
		if ( is_string( $_values ) ) {
			$_values = explode( ',', $_values );
		}
		if ( empty( $_values ) ) {
			return false;
		}
		$product_categories = get_the_terms( wcj_get_product_id_or_variation_parent_id( $_product ), $_term );
		if ( empty( $product_categories ) ) {
			return false;
		}
		foreach ( $product_categories as $product_category ) {
			foreach ( $_values as $_value ) {
				if ( $product_category->slug === $_value ) {
					return true;
				}
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wcj_get_terms' ) ) {
	/**
	 * wcj_get_terms.
	 *
	 * @version 2.8.0
	 * @version 2.8.0
	 */
	function wcj_get_terms( $args ) {
		if ( ! is_array( $args ) ) {
			$_taxonomy = $args;
			$args = array(
				'taxonomy'   => $_taxonomy,
				'orderby'    => 'name',
				'hide_empty' => false,
			);
		}
		global $wp_version;
		if ( version_compare( $wp_version, '4.5.0', '>=' ) ) {
			$_terms = get_terms( $args );
		} else {
			$_taxonomy = $args['taxonomy'];
			unset( $args['taxonomy'] );
			$_terms = get_terms( $_taxonomy, $args );
		}
		$_terms_options = array();
		if ( ! empty( $_terms ) && ! is_wp_error( $_terms ) ){
			foreach ( $_terms as $_term ) {
				$_terms_options[ $_term->term_id ] = $_term->name;
			}
		}
		return $_terms_options;
	}
}

if ( ! function_exists( 'wcj_get_product' ) ) {
	/*
	 * wcj_get_product.
	 */
	function wcj_get_product( $product_id = 0 ) {
		if ( 0 == $product_id ) $product_id = get_the_ID();
		$the_product = new WCJ_Product( $product_id );
		return $the_product;
	}
}

if ( ! function_exists( 'validate_vat_no_soap' ) ) {
	/**
	 * validate_vat_no_soap.
	 *
	 * @version 2.6.0
	 * @since   2.5.7
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function validate_vat_no_soap( $country_code, $vat_number ) {
		$country_code = strtoupper( $country_code );
		$api_url = "http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms=" . $country_code . "&vat=" . $vat_number;
		if ( ini_get( 'allow_url_fopen' ) ) {
			$response = file_get_contents( $api_url );
		} elseif ( function_exists( 'curl_version' ) ) {
			$curl = curl_init( $api_url );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			$response = curl_exec( $curl );
			curl_close( $curl );
		} else {
			return null;
		}
		if ( false === $response ) {
			return null;
		}
		return ( false !== strpos( $response, '="validStyle"' ) ) ? true : false;
	}
}

if ( ! function_exists( 'validate_VAT' ) ) {
	/**
	 * validate_VAT.
	 *
	 * @version 2.5.7
	 * @return  mixed: bool on successful checking (can be true or false), null otherwise
	 */
	function validate_VAT( $country_code, $vat_number ) {
		try {
			if ( class_exists( 'SoapClient' ) ) {
				$client = new SoapClient(
					'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
					array( 'exceptions' => true )
				);
				$result = $client->checkVat( array(
					'countryCode' => $country_code,
					'vatNumber'   => $vat_number,
				) );
				return ( isset( $result->valid ) ) ? $result->valid : null;
			} else {
				return validate_vat_no_soap( $country_code, $vat_number );
			}
		} catch( Exception $exception ) {
			return null;
		}
	}
}

if ( ! function_exists( 'wcj_plugin_url' ) ) {
	/**
	 * wcj_plugin_url.
	 *
	 * @version 2.3.0
	 * @todo    remove this function
	 */
	function wcj_plugin_url() {
		return untrailingslashit( plugin_dir_url( realpath( dirname( __FILE__ ) . '/..' ) ) );
	}
}

if ( ! function_exists( 'wcj_plugin_path' ) ) {
	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	function wcj_plugin_path() {
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) );
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

if ( ! function_exists( 'wcj_get_order_statuses' ) ) {
	/**
	 * wcj_get_order_statuses.
	 */
	function wcj_get_order_statuses( $cut_the_prefix ) {
		$order_statuses = array(
			'wc-pending'    => _x( 'Pending Payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On Hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		);
		$order_statuses = apply_filters( 'wc_order_statuses', $order_statuses );
		if ( $cut_the_prefix ) {
			$order_statuses_no_prefix = array();
			foreach ( $order_statuses as $status => $desc ) {
				$order_statuses_no_prefix[ substr( $status, 3 ) ] = $desc;
			}
			return $order_statuses_no_prefix;
		}
		return $order_statuses;
	}
}
