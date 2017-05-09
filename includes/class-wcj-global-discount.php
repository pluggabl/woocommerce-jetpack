<?php
/**
 * Booster for WooCommerce - Module - Global Discount
 *
 * @version 2.8.0
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 * @todo    products and cats/tags to include/exclude (cats to include - done); regular price coefficient; fee instead of discount;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Global_Discount' ) ) :

class WCJ_Global_Discount extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.5.7
	 */
	function __construct() {

		$this->id         = 'global_discount';
		$this->short_desc = __( 'Global Discount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add global discount to all WooCommerce products.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shop-global-discount';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				// Prices
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,                 array( $this, 'add_global_discount_price' ),         PHP_INT_MAX, 2 );
				add_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER,            array( $this, 'add_global_discount_sale_price' ),    PHP_INT_MAX, 2 );
//				add_filter( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER,         array( $this, 'add_global_discount_regular_price' ), PHP_INT_MAX, 2 );
				// Variations
				add_filter( 'woocommerce_variation_prices_price',         array( $this, 'add_global_discount_price' ),         PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'add_global_discount_sale_price' ),    PHP_INT_MAX, 2 );
//				add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'add_global_discount_regular_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ),         PHP_INT_MAX, 3 );
				// Grouped products
				add_filter( 'woocommerce_get_price_including_tax',        array( $this, 'add_global_discount_grouped' ),       PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_get_price_excluding_tax',        array( $this, 'add_global_discount_grouped' ),       PHP_INT_MAX, 3 );
				if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_product_variation_get_price',         array( $this, 'add_global_discount_price' ),         PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_product_variation_get_sale_price',    array( $this, 'add_global_discount_sale_price' ),    PHP_INT_MAX, 2 );
//					add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'add_global_discount_regular_price' ), PHP_INT_MAX, 2 );
				}
			}
		}
	}

	/**
	 * add_global_discount_grouped.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function add_global_discount_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			foreach ( $_product->get_children() as $child_id ) {
				$the_price = get_post_meta( $child_id, '_price', true );
				$the_product = wc_get_product( $child_id );
				$the_price = wcj_get_product_display_price( $the_product, $the_price, 1 );
				if ( $the_price == $price ) {
					return $this->add_global_discount_price( $price, $the_product );
				}
			}
		}
		return $price;
	}

	/**
	 * calculate_price.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function calculate_price( $price, $coefficient, $group  ) {
		$return_price = ( 'percent' === get_option( 'wcj_global_discount_sale_coefficient_type_' . $group, 'percent' ) ) ?
			( $price + $price * ( $coefficient / 100 ) ) :
			( $price + $coefficient );
		return ( $return_price >= 0 ) ? $return_price : 0;
	}

	/**
	 * check_if_applicable.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 * @return  bool
	 */
	function check_if_applicable( $_product, $group ) {
		return ( 'yes' === get_option( 'wcj_global_discount_sale_enabled_' . $group, 'yes' ) && $this->check_product_categories( $_product, $group ) );
	}

	/**
	 * check_product_categories.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 * @return  bool
	 */
	function check_product_categories( $_product, $group ) {
		// Check product category - include
		$categories_in = get_option( 'wcj_global_discount_sale_categories_incl_' . $group, '' );
		if ( ! empty( $categories_in ) ) {
			$product_categories = get_the_terms( wcj_get_product_id_or_variation_parent_id( $_product ), 'product_cat' );
			if ( empty( $product_categories ) ) {
				return false; // option set to some categories, but product has no categories
			}
			foreach( $product_categories as $product_category ) {
				if ( in_array( $product_category->term_id, $categories_in ) ) {
					return true; // category found
				}
			}
			return false; // no categories found
		}
		return true; // option not set (i.e. left blank)
	}

	/**
	 * add_global_discount_any_price.
	 *
	 * @version 2.7.0
	 * @since   2.5.7
	 */
	function add_global_discount_any_price( $price_type, $price, $_product ) {
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_global_discount_groups_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( ! $this->check_if_applicable( $_product, $i ) ) {
				continue; // no changes by current discount group
			}
			$coefficient = get_option( 'wcj_global_discount_sale_coefficient_' . $i, 0 );
			if ( 0 != $coefficient ) {
				if ( 'sale_price' === $price_type ) {
					if ( 0 == $price ) {
						// The product is currently not on sale
						if ( 'only_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) ) {
							continue; // no changes by current discount group
						} else {
							$price = $_product->get_regular_price();
						}
					} else {
						// The product is currently on sale
						if ( 'only_not_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) ) {
							continue; // no changes by current discount group
						}
					}
				} else { // if ( 'price' === $price_type )
					remove_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER,         array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					remove_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
						remove_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					}
					if ( 'only_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) && 0 == $_product->get_sale_price() ) {
						continue; // no changes by current discount group
					} elseif ( 'only_not_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) && 0 != $_product->get_sale_price() ) {
						continue; // no changes by current discount group
					}
					add_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER,         array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
						add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'add_global_discount_sale_price' ), PHP_INT_MAX, 2 );
					}
				}
				return $this->calculate_price( $price, $coefficient, $i ); // discount applied
			}
		}
		return $price; // no changes
	}

	/**
	 * add_global_discount_sale_price.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_global_discount_sale_price( $price, $_product ) {
		return $this->add_global_discount_any_price( 'sale_price', $price, $_product );
	}

	/**
	 * add_global_discount_price.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_global_discount_price( $price, $_product ) {
		if ( '' === $price ) {
			return $price; // no changes
		}
		return $this->add_global_discount_any_price( 'price', $price, $_product );
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$wcj_global_discount_price_hash = array();
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_global_discount_groups_total_number', 1 ) );
		$wcj_global_discount_price_hash['total_number'] = $total_number;
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$wcj_global_discount_price_hash[ 'enabled_' . $i ] = get_option( 'wcj_global_discount_sale_enabled_'          . $i, 'yes' );
			$wcj_global_discount_price_hash[ 'type_'    . $i ] = get_option( 'wcj_global_discount_sale_coefficient_type_' . $i, 'percent' );
			$wcj_global_discount_price_hash[ 'value_'   . $i ] = get_option( 'wcj_global_discount_sale_coefficient_'      . $i, 0 );
			$wcj_global_discount_price_hash[ 'scope_'   . $i ] = get_option( 'wcj_global_discount_sale_product_scope_'    . $i, 'all' );
			$wcj_global_discount_price_hash[ 'cats_in_' . $i ] = get_option( 'wcj_global_discount_sale_categories_incl_'  . $i, '' );
		}
		$price_hash['wcj_global_discount_price_hash'] = $wcj_global_discount_price_hash;
		return $price_hash;
	}

}

endif;

return new WCJ_Global_Discount();
