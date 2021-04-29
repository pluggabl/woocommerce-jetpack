/**
* wcj-checkout-core-fields.
*
* @version 5.3.7
* @since   5.3.7
*/

jQuery(document).ready(function () {
    jQuery.each(wcj_checkout_core_fields, function (i, wcj_checkout_core_field_sections) {
        jQuery.each(wcj_checkout_core_field_sections, function (i, wcj_checkout_core_field_section) {
            jQuery.each(wcj_checkout_core_field_section, function (field, wcj_checkout_core_field) {
                jQuery("p#" + field + '_field' + " label").each(function () {
                    var required_star = '<abbr class="required" title="required">*</abbr>'
                    var is_required = wcj_checkout_core_field.required
                    var field_label = wcj_checkout_core_field.label
                    if (is_required == 1 || is_required) {
                        field_label = field_label + " " + required_star
                    }
                    jQuery(this).html(field_label);
                });

                jQuery("p#" + field + '_field').each(function () {
                    jQuery(this).removeClass("form-row-wide");
                    jQuery(this).addClass(wcj_checkout_core_field.class[0]);
                });
            });
        });
    });
});
