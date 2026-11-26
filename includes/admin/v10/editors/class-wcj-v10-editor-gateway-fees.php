<?php
/**
 * Booster True V10 - Guided Editor: Payment Gateways Fees & Discounts.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Gateway_Fees' ) ) :

	/**
	 * Gateway Fees guided editor implementation.
	 */
	class WCJ_V10_Editor_Gateway_Fees {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'payment_gateways_fees';
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Payment Gateways Fees & Discounts', 'woocommerce-jetpack' ),
				'description' => __( 'Test exact checkout fee math with a live Before & After cart simulator.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Cart Simulator', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'payment_gateways_fees' => self::slug(),
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'          => __( 'Fees & Discounts Module', 'woocommerce-jetpack' ),
				'label'            => __( 'Checkout Line Label', 'woocommerce-jetpack' ),
				'fee_type'         => __( 'Fee Type', 'woocommerce-jetpack' ),
				'amount'           => __( 'Fee Amount', 'woocommerce-jetpack' ),
				'min_cart'         => __( 'Minimum Cart Subtotal', 'woocommerce-jetpack' ),
				'max_cart'         => __( 'Maximum Cart Subtotal', 'woocommerce-jetpack' ),
				'exclude_shipping' => __( 'Exclude Shipping from % & Thresholds', 'woocommerce-jetpack' ),
				'is_taxable'       => __( 'Make Fee Taxable', 'woocommerce-jetpack' ),
				'round'            => __( 'Round Fee to Nearest Whole Unit', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Registered WooCommerce gateways list with clean fallback.
		 *
		 * @return array<int, array{id: string, title: string, enabled: bool}>
		 */
		private static function get_gateways_list() {
			$list = array();
			if ( function_exists( 'WC' ) && WC()->payment_gateways ) {
				$gateways = WC()->payment_gateways->payment_gateways();
				if ( is_array( $gateways ) ) {
					foreach ( $gateways as $id => $gw ) {
						$title  = is_object( $gw ) && method_exists( $gw, 'get_title' ) ? wp_strip_all_tags( (string) $gw->get_title() ) : $id;
						$list[] = array(
							'id'      => (string) $id,
							'title'   => '' !== $title ? $title : (string) $id,
							'enabled' => is_object( $gw ) && isset( $gw->enabled ) && 'yes' === $gw->enabled,
						);
					}
				}
			}
			if ( empty( $list ) ) {
				$list[] = array(
					'id'      => 'bacs',
					'title'   => __( 'Direct bank transfer', 'woocommerce-jetpack' ),
					'enabled' => true,
				);
			}
			return $list;
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (gateway).
		 * @param array $store_ctx Store context from WCJ_V10_REST::get_store_sample_data().
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce    = wp_create_nonce( 'wcj-cat-nonce' );
			$gateways = self::get_gateways_list();
			$gw_ids   = wp_list_pluck( $gateways, 'id' );
			$gateway  = isset( $context['gateway'] ) ? sanitize_key( (string) $context['gateway'] ) : '';
			if ( '' === $gateway || ! in_array( $gateway, $gw_ids, true ) ) {
				$gateway = $gw_ids[0];
			}

			$texts     = (array) get_option( 'wcj_gateways_fees_text', array() );
			$types     = (array) get_option( 'wcj_gateways_fees_type', array() );
			$values    = (array) get_option( 'wcj_gateways_fees_value', array() );
			$mins      = (array) get_option( 'wcj_gateways_fees_min_cart_amount', array() );
			$maxs      = (array) get_option( 'wcj_gateways_fees_max_cart_amount', array() );
			$excl_ship = (array) get_option( 'wcj_gateways_fees_exclude_shipping', array() );
			$taxable   = (array) get_option( 'wcj_gateways_fees_is_taxable', array() );
			$rounding  = (array) get_option( 'wcj_gateways_fees_round', array() );

			$gw_enabled = true;
			$gw_title   = $gateway;
			foreach ( $gateways as $gw_item ) {
				if ( $gw_item['id'] === $gateway ) {
					$gw_enabled = ! empty( $gw_item['enabled'] );
					$gw_title   = $gw_item['title'];
					break;
				}
			}

			$diagnostics = array();
			if ( ! $gw_enabled ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %s: Payment gateway title */
						__( '%s is currently disabled in WooCommerce Payments, so shoppers will not see this fee at checkout yet.', 'woocommerce-jetpack' ),
						$gw_title
					),
					'action_label' => __( 'Manage Woo Payments', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
				);
			}
			if ( ! empty( $store_ctx['is_blocks_checkout'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'WooCommerce Blocks Checkout detected: gateway fees recalculate live when the shopper switches payment methods.', 'woocommerce-jetpack' ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=payment_gateways&section=payment_gateways_fees&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'          => 'yes' === get_option( 'wcj_payment_gateways_fees_enabled', 'no' ),
					'gateway'          => $gateway,
					'label'            => isset( $texts[ $gateway ] ) ? (string) $texts[ $gateway ] : '',
					'fee_type'         => isset( $types[ $gateway ] ) && 'percent' === $types[ $gateway ] ? 'percent' : 'fixed',
					'amount'           => isset( $values[ $gateway ] ) ? (string) $values[ $gateway ] : '0',
					'min_cart'         => isset( $mins[ $gateway ] ) ? (string) $mins[ $gateway ] : '0',
					'max_cart'         => isset( $maxs[ $gateway ] ) ? (string) $maxs[ $gateway ] : '0',
					'exclude_shipping' => isset( $excl_ship[ $gateway ] ) && 'yes' === $excl_ship[ $gateway ],
					'is_taxable'       => isset( $taxable[ $gateway ] ) && 'yes' === $taxable[ $gateway ],
					'round'            => isset( $rounding[ $gateway ] ) && 'yes' === $rounding[ $gateway ],
				),
				'meta'        => array(
					'gateways'        => $gateways,
					'currency_symbol' => $store_ctx['currency_symbol'],
					'decimals'        => $store_ctx['decimals'],
					'verify_url'      => $store_ctx['checkout_url'],
					'verify_label'    => __( 'Test on Live Checkout', 'woocommerce-jetpack' ),
					'store_defaults'  => $store_ctx['store_defaults'],
					'diagnostics'     => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$amount   = isset( $draft['amount'] ) && is_numeric( $draft['amount'] ) ? (string) round( (float) $draft['amount'], 4 ) : '0';
			$min_cart = isset( $draft['min_cart'] ) && is_numeric( $draft['min_cart'] ) ? (string) max( 0, round( (float) $draft['min_cart'], 2 ) ) : '0';
			$max_cart = isset( $draft['max_cart'] ) && is_numeric( $draft['max_cart'] ) ? (string) max( 0, round( (float) $draft['max_cart'], 2 ) ) : '0';

			return array(
				'enabled'          => ! empty( $draft['enabled'] ),
				'gateway'          => isset( $draft['gateway'] ) ? sanitize_key( (string) $draft['gateway'] ) : $live['gateway'],
				'label'            => isset( $draft['label'] ) ? sanitize_text_field( (string) $draft['label'] ) : '',
				'fee_type'         => isset( $draft['fee_type'] ) && 'percent' === $draft['fee_type'] ? 'percent' : 'fixed',
				'amount'           => $amount,
				'min_cart'         => $min_cart,
				'max_cart'         => $max_cart,
				'exclude_shipping' => ! empty( $draft['exclude_shipping'] ),
				'is_taxable'       => ! empty( $draft['is_taxable'] ),
				'round'            => ! empty( $draft['round'] ),
			);
		}

		/**
		 * Prevent publishing an active non-zero fee with an empty Checkout Line Label.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			if ( ! empty( $draft['enabled'] ) && 0.0 !== (float) $draft['amount'] && '' === trim( (string) $draft['label'] ) ) {
				return new WP_Error(
					'wcj_v10_missing_fee_label',
					__( 'Enter a Checkout Line Label (for example "COD Handling Fee") before publishing — WooCommerce requires a label to apply a fee.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}
			return true;
		}

		/**
		 * Project Before & After checkout totals for a sample cart.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Cart subtotal/shipping context.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$subtotal = isset( $context['subtotal'] ) && is_numeric( $context['subtotal'] ) ? max( 0.0, (float) $context['subtotal'] ) : 25.00;
			$shipping = isset( $context['shipping'] ) && is_numeric( $context['shipping'] ) ? max( 0.0, (float) $context['shipping'] ) : 10.00;
			$decimals = isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 2;

			$calc = static function ( $cfg ) use ( $subtotal, $shipping, $decimals ) {
				$calc_base   = ! empty( $cfg['exclude_shipping'] ) ? $subtotal : ( $subtotal + $shipping );
				$order_base  = $subtotal + $shipping;
				$fee_amount  = 0.0;
				$reason      = 'applied';
				$needs_label = '' === trim( (string) $cfg['label'] );

				if ( empty( $cfg['enabled'] ) ) {
					$reason = 'module_disabled';
				} else {
					$min = (float) $cfg['min_cart'];
					$max = (float) $cfg['max_cart'];
					if ( $calc_base < $min || ( $max > 0 && $calc_base > $max ) ) {
						$reason = 'outside_cart_range';
					} else {
						$val = (float) $cfg['amount'];
						if ( 'percent' === $cfg['fee_type'] ) {
							$fee_amount = round( $calc_base * ( $val / 100.0 ), $decimals );
						} else {
							$fee_amount = round( $val, $decimals );
						}
						if ( ! empty( $cfg['round'] ) ) {
							$fee_amount = round( $fee_amount, 0 );
						}
						if ( $needs_label && 0.0 !== (float) $val ) {
							$reason = 'needs_label';
						} elseif ( $needs_label ) {
							$reason = 'no_label';
						}
					}
				}

				$default_label = ( 'needs_label' === $reason )
					? __( 'Gateway Fee (needs label)', 'woocommerce-jetpack' )
					: __( 'Gateway Fee', 'woocommerce-jetpack' );

				return array(
					'module_enabled'   => ! empty( $cfg['enabled'] ),
					'gateway'          => $cfg['gateway'],
					'subtotal'         => round( $subtotal, $decimals ),
					'shipping'         => round( $shipping, $decimals ),
					'fee_label'        => ! $needs_label ? $cfg['label'] : $default_label,
					'fee_amount'       => $fee_amount,
					'total'            => round( $order_base + $fee_amount, $decimals ),
					'exclude_shipping' => ! empty( $cfg['exclude_shipping'] ),
					'is_taxable'       => ! empty( $cfg['is_taxable'] ),
					'round'            => ! empty( $cfg['round'] ),
					'reason'           => $reason,
				);
			};

			return array(
				'before' => $calc( $live ),
				'after'  => $calc( $draft ),
			);
		}

		/**
		 * Map sanitized draft values back onto native wp_options keys.
		 *
		 * @param array $draft Sanitized draft.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft ) {
			$gw        = $draft['gateway'];
			$texts     = (array) get_option( 'wcj_gateways_fees_text', array() );
			$types     = (array) get_option( 'wcj_gateways_fees_type', array() );
			$values    = (array) get_option( 'wcj_gateways_fees_value', array() );
			$mins      = (array) get_option( 'wcj_gateways_fees_min_cart_amount', array() );
			$maxs      = (array) get_option( 'wcj_gateways_fees_max_cart_amount', array() );
			$excl_ship = (array) get_option( 'wcj_gateways_fees_exclude_shipping', array() );
			$taxable   = (array) get_option( 'wcj_gateways_fees_is_taxable', array() );
			$rounding  = (array) get_option( 'wcj_gateways_fees_round', array() );

			$texts[ $gw ]     = $draft['label'];
			$types[ $gw ]     = $draft['fee_type'];
			$values[ $gw ]    = $draft['amount'];
			$mins[ $gw ]      = $draft['min_cart'];
			$maxs[ $gw ]      = $draft['max_cart'];
			$excl_ship[ $gw ] = ! empty( $draft['exclude_shipping'] ) ? 'yes' : 'no';
			$taxable[ $gw ]   = ! empty( $draft['is_taxable'] ) ? 'yes' : 'no';
			$rounding[ $gw ]  = ! empty( $draft['round'] ) ? 'yes' : 'no';

			return array(
				'wcj_payment_gateways_fees_enabled'  => $draft['enabled'] ? 'yes' : 'no',
				'wcj_gateways_fees_text'             => $texts,
				'wcj_gateways_fees_type'             => $types,
				'wcj_gateways_fees_value'            => $values,
				'wcj_gateways_fees_min_cart_amount'  => $mins,
				'wcj_gateways_fees_max_cart_amount'  => $maxs,
				'wcj_gateways_fees_exclude_shipping' => $excl_ship,
				'wcj_gateways_fees_is_taxable'       => $taxable,
				'wcj_gateways_fees_round'            => $rounding,
			);
		}
	}

endif;
