<?php
/**
 * Booster True V10 - Guided Editor: Shipping Options & Free Shipping Bar.
 *
 * Unifies: shipping_options + left_to_free_shipping.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Shipping_Free_Bar' ) ) :

	/**
	 * Shipping Options & Left-to-Free Shipping guided editor.
	 */
	class WCJ_V10_Editor_Shipping_Free_Bar extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'shipping_free_bar';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/shipping-free-bar.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array();
		}

		/**
		 * Primary Classic module section fallback.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'shipping_options';
		}

		/**
		 * Underlying Booster module IDs controlled by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'shipping_options', 'left_to_free_shipping' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Shipping Options & Free Shipping Bar', 'woocommerce-jetpack' ),
				'description' => __( 'Hide paid shipping when Free Shipping unlocks and show live "Left to Free Shipping" cart bars.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Shipping & Bar Simulator', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'shipping_options'      => self::slug(),
				'left_to_free_shipping' => self::slug(),
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
			$keys = array(
				'wcj_shipping_options_enabled',
				'wcj_shipping_hide_if_free_available_all',
				'wcj_shipping_hide_if_free_available_type',
				'wcj_shipping_most_expensive_enabled',
				'wcj_shipping_most_expensive_ignored_methods',
				'wcj_left_to_free_shipping_enabled',
				'wcj_shipping_left_to_free_info_enabled_cart',
				'wcj_shipping_left_to_free_info_content_cart',
				'wcj_shipping_left_to_free_info_position_cart',
				'wcj_shipping_left_to_free_info_content_reached',
			);
			if ( self::is_pro() ) {
				$keys[] = 'wcj_shipping_left_to_free_info_enabled_mini_cart';
				$keys[] = 'wcj_shipping_left_to_free_info_content_mini_cart';
				$keys[] = 'wcj_shipping_left_to_free_info_enabled_checkout';
				$keys[] = 'wcj_shipping_left_to_free_info_content_checkout';
			}
			if ( self::is_elite() ) {
				$keys[] = 'wcj_shipping_bar_enabled';
				$keys[] = 'wcj_shipping_bar_position';
				$keys[] = 'wcj_shipping_bar_pages';
				$keys[] = 'wcj_shipping_bar_message';
				$keys[] = 'wcj_shipping_bar_bg_color';
				$keys[] = 'wcj_shipping_bar_text_color';
				$keys[] = 'wcj_shipping_left_to_free_progress_bar_enable_for';
				$keys[] = 'wcj_shipping_left_to_free_progress_color';
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
				'so_enabled'             => __( 'Shipping Options Module', 'woocommerce-jetpack' ),
				'hide_if_free'           => __( 'Hide Paid Rates When Free Shipping Unlocks', 'woocommerce-jetpack' ),
				'hide_type'              => __( 'Rates Kept With Free Shipping', 'woocommerce-jetpack' ),
				'most_expensive_enabled' => __( 'Show Only Most Expensive Rate', 'woocommerce-jetpack' ),
				'ltfs_enabled'           => __( 'Left to Free Shipping Module', 'woocommerce-jetpack' ),
				'cart_enabled'           => __( 'Show Progress Message on Cart', 'woocommerce-jetpack' ),
				'cart_content'           => __( 'Cart Progress Message', 'woocommerce-jetpack' ),
				'cart_position'          => __( 'Cart Message Hook Position', 'woocommerce-jetpack' ),
				'reached_content'        => __( 'Free Shipping Reached Message', 'woocommerce-jetpack' ),
				'mini_cart_enabled'      => __( 'Show Message in Mini-Cart', 'woocommerce-jetpack' ),
				'checkout_enabled'       => __( 'Show Message on Checkout', 'woocommerce-jetpack' ),
				'bar_enabled'            => __( 'Enable Sticky Storefront Bar (Elite)', 'woocommerce-jetpack' ),
				'bar_position'           => __( 'Sticky Bar Position', 'woocommerce-jetpack' ),
				'bar_message'            => __( 'Sticky Bar Message', 'woocommerce-jetpack' ),
				'bar_bg_color'           => __( 'Sticky Bar Background Color', 'woocommerce-jetpack' ),
				'progress_cart'          => __( 'Show Visual Progress Bar on Cart (Elite)', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'so_enabled'             => array(
					'goes_live' => true,
					'impact'    => __( 'Activates or pauses Booster shipping rate filtering on cart and checkout.', 'woocommerce-jetpack' ),
				),
				'hide_if_free'           => array(
					'goes_live' => true,
					'impact'    => __( 'Hides paid shipping methods as soon as Free Shipping becomes available.', 'woocommerce-jetpack' ),
				),
				'hide_type'              => array(
					'goes_live' => true,
					'impact'    => __( 'Controls whether Local Pickup or custom shipping methods remain visible alongside Free Shipping.', 'woocommerce-jetpack' ),
				),
				'most_expensive_enabled' => array(
					'goes_live' => true,
					'impact'    => __( 'Shows only the single highest-cost paid shipping rate (plus ignored methods like Free Shipping).', 'woocommerce-jetpack' ),
				),
				'ltfs_enabled'           => array(
					'goes_live' => true,
					'impact'    => __( 'Enables or pauses "Left to Free Shipping" progress messages on your storefront.', 'woocommerce-jetpack' ),
				),
				'cart_enabled'           => array(
					'goes_live' => true,
					'impact'    => __( 'Displays the live progress banner inside the Classic Cart totals area.', 'woocommerce-jetpack' ),
				),
				'cart_content'           => array(
					'goes_live' => true,
					'impact'    => __( 'Updates shopper-facing banner copy showing remaining amount to Free Shipping.', 'woocommerce-jetpack' ),
				),
				'cart_position'          => array(
					'goes_live' => false,
					'impact'    => __( 'Changes the Classic Cart hook position for AJAX cart refresh updates.', 'woocommerce-jetpack' ),
				),
				'reached_content'        => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the celebratory message shown once Free Shipping is unlocked.', 'woocommerce-jetpack' ),
				),
				'mini_cart_enabled'      => array(
					'goes_live' => true,
					'impact'    => __( 'Displays the remaining-to-free message inside Classic Mini-Cart widgets.', 'woocommerce-jetpack' ),
				),
				'checkout_enabled'       => array(
					'goes_live' => true,
					'impact'    => __( 'Displays the remaining-to-free reminder on Classic Checkout.', 'woocommerce-jetpack' ),
				),
				'bar_enabled'            => array(
					'goes_live' => true,
					'impact'    => __( 'Prints a floating top/bottom Free Shipping banner across selected storefront pages.', 'woocommerce-jetpack' ),
				),
				'bar_position'           => array(
					'goes_live' => true,
					'impact'    => __( 'Moves the sticky Free Shipping bar between Top and Bottom of screen.', 'woocommerce-jetpack' ),
				),
				'bar_message'            => array(
					'goes_live' => true,
					'impact'    => __( 'Updates sticky storefront bar copy and placeholders.', 'woocommerce-jetpack' ),
				),
				'bar_bg_color'           => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the sticky Free Shipping bar background color.', 'woocommerce-jetpack' ),
				),
				'progress_cart'          => array(
					'goes_live' => true,
					'impact'    => __( 'Renders the Cart info message as a visual progress meter.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Bust WooCommerce shipping package rate caches immediately after publishing or rolling back rules.
		 *
		 * @param array<int, string> $written_keys Written option keys.
		 * @return void
		 */
		public static function on_after_write( $written_keys ) {
			unset( $written_keys );
			delete_transient( 'wcj_v10_ship_ctx_v1' );
			if ( class_exists( 'WC_Cache_Helper' ) ) {
				WC_Cache_Helper::get_transient_version( 'shipping', true );
			}
		}

		/**
		 * Introspect WooCommerce Shipping Zones for Free Shipping thresholds and methods.
		 *
		 * @return array<string, mixed>
		 */
		private static function inspect_wc_shipping() {
			$cached = get_transient( 'wcj_v10_ship_ctx_v1' );
			if ( is_array( $cached ) && isset( $cached['zone_methods'] ) ) {
				return $cached;
			}
			$has_free_method  = false;
			$min_amount       = 75.0;
			$zone_name        = __( 'Sample Zone (Preview)', 'woocommerce-jetpack' );
			$ignore_discounts = false;
			$methods          = array();

			if ( class_exists( 'WC_Shipping_Zones' ) ) {
				$zones = WC_Shipping_Zones::get_zones();
				$zone0 = new WC_Shipping_Zone( 0 );
				$all   = is_array( $zones ) ? $zones : array();
				$all[] = array(
					'zone_name'        => $zone0->get_zone_name(),
					'shipping_methods' => $zone0->get_shipping_methods( true ),
				);
				foreach ( $all as $z ) {
					$z_name   = isset( $z['zone_name'] ) ? (string) $z['zone_name'] : __( 'Default Zone', 'woocommerce-jetpack' );
					$z_meths  = isset( $z['shipping_methods'] ) && is_array( $z['shipping_methods'] ) ? $z['shipping_methods'] : array();
					$z_list   = array();
					$z_found  = false;
					$z_min    = 0.0;
					$z_ign    = false;

					foreach ( $z_meths as $m ) {
						if ( ! is_object( $m ) || ( isset( $m->enabled ) && 'yes' !== $m->enabled ) ) {
							continue;
						}
						$mid   = isset( $m->id ) ? (string) $m->id : '';
						$title = method_exists( $m, 'get_title' ) ? wp_strip_all_tags( (string) $m->get_title() ) : $mid;
						$cost  = isset( $m->cost ) && is_numeric( $m->cost ) ? (float) $m->cost : 0.0;
						$key   = $mid . ':' . ( isset( $m->instance_id ) ? (int) $m->instance_id : 1 );

						if ( 'free_shipping' === $mid ) {
							$cost = 0.0;
							$req  = isset( $m->requires ) ? (string) $m->requires : '';
							$amt  = isset( $m->min_amount ) && is_numeric( $m->min_amount ) ? (float) $m->min_amount : 0.0;
							if ( in_array( $req, array( 'min_amount', 'either', 'both' ), true ) && $amt > 0 ) {
								$z_found = true;
								$z_min   = $amt;
								$z_ign   = isset( $m->ignore_discounts ) && 'yes' === $m->ignore_discounts;
							}
						}
						$z_list[] = array(
							'key'       => $key,
							'method_id' => $mid,
							'title'     => '' !== $title ? $title : $mid,
							'cost'      => $cost,
						);
					}
					if ( $z_found && ! $has_free_method ) {
						$has_free_method  = true;
						$min_amount       = $z_min;
						$zone_name        = $z_name;
						$ignore_discounts = $z_ign;
						$methods          = $z_list;
						break;
					}
					if ( empty( $methods ) && ! empty( $z_list ) ) {
						$methods   = $z_list;
						$zone_name = $z_name;
					}
				}
			}

			if ( empty( $methods ) ) {
				$methods = array(
					array(
						'key'       => 'flat_rate:1',
						'method_id' => 'flat_rate',
						'title'     => __( 'Flat Rate Standard', 'woocommerce-jetpack' ),
						'cost'      => 10.0,
					),
					array(
						'key'       => 'flat_rate:2',
						'method_id' => 'flat_rate',
						'title'     => __( 'Express Courier', 'woocommerce-jetpack' ),
						'cost'      => 22.0,
					),
					array(
						'key'       => 'local_pickup:3',
						'method_id' => 'local_pickup',
						'title'     => __( 'Local Pickup', 'woocommerce-jetpack' ),
						'cost'      => 0.0,
					),
					array(
						'key'       => 'free_shipping:4',
						'method_id' => 'free_shipping',
						'title'     => __( 'Free Shipping', 'woocommerce-jetpack' ),
						'cost'      => 0.0,
					),
				);
			} else {
				$has_free_row = false;
				foreach ( $methods as $row ) {
					if ( 'free_shipping' === $row['method_id'] ) {
						$has_free_row = true;
						break;
					}
				}
				if ( ! $has_free_row ) {
					$methods[] = array(
						'key'       => 'free_shipping:99',
						'method_id' => 'free_shipping',
						'title'     => __( 'Free Shipping (Zone sample)', 'woocommerce-jetpack' ),
						'cost'      => 0.0,
					);
				}
			}

			$res = array(
				'has_free_method'  => $has_free_method,
				'min_amount'       => $min_amount,
				'zone_name'        => $zone_name,
				'ignore_discounts' => $ignore_discounts,
				'zone_methods'     => $methods,
			);
			set_transient( 'wcj_v10_ship_ctx_v1', $res, 30 );
			return $res;
		}

		/**
		 * Normalize allowed rate-hiding enum.
		 *
		 * @param mixed $raw Raw value.
		 * @return string
		 */
		private static function normalize_hide_type( $raw ) {
			$val = sanitize_key( (string) $raw );
			return in_array( $val, array( 'hide_all', 'except_local_pickup', 'flat_rate_only' ), true ) ? $val : 'hide_all';
		}

		/**
		 * Normalize string/bool array option safely.
		 *
		 * @param mixed $raw Raw option value.
		 * @return array<int, string>
		 */
		private static function to_str_array( $raw ) {
			if ( ! is_array( $raw ) ) {
				return array();
			}
			return array_values( array_filter( array_map( 'sanitize_key', $raw ), static function ( $v ) { return '' !== $v; } ) );
		}

		/**
		 * Detect classic-only rules that live outside V10 Simple Setup.
		 *
		 * @return array<string, mixed>
		 */
		private static function detect_classic_details() {
			$is_pro   = self::is_pro();
			$is_elite = self::is_elite();

			$by_product = 'yes' === get_option( 'wcj_shipping_free_shipping_by_product_enabled', 'no' );
			$raw_ign    = get_option( 'wcj_shipping_most_expensive_ignored_methods', array( 'free_shipping' ) );
			$ign        = self::to_str_array( $raw_ign );
			if ( empty( $ign ) && ! is_array( $raw_ign ) ) {
				$ign = array( 'free_shipping' );
			}
			$paid_hide_type  = (string) get_option( 'wcj_shipping_hide_if_free_available_type', 'hide_all' );
			$free_stored_mod = ( ! $is_pro && 'hide_all' !== $paid_hide_type )
				|| ( ! $is_pro && array( 'free_shipping' ) !== $ign && ! empty( $ign ) );
			$plus_stored_bar = ( $is_pro && ! $is_elite ) && 'yes' === get_option( 'wcj_shipping_bar_enabled', 'no' );
			$adv_min         = get_option( 'wcj_shipping_left_to_free_advanced_min_amount', false );
			$ship_disc       = $is_elite && 'yes' === get_option( 'left_to_free_enable_shipping_discount', 'no' );

			return array(
				'by_product'      => $by_product,
				'ignored_methods' => $ign,
				'paid_hide_type'  => $paid_hide_type,
				'free_stored_mod' => $free_stored_mod,
				'plus_stored_bar' => $plus_stored_bar,
				'adv_min'         => $adv_min,
				'ship_disc'       => $ship_disc,
			);
		}

		/**
		 * Load live state, metadata, and diagnostics.
		 *
		 * @param array $context   Editor context.
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			unset( $context );
			$nonce    = wp_create_nonce( 'wcj-cat-nonce' );
			$is_pro   = self::is_pro();
			$is_elite = self::is_elite();
			$snap     = WCJ_V10_Store::snapshot_options( self::owned_options() );
			$ship_ctx = self::inspect_wc_shipping();
			$classic  = self::detect_classic_details();

			$raw_hide_type = self::snap_val( $snap, 'wcj_shipping_hide_if_free_available_type', 'hide_all' );
			$hide_type     = $is_pro ? self::normalize_hide_type( $raw_hide_type ) : 'hide_all';
			$bar_pages     = $is_elite ? self::to_str_array( self::snap_val( $snap, 'wcj_shipping_bar_pages', array() ) ) : array();
			$prog_for      = $is_elite ? self::to_str_array( self::snap_val( $snap, 'wcj_shipping_left_to_free_progress_bar_enable_for', array() ) ) : array();
			$bar_bg_raw    = $is_elite ? (string) self::snap_val( $snap, 'wcj_shipping_bar_bg_color', '#f5f5f5' ) : '#3d9cd2';
			$prog_col_raw  = $is_elite ? (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_progress_color', '#6495ED' ) : '#6495ED';

			$diagnostics = array();
			if ( empty( $ship_ctx['has_free_method'] ) ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => __( 'No active WooCommerce Free Shipping method with a minimum order amount was found in your Shipping Zones. Add one in WooCommerce Shipping Zones for live storefront messages and rate-hiding to trigger.', 'woocommerce-jetpack' ),
					'action_label' => __( 'Open Shipping Zones', 'woocommerce-jetpack' ),
					'action_url'   => $store_ctx['shipping_zones_url'] ?? admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
				);
			}
			if ( ! empty( $ship_ctx['ignore_discounts'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Your WooCommerce Free Shipping method ignores coupon discounts, whereas Booster\'s Left to Free Shipping subtracts cart discounts. Check the "With 10% coupon" preview tab to verify the threshold matches your policy.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $store_ctx['is_blocks_cart'] ) || ! empty( $store_ctx['is_blocks_checkout'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Block Cart/Checkout detected: Rate-hiding ("Shipping Options") works inside Cart & Checkout Blocks via Store API, while Left to Free Shipping text hooks target Classic Cart/Checkout templates.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $store_ctx['has_page_cache'] ) && $is_elite ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Page caching detected: The Elite sticky bar is server-rendered on Shop/Home pages and may cache a visitor\'s cart amount unless dynamic/cached exclusions are set.', 'woocommerce-jetpack' ),
				);
			}
			if ( false !== $classic['adv_min'] && '0' !== (string) $classic['adv_min'] ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => '' === (string) $classic['adv_min']
						? __( 'Hidden Classic option wcj_shipping_left_to_free_advanced_min_amount is empty string "", causing the reached message to always show on Elite. Update in Classic Tools if unintended.', 'woocommerce-jetpack' )
						: sprintf( __( 'Hidden override active: Free shipping threshold is overridden to %s.', 'woocommerce-jetpack' ), is_scalar( $classic['adv_min'] ) ? (string) $classic['adv_min'] : 'array' ),
				);
			}
			if ( ! empty( $classic['free_stored_mod'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Paid Shipping Options rules (for example Local Pickup exception or custom ignored methods) are saved in your database from a higher plan and preserved untouched.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $classic['plus_stored_bar'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Saved Elite Sticky Bar settings exist in your database and are preserved untouched (inactive on Booster Plus).', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $classic['by_product'] ) || ! empty( $classic['ship_disc'] ) ) {
				$diagnostics[] = array(
					'level'        => 'info',
					'text'         => __( 'Additional Classic rules (Free Shipping by Product or Elite Shipping Discount) are active and preserved untouched.', 'woocommerce-jetpack' ),
					'action_label' => __( 'Open in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=shipping_and_orders&section=shipping_options&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=shipping_and_orders&section=shipping_options&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'so_enabled'             => 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_options_enabled', 'no' ),
					'hide_if_free'           => 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_hide_if_free_available_all', 'no' ),
					'hide_type'              => $hide_type,
					'most_expensive_enabled' => 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_most_expensive_enabled', 'no' ),
					'ltfs_enabled'           => 'yes' === (string) self::snap_val( $snap, 'wcj_left_to_free_shipping_enabled', 'no' ),
					'cart_enabled'           => 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_enabled_cart', 'no' ),
					'cart_content'           => (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_content_cart', '%left_to_free% left to free shipping' ),
					'cart_position'          => (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_position_cart', 'woocommerce_after_cart_totals' ),
					'reached_content'        => (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_content_reached', 'You have Free delivery' ),
					'mini_cart_enabled'      => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_enabled_mini_cart', 'no' ),
					'mini_cart_content'      => $is_pro ? (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_content_mini_cart', '%left_to_free% left to free shipping' ) : '%left_to_free% left to free shipping',
					'checkout_enabled'       => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_enabled_checkout', 'no' ),
					'checkout_content'       => $is_pro ? (string) self::snap_val( $snap, 'wcj_shipping_left_to_free_info_content_checkout', '%left_to_free% left to free shipping' ) : '%left_to_free% left to free shipping',
					'bar_enabled'            => $is_elite && 'yes' === (string) self::snap_val( $snap, 'wcj_shipping_bar_enabled', 'no' ),
					'bar_position'           => $is_elite ? ( ( 'bottom' === (string) self::snap_val( $snap, 'wcj_shipping_bar_position', 'top' ) ) ? 'bottom' : 'top' ) : 'bottom',
					'bar_message'            => $is_elite ? (string) self::snap_val( $snap, 'wcj_shipping_bar_message', '' ) : 'Add %left_to_free% more for Free Shipping!',
					'bar_bg_color'           => '' !== sanitize_hex_color( $bar_bg_raw ) ? sanitize_hex_color( $bar_bg_raw ) : '#3d9cd2',
					'progress_cart'          => $is_elite && in_array( 'woocommerce_cart', $prog_for, true ),
					'progress_color'         => '' !== sanitize_hex_color( $prog_col_raw ) ? sanitize_hex_color( $prog_col_raw ) : '#6495ED',
					'ignored_methods'        => $classic['ignored_methods'],
					'bar_pages'              => $bar_pages,
					'progress_enable_for'    => $prog_for,
				),
				'meta'        => array(
					'is_pro'             => $is_pro,
					'is_elite'           => $is_elite,
					'currency_symbol'    => $store_ctx['currency_symbol'] ?? '$',
					'decimals'           => (int) ( $store_ctx['decimals'] ?? 2 ),
					'has_free_method'    => $ship_ctx['has_free_method'],
					'min_amount'         => $ship_ctx['min_amount'],
					'zone_name'          => $ship_ctx['zone_name'],
					'ignore_discounts'   => $ship_ctx['ignore_discounts'],
					'zone_methods'       => $ship_ctx['zone_methods'],
					'shipping_zones_url' => $store_ctx['shipping_zones_url'] ?? admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
					'is_blocks_cart'     => ! empty( $store_ctx['is_blocks_cart'] ),
					'is_blocks_checkout' => ! empty( $store_ctx['is_blocks_checkout'] ),
					'verify_url'         => $store_ctx['cart_url'] ?? home_url( '/cart/' ),
					'verify_label'       => __( 'Verify on Live Cart', 'woocommerce-jetpack' ),
					'ltfs_classic_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=shipping_and_orders&section=left_to_free_shipping&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					'diagnostics'        => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values while preserving untouched live values & paid gates verbatim.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$is_pro   = self::is_pro();
			$is_elite = self::is_elite();

			$txt = static function ( $k, $html = false ) use ( $draft, $live ) {
				if ( ! array_key_exists( $k, $draft ) || (string) $draft[ $k ] === (string) $live[ $k ] ) {
					return $live[ $k ];
				}
				return $html ? wp_kses_post( (string) $draft[ $k ] ) : sanitize_text_field( (string) $draft[ $k ] );
			};

			$cart_pos = $live['cart_position'];
			if ( array_key_exists( 'cart_position', $draft ) ) {
				$allowed_pos = array(
					'woocommerce_after_cart_totals',
					'woocommerce_before_cart_totals',
					'woocommerce_before_cart',
					'woocommerce_after_cart',
					'woocommerce_proceed_to_checkout',
					'woocommerce_cart_totals_before_shipping',
					'woocommerce_cart_totals_after_shipping',
				);
				$cand_pos = sanitize_key( (string) $draft['cart_position'] );
				$cart_pos = in_array( $cand_pos, $allowed_pos, true ) ? $cand_pos : $live['cart_position'];
			}

			$bar_bg = $live['bar_bg_color'];
			if ( $is_elite && array_key_exists( 'bar_bg_color', $draft ) ) {
				$hex = sanitize_hex_color( (string) $draft['bar_bg_color'] );
				if ( $hex ) {
					$bar_bg = $hex;
				}
			}

			$prog_col = $live['progress_color'];
			if ( $is_elite && array_key_exists( 'progress_color', $draft ) ) {
				$hex = sanitize_hex_color( (string) $draft['progress_color'] );
				if ( $hex ) {
					$prog_col = $hex;
				}
			}

			$progress_cart = $is_elite
				? ( array_key_exists( 'progress_cart', $draft ) ? ! empty( $draft['progress_cart'] ) : ! empty( $live['progress_cart'] ) )
				: false;
			$prog_arr = is_array( $live['progress_enable_for'] ) ? $live['progress_enable_for'] : array();
			if ( $is_elite && array_key_exists( 'progress_cart', $draft ) ) {
				$others = array_values( array_diff( $prog_arr, array( 'woocommerce_cart' ) ) );
				if ( $progress_cart ) {
					$others[] = 'woocommerce_cart';
				}
				$prog_arr = array_values( array_unique( $others ) );
			}

			return array(
				'so_enabled'             => array_key_exists( 'so_enabled', $draft ) ? ! empty( $draft['so_enabled'] ) : ! empty( $live['so_enabled'] ),
				'hide_if_free'           => array_key_exists( 'hide_if_free', $draft ) ? ! empty( $draft['hide_if_free'] ) : ! empty( $live['hide_if_free'] ),
				'hide_type'              => $is_pro
					? ( array_key_exists( 'hide_type', $draft ) ? self::normalize_hide_type( $draft['hide_type'] ) : $live['hide_type'] )
					: $live['hide_type'],
				'most_expensive_enabled' => array_key_exists( 'most_expensive_enabled', $draft ) ? ! empty( $draft['most_expensive_enabled'] ) : ! empty( $live['most_expensive_enabled'] ),
				'ltfs_enabled'           => array_key_exists( 'ltfs_enabled', $draft ) ? ! empty( $draft['ltfs_enabled'] ) : ! empty( $live['ltfs_enabled'] ),
				'cart_enabled'           => array_key_exists( 'cart_enabled', $draft ) ? ! empty( $draft['cart_enabled'] ) : ! empty( $live['cart_enabled'] ),
				'cart_content'           => $txt( 'cart_content', true ),
				'cart_position'          => $cart_pos,
				'reached_content'        => $txt( 'reached_content', true ),
				'mini_cart_enabled'      => $is_pro ? ( array_key_exists( 'mini_cart_enabled', $draft ) ? ! empty( $draft['mini_cart_enabled'] ) : ! empty( $live['mini_cart_enabled'] ) ) : false,
				'mini_cart_content'      => $is_pro ? $txt( 'mini_cart_content', true ) : $live['mini_cart_content'],
				'checkout_enabled'       => $is_pro ? ( array_key_exists( 'checkout_enabled', $draft ) ? ! empty( $draft['checkout_enabled'] ) : ! empty( $live['checkout_enabled'] ) ) : false,
				'checkout_content'       => $is_pro ? $txt( 'checkout_content', true ) : $live['checkout_content'],
				'bar_enabled'            => $is_elite ? ( array_key_exists( 'bar_enabled', $draft ) ? ! empty( $draft['bar_enabled'] ) : ! empty( $live['bar_enabled'] ) ) : false,
				'bar_position'           => $is_elite && array_key_exists( 'bar_position', $draft ) ? ( 'bottom' === $draft['bar_position'] ? 'bottom' : 'top' ) : $live['bar_position'],
				'bar_message'            => $is_elite ? $txt( 'bar_message', true ) : $live['bar_message'],
				'bar_bg_color'           => $bar_bg,
				'progress_cart'          => $progress_cart,
				'progress_color'         => $prog_col,
				'ignored_methods'        => $live['ignored_methods'],
				'bar_pages'              => $live['bar_pages'],
				'progress_enable_for'    => $prog_arr,
			);
		}

		/**
		 * Project deterministic BEFORE & AFTER shipping rate list + bar preview.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview context.
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$is_pro   = ! empty( $meta['is_pro'] );
			$is_elite = ! empty( $meta['is_elite'] );
			$min_amt  = max( 1.0, (float) ( $meta['min_amount'] ?? 75.0 ) );
			$decimals = (int) ( $meta['decimals'] ?? 2 );
			$sym      = (string) ( $meta['currency_symbol'] ?? '$' );
			$scenario = isset( $context['shipping_scenario'] ) ? sanitize_key( (string) $context['shipping_scenario'] ) : 'below';
			$tab      = isset( $context['shipping_tab'] ) ? sanitize_key( (string) $context['shipping_tab'] ) : 'cart';
			$methods  = isset( $meta['zone_methods'] ) && is_array( $meta['zone_methods'] ) ? $meta['zone_methods'] : array();

			$raw_sub = round( $min_amt * 0.6, $decimals );
			$disc    = 0.0;
			if ( 'reached' === $scenario ) {
				$raw_sub = round( $min_amt, $decimals );
			} elseif ( 'coupon' === $scenario ) {
				$raw_sub = round( $min_amt * 1.05, $decimals );
				$disc    = round( $raw_sub * 0.10, $decimals );
			}
			$cart_total     = round( $raw_sub - $disc, $decimals );
			$free_unlocked  = $cart_total >= $min_amt;
			$left_to_free   = max( 0.0, round( $min_amt - $cart_total, $decimals ) );
			$pct            = (int) min( 100, max( 0, round( ( $cart_total / $min_amt ) * 100 ) ) );
			$fmt            = static function ( $amt ) use ( $sym, $decimals ) {
				return $sym . number_format( (float) $amt, $decimals, '.', '' );
			};

			$render_rates = static function ( $cfg ) use ( $methods, $free_unlocked, $is_pro ) {
				$out = array();
				foreach ( $methods as $m ) {
					$out[] = array(
						'key'       => $m['key'],
						'method_id' => $m['method_id'],
						'title'     => $m['title'],
						'cost'      => (float) $m['cost'],
						'visible'   => ( 'free_shipping' !== $m['method_id'] ) || $free_unlocked,
					);
				}
				if ( empty( $cfg['so_enabled'] ) ) {
					return $out;
				}
				if ( ! empty( $cfg['most_expensive_enabled'] ) ) {
					$ignored  = is_array( $cfg['ignored_methods'] ) ? $cfg['ignored_methods'] : array( 'free_shipping' );
					$max_idx  = -1;
					$max_cost = -1.0;
					foreach ( $out as $idx => $r ) {
						if ( ! $r['visible'] || in_array( $r['method_id'], $ignored, true ) ) {
							continue;
						}
						if ( $r['cost'] > $max_cost ) {
							$max_cost = $r['cost'];
							$max_idx  = $idx;
						}
					}
					foreach ( $out as $idx => $r ) {
						if ( ! $r['visible'] ) {
							continue;
						}
						if ( ! in_array( $r['method_id'], $ignored, true ) && $idx !== $max_idx ) {
							$out[ $idx ]['visible'] = false;
						}
					}
				}
				if ( ! empty( $cfg['hide_if_free'] ) && $free_unlocked ) {
					$htype = $is_pro ? (string) $cfg['hide_type'] : 'hide_all';
					foreach ( $out as $idx => $r ) {
						if ( ! $r['visible'] || 'free_shipping' === $r['method_id'] ) {
							continue;
						}
						if ( 'except_local_pickup' === $htype && false !== strpos( $r['key'], 'local_pickup' ) ) {
							continue;
						}
						if ( 'flat_rate_only' === $htype && false === strpos( $r['key'], 'flat_rate' ) ) {
							continue;
						}
						$out[ $idx ]['visible'] = false;
					}
				}
				return $out;
			};

			$render_bar = static function ( $cfg, $force_sample ) use ( $tab, $free_unlocked, $left_to_free, $min_amt, $cart_total, $pct, $fmt, $is_pro, $is_elite ) {
				$enabled_ltfs = ! empty( $cfg['ltfs_enabled'] );
				$active_for_tab = false;
				$tpl = (string) ( $cfg['cart_content'] ?: '%left_to_free% left to free shipping' );
				$is_progress = false;

				if ( 'mini_cart' === $tab ) {
					$active_for_tab = $enabled_ltfs && ( ( $is_pro && ! empty( $cfg['mini_cart_enabled'] ) ) || $force_sample );
					$tpl = $is_pro && ! empty( $cfg['mini_cart_content'] ) ? (string) $cfg['mini_cart_content'] : '%left_to_free% left to free shipping';
				} elseif ( 'checkout' === $tab ) {
					$active_for_tab = $enabled_ltfs && ( ( $is_pro && ! empty( $cfg['checkout_enabled'] ) ) || $force_sample );
					$tpl = $is_pro && ! empty( $cfg['checkout_content'] ) ? (string) $cfg['checkout_content'] : '%left_to_free% left to free shipping';
				} elseif ( 'sticky_bar' === $tab ) {
					$active_for_tab = $enabled_ltfs && ( ( $is_elite && ! empty( $cfg['bar_enabled'] ) ) || $force_sample );
					$tpl = ! empty( $cfg['bar_message'] ) ? (string) $cfg['bar_message'] : 'Add %left_to_free% more for Free Shipping!';
				} else {
					$active_for_tab = $enabled_ltfs && ! empty( $cfg['cart_enabled'] );
					$is_progress    = ( $is_elite && ! empty( $cfg['progress_cart'] ) ) || ( ! $is_elite && $force_sample );
				}

				if ( ! $active_for_tab ) {
					return array(
						'visible'     => false,
						'text'        => '',
						'pct'         => $pct,
						'is_progress' => false,
						'bar_pos'     => (string) ( $cfg['bar_position'] ?? 'top' ),
						'bar_bg'      => (string) ( $cfg['bar_bg_color'] ?? '#3d9cd2' ),
						'prog_color'  => (string) ( $cfg['progress_color'] ?? '#6495ED' ),
					);
				}

				if ( $free_unlocked ) {
					$msg = trim( wp_strip_all_tags( (string) ( $cfg['reached_content'] ?? '' ) ) );
					if ( '' === $msg ) {
						$msg = 'You have Free delivery';
					}
				} else {
					$msg = str_replace(
						array( '%left_to_free%', '%free_shipping_min_amount%', '%cart_total%' ),
						array( $fmt( $left_to_free ), $fmt( $min_amt ), $fmt( $cart_total ) ),
						wp_strip_all_tags( $tpl )
					);
				}

				return array(
					'visible'     => true,
					'text'        => $msg,
					'pct'         => $pct,
					'is_progress' => $is_progress,
					'bar_pos'     => (string) ( $cfg['bar_position'] ?? 'top' ),
					'bar_bg'      => (string) ( $cfg['bar_bg_color'] ?? '#3d9cd2' ),
					'prog_color'  => (string) ( $cfg['progress_color'] ?? '#6495ED' ),
				);
			};

			return array(
				'before' => array(
					'cart_total'    => $cart_total,
					'min_amount'    => $min_amt,
					'free_unlocked' => $free_unlocked,
					'rates'         => $render_rates( $live ),
					'bar'           => $render_bar( $live, false ),
				),
				'after'  => array(
					'cart_total'    => $cart_total,
					'min_amount'    => $min_amt,
					'free_unlocked' => $free_unlocked,
					'rates'         => $render_rates( $draft ),
					'bar'           => $render_bar( $draft, false ),
				),
			);
		}

		/**
		 * Map changed draft keys to authoritative wp_options writes.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $changed_keys Changed diff keys.
		 * @param array $snapshot     Pre-write DB snapshot.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft, $changed_keys, $snapshot = array() ) {
			$map      = array_flip( (array) $changed_keys );
			$is_pro   = self::is_pro();
			$is_elite = self::is_elite();
			$writes   = array();

			$pair = array(
				'so_enabled'             => 'wcj_shipping_options_enabled',
				'hide_if_free'           => 'wcj_shipping_hide_if_free_available_all',
				'most_expensive_enabled' => 'wcj_shipping_most_expensive_enabled',
				'ltfs_enabled'           => 'wcj_left_to_free_shipping_enabled',
				'cart_enabled'           => 'wcj_shipping_left_to_free_info_enabled_cart',
			);
			foreach ( $pair as $dk => $ok ) {
				if ( isset( $map[ $dk ] ) ) {
					$writes[ $ok ] = ! empty( $draft[ $dk ] ) ? 'yes' : 'no';
				}
			}

			if ( $is_pro && isset( $map['hide_type'] ) ) {
				$writes['wcj_shipping_hide_if_free_available_type'] = $draft['hide_type'];
			}
			if ( isset( $map['cart_content'] ) ) {
				$writes['wcj_shipping_left_to_free_info_content_cart'] = $draft['cart_content'];
			}
			if ( isset( $map['cart_position'] ) ) {
				$writes['wcj_shipping_left_to_free_info_position_cart'] = $draft['cart_position'];
			}
			if ( isset( $map['reached_content'] ) ) {
				$writes['wcj_shipping_left_to_free_info_content_reached'] = $draft['reached_content'];
			}

			// Materialize mandatory array option on Most Expensive so runtime in_array() never receives non-array.
			if ( ! empty( $draft['most_expensive_enabled'] ) ) {
				$cur_ign = isset( $snapshot['wcj_shipping_most_expensive_ignored_methods'] ) && ! empty( $snapshot['wcj_shipping_most_expensive_ignored_methods']['exists'] )
					? WCJ_V10_Store::state_value( $snapshot['wcj_shipping_most_expensive_ignored_methods'] )
					: null;
				if ( ! is_array( $cur_ign ) ) {
					$writes['wcj_shipping_most_expensive_ignored_methods'] = array( 'free_shipping' );
				}
			}

			if ( $is_pro ) {
				if ( isset( $map['mini_cart_enabled'] ) ) {
					$writes['wcj_shipping_left_to_free_info_enabled_mini_cart'] = ! empty( $draft['mini_cart_enabled'] ) ? 'yes' : 'no';
				}
				if ( isset( $map['checkout_enabled'] ) ) {
					$writes['wcj_shipping_left_to_free_info_enabled_checkout'] = ! empty( $draft['checkout_enabled'] ) ? 'yes' : 'no';
				}
			}

			if ( $is_elite ) {
				if ( isset( $map['bar_enabled'] ) ) {
					$writes['wcj_shipping_bar_enabled'] = ! empty( $draft['bar_enabled'] ) ? 'yes' : 'no';
				}
				if ( isset( $map['bar_position'] ) ) {
					$writes['wcj_shipping_bar_position'] = $draft['bar_position'];
				}
				if ( isset( $map['bar_message'] ) ) {
					$writes['wcj_shipping_bar_message'] = $draft['bar_message'];
				}
				if ( isset( $map['bar_bg_color'] ) ) {
					$writes['wcj_shipping_bar_bg_color'] = $draft['bar_bg_color'];
				}
				if ( isset( $map['progress_cart'] ) ) {
					$writes['wcj_shipping_left_to_free_progress_bar_enable_for'] = is_array( $draft['progress_enable_for'] ) ? $draft['progress_enable_for'] : array();
				}
				// Guard Elite runtime foreach() TypeError if progress_bar_enable_for is absent or scalar in DB!
				if ( ! empty( $draft['ltfs_enabled'] ) && ! isset( $writes['wcj_shipping_left_to_free_progress_bar_enable_for'] ) ) {
					$cur_prog = isset( $snapshot['wcj_shipping_left_to_free_progress_bar_enable_for'] ) && ! empty( $snapshot['wcj_shipping_left_to_free_progress_bar_enable_for']['exists'] )
						? WCJ_V10_Store::state_value( $snapshot['wcj_shipping_left_to_free_progress_bar_enable_for'] )
						: null;
					if ( ! is_array( $cur_prog ) ) {
						$writes['wcj_shipping_left_to_free_progress_bar_enable_for'] = array();
					}
				}
				// Ensure Elite Sticky Bar has display pages & message initialized when switched on!
				if ( ! empty( $draft['bar_enabled'] ) ) {
					$cur_pages = isset( $snapshot['wcj_shipping_bar_pages'] ) && ! empty( $snapshot['wcj_shipping_bar_pages']['exists'] )
						? WCJ_V10_Store::state_value( $snapshot['wcj_shipping_bar_pages'] )
						: null;
					if ( ! is_array( $cur_pages ) || empty( $cur_pages ) ) {
						$writes['wcj_shipping_bar_pages'] = array( 'shop', 'cart', 'checkout' );
					}
					if ( '' === trim( (string) $draft['bar_message'] ) && ! isset( $writes['wcj_shipping_bar_message'] ) ) {
						$writes['wcj_shipping_bar_message'] = 'Add %left_to_free% more for Free Shipping!';
					}
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff disables both Shipping Options & Left to Free Shipping immediately.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			$writes = array();
			if ( 'yes' === (string) self::snap_val( $snapshot, 'wcj_shipping_options_enabled', 'no' ) ) {
				$writes['wcj_shipping_options_enabled'] = 'no';
			}
			if ( 'yes' === (string) self::snap_val( $snapshot, 'wcj_left_to_free_shipping_enabled', 'no' ) ) {
				$writes['wcj_left_to_free_shipping_enabled'] = 'no';
			}
			return $writes;
		}
	}

endif;
