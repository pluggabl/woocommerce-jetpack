<?php
/**
 * Booster for WooCommerce - Widget - Left to Free Shipping
 *
 * @version 
 * @since   2.4.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Widget_Left_to_Free_Shipping' ) ) :

		/**
		 * WCJ_Widget_Left_to_Free_Shipping.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 */
	class WCJ_Widget_Left_To_Free_Shipping extends WCJ_Widget {

		/**
		 * Get_data.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 * @param int $id Get id base widget data.
		 */
		public function get_data( $id ) {
			switch ( $id ) {
				case 'id_base':
					return 'wcj_widget_left_to_free_shipping';
				case 'name':
					return __( 'Booster - Left to Free Shipping', 'woocommerce-jetpack' );
				case 'description':
					return __( 'Booster: Left to Free Shipping Widget', 'woocommerce-jetpack' );
			}
		}

		/**
		 * Get_content.
		 *
		 * @version 
		 * @since   3.1.0
		 * @param array $instance Saved values from database.
		 */
		public function get_content( $instance ) {
			if ( ! isset( $instance['content'] ) ) {
				$instance['content'] = '';
			}
			return wcj_get_left_to_free_shipping( $instance['content'] );
		}

		/**
		 * Get_options.
		 *
		 * @version 3.1.0
		 * @since   3.1.0
		 */
		public function get_options() {
			return array(
				array(
					'title'   => __( 'Title', 'woocommerce-jetpack' ),
					'id'      => 'title',
					'default' => '',
					'type'    => 'text',
					'class'   => 'widefat',
				),
				array(
					'title'   => __( 'Content', 'woocommerce-jetpack' ),
					'id'      => 'content',
					'default' => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
					'type'    => 'text',
					'class'   => 'widefat',
				),
			);
		}

	}

endif;

if ( ! function_exists( 'register_wcj_widget_left_to_free_shipping' ) ) {
	/**
	 * Register WCJ_Widget_Left_to_Free_Shipping widget.
	 */
	function register_wcj_widget_left_to_free_shipping() {
		register_widget( 'WCJ_Widget_Left_to_Free_Shipping' );
	}
}
add_action( 'widgets_init', 'register_wcj_widget_left_to_free_shipping' );
