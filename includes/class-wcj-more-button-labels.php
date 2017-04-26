<?php
/**
 * WooCommerce Jetpack More Button Labels
 *
 * The WooCommerce Jetpack More Button Labels class.
 *
 * @version 2.7.2
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_More_Button_Labels' ) ) :

class WCJ_More_Button_Labels extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.0
	 */
	function __construct() {

		$this->id         = 'more_button_labels';
		$this->short_desc = __( 'More Button Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce "Place order" button label.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-more-button-labels/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'set_order_button_text' ), PHP_INT_MAX );
		}
	}

	/**
	 * set_order_button_text.
	 *
	 * @version 2.7.2
	 */
	function set_order_button_text( $current_text ) {
		return ( '' != ( $new_text = get_option( 'wcj_checkout_place_order_button_text', '' ) ) ) ? $new_text : $current_text;
	}

}

endif;

return new WCJ_More_Button_Labels();
