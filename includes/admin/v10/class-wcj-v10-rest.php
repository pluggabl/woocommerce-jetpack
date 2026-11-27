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
			$recipes = array(
				array(
					'id'          => 'brand_invoices',
					'title'       => __( 'Brand PDF Invoices', 'woocommerce-jetpack' ),
					'description' => __( 'Safe first win: add your logo, address, and colors to order PDFs without touching checkout.', 'woocommerce-jetpack' ),
					'editor_slug' => 'pdf_invoicing',
					'cta'         => __( 'Open Guided Preview', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'customer_bulk_pricing',
					'title'       => __( 'Offer Customer & Bulk Discounts', 'woocommerce-jetpack' ),
					'description' => __( 'Set VIP/role % discounts or "Buy 5+ save 10%" volume tiers with a live price ledger.', 'woocommerce-jetpack' ),
					'editor_slug' => 'customer_bulk_pricing',
					'cta'         => __( 'Open Pricing Simulator', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'product_addons',
					'title'       => __( 'Add Gift Wrap & Product Options', 'woocommerce-jetpack' ),
					'description' => __( 'Offer optional paid add-ons or gift notes directly on product pages.', 'woocommerce-jetpack' ),
					'editor_slug' => 'product_addons',
					'cta'         => __( 'Preview Product Add-ons', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'gateway_fees',
					'title'       => __( 'Add Payment Gateway Discounts or Fees', 'woocommerce-jetpack' ),
					'description' => __( 'Reward bank transfer choices or add COD handling rules with live checkout math.', 'woocommerce-jetpack' ),
					'editor_slug' => 'payment_gateways_fees',
					'cta'         => __( 'Open Cart Simulator', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'shipping_free_bar',
					'title'       => __( 'Boost AOV with Free Shipping Bar', 'woocommerce-jetpack' ),
					'description' => __( 'Show shoppers how close they are to Free Shipping and hide extra paid rates once reached.', 'woocommerce-jetpack' ),
					'editor_slug' => 'shipping_free_bar',
					'cta'         => __( 'Preview Shipping Bar', 'woocommerce-jetpack' ),
				),
				array(
					'id'          => 'multicurrency',
					'title'       => __( 'Show Prices in Shopper Currencies', 'woocommerce-jetpack' ),
					'description' => __( 'Add EUR, GBP, or CAD display currencies and rounding rules with a live storefront switcher.', 'woocommerce-jetpack' ),
					'editor_slug' => 'multicurrency',
					'cta'         => __( 'Open Currency Preview', 'woocommerce-jetpack' ),
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
		 * Extract typed editor context keys defined by WCJ_V10_Registry::context_keys().
		 *
		 * @param string $slug   Editor slug.
		 * @param array  $source Key/value map.
		 * @return array<string, mixed>
		 */
		private static function extract_context( $slug, $source ) {
			$out = array();
			foreach ( WCJ_V10_Registry::context_keys( $slug ) as $key ) {
				if ( ! isset( $source[ $key ] ) || '' === (string) $source[ $key ] ) {
					$out[ $key ] = '';
					continue;
				}
				if ( 'field_index' === $key || 'addon_index' === $key || 'template_index' === $key ) {
					$out[ $key ] = max( 1, absint( $source[ $key ] ) );
				} elseif ( 'gateway' === $key || 'role' === $key ) {
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
		 * POST /booster/v1/preview/{slug} (zero DB writes, fast path skipping revisions and heavy diagnostics).
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
			$ed_ctx  = self::extract_context( $slug, $draft );
			$state   = self::load_editor_state( $slug, $ed_ctx, false, true );
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
				$volatile_keys = $editor_cls::volatile_options( $ed_ctx );
				$hash_keys     = array_values( array_diff( $owned_keys, $volatile_keys ) );
				$live_snapshot = WCJ_V10_Store::snapshot_options( $owned_keys );

				// Optimistic concurrency guard: reject publish if another tab or Classic View mutated non-volatile options since editor load.
				if ( null !== $client_hashes ) {
					$current_hashes = WCJ_V10_Store::hashes_from_snapshot( $live_snapshot );
					foreach ( $hash_keys as $opt_key ) {
						if ( ! array_key_exists( $opt_key, $client_hashes ) || (string) $client_hashes[ $opt_key ] !== (string) $current_hashes[ $opt_key ] ) {
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
					// Keep currency slot codes and exchange rates paired so partial selection never writes a currency without its rate.
					foreach ( array( 1, 2, 3 ) as $mc_slot ) {
						if ( isset( $allowed[ 'currency_' . $mc_slot ] ) || isset( $allowed[ 'rate_' . $mc_slot ] ) ) {
							$allowed[ 'currency_' . $mc_slot ] = true;
							$allowed[ 'rate_' . $mc_slot ]     = true;
						}
					}
					$effective_draft = $state['live'];
					foreach ( $ed_ctx as $ck => $cv ) {
						if ( '' !== (string) $cv ) {
							$effective_draft[ $ck ] = $cv;
						}
					}
					foreach ( $draft as $dk => $dv ) {
						if ( isset( $allowed[ $dk ] ) ) {
							$effective_draft[ $dk ] = $dv;
						}
					}
					if ( array_key_exists( 'counter_base', $draft ) && ! isset( $allowed['counter'] ) ) {
						$effective_draft['counter_base'] = $draft['counter_base'];
					}
				}

				$clean_draft  = $editor_cls::sanitize_draft( $effective_draft, $state['live'] );
				$diffs        = self::compute_diffs( $editor_cls, $state['live'], $clean_draft );
				$changed_keys = wp_list_pluck( $diffs, 'key' );
				$valid        = $editor_cls::validate_publish( $clean_draft, $state['live'], $changed_keys );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}

				$option_writes = ! empty( $changed_keys )
					? $editor_cls::build_option_writes( $clean_draft, $changed_keys, $live_snapshot )
					: array();

				// Enforce strict write ownership: editors may only mutate options declared in owned_options().
				$unowned = array_diff( array_keys( $option_writes ), $owned_keys );
				if ( ! empty( $unowned ) ) {
					return new WP_Error(
						'wcj_v10_unowned_write',
						__( 'Editor attempted to write undeclared options.', 'woocommerce-jetpack' ),
						array( 'status' => 500 )
					);
				}

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

				$editor_cls::on_after_write( array_keys( $option_writes ) );
				delete_transient( 'wcj_v10_store_ctx_v4' );
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
			$slug             = sanitize_key( (string) $request->get_param( 'slug' ) );
			$params           = (array) $request->get_json_params();
			$rev_id           = isset( $params['revision_id'] ) ? sanitize_text_field( (string) $params['revision_id'] ) : '';
			$ed_ctx           = self::extract_context( $slug, $params );
			$editor_cls       = WCJ_V10_Registry::get( $slug );
			$volatile_keys    = $editor_cls ? $editor_cls::volatile_options( $ed_ctx ) : array();
			$skipped_volatile = array();

			try {
				WCJ_V10_Store::rollback_revision( $slug, $rev_id, $volatile_keys, $skipped_volatile );
			} catch ( RuntimeException $e ) {
				$status = in_array( (int) $e->getCode(), array( 404, 409 ), true ) ? (int) $e->getCode() : 500;
				return new WP_Error( 'wcj_v10_rollback_error', $e->getMessage(), array( 'status' => $status ) );
			}

			if ( $editor_cls ) {
				$editor_cls::on_after_write( $editor_cls::owned_options( $ed_ctx ) );
			}
			delete_transient( 'wcj_v10_store_ctx_v4' );
			do_action( 'wcj_v10_rolled_back', $slug, $rev_id );
			$updated = self::load_editor_state( $slug, $ed_ctx, true );
			return rest_ensure_response(
				array(
					'rolled_back'      => true,
					'skipped_volatile' => $skipped_volatile,
					'live'             => $updated['live'],
					'meta'             => $updated['meta'],
					'revisions'        => $updated['revisions'],
					'base_hashes'      => $updated['base_hashes'],
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

				$editor_cls::on_after_write( array_keys( $off_writes ) );
				delete_transient( 'wcj_v10_store_ctx_v4' );
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
			$cached = get_transient( 'wcj_v10_store_ctx_v4' );
			if ( is_array( $cached ) && isset( $cached['product'], $cached['store_defaults'], $cached['roles'], $cached['store_currency'], $cached['cache_plugins'], $cached['mc_conflicts'] ) ) {
				return $cached;
			}

			global $wpdb;
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
			$sample_reg_num    = 95.0;
			$sample_product    = array(
				'id'                => 0,
				'name'              => __( 'Sample Storefront Product', 'woocommerce-jetpack' ),
				'permalink'         => home_url( '/' ),
				'image_url'         => '',
				'regular_price'     => $fmt_price( 95 ),
				'sale_price'        => $fmt_price( 76 ),
				'price_num'         => 76.0,
				'regular_num'       => 95.0,
				'on_sale'           => true,
				'is_variable'       => false,
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
						$sample_reg_num   = max( 1.0, (float) $reg_raw );
						$sample_price_num = max( 1.0, (float) ( $on_sale ? $sale_raw : $reg_raw ) );
						$sample_product   = array(
							'id'                => (int) $picked->get_id(),
							'name'              => wp_strip_all_tags( (string) $picked->get_name() ),
							'permalink'         => (string) $picked->get_permalink(),
							'image_url'         => $img_url ? (string) $img_url : '',
							'regular_price'     => $fmt_price( (float) $reg_raw ),
							'sale_price'        => $on_sale ? $fmt_price( (float) $sale_raw ) : '',
							'price_num'         => $sample_price_num,
							'regular_num'       => $sample_reg_num,
							'on_sale'           => (bool) $on_sale,
							'is_variable'       => method_exists( $picked, 'is_type' ) && $picked->is_type( 'variable' ),
							'has_sale_products' => $has_sale_products,
						);
					}
				}
			}

			$published_products = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$downloadable_count = (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key IN ('_downloadable','_virtual') AND pm.meta_value = 'yes'"
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$last_order_id = 0;
			if ( function_exists( 'wc_get_orders' ) ) {
				$latest_orders = wc_get_orders(
					array(
						'limit'   => 1,
						'orderby' => 'ID',
						'order'   => 'DESC',
						'return'  => 'ids',
					)
				);
				if ( ! empty( $latest_orders ) ) {
					$last_order_id = (int) $latest_orders[0];
				}
			}

			$user_counts = function_exists( 'count_users' ) ? count_users() : array( 'avail_roles' => array() );
			$avail_roles = isset( $user_counts['avail_roles'] ) && is_array( $user_counts['avail_roles'] ) ? $user_counts['avail_roles'] : array();
			$wp_roles    = function_exists( 'wp_roles' ) ? wp_roles()->get_names() : array( 'customer' => 'Customer' );
			$custom_r    = (array) get_option( 'wcj_custom_roles', array() );
			$roles_list  = array();
			foreach ( $wp_roles as $r_slug => $r_name ) {
				$roles_list[] = array(
					'id'    => (string) $r_slug,
					'name'  => wp_strip_all_tags( (string) translate_user_role( $r_name ) ),
					'users' => isset( $avail_roles[ $r_slug ] ) ? (int) $avail_roles[ $r_slug ] : 0,
				);
			}
			foreach ( $custom_r as $c_slug => $c_data ) {
				if ( is_string( $c_slug ) && ! isset( $wp_roles[ $c_slug ] ) && is_array( $c_data ) ) {
					$roles_list[] = array(
						'id'    => $c_slug,
						'name'  => wp_strip_all_tags( (string) ( $c_data['name'] ?? $c_slug ) ),
						'users' => isset( $avail_roles[ $c_slug ] ) ? (int) $avail_roles[ $c_slug ] : 0,
					);
				}
			}
			$roles_list[] = array(
				'id'    => 'guest',
				'name'  => __( 'Guest (Logged-out)', 'woocommerce-jetpack' ),
				'users' => 0,
			);

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
			$cart_id       = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
			$is_blocks_chk = ( $checkout_id > 0 && function_exists( 'has_block' ) ) ? has_block( 'woocommerce/checkout', $checkout_id ) : false;
			$is_blocks_crt = ( $cart_id > 0 && function_exists( 'has_block' ) ) ? has_block( 'woocommerce/cart', $cart_id ) : false;
			$wc_version    = defined( 'WC_VERSION' ) ? (string) WC_VERSION : '0.0.0';
			$cache_plugins = array();
			if ( defined( 'WP_ROCKET_VERSION' ) ) {
				$cache_plugins[] = 'WP Rocket';
			}
			if ( defined( 'LSCWP_V' ) ) {
				$cache_plugins[] = 'LiteSpeed Cache';
			}
			if ( defined( 'W3TC' ) ) {
				$cache_plugins[] = 'W3 Total Cache';
			}
			if ( defined( 'WPCACHEHOME' ) ) {
				$cache_plugins[] = 'WP Super Cache';
			}
			if ( defined( 'CLOUDFLARE_PLUGIN_DIR' ) ) {
				$cache_plugins[] = 'Cloudflare APO';
			}
			if ( empty( $cache_plugins ) && defined( 'WP_CACHE' ) && WP_CACHE ) {
				$cache_plugins[] = 'WordPress Page Cache';
			}
			$has_cache     = ! empty( $cache_plugins );
			$mc_conflicts  = array();
			if ( class_exists( 'WC_Payments_Multi_Currency' ) || class_exists( '\WCPay\MultiCurrency\MultiCurrency' ) ) {
				$mc_conflicts[] = 'WooPayments Multi-Currency';
			}
			if ( class_exists( 'WCML_Multi_Currency' ) ) {
				$mc_conflicts[] = 'WPML / WCML Multi-Currency';
			}
			if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) || class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
				$mc_conflicts[] = 'CURCY Multi Currency';
			}
			if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
				$mc_conflicts[] = 'Aelia Currency Switcher';
			}
			$has_smtp      = defined( 'WPMS_PLUGIN_VER' ) || defined( 'FLUENTMAIL' ) || defined( 'POST_SMTP_VER' ) || defined( 'EASY_WP_SMTP_VERSION' ) || class_exists( 'WP_Mail_SMTP' );
			$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
			$base_loc      = function_exists( 'wc_get_base_location' ) ? wc_get_base_location() : array( 'country' => '' );
			$base_country  = isset( $base_loc['country'] ) ? (string) $base_loc['country'] : '';
			$store_cur     = function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : 'USD';
			$taxes_enabled = function_exists( 'wc_tax_enabled' ) ? wc_tax_enabled() : false;
			$third_party   = array();
			if ( defined( 'CFW_VERSION' ) ) {
				$third_party[] = 'CheckoutWC';
			}
			if ( class_exists( 'WFACP_Core' ) ) {
				$third_party[] = 'FunnelKit';
			}
			if ( defined( 'CARTFLOWS_VER' ) ) {
				$third_party[] = 'CartFlows';
			}
			if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
				$third_party[] = 'Elementor Pro';
			}

			$ctx = array(
				'currency_symbol'               => $currency,
				'store_currency'                => $store_cur,
				'base_country'                  => $base_country,
				'taxes_enabled'                 => (bool) $taxes_enabled,
				'decimals'                      => $decimals,
				'wc_version'                    => $wc_version,
				'wc_blocks_supports_conditions' => version_compare( $wc_version, '9.9.0', '>=' ),
				'has_page_cache'                => (bool) $has_cache,
				'cache_plugins'                 => $cache_plugins,
				'mc_conflicts'                  => $mc_conflicts,
				'has_smtp_plugin'               => (bool) $has_smtp,
				'cron_disabled'                 => (bool) $cron_disabled,
				'third_party_checkout'          => $third_party,
				'sample_cart_amounts'           => $sample_cart_amounts,
				'product'                       => $sample_product,
				'product_counts'                => array(
					'published'    => $published_products,
					'downloadable' => $downloadable_count,
				),
				'last_order_id'                 => $last_order_id,
				'roles'                         => $roles_list,
				'store_defaults'                => array(
					'name'     => (string) $store_name,
					'address'  => $store_addr,
					'logo_url' => $logo_url,
				),
				'checkout_url'                  => function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : home_url( '/checkout/' ),
				'cart_url'                      => function_exists( 'wc_get_cart_url' ) ? (string) wc_get_cart_url() : home_url( '/cart/' ),
				'shop_url'                      => function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
				'shipping_zones_url'            => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
				'tax_settings_url'              => admin_url( 'admin.php?page=wc-settings&tab=tax' ),
				'orders_url'                    => ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() )
					? admin_url( 'admin.php?page=wc-orders' )
					: admin_url( 'edit.php?post_type=shop_order' ),
				'is_blocks_checkout'            => (bool) $is_blocks_chk,
				'is_blocks_cart'                => (bool) $is_blocks_crt,
				'is_block_theme'                => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			);

			set_transient( 'wcj_v10_store_ctx_v4', $ctx, 60 );
			return $ctx;
		}

		/**
		 * Load editor state, base_hashes (excluding volatile runtime counters), and revisions.
		 *
		 * @param string $slug              Editor slug.
		 * @param array  $context           Optional context params.
		 * @param bool   $include_revisions Whether to load revisions + base_hashes.
		 * @return array|WP_Error
		 */
		private static function load_editor_state( $slug, $context = array(), $include_revisions = true, $preview_mode = false ) {
			$editor_cls = WCJ_V10_Registry::get( $slug );
			if ( ! $editor_cls ) {
				return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
			}

			$store_ctx                 = self::get_store_sample_data();
			$store_ctx['preview_mode'] = (bool) $preview_mode;
			$data                      = $editor_cls::load_state( $context, $store_ctx );

			if ( $include_revisions ) {
				$owned               = $editor_cls::owned_options( $context );
				$volatile            = $editor_cls::volatile_options( $context );
				$hash_keys           = array_values( array_diff( $owned, $volatile ) );
				$data['base_hashes'] = WCJ_V10_Store::hashes_from_snapshot( WCJ_V10_Store::snapshot_options( $hash_keys ) );
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
		 * Format scalar or array option diff values into plain human-readable text.
		 *
		 * @param mixed $val Field value.
		 * @return string
		 */
		private static function stringify_diff_value( $val ) {
			if ( is_array( $val ) ) {
				$flat = array_map(
					static function ( $item ) {
						return is_scalar( $item ) ? wp_strip_all_tags( (string) $item ) : '';
					},
					$val
				);
				$flat = array_filter( $flat, static function ( $s ) { return '' !== $s; } );
				return empty( $flat ) ? __( '(Empty)', 'woocommerce-jetpack' ) : implode( ', ', $flat );
			}
			$str = (string) $val;
			return '' === $str ? __( '(Empty)', 'woocommerce-jetpack' ) : wp_strip_all_tags( $str );
		}

		/**
		 * Human-readable field diffs with goes_live + stored_on_orders grouping for Review & Publish modal.
		 *
		 * @param string $editor_cls Editor class name.
		 * @param array  $live       Live state.
		 * @param array  $draft      Sanitized draft state.
		 * @return array<int, array{key: string, label: string, from: string, to: string, summary: string, goes_live: bool, stored_on_orders: bool, impact: string}>
		 */
		private static function compute_diffs( $editor_cls, $live, $draft ) {
			$map    = $editor_cls::diff_labels();
			$impact = $editor_cls::impact_groups();
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
				} elseif ( is_array( $old ) || is_array( $new ) ) {
					if ( $old === $new ) {
						continue;
					}
					$from_str = self::stringify_diff_value( $old );
					$to_str   = self::stringify_diff_value( $new );
				} else {
					if ( (string) $old === (string) $new ) {
						continue;
					}
					$from_str = self::stringify_diff_value( $old );
					$to_str   = self::stringify_diff_value( $new );
				}
				$meta_imp = isset( $impact[ $key ] ) ? $impact[ $key ] : array();
				$diffs[]  = array(
					'key'              => $key,
					'label'            => $field_label,
					'from'             => $from_str,
					'to'               => $to_str,
					'summary'          => $field_label . ': ' . $from_str . ' -> ' . $to_str,
					'goes_live'        => ! empty( $meta_imp['goes_live'] ),
					'stored_on_orders' => ! empty( $meta_imp['stored_on_orders'] ),
					'sends_emails'     => ! empty( $meta_imp['sends_emails'] ),
					'impact'           => isset( $meta_imp['impact'] ) ? (string) $meta_imp['impact'] : '',
				);
			}
			return $diffs;
		}
	}

endif;
