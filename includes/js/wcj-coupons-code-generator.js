/**
 * Wcj-coupons-code-generator.
 *
 * @version 7.5.0
 * @since   3.1.3
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		if ( '' === jQuery( "#title" ).val() ) {
			var data = {
				'action': 'wcj_generate_coupon_code',
				'security': ajax_object.nonce // added nonce
			};
			jQuery.ajax(
				{
					type: "POST",
					url: ajax_object.ajax_url,
					data: data,
					success: function(response) {
						if ( '' !== response && '' === jQuery( "#title" ).val() ) {
							jQuery( "#title" ).val( response );
							jQuery( "#title-prompt-text" ).html( '' );
						}
					},
				}
			);
		}
	}
);
