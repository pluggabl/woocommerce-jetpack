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
		$this->desc       = __( 'Let your WooCommerce store customers enter price for the product manually.', 'woocommerce-jetpack' );
		$this->link       = '';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',                         array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product',                      array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price',                  array( $this, 'get_dynamic_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_price_html',             array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_variation_price_html',   array( $this, 'hide_original_price' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_is_sold_individually',       array( $this, 'hide_quantity_input_field' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_is_purchasable',             array( $this, 'is_purchasable' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_add_to_cart_url',    array( $this, 'add_to_cart_url' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_add_to_cart_text',   array( $this, 'add_to_cart_text' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_before_add_to_cart_button',  array( $this, 'add_dynamic_price_input_field_to_frontend' ), PHP_INT_MAX );
			add_filter( 'woocommerce_add_to_cart_validation',     array( $this, 'validate_dynamic_price_on_add_to_cart' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_add_cart_item_data',         array( $this, 'add_dynamic_price_to_cart_item_data' ), PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_add_cart_item',              array( $this, 'add_dynamic_price_to_cart_item' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_dynamic_price_from_session' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * is_dynamic_price_product.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function is_dynamic_price_product( $_product ) {
		return ( 'yes' === get_post_meta( $_product->id, '_' . 'wcj_product_dynamic_price_enabled', true ) ) ? true : false;
	}

	/**
	 * is_purchasable.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function is_purchasable( $purchasable, $_product ) {
		if ( $this->is_dynamic_price_product( $_product ) ) {
			$purchasable = true;

			// Products must exist of course
			if ( ! $_product->exists() ) {
				$purchasable = false;

			// Other products types need a price to be set
			/* } elseif ( $_product->get_price() === '' ) {
				$purchasable = false; */

			// Check the product is published
			} elseif ( $_product->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $_product->id ) ) {
				$purchasable = false;
			}
		}
		return $purchasable;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_dynamic_price_enabled',
				'default'    => 'no',
				'type'       => 'select',
				'options'    => array(
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
					'no'  => __( 'No', 'woocommerce-jetpack' ),
				),
				'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
			),
			array(
				'name'       => 'wcj_product_dynamic_price_default_price',
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Default Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
			array(
				'name'       => 'wcj_product_dynamic_price_min_price',
				'default'    => 1,
				'type'       => 'number',
				'title'      => __( 'Min Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
			array(
				'name'       => 'wcj_product_dynamic_price_max_price',
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Max Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			),
		);
		return $options;
	}

	/**
	 * add_to_cart_text.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_to_cart_text( $text, $_product ) {
		return ( $this->is_dynamic_price_product( $_product ) ) ? __( 'Read more', 'woocommerce' ) : $text;
	}

	/**
	 * add_to_cart_url.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_to_cart_url( $url, $_product ) {
		return ( $this->is_dynamic_price_product( $_product ) ) ? get_permalink( $_product->id ) : $url;
	}

	/**
	 * hide_quantity_input_field.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function hide_quantity_input_field( $return, $_product ) {
		return ( $this->is_dynamic_price_product( $_product ) ) ? true : $return;
	}

	/**
	 * hide_original_price.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function hide_original_price( $price, $_product ) {
		return ( $this->is_dynamic_price_product( $_product ) ) ? '' : $price;
	}

	/**
	 * get_dynamic_price.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_dynamic_price( $price, $_product ) {
		return ( $this->is_dynamic_price_product( $_product ) && isset( $_product->wcj_dynamic_price ) ) ? $_product->wcj_dynamic_price : $price;
	}

	/**
	 * validate_dynamic_price_on_add_to_cart.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function validate_dynamic_price_on_add_to_cart( $passed, $product_id ) {
		$the_product = wc_get_product( $product_id );
		if ( $this->is_dynamic_price_product( $the_product ) ) {
			$min_price = get_post_meta( $product_id, '_' . 'wcj_product_dynamic_price_min_price', true );
			$max_price = get_post_meta( $product_id, '_' . 'wcj_product_dynamic_price_max_price', true );
			if ( $min_price > 0 ) {
				if ( ! isset( $_POST['wcj_dynamic_price'] ) || '' === $_POST['wcj_dynamic_price'] ) {
					wc_add_notice( __( 'Price is required!', 'woocommerce-jetpack' ), 'error' );
					return false;
				}
				if ( $_POST['wcj_dynamic_price'] < $min_price ) {
					wc_add_notice( __( 'Entered price is to small!', 'woocommerce-jetpack' ), 'error' );
					return false;
				}
			}
			if ( $max_price > 0 ) {
				if ( isset( $_POST['wcj_dynamic_price'] ) && $_POST['wcj_dynamic_price'] > $max_price ) {
					wc_add_notice( __( 'Entered price is to big!', 'woocommerce-jetpack' ), 'error' );
					return false;
				}
			}
		}
		return $passed;
	}

	/**
	 * get_cart_item_dynamic_price_from_session.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function get_cart_item_dynamic_price_from_session( $item, $values, $key ) {
		if ( array_key_exists( 'wcj_dynamic_price', $values ) ) {
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
		if ( isset( $_POST['wcj_dynamic_price'] ) ) {
			$cart_item_data['wcj_dynamic_price'] = $_POST['wcj_dynamic_price'];
		}
		return $cart_item_data;
	}

	/**
	 * add_dynamic_price_to_cart_item.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_dynamic_price_to_cart_item( $cart_item_data, $cart_item_key ) {
		if ( isset( $cart_item_data['wcj_dynamic_price'] ) ) {
			$cart_item_data['data']->wcj_dynamic_price = $cart_item_data['wcj_dynamic_price'];
		}
		return $cart_item_data;
	}

	/**
	 * add_dynamic_price_input_field_to_frontend.
	 *
	 * @version 2.4.8
	 * @since   2.4.8
	 */
	function add_dynamic_price_input_field_to_frontend() {
		$the_product = wc_get_product();
		if ( $this->is_dynamic_price_product( $the_product ) ) {
			$title = __( 'Your offer', 'woocommerce-jetpack' );
//			$placeholder = $the_product->get_price();
			$value = ( isset( $_POST['wcj_dynamic_price'] ) ) ?
				$_POST['wcj_dynamic_price'] :
				get_post_meta( $the_product->id, '_' . 'wcj_product_dynamic_price_default_price', true );
			$custom_attributes = '';
			$wc_price_decimals = wc_get_price_decimals();
			if ( $wc_price_decimals > 0 ) {
				$custom_attributes .= sprintf( 'step="0.%0' . ( $wc_price_decimals ) . 'd" ', 1 );
			}
			echo
				/* '<div>' . */ '<label for="wcj_dynamic_price">' . $title . '</label>' . ' '
				. '<input '
					. 'type="number" '
					. 'class="text" '
					. 'style="width:75px;text-align:center;" '
					. 'name="wcj_dynamic_price" '
					. 'id="wcj_dynamic_price" '
//					. 'placeholder="' . $placeholder . '" '
					. 'value="' . $value . '" '
					. $custom_attributes
				. '>'
				. ' ' . get_woocommerce_currency_symbol() /* . '</div>' */;
		}
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
