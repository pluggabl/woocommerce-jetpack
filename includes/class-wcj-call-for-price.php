<?php
/**
 * Booster for WooCommerce - Module - Call for Price
 *
 * @version 3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

if ( ! class_exists( 'WCJ_Call_For_Price' ) ) :

class WCJ_Call_For_Price extends WCJ_module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 * @todo    add "per product type" labels
	 * @todo    add "per product" labels
	 */
	function __construct() {

		$this->id         = 'call_for_price';
		$this->short_desc = __( 'Call for Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Create any custom price label for all WooCommerce products with empty price.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-call-for-price';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'get_variation_prices_hash' ),                 PHP_INT_MAX, 3 );
			add_action( 'init',                                  array( $this, 'add_empty_price_hooks' ),                     PHP_INT_MAX );
			add_filter( 'woocommerce_sale_flash',                array( $this, 'hide_sales_flash' ),                          PHP_INT_MAX, 3 );
			add_action( 'admin_head',                            array( $this, 'hide_variation_price_required_placeholder' ), PHP_INT_MAX );
			add_filter( 'woocommerce_variation_is_visible',      array( $this, 'make_variation_visible_with_empty_price' ),   PHP_INT_MAX, 4 );
			add_action( 'wp_head',                               array( $this, 'hide_disabled_variation_add_to_cart_button' ) );
			if ( 'yes' === get_option( 'wcj_call_for_price_make_all_empty', 'no' ) ) {
				add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,                  array( $this, 'make_empty_price' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_variation_prices_price',          array( $this, 'make_empty_price' ), PHP_INT_MAX, 2 );
				if ( ! WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_product_variation_get_price', array( $this, 'make_empty_price' ), PHP_INT_MAX, 2 );
				}
			}
		}
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @todo    not sure if this is really needed
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$price_hash['wcj_call_for_price'] = array(
			get_option( 'wcj_call_for_price_make_all_empty', 'no' ),
		);
		return $price_hash;
	}

	/**
	 * make_variation_visible_with_empty_price.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 * @return  bool
	 */
	function make_variation_visible_with_empty_price( $visible, $_variation_id, $_id, $_product ) {
		if ( '' === $_product->get_price() ) {
			$visible = true;
			// Published == enabled checkbox
			if ( get_post_status( $_variation_id ) != 'publish' ) {
				$visible = false;
			}
		}
		return $visible;
	}

	/**
	 * hide_disabled_variation_add_to_cart_button.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function hide_disabled_variation_add_to_cart_button() {
		echo '<style>div.woocommerce-variation-add-to-cart-disabled { display: none ! important; }</style>';
	}

	/**
	 * hide_variation_price_required_placeholder.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function hide_variation_price_required_placeholder() {
		echo '<style>
			div.variable_pricing input.wc_input_price::-webkit-input-placeholder { /* WebKit browsers */
				color: transparent;
			}
			div.variable_pricing input.wc_input_price:-moz-placeholder { /* Mozilla Firefox 4 to 18 */
				color: transparent;
			}
			div.variable_pricing input.wc_input_price::-moz-placeholder { /* Mozilla Firefox 19+ */
				color: transparent;
			}
			div.variable_pricing input.wc_input_price:-ms-input-placeholder { /* Internet Explorer 10+ */
				color: transparent;
			}
		</style>';
	}

	/**
	 * make_empty_price.
	 *
	 * @version 3.2.4
	 * @since   2.5.7
	 */
	function make_empty_price( $price, $_product ) {
		return '';
	}

	/**
	 * add_empty_price_hooks.
	 *
	 * @version 3.2.4
	 */
	function add_empty_price_hooks() {
		add_filter( 'woocommerce_empty_price_html',           array( $this, 'on_empty_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_variable_empty_price_html',  array( $this, 'on_empty_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_grouped_empty_price_html',   array( $this, 'on_empty_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_variation_empty_price_html', array( $this, 'on_empty_price' ), PHP_INT_MAX, 2 ); // Only in < WC3
	}

	/**
	 * Hide "sales" icon for empty price products.
	 *
	 * @version 3.2.4
	 * @todo    recheck if we really need this
	 */
	function hide_sales_flash( $onsale_html, $post, $product ) {
		if ( 'yes' === get_option( 'wcj_call_for_price_hide_sale_sign', 'yes' ) && '' === $product->get_price() ) {
			return '';
		}
		return $onsale_html;
	}

	/**
	 * On empty price filter - return the label.
	 *
	 * @version 3.2.4
	 * @todo    `is_page()`
	 */
	function on_empty_price( $price, $_product ) {
		if ( '' !== get_option( 'wcj_call_for_price_text_variation' ) && $_product->is_type( 'variation' ) ) {
			return do_shortcode( apply_filters( 'booster_option', '<strong>Call for price</strong>', get_option( 'wcj_call_for_price_text_variation' ) ) );
		} elseif ( '' !== get_option( 'wcj_call_for_price_text' ) && is_single( get_the_ID() ) ) {
			return do_shortcode( apply_filters( 'booster_option', '<strong>Call for price</strong>', get_option( 'wcj_call_for_price_text' ) ) );
		} elseif ( '' !== get_option( 'wcj_call_for_price_text_on_related' ) && is_single() && ! is_single( get_the_ID() ) ) {
			return do_shortcode( apply_filters( 'booster_option', '<strong>Call for price</strong>', get_option( 'wcj_call_for_price_text_on_related' ) ) );
		} elseif ( '' !== get_option( 'wcj_call_for_price_text_on_archive' ) && is_archive() ) {
			return do_shortcode( apply_filters( 'booster_option', '<strong>Call for price</strong>', get_option( 'wcj_call_for_price_text_on_archive' ) ) );
		} elseif ( '' !== get_option( 'wcj_call_for_price_text_on_home' ) && is_front_page() ) {
			return do_shortcode( apply_filters( 'booster_option', '<strong>Call for price</strong>', get_option( 'wcj_call_for_price_text_on_home' ) ) );
		} else {
			return $price;
		}
	}

}

endif;

return new WCJ_Call_For_Price();
