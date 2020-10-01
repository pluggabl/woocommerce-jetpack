<?php
/**
 * Booster for WooCommerce - Functions - Price and Currency
 *
 * @version 5.2.0
 * @since   2.7.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_get_wc_price_step' ) ) {
	/**
	 * wcj_get_wc_price_step.
	 *
	 * @version 3.2.3
	 * @since   3.2.3
	 * @todo    use this where needed
	 */
	function wcj_get_wc_price_step() {
		return ( 1 / pow( 10, absint( wcj_get_option( 'woocommerce_price_num_decimals', 2 ) ) ) );
	}
}

if ( ! function_exists( 'wcj_get_module_price_hooks_priority' ) ) {
	/**
	 * wcj_get_module_price_hooks_priority.
	 *
	 * @version 4.1.0
	 * @since   3.2.2
	 * @todo    add all corresponding modules
	 */
	function wcj_get_module_price_hooks_priority( $module_id ) {
		$modules_priorities = array(
			'price_by_user_role'         => PHP_INT_MAX - 200,
			'product_addons'             => PHP_INT_MAX - 100,
			'product_price_by_formula'   => PHP_INT_MAX - 100,
			'multicurrency_base_price'   => PHP_INT_MAX - 10,
			'multicurrency'              => PHP_INT_MAX - 1,
			'price_by_country'           => PHP_INT_MAX - 1,
			'wholesale_price'            => PHP_INT_MAX,
			'global_discount'            => PHP_INT_MAX,
		);
		return ( 0 != ( $priority = wcj_get_option( 'wcj_' . $module_id . '_advanced_price_hooks_priority', 0 ) ) ? $priority : $modules_priorities[ $module_id ] );
	}
}

if ( ! function_exists( 'wcj_add_change_price_hooks' ) ) {
	/**
	 * wcj_add_change_price_hooks.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 * @todo    use `$module_object->price_hooks_priority` instead of passing `$priority` argument
	 */
	function wcj_add_change_price_hooks( $module_object, $priority, $include_shipping = true ) {
		// Prices
		add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,                          array( $module_object, 'change_price' ),              $priority, 2 );
		add_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER,                     array( $module_object, 'change_price' ),              $priority, 2 );
		add_filter( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,                  array( $module_object, 'change_price' ),              $priority, 2 );
		// Variations
		add_filter( 'woocommerce_variation_prices_price',                  array( $module_object, 'change_price' ),              $priority, 2 );
		add_filter( 'woocommerce_variation_prices_regular_price',          array( $module_object, 'change_price' ),              $priority, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price',             array( $module_object, 'change_price' ),              $priority, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash',               array( $module_object, 'get_variation_prices_hash' ), $priority, 3 );
		if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
			add_filter( 'woocommerce_product_variation_get_price',         array( $module_object, 'change_price' ),              $priority, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array( $module_object, 'change_price' ),              $priority, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price',    array( $module_object, 'change_price' ),              $priority, 2 );
		}
		// Shipping
		if ( $include_shipping ) {
			add_filter( 'woocommerce_package_rates',                       array( $module_object, 'change_price_shipping' ),     $priority, 2 );
		}
		// Grouped products
		add_filter( 'woocommerce_get_price_including_tax',                 array( $module_object, 'change_price_grouped' ),      $priority, 3 );
		add_filter( 'woocommerce_get_price_excluding_tax',                 array( $module_object, 'change_price_grouped' ),      $priority, 3 );
	}
}

if ( ! function_exists( 'wcj_remove_change_price_hooks' ) ) {
	/**
	 * wcj_remove_change_price_hooks.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    make one function from this and `wcj_add_change_price_hooks()`
	 */
	function wcj_remove_change_price_hooks( $module_object, $priority, $include_shipping = true ) {
		// Prices
		remove_filter( WCJ_PRODUCT_GET_PRICE_FILTER,                          array( $module_object, 'change_price' ),              $priority );
		remove_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER,                     array( $module_object, 'change_price' ),              $priority );
		remove_filter( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,                  array( $module_object, 'change_price' ),              $priority );
		// Variations
		remove_filter( 'woocommerce_variation_prices_price',                  array( $module_object, 'change_price' ),              $priority );
		remove_filter( 'woocommerce_variation_prices_regular_price',          array( $module_object, 'change_price' ),              $priority );
		remove_filter( 'woocommerce_variation_prices_sale_price',             array( $module_object, 'change_price' ),              $priority );
		remove_filter( 'woocommerce_get_variation_prices_hash',               array( $module_object, 'get_variation_prices_hash' ), $priority );
		if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
			remove_filter( 'woocommerce_product_variation_get_price',         array( $module_object, 'change_price' ),              $priority );
			remove_filter( 'woocommerce_product_variation_get_regular_price', array( $module_object, 'change_price' ),              $priority );
			remove_filter( 'woocommerce_product_variation_get_sale_price',    array( $module_object, 'change_price' ),              $priority );
		}
		// Shipping
		if ( $include_shipping ) {
			remove_filter( 'woocommerce_package_rates',                       array( $module_object, 'change_price_shipping' ),     $priority );
		}
		// Grouped products
		remove_filter( 'woocommerce_get_price_including_tax',                 array( $module_object, 'change_price_grouped' ),      $priority );
		remove_filter( 'woocommerce_get_price_excluding_tax',                 array( $module_object, 'change_price_grouped' ),      $priority );
	}
}

