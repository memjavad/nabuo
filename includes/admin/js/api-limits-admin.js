/**
 * API Rate Limiting Admin JavaScript
 */

(function ($) {
    'use strict';

    const ApiLimits = {
        apiUrl: apaApiLimits.apiUrl,
        nonce: apaApiLimits.nonce,

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.loadStats();
            this.loadConfig();
            this.loadBlocked();
        },

        cacheDOM() {
            this.$blockedCount = $('#naboo-blocked-count');
            this.$totalRequests = $('#naboo-total-requests');
            this.$blockedList = $('#naboo-blocked-list');
            this.$endpointsList = $('#naboo-top-endpoints-list');
            this.$configForm = $('#naboo-api-config-form');
        },

        bindEvents() {
            this.$configForm.on('submit', (e) => {
                e.preventDefault();
                this.saveConfig();
            });

            this.$blockedList.on('click', '.naboo-unblock-btn', (e) => {
                const identifier = $(e.currentTarget).data('identifier');
                this.unblock(identifier);
            });
        },

        loadStats() {
            $.ajax({
                url: `${this.apiUrl}/stats`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.$blockedCount.text(response.currently_blocked);
                    this.$totalRequests.text(response.total_requests.toLocaleString());
                    this.renderEndpoints(response.top_endpoints);
                }
            });
        },

        loadConfig() {
            $.ajax({
                url: `${this.apiUrl}/config`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    $('#naboo-api-enabled').prop('checked', response.enabled);
                    $('#naboo-api-auth-limit').val(response.authenticated_limit);
                    $('#naboo-api-auth-window').val(response.authenticated_window);
                    $('#naboo-api-anon-limit').val(response.anonymous_limit);
                    $('#naboo-api-anon-window').val(response.anonymous_window);
                    $('#naboo-api-block-duration').val(response.block_duration);
                }
            });
        },

        loadBlocked() {
            $.ajax({
                url: `${this.apiUrl}/blocked`,
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: (response) => {
                    this.renderBlocked(response.blocked);
                }
            });
        },

        renderBlocked(blocked) {
            if (!blocked || blocked.length === 0) {
                this.$blockedList.html('<tr><td colspan="5">No active blocks.</td></tr>');
                return;
            }

            let html = '';
            blocked.forEach((item) => {
                html += `
                    <tr>
                        <td><code>${item.identifier}</code></td>
                        <td>${item.endpoint}</td>
                        <td>${item.request_count}</td>
                        <td>${item.reset_at}</td>
                        <td><button class="button button-small naboo-unblock-btn" data-identifier="${item.identifier}">Unblock</button></td>
                    </tr>
                `;
            });
            this.$blockedList.html(html);
        },

        renderEndpoints(endpoints) {
            let html = '';
            endpoints.forEach((ep) => {
                html += `
                    <div>
                        <span><code>${ep.endpoint}</code></span>
                        <span><strong>${ep.requests}</strong> requests by <strong>${ep.unique_users}</strong> users</span>
                    </div>
                `;
            });
            this.$endpointsList.html(html || '<p>No data available.</p>');
        },

        saveConfig() {
            const data = {
                enabled: $('#naboo-api-enabled').is(':checked'),
                authenticated_limit: $('#naboo-api-auth-limit').val(),
                authenticated_window: $('#naboo-api-auth-window').val(),
                anonymous_limit: $('#naboo-api-anon-limit').val(),
                anonymous_window: $('#naboo-api-anon-window').val(),
                block_duration: $('#naboo-api-block-duration').val()
            };

            $.ajax({
                url: `${this.apiUrl}/config`,
                method: 'POST',
                data: data,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: () => {
                    alert('Configuration saved.');
                }
            });
        },

        unblock(identifier) {
            $.ajax({
                url: `${this.apiUrl}/unblock`,
                method: 'POST',
                data: { identifier: identifier },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                },
                success: () => {
                    this.loadBlocked();
                    this.loadStats();
                }
            });
        }
    };

    $(document).ready(() => ApiLimits.init());

})(jQuery);
