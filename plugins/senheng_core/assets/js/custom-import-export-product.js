jQuery(document).ready(function($) {
    // Drag & Drop functionality
    var dropArea = $('#drop-area');
    var fileInput = dropArea.find('input[type="file"]');
    var fileInfo = $('#file-info');

    dropArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropArea.addClass('dragover');
    });

    dropArea.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropArea.removeClass('dragover');
    });

    dropArea.on('drop', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            fileInput[0].files = files;
            showFileInfo(files[0]);
        }
    });

    fileInput.on('change', function(e) {
        if (this.files && this.files[0]) {
            showFileInfo(this.files[0]);
        }
    });

    function showFileInfo(file) {
        var icon = '<span class="file-icon"><i class="fas fa-file"></i></span>';
        var info = icon + '<span>' + file.name + '</span> <span style="font-size:12px;color:#888;">(' + Math.round(file.size/1024) + ' KB)</span>';
        fileInfo.html(info).show();
    }

    // Handle form submission
    $('#custom-import-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var formData = new FormData(this);

        // Show progress area
        $('#import-progress').show();
        $('#progress-text').html(
            '<span class="spinner"></span> Uploading file and preparing import...'
        );
        $('#import-stats').show();
        $('#import-log').html('');
        
        // Disable submit button
        $submitButton.prop('disabled', true);

        // Upload file
        $.ajax({
            url: senhengImport.ajaxurl + '?action=custom_import_upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000, // 2 minutes for upload
            success: function(resp) {
                if (resp.success) {
                    // Show success message and automatically start import
                    $('#progress-text').html(
                        '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;">' +
                            '<h3 style="color: #155724; margin-top: 0;">✓ File Uploaded Successfully!</h3>' +
                            '<p style="color: #155724; margin-bottom: 15px;">Your CSV file has been uploaded. Total rows: ' + (resp.data.total_rows || 'Unknown') + '</p>' +
                            '<div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0;">' +
                                '<strong>Starting chunked import automatically...</strong><br>' +
                                '• Import process will begin in 2 seconds<br>' +
                                '• Processing 25 items per chunk (like WP All Import)<br>' +
                                '• No timeout issues<br>' +
                                '• Monitor progress in real-time below<br>' +
                            '</div>' +
                        '</div>'
                    );
                    
                    // Clear only the CSV file input, preserve checkbox settings
                    $form.find('input[name="file"]').val('');
                    fileInfo.hide();
                    
                    // Show start import section (hidden button for auto-trigger)
                    $('#start-import-section').show();
                    
                    // Show log container for real-time logs
                    $('#import-log-container').show();
                    
                    // Automatically start import after 2 seconds
                    setTimeout(function() {
                        startChunkedImport();
                    }, 2000);
                    
                } else {
                    showError(resp.data || 'Upload failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Upload failed: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                showError(errorMsg);
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });

    function showError(message) {
        $('#progress-text').html(
            '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;">' +
                '<h3 style="color: #721c24; margin-top: 0;">✗ Upload Error</h3>' +
                '<p style="color: #721c24;">' + message + '</p>' +
                '<button onclick="location.reload();" class="button button-primary">Try Again</button>' +
            '</div>'
        );
        $('#import-log').html('Error: ' + message + '\n');
    }

    var progressPollingInterval = null;
    var manualProgressPollingInterval = null;


    function startManualProgressPolling() {
        // Clear any existing interval
        if (manualProgressPollingInterval) {
            clearInterval(manualProgressPollingInterval);
        }

        // Show manual progress area
        $('#manual-progress').show();
        $('#start-monitoring').hide();
        $('#stop-monitoring').show();

        // Start polling every 1 second for more responsive updates
        manualProgressPollingInterval = setInterval(function() {
            $.ajax({
                url: senhengImport.ajaxurl + '?action=get_import_progress_logs',
                type: 'POST',
                data: {
                    nonce: senhengImport.nonce
                },
                success: function(resp) {
                    if (resp.success) {
                        updateManualProgressDisplay(resp.data);
                    }
                },
                error: function() {
                    // Silently handle errors to avoid disrupting the user experience
                }
            });
        }, 1000); // Reduced from 2000ms to 1000ms for more responsive updates
    }

    function stopManualProgressPolling() {
        if (manualProgressPollingInterval) {
            clearInterval(manualProgressPollingInterval);
            manualProgressPollingInterval = null;
        }
        $('#start-monitoring').show();
        $('#stop-monitoring').hide();
    }

    function updateProgressDisplay(data) {
        var stats = data.stats || {};
        var logs = data.logs || [];
        
        // Update statistics
        $('#stat-processed').text(stats.processed || 0);
        $('#stat-created').text(stats.created || 0);
        $('#stat-updated').text(stats.updated || 0);
        $('#stat-variations').text(stats.variations || 0);
        $('#stat-skipped').text(stats.skipped || 0);
        $('#stat-errors').text(stats.errors || 0);
        
        // Update progress bar with smooth animation (if we have total rows info)
        var totalRows = stats.total_rows || 0;
        if (totalRows > 0) {
            var percentage = Math.min((stats.processed / totalRows) * 100, 100);
            $('#progress-bar').animate({
                width: percentage + '%'
            }, 300); // Smooth animation for progress bar
            
            // Update progress text with percentage
            var progressText = 'Processing: ' + (stats.processed || 0) + ' of ' + totalRows + 
                             ' (' + Math.round(percentage) + '%)';
            $('#progress-text').text(progressText);
        } else {
            // If no total rows, just show processed count
            $('#progress-text').text('Processing: ' + (stats.processed || 0) + ' items');
        }
        
        // Update logs
        if (logs.length > 0) {
            var logText = '';
            logs.forEach(function(log) {
                var level = log.level || 'INFO';
                var timestamp = log.timestamp || '';
                var message = log.message || '';
                
                var levelClass = '';
                if (level === 'ERROR') levelClass = 'color: #dc3545;';
                else if (level === 'WARNING') levelClass = 'color: #ffc107; font-weight: bold;';
                else levelClass = 'color: #28a745;';
                
                logText += '<span style="' + levelClass + '">[' + timestamp + '] ' + level + ': ' + message + '</span>\n';
            });
            
            $('#import-log').html(logText);
            
            // Auto-scroll to bottom
            var logContainer = document.getElementById('import-log-container');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        }
        
        // Check if import is complete
        if (!data.is_running && stats.processed > 0) {
            if (progressPollingInterval) {
                clearInterval(progressPollingInterval);
                progressPollingInterval = null;
            }
            
            // Show completion message
            setTimeout(function() {
                $('#progress-text').html(
                    '<div style="padding: 20px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 20px 0;">' +
                        '<h3 style="color: #0c5460; margin-top: 0;">✓ Import Complete!</h3>' +
                        '<p style="color: #0c5460; margin-bottom: 15px;">All products have been processed successfully.</p>' +
                        '<div style="margin-top: 15px;">' +
                            '<button onclick="location.reload();" class="button button-primary">Upload Another File</button> ' +
                            '<button onclick="window.location.href=\'' + senhengImport.adminUrl + 'admin.php?page=product-import\';" class="button">View Import Page</button>' +
                        '</div>' +
                    '</div>'
                );
            }, 1000);
        }
    }

    function updateManualProgressDisplay(data) {
        var stats = data.stats || {};
        var logs = data.logs || [];
        
        // Update statistics
        $('#manual-stat-processed').text(stats.processed || 0);
        $('#manual-stat-created').text(stats.created || 0);
        $('#manual-stat-updated').text(stats.updated || 0);
        $('#manual-stat-variations').text(stats.variations || 0);
        $('#manual-stat-skipped').text(stats.skipped || 0);
        $('#manual-stat-errors').text(stats.errors || 0);
        
        // Update progress bar with smooth animation
        var totalRows = stats.total_rows || 0;
        if (totalRows > 0) {
            var percentage = Math.min((stats.processed / totalRows) * 100, 100);
            $('#manual-progress-bar').animate({
                width: percentage + '%'
            }, 300); // Smooth animation for progress bar
        }
        
        // Update progress text with more detailed information
        var statusText = 'Monitoring import progress...';
        if (stats.processed > 0) {
            var processedText = (stats.processed || 0) + ' of ' + (totalRows || 0) + ' items processed';
            var percentageText = totalRows > 0 ? ' (' + Math.round((stats.processed / totalRows) * 100) + '%)' : '';
            statusText = 'Import in progress - ' + processedText + percentageText;
        }
        $('#manual-progress-text').text(statusText);
        
        // Update logs
        if (logs.length > 0) {
            var logText = '';
            logs.forEach(function(log) {
                var level = log.level || 'INFO';
                var timestamp = log.timestamp || '';
                var message = log.message || '';
                
                var levelClass = '';
                if (level === 'ERROR') levelClass = 'color: #dc3545;';
                else if (level === 'WARNING') levelClass = 'color: #ffc107; font-weight: bold;';
                else levelClass = 'color: #28a745;';
                
                logText += '<span style="' + levelClass + '">[' + timestamp + '] ' + level + ': ' + message + '</span>\n';
            });
            
            $('#manual-import-log').html(logText);
            
            // Auto-scroll to bottom
            var logContainer = document.getElementById('manual-import-log-container');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        }
    }

    // Manual monitoring event handlers
    $('#start-monitoring').on('click', function() {
        startManualProgressPolling();
    });

    $('#stop-monitoring').on('click', function() {
        stopManualProgressPolling();
    });

    $('#clear-logs').on('click', function() {
        $('#manual-import-log').html('');
        // Also clear logs from server
        $.ajax({
            url: senhengImport.ajaxurl + '?action=clear_import_logs',
            type: 'POST',
            data: {
                nonce: senhengImport.nonce
            },
            success: function(resp) {
                if (resp.success) {
                    console.log('Logs cleared successfully');
                }
            }
        });
    });

    // Clear import lock functionality removed - no longer using lock files

    // Update statistics display using the existing view elements
    function updateStatisticsDisplay(stats) {
        $('#stat-processed').text(stats.processed || 0);
        $('#stat-created').text(stats.created || 0);
        $('#stat-updated').text(stats.updated || 0);
        $('#stat-variations').text(stats.variations || 0);
        $('#stat-skipped').text(stats.skipped || 0);
        $('#stat-errors').text(stats.errors || 0);
    }

    // Display logs in the import-log element with improved real-time handling
    function displayLogs(logs) {
        if (!logs || logs.length === 0) return;
        
        var logContainer = $('#import-log');
        var logScrollContainer = document.getElementById('import-log-container');
        
        logs.forEach(function(log) {
            var level = log.level || 'INFO';
            var timestamp = log.timestamp || '';
            var message = log.message || '';
            
            var levelClass = '';
            if (level === 'ERROR') levelClass = 'color: #dc3545; font-weight: bold;';
            else if (level === 'WARNING') levelClass = 'color: #ffc107; font-weight: bold;';
            else levelClass = 'color: #28a745;';
            
            // Create a new log line with better formatting
            var logLine = '<div style="' + levelClass + ' margin-bottom: 2px; font-family: monospace; font-size: 12px;">' +
                         '[' + timestamp + '] ' + level + ': ' + message + '</div>';
            
            // Append each log line individually for smoother updates
            logContainer.append(logLine);
        });
        
        // Auto-scroll to bottom for real-time viewing
        if (logScrollContainer) {
            logScrollContainer.scrollTop = logScrollContainer.scrollHeight;
        }
        
        // Limit log display to last 1000 lines to prevent memory issues
        var logLines = logContainer.children();
        if (logLines.length > 1000) {
            logLines.slice(0, logLines.length - 1000).remove();
        }
    }

    // Chunked Import Processing (like WP All Import)
    function startChunkedImport() {
        var $button = $('#start-import-btn');
        var $status = $('#import-status');
        var originalText = $button.html();
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting Chunked Import...');
        $status.text('Starting chunked import process...');
        
        // Show progress area, statistics, and log container
        $('#import-progress').show();
        $('#import-stats').show();
        $('#import-log-container').show();
        
        // First, trigger the start import to initialize chunked processing
        $.ajax({
            url: senhengImport.ajaxurl + '?action=start_cli_import',
            type: 'POST',
            data: {
                nonce: senhengImport.nonce
            },
            timeout: 30000,
            success: function(resp) {
                if (resp.success) {
                    console.log('Chunked import initialized:', resp.data);
                    $status.html('<span style="color: #28a745;">✓ Chunked import initialized! Starting processing...</span>');
                    
                    // Now start processing chunks
                    setTimeout(function() {
                        processNextChunk();
                    }, 1000);
                } else {
                    console.error('Failed to initialize chunked import:', resp.data);
                    $status.html('<span style="color: #dc3545;">✗ Failed to initialize: ' + (resp.data || 'Unknown error') + '</span>');
                    $button.html('<i class="fas fa-times"></i> Initialization Failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('Chunked import initialization error:', error);
                $status.html('<span style="color: #dc3545;">✗ Initialization error: ' + error + '</span>');
                $button.html('<i class="fas fa-times"></i> Initialization Error');
            }
        });
    }

    // Retry mechanism variables (like WP All Import Pro)
    var retryCount = 0;
    var maxRetries = 5;
    var baseTimeout = 120000; // 2 minutes base timeout
    var currentTimeout = baseTimeout;
    
    function processNextChunk(isRetry = false) {
        // Dynamic timeout adjustment based on retry count
        if (isRetry) {
            currentTimeout = Math.min(baseTimeout * Math.pow(2, retryCount), 300000); // Max 5 minutes
        } else {
            retryCount = 0;
            currentTimeout = baseTimeout;
        }
        
        $.ajax({
            url: senhengImport.ajaxurl + '?action=process_next_chunk',
            type: 'POST',
            data: {
                nonce: senhengImport.nonce,
                retry_count: retryCount
            },
            timeout: currentTimeout,
            success: function(resp) {
                if (resp.success) {
                    if (resp.data.status === 'completed') {
                        // Import completed
                        var finalStats = resp.data.statistics || {};
                        $('#import-status').html('<span style="color: #28a745;">✓ Import completed successfully!</span>');
                        $('#start-import-btn').html('<i class="fas fa-check"></i> Import Completed');
                        
                        // Update final statistics
                        updateStatisticsDisplay(finalStats);
                        
                        // Update progress text with final statistics
                        $('#progress-text').html(
                            '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;">' +
                                '<h3 style="color: #155724; margin-top: 0;">✓ Import Completed Successfully!</h3>' +
                                '<p style="color: #155724; margin-bottom: 15px;">All chunks have been processed successfully.</p>' +
                                '<div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0;">' +
                                    '<strong>Final Import Summary:</strong><br>' +
                                    '• Total Processed: ' + (finalStats.processed || 0) + ' items<br>' +
                                    '• Products Created: ' + (finalStats.created || 0) + '<br>' +
                                    '• Products Updated: ' + (finalStats.updated || 0) + '<br>' +
                                    '• Variations Created: ' + (finalStats.variations || 0) + '<br>' +
                                    '• Items Skipped: ' + (finalStats.skipped || 0) + '<br>' +
                                    '• Errors: ' + (finalStats.errors || 0) + '<br>' +
                                    '• All chunks processed successfully<br>' +
                                '</div>' +
                                    '<div style="margin-top: 15px;">' +
                                    '<button onclick="location.reload();" class="button button-primary">Upload Another File</button> ' +
                                    '<button onclick="window.location.href=\'/wp-admin/edit.php?post_type=product\';" class="button">View Products</button>' +
                                '</div>' +
                            '</div>'
                        );
                        
                        // IMPORTANT: Stop any further processing attempts
                        return; // Exit the function to prevent further chunk processing
                        
                    } else {
                        // Continue processing next chunk
                        var progressPercent = resp.data.progress_percent || 0;
                        var currentChunk = resp.data.current_chunk || 0;
                        var totalChunks = resp.data.total_chunks || 0;
                        var totalRows = resp.data.total_rows || 0;
                        var stats = resp.data.statistics || {};
                        var logs = resp.data.logs || [];
                        
                        // Use the message from the server instead of constructing our own
                        var serverMessage = resp.data.message || '';
                        var processedRows = stats.processed || 0;
                        
                        // Debug logging to track progress display issue
                        console.log('Chunk ' + currentChunk + ': Server message:', serverMessage);
                        console.log('Chunk ' + currentChunk + ': Received processed count:', processedRows, 'of', totalRows);
                        
                        $('#import-status').html(
                            '<span style="color: #0969da;">' + serverMessage + '</span>'
                        );
                        
                        // Update progress bar
                        $('#progress-bar').css('width', progressPercent + '%');
                        
                        // Update statistics display
                        updateStatisticsDisplay(stats);
                        
                        // Display logs from the chunk processing
                        if (logs.length > 0) {
                            displayLogs(logs);
                        }
                        
                        // Process next chunk after a shorter delay for more responsive updates
                        setTimeout(function() {
                            processNextChunk(false); // Reset retry count on successful chunk
                        }, 500); // Reduced from 1000ms to 500ms for faster updates
                    }
                } else {
                    console.error('Chunk processing failed:', resp.data);
                    
                    // Implement retry logic for failed chunks
                    if (retryCount < maxRetries) {
                        retryCount++;
                        var retryDelay = Math.min(1000 * Math.pow(2, retryCount), 30000); // Exponential backoff, max 30s
                        
                        $('#import-status').html(
                            '<span style="color: #ffc107;">⚠ Chunk failed, retrying in ' + (retryDelay/1000) + 's... (Attempt ' + 
                            (retryCount + 1) + '/' + (maxRetries + 1) + ')</span>'
                        );
                        
                        setTimeout(function() {
                            processNextChunk(true);
                        }, retryDelay);
                    } else {
                        $('#import-status').html('<span style="color: #dc3545;">✗ Chunk processing failed after ' + maxRetries + ' retries: ' + (resp.data || 'Unknown error') + '</span>');
                        $('#start-import-btn').html('<i class="fas fa-times"></i> Import Failed');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Chunk processing error:', error, 'Status:', status, 'Retry count:', retryCount);
                
                // Enhanced error handling with retry mechanism
                var errorMessage = error;
                var shouldRetry = false;
                
                if (status === 'timeout') {
                    errorMessage = 'Request timeout (may indicate server overload or large chunk processing)';
                    shouldRetry = true;
                } else if (status === 'error' && xhr.status === 0) {
                    errorMessage = 'Network connection lost';
                    shouldRetry = true;
                } else if (status === 'error' && xhr.status >= 500) {
                    errorMessage = 'Server error (HTTP ' + xhr.status + ')';
                    shouldRetry = true;
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                // Implement retry logic for recoverable errors
                if (shouldRetry && retryCount < maxRetries) {
                    retryCount++;
                    var retryDelay = Math.min(2000 * Math.pow(2, retryCount), 60000); // Longer delays for errors, max 1 minute
                    
                    $('#import-status').html(
                        '<span style="color: #ffc107;">⚠ ' + errorMessage + ' - Retrying in ' + (retryDelay/1000) + 's... (Attempt ' + 
                        (retryCount + 1) + '/' + (maxRetries + 1) + ')</span>'
                    );
                    
                    setTimeout(function() {
                        processNextChunk(true);
                    }, retryDelay);
                    return;
                }
                
                // Final failure after all retries
                $('#import-status').html('<span style="color: #dc3545;">✗ Error processing chunk after ' + maxRetries + ' retries: ' + errorMessage + '</span>');
                $('#start-import-btn').html('<i class="fas fa-times"></i> Import Error');
                
                // For timeout errors, check if import might have actually completed
                 if (status === 'timeout') {
                     $('#import-status').html('<span style="color: #ffc107;">⚠ Checking import status...</span>');
                     
                     setTimeout(function() {
                         // Try to get final status
                         $.ajax({
                             url: senhengImport.ajaxurl + '?action=get_import_progress_logs',
                             type: 'POST',
                             data: { nonce: senhengImport.nonce },
                             success: function(resp) {
                                 if (resp.success && resp.data.stats) {
                                     var stats = resp.data.stats;
                                     if (stats.processed > 0) {
                                         $('#import-status').html('<span style="color: #28a745;">✓ Import completed successfully (despite timeout)</span>');
                                         $('#start-import-btn').html('<i class="fas fa-check"></i> Import Completed');
                                         updateStatisticsDisplay(stats);
                                         
                                         // Show completion message
                                         $('#progress-text').html(
                                             '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;">' +
                                                 '<h3 style="color: #155724; margin-top: 0;">✓ Import Completed Successfully!</h3>' +
                                                 '<p style="color: #155724; margin-bottom: 15px;">Import finished despite timeout error.</p>' +
                                                 '<div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0;">' +
                                                     '<strong>Final Import Summary:</strong><br>' +
                                                     '• Total Processed: ' + (stats.processed || 0) + ' items<br>' +
                                                     '• Products Created: ' + (stats.created || 0) + '<br>' +
                                                     '• Products Updated: ' + (stats.updated || 0) + '<br>' +
                                                     '• Variations Created: ' + (stats.variations || 0) + '<br>' +
                                                     '• Items Skipped: ' + (stats.skipped || 0) + '<br>' +
                                                     '• Errors: ' + (stats.errors || 0) + '<br>' +
                                                 '</div>' +
                                                 '<div style="margin-top: 15px;">' +
                                                     '<button onclick="location.reload();" class="button button-primary">Upload Another File</button> ' +
                                                     '<button onclick="window.location.href=\'/wp-admin/edit.php?post_type=product\';" class="button">View Products</button>' +
                                                 '</div>' +
                                             '</div>'
                                         );
                                     } else {
                                         $('#import-status').html('<span style="color: #dc3545;">✗ Import failed - no items processed</span>');
                                     }
                                 } else {
                                     $('#import-status').html('<span style="color: #dc3545;">✗ Could not verify import status</span>');
                                 }
                             },
                             error: function() {
                                 $('#import-status').html('<span style="color: #dc3545;">✗ Could not check import status</span>');
                             }
                         });
                     }, 2000);
                 }
            }
        });
    }

    // Start Import Button Handler
    $('#start-import-btn').on('click', function() {
        var $button = $(this);
        var $status = $('#import-status');
        var originalText = $button.html();
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting Import...');
        $status.text('Starting import process...');
        
        
        // Show progress area, statistics, and log container
        $('#import-progress').show();
        $('#import-stats').show();
        $('#import-log-container').show();
        
        // Trigger import
        $.ajax({
            url: senhengImport.ajaxurl + '?action=start_cli_import',
            type: 'POST',
            data: {
                nonce: senhengImport.nonce
            },
            timeout: 30000, // 30 seconds timeout
            success: function(resp) {
                if (resp.success) {
                    console.log('Import started:', resp.data);
                    $status.html('<span style="color: #28a745;">✓ Import started successfully! Running in background...</span>');
                    $button.html('<i class="fas fa-check"></i> Import Started');
                    
                    // Update progress text with execution method
                    var method = resp.data.method || 'async_execution';
                    var methodText = method === 'async_execution' ? 'Asynchronous Execution' : 'Direct Execution';
                    var methodIcon = method === 'async_execution' ? '🚀' : '⚡';
                    
                    $('#progress-text').html(
                        '<div style="padding: 20px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 20px 0;">' +
                            '<h3 style="color: #0c5460; margin-top: 0;">' + methodIcon + ' Heavy Import Started via ' + methodText + '!</h3>' +
                            '<p style="color: #0c5460; margin-bottom: 15px;">Your heavy import is now running in the background with optimized performance.</p>' +
                            '<div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0;">' +
                                '<strong>Processing heavy dataset...</strong><br>' +
                                '• Optimized batch processing (500 items/batch)<br>' +
                                '• Asynchronous execution for maximum performance<br>' +
                                '• Products are being imported/updated in real-time<br>' +
                                '• Watch the progress below<br>' +
                                '• You can keep this page open to monitor progress<br>' +
                                '• Compatible with Plesk and other hosting environments<br>' +
                            '</div>' +
                        '</div>'
                    );
                } else {
                    console.error('Import start failed:', resp.data);
                    $status.html('<span style="color: #dc3545;">✗ Failed to start import: ' + (resp.data || 'Unknown error') + '</span>');
                    $button.html('<i class="fas fa-times"></i> Start Failed');
                    setTimeout(function() {
                        $button.html(originalText).prop('disabled', false);
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Import start error:', error);
                $status.html('<span style="color: #dc3545;">✗ Error starting import: ' + error + '</span>');
                $button.html('<i class="fas fa-times"></i> Start Error');
                setTimeout(function() {
                    $button.html(originalText).prop('disabled', false);
                }, 3000);
            }
        });
    });

    // Test Cron Import Button Handler
    $('#test-cron-import').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing Cron Import...');
        
        // Start monitoring if not already running
        if (!manualProgressPollingInterval) {
            startManualProgressPolling();
        }
        
        // Trigger cron import
        $.ajax({
            url: senhengImport.ajaxurl + '?action=senheng_cron_import&secret=' + encodeURIComponent(senhengImport.cronSecret),
            type: 'GET',
            timeout: 30000, // 30 seconds timeout
            success: function(resp) {
                if (resp.success) {
                    console.log('Cron import test completed:', resp.data);
                    $button.html('<i class="fas fa-check"></i> Test Completed');
                    setTimeout(function() {
                        $button.html(originalText).prop('disabled', false);
                    }, 3000);
                } else {
                    console.error('Cron import test failed:', resp.data);
                    $button.html('<i class="fas fa-times"></i> Test Failed');
                    setTimeout(function() {
                        $button.html(originalText).prop('disabled', false);
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Cron import test error:', error);
                $button.html('<i class="fas fa-times"></i> Test Error');
                setTimeout(function() {
                    $button.html(originalText).prop('disabled', false);
                }, 3000);
            }
        });
    });
});