if ( ! function_exists( 'wcj_change_price_shipping_package_rates' ) ) {
	/**
	 * wcj_change_price_shipping_package_rates.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	function wcj_change_price_shipping_package_rates( $package_rates, $multiplier ) {
		$modified_package_rates = array();
		foreach ( $package_rates as $id => $package_rate ) {
			if ( 1 != $multiplier && isset( $package_rate->cost ) ) {
				$package_rate->cost = $package_rate->cost * $multiplier;
				if ( isset( $package_rate->taxes ) && ! empty( $package_rate->taxes ) ) {
					if ( ! WCJ_IS_WC_VERSION_BELOW_3_2_0 ) {
						$rate_taxes = $package_rate->taxes;
						foreach ( $rate_taxes as &$tax ) {
							$tax *= $multiplier;
						}
						$package_rate->taxes = $rate_taxes;
					} else {
						foreach ( $package_rate->taxes as $tax_id => $tax ) {
							$package_rate->taxes[ $tax_id ] = $package_rate->taxes[ $tax_id ] * $multiplier;
						}
					}
				}
			}
			$modified_package_rates[ $id ] = $package_rate;
		}
		return $modified_package_rates;
	}
}

if ( ! function_exists( 'wcj_get_currency_exchange_rate_product_base_currency' ) ) {
	/**
	 * wcj_get_currency_exchange_rate_product_base_currency.
	 *
	 * @version 3.5.0
	 * @since   2.5.6
	 */
	function wcj_get_currency_exchange_rate_product_base_currency( $currency_code ) {
		$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( $currency_code === wcj_get_option( 'wcj_multicurrency_base_price_currency_' . $i ) ) {
				return wcj_get_option( 'wcj_multicurrency_base_price_exchange_rate_' . $i );
			}
		}
		return 1; // fallback
	}
}

if ( ! function_exists( 'wcj_price_by_product_base_currency' ) ) {
	/**
	 * wcj_price_by_product_base_currency.
	 *
	 * @version 3.3.0
	 * @since   2.5.6
	 */
	function wcj_price_by_product_base_currency( $price, $product_id ) {
		if ( '' == $price ) {
			return $price;
		}
		$do_save = ( 'yes' === wcj_get_option( 'wcj_multicurrency_base_price_save_prices', 'no' ) );
		if ( $do_save ) {
			$_current_filter = current_filter();
			if ( '' == $_current_filter ) {
				$_current_filter = 'wcj_filter__none';
			}
		}
		if ( $do_save && isset( WCJ()->modules['multicurrency_base_price']->calculated_products_prices[ $product_id ][ $_current_filter ] ) ) {
			return WCJ()->modules['multicurrency_base_price']->calculated_products_prices[ $product_id ][ $_current_filter ];
		}
		$multicurrency_base_price_currency = get_post_meta( $product_id, '_' . 'wcj_multicurrency_base_price_currency', true );
		if ( '' != $multicurrency_base_price_currency ) {
			if ( 1 != ( $currency_exchange_rate = wcj_get_currency_exchange_rate_product_base_currency( $multicurrency_base_price_currency ) ) && 0 != $currency_exchange_rate ) {
				$_price = $price / $currency_exchange_rate;
				if ( 'yes' === wcj_get_option( 'wcj_multicurrency_base_price_round_enabled', 'no' ) ) {
					$_price = round( $_price, wcj_get_option( 'wcj_multicurrency_base_price_round_precision', wcj_get_option( 'woocommerce_price_num_decimals' ) ) );
				}
				if ( $do_save ) {
					WCJ()->modules['multicurrency_base_price']->calculated_products_prices[ $product_id ][ $_current_filter ] = $_price;
				}
				return $_price;
			}
		}
		return $price;
	}
}

