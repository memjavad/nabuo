/**
 * Scale Collections
 * Create and manage custom collections of scales
 */

(function ($) {
	'use strict';

	if (typeof apaCollections === 'undefined') {
		return;
	}

	const Collections = {
		apiUrl: apaCollections.api_url,
		nonce: apaCollections.nonce,
		isLoggedIn: apaCollections.is_logged_in,
		currentCollections: [],

		init() {
			if (this.isLoggedIn) {
				this.addCollectionButtons();
				this.loadCollections();
			}
		},

		addCollectionButtons() {
			// On scale detail page
			const $scaleContent = $('.entry-content, .post-content').first();
			if ($scaleContent.length && $('body.single-psych_scale').length) {
				const scaleId = $('body').data('post-id') || (new URLSearchParams(window.location.search)).get('scale_id');
				if (scaleId) {
					const html = `
						<div class="naboo-collection-actions">
							<button class="naboo-add-to-collection-btn" data-scale-id="${scaleId}">
								<svg viewBox="0 0 24 24" width="18" height="18">
									<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
								</svg>
								Add to Collection
							</button>
						</div>
					`;
					$scaleContent.prepend(html);
					this.setupAddToCollectionListener();
				}
			}

			// On dashboard
			if ($('.naboo-user-dashboard, [data-dashboard="true"]').length) {
				this.addCollectionDashboard();
			}
		},

		loadCollections() {
			if (!this.isLoggedIn) return;

			$.ajax({
				url: `${this.apiUrl}/collections`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.currentCollections = response.collections || [];
				},
				error: (err) => {
					console.error('Load collections error:', err);
				},
			});
		},

		addCollectionDashboard() {
			const html = `
				<div class="naboo-collections-dashboard">
					<div class="naboo-collections-header">
						<h3>
							<svg class="naboo-collections-icon" viewBox="0 0 24 24" width="22" height="22">
								<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
							</svg>
							My Collections
						</h3>
						<button class="naboo-create-collection-btn">
							<svg viewBox="0 0 24 24" width="16" height="16">
								<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"></path>
							</svg>
							New Collection
						</button>
					</div>
					<div class="naboo-collections-list"></div>
				</div>
			`;

			const $dashboard = $('.naboo-user-dashboard, [data-dashboard="true"]').first();
			if ($dashboard.length) {
				$dashboard.append(html);
				this.setupCollectionDashboard();
			}
		},

		setupCollectionDashboard() {
			$(document).on('click', '.naboo-create-collection-btn', () => {
				this.openCreateCollectionModal();
			});

			this.renderCollectionsList();
		},

		renderCollectionsList() {
			const $list = $('.naboo-collections-list');

			if (!this.currentCollections || !this.currentCollections.length) {
				$list.html('<p class="naboo-no-collections">No collections yet. Create one to get started!</p>');
				return;
			}

			const html = this.currentCollections.map((collection) => `
				<div class="naboo-collection-card" data-collection-id="${collection.id}">
					<div class="naboo-collection-header">
						<div class="naboo-collection-color" style="background-color: ${collection.color_code}"></div>
						<div class="naboo-collection-info">
							<h4>${this.escapeHtml(collection.collection_name)}</h4>
							<p>${collection.item_count} item${collection.item_count !== 1 ? 's' : ''}</p>
						</div>
					</div>
					<p class="naboo-collection-description">${this.escapeHtml(collection.description || '').substring(0, 100)}${collection.description && collection.description.length > 100 ? '...' : ''}</p>
					<div class="naboo-collection-actions">
						<button class="naboo-view-collection-btn" data-id="${collection.id}">View</button>
						<button class="naboo-edit-collection-btn" data-id="${collection.id}">Edit</button>
						<button class="naboo-delete-collection-btn" data-id="${collection.id}">Delete</button>
					</div>
				</div>
			` ).join('');

			$list.html(html);

			$(document).on('click', '.naboo-view-collection-btn', (e) => {
				const id = $(e.currentTarget).data('id');
				this.openCollectionModal(id);
			});

			$(document).on('click', '.naboo-edit-collection-btn', (e) => {
				const id = $(e.currentTarget).data('id');
				this.openEditCollectionModal(id);
			});

			$(document).on('click', '.naboo-delete-collection-btn', (e) => {
				const id = $(e.currentTarget).data('id');
				if (confirm('Delete this collection?')) {
					this.deleteCollection(id);
				}
			});
		},

		setupAddToCollectionListener() {
			$(document).on('click', '.naboo-add-to-collection-btn', (e) => {
				const scaleId = $(e.currentTarget).data('scale-id');
				this.openAddToCollectionModal(scaleId);
			});
		},

		openAddToCollectionModal(scaleId) {
			const collectionsHtml = this.currentCollections.map((col) =>
				`<option value="${col.id}">${this.escapeHtml(col.collection_name)}</option>`
			).join('');

			const modal = `
				<div class="naboo-modal-overlay">
					<div class="naboo-modal">
						<div class="naboo-modal-header">
							<h3>Add to Collection</h3>
							<button class="naboo-modal-close">&times;</button>
						</div>
						<div class="naboo-modal-body">
							<label>Select Collection:
								<select class="naboo-collection-select" data-scale-id="${scaleId}">
									<option value="">-- Choose a collection --</option>
									${collectionsHtml}
								</select>
							</label>
							<label>Note (Optional):
								<textarea class="naboo-collection-note" placeholder="Add a note about this scale..."></textarea>
							</label>
						</div>
						<div class="naboo-modal-footer">
							<button class="naboo-btn-secondary naboo-modal-cancel">Cancel</button>
							<button class="naboo-btn-primary naboo-add-to-collection">Add to Collection</button>
						</div>
					</div>
				</div>
			`;

			const $modal = $(modal);
			$('body').append($modal);

			$modal.on('click', '.naboo-modal-close, .naboo-modal-cancel, .naboo-modal-overlay', (e) => {
				if (e.target === e.currentTarget || $(e.target).hasClass('naboo-modal-close')) {
					$modal.remove();
				}
			});

			$modal.on('click', '.naboo-add-to-collection', () => {
				const collectionId = $modal.find('.naboo-collection-select').val();
				const note = $modal.find('.naboo-collection-note').val();

				if (!collectionId) {
					alert('Please select a collection');
					return;
				}

				this.addScaleToCollection(collectionId, scaleId, note);
				$modal.remove();
			});
		},

		addScaleToCollection(collectionId, scaleId, note) {
			$.ajax({
				url: `${this.apiUrl}/collections/${collectionId}/items`,
				method: 'POST',
				data: JSON.stringify({ scale_id: scaleId, note }),
				contentType: 'application/json',
				headers: { 'X-WP-Nonce': this.nonce },
				success: () => {
					this.showNotification('Scale added to collection!', 'success');
					this.loadCollections();
				},
				error: (err) => {
					this.showNotification('Error adding scale to collection', 'error');
					console.error('Add error:', err);
				},
			});
		},

		openCreateCollectionModal() {
			const modal = `
				<div class="naboo-modal-overlay">
					<div class="naboo-modal">
						<div class="naboo-modal-header">
							<h3>Create Collection</h3>
							<button class="naboo-modal-close">&times;</button>
						</div>
						<div class="naboo-modal-body">
							<label>Collection Name:
								<input type="text" class="naboo-collection-name-input" placeholder="e.g., 'Anxiety Measures'" />
							</label>
							<label>Description:
								<textarea class="naboo-collection-description-input" placeholder="Optional description..."></textarea>
							</label>
							<label>Color:
								<input type="color" class="naboo-collection-color-input" value="#00796b" />
							</label>
							<label class="naboo-public-checkbox">
								<input type="checkbox" />
								<span>Make this collection public</span>
							</label>
						</div>
						<div class="naboo-modal-footer">
							<button class="naboo-btn-secondary naboo-modal-cancel">Cancel</button>
							<button class="naboo-btn-primary naboo-modal-save">Create</button>
						</div>
					</div>
				</div>
			`;

			const $modal = $(modal);
			$('body').append($modal);

			$modal.on('click', '.naboo-modal-close, .naboo-modal-cancel, .naboo-modal-overlay', (e) => {
				if (e.target === e.currentTarget || $(e.target).hasClass('naboo-modal-close')) {
					$modal.remove();
				}
			});

			$modal.on('click', '.naboo-modal-save', () => {
				const name = $modal.find('.naboo-collection-name-input').val().trim();
				const description = $modal.find('.naboo-collection-description-input').val();
				const color = $modal.find('.naboo-collection-color-input').val();
				const isPublic = $modal.find('.naboo-public-checkbox input').is(':checked');

				if (!name) {
					alert('Please enter a collection name');
					return;
				}

				this.createCollection(name, description, color, isPublic);
				$modal.remove();
			});

			$modal.find('.naboo-collection-name-input').focus();
		},

		createCollection(name, description, color, isPublic) {
			$.ajax({
				url: `${this.apiUrl}/collections`,
				method: 'POST',
				data: JSON.stringify({ collection_name: name, description, color_code: color, is_public: isPublic }),
				contentType: 'application/json',
				headers: { 'X-WP-Nonce': this.nonce },
				success: () => {
					this.showNotification('Collection created!', 'success');
					this.loadCollections();
					setTimeout(() => {
						this.renderCollectionsList();
					}, 500);
				},
				error: (err) => {
					this.showNotification('Error creating collection', 'error');
					console.error('Create error:', err);
				},
			});
		},

		deleteCollection(id) {
			$.ajax({
				url: `${this.apiUrl}/collections/${id}`,
				method: 'DELETE',
				headers: { 'X-WP-Nonce': this.nonce },
				success: () => {
					this.showNotification('Collection deleted', 'success');
					this.loadCollections();
					setTimeout(() => {
						this.renderCollectionsList();
					}, 500);
				},
				error: (err) => {
					this.showNotification('Error deleting collection', 'error');
					console.error('Delete error:', err);
				},
			});
		},

		openCollectionModal(id) {
			$.ajax({
				url: `${this.apiUrl}/collections/${id}`,
				method: 'GET',
				headers: { 'X-WP-Nonce': this.nonce },
				success: (response) => {
					this.displayCollectionModal(response);
				},
				error: (err) => {
					this.showNotification('Error loading collection', 'error');
					console.error('Load error:', err);
				},
			});
		},

		displayCollectionModal(collection) {
			const itemsHtml = collection.items && collection.items.length ?
				collection.items.map((item) => `
					<div class="naboo-collection-item">
						<h5>${this.escapeHtml(item.post_title)}</h5>
						${item.note ? `<p class="naboo-collection-item-note">${this.escapeHtml(item.note)}</p>` : ''}
						<a href="${item.scale_url}" class="naboo-view-scale">View Scale →</a>
					</div>
				` ).join('') : '<p>No items in this collection</p>';

			const modal = `
				<div class="naboo-modal-overlay">
					<div class="naboo-modal naboo-modal-large">
						<div class="naboo-modal-header">
							<h3>${this.escapeHtml(collection.collection_name)}</h3>
							<button class="naboo-modal-close">&times;</button>
						</div>
						<div class="naboo-modal-body">
							${collection.description ? `<p>${this.escapeHtml(collection.description)}</p>` : ''}
							<div class="naboo-collection-items">
								${itemsHtml}
							</div>
						</div>
					</div>
				</div>
			`;

			const $modal = $(modal);
			$('body').append($modal);

			$modal.on('click', '.naboo-modal-close, .naboo-modal-overlay', (e) => {
				if (e.target === e.currentTarget || $(e.target).hasClass('naboo-modal-close')) {
					$modal.remove();
				}
			});
		},

		openEditCollectionModal(id) {
			const collection = this.currentCollections.find(c => c.id == id);
			if (!collection) return;

			// Similar to create modal but with existing values
			this.showNotification('Edit modal (coming soon)', 'info');
		},

		showNotification(message, type = 'success') {
			const $notification = $(`
				<div class="naboo-collections-notification naboo-collections-notification-${type}">
					<div class="naboo-collections-notification-content">
						<span>${message}</span>
					</div>
					<button class="naboo-collections-notification-close">&times;</button>
				</div>
			` );

			$('body').append($notification);

			$notification.on('click', '.naboo-collections-notification-close', () => {
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
	};

	// Initialize on document ready
	$(document).ready(() => {
		Collections.init();
	});

})(jQuery);
