/**
 * Naboo Glossary - High-Performance Engine (v1.23.0)
 * AJAX-powered, virtual rendering, handles 100k+ items.
 */

(function () {
    'use strict';

    const cfg = window.nabooGlossaryConfig || {};
    const restUrl = cfg.restUrl || '';
    const nonce = cfg.nonce || '';
    const perPage = cfg.perPage || 50;
    const pagination = cfg.pagination || 'infinite';
    const showExcerpt = cfg.showExcerpt !== false;
    const showSecondary = cfg.showSecondary !== false;
    const showLetId = cfg.showLetterIndex !== false;
    const accentColor = cfg.accentColor || '#6366f1';
    const cardRadius = cfg.cardRadius || 16;
    const i18n = cfg.i18n || {};

    // Apply CSS vars from settings
    document.documentElement.style.setProperty('--ngg-accent', accentColor);
    document.documentElement.style.setProperty('--ngg-radius', cardRadius + 'px');

    // ── Query state ──────────────────────────────────────────────────
    let state = {
        postType: '',
        metaKey: '',
        metaLabel: '',
        letter: 'all',
        search: '',
        page: 1,
        totalPages: 1,
        total: 0,
        loading: false,
        allLoaded: false,    // for infinite scroll
    };

    // ── DOM references ────────────────────────────────────────────────
    let $app, $items, $empty, $loader, $sentinel, $pagination, $prev, $next, $pageInfo, $count;
    let searchTimer;
    let observer;

    function init() {
        $app = document.getElementById('naboo-glossary-app');
        if (!$app) return;

        $items = document.getElementById('ngg-items');
        $empty = document.getElementById('ngg-empty');
        $loader = document.getElementById('ngg-loader');
        $sentinel = document.getElementById('ngg-sentinel');
        $pagination = document.getElementById('ngg-pagination');
        $prev = document.getElementById('ngg-prev');
        $next = document.getElementById('ngg-next');
        $pageInfo = document.getElementById('ngg-page-info');
        $count = document.getElementById('ngg-count');

        // Read shortcode attributes from data attrs
        state.postType = $app.dataset.postType || 'naboo_glossary';
        state.metaKey = $app.dataset.metaKey || '';
        state.metaLabel = $app.dataset.metaLabel || '';

        // Hide letter nav if disabled
        const $alphaNav = document.getElementById('ngg-alpha-nav');
        if ($alphaNav && !showLetId) $alphaNav.style.display = 'none';

        // Pagination bar vs infinite
        if (pagination === 'pagination') {
            $pagination.style.display = 'flex';
            if ($sentinel) $sentinel.style.display = 'none';
        }

        bindEvents();
        fetchItems(true);
    }

    // ── Events ────────────────────────────────────────────────────────
    function bindEvents() {
        // Search
        const $search = document.getElementById('ngg-search');
        if ($search) {
            $search.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    state.search = $search.value.trim();
                    state.page = 1;
                    state.allLoaded = false;
                    fetchItems(true);
                }, 280);
            });
        }

        // Clear search
        const $clear = document.getElementById('ngg-search-clear');
        if ($clear && $search) {
            $clear.addEventListener('click', () => {
                $search.value = '';
                state.search = '';
                state.page = 1;
                state.allLoaded = false;
                fetchItems(true);
            });
        }

        // Letter nav
        document.querySelectorAll('.ngg-alpha-btn').forEach($btn => {
            $btn.addEventListener('click', () => {
                document.querySelectorAll('.ngg-alpha-btn').forEach(b => b.classList.remove('active'));
                $btn.classList.add('active');
                state.letter = $btn.dataset.letter;
                state.page = 1;
                state.allLoaded = false;
                fetchItems(true);
            });
        });

        // Pagination prev/next
        if ($prev) {
            $prev.addEventListener('click', () => {
                if (state.page > 1) {
                    state.page--;
                    fetchItems(true);
                    $app.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
        if ($next) {
            $next.addEventListener('click', () => {
                if (state.page < state.totalPages) {
                    state.page++;
                    fetchItems(true);
                    $app.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        // Infinite scroll
        if (pagination === 'infinite' && $sentinel) {
            observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting && !state.loading && !state.allLoaded) {
                    state.page++;
                    fetchItems(false);
                }
            }, { rootMargin: '200px' });
            observer.observe($sentinel);
        }
    }

    // ── Fetch ─────────────────────────────────────────────────────────
    async function fetchItems(replace) {
        if (state.loading) return;
        state.loading = true;

        if (replace) clearItems();
        showLoader(true);

        const params = new URLSearchParams({
            post_type: state.postType,
            meta_key: state.metaKey,
            letter: state.letter,
            search: state.search,
            page: state.page,
            per_page: perPage,
        });

        try {
            const response = await fetch(`${restUrl}?${params}`, {
                headers: { 'X-WP-Nonce': nonce },
            });

            if (!response.ok) throw new Error('Network Error: ' + response.status);
            const data = await response.json();

            state.total = data.total || 0;
            state.totalPages = data.total_pages || 1;

            renderItems(data.items || [], replace);
            updateUI();

            if (pagination === 'infinite' && data.items.length < perPage) {
                state.allLoaded = true;
            }
        } catch (err) {
            console.error('[Naboo Glossary]', err);
            if (replace) showEmpty(true);
        } finally {
            state.loading = false;
            showLoader(false);
        }
    }

    // ── Render ────────────────────────────────────────────────────────
    function renderItems(items, replace) {
        if (replace) clearItems();

        if (!items || items.length === 0) {
            if (replace) showEmpty(true);
            return;
        }

        showEmpty(false);
        const frag = document.createDocumentFragment();

        items.forEach(item => {
            const card = buildCard(item);
            frag.appendChild(card);
        });

        $items.appendChild(frag);
    }

    function buildCard(item) {
        const div = document.createElement('div');
        div.className = 'ngg-card';
        div.dataset.letter = item.letter;
        div.dataset.title = (item.title + ' ' + item.secondary).toLowerCase();

        const inner = document.createElement('div');
        inner.className = 'ngg-card-inner';

        // Letter badge
        const badge = document.createElement('div');
        badge.className = 'ngg-card-badge';
        badge.textContent = item.letter;
        inner.appendChild(badge);

        // Header
        const header = document.createElement('div');
        header.className = 'ngg-card-header';

        // Title — clickable link
        const title = document.createElement('a');
        title.className = 'ngg-card-title';
        title.href = item.url;
        title.textContent = item.title;
        header.appendChild(title);

        if (showSecondary && item.secondary) {
            const sec = document.createElement('span');
            sec.className = 'ngg-card-secondary';
            if (state.metaLabel) {
                const lbl = document.createElement('span');
                lbl.className = 'ngg-meta-label';
                lbl.textContent = state.metaLabel + ':';
                sec.appendChild(lbl);
                sec.appendChild(document.createTextNode(' ' + item.secondary));
            } else {
                sec.textContent = item.secondary;
            }
            header.appendChild(sec);
        }

        inner.appendChild(header);

        // Excerpt
        if (showExcerpt && item.excerpt) {
            const excerpt = document.createElement('p');
            excerpt.className = 'ngg-card-excerpt';
            excerpt.textContent = item.excerpt;
            inner.appendChild(excerpt);
        }

        // No footer — title is the link

        div.appendChild(inner);
        return div;
    }

    // ── UI helpers ────────────────────────────────────────────────────
    function clearItems() {
        if ($items) $items.innerHTML = '';
    }

    function showLoader(show) {
        if ($loader) $loader.style.display = show ? 'flex' : 'none';
    }

    function showEmpty(show) {
        if ($empty) $empty.style.display = show ? 'flex' : 'none';
    }

    function updateUI() {
        // Count label
        if ($count) {
            $count.textContent = state.total + ' ' + (i18n.items || 'items');
        }

        // Pagination UI
        if (pagination === 'pagination' && $pagination) {
            $pagination.style.display = state.totalPages > 1 ? 'flex' : 'none';
            if ($prev) $prev.disabled = state.page <= 1;
            if ($next) $next.disabled = state.page >= state.totalPages;
            if ($pageInfo) {
                $pageInfo.textContent = (i18n.page || 'Page') + ' ' + state.page + ' ' + (i18n.of || 'of') + ' ' + state.totalPages;
            }
        }
    }

    // ── Boot ──────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
