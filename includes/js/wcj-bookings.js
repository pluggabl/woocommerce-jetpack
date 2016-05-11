/**
 * wcj-bookings.
 *
 * version 2.4.9
 * since   2.4.9
 */
var decodeEntities = (function () {
	//create a new html document (doesn't execute script tags in child elements)
	var doc = document.implementation.createHTMLDocument("");
	var element = doc.createElement('div');

	function getText(str) {
		element.innerHTML = str;
		str = element.textContent;
		element.textContent = '';
		return str;
	}

	function decodeHTMLEntities(str) {
		if (str && typeof str === 'string') {
			var x = getText(str);
			while (str !== x) {
				str = x;
				x = getText(x);
			}
			return x;
		}
	}
	return decodeHTMLEntities;
})();
jQuery(document).ready(function() {
	jQuery("input[display='date']").each( function () {
		jQuery(this).change( function () {
			var product_id = jQuery("input[type='hidden'][name='variation_id']").val();
			if ( ! product_id ) {
				product_id = ajax_object.product_id;
			}
			var data = {
				'action': 'price_change',
				'product_id': product_id,
				'date_from': jQuery("input[name='wcj_product_bookings_date_from']").val(),
				'date_to': jQuery("input[name='wcj_product_bookings_date_to']").val()
			};
			jQuery.post(ajax_object.ajax_url, data, function(response) {
				if ( '' != response ) {
					jQuery("p[class='price']").text(decodeEntities(response));
				}
			});
		});
	});
});