<?php
/**
 * WooCommerce Jetpack Product
 *
 * The WooCommerce Jetpack Product class.
 *
 * @version 2.2.0
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
		$this->id = $product_id;
		//$this->product = wc_get_product( $this->id );
    }

    /**
     * get_purchase_price.
     */
	public function get_purchase_price() {
		/* $current_post_id = $this->id;//get_the_ID();
		$option_name = 'wcj_purchase_price';
		if ( ! ( $purchase_price = get_post_meta( $current_post_id, '_' . $option_name, true ) ) ) {
			$purchase_price = 0;
		} */
		$purchase_price = 0;
		$purchase_price += get_post_meta( $this->id, '_' . 'wcj_purchase_price', true );
		$purchase_price += get_post_meta( $this->id, '_' . 'wcj_purchase_price_extra', true );
		return $purchase_price;
	}
}

endif;
