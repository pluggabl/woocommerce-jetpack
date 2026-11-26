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
					'permission_callback' => array( __CLASS__, 'editor_permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/preview/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_preview' ),
					'permission_callback' => array( __CLASS__, 'editor_permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/publish/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_publish' ),
					'permission_callback' => array( __CLASS__, 'editor_permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/rollback/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_rollback' ),
					'permission_callback' => array( __CLASS__, 'editor_permissions_check' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/emergency-off/' . $slug_pattern,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'post_emergency_off' ),
					'permission_callback' => array( __CLASS__, 'editor_permissions_check' ),
				)
			);
		}

		/**
		 * Enforce global WooCommerce / Booster admin capability.
		 *
		 * @return bool
		 */
		public static function permissions_check() {
			if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return false;
			}
			if ( 'yes' === get_option( 'wcj_admin_tools_enabled', 'no' ) && 'yes' === get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ) {
				return current_user_can( 'manage_options' );
			}
			return true;
		}

		/**
		 * Enforce both global capability and per-module Booster allowlist / role restrictions.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return bool
		 */
		public static function editor_permissions_check( $request ) {
			if ( ! self::permissions_check() ) {
				return false;
			}
			$slug    = sanitize_key( (string) $request->get_param( 'slug' ) );
			$mod_ids = WCJ_V10_Registry::required_module_ids( $slug );
			if ( empty( $mod_ids ) ) {
				return false;
			}
			foreach ( $mod_ids as $mod_id ) {
				if ( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( $mod_id ) ) {
					return false;
				}
				if ( function_exists( 'wcj_is_module_enabled' ) && wcj_is_module_enabled( 'modules_by_user_roles' ) && function_exists( 'wcj_check_modules_by_user_roles' ) && ! wcj_check_modules_by_user_roles( $mod_id ) ) {
					return false;
				}
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
					'description' => __( 'Safe first win: add your logo, address, and colors to order PDFs without touching checkout.', 'woocommerce-jetpack' ),
					'editor_slug' => 'pdf_invoicing',
					'cta'         => __( 'Open Guided Preview', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'gateway_fees',
					'title'       => __( 'Add Payment Gateway Discounts or Fees', 'woocommerce-jetpack' ),
					'description' => __( 'Reward bank transfer choices or add COD handling rules with live checkout math.', 'woocommerce-jetpack' ),
					'editor_slug' => 'payment_gateways_fees',
					'cta'         => __( 'Open Cart Simulator', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'storefront_labels',
					'title'       => __( 'Boost Button, Price & Sale Labels', 'woocommerce-jetpack' ),
					'description' => __( 'Customize Add to cart, Place order, tax suffixes, and sale flash badges.', 'woocommerce-jetpack' ),
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
		 * Extract editor context keys defined by WCJ_V10_Registry::context_keys().
		 * Preserves exact case for gateway IDs while normalizing doc_type.
		 *
		 * @param string $slug   Editor slug.
		 * @param array  $source Key/value map.
		 * @return array<string, string>
		 */
		private static function extract_context( $slug, $source ) {
			$out = array();
			foreach ( WCJ_V10_Registry::context_keys( $slug ) as $key ) {
				if ( ! isset( $source[ $key ] ) ) {
					$out[ $key ] = '';
					continue;
				}
				if ( 'gateway' === $key ) {
					$out[ $key ] = sanitize_text_field( (string) $source[ $key ] );
				} else {
					$out[ $key ] = sanitize_key( (string) $source[ $key ] );
				}
			}
			return $out;
		}

		/**
		 * GET /booster/v1/editor/{slug}
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function get_editor( $request ) {
			$slug    = sanitize_key( (string) $request->get_param( 'slug' ) );
			$raw_ctx = array();
			foreach ( WCJ_V10_Registry::context_keys( $slug ) as $key ) {
				$raw_ctx[ $key ] = $request->get_param( $key );
			}
			$context = self::extract_context( $slug, $raw_ctx );
			$data    = self::load_editor_state( $slug, $context, true );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			return rest_ensure_response( $data );
		}

		/**
		 * POST /booster/v1/preview/{slug} (zero DB writes, fast path skipping revisions).
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function post_preview( $request ) {
			$slug       = sanitize_key( (string) $request->get_param( 'slug' ) );
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$params  = (array) $request->get_json_params();
			$draft   = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();
			$context = isset( $params['context'] ) && is_array( $params['context'] ) ? $params['context'] : array();
			$state   = self::load_editor_state( $slug, self::extract_context( $slug, $draft ), false );
			if ( is_wp_error( $state ) ) {
				return $state;
			}

			$clean_draft = $editor_cls::sanitize_draft( $draft, $state['live'] );
			$preview     = $editor_cls::compute_preview( $state['live'], $clean_draft, $context, $state['meta'] );
			$diffs       = self::compute_diffs( $editor_cls, $state['live'], $clean_draft );

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
			$slug       = sanitize_key( (string) $request->get_param( 'slug' ) );
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$params        = (array) $request->get_json_params();
			$draft         = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();
			$client_hashes = isset( $params['base_hashes'] ) && is_array( $params['base_hashes'] ) ? $params['base_hashes'] : null;
			$selected_keys = isset( $params['selected_keys'] ) && is_array( $params['selected_keys'] ) ? array_map( 'strval', $params['selected_keys'] ) : null;
			$ed_ctx        = self::extract_context( $slug, $draft );

			$lock_key = 'wcj_v10_lock_' . sanitize_key( $slug );
			try {
				$mine = WCJ_V10_Store::lock( $lock_key, __( 'Another publish or undo is already running.', 'woocommerce-jetpack' ) );
			} catch ( RuntimeException $e ) {
				return new WP_Error( 'wcj_v10_locked', $e->getMessage(), array( 'status' => 409 ) );
			}

			try {
				$owned_keys    = $editor_cls::owned_options( $ed_ctx );
				$live_snapshot = WCJ_V10_Store::snapshot_options( $owned_keys );

				// Optimistic concurrency guard: reject publish if another tab or Classic View mutated options since editor load.
				if ( null !== $client_hashes ) {
					$current_hashes = WCJ_V10_Store::hashes_from_snapshot( $live_snapshot );
					foreach ( $owned_keys as $opt_key ) {
						if ( isset( $client_hashes[ $opt_key ] ) && (string) $client_hashes[ $opt_key ] !== (string) $current_hashes[ $opt_key ] ) {
							return new WP_Error(
								'wcj_v10_stale_client',
								__( 'Live settings changed (for example in Classic View or another tab) after you opened this editor. Reload to review live values before publishing.', 'woocommerce-jetpack' ),
								array( 'status' => 409 )
							);
						}
					}
				}

				$state = self::load_editor_state( $slug, $ed_ctx, false );
				if ( is_wp_error( $state ) ) {
					return $state;
				}

				// If merchant unchecked individual changes in Review & Publish, merge ONLY selected keys onto live baseline.
				$effective_draft = $draft;
				if ( null !== $selected_keys ) {
					$allowed         = array_flip( $selected_keys );
					$effective_draft = $state['live'];
					foreach ( $ed_ctx as $ck => $cv ) {
						if ( '' !== $cv ) {
							$effective_draft[ $ck ] = $cv;
						}
					}
					foreach ( $draft as $dk => $dv ) {
						if ( isset( $allowed[ $dk ] ) ) {
							$effective_draft[ $dk ] = $dv;
						}
					}
				}

				$clean_draft = $editor_cls::sanitize_draft( $effective_draft, $state['live'] );
				$valid       = $editor_cls::validate_publish( $clean_draft );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}

				$diffs        = self::compute_diffs( $editor_cls, $state['live'], $clean_draft );
				$changed_keys = wp_list_pluck( $diffs, 'key' );
				$option_writes = ! empty( $changed_keys )
					? $editor_cls::build_option_writes( $clean_draft, $changed_keys, $live_snapshot )
					: array();

				if ( empty( $diffs ) || empty( $option_writes ) ) {
					$current = self::load_editor_state( $slug, $ed_ctx, true );
					return rest_ensure_response(
						array(
							'published'   => false,
							'message'     => __( 'No changes to publish.', 'woocommerce-jetpack' ),
							'live'        => $current['live'],
							'revisions'   => $current['revisions'],
							'base_hashes' => $current['base_hashes'],
							'diffs'       => array(),
						)
					);
				}

				$before_states = array();
				foreach ( array_keys( $option_writes ) as $opt_key ) {
					$before_states[ $opt_key ] = isset( $live_snapshot[ $opt_key ] ) ? $live_snapshot[ $opt_key ] : WCJ_V10_Store::read_state( $opt_key );
				}

				$batch = WCJ_V10_Store::atomic_write_batch( $before_states, $option_writes );
				if ( empty( $batch['ok'] ) ) {
					return new WP_Error(
						'wcj_v10_conflict',
						__( 'Concurrent change detected while publishing. Please reload and try again.', 'woocommerce-jetpack' ),
						array( 'status' => 409 )
					);
				}

				$summary  = implode( '; ', wp_list_pluck( $diffs, 'summary' ) );
				$revision = WCJ_V10_Store::record_revision( $slug, $summary, $before_states, $batch['after_states'] );

				do_action( 'wcj_v10_published', $slug, array_keys( $option_writes ) );

				$updated = self::load_editor_state( $slug, $ed_ctx, true );

				return rest_ensure_response(
					array(
						'published'   => true,
						'revision'    => array(
							'id'        => $revision['id'],
							'timestamp' => $revision['timestamp'],
							'summary'   => $revision['summary'],
						),
						'live'        => $updated['live'],
						'meta'        => $updated['meta'],
						'revisions'   => $updated['revisions'],
						'base_hashes' => $updated['base_hashes'],
						'diffs'       => $diffs,
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
			$slug   = sanitize_key( (string) $request->get_param( 'slug' ) );
			$params = (array) $request->get_json_params();
			$rev_id = isset( $params['revision_id'] ) ? sanitize_text_field( (string) $params['revision_id'] ) : '';
			$ed_ctx = self::extract_context( $slug, $params );

			try {
				WCJ_V10_Store::rollback_revision( $slug, $rev_id );
			} catch ( RuntimeException $e ) {
				$status = in_array( (int) $e->getCode(), array( 404, 409 ), true ) ? (int) $e->getCode() : 500;
				return new WP_Error( 'wcj_v10_rollback_error', $e->getMessage(), array( 'status' => $status ) );
			}

			do_action( 'wcj_v10_rolled_back', $slug, $rev_id );
			$updated = self::load_editor_state( $slug, $ed_ctx, true );
			return rest_ensure_response(
				array(
					'rolled_back' => true,
					'live'        => $updated['live'],
					'meta'        => $updated['meta'],
					'revisions'   => $updated['revisions'],
					'base_hashes' => $updated['base_hashes'],
				)
			);
		}

		/**
		 * POST /booster/v1/emergency-off/{slug}
		 * Immediately disables an active guided module outside drafts while recording an Undo snapshot.
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function post_emergency_off( $request ) {
			$slug       = sanitize_key( (string) $request->get_param( 'slug' ) );
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$params = (array) $request->get_json_params();
			$ed_ctx = self::extract_context( $slug, $params );

			$lock_key = 'wcj_v10_lock_' . sanitize_key( $slug );
			try {
				$mine = WCJ_V10_Store::lock( $lock_key, __( 'Another publish or undo is already running.', 'woocommerce-jetpack' ) );
			} catch ( RuntimeException $e ) {
				return new WP_Error( 'wcj_v10_locked', $e->getMessage(), array( 'status' => 409 ) );
			}

			try {
				$owned_keys    = $editor_cls::owned_options( $ed_ctx );
				$live_snapshot = WCJ_V10_Store::snapshot_options( $owned_keys );
				$off_writes    = method_exists( $editor_cls, 'emergency_off_writes' ) ? $editor_cls::emergency_off_writes( $live_snapshot ) : array();

				if ( empty( $off_writes ) ) {
					$updated = self::load_editor_state( $slug, $ed_ctx, true );
					return rest_ensure_response(
						array(
							'disabled'    => false,
							'message'     => __( 'Module is already inactive on your live store.', 'woocommerce-jetpack' ),
							'live'        => $updated['live'],
							'meta'        => $updated['meta'],
							'revisions'   => $updated['revisions'],
							'base_hashes' => $updated['base_hashes'],
						)
					);
				}

				$before_states = array();
				foreach ( array_keys( $off_writes ) as $opt_key ) {
					$before_states[ $opt_key ] = isset( $live_snapshot[ $opt_key ] ) ? $live_snapshot[ $opt_key ] : WCJ_V10_Store::read_state( $opt_key );
				}

				$batch = WCJ_V10_Store::atomic_write_batch( $before_states, $off_writes );
				if ( empty( $batch['ok'] ) ) {
					return new WP_Error(
						'wcj_v10_conflict',
						__( 'Concurrent change detected while turning off. Please reload and try again.', 'woocommerce-jetpack' ),
						array( 'status' => 409 )
					);
				}

				$revision = WCJ_V10_Store::record_revision(
					$slug,
					__( 'Emergency Turn Off on live store', 'woocommerce-jetpack' ),
					$before_states,
					$batch['after_states']
				);

				do_action( 'wcj_v10_published', $slug, array_keys( $off_writes ) );
				$updated = self::load_editor_state( $slug, $ed_ctx, true );

				return rest_ensure_response(
					array(
						'disabled'    => true,
						'revision'    => array(
							'id'        => $revision['id'],
							'timestamp' => $revision['timestamp'],
							'summary'   => $revision['summary'],
						),
						'live'        => $updated['live'],
						'meta'        => $updated['meta'],
						'revisions'   => $updated['revisions'],
						'base_hashes' => $updated['base_hashes'],
					)
				);
			} finally {
				WCJ_V10_Store::unlock( $lock_key, $mine );
			}
		}

		/**
		 * Gather real read-only WooCommerce storefront context (short transient cached for fast preview typing).
		 *
		 * @return array<string, mixed>
		 */
		public static function get_store_sample_data() {
			$cached = get_transient( 'wcj_v10_store_ctx_v1' );
			if ( is_array( $cached ) && isset( $cached['product'], $cached['store_defaults'] ) ) {
				return $cached;
			}

			$currency  = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
			$decimals  = (int) get_option( 'woocommerce_price_num_decimals', 2 );
			$dec_sep   = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
			$thou_sep  = function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',';
			$fmt_price = static function ( $amount ) use ( $currency, $decimals, $dec_sep, $thou_sep ) {
				return $currency . number_format( (float) $amount, $decimals, $dec_sep, $thou_sep );
			};

			$sale_ids          = function_exists( 'wc_get_product_ids_on_sale' ) ? array_filter( (array) wc_get_product_ids_on_sale() ) : array();
			$has_sale_products = ! empty( $sale_ids );
			$sample_price_num  = 76.0;
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
						$sample_price_num = max( 5.0, (float) ( $on_sale ? $sale_raw : $reg_raw ) );
						$sample_product   = array(
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

			$tier_low            = max( 10, (int) round( $sample_price_num / 5 ) * 5 );
			$tier_mid            = max( $tier_low + 25, (int) round( ( $sample_price_num * 2 ) / 5 ) * 5 );
			$tier_high           = max( $tier_mid + 50, (int) round( ( $sample_price_num * 4 ) / 10 ) * 10 );
			$sample_cart_amounts = array( $tier_low, $tier_mid, $tier_high );

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

			$ctx = array(
				'currency_symbol'     => $currency,
				'decimals'            => $decimals,
				'sample_cart_amounts' => $sample_cart_amounts,
				'product'             => $sample_product,
				'store_defaults'      => array(
					'name'     => (string) $store_name,
					'address'  => $store_addr,
					'logo_url' => $logo_url,
				),
				'checkout_url'        => function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : home_url( '/checkout/' ),
				'orders_url'          => ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() )
					? admin_url( 'admin.php?page=wc-orders' )
					: admin_url( 'edit.php?post_type=shop_order' ),
				'is_blocks_checkout'  => (bool) $is_blocks_chk,
				'is_block_theme'      => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			);

			set_transient( 'wcj_v10_store_ctx_v1', $ctx, 60 );
			return $ctx;
		}

		/**
		 * Load editor state, base_hashes, and (optionally) revisions via WCJ_V10_Registry.
		 *
		 * @param string $slug              Editor slug.
		 * @param array  $context           Optional context params.
		 * @param bool   $include_revisions Whether to load revisions + base_hashes.
		 * @return array|WP_Error
		 */
		private static function load_editor_state( $slug, $context = array(), $include_revisions = true ) {
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$store_ctx = self::get_store_sample_data();
			$data      = $editor_cls::load_state( $context, $store_ctx );

			if ( $include_revisions ) {
				$owned               = $editor_cls::owned_options( $context );
				$data['base_hashes'] = WCJ_V10_Store::hashes_from_snapshot( WCJ_V10_Store::snapshot_options( $owned ) );
				$data['revisions']   = array_map(
					static function ( $rev ) {
						return array(
							'id'        => $rev['id'],
							'timestamp' => $rev['timestamp'],
							'summary'   => $rev['summary'],
						);
					},
					array_reverse( WCJ_V10_Store::get_revisions( $slug ) )
				);
			}

			return $data;
		}

		/**
		 * Human-readable field diffs with customer-facing impact grouping for Review & Publish modal.
		 *
		 * @param string $editor_cls Editor class name.
		 * @param array  $live       Live state.
		 * @param array  $draft      Sanitized draft state.
		 * @return array<int, array{key: string, label: string, from: string, to: string, summary: string, goes_live: bool, impact: string}>
		 */
		private static function compute_diffs( $editor_cls, $live, $draft ) {
			$map    = $editor_cls::diff_labels();
			$impact = method_exists( $editor_cls, 'impact_groups' ) ? $editor_cls::impact_groups() : array();
			$diffs  = array();
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
				$meta_imp = isset( $impact[ $key ] ) ? $impact[ $key ] : array();
				$diffs[]  = array(
					'key'       => $key,
					'label'     => $field_label,
					'from'      => $from_str,
					'to'        => $to_str,
					'summary'   => $field_label . ': ' . $from_str . ' -> ' . $to_str,
					'goes_live' => ! empty( $meta_imp['goes_live'] ),
					'impact'    => isset( $meta_imp['impact'] ) ? (string) $meta_imp['impact'] : '',
				);
			}
			return $diffs;
		}
	}

endif;
