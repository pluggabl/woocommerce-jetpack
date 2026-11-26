<?php
/**
 * Booster True V10 - Guided Editor: Product Addons.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Product_Addons' ) ) :

	/**
	 * Product Addons guided editor supporting interactive real-card pricing, EU-safe checkbox defaults & Free caps.
	 */
	class WCJ_V10_Editor_Product_Addons extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'product_addons';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/product-addons.js';
		}

		/**
		 * Context keys accepted by this editor.
		 *
		 * @return array<int, string>
		 */
		public static function context_keys() {
			return array( 'addon_index' );
		}

		/**
		 * Primary Classic module section slug.
		 *
		 * @return string
		 */
		public static function primary_classic_section() {
			return 'product_addons';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'product_addons' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Product Addons', 'woocommerce-jetpack' ),
				'description' => __( 'Add optional gift wrap, rush handling, or product extras with live total updates.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Interactive Product Card', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'product_addons' => self::slug(),
			);
		}

		/**
		 * Resolve safe global addon index (always 1 in Free; 1..total_number in Paid).
		 *
		 * @param array $context Editor context.
		 * @return int
		 */
		private static function resolve_index( $context = array() ) {
			if ( ! self::is_pro() ) {
				return 1;
			}
			$total = max( 1, (int) get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			$raw   = isset( $context['addon_index'] ) ? absint( $context['addon_index'] ) : 1;
			return max( 1, min( $total, $raw ) );
		}

		/**
		 * Authoritative list of wp_options keys read/written by this editor.
		 *
		 * @param array $context Editor context (addon_index).
		 * @return array<int, string>
		 */
		public static function owned_options( $context = array() ) {
			$i = self::resolve_index( $context );
			return array(
				'wcj_product_addons_enabled',
				'wcj_product_addons_all_products_enabled',
				'wcj_product_addons_all_products_enabled_' . $i,
				'wcj_product_addons_all_products_type_' . $i,
				'wcj_product_addons_all_products_title_' . $i,
				'wcj_product_addons_all_products_label_' . $i,
				'wcj_product_addons_all_products_price_' . $i,
				'wcj_product_addons_all_products_required_' . $i,
				'wcj_product_addons_all_products_tooltip_' . $i,
				'wcj_product_addons_all_products_default_' . $i,
				'wcj_product_addons_all_products_placeholder_' . $i,
				'wcj_product_addons_all_products_qty_' . $i,
				'wcj_product_addons_position',
				'wcj_product_addons_ajax_enabled',
				'wcj_product_addons_per_product_enabled',
			);
		}

		/**
		 * Stock counter option mutates during order placement; exclude from base_hashes stale checks.
		 *
		 * @param array $context Editor context.
		 * @return array<int, string>
		 */
		public static function volatile_options( $context = array() ) {
			$i = self::resolve_index( $context );
			return array(
				'wcj_product_addons_all_products_qty_' . $i,
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'enabled'             => __( 'Product Addons Module', 'woocommerce-jetpack' ),
				'all_enabled'         => __( 'Enable Store-Wide Addons', 'woocommerce-jetpack' ),
				'addon_enabled'       => __( 'Global Addon Active', 'woocommerce-jetpack' ),
				'addon_title'         => __( 'Addon Heading Title', 'woocommerce-jetpack' ),
				'addon_type'          => __( 'Addon Control Type', 'woocommerce-jetpack' ),
				'addon_label'         => __( 'Option Label(s)', 'woocommerce-jetpack' ),
				'addon_price'         => __( 'Option Price(s)', 'woocommerce-jetpack' ),
				'addon_required'      => __( 'Require Selection Before Add to Cart', 'woocommerce-jetpack' ),
				'addon_tooltip'       => __( 'Tooltip Text', 'woocommerce-jetpack' ),
				'addon_default'       => __( 'Default Selected Option', 'woocommerce-jetpack' ),
				'addon_placeholder'   => __( 'Input Placeholder', 'woocommerce-jetpack' ),
				'position'            => __( 'Product Page Position', 'woocommerce-jetpack' ),
				'ajax_enabled'        => __( 'Live Ajax Price Recalculation', 'woocommerce-jetpack' ),
				'per_product_enabled' => __( 'Also Allow Per-Product Addons', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live?: bool, stored_on_orders?: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'enabled'             => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Renders product add-ons on product pages and stores chosen add-on line items on orders.', 'woocommerce-jetpack' ),
				),
				'all_enabled'         => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Turns store-wide global product add-ons on across your catalog.', 'woocommerce-jetpack' ),
				),
				'addon_enabled'       => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Activates this add-on on every published product.', 'woocommerce-jetpack' ),
				),
				'addon_title'         => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Heading shown above the add-on choice on product pages and order items.', 'woocommerce-jetpack' ),
				),
				'addon_label'         => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Option text chosen by shoppers and stored on order line items.', 'woocommerce-jetpack' ),
				),
				'addon_price'         => array(
					'goes_live'        => true,
					'stored_on_orders' => true,
					'impact'           => __( 'Amount added to the cart & checkout item price when selected.', 'woocommerce-jetpack' ),
				),
				'addon_required'      => array(
					'goes_live' => true,
					'impact'    => __( 'Requires selection before purchasing and converts shop quick Add to Cart buttons into option selection.', 'woocommerce-jetpack' ),
				),
				'addon_type'          => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the product page control between checkbox, radio, dropdown, or text input.', 'woocommerce-jetpack' ),
				),
				'addon_tooltip'       => array(
					'goes_live' => false,
					'impact'    => __( 'Displays helper tooltip copy beside the add-on.', 'woocommerce-jetpack' ),
				),
				'addon_default'       => array(
					'goes_live' => false,
					'impact'    => __( 'Pre-selects a default option on non-checkbox free options.', 'woocommerce-jetpack' ),
				),
				'addon_placeholder'   => array(
					'goes_live' => false,
					'impact'    => __( 'Shows placeholder copy inside Text add-ons.', 'woocommerce-jetpack' ),
				),
				'position'            => array(
					'goes_live' => false,
					'impact'    => __( 'Places add-ons above or below the Add to Cart button.', 'woocommerce-jetpack' ),
				),
				'ajax_enabled'        => array(
					'goes_live' => false,
					'impact'    => __( 'Updates displayed product page price dynamically when shoppers pick options.', 'woocommerce-jetpack' ),
				),
				'per_product_enabled' => array(
					'goes_live' => false,
					'impact'    => __( 'Enables per-product add-on meta boxes on individual product screens.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Split non-empty lines safely.
		 *
		 * @param mixed $raw Multiline string.
		 * @return array<int, string>
		 */
		private static function lines( $raw ) {
			return array_values(
				array_filter(
					array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ),
					static function ( $line ) {
						return '' !== $line;
					}
				)
			);
		}

		/**
		 * Compute numeric extra price from raw fixed or N% expression.
		 *
		 * @param string $price_str Raw price expression.
		 * @param float  $base      Product base price.
		 * @return float
		 */
		private static function eval_price_line( $price_str, $base ) {
			$trim = trim( (string) $price_str );
			if ( '' === $trim ) {
				return 0.0;
			}
			if ( '%' === substr( $trim, -1 ) ) {
				$pct = (float) substr( $trim, 0, -1 );
				return max( 0.0, $base * ( $pct / 100.0 ) );
			}
			return max( 0.0, (float) $trim );
		}

		/**
		 * Detect Classic-only rules, per-product counts, and extra global addons #2..#N.
		 *
		 * @param int $active_idx Active addon index.
		 * @return array{extra_addons: int, per_product_count: int, classic_rules: int}
		 */
		private static function detect_hidden( $active_idx ) {
			global $wpdb;
			$total = max( 1, (int) get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			$extra = 0;
			$limit = max( $total, 4 );
			for ( $n = 2; $n <= $limit; $n++ ) {
				if ( $n === $active_idx ) {
					continue;
				}
				if ( '' !== trim( (string) get_option( 'wcj_product_addons_all_products_label_' . $n, '' ) ) || '' !== trim( (string) get_option( 'wcj_product_addons_all_products_title_' . $n, '' ) ) ) {
					++$extra;
				}
			}

			$per_prod = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_wcj_product_addons_per_product_settings_enabled' AND meta_value = 'yes'"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$rules = 0;
			if ( ! empty( get_option( 'wcj_product_addons_all_products_exclude_products_' . $active_idx, array() ) ) ) {
				++$rules;
			}
			if ( '' !== (string) get_option( 'wcj_product_addons_all_products_qty_' . $active_idx, '' ) ) {
				++$rules;
			}
			if ( 'by_module' !== get_option( 'wcj_product_addons_apply_price_filters', 'by_module' ) ) {
				++$rules;
			}

			return array(
				'extra_addons'      => $extra,
				'per_product_count' => $per_prod,
				'classic_rules'     => $rules,
			);
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params (addon_index).
		 * @param array $store_ctx Store context.
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			$nonce      = wp_create_nonce( 'wcj-cat-nonce' );
			$is_pro     = self::is_pro();
			$i          = self::resolve_index( $context );
			$total_num  = max( 1, (int) get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
			$hidden     = self::detect_hidden( $i );
			$counts     = isset( $store_ctx['product_counts'] ) ? $store_ctx['product_counts'] : array( 'published' => 0, 'downloadable' => 0 );
			$cur_type   = (string) get_option( 'wcj_product_addons_all_products_type_' . $i, 'checkbox' );
			if ( '' === $cur_type ) {
				$cur_type = 'checkbox';
			}

			$addons_nav = array();
			$max_nav    = $is_pro ? $total_num : 1;
			for ( $n = 1; $n <= $max_nav; $n++ ) {
				$t            = trim( (string) get_option( 'wcj_product_addons_all_products_title_' . $n, '' ) );
				$l            = self::lines( get_option( 'wcj_product_addons_all_products_label_' . $n, '' ) );
				$addons_nav[] = array(
					'index' => $n,
					'label' => '' !== $t ? $t : ( ! empty( $l[0] ) ? $l[0] : sprintf( __( 'Add-on #%d', 'woocommerce-jetpack' ), $n ) ),
				);
			}

			$diagnostics   = array();
			$published_cnt = (int) ( $counts['published'] ?? 0 );
			$dl_cnt        = (int) ( $counts['downloadable'] ?? 0 );
			$diagnostics[] = array(
				'level' => 'info',
				'text'  => $dl_cnt > 0
					? sprintf(
						/* translators: 1: Total published products, 2: Downloadable/virtual products */
						__( 'Global add-ons appear on all %1$d published products (including %2$d downloadable/virtual products).', 'woocommerce-jetpack' ),
						$published_cnt,
						$dl_cnt
					)
					: sprintf(
						/* translators: %d: Total published products */
						__( 'Global add-ons appear on all %d published products.', 'woocommerce-jetpack' ),
						$published_cnt
					),
			);

			if ( $hidden['extra_addons'] > 0 && ! $is_pro ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => sprintf(
						/* translators: %d: Count of extra global add-ons saved in DB */
						__( '%d extra store-wide add-on(s) from Classic/Elite are saved and preserved untouched (Booster Free runs Global Add-on #1).', 'woocommerce-jetpack' ),
						$hidden['extra_addons']
					),
				);
			}
			if ( $hidden['per_product_count'] > 0 || $hidden['classic_rules'] > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: 1: Count of products with per-product addons, 2: Classic rules count */
						__( '%1$d product(s) have per-product add-ons and %2$d Classic rule(s) (stock cap/exclusions/templates) apply; all preserved untouched.', 'woocommerce-jetpack' ),
						$hidden['per_product_count'],
						$hidden['classic_rules']
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=products&section=product_addons&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			$cur_default = (string) get_option( 'wcj_product_addons_all_products_default_' . $i, '' );
			$cur_price   = (string) get_option( 'wcj_product_addons_all_products_price_' . $i, '0' );
			if ( 'checkbox' === $cur_type && 'checked' === strtolower( trim( $cur_default ) ) && (float) str_replace( '%', '', $cur_price ) > 0.0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => __( 'This paid checkbox add-on is currently pre-ticked by default from Classic settings. Pre-ticked paid add-ons are preserved until edited, but clearing Default Value in Classic avoids unexpected charges.', 'woocommerce-jetpack' ),
					'action_label' => __( 'Edit Default in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=products&section=product_addons&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => WCJ_V10_Store::edition(),
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=products&section=product_addons&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'addon_index'         => $i,
					'enabled'             => 'yes' === get_option( 'wcj_product_addons_enabled', 'no' ),
					'all_enabled'         => 'yes' === get_option( 'wcj_product_addons_all_products_enabled', 'no' ),
					'addon_enabled'       => 'yes' === get_option( 'wcj_product_addons_all_products_enabled_' . $i, 'yes' ),
					'addon_title'         => (string) get_option( 'wcj_product_addons_all_products_title_' . $i, '' ),
					'addon_type'          => in_array( $cur_type, array( 'checkbox', 'radio', 'select', 'text' ), true ) ? $cur_type : 'checkbox',
					'addon_label'         => (string) get_option( 'wcj_product_addons_all_products_label_' . $i, '' ),
					'addon_price'         => (string) get_option( 'wcj_product_addons_all_products_price_' . $i, '0' ),
					'addon_required'      => 'yes' === get_option( 'wcj_product_addons_all_products_required_' . $i, 'no' ),
					'addon_tooltip'       => (string) get_option( 'wcj_product_addons_all_products_tooltip_' . $i, '' ),
					'addon_default'       => (string) get_option( 'wcj_product_addons_all_products_default_' . $i, '' ),
					'addon_placeholder'   => (string) get_option( 'wcj_product_addons_all_products_placeholder_' . $i, '' ),
					'position'            => (string) get_option( 'wcj_product_addons_position', 'woocommerce_before_add_to_cart_button' ),
					'ajax_enabled'        => 'yes' === get_option( 'wcj_product_addons_ajax_enabled', 'no' ),
					'per_product_enabled' => 'yes' === get_option( 'wcj_product_addons_per_product_enabled', 'no' ),
				),
				'meta'        => array(
					'is_pro'          => $is_pro,
					'addons_nav'      => $addons_nav,
					'sample_product'  => $store_ctx['product'],
					'currency_symbol' => $store_ctx['currency_symbol'],
					'decimals'        => $store_ctx['decimals'],
					'verify_url'      => $store_ctx['product']['permalink'],
					'verify_label'    => __( 'Check Add-on on Live Product', 'woocommerce-jetpack' ),
					'diagnostics'     => $diagnostics,
				),
			);
		}

		/**
		 * Sanitize draft values while blocking paid pre-ticked checkbox trap cleanly.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$idx  = self::resolve_index( $draft );
			$type = array_key_exists( 'addon_type', $draft ) ? sanitize_key( (string) $draft['addon_type'] ) : $live['addon_type'];
			if ( ! in_array( $type, array( 'checkbox', 'radio', 'select', 'text' ), true ) ) {
				$type = 'checkbox';
			}

			$str = static function ( $k, $textarea = false ) use ( $draft, $live ) {
				if ( ! array_key_exists( $k, $draft ) || (string) $draft[ $k ] === (string) $live[ $k ] ) {
					return $live[ $k ];
				}
				return $textarea ? sanitize_textarea_field( (string) $draft[ $k ] ) : sanitize_text_field( (string) $draft[ $k ] );
			};

			$label = $str( 'addon_label', true );
			$price = $str( 'addon_price', true );
			$def   = $str( 'addon_default' );

			// Guard: only block saving new 'checked' default on checkbox addons so live Classic settings never create phantom diffs.
			if ( 'checkbox' === $type && $def !== $live['addon_default'] && 'checked' === strtolower( trim( $def ) ) ) {
				$def = '';
			}

			$pos = array_key_exists( 'position', $draft ) ? sanitize_key( (string) $draft['position'] ) : $live['position'];
			if ( ! in_array( $pos, array( 'woocommerce_before_add_to_cart_button', 'woocommerce_after_add_to_cart_button' ), true ) ) {
				$pos = 'woocommerce_before_add_to_cart_button';
			}

			return array(
				'addon_index'         => $idx,
				'enabled'             => array_key_exists( 'enabled', $draft ) ? ! empty( $draft['enabled'] ) : ! empty( $live['enabled'] ),
				'all_enabled'         => array_key_exists( 'all_enabled', $draft ) ? ! empty( $draft['all_enabled'] ) : ! empty( $live['all_enabled'] ),
				'addon_enabled'       => array_key_exists( 'addon_enabled', $draft ) ? ! empty( $draft['addon_enabled'] ) : ! empty( $live['addon_enabled'] ),
				'addon_title'         => $str( 'addon_title' ),
				'addon_type'          => $type,
				'addon_label'         => $label,
				'addon_price'         => $price,
				'addon_required'      => array_key_exists( 'addon_required', $draft ) ? ! empty( $draft['addon_required'] ) : ! empty( $live['addon_required'] ),
				'addon_tooltip'       => $str( 'addon_tooltip' ),
				'addon_default'       => $def,
				'addon_placeholder'   => $str( 'addon_placeholder' ),
				'position'            => $pos,
				'ajax_enabled'        => array_key_exists( 'ajax_enabled', $draft ) ? ! empty( $draft['ajax_enabled'] ) : ! empty( $live['ajax_enabled'] ),
				'per_product_enabled' => array_key_exists( 'per_product_enabled', $draft ) ? ! empty( $draft['per_product_enabled'] ) : ! empty( $live['per_product_enabled'] ),
			);
		}

		/**
		 * Validate label presence, integer percentage pricing, and radio/select line count parity.
		 *
		 * @param array $draft        Sanitized draft.
		 * @param array $live         Live state.
		 * @param array $changed_keys Changed diff keys.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft, $live = array(), $changed_keys = array() ) {
			unset( $live, $changed_keys );
			if ( empty( $draft['enabled'] ) || empty( $draft['all_enabled'] ) || empty( $draft['addon_enabled'] ) ) {
				return true;
			}
			$labels = self::lines( $draft['addon_label'] );
			$prices = self::lines( $draft['addon_price'] );
			if ( empty( $labels ) ) {
				return new WP_Error(
					'wcj_v10_missing_addon_label',
					__( 'Enter an Option Label (for example "Gift Wrapping") before publishing.', 'woocommerce-jetpack' ),
					array( 'status' => 400 )
				);
			}
			foreach ( $prices as $pl ) {
				if ( false !== strpos( $pl, '%' ) && ! preg_match( '/^\d+%$/', trim( $pl ) ) ) {
					return new WP_Error(
						'wcj_v10_addon_pct_decimal',
						__( 'Percentage add-on prices must use whole numbers (for example "5%" or "10%") to match Booster pricing rules.', 'woocommerce-jetpack' ),
						array( 'status' => 400 )
					);
				}
			}
			if ( in_array( $draft['addon_type'], array( 'radio', 'select' ), true ) && count( $labels ) !== count( $prices ) ) {
				return new WP_Error(
					'wcj_v10_addon_count_mismatch',
					sprintf(
						/* translators: 1: Label lines count, 2: Price lines count */
						__( 'Radio/Select add-ons require an equal number of Label lines (%1$d) and Price lines (%2$d) — Booster skips choices when they do not match.', 'woocommerce-jetpack' ),
						count( $labels ),
						count( $prices )
					),
					array( 'status' => 400 )
				);
			}
			return true;
		}

		/**
		 * Project Before & After product card + dynamic cart item price with selected add-on.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview parameters (addon_selected_idx).
		 * @param array $meta    Metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			$prod     = isset( $meta['sample_product'] ) && is_array( $meta['sample_product'] ) ? $meta['sample_product'] : array();
			$base_num = ! empty( $prod['price_num'] ) ? (float) $prod['price_num'] : 76.0;
			$decimals = isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 2;
			$sel_idx  = isset( $context['addon_selected_idx'] ) ? (int) $context['addon_selected_idx'] : 0;

			$project = static function ( $cfg, $is_after ) use ( $base_num, $decimals, $sel_idx ) {
				$active  = ! empty( $cfg['enabled'] ) && ! empty( $cfg['all_enabled'] ) && ! empty( $cfg['addon_enabled'] );
				$labels  = self::lines( $cfg['addon_label'] );
				$prices  = self::lines( $cfg['addon_price'] );
				$def_chk = ( 'checkbox' === $cfg['addon_type'] && 'checked' === strtolower( trim( (string) ( $cfg['addon_default'] ?? '' ) ) ) );
				$options = array();
				foreach ( $labels as $idx => $lbl ) {
					$raw_p     = isset( $prices[ $idx ] ) ? $prices[ $idx ] : ( $prices[0] ?? '0' );
					$extra     = self::eval_price_line( $raw_p, $base_num );
					$options[] = array(
						'index'  => $idx,
						'label'  => $lbl,
						'extra'  => round( $extra, $decimals ),
						'is_pct' => '%' === substr( trim( $raw_p ), -1 ),
					);
				}

				$chosen_idx   = $is_after ? $sel_idx : ( 'checkbox' === $cfg['addon_type'] ? ( $def_chk ? 0 : -1 ) : 0 );
				$chosen_extra = 0.0;
				if ( $active && $chosen_idx >= 0 && isset( $options[ $chosen_idx ] ) ) {
					$chosen_extra = (float) $options[ $chosen_idx ]['extra'];
				}

				return array(
					'active'          => $active,
					'title'           => $cfg['addon_title'],
					'type'            => $cfg['addon_type'],
					'required'        => ! empty( $cfg['addon_required'] ),
					'default_checked' => $def_chk,
					'selected_idx'    => $chosen_idx,
					'options'         => $options,
					'base_price'      => round( $base_num, $decimals ),
					'addon_fee'       => round( $chosen_extra, $decimals ),
					'cart_total'      => round( $base_num + $chosen_extra, $decimals ),
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
			$i       = self::resolve_index( $draft );
			$writes  = array();
			$map     = array(
				'enabled'             => array( 'wcj_product_addons_enabled', ! empty( $draft['enabled'] ) ? 'yes' : 'no' ),
				'all_enabled'         => array( 'wcj_product_addons_all_products_enabled', ! empty( $draft['all_enabled'] ) ? 'yes' : 'no' ),
				'addon_enabled'       => array( 'wcj_product_addons_all_products_enabled_' . $i, ! empty( $draft['addon_enabled'] ) ? 'yes' : 'no' ),
				'addon_title'         => array( 'wcj_product_addons_all_products_title_' . $i, $draft['addon_title'] ),
				'addon_type'          => array( 'wcj_product_addons_all_products_type_' . $i, $draft['addon_type'] ),
				'addon_label'         => array( 'wcj_product_addons_all_products_label_' . $i, $draft['addon_label'] ),
				'addon_price'         => array( 'wcj_product_addons_all_products_price_' . $i, $draft['addon_price'] ),
				'addon_required'      => array( 'wcj_product_addons_all_products_required_' . $i, ! empty( $draft['addon_required'] ) ? 'yes' : 'no' ),
				'addon_tooltip'       => array( 'wcj_product_addons_all_products_tooltip_' . $i, $draft['addon_tooltip'] ),
				'addon_default'       => array( 'wcj_product_addons_all_products_default_' . $i, $draft['addon_default'] ),
				'addon_placeholder'   => array( 'wcj_product_addons_all_products_placeholder_' . $i, $draft['addon_placeholder'] ),
				'position'            => array( 'wcj_product_addons_position', $draft['position'] ),
				'ajax_enabled'        => array( 'wcj_product_addons_ajax_enabled', ! empty( $draft['ajax_enabled'] ) ? 'yes' : 'no' ),
				'per_product_enabled' => array( 'wcj_product_addons_per_product_enabled', ! empty( $draft['per_product_enabled'] ) ? 'yes' : 'no' ),
			);

			foreach ( $map as $field => $pair ) {
				if ( isset( $changed[ $field ] ) ) {
					$writes[ $pair[0] ] = $pair[1];
				}
			}

			// Ensure type option exists when activating an addon on a fresh store so runtime matches preview.
			if ( ! empty( $draft['enabled'] ) && ! empty( $draft['all_enabled'] ) && ! array_key_exists( 'wcj_product_addons_all_products_type_' . $i, $writes ) && empty( $snapshot[ 'wcj_product_addons_all_products_type_' . $i ]['exists'] ) ) {
				$writes[ 'wcj_product_addons_all_products_type_' . $i ] = $draft['addon_type'];
			}

			return $writes;
		}

		/**
		 * Emergency shutoff option writes.
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			if ( 'yes' !== self::snap_val( $snapshot, 'wcj_product_addons_enabled', 'no' ) ) {
				return array();
			}
			return array(
				'wcj_product_addons_enabled' => 'no',
			);
		}
	}

endif;
