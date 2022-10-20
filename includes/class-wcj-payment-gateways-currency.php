<?php
/**
 * Booster for WooCommerce - Module - Gateways Currency Converter
 *
 * @version 5.6.7
 * @since   2.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Currency' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Payment_Gateways_Currency extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.3.0
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_currency';
			$this->short_desc = __( 'Gateways Currency Converter', 'woocommerce-jetpack' );
			$this->desc       = __( 'Currency converter for payment gateways. Update exchange rates automatically (Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Currency converter for payment gateways.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-currency-converter';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->page_scope = wcj_get_option( 'wcj_gateways_currency_page_scope', 'cart_and_checkout' );
				$this->add_hooks();
				if ( is_admin() ) {
					include_once 'reports/class-wcj-currency-reports.php';
				}
				if ( 'yes' === wcj_get_option( 'wcj_gateways_currency_fix_chosen_payment_method', 'no' ) ) {
					add_action( 'woocommerce_checkout_update_order_review', array( $this, 'fix_chosen_payment_method' ) );
				}
			}
		}

		/**
		 * Add_hooks.
		 *
		 * @version 3.9.0
		 * @since   2.3.2
		 */
		public function add_hooks() {
			add_filter( 'woocommerce_currency', array( $this, 'change_currency_code' ), PHP_INT_MAX, 1 );

			add_filter( 'woocommerce_paypal_supported_currencies', array( $this, 'extend_paypal_supported_currencies' ), PHP_INT_MAX, 1 );

			add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, array( $this, 'change_price_by_gateway' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price_by_gateway' ), PHP_INT_MAX, 2 );

			add_filter( 'woocommerce_package_rates', array( $this, 'change_shipping_price_by_gateway' ), PHP_INT_MAX, 2 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_script' ) );
			add_action( 'init', array( $this, 'register_script' ) );
		}

		/**
		 * Fix_chosen_payment_method.
		 *
		 * @version 5.6.7
		 * @since   3.9.0
		 * @param string | array $post_data defines the post_data.
		 */
		public function fix_chosen_payment_method( $post_data ) {
			$wpnonce = false;
			$args    = array();
			if ( isset( $_POST['post_data'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_POST['post_data'] ) ), $args );
				$wpnonce = isset( $args['woocommerce-process-checkout-nonce'] ) ? wp_verify_nonce( sanitize_key( $args['woocommerce-process-checkout-nonce'] ), 'woocommerce-process_checkout' ) : false;
			}

			$payment_gateway            = ( empty( $_POST['payment_method'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) );
			$available_payment_gateways = array_keys( WC()->payment_gateways->get_available_payment_gateways() );
			if ( ! empty( $available_payment_gateways ) && $wpnonce ) {

				if ( ! in_array( $payment_gateway, $available_payment_gateways, true ) ) {
					$_POST['payment_method'] = $available_payment_gateways[0];
				}
			} else {
				$_POST['payment_method'] = '';
			}
		}

		/**
		 * Get_chosen_payment_method.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @todo    [dev] maybe make this more complex
		 */
		public function get_chosen_payment_method() {
			return WC()->session->chosen_payment_method;
		}

		/**
		 * Change_shipping_price_by_gateway.
		 *
		 * @version 5.6.7
		 * @since   2.4.8
		 * @param int | array    $package_rates defines the package_rates.
		 * @param string | array $package defines the package.
		 */
		public function change_shipping_price_by_gateway( $package_rates, $package ) {
			if ( $this->is_cart_or_checkout() ) {
				$current_gateway = $this->get_chosen_payment_method();
				if ( '' !== $current_gateway && null !== $current_gateway ) {
					$gateway_currency_exchange_rate = wcj_get_option( 'wcj_gateways_currency_exchange_rate_' . $current_gateway );
					return wcj_change_price_shipping_package_rates( $package_rates, $gateway_currency_exchange_rate );
				}
			}
			return $package_rates;
		}

		/**
		 * Is_cart_or_checkout.
		 *
		 * @version 5.3.6
		 * @since   2.3.0
		 * @todo    [dev] rename function to `do_apply_by_page_scope()`
		 */
		public function is_cart_or_checkout() {
			if (
			is_admin()
			|| ! function_exists( 'is_checkout' )
			|| ! function_exists( 'is_cart' )
			) {
				return false;
			}
			if ( 'cart_and_checkout' === $this->page_scope ) {
				if ( is_cart() || is_checkout() ) {
					return true;
				}
			} elseif ( 'checkout_only' === $this->page_scope ) {
				if ( is_checkout() ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Change_price_by_gateway.
		 *
		 * @version 5.6.7
		 * @since   2.3.0
		 * @param string         $price defines the price.
		 * @param string | array $product defines the product.
		 */
		public function change_price_by_gateway( $price, $product ) {
			if ( $this->is_cart_or_checkout() ) {
				$current_gateway = $this->get_chosen_payment_method();

				if ( '' !== $current_gateway && null !== $current_gateway ) {
					$gateway_currency_exchange_rate = wcj_get_option( 'wcj_gateways_currency_exchange_rate_' . $current_gateway );
					$gateway_currency_exchange_rate = str_replace( ',', '.', $gateway_currency_exchange_rate );
					if ( is_numeric( $price ) ) {
						$price = $price * (float) $gateway_currency_exchange_rate;
					}
				}
			}
			return $price;
		}

		/**
		 * Extend_paypal_supported_currencies.
		 *
		 * @version 5.6.7
		 * @since   2.3.0
		 * @param  array $supported_currencies defines the supported_currencies.
		 */
		public function extend_paypal_supported_currencies( $supported_currencies ) {
			if ( $this->is_cart_or_checkout() ) {
				$current_gateway = $this->get_chosen_payment_method();
				if ( '' !== $current_gateway && null !== $current_gateway ) {
					$gateway_currency = wcj_get_option( 'wcj_gateways_currency_' . $current_gateway );
					if ( 'no_changes' !== $gateway_currency ) {
						$supported_currencies[] = $gateway_currency;
					}
				}
			}
			return $supported_currencies;
		}

		/**
		 * Change_currency_code.
		 *
		 * @version 5.6.7
		 * @since   2.3.0
		 * @param  string $currency defines the currency.
		 */
		public function change_currency_code( $currency ) {
			if ( $this->is_cart_or_checkout() ) {
				$current_gateway = $this->get_chosen_payment_method();
				if ( '' !== $current_gateway && null !== $current_gateway ) {
					$gateway_currency = wcj_get_option( 'wcj_gateways_currency_' . $current_gateway );
					if ( 'no_changes' !== $gateway_currency ) {
						return $gateway_currency;
					}
				}
			}
			return $currency;
		}

		/**
		 * Register_script.
		 *
		 * @version 2.9.0
		 * @since   2.3.0
		 */
		public function register_script() {
			wp_register_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/wcj-checkout.js', array( 'jquery' ), w_c_j()->version, true );
		}

		/**
		 * Enqueue_checkout_script.
		 *
		 * @version 2.3.0
		 * @since   2.3.0
		 */
		public function enqueue_checkout_script() {
			if ( ! is_checkout() ) {
				return;
			}
			wp_enqueue_script( 'wcj-payment-gateways-checkout' );
		}

	}

endif;

return new WCJ_Payment_Gateways_Currency();
