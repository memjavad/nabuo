/**
 * Bulk Operations Admin JavaScript
 */

(function ($) {
    'use strict';

    const BulkOps = {
        selectedIds: [],
        apiUrl: apaBulkOps.apiUrl,
        nonce: apaBulkOps.nonce,

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadScales();
        },

        cacheDOM() {
            this.$list = $('#naboo-bulk-items-list');
            this.$search = $('#naboo-bulk-search');
            this.$catFilter = $('#naboo-bulk-cat-filter');
            this.$selectAll = $('#naboo-bulk-select-all');
            this.$execBtns = $('.naboo-bulk-exec');
            this.$progress = $('#naboo-bulk-progress');
            this.$progressBar = $('.naboo-progress-bar');
            this.$statusText = $('#naboo-bulk-status-text');
        },

        bindEvents() {
            let timeout = null;
            this.$search.on('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.loadScales(), 500);
            });

            this.$catFilter.on('change', () => this.loadScales());

            this.$selectAll.on('change', (e) => {
                const checked = $(e.currentTarget).prop('checked');
                $('.naboo-bulk-item-check').prop('checked', checked).trigger('change');
            });

            this.$list.on('change', '.naboo-bulk-item-check', () => {
                this.updateSelected();
            });

            this.$execBtns.on('click', (e) => {
                const action = $(e.currentTarget).data('action');
                this.executeAction(action);
            });
        },

        loadScales() {
            const search = this.$search.val();
            const category = this.$catFilter.val();

            this.$list.html('<tr><td colspan="3">Searching...</td></tr>');

            $.ajax({
                url: wpApiSettings.root + 'wp/v2/psych_scale',
                method: 'GET',
                data: {
                    search: search,
                    scale_category: category,
                    per_page: 50,
                    _fields: 'id,title,status'
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: (response) => {
                    this.renderScales(response);
                }
            });
        },

        renderScales(scales) {
            if (!scales || scales.length === 0) {
                this.$list.html('<tr><td colspan="3">No scales found.</td></tr>');
                return;
            }

            let html = '';
            scales.forEach((scale) => {
                const isChecked = this.selectedIds.includes(scale.id) ? 'checked' : '';
                html += `
                    <tr>
                        <td class="check-column"><input type="checkbox" class="naboo-bulk-item-check" value="${scale.id}" ${isChecked}></td>
                        <td>${scale.title.rendered}</td>
                        <td><span class="status-${scale.status}">${scale.status}</span></td>
                    </tr>
                `;
            });
            this.$list.html(html);
        },

        updateSelected() {
            this.selectedIds = [];
            $('.naboo-bulk-item-check:checked').each((i, el) => {
                this.selectedIds.push(parseInt($(el).val()));
            });
        },

        executeAction(action) {
            if (this.selectedIds.length === 0) {
                alert('Please select at least one scale.');
                return;
            }

            if (!confirm(`Are you sure you want to perform this action on ${this.selectedIds.length} scales?`)) {
                return;
            }

            let data = { scale_ids: this.selectedIds };

            if (action === 'change-status') {
                data.status = $('#naboo-bulk-status-val').val();
            } else if (action === 'add-taxonomy') {
                data.term_ids = [$('#naboo-bulk-tax-val').val()];
                data.taxonomy = 'scale_category';
            } else if (action === 'export') {
                data.format = $('#naboo-bulk-export-format').val();
            }

            this.$progress.show();
            this.$progressBar.css('width', '50%');
            this.$statusText.text('Processing request...');

            $.ajax({
                url: `${this.apiUrl}/${action}`,
                method: 'POST',
                data: data,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.$progress.hide();
                    if (action === 'export') {
                        this.handleExport(response.data, data.format);
                    } else {
                        alert(response.message);
                        this.loadScales();
                    }
                },
                error: (xhr) => {
                    this.$progress.hide();
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Action failed';
                    alert(`Error: ${error}`);
                }
            });
        },

        handleExport(data, format) {
            const blob = new Blob([format === 'json' ? JSON.stringify(data, null, 2) : data], {
                type: format === 'json' ? 'application/json' : 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `scales_export_${new Date().getTime()}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        }
    };

    $(document).ready(() => BulkOps.init());

})(jQuery);
