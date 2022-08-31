<?php
/**
 * Booster for WooCommerce - Module - Mini Cart Custom Info
 *
 * @version 5.6.3
 * @since   2.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Mini_Cart' ) ) :
	/**
	 * WCJ_Mini_Cart.
	 */
	class WCJ_Mini_Cart extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 */
		public function __construct() {

			$this->id         = 'mini_cart';
			$this->short_desc = __( 'Mini Cart Custom Info', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add custom info to the mini cart widget (1 block allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add custom info to the mini cart widget.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-mini-cart';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );
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
		 * Add_mini_cart_custom_info.
		 *
		 * @version 5.6.3
		 */
		public function add_mini_cart_custom_info() {
			$current_filter          = current_filter();
			$current_filter_priority = wcj_current_filter_priority();
			$total_number            = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_mini_cart_custom_info_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if (
				'' !== wcj_get_option( 'wcj_mini_cart_custom_info_content_' . $i ) &&
				wcj_get_option( 'wcj_mini_cart_custom_info_hook_' . $i, 'woocommerce_after_mini_cart' ) === $current_filter &&
				wcj_get_option( 'wcj_mini_cart_custom_info_priority_' . $i, 10 ) === (string) $current_filter_priority
				) {
					echo do_shortcode( wcj_get_option( 'wcj_mini_cart_custom_info_content_' . $i ) );
				}
			}
		}

	}

endif;

return new WCJ_Mini_Cart();
