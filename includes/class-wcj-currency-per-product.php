<?php
/**
 * WooCommerce Jetpack Currency per Product
 *
 * The WooCommerce Jetpack Currency per Product class.
 *
 * @version 2.5.2
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Currency_Per_Product' ) ) :

class WCJ_Currency_Per_Product extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function __construct() {

		$this->id         = 'currency_per_product';
		$this->short_desc = __( 'Currency per Product', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display prices for WooCommerce products in different currencies.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-currency-per-product/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {

			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

			//if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

				// Currency code and symbol
				add_filter( 'woocommerce_currency_symbol',                array( $this, 'change_currency_symbol' ),     PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_currency',                       array( $this, 'change_currency_code' ),       PHP_INT_MAX );

				// Add to cart
				add_filter( 'woocommerce_add_cart_item_data',             array( $this, 'add_cart_item_data' ),         PHP_INT_MAX, 3 );
				add_filter( 'woocommerce_add_cart_item',                  array( $this, 'add_cart_item' ),              PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session',     array( $this, 'get_cart_item_from_session' ), PHP_INT_MAX, 3 );

				// Price
				add_filter( 'woocommerce_get_price',                      array( $this, 'change_price' ),               PHP_INT_MAX, 2 );

				// Grouped
				add_filter( 'woocommerce_grouped_price_html',             array( $this, 'grouped_price_html' ),         PHP_INT_MAX, 2 );

			//}
		}
	}

	/**
	 * grouped_price_html.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function grouped_price_html( $price_html, $_product ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$child_prices     = array();

		foreach ( $_product->get_children() as $child_id )
			$child_prices[ $child_id ] = get_post_meta( $child_id, '_price', true );

//		$child_prices     = array_unique( $child_prices );
		$get_price_method = 'get_price_' . $tax_display_mode . 'uding_tax';

		if ( ! empty( $child_prices ) ) {
			/* $min_price = min( $child_prices );
			$max_price = max( $child_prices );
			$min_price_id = min( array_keys( $child_prices, min( $child_prices ) ) );
			$max_price_id = max( array_keys( $child_prices, max( $child_prices ) ) ); */
			asort( $child_prices );
			$min_price = current( $child_prices );
			$min_price_id = key( $child_prices );
			end( $child_prices );
			$max_price = current( $child_prices );
			$max_price_id = key( $child_prices );
			$min_currency_per_product_currency = get_post_meta( $min_price_id, '_' . 'wcj_currency_per_product_currency', true );
			$max_currency_per_product_currency = get_post_meta( $max_price_id, '_' . 'wcj_currency_per_product_currency', true );
		} else {
			$min_price = '';
			$max_price = '';
		}

		if ( $min_price ) {
			if ( $min_price == $max_price && $min_currency_per_product_currency === $max_currency_per_product_currency ) {
				$display_price = wc_price( $_product->$get_price_method( 1, $min_price ), array( 'currency' => $min_currency_per_product_currency ) );
			} else {
				$from          = wc_price( $_product->$get_price_method( 1, $min_price ), array( 'currency' => $min_currency_per_product_currency ) );
				$to            = wc_price( $_product->$get_price_method( 1, $max_price ), array( 'currency' => $max_currency_per_product_currency ) );
				$display_price = sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), $from, $to );
			}

			$new_price_html = $display_price . $_product->get_price_suffix();

			return $new_price_html;
		}

		return $price_html;
	}

	/**
	 * get_currency_exchange_rate.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_currency_exchange_rate( $currency_code ) {
		$currency_exchange_rate = 1;
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( $currency_code === get_option( 'wcj_currency_per_product_currency_' . $i ) ) {
				$currency_exchange_rate = 1 / get_option( 'wcj_currency_per_product_exchange_rate_' . $i );
				break;
			}
		}
		return $currency_exchange_rate;
	}

	/**
	 * change_price.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function change_price( $price, $_product ) {
		if ( isset( $_product->wcj_currency_per_product ) ) {
			$exchange_rate = $this->get_currency_exchange_rate( $_product->wcj_currency_per_product );
			return $price * $exchange_rate;
		}
		return $price;
	}

	/**
	 * get_cart_item_from_session.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_cart_item_from_session( $item, $values, $key ) {
		if ( array_key_exists( 'wcj_currency_per_product', $values ) ) {
			$item['data']->wcj_currency_per_product = $values['wcj_currency_per_product'];
		}
		return $item;
	}

	/**
	 * add_cart_item_data.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$currency_per_product_currency = get_post_meta( $product_id, '_' . 'wcj_currency_per_product_currency', true );
		if ( '' != $currency_per_product_currency ) {
			$cart_item_data['wcj_currency_per_product'] = $currency_per_product_currency;
		}
		return $cart_item_data;
	}

	/**
	 * add_cart_item.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_cart_item( $cart_item_data, $cart_item_key ) {
		if ( isset( $cart_item_data['wcj_currency_per_product'] ) ) {
			$cart_item_data['data']->wcj_currency_per_product = $cart_item_data['wcj_currency_per_product'];
		}
		return $cart_item_data;
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	public function change_currency_code( $currency ) {
		$the_ID = get_the_ID();
		if ( 0 != $the_ID && 'product' === get_post_type( $the_ID ) ) {
			$currency_per_product_currency = get_post_meta( $the_ID, '_' . 'wcj_currency_per_product_currency', true );
			if ( '' != $currency_per_product_currency ) {
				return $currency_per_product_currency;
			}
		}
		return $currency;
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function change_currency_symbol( $currency_symbol, $currency ) {
		$the_ID = get_the_ID();
		if ( 0 != $the_ID && 'product' === get_post_type( $the_ID ) ) {
			$currency_per_product_currency = get_post_meta( $the_ID, '_' . 'wcj_currency_per_product_currency', true );
			if ( '' != $currency_per_product_currency ) {
				return wcj_get_currency_symbol( $currency_per_product_currency );
			}
		}
		return $currency_symbol;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_meta_box_options() {
		$currency_codes = array();
		$currency_codes[ get_woocommerce_currency() ] = get_woocommerce_currency();
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$currency_codes[ get_option( 'wcj_currency_per_product_currency_' . $i ) ] = get_option( 'wcj_currency_per_product_currency_' . $i );
		}
		$options = array(
			array(
				'name'       => 'wcj_currency_per_product_currency',
				'default'    => get_woocommerce_currency(),
				'type'       => 'select',
				'title'      => __( 'Product Currency', 'woocommerce-jetpack' ),
				'options'    => $currency_codes,
			),
		);
		return $options;
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_settings_hook() {
		add_filter( 'wcj_currency_per_product_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_currency_per_product_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_settings() {
		$currency_from = get_woocommerce_currency();
		$all_currencies = wcj_get_currencies_names_and_symbols();
		foreach ( $all_currencies as $currency_key => $currency_name ) {
			if ( $currency_from == $currency_key ) {
				unset( $all_currencies[ $currency_key ] );
			}
		}
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_currency_per_product_options',
			),
			array(
				'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
				'id'       => 'wcj_currency_per_product_exchange_rate_update',
				'default'  => 'manual',
				'type'     => 'select',
				'options'  => array(
					'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
					'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
				),
				'desc'     => ( '' == apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ) ) ?
					__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
					:
					apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_currency_per_product_options',
			),
			array(
				'title'    => __( 'Currencies Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_currency_per_product_currencies_options',
			),
			array(
				'title'    => __( 'Total Currencies', 'woocommerce-jetpack' ),
				'id'       => 'wcj_currency_per_product_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min'  => '1', )
				),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$currency_to = get_option( 'wcj_currency_per_product_currency_' . $i, $currency_from );
			$custom_attributes = array(
				'currency_from'        => $currency_from,
				'currency_to'          => $currency_to,
				'multiply_by_field_id' => 'wcj_currency_per_product_exchange_rate_' . $i,
			);
			if ( $currency_from == $currency_to ) {
				$custom_attributes['disabled'] = 'disabled';
			}
			$settings = array_merge( $settings, array(
				array(
					'title'    => __( 'Currency', 'woocommerce-jetpack' ) . ' #' . $i,
					'id'       => 'wcj_currency_per_product_currency_' . $i,
					'default'  => $currency_from,
					'type'     => 'select',
					'options'  => $all_currencies,
					'css'      => 'width:250px;',
				),
				array(
					'title'                    => '',
					'id'                       => 'wcj_currency_per_product_exchange_rate_' . $i,
					'default'                  => 1,
					'type'                     => 'exchange_rate',
					'custom_attributes'        => array( 'step' => '0.000001', 'min'  => '0', ),
					'custom_attributes_button' => $custom_attributes,
					'css'                      => 'width:100px;',
					'value'                    => $currency_from . '/' . $currency_to,
					'value_title'              => sprintf( __( 'Grab %s rate from Yahoo.com', 'woocommerce-jetpack' ), $currency_from . '/' . $currency_to ),
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_currency_per_product_currencies_options',
			),
		) );
		return $settings;
	}
}

endif;

return new WCJ_Currency_Per_Product();
