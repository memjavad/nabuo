(function ($) {
    'use strict';

    $(function () {
        var $startBtn = $('#naboo-start-batch-ai');
        var $wrapper = $('#naboo-batch-progress-wrapper');
        var $log = $('#naboo-batch-log');
        var $progress = $('#naboo-batch-progress');

        var drafts = window.nabooPendingDrafts || [];
        var total = drafts.length;
        var currentIdx = 0;

        var $stopBtn = $('#naboo-stop-batch-ai');
        var $delaySelect = $('#naboo-batch-delay');
        var $randomInputs = $('#naboo-random-inputs');
        var $randomMin = $('#naboo-random-min');
        var $randomMax = $('#naboo-random-max');
        var $bgKeepWrap = $('#naboo-continuous-wrapper');
        var $bgKeepActive = $('#naboo-bg-keep-active');
        var $dailyLimit = $('#naboo-daily-limit');

        // Toggle random inputs and keep active checkbox visibility
        $delaySelect.on('change', function () {
            var val = $(this).val();
            if (val === 'random') {
                $randomInputs.css('display', 'flex');
                $bgKeepWrap.show();
            } else if (val !== '0') {
                $randomInputs.hide();
                $bgKeepWrap.show();
            } else {
                $randomInputs.hide();
                $bgKeepWrap.hide();
            }
        });

        function logMessage(message, type = 'info') {
            var color = '#333';
            if (type === 'success') color = 'green';
            if (type === 'error') color = 'red';

            $log.append('<div style="color: ' + color + '; margin-bottom: 5px;">' + message + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        }

        var isSkipping = false;
        var currentXhr = null;
        var currentTimeout = null;

        $('#naboo-skip-current-draft').on('click', function (e) {
            e.preventDefault();

            var isBackground = $stopBtn.is(':visible');
            var confirmMsg = isBackground
                ? 'This will skip the draft currently being processed in the background. Are you sure?'
                : 'Are you sure you want to skip the current draft?';

            if (!confirm(confirmMsg)) return;

            if (isBackground) {
                var $btn = $(this);
                $btn.prop('disabled', true).text('⌛ Skipping...');
                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_skip_bg_draft',
                        nonce: nabooBatchAI.nonce
                    },
                    success: function (res) {
                        if (res.success) {
                            logMessage('⏭ Background skip requested: ' + res.data, 'success');
                            updateBackgroundStatus(); // Force update
                        } else {
                            alert(res.data || 'Could not skip.');
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('⏭ Skip Current Draft');
                    }
                });
                return;
            }

            isSkipping = true;
            if (currentXhr) {
                currentXhr.abort();
            }
            if (currentTimeout) {
                clearTimeout(currentTimeout);
            }

            logMessage('⏭ Skipped by user.', '#ff9900');
            currentIdx++;
            $progress.val(currentIdx);

            // Resume loop briefly after skip
            setTimeout(processNext, 500);
        });

        function processRefinements(scaleId, fieldsToRefine, index) {
            if (isSkipping) return;

            if (index >= fieldsToRefine.length) {
                // All refinements done for this scale, move to the next draft
                logMessage('✅ Completed all refinements for Scale ID ' + scaleId, 'success');
                currentIdx++;
                $progress.val(currentIdx);
                // Slight delay before moving to the next draft
                currentTimeout = setTimeout(processNext, 2000);
                return;
            }

            var fieldName = fieldsToRefine[index];
            logMessage('  ↳ Refining field: ' + fieldName + ' (Waiting 5s...)');

            // 5 second delay as requested before hitting the API again
            currentTimeout = setTimeout(function () {
                if (isSkipping) return;

                currentXhr = $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_inline_ai_refine',
                        nonce: nabooBatchAI.search_nonce || nabooBatchAI.nonce, // Batch processor nonce handles auth
                        post_id: scaleId,
                        field_name: fieldName
                    },
                    success: function (response) {
                        if (isSkipping) return;
                        if (response.success) {
                            logMessage('    ✓ Refined: ' + fieldName, 'success');
                        } else {
                            logMessage('    ❌ Failed to refine ' + fieldName + ': ' + (response.data.message || 'Unknown error'), 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (status === 'abort') return;
                        logMessage('    ❌ Server Error refining ' + fieldName + ': ' + error, 'error');
                    },
                    complete: function () {
                        if (isSkipping) return;
                        // Move to next field
                        processRefinements(scaleId, fieldsToRefine, index + 1);
                    }
                });
            }, 5000); // 5000ms = 5 second delay
        }

        function processNext() {
            isSkipping = false;

            if (currentIdx >= total) {
                logMessage('<strong>All drafts processed!</strong>', 'success');
                $startBtn.prop('disabled', false).text('Finished');
                $('#naboo-current-processing').text('None');
                $('#naboo-next-processing').text('None');
                return;
            }

            var draftObj = drafts[currentIdx];
            var draftId = draftObj.id || draftObj;
            var draftTitle = draftObj.title || ('Draft ID: ' + draftId);

            var nextObj = drafts[currentIdx + 1];
            var nextTitle = nextObj ? (nextObj.title || ('Draft ID: ' + (nextObj.id || nextObj))) : 'None (End of Queue)';

            $('#naboo-current-processing').text(draftTitle);
            $('#naboo-next-processing').text(nextTitle);

            logMessage('<strong>Processing: ' + draftTitle + ' (' + (currentIdx + 1) + ' of ' + total + ')</strong>...');

            currentXhr = $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_process_single_draft',
                    nonce: nabooBatchAI.nonce,
                    draft_id: draftId
                },
                success: function (response) {
                    if (isSkipping) return;
                    if (response.success) {
                        logMessage('✅ Initial Extraction Complete: ' + response.data.message, 'success');

                        if (response.data.new_scale_id) {
                            var fieldsToRefine = [
                                'abstract',
                                'category',
                                'year',
                                'authors',
                                'language',
                                'test_type',
                                'format',
                                'age_group',
                                'author_details',
                                'permissions'
                            ];
                            processRefinements(response.data.new_scale_id, fieldsToRefine, 0);
                        } else {
                            currentIdx++;
                            $progress.val(currentIdx);
                            currentTimeout = setTimeout(processNext, 2000);
                        }
                    } else {
                        logMessage('❌ Failed initial extraction for ' + draftTitle + ': ' + (response.data.message || 'Unknown error'), 'error');
                        currentIdx++;
                        $progress.val(currentIdx);
                        currentTimeout = setTimeout(processNext, 2000);
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'abort') return;
                    logMessage('❌ Server Error processing ' + draftTitle + ': ' + error, 'error');
                    currentIdx++;
                    $progress.val(currentIdx);
                    currentTimeout = setTimeout(processNext, 2000);
                }
            });
        }

        $startBtn.on('click', function (e) {
            e.preventDefault();

            if (total === 0) return;

            var delayVal = $delaySelect.val(); // Can be numeric string or 'random'
            var rMin = parseInt($randomMin.val()) || 45;
            var rMax = parseInt($randomMax.val()) || 90;
            var keepActive = $bgKeepActive.is(':checked') ? 1 : 0;
            var dLimit = parseInt($dailyLimit.val()) || 0;

            if (delayVal !== '0') {
                var msg = '';
                if (delayVal === 'random') {
                    if (rMin >= rMax) {
                        alert('Minimum random minutes must be less than maximum.');
                        return;
                    }
                    msg = 'This will start processing drafts in the background via WP-Cron at a random interval between ' + rMin + ' and ' + rMax + ' minutes. You can safely close your browser. Proceed?';
                } else {
                    msg = 'This will start processing drafts in the background via WP-Cron, 1 draft every ' + (parseInt(delayVal) / 60) + ' minutes. You can safely close your browser. Proceed?';
                }

                if (!confirm(msg)) {
                    return;
                }

                $startBtn.prop('disabled', true).text('Starting Background...');

                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_toggle_background_ai',
                        nonce: nabooBatchAI.nonce,
                        delay: delayVal,
                        random_min: rMin,
                        random_max: rMax,
                        keep_active: keepActive,
                        daily_limit: dLimit
                    },
                    success: function (res) {
                        if (res.success && res.data.active) {
                            $startBtn.hide();
                            $delaySelect.prop('disabled', true);
                            $randomMin.prop('disabled', true);
                            $randomMax.prop('disabled', true);
                            $bgKeepActive.prop('disabled', true);
                            $dailyLimit.prop('disabled', true);
                            $stopBtn.show();
                            $wrapper.show();
                            logMessage('<strong>⚙️ Background processing activated!</strong>', 'success');
                            if (delayVal === 'random') {
                                logMessage('Cron is now scheduling 1 draft at random intervals (' + rMin + '-' + rMax + 'm). It is safe to close this page.');
                            } else {
                                logMessage('Cron is now scheduling 1 draft every ' + (parseInt(delayVal) / 60) + ' minutes. It is safe to close this page.');
                            }
                            if (dLimit > 0) {
                                logMessage('Daily limit set to <strong>' + dLimit + '</strong> drafts per day.');
                            }
                            if (keepActive) {
                                logMessage('<span style="color:#007cba;"> Continuous Mode: Active. The system will keep polling for new drafts when the queue is empty.</span>', 'info');
                            }
                        } else {
                            $startBtn.prop('disabled', false).text('▶ Start Batch Processing');
                            alert(res.data.message || 'Error starting background job.');
                        }
                    },
                    error: function () {
                        $startBtn.prop('disabled', false).text('▶ Start Batch Processing');
                        alert('Server error.');
                    }
                });
                return;
            }

            if (!confirm('This will sequentially send all raw drafts to the Gemini AI API to extract scales. This cannot be interrupted easily. Proceed?')) {
                return;
            }

            $startBtn.prop('disabled', true).text('Processing...');
            $wrapper.show();

            processNext();
        });

        $stopBtn.on('click', function (e) {
            e.preventDefault();

            $stopBtn.prop('disabled', true).text('Stopping...');

            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_toggle_background_ai',
                    nonce: nabooBatchAI.nonce,
                    delay: 0
                },
                success: function (res) {
                    if (res.success && !res.data.active) {
                        $stopBtn.hide().text('⏹ Stop Background');
                        $delaySelect.prop('disabled', false);
                        $randomMin.prop('disabled', false);
                        $randomMax.prop('disabled', false);
                        $bgKeepActive.prop('disabled', false);
                        $dailyLimit.prop('disabled', false);
                        $startBtn.show().prop('disabled', false).text('▶ Start Batch Processing');
                        logMessage('<strong>⏸ Background processing stopped.</strong>', '#ff9900');
                    }
                }
            });
        });

        // --- Background Status Polling ---
        var bgPollTimer = null;
        function updateBackgroundStatus() {
            if (!$stopBtn.is(':visible')) {
                if (bgPollTimer) clearInterval(bgPollTimer);
                return;
            }

            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_get_bg_status',
                    nonce: nabooBatchAI.nonce
                },
                success: function (res) {
                    if (res.success && res.data) {
                        if (res.data.is_active && res.data.limit_reached) {
                            $('#naboo-current-processing').html('<span style="color:var(--naboo-danger);">⚠️ Daily Limit Reached (' + nabooBatchAI.daily_limit + '). Increase limit or wait until tomorrow.</span>');
                        } else if (res.data.current) {
                            var title = res.data.current.title || 'Draft ID: ' + res.data.current.id;
                            if (res.data.current.refining) {
                                title += ' (Refining fields...)';
                            }
                            $('#naboo-current-processing').text(title);
                        } else {
                            $('#naboo-current-processing').text('Waiting for next scheduled run...');
                        }

                        $('#naboo-next-processing').text(res.data.next_title || 'None');

                        if (res.data.summary) {
                            $('#naboo-batch-stats').text(res.data.summary);

                            // Update the Processing Log status line for daily count
                            if (res.data.daily_limit > 0) {
                                var $logBody = $('#naboo-batch-log');
                                var progressHtml = "Today's progress: <strong>" + res.data.daily_count + " / " + res.data.daily_limit + "</strong> drafts processed.";

                                // Find and update the line in the log if possible, or append it
                                // (Actually, the log is just a text dump, better to update a dedicated status element if we had one)
                                // Let's just update the whole stats display
                            }

                            // Update progress bar
                            if (res.data.stats) {
                                var totalQ = res.data.stats.pending + res.data.stats.processing + res.data.stats.done + res.data.stats.failed;
                                var doneQ = res.data.stats.done + res.data.stats.failed;
                                $progress.attr('max', totalQ);
                                $progress.val(doneQ);
                            }
                        }

                        // If limit reached, make it extremely obvious and add a reset btn
                        if (res.data.limit_reached) {
                            if (!$('#naboo-reset-bg-limit').length) {
                                logMessage('<span style="color:red; font-weight:bold;">⚠️ Daily Limit reached! Background processing is PAUSED.</span>', 'error');
                                logMessage('<button id="naboo-reset-bg-limit" class="naboo-btn naboo-btn-secondary" style="margin-top:5px; font-size:11px;">🔄 Reset Daily Progress & Resume Now</button>');

                                $(document).on('click', '#naboo-reset-bg-limit', function (e) {
                                    e.preventDefault();
                                    var $btn = $(this);
                                    $btn.prop('disabled', true).text('Resetting...');
                                    $.post(nabooBatchAI.ajax_url, {
                                        action: 'naboo_reset_daily_progress',
                                        nonce: nabooBatchAI.nonce
                                    }, function (r) {
                                        if (r.success) {
                                            $btn.remove();
                                            logMessage('✅ Progress reset! Processing will resume shortly.', 'success');
                                            updateBackgroundStatus();
                                        }
                                    });
                                });
                            }
                        }

                        if (!res.data.is_active && res.data.stats && res.data.stats.pending === 0 && res.data.stats.processing === 0) {
                            // Background finished
                            location.reload();
                        }
                    }
                }
            });
        }


        // Initialize UI if background is active
        if (nabooBatchAI.is_bg_active) {
            $delaySelect.val(nabooBatchAI.bg_delay);
            if (nabooBatchAI.bg_delay === 'random') {
                $randomInputs.css('display', 'flex');
            }
            if (nabooBatchAI.bg_delay !== '0') {
                $bgKeepWrap.show();
            }

            $randomMin.val(nabooBatchAI.bg_rand_min);
            $randomMax.val(nabooBatchAI.bg_rand_max);
            $bgKeepActive.prop('checked', nabooBatchAI.bg_keep);
            $dailyLimit.val(nabooBatchAI.daily_limit);

            $startBtn.hide();
            $delaySelect.prop('disabled', true);
            $randomMin.prop('disabled', true);
            $randomMax.prop('disabled', true);
            $bgKeepActive.prop('disabled', true);
            $dailyLimit.prop('disabled', true);
            $stopBtn.show();
            $wrapper.show();
            logMessage('<strong>⚙️ Background processing is currently ACTIVE.</strong>', 'success');
            logMessage('Drafts are being processed automatically via WP-Cron.');
            if (nabooBatchAI.daily_limit > 0) {
                logMessage('Today\'s progress: <strong>' + nabooBatchAI.daily_count + ' / ' + nabooBatchAI.daily_limit + '</strong> drafts processed.');
            }
            if (nabooBatchAI.bg_keep) {
                logMessage('<span style="color:#007cba;"> Continuous Mode: Active. (Polling enabled)</span>', 'info');
            }

            // Start polling if background is active
            updateBackgroundStatus();
            bgPollTimer = setInterval(updateBackgroundStatus, 5000);
        }

        // Remote Connect logic
        $('#naboo-connect-remote').on('click', function (e) {
            e.preventDefault();

            var url = $('#naboo_remote_url').val();
            var token = $('#naboo_remote_token').val();
            var $status = $('#naboo-remote-status');
            var $btn = $(this);

            if (!url || !token) {
                $status.html('<span style="color:red;">Please enter both URL and Token to connect.</span>');
                return;
            }

            $btn.prop('disabled', true).text('Connecting...');
            $status.html('<span style="color:#007cba;">Connecting to origin site and fetching options...</span>');
            $('#naboo-remote-options-wrap').hide();

            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_connect_remote_drafts',
                    nonce: nabooBatchAI.nonce,
                    remote_url: url,
                    remote_token: token
                },
                success: function (response) {
                    if (response.success) {
                        $status.html('<span style="color:green;">' + response.data.message + '</span>');

                        var $typeSelect = $('#naboo_remote_post_type');
                        var $statusSelect = $('#naboo_remote_post_status');

                        $typeSelect.empty();
                        $statusSelect.empty();

                        response.data.types.forEach(function (item) {
                            $typeSelect.append($('<option>', { value: item.name, text: item.label + ' (' + item.name + ')' }));
                        });

                        response.data.statuses.forEach(function (item) {
                            $statusSelect.append($('<option>', { value: item.name, text: item.label + ' (' + item.name + ')' }));
                        });

                        // Set defaults if available
                        $typeSelect.val('post');
                        $statusSelect.val('draft');

                        $('#naboo-remote-options-wrap').slideDown();
                    } else {
                        $status.html('<span style="color:red;">' + (response.data.message || 'Error occurred.') + '</span>');
                    }
                },
                error: function (xhr, status, error) {
                    $status.html('<span style="color:red;">Server error: ' + error + '</span>');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Connect & Get Options');
                }
            });
        });

        // ---- AUTO-RESUME from saved cursor ----
        if (nabooBatchAI.last_page && nabooBatchAI.last_page > 1) {
            $('#naboo_remote_page').val(nabooBatchAI.last_page);
        }
        $('#naboo-reset-cursor').on('click', function (e) {
            e.preventDefault();
            $.post(nabooBatchAI.ajax_url, {
                action: 'naboo_save_cursor',
                nonce: nabooBatchAI.nonce,
                page: 0
            }, function () {
                $('#naboo_remote_page').val(1);
                location.reload();
            });
        });

        // Pipeline pre-fetch storage
        window.nabooNextPagePosts = null;
        window.nabooNextPagePrefetching = false;

        // Remote fetch logic
        function performFetchRemote(fetchAllPages, prefetchedPosts) {
            var url = $('#naboo_remote_url').val();
            var token = $('#naboo_remote_token').val();
            var postType = $('#naboo_remote_post_type').val();
            var postStatus = $('#naboo_remote_post_status').val();
            var pageNum = parseInt($('#naboo_remote_page').val()) || 1;
            var $status = $('#naboo-remote-status');
            var $btn = $('#naboo-fetch-remote');
            var $allBtn = $('#naboo-fetch-all-remote');
            var $progressWrap = $('#naboo-remote-progress-wrapper');
            var $log = $('#naboo-remote-log');
            var $progress = $('#naboo-remote-progress');

            if (!url || !token) {
                $status.html('<span style="color:red;">Please ensure you are connected first.</span>');
                return;
            }

            $btn.prop('disabled', true);
            $allBtn.prop('disabled', true);
            if (fetchAllPages) {
                $allBtn.text('Fetching All Pages...');
            } else {
                $btn.text('Fetching...');
            }

            $status.html('<span style="color:#007cba;">Pulling post list from origin site (Page ' + pageNum + ')...</span>');
            $progressWrap.show();

            // Clear the log on every page to prevent browser RAM from growing too large
            $log.empty();
            if (window.isFetchingAll) {
                rLogMessage('<strong>&#9193; Fetching All Pages: Starting Page ' + pageNum + '...</strong>', '#007cba');
            }

            function rLogMessage(message, color) {
                color = color || '#333';
                $log.append('<div style="color: ' + color + '; margin-bottom: 5px;">' + message + '</div>');
                $log.scrollTop($log[0].scrollHeight);
            }

            // Helper to start a pre-fetch for the NEXT page in the background
            function triggerPrefetch(nextPage) {
                if (window.nabooNextPagePrefetching || !fetchAllPages) return;
                window.nabooNextPagePrefetching = true;
                window.nabooNextPagePosts = null;
                rLogMessage('&#9889; Pre-fetching page ' + nextPage + ' in background...', '#a0a0a0');
                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_fetch_remote_list',
                        nonce: nabooBatchAI.nonce,
                        remote_url: url,
                        remote_token: token,
                        post_type: postType,
                        post_status: postStatus,
                        page: nextPage
                    },
                    success: function (res) {
                        if (res.success) {
                            window.nabooNextPagePosts = res.data.posts;
                            rLogMessage('&#9989; Pre-fetched page ' + nextPage + ': ' + res.data.posts.length + ' posts ready.', '#a0a0a0');
                        }
                    },
                    complete: function () {
                        window.nabooNextPagePrefetching = false;
                    }
                });
            }

            // Retry mechanism for fetching list
            function fetchList(retryCount) {
                // If a pre-fetched batch is already waiting, use it immediately
                if (prefetchedPosts && prefetchedPosts.length >= 0) {
                    processBatch(prefetchedPosts);
                    return;
                }
                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_fetch_remote_list',
                        nonce: nabooBatchAI.nonce,
                        remote_url: url,
                        remote_token: token,
                        post_type: postType,
                        post_status: postStatus,
                        page: pageNum
                    },
                    success: function (response) {
                        if (response.success) {
                            processBatch(response.data.posts);
                        } else {
                            $status.html('<span style="color:red;">' + (response.data.message || 'Error occurred.') + '</span>');
                            $btn.prop('disabled', false).text('Fetch Current Page');
                            $allBtn.prop('disabled', false).text('Fetch All Pages');
                            window.isFetchingAll = false;
                        }
                    },
                    error: function (xhr, status, error) {
                        if (retryCount < 5) {
                            $status.html('<span style="color:#ff9900;">Server error: ' + error + '. Retrying in 10 seconds (' + (retryCount + 1) + '/5)...</span>');
                            setTimeout(function () { fetchList(retryCount + 1); }, 10000);
                        } else {
                            $status.html('<span style="color:red;">Server error: ' + error + '</span>');
                            $btn.prop('disabled', false).text('Fetch Current Page');
                            $allBtn.prop('disabled', false).text('Fetch All Pages');
                            window.isFetchingAll = false;
                        }
                    }
                });
            }

            // processBatch: the core import loop for one page of posts
            function processBatch(posts) {
                var totalPosts = posts.length;
                $progress.attr('max', totalPosts);
                $progress.val(0);

                if (totalPosts === 0) {
                    handleEmptyBatch();
                    return;
                }

                $status.html('<span style="color:green;">Page ' + pageNum + ': ' + totalPosts + ' posts found. Importing...</span>');

                var batchState = {
                    totalPosts: totalPosts,
                    currentIdx: 0,
                    importedCount: 0,
                    preFetchTriggered: false,
                    sessionImported: window.nabooSessionImported || 0,
                    posts: posts
                };

                importNextPost(batchState, 0);
            }

            function handleEmptyBatch() {
                rLogMessage('<strong>&#127881; No more posts on page ' + pageNum + '! All done.</strong>', 'green');
                $.post(nabooBatchAI.ajax_url, { action: 'naboo_save_cursor', nonce: nabooBatchAI.nonce, page: 0 });
                $btn.prop('disabled', false).text('Fetch Current Page');
                $allBtn.prop('disabled', false).text('Fetch All Pages');
                window.isFetchingAll = false;
                setTimeout(function () { window.location.reload(); }, 2000);
            }

            function handleBatchComplete(batchState) {
                var nextPage = pageNum + 1;
                rLogMessage('<strong>&#9989; Finished page ' + pageNum + ': imported ' + batchState.importedCount + ', skipped ' + (batchState.totalPosts - batchState.importedCount) + '</strong>', 'green');

                if (fetchAllPages) {
                    $.post(nabooBatchAI.ajax_url, { action: 'naboo_save_cursor', nonce: nabooBatchAI.nonce, page: nextPage });
                    window.isFetchingAll = true;
                    $('#naboo_remote_page').val(nextPage);

                    var cached = window.nabooNextPagePosts;
                    window.nabooNextPagePosts = null;
                    if (cached !== null) {
                        rLogMessage('&#9889; Next page already pre-fetched \u2014 starting page ' + nextPage + ' instantly!', '#007cba');
                        setTimeout(function () { performFetchRemote(true, cached); }, 500);
                    } else {
                        rLogMessage('Waiting 10 seconds before page ' + nextPage + '...', '#007cba');
                        setTimeout(function () { performFetchRemote(true, null); }, 10000);
                    }
                } else {
                    $.post(nabooBatchAI.ajax_url, { action: 'naboo_save_cursor', nonce: nabooBatchAI.nonce, page: 0 });
                    $btn.prop('disabled', false).text('Fetch Current Page');
                    $allBtn.prop('disabled', false).text('Fetch All Pages');
                    window.isFetchingAll = false;
                    setTimeout(function () { window.location.reload(); }, 2000);
                }
            }

            function updatePostProgress(pRes, batchState) {
                if (pRes.data.status === 'imported') {
                    batchState.importedCount++;
                    batchState.sessionImported++;
                    window.nabooSessionImported = batchState.sessionImported;
                    rLogMessage('&#9989; ' + pRes.data.message, 'green');
                    $('#naboo-session-count').text(batchState.sessionImported);
                    if (pRes.data.log_count !== undefined) {
                        $('#naboo-log-count-live, #naboo-log-count').text(Number(pRes.data.log_count).toLocaleString());
                    }
                } else {
                    rLogMessage('&#9197; ' + pRes.data.message, '#aaa');
                }
            }

            function importNextPost(batchState, postRetryCount) {
                postRetryCount = postRetryCount || 0;

                if (batchState.currentIdx >= batchState.totalPosts) {
                    handleBatchComplete(batchState);
                    return;
                }

                if (!batchState.preFetchTriggered && fetchAllPages && batchState.currentIdx >= Math.floor(batchState.totalPosts / 2)) {
                    batchState.preFetchTriggered = true;
                    triggerPrefetch(pageNum + 1);
                }

                var post = batchState.posts[batchState.currentIdx];
                if (postRetryCount === 0) {
                    rLogMessage('[' + (batchState.currentIdx + 1) + '/' + batchState.totalPosts + '] ' + post.title);
                } else {
                    rLogMessage('Retrying (' + postRetryCount + '/5): ' + post.title, '#ff9900');
                }

                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_import_remote_single',
                        nonce: nabooBatchAI.nonce,
                        post_data: post
                    },
                    success: function (pRes) {
                        if (pRes.success) {
                            updatePostProgress(pRes, batchState);
                        } else {
                            rLogMessage('&#10060; ' + (pRes.data.message || 'Error occurred.'), 'red');
                        }
                        batchState.currentIdx++;
                        $progress.val(batchState.currentIdx);
                        setTimeout(function () { importNextPost(batchState, 0); }, 500);
                    },
                    error: function (xhr, stat, err) {
                        rLogMessage('&#10060; Server error: ' + err, 'red');
                        if (postRetryCount < 5) {
                            setTimeout(function () { importNextPost(batchState, postRetryCount + 1); }, 10000);
                        } else {
                            batchState.currentIdx++;
                            $progress.val(batchState.currentIdx);
                            setTimeout(function () { importNextPost(batchState, 0); }, 500);
                        }
                    }
                });
            }

            fetchList(0);
        }


        $('#naboo-fetch-remote').on('click', function (e) {
            e.preventDefault();
            performFetchRemote(false);
        });

        $('#naboo-fetch-all-remote').on('click', function (e) {
            e.preventDefault();
            window.isFetchingAll = true;
            performFetchRemote(true);
        });

        // Save auto-sync setting
        $('#naboo_remote_auto_sync').on('change', function () {
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var $spinner = $('#naboo-sync-spinner');

            $spinner.addClass('is-active');

            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_save_remote_settings',
                    nonce: nabooBatchAI.nonce,
                    auto_sync: isChecked
                },
                complete: function () {
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Clear Import Log handler
        $('#naboo-clear-import-log').on('click', function () {
            if (!confirm('Are you sure you want to clear the persistent import log? The importer will no longer remember which posts have already been imported and may create duplicates on the next run.')) {
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');
            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'naboo_clear_import_log',
                    nonce: nabooBatchAI.nonce
                },
                success: function (res) {
                    if (res.success) {
                        $('#naboo-log-count, #naboo-log-count-live').text('0');
                        window.nabooSessionImported = 0;
                        $('#naboo-session-count').text('0');
                        alert('Import log cleared successfully.');
                    }
                },
                error: function () {
                    alert('Failed to clear the log. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Clear Log');
                }
            });
        });
    });

    // =====================================================================
    // ⚡ AUTO-IMPORT (REST API, server-side bulk, parallel pages)
    // =====================================================================
    (function () {
        var isRunning = false;
        var stopRequested = false;
        var nextPage = (nabooBatchAI.last_page > 0) ? nabooBatchAI.last_page : 1;
        var totalImported = 0;
        var totalSkipped = 0;
        var totalPages = 0;
        var startTime = null;
        var inFlight = 0;
        var maxParallel = parseInt(nabooBatchAI.parallelism) || 2;

        var $startBtn = $('#naboo-start-auto-import');
        var $stopBtn = $('#naboo-stop-auto-import');
        var $board = $('#naboo-auto-import-board');
        var $autoLog = $('#naboo-auto-log');

        function aLog(msg, color) {
            color = color || '#333';
            $autoLog.append('<div style="color:' + color + ';margin-bottom:3px;">' + msg + '</div>');
            $autoLog.scrollTop($autoLog[0].scrollHeight);
        }

        function updateStats() {
            $('#stat-pages-done').text(totalPages);
            $('#stat-imported').text(totalImported.toLocaleString());
            $('#stat-skipped').text(totalSkipped.toLocaleString());
            if (startTime && totalPages > 0) {
                var mins = (Date.now() - startTime) / 60000;
                var rate = (totalPages / mins).toFixed(1);
                $('#stat-rate').text(rate);
            }
            // Sync all-time log count display
            var newTotal = totalImported + parseInt($('#naboo-log-count-live').text().replace(/,/g, '') || 0);
            // We get the real count from the API response, not calculated
        }

        // Fire a single REST call for one page
        function importPage(page) {
            return $.ajax({
                url: nabooBatchAI.rest_url + 'import-page',
                type: 'GET',
                data: { page: page },
                headers: { 'X-WP-Nonce': nabooBatchAI.rest_nonce },
                beforeSend: function () { inFlight++; },
                success: function (res) {
                    totalPages++;
                    totalImported += res.imported;
                    totalSkipped += res.skipped;
                    var icon = res.imported > 0 ? '&#9989;' : '&#9197;';
                    aLog(icon + ' Page ' + page + ': +' + res.imported + ' imported, ' + res.skipped + ' skipped (all-time: ' + Number(res.log_count).toLocaleString() + ')', res.imported > 0 ? 'green' : '#888');
                    if (res.log_count !== undefined) {
                        $('#naboo-log-count-live, #naboo-log-count').text(Number(res.log_count).toLocaleString());
                    }
                    updateStats();
                    return res.has_more;
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText;
                    aLog('&#10060; Page ' + page + ' error: ' + msg, 'red');
                    return false;
                },
                complete: function () { inFlight--; }
            });
        }

        // Scheduler: fires up to maxParallel concurrent page imports
        function runScheduler() {
            if (stopRequested || !isRunning) {
                if (inFlight === 0) {
                    setRunning(false);
                    aLog('<strong>&#9209; Import stopped at page ' + nextPage + '.</strong>', '#555');
                }
                return;
            }

            while (inFlight < maxParallel && isRunning && !stopRequested) {
                var pageToFetch = nextPage++;
                importPage(pageToFetch).then(function (hasMore) {
                    if (hasMore === false || hasMore === undefined) {
                        stopRequested = true;
                    }
                    // Scheduler fires again when this request completes
                    if (!stopRequested) {
                        runScheduler();
                    } else if (inFlight === 0) {
                        setRunning(false);
                        aLog('<strong>&#127881; All pages imported! Total: +' + totalImported + ' new.</strong>', 'green');
                    }
                });
            }
        }

        function setRunning(running) {
            isRunning = running;
            $startBtn.toggle(!running);
            $stopBtn.toggle(running);
            if (running) {
                $board.show();
                startTime = startTime || Date.now();
            }
        }

        $startBtn.on('click', function () {
            if (isRunning) return;
            stopRequested = false;
            maxParallel = parseInt($('#naboo_parallelism').val()) || 2;
            // Save parallelism preference
            $.post(nabooBatchAI.ajax_url, { action: 'naboo_save_remote_settings', nonce: nabooBatchAI.nonce, parallelism: maxParallel });
            aLog('<strong>&#9654; Starting Auto-Import from page ' + nextPage + ' with ' + maxParallel + ' pages in parallel...</strong>', '#007cba');
            setRunning(true);
            runScheduler();
        });

        $stopBtn.on('click', function () {
            stopRequested = true;
            aLog('Stopping after current requests complete...', '#a00');
        });

        // Toggle server-side WP-Cron auto-import
        $('#naboo-toggle-server-auto').on('click', function () {
            var $btn = $(this);
            var isEnabled = $btn.text().indexOf('Stop') !== -1;
            var enable = isEnabled ? 0 : 1;
            $btn.prop('disabled', true);
            $.ajax({
                url: nabooBatchAI.rest_url + 'toggle-auto-import',
                type: 'POST',
                headers: { 'X-WP-Nonce': nabooBatchAI.rest_nonce, 'Content-Type': 'application/json' },
                data: JSON.stringify({ enable: enable }),
                success: function (res) {
                    if (res.auto_import) {
                        $btn.text('&#9632; Stop Server Auto-Import');
                        aLog('&#9889; Server Auto-Import ENABLED. WP-Cron will now import pages in the background every 30 seconds.', 'green');
                        $board.show();
                    } else {
                        $btn.text('&#9889; Enable Server Auto-Import (no browser needed)');
                        aLog('&#9632; Server Auto-Import DISABLED.', '#555');
                    }
                },
                complete: function () { $btn.prop('disabled', false); }
            });
        });

    })(); // end auto-import IIFE

    // ──────────────────────────────────────────────────────────────
    (function () {
        var $btn = $('#naboo-import-from-file-btn');
        var $fileInput = $('#naboo-file-import-input');
        var $status = $('#naboo-file-import-status');
        var $log = $('#naboo-file-import-log');

        if (!$btn.length) return;

        $btn.on('click', function () {
            var file = $fileInput[0].files[0];
            if (!file) {
                $status.text('⚠️ Please select a .json file first.');
                return;
            }
            if (!file.name.endsWith('.json')) {
                $status.text('⚠️ Only .json files are supported.');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'naboo_import_from_file');
            formData.append('nonce', nabooBatchAI.nonce);
            formData.append('import_file', file);

            $btn.prop('disabled', true).text('Importing...');
            $status.text('Reading file and importing posts...');
            $log.hide().html('');

            $.ajax({
                url: nabooBatchAI.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $btn.prop('disabled', false).text('📥 Upload & Import');
                    if (res.success) {
                        $status.html('<strong style="color:#00a32a;">✅ ' + res.data.message + '</strong>');
                        if (res.data.log && res.data.log.length) {
                            var html = res.data.log.join('<br>');
                            $log.html(html).show();
                        }
                    } else {
                        $status.html('<span style="color:#cc0000;">❌ ' + (res.data.message || 'Error') + '</span>');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('📥 Upload & Import');
                    $status.text('❌ Server error. Please try again.');
                }
            });
        });

        // URL Direct Import functionality
        var $urlInput = $('#naboo-url-import-input');

        $btn.on('click', function (e) {
            // Overriding the previous click handler to support both file and URL
            e.stopImmediatePropagation();

            var urlVal = $.trim($urlInput.val());
            var file = $fileInput[0].files[0];

            if (!urlVal && !file) {
                $status.text('⚠️ Please provide a URL or select a file.');
                return;
            }

            if (urlVal) {
                // Handle URL Import
                $btn.prop('disabled', true).text('Downloading ZIP...');
                $status.text('Servers are talking... downloading chunk...');
                $log.hide().html('');

                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'naboo_import_from_url',
                        nonce: nabooBatchAI.nonce,
                        zip_url: urlVal
                    },
                    success: function (res) {
                        $btn.prop('disabled', false).text('📥 Upload & Import');
                        if (res.success) {
                            $status.html('<strong style="color:#00a32a;">✅ ' + res.data.message + '</strong>');
                            if (res.data.log && res.data.log.length) {
                                $log.html(res.data.log.join('<br>')).show();
                            }
                            $urlInput.val(''); // clear input on success
                        } else {
                            $status.html('<span style="color:#cc0000;">❌ ' + (res.data.message || 'Error') + '</span>');
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false).text('📥 Upload & Import');
                        $status.text('❌ Server error. Please try again.');
                    }
                });

            } else {
                // Handle File Import (Original Logic)
                if (!file.name.endsWith('.json') && !file.name.endsWith('.zip')) {
                    $status.text('⚠️ Only .json or .zip files are supported.');
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'naboo_import_from_file');
                formData.append('nonce', nabooBatchAI.nonce);
                formData.append('import_file', file);

                $btn.prop('disabled', true).text('Importing...');
                $status.text('Reading file and importing posts...');
                $log.hide().html('');

                $.ajax({
                    url: nabooBatchAI.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        $btn.prop('disabled', false).text('📥 Upload & Import');
                        if (res.success) {
                            $status.html('<strong style="color:#00a32a;">✅ ' + res.data.message + '</strong>');
                            if (res.data.log && res.data.log.length) {
                                $log.html(res.data.log.join('<br>')).show();
                            }
                            $fileInput.val('');
                        } else {
                            $status.html('<span style="color:#cc0000;">❌ ' + (res.data.message || 'Error') + '</span>');
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false).text('📥 Upload & Import');
                        $status.text('❌ Server error. Please try again.');
                    }
                });
            }
        });

    })();

})(jQuery);
