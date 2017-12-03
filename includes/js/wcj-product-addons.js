/**
 * wcj-product-addons.
 *
 * @version 3.2.2
 * @since   2.5.3
 * @todo    `text` type - update price not only on change, but on each pressed key
 * @todo    fix the issue with custom price labels module
 */

var _ajax_object = ajax_object;

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
			product_id = _ajax_object.product_id;
		} else {
			if ( 0 == product_id ) {
				setTimeout(change_price, 100);
				return;
			}
			is_variable = true;
		}
		var data = {
			'action': 'product_addons_price_change',
			'product_id': product_id,
		};
		jQuery("input[name^='wcj_product_all_products_addons_'], input[name^='wcj_product_per_product_addons_']").each( function () {
			if (jQuery(this).is(':checked')) {
				data[jQuery(this).attr('name')] = jQuery(this).val();
			}
			if ('text'==jQuery(this).attr('type') && jQuery(this).val()!='') {
				data[jQuery(this).attr('name')] = jQuery(this).val();
			}
		});
		jQuery("select[name^='wcj_product_all_products_addons_'], select[name^='wcj_product_per_product_addons_']").each( function () {
			data[jQuery(this).attr('name')] = jQuery(this).find(':selected').val();
		});
		jQuery.post(_ajax_object.ajax_url, data, function(response) {
			if ( '' != response ) {
				if ( ! is_variable || _ajax_object.is_variable_with_single_price ) {
					jQuery("p[class='price']").html(response);
				} else if ( is_variable ) {
					jQuery("span[class='price']").html(response);
				}
			}
		});
	}
}

jQuery(document).ready(function() {
	change_price();
	jQuery("[name^='wcj_product_all_products_addons_'], [name^='wcj_product_per_product_addons_']").each( function () {
		jQuery(this).change( change_price );
	});
});

jQuery(document.body).on('change','.variations select',change_price);
jQuery(document.body).on('change','input[name="wcj_variations"]',change_price);
