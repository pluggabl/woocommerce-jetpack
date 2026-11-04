<?php // phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar -- Preserve existing inline HTML response bytes and consistent CRLF physical endings.
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
		 * Keep a verified fee mutation exclusive through this order-pay request only.
		 *
		 * @var bool
		 */
		private $order_pay_fee_request_active = false;

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @todo    (maybe) add settings subsections for each gateway
		 */
		public function __construct() {
			$this->id         = 'payment_gateways_fees';
			$this->short_desc = 'Gateways Fees and Discounts';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways Fees and Discounts', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Enable extra fees or discounts for payment gateways. Force Default Payment Gateway (Elite). Apply fees depending on specific products (Elite).';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Enable extra fees or discounts for payment gateways. Force Default Payment Gateway (Elite). Apply fees depending on specific products (Elite).', 'woocommerce-jetpack' );
				}
			);
			$this->desc_pro = 'Enable extra fees or discounts for payment gateways.';
			wcj_init_presentation(
				function () {
					$this->desc_pro = __( 'Enable extra fees or discounts for payment gateways.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-fees-and-discounts';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$modules_on_init = wcj_get_option( 'wcj_load_modules_on_init', 'no' );
				if ( 'no' === ( $modules_on_init ) ) {
					add_action( 'init', array( $this, 'init_options' ) );
				} elseif ( 'yes' === $modules_on_init && 'init' === current_filter() ) {
					$this->init_options();
				}
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'gateways_fees' ) );
				add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'tag_order_fee_item' ), 10, 4 );
				add_action( 'before_woocommerce_pay_form', array( $this, 'maybe_recalculate_order_pay_fees_on_load' ), 10, 3 );
				add_action( 'woocommerce_before_pay_action', array( $this, 'recalculate_order_pay_fees_before_payment' ), 10, 1 );
				add_action( 'wc_ajax_wcj_update_order_pay_fees', array( $this, 'ajax_update_order_pay_fees' ) );
				add_action( 'wp_ajax_wcj_update_order_pay_fees', array( $this, 'ajax_update_order_pay_fees' ) );
				add_action( 'wp_ajax_nopriv_wcj_update_order_pay_fees', array( $this, 'ajax_update_order_pay_fees' ) );
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
		 * @param array  $evaluation Bounded evaluation facts.
		 * @return void
		 */
		private function set_fee_decision( $gateway, $reason, $evaluation = array() ) {
			$this->last_fee_decision = array_merge(
				$this->fee_evaluation( $gateway, $reason, array() ),
				$evaluation,
				array(
					'gateway'    => sanitize_key( $gateway ),
					'reason'     => sanitize_key( $reason ),
					'generation' => $this->fee_calculation_count,
				)
			);
		}

		/** Builds a bounded internal evaluation.
		 *
		 * @param string         $gateway Selected gateway.
		 * @param string         $reason Decision code.
		 * @param array          $conditions Fixed condition states.
		 * @param array|null     $fee Internal application payload, never exposed by diagnostics.
		 * @param float|int|null $amount Evaluated amount before fee tax.
		 * @return array
		 */
		private function fee_evaluation( $gateway, $reason, $conditions, $fee = null, $amount = null ) {
			$result = array(
				'version'    => 1,
				'gateway'    => sanitize_key( $gateway ),
				'reason'     => $reason,
				'generation' => 0,
				'conditions' => $conditions,
				'amount'     => $amount,
			);
			if ( null !== $fee ) {
				$result['fee'] = $fee;
			}
			return $result;
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
		public function wcj_get_option( $option, $key, $default = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- Preserve public parameter name for named-argument compatibility.
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
		 * @version 9.0.0
		 */
		public function enqueue_checkout_script() {
			if ( ! is_checkout() ) {
				return;
			}
			wp_enqueue_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/wcj-checkout.js', array( 'jquery' ), w_c_j()->version, true );
			if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
				global $wp;
				$order_id = ( isset( $wp->query_vars['order-pay'] ) && absint( $wp->query_vars['order-pay'] ) > 0 )
					? absint( $wp->query_vars['order-pay'] )
					: absint( get_query_var( 'order-pay' ) );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading order key from order-pay URL to localize AJAX parameters.
				$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
				$ajax_url  = class_exists( 'WC_AJAX' )
					? WC_AJAX::get_endpoint( 'wcj_update_order_pay_fees' )
					: admin_url( 'admin-ajax.php?action=wcj_update_order_pay_fees' );
				wp_localize_script(
					'wcj-payment-gateways-checkout',
					'wcj_order_pay_fees_params',
					array(
						'is_order_pay'  => 'yes',
						'ajax_url'      => $ajax_url,
						'order_id'      => $order_id,
						'order_key'     => $order_key,
						'nonce'         => wp_create_nonce( 'wcj-update-order-pay-fees-' . $order_id ),
						'error_message' => __( 'Payment fees could not be verified. Please reload or contact the store before paying.', 'woocommerce-jetpack' ),
					)
				);
			}
		}

		/**
		 * Tag order fee items created from Booster gateway cart fees.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order_Item_Fee $item    Order fee item.
		 * @param string            $fee_key Cart fee key.
		 * @param object            $fee     Cart fee object.
		 * @param WC_Order          $order   Order object.
		 * @return void
		 */
		public function tag_order_fee_item( $item, $fee_key, $fee, $order ) {
			unset( $order );
			if ( ! is_object( $item ) || ! method_exists( $item, 'update_meta_data' ) ) {
				return;
			}
			$fee_id = ( is_object( $fee ) && isset( $fee->id ) ) ? (string) $fee->id : (string) $fee_key;
			if ( 0 === strpos( $fee_id, 'wcj_gateway_fee_' ) ) {
				$gateway = substr( $fee_id, strlen( 'wcj_gateway_fee_' ) );
				$item->update_meta_data( '_wcj_gateway_fee', 'yes' );
				$item->update_meta_data( '_wcj_gateway_fee_gateway', sanitize_key( $gateway ) );
			}
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
		 * Get product and variation IDs for an existing order.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order Order object.
		 * @return array
		 */
		private function get_order_product_ids( $order ) {
			$product_ids = array();
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
				return $product_ids;
			}
			foreach ( (array) $order->get_items( 'line_item' ) as $item ) {
				if ( is_object( $item ) ) {
					$product_id   = method_exists( $item, 'get_product_id' ) ? (int) $item->get_product_id() : 0;
					$variation_id = method_exists( $item, 'get_variation_id' ) ? (int) $item->get_variation_id() : 0;
					if ( $product_id > 0 ) {
						$product_ids[] = (string) $product_id;
					}
					if ( $variation_id > 0 ) {
						$product_ids[] = (string) $variation_id;
					}
				} elseif ( is_array( $item ) ) {
					if ( ! empty( $item['product_id'] ) ) {
						$product_ids[] = (string) $item['product_id'];
					}
					if ( ! empty( $item['variation_id'] ) ) {
						$product_ids[] = (string) $item['variation_id'];
					}
				}
			}
			return array_values( array_unique( $product_ids ) );
		}

		/**
		 * Check product include/exclude rules against an existing order.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param string   $gateway Gateway ID.
		 * @param WC_Order $order   Order object.
		 * @return bool
		 */
		public function check_order_products( $gateway, $order ) {
			$include_products = array_values( array_filter( array_map( 'strval', (array) $this->wcj_get_option( 'include_products', $gateway ) ) ) );
			$exclude_products = array_values( array_filter( array_map( 'strval', (array) $this->wcj_get_option( 'exclude_products', $gateway ) ) ) );
			if ( empty( $include_products ) && empty( $exclude_products ) ) {
				return true;
			}

			$product_ids = $this->get_order_product_ids( $order );
			if ( ! empty( $include_products ) && empty( array_intersect( $product_ids, $include_products ) ) ) {
				return false;
			}
			if ( ! empty( $exclude_products ) && ! empty( array_intersect( $product_ids, $exclude_products ) ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Determine whether an order fee item was added by Booster's Gateways Fees module.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param object        $item  Order fee item.
		 * @param WC_Order|null $order Optional order context.
		 * @return bool
		 */
		private function is_booster_gateway_fee_item( $item, $order = null ) {
			unset( $order );
			if ( ! is_object( $item ) ) {
				return false;
			}
			if ( method_exists( $item, 'get_meta' ) ) {
				if ( 'yes' === $item->get_meta( '_wcj_gateway_fee', true ) ) {
					return true;
				}
				$legacy_key = (string) $item->get_meta( '_legacy_fee_key', true );
				if ( '' !== $legacy_key ) {
					if ( 0 === strpos( $legacy_key, 'wcj_gateway_fee_' ) ) {
						return true;
					}
					if ( 0 === strpos( $legacy_key, 'wcj_checkout_fee_' ) ) {
						return false;
					}
				}
			}
			if ( isset( $item->legacy_fee_key ) && is_string( $item->legacy_fee_key ) ) {
				if ( 0 === strpos( $item->legacy_fee_key, 'wcj_gateway_fee_' ) ) {
					return true;
				}
				if ( 0 === strpos( $item->legacy_fee_key, 'wcj_checkout_fee_' ) ) {
					return false;
				}
			}
			return false;
		}

		/**
		 * Migrate one unambiguous legacy fee using the stored gateway and amount.
		 * A display name alone is not ownership evidence. Tagged fees take priority.
		 *
		 * @param WC_Order $order Unpaid order.
		 * @param bool     $migrated Whether an untagged legacy fee was admitted.
		 * @return bool False when identical untagged candidates require merchant review.
		 */
		private function prepare_legacy_gateway_fee( $order, &$migrated = false ) {
			$fees = (array) $order->get_items( 'fee' );
			foreach ( $fees as $fee ) {
				if ( $this->is_booster_gateway_fee_item( $fee ) ) {
					return true;
				}
			}
			$gateway = (string) $order->get_payment_method();
			$title   = trim( (string) $this->wcj_get_option( 'text', $gateway ) );
			if ( '' === $gateway || '' === $title ) {
				return true;
			}
			$evaluated_title = trim( (string) do_shortcode( $title ) );
			$matches         = array();
			foreach ( $fees as $fee ) {
				// Explicit ownership by another fee producer must never be guessed away.
				if ( '' !== (string) $fee->get_meta( '_legacy_fee_key', true ) || '' !== (string) $fee->get_meta( '_wcj_gateway_fee', true ) || ! empty( $fee->legacy_fee_key ) ) {
					continue;
				}
				$name = trim( (string) $fee->get_name() );
				if ( $name !== $title && ( '' === $evaluated_title || $name !== $evaluated_title ) ) {
					continue;
				}
				$base     = $this->get_order_fee_base_context( $order, (float) $order->get_total(), (float) $fee->get_total(), (float) $fee->get_total_tax(), array( $fee ), false );
				$expected = $this->calculate_gateway_fee_for_order( $order, $gateway, $base );
				if ( ! is_array( $expected ) || abs( (float) $fee->get_total() - $expected['amount'] ) > 0.000001 ) {
					$this->set_fee_decision( $gateway, 'unverified_legacy_gateway_fee' );
					return false;
				}
				$tax_status = ! empty( $expected['taxable'] ) ? 'taxable' : 'none';
				if ( $fee->get_tax_status() !== $tax_status || ( 'taxable' === $tax_status && $fee->get_tax_class() !== $expected['tax_class'] ) ) {
					$this->set_fee_decision( $gateway, 'unverified_legacy_gateway_fee' );
					return false;
				}
				$matches[] = $fee;
			}
			if ( count( $matches ) > 1 ) {
				$this->set_fee_decision( $gateway, 'ambiguous_legacy_gateway_fee' );
				return false;
			}
			if ( 1 === count( $matches ) ) {
				$migrated = true;
				$matches[0]->update_meta_data( '_wcj_gateway_fee', 'yes' );
				$matches[0]->update_meta_data( '_wcj_gateway_fee_gateway', $gateway );
			}
			return true;
		}

		/**
		 * Remove existing Booster gateway fee items from an order and return removed totals.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order Order object.
		 * @return array
		 */
		private function remove_order_gateway_fees( $order ) {
			$removed_total = 0.0;
			$removed_tax   = 0.0;
			$removed_count = 0;
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
				return array(
					'total' => 0.0,
					'tax'   => 0.0,
					'count' => 0,
				);
			}
			$fee_items = (array) $order->get_items( 'fee' );
			foreach ( $fee_items as $item_id => $item ) {
				if ( ! $this->is_booster_gateway_fee_item( $item, $order ) ) {
					continue;
				}
				$removed_total += method_exists( $item, 'get_total' ) ? (float) $item->get_total() : 0.0;
				$removed_tax   += method_exists( $item, 'get_total_tax' ) ? (float) $item->get_total_tax() : 0.0;
				++$removed_count;
				if ( method_exists( $order, 'remove_item' ) ) {
					$order->remove_item( $item_id );
				}
				// Handle unsaved in-memory fee items keyed by temporary 'new:fee_lines*' keys in WC_Abstract_Order.
				$remaining_fees = (array) $order->get_items( 'fee' );
				if ( isset( $remaining_fees[ $item_id ] ) ) {
					if ( method_exists( $item, 'set_amount' ) ) {
						$item->set_amount( 0 );
					}
					if ( method_exists( $item, 'set_total' ) ) {
						$item->set_total( 0 );
					}
					if ( method_exists( $item, 'set_total_tax' ) ) {
						$item->set_total_tax( 0 );
					}
					if ( class_exists( 'ReflectionProperty' ) && is_a( $order, 'WC_Abstract_Order' ) ) {
						try {
							$items_prop = new ReflectionProperty( 'WC_Abstract_Order', 'items' );
							if ( PHP_VERSION_ID < 80100 ) {
								$items_prop->setAccessible( true );
							}
							$internal_items = $items_prop->getValue( $order );
							if ( is_array( $internal_items ) && isset( $internal_items['fee_lines'][ $item_id ] ) ) {
								unset( $internal_items['fee_lines'][ $item_id ] );
								$items_prop->setValue( $order, $internal_items );
							}
						} catch ( Exception $e ) {
							unset( $e );
						}
					}
				}
			}
			return array(
				'total' => $removed_total,
				'tax'   => $removed_tax,
				'count' => $removed_count,
			);
		}

		/**
		 * Check whether an order is unpaid and eligible for order-pay gateway fee recalculation.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order Order object.
		 * @return bool
		 */
		private function is_order_eligible_for_pay_recalculation( $order ) {
			if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
				return false;
			}
			if ( method_exists( $order, 'is_paid' ) && $order->is_paid() ) {
				return false;
			}
			$pending_statuses   = function_exists( 'wc_get_is_pending_statuses' ) ? (array) wc_get_is_pending_statuses() : array( 'pending' );
			$pending_statuses[] = 'failed';
			$pending_statuses   = array_values( array_unique( $pending_statuses ) );
			if ( method_exists( $order, 'has_status' ) && $order->has_status( $pending_statuses ) ) {
				return true;
			}
			if ( method_exists( $order, 'needs_payment' ) && $order->needs_payment() ) {
				return true;
			}
			return false;
		}

		/**
		 * Get available payment gateways for order-pay validation.
		 *
		 * @param WC_Order $order Authenticated order context.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @return array
		 */
		/**
		 * Own one order mutation without an expiring or reconnect-sensitive lock.
		 *
		 * @param int $order_id Order identity.
		 * @return array|false Exact unique option owner or refusal.
		 */
		private function acquire_order_fee_lock( $order_id ) {
			global $wpdb;
			$name   = 'wcj_order_pay_fee_lock_' . $order_id;
			$token  = wp_generate_uuid4();
			$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $name, $token ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic INSERT IGNORE acquires the per-order token lock; a cached read cannot serialize writers.
			return 1 === $result ? array(
				'name'     => $name,
				'token'    => $token,
				'order_id' => $order_id,
			) : false;
		}

		/**
		 * Release only the exact owner after a verified or non-writing outcome.
		 *
		 * @param array $lock Exact owner, never a timeout-based takeover.
		 * @return bool Whether the one owned row was removed.
		 */
		private function release_order_fee_lock( $lock ) {
			global $wpdb;
			return 1 === $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s", $lock['name'], $lock['token'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic token-matched DELETE releases only the current owner; cached reads cannot establish ownership.
		}

		/**
		 * Read an uncached Woo order without changing the configured cache policy.
		 *
		 * @param int $order_id Persisted order identity.
		 * @return WC_Order Fresh CRUD order, not a factory-cached object.
		 */
		private function read_fresh_fee_order( $order_id ) {
			clean_post_cache( $order_id );
			wp_cache_delete( 'order-items-' . $order_id, 'orders' );
			$without_cache = static function () {
				return 'no';
			};
			add_filter( 'pre_option_woocommerce_hpos_datastore_caching_enabled', $without_cache, PHP_INT_MAX );
			try {
				$order = new WC_Order( $order_id );
				$order->read_meta_data( true );
				foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) ) as $item ) {
					$item->read_meta_data( true );
				}
				return $order;
			} finally {
				remove_filter( 'pre_option_woocommerce_hpos_datastore_caching_enabled', $without_cache, PHP_INT_MAX );
			}
		}

		/**
		 * Bind confirmation to this order, exact method, amount and revision.
		 *
		 * @param WC_Order $order Current order.
		 * @return string Nonce action; no order key is printed by this helper.
		 */
		private function order_pay_confirmation_action( $order ) {
			return 'wcj-order-pay-fees-' . hash( 'sha256', wp_json_encode( array( $order->get_id(), $order->get_order_key(), $order->get_payment_method(), $order->get_total(), $order->get_currency(), $order->get_meta( '_wcj_gateway_fee_revision', true ) ) ) );
		}




		/**
		 * Stop before core's retained object can be saved or passed to a gateway.
		 *
		 * @param WC_Order $order Authenticated core order.
		 * @return void This branch redirects and exits.
		 */
		private function refuse_order_pay_fees( $order ) {
			wc_add_notice( esc_html__( 'Payment fees could not be verified. Please contact the store before paying.', 'woocommerce-jetpack' ), 'error' );
			wp_safe_redirect( $order->get_checkout_payment_url() );
			exit;
		}

		/**
		 * Project supported public data without losing scalar types or opaque item state.
		 *
		 * @param mixed $value Public order data.
		 * @param int   $depth Current recursion depth.
		 * @return array Typed value projection.
		 * @throws RuntimeException When public state cannot be compared safely.
		 */
		private function project_order_fee_value( $value, $depth = 0 ) {
			if ( $depth > 32 ) {
				throw new RuntimeException( 'Order fee state exceeds the supported depth.' );
			}
			if ( is_array( $value ) ) {
				$entries = array();
				foreach ( $value as $key => $entry ) {
					$entries[] = array( gettype( $key ), $key, $this->project_order_fee_value( $entry, $depth + 1 ) );
				}
				return array( 'array', $entries );
			}
			if ( $value instanceof WC_Meta_Data ) {
				if ( array() !== $value->get_changes() ) {
					throw new RuntimeException( 'Order fee metadata has pending changes.' );
				}
				$record = $value->get_data();
				if ( ! is_array( $record ) || 3 !== count( $record ) || ! array_key_exists( 'id', $record ) || ! array_key_exists( 'key', $record ) || ! array_key_exists( 'value', $record ) ) {
					throw new RuntimeException( 'Order fee metadata record cannot be verified.' );
				}
				// Woo appends the ID on save but prepends it on read; only this fixed envelope is unordered.
				$record = array(
					'id'    => $record['id'],
					'key'   => $record['key'],
					'value' => $record['value'],
				);
				return array( 'meta', $this->project_order_fee_value( $record, $depth + 1 ) );
			}
			if ( $value instanceof DateTimeInterface ) {
				return array( 'date', $value->format( 'U.u' ), $value->getTimezone()->getName() );
			}
			if ( null === $value || is_bool( $value ) || is_int( $value ) || is_string( $value ) || ( is_float( $value ) && is_finite( $value ) ) ) {
				return array( gettype( $value ), $value );
			}
			throw new RuntimeException( 'Order fee state contains an unsupported value.' );
		}

		/**
		 * Order Woo metadata records by their persisted identity, not read/cache order.
		 *
		 * @param array $metadata Woo metadata collection, never a nested metadata value.
		 * @return array All records retained, including duplicate keys or values.
		 * @throws RuntimeException When persisted record identity cannot be verified.
		 */
		private function get_order_fee_metadata_snapshot( $metadata ) {
			$records = array();
			foreach ( $metadata as $meta ) {
				if ( ! ( $meta instanceof WC_Meta_Data ) ) {
					throw new RuntimeException( 'Order fee metadata collection cannot be verified.' );
				}
				$record = $meta->get_data();
				if ( ! isset( $record['id'] ) || ! is_int( $record['id'] ) || $record['id'] <= 0 || isset( $records[ $record['id'] ] ) ) {
					throw new RuntimeException( 'Order fee metadata identity cannot be verified.' );
				}
				$records[ $record['id'] ] = $meta;
			}
			ksort( $records, SORT_NUMERIC );
			return array_values( $records );
		}

		/**
		 * Compare clean public order, item and metadata state against fresh persistence.
		 *
		 * @param WC_Order $order Order to inspect without changing its pending data.
		 * @return array Typed order projection.
		 * @throws RuntimeException When an order or item has pending changes.
		 */
		private function get_order_fee_snapshot( $order ) {
			if ( array() !== $order->get_changes() ) {
				throw new RuntimeException( 'Order fee order has pending changes.' );
			}
			$data              = $order->get_data();
			$data['meta_data'] = $this->get_order_fee_metadata_snapshot( $order->get_meta_data() );
			$groups            = array(
				'line_items'     => 'line_item',
				'tax_lines'      => 'tax',
				'shipping_lines' => 'shipping',
				'fee_lines'      => 'fee',
				'coupon_lines'   => 'coupon',
			);
			foreach ( $groups as $group => $type ) {
				$data[ $group ] = array();
				foreach ( $order->get_items( $type ) as $key => $item ) {
					if ( array() !== $item->get_changes() || $item->get_id() <= 0 || $type !== $item->get_type() ) {
						throw new RuntimeException( 'Order fee item state cannot be verified.' );
					}
					$item_data              = $item->get_data();
					$item_data['meta_data'] = $this->get_order_fee_metadata_snapshot( $item->get_meta_data() );
					$data[ $group ][ $key ] = array( $item->get_id(), $item->get_type(), $item_data );
				}
			}
			return $this->project_order_fee_value( $data );
		}

		/**
		 * Reflect verified fee results without saving the caller's pending item queues.
		 *
		 * @param WC_Order $order Caller-owned order, never persisted here.
		 * @param WC_Order $persisted Freshly verified authoritative order.
		 * @return void
		 * @throws RuntimeException When the caller gained a pending date change.
		 */
		private function reflect_order_fee_result( $order, $persisted ) {
			if ( array_key_exists( 'date_modified', $order->get_changes() ) ) {
				throw new RuntimeException( 'Order fee caller date has pending changes.' );
			}
			$fields = array( 'payment_method', 'payment_method_title', 'total', 'cart_tax', 'shipping_total', 'shipping_tax', 'discount_total', 'discount_tax' );
			foreach ( $fields as $field ) {
				$getter = 'get_' . $field;
				$setter = 'set_' . $field;
				$order->{$setter}( $persisted->{$getter}( 'edit' ) );
			}
			// Import the verified date as baseline data, not a new caller write request.
			$object_read = $order->get_object_read();
			try {
				$order->set_object_read( false );
				$order->set_date_modified( $persisted->get_date_modified( 'edit' ) );
			} finally {
				$order->set_object_read( $object_read );
			}
			// Totals can change line, shipping and tax items as well as the fee itself.
			foreach ( array( 'line_item', 'tax', 'shipping', 'fee', 'coupon' ) as $type ) {
				$caller_items   = $order->get_items( $type );
				$verified_items = $persisted->get_items( $type );
				foreach ( $caller_items as $item_id => $item ) {
					if ( ! isset( $verified_items[ $item_id ] ) ) {
						$order->remove_item( $item_id );
					}
				}
				foreach ( $verified_items as $item ) {
					// Preserve persisted IDs and metadata IDs; cloning WC_Data can clear metadata IDs.
					$order->add_item( $item );
				}
			}
			$revision_key       = '_wcj_gateway_fee_revision';
			$caller_revisions   = array();
			$verified_revisions = array();
			foreach ( $order->get_meta_data() as $meta ) {
				if ( $revision_key === $meta->key ) {
					$caller_revisions[] = $meta;
				}
			}
			foreach ( $persisted->get_meta_data() as $meta ) {
				if ( $revision_key === $meta->key ) {
					$verified_revisions[] = $meta;
				}
			}
			// Reconcile only this key by verified positive IDs, never key-only duplicate collapse.
			$caller_revisions = $this->get_order_fee_metadata_snapshot( $caller_revisions );
			$verified_by_id   = array();
			foreach ( $this->get_order_fee_metadata_snapshot( $verified_revisions ) as $meta ) {
				$verified_by_id[ $meta->id ] = $meta;
			}
			foreach ( $caller_revisions as $meta ) {
				if ( ! isset( $verified_by_id[ $meta->id ] ) ) {
					$order->delete_meta_data_by_mid( $meta->id );
					continue;
				}
				$verified = $verified_by_id[ $meta->id ];
				if ( $meta->value !== $verified->value ) {
					$order->update_meta_data( $revision_key, $verified->value, $meta->id );
				}
				unset( $verified_by_id[ $meta->id ] );
			}
			foreach ( $verified_by_id as $meta ) {
				// Newly persisted revisions keep their IDs, not new caller inserts.
				$order->set_meta_data( array( $meta->get_data() ) );
			}
		}

		/**
		 * Mutate only an order proven equal to a fresh authoritative CRUD read.
		 *
		 * @param WC_Order    $order   Caller retained by Woo core.
		 * @param string|null $gateway Target available gateway.
		 * @param bool        $save    Must be true; false is rejected before any write.
		 * @return bool Whether persistence and readback both completed.
		 */
		public function recalculate_order_gateway_fees( $order, $gateway = null, $save = true ) {
			if ( true !== $save || ! $this->is_order_eligible_for_pay_recalculation( $order ) ) {
				return false;
			}
			$lock = $this->acquire_order_fee_lock( (int) $order->get_id() );
			if ( ! $lock ) {
				$this->set_fee_decision( (string) $gateway, 'order_fee_locked' );
				return false;
			}
			$write_started     = false;
			$verified          = false;
			$release_attempted = false;
			$request_lock      = $this->order_pay_fee_request_active;
			if ( $request_lock ) {
				// PHP shutdown covers normal return, redirect/exit and exceptions, not a killed process.
				register_shutdown_function(
					function () use ( $lock, &$write_started, &$verified, &$release_attempted ) {
						if ( ! $release_attempted && ( ! $write_started || $verified ) ) {
							$release_attempted = true;
							$this->release_order_fee_lock( $lock );
						}
					}
				);
			}
			try {
				$fresh    = $this->read_fresh_fee_order( $order->get_id() );
				$original = $this->get_order_fee_snapshot( $order );
				if ( $this->get_order_fee_snapshot( $fresh ) !== $original || ! $this->is_order_eligible_for_pay_recalculation( $fresh ) ) {
					$this->set_fee_decision( (string) $gateway, 'stale_order' );
					return false;
				}
				if ( ! $this->recalculate_locked_order_gateway_fees( $fresh, $gateway, $write_started ) ) {
					return false;
				}
				$persisted = $this->read_fresh_fee_order( $order->get_id() );
				$expected  = $this->get_order_fee_snapshot( $fresh );
				if ( $this->get_order_fee_snapshot( $persisted ) !== $expected ) {
					$this->set_fee_decision( (string) $gateway, 'order_fee_write_unverified' );
					return false;
				}
				$verified = true;
				$this->reflect_order_fee_result( $order, $persisted );
				return true;
			} catch ( Throwable $error ) {
				$this->set_fee_decision( (string) $gateway, 'order_fee_write_unverified' );
				return false;
			} finally {
				if ( ( ! $write_started || $verified ) && ! ( $request_lock && $verified ) ) {
					$release_attempted = true;
					$this->release_order_fee_lock( $lock );
				}
			}
		}

		/**
		 * Resolve availability in the authenticated order context and restore it.
		 *
		 * @param WC_Order $order Authenticated order.
		 * @return array Filtered available gateways, including an empty refusal.
		 */
		private function get_order_pay_available_gateways( $order ) {
			global $wp, $wp_query;
			if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways || ! is_object( $wp ) || ! is_object( $wp_query ) || ! isset( $wp->query_vars, $wp_query->query_vars ) ) {
				return array();
			}
			$wp_vars    = $wp->query_vars;
			$query_vars = $wp_query->query_vars;
			$customer   = WC()->customer;
			$previous   = array();
			$location   = array();
			try {
				$wp->query_vars['order-pay']       = $order->get_id();
				$wp_query->query_vars['order-pay'] = $order->get_id();
				foreach ( array( 'billing_country', 'billing_state', 'billing_postcode', 'billing_city' ) as $key ) {
					$getter           = 'get_' . $key;
					$previous[ $key ] = $customer->$getter();
					$location[ $key ] = $order->$getter();
				}
				$customer->set_props( $location );
				$available = WC()->payment_gateways->get_available_payment_gateways();
				return is_array( $available ) ? $available : array();
			} finally {
				$wp->query_vars       = $wp_vars;
				$wp_query->query_vars = $query_vars;
				if ( $previous ) {
					$customer->set_props( $previous );
				}
			}
		}

		/**
		 * Resolve the user role applicable to an order for role-based gateway fees.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order Order object.
		 * @return string
		 */
		private function get_order_user_role( $order ) {
			if ( is_object( $order ) && method_exists( $order, 'get_user' ) ) {
				$order_user = $order->get_user();
				if ( is_object( $order_user ) && ! empty( $order_user->roles ) && is_array( $order_user->roles ) ) {
					$first_role = reset( $order_user->roles );
					if ( is_string( $first_role ) && '' !== $first_role ) {
						return $first_role;
					}
				}
			}
			if ( is_user_logged_in() ) {
				$user = wp_get_current_user();
				if ( is_object( $user ) && isset( $user->roles[0] ) && '' !== (string) $user->roles[0] ) {
					return (string) $user->roles[0];
				}
			}
			return 'guest';
		}

		/**
		 * Build the order base amounts (excluding gateway fees) for gateway fee calculation.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order             Order object (after removing Booster gateway fees).
		 * @param float    $prior_order_total Order total prior to removing Booster gateway fees.
		 * @param float    $removed_fee_total Removed Booster gateway fee total.
		 * @param float    $removed_fee_tax   Removed Booster gateway fee tax.
		 * @param array    $excluded_fees     Fees still present but excluded from the base.
		 * @param bool     $cache_base        Whether to cache a newly inferred legacy base.
		 * @return array
		 */
		private function get_order_fee_base_context( $order, $prior_order_total, $removed_fee_total, $removed_fee_tax, $excluded_fees = array(), $cache_base = true ) {
			$shipping_total = method_exists( $order, 'get_shipping_total' ) ? (float) $order->get_shipping_total() : 0.0;
			$shipping_tax   = method_exists( $order, 'get_shipping_tax' ) ? (float) $order->get_shipping_tax() : 0.0;
			$discount_total = method_exists( $order, 'get_discount_total' ) ? (float) $order->get_discount_total() : 0.0;
			$line_items     = method_exists( $order, 'get_items' ) ? (array) $order->get_items( 'line_item' ) : array();

			if ( ! empty( $line_items ) ) {
				$sum_line_total    = 0.0;
				$sum_line_subtotal = 0.0;
				$subtotal_tax      = 0.0;
				foreach ( $line_items as $line_item ) {
					if ( is_object( $line_item ) && method_exists( $line_item, 'get_total' ) ) {
						$line_total         = (float) $line_item->get_total();
						$line_subtotal      = method_exists( $line_item, 'get_subtotal' ) ? (float) $line_item->get_subtotal() : $line_total;
						$sum_line_total    += $line_total;
						$sum_line_subtotal += $line_subtotal;
						$subtotal_tax      += method_exists( $line_item, 'get_subtotal_tax' )
							? (float) $line_item->get_subtotal_tax()
							: ( method_exists( $line_item, 'get_total_tax' ) ? (float) $line_item->get_total_tax() : 0.0 );
					}
				}
				if ( $discount_total > 0.0 && $sum_line_subtotal >= $sum_line_total ) {
					$contents_total = min( $sum_line_total, max( 0.0, $sum_line_subtotal - $discount_total ) );
				} else {
					$contents_total = $sum_line_total;
				}
				return array(
					'contents_total' => $contents_total,
					'subtotal_tax'   => $subtotal_tax,
					'shipping_total' => $shipping_total,
					'shipping_tax'   => $shipping_tax,
					'has_line_items' => true,
				);
			}

			$stored_base = method_exists( $order, 'get_meta' ) ? $order->get_meta( '_wcj_order_pay_base_contents_total', true ) : '';
			if ( '' !== $stored_base && is_numeric( $stored_base ) ) {
				$contents_total = max( 0.0, (float) $stored_base );
			} else {
				$subtotal = method_exists( $order, 'get_subtotal' ) ? (float) $order->get_subtotal() : 0.0;
				if ( $subtotal > 0.0 ) {
					$contents_total = max( 0.0, $subtotal - $discount_total );
				} else {
					$other_fees_total = 0.0;
					$other_fees_tax   = 0.0;
					foreach ( (array) $order->get_items( 'fee' ) as $fee_item ) {
						if ( is_object( $fee_item ) && ! in_array( $fee_item, $excluded_fees, true ) ) {
							$other_fees_total += method_exists( $fee_item, 'get_total' ) ? (float) $fee_item->get_total() : 0.0;
							$other_fees_tax   += method_exists( $fee_item, 'get_total_tax' ) ? (float) $fee_item->get_total_tax() : 0.0;
						}
					}
					$cart_tax       = method_exists( $order, 'get_cart_tax' ) ? (float) $order->get_cart_tax() : 0.0;
					$non_fee_tax    = max( 0.0, $cart_tax - $removed_fee_tax - $other_fees_tax );
					$contents_total = max(
						0.0,
						$prior_order_total - $removed_fee_total - $removed_fee_tax - $other_fees_total - $other_fees_tax - $shipping_total - $shipping_tax - $non_fee_tax
					);
				}
				if ( $cache_base && method_exists( $order, 'update_meta_data' ) ) {
					$order->update_meta_data( '_wcj_order_pay_base_contents_total', (string) $contents_total );
				}
			}

			$cart_tax     = method_exists( $order, 'get_cart_tax' ) ? (float) $order->get_cart_tax() : 0.0;
			$subtotal_tax = max( 0.0, $cart_tax - $removed_fee_tax );

			return array(
				'contents_total' => $contents_total,
				'subtotal_tax'   => $subtotal_tax,
				'shipping_total' => $shipping_total,
				'shipping_tax'   => $shipping_tax,
				'has_line_items' => false,
			);
		}

		/**
		 * Convert a configured base-currency fee amount to the persisted order currency.
		 * Does not consult or mutate the customer's current currency/session.
		 *
		 * @param float    $price Configured base-currency amount.
		 * @param WC_Order $order Existing order.
		 * @throws RuntimeException When the configured exchange rate is unusable.
		 * @return float
		 */
		private function convert_order_gateway_fee_amount( $price, $order ) {
			$currency = (string) $order->get_currency( 'edit' );
			$rate     = w_c_j()->all_modules['multicurrency']->get_currency_exchange_rate( $currency );
			if ( ! is_numeric( $rate ) || (float) $rate <= 0.0 ) {
				throw new RuntimeException( esc_html__( 'Payment fee currency could not be verified.', 'woocommerce-jetpack' ) );
			}
			if ( '1' !== $rate ) {
				$price = (float) $price * (float) $rate;
				switch ( wcj_get_option( 'wcj_multicurrency_rounding', 'no_round' ) ) {
					case 'round':
						$price = round( $price, wcj_get_option( 'wcj_multicurrency_rounding_precision', absint( wcj_get_option( 'woocommerce_price_num_decimals', 2 ) ) ) );
						break;
					case 'round_up':
						$price = ceil( $price );
						break;
					case 'round_down':
						$price = floor( $price );
						break;
				}
			}
			return (float) $price;
		}

		/**
		 * Calculate the gateway fee specification for an existing order and gateway.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order        Order object.
		 * @param string   $gateway      Selected payment gateway ID.
		 * @param array    $base_context Precomputed order base amounts.
		 * @return array|null
		 */
		private function calculate_gateway_fee_for_order( $order, $gateway, $base_context ) {
			$current_gateway = is_string( $gateway ) ? trim( $gateway ) : '';
			if ( '' === $current_gateway ) {
				$this->set_fee_decision( '', 'no_gateway_selected' );
				return null;
			}
			if ( false !== strpos( $current_gateway, 'klarna' ) && 'yes' === wcj_get_option( 'wcj_enable_payment_gateway_charge_discount', 'no' ) ) {
				$current_gateway = 'klarna_payments';
			}

			$fee_text        = do_shortcode( (string) $this->wcj_get_option( 'text', $current_gateway ) );
			$min_cart_amount = (float) $this->wcj_get_option( 'min_cart_amount', $current_gateway );
			$max_cart_amount = (float) $this->wcj_get_option( 'max_cart_amount', $current_gateway );
			if ( '' === $fee_text ) {
				$this->set_fee_decision( $current_gateway, 'not_configured' );
				return null;
			}

			$multicurrency_enabled = isset( w_c_j()->all_modules['multicurrency'] ) && w_c_j()->all_modules['multicurrency']->is_enabled();
			if ( $multicurrency_enabled ) {
				$min_cart_amount = $this->convert_order_gateway_fee_amount( $min_cart_amount, $order );
				$max_cart_amount = $this->convert_order_gateway_fee_amount( $max_cart_amount, $order );
			}

			$total_in_order  = ( 'no' === $this->wcj_get_option( 'exclude_shipping', $current_gateway ) )
				? ( (float) $base_context['contents_total'] + (float) $base_context['shipping_total'] )
				: (float) $base_context['contents_total'];
			$total_in_order += ( 'no' === $this->wcj_get_option( 'include_taxes', $current_gateway ) )
				? 0.0
				: ( (float) $base_context['subtotal_tax'] + (float) $base_context['shipping_tax'] );

			if ( $total_in_order < $min_cart_amount || ( 0.0 !== $max_cart_amount && $total_in_order > $max_cart_amount ) ) {
				$this->set_fee_decision( $current_gateway, 'outside_cart_amount' );
				return null;
			}
			if ( ! $this->check_order_products( $current_gateway, $order ) ) {
				$this->set_fee_decision( $current_gateway, 'product_condition_not_met' );
				return null;
			}

			$userwise_options                 = (array) wcj_get_option( 'wcj_enable_payment_gateway_charge_discount_userwise', array() );
			$enable_user_wise_charge_discount = isset( $userwise_options[ $current_gateway ] ) ? $userwise_options[ $current_gateway ] : 'no';
			if ( 'yes' === $enable_user_wise_charge_discount ) {
				$user_role                  = $this->get_order_user_role( $order );
				$role_values                = (array) wcj_get_option( 'wcj_gateways_fees_' . $user_role, array() );
				$additional_discountby_user = isset( $role_values[ $current_gateway ] ) ? $role_values[ $current_gateway ] : '';
				$fee_value                  = $additional_discountby_user ? $additional_discountby_user : $this->wcj_get_option( 'value', $current_gateway );
			} else {
				$fee_value = $this->wcj_get_option( 'value', $current_gateway );
			}

			$fee_type         = $this->wcj_get_option( 'type', $current_gateway );
			$final_fee_to_add = 0.0;
			switch ( $fee_type ) {
				case 'fixed':
					if ( $multicurrency_enabled ) {
						$fee_value = $this->convert_order_gateway_fee_amount( $fee_value, $order );
					}
					$final_fee_to_add = (float) $fee_value;
					break;
				case 'percent':
					$final_fee_to_add = ( (float) $fee_value / 100 ) * $total_in_order;
					if ( 'yes' === $this->wcj_get_option( 'round', $current_gateway ) ) {
						$final_fee_to_add = round( $final_fee_to_add, (int) $this->wcj_get_option( 'round_precision', $current_gateway ) );
					}
					break;
			}

			$decimals   = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : (int) wcj_get_option( 'woocommerce_price_num_decimals', 2 );
			$fee_amount = function_exists( 'wc_format_decimal' )
				? (float) wc_format_decimal( $final_fee_to_add, $decimals )
				: round( (float) $final_fee_to_add, $decimals );

			if ( 0.0 === $fee_amount ) {
				$this->set_fee_decision( $current_gateway, 'zero_or_invalid_amount' );
				return null;
			}

			$taxable        = ( 'yes' === $this->wcj_get_option( 'is_taxable', $current_gateway ) );
			$tax_class_name = '';
			if ( $taxable && class_exists( 'WC_Tax' ) ) {
				$tax_class_id    = $this->wcj_get_option( 'tax_class_id', $current_gateway );
				$tax_class_names = array_merge( array( '' ), WC_Tax::get_tax_class_slugs() );
				$tax_class_name  = isset( $tax_class_names[ $tax_class_id ] ) ? $tax_class_names[ $tax_class_id ] : '';
			}

			return array(
				'id'        => 'wcj_gateway_fee_' . sanitize_key( $current_gateway ),
				'gateway'   => sanitize_key( $current_gateway ),
				'name'      => $fee_text,
				'amount'    => $fee_amount,
				'taxable'   => $taxable,
				'tax_class' => $tax_class_name,
			);
		}

		/**
		 * Mirror WooCommerce's ordered negative-fee cap without saving or retaxing items.
		 *
		 * Booster fees are replaced at the end of the retained fee sequence.
		 *
		 * @param WC_Order $order Authoritative order under the fee mutation lock.
		 * @param float    $amount Configured fee amount before the core cap.
		 * @return float Capped total; the configured amount remains unchanged.
		 */
		private function get_order_gateway_fee_total( $order, $amount ) {
			if ( 0 <= $amount ) {
				return $amount;
			}
			$line_totals = array();
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$line_totals[] = wc_add_number_precision( (float) $item->get_total(), false );
			}
			$cart_total     = (float) wc_remove_number_precision( $order->get_rounded_items_total( $line_totals ) );
			$price_decimals = wc_get_price_decimals();
			$shipping_total = 0.0;
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				$shipping_total += \Automattic\WooCommerce\Utilities\NumberUtil::round( $shipping->get_total(), $price_decimals );
			}
			$fees_total = 0.0;
			foreach ( $order->get_items( 'fee' ) as $item ) {
				if ( $this->is_booster_gateway_fee_item( $item ) ) {
					continue;
				}
				$fee_total = (float) $item->get_total();
				if ( 0 > $fee_total ) {
					$max_discount = \Automattic\WooCommerce\Utilities\NumberUtil::round( $cart_total + $fees_total + $shipping_total, $price_decimals ) * -1;
					if ( $fee_total < $max_discount && 0 > $max_discount ) {
						$fee_total = $max_discount;
					}
				}
				$fees_total += $fee_total;
			}
			$max_discount = \Automattic\WooCommerce\Utilities\NumberUtil::round( $cart_total + $fees_total + $shipping_total, $price_decimals ) * -1;
			return ( $amount < $max_discount && 0 > $max_discount ) ? $max_discount : $amount;
		}

		/**
		 * Refuse a fee change that would re-cap another plugin's retained discount.
		 *
		 * Follow core fee ordering without changing stored totals or historical taxes.
		 *
		 * @param WC_Order $order Authoritative order under the fee mutation lock.
		 * @return bool Whether every retained fee keeps its stored total.
		 */
		private function has_stable_retained_gateway_fee_totals( $order ) {
			$line_totals = array();
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$line_totals[] = wc_add_number_precision( (float) $item->get_total(), false );
			}
			$cart_total     = (float) wc_remove_number_precision( $order->get_rounded_items_total( $line_totals ) );
			$price_decimals = wc_get_price_decimals();
			$shipping_total = 0.0;
			foreach ( $order->get_items( 'shipping' ) as $shipping ) {
				$shipping_total += \Automattic\WooCommerce\Utilities\NumberUtil::round( $shipping->get_total(), $price_decimals );
			}
			$fees_total = 0.0;
			foreach ( $order->get_items( 'fee' ) as $item ) {
				if ( $this->is_booster_gateway_fee_item( $item ) ) {
					continue;
				}
				$fee_total = (float) $item->get_total();
				if ( 0 > $fee_total ) {
					$max_discount = \Automattic\WooCommerce\Utilities\NumberUtil::round( $cart_total + $fees_total + $shipping_total, $price_decimals ) * -1;
					if ( $fee_total < $max_discount && 0 > $max_discount ) {
						return false;
					}
				}
				$fees_total += $fee_total;
			}
			return true;
		}

		/**
		 * Recalculate Booster payment gateway fees on an existing unpaid order.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order    $order   Order object.
		 * @param string|null $gateway Target gateway ID (defaults to order's payment method).
		 * @param bool        $write_started Whether persistence may have begun.
		 * @throws RuntimeException When fee or order persistence cannot be verified.
		 * @return bool
		 */
		private function recalculate_locked_order_gateway_fees( $order, $gateway, &$write_started ) {
			if ( ! $this->is_order_eligible_for_pay_recalculation( $order ) ) {
				return false;
			}
			++$this->fee_calculation_count;
			if ( empty( $this->options ) || empty( $this->defaults ) ) {
				$this->init_options();
			}

			$target_gateway = ( is_string( $gateway ) && '' !== trim( $gateway ) )
				? trim( $gateway )
				: ( method_exists( $order, 'get_payment_method' ) ? (string) $order->get_payment_method() : '' );

			$available_gateways = $this->get_order_pay_available_gateways( $order );
			if ( '' === $target_gateway || ! isset( $available_gateways[ $target_gateway ] ) ) {
				$this->set_fee_decision( $target_gateway, 'invalid_payment_method' );
				return false;
			}
			$legacy_migrated = false;
			if ( ! $this->prepare_legacy_gateway_fee( $order, $legacy_migrated ) ) {
				return false;
			}

			$prior_order_total    = method_exists( $order, 'get_total' ) ? (float) $order->get_total() : 0.0;
			$prior_shipping_total = method_exists( $order, 'get_shipping_total' ) ? (float) $order->get_shipping_total() : 0.0;
			$prior_shipping_tax   = method_exists( $order, 'get_shipping_tax' ) ? (float) $order->get_shipping_tax() : 0.0;
			$prior_discount_total = method_exists( $order, 'get_discount_total' ) ? (float) $order->get_discount_total() : 0.0;
			$prior_discount_tax   = method_exists( $order, 'get_discount_tax' ) ? (float) $order->get_discount_tax() : 0.0;
			$has_shipping_items   = method_exists( $order, 'get_items' ) && ! empty( $order->get_items( 'shipping' ) );

			$owned_fees  = array();
			$owned_total = 0.0;
			$owned_tax   = 0.0;
			foreach ( $order->get_items( 'fee' ) as $fee_item ) {
				if ( $this->is_booster_gateway_fee_item( $fee_item ) ) {
					$owned_fees[] = $fee_item;
					$owned_total += (float) $fee_item->get_total();
					$owned_tax   += (float) $fee_item->get_total_tax();
				}
			}
			$base_context = $this->get_order_fee_base_context( $order, $prior_order_total, $owned_total, $owned_tax, $owned_fees, false );
			$fee_data     = $this->calculate_gateway_fee_for_order( $order, $target_gateway, $base_context );
			$fee_total    = is_array( $fee_data ) ? $this->get_order_gateway_fee_total( $order, $fee_data['amount'] ) : 0.0;
			$fee_changed  = ! empty( $owned_fees ) || is_array( $fee_data );
			if ( 1 === count( $owned_fees ) && is_array( $fee_data ) ) {
				$existing_fee = $owned_fees[0];
				$fee_changed  = (string) $existing_fee->get_name() !== $fee_data['name']
					|| (float) $existing_fee->get_total() !== (float) $fee_total
					|| (float) $existing_fee->get_amount() !== (float) $fee_data['amount']
					|| $existing_fee->get_tax_status() !== ( $fee_data['taxable'] ? 'taxable' : 'none' )
					|| $existing_fee->get_tax_class() !== ( $fee_data['taxable'] ? $fee_data['tax_class'] : '' )
					|| 'yes' !== $existing_fee->get_meta( '_wcj_gateway_fee', true )
					|| $existing_fee->get_meta( '_wcj_gateway_fee_gateway', true ) !== $fee_data['gateway'];
			}
			$fee_changed     = $fee_changed || $legacy_migrated;
			$gateway_changed = $target_gateway !== (string) $order->get_payment_method();
			if ( ! $fee_changed && ! $gateway_changed ) {
				return true;
			}

			if ( $fee_changed && ! $this->has_stable_retained_gateway_fee_totals( $order ) ) {
				$this->set_fee_decision( $target_gateway, 'retained_fee_requires_review' );
				return false;
			}

			$write_started = true;
			$order->set_payment_method( $available_gateways[ $target_gateway ] );
			if ( ! $fee_changed ) {
				$order->update_meta_data( '_wcj_gateway_fee_revision', wp_generate_uuid4() );
				if ( (int) $order->save() !== (int) $order->get_id() ) {
					throw new RuntimeException( esc_html__( 'Payment fee persistence could not be verified.', 'woocommerce-jetpack' ) );
				}
				return true;
			}
			$this->remove_order_gateway_fees( $order );
			if ( empty( $base_context['has_line_items'] ) && '' === $order->get_meta( '_wcj_order_pay_base_contents_total', true ) ) {
				$order->update_meta_data( '_wcj_order_pay_base_contents_total', (string) $base_context['contents_total'] );
			}
			if ( is_array( $fee_data ) && class_exists( 'WC_Order_Item_Fee' ) ) {
				$fee_item = new WC_Order_Item_Fee();
				$fee_item->set_name( $fee_data['name'] );
				$fee_item->set_amount( $fee_data['amount'] );
				$fee_item->set_total( $fee_total );
				if ( method_exists( $fee_item, 'set_tax_status' ) ) {
					$fee_item->set_tax_status( ! empty( $fee_data['taxable'] ) ? 'taxable' : 'none' );
				}
				if ( method_exists( $fee_item, 'set_tax_class' ) ) {
					$fee_item->set_tax_class( ! empty( $fee_data['taxable'] ) ? $fee_data['tax_class'] : '' );
				}
				if ( method_exists( $fee_item, 'update_meta_data' ) ) {
					$fee_item->update_meta_data( '_wcj_gateway_fee', 'yes' );
					$fee_item->update_meta_data( '_wcj_gateway_fee_gateway', $fee_data['gateway'] );
				}
				$order->add_item( $fee_item );
				$is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt', true ), $order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Apply WooCommerce's existing VAT-exemption hook exactly as core; a Booster prefix would change tax semantics.
				if ( $is_vat_exempt ) {
					$fee_item->set_taxes( false );
				} else {
					$fee_item->calculate_taxes( $order->get_taxable_location() );
				}
				if ( method_exists( $order, 'get_id' ) && $order->get_id() > 0 && method_exists( $fee_item, 'save' ) ) {
					if ( method_exists( $fee_item, 'set_order_id' ) ) {
						$fee_item->set_order_id( $order->get_id() );
					}
					if ( $fee_item->save() <= 0 ) {
						throw new RuntimeException( esc_html__( 'Payment fee persistence could not be verified.', 'woocommerce-jetpack' ) );
					}
				}
				$this->set_fee_decision( $fee_data['gateway'], 'applied' );
			}

			$order->update_taxes();
			if ( method_exists( $order, 'calculate_totals' ) ) {
				$order->calculate_totals( false );
			}

			$decimals     = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : (int) wcj_get_option( 'woocommerce_price_num_decimals', 2 );
			$total_adjust = 0.0;
			$current_ship = method_exists( $order, 'get_shipping_total' ) ? (float) $order->get_shipping_total() : 0.0;
			$current_disc = method_exists( $order, 'get_discount_total' ) ? (float) $order->get_discount_total() : 0.0;
			if ( ! $has_shipping_items && $prior_shipping_total > 0.0 && 0.0 === $current_ship ) {
				if ( method_exists( $order, 'set_shipping_total' ) ) {
					$order->set_shipping_total( $prior_shipping_total );
				}
				if ( method_exists( $order, 'set_shipping_tax' ) && $prior_shipping_tax > 0.0 ) {
					$order->set_shipping_tax( $prior_shipping_tax );
				}
				$total_adjust += $prior_shipping_total + $prior_shipping_tax;
			}
			if ( $prior_discount_total > 0.0 && 0.0 === $current_disc && ! empty( $base_context['has_line_items'] ) ) {
				if ( method_exists( $order, 'set_discount_total' ) ) {
					$order->set_discount_total( $prior_discount_total );
				}
				if ( method_exists( $order, 'set_discount_tax' ) && $prior_discount_tax > 0.0 ) {
					$order->set_discount_tax( $prior_discount_tax );
				}
				$total_adjust -= ( $prior_discount_total + $prior_discount_tax );
			}
			if ( empty( $base_context['has_line_items'] ) && $base_context['contents_total'] > 0.0 ) {
				$total_adjust += (float) $base_context['contents_total'];
			}
			if ( 0.0 !== $total_adjust && method_exists( $order, 'set_total' ) && method_exists( $order, 'get_total' ) ) {
				$adjusted_total = max( 0.0, round( (float) $order->get_total() + $total_adjust, $decimals ) );
				$order->set_total( $adjusted_total );
			}

			$order->update_meta_data( '_wcj_gateway_fee_revision', wp_generate_uuid4() );
			if ( (int) $order->save() !== (int) $order->get_id() ) {
				throw new RuntimeException( esc_html__( 'Payment fee persistence could not be verified.', 'woocommerce-jetpack' ) );
			}
			return true;
		}

		/**
		 * Synchronize gateway fees on the order-pay form before rendering totals.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order              Order being paid for.
		 * @param string   $order_button_text  Order pay button text.
		 * @param array    $available_gateways Available payment gateways.
		 * @return void
		 */
		public function maybe_recalculate_order_pay_fees_on_load( $order, $order_button_text = '', $available_gateways = array() ) {
			unset( $order_button_text );
			if ( ! $this->is_order_eligible_for_pay_recalculation( $order ) ) {
				return;
			}
			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_POST['woocommerce_pay'] ) && empty( $_POST['payment_method'] ) ) {
				return;
			}
			$posted_gateway = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

			$target_gateway = '';
			if ( '' !== $posted_gateway && ( empty( $available_gateways ) || isset( $available_gateways[ $posted_gateway ] ) ) ) {
				$target_gateway = $posted_gateway;
			} elseif ( method_exists( $order, 'get_payment_method' ) ) {
				$order_gateway = (string) $order->get_payment_method();
				if ( '' !== $order_gateway && ( empty( $available_gateways ) || isset( $available_gateways[ $order_gateway ] ) ) ) {
					$target_gateway = $order_gateway;
				}
			}
			if ( '' === $target_gateway && is_array( $available_gateways ) && ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway_id => $gateway_obj ) {
					if ( is_object( $gateway_obj ) && ! empty( $gateway_obj->chosen ) ) {
						$target_gateway = (string) $gateway_id;
						break;
					}
				}
				if ( '' === $target_gateway ) {
					reset( $available_gateways );
					$first_key      = key( $available_gateways );
					$target_gateway = null !== $first_key ? (string) $first_key : '';
				}
			}

			if ( '' !== $target_gateway && is_array( $available_gateways ) && isset( $available_gateways[ $target_gateway ] ) ) {
				foreach ( $available_gateways as $gateway_id => $gateway_obj ) {
					if ( is_object( $gateway_obj ) ) {
						$gateway_obj->chosen = ( (string) $gateway_id === $target_gateway );
					}
				}
				if ( function_exists( 'WC' ) && WC()->session ) {
					WC()->session->set( 'chosen_payment_method', $target_gateway );
				}
			}

			if ( $this->recalculate_order_gateway_fees( $order, $target_gateway, true ) ) {
				printf( '<input type="hidden" form="order_review" name="wcj_order_pay_fee_confirmation" data-payment-method="%s" value="%s" />', esc_attr( $order->get_payment_method() ), esc_attr( wp_create_nonce( $this->order_pay_confirmation_action( $order ) ) ) );
			}
		}

		/**
		 * Validate and recalculate gateway fees on an unpaid order before payment processing.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @param WC_Order $order Order being paid for in WC_Form_Handler::pay_action().
		 * @return void
		 */
		public function recalculate_order_pay_fees_before_payment( $order ) {
			$amount = array( $order->get_payment_method(), $order->get_total(), $order->get_currency() );
			// Core has already checked the pay nonce, order key and needs_payment.
			$gateway      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
			$confirmation = isset( $_POST['wcj_order_pay_fee_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_order_pay_fee_confirmation'] ) ) : '';
			if ( '' === $gateway || $gateway !== $order->get_payment_method() || ! wp_verify_nonce( $confirmation, $this->order_pay_confirmation_action( $order ) ) ) {
				$this->refuse_order_pay_fees( $order );
			}
			$this->order_pay_fee_request_active = true;
			try {
				$verified = $this->recalculate_order_gateway_fees( $order, $gateway, true );
			} finally {
				$this->order_pay_fee_request_active = false;
			}
			if ( ! $verified || array( $order->get_payment_method(), $order->get_total(), $order->get_currency() ) !== $amount ) {
				$this->refuse_order_pay_fees( $order );
			}
		}

		/**
		 * AJAX handler to recalculate gateway fees and return updated order-pay totals rows.
		 *
		 * @version 9.0.0
		 * @since   9.0.0
		 * @return void
		 */
		public function ajax_update_order_pay_fees() {
			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			$nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( $order_id <= 0 || ! wp_verify_nonce( $nonce, 'wcj-update-order-pay-fees-' . $order_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'woocommerce-jetpack' ) ), 403 );
				return;
			}

			$order_key      = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
			$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
			$order          = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
			if (
				! $order ||
				(int) $order->get_id() !== $order_id ||
				'' === $order_key ||
				! hash_equals( (string) $order->get_order_key(), $order_key ) ||
				! $this->is_order_eligible_for_pay_recalculation( $order )
			) {
				wp_send_json_error( array( 'message' => __( 'Invalid order.', 'woocommerce-jetpack' ) ), 400 );
				return;
			}

			$customer_id = method_exists( $order, 'get_customer_id' ) ? (int) $order->get_customer_id() : 0;
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce order payment meta capability.
			if ( $customer_id > 0 && get_current_user_id() !== $customer_id && ! current_user_can( 'pay_for_order', $order_id ) && ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Unauthorized order access.', 'woocommerce-jetpack' ) ), 403 );
				return;
			}

			$confirmation = isset( $_POST['wcj_order_pay_fee_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_order_pay_fee_confirmation'] ) ) : '';
			if ( ! wp_verify_nonce( $confirmation, $this->order_pay_confirmation_action( $order ) ) ) {
				wp_send_json_error( array( 'message' => __( 'Payment fees changed. Please reload before paying.', 'woocommerce-jetpack' ) ), 409 );
				return;
			}

			if ( function_exists( 'WC' ) && WC()->session ) {
				WC()->session->set( 'chosen_payment_method', $payment_method );
			}

			if ( ! $this->recalculate_order_gateway_fees( $order, $payment_method, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Payment fees could not be verified. Please contact the store before paying.', 'woocommerce-jetpack' ) ), 400 );
				return;
			}

			$totals_html = '';
			if ( method_exists( $order, 'get_order_item_totals' ) ) {
				$totals = $order->get_order_item_totals();
				if ( is_array( $totals ) ) {
					ob_start();
					foreach ( $totals as $total ) {
						$label = isset( $total['label'] ) ? $total['label'] : '';
						$value = isset( $total['value'] ) ? $total['value'] : '';
						?>
						<tr>
							<th scope="row" colspan="2"><?php echo wp_kses_post( $label ); ?></th>
							<td class="product-total"><?php echo wp_kses_post( $value ); ?></td>
						</tr>
						<?php
					}
					$totals_html = (string) ob_get_clean();
				}
			}

			wp_send_json_success(
				array(
					'totals_html'    => $totals_html,
					'total'          => method_exists( $order, 'get_total' ) ? $order->get_total() : '',
					'payment_method' => $payment_method,
					'confirmation'   => wp_create_nonce( $this->order_pay_confirmation_action( $order ) ),
				)
			);
		}

		/**
		 * Gateways_fees.
		 *
		 * @version 8.1.0
		 * @param WC_Cart|null $cart Cart passed by WooCommerce.
		 */
		public function gateways_fees( $cart = null ) {
			++$this->fee_calculation_count;
			$this->set_fee_decision( '', 'evaluation_incomplete' );
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
			$current_gateway = $this->resolve_fee_gateway( $current_gateway );

			$evaluation = $this->evaluate_gateway_fee( $current_gateway, $cart );
			if ( isset( $evaluation['fee'] ) ) {
				$result               = $cart->fees_api()->add_fee( $evaluation['fee'] );
				$evaluation['reason'] = is_wp_error( $result ) ? 'duplicate_suppressed' : 'applied';
			}
			unset( $evaluation['fee'] );
			$this->set_fee_decision( $current_gateway, $evaluation['reason'], $evaluation );
		}

		/**
		 * Resolve the existing Klarna fee configuration alias for both callers.
		 *
		 * @param string $gateway Explicit or already selected gateway ID.
		 * @return string Fee configuration gateway ID.
		 */
		public function resolve_fee_gateway( $gateway ) {
			if ( false !== strpos( $gateway, 'klarna' ) && 'yes' === wcj_get_option( 'wcj_enable_payment_gateway_charge_discount', 'no' ) ) {
				return 'klarna_payments';
			}
			return $gateway;
		}

		/**
		 * Single fee evaluator used by the live callback and bounded merchant scenarios.
		 * Does not select a gateway, apply a fee or write a session. Live callers retain
		 * their existing shortcode and currency behavior. Scenario callers must reject
		 * those dynamic contexts before invoking this method.
		 *
		 * @param string      $current_gateway Already selected gateway.
		 * @param object      $cart Cart getter context, never mutated here.
		 * @param string|null $user_role Explicit scenario role, or null for the live user.
		 * @param bool        $scenario Enforce the read-only scenario boundary after dynamic reads.
		 * @return array Internal evaluation, including an application payload when eligible.
		 */
		public function evaluate_gateway_fee( $current_gateway, $cart, $user_role = null, $scenario = false ) {
			$conditions   = array_fill_keys( array( 'configuration', 'cart_amount', 'products', 'user_role', 'fee_amount' ), 'not_evaluated' );
			$raw_fee_text = $this->wcj_get_option( 'text', $current_gateway );
			if ( $scenario && ( ! is_string( $raw_fee_text ) || false !== strpos( $raw_fee_text, '[' ) ) ) {
				return $this->fee_evaluation( $current_gateway, 'dynamic_title', $conditions );
			}
			$fee_text        = do_shortcode( $raw_fee_text );
			$min_cart_amount = (float) $this->wcj_get_option( 'min_cart_amount', $current_gateway );
			$max_cart_amount = (float) $this->wcj_get_option( 'max_cart_amount', $current_gateway );
			if ( '' === $fee_text ) {
				$conditions['configuration'] = 'unmatched';
				return $this->fee_evaluation( $current_gateway, 'not_configured', $conditions );
			}

			$conditions['configuration'] = 'matched';

			// Multicurrency (Currency Switcher) module.
			$multicurrency_enabled = isset( w_c_j()->all_modules['multicurrency'] ) && w_c_j()->all_modules['multicurrency']->is_enabled();
			if ( $scenario && ( 'yes' === wcj_get_option( 'wcj_multicurrency_enabled', 'no' ) || $multicurrency_enabled ) ) {
				return $this->fee_evaluation( $current_gateway, 'multicurrency_context', $conditions );
			}
			if ( $multicurrency_enabled ) {
				$min_cart_amount = w_c_j()->all_modules['multicurrency']->change_price( $min_cart_amount, null );
				$max_cart_amount = w_c_j()->all_modules['multicurrency']->change_price( $max_cart_amount, null );
			}
			$total_in_cart  = ( 'no' === $this->wcj_get_option( 'exclude_shipping', $current_gateway ) ?
				$cart->get_cart_contents_total() + $cart->get_shipping_total() :
				$cart->get_cart_contents_total() );
			$total_in_cart += 'no' === $this->wcj_get_option( 'include_taxes', $current_gateway ) ? 0 : $cart->get_subtotal_tax() + $cart->get_shipping_tax();
			if ( $total_in_cart < $min_cart_amount || ( 0.0 !== $max_cart_amount && $total_in_cart > $max_cart_amount ) ) {
				$conditions['cart_amount'] = 'unmatched';
				return $this->fee_evaluation( $current_gateway, 'outside_cart_amount', $conditions );
			}
			$conditions['cart_amount'] = 'matched';
			if ( ! $this->check_cart_products( $current_gateway, $cart ) ) {
				$conditions['products'] = 'unmatched';
				return $this->fee_evaluation( $current_gateway, 'product_condition_not_met', $conditions );
			}
			$conditions['products'] = 'matched';
			if ( $total_in_cart >= $min_cart_amount ) {
				$userwise_options                 = (array) wcj_get_option( 'wcj_enable_payment_gateway_charge_discount_userwise', array() );
				$enable_user_wise_charge_discount = isset( $userwise_options[ $current_gateway ] ) ? $userwise_options[ $current_gateway ] : 'no';
				if ( 'yes' === $enable_user_wise_charge_discount ) {
					if ( $scenario && null === $user_role ) {
						return $this->fee_evaluation( $current_gateway, 'role_fact_required', $conditions );
					}
					if ( null === $user_role ) {
						if ( is_user_logged_in() ) {
							$user      = wp_get_current_user();
							$user_role = isset( $user->roles[0] ) ? $user->roles[0] : 'guest';
						} else {
							$user_role = 'guest';
						}
					}
					$role_values                = (array) wcj_get_option( 'wcj_gateways_fees_' . $user_role, array() );
					$additional_discountby_user = isset( $role_values[ $current_gateway ] ) ? $role_values[ $current_gateway ] : '';
					$conditions['user_role']    = $additional_discountby_user ? 'role_override' : 'base_value';
					$fee_value                  = $additional_discountby_user ? $additional_discountby_user : $this->wcj_get_option( 'value', $current_gateway );
				} else {
					$conditions['user_role'] = 'not_applicable';
					$fee_value               = $this->wcj_get_option( 'value', $current_gateway );
				}
				if ( $scenario && ( ! is_numeric( $fee_value ) || ! is_finite( (float) $fee_value ) || abs( (float) $fee_value ) > 1000000000 ) ) {
					return $this->fee_evaluation( $current_gateway, 'invalid_configuration', $conditions );
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
					$conditions['fee_amount'] = 'nonzero';
					$fee                      = array(
						'id'        => 'wcj_gateway_fee_' . sanitize_key( $current_gateway ),
						'name'      => $fee_text,
						'amount'    => $final_fee_to_add,
						'taxable'   => $taxable,
						'tax_class' => $tax_class_name,
					);
					return $this->fee_evaluation( $current_gateway, 'eligible', $conditions, $fee, $final_fee_to_add );
				} else {
					$conditions['fee_amount'] = 'zero_or_invalid';
					return $this->fee_evaluation( $current_gateway, 'zero_or_invalid_amount', $conditions, null, 0 );
				}
			}
			return $this->fee_evaluation( $current_gateway, 'evaluation_incomplete', $conditions );
		}
	}

endif;

return new WCJ_Payment_Gateways_Fees();
