/**
 * Booster for WooCommerce - Timepicker JS
 *
 * @version 5.6.2
 * @author  Pluggabl LLC.
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( "input[display='time']" ).each(
			function () {
				jQuery( this ).timepicker(
					{
						timeFormat : jQuery( this ).attr( "timeformat" ),
						interval : jQuery( this ).attr( "interval" ),
						minTime: jQuery( this ).attr( "mintime" ),
						maxTime: jQuery( this ).attr( "maxtime" )
					}
				);
			}
		);
	}
);
