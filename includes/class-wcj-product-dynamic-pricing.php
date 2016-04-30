<?php
/**
 * WooCommerce Jetpack Product Dynamic Pricing
 *
 * The WooCommerce Jetpack Product Dynamic Pricing class.
 *
 * @version 2.4.8
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_Dynamic_Pricing' ) ) :

class WCJ_Product_Dynamic_Pricing extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'product_dynamic_pricing';
		$this->short_desc = __( 'Product Dynamic Pricing', 'woocommerce-jetpack' );
		$this->desc       = __( 'Product Dynamic Pricing.', 'woocommerce-jetpack' );
		$this->link       = '';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_get_price',                  array( $this, 'get_dynamic_price' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_before_add_to_cart_button',  array( $this, 'add_dynamic_price_input_field_to_frontend' ), PHP_INT_MAX );
//			add_filter( 'woocommerce_add_to_cart_validation',     array( $this, 'validate_dynamic_price_on_add_to_cart' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_add_cart_item_data',         array( $this, 'add_dynamic_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_add_cart_item',              array( $this, 'add_dynamic_price_to_cart_item' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_dynamic_price_from_session' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * get_dynamic_price.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_dynamic_price( $price, $_product ) {
		return ( isset( $_product->wcj_dynamic_price ) ) ? $_product->wcj_dynamic_price : $price;
	}

	/**
	 * get_cart_item_dynamic_price_from_session.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	public function get_cart_item_dynamic_price_from_session( $item, $values, $key ) {
		if ( array_key_exists( 'wcj_dynamic_price', $values ) ) {
			$item['wcj_dynamic_price']       = $values['wcj_dynamic_price']; // TODO? - do I need this?
			$item['data']->wcj_dynamic_price = $values['wcj_dynamic_price'];
		}
		return $item;
	}

	/**
	 * add_dynamic_price_to_cart_item_data.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_dynamic_price_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$cart_item_data['wcj_dynamic_price'] = $_POST['wcj_dynamic_price'];
		return $cart_item_data;
	}

	/**
	 * add_dynamic_price_to_cart_item.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_dynamic_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		$cart_item_data['data']->wcj_dynamic_price = $cart_item_data['wcj_dynamic_price'];
		return $cart_item_data;
	}

	/**
	 * add_dynamic_price_input_field_to_frontend.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_dynamic_price_input_field_to_frontend() {
		$title = '';             // TODO
		$placeholder = '';       // TODO
		$custom_attributes = ''; // TODO
		echo '<p>' . $title . '<input type="number" name="wcj_dynamic_price" placeholder="' . $placeholder . '"' . $custom_attributes . '>' . '</p>';
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_settings() {
		$settings = array();
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_Dynamic_Pricing();
