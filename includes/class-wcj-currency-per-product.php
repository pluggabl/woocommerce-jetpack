<?php
/**
 * Booster for WooCommerce - Module - Currency per Product
 *
 * @version 7.1.6
 * @since   2.5.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Currency_Per_Product' ) ) :
	/**
	 * WCJ_Currency_Per_Product.
	 */
	class WCJ_Currency_Per_Product extends WCJ_Module {

		/**
		 * The module do_save_converted_prices
		 *
		 * @var varchar $do_save_converted_prices Module.
		 */
		public $do_save_converted_prices;
		/**
		 * The module is_currency_per_product_by_product_enabled
		 *
		 * @var varchar $is_currency_per_product_by_product_enabled Module.
		 */
		public $is_currency_per_product_by_product_enabled;
		/**
		 * The module saved_product_prices
		 *
		 * @var varchar $saved_product_prices Module.
		 */
		public $saved_product_prices;
		/**
		 * Constructor.
		 *
		 * @version 5.4.9
		 * @since   2.5.2
		 * @todo    (maybe) add `$this->price_hooks_priority`
		 */
		public function __construct() {
			$this->id         = 'currency_per_product';
			$this->short_desc = __( 'Currency per Product', 'woocommerce-jetpack' );
			$this->desc       = __( 'Display prices for products in different currencies (1 currency allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Display prices for products in different currencies.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-currency-per-product';
			parent::__construct();

			if ( $this->is_enabled() ) {

				$this->do_save_converted_prices = ( 'yes' === get_option( 'wcj_currency_per_product_save_prices', 'no' ) );

				$this->is_currency_per_product_by_product_enabled = ( 'yes' === get_option( 'wcj_currency_per_product_per_product', 'yes' ) );
				if ( $this->is_currency_per_product_by_product_enabled ) {
					add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}

				// Currency code and symbol.
				add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), PHP_INT_MAX, 10 );

				// Add to cart.
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), PHP_INT_MAX, 10, 3 );
				add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), PHP_INT_MAX, 10, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), PHP_INT_MAX, 10, 3 );
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_on_add_to_cart' ), PHP_INT_MAX, 2 );

				// Price.
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, array( $this, 'change_price' ), PHP_INT_MAX, 10, 2 );
				add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price' ), PHP_INT_MAX, 10, 2 );

				// Grouped.
				add_filter( 'woocommerce_grouped_price_html', array( $this, 'grouped_price_html' ), PHP_INT_MAX, 10, 2 );

				// Shipping.
				add_filter( 'woocommerce_package_rates', array( $this, 'change_shipping_price' ), PHP_INT_MAX, 10, 2 );
			}
		}

		/**
		 * Change_shipping_price.
		 *
		 * @version 5.4.9
		 * @since   2.7.0
		 * @param int    $package_rates defines the package_rates.
		 * @param string $package defines the package.
		 */
		public function change_shipping_price( $package_rates, $package ) {
			if ( isset( WC()->cart ) ) {
				if ( WC()->cart->is_empty() ) {
					return $package_rates;
				}
				$cart_checkout_behaviour = get_option( 'wcj_currency_per_product_cart_checkout', 'convert_shop_default' );
				switch ( $cart_checkout_behaviour ) {
					case 'leave_one_product':
					case 'leave_same_currency':
					case 'convert_first_product':
					case 'convert_last_product':
						$shop_currency = get_option( 'woocommerce_currency' );
						$_currency     = $this->get_cart_checkout_currency();
						if ( false !== ( $_currency ) && $_currency !== $shop_currency ) {
							$currency_exchange_rate = $this->get_currency_exchange_rate( $_currency );
							if ( 0 !== $currency_exchange_rate && 1 !== $currency_exchange_rate ) {
								$currency_exchange_rate = 1 / $currency_exchange_rate;
								return wcj_change_price_shipping_package_rates( $package_rates, $currency_exchange_rate );
							} else {
								return $package_rates;
							}
						} else {
							return $package_rates;
						}
					default:
						return $package_rates;
				}
			}
			return $package_rates;
		}

		/**
		 * Get_product_currency.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @todo    (maybe) return empty string or false, if it's shop default currency: `return ( get_option( 'woocommerce_currency' ) != ( $return = get_post_meta( $product_id, '_' . 'wcj_currency_per_product_currency', true ) ) ? $return : false );`.
		 * @param int $product_id defines the product_id.
		 */
		public function get_product_currency( $product_id ) {

			// By users or user roles.
			$do_check_by_users        = ( 'yes' === get_option( 'wcj_currency_per_product_by_users_enabled', 'no' ) );
			$do_check_by_user_roles   = ( 'yes' === get_option( 'wcj_currency_per_product_by_user_roles_enabled', 'no' ) );
			$do_check_by_product_cats = ( 'yes' === get_option( 'wcj_currency_per_product_by_product_cats_enabled', 'no' ) );
			$do_check_by_product_tags = ( 'yes' === get_option( 'wcj_currency_per_product_by_product_tags_enabled', 'no' ) );
			if ( $do_check_by_users || $do_check_by_user_roles || $do_check_by_product_cats || $do_check_by_product_tags ) {
				if ( $do_check_by_users || $do_check_by_user_roles ) {
					$product_author_id = get_post_field( 'post_author', $product_id );
				}
				if ( $do_check_by_product_cats ) {
					$_product_cats = wcj_get_the_terms( $product_id, 'product_cat' );
				}
				if ( $do_check_by_product_tags ) {
					$_product_tags = wcj_get_the_terms( $product_id, 'product_tag' );
				}
				$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( $do_check_by_users ) {
						$users = get_option( 'wcj_currency_per_product_users_' . $i, '' );

						if ( ! empty( $users ) && in_array( $product_author_id, $users, true ) ) {
							return get_option( 'wcj_currency_per_product_currency_' . $i );
						}
					}
					if ( $do_check_by_user_roles ) {
						$user_roles = get_option( 'wcj_currency_per_product_user_roles_' . $i, '' );
						if ( ! empty( $user_roles ) && wcj_is_user_role( $user_roles, $product_author_id ) ) {
							return get_option( 'wcj_currency_per_product_currency_' . $i );
						}
					}
					if ( $do_check_by_product_cats ) {
						$product_cats = get_option( 'wcj_currency_per_product_product_cats_' . $i, '' );
						if ( ! empty( $_product_cats ) && ! empty( $product_cats ) ) {
							$_intersect = array_intersect( $_product_cats, $product_cats );
							if ( ! empty( $_intersect ) ) {
								return get_option( 'wcj_currency_per_product_currency_' . $i );
							}
						}
					}
					if ( $do_check_by_product_tags ) {
						$product_tags = get_option( 'wcj_currency_per_product_product_tags_' . $i, '' );
						if ( ! empty( $_product_tags ) && ! empty( $product_tags ) ) {
							$_intersect = array_intersect( $_product_tags, $product_tags );
							if ( ! empty( $_intersect ) ) {
								return get_option( 'wcj_currency_per_product_currency_' . $i );
							}
						}
					}
				}
			}
			// By product meta.
			return ( $this->is_currency_per_product_by_product_enabled ? get_post_meta( $product_id, '_wcj_currency_per_product_currency', true ) : false );
		}

		/**
		 * Validate_on_add_to_cart.
		 *
		 * @version 5.4.9
		 * @since   2.7.0
		 * @param string $passed defines the passed.
		 * @param int    $product_id defines the product_id.
		 */
		public function validate_on_add_to_cart( $passed, $product_id ) {
			$cart_checkout_behaviour = get_option( 'wcj_currency_per_product_cart_checkout', 'convert_shop_default' );
			if ( 'leave_one_product' === $cart_checkout_behaviour ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( $cart_item['product_id'] !== $product_id ) {
						wc_add_notice(
							get_option(
								'wcj_currency_per_product_cart_checkout_leave_one_product',
								__( 'Only one product can be added to the cart. Clear the cart or finish the order, before adding another product to the cart.', 'woocommerce-jetpack' )
							),
							'error'
						);
						return false;
					}
				}
			} elseif ( 'leave_same_currency' === $cart_checkout_behaviour ) {
				$shop_currency    = get_option( 'woocommerce_currency' );
				$product_currency = $this->get_product_currency( $product_id );
				if ( '' === $product_currency ) {
					$product_currency = $shop_currency;
				}
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$cart_product_currency = ( isset( $cart_item['wcj_currency_per_product'] ) && '' !== $cart_item['wcj_currency_per_product'] ) ?
						$cart_item['wcj_currency_per_product'] : $shop_currency;
					if ( $cart_product_currency !== $product_currency ) {
						wc_add_notice(
							get_option(
								'wcj_currency_per_product_cart_checkout_leave_same_currency',
								__( 'Only products with same currency can be added to the cart. Clear the cart or finish the order, before adding products with another currency to the cart.', 'woocommerce-jetpack' )
							),
							'error'
						);
						return false;
					}
				}
			}
			return $passed;
		}

		/**
		 * Grouped_price_html.
		 *
		 * @version 2.9.0
		 * @since   2.5.2
		 * @param string         $price_html defines the price_html.
		 * @param string | array $_product defines the _product.
		 */
		public function grouped_price_html( $price_html, $_product ) {
			$child_prices = array();
			foreach ( $_product->get_children() as $child_id ) {
				$child_prices[ $child_id ] = get_post_meta( $child_id, '_price', true );
			}
			if ( ! empty( $child_prices ) ) {
				asort( $child_prices );
				$min_price    = current( $child_prices );
				$min_price_id = key( $child_prices );
				end( $child_prices );
				$max_price                         = current( $child_prices );
				$max_price_id                      = key( $child_prices );
				$min_currency_per_product_currency = $this->get_product_currency( $min_price_id );
				$max_currency_per_product_currency = $this->get_product_currency( $max_price_id );
			} else {
				$min_price = '';
				$max_price = '';
			}

			if ( $min_price ) {
				if ( $min_price === $max_price && $min_currency_per_product_currency === $max_currency_per_product_currency ) {
					$display_price = wc_price( wcj_get_product_display_price( $_product, $min_price, 1 ), array( 'currency' => $min_currency_per_product_currency ) );
				} else {
					$from = wc_price( wcj_get_product_display_price( $_product, $min_price, 1 ), array( 'currency' => $min_currency_per_product_currency ) );
					$to   = wc_price( wcj_get_product_display_price( $_product, $max_price, 1 ), array( 'currency' => $max_currency_per_product_currency ) );
					/* translators: %s: translation added */
					$display_price = sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), $from, $to );
				}
				$new_price_html = $display_price . $_product->get_price_suffix();
				return $new_price_html;
			}

			return $price_html;
		}

		/**
		 * Get_currency_exchange_rate.
		 *
		 * @version 5.4.9
		 * @since   2.5.2
		 * @param string | int $currency_code defines the currency_code.
		 */
		public function get_currency_exchange_rate( $currency_code ) {
			$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( get_option( 'wcj_currency_per_product_currency_' . $i ) === $currency_code ) {
					$rate = get_option( 'wcj_currency_per_product_exchange_rate_' . $i, 1 );
					return ( 0 !== ( $rate ) ? ( 1 / $rate ) : 1 );
				}
			}
			return 1;
		}

		/**
		 * Maybe_return_saved_converted_price.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param string | array $_product defines the _product.
		 * @param int | string   $_currency defines the _currency.
		 */
		public function maybe_return_saved_converted_price( $_product, $_currency ) {
			if ( $this->do_save_converted_prices ) {
				$product_id = ( isset( $_product->wcj_currency_per_product_item_key ) ? $_product->wcj_currency_per_product_item_key : wcj_get_product_id( $_product ) );
				if ( isset( $this->saved_product_prices[ $product_id ][ $_product->wcj_currency_per_product ][ $_currency ] ) ) {
					return $this->saved_product_prices[ $product_id ][ $_product->wcj_currency_per_product ][ $_currency ];
				}
			}
			return false;
		}

		/**
		 * Maybe_save_converted_price.
		 *
		 * @version 3.3.0
		 * @since   3.3.0
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 * @param int | string   $_currency defines the _currency.
		 */
		public function maybe_save_converted_price( $price, $_product, $_currency ) {
			if ( $this->do_save_converted_prices ) {
				$product_id = ( isset( $_product->wcj_currency_per_product_item_key ) ? $_product->wcj_currency_per_product_item_key : wcj_get_product_id( $_product ) );
				$this->saved_product_prices[ $product_id ][ $_product->wcj_currency_per_product ][ $_currency ] = $price;
			}
			return $price;
		}

		/**
		 * Change_price.
		 *
		 * @version 5.4.9
		 * @since   2.5.2
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function change_price( $price, $_product ) {
			if ( isset( $_product->wcj_currency_per_product ) ) {
				$cart_checkout_behaviour = get_option( 'wcj_currency_per_product_cart_checkout', 'convert_shop_default' );
				switch ( $cart_checkout_behaviour ) {
					case 'leave_one_product':
					case 'leave_same_currency':
						return $price;
					case 'convert_first_product':
					case 'convert_last_product':
						$shop_currency = get_option( 'woocommerce_currency' );
						$_currency     = $this->get_cart_checkout_currency();
						if ( false !== ( $_currency ) && $_currency !== $shop_currency ) {
							if ( $_product->wcj_currency_per_product === $_currency ) {
								return $price;
							} else {
								$saved_price = $this->maybe_return_saved_converted_price( $_product, $_currency );
								if ( false !== ( $saved_price ) ) {
									return $saved_price;
								}
								$exchange_rate_product       = $this->get_currency_exchange_rate( $_product->wcj_currency_per_product );
								$exchange_rate_cart_checkout = $this->get_currency_exchange_rate( $_currency );
								$exchange_rate               = $exchange_rate_product / $exchange_rate_cart_checkout;
								return $this->maybe_save_converted_price( $price * $exchange_rate, $_product, $_currency );
							}
						} elseif ( $_product->wcj_currency_per_product === $shop_currency ) {
							return $price;
						} else {
							$saved_price = $this->maybe_return_saved_converted_price( $_product, $shop_currency );
							if ( false !== ( $saved_price ) ) {
								return $saved_price;
							}
							$exchange_rate = $this->get_currency_exchange_rate( $_product->wcj_currency_per_product );
							return $this->maybe_save_converted_price( $price * $exchange_rate, $_product, $shop_currency );
						}
					default:
						$shop_currency = get_option( 'woocommerce_currency' );
						$saved_price   = $this->maybe_return_saved_converted_price( $_product, $shop_currency );
						if ( false !== ( $saved_price ) ) {
							return $saved_price;
						}
						$exchange_rate = $this->get_currency_exchange_rate( $_product->wcj_currency_per_product );
						return $this->maybe_save_converted_price( $price * $exchange_rate, $_product, $shop_currency );
				}
			}
			return $price;
		}

		/**
		 * Get_cart_item_from_session.
		 *
		 * @version 3.3.0
		 * @since   2.5.2
		 * @param array          $item defines the item.
		 * @param array          $values defines the values.
		 * @param string | array $key defines the key.
		 */
		public function get_cart_item_from_session( $item, $values, $key ) {
			if ( array_key_exists( 'wcj_currency_per_product', $values ) ) {
				$item['data']->wcj_currency_per_product = $values['wcj_currency_per_product'];
				if ( $this->do_save_converted_prices ) {
					$item['data']->wcj_currency_per_product_item_key = $key;
				}
			}
			return $item;
		}

		/**
		 * Add_cart_item_data.
		 *
		 * @version 2.9.0
		 * @since   2.5.2
		 * @param array $cart_item_data defines the cart_item_data.
		 * @param int   $product_id defines the product_id.
		 * @param int   $variation_id defines the variation_id.
		 */
		public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			$currency_per_product_currency = $this->get_product_currency( $product_id );
			if ( '' !== $currency_per_product_currency ) {
				$cart_item_data['wcj_currency_per_product'] = $currency_per_product_currency;
			}
			return $cart_item_data;
		}

		/**
		 * Add_cart_item.
		 *
		 * @version 3.3.0
		 * @since   2.5.2
		 * @todo    `wcj_currency_per_product_item_key` seems to be not working here
		 * @param array        $cart_item_data defines the cart_item_data.
		 * @param int | string $cart_item_key defines the cart_item_key.
		 */
		public function add_cart_item( $cart_item_data, $cart_item_key ) {
			if ( isset( $cart_item_data['wcj_currency_per_product'] ) ) {
				$cart_item_data['data']->wcj_currency_per_product = $cart_item_data['wcj_currency_per_product'];
				if ( $this->do_save_converted_prices ) {
					$cart_item_data['data']->wcj_currency_per_product_item_key = $cart_item_key;
				}
			}
			return $cart_item_data;
		}

		/**
		 * Get_current_product_id_and_currency.
		 *
		 * @version 5.6.8
		 * @since   2.7.0
		 */
		public function get_current_product_id_and_currency() {
			// Get ID.
			$the_id = false;
			global $product;
			if ( $product ) {
				$the_id = wcj_get_product_id_or_variation_parent_id( $product );
			}
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! $the_id && isset( $_REQUEST['product_id'] ) ) {
				$the_id = isset( $_REQUEST['product_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['product_id'] ) ) : '';
			}
			if ( ! $the_id && isset( $_POST['form'] ) ) { // WooCommerce Bookings plugin.
				$posted    = array();
				$post_form = isset( $_POST['form'] ) ? sanitize_text_field( wp_unslash( $_POST['form'] ) ) : '';
				parse_str( $post_form, $posted );
				$the_id = isset( $posted['add-to-cart'] ) ? $posted['add-to-cart'] : 0;
			}
			// phpcs:enable WordPress.Security.NonceVerification
			$eventon_wc_product_id = get_post_meta( get_the_ID(), 'tx_woocommerce_product_id', true );
			if ( ! $the_id && '' !== ( $eventon_wc_product_id ) ) { // EventON plugin.
				$the_id = $eventon_wc_product_id;
			}
			if ( ! $the_id ) {
				$the_id = get_the_ID();
			}
			// Get currency.
			if ( $the_id && 'product' === get_post_type( $the_id ) ) {
				$currency_per_product_currency = $this->get_product_currency( $the_id );
				return ( '' !== $currency_per_product_currency ) ? $currency_per_product_currency : false;
			}
			return false;
		}

		/**
		 * Get_cart_checkout_currency.
		 *
		 * @version 5.4.9
		 * @since   2.7.0
		 */
		public function get_cart_checkout_currency() {
			$cart_checkout_behaviour = get_option( 'wcj_currency_per_product_cart_checkout', 'convert_shop_default' );
			$value                   = apply_filters( 'wcj_currency_per_product_cart_checkout_currency', false, $cart_checkout_behaviour );
			if ( false !== ( $value ) ) {
				return $value;
			}

			if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
				return false;
			}
			if ( 'convert_shop_default' === $cart_checkout_behaviour ) {
				return false;
			}
			$cart_items = WC()->cart->get_cart();
			if ( 'convert_last_product' === $cart_checkout_behaviour ) {
				$cart_items = array_reverse( $cart_items );
			}
			foreach ( $cart_items as $cart_item ) {
				return ( isset( $cart_item['wcj_currency_per_product'] ) ) ? $cart_item['wcj_currency_per_product'] : false;
			}
		}

		/**
		 * Is_cart_or_checkout_or_ajax.
		 *
		 * @version 3.7.0
		 * @since   2.7.0
		 * @todo    fix AJAX issue (for minicart)
		 */
		public function is_cart_or_checkout_or_ajax() {
			return apply_filters(
				'wcj_currency_per_product_is_cart_or_checkout',
				( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) )
			);
		}

		/**
		 * Change_currency_code.
		 *
		 * @version 2.7.0
		 * @since   2.5.2
		 * @param int | string $currency defines the currency.
		 */
		public function change_currency_code( $currency ) {
			$_currency = $this->get_current_product_id_and_currency();
			if ( false !== ( $_currency ) ) {
				return $_currency;
			} elseif ( $this->is_cart_or_checkout_or_ajax() ) {
				$_currency = $this->get_cart_checkout_currency();
				return ( false !== ( $_currency ) ) ? $_currency : $currency;
			}
			return $currency;
		}
	}

endif;

return new WCJ_Currency_Per_Product();
