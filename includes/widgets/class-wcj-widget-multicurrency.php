<?php
/**
 * Booster for WooCommerce - Widget - Multicurrency
 *
 * @version 3.1.0
 * @since   2.4.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget_Multicurrency' ) ) :

class WCJ_Widget_Multicurrency extends WCJ_Widget {

	/**
	 * get_data.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_data( $id ) {
		switch ( $id ) {
			case 'id_base':
				return 'wcj_widget_multicurrency';
			case 'name':
				return __( 'Booster - Multicurrency Switcher', 'woocommerce-jetpack' );
			case 'description':
				return __( 'Booster: Multicurrency Switcher Widget', 'woocommerce-jetpack' );
		}
	}

	/**
	 * get_content.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_content( $instance ) {
		if ( ! wcj_is_module_enabled( 'multicurrency' ) ) {
			return __( 'Multicurrency module not enabled!', 'woocommerce-jetpack' );
		} else {
			switch ( $instance['switcher_type'] ) {
				case 'link_list':
					return do_shortcode( '[wcj_currency_select_link_list]' );
				case 'radio_list':
					return do_shortcode( '[wcj_currency_select_radio_list]' );
				default: // 'drop_down'
					return do_shortcode( '[wcj_currency_select_drop_down_list]' );
			}
		}
	}

	/**
	 * get_options.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_options() {
		return array(
			array(
				'title'    => __( 'Title', 'woocommerce-jetpack' ),
				'id'       => 'title',
				'default'  => '',
				'type'     => 'text',
				'class'    => 'widefat',
			),
			array(
				'title'    => __( 'Type', 'woocommerce-jetpack' ),
				'id'       => 'switcher_type',
				'default'  => 'drop_down',
				'type'     => 'select',
				'options'  => array(
					'drop_down'  => __( 'Drop down', 'woocommerce-jetpack' ),
					'radio_list' => __( 'Radio list', 'woocommerce-jetpack' ),
					'link_list'  => __( 'Link list', 'woocommerce-jetpack' ),
				),
				'class'    => 'widefat',
			),
		);
	}

}

endif;

if ( ! function_exists( 'register_wcj_widget_multicurrency' ) ) {
	/**
	 * Register WCJ_Widget_Multicurrency widget.
	 */
	function register_wcj_widget_multicurrency() {
		register_widget( 'WCJ_Widget_Multicurrency' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_multicurrency' );
