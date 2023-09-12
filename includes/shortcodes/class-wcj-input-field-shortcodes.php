<?php
/**
 * Booster for WooCommerce - Shortcodes - Input Field
 *
 * @version 7.1.1
 * @since   2.5.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Input_Field_Shortcodes' ) ) :

		/**
		 * WCJ_Input_Field_Shortcodes.
		 *
		 * @version 3.0.1
		 * @since   2.5.2
		 */
	class WCJ_Input_Field_Shortcodes extends WCJ_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 3.0.1
		 * @since   2.5.2
		 */
		public function __construct() {

			$this->the_shortcodes = array(
				'wcj_input_field',
			);

			$this->the_atts = array(
				'type'        => 'text',
				'class'       => '',
				'value'       => '',
				'placeholder' => '',
				'name'        => '',
				'attach_to'   => '',
				'label'       => '',
				'required'    => 'no',
			);

			parent::__construct();

		}

		/**
		 * Wcj_input_field.
		 *
		 * @version 7.1.1
		 * @since   2.5.2
		 * @param array  $atts The user defined shortcode attributes.
		 * @param string $content Optional. The content between the opening and closing shortcode tags. Default is an empty string.
		 */
		public function wcj_input_field( $atts, $content ) {

			$type        = wcj_sanitize_input_attribute_values( $atts['type'] );
			$class       = wcj_sanitize_input_attribute_values( $atts['class'] );
			$name        = wcj_sanitize_input_attribute_values( $atts['name'] );
			$label       = wcj_sanitize_input_attribute_values( $atts['label'] );
			$value       = wcj_sanitize_input_attribute_values( $atts['value'] );
			$placeholder = wcj_sanitize_input_attribute_values( $atts['placeholder'] );
			if ( '' === $name ) {
				return __( 'Attribute "name" is required!', 'woocommerce-jetpack' );
			}

			// Custom data attributes.
			$data_attributes = null;
			if ( isset( $atts['data_attributes'] ) && ! empty( $atts['data_attributes'] ) ) {
				$data_attributes = wp_parse_args( $atts['data_attributes'] );
			}
			$data_attributes_html = wcj_get_data_attributes_html( $data_attributes );

			// Name.
			$name_html = ' name="wcj_input_field_' . $name . '"';
			if ( isset( $atts['name_array'] ) && ! empty( $atts['name_array'] ) ) {
				$name_html = ' name="wcj_input_field_' . $atts['name_array'] . '[' . $name . '][value]"';
			}

			$the_field  = '';
			$the_field .= '<input' .
			$data_attributes_html .
			' type="' . $type . '"' .
			' class="' . $class . '"' .
			' value="' . $value . '"' .
			' placeholder="' . $placeholder . '"' .
			$name_html .
			' id="wcj_input_field_' . $name . '">';
			if ( '' !== $atts['attach_to'] ) {
				$the_field .= '<input type="hidden" name="for_wcj_input_field_' . $name . '" value="' . $atts['attach_to'] . '">';
			}

			// Label.
			if ( '' !== $label ) {
				$label_name_html = ' name="label_for_wcj_input_field_' . $name . '"';
				if ( isset( $atts['name_array'] ) && ! empty( $atts['name_array'] ) ) {
					$label_name_html = ' name="wcj_input_field_' . $atts['name_array'] . '[' . $name . '][label]"';
				}
				$the_field .= '<input type="hidden" ' . $label_name_html . ' value="' . $label . '">';
			}

			if ( 'yes' === $atts['required'] ) {
				$the_field .= '<input type="hidden" name="wcj_input_field_' . $name . '_required" value="yes">';
			}
			return $the_field;
		}
	}

endif;

return new WCJ_Input_Field_Shortcodes();
