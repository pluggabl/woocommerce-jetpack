/**
 * wcj-datepicker.
 *
 * version 2.8.0
 */
jQuery(document).ready(function() {
	jQuery("input[display='date']").each( function () {
		var mindate = jQuery(this).attr("mindate");
		if (mindate === 'zero') {
			mindate = 0;
		}
		var maxdate = jQuery(this).attr("maxdate");
		if (maxdate === 'zero') {
			maxdate = 0;
		}
		jQuery(this).datepicker({
			dateFormat : jQuery(this).attr("dateformat"),
			minDate : mindate,
			maxDate : maxdate,
			firstDay : jQuery(this).attr("firstday"),
			changeYear: jQuery(this).attr("changeyear"),
			yearRange: jQuery(this).attr("yearrange")
		});
	});
});