/**
 * Naboo Database - Favorites System JavaScript
 */

jQuery(document).ready(function ($) {
	'use strict';

	if (typeof apaFavorites === 'undefined') {
		return;
	}

	var Favorites = {
		apiUrl: apaFavorites.ajax_url,
		isUser: apaFavorites.is_user,
		scaleId: null,

		init: function () {
			this.cacheElements();
			this.bindEvents();
			this.checkFavoriteStatus();
		},

		cacheElements: function () {
			this.$favoriteButton = $('#add-to-favorites');
			this.$favoriteContainer = $('.naboo-favorite-button');
		},

		bindEvents: function () {
			var self = this;

			// Add to favorites button
			this.$favoriteButton.on('click', function (e) {
				e.preventDefault();

				if (!self.isUser) {
					alert('Please log in to save favorites.');
					window.location.href = '/wp-login.php';
					return;
				}

				self.toggleFavorite();
			});

			// Remove from favorites in dashboard
			$(document).on('click', '.naboo-remove-favorite', function (e) {
				e.preventDefault();
				var favoriteId = $(this).data('favorite-id');
				self.removeFavorite(favoriteId, $(this).closest('li'));
			});

			// Folder selection
			$(document).on('change', '.favorite-folder-select', function () {
				var favoriteId = $(this).data('favorite-id');
				var newFolder = $(this).val();
				self.moveFavorite(favoriteId, newFolder);
			});
		},

		checkFavoriteStatus: function () {
			var self = this;
			var scaleId = this.$favoriteContainer.data('scale-id');

			if (!scaleId || !this.isUser) {
				return;
			}

			$.ajax({
				url: this.apiUrl + '/check/' + scaleId,
				type: 'GET',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
				},
				success: function (response) {
					if (response.is_favorite) {
						self.$favoriteButton.addClass('is-favorite');
						self.$favoriteButton.find('.text').text('Remove from Favorites');
					}
				}
			});
		},

		toggleFavorite: function () {
			var self = this;
			var scaleId = this.$favoriteContainer.data('scale-id');

			if (this.$favoriteButton.hasClass('is-favorite')) {
				this.removeFavoriteByScaleId(scaleId);
			} else {
				this.addFavorite(scaleId);
			}
		},

		addFavorite: function (scaleId) {
			var self = this;

			$.ajax({
				url: this.apiUrl,
				type: 'POST',
				contentType: 'application/json',
				data: JSON.stringify({
					scale_id: scaleId,
					folder: 'default'
				}),
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
				},
				success: function (response) {
					self.$favoriteButton.addClass('is-favorite');
					self.$favoriteButton.find('.text').text('Remove from Favorites');
					self.showNotification('Added to favorites!', 'success');
				},
				error: function () {
					self.showNotification('Error adding to favorites', 'error');
				}
			});
		},

		removeFavoriteByScaleId: function (scaleId) {
			var self = this;

			$.ajax({
				url: this.apiUrl + '/check/' + scaleId,
				type: 'GET',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
				},
				success: function (response) {
					// Get the favorite ID and remove it
					$.ajax({
						url: self.apiUrl,
						type: 'GET',
						beforeSend: function (xhr) {
							xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
						},
						success: function (favorites) {
							var favorite = favorites.find(function (f) {
								return f.scale_id == scaleId;
							});

							if (favorite) {
								self.removeFavorite(favorite.id);
							}
						}
					});
				}
			});
		},

		removeFavorite: function (favoriteId, $element) {
			var self = this;

			$.ajax({
				url: this.apiUrl + '/' + favoriteId,
				type: 'DELETE',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
				},
				success: function (response) {
					if ($element) {
						$element.fadeOut(300, function () {
							$(this).remove();
						});
					} else {
						self.$favoriteButton.removeClass('is-favorite');
						self.$favoriteButton.find('.text').text('Add to Favorites');
					}
					self.showNotification('Removed from favorites', 'success');
				},
				error: function () {
					self.showNotification('Error removing favorite', 'error');
				}
			});
		},

		moveFavorite: function (favoriteId, newFolder) {
			var self = this;

			$.ajax({
				url: this.apiUrl + '/' + favoriteId,
				type: 'PUT',
				contentType: 'application/json',
				data: JSON.stringify({
					folder: newFolder
				}),
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', apaFavorites.nonce);
				},
				success: function (response) {
					self.showNotification('Moved to ' + newFolder, 'success');
				},
				error: function () {
					self.showNotification('Error moving favorite', 'error');
				}
			});
		},

		showNotification: function (message, type) {
			var className = 'naboo-favorites-notification ' + type;
			var $notification = $('<div class="' + className + '">' + message + '</div>');

			$('body').append($notification);

			setTimeout(function () {
				$notification.fadeIn(300);
			}, 100);

			setTimeout(function () {
				$notification.fadeOut(300, function () {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Initialize on page load
	Favorites.init();
});
