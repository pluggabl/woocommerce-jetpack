<?php
/**
 * Booster for WooCommerce - Shortcodes - Input Field
 *
 * @version 3.0.1
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Input_Field_Shortcodes' ) ) :

class WCJ_Input_Field_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 3.0.1
	 * @since   2.5.2
	 */
	function __construct() {

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
	 * wcj_input_field.
	 *
	 * @version 3.0.1
	 * @since   2.5.2
	 */
	function wcj_input_field( $atts, $content ) {
		if ( '' == $atts['name'] ) {
			return __( 'Attribute "name" is required!', 'woocommerce-jetpack' );
		}
		$the_field = '';
		$the_field .= '<input' .
			' type="' . $atts['type'] . '"' .
			' class="' . $atts['class'] . '"' .
			' value="' . $atts['value'] . '"' .
			' placeholder="' . $atts['placeholder'] . '"' .
			' name="wcj_input_field_' . $atts['name'] . '"' .
			' id="wcj_input_field_' . $atts['name'] . '">';
		if ( '' != $atts['attach_to'] ) {
			$the_field .= '<input type="hidden" name="for_wcj_input_field_' . $atts['name'] . '" value="' . $atts['attach_to'] . '">';
		}
		if ( '' != $atts['label'] ) {
			$the_field .= '<input type="hidden" name="label_for_wcj_input_field_' . $atts['name'] . '" value="' . $atts['label'] . '">';
		}
		if ( 'yes' == $atts['required'] ) {
			$the_field .= '<input type="hidden" name="wcj_input_field_' . $atts['name'] . '_required" value="yes">';
		}
		return $the_field;
	}

}

endif;

return new WCJ_Input_Field_Shortcodes();
