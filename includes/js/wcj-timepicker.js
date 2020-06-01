/**
 * Booster for WooCommerce - Timepicker JS
 *
 * @version 2.7.0
 * @author  Pluggabl LLC.
 */
jQuery(document).ready(function() {
	jQuery("input[display='time']").each( function () {
		jQuery(this).timepicker({
			timeFormat : jQuery(this).attr("timeformat"),
			interval : jQuery(this).attr("interval"),
			minTime: jQuery(this).attr("mintime"),
			maxTime: jQuery(this).attr("maxtime")
		});
	});
});