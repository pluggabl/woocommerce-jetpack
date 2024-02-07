/**
 * Eu-vat-number.
 *
 * @version 7.1.6
 * @package Booster_For_WooCommerce/includes
 */

var _ajax_object = ajax_object;

jQuery(
	function ($) {

		// Setup before functions.
		var inputTimer;               // Timer identifier.
		var doneInputInterval    = 1000; // Time in ms.
		var $vatInput            = $( 'input[name="billing_eu_vat_number"]' );
		var $countryInput        = $( 'select[name="billing_country"]' );
		var $shipingcountryInput = $( 'select[name="shipping_country"]' );
		var $vatParagraph        = $( 'p[id="billing_eu_vat_number_field"]' );

		// Add progress text.
		if ('yes' == _ajax_object.add_progress_text) {
			$vatParagraph.append( '<div id="wcj_eu_vat_number_progress"></div>' );
			var $progressText = $( 'div[id="wcj_eu_vat_number_progress"]' );
		}

		// Initial validate.
		validateVat();
		jQuery( '#billing_country' ).on( 'change', validateVat );
		jQuery( '#shipping_country' ).on( 'change', validateVat );

		// On input, start the countdown.
		$vatInput.on(
			'input',
			function () {
				clearTimeout( inputTimer );
				inputTimer = setTimeout( validateVat, doneInputInterval );
			}
		);

		// On input, start the countdown.
		$countryInput.on(
			'input',
			function () {
				clearTimeout( inputTimer );
				inputTimer = setTimeout( validateVat, doneInputInterval );
			}
		);

		// On input, start the countdown.
		$shipingcountryInput.on(
			'input',
			function () {
				clearTimeout( inputTimer );
				inputTimer = setTimeout( validateVat, doneInputInterval );
			}
		);

		// Validate VAT.
		function validateVat() {
			$vatParagraph.removeClass( 'woocommerce-invalid' );
			$vatParagraph.removeClass( 'woocommerce-validated' );
			if ('yes' == _ajax_object.is_vat_field_required_for_eu_only) {
				jQuery( '#billing_eu_vat_number_field label span.optional' ).replaceWith( '<abbr class="required" title="required">*</abbr>' );
			}
			var vatNumberToCheck       = $vatInput.val();
			var countryToCheck         = $countryInput.val();
			var shippingcountryToCheck = $shipingcountryInput.val();
			if ('' != vatNumberToCheck) {
				// Validating EU VAT Number through AJAX call.
				if ('yes' == _ajax_object.add_progress_text) {
					$progressText.text( _ajax_object.progress_text_validating );
				}
				var data = {
					'action': 'wcj_validate_eu_vat_number',
					'wcj_eu_vat_number_to_check': vatNumberToCheck,
					'wcj_eu_country_to_check': countryToCheck,
					'wcj_eu_shipping_country_to_check': shippingcountryToCheck,
					'_wpnonce': _ajax_object._wpnonce,
				};
				$.ajax(
					{
						type: "POST",
						url: _ajax_object.ajax_url,
						data: data,
						success: function (response) {
							if ('1' == response.result) {
								$vatParagraph.addClass( 'woocommerce-validated' );
								if ('yes' == _ajax_object.add_progress_text) {
									$progressText.text( _ajax_object.progress_text_valid );
								}
							} else if ('0' == response.result) {
								$vatParagraph.addClass( 'woocommerce-invalid' );
								if ('yes' == _ajax_object.add_progress_text) {
									$progressText.text( _ajax_object.progress_text_not_valid );
								}
							} else {
								$vatParagraph.addClass( 'woocommerce-invalid' );
								if ('yes' == _ajax_object.add_progress_text) {
									$progressText.text( _ajax_object.progress_text_validation_failed );
								}
							}
							$( 'body' ).trigger( 'update_checkout' );
						},
					}
				);
			} else {
				// VAT input is empty.
				if ('yes' == _ajax_object.add_progress_text) {
					$progressText.text( '' );
				}
				if ($vatParagraph.hasClass( 'validate-required' )) {
					// Required.
					$vatParagraph.addClass( 'woocommerce-invalid' );
				} else {
					// Not required.
					$vatParagraph.addClass( 'woocommerce-validated' );
				}
				$( 'body' ).trigger( 'update_checkout' );
			}
		};

		// Show VAT Field for EU countries only.
		var vatFieldContainer = jQuery( '#billing_eu_vat_number_field' );
		var vatFieldWrapper   = $vatInput.parent();
		var vatField          = null;

		function showVATFieldForEUOnly(e) {
			var targetField     = jQuery( e.target );
			var selectedCountry = targetField.val();
			if (_ajax_object.eu_countries.indexOf( selectedCountry ) != -1) {
				if (vatField) {
					if ('yes' == _ajax_object.is_vat_field_required_for_eu_only) {
						vatFieldContainer.addClass( "validate-required" );
						jQuery( '#billing_eu_vat_number_field label span.optional' ).replaceWith( '<abbr class="required" title="required">*</abbr>' );
					}
					vatFieldWrapper.append( vatField );
					vatFieldContainer.slideDown( 400 );
				}
			} else {
				vatFieldContainer.slideUp(
					500,
					function () {
						vatField = $vatInput.detach();
					}
				);
			}
		}
		if ('yes' == _ajax_object.show_vat_field_for_eu_only) {
			jQuery( '#billing_country' ).on( 'change', showVATFieldForEUOnly );
			jQuery( '#billing_country' ).change();
		}
	}
);
