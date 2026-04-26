(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     */

    $(document).ready(function () {
        if (typeof naboo_ajax_obj === 'undefined') {
            return;
        }

        var searchForm = $('#naboo-search-form');
        var resultsWrapper = $('#naboo-search-results-wrapper');

        if (searchForm.length) {
            searchForm.on('submit', function (e) {
                e.preventDefault();

                var searchTerm = $('#naboo-search-query').val();
                var category = $('#naboo-search-category').val();
                var year = $('#naboo-search-year').val();
                var sort = $('#naboo-search-sort').val();

                // Add loading state
                resultsWrapper.css('opacity', '0.5');

                $.ajax({
                    url: naboo_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_search_scales',
                        nonce: naboo_ajax_obj.nonce,
                        search_term: searchTerm,
                        category: category,
                        year: year,
                        sort: sort
                    },
                    success: function (response) {
                        resultsWrapper.css('opacity', '1');
                        if (response.success) {
                            var html = response.data.html;
                            if (resultsWrapper.find('.naboo-search-results').length) {
                                resultsWrapper.find('.naboo-search-results').html(html);
                            } else {
                                resultsWrapper.html('<div class="naboo-search-results">' + html + '</div>');
                            }
                        } else {
                            resultsWrapper.html('<p>' + response.data.message + '</p>');
                        }
                    },
                    error: function () {
                        resultsWrapper.css('opacity', '1');
                        resultsWrapper.html('<p>Error retrieving results. Please try again.</p>');
                    }
                });
            });
        }

        /** --- Publish Scale Button Logic --- */
        $('#naboo-publish-scale-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');

            if (!confirm('Are you sure you want to publish this scale?')) {
                return;
            }

            $btn.prop('disabled', true).html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="naboo-spin" style="margin-right: 10px; animation: naboo-spin 1s linear infinite;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Publishing...');

            $.ajax({
                url: naboo_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_publish_scale',
                    nonce: nonce,
                    post_id: postId
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data.next_url) {
                            window.location.href = response.data.next_url;
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(response.data.message || 'Error publishing scale.');
                        $btn.prop('disabled', false).html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Publish Scale');
                    }
                },
                error: function () {
                    alert('Server error occurred.');
                    $btn.prop('disabled', false).html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Publish Scale');
                }
            });
        });
    });

})(jQuery);
