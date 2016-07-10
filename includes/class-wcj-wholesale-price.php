<?php
/**
 * WooCommerce Jetpack Wholesale Price
 *
 * The WooCommerce Jetpack Wholesale Price class.
 *
 * @version 2.5.4
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 * @todo    per variation;
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Wholesale_Price' ) ) :

class WCJ_Wholesale_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	function __construct() {

		$this->id         = 'wholesale_price';
		$this->short_desc = __( 'Wholesale Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce wholesale pricing depending on product quantity in cart (buy more pay less).', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-wholesale-price/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( 'yes' === get_option( 'wcj_wholesale_price_per_product_enable', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}

			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_loaded_from_session' ), PHP_INT_MAX, 1 );
			add_action( 'woocommerce_before_calculate_totals',  array( $this, 'calculate_totals' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_get_price',                array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );

			if ( 'yes' === get_option( 'wcj_wholesale_price_show_info_on_cart', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_price', array( $this, 'add_discount_info_to_cart_page' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * add_discount_info_to_cart_page.
	 *
	 * @version 2.5.0
	 */
	function add_discount_info_to_cart_page( $price_html, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['wcj_wholesale_price'] ) ) {
			$the_quantity = ( 'yes' === get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ) )
				? WC()->cart->cart_contents_count
				: $cart_item['quantity'];
			$discount     = $this->get_discount_by_quantity( $the_quantity, $cart_item['product_id'] );
			if ( 0 != $discount ) {
				$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $cart_item['product_id'] ) )
					? get_post_meta( $cart_item['product_id'], '_' . 'wcj_wholesale_price_discount_type', true )
					: get_option( 'wcj_wholesale_price_discount_type', 'percent' );
				if ( 'fixed' === $discount_type ) {
					$discount = wc_price( $discount );
				} else {
					$discount = $discount . '%';
				}
				$old_price_html = wc_price( $cart_item['wcj_wholesale_price_old'] );
				$wholesale_price_html = get_option( 'wcj_wholesale_price_show_info_on_cart_format' );
				$wholesale_price_html = str_replace(
					array( '%old_price%',   '%price%',   '%discount_value%', '%discount_percent%' ), // '%discount_percent%' is depreciated
					array( $old_price_html, $price_html, $discount,          $discount ),
					$wholesale_price_html
				);
				return $wholesale_price_html;
			}
		}

		return $price_html;
	}

	/**
	 * get_discount_by_quantity.
	 *
	 * @version 2.5.0
	 */
	private function get_discount_by_quantity( $quantity, $product_id ) {

		$max_qty_level = 1;
		$discount = 0;

		if ( wcj_is_product_wholesale_enabled_per_product( $product_id ) ) {
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number', true ) ); $i++ ) {
				$level_qty = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_min_qty_' . $i, true );
				if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
					$max_qty_level = $level_qty;
					$discount = get_post_meta( $product_id, '_' . 'wcj_wholesale_price_level_discount_' . $i, true );
				}
			}
		} else {
			for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {
				$level_qty = get_option( 'wcj_wholesale_price_level_min_qty_' . $i, PHP_INT_MAX );
				if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
					$max_qty_level = $level_qty;
					$discount = get_option( 'wcj_wholesale_price_level_discount_percent_' . $i, 0 );
				}
			}
		}

		return $discount;
	}

	/**
	 * get_wholesale_price.
	 *
	 * @version 2.5.0
	 */
	private function get_wholesale_price( $price, $quantity, $product_id ) {
		$discount = $this->get_discount_by_quantity( $quantity, $product_id );
		$discount_type = ( wcj_is_product_wholesale_enabled_per_product( $product_id ) )
			? get_post_meta( $product_id, '_' . 'wcj_wholesale_price_discount_type', true )
			: get_option( 'wcj_wholesale_price_discount_type', 'percent' );
		if ( 'percent' === $discount_type ) {
			$discount_koef = 1.0 - ( $discount / 100.0 );
			return $price * $discount_koef;
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
	 * @version 2.5.2
	 * @since   2.5.0
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

			$_product = wc_get_product( $item['product_id'] );
			if ( ! wcj_is_product_wholesale_enabled( $_product->id ) ) {
				continue;
			}

			$price = $_product->get_price();

			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$get_price_method = 'get_price_' . $tax_display_mode . 'uding_tax';
			$price_old = $_product->$get_price_method(); // used for display only

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
			if ( $the_quantity > 1 ) {
				$wholesale_price = $this->get_wholesale_price( $price, $the_quantity, $_product->id );
				if ( $wholesale_price != $price ) {
					// Setting wholesale price
					$precision = get_option( 'woocommerce_price_num_decimals', 2 );
					$wcj_wholesale_price = round( $wholesale_price, $precision );
					WC()->cart->cart_contents[ $item_key ]['data']->wcj_wholesale_price = $wcj_wholesale_price;
					WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price']       = $wcj_wholesale_price;
					WC()->cart->cart_contents[ $item_key ]['wcj_wholesale_price_old']   = $price_old;
				}
			}

		}
	}

	/**
	 * wholesale_price.
	 *
	 * @version 2.5.0
	 */
	function wholesale_price( $price, $_product ) {
		return ( wcj_is_product_wholesale_enabled( $_product->id ) && isset( $_product->wcj_wholesale_price ) ) ? $_product->wcj_wholesale_price : $price;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	function get_meta_box_options() {
		$product_id = get_the_ID();
		$options = array(
			array(
				'name'       => 'wcj_wholesale_price_per_product_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enable per Product Levels', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_wholesale_price_discount_type',
				'default'    => 'percent',
				'type'       => 'select',
				'options'    => array(
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Discount Type', 'woocommerce-jetpack' ),
			),
			array(
				'name'    => 'wcj_wholesale_price_levels_number',
				'default' => 0,
				'type'    => 'number',
				'title'   => __( 'Number of levels', 'woocommerce-jetpack' ) . ' (<em>' . __( 'Press "Update" after you change this number', 'woocommerce-jetpack' ) . '</em>)',
			),
		);
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_post_meta( $product_id, '_' . 'wcj_wholesale_price_levels_number', true ) ); $i++ ) {
			$options = array_merge( $options, array(
				/* array(
					'type'    => 'title',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i,
				), */
				array(
					'name'    => 'wcj_wholesale_price_level_min_qty_' . $i,
					'default' => 0,
					'type'    => 'number',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . __( 'Min quantity', 'woocommerce-jetpack' ),
				),
				array(
					'name'    => 'wcj_wholesale_price_level_discount_' . $i,
					'default' => 0,
					'type'    => 'number',
					'title'   => __( 'Level', 'woocommerce-jetpack' ) . ' #' . $i . ' ' . __( 'Discount', 'woocommerce-jetpack' ),
				),
			) );
		}
		return $options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.4
	 */
	function get_settings() {
		$products = apply_filters( 'wcj_get_products_filter', array() );
		$settings = array(
			array(
				'title'   => __( 'Options', 'woocommerce-jetpack' ),
				'type'    => 'title',
				'desc'    => __( 'Wholesale Price Levels Options. If you want to display prices table on frontend, use [wcj_product_wholesale_price_table] shortcode.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_options',
			),
			array(
				'title'   => __( 'Enable per Product', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_per_product_enable',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Use total cart quantity instead of product quantity', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_use_total_cart_quantity',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Apply wholesale discount only if no other cart discounts were applied', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_apply_only_if_no_other_discounts',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Show discount info on cart page', 'woocommerce-jetpack' ),
				'desc'    => __( 'Show', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_show_info_on_cart',
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'If show discount info on cart page is enabled, set format here', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_show_info_on_cart_format',
				'default' => '<del>%old_price%</del> %price%<br>You save: <span style="color:red;">%discount_value%</span>',
				'type'    => 'textarea',
				'css'     => 'width: 450px;',
			),
			array(
				'title'   => __( 'Discount Type', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_discount_type',
				'default' => 'percent',
				'type'    => 'select',
				'options' => array(
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Products to include', 'woocommerce-jetpack' ),
				'desc'    => __( 'Leave blank to include all products.', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_products_to_include',
				'default' => '',
				'type'    => 'multiselect',
				'class'   => 'chosen_select',
				'options' => $products,
			),
			array(
				'title'   => __( 'Products to exclude', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_products_to_exclude',
				'default' => '',
				'type'    => 'multiselect',
				'class'   => 'chosen_select',
				'options' => $products,
			),
			array(
				'title'   => __( 'Number of levels', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_levels_number',
				'default' => 1,
				'type'    => 'custom_number',
				'desc'    => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array('step' => '1', 'min' => '1', ) ),
				'css'     => 'width:100px;',
			),
		);
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {
			$settings[] = array(
				'title'   => __( 'Min quantity', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_min_qty_' . $i,
				'default' => 0,
				'type'    => 'number',
				'custom_attributes' => array('step' => '1', 'min' => '0', ),
			);
			$settings[] = array(
				'title'   => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Discount', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_discount_percent_' . $i, // mislabeled - should be 'wcj_wholesale_price_level_discount_'
				'default' => 0,
				'type'    => 'number',
				'custom_attributes' => array('step' => '0.0001', 'min' => '0', ),
			);
		}
		$settings[] = array(
			'type'        => 'sectionend',
			'id'          => 'wcj_wholesale_price_level_options',
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Wholesale_Price();
