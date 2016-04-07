<?php
/**
 * WooCommerce Jetpack Mini Cart
 *
 * The WooCommerce Jetpack Mini Cart class.
 *
 * @version 2.4.6
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Mini_Cart' ) ) :

class WCJ_Mini_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.6
	 */
	public function __construct() {

		$this->id         = 'mini_cart';
		$this->short_desc = __( 'Mini Cart', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce mini cart widget.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-mini-cart/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				add_action(
					get_option( 'wcj_mini_cart_custom_info_hook_' . $i, 'woocommerce_after_mini_cart' ),
					array( $this, 'add_mini_cart_custom_info' ),
					get_option( 'wcj_mini_cart_custom_info_priority_' . $i, 10 )
				);
			}
		}
	}

	/**
	 * add_mini_cart_custom_info.
	 *
	 * @version 2.4.6
	 */
	function add_mini_cart_custom_info() {
		$current_filter = current_filter();
		$current_filter_priority = wcj_current_filter_priority();
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if (
				'' != get_option( 'wcj_mini_cart_custom_info_content_' . $i ) &&
				$current_filter === get_option( 'wcj_mini_cart_custom_info_hook_' . $i, 'woocommerce_after_mini_cart' ) &&
				$current_filter_priority == get_option( 'wcj_mini_cart_custom_info_priority_' . $i, 10 )
			) {
				echo do_shortcode( get_option( 'wcj_mini_cart_custom_info_content_' . $i ) );
			}
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.6
	 */
	function get_settings() {

		$settings = array();

		// Mini Cart Custom Info Options
		$settings[] = array(
			'title'    => __( 'Mini Cart Custom Info Blocks', 'woocommerce-jetpack' ),
			'type'     => 'title',
			'id'       => 'wcj_mini_cart_custom_info_options',
		);

		$settings[] = array(
			'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
			'id'       => 'wcj_mini_cart_custom_info_total_number',
			'default'  => 1,
			'type'     => 'custom_number',
			'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
		);

		$settings[] = array(
			'type'     => 'sectionend',
			'id'       => 'wcj_mini_cart_custom_info_options',
		);

		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );

		for ( $i = 1; $i <= $total_number; $i++ ) {

			$settings = array_merge( $settings, array(

				array(
					'title'    => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i,
					'type'     => 'title',
					'id'       => 'wcj_mini_cart_custom_info_options_' . $i,
				),

				array(
					'title'    => __( 'Content', 'woocommerce-jetpack' ),
					'id'       => 'wcj_mini_cart_custom_info_content_' . $i,
					'default'  => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
					'type'     => 'textarea',
					'css'      => 'width:30%;min-width:300px;height:100px;',
				),

				array(
					'title'    => __( 'Position', 'woocommerce-jetpack' ),
					'id'       => 'wcj_mini_cart_custom_info_hook_' . $i,
					'default'  => 'woocommerce_after_mini_cart',
					'type'     => 'select',
					'options'  => array(
						'woocommerce_before_mini_cart'                    => __( 'Before mini cart', 'woocommerce-jetpack' ),
						'woocommerce_widget_shopping_cart_before_buttons' => __( 'Before buttons', 'woocommerce-jetpack' ),
						'woocommerce_after_mini_cart'                     => __( 'After mini cart', 'woocommerce-jetpack' ),
					),
					'css'      => 'width:250px;',
				),

				array(
					'title'    => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
					'id'       => 'wcj_mini_cart_custom_info_priority_' . $i,
					'default'  => 10,
					'type'     => 'number',
					'css'      => 'width:250px;',
				),

				array(
					'type'     => 'sectionend',
					'id'       => 'wcj_mini_cart_custom_info_options_' . $i,
				),
			) );
		}

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Mini_Cart();
