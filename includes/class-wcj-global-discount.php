<?php
/**
 * WooCommerce Jetpack Global Discount
 *
 * The WooCommerce Jetpack Global Discount class.
 *
 * @version 2.5.7
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 * @todo    products and products cats/tags to include/exclude; multiple groups; regular price coefficient; fee instead of discount;
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
	function calculate_price( $price, $coefficient ) {
		$return_price = ( 'percent' === get_option( 'wcj_global_discount_sale_coefficient_type_1', 'percent' ) ) ?
			( $price + $price * ( $coefficient / 100 ) ) :
			( $price + $coefficient );
		return ( $return_price >= 0 ) ? $return_price : 0;
	}

	/**
	 * add_global_discount_sale_price.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_global_discount_sale_price( $price, $_product ) {
		$coefficient = get_option( 'wcj_global_discount_sale_coefficient_1', 0 );
		if ( 0 != $coefficient ) {
			if ( 0 == $price ) {
				// The product is currently not on sale
				if ( 'only_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_1', 'all' ) ) {
					return $price; // no changes
				} else {
					$price = $_product->get_regular_price();
				}
			}
			return $this->calculate_price( $price, $coefficient );
		}
		return $price; // no changes
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
		$coefficient = get_option( 'wcj_global_discount_sale_coefficient_1', 0 );
		if ( 0 != $coefficient ) {
			if ( 0 == $_product->get_sale_price() && 'only_on_sale' === get_option( 'wcj_global_discount_sale_product_scope_1', 'all' ) ) {
				return $price; // no changes
			}
			return $this->calculate_price( $price, $coefficient );
		}
		return $price; // no changes
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$price_hash['wcj_global_discount_price_hash'] = array(
			get_option( 'wcj_global_discount_sale_coefficient_type_1', 'percent' ),
			get_option( 'wcj_global_discount_sale_coefficient_1', 0 ),
			get_option( 'wcj_global_discount_sale_product_scope_1', 'all' ),
		);
		return $price_hash;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_global_discount_options',
			),
			array(
				'title'    => __( 'Global Discount Sale Coefficient Type', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_sale_coefficient_type_1',
				'default'  => 'percent',
				'type'     => 'select',
				'options'  => array(
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Global Discount Sale Coefficient', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_sale_coefficient_1',
				'default'  => 0,
				'type'     => 'number',
				'custom_attributes' => array( /* 'min' => 0, */ 'max' => 0, 'step' => 0.0001 ), // todo
			),
			array(
				'title'    => __( 'Product Scope', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_sale_product_scope_1',
				'default'  => 'all',
				'type'     => 'select',
				'options'  => array(
					'all'          => __( 'All products', 'woocommerce-jetpack' ),
					'only_on_sale' => __( 'Only products that are already on sale', 'woocommerce-jetpack' ),
				),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_global_discount_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Global_Discount();
