/**
 * wcj-datepicker.
 *
 * version 2.7.2
 */
jQuery(document).ready(function() {
	jQuery("input[display='date']").each( function () {
		var dateformat = jQuery(this).attr("dateformat");
		if (typeof dateformat === typeof undefined || dateformat === false) {
			dateformat = 0;
		}
		var mindate = jQuery(this).attr("mindate");
		if (typeof mindate === typeof undefined || mindate === false) {
			mindate = 0;
		}
		var maxdate = jQuery(this).attr("maxdate");
		if (typeof maxdate === typeof undefined || maxdate === false) {
			maxdate = 0;
		}
		var firstday = jQuery(this).attr("firstday");
		if (typeof firstday === typeof undefined || firstday === false) {
			firstday = 0;
		}
		var changeyear = jQuery(this).attr("changeyear");
		if (typeof changeyear === typeof undefined || changeyear === false) {
			changeyear = 0;
		}
		var yearrange = jQuery(this).attr("yearrange");
		if (typeof yearrange === typeof undefined || yearrange === false) {
			yearrange = 0;
		}
		jQuery(this).datepicker({
			dateFormat : dateformat,
			minDate : mindate,
			maxDate : maxdate,
			firstDay : firstday,
			changeYear: changeyear,
			yearRange: yearrange
		});
	});
});