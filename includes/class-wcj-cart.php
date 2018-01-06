<?php
/**
 * Booster for WooCommerce - Module - Cart Custom Info
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cart' ) ) :

class WCJ_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'cart';
		$this->short_desc = __( 'Cart Custom Info', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom info to WooCommerce cart page.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cart-custom-info';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Cart items table custom info
			add_filter( 'woocommerce_cart_item_name', array( $this, 'add_custom_info_to_cart_item_name' ), PHP_INT_MAX, 3 );
			// Cart custom info
			$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++) {
				add_action(
					get_option( 'wcj_cart_custom_info_hook_' . $i, 'woocommerce_after_cart_totals' ),
					array( $this, 'add_cart_custom_info' ),
					get_option( 'wcj_cart_custom_info_priority_' . $i, 10 )
				);
			}
		}
	}

	/**
	 * add_custom_info_to_cart_item_name.
	 *
	 * @version 2.3.9
	 * @since   2.3.9
	 * @todo    (maybe) `wc_setup_product_data( $post );`
	 */
	function add_custom_info_to_cart_item_name( $product_title, $cart_item, $cart_item_key ) {
		$custom_content = get_option( 'wcj_cart_custom_info_item' );
		if ( '' != $custom_content ) {
			global $post;
			$post = get_post( $cart_item['product_id'] );
			setup_postdata( $post );
			$product_title .= do_shortcode( $custom_content );
		}
		return $product_title;
	}

	/**
	 * add_cart_custom_info.
	 *
	 * @version 2.4.6
	 */
	function add_cart_custom_info() {
		$current_filter = current_filter();
		$current_filter_priority = wcj_current_filter_priority();
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_cart_custom_info_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if (
				''                       != get_option( 'wcj_cart_custom_info_content_'  . $i ) &&
				$current_filter         === get_option( 'wcj_cart_custom_info_hook_'     . $i, 'woocommerce_after_cart_totals' ) &&
				$current_filter_priority == get_option( 'wcj_cart_custom_info_priority_' . $i, 10 )
			) {
				echo do_shortcode( get_option( 'wcj_cart_custom_info_content_' . $i ) );
			}
		}
	}

}

endif;

return new WCJ_Cart();
