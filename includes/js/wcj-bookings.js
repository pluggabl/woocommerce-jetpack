/**
 * wcj-bookings.
 *
 * version 4.3.0
 * since   2.5.0
 */

var _ajax_object = ajax_object;

function wcj_hide_loader(){
	jQuery('.wcj-bookings-price-wrapper').removeClass('loading');
}

function wcj_show_loader(){
	jQuery('.wcj-bookings-price-wrapper').addClass('loading');
}

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
				product_id = _ajax_object.product_id;
			}
			var data = {
				'action': 'price_change',
				'product_id': product_id,
				'date_from': date_from,
				'date_to': date_to
			};
			wcj_show_loader();
			jQuery('.wcj-bookings-price-wrapper .wcj-value').html('');
			jQuery.post(_ajax_object.ajax_url, data, function(response) {
				if ( '' != response ) {
					jQuery('.wcj-bookings-price-wrapper .wcj-value').html('<p class="price wcj-price-bookings">'+response+'</p>');
					wcj_hide_loader();
				}
			});
			jQuery("div[name='wcj_bookings_message']").css("display", "none");
			jQuery("div[name='wcj_bookings_message'] p").text('');
		} else {
			jQuery("div[name='wcj_bookings_message']").css("display", "block");
			jQuery("div[name='wcj_bookings_message'] p").text(_ajax_object.wrong_dates_message);
			jQuery('.wcj-bookings-price-wrapper .wcj-value').html('');
		}
	} else {
		jQuery('.wcj-bookings-price-wrapper .wcj-value').html('');
		jQuery("p[class='price wcj-price-bookings']").css("display", "none");
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
