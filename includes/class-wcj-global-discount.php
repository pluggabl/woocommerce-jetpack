<?php
/**
 * WooCommerce Jetpack Global Discount
 *
 * The WooCommerce Jetpack Global Discount class.
 *
 * @version 2.5.7
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 * @todo    products and cats/tags to include/exclude (cats to include - done); (maybe) product scope - apply only to products that are NOT on sale; regular price coefficient; fee instead of discount;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Global_Discount' ) ) :

class WCJ_Global_Discount extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function __construct() {

		$this->id         = 'global_discount';
		$this->short_desc = __( 'Global Discount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add global discount to all WooCommerce products.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-shop-global-discount/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				// Prices
				add_filter( 'woocommerce_get_price',                      array( $this, 'add_global_discount_price' ),         PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_sale_price',                 array( $this, 'add_global_discount_sale_price' ),    PHP_INT_MAX, 2 );
//				add_filter( 'woocommerce_get_regular_price',              array( $this, 'add_global_discount_regular_price' ), PHP_INT_MAX, 2 );
				// Variations
				add_filter( 'woocommerce_variation_prices_price',         array( $this, 'add_global_discount_price' ),         PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_variation_prices_sale_price',    array( $this, 'add_global_discount_sale_price' ),    PHP_INT_MAX, 2 );
//				add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'add_global_discount_regular_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_variation_prices_hash',      array( $this, 'get_variation_prices_hash' ),         PHP_INT_MAX, 3 );
				// Grouped products
				add_filter( 'woocommerce_get_price_including_tax',        array( $this, 'add_global_discount_grouped' ),       PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_get_price_excluding_tax',        array( $this, 'add_global_discount_grouped' ),       PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * add_global_discount_grouped.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_global_discount_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			$get_price_method = 'get_price_' . get_option( 'woocommerce_tax_display_shop' ) . 'uding_tax';
			foreach ( $_product->get_children() as $child_id ) {
				$the_price = get_post_meta( $child_id, '_price', true );
				$the_product = wc_get_product( $child_id );
				$the_price = $the_product->$get_price_method( 1, $the_price );
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
	 * @version 2.5.7
	 * @since   2.5.7
	 * @return  bool
	 */
	function check_product_categories( $_product, $group ) {
		// Check product category - include
		$categories_in = get_option( 'wcj_global_discount_sale_categories_incl_' . $group, '' );
		if ( ! empty( $categories_in ) ) {
			$product_categories = get_the_terms( $_product->id, 'product_cat' );
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
	 * @version 2.5.7
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
					}
				} else { // if ( 'price' === $price_type )
					if ( 'only_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_' . $i, 'all' ) && 0 == $_product->get_sale_price() ) {
						continue; // no changes by current discount group
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

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_settings() {
		$product_cats_options = array();
		$product_cats = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ){
			foreach ( $product_cats as $product_cat ) {
				$product_cats_options[ $product_cat->term_id ] = $product_cat->name;
			}
		}
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_global_discount_options',
			),
			array(
				'title'    => __( 'Total Groups', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_groups_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc_tip' => __( 'Press Save changes after you change this number.', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'custom_attributes' => is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ?
					apply_filters( 'booster_get_message', '', 'readonly' ) : array( 'step' => '1', 'min'  => '1', ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_global_discount_options',
			),
		);
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_global_discount_groups_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Discount Group', 'woocommerce-jetpack' ) . ' #' . $i,
					'type'     => 'title',
					'id'       => 'wcj_global_discount_options_' . $i,
				),
				array(
					'title'    => __( 'Enabled', 'woocommerce-jetpack' ),
					'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
					'id'       => 'wcj_global_discount_sale_enabled_' . $i,
					'default'  => 'yes',
					'type'     => 'checkbox',
				),
				array(
					'title'    => __( 'Type', 'woocommerce-jetpack' ),
					'id'       => 'wcj_global_discount_sale_coefficient_type_' . $i,
					'default'  => 'percent',
					'type'     => 'select',
					'options'  => array(
						'percent' => __( 'Percent', 'woocommerce-jetpack' ),
						'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'    => __( 'Value', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Must be negative number.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_global_discount_sale_coefficient_' . $i,
					'default'  => 0,
					'type'     => 'number',
					'custom_attributes' => array( /* 'min' => 0, */ 'max' => 0, 'step' => 0.0001 ), // todo
				),
				array(
					'title'    => __( 'Product Scope', 'woocommerce-jetpack' ),
					'id'       => 'wcj_global_discount_sale_product_scope_' . $i,
					'default'  => 'all',
					'type'     => 'select',
					'options'  => array(
						'all'          => __( 'All products', 'woocommerce-jetpack' ),
						'only_on_sale' => __( 'Only products that are already on sale', 'woocommerce-jetpack' ),
					),
				),
				array(
					'title'    => __( 'Include Product Categories', 'woocommerce-jetpack' ),
					'desc_tip' => __( 'Set this field to apply discount to selected categories only. Leave blank to apply to all categories.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_global_discount_sale_categories_incl_' . $i,
					'default'  => '',
					'class'    => 'chosen_select',
					'type'     => 'multiselect',
					'options'  => $product_cats_options,
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_global_discount_options_' . $i,
				),
			) );
		}
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Global_Discount();
