jQuery(document).ready(function() {
	jQuery("input[display='date']").each( function () {
		jQuery(this).datepicker({
			dateFormat : jQuery(this).attr("dateformat"),
			minDate : jQuery(this).attr("mindate"),
			maxDate : jQuery(this).attr("maxdate"),
			firstDay : jQuery(this).attr("firstday")
		});
	});
});