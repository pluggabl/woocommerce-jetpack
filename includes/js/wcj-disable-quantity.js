/**
 * wcj-disable-quantity.
 *
 * version 2.5.2
 * since   2.5.2
 */
jQuery(document).ready(function() {
	jQuery("div.quantity input.qty").each( function () {
		jQuery(this).attr("disabled", "disabled");
	});
});