<?php
/**
 * Booster for WooCommerce - Checkout Custom Fields - WooCommerce Blocks Integration
 *
 * Registers Booster checkout custom fields with the WooCommerce Blocks checkout
 * and bridges Blocks-saved data back to Booster meta format so that existing
 * admin display, email, shortcode, and exporter code continues to work.
 *
 * Supported field types on Blocks checkout: text, select, checkbox.
 * Unsupported types (textarea, radio, datepicker, etc.) are silently skipped
 * and continue to work only on classic checkout.
 *
 * @version 7.12.0
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
	 * @version 7.12.0
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
		 *
		 * @var array
		 */
		private $supported_types = array( 'text', 'select', 'checkbox' );

		/**
		 * Constructor.
		 *
		 * @version 7.12.0
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
		 * Register Booster checkout custom fields with the WC Blocks API.
		 *
		 * Only registers fields with supported types (text, select, checkbox).
		 * All fields use the 'order' location so they appear in the
		 * "Additional Information" section on the Blocks checkout.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 */
		public function register_blocks_checkout_fields() {
			if ( ! $this->is_blocks_api_available() ) {
				return;
			}

			for ( $i = 1; $i <= $this->total_fields; $i++ ) {
				if ( 'yes' !== wcj_get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {
					continue;
				}

				$booster_type = wcj_get_option( 'wcj_checkout_custom_field_type_' . $i, 'text' );
				if ( ! in_array( $booster_type, $this->supported_types, true ) ) {
					continue;
				}

				$label    = wcj_get_option( 'wcj_checkout_custom_field_label_' . $i, '' );
				$required = ( 'yes' === wcj_get_option( 'wcj_checkout_custom_field_required_' . $i, 'no' ) );

				$field_args = array(
					'id'       => $this->get_blocks_field_id( $i ),
					'label'    => $label,
					'location' => 'order',
					'type'     => $booster_type,
					'required' => $required,
				);

				if ( 'select' === $booster_type ) {
					$options_raw            = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' );
					$field_args['options']  = $this->format_select_options_for_blocks( $options_raw );
				}

				if ( 'text' === $booster_type ) {
					$placeholder = wcj_get_option( 'wcj_checkout_custom_field_placeholder_' . $i, '' );
					if ( '' !== $placeholder ) {
						$field_args['attributes'] = array( 'placeholder' => $placeholder );
					}
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
		 * @version 7.12.0
		 * @since   7.12.0
		 * @param int    $field_index Booster field index.
		 * @param string $slug        The sanitized slug submitted by Blocks checkout.
		 * @return string The raw Booster option label, or the slug if no match found.
		 */
		private function reverse_map_select_value( $field_index, $slug ) {
			$options_raw = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $field_index, '' );
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
		 * Bridge WC Blocks meta to Booster meta format after a Blocks checkout.
		 *
		 * When a customer checks out via WooCommerce Blocks, field data is saved
		 * using the Blocks meta key format (_wc_other/booster-wcj/checkout-field-N).
		 * This method copies that data into Booster's expected meta key format
		 * (_SECTION_wcj_checkout_field_N) so that existing admin order display,
		 * emails, shortcodes, and store exporters continue to work.
		 *
		 * @version 7.12.0
		 * @since   7.12.0
		 * @param \WC_Order $order The order object.
		 */
		public function bridge_blocks_meta_to_booster_format( $order ) {
			$bridged_any = false;

			for ( $i = 1; $i <= $this->total_fields; $i++ ) {
				if ( 'yes' !== wcj_get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {
					continue;
				}

				$booster_type = wcj_get_option( 'wcj_checkout_custom_field_type_' . $i, 'text' );
				if ( ! in_array( $booster_type, $this->supported_types, true ) ) {
					continue;
				}

				$field_id       = $this->get_blocks_field_id( $i );
				$blocks_meta_key = '_wc_other/' . $field_id;
				$blocks_value    = $order->get_meta( $blocks_meta_key );

				if ( '' === $blocks_value ) {
					if ( 'checkbox' !== $booster_type ) {
						continue;
					}
				}

				$section = wcj_get_option( 'wcj_checkout_custom_field_section_' . $i, 'billing' );
				$label   = wcj_get_option( 'wcj_checkout_custom_field_label_' . $i, '' );

				$booster_key       = '_' . $section . '_wcj_checkout_field_' . $i;
				$booster_key_label = '_' . $section . '_wcj_checkout_field_label_' . $i;
				$booster_key_type  = '_' . $section . '_wcj_checkout_field_type_' . $i;

				// Normalize checkbox values: WC Blocks stores true/false, Booster stores 1/0.
				if ( 'checkbox' === $booster_type ) {
					$blocks_value = ! empty( $blocks_value ) ? 1 : 0;
				}

				// Reverse-map select slug back to the raw Booster option label.
				// Blocks submits the sanitized slug (e.g. "express-delivery") but
				// Booster's classic path stores the raw option text ("Express Delivery").
				if ( 'select' === $booster_type ) {
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

				// Store select options string for display resolution.
				if ( 'select' === $booster_type ) {
					$select_key = '_' . $section . '_wcj_checkout_field_select_options_' . $i;
					$the_values = wcj_get_option( 'wcj_checkout_custom_field_select_options_' . $i, '' );
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
