<?php
/**
 * Booster for WooCommerce - Module - Currency for External Products
 *
 * @version 3.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Currency_External_Products' ) ) :
	/**
	 * WCJ_Currency_External_Products.
	 */
	class WCJ_Currency_External_Products extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.9.0
		 */
		public function __construct() {

			$this->id         = 'currency_external_products';
			$this->short_desc = __( 'Currency for External Products', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set different currency for external products.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-currency-for-external-products';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( '' !== wcj_get_option( 'wcj_currency_external_products_symbol', 'EUR' ) ) {
					add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), PHP_INT_MAX, 1 );
				}
			}
		}

		/**
		 * Change_currency_code.
		 *
		 * @version 2.7.0
		 * @since   2.4.4
		 * @param string | int $currency defines the currency.
		 */
		public function change_currency_code( $currency ) {
			global $product;
			if ( is_object( $product ) && $product->is_type( 'external' ) ) {
				return wcj_get_option( 'wcj_currency_external_products_symbol', 'EUR' );
			}
			return $currency;
		}

	}

endif;

return new WCJ_Currency_External_Products();
