(function ($) {
	'use strict';

	var apaRelatedApp = {
		currentIndex: 0,
		itemsPerView: 3,

		init: function () {
			this.cacheElements();
			if (!this.$container.length) return;

			this.updateItemsPerView();
			this.bindEvents();
			this.updateNavButtons();
		},

		cacheElements: function () {
			this.$section = $('.naboo-related-scales-section');
			this.$container = $('.naboo-related-scales-container');
			this.$prevBtn = $('.naboo-slider-prev');
			this.$nextBtn = $('.naboo-slider-next');
			this.$items = $('.naboo-related-scale-card');
		},

		updateItemsPerView: function () {
			var width = $(window).width();
			if (width <= 640) {
				this.itemsPerView = 1;
			} else if (width <= 1024) {
				this.itemsPerView = 2;
			} else {
				this.itemsPerView = 3;
			}
		},

		bindEvents: function () {
			var self = this;

			this.$prevBtn.on('click', function () {
				self.slide('prev');
			});

			this.$nextBtn.on('click', function () {
				self.slide('next');
			});

			$(window).on('resize', function () {
				self.updateItemsPerView();
				self.goToIndex(0); // Reset to start on resize to avoid layout glitches
			});
		},

		slide: function (direction) {
			if (direction === 'next') {
				if (this.currentIndex < this.$items.length - this.itemsPerView) {
					this.currentIndex++;
				}
			} else {
				if (this.currentIndex > 0) {
					this.currentIndex--;
				}
			}
			this.updateSlider();
		},

		goToIndex: function (index) {
			this.currentIndex = index;
			this.updateSlider();
		},

		updateSlider: function () {
			var itemWidth = this.$items.first().outerWidth(true);
			var offset = -(this.currentIndex * itemWidth);

			// In RTL, we might need to adjust logic, but standard flex-direction row 
			// with LTR transform usually works if the container is correctly aligned.
			// WordPress sets dir="rtl" on <html>.
			if ($('html').attr('dir') === 'rtl') {
				offset = -offset; // Reverse for RTL if transform logic requires it
			}

			this.$container.css('transform', 'translateX(' + offset + 'px)');
			this.updateNavButtons();
		},

		updateNavButtons: function () {
			this.$prevBtn.prop('disabled', this.currentIndex === 0);
			this.$nextBtn.prop('disabled', this.currentIndex >= this.$items.length - this.itemsPerView);
		}
	};

	$(document).ready(function () {
		apaRelatedApp.init();
	});

})(jQuery);
