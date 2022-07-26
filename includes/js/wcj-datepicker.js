/**
 * Wcj-datepicker.
 *
 * @version 5.6.2
 * @todo    maybe_exclude_dates: `date.getDate()`, `date.getFullYear()`
 * @see     maybe_exclude_dates: https://stackoverflow.com/questions/501943/can-the-jquery-ui-datepicker-be-made-to-disable-saturdays-and-sundays-and-holid
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		jQuery( "input[display='date']" ).each(
			function () {
				var mindate = jQuery( this ).attr( "mindate" );
				if (mindate === 'zero') {
					mindate = 0;
				}
				var maxdate = jQuery( this ).attr( "maxdate" );
				if (maxdate === 'zero') {
					maxdate = 0;
				}
				jQuery( this ).datepicker(
					{
						dateFormat : jQuery( this ).attr( "dateformat" ),
						minDate : mindate,
						maxDate : maxdate,
						firstDay : jQuery( this ).attr( "firstday" ),
						changeYear: jQuery( this ).attr( "changeyear" ),
						yearRange: jQuery( this ).attr( "yearrange" ),
						beforeShowDay: maybe_exclude_dates,
					}
				);
				function maybe_exclude_dates(date){
					var exclude_days = jQuery( this ).attr( "excludedays" );
					if (typeof exclude_days !== typeof undefined && exclude_days !== false) {
						var day                 = date.getDay();
						var exclude_days_length = exclude_days.length;
						for (var i = 0; i < exclude_days_length; i++) {
							if (day == exclude_days[i]) {
								return [false];
							}
						}
					}
					var exclude_months = jQuery( this ).attr( "excludemonths" );
					if (typeof exclude_months !== typeof undefined && exclude_months !== false) {
						var month                 = date.getMonth() + 1;
						var exclude_months_length = exclude_months.length;
						for (var i = 0; i < exclude_months_length; i++) {
							if (month == exclude_months[i]) {
								return [false];
							}
						}
					}
					var blocked_dates = jQuery( this ).attr( "data-blocked_dates" );
					if (typeof blocked_dates !== typeof undefined && blocked_dates !== false) {
						var blocked_dates_arr = blocked_dates.split( ' ' );
						var datestring        = jQuery.datepicker.formatDate( jQuery( this ).attr( "data-blocked_dates_format" ), date );
						return [ blocked_dates_arr.indexOf( datestring ) == -1 ];
					}
					return [true];
				}
			}
		);
	}
);
