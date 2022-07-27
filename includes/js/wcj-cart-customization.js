/**
 * Wcj-cart-customization.
 *
 * @version 5.6.2
 * @since   2.8.0
 * @todo    (maybe) fix when cart is emptied (i.e. after products removed)
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( "p.return-to-shop a.button.wc-backward" ).each(
			function() {
				jQuery( this ).text( wcj_cart_customization.return_to_shop_button_text );
			}
		);
	}
);
