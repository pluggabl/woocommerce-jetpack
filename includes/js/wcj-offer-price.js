/**
 * wcj-offer-price.
 *
 * @version 2.9.0
 * @since   2.9.0
 */

// Get the modal
var modal = jQuery('#wcj-offer-price-modal');

// When the user clicks on the button, open the modal
jQuery('#wcj-offer-price-button').click(function(){
	modal.css('display','block');
});

// When the user clicks on <span> (x), close the modal
jQuery('.wcj-offer-price-form-close').first().click(function(){
	modal.css('display','none');
});

// When the user clicks anywhere outside of the modal, close it
jQuery(window).click(function(e){
	if (modal.is(e.target)){
		modal.css('display','none');
	}
});
