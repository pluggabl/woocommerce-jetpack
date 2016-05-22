<?php
/**
 * WooCommerce Jetpack Currency for External Products
 *
 * The WooCommerce Jetpack Currency for External Products class.
 *
 * @version 2.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currency_External_Products' ) ) :

class WCJ_Currency_External_Products extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	public function __construct() {

		$this->id         = 'currency_external_products';
		$this->short_desc = __( 'Currency for External Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set different currency for external WooCommerce products.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-currency-for-external-products/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( '' != get_option( 'wcj_currency_external_products_symbol' ) ) {
				add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_currency',        array( $this, 'change_currency_code' ),   PHP_INT_MAX, 1 );
			}
		}
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 */
	public function change_currency_code( $currency ) {
		global $product;
		if ( is_object( $product ) && isset( $product->product_type ) && 'external' === $product->product_type ) {
			return get_option( 'wcj_currency_external_products_symbol' );
		}
		return $currency;
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.4.4
	 */
	public function change_currency_symbol( $currency_symbol, $currency ) {
		global $product;
		if ( is_object( $product ) && isset( $product->product_type ) && 'external' === $product->product_type ) {
			return wcj_get_currency_symbol( get_option( 'wcj_currency_external_products_symbol' ) );
		}
		return $currency_symbol;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.4
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Currency for External Products Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_currency_external_products_options',
			),
			array(
				'title'    => __( 'Currency', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Set currency for all external products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_currency_external_products_symbol', // mislabeled, should be 'wcj_currency_external_products_code'
				'default'  => 'EUR',
				'type'     => 'select',
				'options'  => wcj_get_currencies_names_and_symbols(),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_currency_external_products_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Currency_External_Products();
