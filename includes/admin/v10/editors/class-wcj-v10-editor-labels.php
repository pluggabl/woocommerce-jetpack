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
	 * Unified Labels guided editor supporting Simple + Variable buttons, price overrides, and sale badges.
	 */
	class WCJ_V10_Editor_Labels {

		/**
		 * Unique editor slug.
		 *
		 * @return string
		 */
		public static function slug() {
			return 'button_price_labels';
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
		 * Plain-english label map for Review & Publish diffs.
		 *
		 * @return array<string, string>
		 */
		public static function diff_labels() {
			return array(
				'add_to_cart_enabled'        => __( 'Add to Cart Labels Module', 'woocommerce-jetpack' ),
				'add_to_cart_single'         => __( 'Single Product Button Text', 'woocommerce-jetpack' ),
				'add_to_cart_archive'        => __( 'Shop/Archive Button Text', 'woocommerce-jetpack' ),
				'apply_to_variable'          => __( 'Apply Button Text to Variable Products', 'woocommerce-jetpack' ),
				'more_button_enabled'        => __( 'More Button Labels Module', 'woocommerce-jetpack' ),
				'place_order_text'           => __( 'Place Order Button Text', 'woocommerce-jetpack' ),
				'place_order_override'       => __( 'Force Override Gateway Place Order Text', 'woocommerce-jetpack' ),
				'price_labels_enabled'       => __( 'Custom Price Labels Module', 'woocommerce-jetpack' ),
				'price_prefix'               => __( 'Text Before Price', 'woocommerce-jetpack' ),
				'price_suffix'               => __( 'Text After Price', 'woocommerce-jetpack' ),
				'price_instead'              => __( 'Instead of Price Text', 'woocommerce-jetpack' ),
				'sale_flash_enabled'         => __( 'Sale Flash Module', 'woocommerce-jetpack' ),
				'sale_flash_global_enabled'  => __( 'Global Sale Badge Override', 'woocommerce-jetpack' ),
				'sale_badge_text'            => __( 'Sale Badge Text', 'woocommerce-jetpack' ),
				'sale_flash_hide_everywhere' => __( 'Hide Sale Badge Everywhere', 'woocommerce-jetpack' ),
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
			if ( preg_match( '/^\s*<span\s+class=(["\'])onsale\1\s*>(.*?)<\/span>\s*$/is', $raw, $matches ) ) {
				return html_entity_decode( wp_strip_all_tags( $matches[2] ), ENT_QUOTES, 'UTF-8' );
			}
			return html_entity_decode( wp_strip_all_tags( $raw ), ENT_QUOTES, 'UTF-8' );
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

			$diagnostics = array();
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
					'sale_flash_global_enabled'  => 'yes' === get_option( 'wcj_product_images_sale_flash_enabled', 'no' ),
					'sale_badge_text'            => $sale_text,
					'sale_flash_html'            => $raw_sale_html,
					'sale_flash_hide_everywhere' => 'yes' === get_option( 'wcj_product_images_sale_flash_hide_everywhere', 'no' ),
				),
				'meta'        => array(
					'currency_symbol'     => $store_ctx['currency_symbol'],
					'price_prefix_locked' => 'free' === $edition,
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
		 * Sanitize draft values with Free-edition prefix lock preserved.
		 *
		 * @param array $draft Raw draft input.
		 * @param array $live  Current live state.
		 * @return array<string, mixed>
		 */
		public static function sanitize_draft( $draft, $live ) {
			$edition   = WCJ_V10_Store::edition();
			$sale_text = isset( $draft['sale_badge_text'] ) ? sanitize_text_field( (string) $draft['sale_badge_text'] ) : '';
			if ( '' !== $sale_text ) {
				$sale_html = '<span class="onsale">' . esc_html( $sale_text ) . '</span>';
			} else {
				$sale_html = isset( $draft['sale_flash_html'] ) ? wp_kses_post( (string) $draft['sale_flash_html'] ) : '';
				$sale_text = self::extract_sale_badge_text( $sale_html );
			}
			$sale_enabled = ! empty( $draft['sale_flash_enabled'] );
			if ( $sale_enabled && ! empty( $draft['sale_flash_global_enabled'] ) ) {
				$sale_global = true;
			} elseif ( $sale_enabled && empty( $live['sale_flash_enabled'] ) ) {
				$sale_global = true;
			} else {
				$sale_global = $sale_enabled && ! empty( $draft['sale_flash_global_enabled'] );
			}

			return array(
				'add_to_cart_enabled'        => ! empty( $draft['add_to_cart_enabled'] ),
				'add_to_cart_single'         => isset( $draft['add_to_cart_single'] ) ? sanitize_text_field( (string) $draft['add_to_cart_single'] ) : '',
				'add_to_cart_archive'        => isset( $draft['add_to_cart_archive'] ) ? sanitize_text_field( (string) $draft['add_to_cart_archive'] ) : '',
				'apply_to_variable'          => array_key_exists( 'apply_to_variable', $draft ) ? ! empty( $draft['apply_to_variable'] ) : ! empty( $live['apply_to_variable'] ),
				'more_button_enabled'        => ! empty( $draft['more_button_enabled'] ),
				'place_order_text'           => isset( $draft['place_order_text'] ) ? sanitize_text_field( (string) $draft['place_order_text'] ) : '',
				'place_order_override'       => ! empty( $draft['place_order_override'] ),
				'price_labels_enabled'       => ! empty( $draft['price_labels_enabled'] ),
				'price_prefix'               => 'free' === $edition ? $live['price_prefix'] : ( isset( $draft['price_prefix'] ) ? wp_kses_post( (string) $draft['price_prefix'] ) : '' ),
				'price_suffix'               => isset( $draft['price_suffix'] ) ? wp_kses_post( (string) $draft['price_suffix'] ) : '',
				'price_instead'              => isset( $draft['price_instead'] ) ? wp_kses_post( (string) $draft['price_instead'] ) : '',
				'sale_flash_enabled'         => $sale_enabled,
				'sale_flash_global_enabled'  => $sale_global,
				'sale_badge_text'            => $sale_text,
				'sale_flash_html'            => $sale_html,
				'sale_flash_hide_everywhere' => ! empty( $draft['sale_flash_hide_everywhere'] ),
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
					'sale_flash_global_enabled'  => ! empty( $cfg['sale_flash_global_enabled'] ),
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
					'price_prefix'               => ! empty( $cfg['price_labels_enabled'] ) ? $cfg['price_prefix'] : '',
					'price_suffix'               => ! empty( $cfg['price_labels_enabled'] ) ? $cfg['price_suffix'] : '',
					'price_instead'              => ! empty( $cfg['price_labels_enabled'] ) ? trim( (string) $cfg['price_instead'] ) : '',
					'sample_name'                => $s_name,
					'sample_image'               => $s_img,
					'sample_regular'             => $s_reg,
					'sample_sale'                => $s_sale,
					'sample_on_sale'             => $s_on,
					'sale_badge_text'            => ( ! empty( $cfg['sale_flash_enabled'] ) && ! empty( $cfg['sale_flash_global_enabled'] ) && '' !== trim( (string) $cfg['sale_badge_text'] ) )
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
		 * Map sanitized draft values back onto native wp_options keys.
		 *
		 * @param array $draft Sanitized draft.
		 * @return array<string, mixed>
		 */
		public static function build_option_writes( $draft ) {
			$writes = array(
				'wcj_add_to_cart_enabled'                     => $draft['add_to_cart_enabled'] ? 'yes' : 'no',
				'wcj_add_to_cart_text_enabled'                => $draft['add_to_cart_enabled'] ? 'yes' : 'no',
				'wcj_add_to_cart_text_on_single_simple'       => $draft['add_to_cart_single'],
				'wcj_add_to_cart_text_on_archives_simple'     => $draft['add_to_cart_archive'],
				'wcj_more_button_labels_enabled'              => $draft['more_button_enabled'] ? 'yes' : 'no',
				'wcj_checkout_place_order_button_text'        => $draft['place_order_text'],
				'wcj_checkout_place_order_button_override'    => ! empty( $draft['place_order_override'] ) ? 'yes' : 'no',
				'wcj_price_labels_enabled'                    => $draft['price_labels_enabled'] ? 'yes' : 'no',
				'wcj_global_price_labels_add_after_text'      => $draft['price_suffix'],
				'wcj_global_price_labels_instead_text'        => $draft['price_instead'],
				'wcj_sale_flash_enabled'                      => $draft['sale_flash_enabled'] ? 'yes' : 'no',
				'wcj_product_images_sale_flash_enabled'       => $draft['sale_flash_global_enabled'] ? 'yes' : 'no',
				'wcj_product_images_sale_flash_html'          => $draft['sale_flash_html'],
				'wcj_product_images_sale_flash_hide_everywhere' => ! empty( $draft['sale_flash_hide_everywhere'] ) ? 'yes' : 'no',
			);

			if ( ! empty( $draft['apply_to_variable'] ) ) {
				$writes['wcj_add_to_cart_text_on_single_variable']   = $draft['add_to_cart_single'];
				$writes['wcj_add_to_cart_text_on_archives_variable'] = $draft['add_to_cart_archive'];
			}

			if ( 'free' !== WCJ_V10_Store::edition() ) {
				$writes['wcj_global_price_labels_add_before_text'] = $draft['price_prefix'];
			}

			return $writes;
		}
	}

endif;
