/**
 * wcj-price-by-user-role-admin.js
 *
 * @version 3.6.0
 * @since   3.6.0
 */

jQuery(document).ready(function() {
	jQuery('.wcj-copy-price').click(function() {
		var wcj_copy_data   = jQuery.parseJSON(jQuery(this).attr('wcj-copy-data'));
		var source_input_id = '#wcj_price_by_user_role_'+wcj_copy_data.price+'_price_'+wcj_copy_data.source_role+'_'+wcj_copy_data.source_product;
		if ('copy_to_roles' == wcj_copy_data.action) {
			wcj_copy_data.dest_roles.forEach(function(element) {
				var dest_input_id = '#wcj_price_by_user_role_'+wcj_copy_data.price+'_price_'+element+'_'+wcj_copy_data.source_product;
				jQuery(dest_input_id).val(jQuery(source_input_id).val());
			});
		} else if ('copy_to_variations' == wcj_copy_data.action) {
			wcj_copy_data.dest_products.forEach(function(element) {
				var dest_input_id = '#wcj_price_by_user_role_'+wcj_copy_data.price+'_price_'+wcj_copy_data.source_role+'_'+element;
				jQuery(dest_input_id).val(jQuery(source_input_id).val());
			});
		} else if ('copy_to_roles_and_variations' == wcj_copy_data.action) {
			wcj_copy_data.dest_roles.concat(wcj_copy_data.source_role).forEach(function(element_role) {
				wcj_copy_data.dest_products.concat(wcj_copy_data.source_product).forEach(function(element_var) {
					var dest_input_id = '#wcj_price_by_user_role_'+wcj_copy_data.price+'_price_'+element_role+'_'+element_var;
					jQuery(dest_input_id).val(jQuery(source_input_id).val());
				});
			});
		}
		return false;
	});
});
