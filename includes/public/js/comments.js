(function ($) {
	'use strict';

	if (typeof apaComments === 'undefined') {
		return;
	}

	var apaCommentsApp = {
		scaleId: null,
		commentsContainer: null,
		currentReplyParent: null,

		init: function () {
			this.cacheElements();
			this.bindEvents();
			if (apaComments.user_logged_in) {
				this.loadComments();
				this.bindCommentFormEvents();
			}
		},

		cacheElements: function () {
			this.commentsContainer = jQuery('#naboo-comments-container');
			if (this.commentsContainer.length) {
				this.scaleId = this.commentsContainer.data('scale-id');
			}
		},

		bindEvents: function () {
			var self = this;

			jQuery(document).on('click', '.naboo-comment-submit-btn', function () {
				self.submitComment();
			});

			jQuery(document).on('click', '.naboo-reply-btn', function () {
				var commentId = jQuery(this).data('comment-id');
				self.showReplyForm(commentId);
			});

			jQuery(document).on('click', '.naboo-reply-submit', function () {
				var commentId = jQuery(this).data('parent-id');
				self.submitReply(commentId);
			});

			jQuery(document).on('click', '.naboo-reply-cancel', function () {
				jQuery(this).closest('.naboo-reply-form').remove();
				self.currentReplyParent = null;
			});

			jQuery(document).on('click', '.naboo-comment-helpful-btn', function () {
				var commentId = jQuery(this).data('comment-id');
				var helpful = jQuery(this).data('helpful') === true || jQuery(this).data('helpful') === 'true';
				self.markHelpful(commentId, helpful);
			});

			jQuery(document).on('click', '.naboo-comment-action-btn.edit', function () {
				var commentId = jQuery(this).data('comment-id');
				self.showEditForm(commentId);
			});

			jQuery(document).on('click', '.naboo-comment-action-btn.delete', function () {
				var commentId = jQuery(this).data('comment-id');
				self.deleteComment(commentId);
			});
		},

		bindCommentFormEvents: function () {
			jQuery('#naboo-comment-text').on('keyup', function () {
				var count = jQuery(this).val().length;
				jQuery('#naboo-char-count').text(count);
			});
		},

		loadComments: function () {
			if (!this.scaleId) return;

			var self = this;
			jQuery.ajax({
				url: apaComments.api_url + '/comments/' + this.scaleId,
				type: 'GET',
				headers: {
					'X-WP-Nonce': apaComments.nonce
				},
				success: function (response) {
					if (response.data && response.data.length > 0) {
						self.displayComments(response.data);
					} else {
						self.showEmptyState();
					}
				},
				error: function () {
					self.showNotification('Failed to load comments', 'error');
				}
			});
		},

		displayComments: function (comments) {
			var html = '';
			var self = this;

			jQuery.each(comments, function (index, comment) {
				html += self.renderCommentItem(comment, false);

				if (comment.replies && comment.replies.length > 0) {
					jQuery.each(comment.replies, function (i, reply) {
						html += self.renderCommentItem(reply, true);
					});
				}
			});

			this.commentsContainer.html(html);
		},

		renderCommentItem: function (comment, isReply) {
			var className = isReply ? 'naboo-comment-item reply' : 'naboo-comment-item';
			var canEdit = apaComments.user_id == comment.user_id;
			var avatar = comment.user_name.charAt(0).toUpperCase();

			var html = '<div class="' + className + '" data-comment-id="' + comment.id + '">';
			html += '<div class="naboo-comment-header">';
			html += '<div class="naboo-comment-meta">';
			html += '<div class="naboo-comment-author"><span class="naboo-comment-avatar">' + avatar + '</span>' + this.escapeHtml(comment.user_name) + '</div>';
			html += '<span class="naboo-comment-date">' + this.formatDate(comment.created_at) + '</span>';
			html += '</div>';

			if (canEdit) {
				html += '<div class="naboo-comment-actions">';
				html += '<button class="naboo-comment-action-btn edit" data-comment-id="' + comment.id + '">Edit</button>';
				html += '<button class="naboo-comment-action-btn delete" data-comment-id="' + comment.id + '">Delete</button>';
				html += '</div>';
			}

			html += '</div>';
			html += '<div class="naboo-comment-text">' + this.escapeHtml(comment.comment_text) + '</div>';
			html += '<div class="naboo-comment-footer">';
			html += '<div class="naboo-comment-helpful">';
			html += '<span style="margin-right: 8px; font-weight: 500;">Was this helpful?</span>';
			html += '<button class="naboo-comment-helpful-btn" data-comment-id="' + comment.id + '" data-helpful="true">👍 ' + comment.helpful_count + '</button>';
			html += '<button class="naboo-comment-helpful-btn" data-comment-id="' + comment.id + '" data-helpful="false">👎 ' + comment.unhelpful_count + '</button>';
			html += '</div>';

			if (!isReply && apaComments.user_logged_in) {
				html += '<button class="naboo-reply-btn" data-comment-id="' + comment.id + '">Reply</button>';
			}

			html += '</div>';
			html += '</div>';

			return html;
		},

		submitComment: function () {
			if (!apaComments.user_logged_in) {
				this.showNotification('Please log in to comment', 'warning');
				return;
			}

			var commentText = jQuery('#naboo-comment-text').val().trim();

			if (!commentText) {
				this.showNotification('Please enter a comment', 'warning');
				return;
			}

			var self = this;
			var data = {
				scale_id: this.scaleId,
				comment: commentText
			};

			jQuery.ajax({
				url: apaComments.api_url + '/comments',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaComments.nonce,
					'Content-Type': 'application/json'
				},
				data: JSON.stringify(data),
				success: function (response) {
					self.showNotification('Comment submitted for moderation', 'success');
					jQuery('#naboo-comment-text').val('');
					jQuery('#naboo-char-count').text('0');
					self.loadComments();
				},
				error: function (xhr) {
					var message = 'Failed to submit comment';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						message = xhr.responseJSON.message;
					}
					self.showNotification(message, 'error');
				}
			});
		},

		showReplyForm: function (commentId) {
			if (this.currentReplyParent) {
				jQuery('[data-parent-id="' + this.currentReplyParent + '"]').closest('.naboo-reply-form').remove();
			}

			var $comment = jQuery('[data-comment-id="' + commentId + '"]');
			var html = '<div class="naboo-reply-form">';
			html += '<textarea class="naboo-reply-textarea" placeholder="Write a reply..." maxlength="2000"></textarea>';
			html += '<div class="naboo-reply-form-actions">';
			html += '<button type="button" class="naboo-reply-submit" data-parent-id="' + commentId + '">Reply</button>';
			html += '<button type="button" class="naboo-reply-cancel">Cancel</button>';
			html += '</div>';
			html += '</div>';

			$comment.append(html);
			$comment.find('.naboo-reply-textarea').focus();
			this.currentReplyParent = commentId;
		},

		submitReply: function (parentId) {
			var $replyForm = jQuery('[data-parent-id="' + parentId + '"]').closest('.naboo-reply-form');
			var replyText = $replyForm.find('.naboo-reply-textarea').val().trim();

			if (!replyText) {
				this.showNotification('Please enter a reply', 'warning');
				return;
			}

			var self = this;
			var data = {
				scale_id: this.scaleId,
				parent_id: parentId,
				comment: replyText
			};

			jQuery.ajax({
				url: apaComments.api_url + '/comments',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaComments.nonce,
					'Content-Type': 'application/json'
				},
				data: JSON.stringify(data),
				success: function (response) {
					self.showNotification('Reply submitted for moderation', 'success');
					$replyForm.remove();
					self.currentReplyParent = null;
					self.loadComments();
				},
				error: function (xhr) {
					var message = 'Failed to submit reply';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						message = xhr.responseJSON.message;
					}
					self.showNotification(message, 'error');
				}
			});
		},

		markHelpful: function (commentId, helpful) {
			var self = this;
			jQuery.ajax({
				url: apaComments.api_url + '/comments/' + commentId + '/helpful',
				type: 'POST',
				headers: {
					'X-WP-Nonce': apaComments.nonce,
					'Content-Type': 'application/json'
				},
				data: JSON.stringify({ helpful: helpful }),
				success: function () {
					self.loadComments();
				},
				error: function () {
					self.showNotification('Failed to record feedback', 'error');
				}
			});
		},

		deleteComment: function (commentId) {
			if (!confirm('Are you sure you want to delete this comment?')) {
				return;
			}

			var self = this;
			jQuery.ajax({
				url: apaComments.api_url + '/comments/' + commentId,
				type: 'DELETE',
				headers: {
					'X-WP-Nonce': apaComments.nonce
				},
				success: function () {
					self.showNotification('Comment deleted', 'success');
					self.loadComments();
				},
				error: function () {
					self.showNotification('Failed to delete comment', 'error');
				}
			});
		},

		showEditForm: function (commentId) {
			// For future implementation with inline editing
			this.showNotification('Edit functionality coming soon', 'warning');
		},

		showEmptyState: function () {
			var html = '<div class="naboo-comments-empty"><p>💬 No comments yet. Be the first to share your thoughts!</p></div>';
			this.commentsContainer.html(html);
		},

		showNotification: function (message, type) {
			var className = 'naboo-comments-notification-' + type;
			var html = '<div class="naboo-comments-notification ' + className + '">' + message + '</div>';
			jQuery('body').append(html);

			var $notification = jQuery('.naboo-comments-notification:last');
			setTimeout(function () {
				$notification.addClass('show');
			}, 10);

			setTimeout(function () {
				$notification.removeClass('show');
				setTimeout(function () {
					$notification.remove();
				}, 300);
			}, 4000);
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
		}
	};

	jQuery(document).ready(function () {
		apaCommentsApp.init();
	});

})(jQuery);
