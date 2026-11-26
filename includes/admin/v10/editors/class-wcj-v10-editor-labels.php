<?php
/**
 * Booster True V10 - Guided Editor: Button, Price & Sale Labels.
 *
 * Unifies: add_to_cart, price_labels, sale_flash, more_button_labels.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_Editor_Labels' ) ) :

	/**
	 * Unified Labels guided editor supporting Simple + Variable/Grouped/External buttons, price overrides, and sale badges.
	 */
	class WCJ_V10_Editor_Labels extends WCJ_V10_Editor_Base {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'button_price_labels';
		}

		/**
		 * Relative JS module path.
		 *
		 * @return string
		 */
		public static function script() {
			return '/assets/js/admin/v10-editors/labels.js';
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
			return 'add_to_cart';
		}

		/**
		 * Required Booster module IDs for authorization.
		 *
		 * @return array<int, string>
		 */
		public static function required_module_ids() {
			return array( 'add_to_cart', 'more_button_labels', 'price_labels', 'sale_flash' );
		}

		/**
		 * Summary card metadata for Command Center.
		 *
		 * @return array<string, string>
		 */
		public static function summary() {
			return array(
				'slug'        => self::slug(),
				'title'       => __( 'Button, Price & Sale Labels', 'woocommerce-jetpack' ),
				'description' => __( 'Customize Add to cart, Place order, price affixes, and sale badges with a live card preview.', 'woocommerce-jetpack' ),
				'engine'      => __( 'Storefront Card', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Classic section IDs handled by this guided editor.
		 *
		 * @return array<string, string>
		 */
		public static function section_map() {
			return array(
				'add_to_cart'        => self::slug(),
				'more_button_labels' => self::slug(),
				'price_labels'       => self::slug(),
				'sale_flash'         => self::slug(),
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
				'wcj_add_to_cart_enabled',
				'wcj_add_to_cart_text_enabled',
				'wcj_add_to_cart_text_on_single_simple',
				'wcj_add_to_cart_text_on_single_variable',
				'wcj_add_to_cart_text_on_single_grouped',
				'wcj_add_to_cart_text_on_single_external',
				'wcj_add_to_cart_text_on_archives_simple',
				'wcj_add_to_cart_text_on_archives_variable',
				'wcj_add_to_cart_text_on_archives_grouped',
				'wcj_add_to_cart_text_on_archives_external',
				'wcj_more_button_labels_enabled',
				'wcj_checkout_place_order_button_text',
				'wcj_checkout_place_order_button_override',
				'wcj_price_labels_enabled',
				'wcj_global_price_labels_add_before_text',
				'wcj_global_price_labels_add_after_text',
				'wcj_global_price_labels_instead_text',
				'wcj_sale_flash_enabled',
				'wcj_product_images_sale_flash_enabled',
				'wcj_product_images_sale_flash_html',
				'wcj_product_images_sale_flash_hide_everywhere',
			);
		}

		/**
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'add_to_cart_enabled'        => __( 'Add to Cart Button Labels (Add to Cart Module)', 'woocommerce-jetpack' ),
				'add_to_cart_single'         => __( 'Single Product Button Text', 'woocommerce-jetpack' ),
				'add_to_cart_archive'        => __( 'Shop/Archive Button Text', 'woocommerce-jetpack' ),
				'apply_to_variable'          => __( 'Apply Button Text to Variable, Grouped & External Products', 'woocommerce-jetpack' ),
				'more_button_enabled'        => __( 'Place Order Button Labels (More Button Labels Module)', 'woocommerce-jetpack' ),
				'place_order_text'           => __( 'Place Order Button Text', 'woocommerce-jetpack' ),
				'place_order_override'       => __( 'Force Override Gateway Place Order Text', 'woocommerce-jetpack' ),
				'price_labels_enabled'       => __( 'Custom Price Labels Module', 'woocommerce-jetpack' ),
				'price_prefix'               => __( 'Text Before Price', 'woocommerce-jetpack' ),
				'price_suffix'               => __( 'Text After Price', 'woocommerce-jetpack' ),
				'price_instead'              => __( 'Instead of Price Text', 'woocommerce-jetpack' ),
				'sale_flash_enabled'         => __( 'Global Sale Badge Override (Sale Flash Module)', 'woocommerce-jetpack' ),
				'sale_badge_text'            => __( 'Sale Badge Text', 'woocommerce-jetpack' ),
				'sale_flash_hide_everywhere' => __( 'Hide Sale Badge Everywhere', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Customer-facing impact grouping metadata for Review & Publish modal.
		 *
		 * @return array<string, array{goes_live: bool, impact: string}>
		 */
		public static function impact_groups() {
			return array(
				'add_to_cart_enabled'        => array(
					'goes_live' => true,
					'impact'    => __( 'Updates product & shop Add to Cart button labels visible to all shoppers.', 'woocommerce-jetpack' ),
				),
				'add_to_cart_single'         => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the Add to Cart button wording on single product pages.', 'woocommerce-jetpack' ),
				),
				'add_to_cart_archive'        => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the Add to Cart button wording on shop & category grids.', 'woocommerce-jetpack' ),
				),
				'more_button_enabled'        => array(
					'goes_live' => true,
					'impact'    => __( 'Enables custom checkout Place Order button wording for shoppers.', 'woocommerce-jetpack' ),
				),
				'place_order_text'           => array(
					'goes_live' => true,
					'impact'    => __( 'Changes the final Place Order button label on the checkout page.', 'woocommerce-jetpack' ),
				),
				'price_labels_enabled'       => array(
					'goes_live' => true,
					'impact'    => __( 'Enables storefront price prefix/suffix/override labels on product prices.', 'woocommerce-jetpack' ),
				),
				'price_prefix'               => array(
					'goes_live' => true,
					'impact'    => __( 'Adds text directly before every product price.', 'woocommerce-jetpack' ),
				),
				'price_suffix'               => array(
					'goes_live' => true,
					'impact'    => __( 'Adds text directly after every product price (for example incl. VAT).', 'woocommerce-jetpack' ),
				),
				'price_instead'              => array(
					'goes_live' => true,
					'impact'    => __( 'Replaces product price display text across the storefront.', 'woocommerce-jetpack' ),
				),
				'sale_flash_enabled'         => array(
					'goes_live' => true,
					'impact'    => __( 'Enables global Sale badge text/visibility override across your store.', 'woocommerce-jetpack' ),
				),
				'sale_badge_text'            => array(
					'goes_live' => true,
					'impact'    => __( 'Updates the Sale badge label on discounted products.', 'woocommerce-jetpack' ),
				),
				'sale_flash_hide_everywhere' => array(
					'goes_live' => true,
					'impact'    => __( 'Hides Sale badges across your entire storefront.', 'woocommerce-jetpack' ),
				),
				'apply_to_variable'          => array(
					'goes_live' => false,
					'impact'    => __( 'Mirrors your button wording to Variable, Grouped, and External products too.', 'woocommerce-jetpack' ),
				),
				'place_order_override'       => array(
					'goes_live' => false,
					'impact'    => __( 'Ensures custom Place Order text overrides payment gateway defaults.', 'woocommerce-jetpack' ),
				),
			);
		}

		/**
		 * Extract plain sale badge label from standard WooCommerce/Booster span markup.
		 *
		 * @param string $raw_html Stored sale flash HTML.
		 * @return string
		 */
		public static function extract_sale_badge_text( $raw_html ) {
			$raw = trim( (string) $raw_html );
			if ( '' === $raw ) {
				return __( 'Sale!', 'woocommerce' );
			}
			if ( preg_match( '/^\s*<span\s+[^>]*>(.*?)<\/span>\s*$/is', $raw, $matches ) ) {
				return html_entity_decode( wp_strip_all_tags( $matches[1] ), ENT_QUOTES, 'UTF-8' );
			}
			return html_entity_decode( wp_strip_all_tags( $raw ), ENT_QUOTES, 'UTF-8' );
		}

		/**
		 * Update sale badge text inside existing span markup cleanly, preserving custom classes/attributes when possible.
		 *
		 * @param string $existing_html Current raw HTML.
		 * @param string $new_text      New badge text.
		 * @return string
		 */
		private static function rebuild_sale_html( $existing_html, $new_text ) {
			$escaped = esc_html( $new_text );
			$raw     = trim( (string) $existing_html );
			if ( '' !== $raw && preg_match( '/^(\s*<span\s+[^>]*>)(.*?)(<\/span>\s*)$/is', $raw, $m ) ) {
				return $m[1] . $escaped . $m[3];
			}
			return '<span class="onsale">' . $escaped . '</span>';
		}

		/**
		 * Detect active Classic-only label rules not shown in Simple Setup.
		 *
		 * @return int
		 */
		private static function count_classic_only_rules() {
			$count = 0;
			if ( 'yes' === get_option( 'wcj_add_to_cart_enabled', 'no' ) ) {
				if ( 'yes' !== get_option( 'wcj_add_to_cart_text_enabled', 'no' ) ) {
					++$count;
				}
				if ( 'yes' === get_option( 'wcj_add_to_cart_per_category_enabled', 'no' ) || 'yes' === get_option( 'wcj_add_to_cart_per_product_enabled', 'no' ) ) {
					++$count;
				}
			}
			if ( 'yes' === get_option( 'wcj_sale_flash_enabled', 'no' ) ) {
				if ( 'yes' !== get_option( 'wcj_product_images_sale_flash_enabled', 'no' ) ) {
					++$count;
				}
				if ( 'yes' === get_option( 'wcj_product_images_sale_flash_per_product_enabled', 'no' )
					|| 'yes' === get_option( 'wcj_product_images_sale_flash_per_category_enabled', 'no' )
					|| 'yes' === get_option( 'wcj_product_images_sale_flash_per_tag_enabled', 'no' )
				) {
					++$count;
				}
			}
			if ( 'yes' === get_option( 'wcj_price_labels_enabled', 'no' ) && 'yes' === get_option( 'wcj_local_price_labels_enabled', 'yes' ) ) {
				++$count;
			}
			return $count;
		}

		/**
		 * Load live state, metadata, and compatibility diagnostics.
		 *
		 * @param array $context   Request params.
		 * @param array $store_ctx Store context from WCJ_V10_REST::get_store_sample_data().
		 * @return array<string, mixed>
		 */
		public static function load_state( $context, $store_ctx ) {
			unset( $context );
			$edition        = WCJ_V10_Store::edition();
			$nonce          = wp_create_nonce( 'wcj-cat-nonce' );
			$sample_product = $store_ctx['product'];
			$raw_sale_html  = (string) get_option( 'wcj_product_images_sale_flash_html', '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>' );
			$sale_text      = self::extract_sale_badge_text( $raw_sale_html );
			$single_simple  = (string) get_option( 'wcj_add_to_cart_text_on_single_simple', __( 'Add to cart', 'woocommerce-jetpack' ) );
			$single_var     = get_option( 'wcj_add_to_cart_text_on_single_variable', false );
			$apply_var      = ( false === $single_var || '' === $single_var || $single_var === $single_simple );

			$diagnostics   = array();
			$classic_rules = self::count_classic_only_rules();
			if ( $classic_rules > 0 ) {
				$diagnostics[] = array(
					'level'        => 'warning',
					'text'         => sprintf(
						/* translators: %d: Number of active Classic-only label rules */
						__( '%d advanced Classic rule(s) (such as per-product, per-category, or redirect settings) are active and preserved untouched when you publish here.', 'woocommerce-jetpack' ),
						$classic_rules
					),
					'action_label' => __( 'View in Classic', 'woocommerce-jetpack' ),
					'action_url'   => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=add_to_cart&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				);
			}
			if ( empty( $sample_product['has_sale_products'] ) ) {
				$diagnostics[] = array(
					'level' => 'warning',
					'text'  => __( 'No products currently have a Sale Price set, so custom Sale Badge text will appear once a product has a sale price.', 'woocommerce-jetpack' ),
				);
			}
			if ( ! empty( $store_ctx['is_block_theme'] ) ) {
				$diagnostics[] = array(
					'level' => 'info',
					'text'  => __( 'Block theme active: custom price labels apply across product & shop pages; Single Button and Sale Badge overrides apply directly on product pages.', 'woocommerce-jetpack' ),
				);
			}

			return array(
				'slug'        => self::slug(),
				'edition'     => $edition,
				'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=add_to_cart&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
				'live'        => array(
					'add_to_cart_enabled'        => 'yes' === get_option( 'wcj_add_to_cart_enabled', 'no' ) && 'yes' === get_option( 'wcj_add_to_cart_text_enabled', 'no' ),
					'add_to_cart_single'         => $single_simple,
					'add_to_cart_archive'        => (string) get_option( 'wcj_add_to_cart_text_on_archives_simple', __( 'Add to cart', 'woocommerce-jetpack' ) ),
					'apply_to_variable'          => (bool) $apply_var,
					'more_button_enabled'        => 'yes' === get_option( 'wcj_more_button_labels_enabled', 'no' ),
					'place_order_text'           => (string) get_option( 'wcj_checkout_place_order_button_text', '' ),
					'place_order_override'       => 'yes' === get_option( 'wcj_checkout_place_order_button_override', 'no' ),
					'price_labels_enabled'       => 'yes' === get_option( 'wcj_price_labels_enabled', 'no' ),
					'price_prefix'               => (string) get_option( 'wcj_global_price_labels_add_before_text', '' ),
					'price_suffix'               => (string) get_option( 'wcj_global_price_labels_add_after_text', '' ),
					'price_instead'              => (string) get_option( 'wcj_global_price_labels_instead_text', '' ),
					'sale_flash_enabled'         => 'yes' === get_option( 'wcj_sale_flash_enabled', 'no' ) && 'yes' === get_option( 'wcj_product_images_sale_flash_enabled', 'no' ),
					'sale_badge_text'            => $sale_text,
					'sale_flash_html'            => $raw_sale_html,
					'sale_flash_hide_everywhere' => 'yes' === get_option( 'wcj_product_images_sale_flash_hide_everywhere', 'no' ),
				),
				'meta'        => array(
					'currency_symbol'     => $store_ctx['currency_symbol'],
					'price_prefix_locked' => ! self::is_pro(),
					'sample_product'      => $sample_product,
					'verify_url'          => $sample_product['permalink'],
					'verify_label'        => __( 'View on Live Product Page', 'woocommerce-jetpack' ),
					'store_defaults'      => $store_ctx['store_defaults'],
					'diagnostics'         => $diagnostics,
					'classic_sections'    => array(
						'add_to_cart'        => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=add_to_cart&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'price_labels'       => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=price_labels&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'sale_flash'         => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=products&section=sale_flash&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
						'more_button_labels' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=more_button_labels&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					),
				),
			);
		}

		/**
		 * Sanitize draft values while preserving untouched live values & custom sale HTML verbatim.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$sale_text = ( ! array_key_exists( 'sale_badge_text', $draft ) || (string) $draft['sale_badge_text'] === (string) $live['sale_badge_text'] )
				? $live['sale_badge_text']
				: sanitize_text_field( (string) $draft['sale_badge_text'] );

			if ( $sale_text === $live['sale_badge_text'] ) {
				$sale_html = $live['sale_flash_html'];
			} elseif ( '' !== trim( (string) $sale_text ) ) {
				$sale_html = self::rebuild_sale_html( $live['sale_flash_html'], $sale_text );
			} else {
				$sale_html = $live['sale_flash_html'];
				$sale_text = self::extract_sale_badge_text( $sale_html );
			}

			return array(
				'add_to_cart_enabled'        => array_key_exists( 'add_to_cart_enabled', $draft ) ? ! empty( $draft['add_to_cart_enabled'] ) : ! empty( $live['add_to_cart_enabled'] ),
				'add_to_cart_single'         => ( ! array_key_exists( 'add_to_cart_single', $draft ) || (string) $draft['add_to_cart_single'] === (string) $live['add_to_cart_single'] )
					? $live['add_to_cart_single']
					: sanitize_text_field( (string) $draft['add_to_cart_single'] ),
				'add_to_cart_archive'        => ( ! array_key_exists( 'add_to_cart_archive', $draft ) || (string) $draft['add_to_cart_archive'] === (string) $live['add_to_cart_archive'] )
					? $live['add_to_cart_archive']
					: sanitize_text_field( (string) $draft['add_to_cart_archive'] ),
				'apply_to_variable'          => array_key_exists( 'apply_to_variable', $draft ) ? ! empty( $draft['apply_to_variable'] ) : ! empty( $live['apply_to_variable'] ),
				'more_button_enabled'        => array_key_exists( 'more_button_enabled', $draft ) ? ! empty( $draft['more_button_enabled'] ) : ! empty( $live['more_button_enabled'] ),
				'place_order_text'           => ( ! array_key_exists( 'place_order_text', $draft ) || (string) $draft['place_order_text'] === (string) $live['place_order_text'] )
					? $live['place_order_text']
					: sanitize_text_field( (string) $draft['place_order_text'] ),
				'place_order_override'       => array_key_exists( 'place_order_override', $draft ) ? ! empty( $draft['place_order_override'] ) : ! empty( $live['place_order_override'] ),
				'price_labels_enabled'       => array_key_exists( 'price_labels_enabled', $draft ) ? ! empty( $draft['price_labels_enabled'] ) : ! empty( $live['price_labels_enabled'] ),
				'price_prefix'               => ( ! self::is_pro() || ! array_key_exists( 'price_prefix', $draft ) || (string) $draft['price_prefix'] === (string) $live['price_prefix'] )
					? $live['price_prefix']
					: wp_kses_post( (string) $draft['price_prefix'] ),
				'price_suffix'               => ( ! array_key_exists( 'price_suffix', $draft ) || (string) $draft['price_suffix'] === (string) $live['price_suffix'] )
					? $live['price_suffix']
					: wp_kses_post( (string) $draft['price_suffix'] ),
				'price_instead'              => ( ! array_key_exists( 'price_instead', $draft ) || (string) $draft['price_instead'] === (string) $live['price_instead'] )
					? $live['price_instead']
					: wp_kses_post( (string) $draft['price_instead'] ),
				'sale_flash_enabled'         => array_key_exists( 'sale_flash_enabled', $draft ) ? ! empty( $draft['sale_flash_enabled'] ) : ! empty( $live['sale_flash_enabled'] ),
				'sale_badge_text'            => $sale_text,
				'sale_flash_html'            => $sale_html,
				'sale_flash_hide_everywhere' => array_key_exists( 'sale_flash_hide_everywhere', $draft ) ? ! empty( $draft['sale_flash_hide_everywhere'] ) : ! empty( $live['sale_flash_hide_everywhere'] ),
			);
		}

		/**
		 * Validate draft before publishing.
		 *
		 * @param array $draft Sanitized draft.
		 * @return true|WP_Error
		 */
		public static function validate_publish( $draft ) {
			unset( $draft );
			return true;
		}

		/**
		 * Project Before & After storefront card previews.
		 *
		 * @param array $live    Live state.
		 * @param array $draft   Draft state.
		 * @param array $context Preview parameters.
		 * @param array $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		public static function compute_preview( $live, $draft, $context, $meta ) {
			unset( $context );
			$project = static function ( $cfg ) use ( $meta ) {
				$sym    = isset( $meta['currency_symbol'] ) ? $meta['currency_symbol'] : '$';
				$prod   = isset( $meta['sample_product'] ) && is_array( $meta['sample_product'] ) ? $meta['sample_product'] : array();
				$s_name = ! empty( $prod['name'] ) ? $prod['name'] : __( 'Sample Storefront Product', 'woocommerce-jetpack' );
				$s_img  = ! empty( $prod['image_url'] ) ? $prod['image_url'] : '';
				$s_reg  = ! empty( $prod['regular_price'] ) ? $prod['regular_price'] : $sym . '95.00';
				$s_sale = isset( $prod['sale_price'] ) ? $prod['sale_price'] : ( $sym . '76.00' );
				$s_on   = array_key_exists( 'on_sale', $prod ) ? ! empty( $prod['on_sale'] ) : true;
				$hide   = ! empty( $cfg['sale_flash_enabled'] ) && ! empty( $cfg['sale_flash_hide_everywhere'] );

				return array(
					'add_to_cart_enabled'        => ! empty( $cfg['add_to_cart_enabled'] ),
					'more_button_enabled'        => ! empty( $cfg['more_button_enabled'] ),
					'price_labels_enabled'       => ! empty( $cfg['price_labels_enabled'] ),
					'sale_flash_enabled'         => ! empty( $cfg['sale_flash_enabled'] ),
					'sale_flash_hide_everywhere' => $hide,
					'add_to_cart_text'           => ( ! empty( $cfg['add_to_cart_enabled'] ) && '' !== trim( $cfg['add_to_cart_single'] ) )
						? $cfg['add_to_cart_single']
						: __( 'Add to cart', 'woocommerce-jetpack' ),
					'archive_text'               => ( ! empty( $cfg['add_to_cart_enabled'] ) && '' !== trim( $cfg['add_to_cart_archive'] ) )
						? $cfg['add_to_cart_archive']
						: __( 'Add to cart', 'woocommerce-jetpack' ),
					'place_order_text'           => ( ! empty( $cfg['more_button_enabled'] ) && '' !== trim( $cfg['place_order_text'] ) )
						? $cfg['place_order_text']
						: __( 'Place order', 'woocommerce-jetpack' ),
					'price_prefix'               => ! empty( $cfg['price_labels_enabled'] ) ? wp_strip_all_tags( (string) $cfg['price_prefix'] ) : '',
					'price_suffix'               => ! empty( $cfg['price_labels_enabled'] ) ? wp_strip_all_tags( (string) $cfg['price_suffix'] ) : '',
					'price_instead'              => ! empty( $cfg['price_labels_enabled'] ) ? trim( wp_strip_all_tags( (string) $cfg['price_instead'] ) ) : '',
					'sample_name'                => $s_name,
					'sample_image'               => $s_img,
					'sample_regular'             => $s_reg,
					'sample_sale'                => $s_sale,
					'sample_on_sale'             => $s_on,
					'sale_badge_text'            => ( ! empty( $cfg['sale_flash_enabled'] ) && '' !== trim( (string) $cfg['sale_badge_text'] ) )
						? $cfg['sale_badge_text']
						: __( 'Sale!', 'woocommerce' ),
				);
			};

			return array(
				'before' => $project( $live ),
				'after'  => $project( $draft ),
			);
		}

		/**
		 * Map ONLY changed draft fields onto native wp_options keys without breaking unrelated Classic features.
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
			$writes  = array();

			// Non-destructive module enablement: turning Guided sub-feature ON also activates parent module,
			// turning Guided sub-feature OFF ONLY clears the sub-feature toggle (leaving Classic per-cat/per-product rules intact).
			if ( isset( $changed['add_to_cart_enabled'] ) ) {
				if ( ! empty( $draft['add_to_cart_enabled'] ) ) {
					$writes['wcj_add_to_cart_enabled']      = 'yes';
					$writes['wcj_add_to_cart_text_enabled'] = 'yes';
				} else {
					$writes['wcj_add_to_cart_text_enabled'] = 'no';
				}
			}

			if ( isset( $changed['add_to_cart_single'] ) || isset( $changed['apply_to_variable'] ) ) {
				$writes['wcj_add_to_cart_text_on_single_simple'] = $draft['add_to_cart_single'];
				if ( ! empty( $draft['apply_to_variable'] ) ) {
					$writes['wcj_add_to_cart_text_on_single_variable'] = $draft['add_to_cart_single'];
					$writes['wcj_add_to_cart_text_on_single_grouped']  = $draft['add_to_cart_single'];
					$writes['wcj_add_to_cart_text_on_single_external'] = $draft['add_to_cart_single'];
				}
			}

			if ( isset( $changed['add_to_cart_archive'] ) || isset( $changed['apply_to_variable'] ) ) {
				$writes['wcj_add_to_cart_text_on_archives_simple'] = $draft['add_to_cart_archive'];
				if ( ! empty( $draft['apply_to_variable'] ) ) {
					$writes['wcj_add_to_cart_text_on_archives_variable'] = $draft['add_to_cart_archive'];
					$writes['wcj_add_to_cart_text_on_archives_grouped']  = $draft['add_to_cart_archive'];
					$writes['wcj_add_to_cart_text_on_archives_external'] = $draft['add_to_cart_archive'];
				}
			}

			if ( isset( $changed['more_button_enabled'] ) ) {
				$writes['wcj_more_button_labels_enabled'] = ! empty( $draft['more_button_enabled'] ) ? 'yes' : 'no';
			}
			if ( isset( $changed['place_order_text'] ) ) {
				$writes['wcj_checkout_place_order_button_text'] = $draft['place_order_text'];
			}
			if ( isset( $changed['place_order_override'] ) ) {
				$writes['wcj_checkout_place_order_button_override'] = ! empty( $draft['place_order_override'] ) ? 'yes' : 'no';
			}

			if ( isset( $changed['price_labels_enabled'] ) ) {
				$writes['wcj_price_labels_enabled'] = ! empty( $draft['price_labels_enabled'] ) ? 'yes' : 'no';
			}
			if ( isset( $changed['price_prefix'] ) && self::is_pro() ) {
				$writes['wcj_global_price_labels_add_before_text'] = $draft['price_prefix'];
			}
			if ( isset( $changed['price_suffix'] ) ) {
				$writes['wcj_global_price_labels_add_after_text'] = $draft['price_suffix'];
			}
			if ( isset( $changed['price_instead'] ) ) {
				$writes['wcj_global_price_labels_instead_text'] = $draft['price_instead'];
			}

			if ( isset( $changed['sale_flash_enabled'] ) ) {
				if ( ! empty( $draft['sale_flash_enabled'] ) ) {
					$writes['wcj_sale_flash_enabled']                = 'yes';
					$writes['wcj_product_images_sale_flash_enabled'] = 'yes';
				} else {
					$writes['wcj_product_images_sale_flash_enabled'] = 'no';
				}
			}
			if ( isset( $changed['sale_badge_text'] ) ) {
				$writes['wcj_product_images_sale_flash_html'] = $draft['sale_flash_html'];
			}
			if ( isset( $changed['sale_flash_hide_everywhere'] ) ) {
				$writes['wcj_product_images_sale_flash_hide_everywhere'] = ! empty( $draft['sale_flash_hide_everywhere'] ) ? 'yes' : 'no';
			}

			unset( $snapshot );
			return $writes;
		}

		/**
		 * Emergency shutoff option writes (pauses only the guided label sub-features).
		 *
		 * @param array<string, array> $snapshot Fresh option snapshot.
		 * @return array<string, mixed>
		 */
		public static function emergency_off_writes( $snapshot = array() ) {
			$keys = array(
				'wcj_add_to_cart_text_enabled',
				'wcj_more_button_labels_enabled',
				'wcj_price_labels_enabled',
				'wcj_product_images_sale_flash_enabled',
			);
			$writes = array();
			foreach ( $keys as $k ) {
				$val = isset( $snapshot[ $k ] ) ? WCJ_V10_Store::state_value( $snapshot[ $k ] ) : get_option( $k, 'no' );
				if ( 'yes' === $val ) {
					$writes[ $k ] = 'no';
				}
			}
			return $writes;
		}
	}

endif;
