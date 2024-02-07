<?php
/**
 * Booster for WooCommerce - Module - Checkout Custom Info
 *
 * @version 7.1.6
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WCJ_Checkout_Custom_Info' ) ) :
	/**
	 * WCJ_Checkout_Custom_Info.
	 *
	 * @version 7.1.6
	 */
	class WCJ_Checkout_Custom_Info extends WCJ_Module {

		/**
		 * The module wcj_checkout_custom_info_total_number
		 *
		 * @var varchar $wcj_checkout_custom_info_total_number Module.
		 */
		public $wcj_checkout_custom_info_total_number;
		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 */
		public function __construct() {

			$this->id         = 'checkout_custom_info';
			$this->short_desc = __( 'Checkout Custom Info', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add custom info to the checkout page (1 block allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add custom info to the checkout page.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-checkout-custom-info';
			parent::__construct();
			$this->wcj_checkout_custom_info_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_checkout_custom_info_total_number', 1 ) );

			if ( $this->is_enabled() ) {

				for ( $i = 1; $i <= $this->wcj_checkout_custom_info_total_number; $i++ ) {
					add_action(
						get_option( 'wcj_checkout_custom_info_hook_' . $i, 'woocommerce_checkout_after_order_review' ),
						array( $this, 'add_checkout_custom_info' ),
						get_option( 'wcj_checkout_custom_info_priority_' . $i, 10 )
					);
				}
			}
		}

		/**
		 * Add_checkout_custom_info.
		 *
		 * @version 2.4.7
		 */
		public function add_checkout_custom_info() {
			$current_filter          = current_filter();
			$current_filter_priority = wcj_current_filter_priority();
			settype( $current_filter_priority, 'string' );
			$total_number = $this->wcj_checkout_custom_info_total_number;
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if (
					'' !== wcj_get_option( 'wcj_checkout_custom_info_content_' . $i ) &&
					wcj_get_option( 'wcj_checkout_custom_info_hook_' . $i ) === $current_filter &&
					wcj_get_option( 'wcj_checkout_custom_info_priority_' . $i, 10 ) === $current_filter_priority
				) {
					echo do_shortcode( wcj_get_option( 'wcj_checkout_custom_info_content_' . $i ) );
				}
			}
		}

	}

	endif;

	return new WCJ_Checkout_Custom_Info();
