<?php
/**
 * Booster for WooCommerce - Module - Gateways Fees and Discounts
 *
 * @version 8.1.0
 * @since   2.2.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Fees' ) ) :
	/**
	 * WCJ_Payment_Gateways_Fees.
	 */
	class WCJ_Payment_Gateways_Fees extends WCJ_Module {


		/**
		 * The module defaults
		 *
		 * @var array
		 */
		public $defaults = array();

		/**
		 * Request-scoped cart product and variation IDs.
		 *
		 * @var array|null
		 */
		private $cart_product_ids = null;

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @todo    (maybe) add settings subsections for each gateway
		 */
		public function __construct() {
			$this->id         = 'payment_gateways_fees';
			$this->short_desc = __( 'Gateways Fees and Discounts', 'woocommerce-jetpack' );
			$this->desc       = __( 'Enable extra fees or discounts for payment gateways. Force Default Payment Gateway (Elite). Apply fees depending on specific products (Elite).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Enable extra fees or discounts for payment gateways.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-fees-and-discounts';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$modules_on_init = wcj_get_option( 'wcj_load_modules_on_init', 'no' );
				if ( 'no' === ( $modules_on_init ) ) {
					add_action( 'init', array( $this, 'init_options' ) );
				} elseif ( 'yes' === $modules_on_init && 'init' === current_filter() ) {
					$this->init_options();
				}
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'gateways_fees' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_script' ) );
			}
		}

		/**
		 * Init_options.
		 *
		 * @version 4.1.0
		 * @since   3.8.0
		 */
		public function init_options() {
			$this->options  = array(
				'text'             => wcj_get_option( 'wcj_gateways_fees_text', array() ),
				'type'             => wcj_get_option( 'wcj_gateways_fees_type', array() ),
				'value'            => wcj_get_option( 'wcj_gateways_fees_value', array() ),
				'min_cart_amount'  => wcj_get_option( 'wcj_gateways_fees_min_cart_amount', array() ),
				'max_cart_amount'  => wcj_get_option( 'wcj_gateways_fees_max_cart_amount', array() ),
				'round'            => wcj_get_option( 'wcj_gateways_fees_round', array() ),
				'round_precision'  => wcj_get_option( 'wcj_gateways_fees_round_precision', array() ),
				'is_taxable'       => wcj_get_option( 'wcj_gateways_fees_is_taxable', array() ),
				'tax_class_id'     => wcj_get_option( 'wcj_gateways_fees_tax_class_id', array() ),
				'exclude_shipping' => wcj_get_option( 'wcj_gateways_fees_exclude_shipping', array() ),
				'include_taxes'    => wcj_get_option( 'wcj_gateways_fees_include_taxes', array() ),
				'include_products' => apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_fees_include_products', array() ) ),
				'exclude_products' => apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_fees_exclude_products', array() ) ),
			);
			$this->defaults = array(
				'text'             => '',
				'type'             => 'fixed',
				'value'            => 0,
				'min_cart_amount'  => 0,
				'max_cart_amount'  => 0,
				'round'            => 'no',
				'round_precision'  => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
				'is_taxable'       => 'no',
				'tax_class_id'     => '',
				'exclude_shipping' => 'no',
				'include_taxes'    => 'no',
				'include_products' => '',
				'exclude_products' => '',
			);
		}

		/**
		 * Get_option.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 * @todo    (dev) maybe move this to `WCJ_Module`
		 * @param string | array $option defines the option.
		 * @param string         $key defines the key.
		 * @param bool           $default defines the default.
		 */
		public function wcj_get_option( $option, $key, $default = false ) {
			return ( isset( $this->options[ $option ][ $key ] ) ? $this->options[ $option ][ $key ] : ( isset( $this->defaults[ $option ] ) ? $this->defaults[ $option ] : $default ) );
		}

		/**
		 * Get_deprecated_options.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 */
		public function get_deprecated_options() {
			$deprecated_options  = array();
			$_deprecated_options = array(
				'wcj_gateways_fees_text'             => 'wcj_gateways_fees_text_',
				'wcj_gateways_fees_type'             => 'wcj_gateways_fees_type_',
				'wcj_gateways_fees_value'            => 'wcj_gateways_fees_value_',
				'wcj_gateways_fees_min_cart_amount'  => 'wcj_gateways_fees_min_cart_amount_',
				'wcj_gateways_fees_max_cart_amount'  => 'wcj_gateways_fees_max_cart_amount_',
				'wcj_gateways_fees_round'            => 'wcj_gateways_fees_round_',
				'wcj_gateways_fees_round_precision'  => 'wcj_gateways_fees_round_precision_',
				'wcj_gateways_fees_is_taxable'       => 'wcj_gateways_fees_is_taxable_',
				'wcj_gateways_fees_tax_class_id'     => 'wcj_gateways_fees_tax_class_id_',
				'wcj_gateways_fees_exclude_shipping' => 'wcj_gateways_fees_exclude_shipping_',
			);
			$available_gateways  = WC()->payment_gateways->payment_gateways();
			foreach ( $_deprecated_options as $new_option => $old_option ) {
				$deprecated_options[ $new_option ] = array();
				foreach ( $available_gateways as $key => $gateway ) {
					$deprecated_options[ $new_option ][ $key ] = $old_option . $key;
				}
			}
			return $deprecated_options;
		}

		/**
		 * Enqueue_checkout_script.
		 *
		 * @version 2.9.0
		 */
		public function enqueue_checkout_script() {
			if ( ! is_checkout() ) {
				return;
			}
			wp_enqueue_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/wcj-checkout.js', array( 'jquery' ), w_c_j()->version, true );
		}

		/**
		 * Get_current_gateway.
		 *
		 * @version 8.1.0
		 * @since   3.3.0
		 */
		public function get_current_gateway() {
			$gateway = '';
			// phpcs:disable WordPress.Security.NonceVerification
			$wc_api        = isset( $_GET['wc-api'] ) ? sanitize_text_field( wp_unslash( $_GET['wc-api'] ) ) : '';
			$wc_ajax       = isset( $_GET['wc-ajax'] ) ? sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) ) : '';
			$startcheckout = isset( $_GET['startcheckout'] ) ? sanitize_text_field( wp_unslash( $_GET['startcheckout'] ) ) : '';
			if ( 'WC_Gateway_PayPal_Express_AngellEYE' === $wc_api ) {
				$gateway = 'paypal_express'; // PayPal for WooCommerce (By Angell EYE).
			} elseif (
				'wc_ppec_generate_cart' === $wc_ajax ||
				'true' === $startcheckout
			) {
				$gateway = 'ppec_paypal'; // WooCommerce PayPal Express Checkout Payment Gateway (By WooCommerce).
			} elseif ( function_exists( 'WC' ) && WC()->session ) {
				$gateway = WC()->session->get( 'chosen_payment_method' );
			}
			// phpcs:enable WordPress.Security.NonceVerification

			// Pre-sets the default available payment gateway on cart and checkout pages.
			if (
				empty( $gateway ) &&
				'yes' === wcj_get_option( 'wcj_gateways_fees_force_default_payment_gateway', 'no' ) &&
				( is_checkout() || is_cart() )
			) {
				$gateways = WC()->payment_gateways->get_available_payment_gateways();
				if ( $gateways ) {
					foreach ( $gateways as $available_gateway ) {
						if ( 'yes' === $available_gateway->enabled ) {
							WC()->session->set( 'chosen_payment_method', $available_gateway->id );
							$gateway = WC()->session->get( 'chosen_payment_method' );
							break;
						}
					}
				}
			}
			return is_string( $gateway ) ? $gateway : '';
		}

		/**
		 * Get product and variation IDs from the cart once per request.
		 *
		 * @param WC_Cart $cart Cart object.
		 * @return array
		 */
		private function get_cart_product_ids( $cart ) {
			if ( null !== $this->cart_product_ids ) {
				return $this->cart_product_ids;
			}
			$this->cart_product_ids = array();
			foreach ( $cart->get_cart() as $item ) {
				if ( ! empty( $item['product_id'] ) ) {
					$this->cart_product_ids[] = (string) $item['product_id'];
				}
				if ( ! empty( $item['variation_id'] ) ) {
					$this->cart_product_ids[] = (string) $item['variation_id'];
				}
			}
			$this->cart_product_ids = array_values( array_unique( $this->cart_product_ids ) );
			return $this->cart_product_ids;
		}

		/**
		 * Check_cart_products.
		 *
		 * @version 8.1.0
		 * @since   3.7.0
		 * @todo    add WPML support
		 * @todo    add product cats and tags
		 * @param string  $gateway Gateway ID.
		 * @param WC_Cart $cart    Cart object.
		 */
		public function check_cart_products( $gateway, $cart ) {
			$product_ids      = $this->get_cart_product_ids( $cart );
			$include_products = array_map( 'strval', (array) $this->wcj_get_option( 'include_products', $gateway ) );
			if ( ! empty( $include_products ) ) {
				if ( empty( array_intersect( $product_ids, $include_products ) ) ) {
					return false;
				}
			}
			$exclude_products = array_map( 'strval', (array) $this->wcj_get_option( 'exclude_products', $gateway ) );
			if ( ! empty( $exclude_products ) && ! empty( array_intersect( $product_ids, $exclude_products ) ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Gateways_fees.
		 *
		 * @version 8.1.0
		 * @param WC_Cart|null $cart Cart passed by WooCommerce.
		 */
		public function gateways_fees( $cart = null ) {
			$cart = $cart instanceof WC_Cart ? $cart : ( function_exists( 'WC' ) ? WC()->cart : null );
			if ( ! $cart || ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}
			if ( empty( $this->options ) || empty( $this->defaults ) ) {
				$this->init_options();
			}

			$current_gateway = $this->get_current_gateway();
			if ( '' === $current_gateway ) {
				return;
			}
			if ( false !== strpos( $current_gateway, 'klarna' ) && 'yes' === wcj_get_option( 'wcj_enable_payment_gateway_charge_discount', 'no' ) ) {
				$current_gateway = 'klarna_payments';
			}

			$fee_text        = do_shortcode( $this->wcj_get_option( 'text', $current_gateway ) );
			$min_cart_amount = (float) $this->wcj_get_option( 'min_cart_amount', $current_gateway );
			$max_cart_amount = (float) $this->wcj_get_option( 'max_cart_amount', $current_gateway );
			if ( '' === $fee_text ) {
				return;
			}

			// Multicurrency (Currency Switcher) module.
			$multicurrency_enabled = isset( w_c_j()->all_modules['multicurrency'] ) && w_c_j()->all_modules['multicurrency']->is_enabled();
			if ( $multicurrency_enabled ) {
				$min_cart_amount = w_c_j()->all_modules['multicurrency']->change_price( $min_cart_amount, null );
				$max_cart_amount = w_c_j()->all_modules['multicurrency']->change_price( $max_cart_amount, null );
			}
			$total_in_cart  = ( 'no' === $this->wcj_get_option( 'exclude_shipping', $current_gateway ) ?
				$cart->get_cart_contents_total() + $cart->get_shipping_total() :
				$cart->get_cart_contents_total() );
			$total_in_cart += 'no' === $this->wcj_get_option( 'include_taxes', $current_gateway ) ? 0 : $cart->get_subtotal_tax() + $cart->get_shipping_tax();
			if ( $total_in_cart >= $min_cart_amount && ( 0.0 === $max_cart_amount || $total_in_cart <= $max_cart_amount ) && $this->check_cart_products( $current_gateway, $cart ) ) {
				$userwise_options                  = (array) wcj_get_option( 'wcj_enable_payment_gateway_charge_discount_userwise', array() );
				$enable_user_wise_charge_discount = isset( $userwise_options[ $current_gateway ] ) ? $userwise_options[ $current_gateway ] : 'no';
				if ( 'yes' === $enable_user_wise_charge_discount ) {
					if ( is_user_logged_in() ) {
						$user      = wp_get_current_user();
						$user_role = isset( $user->roles[0] ) ? $user->roles[0] : 'guest';
					} else {
						$user_role = 'guest';
					}
					$role_values                = (array) wcj_get_option( 'wcj_gateways_fees_' . $user_role, array() );
					$additional_discountby_user = isset( $role_values[ $current_gateway ] ) ? $role_values[ $current_gateway ] : '';
					$fee_value                  = $additional_discountby_user ? $additional_discountby_user : $this->wcj_get_option( 'value', $current_gateway );
				} else {
					$fee_value = $this->wcj_get_option( 'value', $current_gateway );
				}
				$fee_type         = $this->wcj_get_option( 'type', $current_gateway );
				$final_fee_to_add = 0;
				switch ( $fee_type ) {
					case 'fixed':
						if ( $multicurrency_enabled ) {
							$fee_value = w_c_j()->all_modules['multicurrency']->change_price( $fee_value, null );
						}
						$final_fee_to_add = $fee_value;
						break;
					case 'percent':
						$final_fee_to_add = ( $fee_value / 100 ) * $total_in_cart;
						if ( 'yes' === $this->wcj_get_option( 'round', $current_gateway ) ) {
							$final_fee_to_add = round( $final_fee_to_add, $this->wcj_get_option( 'round_precision', $current_gateway ) );
						}
						break;
				}
				if ( 0.0 !== (float) $final_fee_to_add ) {
					$taxable        = ( 'yes' === $this->wcj_get_option( 'is_taxable', $current_gateway ) );
					$tax_class_name = '';
					if ( $taxable ) {
						$tax_class_id    = $this->wcj_get_option( 'tax_class_id', $current_gateway );
						$tax_class_names = array_merge( array( '' ), WC_Tax::get_tax_classes() );
						$tax_class_name  = isset( $tax_class_names[ $tax_class_id ] ) ? $tax_class_names[ $tax_class_id ] : '';
					}
					$cart->add_fee( $fee_text, $final_fee_to_add, $taxable, $tax_class_name );
				}
			}
		}
	}

endif;

return new WCJ_Payment_Gateways_Fees();
