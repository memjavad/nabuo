/**
 * Scale Validator Admin JavaScript
 */

(function ($) {
    'use strict';

    const ScaleValidator = {
        apiUrl: apaValidator.apiUrl,
        nonce: apaValidator.nonce,

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadInitialReport();
        },

        cacheDOM() {
            this.$runBtn = $('#naboo-run-validation');
            this.$status = $('#naboo-validator-status');
            this.$results = $('#naboo-validation-results');
            this.$progress = $('#naboo-v-progress');
            this.$progressBar = $('.naboo-v-progress-bar-fill');
            this.$progressText = $('#naboo-v-progress-text');

            this.$statPercent = $('#naboo-v-stat-percent');
            this.$statIssues = $('#naboo-v-stat-issues');
            this.$issuesList = $('#naboo-v-issues-list');
            this.$summaryBox = $('#naboo-v-summary');
        },

        bindEvents() {
            this.$runBtn.on('click', () => this.runFullScan());
        },

        loadInitialReport() {
            $.ajax({
                url: `${this.apiUrl}/report`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderReport(response);
                    this.$results.show();
                }
            });
        },

        runFullScan() {
            this.$progress.show();
            this.$progressBar.css('width', '10%');
            this.$progressText.text('Analyzing published scales...');

            $.ajax({
                url: `${this.apiUrl}/validate-all`,
                method: 'POST',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderFromBulkScan(response);
                    this.$progress.hide();
                    this.$results.show();
                    alert('Database scan complete.');
                    this.loadInitialReport(); // Reload to get the fresh summary report
                },
                error: () => {
                    this.$progress.hide();
                    alert('Error running scan.');
                }
            });
        },

        renderReport(data) {
            this.$statPercent.text(`${data.compliance_percentage}%`);
            this.$statIssues.text(data.invalid_scales);

            let summaryHtml = '';
            for (const [issue, count] of Object.entries(data.common_issues)) {
                summaryHtml += `
                    <div class="naboo-issue-count-item">
                        <span class="naboo-issue-text">${issue}</span>
                        <span class="naboo-issue-count">${count}</span>
                    </div>
                `;
            }
            this.$summaryBox.html(summaryHtml || '<p>No issues found.</p>');
        },

        renderFromBulkScan(data) {
            let listHtml = '';
            for (const [scaleId, issues] of Object.entries(data.issues)) {
                listHtml += `
                    <div class="naboo-v-item">
                        <h4 class="naboo-v-item-title">Scale #${scaleId}</h4>
                        <ul class="naboo-v-item-issues">
                            ${issues.map(issue => `<li>${issue}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            this.$issuesList.html(listHtml || '<p>All scales meet quality standards.</p>');
        }
    };

    $(document).ready(() => ScaleValidator.init());

})(jQuery);
