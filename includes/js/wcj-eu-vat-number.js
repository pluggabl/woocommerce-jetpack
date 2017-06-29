/**
 * eu-vat-number.
 *
 * @version 2.7.0
 */

var _ajax_object = ajax_object;

jQuery( function( $ ) {

	// Setup before functions
	var inputTimer;               //timer identifier
	var doneInputInterval = 1000; //time in ms
	var $vatInput = $('input[name="billing_eu_vat_number"]');
	var $vatParagraph = $('p[id="billing_eu_vat_number_field"]');

	// Add progress text
	if ('yes'==_ajax_object.add_progress_text) {
		$vatParagraph.append('<div id="wcj_eu_vat_number_progress"></div>');
		var $progressText = $('div[id="wcj_eu_vat_number_progress"]');
	}

	// Initial validate
	validateVat();

	// On input, start the countdown
	$vatInput.on('input', function() {
		clearTimeout(inputTimer);
		inputTimer = setTimeout(validateVat, doneInputInterval);
	});

	// Validate VAT
	function validateVat() {
		$vatParagraph.removeClass('woocommerce-invalid');
		$vatParagraph.removeClass('woocommerce-validated');
		var vatNumberToCheck = $vatInput.val();
		if (''!=vatNumberToCheck) {
			// Validating EU VAT Number through AJAX call
			if ('yes'==_ajax_object.add_progress_text) {
				$progressText.text(_ajax_object.progress_text_validating);
			}
			var data = {
				'action': 'wcj_validate_eu_vat_number',
				'wcj_eu_vat_number_to_check': vatNumberToCheck,
			};
			$.ajax({
				type: "POST",
				url: _ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if ('1'==response) {
						$vatParagraph.addClass('woocommerce-validated');
						if ('yes'==_ajax_object.add_progress_text) {
							$progressText.text(_ajax_object.progress_text_valid);
						}
					} else if ('0'==response) {
						$vatParagraph.addClass('woocommerce-invalid');
						if ('yes'==_ajax_object.add_progress_text) {
							$progressText.text(_ajax_object.progress_text_not_valid);
						}
					} else {
						$vatParagraph.addClass('woocommerce-invalid');
						if ('yes'==_ajax_object.add_progress_text) {
							$progressText.text(_ajax_object.progress_text_validation_failed);
						}
					}
					$('body').trigger('update_checkout');
				},
			});
		} else {
			// VAT input is empty
			if ('yes'==_ajax_object.add_progress_text) {
				$progressText.text('');
			}
			if ($vatParagraph.hasClass('validate-required')) {
				// Required
				$vatParagraph.addClass('woocommerce-invalid');
			} else {
				// Not required
				$vatParagraph.addClass('woocommerce-validated');
			}
			$('body').trigger('update_checkout');
		}
	};
});