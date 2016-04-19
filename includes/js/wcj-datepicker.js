/**
 * wcj-datepicker.
 *
 * version 2.4.7
 */
jQuery(document).ready(function() {
	jQuery("input[display='date']").each( function () {
		jQuery(this).datepicker({
			dateFormat : jQuery(this).attr("dateformat"),
			minDate : jQuery(this).attr("mindate"),
			maxDate : jQuery(this).attr("maxdate"),
			firstDay : jQuery(this).attr("firstday"),
			changeYear: jQuery(this).attr("changeyear"),
			yearRange: jQuery(this).attr("yearrange")
		});
	});
});