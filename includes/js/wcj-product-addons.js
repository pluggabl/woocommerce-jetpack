/**
 * wcj-product-addons.
 *
 * version 2.5.3
 * since   2.5.3
 */

function change_price() {
	var is_variation_ok = true;
	if ( jQuery(".variations select").length ) {
		if ( jQuery(".variations select").find(":selected").val() == '' ) {
			is_variation_ok = false;
		}
	}
	if ( is_variation_ok ) {
		var product_id = jQuery("input[type='hidden'][name='variation_id']").val();
		var is_variable;
		if ( ! product_id ) {
			is_variable = false;
			product_id = ajax_object.product_id;
		} else {
			is_variable = true;
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
				if ( ! is_variable ) {
					jQuery("p[class='price']").html(response);
				} else {
					jQuery("span[class='price']").html(response);
				}
			}
		});
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
