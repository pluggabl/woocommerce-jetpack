/**
 * Booster for WooCommerce - Quick Start Presets JavaScript
 *
 * This file handles the client-side logic for applying Quick Start presets.
 * When a user clicks a preset button, this script reads the preset's settings
 * and applies them to the corresponding form fields on the page.
 *
 * @version 7.8.0
 * @since   7.6.0
 * @package Booster_For_WooCommerce
 */

(function($) {
	'use strict';

	/**
	 * Apply a preset's settings to the form fields.
	 *
	 * @param {Object} settings - Map of setting key => value
	 * @param {string} moduleId - The module ID (for debugging)
	 * @param {string} presetId - The preset ID (for debugging)
	 */
	function applyPresetSettings(settings, moduleId, presetId) {
		var appliedCount = 0;
		var skippedCount = 0;

		// Iterate through each setting in the preset
		$.each(settings, function(settingKey, settingValue) {
			// Try to find the field by name attribute first
			var $field = $('[name="' + settingKey + '"]');

			// If not found by name, try by id
			if ($field.length === 0) {
				$field = $('#' + settingKey);
			}

			// If still not found, skip this setting
			if ($field.length === 0) {
				skippedCount++;
				return; // continue to next iteration
			}

			// Apply the value based on field type
			var fieldType = $field.attr('type');
			var tagName = $field.prop('tagName').toLowerCase();

			if (tagName === 'select') {
				// Handle select dropdowns
				$field.val(settingValue).trigger('change');
				appliedCount++;
			} else if (fieldType === 'hidden') {
				// Handle checkboxes stored as hidden fields.
				$field = jQuery(".wcj_setting_checkbox_key[data-rel_id='" + settingKey + "']");
				fieldType = $field.attr('type');
				if (fieldType === 'checkbox') {
					// Handle checkboxes
					var shouldCheck = (
					settingValue === true ||
					settingValue === 'yes' ||
					settingValue === '1' ||
					settingValue === 1
					);
					$field.prop('checked', shouldCheck).trigger('change');
					appliedCount++;
				}
			}else if (fieldType === 'radio') {
				// Handle radio buttons - find the specific radio with this value
				$field.filter('[value="' + settingValue + '"]').prop('checked', true).trigger('change');
				appliedCount++;
			} else if (tagName === 'textarea') {
				// Handle textareas
				$field.val(settingValue).trigger('change');
				appliedCount++;
			} else {
				// Handle text, number, and other input types
				$field.val(settingValue).trigger('change');
				appliedCount++;
			}
		});

		// Log results for debugging (only in console, not visible to user)
		if (window.console && console.log) {
			console.log('Quick Start: Applied ' + appliedCount + ' settings, skipped ' + skippedCount + ' (not found on page)');
		}

		return appliedCount > 0;
	}

	/**
	 * Show confirmation message to user.
	 *
	 * @param {jQuery} $box - The Quick Start box element
	 * @param {string} message - The message to display
	 */
function showConfirmationMessage($box, message) {
    var $messageDiv = $box.find('.wcj-quick-start-message');
    
    // Allow HTML (bold)
    $messageDiv.html(message).fadeIn(300);

    // Optionally scroll to the message
    $('html, body').animate({
        scrollTop: $messageDiv.offset().top - 100
    }, 300);
}

	/**
	 * Smooth scroll to advanced options.
	 *
	 * @param {Event} e - The click event
	 */
	function scrollToAdvancedOptions(e) {
		e.preventDefault();
		$('html, body').animate({
			scrollTop: $(this).offset().top - 100
		}, 300);
	}

	// Initialize when document is ready
	$(document).ready(function() {
		// Handle preset button clicks (using event delegation)
		$(document).on('click', '.wcj-quick-start-apply', function(e) {
			e.preventDefault();

			var $button = $(this);
			var $box = $button.closest('.wcj-quick-start-box');
			var moduleId = $button.data('module-id');
			var presetId = $button.data('preset-id');
			var settingsJson = $button.data('settings');

			// Parse the settings JSON
			var settings;
			try {
				if (typeof settingsJson === 'string') {
					settings = JSON.parse(settingsJson);
				} else {
					settings = settingsJson;
				}
			} catch (err) {
				if (window.console && console.error) {
					console.error('Quick Start: Failed to parse settings JSON', err);
				}
				return;
			}

			// Disable the button temporarily to prevent double-clicks
			$button.prop('disabled', true);

			// Apply the settings
			var success = applyPresetSettings(settings, moduleId, presetId);

			// Re-enable the button
			$button.prop('disabled', false);

			// Show confirmation message
			if (success) {
				var message = wcjQuickStart.confirmMessage || 'Preset applied!. Review the settings below and click "Save changes".';
				// Bold only "Preset applied!"
				message = message.replace(
					"Preset applied!",
					"<strong>Preset applied!</strong>"
				);
				 // Apply color to the entire message
                 message = '<span style="color:#3c434a;">' + message + '</span>';
				showConfirmationMessage($box, message);
			}
		});

		// Handle "See advanced options" link clicks
		$(document).on('click', '.wcj-quick-start-advanced', scrollToAdvancedOptions);
	});

})(jQuery);
