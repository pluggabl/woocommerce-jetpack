<?php
/**
 * Booster for WooCommerce - Functions - Admin
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'wcj_message_replaced_values' ) ) {
	/**
	 * wcj_message_replaced_values.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    use this function in all applicable settings descriptions
	 */
	function wcj_message_replaced_values( $values ) {
		$message_template = ( 1 == count( $values ) ? __( 'Replaced value: %s', 'woocommerce-jetpack' ) : __( 'Replaced values: %s', 'woocommerce-jetpack' ) );
		return sprintf( $message_template, '<code>' . implode( '</code>, <code>', $values ) . '</code>' );
	}
}

if ( ! function_exists( 'wcj_get_plus_message' ) ) {
	/**
	 * wcj_get_plus_message.
	 *
	 * @version 2.9.0
	 */
	function wcj_get_plus_message( $value, $message_type, $args = array() ) {

		switch ( $message_type ) {

			case 'global':
				return '<div class="updated">
							<p class="main"><strong>' . __( 'Install Booster Plus to unlock all features', 'woocommerce-jetpack' ) . '</strong></p>
							<span>' . sprintf( __( 'Some settings fields are locked and you will need %s to modify all locked fields.', 'woocommerce-jetpack'), '<a href="https://booster.io/plus/" target="_blank">Booster for WooCommerce Plus</a>' ) . '</span>
							<p><a href="https://booster.io/plus/" target="_blank" class="button button-primary">' . __( 'Buy now', 'woocommerce-jetpack' ) . '</a> <a href="https://booster.io" target="_blank" class="button">'. __( 'Visit Booster Site', 'woocommerce-jetpack' ) . '</a></p>
						</div>';

			case 'desc':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change value.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_advanced':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to enable "%s" option.', 'woocommerce-jetpack' ), 'https://booster.io/plus/', $args['option'] );

			case 'desc_advanced_no_link':
				return sprintf( __( 'Get Booster Plus to enable "%s" option.', 'woocommerce-jetpack' ), $args['option'] );

			case 'desc_below':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change values below.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_above':
				return sprintf( __( 'Get <a href="%s" target="_blank">Booster Plus</a> to change values above.', 'woocommerce-jetpack' ), 'https://booster.io/plus/' );

			case 'desc_no_link':
				return __( 'Get Booster Plus to change value.', 'woocommerce-jetpack' );

			case 'readonly':
				return array( 'readonly' => 'readonly' );

			case 'disabled':
				return array( 'disabled' => 'disabled' );

			case 'readonly_string':
				return 'readonly';

			case 'disabled_string':
				return 'disabled';
		}

		return $value;
	}
}
