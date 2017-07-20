/**
 * wcj-datepicker.
 *
 * @version 3.0.0
 * @todo    maybe_exclude_dates: `date.getDate()`, `date.getFullYear()`
 * @see     maybe_exclude_dates: https://stackoverflow.com/questions/501943/can-the-jquery-ui-datepicker-be-made-to-disable-saturdays-and-sundays-and-holid
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
			yearRange: jQuery(this).attr("yearrange"),
			beforeShowDay: maybe_exclude_dates,
		});
		function maybe_exclude_dates(date){
			var exclude_days = jQuery(this).attr("excludedays");
			if (typeof exclude_days !== typeof undefined && exclude_days !== false) {
				var day = date.getDay();
				for (var i = 0; i < exclude_days.length; i++) {
					if (day == exclude_days[i]) {
						return [false];
					}
				}
			}
			var exclude_months = jQuery(this).attr("excludemonths");
			if (typeof exclude_months !== typeof undefined && exclude_months !== false) {
				var month = date.getMonth() + 1;
				for (var i = 0; i < exclude_months.length; i++) {
					if (month == exclude_months[i]) {
						return [false];
					}
				}
			}
			return [true];
		}
	});
});
