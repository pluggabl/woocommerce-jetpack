<?php
/**
 * Booster for WooCommerce - Module - Gateways per Product or Category
 *
 * @version 7.1.6
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Per_Category' ) ) :
	/**
	 * WCJ_Payment_Gateways_Per_Category.
	 */
	class WCJ_Payment_Gateways_Per_Category extends WCJ_Module {
		/**
		 * Payment receipt.
		 *
		 * @var mixed
		 */
		private $payment_receipt = null;
		/**
		 * Payment collect.
		 *
		 * @var mixed
		 */
		private $payment_collect = false;

		/**
		 * The module do_use_variations
		 *
		 * @var varchar $do_use_variations Module do_use_variations.
		 */
		public $do_use_variations;

		/**
		 * Request-scoped normalized gateway rules.
		 *
		 * @var array
		 */
		private $gateway_rules_cache = array();

		/**
		 * Normalizes legacy scalar and array rule values.
		 *
		 * @param mixed $values Saved rule values.
		 * @return array
		 */
		private function normalize_rule_values( $values ) {
			return array_values( array_filter( array_map( 'strval', (array) $values ) ) );
		}

		/**
		 * Gets normalized gateway rules once per request.
		 *
		 * @param string $gateway_id Gateway ID.
		 * @return array
		 */
		private function get_gateway_rules( $gateway_id ) {
			if ( ! isset( $this->gateway_rules_cache[ $gateway_id ] ) ) {
				$this->gateway_rules_cache[ $gateway_id ] = array(
					'categories_in'   => $this->normalize_rule_values( wcj_get_option( 'wcj_gateways_per_category_' . $gateway_id, array() ) ),
					'categories_excl' => $this->normalize_rule_values( wcj_get_option( 'wcj_gateways_per_category_excl_' . $gateway_id, array() ) ),
					'products_in'     => $this->normalize_rule_values( wcj_maybe_convert_string_to_array( apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_per_products_' . $gateway_id, array() ) ) ) ),
					'products_excl'   => $this->normalize_rule_values( wcj_maybe_convert_string_to_array( apply_filters( 'booster_option', array(), wcj_get_option( 'wcj_gateways_per_products_excl_' . $gateway_id, array() ) ) ) ),
				);
			}
			return $this->gateway_rules_cache[ $gateway_id ];
		}

		/**
		 * Constructor.
		 *
		 * @version 4.1.0
		 * @todo    [dev] (maybe) add `$this->do_use_variations_and_variable`
		 * @todo    [dev] (maybe) `add_filter( 'woocommerce_payment_gateways_settings',  array( $this, 'add_per_category_settings' ), 100 );`
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_per_category';
			$this->short_desc = 'Gateways per Product or Category';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways per Product or Category', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Show payment gateway only if there is selected product or product category in cart.';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Show payment gateway only if there is selected product or product category in cart.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-per-product-or-category';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_payment_gateways_per_category' ), 100 );
				$this->do_use_variations = ( 'yes' === wcj_get_option( 'wcj_gateways_per_category_use_variations', 'no' ) );
			}
		}

		/**
		 * Is_gateway_allowed.
		 *
		 * @version 8.3.0
		 * @since   4.6.0
		 *
		 * @param string | int $gateway_id defines the gateway_id.
		 * @param array        $products defines the products.
		 *
		 * @return bool
		 */
		public function is_gateway_allowed( $gateway_id, $products = array() ) {
			$rules = $this->get_gateway_rules( $gateway_id );
			if ( ! array_filter( $rules ) ) {
				$decision = array( 'not_configured', 'no_rules', null );
				if ( $this->payment_collect ) {
					$this->payment_receipt = $decision; }
				return true;
			}
			$products = array_values( array_unique( array_map( 'strval', $products ) ) );

			$uncertain            = false;
			$product_category_ids = array();
			if ( ! empty( $rules['categories_in'] ) || ! empty( $rules['categories_excl'] ) ) {
				foreach ( $products as $product_id ) {
					$product_categories = wcj_get_product_terms( $product_id, 'product_cat' );
					if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
						$uncertain = true;
						continue;
					}
					foreach ( $product_categories as $product_category ) {
						$product_category_ids[] = (string) $product_category->term_id;
					}
				}
				$product_category_ids = array_values( array_unique( $product_category_ids ) );
			}

			// Including by categories.
			$categories_in = $rules['categories_in'];
			if ( ! empty( $categories_in ) ) {
				if ( empty( array_intersect( $product_category_ids, $categories_in ) ) ) {
					$decision = $uncertain ? array( 'not_checked', 'category_terms_uncertain', 'category' ) : array( 'removed', 'include_miss', 'category' );
					if ( $this->payment_collect ) {
						$this->payment_receipt = $decision; }
					return false;
				}
			}

			// Excluding by categories.
			$categories_excl = $rules['categories_excl'];
			if ( ! empty( $categories_excl ) ) {
				if ( ! empty( array_intersect( $product_category_ids, $categories_excl ) ) ) {
					$decision = $uncertain ? array( 'not_checked', 'category_terms_uncertain', 'category' ) : array( 'removed', 'exclude_match', 'category' );
					if ( $this->payment_collect ) {
						$this->payment_receipt = $decision; }
					return false;
				}
			}

			// Including by products.
			$products_in = $rules['products_in'];
			if ( ! empty( $products_in ) ) {
				if ( empty( array_intersect( $products, $products_in ) ) ) {
					$decision = $uncertain ? array( 'not_checked', 'category_terms_uncertain', 'category' ) : array( 'removed', 'include_miss', 'product' );
					if ( $this->payment_collect ) {
						$this->payment_receipt = $decision; }
					return false;
				}
			}

			// Excluding by products.
			$products_excl = $rules['products_excl'];
			if ( ! empty( $products_excl ) ) {
				if ( ! empty( array_intersect( $products, $products_excl ) ) ) {
					$decision = $uncertain ? array( 'not_checked', 'category_terms_uncertain', 'category' ) : array( 'removed', 'exclude_match', 'product' );
					if ( $this->payment_collect ) {
						$this->payment_receipt = $decision; }
					return false;
				}
			}

			$decision = $uncertain ? array( 'not_checked', 'category_terms_uncertain', 'category' ) : array( 'retained', 'rules_pass', null );
			if ( $this->payment_collect ) {
				$this->payment_receipt = $decision; }
			return true;
		}

		/**
		 * Filter_available_payment_gateways_per_category.
		 *
		 * @version 8.3.0
		 * @todo    [dev] (maybe) `if ( ! is_checkout() ) { return $available_gateways; }`
		 * @param array $available_gateways defines the available_gateways.
		 */
		public function filter_available_payment_gateways_per_category( $available_gateways ) {
			$ticket                = WCJ_Rule_Explanations::payment_start( $this, $available_gateways );
			$this->payment_collect = null !== $ticket;
			if ( $this->payment_collect ) {
				$this->payment_receipt = null; }
			$row                   = null;
			$whole                 = false;
			$result                = $this->payment_filter_body( $available_gateways, $ticket, $row, $whole );
			$this->payment_collect = false;
			if ( null !== $ticket ) {
				WCJ_Rule_Explanations::payment_finish( $this, $ticket, $row, $whole ); }
			return $result;
		}

		/**
		 * Original lazy collector, shared with bounded per-gateway decisions.
		 *
		 * @param mixed $available_gateways Available gateway map.
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $row Captured decision row.
		 * @param mixed $whole Whether the complete decision is requested.
		 */
		private function payment_filter_body( $available_gateways, $ticket, &$row, &$whole ) {
			if ( empty( $available_gateways ) ) {
				$whole = true;
				$row   = array( 'not_checked', 'no_gateways', null );
				return $available_gateways;
			}

			$cart_products  = array();
			$order_products = array();
			$is_order_pay   = is_wc_endpoint_url( 'order-pay' );
			$is_store_api   = function_exists( 'wcj_is_store_api_request' ) && wcj_is_store_api_request();
			if ( ! is_checkout() && ! $is_store_api && ! $is_order_pay ) {
				return $available_gateways;
			}

			// Check if it is on Checkout Page.
			if ( ( is_checkout() || $is_store_api ) && ! $is_order_pay ) {
				if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
					$whole = true;
					$row   = array( 'not_checked', 'missing_cart', null );
					return $available_gateways;
				}
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_id      = ( $this->do_use_variations ? ( ! empty( $values['variation_id'] ) ? $values['variation_id'] : $values['product_id'] ) : $values['product_id'] );
					$cart_products[] = $product_id;
				}

				// Check if it is on checkout/order-pay/xxx page.
			} elseif ( $is_order_pay ) {
				$url_arr = preg_split( '/[\/\?]/', ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) );

				if ( in_array( 'order-pay', $url_arr, true ) ) {
					$order_pay_index = array_search( 'order-pay', $url_arr, true );
					$order_id        = intval( $url_arr[ $order_pay_index + 1 ] );
					$order           = wc_get_order( $order_id );
					if ( ! $order ) {
						return $available_gateways;
					}
					foreach ( $order->get_items() as $item_id => $values ) {
						$product_id       = ( $this->do_use_variations ? ( ! empty( $values['variation_id'] ) ? $values['variation_id'] : $values['product_id'] ) : $values['product_id'] );
						$order_products[] = $product_id;
					}
				}
			}

			// Check gateways.
			$unavailable_gateways = array();
			foreach ( $available_gateways as $gateway_id => $gateway ) {
				if ( $this->payment_collect ) {
					$this->payment_receipt = null; }
				if ( ! empty( $cart_products ) ) {
					if ( ! $this->is_gateway_allowed( $gateway_id, $cart_products ) ) {
						$unavailable_gateways[] = $gateway_id;
					}
				} elseif ( ! empty( $order_products ) ) {
					if ( ! $this->is_gateway_allowed( $gateway_id, $order_products ) ) {
						$unavailable_gateways[] = $gateway_id;
					}
				} elseif ( $this->payment_collect ) {
					$this->payment_receipt = array( 'not_checked', 'no_products', null );
				}
				if ( $ticket === (string) $gateway_id ) {
					$row = $this->payment_receipt; }
			}

			// Remove invalid gateways.
			if ( count( $unavailable_gateways ) > 0 ) {
				$available_gateways = array_diff_key( $available_gateways, array_flip( $unavailable_gateways ) );
			}

			return $available_gateways;
		}
	}

endif;

return new WCJ_Payment_Gateways_Per_Category();
