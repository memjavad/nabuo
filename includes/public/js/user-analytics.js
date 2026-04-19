/**
 * User Analytics Dashboard JavaScript
 */

(function ($) {
    'use strict';

    if (typeof apaAnalytics === 'undefined') {
        return;
    }

    const UserAnalytics = {
        apiUrl: apaAnalytics.ajaxUrl,
        nonce: apaAnalytics.nonce,
        chartInstance: null,

        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Listen for standard dashboard tab clicks to mount our module dynamically
            $(document).on('click', '.naboo-dashboard-nav a[href="#analytics"]', (e) => {
                e.preventDefault();

                // Manage tab active states (this may duplicate existing dashboard JS, but ensures isolated tab switching)
                $('.naboo-dashboard-nav a').removeClass('active');
                $(e.currentTarget).addClass('active');

                $('.naboo-dashboard-section').hide();
                $('#naboo-dashboard-analytics').fadeIn(300);

                this.loadDashboardData();
            });
        },

        loadDashboardData() {
            const $spinner = $('.naboo-analytics-spinner');
            const $wrapper = $('.naboo-analytics-data-wrapper');

            // Only load once
            if ($wrapper.data('loaded')) {
                return;
            }

            $spinner.show();
            $wrapper.hide();

            // Fetch parallel stats/dashboard profile
            Promise.all([
                $.ajax({
                    url: `${this.apiUrl}analytics/user-dashboard`,
                    method: 'GET',
                    headers: { 'X-WP-Nonce': this.nonce }
                }),
                $.ajax({
                    url: `${this.apiUrl}analytics/user-activity`,
                    method: 'GET',
                    headers: { 'X-WP-Nonce': this.nonce }
                })
            ]).then(([dashboardRes, activityRes]) => {
                $spinner.hide();
                this.populateDashboard(dashboardRes, activityRes);
                $wrapper.fadeIn(300).data('loaded', true);
            }).catch((err) => {
                $spinner.hide();
                $wrapper.html('<p class="naboo-error-text">Failed to load analytics data.</p>').show();
                console.error('Analytics load error:', err);
            });
        },

        populateDashboard(dashboard, activity) {
            // Populate KPIs
            if (dashboard.stats) {
                const stats = dashboard.stats;
                this.animateValue('#kpi-downloads', parseInt(stats.total_downloads) || 0);
                this.animateValue('#kpi-views', parseInt(stats.total_views) || 0);
                this.animateValue('#kpi-submissions', parseInt(stats.total_submissions) || 0);
                this.animateValue('#kpi-favorites', parseInt(stats.total_favorites) || 0);
            }

            // Render Charts
            this.renderActivityChart(activity.activities);
            this.renderCategoriesList(dashboard.favorite_categories);
            this.renderRecentSearches(dashboard.recent_searches);
        },

        animateValue(selector, endValue) {
            const $el = $(selector);
            $({ val: 0 }).animate({ val: endValue }, {
                duration: 1000,
                easing: 'swing',
                step: function () {
                    $el.text(Math.floor(this.val));
                },
                complete: function () {
                    $el.text(endValue);
                }
            });
        },

        renderActivityChart(activities) {
            const ctx = document.getElementById('naboo-activity-chart');
            if (!ctx) return;

            if (this.chartInstance) {
                this.chartInstance.destroy();
            }

            const dataValues = [
                parseInt(activities.favorites) || 0,
                parseInt(activities.ratings) || 0,
                parseInt(activities.comments) || 0,
                parseInt(activities.submissions) || 0
            ];

            // Check if entirely empty
            if (dataValues.every(val => val === 0)) {
                $(ctx).replaceWith('<div class="naboo-empty-state">No activity tracked in the last 30 days.</div>');
                return;
            }

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Favorites', 'Ratings', 'Comments', 'Submissions'],
                    datasets: [{
                        label: 'Past 30 Days',
                        data: dataValues,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        },

        renderCategoriesList(categories) {
            const $container = $('.naboo-analytics-cats-list');
            $container.empty();

            if (!categories || categories.length === 0) {
                $container.html('<div class="naboo-empty-state">No favorites saved yet.</div>');
                return;
            }

            categories.forEach(cat => {
                $container.append(`
					<div class="naboo-cat-item">
						<span class="naboo-cat-name">${cat.name}</span>
						<span class="naboo-cat-count">${cat.count}</span>
					</div>
				`);
            });
        },

        renderRecentSearches(searches) {
            const $container = $('.naboo-analytics-search-list');
            $container.empty();

            if (!searches || searches.length === 0) {
                $container.html('<div class="naboo-empty-state">No recent searches found.</div>');
                return;
            }

            searches.forEach(search => {
                // Avoid returning empty searches
                if (search.search_query.trim() === '') return;

                // Make the tag clickable to actually perform the search
                const searchUrl = `/?s=${encodeURIComponent(search.search_query)}&post_type=psych_scale`;

                $container.append(`
					<a href="${searchUrl}" class="naboo-search-tag">
						<span>${search.search_query}</span>
						<span class="naboo-search-tag-count">${search.count}x</span>
					</a>
				`);
            });
        }
    };

    $(document).ready(() => {
        UserAnalytics.init();
    });

})(jQuery);
