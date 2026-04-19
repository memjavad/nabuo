/**
 * Search Result Improvements
 * Faceted search, filter sidebar, and saved searches
 */

(function ($) {
	'use strict';

	if (typeof apaSearchImprovements === 'undefined') {
		return;
	}

	const SearchImprovements = {
		apiUrl: apaSearchImprovements.api_url || '',
		nonce: apaSearchImprovements.nonce || '',
		isLoggedIn: apaSearchImprovements.is_logged_in || false,
		activeFilters: {},
		currentQuery: '',

		init() {
			this.setupSearch();
			this.setupFilterSidebar();
			this.setupSavedSearches();
		},

		setupSearch() {
			const $searchForm = $('.naboo-search-form');
			if (!$searchForm.length) return;

			$searchForm.after(this.createFilterSidebar());
			$searchForm.after(this.createResultsContainer());

			this.loadFacets('');
		},

		createFilterSidebar() {
			return `
				<aside class="naboo-filter-sidebar">
					<div class="naboo-filters-header">
						<h3>Filters</h3>
						<button class="naboo-clear-filters" style="display: none;">Clear All</button>
					</div>

					<div class="naboo-filter-group">
						<h4>Categories</h4>
						<div class="naboo-filter-items" data-filter-type="categories"></div>
					</div>

					<div class="naboo-filter-group">
						<h4>Authors</h4>
						<div class="naboo-filter-items" data-filter-type="authors"></div>
					</div>

					<div class="naboo-filter-group">
						<h4>Year</h4>
						<div class="naboo-filter-items" data-filter-type="years"></div>
					</div>

					<div class="naboo-filter-group">
						<h4>Language</h4>
						<div class="naboo-filter-items" data-filter-type="languages"></div>
					</div>

					${this.isLoggedIn ? `
						<div class="naboo-saved-searches-widget">
							<h4>Saved Searches</h4>
							<div class="naboo-saved-searches-list"></div>
							<button class="naboo-save-current-search">Save This Search</button>
						</div>
					` : ''}
				</aside>
			`;
		},

		createResultsContainer() {
			return `<div class="naboo-search-results-info"></div>`;
		},

		setupFilterSidebar() {
			const $sidebar = $('.naboo-filter-sidebar');

			$sidebar.on('change', 'input[type="checkbox"]', (e) => {
				const $input = $(e.target);
				const filterType = $input.closest('.naboo-filter-items').data('filter-type');
				const value = $input.val();

				if ($input.is(':checked')) {
					this.activeFilters[filterType] = this.activeFilters[filterType] || [];
					this.activeFilters[filterType].push(value);
				} else {
					this.activeFilters[filterType] = this.activeFilters[filterType].filter(v => v !== value);
					if (!this.activeFilters[filterType].length) {
						delete this.activeFilters[filterType];
					}
				}

				this.updateActiveFilterDisplay();
			});

			$sidebar.on('click', '.naboo-clear-filters', () => {
				this.activeFilters = {};
				$sidebar.find('input[type="checkbox"]').prop('checked', false);
				this.updateActiveFilterDisplay();
			});
		},

		loadFacets(query) {
			$.ajax({
				url: `${this.apiUrl}/search/facets`,
				method: 'GET',
				data: { query },
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.renderFacets(response);
					this.updateResultsInfo(response.total_results);
				},
				error: (err) => {
					console.error('Facets error:', err);
				},
			});
		},

		renderFacets(data) {
			this.renderFacetGroup('categories', data.categories, 'Categories');
			this.renderFacetGroup('authors', data.authors, 'Authors');
			this.renderFacetGroup('years', data.years, 'Years');
			this.renderFacetGroup('languages', data.languages, 'Languages');
		},

		renderFacetGroup(type, items, label) {
			const $container = $(`.naboo-filter-items[data-filter-type="${type}"]`);

			if (!items || !items.length) {
				$container.html(`<p class="naboo-no-facets">No ${label.toLowerCase()} available</p>`);
				return;
			}

			const html = items.map((item, index) => {
				const name = item.name || item.year || item.language;
				const count = item.count || 0;
				const value = item.id || item.year || item.language;

				return `
					<label class="naboo-filter-item">
						<input type="checkbox" value="${this.escapeAttr(value)}" />
						<span class="naboo-filter-label">${this.escapeHtml(name)}</span>
						<span class="naboo-filter-count">${count}</span>
					</label>
				`;
			}).join('');

			$container.html(html);
		},

		updateResultsInfo(total) {
			const $info = $('.naboo-search-results-info');
			const activeCount = Object.values(this.activeFilters).reduce((sum, arr) => sum + arr.length, 0);

			let text = `Showing ${total} result${total !== 1 ? 's' : ''}`;
			if (activeCount > 0) {
				text += ` (${activeCount} filter${activeCount !== 1 ? 's' : ''} applied)`;
			}

			$info.html(`<div class="naboo-results-summary">${text}</div>`);
		},

		updateActiveFilterDisplay() {
			const $sidebar = $('.naboo-filter-sidebar');
			const activeCount = Object.values(this.activeFilters).reduce((sum, arr) => sum + arr.length, 0);
			const $clearBtn = $sidebar.find('.naboo-clear-filters');

			if (activeCount > 0) {
				$clearBtn.show();
			} else {
				$clearBtn.hide();
			}
		},

		setupSavedSearches() {
			if (!this.isLoggedIn) return;

			const $sidebar = $('.naboo-filter-sidebar');
			const $saveBtn = $sidebar.find('.naboo-save-current-search');

			$saveBtn.on('click', () => {
				this.openSaveSearchModal();
			});

			this.loadSavedSearches();
		},

		openSaveSearchModal() {
			const modal = `
				<div class="naboo-modal-overlay">
					<div class="naboo-modal">
						<div class="naboo-modal-header">
							<h3>Save This Search</h3>
							<button class="naboo-modal-close">&times;</button>
						</div>
						<div class="naboo-modal-body">
							<input type="text" class="naboo-search-name-input" placeholder="e.g., 'Anxiety Scales' or 'Recent Scales'" />
							<label class="naboo-public-checkbox">
								<input type="checkbox" />
								<span>Make this search public</span>
							</label>
						</div>
						<div class="naboo-modal-footer">
							<button class="naboo-btn-secondary naboo-modal-cancel">Cancel</button>
							<button class="naboo-btn-primary naboo-modal-save">Save Search</button>
						</div>
					</div>
				</div>
			`;

			const $modal = $(modal);
			$('body').append($modal);

			$modal.on('click', '.naboo-modal-close, .naboo-modal-cancel, .naboo-modal-overlay', (e) => {
				if (e.target === e.currentTarget) {
					$modal.remove();
				}
			});

			$modal.on('click', '.naboo-modal-save', () => {
				const searchName = $modal.find('.naboo-search-name-input').val().trim();
				const isPublic = $modal.find('.naboo-public-checkbox input').is(':checked');

				if (!searchName) {
					alert('Please enter a search name');
					return;
				}

				this.saveSearch(searchName, isPublic);
				$modal.remove();
			});

			$modal.find('.naboo-search-name-input').focus();
		},

		saveSearch(name, isPublic) {
			const $searchInput = $('input[name="search"], input[name="keyword"], input[name="s"]').first();
			const query = $searchInput.val() || '';

			$.ajax({
				url: `${this.apiUrl}/saved-searches`,
				method: 'POST',
				data: JSON.stringify({
					search_name: name,
					search_query: query,
					filters: this.activeFilters,
					is_public: isPublic,
				}),
				contentType: 'application/json',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					alert('Search saved successfully!');
					this.loadSavedSearches();
				},
				error: (err) => {
					alert('Error saving search. Please try again.');
					console.error('Save search error:', err);
				},
			});
		},

		loadSavedSearches() {
			if (!this.isLoggedIn) return;

			$.ajax({
				url: `${this.apiUrl}/saved-searches`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.renderSavedSearches(response.saved_searches || []);
				},
				error: (err) => {
					console.error('Load saved searches error:', err);
				},
			});
		},

		renderSavedSearches(searches) {
			const $list = $('.naboo-saved-searches-list');

			if (!searches || !searches.length) {
				$list.html('<p class="naboo-no-searches">No saved searches yet</p>');
				return;
			}

			const html = searches.map((search) => `
				<div class="naboo-saved-search-item">
					<div class="naboo-saved-search-name">${this.escapeHtml(search.search_name)}</div>
					<div class="naboo-saved-search-actions">
						<button class="naboo-load-search" data-id="${search.id}" title="Load this search">↻</button>
						<button class="naboo-delete-search" data-id="${search.id}" title="Delete">✕</button>
					</div>
				</div>
			` ).join('');

			$list.html(html);

			$list.on('click', '.naboo-load-search', (e) => {
				const id = $(e.target).data('id');
				this.loadSavedSearch(id);
			});

			$list.on('click', '.naboo-delete-search', (e) => {
				const id = $(e.target).data('id');
				if (confirm('Delete this saved search?')) {
					this.deleteSavedSearch(id);
				}
			});
		},

		loadSavedSearch(id) {
			$.ajax({
				url: `${this.apiUrl}/saved-searches/${id}`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					const $searchInput = $('input[name="search"], input[name="keyword"], input[name="s"]').first();
					$searchInput.val(response.search_query || '');

					this.activeFilters = response.filters || {};
					this.updateActiveFilterDisplay();

					// Optionally trigger search here
					$searchInput.closest('form').submit();
				},
				error: (err) => {
					console.error('Load saved search error:', err);
				},
			});
		},

		deleteSavedSearch(id) {
			$.ajax({
				url: `${this.apiUrl}/saved-searches/${id}`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': this.nonce },
				success: () => {
					this.loadSavedSearches();
				},
				error: (err) => {
					console.error('Delete search error:', err);
				},
			});
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
		SearchImprovements.init();
	});

})(jQuery);
