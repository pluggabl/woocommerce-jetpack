<?php
/**
 * Booster for WooCommerce - Widget - Country Switcher
 *
 * @version 3.1.0
 * @since   2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Widget_Country_Switcher' ) ) :

class WCJ_Widget_Country_Switcher extends WCJ_Widget {

	/**
	 * get_data.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_data( $id ) {
		switch ( $id ) {
			case 'id_base':
				return 'wcj_widget_country_switcher';
			case 'name':
				return __( 'Booster - Country Switcher', 'woocommerce-jetpack' );
			case 'description':
				return __( 'Booster: Country Switcher Widget', 'woocommerce-jetpack' );
		}
	}

	/**
	 * get_content.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function get_content( $instance ) {
		if ( ! wcj_is_module_enabled( 'price_by_country' ) ) {
			return __( 'Prices and Currencies by Country module not enabled!', 'woocommerce-jetpack' );
		} elseif ( 'by_ip' === get_option( 'wcj_price_by_country_customer_country_detection_method', 'by_ip' ) ) {
			return __( 'Customer Country Detection Method must include "by user selection"!', 'woocommerce-jetpack' );
		} else {
			if ( ! isset( $instance['replace_with_currency'] ) ) {
				$instance['replace_with_currency'] = 'no';
			}
			return do_shortcode( '[wcj_country_select_drop_down_list countries="' . $instance['countries'] . '" replace_with_currency="' . $instance['replace_with_currency'] . '"]' );
		}
	}

	/**
	 * get_options.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    (maybe) `switcher_type`
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
				'title'    => __( 'Countries', 'woocommerce-jetpack' ),
				'id'       => 'countries',
				'default'  => '',
				'type'     => 'text',
				'class'    => 'widefat',
			),
			array(
				'title'    => __( 'Replace with currency', 'woocommerce-jetpack' ),
				'id'       => 'replace_with_currency',
				'default'  => 'no',
				'type'     => 'select',
				'class'    => 'widefat',
				'options'  => array(
					'no'  => __( 'No', 'woocommerce-jetpack' ),
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				),
			),
		);
	}

}

endif;

if ( ! function_exists( 'register_wcj_widget_country_switcher' ) ) {
	/**
	 * Register WCJ_Widget_Country_Switcher widget.
	 */
	function register_wcj_widget_country_switcher() {
		register_widget( 'WCJ_Widget_Country_Switcher' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_country_switcher' );
