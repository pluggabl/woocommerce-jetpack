/**
 * Wcj-offer-price.
 *
 * @version 5.6.2
 * @since   2.9.0
 * @package Booster_For_WooCommerce/includes/JS
 */

// Get the modal.
var modal = jQuery( '#wcj-offer-price-modal' );

// When the user clicks on the button, fill in values and open the modal.
jQuery( '.wcj-offer-price-button' ).click(
	function(){
		// Get data.
		var wcj_data = jQuery.parseJSON( jQuery( this ).attr( 'wcj_data' ) );
		// Fill in price input.
		var price_input = jQuery( '#wcj-offer-price-price' );
		price_input.attr( 'step',wcj_data['price_step'] );
		price_input.attr( 'min',wcj_data['min_price'] );
		if (0 != wcj_data['max_price']) {
			price_input.attr( 'max',wcj_data['max_price'] );
		}
		if (0 != wcj_data['default_price']) {
			price_input.val( wcj_data['default_price'] );
		}
		jQuery( '#wcj-offer-price-price-label' ).html( wcj_data['price_label'] );
		// Fill in form header.
		jQuery( '#wcj-offer-form-header' ).html( wcj_data['form_header'] );
		// Product ID (hidden input).
		jQuery( '#wcj-offer-price-product-id' ).val( wcj_data['product_id'] );
		// Show the form.
		modal.css( 'display','block' );
	}
);

// When the user clicks on <span> (x), close the modal.
jQuery( '.wcj-offer-price-form-close' ).first().click(
	function(){
		modal.css( 'display','none' );
	}
);

// When the user clicks anywhere outside of the modal, close it.
jQuery( window ).click(
	function(e){
		if (modal.is( e.target )) {
			modal.css( 'display','none' );
		}
	}
);

// When the user presses ESC, close the modal.
jQuery( document ).keyup(
	function(e){
		if (27 === e.keyCode) { // esc.
			modal.css( 'display','none' );
		}
	}
);
