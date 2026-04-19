/**
 * Data Export Features
 * Export scales data in CSV and JSON formats
 */

(function ($) {
	'use strict';

	if (typeof apaDataExport === 'undefined') {
		return;
	}

	const DataExport = {
		apiUrl: apaDataExport.api_url,
		nonce: apaDataExport.nonce,
		isLoggedIn: apaDataExport.is_logged_in,

		init() {
			this.addExportButtons();
		},

		addExportButtons() {
			// On search/archive pages
			const $archiveContent = $('.archive-header, .page-header').first();
			if ($archiveContent.length) {
				// this.addArchiveExportBar();
			}

			// On user dashboard
			if (this.isLoggedIn) {
				const $dashboard = $('.naboo-user-dashboard, [data-dashboard="true"]');
				if ($dashboard.length) {
					this.addDashboardExportButtons();
				}
			}
		},

		addArchiveExportBar() {
			const html = `
				<div class="naboo-export-bar">
					<div class="naboo-export-bar-content">
						<span class="naboo-export-label">Export Results:</span>
						<button class="naboo-export-btn naboo-export-json-btn" data-format="json">
							<svg class="naboo-export-icon" viewBox="0 0 24 24" width="16" height="16">
								<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
							</svg>
							JSON
						</button>
						<button class="naboo-export-btn naboo-export-csv-btn" data-format="csv">
							<svg class="naboo-export-icon" viewBox="0 0 24 24" width="16" height="16">
								<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
							</svg>
							CSV
						</button>
					</div>
				</div>
			`;

			$('.archive-header, .page-header').first().after(html);
			this.setupExportListeners('scales');
		},

		addDashboardExportButtons() {
			const html = `
				<div class="naboo-dashboard-export-section">
					<h3>
						<svg class="naboo-export-icon-large" viewBox="0 0 24 24" width="20" height="20">
							<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
						</svg>
						Export My Data
					</h3>
					<p>Download your favorite scales and saved searches</p>
					<div class="naboo-export-buttons">
						<button class="naboo-export-btn naboo-export-btn-large naboo-export-favorites-json" data-type="favorites" data-format="json">
							<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path></svg>
							Export Favorites (JSON)
						</button>
						<button class="naboo-export-btn naboo-export-btn-large naboo-export-favorites-csv" data-type="favorites" data-format="csv">
							<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path></svg>
							Export Favorites (CSV)
						</button>
					</div>
				</div>
			`;

			const $dashboard = $('.naboo-user-dashboard, [data-dashboard="true"]').first();
			if ($dashboard.length) {
				$dashboard.append(html);
				this.setupExportListeners('favorites');
			}
		},

		setupExportListeners(type) {
			$(document).on('click', '.naboo-export-json-btn, .naboo-export-csv-btn, .naboo-export-favorites-json, .naboo-export-favorites-csv', (e) => {
				const $btn = $(e.currentTarget);
				const format = $btn.data('format');
				const exportType = $btn.data('type') || type;

				this.exportData(exportType, format, $btn);
			});
		},

		exportData(type, format, $btn) {
			const originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<span class="naboo-spinner"></span> Exporting...');

			let url = '';
			if ('favorites' === type) {
				url = `${this.apiUrl}/export/my-favorites?format=${format}`;
			} else {
				url = `${this.apiUrl}/export/scales?format=${format}`;
			}

			$.ajax({
				url: url,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					if (response.success) {
						this.downloadExport(response, format);
						this.showNotification(`Export successful! (${response.count || '?'} items)`, 'success');
					}
					$btn.prop('disabled', false).html(originalHtml);
				},
				error: (err) => {
					$btn.prop('disabled', false).html(originalHtml);
					this.showNotification('Export failed. Please try again.', 'error');
					console.error('Export error:', err);
				},
			});
		},

		downloadExport(response, format) {
			let content = '';
			let mimeType = 'text/plain';
			let filename = response.filename || `export-${Date.now()}`;

			if ('json' === format) {
				content = JSON.stringify(response.data, null, 2);
				mimeType = 'application/json';
			} else if ('csv' === format) {
				content = response.data;
				mimeType = 'text/csv';
			}

			const blob = new Blob([content], { type: mimeType });
			const link = document.createElement('a');
			link.href = URL.createObjectURL(blob);
			link.download = filename;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			URL.revokeObjectURL(link.href);
		},

		showNotification(message, type = 'success') {
			const $notification = $(`
				<div class="naboo-notification naboo-notification-${type}">
					<div class="naboo-notification-content">
						${type === 'success' ? '<svg class="naboo-icon" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>' : '<svg class="naboo-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path></svg>'}
						<span>${message}</span>
					</div>
					<button class="naboo-notification-close">&times;</button>
				</div>
			` );

			$('body').append($notification);

			$notification.on('click', '.naboo-notification-close', () => {
				$notification.fadeOut(300, function () {
					$(this).remove();
				});
			});

			setTimeout(() => {
				$notification.fadeOut(300, function () {
					$(this).remove();
				});
			}, 5000);
		},
	};

	// Initialize on document ready
	$(document).ready(() => {
		DataExport.init();
	});

})(jQuery);
