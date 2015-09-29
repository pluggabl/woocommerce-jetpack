<?php
/**
 * WooCommerce Jetpack Cart
 *
 * The WooCommerce Jetpack Cart class.
 *
 * @version 2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cart' ) ) :

class WCJ_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id         = 'cart';
		$this->short_desc = __( 'Cart', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom info to WooCommerce cart page. Add empty cart button.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			/*if ( 'yes' === get_option( 'wcj_cart_hide_shipping_and_taxes_estimated_message' ) )
					add_filter( 'gettext', array( $this, 'hide_shipping_and_taxes_estimated_message' ), 20, 3 );*/

			//add_action( get_option( 'wcj_cart_custom_info_hook', 'woocommerce_after_cart_totals' ), array( $this, 'add_cart_custom_info' ) );
			$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++) {
				add_action( get_option( 'wcj_cart_custom_info_hook_' . $i, 'woocommerce_after_cart_totals' ), array( $this, 'add_cart_custom_info' ) );
			}
		}
	}

	/**
	 * add_cart_custom_info.
	 */
	function add_cart_custom_info() {
		$current_filter = current_filter();
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++) {
			if ( '' != get_option( 'wcj_cart_custom_info_content_' . $i ) && $current_filter === get_option( 'wcj_cart_custom_info_hook_' . $i ) ) {
				echo do_shortcode( get_option( 'wcj_cart_custom_info_content_' . $i ) );
			}
		}
	}

	/**
	 * change_labels.
	 *
	public function hide_shipping_and_taxes_estimated_message( $translated_text, $text, $domain ) {

		if ( ! function_exists( 'is_cart' ) || ! is_cart() )
			return $translated_text;

		if ( 'Note: Shipping and taxes are estimated%s and will be updated during checkout based on your billing and shipping information.' === $text )
			return '';

		return $translated_text;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array();

			/*array(
				'title'    => __( 'Hide "Note: Shipping and taxes are estimated..." message on Cart page', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_cart_hide_shipping_and_taxes_estimated_message',
				'default'  => 'no',
				'type'     => 'checkbox',
			),*/

		// Cart Custom Info Options
		$settings[] = array( 'title' => __( 'Cart Custom Info Blocks', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_cart_custom_info_options' );

		$settings[] = array(
			'title'    => __( 'Total Blocks', 'woocommerce-jetpack' ),
			'id'       => 'wcj_cart_custom_info_total_number',
			'default'  => 1,
			'type'     => 'custom_number',
			'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			'custom_attributes'
					   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
		);

		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_cart_custom_info_options' );

		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) );

		for ( $i = 1; $i <= $total_number; $i++) {

			$settings = array_merge( $settings, array(

				array( 'title' => __( 'Info Block', 'woocommerce-jetpack' ) . ' #' . $i, 'type' => 'title', 'desc' => '', 'id' => 'wcj_cart_custom_info_options_' . $i, ),

				array(
					'title'    => __( 'Content', 'woocommerce-jetpack' ),
					'id'       => 'wcj_cart_custom_info_content_' . $i,
					'default'  => '[wcj_cart_items_total_weight before="Total weight: " after=" kg"]',
					'type'     => 'textarea',
					'css'      => 'width:30%;min-width:300px;height:100px;',
				),

				array(
					'title'    => __( 'Position', 'woocommerce-jetpack' ),
					'id'       => 'wcj_cart_custom_info_hook_' . $i,
					'default'  => 'woocommerce_after_cart_totals',
					'type'     => 'select',
					'options'  => array(

						'woocommerce_before_cart'                    => __( 'Before cart', 'woocommerce-jetpack' ),
						'woocommerce_before_cart_table'              => __( 'Before cart table', 'woocommerce-jetpack' ),
						'woocommerce_before_cart_contents'           => __( 'Before cart contents', 'woocommerce-jetpack' ),
						'woocommerce_cart_contents'                  => __( 'Cart contents', 'woocommerce-jetpack' ),
						'woocommerce_cart_coupon'                    => __( 'Cart coupon', 'woocommerce-jetpack' ),
						'woocommerce_cart_actions'                   => __( 'Cart actions', 'woocommerce-jetpack' ),
						'woocommerce_after_cart_contents'            => __( 'After cart contents', 'woocommerce-jetpack' ),
						'woocommerce_after_cart_table'               => __( 'After cart table', 'woocommerce-jetpack' ),
						'woocommerce_cart_collaterals'               => __( 'Cart collaterals', 'woocommerce-jetpack' ),
						'woocommerce_after_cart'                     => __( 'After cart', 'woocommerce-jetpack' ),

						'woocommerce_before_cart_totals'             => __( 'Before cart totals', 'woocommerce-jetpack' ),
						'woocommerce_cart_totals_before_shipping'    => __( 'Cart totals: Before shipping', 'woocommerce-jetpack' ),
						'woocommerce_cart_totals_after_shipping'     => __( 'Cart totals: After shipping', 'woocommerce-jetpack' ),
						'woocommerce_cart_totals_before_order_total' => __( 'Cart totals: Before order total', 'woocommerce-jetpack' ),
						'woocommerce_cart_totals_after_order_total'  => __( 'Cart totals: After order total', 'woocommerce-jetpack' ),
						'woocommerce_proceed_to_checkout'            => __( 'Proceed to checkout', 'woocommerce-jetpack' ),
						'woocommerce_after_cart_totals'              => __( 'After cart totals', 'woocommerce-jetpack' ),

						'woocommerce_before_shipping_calculator'     => __( 'Before shipping calculator', 'woocommerce-jetpack' ),
						'woocommerce_after_shipping_calculator'      => __( 'After shipping calculator', 'woocommerce-jetpack' ),

						'woocommerce_cart_is_empty'                  => __( 'If cart is empty', 'woocommerce-jetpack' ),
					),
					'css'      => 'width:250px;',
				),

				array(
					'title'    => __( 'Priority', 'woocommerce-jetpack' ),
					'id'       => 'wcj_cart_custom_info_priority_' . $i,
					'default'  => 10,
					'type'     => 'number',
					'css'      => 'width:250px;',
				),

				array( 'type'  => 'sectionend', 'id' => 'wcj_cart_custom_info_options_' . $i, ),
			) );
		}

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Cart();
