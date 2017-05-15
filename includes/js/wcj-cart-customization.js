/**
 * wcj-cart-customization.
 *
 * version 2.8.0
 * since   2.8.0
 */
jQuery(document).ready( function() {
	jQuery( "p.return-to-shop a.button.wc-backward" ).each( function() {
		jQuery(this).text( wcj_cart_customization.return_to_shop_button_text );
	} );
} );