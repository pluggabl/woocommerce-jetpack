/**
 * wcj-order-quantities.js
 *
 * @version 3.2.2
 * @version 3.2.2
 */

function check_qty(){
	var variation_id = jQuery('[name=variation_id]').val();
	if (0 == variation_id) {
		return;
	}
	var current_qty = jQuery('[name=quantity]').val();
	if (current_qty < parseInt(product_quantities[variation_id]['min_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['min_qty']);
		return;
	}
	if (current_qty > parseInt(product_quantities[variation_id]['max_qty'])){
		jQuery('[name=quantity]').val(product_quantities[variation_id]['max_qty']);
		return;
	}
}

jQuery(document).ready(function(){
	jQuery('[name=variation_id]').on('change',check_qty);
});
