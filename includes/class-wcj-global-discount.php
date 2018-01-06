<?php
/**
 * Booster for WooCommerce - Module - Global Discount
 *
 * @version 3.1.0
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Global_Discount' ) ) :

class WCJ_Global_Discount extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
	 * @since   2.5.7
	 * @todo    fee instead of discount
	 * @todo    regular price coefficient
	 */
	function __construct() {

		$this->id         = 'global_discount';
		$this->short_desc = __( 'Global Discount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add global discount to all WooCommerce products.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shop-global-discount';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				wcj_add_change_price_hooks( $this, PHP_INT_MAX, false );
			}
		}
	}

	/**
	 * change_price.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    `WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER, 'woocommerce_variation_prices_regular_price', 'woocommerce_product_variation_get_regular_price'`
	 */
	function change_price( $price, $_product ) {
		$_current_filter = current_filter();
		if ( in_array( $_current_filter, array( WCJ_PRODUCT_GET_PRICE_FILTER, 'woocommerce_variation_prices_price', 'woocommerce_product_variation_get_price' ) ) ) {
			return $this->add_global_discount( $price, $_product, 'price' );
		} elseif ( in_array( $_current_filter, array( WCJ_PRODUCT_GET_SALE_PRICE_FILTER, 'woocommerce_variation_prices_sale_price', 'woocommerce_product_variation_get_sale_price' ) ) ) {
			return $this->add_global_discount( $price, $_product, 'sale_price' );
		} else {
			return $price;
		}
	}

	/**
	 * change_price_grouped.
	 *
	 * @version 3.1.0
	 * @since   2.5.7
	 */
	function change_price_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			foreach ( $_product->get_children() as $child_id ) {
				$the_price   = get_post_meta( $child_id, '_price', true );
				$the_product = wc_get_product( $child_id );
				$the_price   = wcj_get_product_display_price( $the_product, $the_price, 1 );
				if ( $the_price == $price ) {
					return $this->add_global_discount( $price, $the_product, 'price' );
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
	 * @version 3.1.0
	 * @since   2.5.7
	 * @return  bool
	 */
	function check_if_applicable( $_product, $group ) {
		return ( 'yes' === get_option( 'wcj_global_discount_sale_enabled_' . $group, 'yes' ) && $this->is_enabled_for_product( $_product, $group ) );
	}

	/**
	 * is_enabled_for_product.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function is_enabled_for_product( $_product, $group ) {
		$product_id = wcj_get_product_id_or_variation_parent_id( $_product );
		return wcj_is_enabled_for_product( $product_id, array(
			'include_products'   => get_option( 'wcj_global_discount_sale_products_incl_'   . $group, '' ),
			'exclude_products'   => get_option( 'wcj_global_discount_sale_products_excl_'   . $group, '' ),
			'include_categories' => get_option( 'wcj_global_discount_sale_categories_incl_' . $group, '' ),
			'exclude_categories' => get_option( 'wcj_global_discount_sale_categories_excl_' . $group, '' ),
			'include_tags'       => get_option( 'wcj_global_discount_sale_tags_incl_'       . $group, '' ),
			'exclude_tags'       => get_option( 'wcj_global_discount_sale_tags_excl_'       . $group, '' ),
		) );
	}

	/**
	 * check_if_applicable_by_product_scope.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function check_if_applicable_by_product_scope( $_product, $price, $price_type, $scope ) {
		$return = true;
		if ( 'sale_price' === $price_type ) {
			if ( 0 == $price ) {
				// The product is currently not on sale
				if ( 'only_on_sale' === $scope ) {
					$return = false;
				}
			} else {
				// The product is currently on sale
				if ( 'only_not_on_sale' === $scope ) {
					$return = false;
				}
			}
		} else { // if ( 'price' === $price_type )
			wcj_remove_change_price_hooks( $this, PHP_INT_MAX, false );
			if ( 'only_on_sale' === $scope && 0 == $_product->get_sale_price() ) {
				$return = false;
			} elseif ( 'only_not_on_sale' === $scope && 0 != $_product->get_sale_price() ) {
				$return = false;
			}
			wcj_add_change_price_hooks( $this, PHP_INT_MAX, false );
		}
		return $return;
	}

	/**
	 * add_global_discount.
	 *
	 * @version 3.1.0
	 * @since   2.5.7
	 */
	function add_global_discount( $price, $_product, $price_type ) {
		if ( 'price' === $price_type && '' === $price ) {
			return $price; // no changes
		}
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_global_discount_groups_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( ! $this->check_if_applicable( $_product, $i ) ) {
				continue; // no changes by current discount group
			}
			$coefficient = get_option( 'wcj_global_discount_sale_coefficient_' . $i, 0 );
			if ( 0 != $coefficient ) {
				if ( ! $this->check_if_applicable_by_product_scope( $_product, $price, $price_type, get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) ) ) {
					continue; // no changes by current discount group
				}
				if ( 'sale_price' === $price_type && 0 == $price ) {
					$price = $_product->get_regular_price();
				}
				return $this->calculate_price( $price, $coefficient, $i ); // discount applied
			}
		}
		return $price; // no changes
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.1.0
	 * @since   2.5.7
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$wcj_global_discount_price_hash = array();
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_global_discount_groups_total_number', 1 ) );
		$wcj_global_discount_price_hash['total_number'] = $total_number;
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$wcj_global_discount_price_hash[ 'enabled_' . $i ]     = get_option( 'wcj_global_discount_sale_enabled_'          . $i, 'yes' );
			$wcj_global_discount_price_hash[ 'type_'    . $i ]     = get_option( 'wcj_global_discount_sale_coefficient_type_' . $i, 'percent' );
			$wcj_global_discount_price_hash[ 'value_'   . $i ]     = get_option( 'wcj_global_discount_sale_coefficient_'      . $i, 0 );
			$wcj_global_discount_price_hash[ 'scope_'   . $i ]     = get_option( 'wcj_global_discount_sale_product_scope_'    . $i, 'all' );
			$wcj_global_discount_price_hash[ 'cats_in_' . $i ]     = get_option( 'wcj_global_discount_sale_categories_incl_'  . $i, '' );
			$wcj_global_discount_price_hash[ 'cats_ex_' . $i ]     = get_option( 'wcj_global_discount_sale_categories_excl_'  . $i, '' );
			$wcj_global_discount_price_hash[ 'tags_in_' . $i ]     = get_option( 'wcj_global_discount_sale_tags_incl_'        . $i, '' );
			$wcj_global_discount_price_hash[ 'tags_ex_' . $i ]     = get_option( 'wcj_global_discount_sale_tags_excl_'        . $i, '' );
			$wcj_global_discount_price_hash[ 'products_in_' . $i ] = get_option( 'wcj_global_discount_sale_products_incl_'    . $i, '' );
			$wcj_global_discount_price_hash[ 'products_ex_' . $i ] = get_option( 'wcj_global_discount_sale_products_excl_'    . $i, '' );
		}
		$price_hash['wcj_global_discount_price_hash'] = $wcj_global_discount_price_hash;
		return $price_hash;
	}

}

endif;

return new WCJ_Global_Discount();
