/**
 * wcj-offer-price.
 *
 * @version 2.8.3
 * @since   2.8.3
 * @todo    jQuery
 */

// Get the modal
var modal = document.getElementById('wcj-offer-price-modal');

// Get the button that opens the modal
var btn = document.getElementById('wcj-offer-price-button');

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on the button, open the modal
btn.onclick = function() {
	modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
	modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
	if (event.target == modal) {
		modal.style.display = "none";
	}
}
