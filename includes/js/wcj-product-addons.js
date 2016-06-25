/**
 * wcj-product-addons.
 *
 * version 2.5.3
 * since   2.5.3
 */
var product_addons_decodeEntities = (function () {
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
	var is_variation_ok = true;
	if ( jQuery(".variations select").length ) {
		if ( jQuery(".variations select").find(":selected").val() == '' ) {
			is_variation_ok = false;
		}
	}
	if ( is_variation_ok ) {
		var product_id = jQuery("input[type='hidden'][name='variation_id']").val();
		if ( ! product_id ) {
			product_id = ajax_object.product_id;
		}
		var data = {
			'action': 'product_addons_price_change',
			'product_id': product_id,
		};
		jQuery("input[name^='wcj_product_all_products_addons_']").each( function () {
			if (jQuery(this).is(':checked')) {
				data[jQuery(this).attr('name')] = '1';
			}
		});
		jQuery("input[name^='wcj_product_per_product_addons_']").each( function () {
			if (jQuery(this).is(':checked')) {
				data[jQuery(this).attr('name')] = '1';
			}
		});
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			if ( '' != response ) {
				jQuery("p[class='price']").text(product_addons_decodeEntities(response));
			}
		});
	} else {
		jQuery("p[class='price']").text(product_addons_decodeEntities(ajax_object.original_price_html));
	}
}

jQuery(document).ready(function() {
	change_price();
	jQuery("input[name^='wcj_product_all_products_addons_']").each( function () {
		jQuery(this).change( change_price );
	});
	jQuery("input[name^='wcj_product_per_product_addons_']").each( function () {
		jQuery(this).change( change_price );
	});
});

jQuery(document.body).on('change','.variations select',change_price);
jQuery(document.body).on('change','input[name="wcj_variations"]',change_price);
