<?php
require_once dirname(__DIR__) . '/Services/Import/Logger.php';
require_once dirname(__DIR__) . '/Services/Import/ProgressStore.php';
require_once dirname(__DIR__) . '/Services/Import/AttributeHelper.php';
require_once dirname(__DIR__) . '/Services/Import/CategoryHelper.php';
require_once dirname(__DIR__) . '/Services/Import/MediaHelper.php';
require_once dirname(__DIR__) . '/Services/Import/BrandHelper.php';
require_once dirname(__DIR__) . '/Services/Import/ProductImportService.php';

use SenhengCore\App\Services\Import\ProductImportService;
use SenhengCore\App\Services\Import\ProgressStore;
use SenhengCore\App\Services\Import\Logger;

class ProductImportController
{
    /**
     * Set data source for cron import
     * 
     * @param string $filePath  Path to CSV file
     */
    public static function setDataSource(string $filePath): void
    {
        update_option('sh_import_data_source', $filePath, false);
    }

    /**
     * Reset import progress
     */
    public static function resetProgress(): void
    {
        ProgressStore::reset();
        delete_option('sh_import_data_source');
        delete_option('sh_active_import_id');
        delete_option('sh_import_flags');
    }

    /**
     * Get import statistics
     * 
     * @return array
     */
    public static function getStats(): array
    {
        return ProgressStore::getStats();
    }

        


    

    /**
     * Enqueue admin assets
     */
    public static function enqueueAssets($hook)
    {
        wp_enqueue_style(
            'custom-import-export-product-style',
            SENHENG_CORE_URL . 'assets/css/custom-import-export-product.css'
        );
        wp_enqueue_script(
            'custom-import-export-product-script',
            SENHENG_CORE_URL . 'assets/js/custom-import-export-product.js',
            ['jquery'],
            null,
            true
        );
        wp_localize_script('custom-import-export-product-script', 'senhengImport', [
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce'    => wp_create_nonce('senheng_import_nonce'),
        ]);
    }

