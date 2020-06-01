<?php
/**
 * Booster for WooCommerce - Widget
 *
 * @version 3.1.0
 * @since   3.1.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget' ) ) :

class WCJ_Widget extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function __construct() {
		$widget_options = array(
			'classname'   => $this->get_data( 'id_base' ),
			'description' => $this->get_data( 'description' ),
		);
		parent::__construct( $widget_options['classname'], $this->get_data( 'name' ), $widget_options );
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @param   array $args
	 * @param   array $instance
	 */
	function widget( $args, $instance ) {
		$html = '';
		$html .= $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			$html .= $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$html .= $this->get_content( $instance );
		$html .= $args['after_widget'];
		echo $html;
	}

	/**
	 * Outputs the options form on admin.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @param   array $instance The widget options
	 * @todo    add more types (not only text etc. and select)
	 */
	function form( $instance ) {
		$html = '';
		foreach ( $this->get_options() as $option ) {
			$field_value = /* esc_attr */( ( ! empty( $instance[ $option['id'] ] ) ? $instance[ $option['id'] ] :  $option['default'] ) );
			$field_id    = $this->get_field_id( $option['id'] );
			$field_name  = $this->get_field_name( $option['id'] );
			$field_type  = $option['type'];
			$field_desc  = ( isset( $option['desc'] ) ? '<br>' . '<em>' . $option['desc'] . '</em>' . '<br>' : '' );
			$field_class = ( isset( $option['class'] ) ? $option['class'] : '' );
			$field_style = ( isset( $option['style'] ) ? $option['style'] : '' );
			$html .= '<label for="' . $field_id . '">' . $option['title'] . '</label>';
			switch ( $field_type ) {
				case 'select':
					$options_html = '';
					foreach ( $option['options'] as $select_option_id => $select_option_title ) {
						$options_html .= '<option value="' . $select_option_id . '" ' . selected( $select_option_id, $field_value, false ) . '>' .
							$select_option_title . '</option>';
					}
					$html .= '<select class="' . $field_class . '" style="' . $field_style . '" id="' . $field_id . '" name="' . $field_name . '"' . '>' .
						$options_html . '</select>';
					break;
				default: // 'text' etc.
					$html .= '<input class="' . $field_class . '" style="' . $field_style . '" id="' . $field_id . '" name="' . $field_name . '"' .
						' type="' . $field_type . '" value="' . $field_value . '">';
			}
			$html .= $field_desc;
		}
		echo '<p>' . $html . '</p>';
	}

	/**
	 * Processing widget options on save.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @param   array $new_instance The new options
	 * @param   array $old_instance The previous options
	 */
	function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach ( $this->get_options() as $option ) {
			$instance[ $option['id'] ] = ( ! empty( $new_instance[ $option['id'] ] ) ? /* strip_tags */( $new_instance[ $option['id'] ] ) : $option['default'] );
		}
		return $instance;
	}
}

endif;
