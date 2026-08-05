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
		private $cart_product_ids = array();

		/**
		 * Signature for the cached cart context.
		 *
		 * @var string
		 */
		private $cart_context_signature = '';

		/**
		 * Number of cart-context builds in this request.
		 *
		 * @var int
		 */
		private $cart_context_build_count = 0;

		/**
		 * Number of gateway-fee callbacks in this request.
		 *
		 * @var int
		 */
		private $fee_calculation_count = 0;

		/**
		 * Last request-local decision; contains no customer data.
		 *
		 * @var array
		 */
		private $last_fee_decision = array();

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
				'include_products' => array(),
				'exclude_products' => array(),
			);
			foreach ( $this->options as $option_key => $option_value ) {
				$this->options[ $option_key ] = is_array( $option_value ) ? $option_value : array();
			}
		}

		/** Returns the last safe fee decision for diagnostics and tests. */
		public function get_last_fee_decision() {
			return $this->last_fee_decision;
		}

		/** Returns safe request-local counters for diagnostics and tests. */
		public function get_performance_counters() {
			return array(
				'fee_calculations'    => $this->fee_calculation_count,
				'cart_context_builds' => $this->cart_context_build_count,
			);
		}

		/**
		 * Records a request-local reason code without cart or customer details.
		 *
		 * @param string $gateway Gateway identifier.
		 * @param string $reason  Fee decision reason code.
		 * @return void
		 */
		private function set_fee_decision( $gateway, $reason ) {
			$this->last_fee_decision = array(
				'gateway' => sanitize_key( $gateway ),
				'reason'  => sanitize_key( $reason ),
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
		 * Get product and variation IDs for the current cart state.
		 *
		 * @param WC_Cart $cart Cart object.
		 * @return array
		 */
		private function get_cart_product_ids( $cart ) {
			$product_ids    = array();
			$signature_data = array();
			foreach ( $cart->get_cart() as $cart_item_key => $item ) {
				$product_id       = ! empty( $item['product_id'] ) ? (string) $item['product_id'] : '';
				$variation_id     = ! empty( $item['variation_id'] ) ? (string) $item['variation_id'] : '';
				$quantity         = isset( $item['quantity'] ) ? (float) $item['quantity'] : 0;
				$signature_data[] = array( (string) $cart_item_key, $product_id, $variation_id, $quantity );
				if ( '' !== $product_id ) {
					$product_ids[] = $product_id;
				}
				if ( '' !== $variation_id ) {
					$product_ids[] = $variation_id;
				}
			}

			$signature = md5( wp_json_encode( $signature_data ) );
			if ( $signature === $this->cart_context_signature ) {
				return $this->cart_product_ids;
			}
			$this->cart_context_signature = $signature;
			$this->cart_product_ids       = array_values( array_unique( $product_ids ) );
			++$this->cart_context_build_count;
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
			$include_products = array_values( array_filter( array_map( 'strval', (array) $this->wcj_get_option( 'include_products', $gateway ) ) ) );
			$exclude_products = array_values( array_filter( array_map( 'strval', (array) $this->wcj_get_option( 'exclude_products', $gateway ) ) ) );
			if ( empty( $include_products ) && empty( $exclude_products ) ) {
				return true;
			}

			$product_ids = $this->get_cart_product_ids( $cart );
			if ( ! empty( $include_products ) ) {
				if ( empty( array_intersect( $product_ids, $include_products ) ) ) {
					return false;
				}
			}
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
			++$this->fee_calculation_count;
			$cart = $cart instanceof WC_Cart ? $cart : ( function_exists( 'WC' ) ? WC()->cart : null );
			if ( ! $cart || ! function_exists( 'WC' ) || ! WC()->session ) {
				$this->set_fee_decision( '', 'missing_cart_or_session' );
				return;
			}
			if ( empty( $this->options ) || empty( $this->defaults ) ) {
				$this->init_options();
			}

			$current_gateway = $this->get_current_gateway();
			if ( '' === $current_gateway ) {
				$this->set_fee_decision( '', 'no_gateway_selected' );
				return;
			}
			if ( false !== strpos( $current_gateway, 'klarna' ) && 'yes' === wcj_get_option( 'wcj_enable_payment_gateway_charge_discount', 'no' ) ) {
				$current_gateway = 'klarna_payments';
			}

			$fee_text        = do_shortcode( $this->wcj_get_option( 'text', $current_gateway ) );
			$min_cart_amount = (float) $this->wcj_get_option( 'min_cart_amount', $current_gateway );
			$max_cart_amount = (float) $this->wcj_get_option( 'max_cart_amount', $current_gateway );
			if ( '' === $fee_text ) {
				$this->set_fee_decision( $current_gateway, 'not_configured' );
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
			if ( $total_in_cart < $min_cart_amount || ( 0.0 !== $max_cart_amount && $total_in_cart > $max_cart_amount ) ) {
				$this->set_fee_decision( $current_gateway, 'outside_cart_amount' );
				return;
			}
			if ( ! $this->check_cart_products( $current_gateway, $cart ) ) {
				$this->set_fee_decision( $current_gateway, 'product_condition_not_met' );
				return;
			}
			if ( $total_in_cart >= $min_cart_amount ) {
				$userwise_options                 = (array) wcj_get_option( 'wcj_enable_payment_gateway_charge_discount_userwise', array() );
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
					$result = $cart->fees_api()->add_fee(
						array(
							'id'        => 'wcj_gateway_fee_' . sanitize_key( $current_gateway ),
							'name'      => $fee_text,
							'amount'    => $final_fee_to_add,
							'taxable'   => $taxable,
							'tax_class' => $tax_class_name,
						)
					);
					$this->set_fee_decision( $current_gateway, is_wp_error( $result ) ? 'duplicate_suppressed' : 'applied' );
				} else {
					$this->set_fee_decision( $current_gateway, 'zero_or_invalid_amount' );
				}
			}
		}
	}

endif;

return new WCJ_Payment_Gateways_Fees();
