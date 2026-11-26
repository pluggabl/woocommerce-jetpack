<?php
/**
 * Booster True V10 - Unified REST Engine for Command Center, Previews, Publish & Undo.
 *
 * Delegates module-specific logic to registered WCJ_V10_Editor_* classes via WCJ_V10_Registry.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_REST' ) ) :

	/**
	 * Registers and handles booster/v1 REST endpoints.
	 */
	class WCJ_V10_REST {

		const NAMESPACE = 'booster/v1';

		/**
		 * Register all V10 REST routes.
		 *
		 * @return void
		 */
		public static function register_routes() {
			register_rest_route(
				self::NAMESPACE,
				'/modules',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_modules' ),
					'permission_callback' => array( __CLASS__, 'permissions_check' ),
				)
			);

			$slug_pattern = WCJ_V10_Registry::slug_pattern();

			register_rest_route(
				self::NAMESPACE,
				'/editor/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_editor' ),
					'permission_callback' => array( __CLASS__, 'permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/preview/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_preview' ),
					'permission_callback' => array( __CLASS__, 'permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/publish/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_publish' ),
					'permission_callback' => array( __CLASS__, 'permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/rollback/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_rollback' ),
					'permission_callback' => array( __CLASS__, 'permissions_check' ),
				)
			);
		}

		/**
		 * Enforce WooCommerce / Booster admin capability.
		 *
		 * @return bool
		 */
		public static function permissions_check() {
			if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}
			if ( 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ) {
				return current_user_can( 'manage_options' );
			}
			return true;
		}

		/**
		 * GET /booster/v1/modules
		 *
		 * @return WP_REST_Response
		 */
		public static function get_modules() {
			$catalog = WCJ_V10_Catalog::get_catalog();
			$nonce   = wp_create_nonce( 'wcj-cat-nonce' );
			$recipes = array(
				array(
					'id'          => 'brand_invoices',
					'title'       => __( 'Brand PDF Invoices', 'woocommerce-jetpack' ),
					'description' => __( 'Add your business address, logo, and brand color to order PDFs.', 'woocommerce-jetpack' ),
					'editor_slug' => 'pdf_invoicing',
					'cta'         => __( 'Open Guided Preview', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'gateway_fees',
					'title'       => __( 'Add Payment Gateway Fees or Discounts', 'woocommerce-jetpack' ),
					'description' => __( 'Encourage bank/COD choices or cover card processing fees cleanly.', 'woocommerce-jetpack' ),
					'editor_slug' => 'payment_gateways_fees',
					'cta'         => __( 'Open Cart Simulator', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'storefront_labels',
					'title'       => __( 'Boost Button, Price & Sale Labels', 'woocommerce-jetpack' ),
					'description' => __( 'Customize Add to cart buttons, tax suffixes, and sale flash badges.', 'woocommerce-jetpack' ),
					'editor_slug' => 'button_price_labels',
					'cta'         => __( 'Preview Storefront Card', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'cart_recovery',
					'title'       => __( 'Recover Lost Sales', 'woocommerce-jetpack' ),
					'description' => __( 'Configure automated abandoned cart follow-ups in Classic settings.', 'woocommerce-jetpack' ),
					'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=cart_and_checkout&section=cart_abandonment&wcj_classic=1&wcj-cat-nonce=' . $nonce ),
					'cta'         => __( 'Configure Cart Recovery', 'woocommerce-jetpack' ),
				),
			);

			return rest_ensure_response(
				array(
					'edition'          => WCJ_V10_Store::edition(),
					'ui_mode'          => WCJ_V10_Bootstrap::is_v10_mode() ? 'v10' : 'classic',
					'categories'       => $catalog['categories'],
					'modules'          => $catalog['modules'],
					'recipes'          => $recipes,
					'flagship_editors' => WCJ_V10_Registry::flagship_summaries(),
				)
			);
		}

		/**
		 * Extract editor context keys (e.g. gateway, doc_type) from params or draft.
		 *
		 * @param array $source Key/value map.
		 * @return array<string, string>
		 */
		private static function extract_context( $source ) {
			return array(
				'gateway'  => isset( $source['gateway'] ) ? sanitize_key( (string) $source['gateway'] ) : '',
				'doc_type' => isset( $source['doc_type'] ) ? sanitize_key( (string) $source['doc_type'] ) : '',
			);
		}

		/**
		 * GET /booster/v1/editor/{slug}
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function get_editor( $request ) {
			$slug    = $request->get_param( 'slug' );
			$context = self::extract_context(
				array(
					'gateway'  => $request->get_param( 'gateway' ),
					'doc_type' => $request->get_param( 'doc_type' ),
				)
			);
			$data    = self::load_editor_state( $slug, $context );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			return rest_ensure_response( $data );
		}

		/**
		 * POST /booster/v1/preview/{slug} (zero DB writes).
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function post_preview( $request ) {
			$slug       = $request->get_param( 'slug' );
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$params  = (array) $request->get_json_params();
			$draft   = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();
			$context = isset( $params['context'] ) && is_array( $params['context'] ) ? $params['context'] : array();
			$state   = self::load_editor_state( $slug, self::extract_context( $draft ) );
			if ( is_wp_error( $state ) ) {
				return $state;
			}

			$clean_draft = $editor_cls::sanitize_draft( $draft, $state['live'] );
			$preview     = $editor_cls::compute_preview( $state['live'], $clean_draft, $context, $state['meta'] );
			$diffs       = self::compute_diffs( $editor_cls::diff_labels(), $state['live'], $clean_draft );

			return rest_ensure_response(
				array(
					'slug'   => $slug,
					'live'   => $state['live'],
					'draft'  => $clean_draft,
					'before' => $preview['before'],
					'after'  => $preview['after'],
					'diffs'  => $diffs,
				)
			);
		}

		/**
		 * POST /booster/v1/publish/{slug}
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function post_publish( $request ) {
			$slug       = $request->get_param( 'slug' );
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$params = (array) $request->get_json_params();
			$draft  = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();
			$ed_ctx = self::extract_context( $draft );

			$lock_key = 'wcj_v10_lock_' . sanitize_key( $slug );
			try {
				$mine = WCJ_V10_Store::lock( $lock_key, __( 'Another publish or undo is already running.', 'woocommerce-jetpack' ) );
			} catch ( RuntimeException $e ) {
				return new WP_Error( 'wcj_v10_locked', $e->getMessage(), array( 'status' => 409 ) );
			}

			try {
				$state = self::load_editor_state( $slug, $ed_ctx );
				if ( is_wp_error( $state ) ) {
					return $state;
				}

				$clean_draft = $editor_cls::sanitize_draft( $draft, $state['live'] );
				$valid       = $editor_cls::validate_publish( $clean_draft );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}

				$diffs = self::compute_diffs( $editor_cls::diff_labels(), $state['live'], $clean_draft );
				if ( empty( $diffs ) ) {
					return rest_ensure_response(
						array(
							'published' => false,
							'message'   => __( 'No changes to publish.', 'woocommerce-jetpack' ),
							'live'      => $state['live'],
							'revisions' => $state['revisions'],
							'diffs'     => array(),
						)
					);
				}

				$option_writes = $editor_cls::build_option_writes( $clean_draft );
				$before_states = WCJ_V10_Store::snapshot_options( array_keys( $option_writes ) );
				$batch         = WCJ_V10_Store::atomic_write_batch( $before_states, $option_writes );

				if ( empty( $batch['ok'] ) ) {
					return new WP_Error(
						'wcj_v10_conflict',
						__( 'Concurrent change detected while publishing. Please reload and try again.', 'woocommerce-jetpack' ),
						array( 'status' => 409 )
					);
				}

				$summary  = implode( '; ', wp_list_pluck( $diffs, 'summary' ) );
				$revision = WCJ_V10_Store::record_revision( $slug, $summary, $before_states, $batch['after_states'] );
				$updated  = self::load_editor_state( $slug, $ed_ctx );

				return rest_ensure_response(
					array(
						'published' => true,
						'revision'  => array(
							'id'        => $revision['id'],
							'timestamp' => $revision['timestamp'],
							'summary'   => $revision['summary'],
						),
						'live'      => $updated['live'],
						'meta'      => $updated['meta'],
						'revisions' => $updated['revisions'],
						'diffs'     => $diffs,
					)
				);
			} finally {
				WCJ_V10_Store::unlock( $lock_key, $mine );
			}
		}

		/**
		 * POST /booster/v1/rollback/{slug}
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function post_rollback( $request ) {
			$slug   = $request->get_param( 'slug' );
			$params = (array) $request->get_json_params();
			$rev_id = isset( $params['revision_id'] ) ? sanitize_text_field( (string) $params['revision_id'] ) : '';
			$ed_ctx = self::extract_context( $params );

			try {
				WCJ_V10_Store::rollback_revision( $slug, $rev_id );
			} catch ( RuntimeException $e ) {
				$status = in_array( (int) $e->getCode(), array( 404, 409 ), true ) ? (int) $e->getCode() : 500;
				return new WP_Error( 'wcj_v10_rollback_error', $e->getMessage(), array( 'status' => $status ) );
			}

			$updated = self::load_editor_state( $slug, $ed_ctx );
			return rest_ensure_response(
				array(
					'rolled_back' => true,
					'live'        => $updated['live'],
					'meta'        => $updated['meta'],
					'revisions'   => $updated['revisions'],
				)
			);
		}

		/**
		 * Gather real read-only WooCommerce storefront context for realistic previews and checks.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_store_sample_data() {
			$currency  = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
			$decimals  = (int) get_option( 'woocommerce_price_num_decimals', 2 );
			$dec_sep   = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
			$thou_sep  = function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',';
			$fmt_price = static function ( $amount ) use ( $currency, $decimals, $dec_sep, $thou_sep ) {
				return $currency . number_format( (float) $amount, $decimals, $dec_sep, $thou_sep );
			};

			$sale_ids          = function_exists( 'wc_get_product_ids_on_sale' ) ? array_filter( (array) wc_get_product_ids_on_sale() ) : array();
			$has_sale_products = ! empty( $sale_ids );
			$sample_product    = array(
				'id'                => 0,
				'name'              => __( 'Sample Storefront Product', 'woocommerce-jetpack' ),
				'permalink'         => home_url( '/' ),
				'image_url'         => '',
				'regular_price'     => $fmt_price( 95 ),
				'sale_price'        => $fmt_price( 76 ),
				'on_sale'           => true,
				'has_sale_products' => $has_sale_products,
			);

			if ( function_exists( 'wc_get_product' ) ) {
				$picked = null;
				if ( $has_sale_products ) {
					foreach ( $sale_ids as $sid ) {
						$cand = wc_get_product( (int) $sid );
						if ( $cand && 'publish' === $cand->get_status() && '' !== $cand->get_regular_price() ) {
							$picked = $cand;
							break;
						}
					}
				}
				if ( ! $picked && function_exists( 'wc_get_products' ) ) {
					$recent = wc_get_products(
						array(
							'status'  => 'publish',
							'limit'   => 1,
							'orderby' => 'ID',
							'order'   => 'DESC',
						)
					);
					if ( ! empty( $recent ) && is_object( $recent[0] ) ) {
						$picked = $recent[0];
					}
				}
				if ( $picked && is_object( $picked ) ) {
					$reg_raw = $picked->get_regular_price();
					if ( '' === (string) $reg_raw ) {
						$reg_raw = $picked->get_price();
					}
					$sale_raw = $picked->get_sale_price();
					$on_sale  = $picked->is_on_sale() && '' !== (string) $sale_raw;
					$img_id   = $picked->get_image_id();
					$img_url  = $img_id ? wp_get_attachment_image_url( (int) $img_id, 'woocommerce_thumbnail' ) : '';
					if ( '' !== (string) $reg_raw ) {
						$sample_product = array(
							'id'                => (int) $picked->get_id(),
							'name'              => wp_strip_all_tags( (string) $picked->get_name() ),
							'permalink'         => (string) $picked->get_permalink(),
							'image_url'         => $img_url ? (string) $img_url : '',
							'regular_price'     => $fmt_price( (float) $reg_raw ),
							'sale_price'        => $on_sale ? $fmt_price( (float) $sale_raw ) : '',
							'on_sale'           => (bool) $on_sale,
							'has_sale_products' => $has_sale_products,
						);
					}
				}
			}

			$addr_parts = array_filter(
				array(
					trim( (string) get_option( 'woocommerce_store_address', '' ) ),
					trim( (string) get_option( 'woocommerce_store_address_2', '' ) ),
					trim( implode( ' ', array_filter( array( (string) get_option( 'woocommerce_store_city', '' ), (string) get_option( 'woocommerce_store_postcode', '' ) ) ) ) ),
					trim( (string) get_option( 'woocommerce_default_country', '' ) ),
				)
			);
			$store_name = get_bloginfo( 'name' );
			$store_addr = trim( $store_name . ( ! empty( $addr_parts ) ? "\n" . implode( "\n", $addr_parts ) : '' ) );
			$logo_id    = (int) get_theme_mod( 'custom_logo', 0 );
			$logo_url   = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

			$checkout_id   = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
			$is_blocks_chk = ( $checkout_id > 0 && function_exists( 'has_block' ) ) ? has_block( 'woocommerce/checkout', $checkout_id ) : false;
			$hpos_on       = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
				? \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
				: false;

			return array(
				'currency_symbol'    => $currency,
				'decimals'           => $decimals,
				'product'            => $sample_product,
				'store_defaults'     => array(
					'name'     => (string) $store_name,
					'address'  => $store_addr,
					'logo_url' => $logo_url,
				),
				'checkout_url'       => function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : home_url( '/checkout/' ),
				'orders_url'         => $hpos_on ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' ),
				'is_blocks_checkout' => (bool) $is_blocks_chk,
				'is_block_theme'     => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			);
		}

		/**
		 * Load editor state + revisions via WCJ_V10_Registry.
		 *
		 * @param string $slug    Editor slug.
		 * @param array  $context Optional context params (gateway, doc_type).
		 * @return array|WP_Error
		 */
		private static function load_editor_state( $slug, $context = array() ) {
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$store_ctx = self::get_store_sample_data();
			$data      = $editor_cls::load_state( $context, $store_ctx );
			$revisions = array_map(
				static function ( $rev ) {
					return array(
						'id'        => $rev['id'],
						'timestamp' => $rev['timestamp'],
						'summary'   => $rev['summary'],
					);
				},
				array_reverse( WCJ_V10_Store::get_revisions( $slug ) )
			);

			$data['revisions'] = $revisions;
			return $data;
		}

		/**
		 * Human-readable field diffs for Review & Publish modal.
		 *
		 * @param array $map   Map of field_key => label.
		 * @param array $live  Live state.
		 * @param array $draft Sanitized draft state.
		 * @return array<int, array{key: string, label: string, from: string, to: string, summary: string}>
		 */
		private static function compute_diffs( $map, $live, $draft ) {
			$diffs = array();
			foreach ( $map as $key => $field_label ) {
				$old = isset( $live[ $key ] ) ? $live[ $key ] : '';
				$new = isset( $draft[ $key ] ) ? $draft[ $key ] : '';
				if ( is_bool( $old ) || is_bool( $new ) ) {
					if ( (bool) $old === (bool) $new ) {
						continue;
					}
					$from_str = $old ? __( 'On', 'woocommerce-jetpack' ) : __( 'Off', 'woocommerce-jetpack' );
					$to_str   = $new ? __( 'On', 'woocommerce-jetpack' ) : __( 'Off', 'woocommerce-jetpack' );
				} else {
					if ( (string) $old === (string) $new ) {
						continue;
					}
					$from_str = '' === (string) $old ? __( '(Empty)', 'woocommerce-jetpack' ) : wp_strip_all_tags( (string) $old );
					$to_str   = '' === (string) $new ? __( '(Empty)', 'woocommerce-jetpack' ) : wp_strip_all_tags( (string) $new );
				}
				$diffs[] = array(
					'key'     => $key,
					'label'   => $field_label,
					'from'    => $from_str,
					'to'      => $to_str,
					'summary' => $field_label . ': ' . $from_str . ' -> ' . $to_str,
				);
			}
			return $diffs;
		}
	}

endif;
