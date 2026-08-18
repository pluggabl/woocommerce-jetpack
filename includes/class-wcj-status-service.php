<?php
/**
 * Booster for WooCommerce - normalized, read-only operational status service.
 *
 * @version 8.3.0
 * @since   8.3.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Status_Service' ) ) :
	/**
	 * Produces bounded status data shared by the admin UI and Abilities API.
	 */
	class WCJ_Status_Service {

		/** Maximum number of modules exposed in a status response. */
		const MAX_MODULES = 160;

		/**
		 * Returns the installed Booster tier.
		 *
		 * @return string
		 */
		public function get_tier() {
			$plugin_file = defined( 'WCJ_PLUGIN_FILE' ) ? WCJ_PLUGIN_FILE : ( defined( 'WCJ_FREE_PLUGIN_FILE' ) ? WCJ_FREE_PLUGIN_FILE : '' );
			$basename    = basename( $plugin_file );
			if ( false !== strpos( $basename, 'elite' ) ) {
				return 'elite';
			}
			if ( false !== strpos( $basename, 'plus' ) ) {
				return 'plus';
			}
			return 'free';
		}

		/**
		 * Returns safe module availability, enabled state, and configuration health.
		 *
		 * @return array
		 */
		public function get_module_status() {
			$items   = array();
			$booster = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
			$modules = ( $booster && isset( $booster->all_modules ) && is_array( $booster->all_modules ) ) ? $booster->all_modules : array();
			foreach ( array_slice( $modules, 0, self::MAX_MODULES, true ) as $key => $module ) {
				$id = isset( $module->id ) ? sanitize_key( $module->id ) : sanitize_key( $key );
				if ( '' === $id ) {
					continue;
				}
				$label    = isset( $module->short_desc ) ? wp_strip_all_tags( $module->short_desc ) : $id;
				$enabled  = method_exists( $module, 'is_enabled' ) ? (bool) $module->is_enabled() : ( function_exists( 'wcj_is_module_enabled' ) && wcj_is_module_enabled( $id ) );
				$compat   = $this->get_compatibility_definition( $id );
				$warnings = array();
				if ( $enabled && in_array( $compat['checkout_blocks'], array( 'partial', 'classic-only' ), true ) ) {
					$warnings[] = 'checkout-boundary';
				}
				if ( $enabled && 'partial' === $compat['hpos'] ) {
					$warnings[] = 'hpos-staging-required';
				}
				$items[] = array(
					'id'            => $id,
					'label'         => wp_html_excerpt( $label, 120, '' ),
					'tier'          => $this->get_tier(),
					'available'     => true,
					'enabled'       => $enabled,
					'health'        => ! $enabled ? 'disabled' : ( empty( $warnings ) ? 'ok' : 'warning' ),
					'warning_codes' => $warnings,
				);
			}
			return array(
				'tier'    => $this->get_tier(),
				'count'   => count( $items ),
				'modules' => $items,
			);
		}

		/**
		 * Returns module-specific compatibility classifications.
		 *
		 * @return array
		 */
		public function get_compatibility_status() {
			$module_status = $this->get_module_status();
			$items         = array();
			foreach ( $module_status['modules'] as $module ) {
				$definition = $this->get_compatibility_definition( $module['id'] );
				$items[]    = array_merge(
					array(
						'id'      => $module['id'],
						'label'   => $module['label'],
						'enabled' => $module['enabled'],
					),
					$definition
				);
			}
			return array(
				'tier'         => $this->get_tier(),
				'hpos_enabled' => function_exists( 'wcj_is_hpos_enabled' ) && wcj_is_hpos_enabled(),
				'count'        => count( $items ),
				'modules'      => $items,
			);
		}

		/**
		 * Returns only aggregate state for known Booster-owned scheduled jobs.
		 *
		 * @return array
		 */
		public function get_background_jobs_status() {
			$now      = time();
			$counts   = array(
				'pending'        => 0,
				'overdue'        => 0,
				'failed'         => 0,
				'recent_success' => 0,
			);
			$buckets  = array(
				'under_1h'       => 0,
				'from_1h_to_24h' => 0,
				'from_1d_to_7d'  => 0,
				'over_7d'        => 0,
			);
			$cron     = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
			$hook_set = array();
			foreach ( (array) $cron as $timestamp => $hooks ) {
				foreach ( array_keys( (array) $hooks ) as $hook ) {
					if ( ! $this->is_booster_job_hook( $hook ) ) {
						continue;
					}
					$hook_set[ $hook ] = true;
					$instances         = isset( $hooks[ $hook ] ) ? count( (array) $hooks[ $hook ] ) : 0;
					if ( (int) $timestamp < $now - 300 ) {
						$counts['overdue'] += $instances;
						$this->add_age_bucket( $buckets, $now - (int) $timestamp, $instances );
					} else {
						$counts['pending'] += $instances;
					}
				}
			}

			// Action Scheduler is queried only for known Booster hooks; action arguments and IDs are never read or returned.
			if ( function_exists( 'as_get_scheduled_actions' ) ) {
				foreach ( array_keys( $hook_set ) as $hook ) {
					$counts['failed']         += count(
						(array) as_get_scheduled_actions(
							array(
								'hook'     => $hook,
								'status'   => 'failed',
								'per_page' => 100,
							),
							'ids'
						)
					);
					$counts['recent_success'] += count(
						(array) as_get_scheduled_actions(
							array(
								'hook'         => $hook,
								'status'       => 'complete',
								'date'         => $now - DAY_IN_SECONDS,
								'date_compare' => '>=',
								'per_page'     => 100,
							),
							'ids'
						)
					);
				}
			}

			$codes = array();
			if ( 0 === array_sum( $counts ) ) {
				$codes[] = 'no-booster-jobs';
			}
			if ( $counts['overdue'] > 0 ) {
				$codes[] = 'overdue-jobs';
			}
			if ( $counts['failed'] > 0 ) {
				$codes[] = 'failed-jobs';
			}
			if ( empty( $codes ) ) {
				$codes[] = 'healthy';
			}

			return array(
				'counts'            => $counts,
				'overdue_buckets'   => $buckets,
				'diagnostic_codes'  => $codes,
				'history_available' => function_exists( 'as_get_scheduled_actions' ),
			);
		}

		/** Adds an aggregate age to a fixed bucket.
		 *
		 * @param array $buckets Reference to the age buckets.
		 * @param int   $age     The age value.
		 * @param int   $count   The count to add to the bucket.
		 */
		private function add_age_bucket( &$buckets, $age, $count ) {
			if ( $age < HOUR_IN_SECONDS ) {
				$buckets['under_1h'] += $count;
			} elseif ( $age < DAY_IN_SECONDS ) {
				$buckets['from_1h_to_24h'] += $count;
			} elseif ( $age < 7 * DAY_IN_SECONDS ) {
				$buckets['from_1d_to_7d'] += $count;
			} else {
				$buckets['over_7d'] += $count;
			}
		}

		/** Returns true only for known Booster-owned job hook families. */
		private function is_booster_job_hook( $hook ) {
			$exact = array(
				'wcj_cart_abandonment_update_order_status_action',
				'wcj_bulk_regenerate_download_permissions_all_orders_cron',
				'wcj_track_users_generate_stats',
				'wcj_download_tcpdf_fonts_hook',
			);
			if ( in_array( $hook, $exact, true ) ) {
				return true;
			}
			foreach ( array( 'wcj_create_products_xml_hook_', 'wcj_update_exchange_rates_hook_', 'wp_wcj_bkg_process_' ) as $prefix ) {
				if ( 0 === strpos( $hook, $prefix ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Returns a normalized compatibility contract for a module.
		 *
		 * @param string $id Module identifier.
		 * @return array
		 */
		private function get_compatibility_definition( $id ) {
			$default = array(
				'hpos'             => 'not-an-order-concern',
				'checkout_blocks'  => 'not-a-checkout-concern',
				'store_api'        => 'not-a-checkout-concern',
				'classic_checkout' => 'not-a-checkout-concern',
				'boundary'         => 'This module does not participate directly in order storage or checkout processing.',
				'guidance_code'    => 'not-applicable',
			);
			$known   = array(
				'checkout_fees'                 => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'partial',
					'store_api'        => 'partial',
					'classic_checkout' => 'supported',
					'boundary'         => 'Simple cart fees are server-supported. Checkout-field-conditional fees require Classic Checkout.',
					'guidance_code'    => 'classic-for-field-conditions',
				),
				'checkout_custom_fields'        => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'partial',
					'store_api'        => 'partial',
					'classic_checkout' => 'supported',
					'boundary'         => 'Supported field types use the Store API. Unsupported field types and Classic placement hooks remain Classic-only.',
					'guidance_code'    => 'stage-exact-field-types',
				),
				'checkout_files_upload'         => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'classic-only',
					'store_api'        => 'classic-only',
					'classic_checkout' => 'supported',
					'boundary'         => 'Checkout file input requires Classic Checkout; post-order account and order-association paths remain available.',
					'guidance_code'    => 'classic-checkout-required',
				),
				'checkout_custom_info'          => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'classic-only',
					'store_api'        => 'classic-only',
					'classic_checkout' => 'supported',
					'boundary'         => 'Classic placement hooks have no supported Checkout block equivalent.',
					'guidance_code'    => 'classic-checkout-required',
				),
				'more_button_labels'            => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'classic-only',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'supported',
					'boundary'         => 'Classic button-label filters do not alter the Checkout block submit control.',
					'guidance_code'    => 'classic-checkout-required',
				),
				'product_addons'                => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'supported',
					'store_api'        => 'supported',
					'classic_checkout' => 'supported',
					'boundary'         => 'Product-page addon data uses shared cart and order-item server paths for both checkout architectures.',
					'guidance_code'    => 'test-product-configurations',
				),
				'product_input_fields'          => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'supported',
					'store_api'        => 'supported',
					'classic_checkout' => 'supported',
					'boundary'         => 'Product-page values travel with cart items through shared order-item server paths.',
					'guidance_code'    => 'test-product-configurations',
				),
				'payment_gateways_fees'         => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'supported',
					'store_api'        => 'supported',
					'classic_checkout' => 'supported',
					'boundary'         => 'Uses the chosen-payment session and WooCommerce cart fee API.',
					'guidance_code'    => 'test-recalculation',
				),
				'payment_gateways_per_category' => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'supported',
					'store_api'        => 'supported',
					'classic_checkout' => 'supported',
					'boundary'         => 'Gateway rules execute on the shared server filter for Store API, Classic Checkout, and order-pay.',
					'guidance_code'    => 'test-matching-carts',
				),
				'orders'                        => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'not-a-checkout-concern',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'not-a-checkout-concern',
					'boundary'         => 'Audited bulk and scheduled download-permission regeneration uses the active WooCommerce order data store.',
					'guidance_code'    => 'test-bulk-and-scheduled-work',
				),
				'order_numbers'                 => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'not-a-checkout-concern',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'not-a-checkout-concern',
					'boundary'         => 'Maintained creation, display, search, and renumeration paths use WooCommerce order APIs.',
					'guidance_code'    => 'stage-renumeration',
				),
				'pdf_invoicing'                 => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'not-a-checkout-concern',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'not-a-checkout-concern',
					'boundary'         => 'Maintained document metadata, owner checks, numbering, and report fields use WooCommerce order APIs.',
					'guidance_code'    => 'test-documents-and-resend',
				),
				'export'                        => array(
					'hpos'             => 'supported',
					'checkout_blocks'  => 'not-a-checkout-concern',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'not-a-checkout-concern',
					'boundary'         => 'Maintained order export paths use bounded WooCommerce order queries and order objects under HPOS.',
					'guidance_code'    => 'test-export-fields-and-batches',
				),
				'checkout_core_fields'          => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'classic-only',
					'store_api'        => 'classic-only',
					'classic_checkout' => 'supported',
					'boundary'         => 'Classic checkout field filters do not provide a supported equivalent for Checkout Blocks core-field controls.',
					'guidance_code'    => 'classic-checkout-required',
				),
				'checkout_customization'        => array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'classic-only',
					'store_api'        => 'classic-only',
					'classic_checkout' => 'supported',
					'boundary'         => 'Classic checkout layout hooks do not have supported Checkout block placement equivalents.',
					'guidance_code'    => 'classic-checkout-required',
				),
			);
			if ( isset( $known[ $id ] ) ) {
				return $known[ $id ];
			}
			if ( 0 === strpos( $id, 'payment_gateways_' ) || 'eu_vat_number' === $id || 'payment_gateways' === $id ) {
				return array(
					'hpos'             => 'not-an-order-concern',
					'checkout_blocks'  => 'partial',
					'store_api'        => 'partial',
					'classic_checkout' => 'supported',
					'boundary'         => 'Server filters apply, but the exact customer, shipping, currency, or total-dependent flow requires staging verification.',
					'guidance_code'    => 'stage-exact-checkout-flow',
				);
			}
			if ( false !== strpos( $id, 'order' ) || false !== strpos( $id, 'report' ) ) {
				return array(
					'hpos'             => 'partial',
					'checkout_blocks'  => 'not-a-checkout-concern',
					'store_api'        => 'not-a-checkout-concern',
					'classic_checkout' => 'not-a-checkout-concern',
					'boundary'         => 'This order-facing module has paths outside the maintained 8.3 audit and requires exact-workflow testing with both order stores.',
					'guidance_code'    => 'stage-exact-order-workflow',
				);
			}
			return $default;
		}
	}
endif;
