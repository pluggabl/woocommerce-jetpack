<?php
/**
 * Booster True V10 - Guided Editor: Multicurrency (Currency Switcher).
 *
 * Enforces zero-price rate guards, base-currency '1' normalization, Blocks revert protection, and volatile auto-rates.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Multicurrency' ) ) :

	/**
	 * Multicurrency guided editor.
	 */
	class WCJ_V10_Editor_Multicurrency extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'multicurrency';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/multicurrency.js';
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
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'multicurrency';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'multicurrency' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Multicurrency (Currency Switcher)', 'woocommerce-jetpack' ),
				'description' => __( 'Display converted storefront prices with exchange rates, clean rounding, and cache safety checks.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Storefront Currency Switcher', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'multicurrency' => self::slug(),
			);
		}

		/**
		 * Maximum currency slots editable inside Guided View (2 in Free, 3 in Plus/Elite).
		 *
		 * @return int
		 */
		private static function max_slots() {
			return self::is_pro() ? 3 : 2;
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
				'wcj_multicurrency_enabled',
				'wcj_multicurrency_total_number',
				'wcj_multicurrency_currency_1',
				'wcj_multicurrency_exchange_rate_1',
				'wcj_multicurrency_currency_2',
				'wcj_multicurrency_exchange_rate_2',
				'wcj_multicurrency_rounding',
				'wcj_multicurrency_rounding_precision',
				'wcj_multicurrency_revert',
				'wcj_multicurrency_switcher_template',
				'wcj_multicurrency_per_product_enabled',
				'wcj_multicurrency_default_currency',
				'wcj_multicurrency_default_currency_force',
			);
			if ( self::is_pro() ) {
				$keys[] = 'wcj_multicurrency_currency_3';
				$keys[] = 'wcj_multicurrency_exchange_rate_3';
				$keys[] = 'wcj_multicurrency_exchange_rate_update_auto';
				$keys[] = 'wcj_multicurrency_convert_shipping_values';
				$keys[] = 'wcj_multicurrency_compatibility_free_shipping';
				$keys[] = 'wcj_multicurrency_compatibility_wc_coupons';
			}
			return $keys;
		}

		/**
		 * Mark exchange rates as volatile when automatic updates are running in Plus/Elite.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function volatile_options( $context = array() ) {
			unset( $context );
			if (
				self::is_pro()
				&& 'auto' === get_option( 'wcj_multicurrency_exchange_rate_update_auto', 'manual' )
				&& 'yes' === get_option( 'wcj_currency_exchange_rates_enabled', 'no' )
			) {
				return array(
					'wcj_multicurrency_exchange_rate_1',
					'wcj_multicurrency_exchange_rate_2',
					'wcj_multicurrency_exchange_rate_3',
				);
			}
			return array();
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'            => __( 'Multicurrency Module', 'woocommerce-jetpack' ),
				'total_currencies'   => __( 'Active Currency Slots', 'woocommerce-jetpack' ),
				'currency_1'         => __( 'Currency #1', 'woocommerce-jetpack' ),
				'rate_1'             => __( 'Currency #1 Rate', 'woocommerce-jetpack' ),
				'currency_2'         => __( 'Currency #2', 'woocommerce-jetpack' ),
				'rate_2'             => __( 'Currency #2 Rate', 'woocommerce-jetpack' ),
				'currency_3'         => __( 'Currency #3 (Plus/Elite)', 'woocommerce-jetpack' ),
				'rate_3'             => __( 'Currency #3 Rate (Plus/Elite)', 'woocommerce-jetpack' ),
				'rounding'           => __( 'Converted Price Rounding', 'woocommerce-jetpack' ),
				'rounding_precision' => __( 'Rounding Decimal Places', 'woocommerce-jetpack' ),
				'revert'             => __( 'Revert Currency at Cart/Checkout', 'woocommerce-jetpack' ),
				'switcher_template'  => __( 'Switcher Display Format', 'woocommerce-jetpack' ),
				'per_product'        => __( 'Enable Per-Product Custom Currency Prices', 'woocommerce-jetpack' ),
				'rate_update_mode'   => __( 'Exchange Rate Updates (Plus/Elite)', 'woocommerce-jetpack' ),
				'convert_shipping'   => __( 'Convert Shipping Costs (Plus/Elite)', 'woocommerce-jetpack' ),
				'compat_free_ship'   => __( 'Convert Free Shipping Minimum Amount (Plus/Elite)', 'woocommerce-jetpack' ),
				'compat_coupons'     => __( 'Convert Fixed Coupons (Plus/Elite)', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'            => array(
					'goes_live' => true,
					'impact'    => __( 'Activates multi-currency price conversion and switcher output on your storefront.', 'woocommerce-jetpack' ),
				),
				'total_currencies'   => array(
					'goes_live' => true,
					'impact'    => __( 'Controls how many currencies shoppers can switch between.', 'woocommerce-jetpack' ),
				),
				'currency_1'         => array(
					'goes_live' => true,
					'impact'    => __( 'Sets Currency #1 in switcher lists and conversion lookup.', 'woocommerce-jetpack' ),
				),
				'rate_1'             => array(
					'goes_live' => true,
					'impact'    => __( 'Changes converted prices for Currency #1.', 'woocommerce-jetpack' ),
				),
				'currency_2'         => array(
					'goes_live' => true,
					'impact'    => __( 'Sets Currency #2 offered to shoppers.', 'woocommerce-jetpack' ),
				),
				'rate_2'             => array(
					'goes_live' => true,
					'impact'    => __( 'Changes all catalog and cart prices when Currency #2 is selected.', 'woocommerce-jetpack' ),
				),
				'currency_3'         => array(
					'goes_live' => true,
					'impact'    => __( 'Sets Currency #3 offered to shoppers.', 'woocommerce-jetpack' ),
				),
				'rate_3'             => array(
					'goes_live' => true,
					'impact'    => __( 'Changes all catalog and cart prices when Currency #3 is selected.', 'woocommerce-jetpack' ),
				),
				'rounding'           => array(
					'goes_live' => true,
					'impact'    => __( 'Rounds converted catalog prices (note: Round Up / Round Down use whole numbers).', 'woocommerce-jetpack' ),
				),
				'rounding_precision' => array(
					'goes_live' => true,
					'impact'    => __( 'Controls decimal precision when rounding is set to Standard Round.', 'woocommerce-jetpack' ),
				),
				'revert'             => array(
					'goes_live' => true,
					'impact'    => __( 'Reverts cart and/or checkout currency back to base currency (Classic Checkout/Cart only).', 'woocommerce-jetpack' ),
				),
				'switcher_template'  => array(
					'goes_live' => true,
					'impact'    => __( 'Formats currency labels inside switcher dropdowns and widgets.', 'woocommerce-jetpack' ),
				),
				'per_product'        => array(
					'goes_live' => false,
					'impact'    => __( 'Shows per-currency price fields inside WooCommerce product edit screens.', 'woocommerce-jetpack' ),
				),
				'rate_update_mode'   => array(
					'goes_live' => false,
					'impact'    => __( 'Switches between manual rates and scheduled cron exchange-rate updates.', 'woocommerce-jetpack' ),
				),
				'convert_shipping'   => array(
					'goes_live' => true,
					'impact'    => __( 'Multiplies live shipping costs by active currency exchange rate.', 'woocommerce-jetpack' ),
				),
				'compat_free_ship'   => array(
					'goes_live' => true,
					'impact'    => __( 'Converts Free Shipping minimum order amounts so Left-to-Free thresholds stay accurate.', 'woocommerce-jetpack' ),
				),
				'compat_coupons'     => array(
					'goes_live' => true,
					'impact'    => __( 'Scales fixed-amount WooCommerce coupons to match active shopper currency.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Bust WooCommerce shipping transient cache after changing rates or shipping conversion settings.
		 *
		 * @param array<int, string> $written_keys Written option keys.
		 * @return void
		 */
		public static function on_after_write( $written_keys ) {
			unset( $written_keys );
			if ( class_exists( 'WC_Cache_Helper' ) ) {
				WC_Cache_Helper::get_transient_version( 'shipping', true );
			}
		}

		/**
		 * Currency map (code => name + symbol) for dropdowns & preview formatting.
		 *
		 * @return array<string, array{code: string, name: string, symbol: string}>
		 */
		private static function currency_catalog() {
			$all_syms = function_exists( 'get_woocommerce_currency_symbols' ) ? get_woocommerce_currency_symbols() : array( 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CAD' => '$', 'AUD' => '$', 'JPY' => '¥' );
			$all_names = function_exists( 'get_woocommerce_currencies' ) ? get_woocommerce_currencies() : array( 'USD' => 'United States (US) dollar', 'EUR' => 'Euro', 'GBP' => 'Pound sterling', 'CAD' => 'Canadian dollar', 'AUD' => 'Australian dollar', 'JPY' => 'Japanese yen' );
			$out = array();
			foreach ( $all_names as $code => $name ) {
				$sym          = isset( $all_syms[ $code ] ) ? html_entity_decode( (string) $all_syms[ $code ], ENT_QUOTES, 'UTF-8' ) : $code;
				$out[ $code ] = array(
					'code'   => (string) $code,
					'name'   => html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ),
					'symbol' => $sym,
				);
			}
			return $out;
		}

		/**
		 * Check whether switcher shortcodes or widgets are placed on active frontend areas.
		 *
		 * @return array<int, string>
		 */
		private static function detect_switcher_locations() {
			global $wpdb;
			$found   = array();
			$widgets = get_option( 'sidebars_widgets', array() );
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $area => $items ) {
					if ( 'wp_inactive_widgets' === $area || ! is_array( $items ) ) {
						continue;
					}
					foreach ( $items as $wid ) {
						if ( is_string( $wid ) && false !== strpos( $wid, 'wcj_widget_multicurrency' ) ) {
							$found[] = __( 'Active Widget Sidebar', 'woocommerce-jetpack' );
							break 2;
						}
					}
				}
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$pages = $wpdb->get_col(
				"SELECT post_title FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE '%wcj_currency_select_%' LIMIT 3"
			);
			if ( is_array( $pages ) ) {
				foreach ( $pages as $title ) {
					$found[] = wp_strip_all_tags( (string) $title );
				}
			}
			return array_values( array_unique( $found ) );
		}

		/**
		 * Normalize exchange rate string; converts any base-currency rate like 1.0/1.00 to exact '1' so Booster skips rounding on base currency.
		 *
		 * @param string $currency Slot currency code.
		 * @param mixed  $rate     Raw rate.
		 * @param string $base_cur Store base currency code.
		 * @return string
		 */
		private static function normalize_rate_str( $currency, $rate, $base_cur ) {
			if ( $currency === $base_cur && is_numeric( $rate ) && abs( (float) $rate - 1.0 ) < 0.0001 ) {
				return '1';
			}
			$str = trim( (string) $rate );
			if ( '1.0' === $str || '1.00' === $str ) {
				return '1';
			}
			return $str;
		}

		/**
		 * Load live state, metadata, and conflict/cache diagnostics.
		 *
		 * @param array $context   Editor context.
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			unset( $context );
			$nonce        = wp_create_nonce( 'wcj-cat-nonce' );
			$preview_mode = ! empty( $store_ctx['preview_mode'] );
			$is_pro       = self::is_pro();
			$snap       = WCJ_V10_Store::snapshot_options( self::owned_options() );
			$base_cur   = strtoupper( (string) ( $store_ctx['store_currency'] ?? 'USD' ) );
			$second_def = 'USD' === $base_cur ? 'EUR' : 'USD';
			$third_def  = 'GBP' === $base_cur ? 'EUR' : 'GBP';
			$cer_on     = 'yes' === get_option( 'wcj_currency_exchange_rates_enabled', 'no' );
			$conflicts  = isset( $store_ctx['mc_conflicts'] ) && is_array( $store_ctx['mc_conflicts'] ) ? $store_ctx['mc_conflicts'] : array();
			$caches     = isset( $store_ctx['cache_plugins'] ) && is_array( $store_ctx['cache_plugins'] ) ? $store_ctx['cache_plugins'] : array();
			$is_blocks  = ! empty( $store_ctx['is_blocks_checkout'] ) || ! empty( $store_ctx['is_blocks_cart'] );

			$cur_1  = strtoupper( (string) self::snap_val( $snap, 'wcj_multicurrency_currency_1', $base_cur ) );
			$rate_1 = self::normalize_rate_str( $cur_1, self::snap_val( $snap, 'wcj_multicurrency_exchange_rate_1', '1' ), $base_cur );
			$cur_2  = strtoupper( (string) self::snap_val( $snap, 'wcj_multicurrency_currency_2', $second_def ) );
			$rate_2 = self::normalize_rate_str( $cur_2, self::snap_val( $snap, 'wcj_multicurrency_exchange_rate_2', 'EUR' === $second_def ? '0.92' : '1.08' ), $base_cur );
			$cur_3  = $is_pro ? strtoupper( (string) self::snap_val( $snap, 'wcj_multicurrency_currency_3', $third_def ) ) : 'GBP';
			$rate_3 = $is_pro ? self::normalize_rate_str( $cur_3, self::snap_val( $snap, 'wcj_multicurrency_exchange_rate_3', '0.79' ), $base_cur ) : '0.79';

			$raw_tot = (int) self::snap_val( $snap, 'wcj_multicurrency_total_number', 2 );
			$tot_num = $is_pro ? max( 2, min( 3, $raw_tot ) ) : 2;

			$rounding  = (string) self::snap_val( $snap, 'wcj_multicurrency_rounding', 'no_round' );
			if ( ! in_array( $rounding, array( 'no_round', 'round', 'round_up', 'round_down' ), true ) ) {
				$rounding = 'no_round';
			}
			$prec      = (string) self::snap_val( $snap, 'wcj_multicurrency_rounding_precision', (string) ( $store_ctx['decimals'] ?? 2 ) );
			$revert    = (string) self::snap_val( $snap, 'wcj_multicurrency_revert', 'no' );
			if ( ! in_array( $revert, array( 'no', 'cart_only', 'yes', 'cart_and_checkout' ), true ) ) {
				$revert = 'no';
			}
			$rate_mode = $is_pro ? ( 'auto' === (string) self::snap_val( $snap, 'wcj_multicurrency_exchange_rate_update_auto', 'manual' ) ? 'auto' : 'manual' ) : 'manual';

			$switchers = $preview_mode ? array() : self::detect_switcher_locations();
			$enabled_l = 'yes' === (string) self::snap_val( $snap, 'wcj_multicurrency_enabled', 'no' );
			$conv_ship = 'yes' === (string) self::snap_val( $snap, 'wcj_multicurrency_convert_shipping_values', 'yes' );
			$role_raw  = get_option( 'wcj_multicurrency_role_defaults_roles', array() );

			$diagnostics = array();
			if ( ! empty( $conflicts ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %s: Conflicting multicurrency plugin(s) */
						__( 'Conflicting currency switcher active: %s. Disable it before enabling Booster Multicurrency (publishing is blocked while both run).', 'woocommerce-jetpack' ),
						implode( ', ', $conflicts )
					),
				);
			}
			if ( ! empty( $caches ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %s: Detected caching plugin(s) */
						__( 'Page cache detected (%s): Exclude the wcj-currency cookie/session from cached pages so one visitor\'s currency is never served to everyone.', 'woocommerce-jetpack' ),
						implode( ', ', $caches )
					),
				);
			}
			if ( $is_blocks && 'no' !== $revert ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Block Cart/Checkout detected: "Revert to default currency" uses Classic hooks and does NOT revert prices inside Cart & Checkout Blocks.', 'woocommerce-jetpack' ),
				);
			}
			if ( $is_pro && 'auto' === $rate_mode && ! $cer_on ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => __( 'Automatic rate updates selected, but Booster\'s "Currency Exchange Rates" module is still Off.', 'woocommerce-jetpack' ),
					'action_label' => __( 'Open Exchange Rates', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=currency_exchange_rates&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			if ( $is_pro && $raw_tot > 3 ) {
				$diagnostics[] = array(
					'level'        => 'info',
					'text'         => sprintf(
						/* translators: %d: total configured currencies */
						__( '%d currencies are configured in Classic View. Guided View edits slots #1-#3 safely while preserving slots #4+ untouched unless you lower Active Currency Slots.', 'woocommerce-jetpack' ),
						$raw_tot
					),
					'action_label' => __( 'Manage #4+ in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=multicurrency&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			if ( ! $is_pro ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Booster Free allows 2 currencies and manual rates (while honoring stored shipping conversion settings). Upgrade to Plus/Elite for 3+ currencies and automatic rate updates.', 'woocommerce-jetpack' ),
				);
			}
			if ( empty( $switchers ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'No switcher widget/shortcode detected yet on published pages — copy [wcj_currency_select_drop_down_list] below or place "Booster - Multicurrency Switcher" in a sidebar.', 'woocommerce-jetpack' ),
				);
			} else {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => sprintf( __( 'Currency switcher found on: %s.', 'woocommerce-jetpack' ), implode( ', ', $switchers ) ),
				);
			}
			if ( '' !== $role_raw && ! is_array( $role_raw ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'Legacy Role Defaults option wcj_multicurrency_role_defaults_roles is non-array in DB; open Classic Role Defaults to re-save cleanly.', 'woocommerce-jetpack' ),
				);
			}
			if ( $raw_tot > self::max_slots() ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => sprintf( __( '%d total currencies exist in Classic Settings; extra slots are preserved untouched.', 'woocommerce-jetpack' ), $raw_tot ),
				);
			}

			$catalog = self::currency_catalog();
			$popular = array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'INR', 'SEK', 'NOK', 'DKK', 'PLN', 'BRL', 'MXN', 'SGD', 'NZD' );
			$options = array();
			if ( isset( $catalog[ $base_cur ] ) ) {
				$options[] = $catalog[ $base_cur ];
			}
			foreach ( $popular as $c ) {
				if ( $c !== $base_cur && isset( $catalog[ $c ] ) ) {
					$options[] = $catalog[ $c ];
				}
			}
			foreach ( $catalog as $code => $item ) {
				if ( $code !== $base_cur && ! in_array( $code, $popular, true ) ) {
					$options[] = $item;
				}
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=prices_and_currencies&section=multicurrency&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'enabled'            => $enabled_l,
					'total_currencies'   => (string) $tot_num,
					'currency_1'         => $cur_1,
					'rate_1'             => $rate_1,
					'currency_2'         => $cur_2,
					'rate_2'             => $rate_2,
					'currency_3'         => $cur_3,
					'rate_3'             => $rate_3,
					'rounding'           => $rounding,
					'rounding_precision' => $prec,
					'revert'             => $revert,
					'switcher_template'  => (string) self::snap_val( $snap, 'wcj_multicurrency_switcher_template', '%currency_name% (%currency_symbol%)' ),
					'per_product'        => 'yes' === (string) self::snap_val( $snap, 'wcj_multicurrency_per_product_enabled', 'yes' ),
					'rate_update_mode'   => $rate_mode,
					'convert_shipping'   => $conv_ship,
					'compat_free_ship'   => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_multicurrency_compatibility_free_shipping', 'no' ),
					'compat_coupons'     => $is_pro && 'yes' === (string) self::snap_val( $snap, 'wcj_multicurrency_compatibility_wc_coupons', 'no' ),
				),
				'meta'        => array(
					'is_pro'               => $is_pro,
					'raw_total_currencies' => $raw_tot,
					'base_currency'        => $base_cur,
					'currency_symbol'    => $store_ctx['currency_symbol'] ?? '$',
					'decimals'           => (int) ( $store_ctx['decimals'] ?? 2 ),
					'product_name'       => $store_ctx['product']['name'] ?? 'Sample Product',
					'sample_price'       => (float) ( $store_ctx['product']['price_num'] ?? 49.0 ),
					'currencies'         => $options,
					'currency_map'       => $catalog,
					'mc_conflicts'       => $conflicts,
					'cache_plugins'      => $caches,
					'is_blocks'          => $is_blocks,
					'switcher_locations' => $switchers,
					'cer_enabled'        => $cer_on,
					'verify_url'         => $store_ctx['shop_url'] ?? home_url( '/shop/' ),
					'verify_label'       => __( 'Verify on Live Shop', 'woocommerce-jetpack' ),
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
			$base_cur = strtoupper( (string) ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD' ) );
			$cur_list = function_exists( 'get_woocommerce_currencies' ) ? get_woocommerce_currencies() : array();

			$clean_cur = static function ( $k ) use ( $draft, $live, $cur_list ) {
				if ( ! array_key_exists( $k, $draft ) ) {
					return $live[ $k ];
				}
				$cand = strtoupper( sanitize_text_field( (string) $draft[ $k ] ) );
				if ( ! empty( $cur_list ) && ! isset( $cur_list[ $cand ] ) ) {
					return $live[ $k ];
				}
				return $cand;
			};

			$clean_rate = static function ( $k, $cur ) use ( $draft, $live, $base_cur ) {
				$raw = array_key_exists( $k, $draft ) ? trim( (string) $draft[ $k ] ) : (string) $live[ $k ];
				return self::normalize_rate_str( $cur, $raw, $base_cur );
			};

			$c1 = $clean_cur( 'currency_1' );
			$c2 = $clean_cur( 'currency_2' );
			$c3 = $is_pro ? $clean_cur( 'currency_3' ) : $live['currency_3'];

			$rnd = $live['rounding'];
			if ( array_key_exists( 'rounding', $draft ) ) {
				$cand = sanitize_key( (string) $draft['rounding'] );
				$rnd  = in_array( $cand, array( 'no_round', 'round', 'round_up', 'round_down' ), true ) ? $cand : $live['rounding'];
			}

			$rev = $live['revert'];
			if ( array_key_exists( 'revert', $draft ) ) {
				$cand = sanitize_key( (string) $draft['revert'] );
				$rev  = in_array( $cand, array( 'no', 'cart_only', 'yes', 'cart_and_checkout' ), true ) ? $cand : $live['revert'];
			}

			$tot = $is_pro && array_key_exists( 'total_currencies', $draft )
				? (string) max( 2, min( 3, absint( $draft['total_currencies'] ) ) )
				: (string) $live['total_currencies'];
			if ( ! $is_pro ) {
				$tot = '2';
			}

			return array(
				'enabled'            => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'total_currencies'   => $tot,
				'currency_1'         => $c1,
				'rate_1'             => $clean_rate( 'rate_1', $c1 ),
				'currency_2'         => $c2,
				'rate_2'             => $clean_rate( 'rate_2', $c2 ),
				'currency_3'         => $c3,
				'rate_3'             => $is_pro ? $clean_rate( 'rate_3', $c3 ) : $live['rate_3'],
				'rounding'           => $rnd,
				'rounding_precision' => ( ! array_key_exists( 'rounding_precision', $draft ) || (string) $draft['rounding_precision'] === (string) $live['rounding_precision'] )
					? $live['rounding_precision']
					: (string) min( 4, max( 0, absint( $draft['rounding_precision'] ) ) ),
				'revert'             => $rev,
				'switcher_template'  => ( ! array_key_exists( 'switcher_template', $draft ) || (string) $draft['switcher_template'] === (string) $live['switcher_template'] )
					? $live['switcher_template']
					: sanitize_text_field( (string) $draft['switcher_template'] ),
				'per_product'        => array_key_exists( 'per_product', $draft ) ? ! empty( $draft['per_product'] ) : ! empty( $live['per_product'] ),
				'rate_update_mode'   => $is_pro && array_key_exists( 'rate_update_mode', $draft )
					? ( 'auto' === $draft['rate_update_mode'] ? 'auto' : 'manual' )
					: $live['rate_update_mode'],
				'convert_shipping'   => $is_pro ? ( array_key_exists( 'convert_shipping', $draft ) ? ! empty( $draft['convert_shipping'] ) : ! empty( $live['convert_shipping'] ) ) : $live['convert_shipping'],
				'compat_free_ship'   => $is_pro ? ( array_key_exists( 'compat_free_ship', $draft ) ? ! empty( $draft['compat_free_ship'] ) : ! empty( $live['compat_free_ship'] ) ) : false,
				'compat_coupons'     => $is_pro ? ( array_key_exists( 'compat_coupons', $draft ) ? ! empty( $draft['compat_coupons'] ) : ! empty( $live['compat_coupons'] ) ) : false,
			);
		}

		/**
		 * Enforce zero-price prevention, conflict blockers, and Blocks revert guard.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			if ( empty( $draft['enabled'] ) ) {
				return true;
			}
			$changed_map = array_flip( (array) $changed_keys );

			// Check for conflicting multicurrency plugins when enabling/changing.
			$conflicts = array();
			if ( class_exists( 'WC_Payments_Multi_Currency' ) || class_exists( '\WCPay\MultiCurrency\MultiCurrency' ) ) {
				$conflicts[] = 'WooPayments Multi-Currency';
			}
			if ( class_exists( 'WCML_Multi_Currency' ) ) {
				$conflicts[] = 'WPML / WCML Multi-Currency';
			}
			if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) || class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
				$conflicts[] = 'CURCY Multi Currency';
			}
			if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
				$conflicts[] = 'Aelia Currency Switcher';
			}
			if ( ! empty( $conflicts ) && ( empty( $live['enabled'] ) || isset( $changed_map['enabled'] ) ) ) {
				return new WP_Error(
					'wcj_v10_mc_conflict',
					sprintf(
						__( 'Cannot enable while another currency switcher is active (%s). Disable the other switcher first.', 'woocommerce-jetpack' ),
						implode( ', ', $conflicts )
					),
					array( 'status' => 400 )
				);
			}

			// Positive numeric exchange rate check for every active slot (prevents P0 $0 prices!).
			$slots = max( 2, min( self::max_slots(), (int) $draft['total_currencies'] ) );
			for ( $s = 1; $s <= $slots; $s++ ) {
				$r = (string) ( $draft[ 'rate_' . $s ] ?? '' );
				if ( '' === trim( $r ) || ! is_numeric( $r ) || (float) $r <= 0 ) {
					return new WP_Error(
						'wcj_v10_mc_zero_rate',
						sprintf(
							/* translators: %d: Currency slot number */
							__( 'Exchange rate for Currency #%d must be greater than 0 (empty or 0 rates zero out storefront prices).', 'woocommerce-jetpack' ),
							$s
						),
						array( 'status' => 400 )
					);
				}
			}

			// Block Revert on WooCommerce Cart/Checkout Blocks because is_cart()/is_checkout() return false in Store API.
			if ( 'no' !== $draft['revert'] && ( empty( $live['enabled'] ) || isset( $changed_map['revert'] ) || isset( $changed_map['enabled'] ) ) ) {
				$chk_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
				$crt_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
				$has_blk = ( $chk_id > 0 && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $chk_id ) )
					|| ( $crt_id > 0 && function_exists( 'has_block' ) && has_block( 'woocommerce/cart', $crt_id ) );
				if ( $has_blk ) {
					return new WP_Error(
						'wcj_v10_mc_blocks_revert',
						__( 'Your store uses WooCommerce Cart or Checkout Blocks, where "Revert to default currency" cannot trigger. Set Revert to "Do not revert" or switch Checkout to Classic shortcode.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
			}

			return true;
		}

		/**
		 * Exact conversion + rounding math matching class-wcj-multicurrency.php runtime.
		 *
		 * @param float  $base_amt Base amount.
		 * @param string $rate_str Rate string.
		 * @param string $rounding Rounding mode.
		 * @param int    $prec     Rounding precision.
		 * @return float
		 */
		private static function convert_amt( $base_amt, $rate_str, $rounding, $prec ) {
			if ( '1' === (string) $rate_str ) {
				return (float) $base_amt;
			}
			$p = (float) $base_amt * (float) $rate_str;
			if ( 'round' === $rounding ) {
				return round( $p, $prec );
			}
			if ( 'round_up' === $rounding ) {
				return (float) ceil( $p );
			}
			if ( 'round_down' === $rounding ) {
				return (float) floor( $p );
			}
			return $p;
		}

		/**
		 * Project deterministic BEFORE & AFTER currency switcher + product/shipping preview.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview context.
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$is_pro      = ! empty( $meta['is_pro'] );
			$base_cur    = strtoupper( (string) ( $meta['base_currency'] ?? 'USD' ) );
			$base_price  = (float) ( $meta['sample_price'] ?? 49.0 );
			$base_ship   = 10.0;
			$base_free   = 75.0;
			$cmap        = isset( $meta['currency_map'] ) && is_array( $meta['currency_map'] ) ? $meta['currency_map'] : array();
			$selected    = isset( $context['mc_currency'] ) ? strtoupper( sanitize_text_field( (string) $context['mc_currency'] ) ) : '';

			$base_sym    = (string) ( $meta['currency_symbol'] ?? '$' );
			$project     = static function ( $cfg ) use ( $is_pro, $base_cur, $base_sym, $base_price, $base_ship, $base_free, $cmap, $selected ) {
				$active = ! empty( $cfg['enabled'] );
				$slots  = $is_pro ? max( 2, min( 3, (int) $cfg['total_currencies'] ) ) : 2;
				$list   = array();
				for ( $s = 1; $s <= $slots; $s++ ) {
					$c = strtoupper( (string) ( $cfg[ 'currency_' . $s ] ?? $base_cur ) );
					$r = trim( (string) ( $cfg[ 'rate_' . $s ] ?? '1' ) );
					if ( '' === $r ) {
						$r = '1';
					}
					if ( ! isset( $list[ $c ] ) ) {
						$info = isset( $cmap[ $c ] ) ? $cmap[ $c ] : array( 'code' => $c, 'name' => $c, 'symbol' => $c );
						$list[ $c ] = array(
							'code'   => $c,
							'name'   => $info['name'],
							'symbol' => $info['symbol'],
							'rate'   => $r,
							'label'  => str_replace(
								array( '%currency_name%', '%currency_symbol%', '%currency_code%' ),
								array( $info['name'], $info['symbol'], $c ),
								(string) ( $cfg['switcher_template'] ?: '%currency_name% (%currency_symbol%)' )
							),
						);
					}
				}
				$active_cur = ( '' !== $selected && isset( $list[ $selected ] ) )
					? $selected
					: (string) ( isset( $list[ $cfg['currency_2'] ] ) ? $cfg['currency_2'] : $base_cur );
				if ( ! $active ) {
					$active_cur = $base_cur;
				}
				$cur_obj = isset( $list[ $active_cur ] ) ? $list[ $active_cur ] : array( 'code' => $base_cur, 'symbol' => $base_sym, 'rate' => '1', 'label' => $base_cur );
				$rate    = $active ? (string) $cur_obj['rate'] : '1';
				$prec    = max( 0, min( 4, (int) $cfg['rounding_precision'] ) );
				$prod_p  = self::convert_amt( $base_price, $rate, (string) $cfg['rounding'], $prec );
				$conv_sh = ( ! $active || empty( $cfg['convert_shipping'] ) ) ? $base_ship : (float) $base_ship * (float) $rate;
				$free_th = ( $active && $is_pro && ! empty( $cfg['compat_free_ship'] ) )
					? self::convert_amt( $base_free, $rate, (string) $cfg['rounding'], $prec )
					: $base_free;
				$reverts = $active && in_array( $cfg['revert'], array( 'yes', 'cart_and_checkout' ), true );

				return array(
					'active'           => $active,
					'selected_code'    => $cur_obj['code'],
					'selected_symbol'  => $cur_obj['symbol'],
					'rate'             => $rate,
					'product_price'    => round( $prod_p, 2 ),
					'shipping_price'   => round( $conv_sh, 2 ),
					'free_threshold'   => round( $free_th, 2 ),
					'checkout_amount'  => $reverts ? round( $base_price, 2 ) : round( $prod_p, 2 ),
					'checkout_symbol'  => $reverts ? $base_sym : $cur_obj['symbol'],
					'checkout_pays'    => $reverts ? $base_cur : $cur_obj['code'],
					'reverts_checkout' => $reverts,
					'switcher_items'   => array_values( $list ),
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
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
			$map    = array_flip( (array) $changed_keys );
			$is_pro = self::is_pro();
			$writes = array();

			if ( isset( $map['enabled'] ) ) {
				$writes['wcj_multicurrency_enabled'] = ! empty( $draft['enabled'] ) ? 'yes' : 'no';
			}
			$slots = $is_pro ? max( 2, min( 3, (int) $draft['total_currencies'] ) ) : 2;
			if ( isset( $map['total_currencies'] ) || ( ! empty( $draft['enabled'] ) && empty( $snapshot['wcj_multicurrency_total_number']['exists'] ) ) ) {
				$writes['wcj_multicurrency_total_number'] = $slots;
			}
			for ( $s = 1; $s <= $slots; $s++ ) {
				$ck = 'currency_' . $s;
				$rk = 'rate_' . $s;
				if ( isset( $map[ $ck ] ) || ( ! empty( $draft['enabled'] ) && empty( $snapshot[ 'wcj_multicurrency_currency_' . $s ]['exists'] ) ) ) {
					$writes[ 'wcj_multicurrency_currency_' . $s ] = $draft[ $ck ];
				}
				if ( isset( $map[ $rk ] ) || ( ! empty( $draft['enabled'] ) && ( empty( $snapshot[ 'wcj_multicurrency_exchange_rate_' . $s ]['exists'] ) || '' === trim( (string) WCJ_V10_Store::state_value( $snapshot[ 'wcj_multicurrency_exchange_rate_' . $s ] ) ) ) ) ) {
					$writes[ 'wcj_multicurrency_exchange_rate_' . $s ] = (string) $draft[ $rk ];
				}
			}

			$direct = array(
				'rounding'           => 'wcj_multicurrency_rounding',
				'rounding_precision' => 'wcj_multicurrency_rounding_precision',
				'revert'             => 'wcj_multicurrency_revert',
				'switcher_template'  => 'wcj_multicurrency_switcher_template',
			);
			foreach ( $direct as $dk => $ok ) {
				if ( isset( $map[ $dk ] ) ) {
					$writes[ $ok ] = $draft[ $dk ];
				}
			}
			if ( isset( $map['per_product'] ) ) {
				$writes['wcj_multicurrency_per_product_enabled'] = ! empty( $draft['per_product'] ) ? 'yes' : 'no';
			}

			if ( $is_pro ) {
				if ( isset( $map['rate_update_mode'] ) ) {
					$writes['wcj_multicurrency_exchange_rate_update_auto'] = $draft['rate_update_mode'];
				}
				if ( isset( $map['convert_shipping'] ) ) {
					$writes['wcj_multicurrency_convert_shipping_values'] = ! empty( $draft['convert_shipping'] ) ? 'yes' : 'no';
				}
				if ( isset( $map['compat_free_ship'] ) ) {
					$writes['wcj_multicurrency_compatibility_free_shipping'] = ! empty( $draft['compat_free_ship'] ) ? 'yes' : 'no';
				}
				if ( isset( $map['compat_coupons'] ) ) {
					$writes['wcj_multicurrency_compatibility_wc_coupons'] = ! empty( $draft['compat_coupons'] ) ? 'yes' : 'no';
				}
			}

			return $writes;
		}

		/**
		 * Emergency shutoff immediately reverts storefront to WooCommerce base currency.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== (string) self::snap_val( $snapshot, 'wcj_multicurrency_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_multicurrency_enabled' => 'no',
			);
		}
	}

endif;
