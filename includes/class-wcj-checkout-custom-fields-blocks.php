<?php
/**
 * Booster for WooCommerce - Checkout Custom Fields - WooCommerce Blocks Integration
 *
 * @version 8.1.0
 * @since   7.12.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Checkout_Custom_Fields_Blocks' ) ) :

	/**
	 * Checkout Custom Fields integration for the WooCommerce Checkout block.
	 *
	 * Supported field types are text, select, checkbox, and radio (rendered as a
	 * select). Conditioned fields require WooCommerce 9.9 or later. Earlier
	 * versions skip conditioned fields instead of displaying them incorrectly.
	 *
	 * @version 8.1.0
	 * @since   7.12.0
	 */
	class WCJ_Checkout_Custom_Fields_Blocks {

		/** Blocks field namespace. */
		const FIELD_NAMESPACE = 'booster-wcj';

		/** Store API extension namespace. */
		const STORE_API_NAMESPACE = 'booster-wcj';

		/** Minimum WooCommerce version with conditional additional fields. */
		const CONDITIONAL_FIELDS_MIN_VERSION = '9.9.0';

		/** @var int Total configured fields for the current tier. */
		private $total_fields;

		/** @var array Types supported by the Additional Checkout Fields API. */
		private $supported_types = array( 'text', 'select', 'checkbox', 'radio' );

		/** @var array|null Request-scoped field configuration. */
		private $field_config_cache = null;

		/** @var array|null Request-scoped cart product IDs. */
		private $cart_product_ids_cache = null;

		/** @var array|null Request-scoped cart category IDs. */
		private $cart_category_ids_cache = null;

		/**
		 * Constructor.
		 *
		 * @version 8.1.0
		 * @since   7.12.0
		 * @param int $total_fields Total configured fields for the current tier.
		 */
		public function __construct( $total_fields ) {
			$this->total_fields = max( 0, (int) $total_fields );

			add_action( 'woocommerce_init', array( $this, 'register_blocks_checkout_fields' ) );
			add_action( 'woocommerce_set_additional_field_value', array( $this, 'bridge_additional_field_value' ), 10, 4 );

			if ( did_action( 'woocommerce_blocks_loaded' ) ) {
				$this->register_store_api_data();
			} else {
				add_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api_data' ) );
			}
		}

		/**
		 * Whether the Additional Checkout Fields API is available.
		 *
		 * @return bool
		 */
		private function is_blocks_api_available() {
			return function_exists( 'woocommerce_register_additional_checkout_field' );
		}

		/**
		 * Whether conditional additional fields are supported.
		 *
		 * @return bool
		 */
		private function supports_conditional_fields() {
			return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::CONDITIONAL_FIELDS_MIN_VERSION, '>=' );
		}

		/**
		 * Get a Blocks field ID.
		 *
		 * @param int $field_index Booster field index.
		 * @return string
		 */
		private function get_blocks_field_id( $field_index ) {
			return self::FIELD_NAMESPACE . '/checkout-field-' . absint( $field_index );
		}

		/**
		 * Normalize an option containing IDs.
		 *
		 * @param mixed $value Option value.
		 * @return array
		 */
		private function normalize_id_list( $value ) {
			if ( ! is_array( $value ) ) {
				$value = '' === $value || null === $value ? array() : array( $value );
			}
			return array_values( array_unique( array_filter( array_map( 'strval', $value ), 'strlen' ) ) );
		}

		/**
		 * Load all supported field configuration once per request.
		 *
		 * @return array
		 */
		private function get_all_field_configs() {
			if ( null !== $this->field_config_cache ) {
				return $this->field_config_cache;
			}

			$this->field_config_cache = array();
			for ( $i = 1; $i <= $this->total_fields; $i++ ) {
				if ( 'yes' !== wcj_get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {
					continue;
				}

				$type = wcj_get_option( 'wcj_checkout_custom_field_type_' . $i, 'text' );
				if ( ! in_array( $type, $this->supported_types, true ) ) {
					continue;
				}

				$config = array(
					'type'           => $type,
					'label'          => wcj_get_option( 'wcj_checkout_custom_field_label_' . $i, '' ),
					'required'       => 'yes' === wcj_get_option( 'wcj_checkout_custom_field_required_' . $i, 'no' ),
					'section'        => wcj_get_option( 'wcj_checkout_custom_field_section_' . $i, 'billing' ),
					'select_options' => wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' ),
					'categories_ex'  => $this->normalize_id_list( wcj_get_option( 'wcj_checkout_custom_field_categories_ex_' . $i, array() ) ),
					'categories_in'  => $this->normalize_id_list( wcj_get_option( 'wcj_checkout_custom_field_categories_in_' . $i, array() ) ),
					'products_ex'    => $this->normalize_id_list( wcj_get_option( 'wcj_checkout_custom_field_products_ex_' . $i, array() ) ),
					'products_in'    => $this->normalize_id_list( wcj_get_option( 'wcj_checkout_custom_field_products_in_' . $i, array() ) ),
					'min_cart'       => (float) wcj_get_option( 'wcj_checkout_custom_field_min_cart_amount_' . $i, 0 ),
					'max_cart'       => (float) wcj_get_option( 'wcj_checkout_custom_field_max_cart_amount_' . $i, 0 ),
				);
				$config['conditioned'] = ! empty( $config['categories_ex'] ) || ! empty( $config['categories_in'] ) || ! empty( $config['products_ex'] ) || ! empty( $config['products_in'] ) || $config['min_cart'] > 0 || $config['max_cart'] > 0;

				$this->field_config_cache[ $i ] = $config;
			}

			return $this->field_config_cache;
		}

		/**
		 * Register contextual field visibility on the Cart Store API schema.
		 */
		public function register_store_api_data() {
			if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! class_exists( '\\Automattic\\WooCommerce\\StoreApi\\Schemas\\V1\\CartSchema' ) ) {
				return;
			}

			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
					'namespace'       => self::STORE_API_NAMESPACE,
					'data_callback'   => array( $this, 'get_store_api_data' ),
					'schema_callback' => array( $this, 'get_store_api_schema' ),
					'schema_type'     => ARRAY_A,
				)
			);
		}

		/**
		 * Store API data used by Checkout block JSON Schema conditions.
		 *
		 * @return array
		 */
		public function get_store_api_data() {
			$visibility = array();
			foreach ( $this->get_all_field_configs() as $i => $config ) {
				$visibility[ 'field_' . $i ] = $this->is_field_visible( $i, $config );
			}
			return array( 'checkout_field_visibility' => $visibility );
		}

		/**
		 * Schema for contextual Checkout field visibility.
		 *
		 * @return array
		 */
		public function get_store_api_schema() {
			$properties = array();
			foreach ( $this->get_all_field_configs() as $i => $config ) {
				$properties[ 'field_' . $i ] = array(
					'description' => __( 'Whether this Booster checkout field is visible for the current cart.', 'woocommerce-jetpack' ),
					'type'        => 'boolean',
					'readonly'    => true,
				);
			}
			return array(
				'checkout_field_visibility' => array(
					'description' => __( 'Booster checkout field visibility for the current cart.', 'woocommerce-jetpack' ),
					'type'        => 'object',
					'properties'  => $properties,
					'readonly'    => true,
				),
			);
		}

		/**
		 * Get current cart product IDs once per request.
		 *
		 * @return array
		 */
		private function get_cart_product_ids() {
			if ( null !== $this->cart_product_ids_cache ) {
				return $this->cart_product_ids_cache;
			}
			$this->cart_product_ids_cache = array();
			if ( function_exists( 'WC' ) && WC()->cart ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( isset( $cart_item['product_id'] ) ) {
						$this->cart_product_ids_cache[] = (string) $cart_item['product_id'];
					}
				}
			}
			return array_values( array_unique( $this->cart_product_ids_cache ) );
		}

		/**
		 * Get current cart category IDs once per request.
		 *
		 * @return array
		 */
		private function get_cart_category_ids() {
			if ( null !== $this->cart_category_ids_cache ) {
				return $this->cart_category_ids_cache;
			}
			$this->cart_category_ids_cache = array();
			foreach ( $this->get_cart_product_ids() as $product_id ) {
				$category_ids = wp_get_post_terms( (int) $product_id, 'product_cat', array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $category_ids ) ) {
					$this->cart_category_ids_cache = array_merge( $this->cart_category_ids_cache, array_map( 'strval', $category_ids ) );
				}
			}
			$this->cart_category_ids_cache = array_values( array_unique( $this->cart_category_ids_cache ) );
			return $this->cart_category_ids_cache;
		}

		/**
		 * Evaluate the existing Booster visibility rules without recalculating cart totals.
		 *
		 * The products-in early return intentionally preserves the Classic Checkout
		 * rule precedence used by existing stores.
		 *
		 * @param int   $field_index Field index.
		 * @param array $config      Normalized field config.
		 * @return bool
		 */
		private function is_field_visible( $field_index, $config ) {
			if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
				return ! $config['conditioned'];
			}
			if ( apply_filters( 'wcj_checkout_custom_field_always_visible_on_empty_cart', false ) && WC()->cart->is_empty() ) {
				return true;
			}

			$product_ids  = $this->get_cart_product_ids();
			$category_ids = $this->get_cart_category_ids();
			if ( ! empty( array_intersect( $config['categories_ex'], $category_ids ) ) ) {
				return false;
			}
			if ( ! empty( $config['categories_in'] ) && empty( array_intersect( $config['categories_in'], $category_ids ) ) ) {
				return false;
			}
			if ( ! empty( array_intersect( $config['products_ex'], $product_ids ) ) ) {
				return false;
			}
			if ( ! empty( $config['products_in'] ) ) {
				return ! empty( array_intersect( $config['products_in'], $product_ids ) );
			}

			$cart_total = (float) WC()->cart->get_total( 'edit' );
			if ( $config['min_cart'] > 0 && $cart_total < $config['min_cart'] ) {
				return false;
			}
			if ( $config['max_cart'] > 0 && $cart_total > $config['max_cart'] ) {
				return false;
			}

			return (bool) apply_filters( 'wcj_checkout_custom_field_visible', true, $field_index );
		}

		/**
		 * Build a document-object condition for a field visibility value.
		 *
		 * @param int  $field_index Field index.
		 * @param bool $visible     Required visibility state.
		 * @return array
		 */
		private function get_visibility_condition( $field_index, $visible ) {
			return array(
				'cart' => array(
					'properties' => array(
						'extensions' => array(
							'properties' => array(
								self::STORE_API_NAMESPACE => array(
									'properties' => array(
										'checkout_field_visibility' => array(
											'properties' => array(
												'field_' . $field_index => array( 'const' => (bool) $visible ),
											),
										),
									),
								),
							),
						),
					),
				),
			);
		}

		/**
		 * Register supported fields with the Checkout block.
		 */
		public function register_blocks_checkout_fields() {
			if ( ! $this->is_blocks_api_available() ) {
				return;
			}

			foreach ( $this->get_all_field_configs() as $i => $config ) {
				if ( $config['conditioned'] && ! $this->supports_conditional_fields() ) {
					continue;
				}

				$blocks_type = 'radio' === $config['type'] ? 'select' : $config['type'];
				$field_args  = array(
					'id'                => $this->get_blocks_field_id( $i ),
					'label'             => $config['label'],
					'location'          => 'order',
					'type'              => $blocks_type,
					'required'          => $config['required'],
					'sanitize_callback' => array( $this, 'sanitize_field_value' ),
				);

				if ( $config['conditioned'] ) {
					$field_args['hidden'] = $this->get_visibility_condition( $i, false );
					if ( $config['required'] ) {
						$field_args['required'] = $this->get_visibility_condition( $i, true );
					}
				}

				if ( 'select' === $blocks_type ) {
					$field_args['options']           = $this->format_select_options_for_blocks( $config['select_options'] );
					$field_args['validate_callback'] = function ( $value ) use ( $config ) {
						$allowed = wp_list_pluck( $this->format_select_options_for_blocks( $config['select_options'] ), 'value' );
						if ( '' !== $value && ! in_array( $value, $allowed, true ) ) {
							return new \WP_Error( 'wcj_invalid_checkout_field', __( 'Please select a valid checkout field option.', 'woocommerce-jetpack' ) );
						}
					};
				}

				woocommerce_register_additional_checkout_field( $field_args );
			}
		}

		/**
		 * Sanitize a Blocks field value.
		 *
		 * @param mixed $value Submitted value.
		 * @return mixed
		 */
		public function sanitize_field_value( $value ) {
			return is_bool( $value ) ? $value : wc_clean( wp_unslash( $value ) );
		}

		/**
		 * Convert Booster newline-separated options to Blocks options.
		 *
		 * @param string $options_raw Stored options.
		 * @return array
		 */
		private function format_select_options_for_blocks( $options_raw ) {
			$options = array();
			foreach ( preg_split( '/\\r\\n|\\r|\\n/', (string) $options_raw ) as $line ) {
				$line = trim( $line );
				if ( '' !== $line ) {
					$options[] = array(
						'label' => $line,
						'value' => urldecode( sanitize_title( $line ) ),
					);
				}
			}
			return $options;
		}

		/**
		 * Map a Blocks option slug to the stored Booster label.
		 *
		 * @param array  $config Field config.
		 * @param string $slug   Blocks value.
		 * @return string
		 */
		private function reverse_map_select_value( $config, $slug ) {
			foreach ( $this->format_select_options_for_blocks( $config['select_options'] ) as $option ) {
				if ( $option['value'] === $slug ) {
					return $option['label'];
				}
			}
			return $slug;
		}

		/**
		 * Bridge a Blocks field into the legacy Booster order-meta shape.
		 *
		 * WooCommerce saves the order object after this hook, so this method only
		 * mutates the object and deliberately does not trigger an extra save.
		 *
		 * @param string    $key       Additional field key.
		 * @param mixed     $value     Saved value.
		 * @param string    $group     Field group.
		 * @param WC_Data   $wc_object WooCommerce data object.
		 */
		public function bridge_additional_field_value( $key, $value, $group, $wc_object ) {
			if ( 'other' !== $group || ! is_a( $wc_object, 'WC_Order' ) ) {
				return;
			}

			foreach ( $this->get_all_field_configs() as $i => $config ) {
				if ( $this->get_blocks_field_id( $i ) !== $key ) {
					continue;
				}

				if ( 'checkbox' === $config['type'] ) {
					$value = ! empty( $value ) && 'false' !== $value && '0' !== $value ? 1 : 0;
				} elseif ( in_array( $config['type'], array( 'select', 'radio' ), true ) ) {
					$value = $this->reverse_map_select_value( $config, (string) $value );
				}

				$prefix = '_' . $config['section'] . '_wcj_checkout_field_';
				$wc_object->update_meta_data( $prefix . $i, $value );
				$wc_object->update_meta_data( $prefix . 'label_' . $i, $config['label'] );
				$wc_object->update_meta_data( $prefix . 'type_' . $i, $config['type'] );

				if ( 'checkbox' === $config['type'] ) {
					$display = $value
						? wcj_get_option( 'wcj_checkout_custom_field_checkbox_yes_' . $i, '' )
						: wcj_get_option( 'wcj_checkout_custom_field_checkbox_no_' . $i, '' );
					$wc_object->update_meta_data( $prefix . 'checkbox_value_' . $i, $display );
				}
				if ( in_array( $config['type'], array( 'select', 'radio' ), true ) ) {
					$wc_object->update_meta_data( $prefix . 'select_options_' . $i, $config['select_options'] );
				}
				break;
			}
		}
	}

endif;
