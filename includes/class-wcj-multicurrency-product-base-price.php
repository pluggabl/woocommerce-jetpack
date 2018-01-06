<?php
/**
 * Booster for WooCommerce - Module - Multicurrency Product Base Price
 *
 * @version 2.8.0
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Multicurrency_Base_Price' ) ) :

class WCJ_Multicurrency_Base_Price extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.4.8
	 */
	function __construct() {

		$this->id         = 'multicurrency_base_price';
		$this->short_desc = __( 'Multicurrency Product Base Price', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enter prices for WooCommerce products in different currencies.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-multicurrency-product-base-price';
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_on_product_edit' ), PHP_INT_MAX, 2 );

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				wcj_add_change_price_hooks( $this, PHP_INT_MAX - 10, false );
			}

			/* if ( is_admin() ) {
				include_once( 'reports/class-wcj-currency-reports.php' );
			} */
		}
	}

	/**
	 * get_currency_exchange_rate.
	 *
	 * @version 2.5.6
	 */
	function get_currency_exchange_rate( $currency_code ) {
		/*
		$currency_exchange_rate = 1;
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( $currency_code === get_option( 'wcj_multicurrency_base_price_currency_' . $i ) ) {
				$currency_exchange_rate = get_option( 'wcj_multicurrency_base_price_exchange_rate_' . $i );
				break;
			}
		}
		return $currency_exchange_rate;
		*/
		return wcj_get_currency_exchange_rate_product_base_currency( $currency_code );
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
	 */
	function change_price( $price, $_product ) {
		return wcj_price_by_product_base_currency( $price, wcj_get_product_id_or_variation_parent_id( $_product ) );
	}

	/**
	 * get_variation_prices_hash.
	 *
	 * @version 2.7.0
	 */
	function get_variation_prices_hash( $price_hash, $_product, $display ) {
		$multicurrency_base_price_currency = get_post_meta( wcj_get_product_id_or_variation_parent_id( $_product, true ), '_' . 'wcj_multicurrency_base_price_currency', true );
		$currency_exchange_rate = $this->get_currency_exchange_rate( $multicurrency_base_price_currency );
		$price_hash['wcj_base_currency'] = array(
			$multicurrency_base_price_currency,
			$currency_exchange_rate,
		);
		return $price_hash;
	}

	/**
	 * change_currency_symbol_on_product_edit.
	 */
	function change_currency_symbol_on_product_edit( $currency_symbol, $currency ) {
		if ( is_admin() ) {
			global $pagenow;
			if ( 'post.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
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
