jQuery(document).ready(function() {
	if(jQuery("input:radio[name='wcj_variations']").is(':checked')){
		var checked_radio = jQuery("input:radio[name='wcj_variations']:checked");
		var variation_id = checked_radio.attr("variation_id");
		jQuery("input:hidden[name='variation_id']").val(variation_id);
		jQuery(checked_radio[0].attributes).each(
			function(i, attribute){
				if(attribute.name.match("^attribute_")){
					jQuery("input:hidden[name='" + attribute.name + "']").val(attribute.value);
				}
			}
		);
	}
	jQuery("input:radio[name='wcj_variations']").change(
		function(){
			jQuery("input:hidden[name='variation_id']").val(jQuery(this).attr("variation_id"));
			jQuery(this.attributes).each(
				function(i, attribute){
					 if(attribute.name.match("^attribute_")){
						jQuery("input:hidden[name='" + attribute.name + "']").val(attribute.value);
					 }
				}
			);
		}
	);
});