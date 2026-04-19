/**
 * PDF Export Functionality
 * Exports psychological scales as PDF documents
 */

(function ($) {
	'use strict';

	if (typeof apaPDFExport === 'undefined') {
		return;
	}

	const PDFExport = {
		apiUrl: apaPDFExport.api_url,
		nonce: apaPDFExport.nonce,
		scaleId: apaPDFExport.scale_id,
		postTitle: apaPDFExport.post_title,

		init() {
			this.setupEventListeners();
		},

		setupEventListeners() {
			$(document).on('click', '.naboo-export-pdf-btn', (e) => {
				e.preventDefault();
				this.generateAndDownloadPDF();
			});
		},

		generateAndDownloadPDF() {
			const $btn = $('.naboo-export-pdf-btn');
			const originalText = $btn.html();

			// Show loading state
			$btn.prop('disabled', true)
				.html('<svg class="naboo-spinner" viewBox="0 0 50 50" width="16" height="16"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg> Generating...');

			// Fetch PDF data from server
			$.ajax({
				url: `${this.apiUrl}scales/${this.scaleId}/export-pdf`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					if (response.success && response.pdf_data) {
						try {
							// Generate PDF using html2pdf
							const decodedHtml = atob(response.pdf_data);
							const element = document.createElement('div');
							element.innerHTML = decodedHtml;

							const opt = {
								margin: [10, 10, 10, 10],
								filename: `${this.sanitizeFilename(this.postTitle)}.pdf`,
								image: { type: 'jpeg', quality: 0.98 },
								html2canvas: { scale: 2, useCORS: true },
								jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' },
							};

							// Required: append temporarily to body so html2canvas can render it properly, then remove
							element.style.position = 'absolute';
							element.style.left = '-9999px';
							document.body.appendChild(element);

							html2pdf().set(opt).from(element).save().then(() => {
								document.body.removeChild(element);
								$btn.prop('disabled', false).html(originalText);
								this.showNotification('PDF downloaded successfully!');
							});

						} catch (e) {
							$btn.prop('disabled', false).html(originalText);
							this.showNotification('Error rendering PDF. Please try again.', 'error');
							console.error('PDF rendering error:', e);
						}
					} else {
						$btn.prop('disabled', false).html(originalText);
						this.showNotification('Error generating PDF.', 'error');
					}
				},
				error: (err) => {
					$btn.prop('disabled', false).html(originalText);
					this.showNotification('Error generating PDF. Please try again.', 'error');
					console.error('PDF export API error:', err);
				},
			});
		},

		sanitizeFilename(name) {
			return name
				.toLowerCase()
				.trim()
				.replace(/[^\w\s-]/g, '')
				.replace(/\s+/g, '-')
				.substring(0, 50);
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
		PDFExport.init();
	});

})(jQuery);
