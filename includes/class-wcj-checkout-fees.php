<?php
/**
 * Booster for WooCommerce - Module - Checkout Fees
 *
 * @version 8.0.0
 * @since   3.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Checkout_Fees' ) ) :
	/**
	 * WCJ_Checkout_Fees.
	 *
	 * @version 8.0.0
	 */
	class WCJ_Checkout_Fees extends WCJ_Module {

		/**
		 * The module checkout_fields
		 *
		 * @var varchar $checkout_fields Module checkout_fields.
		 */
		public $checkout_fields;

		/**
		 * Cached fee configuration to avoid repeated option lookups.
		 *
		 * @var array|null
		 */
		private $cached_fees_config = null;

		/**
		 * Constructor.
		 *
		 * @version 8.0.0
		 * @since   3.7.0
		 */
		public function __construct() {

			$this->id         = 'checkout_fees';
			$this->short_desc = __( 'Checkout Fees', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add fees to WooCommerce cart & checkout (1 fee allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add fees to WooCommerce cart & checkout.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-checkout-fees';
			parent::__construct();

			if ( $this->is_enabled() ) {
				// Core function — fires on both classic and Blocks checkout.
				add_action( 'woocommerce_review_order_after_submit', array( $this, 'wcj_add_nonce_checkout_fees' ), PHP_INT_MAX );
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fees' ), PHP_INT_MAX );
				// Checkout fields.
				$this->checkout_fields = wcj_get_option( 'wcj_checkout_fees_data_checkout_fields', array() );
				$this->checkout_fields = array_filter( $this->checkout_fields );
				if ( ! empty( $this->checkout_fields ) ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				}
			}
		}

		/**
		 * Check if the current request is a WC Store API request (Blocks checkout).
		 *
		 * @version 8.0.0
		 * @since   8.0.0
		 * @return bool
		 */
		private function is_store_api_request() {
			if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
				return false;
			}
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			return false !== strpos( $uri, '/wc/store/' );
		}

		/**
		 * Enqueue_scripts.
		 *
		 * @version 3.8.0
		 * @since   3.8.0
		 */
		public function enqueue_scripts() {
			if ( is_checkout() ) {
				wp_enqueue_script( 'wcj-checkout-fees', wcj_plugin_url() . '/includes/js/wcj-checkout-fees.js', array( 'jquery' ), w_c_j()->version, true );
				wp_localize_script(
					'wcj-checkout-fees',
					'wcj_checkout_fees',
					array(
						'checkout_fields' => 'input[name="' . implode( '"], input[name="', $this->checkout_fields ) . '"]',
					)
				);
			}
		}

		/**
		 * Validate fee without considering overlapping.
		 *
		 * @version 8.0.0
		 * @since   4.5.0
		 *
		 * @param int                    $fee_id  defines the fee_id.
		 * @param array | string WC_Cart $cart defines the cart.
		 *
		 * @return bool
		 */
		public function is_fee_valid( $fee_id, \WC_Cart $cart ) {
			$fees    = $this->get_fees();
			$enabled = $this->get_fee_option( 'wcj_checkout_fees_data_enabled' );
			$values  = $this->get_fee_option( 'wcj_checkout_fees_data_values' );

			// Check if is active and empty value.
			$value = ( isset( $values[ $fee_id ] ) ? $values[ $fee_id ] : 0 );
			if (
			( isset( $enabled[ $fee_id ] ) && 'no' === $enabled[ $fee_id ] ) ||
			( 0 === ( $value ) )
			) {
				return false;
			}

			// Check cart quantity.
			if (
			$cart->get_cart_contents_count() < $fees[ $fee_id ]['cart_min'] ||
			( $fees[ $fee_id ]['cart_max'] > 0 && $cart->get_cart_contents_count() > $fees[ $fee_id ]['cart_max'] )
			) {
				return false;
			}

			// Check cart total.
			if (
			$cart->get_cart_contents_total() < $fees[ $fee_id ]['cart_min_total'] ||
			( ! empty( $fees[ $fee_id ]['cart_max_total'] ) && $fees[ $fee_id ]['cart_max_total'] > 0 && $cart->get_cart_contents_total() > $fees[ $fee_id ]['cart_max_total'] )
			) {
				return false;
			}

			// Check checkout fields — only works on classic checkout (POST data).
			// On Blocks checkout (Store API), checkout-field-conditional fees are skipped.
			if ( ! empty( $this->checkout_fields[ $fee_id ] ) ) {
				if ( $this->is_store_api_request() ) {
					// Checkout-field-conditional fees are not supported on Blocks checkout.
					return false;
				}
				if ( isset( $post_data ) || isset( $_REQUEST['post_data'] ) ) {
					if ( ! isset( $post_data ) ) {
						$post_data = array();
						parse_str( sanitize_text_field( wp_unslash( $_REQUEST['post_data'] ) ), $post_data );
						$wpnonce = isset( $post_data['wcj-process-checkout-nonce'] ) ? wp_verify_nonce( sanitize_key( $post_data['wcj-process-checkout-nonce'] ), 'wcj-process_checkout' ) : false;
					}
					if ( ! $wpnonce || empty( $post_data[ $this->checkout_fields[ $fee_id ] ] ) ) {
						return false;
					}
				} elseif ( empty( $_REQUEST[ $this->checkout_fields[ $fee_id ] ] ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Get_overlapped_fees.
		 *
		 * @version 4.5.0
		 * @since   4.5.0
		 *
		 * @param int | string $valid_fees defines the valid_fees.
		 *
		 * @return array
		 */
		public function get_overlapped_fees( $valid_fees ) {
			$fees       = $this->get_fees();
			$overlapped = array();
			foreach ( $valid_fees as $fee_id ) {
				if ( ! in_array( $fee_id, $overlapped, true ) ) {
					$overlapped = array_unique( array_merge( $overlapped, $fees[ $fee_id ]['overlap'] ) );
				}
			}
			return $overlapped;
		}

		/**
		 * Get a fee-related option with request-scope caching.
		 *
		 * Avoids repeated identical wcj_get_option calls within the same request
		 * for fee configuration arrays.
		 *
		 * @version 8.0.0
		 * @since   8.0.0
		 * @param string $option_name The option key.
		 * @return mixed
		 */
		private function get_fee_option( $option_name ) {
			if ( null === $this->cached_fees_config ) {
				$this->cached_fees_config = array();
			}
			if ( ! isset( $this->cached_fees_config[ $option_name ] ) ) {
				$this->cached_fees_config[ $option_name ] = wcj_get_option( $option_name, array() );
			}
			return $this->cached_fees_config[ $option_name ];
		}

		/**
		 * Get Fees.
		 *
		 * @version 8.0.0
		 * @since   4.5.0
		 *
		 * @param bool $only_enabled check enabled.
		 * @param bool $adjust_priority check adjust_priority.
		 *
		 * @return array
		 */
		public function get_fees( $only_enabled = true, $adjust_priority = true ) {
			$total_number    = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_fees_total_number', 1 ) );
			$titles          = $this->get_fee_option( 'wcj_checkout_fees_data_titles' );
			$types           = $this->get_fee_option( 'wcj_checkout_fees_data_types' );
			$values          = $this->get_fee_option( 'wcj_checkout_fees_data_values' );
			$cart_min        = $this->get_fee_option( 'wcj_checkout_fees_cart_min_amount' );
			$cart_min_total  = $this->get_fee_option( 'wcj_checkout_fees_cart_min_total_amount' );
			$cart_max        = $this->get_fee_option( 'wcj_checkout_fees_cart_max_amount' );
			$cart_max_total  = $this->get_fee_option( 'wcj_checkout_fees_cart_max_total_amount' );
			$taxable         = $this->get_fee_option( 'wcj_checkout_fees_data_taxable' );
			$checkout_fields = $this->get_fee_option( 'wcj_checkout_fees_data_values' );
			$enabled         = $this->get_fee_option( 'wcj_checkout_fees_data_enabled' );
			$overlap_opt     = $this->get_fee_option( 'wcj_checkout_fees_overlap' );
			$priorities      = $this->get_fee_option( 'wcj_checkout_fees_priority' );

			$fees = array();
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( ! isset( $priorities[ $i ] ) || empty( $priorities[ $i ] ) ) {
					$priorities[ $i ] = 0;
				}
				$enabled = isset( $enabled[ $i ] ) ? $enabled[ $i ] : 'yes';
				if ( $only_enabled && 'no' === $enabled ) {
					continue;
				}
				$fees[ $i ] = array(
					'enabled'        => $enabled,
					'cart_min'       => isset( $cart_min[ $i ] ) ? $cart_min[ $i ] : 1,
					'cart_min_total' => isset( $cart_min_total[ $i ] ) ? $cart_min_total[ $i ] : 0,
					'cart_max'       => isset( $cart_max[ $i ] ) ? $cart_max[ $i ] : 0,
					'cart_max_total' => isset( $cart_max_total[ $i ] ) ? $cart_max_total[ $i ] : '',
					'title'          => isset( $titles[ $i ] ) ? $titles[ $i ] : '',
					'type'           => isset( $types[ $i ] ) ? $types[ $i ] : 'fixed',
					'value'          => isset( $values[ $i ] ) ? $values[ $i ] : 0,
					'priority'       => isset( $priorities[ $i ] ) ? ( $priorities[ $i ] ) : 0,
					'taxable'        => isset( $taxable[ $i ] ) ? $taxable[ $i ] : 'yes',
					'checkout_field' => isset( $checkout_fields[ $i ] ) ? $checkout_fields[ $i ] : '',
					'overlap'        => isset( $overlap_opt[ $i ] ) ? $overlap_opt[ $i ] : array(),
				);
			}
			if ( $adjust_priority ) {
				uksort(
					$fees,
					function ( $a, $b ) use ( $fees, $priorities ) {
						return $priorities[ $a ] < $priorities[ $b ];
					}
				);
			}
			return $fees;
		}

		/**
		 * Get valid fees.
		 *
		 * @version 8.0.0
		 * @since   4.5.0
		 *
		 * @param string | array $cart define cart details.
		 * @param bool           $ignore_overlapped define ignore_overlapped.
		 *
		 * @return array
		 */
		public function get_valid_fees( $cart, $ignore_overlapped = true ) {
			$titles  = $this->get_fee_option( 'wcj_checkout_fees_data_titles' );
			$types   = $this->get_fee_option( 'wcj_checkout_fees_data_types' );
			$values  = $this->get_fee_option( 'wcj_checkout_fees_data_values' );
			$taxable = $this->get_fee_option( 'wcj_checkout_fees_data_taxable' );

			$fees = $this->get_fees();

			$fees_to_add = array();
			$valid_fees  = array();

			// Get Valid fees.
			foreach ( $fees as $fee_id => $fee_title ) {
				if ( ! $this->is_fee_valid( $fee_id, $cart ) ) {
					continue;
				}
				$valid_fees[] = $fee_id;
			}

			// Ignore overlapped.
			if ( $ignore_overlapped ) {
				$overlapped_fees = $this->get_overlapped_fees( $valid_fees );
				$valid_fees      = array_diff( $valid_fees, $overlapped_fees );
			}

			foreach ( $valid_fees as $fee_id ) {
				// Adding the fee.
				$title = ( isset( $titles[ $fee_id ] ) ? $titles[ $fee_id ] : __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $fee_id );
				$value = isset( $values[ $fee_id ] ) ? $values[ $fee_id ] : 0;
				if ( isset( $types[ $fee_id ] ) && 'percent' === $types[ $fee_id ] ) {
					$value = $cart->get_cart_contents_total() * $value / 100;
				}
				$fees_to_add[ $fee_id ] = array(
					'name'      => $title,
					'amount'    => $value,
					'taxable'   => ( isset( $taxable[ $fee_id ] ) ? ( 'yes' === $taxable[ $fee_id ] ) : true ),
					'tax_class' => 'standard',
				);
			}

			return $fees_to_add;
		}

		/**
		 * Add_fees.
		 *
		 * Fires on both classic checkout and WooCommerce Blocks checkout (via Store API).
		 * Simple fees (without checkout field conditions) work on both.
		 * Checkout-field-conditional fees only work on classic checkout.
		 *
		 * @version 8.0.0
		 * @since   3.7.0
		 * @param string | array $cart defines the cart.
		 */
		public function add_fees( $cart ) {
			if ( ! wcj_is_frontend() && ! $this->is_store_api_request() ) {
				return;
			}

			$fees_to_add = $this->get_valid_fees( $cart );

			if ( ! empty( $fees_to_add ) ) {
				foreach ( $fees_to_add as $fee_to_add ) {
					$cart->add_fee( $fee_to_add['name'], $fee_to_add['amount'], $fee_to_add['taxable'], $fee_to_add['tax_class'] );
				}
			}
		}

		/**
		 * Wcj_add_nonce_checkout_fees.
		 *
		 * @version 5.6.7
		 * @since   5.6.7
		 */
		public function wcj_add_nonce_checkout_fees() {
			return wp_nonce_field( 'wcj-process_checkout', 'wcj-process-checkout-nonce' );
		}
	}

endif;

return new WCJ_Checkout_Fees();
