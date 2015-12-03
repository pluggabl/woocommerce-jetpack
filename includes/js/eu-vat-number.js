jQuery( function( $ ) {
	$( 'form.checkout' ).on( 'blur change', 'input[name="billing_eu_vat_number"]', function(event) {
//	$( 'form.checkout' ).on( 'click', 'a[name="billing_eu_vat_number_verify"]', function(event) {
		event.stopImmediatePropagation();
//		event.preventDefault();
		$('p[id="billing_eu_vat_number_field"]').removeClass('woocommerce-invalid');
		$('p[id="billing_eu_vat_number_field"]').removeClass('woocommerce-validated');
		var wcj_eu_vat_number_to_check = $('input[name="billing_eu_vat_number"]').val();
		if (''!=wcj_eu_vat_number_to_check) {
			//Validating EU VAT Number through AJAX call
			$.ajax({
				url: '/?wcj_validate_eu_vat_number',
				data: 'wcj_eu_vat_number_to_check='+wcj_eu_vat_number_to_check,
				type: 'GET',
				success: function (response) {
					if ('1'==response) {
						$('p[id="billing_eu_vat_number_field"]').addClass('woocommerce-validated');
					} else {
						$('p[id="billing_eu_vat_number_field"]').addClass('woocommerce-invalid');
					}
					console.log('triggering update_checkout');//TODO: double update_checkout issue.
					$('body').trigger('update_checkout');
				},
				error: function (e) {
					console.log(e.message);
				}
			});
		} else {
			//Empty
			if ($('p[id="billing_eu_vat_number_field"]').hasClass('validate-required')) {
				//Required
				$('p[id="billing_eu_vat_number_field"]').addClass('woocommerce-invalid');
			} else {
				//Not required
				$('p[id="billing_eu_vat_number_field"]').addClass('woocommerce-validated');
			}
		}
	});
});