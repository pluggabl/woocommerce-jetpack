/**
 * WooCommerce Jetpack - Timepicker JS
 *
 * @version 2.6.1
 * @author  Algoritmika Ltd.
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