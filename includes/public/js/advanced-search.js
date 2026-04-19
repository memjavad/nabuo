/**
 * Naboo Advanced Academic Search — Scopus-Style Two-Screen Engine
 */
(function () {
	'use strict';

	if (!window.nabooAdvancedSearch) return;

	/* ── Full-viewport breakout using negative margins ────────────
	   This breaks out of any theme container (e.g. max-width: 1200px)
	   to make the search page full-bleed width, making the search
	   interface immersive again. without using position:fixed so
	   the header is still visible. */
	(function breakout() {
		var wrapper = document.getElementById('naboo-slide-wrapper');
		if (!wrapper) return;

		function applyBreakout() {
			var w = document.documentElement.clientWidth;
			var parentRect = wrapper.parentElement.getBoundingClientRect();
			wrapper.style.width = w + 'px';
			wrapper.style.maxWidth = 'none';

			if (document.documentElement.dir === 'rtl' || document.body.classList.contains('rtl')) {
				wrapper.style.marginRight = -(w - parentRect.right) + 'px';
				wrapper.style.marginLeft = '';
			} else {
				wrapper.style.marginLeft = -parentRect.left + 'px';
				wrapper.style.marginRight = '';
			}
		}

		applyBreakout();
		window.addEventListener('resize', applyBreakout);
	})();

	const API = nabooAdvancedSearch.api_url;
	const NONCE = nabooAdvancedSearch.nonce;

	/* ── State ───────────────────────────────────────────────── */
	let rowCount = 1;
	let currentPage = 1;
	let activeView = window.innerWidth <= 850 ? 'grid' : 'list';
	let filtersData = null;

	/* ── DOM ─────────────────────────────────────────────────── */
	const slideTrack = document.getElementById('naboo-slide-track');
	const resultsWrap = document.getElementById('naboo-search-results-wrapper');
	const topbarCount = document.getElementById('naboo-topbar-count');
	const addRowBtn = document.getElementById('naboo-add-row');
	const addDateBtn = document.getElementById('naboo-add-date');
	const advToggle = document.getElementById('naboo-toggle-advanced');
	const advPanel = document.getElementById('naboo-advanced-panel');
	const dateRow = document.getElementById('naboo-date-range-row');
	const submitBtn = document.getElementById('naboo-submit-search');
	const clearBtn = document.getElementById('naboo-clear-form');
	const backBtn = document.getElementById('naboo-back-btn');
	const filterBadge = document.getElementById('naboo-filter-badge');
	const filterCount = document.getElementById('naboo-filter-count');

	if (!slideTrack || !resultsWrap) return;

	/* ── Slide (RTL-aware) ───────────────────────────────────────── */
	const isRTL = document.documentElement.dir === 'rtl'
		|| document.body.classList.contains('rtl')
		|| document.documentElement.lang === 'ar';

	// Ensure we always start on Screen 1 on page load.
	slideTrack.classList.remove('results-active');
	document.body.classList.add('naboo-search-page');
	document.body.classList.remove('naboo-results-view');

	function slideToResults() {
		slideTrack.classList.add('results-active');
		document.body.classList.add('naboo-results-view');
		if (resultsWrap) resultsWrap.scrollTop = 0;
	}
	function slideToSearch() {
		slideTrack.classList.remove('results-active');
		document.body.classList.remove('naboo-results-view');
	}
	if (backBtn) backBtn.addEventListener('click', slideToSearch);

	/* ── Toggle advanced panel ───────────────────────────────── */
	if (advToggle && advPanel) {
		advToggle.addEventListener('click', () => {
			const open = advPanel.style.display !== 'none';
			advPanel.style.display = open ? 'none' : 'block';
			advToggle.classList.toggle('open', !open);
		});
	}

	/* ── Toggle date row ─────────────────────────────────────── */
	if (addDateBtn && dateRow) {
		addDateBtn.addEventListener('click', () => {
			dateRow.style.display = dateRow.style.display === 'none' ? 'flex' : 'none';
			updateFilterBadge();
		});
	}

	/* ── Load filters from REST ──────────────────────────────── */
	fetch(API + '/search/filters', { headers: { 'X-WP-Nonce': NONCE } })
		.then(r => r.json())
		.then(data => {
			if (!data.success) return;
			filtersData = data.filters;
			populateFilters(data.filters);
		})
		.catch(() => { });

	function populateFilters(f) {
		[
			{ id: 'naboo-fstrip-category', key: 'categories', nameKey: 'name', valKey: 'id' },
			{ id: 'naboo-fstrip-author', key: 'authors', nameKey: 'name', valKey: 'id' },
			{ id: 'naboo-filter-language', key: 'languages', nameKey: 'name', valKey: 'id' },
			{ id: 'naboo-filter-test-type', key: 'test_types', nameKey: 'name', valKey: 'id' },
			{ id: 'naboo-filter-age-group', key: 'age_groups', nameKey: 'name', valKey: 'id' },
			{ id: 'naboo-filter-format', key: 'formats', nameKey: 'name', valKey: 'id' },
		].forEach(({ id, key, nameKey, valKey }) => {
			const sel = document.getElementById(id);
			if (!sel || !f[key]) return;
			f[key].forEach(item => {
				const opt = document.createElement('option');
				opt.value = valKey ? item[valKey] : item;
				opt.textContent = nameKey ? (item[nameKey] + (item.count ? ` (${item.count})` : '')) : item;
				sel.appendChild(opt);
			});
			sel.addEventListener('change', updateFilterBadge);

			// Initialize searchable dropdown
			initSearchableDropdown(id);
		});
	}

	/**
	 * Transforms a standard <select> into a premium searchable dropdown.
	 */
	function initSearchableDropdown(selectId) {
		const sel = document.getElementById(selectId);
		if (!sel) return;

		// Create component structure
		const wrapper = document.createElement('div');
		wrapper.className = 'naboo-searchable-dropdown';
		sel.parentNode.insertBefore(wrapper, sel);

		const inputWrap = document.createElement('div');
		inputWrap.className = 'naboo-sd-input-wrap';

		const searchInput = document.createElement('input');
		searchInput.type = 'text';
		searchInput.className = 'naboo-sd-search-input';
		searchInput.placeholder = sel.options[0] ? sel.options[0].text : 'Search...';
		searchInput.autocomplete = 'off';

		const searchIcon = document.createElement('span');
		searchIcon.className = 'naboo-sd-search-icon';
		searchIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;

		const resultsList = document.createElement('div');
		resultsList.className = 'naboo-sd-results';

		inputWrap.appendChild(searchInput);
		inputWrap.appendChild(searchIcon);
		wrapper.appendChild(inputWrap);
		wrapper.appendChild(resultsList);

		// Hide original select
		sel.style.display = 'none';

		// Close dropdown when clicking outside
		document.addEventListener('click', (e) => {
			if (!wrapper.contains(e.target)) {
				resultsList.style.display = 'none';
			}
		});

		// Focus logic
		searchInput.addEventListener('focus', () => {
			renderResults();
			resultsList.style.display = 'block';
		});

		// Filtering logic
		searchInput.addEventListener('input', () => {
			renderResults(searchInput.value.trim());
			resultsList.style.display = 'block';
		});

		function renderResults(query = '') {
			resultsList.innerHTML = '';
			const items = Array.from(sel.options).slice(1); // Skip the first default option
			const filtered = query
				? items.filter(opt => opt.text.toLowerCase().includes(query.toLowerCase()))
				: items.slice(0, 100); // Limit initial view for performance

			if (filtered.length === 0) {
				resultsList.innerHTML = `<div class="naboo-sd-no-results">${isRTL ? 'لم يتم العثور على نتائج' : 'No results found'}</div>`;
				return;
			}

			filtered.forEach(opt => {
				const item = document.createElement('div');
				item.className = 'naboo-sd-item';
				if (opt.selected) item.classList.add('selected');
				item.textContent = opt.text;
				item.addEventListener('click', () => {
					// Select the option in the original select
					sel.value = opt.value;
					searchInput.value = opt.text;
					resultsList.style.display = 'none';

					// Trigger change event for filter badge updates and other logic
					sel.dispatchEvent(new Event('change'));
				});
				resultsList.appendChild(item);
			});
		}

		// Handle synchronization if the select value changes externally (e.g. Clear button)
		sel.addEventListener('change', () => {
			if (sel.value === '') {
				searchInput.value = '';
			} else {
				const selectedOpt = sel.options[sel.selectedIndex];
				if (selectedOpt) {
					searchInput.value = selectedOpt.text;
				}
			}
		});
	}

	/* ── Add boolean rows ────────────────────────────────────── */
	if (addRowBtn) addRowBtn.addEventListener('click', addRow);

	function addRow() {
		rowCount++;
		const tmpl = document.getElementById('naboo-row-template');
		if (!tmpl) return;
		const clone = tmpl.content.cloneNode(true);
		const div = clone.querySelector('.naboo-sc-row');
		if (!div) return;
		div.id = 'naboo-row-' + rowCount;
		div.dataset.row = rowCount;
		const input = div.querySelector('.naboo-row-term');
		const box = div.querySelector('.naboo-suggestions-box');
		if (input) { input.id = 'naboo-row-' + rowCount + '-term'; }
		if (box) { box.id = 'naboo-suggestions-' + rowCount; }
		div.querySelector('.naboo-remove-row')?.addEventListener('click', () => div.remove());
		if (input && box) attachSuggestions(input, box);
		document.getElementById('naboo-search-rows').appendChild(div);
	}

	/* ── Autocomplete ────────────────────────────────────────── */
	attachSuggestions(
		document.getElementById('naboo-row-1-term'),
		document.getElementById('naboo-suggestions-1')
	);

	function attachSuggestions(input, box) {
		if (!input || !box) return;
		let sd;
		input.addEventListener('input', () => {
			clearTimeout(sd);
			const q = input.value.trim();
			if (q.length < 2) { box.style.display = 'none'; return; }
			sd = setTimeout(() => {
				fetch(`${API}/search/suggestions?search=${encodeURIComponent(q)}`, { headers: { 'X-WP-Nonce': NONCE } })
					.then(r => r.json())
					.then(data => {
						if (!data.success || !data.suggestions?.length) { box.style.display = 'none'; return; }
						box.innerHTML = '';
						data.suggestions.forEach(s => {
							const item = document.createElement('div');
							item.className = 'naboo-suggest-item';
							item.textContent = s;
							item.addEventListener('mousedown', e => { e.preventDefault(); input.value = s; box.style.display = 'none'; });
							box.appendChild(item);
						});
						box.style.display = 'block';
					}).catch(() => { });
			}, 280);
		});
		input.addEventListener('blur', () => setTimeout(() => box.style.display = 'none', 180));
	}

	/* ── Filter badge ────────────────────────────────────────── */
	function updateFilterBadge() {
		let n = 0;
		['naboo-fstrip-category', 'naboo-fstrip-author', 'naboo-filter-language', 'naboo-filter-test-type',
			'naboo-filter-age-group', 'naboo-filter-format', 'naboo-items-min', 'naboo-items-max'].forEach(id => {
				const el = document.getElementById(id);
				if (el && el.value) n++;
			});
		const yearFrom = document.getElementById('naboo-year-from-preset')?.value;
		const yearTo = document.getElementById('naboo-year-to')?.value;
		if (yearFrom || yearTo) n++;
		if (document.getElementById('naboo-filter-has-file')?.checked) n++;
		if (filterCount) filterCount.textContent = n;
		if (filterBadge) filterBadge.style.display = n > 0 ? 'inline-flex' : 'none';
	}

	['naboo-year-from-preset', 'naboo-year-to', 'naboo-items-min', 'naboo-items-max',
		'naboo-filter-has-file'].forEach(id => {
			document.getElementById(id)?.addEventListener('change', updateFilterBadge);
		});

	/* ── Collect params ──────────────────────────────────────── */
	function collectParams() {
		const rows = [];
		document.querySelectorAll('#naboo-search-rows .naboo-sc-row').forEach((rowEl, i) => {
			const term = rowEl.querySelector('.naboo-row-term')?.value.trim() || '';
			const field = rowEl.querySelector('.naboo-row-field')?.value || 'any';
			const op = rowEl.querySelector('.naboo-row-operator')?.value || 'AND';
			if (term) rows.push({ term, field, operator: i === 0 ? 'AND' : op });
		});

		return {
			rows,
			year_from: document.getElementById('naboo-year-from-preset')?.value || '',
			year_to: document.getElementById('naboo-year-to')?.value || '',
			categories: document.getElementById('naboo-fstrip-category')?.value
				? [document.getElementById('naboo-fstrip-category').value] : [],
			authors: document.getElementById('naboo-fstrip-author')?.value
				? [document.getElementById('naboo-fstrip-author').value] : [],
			language: document.getElementById('naboo-filter-language')?.value || '',
			test_type: document.getElementById('naboo-filter-test-type')?.value || '',
			age_group: document.getElementById('naboo-filter-age-group')?.value || '',
			format: document.getElementById('naboo-filter-format')?.value || '',
			items_min: document.getElementById('naboo-items-min')?.value || '',
			items_max: document.getElementById('naboo-items-max')?.value || '',
			has_file: document.getElementById('naboo-filter-has-file')?.checked ? '1' : '',
			sort: document.getElementById('naboo-sort')?.value || 'date',
			per_page: document.getElementById('naboo-per-page')?.value || '20',
			page: currentPage,
		};
	}

	/* ── Submit ──────────────────────────────────────────────── */
	if (submitBtn) submitBtn.addEventListener('click', () => { currentPage = 1; doSearch(); });
	document.getElementById('naboo-search-rows')?.addEventListener('keydown', e => {
		if (e.key === 'Enter') { currentPage = 1; doSearch(); }
	});

	/* ── Clear ───────────────────────────────────────────────── */
	if (clearBtn) {
		clearBtn.addEventListener('click', () => {
			document.querySelectorAll('.naboo-sc-row:not(#naboo-row-1)').forEach(r => r.remove());
			document.querySelectorAll('.naboo-row-term').forEach(i => i.value = '');
			['naboo-fstrip-category', 'naboo-fstrip-author', 'naboo-filter-language', 'naboo-filter-test-type',
				'naboo-year-to', 'naboo-items-min', 'naboo-items-max'].forEach(id => {
					const el = document.getElementById(id);
					if (el) el.value = '';
				});
			const hf = document.getElementById('naboo-filter-has-file');
			if (hf) hf.checked = false;
			if (dateRow) dateRow.style.display = 'none';
			if (advPanel) advPanel.style.display = 'none';
			advToggle?.classList.remove('open');
			updateFilterBadge();
			slideToSearch();
		});
	}

	/* ── Execute search ──────────────────────────────────────── */
	function doSearch() {
		const params = collectParams();
		const keyword = params.rows[0]?.term || '';

		resultsWrap.innerHTML = `
			<div class="naboo-results-loading">
				<div class="naboo-spinner"></div>
				<p>Searching the database…</p>
			</div>`;
		if (topbarCount) topbarCount.textContent = '';
		slideToResults();

		const url = new URL(API + '/search/advanced');
		url.searchParams.set('page', params.page);
		url.searchParams.set('per_page', params.per_page);
		url.searchParams.set('sort', params.sort);
		if (params.year_from) url.searchParams.set('year_from', params.year_from);
		if (params.year_to) url.searchParams.set('year_to', params.year_to);
		if (params.language) url.searchParams.set('language', params.language);
		if (params.test_type) url.searchParams.set('test_type', params.test_type);
		if (params.format) url.searchParams.set('format', params.format);
		if (params.age_group) url.searchParams.set('age_group', params.age_group);
		if (params.items_min) url.searchParams.set('items_min', params.items_min);
		if (params.items_max) url.searchParams.set('items_max', params.items_max);
		if (params.items_max) url.searchParams.set('items_max', params.items_max);
		if (params.has_file) url.searchParams.set('has_file', '1');
		params.categories.forEach(id => url.searchParams.append('categories[]', id));
		params.authors.forEach(id => url.searchParams.append('authors[]', id));
		params.rows.forEach((row, i) => {
			url.searchParams.append(`rows[${i}][term]`, row.term);
			url.searchParams.append(`rows[${i}][field]`, row.field);
			url.searchParams.append(`rows[${i}][operator]`, row.operator);
		});

		fetch(url.toString(), { headers: { 'X-WP-Nonce': NONCE } })
			.then(r => r.json())
			.then(data => {
				if (!data.success) { renderError('Search failed. Please try again.'); return; }
				renderResults(data, keyword);
				if (params.page === 1) saveRecentSearch(params); // Save only page 1 searches
			})
			.catch(err => renderError('Connection error: ' + err.message));
	}

	/* ── Recent Searches (localStorage) ────────────────────────── */
	const RECENT_KEY = 'naboo_recent_searches';
	const MAX_RECENT = 10;
	const recentWrap = document.getElementById('naboo-recent-searches-wrap');
	const recentList = document.getElementById('naboo-recent-list');
	const clearHistoryBtn = document.getElementById('naboo-clear-history-btn');

	function getRecentSearches() {
		try {
			const saved = localStorage.getItem(RECENT_KEY);
			return saved ? JSON.parse(saved) : [];
		} catch (e) { return []; }
	}

	function saveRecentSearch(payload) {
		try {
			let searches = getRecentSearches();

			// Reconstruct string representation easily visible to user
			const displayQuery = payload.rows.map((row, i) => {
				return (i > 0 ? (row.operator + ' ') : '') + row.field.toUpperCase() + '("' + row.term + '")';
			}).join(' ');

			if (!displayQuery.trim() || displayQuery === 'ANY("")') return; // Don't save totally empty defaults

			// Avoid identical consecutive saves
			if (searches.length > 0 && searches[0].display === displayQuery) return;

			searches.unshift({
				display: displayQuery,
				payload: JSON.parse(JSON.stringify(payload)),
				time: Date.now()
			});

			if (searches.length > MAX_RECENT) searches = searches.slice(0, MAX_RECENT);
			localStorage.setItem(RECENT_KEY, JSON.stringify(searches));
			renderRecentSearches();
		} catch (e) { console.warn('Could not save recent search.', e); }
	}

	function renderRecentSearches() {
		if (!recentWrap || !recentList) return;
		const searches = getRecentSearches();

		if (searches.length === 0) {
			recentWrap.style.display = 'none';
			return;
		}

		recentList.innerHTML = '';
		searches.forEach((item, index) => {
			const li = document.createElement('li');
			li.className = 'naboo-recent-item';
			const d = new Date(item.time);
			const timeStr = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });

			li.innerHTML = `
				<div class="naboo-recent-info">
					<span class="naboo-recent-query">${esc(item.display)}</span>
					<span class="naboo-recent-time">${timeStr}</span>
				</div>
				<button type="button" class="naboo-recent-run" data-index="${index}">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
					Run
				</button>
			`;
			recentList.appendChild(li);
		});
		recentWrap.style.display = 'block';
	}

	if (recentList) {
		recentList.addEventListener('click', e => {
			const runBtn = e.target.closest('.naboo-recent-run');
			if (!runBtn) return;
			const index = parseInt(runBtn.dataset.index, 10);
			const searches = getRecentSearches();
			if (searches[index]) {
				restoreSearchState(searches[index].payload);
				currentPage = 1;
				doSearch(); // immediately trigger
			}
		});
	}

	if (clearHistoryBtn) {
		clearHistoryBtn.addEventListener('click', () => {
			localStorage.removeItem(RECENT_KEY);
			renderRecentSearches();
		});
	}

	function restoreSearchState(payload) {
		// Clean rows
		document.querySelectorAll('.naboo-sc-row:not(#naboo-row-1)').forEach(r => r.remove());

		// Fill first row
		if (payload.rows && payload.rows.length > 0) {
			const firstRow = payload.rows[0];
			document.getElementById('naboo-row-1-field').value = firstRow.field || 'any';
			document.getElementById('naboo-row-1-term').value = firstRow.term || '';

			// Fill additional rows
			for (let j = 1; j < payload.rows.length; j++) {
				addRow();
				const rData = payload.rows[j];
				const newRow = document.querySelector('#naboo-search-rows').lastElementChild;
				if (newRow) {
					newRow.querySelector('.naboo-row-operator').value = rData.operator || 'AND';
					newRow.querySelector('.naboo-row-field').value = rData.field || 'any';
					newRow.querySelector('.naboo-row-term').value = rData.term || '';
				}
			}
		}

		// Fill advanced
		['naboo-fstrip-category', 'naboo-filter-language', 'naboo-filter-test-type',
			'naboo-filter-age-group', 'naboo-filter-format', 'naboo-year-from-preset', 'naboo-year-to',
			'naboo-items-min', 'naboo-items-max'].forEach(id => {
				const el = document.getElementById(id);
				if (el) el.value = ''; // Reset
			});

		if (payload.categories && payload.categories.length) document.getElementById('naboo-fstrip-category').value = payload.categories[0];
		if (payload.authors && payload.authors.length) document.getElementById('naboo-fstrip-author').value = payload.authors[0];
		if (payload.language) document.getElementById('naboo-filter-language').value = payload.language;
		if (payload.test_type) document.getElementById('naboo-filter-test-type').value = payload.test_type;
		if (payload.age_group) document.getElementById('naboo-filter-age-group').value = payload.age_group;
		if (payload.format) document.getElementById('naboo-filter-format').value = payload.format;
		if (payload.items_min) document.getElementById('naboo-items-min').value = payload.items_min;
		if (payload.items_max) document.getElementById('naboo-items-max').value = payload.items_max;
		if (payload.items_max) document.getElementById('naboo-items-max').value = payload.items_max;

		const hf = document.getElementById('naboo-filter-has-file');
		if (hf) hf.checked = !!payload.has_file;

		if (payload.year_from) document.getElementById('naboo-year-from-preset').value = payload.year_from;
		if (payload.year_to) document.getElementById('naboo-year-to').value = payload.year_to;
		if (payload.year_from || payload.year_to) document.getElementById('naboo-date-range-row').style.display = 'flex';

		if (payload.sort) document.getElementById('naboo-sort').value = payload.sort;
		if (payload.per_page) document.getElementById('naboo-per-page').value = payload.per_page;

		updateFilterBadge();
	}

	/* ── Render ──────────────────────────────────────────────── */
	function renderError(msg) {
		resultsWrap.innerHTML = `<div class="naboo-results-error">⚠️ ${esc(msg)}</div>`;
	}

	function renderResults(data, keyword) {
		if (topbarCount) {
			topbarCount.textContent = data.total.toLocaleString() + ' result' + (data.total !== 1 ? 's' : '')
				+ (keyword ? ` for "${keyword}"` : '');
		}

		if (!data.data.length) {
			resultsWrap.innerHTML = `
				<div class="naboo-no-results">
					<div class="naboo-no-results-icon">🔍</div>
					<h3>No scales found</h3>
					<p>Try broadening your search or adjusting the filters.</p>
				</div>`;
			return;
		}

		const offset = (currentPage - 1) * parseInt(data.per_page, 10);
		const cards = data.data.map((s, i) => renderCard(s, keyword, offset + i + 1)).join('');
		const pgHtml = data.total_pages > 1 ? renderPagination(data.page, data.total_pages) : '';

		resultsWrap.innerHTML = `
		<div class="naboo-mobile-filter-overlay" id="naboo-mobile-filter-overlay"></div>
		<button class="naboo-mobile-filter-toggle" id="naboo-mobile-filter-toggle" aria-label="Toggle Filters">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
		</button>
		<div class="naboo-results-layout">
			<aside class="naboo-facet-sidebar" id="naboo-facet-sidebar">
				<button class="naboo-sidebar-close" id="naboo-sidebar-close" aria-label="Close Filters">&times;</button>
				${buildFacetSidebar()}
			</aside>
			<div class="naboo-results-main">
				<h2 class="naboo-visually-hidden"><?php esc_html_e( 'Search Results', 'naboodatabase' ); ?></h2>
				<div class="naboo-results-toolbar">
					<span class="naboo-results-count-badge">
						<strong>${data.total.toLocaleString()}</strong>
						<span>Result${data.total !== 1 ? 's' : ''}</span>
						${keyword ? `<span class="naboo-kw-label">for <em>${esc(keyword)}</em></span>` : ''}
					</span>
					<div class="naboo-toolbar-right">
						<label class="naboo-toolbar-label">Sort</label>
						<select id="naboo-sort-inline" class="naboo-toolbar-select">
							<option value="date">Newest</option>
							<option value="year_desc">Year ↓</option>
							<option value="year_asc">Year ↑</option>
							<option value="reliability_desc">Reliability ↓</option>
							<option value="validity_desc">Validity ↓</option>
							<option value="relevance">Relevance</option>
							<option value="views">Most Viewed</option>
							<option value="title_asc">Title A–Z</option>
						</select>
						<div class="naboo-view-toggle-inline">
							<button class="naboo-vbtn ${activeView === 'list' ? 'active' : ''}" data-v="list" title="List">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1.5" fill="currentColor"/><circle cx="3" cy="12" r="1.5" fill="currentColor"/><circle cx="3" cy="18" r="1.5" fill="currentColor"/></svg>
							</button>
							<button class="naboo-vbtn ${activeView === 'grid' ? 'active' : ''}" data-v="grid" title="Grid">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
							</button>
						</div>
					</div>
				</div>
				${pgHtml}
				<div id="naboo-results-list" class="naboo-results-list${activeView === 'grid' ? ' naboo-grid-view' : ''}">${cards}</div>
				${pgHtml}
			</div>
		</div>`;

		// Mobile Filter Toggle Logic
		const mobileToggle = document.getElementById('naboo-mobile-filter-toggle');
		const sidebarClose = document.getElementById('naboo-sidebar-close');
		const overlay = document.getElementById('naboo-mobile-filter-overlay');
		const sidebar = document.getElementById('naboo-facet-sidebar');

		if (mobileToggle && sidebar && overlay && sidebarClose) {
			const toggleFilters = () => {
				sidebar.classList.toggle('active');
				overlay.classList.toggle('active');
			};
			mobileToggle.addEventListener('click', toggleFilters);
			sidebarClose.addEventListener('click', toggleFilters);
			overlay.addEventListener('click', toggleFilters);
		}

		// Sort inline sync
		const si = document.getElementById('naboo-sort-inline');
		if (si) {
			si.value = document.getElementById('naboo-sort')?.value || 'date';
			si.addEventListener('change', () => {
				const main = document.getElementById('naboo-sort');
				if (main) main.value = si.value;
				currentPage = 1; doSearch();
			});
		}

		// View toggle
		resultsWrap.querySelectorAll('.naboo-vbtn').forEach(btn => {
			btn.addEventListener('click', () => {
				resultsWrap.querySelectorAll('.naboo-vbtn').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				activeView = btn.dataset.v;
				const list = document.getElementById('naboo-results-list');
				if (list) list.className = 'naboo-results-list' + (activeView === 'grid' ? ' naboo-grid-view' : '');
			});
		});

		// Facet links
		resultsWrap.querySelectorAll('.naboo-facet-link').forEach(link => {
			link.addEventListener('click', e => {
				e.preventDefault();
				const type = link.dataset.facet;
				const val = link.dataset.val;
				const selMap = { category: 'naboo-fstrip-category', author: 'naboo-fstrip-author', 'test-type': 'naboo-filter-test-type', 'age-group': 'naboo-filter-age-group', language: 'naboo-filter-language' };
				const el = document.getElementById(selMap[type]);
				if (el) el.value = val;
				updateFilterBadge();
				currentPage = 1; doSearch();
			});
		});

		// Pagination
		resultsWrap.querySelectorAll('.naboo-page-btn').forEach(btn => {
			btn.addEventListener('click', () => { currentPage = parseInt(btn.dataset.page, 10); doSearch(); });
		});
	}

	/* ── Abstract Toggle Logic ───────────────────────────────── */
	if (resultsWrap) {
		resultsWrap.addEventListener('click', e => {
			const btn = e.target.closest('.naboo-action-description');
			if (!btn) return;

			e.preventDefault();
			const card = btn.closest('.naboo-result-card');
			if (!card) return;

			const abstractCont = card.querySelector('.naboo-abstract-container');
			if (!abstractCont) return;

			const id = btn.dataset.id;

			if (abstractCont.style.display === 'none') {
				abstractCont.style.display = 'block';
				btn.classList.add('active');
				btn.innerHTML = 'Description';

				if (!abstractCont.dataset.loaded) {
					abstractCont.innerHTML = '<div class="naboo-abstract-loading"><div class="naboo-spinner-sm"></div> ' + (document.documentElement.lang === 'ar' ? 'جاري التحميل...' : 'Loading abstract...') + '</div>';
					fetch(`${API}/scales/${id}/abstract`, { headers: { 'X-WP-Nonce': NONCE } })
						.then(r => r.json())
						.then(data => {
							if (data.success) {
								abstractCont.innerHTML = `<div class="naboo-abstract-content">${data.abstract}</div>`;
								abstractCont.dataset.loaded = 'true';
							} else {
								abstractCont.innerHTML = '<div class="naboo-abstract-error">Failed to load abstract.</div>';
							}
						})
						.catch(() => {
							abstractCont.innerHTML = '<div class="naboo-abstract-error">Connection error.</div>';
						});
				}
			} else {
				abstractCont.style.display = 'none';
				btn.classList.remove('active');
				btn.innerHTML = 'Description';
			}
		});
	}

	/* ── Card ────────────────────────────────────────────────── */
	function renderCard(scale, keyword, index) {
		const authors = (scale.authors || []).join('; ');
		const cats = (scale.categories || []).map(c => esc(c.name)).join('; ');
		const chips = [];
		if (scale.year) chips.push(`<span class="naboo-meta-chip naboo-meta-year">📅 ${scale.year}</span>`);
		if (scale.language) chips.push(`<span class="naboo-meta-chip">🌐 ${esc(scale.language)}</span>`);
		if (scale.items) chips.push(`<span class="naboo-meta-chip">📋 ${scale.items} items</span>`);
		if (scale.age_groups && scale.age_groups.length) chips.push(`<span class="naboo-meta-chip">👥 ${esc(scale.age_groups[0])}</span>`);

		let actions = `<button type="button" class="naboo-action-btn naboo-action-primary naboo-action-description" data-id="${scale.id}">Description</button>`;
		if (scale.has_file) actions += `<a href="${esc(scale.url)}" class="naboo-action-btn naboo-action-file">📄 Download</a>`;
		actions += `<a href="${esc(scale.url)}" class="naboo-action-btn naboo-action-view">View Scale</a>`;
		if (scale.views) actions += `<span class="naboo-action-views">👁 ${scale.views.toLocaleString()}</span>`;

		return `
<article class="naboo-result-card">
	<div class="naboo-card-content">
		<h3 class="naboo-result-title">
			<span class="naboo-card-number-inline">${index}.</span>
			<a href="${esc(scale.url)}">${highlight(scale.title, keyword)}</a>
			${scale.has_file ? '<span class="naboo-file-badge">📂</span>' : ''}
		</h3>
		${authors ? `<div class="naboo-result-authors">${esc(authors)}</div>` : ''}
		${cats ? `<div class="naboo-result-cats">${cats}</div>` : ''}
		${chips.length ? `<div class="naboo-result-meta">${chips.join('')}</div>` : ''}
		${scale.construct ? `<div class="naboo-result-construct"><span class="naboo-construct-label">Construct:</span> ${highlight(scale.construct, keyword)}</div>` : ''}
		${scale.excerpt ? `<div class="naboo-result-excerpt">${highlight(scale.excerpt, keyword)}</div>` : ''}
		<div class="naboo-abstract-container" style="display:none;"></div>
		<div class="naboo-result-actions">${actions}</div>
	</div>
</article>`;
	}

	/* ── Facet sidebar ───────────────────────────────────────── */
	function buildFacetSidebar() {
		if (!filtersData) return '<p class="naboo-facet-empty">Loading filters…</p>';
		const f = filtersData;

		let html = `
			<div class="naboo-sidebar-section">
				<div class="naboo-sidebar-header-blue">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
					Search Within Results
				</div>
				<div class="naboo-sidebar-search-box">
					<input type="text" id="naboo-search-within" placeholder="Search..." class="naboo-sw-input" />
					<button type="button" id="naboo-search-within-btn" class="naboo-sw-btn">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					</button>
				</div>
			</div>

			<div class="naboo-sidebar-section">
				<div class="naboo-sidebar-header-blue">Filter Results</div>
		`;

		[
			{ label: 'Category / Construct', data: (f.categories || []).slice(0, 8), facet: 'category' },
			{ label: 'Instrument Type', data: (f.test_types || []).slice(0, 5), facet: 'test-type' },
			{ label: 'Age Group', data: (f.age_groups || []).slice(0, 5), facet: 'age-group' },
			{ label: 'Language', data: (f.languages || []).slice(0, 5), facet: 'language' },
		].forEach((g, idx) => {
			if (!g.data.length) return;
			// Expand the first one by default
			const isExpanded = idx === 0 ? 'true' : 'false';
			const listStyle = idx === 0 ? 'display:block;' : 'display:none;';

			html += `
				<div class="naboo-facet-group naboo-accordion">
					<button type="button" class="naboo-facet-group-title" aria-expanded="${isExpanded}">
						${esc(g.label)}
						<svg class="naboo-acc-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
					</button>
					<ul class="naboo-facet-list" style="${listStyle}">
			`;

			g.data.forEach(item => {
				html += `<li><a href="#" class="naboo-facet-link" data-facet="${esc(g.facet)}" data-val="${esc(item.id || item)}">${esc(item.name || item)} <span class="naboo-facet-count">(${item.count || 0})</span></a></li>`;
			});
			html += '</ul></div>';
		});

		html += '</div>'; // close section
		return html;
	}

	// Delegated events for results area (Facets + Search Within)
	resultsWrap.addEventListener('click', e => {
		// Facet links
		if (e.target.closest('.naboo-facet-link')) {
			e.preventDefault();
			const link = e.target.closest('.naboo-facet-link');
			const facet = link.dataset.facet;
			const val = link.dataset.val;

			if (facet === 'category') {
				document.getElementById('naboo-fstrip-category').value = val;
			} else if (facet === 'author') {
				document.getElementById('naboo-fstrip-author').value = val;
			} else if (facet === 'test-type') {
				document.getElementById('naboo-filter-test-type').value = val;
			} else if (facet === 'age-group') {
				document.getElementById('naboo-filter-age-group').value = val;
			} else if (facet === 'language') {
				document.getElementById('naboo-filter-language').value = val;
			}

			currentPage = 1;
			doSearch();
			return;
		}

		// Accordion Toggles
		if (e.target.closest('.naboo-facet-group-title')) {
			const btn = e.target.closest('.naboo-facet-group-title');
			const list = btn.nextElementSibling;
			const isExpanded = btn.getAttribute('aria-expanded') === 'true';

			if (isExpanded) {
				btn.setAttribute('aria-expanded', 'false');
				list.style.display = 'none';
			} else {
				btn.setAttribute('aria-expanded', 'true');
				list.style.display = 'block';
			}
			return;
		}

		// Search Within Button
		if (e.target.closest('#naboo-search-within-btn')) {
			executeSearchWithin();
		}
	});

	// Handle Enter key in Search Within input
	resultsWrap.addEventListener('keydown', e => {
		if (e.target.id === 'naboo-search-within' && e.key === 'Enter') {
			e.preventDefault();
			executeSearchWithin();
		}
	});

	function executeSearchWithin() {
		const swInput = document.getElementById('naboo-search-within');
		if (!swInput || !swInput.value.trim()) return;

		const term = swInput.value.trim();

		// If Row 1 is empty, just use it
		const row1Term = document.getElementById('naboo-row-1-term');
		if (!row1Term.value.trim()) {
			row1Term.value = term;
		} else {
			// Otherwise append a new AND row
			addRow();
			const newRow = document.querySelector('#naboo-search-rows').lastElementChild;
			if (newRow) {
				newRow.querySelector('.naboo-row-operator').value = 'AND';
				newRow.querySelector('.naboo-row-field').value = 'any';
				newRow.querySelector('.naboo-row-term').value = term;
			}
		}

		currentPage = 1;
		doSearch();
	}

	/* ── Pagination ──────────────────────────────────────────── */
	function renderPagination(page, total) {
		const start = Math.max(1, page - 3), end = Math.min(total, page + 3);
		let html = '<nav class="naboo-pagination">';
		html += `<span class="naboo-page-info">Page ${page} of ${total}</span>`;
		if (page > 1) html += `<button class="naboo-page-btn" data-page="${page - 1}">‹</button>`;
		if (start > 1) { html += `<button class="naboo-page-btn" data-page="1">1</button>`; if (start > 2) html += `<span class="naboo-page-ellipsis">…</span>`; }
		for (let p = start; p <= end; p++) html += `<button class="naboo-page-btn${p === page ? ' active' : ''}" data-page="${p}">${p}</button>`;
		if (end < total) { if (end < total - 1) html += `<span class="naboo-page-ellipsis">…</span>`; html += `<button class="naboo-page-btn" data-page="${total}">${total}</button>`; }
		if (page < total) html += `<button class="naboo-page-btn" data-page="${page + 1}">›</button>`;
		html += '</nav>';
		return html;
	}

	/* ── URL Parameter Handling ────────────────────────────── */
	function initFromUrl() {
		const params = new URLSearchParams(window.location.search);
		const keyword = params.get('keyword');
		const catId = params.get('category');
		const authId = params.get('author');

		if (!keyword && !catId && !authId) return;

		let hasInited = false;

		// 1. Pre-fill keyword if present
		if (keyword) {
			const kInput = document.getElementById('naboo-row-1-term');
			if (kInput) {
				kInput.value = keyword;
				hasInited = true;
			}
		}

		// 2. Pre-fill taxonomy filters (must wait for filters to load)
		const applyTax = () => {
			if (catId) {
				const cSel = document.getElementById('naboo-fstrip-category');
				if (cSel) {
					cSel.value = catId;
					hasInited = true;
				}
			}
			if (authId) {
				const aSel = document.getElementById('naboo-fstrip-author');
				if (aSel) {
					aSel.value = authId;
					hasInited = true;
				}
			}
			if (hasInited) {
				updateFilterBadge();
				currentPage = 1;
				doSearch();
			}
		};

		if (filtersData) {
			applyTax();
		} else {
			const checker = setInterval(() => {
				if (filtersData) {
					clearInterval(checker);
					applyTax();
				}
			}, 100);
			setTimeout(() => clearInterval(checker), 5000); // safety
		}
	}

	initFromUrl();

	/* ── Utils ───────────────────────────────────────────────── */
	function highlight(text, kw) {
		if (!kw || !text) return esc(text || '');
		const safe = esc(text);
		const safekw = kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		return safe.replace(new RegExp('(' + safekw + ')', 'gi'), '<mark class="naboo-hl">$1</mark>');
	}
	function esc(s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

})();
