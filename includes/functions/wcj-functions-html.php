<?php
/**
 * Booster for WooCommerce - Functions - HTML Functions
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_get_table_html' ) ) {
	/**
	 * Wcj_get_table_html.
	 *
	 * @version 5.6.8
	 * @param array $data defines the data.
	 * @param array $args defines the args.
	 */
	function wcj_get_table_html( $data, $args = array() ) {
		$defaults = array(
			'table_class'        => '',
			'table_style'        => '',
			'row_styles'         => '',
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => array(),
		);
		$args     = array_merge( $defaults, $args );

		$table_class        = $args['table_class'];
		$table_style        = $args['table_style'];
		$row_styles         = $args['row_styles'];
		$table_heading_type = $args['table_heading_type'];
		$columns_classes    = $args['columns_classes'];
		$columns_styles     = $args['columns_styles'];

		$table_class = ( '' === $table_class ) ? '' : ' class="' . $table_class . '"';
		$table_style = ( '' === $table_style ) ? '' : ' style="' . $table_style . '"';
		$row_styles  = ( '' === $row_styles ) ? '' : ' style="' . $row_styles . '"';
		$html        = '';
		$html       .= '<table' . $table_class . $table_style . '>';
		$html       .= '<tbody>';
		foreach ( $data as $row_number => $row ) {
			$row_class  = 'wcj-row wcj-row' . $row_number;
			$row_class .= 0 === $row_number % 2 ? ' wcj-row-even' : ' wcj-row-odd';
			$html      .= '<tr' . $row_styles . ' class="' . $row_class . '">';
			foreach ( $row as $column_number => $value ) {
				$th_or_td     = ( ( 0 === $row_number && 'horizontal' === $table_heading_type ) || ( 0 === $column_number && 'vertical' === $table_heading_type ) ) ? 'th' : 'td';
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

if ( ! function_exists( 'wcj_get_select_html' ) ) {
	/**
	 * Wcj_get_select_html.
	 *
	 * @version 4.0.0
	 * @since   4.0.0
	 * @todo    [dev] `selected`, `class`
	 * @param int | string $id defines the id.
	 * @param array        $options defines the options.
	 * @param null         $style defines the style.
	 */
	function wcj_get_select_html( $id, $options, $style = '' ) {
		$html  = '';
		$html .= '<select id="' . $id . '" name="' . $id . '" style="' . $style . '">';
		foreach ( $options as $option_id => $option_title ) {
			$html .= '<option value="' . $option_id . '">' . $option_title . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
}

if ( ! function_exists( 'wcj_get_option_html' ) ) {
	/**
	 * Wcj_get_option_html.
	 *
	 * @version 3.3.0
	 * @param string $option_type defines the option_type.
	 * @param int    $option_id defines the option_id.
	 * @param string $option_value defines the option_value.
	 * @param string $option_description defines the option_description.
	 * @param string $option_class defines the option_class.
	 */
	function wcj_get_option_html( $option_type, $option_id, $option_value, $option_description, $option_class ) {

		if ( 'checkbox' === $option_type ) {
			$is_checked = checked( $option_value, 'on', false );
		}

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
				$html .= '<input class="checkbox" style="margin-right:5px !important;" type="checkbox" name="' . $option_id . '" id="' . $option_id . '" ' . $is_checked . ' />';
				break;
			case 'select':
				$html .= '<select class="' . $option_class . '" id="' . $option_id . '" name="' . $option_id . '">' . $option_value . '</select>';
				break;
		}
		$html .= '<span class="description">' . $option_description . '</span>';

		return $html;
	}
}

if ( ! function_exists( 'wcj_empty_cart_button_html' ) ) {
	/**
	 * Wcj_empty_cart_button_html.
	 *
	 * @version 5.6.7
	 * @since   2.8.0
	 * @todo    optional function parameters instead of default `get_option()` calls
	 */
	function wcj_empty_cart_button_html() {
		$confirmation_html = ( 'confirm_with_pop_up_box' === wcj_get_option( 'wcj_empty_cart_confirmation', 'no_confirmation' ) ) ?
			' onclick="return confirm(\'' . wcj_get_option( 'wcj_empty_cart_confirmation_text', __( 'Are you sure?', 'woocommerce-jetpack' ) ) . '\')"' : '';
		return '<div style="' . wcj_get_option( 'wcj_empty_cart_div_style', 'float: right;' ) . '">' .
			'<form action="" method="post"><input type="submit" class="' . wcj_get_option( 'wcj_empty_cart_button_class', 'button' ) . '" name="wcj_empty_cart" value="' .
				apply_filters( 'booster_option', 'Empty Cart', wcj_get_option( 'wcj_empty_cart_text', 'Empty Cart' ) ) . '"' . $confirmation_html . '>' .
				wp_nonce_field( 'wcj-empty-cart', 'wcj_empty_cart_nonce' ) .
			'</form>' .
		'</div>';
	}
}
