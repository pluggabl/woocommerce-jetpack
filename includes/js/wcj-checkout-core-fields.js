/**
* wcj-checkout-core-fields.
*
* @version 5.3.7
* @since   5.3.7
*/
 
jQuery(document).ready(function() {
   jQuery.each(wcj_checkout_core_fields, function(i, wcj_checkout_core_field_sections) {
       jQuery.each(wcj_checkout_core_field_sections, function(i, wcj_checkout_core_field_section) {
           jQuery.each(wcj_checkout_core_field_section, function(field, wcj_checkout_core_field) {
               jQuery("p#" + field + '_field' + " label").each(function() {
                   jQuery(this).text(wcj_checkout_core_field.label);
               });
               jQuery("p#" + field + '_field').each(function() {
                   jQuery(this).removeClass("form-row-wide");
                   jQuery(this).addClass(wcj_checkout_core_field.class[0]);
               });
           });
       });
   });
});
