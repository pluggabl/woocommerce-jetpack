<?php
/**
 * Booster True V10 - Unified REST API for Command Center, Previews, Publish & Undo.
 *
 * @package Booster_For_WooCommerce/admin/v10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_V10_REST' ) ) :

	/**
	 * Registers booster/v1 REST endpoints.
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

			$slug_pattern = '(?P<slug>payment_gateways_fees|pdf_invoicing|button_price_labels)';

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
			return rest_ensure_response(
				array(
					'edition'          => WCJ_V10_Store::edition(),
					'ui_mode'          => WCJ_V10_Bootstrap::is_v10_mode() ? 'v10' : 'classic',
					'categories'       => $catalog['categories'],
					'modules'          => $catalog['modules'],
					'flagship_editors' => array(
						array(
							'slug'        => 'payment_gateways_fees',
							'title'       => __( 'Payment Gateways Fees & Discounts', 'woocommerce-jetpack' ),
							'description' => __( 'Test exact checkout fee math with a live Before & After cart simulator.', 'woocommerce-jetpack' ),
							'engine'      => __( 'Cart Simulator', 'woocommerce-jetpack' ),
						),
						array(
							'slug'        => 'pdf_invoicing',
							'title'       => __( 'PDF Invoicing & Packing Slips', 'woocommerce-jetpack' ),
							'description' => __( 'Brand your invoices with logo, colors, business details, and live PDF preview.', 'woocommerce-jetpack' ),
							'engine'      => __( 'Document Preview', 'woocommerce-jetpack' ),
						),
						array(
							'slug'        => 'button_price_labels',
							'title'       => __( 'Button, Price & Sale Labels', 'woocommerce-jetpack' ),
							'description' => __( 'Customize Add to cart, Place order, price affixes, and sale badges with a live card preview.', 'woocommerce-jetpack' ),
							'engine'      => __( 'Storefront Card', 'woocommerce-jetpack' ),
						),
					),
				)
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
			$gateway = sanitize_key( (string) $request->get_param( 'gateway' ) );
			$data    = self::load_editor_state( $slug, $gateway );
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
			$slug    = $request->get_param( 'slug' );
			$params  = (array) $request->get_json_params();
			$draft   = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();
			$context = isset( $params['context'] ) && is_array( $params['context'] ) ? $params['context'] : array();
			$gateway = isset( $draft['gateway'] ) ? sanitize_key( (string) $draft['gateway'] ) : '';

			$editor = self::load_editor_state( $slug, $gateway );
			if ( is_wp_error( $editor ) ) {
				return $editor;
			}

			$clean_draft = self::sanitize_draft( $slug, $draft, $editor['live'] );
			$preview     = self::compute_preview( $slug, $editor['live'], $clean_draft, $context, $editor['meta'] );
			$diffs       = self::compute_diffs( $slug, $editor['live'], $clean_draft );

			return rest_ensure_response(
				array(
					'slug'   => $slug,
					'live'   => $editor['live'],
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
			$slug   = $request->get_param( 'slug' );
			$params = (array) $request->get_json_params();
			$draft  = isset( $params['draft'] ) && is_array( $params['draft'] ) ? $params['draft'] : array();

			$lock_key = 'wcj_v10_lock_' . sanitize_key( $slug );
			try {
				$mine = WCJ_V10_Store::lock( $lock_key, __( 'Another publish or undo is already running.', 'woocommerce-jetpack' ) );
			} catch ( RuntimeException $e ) {
				return new WP_Error( 'wcj_v10_locked', $e->getMessage(), array( 'status' => 409 ) );
			}

			try {
				$gateway = isset( $draft['gateway'] ) ? sanitize_key( (string) $draft['gateway'] ) : '';
				$editor  = self::load_editor_state( $slug, $gateway );
				if ( is_wp_error( $editor ) ) {
					return $editor;
				}

				$clean_draft = self::sanitize_draft( $slug, $draft, $editor['live'] );
				$diffs       = self::compute_diffs( $slug, $editor['live'], $clean_draft );
				if ( empty( $diffs ) ) {
					return rest_ensure_response(
						array(
							'published' => false,
							'message'   => __( 'No changes to publish.', 'woocommerce-jetpack' ),
							'live'      => $editor['live'],
							'revisions' => $editor['revisions'],
							'diffs'     => array(),
						)
					);
				}

				$option_writes = self::build_option_writes( $slug, $clean_draft );
				$option_keys   = array_keys( $option_writes );
				$before_states = WCJ_V10_Store::snapshot_options( $option_keys );
				$after_states  = array();

				foreach ( $option_writes as $opt_key => $new_val ) {
					$before_st = $before_states[ $opt_key ];
					$after_st  = WCJ_V10_Store::value_state( $new_val );
					if ( ! WCJ_V10_Store::compare_exchange( $opt_key, $before_st, $after_st ) ) {
						return new WP_Error(
							'wcj_v10_conflict',
							__( 'Concurrent change detected while publishing. Please reload and try again.', 'woocommerce-jetpack' ),
							array( 'status' => 409 )
						);
					}
					$after_states[ $opt_key ] = WCJ_V10_Store::read_state( $opt_key );
				}

				$summary  = implode( '; ', wp_list_pluck( $diffs, 'summary' ) );
				$revision = WCJ_V10_Store::record_revision( $slug, $summary, $before_states, $after_states );
				$updated  = self::load_editor_state( $slug, $gateway );

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
			$slug    = $request->get_param( 'slug' );
			$params  = (array) $request->get_json_params();
			$rev_id  = isset( $params['revision_id'] ) ? sanitize_text_field( (string) $params['revision_id'] ) : '';
			$gateway = isset( $params['gateway'] ) ? sanitize_key( (string) $params['gateway'] ) : '';

			try {
				WCJ_V10_Store::rollback_revision( $slug, $rev_id );
			} catch ( RuntimeException $e ) {
				$status = in_array( (int) $e->getCode(), array( 404, 409 ), true ) ? (int) $e->getCode() : 500;
				return new WP_Error( 'wcj_v10_rollback_error', $e->getMessage(), array( 'status' => $status ) );
			}

			$updated = self::load_editor_state( $slug, $gateway );
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
		 * Registered WooCommerce gateways list with clean fallback.
		 *
		 * @return array<int, array{id: string, title: string, enabled: bool}>
		 */
		private static function get_gateways_list() {
			$list = array();
			if ( function_exists( 'WC' ) && WC()->payment_gateways ) {
				$gateways = WC()->payment_gateways->payment_gateways();
				if ( is_array( $gateways ) ) {
					foreach ( $gateways as $id => $gw ) {
						$title  = is_object( $gw ) && method_exists( $gw, 'get_title' ) ? wp_strip_all_tags( (string) $gw->get_title() ) : $id;
						$list[] = array(
							'id'      => (string) $id,
							'title'   => '' !== $title ? $title : (string) $id,
							'enabled' => is_object( $gw ) && isset( $gw->enabled ) && 'yes' === $gw->enabled,
						);
					}
				}
			}
			if ( empty( $list ) ) {
				$list[] = array(
					'id'      => 'bacs',
					'title'   => __( 'Direct bank transfer', 'woocommerce-jetpack' ),
					'enabled' => true,
				);
			}
			return $list;
		}

		/**
		 * Read current live state, metadata, and revision summaries for an editor.
		 *
		 * @param string $slug    Editor slug.
		 * @param string $gateway Optional gateway ID for fees editor.
		 * @return array|WP_Error
		 */
		private static function load_editor_state( $slug, $gateway = '' ) {
			$edition   = WCJ_V10_Store::edition();
			$nonce     = wp_create_nonce( 'wcj-cat-nonce' );
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

			if ( 'payment_gateways_fees' === $slug ) {
				$gateways = self::get_gateways_list();
				$gw_ids   = wp_list_pluck( $gateways, 'id' );
				if ( '' === $gateway || ! in_array( $gateway, $gw_ids, true ) ) {
					$gateway = $gw_ids[0];
				}
				$texts    = (array) get_option( 'wcj_gateways_fees_text', array() );
				$types    = (array) get_option( 'wcj_gateways_fees_type', array() );
				$values   = (array) get_option( 'wcj_gateways_fees_value', array() );
				$mins     = (array) get_option( 'wcj_gateways_fees_min_cart_amount', array() );
				$maxs     = (array) get_option( 'wcj_gateways_fees_max_cart_amount', array() );
				$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
				$decimals = (int) get_option( 'woocommerce_price_num_decimals', 2 );

				return array(
					'slug'        => $slug,
					'edition'     => $edition,
					'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=payment_gateways&section=payment_gateways_fees&wcj-cat-nonce=' . $nonce ),
					'live'        => array(
						'enabled'  => 'yes' === get_option( 'wcj_payment_gateways_fees_enabled', 'no' ),
						'gateway'  => $gateway,
						'label'    => isset( $texts[ $gateway ] ) ? (string) $texts[ $gateway ] : '',
						'fee_type' => isset( $types[ $gateway ] ) && 'percent' === $types[ $gateway ] ? 'percent' : 'fixed',
						'amount'   => isset( $values[ $gateway ] ) ? (string) $values[ $gateway ] : '0',
						'min_cart' => isset( $mins[ $gateway ] ) ? (string) $mins[ $gateway ] : '0',
						'max_cart' => isset( $maxs[ $gateway ] ) ? (string) $maxs[ $gateway ] : '0',
					),
					'meta'        => array(
						'gateways'        => $gateways,
						'currency_symbol' => $currency,
						'decimals'        => $decimals,
					),
					'revisions'   => $revisions,
				);
			}

			if ( 'pdf_invoicing' === $slug ) {
				$emails   = (array) get_option( 'wcj_invoicing_invoice_attach_to_emails', array() );
				$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
				return array(
					'slug'        => $slug,
					'edition'     => $edition,
					'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=pdf_invoicing&section=pdf_invoicing&wcj-cat-nonce=' . $nonce ),
					'live'        => array(
						'enabled'           => 'yes' === get_option( 'wcj_pdf_invoicing_enabled', 'no' ),
						'header_enabled'    => 'yes' === get_option( 'wcj_invoicing_invoice_header_enabled', 'yes' ),
						'header_image'      => (string) get_option( 'wcj_invoicing_invoice_header_image', '' ),
						'header_title'      => (string) get_option( 'wcj_invoicing_invoice_header_title_text', __( 'Invoice', 'woocommerce-jetpack' ) ),
						'header_text'       => (string) get_option( 'wcj_invoicing_invoice_header_text', __( 'Company Name', 'woocommerce-jetpack' ) ),
						'header_text_color' => (string) get_option( 'wcj_invoicing_invoice_header_text_color', '#cccccc' ),
						'header_line_color' => (string) get_option( 'wcj_invoicing_invoice_header_line_color', '#cccccc' ),
						'numbering_prefix'  => (string) get_option( 'wcj_invoicing_invoice_numbering_prefix', '' ),
						'attach_completed'  => in_array( 'customer_completed_order', $emails, true ),
					),
					'meta'        => array(
						'currency_symbol' => $currency,
						'site_name'       => get_bloginfo( 'name' ),
					),
					'revisions'   => $revisions,
				);
			}

			if ( 'button_price_labels' === $slug ) {
				$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
				return array(
					'slug'        => $slug,
					'edition'     => $edition,
					'classic_url' => admin_url( 'admin.php?page=wcj-plugins&wcj-cat=labels&section=add_to_cart&wcj-cat-nonce=' . $nonce ),
					'live'        => array(
						'add_to_cart_enabled'       => 'yes' === get_option( 'wcj_add_to_cart_enabled', 'no' ),
						'add_to_cart_single'        => (string) get_option( 'wcj_add_to_cart_text_on_single_simple', __( 'Add to cart', 'woocommerce-jetpack' ) ),
						'add_to_cart_archive'       => (string) get_option( 'wcj_add_to_cart_text_on_archives_simple', __( 'Add to cart', 'woocommerce-jetpack' ) ),
						'more_button_enabled'       => 'yes' === get_option( 'wcj_more_button_labels_enabled', 'no' ),
						'place_order_text'          => (string) get_option( 'wcj_checkout_place_order_button_text', '' ),
						'price_labels_enabled'      => 'yes' === get_option( 'wcj_price_labels_enabled', 'no' ),
						'price_prefix'              => (string) get_option( 'wcj_global_price_labels_add_before_text', '' ),
						'price_suffix'              => (string) get_option( 'wcj_global_price_labels_add_after_text', '' ),
						'sale_flash_enabled'        => 'yes' === get_option( 'wcj_sale_flash_enabled', 'no' ),
						'sale_flash_global_enabled' => 'yes' === get_option( 'wcj_product_images_sale_flash_enabled', 'no' ),
						'sale_flash_html'           => (string) get_option( 'wcj_product_images_sale_flash_html', '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>' ),
					),
					'meta'        => array(
						'currency_symbol'     => $currency,
						'price_prefix_locked' => 'free' === $edition,
					),
					'revisions'   => $revisions,
				);
			}

			return new WP_Error( 'wcj_v10_unknown_editor', __( 'Unknown Booster editor.', 'woocommerce-jetpack' ), array( 'status' => 404 ) );
		}

		/**
		 * Strictly sanitize draft values per field schema and edition limits.
		 *
		 * @param string $slug  Editor slug.
		 * @param array  $draft Raw draft array.
		 * @param array  $live  Current live array.
		 * @return array
		 */
		private static function sanitize_draft( $slug, $draft, $live ) {
			if ( 'payment_gateways_fees' === $slug ) {
				$amount   = isset( $draft['amount'] ) && is_numeric( $draft['amount'] ) ? (string) round( (float) $draft['amount'], 4 ) : '0';
				$min_cart = isset( $draft['min_cart'] ) && is_numeric( $draft['min_cart'] ) ? (string) max( 0, round( (float) $draft['min_cart'], 2 ) ) : '0';
				$max_cart = isset( $draft['max_cart'] ) && is_numeric( $draft['max_cart'] ) ? (string) max( 0, round( (float) $draft['max_cart'], 2 ) ) : '0';

				return array(
					'enabled'  => ! empty( $draft['enabled'] ),
					'gateway'  => isset( $draft['gateway'] ) ? sanitize_key( (string) $draft['gateway'] ) : $live['gateway'],
					'label'    => isset( $draft['label'] ) ? sanitize_text_field( (string) $draft['label'] ) : '',
					'fee_type' => isset( $draft['fee_type'] ) && 'percent' === $draft['fee_type'] ? 'percent' : 'fixed',
					'amount'   => $amount,
					'min_cart' => $min_cart,
					'max_cart' => $max_cart,
				);
			}

			if ( 'pdf_invoicing' === $slug ) {
				$text_color = isset( $draft['header_text_color'] ) ? sanitize_hex_color( (string) $draft['header_text_color'] ) : '';
				$line_color = isset( $draft['header_line_color'] ) ? sanitize_hex_color( (string) $draft['header_line_color'] ) : '';
				$image_url  = isset( $draft['header_image'] ) ? esc_url_raw( trim( (string) $draft['header_image'] ) ) : '';

				return array(
					'enabled'           => ! empty( $draft['enabled'] ),
					'header_enabled'    => ! empty( $draft['header_enabled'] ),
					'header_image'      => $image_url,
					'header_title'      => isset( $draft['header_title'] ) ? sanitize_text_field( (string) $draft['header_title'] ) : '',
					'header_text'       => isset( $draft['header_text'] ) ? sanitize_textarea_field( (string) $draft['header_text'] ) : '',
					'header_text_color' => $text_color ? $text_color : '#cccccc',
					'header_line_color' => $line_color ? $line_color : '#cccccc',
					'numbering_prefix'  => isset( $draft['numbering_prefix'] ) ? sanitize_text_field( (string) $draft['numbering_prefix'] ) : '',
					'attach_completed'  => ! empty( $draft['attach_completed'] ),
				);
			}

			if ( 'button_price_labels' === $slug ) {
				$edition = WCJ_V10_Store::edition();
				return array(
					'add_to_cart_enabled'       => ! empty( $draft['add_to_cart_enabled'] ),
					'add_to_cart_single'        => isset( $draft['add_to_cart_single'] ) ? sanitize_text_field( (string) $draft['add_to_cart_single'] ) : '',
					'add_to_cart_archive'       => isset( $draft['add_to_cart_archive'] ) ? sanitize_text_field( (string) $draft['add_to_cart_archive'] ) : '',
					'more_button_enabled'       => ! empty( $draft['more_button_enabled'] ),
					'place_order_text'          => isset( $draft['place_order_text'] ) ? sanitize_text_field( (string) $draft['place_order_text'] ) : '',
					'price_labels_enabled'      => ! empty( $draft['price_labels_enabled'] ),
					'price_prefix'              => 'free' === $edition ? $live['price_prefix'] : ( isset( $draft['price_prefix'] ) ? wp_kses_post( (string) $draft['price_prefix'] ) : '' ),
					'price_suffix'              => isset( $draft['price_suffix'] ) ? wp_kses_post( (string) $draft['price_suffix'] ) : '',
					'sale_flash_enabled'        => ! empty( $draft['sale_flash_enabled'] ),
					'sale_flash_global_enabled' => ! empty( $draft['sale_flash_global_enabled'] ),
					'sale_flash_html'           => isset( $draft['sale_flash_html'] ) ? wp_kses_post( (string) $draft['sale_flash_html'] ) : '',
				);
			}

			return array();
		}

		/**
		 * Compute read-only Before & After preview projections in PHP.
		 *
		 * @param string $slug    Editor slug.
		 * @param array  $live    Live state.
		 * @param array  $draft   Sanitized draft state.
		 * @param array  $context Preview parameters (e.g. cart subtotal/shipping).
		 * @param array  $meta    Editor metadata.
		 * @return array{before: array, after: array}
		 */
		private static function compute_preview( $slug, $live, $draft, $context, $meta ) {
			if ( 'payment_gateways_fees' === $slug ) {
				$subtotal = isset( $context['subtotal'] ) && is_numeric( $context['subtotal'] ) ? max( 0.0, (float) $context['subtotal'] ) : 25.00;
				$shipping = isset( $context['shipping'] ) && is_numeric( $context['shipping'] ) ? max( 0.0, (float) $context['shipping'] ) : 10.00;
				$decimals = isset( $meta['decimals'] ) ? (int) $meta['decimals'] : 2;

				$calc = static function ( $cfg ) use ( $subtotal, $shipping, $decimals ) {
					$base_total = $subtotal + $shipping;
					$fee_amount = 0.0;
					$reason     = 'applied';

					if ( empty( $cfg['enabled'] ) ) {
						$reason = 'module_disabled';
					} elseif ( '' === trim( (string) $cfg['label'] ) ) {
						$reason = 'no_label';
					} else {
						$min = (float) $cfg['min_cart'];
						$max = (float) $cfg['max_cart'];
						if ( $base_total < $min || ( $max > 0 && $base_total > $max ) ) {
							$reason = 'outside_cart_range';
						} else {
							$val = (float) $cfg['amount'];
							if ( 'percent' === $cfg['fee_type'] ) {
								$fee_amount = round( $base_total * ( $val / 100.0 ), $decimals );
							} else {
								$fee_amount = round( $val, $decimals );
							}
						}
					}

					return array(
						'gateway'    => $cfg['gateway'],
						'subtotal'   => round( $subtotal, $decimals ),
						'shipping'   => round( $shipping, $decimals ),
						'fee_label'  => '' !== trim( (string) $cfg['label'] ) ? $cfg['label'] : __( 'Gateway Fee', 'woocommerce-jetpack' ),
						'fee_amount' => $fee_amount,
						'total'      => round( $base_total + $fee_amount, $decimals ),
						'reason'     => $reason,
					);
				};

				return array(
					'before' => $calc( $live ),
					'after'  => $calc( $draft ),
				);
			}

			if ( 'pdf_invoicing' === $slug ) {
				$project = static function ( $cfg ) {
					return array(
						'enabled'          => ! empty( $cfg['enabled'] ),
						'header_enabled'   => ! empty( $cfg['header_enabled'] ),
						'header_image'     => $cfg['header_image'],
						'header_title'     => '' !== trim( $cfg['header_title'] ) ? $cfg['header_title'] : __( 'Invoice', 'woocommerce-jetpack' ),
						'header_text'      => '' !== trim( $cfg['header_text'] ) ? $cfg['header_text'] : __( 'Company Name', 'woocommerce-jetpack' ),
						'header_text_color'=> $cfg['header_text_color'],
						'header_line_color'=> $cfg['header_line_color'],
						'invoice_number'   => $cfg['numbering_prefix'] . '1042',
						'email_status'     => ! empty( $cfg['attach_completed'] )
							? __( 'Attaches to Completed Order email', 'woocommerce-jetpack' )
							: __( 'Manual download only', 'woocommerce-jetpack' ),
					);
				};
				return array(
					'before' => $project( $live ),
					'after'  => $project( $draft ),
				);
			}

			if ( 'button_price_labels' === $slug ) {
				$project = static function ( $cfg ) use ( $meta ) {
					$sym = isset( $meta['currency_symbol'] ) ? $meta['currency_symbol'] : '$';
					return array(
						'add_to_cart_text' => ( ! empty( $cfg['add_to_cart_enabled'] ) && '' !== trim( $cfg['add_to_cart_single'] ) )
							? $cfg['add_to_cart_single']
							: __( 'Add to cart', 'woocommerce-jetpack' ),
						'archive_text'     => ( ! empty( $cfg['add_to_cart_enabled'] ) && '' !== trim( $cfg['add_to_cart_archive'] ) )
							? $cfg['add_to_cart_archive']
							: __( 'Add to cart', 'woocommerce-jetpack' ),
						'place_order_text' => ( ! empty( $cfg['more_button_enabled'] ) && '' !== trim( $cfg['place_order_text'] ) )
							? $cfg['place_order_text']
							: __( 'Place order', 'woocommerce-jetpack' ),
						'price_prefix'     => ! empty( $cfg['price_labels_enabled'] ) ? $cfg['price_prefix'] : '',
						'price_suffix'     => ! empty( $cfg['price_labels_enabled'] ) ? $cfg['price_suffix'] : '',
						'sample_regular'   => $sym . '95.00',
						'sample_sale'      => $sym . '76.00',
						'sale_badge_html'  => ( ! empty( $cfg['sale_flash_enabled'] ) && ! empty( $cfg['sale_flash_global_enabled'] ) && '' !== trim( $cfg['sale_flash_html'] ) )
							? $cfg['sale_flash_html']
							: '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>',
					);
				};
				return array(
					'before' => $project( $live ),
					'after'  => $project( $draft ),
				);
			}

			return array(
				'before' => array(),
				'after'  => array(),
			);
		}

		/**
		 * Human-readable field diffs for Review & Publish modal.
		 *
		 * @param string $slug  Editor slug.
		 * @param array  $live  Live state.
		 * @param array  $draft Sanitized draft state.
		 * @return array<int, array{key: string, label: string, from: string, to: string, summary: string}>
		 */
		private static function compute_diffs( $slug, $live, $draft ) {
			$labels = array(
				'payment_gateways_fees' => array(
					'enabled'  => __( 'Fees & Discounts Module', 'woocommerce-jetpack' ),
					'label'    => __( 'Fee Title', 'woocommerce-jetpack' ),
					'fee_type' => __( 'Fee Type', 'woocommerce-jetpack' ),
					'amount'   => __( 'Fee Amount', 'woocommerce-jetpack' ),
					'min_cart' => __( 'Minimum Cart Subtotal', 'woocommerce-jetpack' ),
					'max_cart' => __( 'Maximum Cart Subtotal', 'woocommerce-jetpack' ),
				),
				'pdf_invoicing'         => array(
					'enabled'           => __( 'PDF Invoicing Module', 'woocommerce-jetpack' ),
					'header_enabled'    => __( 'Invoice Header', 'woocommerce-jetpack' ),
					'header_image'      => __( 'Header Logo URL', 'woocommerce-jetpack' ),
					'header_title'      => __( 'Header Title', 'woocommerce-jetpack' ),
					'header_text'       => __( 'Business Header Text', 'woocommerce-jetpack' ),
					'header_text_color' => __( 'Header Text Color', 'woocommerce-jetpack' ),
					'header_line_color' => __( 'Header Line Color', 'woocommerce-jetpack' ),
					'numbering_prefix'  => __( 'Invoice Prefix', 'woocommerce-jetpack' ),
					'attach_completed'  => __( 'Attach to Completed Email', 'woocommerce-jetpack' ),
				),
				'button_price_labels'   => array(
					'add_to_cart_enabled'       => __( 'Add to Cart Labels Module', 'woocommerce-jetpack' ),
					'add_to_cart_single'        => __( 'Single Product Button Text', 'woocommerce-jetpack' ),
					'add_to_cart_archive'       => __( 'Shop/Archive Button Text', 'woocommerce-jetpack' ),
					'more_button_enabled'       => __( 'More Button Labels Module', 'woocommerce-jetpack' ),
					'place_order_text'          => __( 'Place Order Button Text', 'woocommerce-jetpack' ),
					'price_labels_enabled'      => __( 'Custom Price Labels Module', 'woocommerce-jetpack' ),
					'price_prefix'              => __( 'Text Before Price', 'woocommerce-jetpack' ),
					'price_suffix'              => __( 'Text After Price', 'woocommerce-jetpack' ),
					'sale_flash_enabled'        => __( 'Sale Flash Module', 'woocommerce-jetpack' ),
					'sale_flash_global_enabled' => __( 'Global Sale Badge Override', 'woocommerce-jetpack' ),
					'sale_flash_html'           => __( 'Sale Badge Text/HTML', 'woocommerce-jetpack' ),
				),
			);

			$map   = isset( $labels[ $slug ] ) ? $labels[ $slug ] : array();
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

		/**
		 * Map sanitized draft values back onto native wp_options keys.
		 *
		 * @param string $slug  Editor slug.
		 * @param array  $draft Sanitized draft.
		 * @return array<string, mixed>
		 */
		private static function build_option_writes( $slug, $draft ) {
			if ( 'payment_gateways_fees' === $slug ) {
				$gw     = $draft['gateway'];
				$texts  = (array) get_option( 'wcj_gateways_fees_text', array() );
				$types  = (array) get_option( 'wcj_gateways_fees_type', array() );
				$values = (array) get_option( 'wcj_gateways_fees_value', array() );
				$mins   = (array) get_option( 'wcj_gateways_fees_min_cart_amount', array() );
				$maxs   = (array) get_option( 'wcj_gateways_fees_max_cart_amount', array() );

				$texts[ $gw ]  = $draft['label'];
				$types[ $gw ]  = $draft['fee_type'];
				$values[ $gw ] = $draft['amount'];
				$mins[ $gw ]   = $draft['min_cart'];
				$maxs[ $gw ]   = $draft['max_cart'];

				return array(
					'wcj_payment_gateways_fees_enabled' => $draft['enabled'] ? 'yes' : 'no',
					'wcj_gateways_fees_text'            => $texts,
					'wcj_gateways_fees_type'            => $types,
					'wcj_gateways_fees_value'           => $values,
					'wcj_gateways_fees_min_cart_amount' => $mins,
					'wcj_gateways_fees_max_cart_amount' => $maxs,
				);
			}

			if ( 'pdf_invoicing' === $slug ) {
				$emails = array_values( array_filter( (array) get_option( 'wcj_invoicing_invoice_attach_to_emails', array() ), 'is_string' ) );
				if ( ! empty( $draft['attach_completed'] ) ) {
					if ( ! in_array( 'customer_completed_order', $emails, true ) ) {
						$emails[] = 'customer_completed_order';
					}
				} else {
					$emails = array_values( array_diff( $emails, array( 'customer_completed_order' ) ) );
				}

				return array(
					'wcj_pdf_invoicing_enabled'               => $draft['enabled'] ? 'yes' : 'no',
					'wcj_invoicing_invoice_header_enabled'    => $draft['header_enabled'] ? 'yes' : 'no',
					'wcj_invoicing_invoice_header_image'      => $draft['header_image'],
					'wcj_invoicing_invoice_header_title_text' => $draft['header_title'],
					'wcj_invoicing_invoice_header_text'       => $draft['header_text'],
					'wcj_invoicing_invoice_header_text_color' => $draft['header_text_color'],
					'wcj_invoicing_invoice_header_line_color' => $draft['header_line_color'],
					'wcj_invoicing_invoice_numbering_prefix'  => $draft['numbering_prefix'],
					'wcj_invoicing_invoice_attach_to_emails'  => $emails,
				);
			}

			if ( 'button_price_labels' === $slug ) {
				$writes = array(
					'wcj_add_to_cart_enabled'                 => $draft['add_to_cart_enabled'] ? 'yes' : 'no',
					'wcj_add_to_cart_text_enabled'            => $draft['add_to_cart_enabled'] ? 'yes' : 'no',
					'wcj_add_to_cart_text_on_single_simple'   => $draft['add_to_cart_single'],
					'wcj_add_to_cart_text_on_archives_simple' => $draft['add_to_cart_archive'],
					'wcj_more_button_labels_enabled'         => $draft['more_button_enabled'] ? 'yes' : 'no',
					'wcj_checkout_place_order_button_text'   => $draft['place_order_text'],
					'wcj_price_labels_enabled'               => $draft['price_labels_enabled'] ? 'yes' : 'no',
					'wcj_global_price_labels_add_after_text' => $draft['price_suffix'],
					'wcj_sale_flash_enabled'                 => $draft['sale_flash_enabled'] ? 'yes' : 'no',
					'wcj_product_images_sale_flash_enabled'  => $draft['sale_flash_global_enabled'] ? 'yes' : 'no',
					'wcj_product_images_sale_flash_html'     => $draft['sale_flash_html'],
				);
				if ( 'free' !== WCJ_V10_Store::edition() ) {
					$writes['wcj_global_price_labels_add_before_text'] = $draft['price_prefix'];
				}
				return $writes;
			}

			return array();
		}
	}

endif;
