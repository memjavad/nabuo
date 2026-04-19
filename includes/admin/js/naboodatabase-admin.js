/**
 * Naboo Database - Admin Scripts
 * Handles metabox tabs and media uploader.
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ──────────────────────────────────────────────
        // Metabox Tab Navigation
        // ──────────────────────────────────────────────
        $(document).on('click', '.naboo-metabox-tab', function (e) {
            e.preventDefault();
            var tab = $(this).data('tab');

            // Switch active tab
            $('.naboo-metabox-tab').removeClass('active');
            $(this).addClass('active');

            // Switch active panel
            $('.naboo-metabox-panel').removeClass('active');
            $('.naboo-metabox-panel[data-panel="' + tab + '"]').addClass('active');
        });

        // ──────────────────────────────────────────────
        // Media Uploader for Scale File
        // ──────────────────────────────────────────────
        var fileFrame;

        $(document).on('click', '.naboo-upload-file-btn', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $input = $button.siblings('input[type="hidden"]');
            var $remove = $button.siblings('.naboo-remove-file-btn');
            var $name = $button.siblings('.naboo-file-name');

            // If the frame already exists, reopen it.
            if (fileFrame) {
                fileFrame.open();
                return;
            }

            // Create the media frame.
            fileFrame = wp.media({
                title: 'Select or Upload Scale Document',
                button: { text: 'Use This File' },
                multiple: false,
                library: {
                    type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
                }
            });

            // When a file is selected.
            fileFrame.on('select', function () {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                $name.html('<span class="dashicons dashicons-media-document"></span> ' + attachment.filename).show();
                $remove.show();
            });

            fileFrame.open();
        });

        // Remove file
        $(document).on('click', '.naboo-remove-file-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            $button.siblings('input[type="hidden"]').val('');
            $button.siblings('.naboo-file-name').hide();
            $button.hide();
        });

        // ──────────────────────────────────────────────
        // Linked Versions Repeater
        // ──────────────────────────────────────────────
        var versionIndex = $('.naboo-version-row').length;

        $('#naboo-add-version-btn').on('click', function (e) {
            e.preventDefault();
            var template = $('#naboo-version-row-template').html();
            // Replace all occurrences of {{INDEX}} with the current index
            template = template.replace(/{{INDEX}}/g, versionIndex);
            $('#naboo-versions-wrapper').append(template);
            versionIndex++;
        });

        $(document).on('click', '.naboo-remove-version-btn', function (e) {
            e.preventDefault();
            $(this).closest('.naboo-version-row').remove();
        });

    });
})(jQuery);
