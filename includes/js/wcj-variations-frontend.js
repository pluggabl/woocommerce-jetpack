/**
 * Booster for WooCommerce - Variations Radio Buttons
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

/**
 * maybe_hide_unavailable.
 *
 * @version 2.9.0
 * @since   2.9.0
 */
function maybe_hide_unavailable(variation) {
	if ( ! variation.is_purchasable || ! variation.is_in_stock || ! variation.variation_is_visible ) {
		jQuery( '.single_add_to_cart_button' ).removeClass( 'wc-variation-selection-needed' ).addClass( 'disabled wc-variation-is-unavailable' );
		jQuery( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-enabled' ).addClass( 'woocommerce-variation-add-to-cart-disabled' );
	}
}

/**
 * process_variations.
 *
 * @version 2.9.0
 * @since   2.9.0
 */
function process_variations(variation_id) {
	var data_product_variations = jQuery.parseJSON(jQuery("form.variations_form.cart").attr('data-product_variations'));
	data_product_variations.forEach(function(variation){
		if(variation_id == variation.variation_id){
			maybe_hide_unavailable(variation);
			jQuery("form.variations_form.cart").wc_variations_image_update(variation);
			jQuery("div.woocommerce-variation-price").html(variation.price_html);
			jQuery("div.woocommerce-variation-availability").html(variation.availability_html);
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
