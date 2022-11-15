/**
 * Wcj-product-addons.
 *
 * @version 5.6.8
 * @since   2.5.3
 * @todo    `text` type - update price not only on change, but on each pressed key
 * @todo    fix the issue with custom price labels module
 * @package Booster_For_WooCommerce/includes/JS
 */

var _ajax_object = ajax_object;

function change_price() {
	var is_variation_ok = true;
	if ( jQuery( ".variations select" ).length ) {
		if ( jQuery( ".variations select" ).find( ":selected" ).val() == '' ) {
			is_variation_ok = false;
		}
	}
	if ( is_variation_ok ) {
		var product_id = jQuery( "input[type='hidden'][name='variation_id']" ).val();
		var wpnonce = jQuery( "input[type='hidden'][name='wcj_product_addons-nonce']" ).val();
		var is_variable;
		if ( ! product_id ) {
			is_variable = false;
			product_id  = _ajax_object.product_id;
		} else {
			if ( 0 == product_id ) {
				setTimeout( change_price, 100 );
				return;
			}
			is_variable = true;
		}
		var data = {
			'action': 'product_addons_price_change',
			'product_id': product_id,
			'wpnonce': wpnonce,
		};
		jQuery( "input[name^='wcj_product_all_products_addons_'], input[name^='wcj_product_per_product_addons_']" ).each(
			function () {
				if (jQuery( this ).is( ':checked' )) {
					data[jQuery( this ).attr( 'name' )] = jQuery( this ).val();
				}
				if ('text' == jQuery( this ).attr( 'type' ) && jQuery( this ).val() != '') {
					data[jQuery( this ).attr( 'name' )] = jQuery( this ).val();
				}
			}
		);
		jQuery( "select[name^='wcj_product_all_products_addons_'], select[name^='wcj_product_per_product_addons_']" ).each(
			function () {
				data[jQuery( this ).attr( 'name' )] = jQuery( this ).find( ':selected' ).val();
			}
		);
		jQuery.post(
			_ajax_object.ajax_url,
			data,
			function(response) {
				if ('' != response) {
					var ignore_strikethrough_str = 'yes' === ajax_object.ignore_strikethrough_price ? '*:not(del)' : '';
					if ( ! is_variable || _ajax_object.is_variable_with_single_price) {
						var amount = jQuery( "p[class='price'] " + ignore_strikethrough_str + " .amount" );
						if ( ! amount.length) {
							amount = jQuery( "p[class='price'] .amount" );
						}
						amount.replaceWith( response );
					} else if (is_variable) {
						var amount = jQuery( ".woocommerce-variation-price span[class='price'] " + ignore_strikethrough_str + " .amount" );
						if ( ! amount.length) {
							amount = jQuery( ".woocommerce-variation-price span[class='price'] .amount" );
						}
						amount.replaceWith( response );
					}
				}
			}
		);
	}
}

jQuery( document ).ready(
	function() {
		change_price();
		jQuery( "[name^='wcj_product_all_products_addons_'], [name^='wcj_product_per_product_addons_']" ).each(
			function () {
				jQuery( this ).change( change_price );
			}
		);

		// Handle "Enable by Variation" option.
		function handle_enable_by_variation() {
			sanitize_variation_addon_fields_array();
			var addon_pattern = 'wcj_product_per_product_addons_';
			function hide_variation_addon_fields() {
				var enable_by_variation        = _ajax_object.enable_by_variation;
				var enable_by_variation_length = _ajax_object.enable_by_variation.length;
				for (i = 0; i < enable_by_variation_length; i++) {
					if (Array.isArray( enable_by_variation[i] )) {
						var addons = jQuery( 'input[name="' + addon_pattern + (i + 1) + '"],label[for*="' + addon_pattern + (i + 1) + '"],*[class*="' + addon_pattern + (i + 1) + '"]' );
						addons.each(
							function () {
								if (jQuery( this ).is( '[required]' )) {
									jQuery( this ).removeAttr( 'required' );
									jQuery( this ).attr( 'data-required', true );
								}
							}
						);
						addons.hide();
					}
				}
			}
			function show_addon_field_by_variation_id(variation_id) {
				hide_variation_addon_fields();
				var enable_by_variation        = _ajax_object.enable_by_variation;
				var enable_by_variation_length = _ajax_object.enable_by_variation.length;
				for (i = 0; i < enable_by_variation_length; i++) {
					if (Array.isArray( enable_by_variation[i] ) && enable_by_variation[i].indexOf( parseInt( variation_id ) ) != -1) {
						var addons = jQuery( 'input[name="' + addon_pattern + (i + 1) + '"],label[for="' + addon_pattern + (i + 1) + '"],*[class*="' + addon_pattern + (i + 1) + '"]' );
						addons.each(
							function() {
								if (jQuery( this ).is( '[data-required]' )) {
									jQuery( this ).attr( 'required','required' );
								}
							}
						);
						addons.show();
					}
				}
			}
			function sanitize_variation_addon_fields_array(){
				if (Array.isArray( _ajax_object.enable_by_variation )) {
					_ajax_object.enable_by_variation = _ajax_object.enable_by_variation.map(
						function (e) {
							if (Array.isArray( e )) {
								e = e.map(
									function (e) {
										e = parseInt( e );
										return e;
									}
								);
							}
							return e;
						}
					);
				}
			}
			if (Array.isArray( _ajax_object.enable_by_variation )) {
				hide_variation_addon_fields();
			}
			jQuery( document ).on(
				'found_variation',
				'form.cart',
				function (event, variation) {
					var variation_id = variation.variation_id;
					if (Array.isArray( _ajax_object.enable_by_variation )) {
						show_addon_field_by_variation_id( variation_id );
					}
				}
			);
		}
		handle_enable_by_variation();

	}
);

jQuery( document.body ).on( 'change','.variations select',change_price );
jQuery( document.body ).on( 'change','input[name="wcj_variations"]',change_price );
