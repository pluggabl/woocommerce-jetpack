<?php
/**
 * Booster for WooCommerce - Module - Cross-sells
 *
 * @version 5.6.8
 * @since   3.5.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Cross_Sells' ) ) :

		/**
		 * WCJ_Cross_Sells.
		 */
	class WCJ_Cross_Sells extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.5.3
		 * @todo    [feature] add pop up box (for `wcj_cross_sells_replace_with_cross_sells`)
		 */
		public function __construct() {

			$this->id         = 'cross_sells';
			$this->short_desc = __( 'Cross-sells', 'woocommerce-jetpack' );
			$this->extra_desc = __( 'Cross-sells are products which you promote in the cart, based on the current product.', 'woocommerce' ) . '<br>' .
			sprintf(
				/* translators: %s: translation added */
				__( 'You can also use %1$s shortcode to display cross-sells anywhere on your site, for example on checkout page with %2$s module.', 'woocommerce-jetpack' ),
				'<code>[wcj_cross_sell_display]</code>',
				'<a href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=cart_and_checkout&section=checkout_custom_info' ) . '">' .
				__( 'Checkout Custom Info', 'woocommerce-jetpack' ) . '</a>'
			);
			$this->desc      = __( 'Customize cross-sells products display. Global Cross-sells (Plus); Exclude "Not in Stock" Products (Plus); Replace Cart Products with Cross-sells (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro  = __( 'Customize cross-sells products display. Global Cross-sells; Exclude "Not in Stock" Products; Replace Cart Products with Cross-sells.', 'woocommerce-jetpack' );
			$this->link_slug = 'woocommerce-cross-sells';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_cross_sells_total', array( $this, 'cross_sells_total' ), PHP_INT_MAX );
				add_filter( 'woocommerce_cross_sells_columns', array( $this, 'cross_sells_columns' ), PHP_INT_MAX );
				add_filter( 'woocommerce_cross_sells_orderby', array( $this, 'cross_sells_orderby' ), PHP_INT_MAX );
				if ( ! WCJ_IS_WC_VERSION_BELOW_3_3_0 ) {
					add_filter( 'woocommerce_cross_sells_order', array( $this, 'cross_sells_order' ), PHP_INT_MAX );
				}
				if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
					if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_cross_sells_global_enabled', 'no' ) ) ) {
						add_filter( 'woocommerce_product_get_cross_sell_ids', array( $this, 'cross_sells_ids' ), PHP_INT_MAX, 2 );
					}
					if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_cross_sells_exclude_not_in_stock', 'no' ) ) ) {
						add_filter( 'woocommerce_product_get_cross_sell_ids', array( $this, 'cross_sells_exclude_not_in_stock' ), PHP_INT_MAX, 2 );
					}
				}
				if ( 'yes' === wcj_get_option( 'wcj_cross_sells_hide', 'no' ) ) {
					add_action( 'init', array( $this, 'hide_cross_sells' ), PHP_INT_MAX );
				}
				if ( 'no_changes' !== wcj_get_option( 'wcj_cross_sells_position', 'no_changes' ) ) {
					add_action( 'init', array( $this, 'reposition_cross_sells' ), PHP_INT_MAX );
				}
				if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_cross_sells_replace_with_cross_sells', 'no' ) ) ) {
					add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'replace_with_cross_sells_to_url' ), PHP_INT_MAX, 2 );
					add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'remove_from_cart_by_product_id' ) );
				}
			}

		}

		/**
		 * Replace_with_cross_sells_to_url.
		 *
		 * @version 5.6.8
		 * @since   3.9.0
		 * @todo    [dev] re-check variable products
		 * @param string         $url defines the url.
		 * @param string | array $product defines the product.
		 */
		public function replace_with_cross_sells_to_url( $url, $product ) {
			$_cart = WC()->cart;
			if ( is_cart() && $_cart ) {
				$product_id            = $product->get_id();
				$product_ids_to_remove = array();
				foreach ( $_cart->get_cart() as $cart_item_key => $values ) {
					$_product       = wc_get_product( $values['product_id'] );
					$cross_sell_ids = $_product->get_cross_sell_ids();
					if ( in_array( $product_id, $cross_sell_ids, true ) ) {
						$product_ids_to_remove[] = $values['product_id'];
					}
				}
				if ( ! empty( $product_ids_to_remove ) ) {
					$url = esc_url_raw(
						add_query_arg(
							array(
								'wcj-remove-from-cart' => implode( ',', array_unique( $product_ids_to_remove ) ),
								'wcj-remove-from-cart-nonce' => wp_create_nonce( 'wcj-remove-from-cart' ),
							),
							$url
						)
					);
				}
			}
			return $url;
		}

		/**
		 * Remove_from_cart_by_product_id.
		 *
		 * @version 5.6.8
		 * @since   3.9.0
		 * @todo    [dev] AJAX
		 */
		public function remove_from_cart_by_product_id() {
			$_cart   = WC()->cart;
			$wpnonce = isset( $_GET['wcj-remove-from-cart-nonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['wcj-remove-from-cart-nonce'] ), 'wcj-remove-from-cart' ) : false;
			if ( isset( $_GET['wcj-remove-from-cart'] ) && $wpnonce ) {
				if ( isset( $_cart ) ) {
					$product_ids_to_remove = explode( ',', sanitize_text_field( wp_unslash( $_GET['wcj-remove-from-cart'] ) ) );
					foreach ( $_cart->get_cart() as $cart_item_key => $values ) {
						if ( in_array( $values['product_id'], $product_ids_to_remove, true ) ) {
							$_cart->remove_cart_item( $cart_item_key );
						}
					}
				}
				wp_safe_redirect( remove_query_arg( array( 'wcj-remove-from-cart', 'wcj-remove-from-cart-nonce' ) ) );
				exit;
			}
		}

		/**
		 * Reposition_cross_sells.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 * @todo    [dev] (maybe) check `woocommerce\templates\cart\cart.php` for more positions
		 */
		public function reposition_cross_sells() {
			$this->hide_cross_sells();
			add_action( wcj_get_option( 'wcj_cross_sells_position', 'no_changes' ), 'woocommerce_cross_sell_display', wcj_get_option( 'wcj_cross_sells_position_priority', 10 ) );
		}

		/**
		 * Hide_cross_sells.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function hide_cross_sells() {
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}

		/**
		 * Cross_sells_exclude_not_in_stock.
		 *
		 * @version 5.4.9
		 * @since   3.9.0
		 * @param int            $ids defines the ids.
		 * @param string | array $_product defines the _product.
		 */
		public function cross_sells_exclude_not_in_stock( $ids, $_product ) {
			foreach ( $ids as $key => $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! $product->managing_stock() && ! $product->is_in_stock() ) {
					unset( $ids[ $key ] );
				}
			}
			return $ids;
		}

		/**
		 * Cross_sells_ids.
		 *
		 * @version 6.0.1
		 * @since   3.6.0
		 * @todo    [dev] (maybe) ids instead of list
		 * @todo    [dev] (maybe) on cart update (i.e. product removed) cross-sells are not updated (so it may be needed to reload page manually to see new cross-sells)
		 * @todo    [feature] (maybe) on per category/tag basis
		 * @param int            $ids defines the ids.
		 * @param string | array $_product defines the _product.
		 */
		public function cross_sells_ids( $ids, $_product ) {
			$global_cross_sells = wcj_get_option( 'wcj_cross_sells_global_ids', '' );
			if ( ! empty( $global_cross_sells ) ) {
				$global_cross_sells = array_unique( $global_cross_sells );
				$product_id         = wcj_get_product_id_or_variation_parent_id( $_product );
				$key                = array_search( $product_id, $global_cross_sells, true );
				if ( false !== $key ) {
					unset( $global_cross_sells[ $key ] );
				}
			}
			return ( empty( $global_cross_sells ) ? $ids : array_unique( array_merge( $ids, $global_cross_sells ) ) );
		}

		/**
		 * Cross_sells_order.
		 *
		 * @version 3.5.3
		 * @since   3.5.3
		 * @param string | array $order defines the order.
		 */
		public function cross_sells_order( $order ) {
			$_order = wcj_get_option( 'wcj_cross_sells_order', 'no_changes' );
			return ( 'no_changes' !== ( $_order ) ? $_order : $order );
		}

		/**
		 * Cross_sells_orderby.
		 *
		 * @version 3.5.3
		 * @since   3.5.3
		 * @param string | array $orderby defines the orderby.
		 */
		public function cross_sells_orderby( $orderby ) {
			$_orderby = wcj_get_option( 'wcj_cross_sells_orderby', 'no_changes' );
			return ( 'no_changes' !== ( $_orderby ) ? $_orderby : $orderby );
		}

		/**
		 * Cross_sells_columns.
		 *
		 * @version 3.5.3
		 * @since   3.5.3
		 * @param string | int $columns defines the columns.
		 */
		public function cross_sells_columns( $columns ) {
			$_columns = wcj_get_option( 'wcj_cross_sells_columns', 0 );
			return ( '0' !== ( $_columns ) ? $_columns : $columns );
		}

		/**
		 * Cross_sells_total.
		 *
		 * @version 3.6.0
		 * @since   3.5.3
		 * @param int $limit defines the limit.
		 */
		public function cross_sells_total( $limit ) {
			$_limit = wcj_get_option( 'wcj_cross_sells_total', 0 );
			return ( '0' !== ( $_limit ) ? $_limit : $limit );
		}

	}

endif;

return new WCJ_Cross_Sells();
