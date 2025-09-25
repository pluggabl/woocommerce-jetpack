/**
 * Sales Notifications
 *
 * @version 7.3.1
 * @package Booster_For_WooCommerce/js
 */

jQuery( document ).ready(
	function($) {
		var count = 0;

		function ajax_call() {
			count++;
			var wcj_sale_notification_hide_str = wcj_getCookie( 'wcj_sale_notification_hide' );
			if (wcj_sale_notification_hide_str == undefined) {
				var wcj_order_data_str = wcj_getCookie( 'wcj_order_data' );
				var data               = {
					action: 'wcj_sale_not_product_html_hpos',
					pageid: wcj_sn_ajax_object.pageid
				};
				jQuery.post(
					wcj_sn_ajax_object.ajax_url,
					data,
					function(response) {
						if (response.msg != "") {
							var order_id = response.order_id
							var already  = 0;
							wcj_setCookie( 'wcj_order_data', order_id, 365 );
							var wcj_order_data_str = wcj_getCookie( 'wcj_order_data' );
							if (typeof wcj_order_data_str != undefined && wcj_order_data_str != "undefined" && wcj_order_data_str != "") {
								wcj_order_data_str     = JSON.stringify( wcj_order_data_str );
								var wcj_order_data_arr = JSON.parse( "[" + wcj_order_data_str + "]" );
								if (wcj_order_data_arr.includes( order_id )) {
									var already    = 1;
									var warr_index = wcj_order_data_arr.indexOf( order_id );
									if (warr_index > -1) {
										wcj_order_data_arr.splice( warr_index, 1 );
										wcj_order_data_str = wcj_order_data_arr.toString();
										wcj_setCookie( 'wcj_order_data', wcj_order_data_str, 365 );
									}
								} else {
									wcj_order_data_arr.push( order_id );
									wcj_order_data_str = wcj_order_data_arr.toString();
									wcj_setCookie( 'wcj_order_data', wcj_order_data_str, 365 );
								}
							} else {
								wcj_setCookie( 'wcj_order_data', order_id, 365 );
							}

							if (response.new_order != 0) {
								if (response.new_order === 'hide') {
									jQuery( '.wcj_sale_notification' ).css( 'display', 'none' );
								} else {
									jQuery( '.wcj_sale_notification' ).css( 'display', 'block' );
								}
								if (response.wcj_sale_msg_hide_all != 'yes') {
									jQuery( '.wcj_sale_notification_close_div' ).css( 'display', 'none' );
								}
								if (response.image_enable == 'no') {
									jQuery( '.wcj_sale_notification_img' ).css( 'display', 'none' );
								}
								if (response.new_order != 'hide') {
									jQuery( '.wcj_sale_notification' ).removeClass( response.hidden_animation );
									jQuery( '.wcj_sale_notification' ).addClass( response.animation );
								}
								if (response.msg_position == 'wcj_bottom_right') {
									jQuery( '.wcj_sale_notification' ).addClass( 'bottom_right' );
								} else if (response.msg_position == 'wcj_bottom_left') {
									jQuery( '.wcj_sale_notification' ).addClass( 'bottom_left' );
								} else if (response.msg_position == 'wcj_top_right') {
									jQuery( '.wcj_sale_notification' ).addClass( 'top_right' );
								} else if (response.msg_position == 'wcj_top_left') {
									jQuery( '.wcj_sale_notification' ).addClass( 'top_left' );
								}
								jQuery( '.wcj_sale_notification_img' ).attr( 'src', response.thumb );
								jQuery( '.wcj_sale_notification_close' ).attr( 'src', response.close_img );
								jQuery( '.wcj_sale_notification_title' ).html( response.msg );
								if (response.msg_screen == 'wcj_desktop') {
									jQuery( '.wcj_sale_notification' ).addClass( 'desk_view' );
								} else if (response.msg_screen == 'wcj_mobile') {
									jQuery( '.wcj_sale_notification' ).addClass( 'mobile_view' );
								} else {
									jQuery( '.wcj_sale_notification' ).fadeIn( "2000" ).css( 'display', 'block' );
								}

								setTimeout(
									function() {
										if (response.new_order != 'hide') {
											jQuery( '.wcj_sale_notification' ).removeClass( response.animation );
											jQuery( '.wcj_sale_notification' ).addClass( response.hidden_animation );
										}
									},
									response.duration * 1000
								);
								if (response.loop === 'yes') {
									setTimeout(
										function() {
											ajax_call();
										},
										response.next_time_display * 1000
									);
								}
							}
						}
					}
				);
			}
		}

		$( ".wcj_sale_notification_close" ).click(
			function() {
				if ($( 'input.wcj_sale_notification_hide' ).is( ':checked' )) {
					wcj_setCookie( 'wcj_sale_notification_hide', '1', 365 );
				}
			}
		);

		$( "body" ).on(
			"click",
			".wcj_sn_close",
			function(){
				$( 'body' ).find( '.wcj_sale_notification' ).hide();
			}
		);

		// Set a Cookie.
		function wcj_setCookie(cName, cValue, expDays) {
			let date = new Date();
			date.setTime( date.getTime() + (expDays * 24 * 60 * 60 * 1000) );
			const expires   = "expires=" + date.toUTCString();
			document.cookie = cName + "=" + cValue + "; " + expires + "; path=/";
		}

		// Get a cookie.
		function wcj_getCookie(cName) {
			const name     = cName + "=";
			const cDecoded = decodeURIComponent( document.cookie );
			const cArr     = cDecoded.split( '; ' );
			let res;
			cArr.forEach(
				val => {
                    if (val.indexOf( name ) === 0) {
                        res = val.substring( name.length );
                    }
				}
			)
			return res;
		}

		setTimeout(
			function() {
				ajax_call();
			},
			5000
		);

	}
);
