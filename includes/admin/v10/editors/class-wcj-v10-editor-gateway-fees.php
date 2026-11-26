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
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/gateway-fees.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'gateway' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'payment_gateways_fees';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'payment_gateways_fees' );
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
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			unset( $context );
			return array(
				'wcj_payment_gateways_fees_enabled',
				'wcj_gateways_fees_text',
				'wcj_gateways_fees_type',
				'wcj_gateways_fees_value',
				'wcj_gateways_fees_min_cart_amount',
				'wcj_gateways_fees_max_cart_amount',
				'wcj_gateways_fees_exclude_shipping',
				'wcj_gateways_fees_is_taxable',
				'wcj_gateways_fees_round',
				'wcj_gateways_fees_round_precision',
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'          => __( 'Fees & Discounts Module (All Gateways)', 'woocommerce-jetpack' ),
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
		 * Customer-facing impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'          => array(
					'goes_live' => true,
					'impact'    => __( 'Turns gateway fees/discounts on or off across WooCommerce checkout.', 'woocommerce-jetpack' ),
				),
				'label'            => array(
					'goes_live' => true,
					'impact'    => __( 'Shown as the fee/discount line item title at checkout and on order totals.', 'woocommerce-jetpack' ),
				),
				'fee_type'         => array(
					'goes_live' => true,
					'impact'    => __( 'Changes how the fee is calculated (Fixed vs Percentage) at checkout.', 'woocommerce-jetpack' ),
				),
				'amount'           => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the exact amount charged or discounted at checkout for shoppers using this payment method.', 'woocommerce-jetpack' ),
				),
				'exclude_shipping' => array(
					'goes_live' => true,
					'impact'    => __( 'Changes checkout fee math so shipping is excluded from % calculations and thresholds.', 'woocommerce-jetpack' ),
				),
				'is_taxable'       => array(
					'goes_live' => true,
					'impact'    => __( 'Applies store tax rules to this checkout fee.', 'woocommerce-jetpack' ),
				),
				'round'            => array(
					'goes_live' => true,
					'impact'    => __( 'Rounds the checkout fee to whole currency units (precision = 0).', 'woocommerce-jetpack' ),
				),
				'min_cart'         => array(
					'goes_live' => false,
					'impact'    => __( 'Minimum cart threshold required before this fee applies.', 'woocommerce-jetpack' ),
				),
				'max_cart'         => array(
					'goes_live' => false,
					'impact'    => __( 'Maximum cart threshold where this fee still applies.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Registered WooCommerce gateways list with active-rule annotation.
		 *
		 * @param array $texts  Stored fee labels by gateway.
		 * @param array $values Stored fee amounts by gateway.
		 * @return array<int, array{id: string, title: string, enabled: bool, has_rule: bool}>
		 */
		private static function get_gateways_list( $texts = array(), $values = array() ) {
			$list = array();
			if ( function_exists( 'WC' ) && WC()->payment_gateways ) {
				$gateways = WC()->payment_gateways->payment_gateways();
				if ( is_array( $gateways ) ) {
					foreach ( $gateways as $id => $gw ) {
						$id_str   = (string) $id;
						$title    = is_object( $gw ) && method_exists( $gw, 'get_title' ) ? wp_strip_all_tags( (string) $gw->get_title() ) : $id_str;
						$has_rule = '' !== trim( (string) ( $texts[ $id_str ] ?? '' ) ) && 0.0 !== (float) ( $values[ $id_str ] ?? 0 );
						$list[]   = array(
							'id'       => $id_str,
							'title'    => '' !== $title ? $title : $id_str,
							'enabled'  => is_object( $gw ) && isset( $gw->enabled ) && 'yes' === $gw->enabled,
							'has_rule' => $has_rule,
						);
					}
				}
			}
			if ( empty( $list ) ) {
				$list[] = array(
					'id'       => 'bacs',
					'title'    => __( 'Direct bank transfer', 'woocommerce-jetpack' ),
					'enabled'  => true,
					'has_rule' => '' !== trim( (string) ( $texts['bacs'] ?? '' ) ) && 0.0 !== (float) ( $values['bacs'] ?? 0 ),
				);
			}
			return $list;
		}

		/**
		 * Count advanced Classic-only rules present on this gateway that aren't modeled in Simple Setup.
		 *
		 * @param string $gateway Gateway ID.
		 * @return int
		 */
		private static function count_classic_only_rules( $gateway ) {
			$count    = 0;
			$inc_tax  = (array) get_option( 'wcj_gateways_fees_include_taxes', array() );
			$inc_prod = (array) get_option( 'wcj_gateways_fees_include_products', array() );
			$exc_prod = (array) get_option( 'wcj_gateways_fees_exclude_products', array() );
			$prec     = (array) get_option( 'wcj_gateways_fees_round_precision', array() );

			if ( ! empty( $inc_tax[ $gateway ] ) && 'yes' === $inc_tax[ $gateway ] ) {
				++$count;
			}
			if ( ! empty( $inc_prod[ $gateway ] ) ) {
				++$count;
			}
			if ( ! empty( $exc_prod[ $gateway ] ) ) {
				++$count;
			}
			$decimals = function_exists( 'wc_get_price_decimals' ) ? (int) wc_get_price_decimals() : 2;
			if ( isset( $prec[ $gateway ] ) && '' !== (string) $prec[ $gateway ] && ! in_array( (string) $prec[ $gateway ], array( '0', (string) $decimals ), true ) ) {
				++$count;
			}
			return $count;
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (gateway).
		 * @param array $store_ctx Store context from WCJ_V10_REST::get_store_sample_data().
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce     = wp_create_nonce( 'wcj-cat-nonce' );
			$texts     = (array) get_option( 'wcj_gateways_fees_text', array() );
			$types     = (array) get_option( 'wcj_gateways_fees_type', array() );
			$values    = (array) get_option( 'wcj_gateways_fees_value', array() );
			$mins      = (array) get_option( 'wcj_gateways_fees_min_cart_amount', array() );
			$maxs      = (array) get_option( 'wcj_gateways_fees_max_cart_amount', array() );
			$excl_ship = (array) get_option( 'wcj_gateways_fees_exclude_shipping', array() );
			$taxable   = (array) get_option( 'wcj_gateways_fees_is_taxable', array() );
			$rounding  = (array) get_option( 'wcj_gateways_fees_round', array() );

			$gateways = self::get_gateways_list( $texts, $values );
			$gw_ids   = wp_list_pluck( $gateways, 'id' );
			$raw_gw   = isset( $context['gateway'] ) ? (string) $context['gateway'] : '';
			$gateway  = ( '' !== $raw_gw && in_array( $raw_gw, $gw_ids, true ) ) ? $raw_gw : $gw_ids[0];

			$gw_enabled = true;
			$gw_title   = $gateway;
			foreach ( $gateways as $gw_item ) {
				if ( $gw_item['id'] === $gateway ) {
					$gw_enabled = ! empty( $gw_item['enabled'] );
					$gw_title   = $gw_item['title'];
					break;
				}
			}

			$diagnostics   = array();
			$classic_rules = self::count_classic_only_rules( $gateway );
			if ( $classic_rules > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %d: Number of active Classic-only gateway fee rules */
						__( '%d advanced rule(s) from Classic (such as product filters, taxes, or custom precision) are active for this gateway and still apply at checkout.', 'woocommerce-jetpack' ),
						$classic_rules
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=payment_gateways&section=payment_gateways_fees&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
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
			$diagnostics[] = array(
				'level' => 'info',
				'text'  => __( 'Compliance note: Card surcharges are restricted or capped in the EU, UK, and several US states. Consider offering a discount for preferred methods like bank transfer instead.', 'woocommerce-jetpack' ),
			);

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
					'sample_amounts'  => ! empty( $store_ctx['sample_cart_amounts'] ) ? $store_ctx['sample_cart_amounts'] : array( 25, 75, 250 ),
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
		 * Sanitize draft values while preserving untouched live values verbatim.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$gw_ids  = wp_list_pluck( self::get_gateways_list(), 'id' );
			$raw_gw  = array_key_exists( 'gateway', $draft ) ? (string) $draft['gateway'] : $live['gateway'];
			$gateway = in_array( $raw_gw, $gw_ids, true ) ? $raw_gw : $live['gateway'];

			$num_or_live = static function ( $key, $precision, $allow_negative = false ) use ( $draft, $live ) {
				if ( ! array_key_exists( $key, $draft ) || (string) $draft[ $key ] === (string) $live[ $key ] ) {
					return $live[ $key ];
				}
				if ( ! is_numeric( $draft[ $key ] ) ) {
					return '0';
				}
				$num = round( (float) $draft[ $key ], $precision );
				if ( ! $allow_negative ) {
					$num = max( 0, $num );
				}
				return (string) $num;
			};

			$label = ( ! array_key_exists( 'label', $draft ) || (string) $draft['label'] === (string) $live['label'] )
				? $live['label']
				: sanitize_text_field( (string) $draft['label'] );

			return array(
				'enabled'          => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'gateway'          => $gateway,
				'label'            => $label,
				'fee_type'         => isset( $draft['fee_type'] ) && 'percent' === $draft['fee_type'] ? 'percent' : ( ! array_key_exists( 'fee_type', $draft ) ? $live['fee_type'] : 'fixed' ),
				'amount'           => $num_or_live( 'amount', 4, true ),
				'min_cart'         => $num_or_live( 'min_cart', 2, false ),
				'max_cart'         => $num_or_live( 'max_cart', 2, false ),
				'exclude_shipping' => array_key_exists( 'exclude_shipping', $draft ) ? ! empty( $draft['exclude_shipping'] ) : ! empty( $live['exclude_shipping'] ),
				'is_taxable'       => array_key_exists( 'is_taxable', $draft ) ? ! empty( $draft['is_taxable'] ) : ! empty( $live['is_taxable'] ),
				'round'            => array_key_exists( 'round', $draft ) ? ! empty( $draft['round'] ) : ! empty( $live['round'] ),
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
			$default_sub = ! empty( $meta['sample_amounts'][0] ) ? (float) $meta['sample_amounts'][0] : 25.00;
			$subtotal    = isset( $context['subtotal'] ) && is_numeric( $context['subtotal'] ) ? max( 0.0, (float) $context['subtotal'] ) : $default_sub;
			$shipping    = isset( $context['shipping'] ) && is_numeric( $context['shipping'] ) ? max( 0.0, (float) $context['shipping'] ) : 10.00;
			$decimals    = isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 2;

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
		 * Map ONLY changed draft fields onto native wp_options keys, merging arrays from fresh DB snapshot.
		 *
		 * @param array                $draft        Sanitized draft.
		 * @param array<int, string>   $changed_keys Changed diff field keys.
		 * @param array<string, array> $snapshot     Fresh option snapshot from WCJ_V10_Store::snapshot_options().
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys = array(), $snapshot = array() ) {
			if ( empty( $changed_keys ) ) {
				return array();
			}

			$changed = array_flip( $changed_keys );
			$gw      = $draft['gateway'];
			$writes  = array();

			$snap_arr = static function ( $key ) use ( $snapshot ) {
				$val = isset( $snapshot[ $key ] ) ? WCJ_V10_Store::state_value( $snapshot[ $key ] ) : get_option( $key, array() );
				return is_array( $val ) ? $val : array();
			};

			if ( isset( $changed['enabled'] ) ) {
				$writes['wcj_payment_gateways_fees_enabled'] = ! empty( $draft['enabled'] ) ? 'yes' : 'no';
			}

			$array_field_map = array(
				'label'            => array( 'wcj_gateways_fees_text', $draft['label'] ),
				'fee_type'         => array( 'wcj_gateways_fees_type', $draft['fee_type'] ),
				'amount'           => array( 'wcj_gateways_fees_value', $draft['amount'] ),
				'min_cart'         => array( 'wcj_gateways_fees_min_cart_amount', $draft['min_cart'] ),
				'max_cart'         => array( 'wcj_gateways_fees_max_cart_amount', $draft['max_cart'] ),
				'exclude_shipping' => array( 'wcj_gateways_fees_exclude_shipping', ! empty( $draft['exclude_shipping'] ) ? 'yes' : 'no' ),
				'is_taxable'       => array( 'wcj_gateways_fees_is_taxable', ! empty( $draft['is_taxable'] ) ? 'yes' : 'no' ),
				'round'            => array( 'wcj_gateways_fees_round', ! empty( $draft['round'] ) ? 'yes' : 'no' ),
			);

			foreach ( $array_field_map as $field_key => $pair ) {
				if ( isset( $changed[ $field_key ] ) ) {
					list( $opt_key, $opt_val ) = $pair;
					$arr                       = $snap_arr( $opt_key );
					$arr[ $gw ]                = $opt_val;
					$writes[ $opt_key ]        = $arr;
				}
			}

			// Booster rounds using wcj_gateways_fees_round_precision[gw]; set precision=0 when whole-unit rounding is enabled.
			if ( isset( $changed['round'] ) ) {
				$prec_arr                                  = $snap_arr( 'wcj_gateways_fees_round_precision' );
				$store_dec                                 = function_exists( 'wc_get_price_decimals' ) ? (string) (int) wc_get_price_decimals() : '2';
				$prec_arr[ $gw ]                           = ! empty( $draft['round'] ) ? '0' : $store_dec;
				$writes['wcj_gateways_fees_round_precision'] = $prec_arr;
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes (disables module immediately without altering rule data).
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			$cur = isset( $snapshot['wcj_payment_gateways_fees_enabled'] )
				? WCJ_V10_Store::state_value( $snapshot['wcj_payment_gateways_fees_enabled'] )
				: get_option( 'wcj_payment_gateways_fees_enabled', 'no' );
			if ( 'yes' !== $cur ) {
				return array();
			}
			return array(
				'wcj_payment_gateways_fees_enabled' => 'no',
			);
		}
	}

endif;
