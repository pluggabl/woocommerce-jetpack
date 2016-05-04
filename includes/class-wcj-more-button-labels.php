<?php
/**
 * WooCommerce Jetpack More Button Labels
 *
 * The WooCommerce Jetpack More Button Labels class.
 *
 * @version 2.4.8
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_More_Button_Labels' ) ) :

class WCJ_More_Button_Labels extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	public function __construct() {

		$this->id         = 'more_button_labels';
		$this->short_desc = __( 'More Button Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce "Place order" button label.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-more-button-labels/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'set_order_button_text' ) );
		}
	}

	/**
	 * set_order_button_text.
	 */
	public function set_order_button_text( $current_text ) {
		$new_text = get_option( 'wcj_checkout_place_order_button_text' );
		return ( '' != $new_text ) ? $new_text : $current_text;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Place order (Order now) Button', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_checkout_place_order_button_options',
			),
			array(
				'title'    => __( 'Text', 'woocommerce-jetpack' ),
				'desc'     => __( 'leave blank for WooCommerce default', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Button on the checkout page.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_place_order_button_text',
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_checkout_place_order_button_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_More_Button_Labels();
