<?php
/**
 * WooCommerce Jetpack HTML Functions
 *
 * The WooCommerce Jetpack HTML functions.
 *
 * @version 1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * wcj_get_table_html.
 */
if ( ! function_exists( 'wcj_get_table_html' ) ) {
	function wcj_get_table_html( $data, $args = array() ) {
		$defaults = array(
			'table_class'        => '',
			'table_style'        => '',
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => array(),
		);
		//wp_parse_args( $args, $defaults );
		$args = array_merge( $defaults, $args );
		extract( $args );
		$table_class = ( '' == $table_class ) ? '' : ' class="' . $table_class . '"';
		$table_style = ( '' == $table_style ) ? '' : ' style="' . $table_style . '"';
		$html = '';
		$html .= '<table' . $table_class . $table_style . '>';
		$html .= '<tbody>';
		foreach( $data as $row_number => $row ) {
			$html .= '<tr>';
			foreach( $row as $column_number => $value ) {
				$th_or_td = ( ( 0 === $row_number && 'horizontal' === $table_heading_type ) || ( 0 === $column_number && 'vertical' === $table_heading_type ) ) ? 'th' : 'td';
				$column_class = ( ! empty( $columns_classes ) && isset( $columns_classes[ $column_number ] ) ) ? ' class="' . $columns_classes[ $column_number ] . '"' : '';
				$column_style = ( ! empty( $columns_styles ) && isset( $columns_styles[ $column_number ] ) ) ? ' style="' . $columns_styles[ $column_number ] . '"' : '';

				$html .= '<' . $th_or_td . $column_class . $column_style . '>';
				$html .= $value;
				$html .= '</' . $th_or_td . '>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .= '</table>';
		return $html;
	}
}

/**
 * wcj_get_option_html.
 */
if ( ! function_exists( 'wcj_get_option_html' ) ) {
	function wcj_get_option_html( $option_type, $option_id, $option_value, $option_description, $option_class ) {

		if ( 'checkbox' === $option_type )
			$is_checked = checked( $option_value, 'on', false );

		$html = '';
		switch ( $option_type ) {
			case 'number':
			case 'text':
				$html .= '<input type="' . $option_type . '" class="' . $option_class . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '">';
				break;
			case 'textarea':
				$html .= '<textarea class="' . $option_class . '" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</textarea>';
				break;
			case 'checkbox':
				$html .= '<input class="checkbox" type="checkbox" name="' . $option_id . '" id="' . $option_id . '" ' . $is_checked . ' />';
				break;
			case 'select':
				$html .= '<select class="' . $option_class . '" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</select>';
				break;
		}
		$html .= '<span class="description">' . $option_description . '</span>';

		return $html;
	}
}