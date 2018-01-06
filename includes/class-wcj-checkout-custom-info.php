<?php
/**
 * Booster for WooCommerce - Module - Checkout Custom Info
 *
 * @version 2.8.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Custom_Info' ) ) :

class WCJ_Checkout_Custom_Info extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'checkout_custom_info';
		$this->short_desc = __( 'Checkout Custom Info', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom info to WooCommerce checkout page.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-custom-info';
		parent::__construct();

		if ( $this->is_enabled() ) {
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_custom_info_total_number', 1 ) ); $i++) {
				add_action(
					get_option( 'wcj_checkout_custom_info_hook_' . $i, 'woocommerce_checkout_after_order_review' ),
					array( $this, 'add_checkout_custom_info' ),
					get_option( 'wcj_checkout_custom_info_priority_' . $i, 10 )
				);
			}
		}
	}

	/**
	 * add_checkout_custom_info.
	 *
	 * @version 2.4.7
	 */
	function add_checkout_custom_info() {
		$current_filter          = current_filter();
		$current_filter_priority = wcj_current_filter_priority();
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_checkout_custom_info_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if (
				''                       != get_option( 'wcj_checkout_custom_info_content_'  . $i ) &&
				$current_filter         === get_option( 'wcj_checkout_custom_info_hook_'     . $i ) &&
				$current_filter_priority == get_option( 'wcj_checkout_custom_info_priority_' . $i, 10 )
			) {
				echo do_shortcode( get_option( 'wcj_checkout_custom_info_content_' . $i ) );
			}
		}
	}

}

endif;

return new WCJ_Checkout_Custom_Info();
