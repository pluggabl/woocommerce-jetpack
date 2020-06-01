/**
 * Booster for WooCommerce - Variations Radio Buttons
 *
 * @version 4.3.0
 * @author  Pluggabl LLC.
 */

/**
 * Finds correspondent WooCommerce dropdown and triggers the 'change' event manually.
 * That will make WooCommerce itself do the rest, like update image, prices, and so on.
 *
 * @version 4.3.0
 * @since   4.3.0
 * @param variation_id
 */
function select_wc_dropdown_programmatically(variation_id) {
	var variations = jQuery("form.variations_form.cart").data('product_variations');
	var attributes = {};
	variations.forEach(function (variation, index) {
		if (variation.variation_id == variation_id) {
			attributes = variation.attributes;
		}
	});
	if (Object.keys(attributes).length !== 0) {
		Object.keys(attributes).forEach(function (index) {
			var select = jQuery("[name*='" + index + "']");
			var value = attributes[index];
			if (value != "") {
				var opt = select.find('option[value="' + value + '"]');
			} else {
				var opt = select.find('option[value!=""]').eq(0);
			}
			opt.prop('selected', true);
			select.trigger('change');
		});
	}
}

/**
 * process_variations.
 *
 * @version 4.3.0
 * @since   2.9.0
 */
function process_variations(variation_id) {
	var data_product_variations = jQuery.parseJSON(jQuery("form.variations_form.cart").attr('data-product_variations'));
	data_product_variations.forEach(function (variation) {
		if (variation_id == variation.variation_id) {
			select_wc_dropdown_programmatically(variation_id);
		}
	});
}

/**
 * hide_all.
 *
 * @version 2.9.0
 * @since   2.9.0
 */
function hide_all() {
	jQuery("div.woocommerce-variation-availability").hide();
	jQuery("div.woocommerce-variation-price").hide();
	jQuery( '.single_add_to_cart_button' ).removeClass( 'wc-variation-is-unavailable' ).addClass( 'disabled wc-variation-selection-needed' );
	jQuery( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-enabled' ).addClass( 'woocommerce-variation-add-to-cart-disabled' );
}

/**
 * show_all.
 *
 * @version 2.9.0
 * @since   2.9.0
 */
function show_all() {
	jQuery("div.woocommerce-variation-availability").show();
	jQuery("div.woocommerce-variation-price").show();
	jQuery( '.single_add_to_cart_button' ).removeClass( 'disabled wc-variation-selection-needed wc-variation-is-unavailable' );
	jQuery( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-disabled' ).addClass( 'woocommerce-variation-add-to-cart-enabled' );
}

/**
 * fill_values.
 *
 * @version 2.9.0
 * @since   2.9.0
 */
function fill_values(variation_id,radio_element) {
	jQuery("input:hidden[name='variation_id']").val(variation_id);
	jQuery(radio_element.attributes).each(
		function(i, attribute){
			if(attribute.name.match("^attribute_")){
				jQuery("input:hidden[name='" + attribute.name + "']").val(attribute.value);
			}
		}
	);
}

/**
 * document ready.
 *
 * @version 2.9.0
 */
jQuery(document).ready(function() {
	// Initial display
	jQuery("form.variations_form.cart").on('wc_variation_form',function(){
		if(jQuery("input:radio[name='wcj_variations']").is(':checked')){
			show_all();
			var checked_radio = jQuery("input:radio[name='wcj_variations']:checked");
			var variation_id = checked_radio.attr("variation_id");
			fill_values(variation_id, checked_radio[0]);
			process_variations(variation_id);
		} else {
			hide_all();
		}
	});
	// On change
	jQuery("input:radio[name='wcj_variations']").change(
		function(){
			show_all();
			var variation_id = jQuery(this).attr("variation_id");
			fill_values(variation_id, this);
			process_variations(variation_id);
		}
	);
});
