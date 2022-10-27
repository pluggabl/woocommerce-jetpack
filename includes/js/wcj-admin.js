/**
 * Wcj-admin.
 *
 * @version 5.6.7
 * @since   5.4.2
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function () {
		/* Quick Links Footer - Popup */
		let circleBadge = document.querySelector( '.circle-badge' ),
		subCircles      = document.querySelectorAll( '.subCircles > div' );
		if (null !== circleBadge) { // ADDED THIS.
			circleBadge.addEventListener( 'click', showCircles );
			function showCircles() {
				subCircles.forEach(
					circle => { circle.classList.toggle( "show" ); }
				)
			};
		}

		/* Klaviyo Email Subscription From Welcome Page */
		jQuery( "#subscribe-email .subscribe-email-btn" ).click(
			function() {
				var email = jQuery( "#subscribe-email input[name=user_email]" );
				var subscribe_email_nonce = jQuery( "#subscribe-email input[name=subscribe-email-nonce]" ).val();
				if (IsValidEmail( email.val() ) == false) {
					email.focus();
					return false;
				}

				var redirectUrl = window.location.origin + window.location.pathname + "?page=jetpack-getting-started&subscribe-email-nonce=" + subscribe_email_nonce + " &wcj-redirect=1&msg=";
				var msgId       = 3;
				var settings    = {
					"async": true,
					"crossDomain": true,
					"url": "https://manage.kmail-lists.com/ajax/subscriptions/subscribe",
					"method": "POST",
					"headers": {
						"content-type": "application/x-www-form-urlencoded",
						"cache-control": "no-cache"
					},
					"data": {
						"g": "RQJNvK",
						"$fields": "",
						"email": email.val(),
					}
				}
				jQuery.ajax( settings ).done(
					function (response) {
						if (response.success === true) {
							if (response.data.is_subscribed === false && response.data.email !== "" ) {
								msgId = 1; // Subscribe to the List.
							} else if (response.data.is_subscribed === true) {
								msgId = 2; // Email already subscribed.
							}
						}
						window.location = redirectUrl + msgId + "#subscribe-email";
					}
				);
			}
		);

		function IsValidEmail(email) {
			var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			return regex.test( email ) ? true : false;
		}
	}
);
