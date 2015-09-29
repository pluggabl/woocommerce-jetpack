<?php
/**
 * WooCommerce Jetpack Wholesale Price
 *
 * The WooCommerce Jetpack Wholesale Price class.
 *
 * @version 2.2.7
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Wholesale_Price' ) ) :

class WCJ_Wholesale_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.2.6
	 */
	function __construct() {

		$this->id         = 'wholesale_price';
		$this->short_desc = __( 'Wholesale Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce wholesale pricing depending on product quantity in cart (buy more pay less).', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			/* add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 ); */

			add_filter( 'woocommerce_get_price',         array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );
			//add_filter( 'woocommerce_get_sale_price',    array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );
			//add_filter( 'woocommerce_get_regular_price', array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );

			if ( 'yes' === get_option( 'wcj_wholesale_price_show_info_on_cart', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_price',   array( $this, 'add_discount_info_to_cart_page' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * get_meta_box_options.
	 *
	 * @since 2.2.6
	 *
	function get_meta_box_options() {
		return array(
			array(
				'name'    => 'wcj_local_wholesale_price_levels_number',
				'default' => 0,
				'type'    => 'number',
				'title'   => __( 'Number', 'woocommerce-jetpack' ),
			),
		);
	}

	/**
	 * add_discount_info_to_cart_page.
	 *
	 * @version 2.2.6
	 */
	function add_discount_info_to_cart_page( $price_html, $cart_item, $cart_item_key ) {

		$_product = wc_get_product( $cart_item['product_id'] );

		remove_filter( 'woocommerce_get_price', array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );
		//$old_price_html = $_product->get_price_html();
		$old_price_html = wc_price( $_product->get_price() );
		add_filter( 'woocommerce_get_price',    array( $this, 'wholesale_price' ), PHP_INT_MAX, 2 );

		if ( $old_price_html != $price_html ) {

			$the_quantity = $this->get_wholesale_quantity( $_product );

			$discount = $this->get_discount_by_quantity( $the_quantity );

			if ( 'fixed' === get_option( 'wcj_wholesale_price_discount_type' ) ) $discount = wc_price( $discount );

			$wholesale_price_html = get_option( 'wcj_wholesale_price_show_info_on_cart_format' );
			$wholesale_price_html = str_replace( '%old_price%',        $old_price_html,   $wholesale_price_html );
			$wholesale_price_html = str_replace( '%price%',            $price_html,       $wholesale_price_html );
			$wholesale_price_html = str_replace( '%discount_percent%', $discount,         $wholesale_price_html ); //depreciated
			$wholesale_price_html = str_replace( '%discount_value%',   $discount,         $wholesale_price_html );

			return $wholesale_price_html;
		}

		return $price_html;
	}

	/**
	 * get_discount_by_quantity.
	 */
	private function get_discount_by_quantity( $quantity ) {

		$max_qty_level = 1;
		$discount = 0;

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {

			$level_qty = get_option( 'wcj_wholesale_price_level_min_qty_' . $i, PHP_INT_MAX );
			if ( $quantity >= $level_qty && $level_qty >= $max_qty_level ) {
				$max_qty_level = $level_qty;
				$discount = get_option( 'wcj_wholesale_price_level_discount_percent_' . $i, 0 );
			}
		}

		return $discount;
	}

	/**
	 * get_wholesale_price.
	 *
	 * @version 2.2.7
	 */
	private function get_wholesale_price( $price, $quantity ) {
		$discount = $this->get_discount_by_quantity( $quantity );
		if ( 'percent' === get_option( 'wcj_wholesale_price_discount_type' ) ) {
			$discount_koef = 1.0 - ( $discount / 100.0 );
			return $price * $discount_koef;
		} else {
			$discounted_price = $price - $discount;
			return ( $discounted_price >= 0 ) ? $discounted_price : 0;
		}
	}

	/**
	 * get_wholesale_quantity.
	 */
	private function get_wholesale_quantity( $_product ) {

		// Get quanitity from cart
		$the_cart = WC()->cart->get_cart();
		$quanitities = array();
		$total_quantity = 0;
		foreach ( $the_cart as $cart_item_key => $values ) {
			if ( ! isset( $quanitities[ $values['product_id'] ] ) ) $quanitities[ $values['product_id'] ] = 0;
			$quanitities[ $values['product_id'] ] += $values['quantity'];
			$total_quantity += $values['quantity'];
		}
		$product_quantity = ( isset( $quanitities[ $_product->id ] ) ) ? $quanitities[ $_product->id ] : 0;

		return ( 'yes' === get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ) ) ? $total_quantity : $product_quantity;
	}

	/**
	 * wholesale_price.
	 *
	 * @version 2.2.6
	 */
	function wholesale_price( $price, $_product ) {

		if ( ! wcj_is_product_wholesale_enabled( $_product->id ) ) return $price;

		// Show only on checkout and cart pages
		//$is_ajax = ( is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ? true : false;
		if ( ! ( is_checkout() || is_cart() /* || $is_ajax */ ) ) return $price;

		// If other discount was applied in cart...
		if ( 'yes' === get_option( 'wcj_wholesale_price_apply_only_if_no_other_discounts', 'no' ) ) {
			if ( WC()->cart->get_total_discount() > 0 || sizeof( WC()->cart->applied_coupons ) > 0 ) return $price;
		}

		// Maybe set wholesale price
		$the_quantity = $this->get_wholesale_quantity( $_product );
		if ( $the_quantity > 1 ) {
			$wholesale_price = $this->get_wholesale_price( $price, $the_quantity );
			if ( $wholesale_price != $price ) {
				// Setting wholesale price
				$precision = get_option( 'woocommerce_price_num_decimals', 2 );
				return round( $wholesale_price, $precision );
			}
		}

		// No changes to the price
		return $price;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$products = apply_filters( 'wcj_get_products_filter', array() );

		$settings = array(

			array(
				'title' => __( 'Wholesale Price Levels Options', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'desc'  => __( 'Wholesale Price Levels Options. If you want to display prices table on frontend, use [wcj_product_wholesale_price_table] shortcode.', 'woocommerce-jetpack' ),
				'id'    => 'wcj_wholesale_price_level_options'
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
				'default' => '<del>%old_price%</del> %price%<br>You save: <span style="color:red;">%discount_value%%</span>',
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
				//'css'     => 'width: 450px;',
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
				'css'	   => 'width:100px;',
			),
		);

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {

			$settings[] = array(
				'title'   => __( 'Min quantity', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_min_qty_' . $i,
				'default' => 0,
				'type'    => 'number',
				//'css'     => 'width:50%;min-width:300px;height:100px;',
				'custom_attributes' => array('step' => '1', 'min' => '0', ),
			);
			$settings[] = array(
				'title'   => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Discount', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_discount_percent_' . $i,
				'default' => 0,
				'type'    => 'number',
				//'css'     => 'width:50%;min-width:300px;height:100px;',
				'custom_attributes' => array('step' => '0.0001', 'min' => '0', ),
			);
			/* $settings[] = array(
				'title'   => __( 'Discount (fixed)', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Discount (fixed)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wholesale_price_level_discount_fixed_' . $i,
				'default' => 0,
				'type'    => 'number',
				//'css'     => 'width:50%;min-width:300px;height:100px;',
				'custom_attributes' => array('step' => '0.0001', 'min' => '0', ),
			); */
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_wholesale_price_level_options'
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Wholesale_Price();
