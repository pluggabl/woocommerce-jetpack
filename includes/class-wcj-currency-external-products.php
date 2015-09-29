<?php
/**
 * WooCommerce Jetpack Currency for External Products
 *
 * The WooCommerce Jetpack Currency for External Products class.
 *
 * @version 2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Currency_External_Products' ) ) :

class WCJ_Currency_External_Products extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.0
	 */
	public function __construct() {

		$this->id         = 'currency_external_products';
		$this->short_desc = __( 'Currency for External Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set different currency for external WooCommerce products.', 'woocommerce-jetpack' );
		parent::__construct();

		$currencies = include( 'currencies/wcj-currencies.php' );
		foreach( $currencies as $data ) {
			$this->currency_symbols[           $data['code'] ] = $data['symbol'];
			$this->currency_names_and_symbols[ $data['code'] ] = $data['name'] . ' (' . $data['symbol'] . ')';
		}

		if ( $this->is_enabled() ) {
			if ( '' != get_option( 'wcj_currency_external_products_symbol' ) ) {
				add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), 100, 2 );
			}
		}
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.3.0
	 */
	public function change_currency_symbol( $currency_symbol, $currency ) {
		global $product;
		if ( is_object( $product ) && isset( $product->product_type ) && 'external' === $product->product_type ) {
			return $this->currency_symbols[ get_option( 'wcj_currency_external_products_symbol' ) ];
		}
		return $currency_symbol;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.3.0
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
				'title'    => __( 'Currency Symbol', 'woocommerce-jetpack' ),
				'desc'     => __( 'Set currency symbol for all external products.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Set currency symbol for all external products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_currency_external_products_symbol',
				'default'  => 'EUR',
				'type'     => 'select',
				'options'  => $this->currency_names_and_symbols,
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_currency_external_products_options',
			),
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Currency_External_Products();
