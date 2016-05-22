/**
 * wcj-bookings.
 *
 * version 2.5.0
 * since   2.5.0
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

function change_price() {
	var date_from = jQuery("input[name='wcj_product_bookings_date_from']").val();
	var date_to = jQuery("input[name='wcj_product_bookings_date_to']").val();
	var is_variation_ok = true;
	if ( jQuery(".variations select").length ) {
		if ( jQuery(".variations select").find(":selected").val() == '' ) {
			is_variation_ok = false;
		}
	}
	if ( date_from && date_to && is_variation_ok ) {
		var d1 = new Date(date_from);
		var d2 = new Date(date_to);
		var t1 = d1.getTime();
		var t2 = d2.getTime();
		if ( t2 > t1 ) {
			var product_id = jQuery("input[type='hidden'][name='variation_id']").val();
			if ( ! product_id ) {
				product_id = ajax_object.product_id;
			}
			var data = {
				'action': 'price_change',
				'product_id': product_id,
				'date_from': date_from,
				'date_to': date_to
			};
			jQuery.post(ajax_object.ajax_url, data, function(response) {
				if ( '' != response ) {
					jQuery("p[class='price']").text(decodeEntities(response));
				}
			});
			jQuery("div[name='wcj_bookings_message']").css("display", "none");
			jQuery("div[name='wcj_bookings_message'] p").text('');
		} else {
			jQuery("div[name='wcj_bookings_message']").css("display", "block");
			jQuery("div[name='wcj_bookings_message'] p").text(ajax_object.wrong_dates_message);
			jQuery("p[class='price']").text(decodeEntities(ajax_object.original_price_html));
		}
	} else {
		jQuery("p[class='price']").text(decodeEntities(ajax_object.original_price_html));
	}
}

jQuery(document).ready(function() {
	change_price();
	jQuery("input[name^='wcj_product_bookings_date_']").each( function () {
		jQuery(this).change( change_price );
	});
});

jQuery(document.body).on('change','.variations select',change_price);
jQuery(document.body).on('change','input[name="wcj_variations"]',change_price);
