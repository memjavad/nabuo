/**
 * Bulk Import Tool Admin JavaScript
 */

(function ($) {
    'use strict';

    const BulkImport = {
        file: null,
        apiUrl: apaBulkImport.apiUrl,
        nonce: apaBulkImport.nonce,

        init() {
            this.cacheDOM();
            this.bindEvents();
        },

        cacheDOM() {
            this.$form = $('#naboo-bulk-import-form');
            this.$fileInput = $('#naboo-import-file');
            this.$dropzone = $('#naboo-dropzone');
            this.$validateBtn = $('#naboo-validate-btn');
            this.$importBtn = $('#naboo-import-btn');
            this.$progress = $('#naboo-import-progress');
            this.$progressBar = this.$progress.find('.naboo-progress-bar');
            this.$statusText = this.$progress.find('.naboo-import-status-text');
            this.$results = $('#naboo-import-results');
            this.$selectedFile = $('.naboo-selected-file');
        },

        bindEvents() {
            this.$dropzone.on('click', () => this.$fileInput.trigger('click'));

            this.$fileInput.on('change', (e) => {
                this.handleFileSelect(e.target.files[0]);
            });

            this.$dropzone.on('dragover', (e) => {
                e.preventDefault();
                this.$dropzone.addClass('dragover');
            });

            this.$dropzone.on('dragleave', () => {
                this.$dropzone.removeClass('dragover');
            });

            this.$dropzone.on('drop', (e) => {
                e.preventDefault();
                this.$dropzone.removeClass('dragover');
                this.handleFileSelect(e.originalEvent.dataTransfer.files[0]);
            });

            this.$validateBtn.on('click', () => this.validateFile());
            this.$form.on('submit', (e) => {
                e.preventDefault();
                this.processImport();
            });
        },

        handleFileSelect(file) {
            if (!file) return;

            this.file = file;
            this.$selectedFile.text(file.name);
            this.$validateBtn.prop('disabled', false);
            this.$importBtn.prop('disabled', true);
            this.$results.hide().empty();
        },

        validateFile() {
            if (!this.file) return;

            const formData = new FormData();
            formData.append('file', this.file);

            this.$validateBtn.prop('disabled', true).text('Validating...');

            $.ajax({
                url: `${this.apiUrl}/validate`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.$validateBtn.prop('disabled', false).text('Validate Content');
                    if (response.valid) {
                        alert(`File is valid! Found ${response.row_count} rows.`);
                        this.$importBtn.prop('disabled', false);
                    }
                },
                error: (xhr) => {
                    this.$validateBtn.prop('disabled', false).text('Validate Content');
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Validation failed';
                    alert(`Error: ${error}`);
                }
            });
        },

        processImport() {
            if (!this.file) return;

            const formData = new FormData();
            formData.append('file', this.file);

            this.$importBtn.prop('disabled', true).text('Processing...');
            this.$progress.show();
            this.$results.show().empty();

            this.updateProgress(0, 'Starting import...');

            $.ajax({
                url: `${this.apiUrl}/process`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.$importBtn.prop('disabled', false).text('Start Import Process');
                    this.updateProgress(100, `Import complete: ${response.successful} successful, ${response.failed} failed.`);
                    this.displayResults(response.results);
                },
                error: (xhr) => {
                    this.$importBtn.prop('disabled', false).text('Start Import Process');
                    this.$progress.hide();
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Import failed';
                    alert(`Error: ${error}`);
                }
            });
        },

        updateProgress(percent, text) {
            this.$progressBar.css('width', `${percent}%`);
            this.$statusText.text(text);
        },

        displayResults(results) {
            this.$results.empty();
            results.forEach((res) => {
                const statusClass = res.success ? 'naboo-result-success' : 'naboo-result-error';
                const statusText = res.success ? 'Success' : `Error: ${res.error}`;

                this.$results.append(`
                    <div class="naboo-result-item">
                        <strong>${res.row.title}</strong>: 
                        <span class="${statusClass}">${statusText}</span>
                    </div>
                `);
            });
        }
    };

    $(document).ready(() => BulkImport.init());

})(jQuery);
