/**
 * Naboo Database Theme Customizer JavaScript
 * 
 * Handles live preview updates, reset functionality, and color picker enhancements
 */

jQuery(document).ready(function ($) {
	'use strict';

	// Initialize color pickers
	if ($.isFunction($.fn.wpColorPicker)) {
		$('.naboo-color-field').wpColorPicker({
			change: function (event, ui) {
				// Small delay to let the input value update
				setTimeout(updateLivePreview, 10);
			}
		});
	}

	// Tab switching handled by PHP reloading
	// Keeping this file for color pickers and preview updates

	// Live preview updates
	$('input, select, textarea').on('change keyup', function () {
		updateLivePreview();
	});

	// Reset button with confirmation
	$('#naboodatabase-reset-button').on('click', function (e) {
		e.preventDefault();

		if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
			// Submit the form with reset action
			var form = $(this).closest('form');
			$('<input>').attr({
				type: 'hidden',
				name: 'naboodatabase_reset',
				value: '1'
			}).appendTo(form);

			form.submit();
		}
	});

	// Update live preview
	function updateLivePreview() {
		var previewPanel = $('#preview-panel');
		if (previewPanel.length === 0) {
			return;
		}

		// Get form values
		var primaryColor = $('#primary_color').val() || '#1a3a52';
		var accentColor = $('#accent_color').val() || '#00796b';
		var buttonColor = $('#button_primary_color').val() || accentColor;
		var buttonText = $('#button_text_color').val() || '#ffffff';
		var cardBg = $('#card_bg_color').val() || '#ffffff';
		var inputBg = $('#input_bg_color').val() || '#ffffff';
		var inputBorder = $('#input_border_color').val() || '#d9d9d9';

		// Update preview styles
		var previewStyles = `
			.preview-button {
				background-color: ${buttonColor} !important;
				color: ${buttonText} !important;
			}
			
			.preview-card {
				background-color: ${cardBg} !important;
				border-left-color: ${accentColor} !important;
			}
			
			.preview-input {
				background-color: ${inputBg} !important;
				border-color: ${inputBorder} !important;
			}
			
			.preview-header {
				border-bottom-color: ${accentColor} !important;
			}
		`;

		// Remove old styles
		$('#preview-styles').remove();

		// Add new styles
		$('head').append($('<style id="preview-styles">').text(previewStyles));

		// Update color swatches
		updateColorSwatches();
	}

	// Update color swatches display
	function updateColorSwatches() {
		$('.naboo-color-field').each(function () {
			var color = $(this).val();
			var preview = $(this).siblings('.color-preview');
			if (preview.length) {
				preview.css('background-color', color || '#ffffff');
			}
		});
	}

	// Image upload button handler
	$('.naboo-upload-btn').on('click', function (e) {
		e.preventDefault();

		var button = $(this);
		var inputField = button.siblings('input[type="text"]');
		var mediaUploader;

		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: 'Select or Upload Image',
			library: { type: 'image' },
			button: { text: 'Use this image' },
			multiple: false
		});

		mediaUploader.on('select', function () {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			inputField.val(attachment.url);
			updateLivePreview();
		});

		mediaUploader.open();
	});

	// Tab navigation with keyboard
	$(document).on('keydown', '.tab-link', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).click();
		}
	});

	// Initial preview setup
	updateLivePreview();

	// Show success message after save
	if (window.location.hash === '#settings-saved') {
		var notice = $('<div class="notice notice-success"><p>Theme settings saved successfully!</p></div>');
		$('.naboodatabase-customizer-wrap').prepend(notice);

		// Remove hash after 2 seconds
		setTimeout(function () {
			window.location.hash = '';
		}, 2000);
	}

	// Auto-save on blur (optional, can be disabled)
	var autoSaveEnabled = $('#naboodatabase-auto-save').val() === '1';

	if (autoSaveEnabled) {
		var saveTimeout;
		$('input, select, textarea').on('change', function () {
			clearTimeout(saveTimeout);

			saveTimeout = setTimeout(function () {
				var form = $('form#naboodatabase-customizer-form');
				if (form.length) {
					// You can auto-save here if desired
					// form.submit();
				}
			}, 1000);
		});
	}

	// Enhance accessibility
	$('.customize-control').attr('role', 'group');
	$('.customize-control label').attr('for', function (i, val) {
		var input = $(this).next('input, select, textarea').attr('id');
		return input;
	});

	// Focus management for better UX
	$('.customize-control input, .customize-control select, .customize-control textarea').on('focus', function () {
		$(this).closest('.customize-control').addClass('focused');
	}).on('blur', function () {
		$(this).closest('.customize-control').removeClass('focused');
	});
});
