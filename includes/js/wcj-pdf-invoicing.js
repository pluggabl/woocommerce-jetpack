/**
 * wcj-pdf-invoicing.
 *
 * version 2.5.2
 * since   2.5.2
 */
jQuery(document).ready(function() {
	jQuery('a.wcj_need_confirmation').click(function() {
		return confirm("Are you sure?");
	});
});