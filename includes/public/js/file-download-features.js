/**
 * File Download Features
 * Download tracking and file management for scale documents
 */

(function ($) {
	'use strict';

	if (typeof apaFileDownloads === 'undefined') {
		return;
	}

	const FileDownloads = {
		apiUrl: apaFileDownloads.api_url,
		nonce: apaFileDownloads.nonce,
		scaleId: apaFileDownloads.scale_id,

		init() {
			this.loadFiles();
		},

		loadFiles() {
			$.ajax({
				url: `${this.apiUrl}/scales/${this.scaleId}/files`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					if (response.files && response.files.length) {
						this.renderDownloadSection(response.files);
					}
				},
				error: (err) => {
					console.error('Failed to load files:', err);
				},
			});
		},

		renderDownloadSection(files) {
			const $content = $('.entry-content, .post-content, main').first();
			if (!$content.length) return;

			const html = `
				<section class="naboo-files-download-section">
					<div class="naboo-files-header">
						<h3>
							<svg class="naboo-files-icon" viewBox="0 0 24 24" width="24" height="24">
								<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
							</svg>
							Download Files
						</h3>
						<p class="naboo-files-description">Download the scale document and related files</p>
					</div>
					<div class="naboo-files-list">
						${files.map((file, index) => this.renderFile(file, index)).join('')}
					</div>
				</section>
			`;

			// Insert before comments or at end of content
			const $comments = $('.naboo-comments-section, .comments-area');
			if ($comments.length) {
				$comments.before(html);
			} else {
				$content.append(html);
			}

			this.setupFileDownloadListeners();
		},

		renderFile(file, index) {
			const fileType = this.getFileType(file.mime_type);
			const icon = this.getFileIcon(file.mime_type);

			return `
				<div class="naboo-file-item">
					<div class="naboo-file-icon-wrapper ${fileType}">
						${icon}
					</div>
					<div class="naboo-file-info">
						<h4 class="naboo-file-title">${this.escapeHtml(file.title)}</h4>
						<p class="naboo-file-details">
							<span class="naboo-file-size">${file.file_size_human}</span>
							<span class="naboo-file-type">${fileType.toUpperCase()}</span>
							<span class="naboo-file-downloads">${file.download_count} download${file.download_count !== 1 ? 's' : ''}</span>
						</p>
					</div>
					<button class="naboo-download-btn" data-file-id="${file.id}" data-filename="${this.escapeAttr(file.filename)}">
						<svg class="naboo-download-icon" viewBox="0 0 24 24" width="18" height="18">
							<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
						</svg>
						<span>Download</span>
					</button>
				</div>
			`;
		},

		setupFileDownloadListeners() {
			$(document).on('click', '.naboo-download-btn', (e) => {
				const $btn = $(e.currentTarget);
				const fileId = $btn.data('file-id');
				const filename = $btn.data('filename');

				this.downloadFile(fileId, filename, $btn);
			});
		},

		downloadFile(fileId, filename, $btn) {
			const originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<span class="naboo-spinner"></span> Downloading...');

			$.ajax({
				url: `${this.apiUrl}/files/${fileId}/download`,
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify({ scale_id: this.scaleId }),
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					if (response.file_url) {
						// Trigger download
						const link = document.createElement('a');
						link.href = response.file_url;
						link.download = filename || response.filename;
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);

						$btn.prop('disabled', false).html(originalHtml);
						this.showNotification('Download started!', 'success');
					}
				},
				error: (err) => {
					$btn.prop('disabled', false).html(originalHtml);
					this.showNotification('Error downloading file. Please try again.', 'error');
					console.error('Download error:', err);
				},
			});
		},

		getFileType(mimeType) {
			if (!mimeType) return 'file';

			const types = {
				'application/pdf': 'pdf',
				'application/msword': 'doc',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'doc',
				'application/vnd.ms-excel': 'xls',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xls',
				'text/plain': 'txt',
				'text/csv': 'csv',
				'application/zip': 'zip',
			};

			return types[mimeType] || 'file';
		},

		getFileIcon(mimeType) {
			const fileType = this.getFileType(mimeType);

			const icons = {
				pdf: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
				doc: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
				xls: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
				txt: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
				csv: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
				zip: '<svg viewBox="0 0 24 24"><path d="M19 12.998h-15v2h15v-2z"/></svg>',
				file: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>',
			};

			return icons[fileType] || icons.file;
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

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		escapeAttr(text) {
			return String(text).replace(/["'<>&]/g, (char) => {
				const entities = {
					'"': '&quot;',
					"'": '&#39;',
					'<': '&lt;',
					'>': '&gt;',
					'&': '&amp;',
				};
				return entities[char];
			});
		},
	};

	// Initialize on document ready
	$(document).ready(() => {
		FileDownloads.init();
	});

})(jQuery);
