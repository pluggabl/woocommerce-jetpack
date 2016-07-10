<?php
/**
 * WooCommerce Jetpack Functions
 *
 * The WooCommerce Jetpack Functions.
 *
 * @version 2.5.4
 * @author  Algoritmika Ltd.
 */

if ( ! function_exists( 'wcj_price_by_country' ) ) {
	/**
	 * wcj_price_by_country.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_price_by_country( $price, $product, $group_id, $the_current_filter = '' ) {

		$is_price_modified = false;

		if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled' ) ) {
			// Per product
			$meta_box_id = 'price_by_country';
			$scope = 'local';

			if ( is_numeric( $product ) ) {
				$the_product_id = $product;
			} else {
				$the_product_id = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;
			}

			$meta_id = '_' . 'wcj_' . $meta_box_id . '_make_empty_price_' . $scope . '_' . $group_id;
			if ( 'on' === get_post_meta( $the_product_id, $meta_id, true ) ) {
				return '';
			}

			$price_by_country = '';
			if ( '' == $the_current_filter ) {
				$the_current_filter = current_filter();
			}
			if ( 'woocommerce_get_price_including_tax' == $the_current_filter || 'woocommerce_get_price_excluding_tax' == $the_current_filter ) {
				$_product = wc_get_product( $the_product_id );
				$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
				return $_product->$get_price_method();

			} elseif ( 'woocommerce_get_price' == $the_current_filter || 'woocommerce_variation_prices_price' == $the_current_filter ) {

				$regular_or_sale = '_regular_price_';
				$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
				$regular_price = get_post_meta( $the_product_id, $meta_id, true );

				$regular_or_sale = '_sale_price_';
				$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
				$sale_price = get_post_meta( $the_product_id, $meta_id, true );

				if ( ! empty( $sale_price ) && $sale_price < $regular_price ) {
					$price_by_country = $sale_price;
				} else {
					$price_by_country = $regular_price;
				}

			}
			elseif (
				'woocommerce_get_regular_price' == $the_current_filter ||
				'woocommerce_get_sale_price' == $the_current_filter ||
				'woocommerce_variation_prices_regular_price' == $the_current_filter ||
				'woocommerce_variation_prices_sale_price' == $the_current_filter
			) {
				$regular_or_sale = (
					'woocommerce_get_regular_price' == $the_current_filter || 'woocommerce_variation_prices_regular_price' == $the_current_filter
				) ? '_regular_price_' : '_sale_price_';
				$meta_id = '_' . 'wcj_' . $meta_box_id . $regular_or_sale . $scope . '_' . $group_id;
				$price_by_country = get_post_meta( $the_product_id, $meta_id, true );
			}

			if ( '' != $price_by_country ) {
				$modified_price = $price_by_country;
				$is_price_modified = true;
			}
		}

		if ( ! $is_price_modified ) {
			if ( 'yes' === get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 1 ) || '' === $price ) {
				return '';
			}
		}

		if ( ! $is_price_modified ) {
			// Globally
			$country_exchange_rate = get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			if ( 1 != $country_exchange_rate ) {
				$modified_price = $price * $country_exchange_rate;
				$rounding = get_option( 'wcj_price_by_country_rounding', 'none' );
				$precision = get_option( 'woocommerce_price_num_decimals', 2 );
				switch ( $rounding ) {
					case 'round':
						$modified_price = round( $modified_price );
						break;
					case 'floor':
						$modified_price = floor( $modified_price );
						break;
					case 'ceil':
						$modified_price = ceil( $modified_price );
						break;
					default: // case 'none':
						$modified_price = round( $modified_price, $precision ); // $modified_price
						break;
				}
				$is_price_modified = true;
			}
		}

		return ( $is_price_modified ) ? $modified_price : $price;
	}
}

if ( ! function_exists( 'wcj_update_products_price_by_country_for_single_product' ) ) {
	/**
	 * wcj_update_products_price_by_country_for_single_product.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_update_products_price_by_country_for_single_product( $product_id ) {
		$_product = wc_get_product( $product_id );
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
				$min_variation_price = PHP_INT_MAX;
				$max_variation_price = 0;
				foreach ( $available_variations as $variation ) {
					$variation_product_id = $variation['variation_id'];
					$_old_variation_price = get_post_meta( $variation_product_id, '_price', true );
					$price_by_country = wcj_price_by_country( $_old_variation_price, $variation_product_id, $i, 'woocommerce_get_price' );
					update_post_meta( $variation_product_id, '_' . 'wcj_price_by_country_' . $i, $price_by_country );
					if ( '' != $price_by_country && $price_by_country < $min_variation_price ) {
						$min_variation_price = $price_by_country;
					}
					if ( $price_by_country > $max_variation_price ) {
						$max_variation_price = $price_by_country;
					}
				}
				delete_post_meta( $product_id, '_' . 'wcj_price_by_country_' . $i );
				add_post_meta( $product_id, '_' . 'wcj_price_by_country_' . $i, $min_variation_price );
				if ( $min_variation_price != $max_variation_price ) {
					add_post_meta( $product_id, '_' . 'wcj_price_by_country_' . $i, $max_variation_price );
				}
			}
		} else {
			$_old_price = get_post_meta( $product_id, '_price', true );
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
				$price_by_country = wcj_price_by_country( $_old_price, $product_id, $i, 'woocommerce_get_price' );
				update_post_meta( $product_id, '_' . 'wcj_price_by_country_' . $i, $price_by_country );
			}
		}
	}
}

if ( ! function_exists( 'wcj_update_products_price_by_country' ) ) {
	/**
	 * wcj_update_products_price_by_country - all products.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_update_products_price_by_country() {
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$product_id = $loop->post->ID;
				wcj_update_products_price_by_country_for_single_product( $product_id );
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
	}
}

if ( ! function_exists( 'wcj_get_rocket_icon' ) ) {
	/**
	 * wcj_get_rocket_icon.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_get_rocket_icon() {
		return '<img class="wcj-rocket-icon" src="' . plugins_url() . '/' . 'woocommerce-jetpack' . '/assets/images/rocket-icon.png' . '" title="">';
	}
}

if ( ! function_exists( 'wcj_get_5_rocket_image' ) ) {
	/**
	 * wcj_get_5_rocket_image.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function wcj_get_5_rocket_image() {
		return '<img class="wcj-rocket-icon" src="' . plugins_url() . '/' . 'woocommerce-jetpack' . '/assets/images/5-rockets.png' . '" title="">';
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

if ( ! function_exists( 'wcj_get_current_currency_code' ) ) {
	/**
	 * wcj_get_current_currency_code.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function wcj_get_current_currency_code( $module ) {
		$current_currency_code = get_woocommerce_currency();
		if ( wcj_is_module_enabled( $module ) ) {
			if ( 'multicurrency' === $module ) {
				$current_currency_code = ( isset( $_SESSION['wcj-currency'] ) ) ? $_SESSION['wcj-currency'] : $current_currency_code;
			}
		}
		return $current_currency_code;
	}
}

if ( ! function_exists( 'wcj_get_currency_by_country' ) ) {
	/**
	 * wcj_get_currency_by_country.
	 *
	 * @version 2.5.4
	 * @since   2.5.4
	 */
	function wcj_get_currency_by_country( $country_code ) {
		$currency_code = '';
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
			switch ( get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
				case 'comma_list':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
					$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
					$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
					break;
				case 'multiselect':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_' . $i );
					break;
				case 'chosen_select':
					$country_exchange_rate_group = get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i );
					break;
			}
			if ( in_array( $country_code, $country_exchange_rate_group ) ) {
				$currency_code = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
				break;
			}
		}
		return ( '' == $currency_code ) ? get_option( 'woocommerce_currency' ) : $currency_code;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate' ) ) {
	/**
	 * wcj_get_currency_exchange_rate.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function wcj_get_currency_exchange_rate( $module, $currency_code ) {
		$currency_exchange_rate = 1;
		if ( wcj_is_module_enabled( $module ) ) {
			if ( 'multicurrency' === $module ) {
				$total_number = apply_filters( 'wcj_get_option_filter', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( $currency_code === get_option( 'wcj_multicurrency_currency_' . $i ) ) {
						$currency_exchange_rate = get_option( 'wcj_multicurrency_exchange_rate_' . $i );
						break;
					}
				}
			}
		}
		return $currency_exchange_rate;
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

/*
 * wcj_current_filter_priority.
 *
 * @version 2.4.6
 * @since   2.4.6
 */
