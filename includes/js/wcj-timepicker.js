jQuery(document).ready(function() {
	jQuery("input[display='time']").each( function () {
		jQuery(this).timepicker({
			timeFormat : jQuery(this).attr("timeformat"),
			interval : jQuery(this).attr("interval")
		});
	});
});