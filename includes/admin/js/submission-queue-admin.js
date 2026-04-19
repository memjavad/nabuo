/**
 * Submission Queue Admin JavaScript
 */

(function ($) {
    'use strict';

    const SubmissionQueue = {
        status: 'pending',
        page: 1,
        apiUrl: apaSubmissions.apiUrl,
        nonce: apaSubmissions.nonce,
        currentSubmissionId: null,

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadQueue();
        },

        cacheDOM() {
            this.$list = $('#naboo-queue-list');
            this.$panel = $('#naboo-submission-details-panel');
            this.$panelContent = this.$panel.find('.naboo-panel-content');
            this.$tabs = $('.naboo-tab-btn');
            this.$modal = $('#naboo-action-modal');
            this.$modalTitle = $('#naboo-modal-title');
            this.$modalText = $('#naboo-modal-textarea');
            this.$modalSubmit = $('#naboo-modal-submit');

            this.$approveBtn = $('.naboo-approve-btn');
            this.$rejectBtn = $('.naboo-reject-btn');
            this.$requestChangesBtn = $('.naboo-request-changes-btn');
        },

        bindEvents() {
            this.$tabs.on('click', (e) => {
                const $btn = $(e.currentTarget);
                this.$tabs.removeClass('active');
                $btn.addClass('active');
                this.status = $btn.data('status');
                this.page = 1;
                this.loadQueue();
                this.$panel.hide();
            });

            this.$list.on('click', '.naboo-queue-item-row', (e) => {
                const id = $(e.currentTarget).data('id');
                $('.naboo-queue-item-row').removeClass('active');
                $(e.currentTarget).addClass('active');
                this.loadDetails(id);
            });

            $('.naboo-close-panel').on('click', () => this.$panel.hide());

            this.$approveBtn.on('click', () => this.approve());
            this.$rejectBtn.on('click', () => this.openModal('reject'));
            this.$requestChangesBtn.on('click', () => this.openModal('request-changes'));

            $('#naboo-modal-cancel').on('click', () => this.$modal.hide());
            this.$modalSubmit.on('click', () => this.handleModalSubmit());
        },

        loadQueue() {
            this.$list.html('<tr><td colspan="6">Loading submissions...</td></tr>');

            $.ajax({
                url: `${this.apiUrl}/queue`,
                method: 'GET',
                data: { status: this.status, page: this.page },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderQueue(response.submissions);
                },
                error: () => {
                    this.$list.html('<tr><td colspan="6">Error loading submissions.</td></tr>');
                }
            });
        },

        renderQueue(submissions) {
            if (!submissions || submissions.length === 0) {
                this.$list.html('<tr><td colspan="6">No submissions found.</td></tr>');
                return;
            }

            let html = '';
            submissions.forEach((item) => {
                html += `
                    <tr class="naboo-queue-item-row" data-id="${item.id}">
                        <th scope="row" class="check-column"><input type="checkbox" name="submission[]" value="${item.id}"></th>
                        <td class="title column-title has-row-actions column-primary"><strong>${item.title}</strong></td>
                        <td class="author column-author">${item.author}</td>
                        <td class="categories column-categories">${item.categories.join(', ')}</td>
                        <td class="date column-date">${item.submitted_date}</td>
                        <td class="actions column-actions">
                            <button class="button button-small view-details">View</button>
                        </td>
                    </tr>
                `;
            });
            this.$list.html(html);
        },

        loadDetails(id) {
            this.currentSubmissionId = id;
            this.$panelContent.html('Loading details...');
            this.$panel.show();

            $.ajax({
                url: `${this.apiUrl}/${id}/details`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderDetails(response);
                }
            });
        },

        renderDetails(data) {
            const html = `
                <div class="naboo-detail-group">
                    <span class="naboo-detail-label">Description</span>
                    <div class="naboo-detail-value">${data.content}</div>
                </div>
                <div class="naboo-detail-group">
                    <span class="naboo-detail-label">Metrics</span>
                    <div class="naboo-detail-value">
                        Items: ${data.items || 'N/A'}<br>
                        Reliability: ${data.reliability || 'N/A'}<br>
                        Validity: ${data.validity || 'N/A'}<br>
                        Year: ${data.year || 'N/A'}
                    </div>
                </div>
                <div class="naboo-detail-group">
                    <span class="naboo-detail-label">Metadata</span>
                    <div class="naboo-detail-value">
                        Language: ${data.language || 'N/A'}<br>
                        Population: ${data.population || 'N/A'}
                    </div>
                </div>
            `;
            this.$panelContent.html(html);
        },

        approve() {
            if (!confirm('Are you sure you want to approve this submission?')) return;

            $.ajax({
                url: `${this.apiUrl}/${this.currentSubmissionId}/approve`,
                method: 'POST',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: () => {
                    this.$panel.hide();
                    this.loadQueue();
                    alert('Submission approved and published.');
                }
            });
        },

        openModal(action) {
            this.currentAction = action;
            this.$modalTitle.text(action === 'reject' ? 'Reject Submission' : 'Request Changes');
            this.$modalText.val('');
            this.$modal.show();
        },

        handleModalSubmit() {
            const message = this.$modalText.val();
            if (!message && this.currentAction === 'request-changes') {
                alert('Please provide feedback for the changes requested.');
                return;
            }

            const endpoint = this.currentAction === 'reject' ? 'reject' : 'request-changes';
            const data = this.currentAction === 'reject' ? { reason: message } : { feedback: message };

            $.ajax({
                url: `${this.apiUrl}/${this.currentSubmissionId}/${endpoint}`,
                method: 'POST',
                data: data,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: () => {
                    this.$modal.hide();
                    this.$panel.hide();
                    this.loadQueue();
                    alert(this.currentAction === 'reject' ? 'Submission rejected.' : 'Feedback sent to author.');
                }
            });
        }
    };

    $(document).ready(() => SubmissionQueue.init());

})(jQuery);
