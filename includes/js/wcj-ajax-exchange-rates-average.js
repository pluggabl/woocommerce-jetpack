/**
 * Wcj-ajax-exchange-rates-average.js
 *
 * @version 6.0.0
 * @since   3.2.2
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( ".wcj_grab_average_currency_exchange_rate" ).click(
			function(){
				var input_id = '#' + this.getAttribute( 'input_id' );
				var data     = {
					'action': 'wcj_ajax_get_exchange_rates_average',
					'wcj_currency_from': this.getAttribute( 'currency_from' ),
					'wcj_currency_to': this.getAttribute( 'currency_to' ),
					'wcj_start_date': this.getAttribute( 'start_date' ),
					'wcj_end_date': this.getAttribute( 'end_date' ),
					'wpnonce': ajax_object.wpnonce,
				};
				jQuery( input_id ).prop( 'readonly', true );
				jQuery.ajax(
					{
						type: "POST",
						url: ajax_object.ajax_url,
						data: data,
						success: function(response) {
							if ( 0 != response ) {
								jQuery( input_id ).val( parseFloat( response ) );
							}
						},
						complete: function() {
							jQuery( input_id ).prop( 'readonly', false );
						},
					}
				);
				return false;
			}
		);
	}
);