    /**
     * Handle file upload and prepare for CLI import
     * This follows the hybrid approach: upload file, then trigger CLI execution
     */
    public static function handleUpload()
    {
        error_log('[ProductImport] handleUpload() method called');
        
        ob_start();
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'senheng_import_nonce')) {
                error_log('[ProductImport] Nonce verification failed');
                ob_end_clean();
                wp_send_json_error('Invalid nonce');
                exit;
            }

            if (!current_user_can('manage_woocommerce')) {
                error_log('[ProductImport] Permission check failed');
                ob_end_clean();
                wp_send_json_error('Permission denied');
                exit;
            }

            if (empty($_FILES['import_csv']['tmp_name'])) {
                error_log('[ProductImport] No file uploaded');
                ob_end_clean();
                wp_send_json_error('No file uploaded');
                exit;
            }

            // Upload file
            $tmp_name = $_FILES['import_csv']['tmp_name'];
            $upload_dir = wp_upload_dir();
            $dest_path = $upload_dir['basedir'] . '/import_' . uniqid() . '.csv';
            
            // Debug: Check original uploaded file for HTML content
            $originalContent = file_get_contents($tmp_name);
            $htmlInOriginal = strpos($originalContent, '<') !== false || strpos($originalContent, '&lt;') !== false;
            error_log('[ProductImport] Original uploaded file contains HTML: ' . ($htmlInOriginal ? 'YES' : 'NO'));
            if ($htmlInOriginal) {
                error_log('[ProductImport] Original file HTML sample: ' . substr($originalContent, strpos($originalContent, '<'), 200));
            }
            
            if (!move_uploaded_file($tmp_name, $dest_path)) {
                error_log('[ProductImport] File upload failed');
                ob_end_clean();
                wp_send_json_error('Failed to save uploaded file');
                exit;
            }
            
            // Debug: Check moved file for HTML content
            $movedContent = file_get_contents($dest_path);
            $htmlInMoved = strpos($movedContent, '<') !== false || strpos($movedContent, '&lt;') !== false;
            error_log('[ProductImport] Moved file contains HTML: ' . ($htmlInMoved ? 'YES' : 'NO'));
            if ($htmlInMoved) {
                error_log('[ProductImport] Moved file HTML sample: ' . substr($movedContent, strpos($movedContent, '<'), 200));
            }

            $import_id = uniqid('import_');
            set_transient($import_id . '_file', $dest_path, 60 * 60);
            update_option('sh_active_import_id', $import_id);
            
            // Set as active data source for CLI
            self::setDataSource($dest_path);

            // Reset progress for new import
            ProgressStore::reset();
            ProgressStore::save(); // Ensure the reset state is saved immediately

            // Count total data rows (excluding header) without altering content
            $total_rows = 0;
            $lines = explode("\n", $movedContent);
            $headerSeen = false;
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l === '') { continue; }
                if (!$headerSeen) { $headerSeen = true; continue; }
                $total_rows++;
            }

            // Initialize progress tracking
            set_transient($import_id . '_progress', [
                'processed'      => 0,
                'success_count'  => 0,
                'error_count'    => 0,
                'total_rows'     => $total_rows,
                'done'           => false,
                'log'            => "File uploaded successfully. Total rows: $total_rows\n",
                'status'         => 'ready', // ready, running, completed, error
            ], 60 * 60);

            $flags = [
                'update_descriptions' => isset($_POST['update_descriptions']) && $_POST['update_descriptions'] === '1',
                'update_parent_from_variations' => isset($_POST['update_parent_from_variations']) && $_POST['update_parent_from_variations'] === '1',
                'cleanup_unused_attributes' => isset($_POST['cleanup_unused_attributes']) && $_POST['cleanup_unused_attributes'] === '1',
                'use_enhanced_parent_finding' => isset($_POST['use_enhanced_parent_finding']) && $_POST['use_enhanced_parent_finding'] === '1',
                'import_new_only' => isset($_POST['import_new_only']) && $_POST['import_new_only'] === '1',
                'partial_update_existing' => isset($_POST['partial_update_existing']) && $_POST['partial_update_existing'] === '1',
            ];
            update_option('sh_import_flags', $flags, false);

                ob_end_clean();
            wp_send_json_success([
                'import_id' => $import_id,
                'message' => 'File uploaded successfully. Ready to start import.',
                'total_rows' => $total_rows
            ]);

        } catch (Exception $e) {
            error_log('[ProductImport] Error in handleUpload: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Start import via PHP script execution (AJAX trigger)
     * This triggers the PHP script to run via Plesk Scheduled Task
     */
    public static function startCliImport()
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'senheng_import_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }

        // Check if we have an active import file
        $dataSource = get_option('sh_import_data_source', '');
        if (empty($dataSource) || !file_exists($dataSource)) {
            wp_send_json_error('No import file available. Please upload a CSV file first.');
            return;
        }

        // Update status to running
        $import_id = get_option('sh_active_import_id');
        if ($import_id) {
            $progress = get_transient($import_id . '_progress');
            if ($progress) {
                $progress['status'] = 'running';
                $progress['log'] .= "\n=== Starting Import via PHP Script ===\n";
                $progress['log'] .= "Triggering PHP script execution...\n";
        set_transient($import_id . '_progress', $progress, 60 * 60);
            }
        }

        // For heavy imports, use chunked processing (like WP All Import)
        // This processes data in small chunks to avoid timeout issues
        $executionMethod = self::startChunkedImport();
        $message = 'Heavy import started with chunked processing - ' . $executionMethod;

        wp_send_json_success([
            'message' => $message,
            'status' => 'running',
            'method' => 'direct_execution'
        ]);
    }

    /**
     * Start chunked import processing (like WP All Import)
     * This processes data in small chunks to avoid timeout issues
     */
    private static function startChunkedImport(): string
    {
        // Initialize chunked processing
        $import_id = get_option('sh_active_import_id');
        if (!$import_id) {
            return 'No active import found';
        }

        // Set up chunked processing state
        $chunkState = [
            'current_chunk' => 0,
            'chunk_size' => 25, // Small chunks like WP All Import
            'total_chunks' => 0,
            'status' => 'initializing',
            'started_at' => time()
        ];

        // Calculate total chunks based on data rows in the CSV
        $dataSource = get_option('sh_import_data_source', '');
        if (!empty($dataSource) && file_exists($dataSource)) {
            $raw = file_get_contents($dataSource);
            $lines = explode("\n", $raw);
            $headerSeen = false;
            $rowCount = 0;
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l === '') { continue; }
                if (!$headerSeen) { $headerSeen = true; continue; }
                $rowCount++;
            }
            $chunkSize = max(1, (int)$chunkState['chunk_size']);
            $chunkState['total_chunks'] = (int) ceil($rowCount / $chunkSize);
        }

        // Store chunk state
        $transientSet = set_transient($import_id . '_chunk_state', $chunkState, 60 * 60);
        
        // Debug: Log chunk state creation
        error_log("Chunk state created for import $import_id: " . json_encode($chunkState));
        error_log("Transient set result: " . ($transientSet ? 'true' : 'false'));

        // Update progress
        $progress = get_transient($import_id . '_progress');
        if ($progress) {
            $progress['status'] = 'chunked_processing';
            $progress['log'] .= "\n=== Chunked Processing Started ===\n";
            $progress['log'] .= "Total chunks: {$chunkState['total_chunks']}\n";
            $progress['log'] .= "Chunk size: {$chunkState['chunk_size']} items\n";
            set_transient($import_id . '_progress', $progress, 60 * 60);
        }

        return 'Chunked Processing (25 items per chunk)';
    }

    /**
     * Process next chunk of import data
     */
    public static function processNextChunk()
    {
        // Increase execution time and memory limits for large chunks (like WP All Import Pro)        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'senheng_import_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $import_id = get_option('sh_active_import_id');
        if (!$import_id) {
            wp_send_json_error('No active import found');
            return;
        }

        // CRITICAL: Load progress state at the very beginning of the request
        // This ensures we start with the correct cumulative totals
        ProgressStore::get();
        
        // Get retry count for timeout adjustment
        $retry_count = (int)($_POST['retry_count'] ?? 0);
        
        // Adjust chunk size based on retry count to prevent repeated timeouts
        $base_chunk_size = 25;
        $adjusted_chunk_size = max(5, $base_chunk_size - ($retry_count * 5)); // Reduce chunk size on retries

        // Get chunk state with error recovery
        $chunkState = get_transient($import_id . '_chunk_state');
        if (!$chunkState) {
            // Try to recover from lost chunk state (like WP All Import Pro)
            $progress = get_transient($import_id . '_progress');
            if ($progress && isset($progress['processed'])) {
                // Reconstruct chunk state from progress data
                $dataSource = get_option('sh_import_data_source', '');
                if (!empty($dataSource) && file_exists($dataSource)) {
                    $raw = file_get_contents($dataSource);
                    $lines = explode("\n", $raw);
                    $headerSeen = false;
                    $rowCount = 0;
                    foreach ($lines as $l) {
                        $l = trim($l);
                        if ($l === '') { continue; }
                        if (!$headerSeen) { $headerSeen = true; continue; }
                        $rowCount++;
                    }
                    
                    $chunkSize = $adjusted_chunk_size;
                    $totalChunks = (int) ceil($rowCount / $chunkSize);
                    $currentChunk = (int) ceil($progress['processed'] / $chunkSize);
                    
                    // Recreate chunk state
                    $chunkState = [
                        'current_chunk' => $currentChunk,
                        'chunk_size' => $chunkSize,
                        'total_chunks' => $totalChunks,
                        'status' => 'recovered',
                        'started_at' => time(),
                        'retry_count' => $retry_count
                    ];
                    
                    set_transient($import_id . '_chunk_state', $chunkState, 60 * 60);
                    
                    // Log recovery
                    if ($progress) {
                        $progress['log'] .= "\n=== Chunk State Recovered ===\n";
                        $progress['log'] .= "Resumed from chunk {$currentChunk}/{$totalChunks}\n";
                        $progress['log'] .= "Processed items: {$progress['processed']}\n";
                        set_transient($import_id . '_progress', $progress, 60 * 60);
                    }
                }
            }
            
            // If still no chunk state, return error with debug info
            if (!$chunkState) {
                $debugInfo = [
                    'import_id' => $import_id,
                    'transient_key' => $import_id . '_chunk_state',
                    'transient_exists' => get_transient($import_id . '_chunk_state') !== false,
                    'progress_exists' => get_transient($import_id . '_progress') !== false,
                    'retry_count' => $retry_count
                ];
                wp_send_json_error('No chunk state found and recovery failed. Debug: ' . json_encode($debugInfo));
                return;
            }
        }

        // If total_chunks is zero, attempt to recompute to avoid premature completion
        $dataSource = get_option('sh_import_data_source', '');
        if ((int)$chunkState['total_chunks'] === 0 && !empty($dataSource) && file_exists($dataSource)) {
            $raw = file_get_contents($dataSource);
            $lines = explode("\n", $raw);
            $headerSeen = false;
            $rowCount = 0;
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l === '') { continue; }
                if (!$headerSeen) { $headerSeen = true; continue; }
                $rowCount++;
            }
            $chunkSize = max(1, (int)$chunkState['chunk_size']);
            $chunkState['total_chunks'] = (int) ceil($rowCount / $chunkSize);
            set_transient($import_id . '_chunk_state', $chunkState, 60 * 60);
        }

        // Check if we're done - fix for small datasets
        if ($chunkState['total_chunks'] > 0 && $chunkState['current_chunk'] >= $chunkState['total_chunks']) {
            // Get final statistics (state already loaded at start)
            $stats = ProgressStore::getStats();
            $finalStats = [
                'processed' => $stats['processed'],
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'variations' => $stats['variations'],
                'skipped' => $stats['skipped'],
                'errors' => $stats['errors']
            ];
            
            // Clean up chunk state to prevent further processing attempts
            delete_transient($import_id . '_chunk_state');
            
            wp_send_json_success([
                'status' => 'completed',
                'message' => 'All chunks processed successfully',
                'statistics' => $finalStats
            ]);
            return;
        }
        
        // For very small datasets (less than chunk size), ensure we don't create empty chunks
        if ($chunkState['total_chunks'] === 0) {
            wp_send_json_success([
                'status' => 'completed', 
                'message' => 'No data to process',
                'statistics' => [
                    'processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'variations' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ]
            ]);
            return;
        }

        // Process current chunk with adjusted size
        $result = self::processChunk($chunkState['current_chunk'], $adjusted_chunk_size);

        // Update chunk state
        $chunkState['current_chunk']++;
        $chunkState['status'] = 'processing';
        $chunkState['chunk_size'] = $adjusted_chunk_size; // Update chunk size in state
        $chunkState['retry_count'] = $retry_count; // Track retry count
        set_transient($import_id . '_chunk_state', $chunkState, 60 * 60);

        // Update progress with real statistics
        $progress = get_transient($import_id . '_progress');
        if ($progress) {
            // Get current statistics from ProgressStore (state already loaded at start)
            $stats = ProgressStore::getStats();
            
            // Use actual processed count from ProgressStore instead of chunk calculation
            $progress['processed'] = $stats['processed'];
            $progress['log'] .= "Processed chunk {$chunkState['current_chunk']}/{$chunkState['total_chunks']}\n";
            
            // Get current statistics from ProgressStore
            $progress['total_created'] = $stats['created'];
            $progress['total_updated'] = $stats['updated'];
            $progress['total_variations'] = $stats['variations'];
            $progress['total_skipped'] = $stats['skipped'];
            $progress['total_errors'] = $stats['errors'];
            
            set_transient($import_id . '_progress', $progress, 60 * 60);
        }
        
        // Get final statistics for frontend (state already loaded, no need to reload)
        $finalStats = ProgressStore::getStats();
        
        // Calculate progress percentage based on actual processed rows vs total rows
        $totalRows = 0;
        if ($progress && isset($progress['total_rows'])) {
            $totalRows = $progress['total_rows'];
        }
        
        $actualProgressPercent = 0;
        if ($totalRows > 0) {
            $actualProgressPercent = round(($finalStats['processed'] / $totalRows) * 100, 2);
        }
        
        // Debug logging to track progress display issue
        error_log('[ProductImportController] Sending progress to frontend: ' . json_encode([
            'chunk' => $chunkState['current_chunk'] . '/' . $chunkState['total_chunks'],
            'processed' => $finalStats['processed'],
            'created' => $finalStats['created'],
            'updated' => $finalStats['updated'],
            'variations' => $finalStats['variations'],
            'skipped' => $finalStats['skipped'],
            'errors' => $finalStats['errors'],
            'progress_percent' => $actualProgressPercent
        ]));
        error_log("[ProductImportController] Chunk {$chunkState['current_chunk']}: Sending processed count: {$finalStats['processed']} of {$totalRows}");
        
        wp_send_json_success([
            'status' => 'processing',
            'current_chunk' => $chunkState['current_chunk'],
            'total_chunks' => $chunkState['total_chunks'],
            'progress_percent' => $actualProgressPercent,
            'total_rows' => $totalRows,
            'message' => "Processed {$finalStats['processed']} of {$totalRows} rows (Chunk {$chunkState['current_chunk']}/{$chunkState['total_chunks']})",
            'statistics' => [
                'processed' => $finalStats['processed'] ?? 0,
                'created' => $finalStats['created'] ?? 0,
                'updated' => $finalStats['updated'] ?? 0,
                'variations' => $finalStats['variations'] ?? 0,
                'skipped' => $finalStats['skipped'] ?? 0,
                'errors' => $finalStats['errors'] ?? 0,
                'total_rows' => $totalRows
            ],
            'logs' => $result['logs'] ?? []
        ]);
    }

    /**
     * Process a single chunk of data
     */
    private static function processChunk(int $chunkIndex, int $chunkSize): array
    {
        $dataSource = get_option('sh_import_data_source', '');
        if (!file_exists($dataSource)) {
            return ['error' => 'Data source not found'];
        }

        // Heartbeat mechanism to prevent server timeout (like WP All Import Pro)
        $startTime = time();
        $heartbeatInterval = 30; // Send heartbeat every 30 seconds
        $lastHeartbeat = $startTime;

        // Read CSV and process chunk - DIRECT extraction to preserve HTML content
        $rawContent = file_get_contents($dataSource);
        
        // Debug: Log CSV file info
        error_log("CSV File: " . $dataSource);
        error_log("CSV File size: " . filesize($dataSource) . " bytes");
        
        // Debug: Check raw CSV content for HTML
        $htmlInRaw = strpos($rawContent, '<') !== false || strpos($rawContent, '&lt;') !== false;
        error_log("Raw CSV contains HTML: " . ($htmlInRaw ? 'YES' : 'NO'));
        if ($htmlInRaw) {
            error_log("Raw CSV HTML sample: " . substr($rawContent, strpos($rawContent, '<'), 200));
        }
        
        // Extract data directly using regex - completely bypass CSV parsing
        $chunkData = self::extractChunkDataDirectly($rawContent, $chunkIndex, $chunkSize);

        // Process the chunk data directly
        if (!empty($chunkData)) {
            $flags = get_option('sh_import_flags', []);
            $updateDescriptions = !empty($flags['update_descriptions']);
            $updateParentFromVariations = !empty($flags['update_parent_from_variations']);
            $cleanupUnusedAttributes = !empty($flags['cleanup_unused_attributes']);
            $useEnhancedParentFinding = !empty($flags['use_enhanced_parent_finding']);
            $importNewOnly = !empty($flags['import_new_only']);
            $partialUpdateExisting = !empty($flags['partial_update_existing']);
            
            // Create a service instance for processing
            $service = new ProductImportService([
                'batch_size' => count($chunkData),
                'log_file' => WP_CONTENT_DIR . '/uploads/senheng_import.log',
                'data_source' => $dataSource,
                'update_descriptions' => $updateDescriptions,
                'update_parent_from_variations' => $updateParentFromVariations,
                'cleanup_unused_attributes' => $cleanupUnusedAttributes,
                'use_enhanced_parent_finding' => $useEnhancedParentFinding,
                'import_new_only' => $importNewOnly,
                'partial_update_existing' => $partialUpdateExisting,
            ]);
            
            // Process each row in the chunk directly with heartbeat
        foreach ($chunkData as $index => $row) {
            try {
                // Send heartbeat every 30 seconds to prevent timeout
                $currentTime = time();
                if ($currentTime - $lastHeartbeat >= $heartbeatInterval) {
                    // Send a small response to keep connection alive
                    if (!headers_sent()) {
                        header('X-Heartbeat: ' . $currentTime);
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                    $lastHeartbeat = $currentTime;
                }
                
                // This is where the actual import happens - logs will be generated here
                $service->upsertFromFeedRow($row);
                ProgressStore::tick($row);
                
                // Save progress after each item for real-time updates
                ProgressStore::save();
                
            } catch (\Throwable $e) {
                ProgressStore::inc('total_errors');
                ProgressStore::addLog('ERROR', "Error processing row: " . $e->getMessage());
                Logger::error($service->logFile, "Error processing row: " . $e->getMessage());
                
                // Save progress after errors too
                ProgressStore::save();
            }
        }
            
            // After processing the chunk, get all logs and return them
        $latestLogs = ProgressStore::getLogs();
        return [
            'processed' => count($chunkData),
            'logs' => $latestLogs, // Return all log entries for complete visibility
            'processing_time' => time() - $startTime
        ];
        }

        return ['processed' => count($chunkData), 'processing_time' => time() - $startTime];
    }

    /**
     * Extract chunk data directly from raw CSV content using regex
     * This completely bypasses CSV parsing to preserve HTML content
     * Optimized for memory efficiency with large datasets (like WP All Import Pro)
     * 
     * @param string $rawContent
     * @param int $chunkIndex
     * @param int $chunkSize
     * @return array
     */
    private static function extractChunkDataDirectly(string $rawContent, int $chunkIndex, int $chunkSize): array
    {
        // Memory optimization: Use SplFileObject for large files instead of loading all into memory
        $dataSource = get_option('sh_import_data_source', '');
        if (filesize($dataSource) > 50 * 1024 * 1024) { // Files larger than 50MB
            return self::extractChunkDataFromFile($dataSource, $chunkIndex, $chunkSize);
        }
        
        $lines = explode("\n", $rawContent);
        $header = null;
        $currentRow = 0;
        $chunkData = [];
        $startRow = $chunkIndex * $chunkSize;
        $endRow = ($chunkIndex + 1) * $chunkSize;
        
        // Memory optimization: Skip lines before our chunk to reduce processing
        $lineIndex = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $lineIndex++;
                continue;
            }
            
            if ($header === null) {
                // Extract header using regex to preserve content
                $header = self::extractFieldsFromLine($line);
                $lineIndex++;
                continue;
            }
            
            // Skip rows before our chunk (performance optimization)
            if ($currentRow < $startRow) {
                $currentRow++;
                $lineIndex++;
                continue;
            }
            
            // Stop processing if we've gone past our chunk
            if ($currentRow >= $endRow) {
                break;
            }
            
            // Extract fields directly from line using regex
            $fields = self::extractFieldsFromLine($line);
            
            // Ensure the line has the same number of columns as header
            $paddedFields = array_pad($fields, count($header), '');
            $truncatedFields = array_slice($paddedFields, 0, count($header));
            
            $rowData = array_combine($header, $truncatedFields);
            
            // Debug: Log column mapping for first few rows only in first chunk
            if ($chunkIndex === 0 && $currentRow < 3) {
                error_log("Row $currentRow - Header count: " . count($header) . ", Fields count: " . count($fields));
                error_log("Row $currentRow - Headers: " . json_encode($header));
                error_log("Row $currentRow - Fields: " . json_encode($fields));
                error_log("Row $currentRow - pc_detail: '" . ($rowData['pc_detail'] ?? 'NULL') . "'");
                error_log("Row $currentRow - image_json: '" . ($rowData['image_json'] ?? 'NULL') . "'");
                
                // Check if HTML was preserved during direct extraction
                $pcDetail = $rowData['pc_detail'] ?? '';
                $hasHtml = strpos($pcDetail, '<') !== false || strpos($pcDetail, '&lt;') !== false;
                error_log("Row $currentRow - pc_detail contains HTML after direct extraction: " . ($hasHtml ? 'YES' : 'NO'));
                if ($hasHtml) {
                    error_log("Row $currentRow - ✓ HTML content preserved with direct extraction!");
                }
            }
            
            $chunkData[] = $rowData;
            $currentRow++;
            $lineIndex++;
        }
        
        // Memory cleanup
        unset($lines);
        
        return $chunkData;
    }
    
    /**
     * Extract chunk data from large files using SplFileObject for memory efficiency
     * 
     * @param string $filePath
     * @param int $chunkIndex
     * @param int $chunkSize
     * @return array
     */
    private static function extractChunkDataFromFile(string $filePath, int $chunkIndex, int $chunkSize): array
    {
        $file = new \SplFileObject($filePath);
        $header = null;
        $currentRow = 0;
        $chunkData = [];
        $startRow = $chunkIndex * $chunkSize;
        $endRow = ($chunkIndex + 1) * $chunkSize;
        
        foreach ($file as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if ($header === null) {
                $header = self::extractFieldsFromLine($line);
                continue;
            }
            
            // Skip rows before our chunk
            if ($currentRow < $startRow) {
                $currentRow++;
                continue;
            }
            
            // Stop if we've processed our chunk
            if ($currentRow >= $endRow) {
                break;
            }
            
            $fields = self::extractFieldsFromLine($line);
            $paddedFields = array_pad($fields, count($header), '');
            $truncatedFields = array_slice($paddedFields, 0, count($header));
            $rowData = array_combine($header, $truncatedFields);
            
            $chunkData[] = $rowData;
            $currentRow++;
        }
        
        return $chunkData;
    }

    /**
     * Extract fields from a CSV line using regex to preserve HTML content
     * 
     * @param string $line
     * @return array
     */
    private static function extractFieldsFromLine(string $line): array
    {
        $fields = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '"';
        
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            
            if ($char === $quoteChar) {
                if ($inQuotes && $i + 1 < strlen($line) && $line[$i + 1] === $quoteChar) {
                    // Handle escaped quotes ("")
                    $current .= $quoteChar;
                    $i++; // Skip the next quote
                } else {
                    // Toggle quote state
                    $inQuotes = !$inQuotes;
                }
            } elseif ($char === ',' && !$inQuotes) {
                // End of field
                $fields[] = $current;
                $current = '';
            } else {
                // Regular character - preserve everything including HTML
                $current .= $char;
            }
        }
        
        // Add the last field
        $fields[] = $current;
        
        return $fields;
    }




    /**
     * Get import progress and logs (for real-time display)
     */

    /**
     * Clear import logs
     */
    public static function clearImportLogs()
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'senheng_import_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }

        ProgressStore::clearLogs();
        
        wp_send_json_success(['message' => 'Logs cleared successfully']);
    }

    /**
     * Render admin page
     */
    public static function renderPage()
    {
        // Generate nonce for the view
        $nonce = wp_create_nonce('senheng_import_nonce');
        
        include dirname(__DIR__) . '/Views/product-import/import.php';
    }
}