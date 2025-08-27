<?php
/**
 * Booster for WooCommerce - Module - Free Price
 *
 * @version 7.3.0
 * @since   2.5.9
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Free_Price' ) ) :
	/**
	 * WCJ_Free_Price.
	 */
	class WCJ_Free_Price extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @since   2.5.9
		 * @todo    single in grouped is treated as "related"
		 */
		public function __construct() {

			$this->id         = 'free_price';
			$this->short_desc = __( 'Free Price Labels', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set free price labels (Variable products allowed in Elite).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set free price labels.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-free-price-labels';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_free_price_html', array( $this, 'modify_free_price_simple_external_custom' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_grouped_free_price_html', array( $this, 'modify_free_price_grouped' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_variable_free_price_html', array( $this, 'modify_free_price_variable' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_variation_free_price_html', array( $this, 'modify_free_price_variation' ), PHP_INT_MAX, 2 );
				} else {
					add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_modify_price' ), PHP_INT_MAX, 2 );
				}
			}
		}

		/**
		 * Are_all_prices_free
		 *
		 * @version 7.0.0
		 * @since   2.7.0
		 * @param string | array $_product defines the _product.
		 * @param string         $type defines the type.
		 */
		public function are_all_prices_free( $_product, $type ) {
			if ( 'variable' === $type ) {
				$prices    = $_product->get_variation_prices( true );
				$min_price = (int) current( $prices['price'] );
				$max_price = (int) end( $prices['price'] );
				if ( '' !== $min_price && '' !== $max_price ) {
					return ( 0 === $min_price && 0 === $max_price );
				}
			} elseif ( 'variable' === $type ) {
				$child_prices = array();
				foreach ( $_product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );
					if ( '' !== $child->get_price() ) {
						$child_prices[] = wcj_get_product_display_price( $child );
					}
				}
				if ( ! empty( $child_prices ) ) {
					$min_price = min( $child_prices );
					$max_price = max( $child_prices );
				} else {
					$min_price = '';
					$max_price = '';
				}
				if ( '' !== $min_price && '' !== $max_price ) {
					return ( 0 === $min_price && 0 === $max_price );
				}
			}
			return false;
		}

		/**
		 * Maybe_modify_price
		 *
		 * @version 5.6.3
		 * @since   2.7.0
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function maybe_modify_price( $price, $_product ) {
			if ( '' !== $price ) {
				if ( '0' === $_product->get_price() ) {
					if ( $_product->is_type( 'grouped' ) ) {
						return ( $this->are_all_prices_free( $_product, 'grouped' ) ) ? $this->modify_free_price_grouped( $price, $_product ) : $price;
					} elseif ( $_product->is_type( 'variable' ) ) {
						return ( $this->are_all_prices_free( $_product, 'variable' ) ) ? $this->modify_free_price_variable( $price, $_product ) : $price;
					} elseif ( $_product->is_type( 'variation' ) ) {
						return $this->modify_free_price_variation( $price, $_product );
					} else {
						return $this->modify_free_price_simple_external_custom( $price, $_product );
					}
				}
			}
			return $price;
		}

		/**
		 * Get_view_id
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 * @param int $product_id defines the product_id.
		 */
		public function get_view_id( $product_id ) {
			$view = 'single'; // default.
			if ( is_single( $product_id ) ) {
				$view = 'single';
			} elseif ( is_single() ) {
				$view = 'related';
			} elseif ( is_front_page() ) {
				$view = 'home';
			} elseif ( is_page() ) {
				$view = 'page';
			} elseif ( is_archive() ) {
				$view = 'archive';
			}
			return $view;
		}

		/**
		 * Modify_free_price_simple_external_custom.
		 *
		 * @version 2.7.0
		 * @since   2.5.9
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function modify_free_price_simple_external_custom( $price, $_product ) {
			$default = '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>';
			return ( $_product->is_type( 'external' ) ) ?
			do_shortcode( wcj_get_option( 'wcj_free_price_external_' . $this->get_view_id( wcj_get_product_id_or_variation_parent_id( $_product ) ), $default ) ) :
			do_shortcode( wcj_get_option( 'wcj_free_price_simple_' . $this->get_view_id( wcj_get_product_id_or_variation_parent_id( $_product ) ), $default ) );
		}

		/**
		 * Modify_free_price_grouped.
		 *
		 * @version 2.7.0
		 * @since   2.5.9
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function modify_free_price_grouped( $price, $_product ) {
			return do_shortcode( wcj_get_option( 'wcj_free_price_grouped_' . $this->get_view_id( wcj_get_product_id_or_variation_parent_id( $_product ) ), __( 'Free!', 'woocommerce' ) ) );
		}

		/**
		 * Modify_free_price_variable.
		 *
		 * @version 2.7.0
		 * @since   2.5.9
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function modify_free_price_variable( $price, $_product ) {
			return do_shortcode( apply_filters( 'booster_option', __( 'Free!', 'woocommerce' ), wcj_get_option( 'wcj_free_price_variable_' . $this->get_view_id( wcj_get_product_id_or_variation_parent_id( $_product ) ), __( 'Free!', 'woocommerce' ) ) ) );
		}

		/**
		 * Modify_free_price_variation.
		 *
		 * @version 2.5.9
		 * @since   2.5.9
		 * @param int            $price defines the price.
		 * @param string | array $_product defines the _product.
		 */
		public function modify_free_price_variation( $price, $_product ) {
			return do_shortcode( apply_filters( 'booster_option', __( 'Free!', 'woocommerce' ), wcj_get_option( 'wcj_free_price_variable_variation', __( 'Free!', 'woocommerce' ) ) ) );
		}

	}

endif;

return new WCJ_Free_Price();
