<?php
/**
 * Booster for WooCommerce Product
 *
 * @version 2.4.8
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
	function __construct( $product_id ) {
		$this->id      = $product_id;
//		$this->product = wc_get_product( $this->id );
	}

	/**
	 * get_purchase_price.
	 *
	 * @version 2.4.8
	 */
	function get_purchase_price() {
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
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_purchase_data_custom_price_fields_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( '' == get_option( 'wcj_purchase_data_custom_price_field_name_' . $i, '' ) ) {
				continue;
			}
			$meta_value = get_post_meta( $this->id, '_' . 'wcj_purchase_price_custom_field_' . $i, true );
			if ( '' != $meta_value ) {
				$the_type = get_option( 'wcj_purchase_data_custom_price_field_type_' . $i, 'fixed' );
				$purchase_price += ( 'fixed' === $the_type ) ? $meta_value : $purchase_price * $meta_value / 100.0;
			}
		}
		return apply_filters( 'wcj_get_product_purchase_price', $purchase_price, $this->id );
	}
}

endif;
