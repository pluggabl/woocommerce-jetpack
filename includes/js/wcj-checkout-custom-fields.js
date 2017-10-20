/**
 * wcj-checkout-custom-fields.
 *
 * @version 3.2.0
 * @since   3.2.0
 */

jQuery(document).ready(function() {
	for (var i = 0, len = wcj_checkout_custom_fields.select2_fields.length; i < len; i++) {
		jQuery("#"+wcj_checkout_custom_fields.select2_fields[i]).select2();
	}
});
