/**
 * Naboo Archive Real-Time Filter
 */
(function ($) {
    'use strict';

    $(function () {
        const $input = $('#naboo-archive-filter-input');
        const $results = $('#naboo-archive-results');
        const $spinner = $('#naboo-archive-spinner');

        if (!$input.length || !$results.length) return;

        let debounceTimer;
        const taxonomy = $input.data('taxonomy');
        const termId = $input.data('term-id');

        $input.on('input', function () {
            clearTimeout(debounceTimer);
            const query = $(this).val();

            debounceTimer = setTimeout(function () {
                filterArchive(query);
            }, 300);
        });

        function filterArchive(s) {
            $spinner.show();
            $results.css('opacity', '0.5');

            $.ajax({
                url: nabooArchiveFilter.ajax_url,
                type: 'GET',
                data: {
                    action: 'naboo_filter_archive',
                    s: s,
                    taxonomy: taxonomy,
                    term_id: termId
                },
                success: function (response) {
                    if (response.success) {
                        $results.html(response.data.html);
                    }
                },
                complete: function () {
                    $spinner.hide();
                    $results.css('opacity', '1');
                }
            });
        }
    });
})(jQuery);