if ( ! function_exists( 'wcj_price_by_country' ) ) {
	/**
	 * wcj_price_by_country.
	 *
	 * @version 4.4.0
	 * @since   2.5.3
	 */
	function wcj_price_by_country( $price, $product, $group_id, $the_current_filter = '' ) {

		$is_price_modified = false;

		if ( 'yes' === wcj_get_option( 'wcj_price_by_country_local_enabled', 'yes' ) ) {
			// Per product
			$meta_box_id = 'price_by_country';
			$scope = 'local';

			if ( is_numeric( $product ) ) {
				$the_product_id = $product;
			} else {
				$the_product_id = wcj_get_product_id( $product );
			}

			$the_product_id = wcj_maybe_get_product_id_wpml( $the_product_id );

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
				return wcj_get_product_display_price( $_product );

			} elseif ( WCJ_PRODUCT_GET_PRICE_FILTER == $the_current_filter || 'woocommerce_variation_prices_price' == $the_current_filter || 'woocommerce_product_variation_get_price' == $the_current_filter ) {

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

			} elseif (
				WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER              == $the_current_filter ||
				WCJ_PRODUCT_GET_SALE_PRICE_FILTER                 == $the_current_filter ||
				'woocommerce_variation_prices_regular_price'      == $the_current_filter ||
				'woocommerce_variation_prices_sale_price'         == $the_current_filter ||
				'woocommerce_product_variation_get_regular_price' == $the_current_filter ||
				'woocommerce_product_variation_get_sale_price'    == $the_current_filter
			) {
				$regular_or_sale = (
					WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER == $the_current_filter || 'woocommerce_variation_prices_regular_price' == $the_current_filter || 'woocommerce_product_variation_get_regular_price' == $the_current_filter
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
			if ( 'yes' === wcj_get_option( 'wcj_price_by_country_make_empty_price_group_' . $group_id, 1 ) || '' === $price ) {
				return '';
			}
		}

		if ( ! $is_price_modified ) {
			// Globally
			$precision             = wcj_get_option( 'woocommerce_price_num_decimals', 2 );
			$rounding              = wcj_get_option( 'wcj_price_by_country_rounding', 'none' );
			$country_exchange_rate = wcj_get_option( 'wcj_price_by_country_exchange_rate_group_' . $group_id, 1 );
			$modified_price        = $price * $country_exchange_rate;
			$is_price_modified     = true;

			if ( 'get_price' === wcj_get_option( 'wcj_price_by_country_price_format_method', 'get_price' ) ) {
				$modified_price = wcj_price_by_country_rounding( $modified_price, $precision, $rounding );
				if ( 'yes' === wcj_get_option( 'wcj_price_by_country_make_pretty', 'no' ) && $modified_price >= 0.5 && $precision > 0 ) {
					$modified_price = wcj_price_by_country_pretty_price( $modified_price, $precision );
				}
			}
		}

		return ( $is_price_modified ) ? $modified_price : $price;
	}
}

if ( ! function_exists( 'wcj_price_by_country_pretty_price' ) ) {
	/**
	 * wcj_price_by_country_pretty_price.
	 *
	 * @version 4.4.0
	 * @since   4.4.0
	 *
	 * @param $price
	 * @param int $multiplier
	 * @param int $precision
	 *
	 * @return float|int
	 */
	function wcj_price_by_country_pretty_price( $price, $precision = - 1, $multiplier = - 1 ) {
		$precision  = - 1 === $precision ? wcj_get_option( 'woocommerce_price_num_decimals', 2 ) : $precision;
		$multiplier = - 1 === $multiplier ? wcj_get_option( 'wcj_price_by_country_make_pretty_min_amount_multiplier', 1 ) : $multiplier;
		return round( $price ) - ( $multiplier / pow( 10, $precision ) );
	}
}

if ( ! function_exists( 'wcj_price_by_country_rounding' ) ) {
	/**
	 * wcj_price_by_country_rounding.
	 *
	 * @version 4.4.0
	 * @since   4.4.0
	 *
	 * @param $price
	 * @param int $precision
	 * @param string $rounding
	 *
	 * @return float
	 */
	function wcj_price_by_country_rounding( $price, $precision = - 1, $rounding = '' ) {
		$rounding  = empty( $rounding ) ? wcj_get_option( 'wcj_price_by_country_rounding', 'none' ) : $rounding;
		$precision = - 1 === $precision ? wcj_get_option( 'woocommerce_price_num_decimals', 2 ) : $precision;
		switch ( $rounding ) {
			case 'round':
				$modified_price = round( $price );
				break;
			case 'floor':
				$modified_price = floor( $price );
				break;
			case 'ceil':
				$modified_price = ceil( $price );
				break;
			default: // case 'none':
				$modified_price = round( $price, $precision ); // $modified_price
				break;
		}
		return $modified_price;
	}
}


if ( ! function_exists( 'wcj_update_products_price_by_country_for_single_product' ) ) {
	/**
	 * wcj_update_products_price_by_country_for_single_product.
	 *
	 * @version 2.7.0
	 * @since   2.5.3
	 */
	function wcj_update_products_price_by_country_for_single_product( $product_id ) {
		$_product = wc_get_product( $product_id );
		if ( $_product->is_type( 'variable' ) ) {
			$available_variations = $_product->get_available_variations();
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
				$min_variation_price = PHP_INT_MAX;
				$max_variation_price = 0;
				foreach ( $available_variations as $variation ) {
					$variation_product_id = $variation['variation_id'];
					$_old_variation_price = get_post_meta( $variation_product_id, '_price', true );
					if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
						$_old_variation_price = wcj_price_by_product_base_currency( $_old_variation_price, $product_id );
					}
					$price_by_country = wcj_price_by_country( $_old_variation_price, $variation_product_id, $i, WCJ_PRODUCT_GET_PRICE_FILTER );
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
			if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
				$_old_price = wcj_price_by_product_base_currency( $_old_price, $product_id );
			}
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
				$price_by_country = wcj_price_by_country( $_old_price, $product_id, $i, WCJ_PRODUCT_GET_PRICE_FILTER );
				update_post_meta( $product_id, '_' . 'wcj_price_by_country_' . $i, $price_by_country );
			}
		}
	}
}

