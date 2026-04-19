(function ($) {
	'use strict';

	if (typeof apaRatings === 'undefined') {
		return;
	}

	var apaRatingApp = {
		scaleId: null,
		userRating: null,
		ratingsContainer: null,

		init: function () {
			this.cacheElements();
			this.bindEvents();
			if (apaRatings.user_logged_in) {
				this.loadUserRating();
			}
			this.loadRatings();
			this.loadRatingStats();
		},

		cacheElements: function () {
			this.ratingsContainer = jQuery('#naboo-ratings-container');
			if (this.ratingsContainer.length) {
				this.scaleId = this.ratingsContainer.data('scale-id');
			}
		},

		bindEvents: function () {
			var self = this;

			jQuery(document).on('click', '.naboo-star-input', function () {
				var rating = jQuery(this).data('rating');
				self.submitRating(rating);
			});

			jQuery(document).on('click', '.naboo-submit-review-btn', function () {
				self.submitReview();
			});

			jQuery(document).on('click', '.naboo-helpful-btn', function () {
				var ratingId = jQuery(this).data('rating-id');
				var helpful = jQuery(this).data('helpful') === true || jQuery(this).data('helpful') === 'true';
				self.markHelpful(ratingId, helpful);
			});

			jQuery(document).on('click', '.naboo-delete-review-btn', function () {
				var ratingId = jQuery(this).data('rating-id');
				self.deleteRating(ratingId);
			});
		},

		loadUserRating: function () {
			if (!this.scaleId) return;

			var self = this;
			jQuery.ajax({
				url: apaRatings.api_url + '/ratings/' + this.scaleId,
				type: 'GET',
				headers: {
					'X-WP-Nonce': apaRatings.nonce
				},
				success: function (response) {
					if (response.data && response.data.length > 0) {
						var userRatings = response.data.filter(function (r) {
							return r.user_id == apaRatings.user_id;
						});
						if (userRatings.length > 0) {
							self.userRating = userRatings[0];
						}
						self.displayUserRatingForm();
					} else if (apaRatings.user_logged_in) {
						self.displayUserRatingForm();
					}
				}
			});
		},

		loadRatings: function () {
			if (!this.scaleId) return;

			var self = this;
			jQuery.ajax({
				url: apaRatings.api_url + '/ratings/' + this.scaleId,
				type: 'GET',
				headers: {
					'X-WP-Nonce': apaRatings.nonce
				},
				success: function (response) {
					self.displayRatings(response.data);
				},
				error: function () {
					self.showNotification('Failed to load ratings', 'error');
				}
			});
		},

		loadRatingStats: function () {
			if (!this.scaleId) return;

			var self = this;
			jQuery.ajax({
				url: apaRatings.api_url + '/ratings/stats/' + this.scaleId,
				type: 'GET',
				headers: {
					'X-WP-Nonce': apaRatings.nonce
				},
				success: function (response) {
					self.displayRatingStats(response.data);
				}
			});
		},

		displayUserRatingForm: function () {
			var html = '<div class="naboo-user-rating-form">';
			html += '<h4>Your Rating</h4>';
			html += '<div class="naboo-star-rating">';
			for (var i = 1; i <= 5; i++) {
				var active = this.userRating && this.userRating.rating >= i ? 'active' : '';
				html += '<span class="naboo-star-input ' + active + '" data-rating="' + i + '">★</span>';
			}
			html += '</div>';
			html += '<textarea id="naboo-review-text" class="naboo-review-textarea" placeholder="Share your thoughts about this scale..." maxlength="1000">';
			if (this.userRating && this.userRating.review) {
				html += this.escapeHtml(this.userRating.review);
			}
			html += '</textarea>';
			html += '<button class="naboo-submit-review-btn btn-primary">Submit Review</button>';
			html += '</div>';

			if (this.ratingsContainer.find('.naboo-user-rating-form').length === 0) {
				this.ratingsContainer.prepend(html);
			}
		},

		displayRatingStats: function (stats) {
			if (!stats || stats.total_ratings === 0) {
				var html = '<div class="naboo-rating-stats"><p>No ratings yet</p></div>';
				jQuery('.naboo-rating-stats').remove();
				this.ratingsContainer.prepend(html);
				return;
			}

			var avgRating = stats.average_rating;
			var totalRatings = stats.total_ratings;
			var distribution = stats.distribution;

			var html = '<div class="naboo-rating-stats">';
			html += '<div class="naboo-rating-summary">';
			html += '<div class="naboo-rating-average">';
			html += '<span class="naboo-rating-number">' + avgRating.toFixed(1) + '</span>';
			html += '<div class="naboo-rating-stars">';
			html += this.generateStars(Math.round(avgRating));
			html += '</div>';
			html += '<span class="naboo-rating-count">(' + totalRatings + ' ratings)</span>';
			html += '</div>';
			html += '<div class="naboo-rating-distribution">';
			for (var i = 5; i >= 1; i--) {
				var count = distribution[i] || 0;
				var percentage = totalRatings > 0 ? (count / totalRatings * 100).toFixed(0) : 0;
				html += '<div class="naboo-rating-row">';
				html += '<span class="naboo-rating-label">' + i + ' ' + this.pluralize('star', i) + '</span>';
				html += '<div class="naboo-rating-bar">';
				html += '<div class="naboo-rating-fill" style="width: ' + percentage + '%"></div>';
				html += '</div>';
				html += '<span class="naboo-rating-percent">' + percentage + '%</span>';
				html += '</div>';
			}
			html += '</div>';
			html += '</div>';
			html += '</div>';

			jQuery('.naboo-rating-stats').remove();
			this.ratingsContainer.prepend(html);
		},

		displayRatings: function (ratings) {
			if (!ratings || ratings.length === 0) {
				return;
			}

			var html = '<div class="naboo-reviews-list">';
			var self = this;

			jQuery.each(ratings, function (index, rating) {
				html += '<div class="naboo-review-item">';
				html += '<div class="naboo-review-header">';
				html += '<div class="naboo-review-meta">';
				html += '<span class="naboo-review-author">User #' + rating.user_id + '</span>';
				html += '<div class="naboo-review-stars">' + self.generateStars(rating.rating) + '</div>';
				html += '<span class="naboo-review-date">' + self.formatDate(rating.created_at) + '</span>';
				html += '</div>';
				if (rating.user_id == apaRatings.user_id) {
					html += '<button class="naboo-delete-review-btn" data-rating-id="' + rating.id + '">Delete</button>';
				}
				html += '</div>';
				if (rating.review) {
					html += '<div class="naboo-review-text">' + self.escapeHtml(rating.review) + '</div>';
				}
				html += '<div class="naboo-review-footer">';
				html += '<span class="naboo-helpful-label">Was this helpful?</span>';
				html += '<button class="naboo-helpful-btn" data-rating-id="' + rating.id + '" data-helpful="true">👍 Helpful (' + rating.helpful_count + ')</button>';
				html += '<button class="naboo-helpful-btn" data-rating-id="' + rating.id + '" data-helpful="false">👎 Not Helpful (' + rating.unhelpful_count + ')</button>';
				html += '</div>';
				html += '</div>';
			});

			html += '</div>';
			jQuery('.naboo-reviews-list').remove();
			this.ratingsContainer.append(html);
		},

		submitRating: function (rating) {
			if (!apaRatings.user_logged_in) {
				this.showNotification('Please log in to rate this scale', 'warning');
				return;
			}

			var self = this;
			jQuery('.naboo-star-input').removeClass('active');
			jQuery('.naboo-star-input[data-rating="' + rating + '"]').addClass('active');
			jQuery('.naboo-star-input[data-rating="' + rating + '"]').prevAll('.naboo-star-input').addClass('active');

			this.userRating = { rating: rating, review: '' };
		},

		submitReview: function () {
			if (!apaRatings.user_logged_in) {
				this.showNotification('Please log in to submit a review', 'warning');
				return;
			}

			var rating = jQuery('.naboo-star-input.active:last').data('rating');
			var review = jQuery('#naboo-review-text').val();

			if (!rating) {
				this.showNotification('Please select a rating', 'warning');
				return;
			}

			var self = this;
			var data = {
				scale_id: this.scaleId,
				rating: rating,
				review: review
			};

			jQuery.ajax({
				url: apaRatings.api_url + '/ratings',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaRatings.nonce,
					'Content-Type': 'application/json'
				},
				data: JSON.stringify(data),
				success: function (response) {
					self.showNotification('Rating submitted for moderation', 'success');
					self.loadRatings();
					self.loadRatingStats();
				},
				error: function (xhr) {
					var message = 'Failed to submit rating';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						message = xhr.responseJSON.message;
					}
					self.showNotification(message, 'error');
				}
			});
		},

		markHelpful: function (ratingId, helpful) {
			var self = this;
			jQuery.ajax({
				url: apaRatings.api_url + '/ratings/' + ratingId + '/helpful',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaRatings.nonce,
					'Content-Type': 'application/json'
				},
				data: JSON.stringify({ helpful: helpful }),
				success: function () {
					self.loadRatings();
					self.showNotification('Thank you for your feedback', 'success');
				},
				error: function () {
					self.showNotification('Failed to record feedback', 'error');
				}
			});
		},

		deleteRating: function (ratingId) {
			if (!confirm('Are you sure you want to delete this review?')) {
				return;
			}

			var self = this;
			jQuery.ajax({
				url: apaRatings.api_url + '/ratings/' + ratingId,
				type: 'DELETE',
				headers: {
					'X-WP-Nonce': apaRatings.nonce
				},
				success: function () {
					self.showNotification('Review deleted', 'success');
					self.loadRatings();
					self.loadRatingStats();
				},
				error: function () {
					self.showNotification('Failed to delete review', 'error');
				}
			});
		},

		generateStars: function (count) {
			var html = '';
			for (var i = 1; i <= 5; i++) {
				var className = i <= count ? 'filled' : 'empty';
				html += '<span class="naboo-star ' + className + '">★</span>';
			}
			return html;
		},

		showNotification: function (message, type) {
			var className = 'naboo-ratings-notification-' + type;
			var html = '<div class="naboo-ratings-notification ' + className + '">' + message + '</div>';
			jQuery('body').append(html);

			var $notification = jQuery('.naboo-ratings-notification:last');
			setTimeout(function () {
				$notification.addClass('show');
			}, 10);

			setTimeout(function () {
				$notification.removeClass('show');
				setTimeout(function () {
					$notification.remove();
				}, 300);
			}, 3000);
		},

		escapeHtml: function (text) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function (m) { return map[m]; });
		},

		formatDate: function (dateString) {
			var date = new Date(dateString);
			var now = new Date();
			var diff = Math.floor((now - date) / 1000);

			if (diff < 60) return 'Just now';
			if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
			if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
			if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';

			return date.toLocaleDateString();
		},

		pluralize: function (word, count) {
			return count === 1 ? word : word + 's';
		}
	};

	jQuery(document).ready(function () {
		apaRatingApp.init();
	});

})(jQuery);
