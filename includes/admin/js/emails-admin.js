/**
 * Email Notifications Admin JavaScript
 */

(function ($) {
    'use strict';

    const EmailAdmin = {
        apiUrl: apaEmails.apiUrl,
        nonce: apaEmails.nonce,

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadSettings();
        },

        cacheDOM() {
            this.$tabs = $('.naboo-tab-btn');
            this.$tabContents = $('.naboo-tab-content');
            this.$configForm = $('#naboo-email-config-form');
            this.$logsList = $('#naboo-email-logs-list');
            this.$testBtn = $('#naboo-send-test-btn');
        },

        bindEvents() {
            this.$tabs.on('click', (e) => {
                const $btn = $(e.currentTarget);
                const tab = $btn.data('tab');

                this.$tabs.removeClass('active');
                $btn.addClass('active');

                this.$tabContents.hide();
                $(`#naboo-email-${tab}`).show();

                if (tab === 'logs') {
                    this.loadLogs();
                }
            });

            this.$configForm.on('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });

            this.$testBtn.on('click', () => this.sendTest());
        },

        loadSettings() {
            $.ajax({
                url: `${this.apiUrl}/settings`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    $('#naboo-email-from-name').val(response.notification_from_name);
                    $('#naboo-email-from-email').val(response.notification_from_email);

                    $('#naboo-email-notify-sub').prop('checked', response.submission_notification);
                    $('#naboo-email-notify-app').prop('checked', response.approval_notification);
                    $('#naboo-email-notify-rej').prop('checked', response.rejection_notification);
                    $('#naboo-email-notify-com').prop('checked', response.comment_notification);
                    $('#naboo-email-notify-rat').prop('checked', response.rating_notification);
                    $('#naboo-email-daily').prop('checked', response.daily_digest);
                }
            });
        },

        saveSettings() {
            const data = {
                notification_from_name: $('#naboo-email-from-name').val(),
                notification_from_email: $('#naboo-email-from-email').val(),
                submission_notification: $('#naboo-email-notify-sub').is(':checked'),
                approval_notification: $('#naboo-email-notify-app').is(':checked'),
                rejection_notification: $('#naboo-email-notify-rej').is(':checked'),
                comment_notification: $('#naboo-email-notify-com').is(':checked'),
                rating_notification: $('#naboo-email-notify-rat').is(':checked'),
                daily_digest: $('#naboo-email-daily').is(':checked')
            };

            $.ajax({
                url: `${this.apiUrl}/settings`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: () => {
                    const $btn = this.$configForm.find('button[type="submit"]');
                    const originalText = $btn.text();
                    $btn.text('✅ Settings Saved!').css('background', '#059669');
                    setTimeout(() => {
                        $btn.text(originalText).css('background', '#4f46e5');
                    }, 2000);
                }
            });
        },

        loadLogs() {
            this.$logsList.html('<tr><td colspan="5">Loading logs...</td></tr>');

            $.ajax({
                url: `${this.apiUrl}/logs`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderLogs(response.logs);
                }
            });
        },

        renderLogs(logs) {
            if (!logs || logs.length === 0) {
                this.$logsList.html('<tr><td colspan="5">No email logs found.</td></tr>');
                return;
            }

            let html = '';
            logs.forEach((log) => {
                html += `
                    <tr>
                        <td>${log.recipient}</td>
                        <td>${log.subject}</td>
                        <td><code>${log.event_type}</code></td>
                        <td><span class="status-${log.status}">${log.status}</span></td>
                        <td>${log.sent_at}</td>
                    </tr>
                `;
            });
            this.$logsList.html(html);
        },

        sendTest() {
            const email = $('#naboo-test-email-addr').val();
            if (!email) {
                alert('Please enter an email address.');
                return;
            }

            this.$testBtn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: `${this.apiUrl}/send-test`,
                method: 'POST',
                data: { email: email },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    $('#naboo-test-status').html(`<span style="color:#059669; font-weight:600;">✅ ${response.message}</span>`);
                    this.$testBtn.prop('disabled', false).text('Send Test Email');
                },
                error: (xhr) => {
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to send';
                    $('#naboo-test-status').html(`<span style="color:#dc2626; font-weight:600;">❌ ${error}</span>`);
                    this.$testBtn.prop('disabled', false).text('Send Test Email');
                }
            });
        }
    };

    $(document).ready(() => EmailAdmin.init());

})(jQuery);
