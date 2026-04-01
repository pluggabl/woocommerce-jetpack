<?php
/**
 * Booster for WooCommerce - Checkout Custom Fields - WooCommerce Blocks Integration
 *
 * Registers Booster checkout custom fields with the WooCommerce Blocks checkout
 * and bridges Blocks-saved data back to Booster meta format so that existing
 * admin display, email, shortcode, and exporter code continues to work.
 *
 * Supported field types on Blocks checkout: text, select, checkbox, radio (as select).
 * Unsupported types (textarea, datepicker, weekpicker, timepicker, number) are
 * silently skipped and continue to work only on classic checkout.
 *
 * Radio fields are automatically converted to select dropdowns on Blocks checkout
 * since the WC Blocks API does not support native radio inputs.
 *
 * Note: Visibility conditions (product/category/cart-amount show/hide) are NOT
 * enforced on Blocks checkout. The WC Blocks additional checkout fields API
 * registers fields globally at plugin init, before cart context is available.
 * Visibility conditions continue to work on classic checkout only.
 *
 * All fields use the 'order' location (additional-information area) on Blocks.
 * The WC Blocks 'address' location duplicates fields across both billing and
 * shipping address groups, which is not the intended Booster section behavior.
 *
 * @version 8.0.0
 * @since   7.12.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Checkout_Custom_Fields_Blocks' ) ) :

	/**
	 * WCJ_Checkout_Custom_Fields_Blocks.
	 *
	 * @version 8.0.0
	 * @since   7.12.0
	 */
	class WCJ_Checkout_Custom_Fields_Blocks {

		/**
		 * Namespace prefix for WC Blocks field IDs.
		 *
		 * @var string
		 */
		const FIELD_NAMESPACE = 'booster-wcj';

		/**
		 * Total number of checkout custom fields configured.
		 *
		 * @var int
		 */
		private $total_fields;

		/**
		 * Field types supported on WooCommerce Blocks checkout.
		 * Radio is included and converted to select during registration.
		 *
		 * @var array
		 */
		private $supported_types = array( 'text', 'select', 'checkbox', 'radio' );

		/**
		 * Cached field configuration loaded once during registration.
		 *
		 * @var array|null
		 */
		private $field_config_cache = null;

		/**
		 * Constructor.
		 *
		 * @version 8.0.0
		 * @since   7.12.0
		 * @param int $total_fields Total number of configured checkout custom fields.
		 */
		public function __construct( $total_fields ) {
			$this->total_fields = $total_fields;

			add_action( 'woocommerce_init', array( $this, 'register_blocks_checkout_fields' ) );
			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'bridge_blocks_meta_to_booster_format' ) );
		}

		/**
		 * Check if the WC Blocks additional checkout fields API is available.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 * @return bool
		 */
		private function is_blocks_api_available() {
			return function_exists( 'woocommerce_register_additional_checkout_field' );
		}

		/**
		 * Get the WC Blocks field ID for a given Booster field index.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 * @param int $field_index Booster field index.
		 * @return string
		 */
		private function get_blocks_field_id( $field_index ) {
			return self::FIELD_NAMESPACE . '/checkout-field-' . $field_index;
		}

		/**
		 * Load and cache all field configuration in a single pass.
		 *
		 * Avoids N+1 option queries by loading all enabled field configs at once.
		 *
		 * @version 8.0.0
		 * @since   8.0.0
		 * @return array Associative array keyed by field index.
		 */
		private function get_all_field_configs() {
			if ( null !== $this->field_config_cache ) {
				return $this->field_config_cache;
			}

			$this->field_config_cache = array();

			for ( $i = 1; $i <= $this->total_fields; $i++ ) {
				$enabled = wcj_get_option( 'wcj_checkout_custom_field_enabled_' . $i );
				if ( 'yes' !== $enabled ) {
					continue;
				}

				$booster_type = wcj_get_option( 'wcj_checkout_custom_field_type_' . $i, 'text' );
				if ( ! in_array( $booster_type, $this->supported_types, true ) ) {
					continue;
				}

				$this->field_config_cache[ $i ] = array(
					'type'           => $booster_type,
					'label'          => wcj_get_option( 'wcj_checkout_custom_field_label_' . $i, '' ),
					'required'       => ( 'yes' === wcj_get_option( 'wcj_checkout_custom_field_required_' . $i, 'no' ) ),
					'section'        => wcj_get_option( 'wcj_checkout_custom_field_section_' . $i, 'billing' ),
					'select_options' => wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' ),
				);
			}

			return $this->field_config_cache;
		}

		/**
		 * Register Booster checkout custom fields with the WC Blocks API.
		 *
		 * Only registers fields with supported types (text, select, checkbox, radio).
		 * Radio fields are converted to select dropdowns since the Blocks API does
		 * not support native radio inputs.
		 *
		 * All fields use the 'order' location so they appear in the
		 * order/additional-information area of the Checkout block.
		 *
		 * Visibility conditions (product/category/cart-amount) are NOT evaluated
		 * here because the WC Blocks API registers fields at plugin init, before
		 * cart context is available. Visibility remains classic-checkout-only.
		 *
		 * @version 8.0.0
		 * @since   7.12.0
		 */
		public function register_blocks_checkout_fields() {
			if ( ! $this->is_blocks_api_available() ) {
				return;
			}

			$configs = $this->get_all_field_configs();

			foreach ( $configs as $i => $config ) {
				$blocks_type = $config['type'];

				// Convert radio to select for Blocks (no native radio support).
				if ( 'radio' === $blocks_type ) {
					$blocks_type = 'select';
				}

				$field_args = array(
					'id'       => $this->get_blocks_field_id( $i ),
					'label'    => $config['label'],
					'location' => 'order',
					'type'     => $blocks_type,
					'required' => $config['required'],
				);

				// Select and radio-as-select both need options.
				if ( 'select' === $blocks_type ) {
					$options_raw = $config['select_options'];
					// For radio fields, options are stored in the same format.
					if ( 'radio' === $config['type'] ) {
						$options_raw = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' );
					}
					$field_args['options'] = $this->format_select_options_for_blocks( $options_raw );
				}

				woocommerce_register_additional_checkout_field( $field_args );
			}
		}

		/**
		 * Convert Booster's newline-separated select options to the WC Blocks format.
		 *
		 * Booster stores select options as a newline-separated string.
		 * WC Blocks expects an array of { label, value } arrays.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 * @param string $options_raw Newline-separated options from Booster settings.
		 * @return array
		 */
		private function format_select_options_for_blocks( $options_raw ) {
			$blocks_options = array();
			if ( '' === $options_raw ) {
				return $blocks_options;
			}

			$lines = array_map( 'trim', explode( PHP_EOL, $options_raw ) );
			foreach ( $lines as $line ) {
				if ( '' === $line ) {
					continue;
				}
				$blocks_options[] = array(
					'label' => $line,
					'value' => urldecode( sanitize_title( $line ) ),
				);
			}

			return $blocks_options;
		}

		/**
		 * Reverse-map a Blocks select slug back to the raw Booster option label.
		 *
		 * During registration, raw Booster option labels are converted to slugs
		 * via sanitize_title(). This reverses that mapping so the bridged meta
		 * stores the same raw label that the classic checkout path would store.
		 *
		 * Works for both select and radio-as-select fields.
		 *
		 * @version 8.0.0
		 * @since   7.12.0
		 * @param int    $field_index Booster field index.
		 * @param string $slug        The sanitized slug submitted by Blocks checkout.
		 * @return string The raw Booster option label, or the slug if no match found.
		 */
		private function reverse_map_select_value( $field_index, $slug ) {
			$configs     = $this->get_all_field_configs();
			$options_raw = '';

			if ( isset( $configs[ $field_index ] ) ) {
				$options_raw = $configs[ $field_index ]['select_options'];
				if ( 'radio' === $configs[ $field_index ]['type'] && '' === $options_raw ) {
					$options_raw = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $field_index, '' );
				}
			}

			if ( '' === $options_raw ) {
				$options_raw = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $field_index, '' );
			}

			if ( '' === $options_raw ) {
				return $slug;
			}

			$lines = array_map( 'trim', explode( PHP_EOL, $options_raw ) );
			foreach ( $lines as $line ) {
				if ( '' === $line ) {
					continue;
				}
				if ( urldecode( sanitize_title( $line ) ) === $slug ) {
					return $line;
				}
			}

			return $slug;
		}

		/**
		 * Get the WC Blocks CheckoutFields service instance.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 * @return object|false The CheckoutFields service or false if unavailable.
		 */
		private function get_checkout_fields_service() {
			if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
				return false;
			}
			try {
				$container = \Automattic\WooCommerce\Blocks\Package::container();
				return $container->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
			} catch ( \Exception $e ) {
				return false;
			}
		}

		/**
		 * Bridge WC Blocks meta to Booster meta format after a Blocks checkout.
		 *
		 * When a customer checks out via WooCommerce Blocks, field data is saved
		 * by the Store API using the Blocks meta key format. This method reads
		 * that data using the WC CheckoutFields service (the recommended access
		 * path) and copies it into Booster's expected meta key format
		 * (_SECTION_wcj_checkout_field_N) so that existing admin order display,
		 * emails, shortcodes, and store exporters continue to work.
		 *
		 * @version 8.0.0
		 * @since   7.12.0
		 * @param \WC_Order $order The order object.
		 */
		public function bridge_blocks_meta_to_booster_format( $order ) {
			$checkout_fields_service = $this->get_checkout_fields_service();
			$configs                 = $this->get_all_field_configs();
			$bridged_any             = false;

			foreach ( $configs as $i => $config ) {
				$booster_type = $config['type'];

				$field_id     = $this->get_blocks_field_id( $i );
				$blocks_value = '';

				// All fields use 'order' location → 'other' service group.
				if ( $checkout_fields_service && method_exists( $checkout_fields_service, 'get_field_from_object' ) ) {
					$blocks_value = $checkout_fields_service->get_field_from_object( $field_id, $order, 'other' );
				}

				// Fallback: try direct meta read if the service returned empty.
				if ( '' === $blocks_value || false === $blocks_value || null === $blocks_value ) {
					$blocks_value = $order->get_meta( '_wc_other/' . $field_id );
				}

				// Skip fields with no value (except checkboxes which can be unchecked).
				if ( '' === $blocks_value || false === $blocks_value || null === $blocks_value ) {
					if ( 'checkbox' !== $booster_type ) {
						continue;
					}
					$blocks_value = '';
				}

				$section = $config['section'];
				$label   = $config['label'];

				$booster_key       = '_' . $section . '_wcj_checkout_field_' . $i;
				$booster_key_label = '_' . $section . '_wcj_checkout_field_label_' . $i;
				$booster_key_type  = '_' . $section . '_wcj_checkout_field_type_' . $i;

				// Normalize checkbox values.
				if ( 'checkbox' === $booster_type ) {
					$blocks_value = ( ! empty( $blocks_value ) && 'false' !== $blocks_value && '0' !== $blocks_value ) ? 1 : 0;
				}

				// Reverse-map select/radio slug back to the raw Booster option label.
				if ( in_array( $booster_type, array( 'select', 'radio' ), true ) ) {
					$blocks_value = $this->reverse_map_select_value( $i, $blocks_value );
				}

				$order->update_meta_data( $booster_key, $blocks_value );
				$order->update_meta_data( $booster_key_label, $label );
				$order->update_meta_data( $booster_key_type, $booster_type );

				// Store checkbox display text.
				if ( 'checkbox' === $booster_type ) {
					$checkbox_key   = '_' . $section . '_wcj_checkout_field_checkbox_value_' . $i;
					$checkbox_value = ( 1 === (int) $blocks_value )
						? get_option( 'wcj_checkout_custom_field_checkbox_yes_' . $i, '' )
						: get_option( 'wcj_checkout_custom_field_checkbox_no_' . $i, '' );
					$order->update_meta_data( $checkbox_key, $checkbox_value );
				}

				// Store select/radio options string for display resolution.
				if ( in_array( $booster_type, array( 'select', 'radio' ), true ) ) {
					$select_key = '_' . $section . '_wcj_checkout_field_select_options_' . $i;
					$the_values = $config['select_options'];
					if ( '' === $the_values ) {
						$the_values = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' );
					}
					$order->update_meta_data( $select_key, $the_values );
				}

				$bridged_any = true;
			}

			if ( $bridged_any ) {
				$order->save();
			}
		}
	}

endif;
