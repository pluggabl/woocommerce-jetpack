<?php
/**
 * Booster True V10 - Guided Editor: Customer & Bulk Pricing.
 *
 * Unifies: price_by_user_role + wholesale_price.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Pricing' ) ) :

	/**
	 * Unified Role & Bulk Quantity Pricing editor with stacked discount ledger.
	 */
	class WCJ_V10_Editor_Pricing extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'customer_bulk_pricing';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/customer-bulk-pricing.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'role' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'price_by_user_role';
		}

		/**
		 * Underlying Booster module IDs controlled by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'price_by_user_role', 'wholesale_price' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Customer & Bulk Pricing', 'woocommerce-jetpack' ),
				'description' => __( 'Set role-based % discounts and bulk quantity pricing with a stacked price ledger.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Role & Tier Simulator', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs mapped to this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'price_by_user_role' => self::slug(),
				'wholesale_price'    => self::slug(),
			);
		}

		/**
		 * Resolve valid role slug from request/context and available WP/Booster roles.
		 *
		 * @param array $context   Editor context.
		 * @param array $store_ctx Store context.
		 * @return string
		 */
		private static function resolve_role( $context, $store_ctx = array() ) {
			$roles = isset( $store_ctx['roles'] ) && is_array( $store_ctx['roles'] ) ? $store_ctx['roles'] : array();
			if ( empty( $roles ) && function_exists( 'wp_roles' ) ) {
				foreach ( wp_roles()->get_names() as $slug => $name ) {
					$roles[] = array( 'id' => (string) $slug, 'name' => (string) $name, 'users' => 0 );
				}
				$roles[] = array( 'id' => 'guest', 'name' => __( 'Guest (Logged-out)', 'woocommerce-jetpack' ), 'users' => 0 );
			}
			$role_ids = wp_list_pluck( $roles, 'id' );
			$raw      = isset( $context['role'] ) ? (string) $context['role'] : '';
			if ( '' !== $raw && ( 'guest' === $raw || in_array( $raw, $role_ids, true ) ) ) {
				return $raw;
			}
			if ( in_array( 'customer', $role_ids, true ) ) {
				return 'customer';
			}
			return ! empty( $role_ids ) ? $role_ids[0] : 'customer';
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context (role).
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			$role = self::resolve_role( $context );
			$keys = array(
				'wcj_price_by_user_role_enabled',
				'wcj_price_by_user_role_' . $role,
				'wcj_price_by_user_role_empty_price_' . $role,
				'wcj_price_by_user_role_disable_for_products_on_sale',
				'wcj_wholesale_price_enabled',
				'wcj_wholesale_price_discount_type',
				'wcj_wholesale_price_level_min_qty_1',
				'wcj_wholesale_price_level_discount_percent_1',
				'wcj_wholesale_price_use_total_cart_quantity',
				'wcj_wholesale_price_rounding_enabled',
				'wcj_wholesale_price_show_info_on_cart',
				'wcj_wholesale_price_apply_only_if_no_other_discounts',
			);
			if ( self::is_pro() ) {
				$keys[] = 'wcj_wholesale_price_levels_number';
				$keys[] = 'wcj_wholesale_price_level_min_qty_2';
				$keys[] = 'wcj_wholesale_price_level_discount_percent_2';
			}
			return $keys;
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'role_enabled'                 => __( 'Price by User Role Module', 'woocommerce-jetpack' ),
				'role_discount_pct'            => __( 'Role Discount (%)', 'woocommerce-jetpack' ),
				'role_empty_price'             => __( 'Hide Product Price for Role', 'woocommerce-jetpack' ),
				'role_disable_on_sale'         => __( 'Skip Role Pricing on Sale Items', 'woocommerce-jetpack' ),
				'wholesale_enabled'            => __( 'Wholesale / Bulk Pricing Module', 'woocommerce-jetpack' ),
				'wholesale_qty_scope'          => __( 'Bulk Quantity Scope', 'woocommerce-jetpack' ),
				'wholesale_discount_type'      => __( 'Bulk Discount Type', 'woocommerce-jetpack' ),
				'wholesale_min_qty'            => __( 'Bulk Tier 1 Min Quantity', 'woocommerce-jetpack' ),
				'wholesale_discount'           => __( 'Bulk Tier 1 Discount', 'woocommerce-jetpack' ),
				'wholesale_min_qty_2'          => __( 'Bulk Tier 2 Min Quantity', 'woocommerce-jetpack' ),
				'wholesale_discount_2'         => __( 'Bulk Tier 2 Discount', 'woocommerce-jetpack' ),
				'wholesale_show_cart_info'     => __( 'Show Savings Info in Cart', 'woocommerce-jetpack' ),
				'wholesale_rounding'           => __( 'Round Bulk Unit Price', 'woocommerce-jetpack' ),
				'wholesale_no_other_discounts' => __( 'Skip Bulk When Coupons/Discounts Exist', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, stored_on_orders?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'role_enabled'                 => array(
					'goes_live' => true,
					'impact'    => __( 'Enables or pauses custom user-role prices on your storefront and cart.', 'woocommerce-jetpack' ),
				),
				'role_discount_pct'            => array(
					'goes_live' => true,
					'impact'    => __( 'Changes catalog & checkout prices for logged-in shoppers in this role.', 'woocommerce-jetpack' ),
				),
				'role_empty_price'             => array(
					'goes_live' => true,
					'impact'    => __( 'Hides prices and Add to Cart buttons for shoppers in this role.', 'woocommerce-jetpack' ),
				),
				'wholesale_enabled'            => array(
					'goes_live' => true,
					'impact'    => __( 'Enables or pauses bulk quantity discounts inside WooCommerce cart totals.', 'woocommerce-jetpack' ),
				),
				'wholesale_min_qty'            => array(
					'goes_live' => true,
					'impact'    => __( 'Sets the minimum quantity threshold required to unlock Tier 1 cart pricing.', 'woocommerce-jetpack' ),
				),
				'wholesale_discount'           => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the exact Tier 1 bulk discount applied in shopper carts.', 'woocommerce-jetpack' ),
				),
				'wholesale_min_qty_2'          => array(
					'goes_live' => true,
					'impact'    => __( 'Sets the minimum quantity required for Tier 2 bulk pricing.', 'woocommerce-jetpack' ),
				),
				'wholesale_discount_2'         => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the Tier 2 bulk discount applied in shopper carts.', 'woocommerce-jetpack' ),
				),
				'wholesale_discount_type'      => array(
					'goes_live' => true,
					'impact'    => __( 'Switches bulk discount calculation between % off and fixed amount off.', 'woocommerce-jetpack' ),
				),
				'wholesale_qty_scope'          => array(
					'goes_live' => true,
					'impact'    => __( 'Changes whether quantity counts per product line or across the entire cart.', 'woocommerce-jetpack' ),
				),
				'role_disable_on_sale'         => array(
					'goes_live' => false,
					'impact'    => __( 'Prevents role discounts from stacking on already-discounted sale items.', 'woocommerce-jetpack' ),
				),
				'wholesale_show_cart_info'     => array(
					'goes_live' => false,
					'impact'    => __( 'Displays old price and bulk savings badge beside cart item prices.', 'woocommerce-jetpack' ),
				),
				'wholesale_rounding'           => array(
					'goes_live' => false,
					'impact'    => __( 'Rounds calculated bulk unit prices to store decimal places.', 'woocommerce-jetpack' ),
				),
				'wholesale_no_other_discounts' => array(
					'goes_live' => false,
					'impact'    => __( 'Disables bulk discount automatically when a shopper uses a coupon.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Count Classic-only role/wholesale overrides preserved untouched by this editor.
		 *
		 * @param string $role Selected role slug.
		 * @return int
		 */
		private static function count_classic_only_rules( $role ) {
			global $wpdb;
			$count = 0;
			$cats  = get_option( 'wcj_price_by_user_role_categories', array() );
			$tags  = get_option( 'wcj_price_by_user_role_tags', array() );
			if ( ! empty( $cats ) || ! empty( $tags ) ) {
				++$count;
			}
			$role_mult = (float) get_option( 'wcj_price_by_user_role_' . $role, 1 );
			if ( $role_mult > 1.0 ) {
				++$count;
			}
			$ws_roles = (array) get_option( 'wcj_wholesale_price_by_user_role_roles', array() );
			if ( ! empty( $ws_roles ) ) {
				++$count;
			}
			$per_prod = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_wcj_price_by_user_role_per_product_settings_enabled','_wcj_wholesale_price_per_product_enabled') AND meta_value = 'yes'"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $per_prod > 0 ) {
				++$count;
			}
			$ws_ex_p = get_option( 'wcj_wholesale_price_products_to_exclude', array() );
			$ws_ex_c = get_option( 'wcj_wholesale_price_product_cats_to_exclude', array() );
			if ( ! empty( $ws_ex_p ) || ! empty( $ws_ex_c ) ) {
				++$count;
			}
			return $count;
		}

		/**
		 * Convert stored multiplier string into clean % discount string for simple UX.
		 *
		 * @param mixed $raw_mult Raw option value.
		 * @return string
		 */
		private static function mult_to_pct( $raw_mult ) {
			if ( '' === (string) $raw_mult || ! is_numeric( $raw_mult ) ) {
				return '0';
			}
			$f = (float) $raw_mult;
			if ( $f >= 1.0 || $f < 0.0 ) {
				return '0';
			}
			return (string) round( ( 1.0 - $f ) * 100.0, 2 );
		}

		/**
		 * Load live state, metadata, and diagnostics.
		 *
		 * @param array $context   Request params (role).
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce      = wp_create_nonce( 'wcj-cat-nonce' );
			$is_pro     = self::is_pro();
			$roles      = isset( $store_ctx['roles'] ) ? $store_ctx['roles'] : array();
			$role       = self::resolve_role( $context, $store_ctx );
			$raw_mult   = (string) get_option( 'wcj_price_by_user_role_' . $role, '1' );
			$levels_num = (int) get_option( 'wcj_wholesale_price_levels_number', 1 );

			$role_users = 0;
			$role_label = $role;
			foreach ( $roles as $r ) {
				if ( $r['id'] === $role ) {
					$role_users = (int) $r['users'];
					$role_label = $r['name'];
					break;
				}
			}

			$diagnostics = array();
			if ( ! $is_pro && $levels_num > 1 ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %d: Number of stored bulk pricing levels */
						__( '%d bulk quantity tiers from Classic/Elite are saved (Tier 1 stays active in Free; extra tiers are preserved untouched if you upgrade).', 'woocommerce-jetpack' ),
						$levels_num
					),
				);
			} elseif ( $is_pro && $levels_num > 2 ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => sprintf(
						/* translators: %d: Number of configured wholesale levels */
						__( '%d wholesale quantity levels are saved on this store. Tiers 1-2 can be edited here while Tiers 3-%d are preserved untouched in Classic View.', 'woocommerce-jetpack' ),
						$levels_num,
						$levels_num
					),
				);
			}
			if ( is_numeric( $raw_mult ) && (float) $raw_mult > 1.0 ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: 1: Role label, 2: Multiplier number */
						__( '%1$s currently uses a Classic markup multiplier (x%2$s). Leaving "Discount %% off" at 0 preserves that markup; entering a discount replaces it.', 'woocommerce-jetpack' ),
						$role_label,
						rtrim( rtrim( number_format( (float) $raw_mult, 2, '.', '' ), '0' ), '.' )
					),
				);
			}
			$classic_rules = self::count_classic_only_rules( $role );
			if ( $classic_rules > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %d: Count of Classic-only pricing rules */
						__( '%d advanced Classic rule(s) (per-product overrides, markups, category multipliers, or role-specific wholesale tiers) are active and preserved untouched.', 'woocommerce-jetpack' ),
						$classic_rules
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=price_by_user_role&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			if ( 0 === $role_users && 'guest' !== $role ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => sprintf(
						/* translators: %s: User role name */
						__( '%s currently has 0 registered users on your store, so no logged-in customers will see that role price yet.', 'woocommerce-jetpack' ),
						$role_label
					),
				);
			}
			if ( ! empty( $store_ctx['has_page_cache'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Page cache detected: ensure logged-in users bypass static HTML cache so shoppers always see their correct role price.', 'woocommerce-jetpack' ),
				);
			}
			$diagnostics[] = array(
				'level' => 'info',
				'text'  => __( 'Bulk quantity discounts calculate in WooCommerce Cart & Checkout only (use [wcj_product_wholesale_price_table] shortcode to show tiers on product pages).', 'woocommerce-jetpack' ),
			);

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=price_by_user_role&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'role'                         => $role,
					'role_enabled'                 => 'yes' === get_option( 'wcj_price_by_user_role_enabled', 'no' ),
					'role_multiplier'              => $raw_mult,
					'role_discount_pct'            => self::mult_to_pct( $raw_mult ),
					'role_empty_price'             => 'yes' === get_option( 'wcj_price_by_user_role_empty_price_' . $role, 'no' ),
					'role_disable_on_sale'         => 'yes' === get_option( 'wcj_price_by_user_role_disable_for_products_on_sale', 'no' ),
					'wholesale_enabled'            => 'yes' === get_option( 'wcj_wholesale_price_enabled', 'no' ),
					'wholesale_discount_type'      => 'fixed' === get_option( 'wcj_wholesale_price_discount_type', 'percent' ) ? 'fixed' : 'percent',
					'wholesale_qty_scope'          => (string) get_option( 'wcj_wholesale_price_use_total_cart_quantity', 'no' ),
					'wholesale_min_qty'            => (string) get_option( 'wcj_wholesale_price_level_min_qty_1', '0' ),
					'wholesale_discount'           => (string) get_option( 'wcj_wholesale_price_level_discount_percent_1', '0' ),
					'wholesale_min_qty_2'          => (string) get_option( 'wcj_wholesale_price_level_min_qty_2', '0' ),
					'wholesale_discount_2'         => (string) get_option( 'wcj_wholesale_price_level_discount_percent_2', '0' ),
					'wholesale_show_cart_info'     => 'yes' === get_option( 'wcj_wholesale_price_show_info_on_cart', 'no' ),
					'wholesale_rounding'           => 'yes' === get_option( 'wcj_wholesale_price_rounding_enabled', 'yes' ),
					'wholesale_no_other_discounts' => 'yes' === get_option( 'wcj_wholesale_price_apply_only_if_no_other_discounts', 'no' ),
				),
				'meta'        => array(
					'is_pro'           => $is_pro,
					'levels_number'    => $levels_num,
					'roles'            => $roles,
					'role_label'       => $role_label,
					'role_users'       => $role_users,
					'sample_product'   => $store_ctx['product'],
					'currency_symbol'  => $store_ctx['currency_symbol'],
					'decimals'         => $store_ctx['decimals'],
					'verify_url'       => $store_ctx['product']['permalink'],
					'verify_label'     => __( 'View Product on Live Store', 'woocommerce-jetpack' ),
					'diagnostics'      => $diagnostics,
					'classic_sections' => array(
						'price_by_user_role' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=price_by_user_role&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'wholesale_price'    => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=wholesale_price&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					),
				),
			);
		}

		/**
		 * Sanitize draft values while preserving untouched multipliers & locking Free extras cleanly.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$is_pro = self::is_pro();
			$role   = isset( $draft['role'] ) ? self::resolve_role( array( 'role' => (string) $draft['role'] ) ) : $live['role'];

			if ( ! array_key_exists( 'role_discount_pct', $draft ) || (string) $draft['role_discount_pct'] === (string) $live['role_discount_pct'] ) {
				$role_pct  = $live['role_discount_pct'];
				$role_mult = $live['role_multiplier'];
			} else {
				$pct_num   = is_numeric( $draft['role_discount_pct'] ) ? round( (float) $draft['role_discount_pct'], 2 ) : 0.0;
				$role_pct  = (string) max( 0.0, min( 100.0, $pct_num ) );
				$role_mult = (string) round( max( 0.0, 1.0 - ( (float) $role_pct / 100.0 ) ), 6 );
			}

			$scope = array_key_exists( 'wholesale_qty_scope', $draft ) ? (string) $draft['wholesale_qty_scope'] : (string) $live['wholesale_qty_scope'];
			if ( ! in_array( $scope, array( 'no', 'total_wholesale', 'yes' ), true ) ) {
				$scope = 'no';
			}

			$clean_num = static function ( $key, $prec = 2 ) use ( $draft, $live ) {
				if ( ! array_key_exists( $key, $draft ) || (string) $draft[ $key ] === (string) $live[ $key ] ) {
					return $live[ $key ];
				}
				return is_numeric( $draft[ $key ] ) ? (string) max( 0, round( (float) $draft[ $key ], $prec ) ) : '0';
			};

			return array(
				'role'                         => $role,
				'role_enabled'                 => array_key_exists( 'role_enabled', $draft ) ? ! empty( $draft['role_enabled'] ) : ! empty( $live['role_enabled'] ),
				'role_discount_pct'            => $role_pct,
				'role_multiplier'              => $role_mult,
				'role_empty_price'             => array_key_exists( 'role_empty_price', $draft ) ? ! empty( $draft['role_empty_price'] ) : ! empty( $live['role_empty_price'] ),
				'role_disable_on_sale'         => array_key_exists( 'role_disable_on_sale', $draft ) ? ! empty( $draft['role_disable_on_sale'] ) : ! empty( $live['role_disable_on_sale'] ),
				'wholesale_enabled'            => array_key_exists( 'wholesale_enabled', $draft ) ? ! empty( $draft['wholesale_enabled'] ) : ! empty( $live['wholesale_enabled'] ),
				'wholesale_discount_type'      => ( array_key_exists( 'wholesale_discount_type', $draft ) && 'fixed' === $draft['wholesale_discount_type'] ) ? 'fixed' : ( ! array_key_exists( 'wholesale_discount_type', $draft ) ? $live['wholesale_discount_type'] : 'percent' ),
				'wholesale_qty_scope'          => $scope,
				'wholesale_min_qty'            => ( ! array_key_exists( 'wholesale_min_qty', $draft ) || (string) $draft['wholesale_min_qty'] === (string) $live['wholesale_min_qty'] )
					? $live['wholesale_min_qty']
					: (string) max( 0, absint( $draft['wholesale_min_qty'] ) ),
				'wholesale_discount'           => $clean_num( 'wholesale_discount', 2 ),
				'wholesale_min_qty_2'          => ! $is_pro
					? $live['wholesale_min_qty_2']
					: ( ( ! array_key_exists( 'wholesale_min_qty_2', $draft ) || (string) $draft['wholesale_min_qty_2'] === (string) $live['wholesale_min_qty_2'] )
						? $live['wholesale_min_qty_2']
						: (string) max( 0, absint( $draft['wholesale_min_qty_2'] ) ) ),
				'wholesale_discount_2'         => ! $is_pro ? $live['wholesale_discount_2'] : $clean_num( 'wholesale_discount_2', 2 ),
				'wholesale_show_cart_info'     => array_key_exists( 'wholesale_show_cart_info', $draft ) ? ! empty( $draft['wholesale_show_cart_info'] ) : ! empty( $live['wholesale_show_cart_info'] ),
				'wholesale_rounding'           => array_key_exists( 'wholesale_rounding', $draft ) ? ! empty( $draft['wholesale_rounding'] ) : ! empty( $live['wholesale_rounding'] ),
				'wholesale_no_other_discounts' => array_key_exists( 'wholesale_no_other_discounts', $draft ) ? ! empty( $draft['wholesale_no_other_discounts'] ) : ! empty( $live['wholesale_no_other_discounts'] ),
			);
		}

		/**
		 * Validate wholesale quantity floors and percentage caps before publishing.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			if ( ! empty( $draft['wholesale_enabled'] ) ) {
				$disc = (float) $draft['wholesale_discount'];
				$min  = (int) $draft['wholesale_min_qty'];
				$floor = max( 1, (int) get_option( 'wcj_wholesale_price_max_qty_level', 1 ) );
				if ( $disc > 0 && $min < $floor ) {
					return new WP_Error(
						'wcj_v10_wholesale_min_qty',
						sprintf(
							/* translators: %d: Minimum allowed bulk quantity threshold */
							__( 'Set a Bulk Tier 1 Min Quantity of at least %d (a minimum of 0 never triggers a bulk discount).', 'woocommerce-jetpack' ),
							$floor
						),
						array( 'status' => 400 )
					);
				}
				if ( 'percent' === $draft['wholesale_discount_type'] && $disc > 100.0 ) {
					return new WP_Error(
						'wcj_v10_wholesale_pct_cap',
						__( 'Bulk percentage discount cannot exceed 100%.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
			}
			return true;
		}

		/**
		 * Project stacked Role + Bulk Quantity discount ledger matching WooCommerce runtime hooks.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Context.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context );
			$prod      = isset( $meta['sample_product'] ) && is_array( $meta['sample_product'] ) ? $meta['sample_product'] : array();
			$base_num  = ! empty( $prod['price_num'] ) ? (float) $prod['price_num'] : 76.0;
			$is_sale   = ! empty( $prod['on_sale'] );
			$decimals   = isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 2;
			$is_pro     = self::is_pro();
			$levels_num = isset( $meta['levels_number'] ) ? (int) $meta['levels_number'] : (int) get_option( 'wcj_wholesale_price_levels_number', 1 );
			$floor      = max( 1, (int) get_option( 'wcj_wholesale_price_max_qty_level', 1 ) );

			$project = static function ( $cfg, $is_after ) use ( $base_num, $is_sale, $decimals, $is_pro, $floor, $levels_num ) {
				$role_on      = ! empty( $cfg['role_enabled'] ) && ! ( $is_sale && ! empty( $cfg['role_disable_on_sale'] ) );
				$role_empty   = $role_on && ! empty( $cfg['role_empty_price'] );
				$role_mult    = (float) $cfg['role_multiplier'];
				$after_role   = $role_on ? ( $base_num * $role_mult ) : $base_num;

				$min1         = (int) $cfg['wholesale_min_qty'];
				$disc1        = (float) $cfg['wholesale_discount'];
				$allow_t2     = $is_pro && ( $levels_num >= 2 || ( $is_after && (int) $cfg['wholesale_min_qty_2'] > 0 ) );
				$min2         = $allow_t2 ? (int) $cfg['wholesale_min_qty_2'] : 0;
				$disc2        = $allow_t2 ? (float) $cfg['wholesale_discount_2'] : 0.0;
				$round_on     = ! empty( $cfg['wholesale_rounding'] );
				$ws_type      = $cfg['wholesale_discount_type'];

				$sample_q1    = 1;
				$sample_q2    = max( 5, $min1 );
				$sample_q3    = $min2 > $sample_q2 ? $min2 : ( $sample_q2 * 2 );
				$qtys         = array_values( array_unique( array( $sample_q1, $sample_q2, $sample_q3 ) ) );

				$calc_unit = static function ( $qty ) use ( $cfg, $after_role, $min1, $disc1, $min2, $disc2, $floor, $round_on, $decimals, $ws_type, $role_empty ) {
					if ( $role_empty ) {
						return null;
					}
					$unit = $after_role;
					if ( ! empty( $cfg['wholesale_enabled'] ) ) {
						$chosen_disc = 0.0;
						$best_min    = 0;
						if ( $min1 >= $floor && $qty >= $min1 && $min1 >= $best_min ) {
							$best_min    = $min1;
							$chosen_disc = $disc1;
						}
						if ( $min2 >= $floor && $qty >= $min2 && $min2 >= $best_min ) {
							$chosen_disc = $disc2;
						}
						if ( $chosen_disc > 0 ) {
							$unit = 'fixed' === $ws_type
								? max( 0.0, $after_role - $chosen_disc )
								: max( 0.0, $after_role * ( 1.0 - ( $chosen_disc / 100.0 ) ) );
							if ( $round_on ) {
								$unit = round( $unit, $decimals );
							}
						}
					}
					return round( $unit, $decimals );
				};

				$tiers = array();
				foreach ( $qtys as $q ) {
					$u       = $calc_unit( $q );
					$tiers[] = array(
						'qty'        => $q,
						'unit_price' => $u,
						'line_total' => null === $u ? null : round( $u * $q, $decimals ),
					);
				}

				return array(
					'base_price'         => round( $base_num, $decimals ),
					'after_role'         => $role_empty ? null : round( $after_role, $decimals ),
					'role_empty'         => $role_empty,
					'role_enabled'       => ! empty( $cfg['role_enabled'] ),
					'wholesale_enabled'  => ! empty( $cfg['wholesale_enabled'] ),
					'stacks_both'        => $role_on && ( 1.0 !== $role_mult ) && ! empty( $cfg['wholesale_enabled'] ) && $disc1 > 0,
					'qty_scope'          => (string) ( $cfg['wholesale_qty_scope'] ?? 'no' ),
					'show_cart_info'     => ! empty( $cfg['wholesale_show_cart_info'] ),
					'rounding'           => ! empty( $cfg['wholesale_rounding'] ),
					'no_other_discounts' => ! empty( $cfg['wholesale_no_other_discounts'] ),
					'disable_on_sale'    => ! empty( $cfg['role_disable_on_sale'] ),
					'tiers'              => $tiers,
				);
			};

			return array(
				'before' => $project( $live, false ),
				'after'  => $project( $draft, true ),
			);
		}

		/**
		 * Map ONLY changed draft fields onto native wp_options keys.
		 *
		 * @param array                $draft        Sanitized draft.
		 * @param array<int, string>   $changed_keys Changed diff keys.
		 * @param array<string, array> $snapshot     Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys = array(), $snapshot = array() ) {
			if ( empty( $changed_keys ) ) {
				return array();
			}

			$changed = array_flip( $changed_keys );
			$role    = $draft['role'];
			$writes  = array();
			$map     = array(
				'role_enabled'                 => array( 'wcj_price_by_user_role_enabled', ! empty( $draft['role_enabled'] ) ? 'yes' : 'no' ),
				'role_discount_pct'            => array( 'wcj_price_by_user_role_' . $role, $draft['role_multiplier'] ),
				'role_empty_price'             => array( 'wcj_price_by_user_role_empty_price_' . $role, ! empty( $draft['role_empty_price'] ) ? 'yes' : 'no' ),
				'role_disable_on_sale'         => array( 'wcj_price_by_user_role_disable_for_products_on_sale', ! empty( $draft['role_disable_on_sale'] ) ? 'yes' : 'no' ),
				'wholesale_enabled'            => array( 'wcj_wholesale_price_enabled', ! empty( $draft['wholesale_enabled'] ) ? 'yes' : 'no' ),
				'wholesale_discount_type'      => array( 'wcj_wholesale_price_discount_type', $draft['wholesale_discount_type'] ),
				'wholesale_qty_scope'          => array( 'wcj_wholesale_price_use_total_cart_quantity', $draft['wholesale_qty_scope'] ),
				'wholesale_min_qty'            => array( 'wcj_wholesale_price_level_min_qty_1', $draft['wholesale_min_qty'] ),
				'wholesale_discount'           => array( 'wcj_wholesale_price_level_discount_percent_1', $draft['wholesale_discount'] ),
				'wholesale_show_cart_info'     => array( 'wcj_wholesale_price_show_info_on_cart', ! empty( $draft['wholesale_show_cart_info'] ) ? 'yes' : 'no' ),
				'wholesale_rounding'           => array( 'wcj_wholesale_price_rounding_enabled', ! empty( $draft['wholesale_rounding'] ) ? 'yes' : 'no' ),
				'wholesale_no_other_discounts' => array( 'wcj_wholesale_price_apply_only_if_no_other_discounts', ! empty( $draft['wholesale_no_other_discounts'] ) ? 'yes' : 'no' ),
			);

			if ( self::is_pro() ) {
				$map['wholesale_min_qty_2']  = array( 'wcj_wholesale_price_level_min_qty_2', $draft['wholesale_min_qty_2'] );
				$map['wholesale_discount_2'] = array( 'wcj_wholesale_price_level_discount_percent_2', $draft['wholesale_discount_2'] );
			}

			foreach ( $map as $field => $pair ) {
				if ( isset( $changed[ $field ] ) ) {
					$writes[ $pair[0] ] = $pair[1];
				}
			}

			// In Paid editions, raise levels_number only upward if Tier 2 is actively configured (never truncate > 2).
			if ( self::is_pro() && ( isset( $changed['wholesale_min_qty_2'] ) || isset( $changed['wholesale_discount_2'] ) ) ) {
				$cur_levels = (int) self::snap_val( $snapshot, 'wcj_wholesale_price_levels_number', 1 );
				if ( $cur_levels < 2 && ( (int) $draft['wholesale_min_qty_2'] > 0 || (float) $draft['wholesale_discount_2'] > 0 ) ) {
					$writes['wcj_wholesale_price_levels_number'] = '2';
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes (disables active pricing modules cleanly).
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			$writes = array();
			foreach ( array( 'wcj_price_by_user_role_enabled', 'wcj_wholesale_price_enabled' ) as $k ) {
				if ( 'yes' === self::snap_val( $snapshot, $k, 'no' ) ) {
					$writes[ $k ] = 'no';
				}
			}
			return $writes;
		}
	}

endif;
