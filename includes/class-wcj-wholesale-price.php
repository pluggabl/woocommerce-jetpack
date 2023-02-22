<?php
/**
 * Booster for WooCommerce - Module - Wholesale Price
 *
 * @version 6.0.3
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @todo    per variation
 * @todo    sort discounts table by quantity (asc) before using
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Wholesale_Price' ) ) :
	/**
	 * WCJ_Wholesale_Price.
	 *
	 * @version 4.1.0
	 */
	class WCJ_Wholesale_Price extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.1.0
		 * @todo    (maybe) `woocommerce_get_variation_prices_hash`
		 */
		public function __construct() {

			$this->id         = 'wholesale_price';
			$this->short_desc = __( 'Wholesale Price', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set wholesale pricing depending on product quantity in cart - buy more pay less (1 level allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set wholesale pricing depending on product quantity in cart - buy more pay less.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-wholesale-price';
			parent::__construct();

			if ( $this->is_enabled() ) {

				if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}

				add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_loaded_from_session' ), PHP_INT_MAX, 1 );
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_totals' ), PHP_INT_MAX, 1 );

				$this->price_hooks_priority = wcj_get_module_price_hooks_priority( 'wholesale_price' );
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, array( $this, 'wholesale_price' ), $this->price_hooks_priority, 2 );
				if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_product_variation_get_price', array( $this, 'wholesale_price' ), $this->price_hooks_priority, 2 );
				}

				if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_show_info_on_cart', 'no' ) ) {
					add_filter( 'woocommerce_cart_item_price', array( $this, 'add_discount_info_to_cart_page' ), PHP_INT_MAX, 3 );
				}
			}
		}

		/**
		 * Get_price_for_cart.
		 *
		 * @version 5.6.2
		 * @since   3.4.0
		 * @param int   $price defines the price.
		 * @param  array $_product defines the _product.
		 */
		public function get_price_for_cart( $price, $_product ) {
			$product_prices_include_tax = ( 'yes' === wcj_get_option( 'woocommerce_prices_include_tax' ) );
			$cart_prices_include_tax    = ( 'incl' === wcj_get_option( 'woocommerce_tax_display_cart' ) );
			if ( $product_prices_include_tax !== $cart_prices_include_tax ) {
				return ( $cart_prices_include_tax ?
				wc_get_price_including_tax(
					$_product,
					array(
						'price' => $price,
						'qty'   => 1,
					)
				) :
				wc_get_price_excluding_tax(
					$_product,
					array(
						'price' => $price,
						'qty'   => 1,
					)
				) );
			} else {
				return $price;
			}
		}

		/**
		 * Get_quantity.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @param  array $cart defines the cart.
		 * @param  array $cart_item defines the cart_item.
		 */
		public function get_quantity( $cart, $cart_item ) {
			switch ( wcj_get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ) ) {
				case 'total_wholesale': // Total cart quantity (wholesale products only).
					$qty = $cart->cart_contents_count;
					foreach ( $cart->cart_contents as $item_key => $item ) {
						if ( ! wcj_is_product_wholesale_enabled( $item['product_id'] ) ) {
							$qty -= $item['quantity'];
						}
					}
					return $qty;
				case 'yes':             // Total cart quantity.
					return $cart->cart_contents_count;
				default: // no.       // Product quantity.
					return $cart_item['quantity'];
			}
		}

		/**
		 * Add_discount_info_to_cart_page.
		 *
		 * @version 
		 * @param  int    $price_html defines the price_html.
		 * @param  array  $cart_item defines the cart_item.
		 * @param  string $cart_item_key defines the cart_item_key.
		 */
		public function add_discount_info_to_cart_page( $price_html, $cart_item, $cart_item_key ) {

			if ( isset( $cart_item['wcj_wholesale_price'] ) ) {
				$the_quantity = $this->get_quantity( WC()->cart, $cart_item );
				$discount     = $this->get_discount_by_quantity( $the_quantity, $cart_item['product_id'] );
				if ( '0' !== $discount && 0 !== $discount ) {
					$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $cart_item['product_id'] ) )
					? get_post_meta( $cart_item['product_id'], '_wcj_wholesale_price_discount_type', true )
					: wcj_get_option( 'wcj_wholesale_price_discount_type', 'percent' );
					$_product      = $cart_item['data'];
					if ( 'price_directly' === $discount_type ) {
						$saved_wcj_wholesale_price = false;
						if ( isset( $_product->wcj_wholesale_price ) ) {
							$saved_wcj_wholesale_price = $_product->wcj_wholesale_price;
							unset( $_product->wcj_wholesale_price );
						}
						$price    = 'do_not_consider_qty' === wcj_get_option( 'wcj_wholesale_price_template_vars_discount_value_pdt', 'do_not_consider_qty' ) ? $_product->get_price() - $discount : ( $_product->get_price() - $discount ) * $the_quantity;
						$discount = wc_price( $this->get_price_for_cart( $price, $_product ) );
						if ( false !== $saved_wcj_wholesale_price ) {
							$_product->wcj_wholesale_price = $saved_wcj_wholesale_price;
						}
					} elseif ( 'fixed' === $discount_type ) {
						$price    = 'do_not_consider_qty' === wcj_get_option( 'wcj_wholesale_price_template_vars_discount_value_fdt', 'do_not_consider_qty' ) ? $discount : $discount * $the_quantity;
						$discount = wc_price( $this->get_price_for_cart( $price, $_product ) );
					} else {
						$discount = $discount . '%';
					}
					$old_price_html       = wc_price( $cart_item['wcj_wholesale_price_old'] );
					$wholesale_price_html = wcj_get_option( 'wcj_wholesale_price_show_info_on_cart_format' );
					$replaced_values      = array(
						'%old_price%'        => $old_price_html,
						'%price%'            => $price_html,
						'%original_price%'   => wc_price( $this->get_price_for_cart( $_product->get_price(), $_product ) ),
						'%discount_value%'   => $discount,
						'%discount_percent%' => $discount, // deprecated (replaced with %discount_value%).
					);
					$wholesale_price_html = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $wholesale_price_html );
					return $wholesale_price_html;
				}
			}

			return $price_html;
		}

		/**
		 * Get_discount_by_quantity.
		 *
		 * @version 6.0.3
		 * @param  int $quantity defines the quantity.
		 * @param  int $product_id defines the product_id.
		 */
		private function get_discount_by_quantity( $quantity, $product_id ) {

			// Check for user role options.
			$role_option_name_addon = '';
			$user_roles             = wcj_get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
			if ( ! empty( $user_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				foreach ( $user_roles as $user_role_key ) {
					if ( $current_user_role === $user_role_key ) {
						$role_option_name_addon = '_' . $user_role_key;
						break;
					}
				}
			}

			// Get discount.
			$max_qty_level = (int) wcj_get_option( 'wcj_wholesale_price_max_qty_level', 1 );
			$discount      = 0;
			if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
				$wholesale_price_levels_num = apply_filters( 'booster_option', 1, get_post_meta( $product_id, '_wcj_wholesale_price_levels_number' . $role_option_name_addon, true ) );
				for ( $i = 1; $i <= $wholesale_price_levels_num; $i++ ) {
					$level_qty = (int) get_post_meta( $product_id, '_wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, true );
					if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
						$max_qty_level = $level_qty;
						if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_apply_only_if_price_country_currency_price_directly' ) ) {
							$discount      = get_post_meta( $product_id, '_wcj_wholesale_price_level_discount' . $role_option_name_addon . '_' . $i, true );
							$discount_type = get_post_meta( $product_id, '_wcj_wholesale_price_discount_type', true );
							if ( 'price_directly' === $discount_type ) {
								$convertion_rate = do_shortcode( '[wcj_currency_exchange_rate_wholesale_module]' );
								$discount        = $discount * $convertion_rate;
							}
						} else {
							$discount = get_post_meta( $product_id, '_wcj_wholesale_price_level_discount' . $role_option_name_addon . '_' . $i, true );
						}
					}
				}
			} else {
				$wholesale_price_levels_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_wholesale_price_levels_number' . $role_option_name_addon, 1 ) );
				for ( $i = 1; $i <= $wholesale_price_levels_num; $i++ ) {
					$level_qty = wcj_get_option( 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX );
					if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
						$max_qty_level = $level_qty;
						$discount      = wcj_get_option( 'wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0 );
					}
				}
			}

			return $discount;
		}

		/**
		 * Get_wholesale_price.
		 *
		 * @version 6.0.3
		 * @param  int $price defines the price.
		 * @param  int $quantity defines the quantity.
		 * @param  int $product_id defines the product_id.
		 */
		private function get_wholesale_price( $price, $quantity, $product_id ) {
			$discount      = $this->get_discount_by_quantity( $quantity, $product_id );
			$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $product_id ) )
			? get_post_meta( $product_id, '_wcj_wholesale_price_discount_type', true )
			: wcj_get_option( 'wcj_wholesale_price_discount_type', 'percent' );
			if ( 'price_directly' === $discount_type ) {
				$discount = 0 === $discount ? '0' : $discount;
				return ( '0' !== $discount ) ? apply_filters( 'wcj_get_wholesale_price', $discount, $product_id ) : $price;
			} elseif ( 'percent' === $discount_type ) {
				return $price * ( 1.0 - ( $discount / 100.0 ) );
			} else {
				$discounted_price = $price - $discount;
				return ( $discounted_price >= 0 ) ? $discounted_price : 0;
			}
		}

		/**
		 * Cart_loaded_from_session.
		 *
		 * @version 2.5.0
		 * @since   2.5.0
		 * @param  array $cart defines the cart.
		 */
		public function cart_loaded_from_session( $cart ) {
			foreach ( $cart->cart_contents as $item_key => $item ) {
				if ( array_key_exists( 'wcj_wholesale_price', $item ) ) {
					WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price = $item['wcj_wholesale_price'];
				}
			}
		}

		/**
		 * Calculate_totals.
		 *
		 * @version 3.8.0
		 * @since   2.5.0
		 * @param  array $cart defines the cart.
		 */
		public function calculate_totals( $cart ) {

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

				if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_check_for_product_changes_price', 'no' ) && $_product ) {
					$product_changes = $_product->get_changes();
					if ( ! empty( $product_changes ) && isset( $product_changes['price'] ) ) {
						continue;
					}
				}

				// Prices.
				$price     = $_product->get_price();
				$price_old = wcj_get_product_display_price( $_product, '', 1, 'cart' ); // used for display only.

				// If other discount was applied in cart.
				if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_apply_only_if_no_other_discounts', 'no' ) ) {
					if ( WC()->cart->get_total_discount() > 0 || count( WC()->cart->applied_coupons ) > 0 ) {
						continue;
					}
				}

				// Maybe set wholesale price.
				$the_quantity = $this->get_quantity( $cart, $item );
				if ( $the_quantity > 0 ) {
					$wholesale_price = $this->get_wholesale_price( $price, $the_quantity, wcj_get_product_id_or_variation_parent_id( $_product ) );
					if ( 'yes' === wcj_get_option( 'wcj_wholesale_price_rounding_enabled', 'yes' ) ) {
						$wholesale_price = round( $wholesale_price, wcj_get_option( 'woocommerce_price_num_decimals', 2 ) );
					}
					if ( $wholesale_price !== $price ) {
						// Setting wholesale price.
						WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price = $wholesale_price;
						WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price']       = $wholesale_price;
						WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price_old']   = $price_old;
					}
				}
			}
		}

		/**
		 * Wholesale_price.
		 *
		 * @version 2.7.0
		 * @param  int   $price defines the price.
		 * @param  array $_product defines the _product.
		 */
		public function wholesale_price( $price, $_product ) {
			return ( wcj_is_product_wholesale_enabled( wcj_get_product_id_or_variation_parent_id( $_product ) ) && isset( $_product->wcj_wholesale_price ) ) ?
			$_product->wcj_wholesale_price : $price;
		}

	}

endif;

return new WCJ_Wholesale_Price();
