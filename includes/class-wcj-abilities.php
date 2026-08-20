<?php
/**
 * Booster for WooCommerce - WordPress Abilities API integration.
 *
 * @version 8.4.0
 * @since   8.3.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Abilities' ) ) :
	/** Registers Booster's bounded, read-only Layer 1 abilities. */
	class WCJ_Abilities {

		/** Constructor. */
		public function __construct() {
			add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
			add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		}

		/** Registers the Booster category. */
		public function register_category() {
			if ( function_exists( 'wp_register_ability_category' ) ) {
				wp_register_ability_category(
					'booster-operations',
					array(
						'label'       => __( 'Booster Operations', 'woocommerce-jetpack' ),
						'description' => __( 'Read-only operational and compatibility status for Booster for WooCommerce.', 'woocommerce-jetpack' ),
					)
				);
			}
		}

		/** Registers three base abilities plus the Elite-only Order Health summary. */
		public function register_abilities() {
			if ( ! function_exists( 'wp_register_ability' ) ) {
				return;
			}
			$common = array(
				'category'            => 'booster-operations',
				'input_schema'        => $this->get_status_input_schema(),
				'permission_callback' => array( $this, 'check_permission' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'public'       => true,
					'mcp'          => array( 'public' => true ),
				),
			);

			wp_register_ability(
				'booster/module-status',
				array_merge(
					$common,
					array(
						'label'            => __( 'Booster module status', 'woocommerce-jetpack' ),
						'description'      => __( 'Returns bounded module availability, enabled state, and normalized configuration health without raw options.', 'woocommerce-jetpack' ),
						'execute_callback' => array( $this, 'execute_module_status' ),
						'output_schema'    => $this->get_module_output_schema(),
					)
				)
			);

			wp_register_ability(
				'booster/compatibility-status',
				array_merge(
					$common,
					array(
						'label'            => __( 'Booster compatibility status', 'woocommerce-jetpack' ),
						'description'      => __( 'Returns per-module HPOS, Checkout Blocks, Store API, and Classic Checkout classifications and safe guidance.', 'woocommerce-jetpack' ),
						'execute_callback' => array( $this, 'execute_compatibility_status' ),
						'output_schema'    => $this->get_compatibility_output_schema(),
					)
				)
			);

			wp_register_ability(
				'booster/background-jobs-status',
				array_merge(
					$common,
					array(
						'label'            => __( 'Booster background jobs status', 'woocommerce-jetpack' ),
						'description'      => __( 'Returns aggregate state for Booster-owned jobs without action arguments, record identifiers, or raw metadata.', 'woocommerce-jetpack' ),
						'execute_callback' => array( $this, 'execute_background_jobs_status' ),
						'output_schema'    => $this->get_background_jobs_output_schema(),
					)
				)
			);

			if ( class_exists( 'WCJ_Order_Health_Service' ) && ( new WCJ_Order_Health_Service() )->is_full_experience() ) {
				wp_register_ability(
					'booster/order-health-summary',
					array_merge(
						$common,
						array(
							'label'            => __( 'Booster Order Health summary', 'woocommerce-jetpack' ),
							'description'      => __( 'Returns bounded, aggregate Order Health and Morning Store Briefing counts without order IDs or customer data.', 'woocommerce-jetpack' ),
							'execute_callback' => array( $this, 'execute_order_health_summary' ),
							'output_schema'    => $this->get_order_health_output_schema(),
						)
					)
				);
			}
		}

		/** Permission is checked again immediately before every execution. */
		public function check_permission() {
			return current_user_can( 'manage_woocommerce' ) ? true : new WP_Error( 'booster_forbidden', __( 'You do not have permission to view Booster operational status.', 'woocommerce-jetpack' ) );
		}

		/** Executes module status. */
		public function execute_module_status() {
			$permission = $this->check_permission(); // phpcs:ignore
			return is_wp_error( $permission ) ? $permission : $this->service()->get_module_status();
		}

		/** Executes compatibility status. */
		public function execute_compatibility_status() {
			$permission = $this->check_permission();
			return is_wp_error( $permission ) ? $permission : $this->service()->get_compatibility_status();
		}

		/** Executes background job status. */
		public function execute_background_jobs_status() {
			$permission = $this->check_permission();
			return is_wp_error( $permission ) ? $permission : $this->service()->get_background_jobs_status();
		}

		/** Executes the bounded, privacy-safe Order Health summary. */
		public function execute_order_health_summary() {
			$permission = $this->check_permission();
			if ( is_wp_error( $permission ) ) {
				return $permission;
			}
			$service = new WCJ_Order_Health_Service();
			return $service->is_full_experience() ? $service->get_order_health_summary() : new WP_Error( 'booster_elite_required', __( 'The Order Health summary Ability is available in Booster Elite.', 'woocommerce-jetpack' ) );
		}

		/** Returns a fresh service so no request/user state is persisted. */
		private function service() {
			return new WCJ_Status_Service();
		}

		/** Strict status-summary input contract. */
		private function get_status_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'scope' => array(
						'type'        => 'string',
						'enum'        => array( 'summary' ),
						'description' => 'Requests the bounded site-level status summary.',
					),
				),
				'required'             => array( 'scope' ),
				'additionalProperties' => false,
			);
		}

		/**
		 * Common schema for a safe string.
		 *
		 * @param int $max Maximum length of the string.
		 */
		private function string_schema( $max = 240 ) {
			return array(
				'type'      => 'string',
				'maxLength' => $max,
			);
		}

		/** Module status output schema. */
		private function get_module_output_schema() {
			$item = array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'id'            => $this->string_schema( 80 ),
					'label'         => $this->string_schema( 120 ),
					'tier'          => array(
						'type' => 'string',
						'enum' => array( 'free', 'plus', 'elite' ),
					),
					'available'     => array( 'type' => 'boolean' ),
					'enabled'       => array( 'type' => 'boolean' ),
					'health'        => array(
						'type' => 'string',
						'enum' => array( 'ok', 'warning', 'disabled' ),
					),
					'warning_codes' => array(
						'type'     => 'array',
						'maxItems' => 4,
						'items'    => $this->string_schema( 64 ),
					),
				),
				'required'             => array( 'id', 'label', 'tier', 'available', 'enabled', 'health', 'warning_codes' ),
			);
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'tier'    => array(
						'type' => 'string',
						'enum' => array( 'free', 'plus', 'elite' ),
					),
					'count'   => array(
						'type'    => 'integer',
						'minimum' => 0,
						'maximum' => WCJ_Status_Service::MAX_MODULES,
					),
					'modules' => array(
						'type'     => 'array',
						'maxItems' => WCJ_Status_Service::MAX_MODULES,
						'items'    => $item,
					),
				),
				'required'             => array( 'tier', 'count', 'modules' ),
			);
		}

		/** Compatibility output schema. */
		private function get_compatibility_output_schema() {
			$checkout = array( 'supported', 'partial', 'classic-only', 'not-a-checkout-concern' );
			$item     = array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'id'               => $this->string_schema( 80 ),
					'label'            => $this->string_schema( 120 ),
					'enabled'          => array( 'type' => 'boolean' ),
					'hpos'             => array(
						'type' => 'string',
						'enum' => array( 'supported', 'partial', 'not-an-order-concern' ),
					),
					'checkout_blocks'  => array(
						'type' => 'string',
						'enum' => $checkout,
					),
					'store_api'        => array(
						'type' => 'string',
						'enum' => $checkout,
					),
					'classic_checkout' => array(
						'type' => 'string',
						'enum' => array( 'supported', 'not-a-checkout-concern' ),
					),
					'boundary'         => $this->string_schema( 320 ),
					'guidance_code'    => $this->string_schema( 80 ),
				),
				'required'             => array( 'id', 'label', 'enabled', 'hpos', 'checkout_blocks', 'store_api', 'classic_checkout', 'boundary', 'guidance_code' ),
			);
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'tier'         => array(
						'type' => 'string',
						'enum' => array( 'free', 'plus', 'elite' ),
					),
					'hpos_enabled' => array( 'type' => 'boolean' ),
					'count'        => array(
						'type'    => 'integer',
						'minimum' => 0,
						'maximum' => WCJ_Status_Service::MAX_MODULES,
					),
					'modules'      => array(
						'type'     => 'array',
						'maxItems' => WCJ_Status_Service::MAX_MODULES,
						'items'    => $item,
					),
				),
				'required'             => array( 'tier', 'hpos_enabled', 'count', 'modules' ),
			);
		}

		/** Background job output schema. */
		private function get_background_jobs_output_schema() {
			$count_properties = array();
			foreach ( array( 'pending', 'overdue', 'failed', 'recent_success' ) as $key ) {
				$count_properties[ $key ] = array(
					'type'    => 'integer',
					'minimum' => 0,
					'maximum' => 100000,
				);
			}
			$bucket_properties = array();
			foreach ( array( 'under_1h', 'from_1h_to_24h', 'from_1d_to_7d', 'over_7d' ) as $key ) {
				$bucket_properties[ $key ] = array(
					'type'    => 'integer',
					'minimum' => 0,
					'maximum' => 100000,
				);
			}
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'counts'            => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => $count_properties,
						'required'             => array_keys( $count_properties ),
					),
					'overdue_buckets'   => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => $bucket_properties,
						'required'             => array_keys( $bucket_properties ),
					),
					'diagnostic_codes'  => array(
						'type'     => 'array',
						'maxItems' => 4,
						'items'    => $this->string_schema( 64 ),
					),
					'history_available' => array( 'type' => 'boolean' ),
				),
				'required'             => array( 'counts', 'overdue_buckets', 'diagnostic_codes', 'history_available' ),
			);
		}

		/** Order Health and Morning Store Briefing output schema. */
		private function get_order_health_output_schema() {
			$count_schema = array(
				'type'    => 'integer',
				'minimum' => 0,
				'maximum' => WCJ_Order_Health_Service::MAX_QUERY_ORDERS,
			);
			$fixed_counts = function ( $keys ) use ( $count_schema ) {
				$properties = array();
				foreach ( $keys as $key ) {
					$properties[ $key ] = $count_schema;
				}
				return array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => $properties,
					'required'             => array_keys( $properties ),
				);
			};
			$state_schema = array(
				'type' => 'string',
				'enum' => array( 'clear', 'review', 'action-needed' ),
			);
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'tier'             => array(
						'type' => 'string',
						'enum' => array( 'free', 'plus', 'elite' ),
					),
					'generated_at_gmt' => $this->string_schema( 40 ),
					'storage_mode'     => array(
						'type' => 'string',
						'enum' => array( 'hpos', 'legacy' ),
					),
					'bounded_query'    => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'query_limit'     => $count_schema,
							'candidate_count' => $count_schema,
							'attention_count' => $count_schema,
							'has_more'        => array( 'type' => 'boolean' ),
						),
						'required'             => array( 'query_limit', 'candidate_count', 'attention_count', 'has_more' ),
					),
					'age_buckets'      => $fixed_counts( WCJ_Order_Health_Service::AGE_BUCKETS ),
					'cause_counts'     => $fixed_counts( WCJ_Order_Health_Service::CAUSES ),
					'reason_counts'    => $fixed_counts( WCJ_Order_Health_Service::REASONS ),
					'oldest_age_days'  => array(
						'type'    => 'integer',
						'minimum' => 0,
						'maximum' => 36500,
					),
					'morning_briefing' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'privacy'         => array(
								'type' => 'string',
								'enum' => array( 'aggregate-only' ),
							),
							'order_health'    => array(
								'type'                 => 'object',
								'additionalProperties' => false,
								'properties'           => array(
									'state'           => $state_schema,
									'attention_count' => $count_schema,
									'truncated'       => array( 'type' => 'boolean' ),
								),
								'required'             => array( 'state', 'attention_count', 'truncated' ),
							),
							'compatibility'   => array(
								'type'                 => 'object',
								'additionalProperties' => false,
								'properties'           => array(
									'state'         => $state_schema,
									'warning_count' => array(
										'type'    => 'integer',
										'minimum' => 0,
										'maximum' => WCJ_Status_Service::MAX_MODULES,
									),
								),
								'required'             => array( 'state', 'warning_count' ),
							),
							'background_jobs' => array(
								'type'                 => 'object',
								'additionalProperties' => false,
								'properties'           => array(
									'state'            => $state_schema,
									'overdue'          => array(
										'type'    => 'integer',
										'minimum' => 0,
										'maximum' => 100000,
									),
									'failed'           => array(
										'type'    => 'integer',
										'minimum' => 0,
										'maximum' => 100000,
									),
									'diagnostic_codes' => array(
										'type'     => 'array',
										'maxItems' => 4,
										'items'    => $this->string_schema( 64 ),
									),
								),
								'required'             => array( 'state', 'overdue', 'failed', 'diagnostic_codes' ),
							),
							'guidance_codes'  => array(
								'type'     => 'array',
								'maxItems' => 4,
								'items'    => $this->string_schema( 64 ),
							),
						),
						'required'             => array( 'privacy', 'order_health', 'compatibility', 'background_jobs', 'guidance_codes' ),
					),
				),
				'required'             => array( 'tier', 'generated_at_gmt', 'storage_mode', 'bounded_query', 'age_buckets', 'cause_counts', 'reason_counts', 'oldest_age_days', 'morning_briefing' ),
			);
		}
	}
endif;

new WCJ_Abilities();
