<?php
/**
 * WooCommerce Jetpack Product
 *
 * The WooCommerce Jetpack Product class.
 *
 * @version 2.4.5
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product' ) ) :

class WCJ_Product {

	public $id;
	public $product;

	/**
	 * Constructor.
	 */
	public function __construct( $product_id ) {
		$this->id      = $product_id;
//		$this->product = wc_get_product( $this->id );
	}

	/**
	 * get_purchase_price.
	 *
	 * @version 2.4.5
	 */
	public function get_purchase_price() {
		$purchase_price = 0;
		if ( 'yes' === get_option( 'wcj_purchase_price_enabled', 'yes' ) ) {
			$purchase_price += get_post_meta( $this->id, '_' . 'wcj_purchase_price' , true );
		}
		if ( 'yes' === get_option( 'wcj_purchase_price_extra_enabled', 'yes' ) ) {
			$purchase_price += get_post_meta( $this->id, '_' . 'wcj_purchase_price_extra', true );
		}
		if ( 'yes' === get_option( 'wcj_purchase_price_affiliate_commission_enabled', 'no' ) ) {
			$purchase_price += get_post_meta( $this->id, '_' . 'wcj_purchase_price_affiliate_commission', true );
		}
		return apply_filters( 'wcj_get_product_purchase_price', $purchase_price, $this->id );
	}
}

endif;