if ( ! function_exists( 'wcj_current_filter_priority' ) ) {
	function wcj_current_filter_priority() {
		global $wp_filter;
		$current_filter_priority = key( $wp_filter[ current_filter() ] );
		return $current_filter_priority;
	}
}

/*
 * wcj_get_product_input_fields.
 *
 * @version 2.4.4
 * @since   2.4.4
 * @return  string
 */
if ( ! function_exists( 'wcj_get_product_input_fields' ) ) {
	function wcj_get_product_input_fields( $item ) {
		$product_input_fields = array();
		foreach ( $item as $key => $value ) {
			if ( false !== strpos( $key, 'wcj_product_input_fields_' ) ) {
				$product_input_fields[] = /* $key . ': ' . */ $value;
			}
		}
		return ( ! empty( $product_input_fields ) ) ? /* ' (' . */ implode( ', ', $product_input_fields ) /* . ')' */ : '';
	}
}

/*
 * wcj_get_left_to_free_shipping.
 *
 * @version 2.5.3
 * @since   2.4.4
 * @return  string
 */
if ( ! function_exists( 'wcj_get_left_to_free_shipping' ) ) {
	function wcj_get_left_to_free_shipping( $content ) {
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
					$content = str_replace( '%left_to_free%',             wc_price( $min_free_shipping_amount - $total ), $content );
					$content = str_replace( '%free_shipping_min_amount%', wc_price( $min_free_shipping_amount ),          $content );
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
 * @version  2.3.0
 * @since    2.3.0
 * @return   array
 */
if ( ! function_exists( 'wcj_get_select_options' ) ) {
	function wcj_get_select_options( $select_options_raw ) {
		$select_options_raw = explode( PHP_EOL, $select_options_raw );
		$select_options = array();
		foreach ( $select_options_raw as $select_options_title ) {
			$select_options_key = sanitize_title( $select_options_title );
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

/**
 * wcj_get_products.
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_products' ) ) {
	function wcj_get_products( $products = array() ) {
		$offset = 0;
		$block_size = 96;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) break;
			while ( $loop->have_posts() ) : $loop->the_post();
				$products[ strval( $loop->post->ID ) ] = get_the_title( $loop->post->ID );
			endwhile;
			$offset += $block_size;
		}
		wp_reset_postdata();
		return $products;
	}
}

/*
 * wcj_get_product.
 */
if ( ! function_exists( 'wcj_get_product' ) ) {
	function wcj_get_product( $product_id = 0 ) {
		if ( 0 == $product_id ) $product_id = get_the_ID();
		$the_product = new WCJ_Product( $product_id );
		return $the_product;
	}
}

/**
 * wc_get_product_purchase_price.
 */
if ( ! function_exists( 'wc_get_product_purchase_price' ) ) {
	function wc_get_product_purchase_price( $product_id = 0 ) {
		$the_product = wcj_get_product( $product_id );
		return $the_product->get_purchase_price();
	}
}

/**
 * is_shop_manager.
 *
 * @return bool
 */
if ( ! function_exists( 'is_shop_manager' ) ) {
	function is_shop_manager( $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		//if ( isset( $the_user['roles'][0] ) && 'shop_manager' === $the_user['roles'][0] ) {
		return ( isset( $the_user->roles[0] ) && 'shop_manager' === $the_user->roles[0] ) ? true : false;
	}
}

/**
 * wcj_get_current_user_first_role.
 *
 * @version 2.5.3
 * @since   2.5.3
 */
if ( ! function_exists( 'wcj_get_current_user_first_role' ) ) {
	function wcj_get_current_user_first_role() {
		$current_user = wp_get_current_user();
		return ( isset( $current_user->roles[0] ) && '' != $current_user->roles[0] ) ? $current_user->roles[0] : 'guest';
	}
}

/**
 * wcj_get_user_roles.
 *
 * @version 2.5.3
 * @since   2.5.3
 */
if ( ! function_exists( 'wcj_get_user_roles' ) ) {
	function wcj_get_user_roles() {
		global $wp_roles;
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
		return $all_roles;
	}
}
/**
 * wcj_get_user_roles_options.
 *
 * @version 2.5.3
 * @since   2.5.3
 */
if ( ! function_exists( 'wcj_get_user_roles_options' ) ) {
	function wcj_get_user_roles_options() {
		global $wp_roles;
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
		$all_roles_options = array();
		foreach ( $all_roles as $_role_key => $_role ) {
			$all_roles_options[ $_role_key ] = $_role['name'];
		}
		return $all_roles_options;
	}
}

/**
 * wcj_is_user_role.
 *
 * @version 2.5.2
 * @since   2.5.0
 * @return  bool
 */
if ( ! function_exists( 'wcj_is_user_role' ) ) {
	function wcj_is_user_role( $user_role, $user_id = 0 ) {
		$the_user = ( 0 == $user_id ) ? wp_get_current_user() : get_user_by( 'id', $user_id );
		if ( ! isset( $the_user->roles ) || empty( $the_user->roles ) ) {
			$the_user->roles = array( 'guest' );
		}
		return ( isset( $the_user->roles ) && is_array( $the_user->roles ) && in_array( $user_role, $the_user->roles ) ) ? true : false;
	}
}

/**
 * validate_VAT.
 *
 * @return mixed: bool on successful checking (can be true or false), null otherwise
 */
if ( ! function_exists( 'validate_VAT' ) ) {
	function validate_VAT( $country_code, $vat_number ) {
		try {
			$client = new SoapClient(
				'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl',
				array( 'exceptions' => true )
			);

			$result = $client->checkVat( array(
				'countryCode' => $country_code,
				'vatNumber'   => $vat_number,
			) );

			return ( isset( $result->valid ) ) ? $result->valid : null;

		} catch( Exception $exception ) {
			return null;
		}
	}
}

/**
 * wcj_plugin_url.
 *
 * @version 2.3.0
 */
if ( ! function_exists( 'wcj_plugin_url' ) ) {
	function wcj_plugin_url() {
		return untrailingslashit( plugin_dir_url( realpath( dirname( __FILE__ ) . '/..' ) ) );
		//return untrailingslashit( realpath( dirname( __FILE__ ) . '/..' ) );
	}
}

/**
 * Get the plugin path.
 *
 * @return string
 */
if ( ! function_exists( 'wcj_plugin_path' ) ) {
	function wcj_plugin_path() {
		//return untrailingslashit( plugin_dir_path( __FILE__ ) );
		return untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../..' ) );
	}
}

/**
 * Convert the php date format string to a js date format.
 * https://gist.github.com/clubduece/4053820
 */
/* if ( ! function_exists( 'wcj_date_format_php_to_js' ) ) {
	function wcj_date_format_php_to_js( $php_date_format ) {
		$date_formats_php_to_js = array(
			'F j, Y' => 'MM dd, yy',
			'Y/m/d'  => 'yy/mm/dd',
			'm/d/Y'  => 'mm/dd/yy',
			'd/m/Y'  => 'dd/mm/yy',
		);
		return isset( $date_formats_php_to_js[ $php_date_format ] ) ? $date_formats_php_to_js[ $php_date_format ] : 'MM dd, yy';
	}
} */

/*
 * Matches each symbol of PHP date format standard
 * with jQuery equivalent codeword
 * http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
 *
 * @author  Tristan Jahier
 * @version 2.4.0
 * @since   2.4.0
 */
if ( ! function_exists( 'wcj_date_format_php_to_js_v2' ) ) {
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

/**
 * wcj_hex2rgb.
 */
if ( ! function_exists( 'wcj_hex2rgb' ) ) {
	function wcj_hex2rgb( $hex ) {
		return sscanf( $hex, "#%2x%2x%2x" );
	}
}

/**
 * wcj_get_the_ip.
 * http://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
 */
if ( ! function_exists( 'wcj_get_the_ip' ) ) {
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

/**
 * wcj_get_shortcodes_atts_list.
 *
if ( ! function_exists( 'wcj_get_shortcodes_atts_list' ) ) {
	function wcj_get_shortcodes_atts_list() {
		return apply_filters( 'wcj_shortcodes_atts', array(
			'before'        => '',
			'after'         => '',
			'visibility'    => '',
		) );
	}
}

/**
 * wcj_get_shortcodes_list.
 */
if ( ! function_exists( 'wcj_get_shortcodes_list' ) ) {
	function wcj_get_shortcodes_list() {
		$the_array = apply_filters( 'wcj_shortcodes_list', array() );
		return implode( ', ', $the_array )/*  . ' (' . count( $the_array ) . ')' */;
	}
}

/**
 * wcj_get_order_statuses.
 */
if ( ! function_exists( 'wcj_get_order_statuses' ) ) {
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

/**
 * wcj_get_currencies_names_and_symbols.
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_currencies_names_and_symbols' ) ) {
	function wcj_get_currencies_names_and_symbols( $result = 'names_and_symbols', $scope = 'all' ) {
		$currency_names_and_symbols = array();
		/* if ( ! wcj_is_module_enabled( 'currency' ) ) {
			return $currency_names_and_symbols;
		} */
		if ( 'all' === $scope || 'no_custom' === $scope ) {
//			$currencies = include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
//			include( wcj_plugin_path() . '/includes/currencies/wcj-currencies.php' );
			$currencies = wcj_get_currencies_array();
			foreach( $currencies as $data ) {
				switch ( $result ) {
					case 'names_and_symbols':
						$currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
						break;
					case 'names':
						$currency_names_and_symbols[ $data['code'] ] = $data['name'];
						break;
					case 'symbols':
						$currency_names_and_symbols[ $data['code'] ] = $data['symbol'];
						break;
				}
			}
		}
		if ( wcj_is_module_enabled( 'currency' ) && ( 'all' === $scope || 'custom_only' === $scope ) ) {
			// Custom currencies
			$custom_currency_total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
			for ( $i = 1; $i <= $custom_currency_total_number; $i++) {
				$custom_currency_code   = get_option( 'wcj_currency_custom_currency_code_'   . $i );
				$custom_currency_name   = get_option( 'wcj_currency_custom_currency_name_'   . $i );
				$custom_currency_symbol = get_option( 'wcj_currency_custom_currency_symbol_' . $i );
				if ( '' != $custom_currency_code && '' != $custom_currency_name /* && '' != $custom_currency_symbol */ ) {
					switch ( $result ) {
						case 'names_and_symbols':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_name . ' (' . $custom_currency_symbol . ')';
							break;
						case 'names':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_name;
							break;
						case 'symbols':
							$currency_names_and_symbols[ $custom_currency_code ] = $custom_currency_symbol;
							break;
					}
				}
			}
		}
		return $currency_names_and_symbols;
	}
}

/**
 * wcj_get_currency_symbol.
 *
 * @version 2.4.4
 */
if ( ! function_exists( 'wcj_get_currency_symbol' ) ) {
	function wcj_get_currency_symbol( $currency_code ) {
		$return = '';
		$currencies = wcj_get_currencies_names_and_symbols( 'symbols', 'no_custom' );
		if ( isset( $currencies[ $currency_code ] ) ) {
			if ( wcj_is_module_enabled( 'currency' ) ) {
				$return = apply_filters( 'wcj_get_option_filter', $currencies[ $currency_code ], get_option( 'wcj_currency_' . $currency_code, $currencies[ $currency_code ] ) );
			} else {
				$return = $currencies[ $currency_code ];
			}
		} else {
			$currencies = wcj_get_currencies_names_and_symbols( 'symbols', 'custom_only' );
			$return = isset( $currencies[ $currency_code ] ) ? $currencies[ $currency_code ] : '';
		}
		return ( '' != $return ) ? $return : false;
	}
}

/**
 * wcj_price.
 */
if ( ! function_exists( 'wcj_price' ) ) {
	function wcj_price( $price, $currency, $hide_currency ) {
		return ( 'yes' === $hide_currency ) ? wc_price( $price, array( 'currency' => 'DISABLED' ) ) : wc_price( $price, array( 'currency' => $currency ) );
	}
}
