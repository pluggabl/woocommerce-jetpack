<?php
/**
 * Booster for WooCommerce - Module - Add to Cart
 *
 * @version 5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Add_To_Cart' ) ) :

class WCJ_Add_To_Cart extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 */
	function __construct() {

		$this->id         = 'add_to_cart';
		$this->short_desc = __( 'Add to Cart Button Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change text for Add to Cart button by product type, by product category or for individual products (1 category group allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Change text for Add to Cart button by product type, by product category or for individual products.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-add-to-cart-labels';
		parent::__construct();

		if ( $this->is_enabled() ) {
			include_once( 'add-to-cart/class-wcj-add-to-cart-per-category.php' );
			include_once( 'add-to-cart/class-wcj-add-to-cart-per-product.php' );
			include_once( 'add-to-cart/class-wcj-add-to-cart-per-product-type.php' );
		}
	}

}

endif;

return new WCJ_Add_To_Cart();
