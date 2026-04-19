/**
 * Scale Comparison Tool - Frontend JavaScript
 */

(function ($) {
	'use strict';

	if (typeof apaComparison === 'undefined') {
		return;
	}

	const ScaleComparison = {
		selectedScales: [],
		maxScales: 4,

		init() {
			this.bindEvents();
			this.loadFromStorage();
			this.updateComparisonUI();
		},

		bindEvents() {
			// Add to comparison button
			$(document).on('click', '.naboo-add-compare-btn', (e) => {
				e.preventDefault();
				const $btn = $(e.currentTarget);
				const scaleId = $btn.data('scale-id');
				const scaleTitle = $btn.data('scale-title');

				this.addToComparison(scaleId, scaleTitle);
			});

			// Remove from comparison
			$(document).on('click', '.naboo-compare-remove', (e) => {
				e.preventDefault();
				const scaleId = $(e.currentTarget).data('id');
				this.removeFromComparison(scaleId);
			});

			// Compare button
			$(document).on('click', '.naboo-run-compare-btn', (e) => {
				e.preventDefault();
				if (this.selectedScales.length >= 2) {
					this.showComparison();
				}
			});

			// Clear comparison
			$(document).on('click', '.naboo-clear-compare-btn', (e) => {
				e.preventDefault();
				this.clearComparison();
			});

			// Toggle bar
			$(document).on('click', '.naboo-compare-bar-toggle', (e) => {
				e.preventDefault();
				$('.naboo-compare-bar-inner').toggleClass('naboo-collapsed');
			});

			// Close comparison modal
			$(document).on('click', '.naboo-compare-modal-close', (e) => {
				e.preventDefault();
				$('#naboo-compare-modal').fadeOut(200);
				$('body').css('overflow', '');
			});

			// Click outside modal to close
			$(document).on('click', '.naboo-compare-modal-overlay', (e) => {
				if (e.target === e.currentTarget) {
					$('#naboo-compare-modal').fadeOut(200);
					$('body').css('overflow', '');
				}
			});
		},

		addToComparison(scaleId, scaleTitle) {
			const existing = this.selectedScales.find(s => s.id === scaleId);
			if (existing) {
				this.showNotification('Scale already in comparison', 'info');
				return;
			}

			if (this.selectedScales.length >= this.maxScales) {
				this.showNotification(`Maximum ${this.maxScales} scales allowed for comparison`, 'error');
				return;
			}

			this.selectedScales.push({ id: scaleId, title: scaleTitle });
			this.saveToStorage();
			this.updateComparisonUI();

			// Pulse animation on the bar
			const $bar = $('#naboo-compare-bar');
			if ($bar.css('display') !== 'none') {
				$bar.addClass('naboo-pulse');
				setTimeout(() => $bar.removeClass('naboo-pulse'), 500);
			}
		},

		removeFromComparison(scaleId) {
			this.selectedScales = this.selectedScales.filter(s => s.id !== scaleId);
			this.saveToStorage();
			this.updateComparisonUI();
		},

		updateComparisonUI() {
			const $bar = $('#naboo-compare-bar');
			const $container = $('.naboo-compare-items');
			const $count = $('.naboo-compare-count');
			const $btn = $('.naboo-run-compare-btn');

			$count.text(`(${this.selectedScales.length})`);

			if (this.selectedScales.length === 0) {
				$bar.slideUp(200);
				return;
			}

			$bar.slideDown(200);
			$btn.prop('disabled', this.selectedScales.length < 2);

			let html = '';
			this.selectedScales.forEach(scale => {
				html += `
					<div class="naboo-compare-item">
						<span class="naboo-compare-item-title">${scale.title}</span>
						<button class="naboo-compare-remove" data-id="${scale.id}">&times;</button>
					</div>
				`;
			});

			// Add placeholders for remaining slots
			for (let i = this.selectedScales.length; i < this.maxScales; i++) {
				html += `<div class="naboo-compare-item naboo-compare-empty"><span>Add Scale</span></div>`;
			}

			$container.html(html);

			// Update button states on the page if they exist
			$('.naboo-add-compare-btn').each((i, btn) => {
				const id = $(btn).data('scale-id');
				if (this.selectedScales.find(s => s.id === id)) {
					$(btn).addClass('naboo-is-added').find('svg').css('stroke', '#00796b');
				} else {
					$(btn).removeClass('naboo-is-added').find('svg').css('stroke', 'currentColor');
				}
			});
		},

		showComparison() {
			if (this.selectedScales.length < 2) {
				return;
			}

			const $modal = $('#naboo-compare-modal');
			const $spinner = $('.naboo-compare-spinner');
			const $container = $('.naboo-compare-table-container');

			$('body').css('overflow', 'hidden');
			$modal.fadeIn(200);

			$container.hide();
			$spinner.show();

			const scaleIds = this.selectedScales.map(s => s.id);

			$.ajax({
				url: apaComparison.ajaxUrl + 'comparison/scales-data',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaComparison.nonce,
				},
				data: JSON.stringify({ scale_ids: scaleIds }),
				contentType: 'application/json',
				success: (response) => {
					$spinner.hide();
					this.renderComparisonTable(response, $container);
					$container.fadeIn(200);
				},
				error: () => {
					$spinner.hide();
					$container.html('<p class="naboo-error-text">Failed to load comparison data. Please try again.</p>').show();
				},
			});
		},

		renderComparisonTable(scales, $container) {
			let html = '<table class="naboo-comparison-datatable">';

			// Header Row
			html += '<thead><tr><th></th>';
			scales.forEach(scale => {
				html += `<th><a href="${scale.permalink}" target="_blank">${scale.title}</a></th>`;
			});
			html += '</tr></thead><tbody>';

			// Metrics defined with their display labels
			const metrics = [
				{ key: 'reliability', label: 'Reliability (α)' },
				{ key: 'validity', label: 'Validity' },
				{ key: 'items', label: 'Total Items' },
				{ key: 'year', label: 'Publication Year' },
				{ key: 'language', label: 'Language' },
				{ key: 'population', label: 'Population Group' },
				{ key: 'categories', label: 'Categories', isArray: true },
				{ key: 'authors', label: 'Authors', isArray: true },
			];

			metrics.forEach(metric => {
				html += `<tr><td class="naboo-compare-metric-name">${metric.label}</td>`;

				scales.forEach(scale => {
					let value = scale[metric.key];
					if (metric.isArray && Array.isArray(value)) {
						value = value.join(', ');
					}
					if (!value) {
						value = '<span class="naboo-compare-empty-val">—</span>';
					}
					html += `<td>${value}</td>`;
				});
				html += '</tr>';
			});

			html += '</tbody></table>';
			$container.html(html);
		},

		clearComparison() {
			this.selectedScales = [];
			this.saveToStorage();
			this.updateComparisonUI();
		},

		saveToStorage() {
			try {
				localStorage.setItem('naboo_compare_scales', JSON.stringify(this.selectedScales));
			} catch (e) {
				console.warn('LocalStorage not available');
			}
		},

		loadFromStorage() {
			try {
				const stored = localStorage.getItem('naboo_compare_scales');
				if (stored) {
					this.selectedScales = JSON.parse(stored);
				}
			} catch (e) {
				console.warn('LocalStorage not available');
			}
		},

		showNotification(message, type = 'info') {
			$('.naboo-compare-toast').remove();

			const html = `
				<div class="naboo-toast naboo-toast-${type}">
					${message}
				</div>
			`;

			$('body').append(html);
			const $notif = $('.naboo-toast:last');

			$notif.fadeIn(300);

			setTimeout(() => {
				$notif.fadeOut(300, function () {
					$(this).remove();
				});
			}, 3000);
		},
	};

	$(document).ready(() => {
		ScaleComparison.init();
	});

})(jQuery);

