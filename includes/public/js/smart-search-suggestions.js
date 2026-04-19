/**
 * Smart Search Suggestions
 * Provides autocomplete, trending searches, and real-time suggestions
 */

(function ($) {
	'use strict';

	if (typeof apaSmartSearch === 'undefined') {
		return;
	}

	const SmartSearch = {
		apiUrl: apaSmartSearch.api_url,
		nonce: apaSmartSearch.nonce,
		minChars: apaSmartSearch.min_chars,
		debounceTimer: null,
		currentFocus: -1,
		currentSuggestions: [],

		init() {
			this.setupSearchForm();
			this.setupEventListeners();
		},

		setupSearchForm() {
			const $searchForm = $('.naboo-search-form, [data-naboo-search="true"]');
			if (!$searchForm.length) return;

			const $searchInput = $searchForm.find('input[type="search"], input[name*="search"], input[name*="keyword"]').first();
			if (!$searchInput.length) return;

			$searchInput.attr('autocomplete', 'off');
			$searchInput.after(this.createSuggestionsUI());
		},

		createSuggestionsUI() {
			return `
				<div class="naboo-search-suggestions-container">
					<div class="naboo-suggestions-dropdown" style="display: none;">
						<div class="naboo-suggestions-list"></div>
					</div>
					<div class="naboo-trending-section">
						<div class="naboo-trending-title">Trending Now</div>
						<div class="naboo-trending-items"></div>
					</div>
				</div>
			`;
		},

		setupEventListeners() {
			const $container = $('.naboo-search-form, [data-naboo-search="true"]');
			const $input = $container.find('input[type="search"], input[name*="search"], input[name*="keyword"]').first();
			const $dropdown = $container.find('.naboo-suggestions-dropdown');
			const $suggestionsListHolder = $container.find('.naboo-suggestions-list');
			const $trendingItems = $container.find('.naboo-trending-items');

			if (!$input.length) return;

			$input.on('focus', () => {
				if ($input.val().length === 0) {
					this.loadTrendingSearches($trendingItems);
				}
			});

			$input.on('input', (e) => {
				clearTimeout(this.debounceTimer);
				const query = $(e.target).val().trim();

				if (query.length < this.minChars) {
					$dropdown.hide();
					if (query.length === 0) {
						this.loadTrendingSearches($trendingItems);
					}
					return;
				}

				this.debounceTimer = setTimeout(() => {
					this.loadSuggestions(query, $suggestionsListHolder, $dropdown);
					this.recordSearch(query);
				}, 300);
			});

			$input.on('keydown', (e) => {
				const $items = $suggestionsListHolder.find('.naboo-suggestion-item');
				const itemCount = $items.length;

				switch (e.key) {
					case 'ArrowDown':
						e.preventDefault();
						this.currentFocus = Math.min(this.currentFocus + 1, itemCount - 1);
						this.highlightSuggestion($items);
						break;

					case 'ArrowUp':
						e.preventDefault();
						this.currentFocus = Math.max(this.currentFocus - 1, -1);
						this.highlightSuggestion($items);
						break;

					case 'Enter':
						e.preventDefault();
						if (this.currentFocus >= 0 && $items.length) {
							$items.eq(this.currentFocus).click();
						} else {
							$input.closest('form').submit();
						}
						break;

					case 'Escape':
						$dropdown.hide();
						this.currentFocus = -1;
						break;
				}
			});

			$(document).on('click', (e) => {
				if (!$(e.target).closest('.naboo-search-suggestions-container').length) {
					$dropdown.hide();
					this.currentFocus = -1;
				}
			});
		},

		loadSuggestions(query, $container, $dropdown) {
			$.ajax({
				url: `${this.apiUrl}/search/suggestions`,
				method: 'GET',
				data: { query, limit: 8 },
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.currentSuggestions = response.suggestions || [];
					this.renderSuggestions(response.suggestions || [], $container);
					$dropdown.show();
					this.currentFocus = -1;
				},
				error: (err) => {
					console.error('Smart search suggestions error:', err);
				},
			});
		},

		renderSuggestions(suggestions, $container) {
			const html = suggestions.map((suggestion, index) => `
				<div class="naboo-suggestion-item" data-index="${index}" data-suggestion="${this.escapeHtml(suggestion)}">
					<svg class="naboo-suggestion-icon" viewBox="0 0 24 24" width="16" height="16">
						<path d="M15.5 1h-8C6.12 1 5 2.12 5 3.5v17C5 21.88 6.12 23 7.5 23h8c1.38 0 2.5-1.12 2.5-2.5v-17C18 2.12 16.88 1 15.5 1zm-4 21c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm4.5-4H7V4h8v14z"></path>
					</svg>
					<span class="naboo-suggestion-text">${this.escapeHtml(suggestion)}</span>
					<svg class="naboo-suggestion-arrow" viewBox="0 0 24 24" width="14" height="14">
						<path d="M5 13h14v-2H5v2z" /><path d="M13 5v8h2V7.42l5.29 5.29 1.42-1.42L16.42 5H21V3h-8z" />
					</svg>
				</div>
			` ).join('');

			$container.html(html || '<div class="naboo-no-suggestions">No suggestions found</div>');

			$container.on('click', '.naboo-suggestion-item', (e) => {
				const $item = $(e.currentTarget);
				const suggestion = $item.data('suggestion');
				const $input = $item.closest('.naboo-search-suggestions-container').prev('input');

				$input.val(suggestion);
				$input.closest('form').submit();
			});
		},

		loadTrendingSearches($container) {
			$.ajax({
				url: `${this.apiUrl}/search/trending`,
				method: 'GET',
				data: { limit: 6, period: 'week' },
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.renderTrending(response.trending || [], $container);
				},
				error: (err) => {
					console.error('Trending searches error:', err);
				},
			});
		},

		renderTrending(trending, $container) {
			const html = trending.map((item) => `
				<div class="naboo-trending-item" data-term="${this.escapeHtml(item.search_term)}">
					<span class="naboo-trending-term">${this.escapeHtml(item.search_term)}</span>
					<span class="naboo-trending-count">${item.search_count}</span>
				</div>
			` ).join('');

			$container.html(html || '');

			$container.on('click', '.naboo-trending-item', (e) => {
				const $item = $(e.currentTarget);
				const term = $item.data('term');
				const $input = $item.closest('.naboo-search-suggestions-container').prev('input');

				$input.val(term);
				$input.focus();
				this.loadSuggestions(term, $item.closest('.naboo-search-suggestions-container').find('.naboo-suggestions-list'),
					$item.closest('.naboo-search-suggestions-container').find('.naboo-suggestions-dropdown'));
			});
		},

		highlightSuggestion($items) {
			$items.removeClass('active');
			if (this.currentFocus >= 0) {
				$items.eq(this.currentFocus).addClass('active');
			}
		},

		recordSearch(query) {
			$.ajax({
				url: `${this.apiUrl}/search/record`,
				method: 'POST',
				data: JSON.stringify({ query }),
				contentType: 'application/json',
				headers: { 'X-WP-Nonce': this.nonce },
				error: (err) => {
					// Silently fail, non-critical
				},
			});
		},

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},
	};

	// Initialize on document ready
	$(document).ready(() => {
		SmartSearch.init();
	});

})(jQuery);
