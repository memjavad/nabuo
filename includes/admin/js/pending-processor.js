(function($) {
    $(document).ready(function() {
        if ( typeof NabooPendingProcessor === 'undefined' || typeof window.nabooPendingScaleIds === 'undefined' ) {
            return;
        }

        var queue = [];
        var totalCount = 0;
        var processedCount = 0;
        var isProcessing = false;
        var currentXHR = null;

        var $startBtn = $('#naboo-start-pending-processor');
        var $stopBtn  = $('#naboo-stop-pending-processor');
        var $progressCont = $('#naboo-pending-progress-container');
        var $progressBar = $('#naboo-pending-progress-bar');
        var $progressText = $('#naboo-pending-progress-text');
        var $logWindow = $('#naboo-pending-log');
        
        if ( $logWindow.length === 0 ) {
            // Append log window if missing but script loaded (should exist per PHP)
            $('#naboo-pending-progress-container').after('<div id="naboo-pending-log" class="naboo-admin-log-window"></div>');
            $logWindow = $('#naboo-pending-log');
        }

        // Make log window a list
        $logWindow.html('<ul style="margin:0; padding:10px 20px;"></ul>');
        var $logList = $logWindow.find('ul');

        $startBtn.on('click', function() {
            if ( isProcessing ) return;

            queue = window.nabooPendingScaleIds.slice(); // Copy array
            totalCount = queue.length;

            if ( totalCount === 0 ) {
                alert('No pending scales to process.');
                return;
            }

            processedCount = 0;
            isProcessing = true;
            
            $startBtn.hide();
            $stopBtn.show();
            $progressCont.show();
            updateProgress(0);
            
            $logList.empty();
            logMessage('<li>Starting pending processor for ' + totalCount + ' scales...</li>');

            processNext();
        });

        $stopBtn.on('click', function() {
            isProcessing = false;
            if ( currentXHR ) {
                currentXHR.abort();
            }
            $stopBtn.hide();
            $startBtn.show();
            logMessage('<li style="color:var(--naboo-red);">Processing stopped by user.</li>');
        });

        function processNext() {
            if ( ! isProcessing ) return;

            if ( queue.length === 0 ) {
                finishProcessing();
                return;
            }

            var postId = queue.shift();

            currentXHR = $.ajax({
                url: NabooPendingProcessor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'naboo_process_pending_scale',
                    nonce: NabooPendingProcessor.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if ( response.success ) {
                        logMessage(response.data.message);
                    } else {
                        logMessage('<li style="color:var(--naboo-red);">Error on ID ' + postId + ': ' + (response.data.message || 'Unknown error') + '</li>');
                    }
                },
                error: function(jqXHR, textStatus) {
                    if ( textStatus !== 'abort' ) {
                        logMessage('<li style="color:var(--naboo-red);">AJAX Error on ID ' + postId + ': ' + textStatus + '</li>');
                    }
                },
                complete: function() {
                    if ( isProcessing ) {
                        processedCount++;
                        updateProgress( Math.floor((processedCount / totalCount) * 100) );
                        
                        // Process next with a small delay
                        setTimeout(processNext, 500);
                    }
                }
            });
        }

        function updateProgress( percent ) {
            $progressBar.css('width', percent + '%');
            $progressText.text(percent + '% (' + processedCount + ' / ' + totalCount + ')');
        }

        function logMessage( html ) {
            $logList.prepend(html);
        }

        function finishProcessing() {
            isProcessing = false;
            $stopBtn.hide();
            $startBtn.show();
            logMessage('<li style="color:var(--naboo-green); font-weight:bold;">Processing complete! Please refresh the page.</li>');
            
            setTimeout(function() {
                location.reload();
            }, 3000);
        }
    });
})(jQuery);
