<?php
/**
 * Booster for WooCommerce - Module - Multicurrency Product Base Price
 *
 * @version 3.7.0
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Multicurrency_Base_Price' ) ) :

class WCJ_Multicurrency_Base_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.7.0
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'multicurrency_base_price';
		$this->short_desc = __( 'Multicurrency Product Base Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enter prices for products in different currencies.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-multicurrency-product-base-price';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_on_product_edit' ), PHP_INT_MAX, 2 );

			$this->do_convert_in_back_end = ( 'yes' === get_option( 'wcj_multicurrency_base_price_do_convert_in_back_end', 'no' ) );

			if ( $this->do_convert_in_back_end || wcj_is_frontend() ) {
				wcj_add_change_price_hooks( $this, PHP_INT_MAX - 10, false );
			}

		}
	}

	/**
	 * change_price_grouped.
	 *
	 * @version 2.7.0
	 * @since   2.5.0
	 */
	function change_price_grouped( $price, $qty, $_product ) {
		if ( $_product->is_type( 'grouped' ) ) {
			foreach ( $_product->get_children() as $child_id ) {
				$the_price = get_post_meta( $child_id, '_price', true );
				$the_product = wc_get_product( $child_id );
				$the_price = wcj_get_product_display_price( $the_product, $the_price, 1 );
				if ( $the_price == $price ) {
					return $this->change_price( $price, $the_product );
				}
			}
		}
		return $price;
	}

	/**
	 * change_price.
	 *
	 * @version 2.7.0
	 * @since   2.4.8
	 */
	function change_price( $price, $_product ) {
		return wcj_price_by_product_base_currency( $price, wcj_get_product_id_or_variation_parent_id( $_product ) );
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 3.5.0
	 * @since   2.4.8
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$multicurrency_base_price_currency = get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product, true ), '_' . 'wcj_multicurrency_base_price_currency', true );
		$price_hash['wcj_multicurrency_base_price'] = array(
			'currency'           => $multicurrency_base_price_currency,
			'exchange_rate'      => wcj_get_currency_exchange_rate_product_base_currency( $multicurrency_base_price_currency ),
			'rounding'           => get_option( 'wcj_multicurrency_base_price_round_enabled', 'no' ),
			'rounding_precision' => get_option( 'wcj_multicurrency_base_price_round_precision', get_option( 'woocommerce_price_num_decimals' ) ),
			'save_prices'        => get_option( 'wcj_multicurrency_base_price_save_prices', 'no' ),
		);
		return $price_hash;
	}

	/**
	 * change_currency_symbol_on_product_edit.
	 *
	 * @version 3.7.0
	 * @since   2.4.8
	 */
	function change_currency_symbol_on_product_edit( $currency_symbol, $currency ) {
		if ( is_admin() ) {
			global $pagenow;
			if (
				( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) ||                                          // admin product edit page
				( ! $this->do_convert_in_back_end && 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) // admin products list
			) {
				$multicurrency_base_price_currency = get_post_meta( get_the_ID(), '_' . 'wcj_multicurrency_base_price_currency', true );
				if ( '' != $multicurrency_base_price_currency ) {
					return wcj_get_currency_symbol( $multicurrency_base_price_currency );
				}
			}
		}
		return $currency_symbol;
	}

}

endif;

return new WCJ_Multicurrency_Base_Price();
