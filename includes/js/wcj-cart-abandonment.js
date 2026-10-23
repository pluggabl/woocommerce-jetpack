/**	
 * wcj-cart-abandonment
 *
 * @version 7.2.7
 */

 jQuery(document).ready(function ($) {

 	"use strict";

 	function wcj_ca_check_email(value) {
		if(value){
			var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
	        if(value.match(mailformat))
	        {
	            return true;    
	        }
	        else
	        {
	            return false;
	        }
		}
		return false;
	}

	function WC_checkout_data() {
		var billing_email = jQuery( '#billing_email' ).val();
		var billing_phone = jQuery( '#billing_phone' ).val();

		if ( typeof billing_email === 'undefined' ) {
			return;
		}

		if ( typeof billing_phone === 'undefined' || billing_phone === null ) {
			//If phone number field does not exist on the Checkout form
			billing_phone = '';
		}

		if ( ! (wcj_ca_check_email(billing_email)) || billing_phone.length >= 5 ) {
			
			var billing_first_name = jQuery('#billing_first_name' ).val();
			var billing_last_name = jQuery('#billing_last_name' ).val();
			var billing_phone = jQuery( '#billing_phone' ).val();
			var billing_country = jQuery( '#billing_country' ).val();
			var billing_city = jQuery( '#billing_city' ).val();
			var billing_company = jQuery( '#billing_company' ).val();
			var billing_address_1 = jQuery('#billing_address_1').val();
			var billing_address_2 = jQuery('#billing_address_2').val();
			var billing_state = jQuery( '#billing_state' ).val();
			var billing_postcode = jQuery( '#billing_postcode' ).val();
			var shipping_first_name = jQuery('#shipping_first_name').val();
			var shipping_last_name = jQuery('#shipping_last_name').val();
			var shipping_company = jQuery( '#shipping_company' ).val();
			var shipping_country = jQuery( '#shipping_country' ).val();
			var shipping_address_1 = jQuery('#shipping_address_1').val();
			var shipping_address_2 = jQuery('#shipping_address_2').val();
			var shipping_city = jQuery( '#shipping_city' ).val();
			var shipping_state = jQuery( '#shipping_state' ).val();
			var shipping_postcode = jQuery('#shipping_postcode').val();
			var order_comments = jQuery( '#order_comments' ).val();
			var coupon_code = jQuery( '#coupon_code' ).val();
			var wpnonce = jQuery( '#woocommerce-process-checkout-nonce' ).val();

			var data = {
				action: 'wcj_save_cart_abandonment_data',
				post_id: ajax_object.post_id,
				billing_email: billing_email,
				billing_first_name: billing_first_name,
				billing_last_name: billing_last_name,
				billing_phone: billing_phone,
				billing_country: billing_country,
				billing_city: billing_city,
				billing_company: billing_company,
				billing_address_1: billing_address_1,
				billing_address_2: billing_address_2,
				billing_state: billing_state,
				billing_postcode: billing_postcode,
				shipping_first_name: shipping_first_name,
				shipping_last_name: shipping_last_name,
				shipping_company: shipping_company,
				shipping_country: shipping_country,
				shipping_address_1: shipping_address_1,
				shipping_address_2: shipping_address_2,
				shipping_city: shipping_city,
				shipping_state: shipping_state,
				shipping_postcode: shipping_postcode,
				order_comments: order_comments,
				coupon_code: coupon_code,
				wpnonce: wpnonce,
			};
			
			if ( wcj_ca_check_email( data.billing_email ) ) {
				jQuery.post(
					ajax_object.ajax_url,
					data,
					function ( response ) {
						console.log(response);
					}
				);
			}
		}
	}

	$(document).on('keyup keypress change','#billing_email, #billing_phone, input.input-text, textarea.input-text, select',function(){
		WC_checkout_data();
	});

	$(document).on('updated_checkout',function(){
		WC_checkout_data();
	});

	setTimeout( function () {
		WC_checkout_data();
	}, 1000 );

});