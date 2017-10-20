<?php
/**
 * Booster for WooCommerce - Module - Gateways Currency
 *
 * @version 3.2.0
 * @since   2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Currency' ) ) :

class WCJ_Payment_Gateways_Currency extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_currency';
		$this->short_desc = __( 'Gateways Currency Converter', 'woocommerce-jetpack' );
		$this->desc       = __( 'Currency converter for WooCommerce payment gateways.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-currency-converter';
		parent::__construct();

		if ( $this->is_enabled() ) {
//			add_action( 'init', array( $this, 'add_hooks' ) );
			$this->add_hooks();
			if ( is_admin() ) {
				include_once( 'reports/class-wcj-currency-reports.php' );
			}
		}
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.7.0
	 * @since   2.3.2
	 */
	function add_hooks() {
		add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_currency',        array( $this, 'change_currency_code' ), PHP_INT_MAX, 1 );

		add_filter( 'woocommerce_paypal_supported_currencies', array( $this, 'extend_paypal_supported_currencies' ), PHP_INT_MAX, 1 );

		add_filter( WCJ_PRODUCT_GET_PRICE_FILTER,              array( $this, 'change_price_by_gateway' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price_by_gateway' ), PHP_INT_MAX, 2 );

		add_filter( 'woocommerce_package_rates', array( $this, 'change_shipping_price_by_gateway' ), PHP_INT_MAX, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_script' ) );
		add_action( 'init',               array( $this, 'register_script' ) );
	}

	/**
	 * change_shipping_price_by_gateway.
	 *
	 * @version 3.2.0
	 * @since   2.4.8
	 */
	function change_shipping_price_by_gateway( $package_rates, $package ) {
		if ( $this->is_cart_or_checkout() ) {
			global $woocommerce;
			$current_gateway = $woocommerce->session->chosen_payment_method;
			if ( '' != $current_gateway ) {
				$gateway_currency_exchange_rate = get_option( 'wcj_gateways_currency_exchange_rate_' . $current_gateway );
				return wcj_change_price_shipping_package_rates( $package_rates, $gateway_currency_exchange_rate );
			}
		}
		return $package_rates;
	}

	/**
	 * is_cart_or_checkout.
	 *
	 * @version 2.3.5
	 */
	function is_cart_or_checkout() {
//		if ( wcj_is_frontend() ) {
		if ( ! is_admin() ) {
			if ( is_cart() || is_checkout() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * change_price_by_gateway.
	 */
	function change_price_by_gateway( $price, $product ) {
		if ( $this->is_cart_or_checkout() ) {
			global $woocommerce;
			$current_gateway = $woocommerce->session->chosen_payment_method;
			if ( '' != $current_gateway ) {
				$gateway_currency_exchange_rate = get_option( 'wcj_gateways_currency_exchange_rate_' . $current_gateway );
				$price = $price * $gateway_currency_exchange_rate;
			}
		}
		return $price;
	}

	/**
	 * extend_paypal_supported_currencies.
	 *
	 * @version 2.4.0
	 */
	function extend_paypal_supported_currencies( $supported_currencies ) {
		if ( $this->is_cart_or_checkout() ) {
			global $woocommerce;
			$current_gateway = $woocommerce->session->chosen_payment_method;
			if ( '' != $current_gateway ) {
				$gateway_currency = get_option( 'wcj_gateways_currency_' . $current_gateway );
				if ( 'no_changes' != $gateway_currency ) {
					$supported_currencies[] = $gateway_currency;
				}
			}
		}
		return $supported_currencies;
	}

	/**
	 * change_currency_symbol.
	 *
	 * @version 2.4.0
	 */
	function change_currency_symbol( $currency_symbol, $currency ) {
		if ( $this->is_cart_or_checkout() ) {
			global $woocommerce;
			$current_gateway = $woocommerce->session->chosen_payment_method;
			if ( '' != $current_gateway ) {
				$gateway_currency = get_option( 'wcj_gateways_currency_' . $current_gateway );
				if ( 'no_changes' != $gateway_currency ) {
					return wcj_get_currency_symbol( $gateway_currency );
				}
			}
		}
		return $currency_symbol;
	}

	/**
	 * change_currency_code.
	 *
	 * @version 2.4.0
	 */
	function change_currency_code( $currency ) {
		if ( $this->is_cart_or_checkout() ) {
			global $woocommerce;
			$current_gateway = $woocommerce->session->chosen_payment_method;
			/*
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( ! array_key_exists( $current_gateway, $available_gateways ) ) {
				$current_gateway = get_option( 'woocommerce_default_gateway', '' );
				if ( '' == $current_gateway ) {
					$current_gateway = current( $available_gateways );
					$current_gateway = isset( $current_gateway->id ) ? $current_gateway->id : '';
				}
			}
			*/
			if ( '' != $current_gateway ) {
				$gateway_currency = get_option( 'wcj_gateways_currency_' . $current_gateway );
				if ( 'no_changes' != $gateway_currency ) {
					return $gateway_currency;
				}
			}
		}
		return $currency;
	}

	/**
	 * register_script.
	 *
	 * @version 2.9.0
	 */
	function register_script() {
		wp_register_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/wcj-checkout.js', array( 'jquery' ), WCJ()->version, true );
	}

	/**
	 * enqueue_checkout_script.
	 */
	function enqueue_checkout_script() {
		if( ! is_checkout() ) {
			return;
		}
		wp_enqueue_script( 'wcj-payment-gateways-checkout' );
	}

}

endif;

return new WCJ_Payment_Gateways_Currency();
