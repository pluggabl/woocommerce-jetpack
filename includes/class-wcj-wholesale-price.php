<?php
/**
 * Booster for WooCommerce - Module - Wholesale Price
 *
 * @version 3.3.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 * @todo    per variation
 * @todo    sort discounts table by quantity (asc) before using
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Wholesale_Price' ) ) :

class WCJ_Wholesale_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @todo    (maybe) `woocommerce_get_variation_prices_hash`
	 */
	function __construct() {

		$this->id         = 'wholesale_price';
		$this->short_desc = __( 'Wholesale Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce wholesale pricing depending on product quantity in cart (buy more pay less).', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-wholesale-price';
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_loaded_from_session' ), PHP_INT_MAX, 1 );
			add_action( 'woocommerce_before_calculate_totals',  array( $this, 'calculate_totals' ), PHP_INT_MAX, 1 );
			add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,           array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );
			if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
				add_filter( 'woocommerce_product_variation_get_price', array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );
			}

			if ( 'yes' === get_option( 'wcj_wholesale_price_show_info_on_cart', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_price', array( $this, 'add_discount_info_to_cart_page' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * add_discount_info_to_cart_page.
	 *
	 * @version 3.2.1
	 */
	function add_discount_info_to_cart_page( $price_html, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['wcj_wholesale_price'] ) ) {
			$the_quantity = ( 'yes' === get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ) )
				? WC()->cart->cart_contents_count
				: $cart_item['quantity'];
			$discount = $this->get_discount_by_quantity( $the_quantity, $cart_item['product_id'] );
			if ( 0 != $discount ) {
				$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $cart_item['product_id'] ) )
					? get_post_meta( $cart_item['product_id'], '_' . 'wcj_wholesale_price_discount_type', true )
					: get_option( 'wcj_wholesale_price_discount_type', 'percent' );
				if ( 'price_directly' === $discount_type ) {
					$_product = $cart_item['data'];
					if ( isset( $_product->wcj_wholesale_price ) ) {
						unset( $_product->wcj_wholesale_price );
					}
					$discount = wc_price( $_product->get_price() - $discount );
				} elseif ( 'fixed' === $discount_type ) {
					$discount = wc_price( $discount );
				} else {
					$discount = $discount . '%';
				}
				$old_price_html = wc_price( $cart_item['wcj_wholesale_price_old'] );
				$wholesale_price_html = get_option( 'wcj_wholesale_price_show_info_on_cart_format' );
				$replaced_values = array(
					'%old_price%'        => $old_price_html,
					'%price%'            => $price_html,
					'%discount_value%'   => $discount,
					'%discount_percent%' => $discount, // deprecated (replaced with %discount_value%)
				);
				$wholesale_price_html = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $wholesale_price_html );
				return $wholesale_price_html;
			}
		}

		return $price_html;
	}

	/**
	 * get_discount_by_quantity.
	 *
	 * @version 2.5.5
	 */
	private function get_discount_by_quantity( $quantity, $product_id ) {

		// Check for user role options
		$role_option_name_addon = '';
		$user_roles = get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
		if ( ! empty( $user_roles ) ) {
			$current_user_role = wcj_get_current_user_first_role();
			foreach ( $user_roles as $user_role_key ) {
				if ( $current_user_role === $user_role_key ) {
					$role_option_name_addon = '_' . $user_role_key;
					break;
				}
			}
		}

		// Get discount
		$max_qty_level = 1;
		$discount      = 0;
		if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number' . $role_option_name_addon, true ) ); $i++ ) {
				$level_qty = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, true );
				if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
					$max_qty_level = $level_qty;
					$discount = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_discount' . $role_option_name_addon . '_' . $i, true );
				}
			}
		} else {
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_wholesale_price_levels_number' . $role_option_name_addon, 1 ) ); $i++ ) {
				$level_qty = get_option( 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX );
				if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
					$max_qty_level = $level_qty;
					$discount = get_option( 'wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0 );
				}
			}
		}

		return $discount;
	}

	/**
	 * get_wholesale_price.
	 *
	 * @version 2.5.7
	 */
	private function get_wholesale_price( $price, $quantity, $product_id ) {
		$discount = $this->get_discount_by_quantity( $quantity, $product_id );
		$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $product_id ) )
			? get_post_meta( $product_id, '_' . 'wcj_wholesale_price_discount_type', true )
			: get_option( 'wcj_wholesale_price_discount_type', 'percent' );
		if ( 'price_directly' === $discount_type ) {
			return ( 0 != $discount ) ? apply_filters( 'wcj_get_wholesale_price', $discount, $product_id ) : $price;
		} elseif ( 'percent' === $discount_type ) {
			return $price * ( 1.0 - ( $discount / 100.0 ) );
		} else {
			$discounted_price = $price - $discount;
			return ( $discounted_price >= 0 ) ? $discounted_price : 0;
		}
	}

	/**
	 * cart_loaded_from_session.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function cart_loaded_from_session( $cart ) {
		foreach ( $cart->cart_contents as $item_key => $item ) {
			if ( array_key_exists( 'wcj_wholesale_price', $item ) ) {
				WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price = $item['wcj_wholesale_price'];
			}
		}
	}

	/**
	 * calculate_totals.
	 *
	 * @version 3.3.0
	 * @since   2.5.0
	 * @todo    `$price_old` must be price to display *in cart* (now it's *in shop*)
	 */
	function calculate_totals( $cart ) {

		foreach ( $cart->cart_contents as $item_key => $item ) {

			if ( isset( WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price ) ) {
				unset( WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price );
			}
			if ( isset( WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price'] ) ) {
				unset( WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price'] );
			}
			if ( isset( WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price_old'] ) ) {
				unset( WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price_old'] );
			}

			$_product = $item['data'];
			if ( ! wcj_is_product_wholesale_enabled( wcj_get_product_id_or_variation_parent_id( $_product ) ) ) {
				continue;
			}

			// Prices
			$price     = $_product->get_price();
			$price_old = wcj_get_product_display_price( $_product ); // used for display only

			// If other discount was applied in cart...
			if ( 'yes' === get_option( 'wcj_wholesale_price_apply_only_if_no_other_discounts', 'no' ) ) {
				if ( WC()->cart->get_total_discount() > 0 || sizeof( WC()->cart->applied_coupons ) > 0 ) {
					continue;
				}
			}

			// Maybe set wholesale price
			$the_quantity = ( 'yes' === get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ) )
				? $cart->cart_contents_count
				: $item['quantity'];
			if ( $the_quantity > 0 ) {
				$wholesale_price = $this->get_wholesale_price( $price, $the_quantity, wcj_get_product_id_or_variation_parent_id( $_product ) );
				if ( 'yes' === get_option( 'wcj_wholesale_price_rounding_enabled', 'yes' ) ) {
					$wholesale_price = round( $wholesale_price, get_option( 'woocommerce_price_num_decimals', 2 ) );
				}
				if ( $wholesale_price != $price ) {
					// Setting wholesale price
					WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price = $wholesale_price;
					WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price']       = $wholesale_price;
					WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price_old']   = $price_old;
				}
			}

		}
	}

	/**
	 * wholesale_price.
	 *
	 * @version 2.7.0
	 */
	function wholesale_price( $price, $_product ) {
		return ( wcj_is_product_wholesale_enabled( wcj_get_product_id_or_variation_parent_id( $_product ) ) && isset( $_product->wcj_wholesale_price ) ) ?
			$_product->wcj_wholesale_price : $price;
	}

}

endif;

return new WCJ_Wholesale_Price();
