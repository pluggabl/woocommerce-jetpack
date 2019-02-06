/**
 * wcj-order-quantities.js
 *
 * @version 4.2.0
 * @since   3.2.2
 * @todo    [dev] maybe `jQuery('[name=quantity]').val('0')` on `jQuery.isEmptyObject(product_quantities[variation_id])` (instead of `return`)
 */

function check_qty(){
	var variation_id = jQuery('[name=variation_id]').val();
	if (0 == variation_id) {
		return;
	}
	var current_qty = jQuery('[name=quantity]').val();

	if (jQuery.isEmptyObject(product_quantities[variation_id])){
		return;
	}

	if (quantities_options['reset_to_min']){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['min_qty']);
	} else if (quantities_options['reset_to_max']){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['max_qty']);
	} else if (current_qty < parseFloat(product_quantities[variation_id]['min_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['min_qty']);
	} else if (current_qty > parseFloat(product_quantities[variation_id]['max_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['max_qty']);
	}
}

function check_qty_no_reset(){
	var variation_id = jQuery('[name=variation_id]').val();
	if (0 == variation_id) {
		return;
	}
	var current_qty = jQuery('[name=quantity]').val();

	if (jQuery.isEmptyObject(product_quantities[variation_id])){
		return;
	}

	if (current_qty < parseFloat(product_quantities[variation_id]['min_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['min_qty']);
	} else if (current_qty > parseFloat(product_quantities[variation_id]['max_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['max_qty']);
	}
}

jQuery(document).ready(function(){
	jQuery('[name=variation_id]').on('change',check_qty);
	if (quantities_options['force_on_add_to_cart']){
		jQuery('.single_add_to_cart_button').on('click',check_qty_no_reset);
	}
});
