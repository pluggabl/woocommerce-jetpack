/**
 * Booster for WooCommerce - Presets JavaScript
 *
 * Handles preset card interactions and AJAX application.
 *
 * @version 7.5.1
 * @since   7.5.1
 * @package Booster_For_WooCommerce/assets/js/admin
 */

/* global jQuery, wcj_preset_params */

(function($) {
	'use strict';

	/**
	 * Initialize preset card functionality.
	 */
	function initPresetCards() {
		// Handle preset apply button click.
		$(document).on('click', '.wcj-apply-preset', function(e) {
			e.preventDefault();

			var $card = $(this).closest('.wcj-preset-card');
			var presetId = $card.data('preset-id');
			var $button = $(this);

			// Disable button and show loading state.
			$button.prop('disabled', true).text(wcj_preset_params.strings.applying);

			// Make AJAX request.
			$.ajax({
				url: wcj_preset_params.ajax_url,
				type: 'POST',
				data: {
					action: 'wcj_apply_preset',
					preset_id: presetId,
					nonce: wcj_preset_params.nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message with first win and next step.
						var html = '<div class="wcj-preset-success">';
						html += '<span class="dashicons dashicons-yes-alt"></span>';
						html += '<p class="wcj-preset-message">' + response.data.message + '</p>';
						html += '<p class="wcj-preset-first-win"><strong>' + wcj_preset_params.strings.first_win + '</strong> ' + response.data.first_win.action + '</p>';
						html += '<a href="' + response.data.first_win.link + '" class="button button-primary">' + wcj_preset_params.strings.go_there_now + '</a>';
						html += '</div>';

						$card.html(html);
					} else {
						alert(wcj_preset_params.strings.error + ' ' + response.data);
						$button.prop('disabled', false).text(wcj_preset_params.strings.apply_preset);
					}
				},
				error: function() {
					alert(wcj_preset_params.strings.error);
					$button.prop('disabled', false).text(wcj_preset_params.strings.apply_preset);
				}
			});
		});
	}

	// Initialize on document ready.
	$(document).ready(function() {
		initPresetCards();
	});

})(jQuery);