if ( ! function_exists( 'wcj_update_products_price_by_country' ) ) {
	/**
	 * wcj_update_products_price_by_country - all products.
	 *
	 * @version 5.2.0
	 * @since   2.5.3
	 */
	function wcj_update_products_price_by_country() {
		$offset      = 0;
		$block_size  = 512;
		$bkg_process = WCJ()->modules['price_by_country']->bkg_process_price_updater;
		if ( empty( $bkg_process ) ) {
			return;
		}
		$bkg_process->cancel_process();
		while ( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
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
			foreach ( $loop->posts as $product_id ) {
				$bkg_process->push_to_queue( array( 'id' => $product_id ) );
			}
			$offset += $block_size;
		}
		$bkg_process->save()->dispatch();
	}
}

if ( ! function_exists( 'wcj_get_current_currency_code' ) ) {
	/**
	 * wcj_get_current_currency_code.
	 *
	 * @version 3.4.0
	 * @since   2.5.0
	 */
	function wcj_get_current_currency_code( $module ) {
		$current_currency_code = get_woocommerce_currency();
		if ( wcj_is_module_enabled( $module ) ) {
			if ( 'multicurrency' === $module ) {
				$current_currency_code = ( null !== ( $session_value = wcj_session_get( 'wcj-currency' ) ) ? $session_value : $current_currency_code );
			}
		}
		return $current_currency_code;
	}
}

if ( ! function_exists( 'wcj_get_currency_by_country' ) ) {
	/**
	 * wcj_get_currency_by_country.
	 *
	 * @version 4.0.0
	 * @since   2.5.4
	 */
	function wcj_get_currency_by_country( $country_code ) {
		$currency_code = '';
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
			switch ( wcj_get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
				case 'comma_list':
					$country_exchange_rate_group = wcj_get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i );
					$country_exchange_rate_group = str_replace( ' ', '', $country_exchange_rate_group );
					$country_exchange_rate_group = explode( ',', $country_exchange_rate_group );
					break;
				case 'multiselect':
					$country_exchange_rate_group = wcj_get_option( 'wcj_price_by_country_countries_group_' . $i, '' );
					if ( '' === $country_exchange_rate_group ) {
						$country_exchange_rate_group = array();
					}
					break;
				case 'chosen_select':
					$country_exchange_rate_group = wcj_get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' );
					if ( '' === $country_exchange_rate_group ) {
						$country_exchange_rate_group = array();
					}
					break;
			}
			if ( in_array( $country_code, $country_exchange_rate_group ) ) {
				$currency_code = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
				break;
			}
		}
		return ( '' == $currency_code ) ? wcj_get_option( 'woocommerce_currency' ) : $currency_code;
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
				$total_number = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( $currency_code === wcj_get_option( 'wcj_multicurrency_currency_' . $i ) ) {
						$currency_exchange_rate = wcj_get_option( 'wcj_multicurrency_exchange_rate_' . $i );
						break;
					}
				}
			}
		}
		return $currency_exchange_rate;
	}
}

if ( ! function_exists( 'wc_get_variable_product_purchase_price' ) ) {
	/**
	 * wc_get_product_purchase_price.
	 *
	 * @version 4.2.0
	 * @since   4.2.0
	 */
	function wc_get_variable_product_purchase_price( $product_id = 0, $args = array() ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return 0;
		}
		$args = wp_parse_args( $args, array(
			'search' => 'min_variation' // min_variation | max_variation | all_variations | min_max_variations
		) );

		// Get children product variation IDs in an array
		$children_ids    = $product->get_children();
		$purchase_prices = array();
		foreach ( $children_ids as $variation_id ) {
			$purchase_prices[] = wc_get_product_purchase_price( $variation_id );
		}
		$final_purchase_prices = array();
		switch ( $args['search'] ) {
			case 'min_variation':
				$final_purchase_prices = array( min( $purchase_prices ) );
			break;
			case 'max_variation':
				$final_purchase_prices = array( max( $purchase_prices ) );
			break;
			case 'min_max_variations':
				$final_purchase_prices[0] = min( $purchase_prices );
				$final_purchase_prices[1] = max( $purchase_prices );
			break;
			case 'all_variations':
				$final_purchase_prices = $purchase_prices;
			break;
		}
		$final_purchase_prices = array_unique( $final_purchase_prices );
		return $final_purchase_prices;
	}
}

if ( ! function_exists( 'wc_get_product_purchase_price' ) ) {
	/**
	 * wc_get_product_purchase_price.
	 *
	 * @version 4.2.0
	 */
	function wc_get_product_purchase_price( $product_id = 0 ) {
		if ( 0 == $product_id ) {
			$product_id = get_the_ID();
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return 0;
		}
		$purchase_price = 0;
		if ( 'yes' === wcj_get_option( 'wcj_purchase_price_enabled', 'yes' ) ) {
			$purchase_price += (float) get_post_meta( $product_id, '_' . 'wcj_purchase_price' , true );
		}
		if ( 'yes' === wcj_get_option( 'wcj_purchase_price_extra_enabled', 'yes' ) ) {
			$purchase_price += (float) get_post_meta( $product_id, '_' . 'wcj_purchase_price_extra', true );
		}
		if ( 'yes' === wcj_get_option( 'wcj_purchase_price_affiliate_commission_enabled', 'no' ) ) {
			$purchase_price += (float) get_post_meta( $product_id, '_' . 'wcj_purchase_price_affiliate_commission', true );
		}
		$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( '' == wcj_get_option( 'wcj_purchase_data_custom_price_field_name_' . $i, '' ) ) {
				continue;
			}
			$meta_value = (float) get_post_meta( $product_id, '_' . 'wcj_purchase_price_custom_field_' . $i, true );
			if ( 0 != $meta_value ) {
				$purchase_price += ( 'fixed' === wcj_get_option( 'wcj_purchase_data_custom_price_field_type_' . $i, 'fixed' ) ) ? $meta_value : $purchase_price * $meta_value / 100.0;
			}
		}
		if ( $product->is_type( 'variable' ) ) {
			$prices = wc_get_variable_product_purchase_price( $product_id, array( 'search' => 'min_variation' ) );
			if ( is_array( $prices ) && count( $prices ) > 0 ) {
				$purchase_price += $prices[0];
			}
		}
		return apply_filters( 'wcj_get_product_purchase_price', $purchase_price, $product_id );
	}
}

if ( ! function_exists( 'wcj_get_woocommerce_currencies_and_symbols' ) ) {
	/**
	 * wcj_get_woocommerce_currencies_and_symbols.
	 *
	 * @version 3.9.0
	 * @since   3.9.0
	 */
	function wcj_get_woocommerce_currencies_and_symbols() {
		$currencies_and_symbols = get_woocommerce_currencies();
		foreach ( $currencies_and_symbols as $code => $name ) {
			$currencies_and_symbols[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
		return $currencies_and_symbols;
	}
}

if ( ! function_exists( 'wcj_price' ) ) {
	/**
	 * wcj_price.
	 *
	 * @version 5.1.0
	 *
	 * @param $price
	 * @param $currency
	 * @param $hide_currency
	 * @param null $args
	 *
	 * @return string
	 */
	function wcj_price( $price, $currency, $hide_currency, $args = null ) {
		$args = wp_parse_args( $args, array(
			'currency'          => 'DISABLED',
			'add_html_on_price' => true
		) );
		if ( 'yes' !== $hide_currency ) {
			$args['currency'] = $currency;
		}
		return wc_price( $price, $args );
	}
}
