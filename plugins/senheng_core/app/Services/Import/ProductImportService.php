<?php
namespace SenhengCore\App\Services\Import;

use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class ProductImportService
{
    private int $batchSize;
    private string $logFile;
    private string $dataSource;
    private ?int $processed = 0;
    private bool $updateDescriptions;
    private bool $updateParentFromVariations;
    private bool $cleanupUnusedAttributes;
    private bool $useEnhancedParentFinding;
    private bool $importNewOnly;
    private bool $partialUpdateExisting;

    // Track products that need cleanup at the end of batch
    private array $productsNeedingCleanup = [];

    public function __construct(array $cfg = [])
    {
        $this->batchSize  = $cfg['batch_size'] ?? 300;
        $this->logFile    = $cfg['log_file'] ?? WP_CONTENT_DIR . '/uploads/senheng_import.log';
        $this->dataSource = $cfg['data_source'] ?? '';
        $this->updateDescriptions = $cfg['update_descriptions'] ?? false;
        $this->updateParentFromVariations = $cfg['update_parent_from_variations'] ?? false;
        $this->cleanupUnusedAttributes = $cfg['cleanup_unused_attributes'] ?? false;
        $this->useEnhancedParentFinding = $cfg['use_enhanced_parent_finding'] ?? false;
        $this->importNewOnly = $cfg['import_new_only'] ?? false;
        $this->partialUpdateExisting = $cfg['partial_update_existing'] ?? false;
    }

    /**
     * Run the import batch
     */
    public function run(): void
    {
        Logger::info($this->logFile, "==== Import Batch Started ====");
        
        $progress = ProgressStore::get();
        $items    = $this->nextBatch($progress['last_pointer'] ?? null, $this->batchSize);

        if (empty($items)) {
            Logger::info($this->logFile, "No items to process. Import complete or end of feed reached.");
            return;
        }

        Logger::info($this->logFile, sprintf("Processing batch of %d items", count($items)));

        foreach ($items as $item) {
            try {
                $this->upsertFromFeedRow($item);
                ProgressStore::tick($item);
                $this->processed++;
                
                // Save progress after each item for real-time updates
                ProgressStore::save();
                
            } catch (\Throwable $e) {
                ProgressStore::inc('total_errors');
                $title = $item['Product_Name'] ?? 'Unknown';
                $sku = $item['sku_code'] ?? 'N/A';
                Logger::error($this->logFile, sprintf(
                    '✗ ERROR: %s (SKU: %s) - %s', 
                    $title,
                    $sku,
                    $e->getMessage()
                ));
                
                // Save progress after errors too
                ProgressStore::save();
            }
        }
        Logger::info($this->logFile, sprintf(
            "Batch complete. Processed: %d items. Stats: %s",
            $this->processed,
            json_encode(ProgressStore::getStats())
        ));
        
        // Perform cleanup for all products that had variations processed in this batch
        $this->performBatchCleanup();
    }
    
    /**
     * Perform cleanup for all products that had variations processed in this batch
     */
    private function performBatchCleanup(): void
    {
        if (empty($this->productsNeedingCleanup)) {
            return;
        }
        
        $cleanupCount = count($this->productsNeedingCleanup);
        Logger::info($this->logFile, "Performing batch cleanup for $cleanupCount products...");
        
        foreach (array_keys($this->productsNeedingCleanup) as $parentId) {
            try {
                AttributeHelper::cleanupUnusedAttributes($parentId);
                Logger::info($this->logFile, "✓ Cleaned up attributes for product ID: $parentId");
            } catch (\Throwable $e) {
                Logger::error($this->logFile, "✗ Failed to cleanup attributes for product ID $parentId: " . $e->getMessage());
            }
        }
        
        // Clear the tracking array for next batch
        $this->productsNeedingCleanup = [];
        
        Logger::info($this->logFile, "Batch cleanup completed for $cleanupCount products");
    }

    /**
     * Get next batch of items from data source
     * 
     * @param string|null $pointer  Last processed pointer
     * @param int         $limit    Batch size
     * @return array                Array of feed rows
     */
    private function nextBatch(?string $pointer, int $limit): array
    {
        // If no data source specified, return empty
        if (empty($this->dataSource) || !file_exists($this->dataSource)) {
            return [];
        }

        $items = [];
        $found = $pointer === null; // If no pointer, start from beginning
        $file  = new \SplFileObject($this->dataSource);
        $file->setFlags(\SplFileObject::READ_CSV);
        
        $header = null;

        foreach ($file as $lineNum => $line) {
            // Skip empty lines
            if ($line === [null] || $line === false) continue;

            // First line is header
            if ($header === null) {
                $header = array_map('trim', $line);
                continue;
            }

            // Skip malformed lines
            if (count($line) !== count($header)) continue;

            // Create associative array
            $row = array_combine($header, $line);
            
            // Decode JSON fields
            // Skip JSON decoding for pc_detail - use raw content directly
            // $row = $this->decodeJsonFields($row);

            // If we have a pointer, skip until we find it
            if (!$found) {
                if ($this->uid($row) === $pointer) {
                    $found = true;
                }
                continue;
            }

            $items[] = $row;

            // Stop when we reach batch limit
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * Decode JSON field with enhanced handling for Google Sheets CSV export
     * 
     * @param string $jsonString  Raw JSON string
     * @return array              Decoded array or empty array on failure
     */
    private function decodeJsonField(string $jsonString): array
    {
        if (empty($jsonString)) {
            return [];
        }

        // Clean up common Google Sheets CSV export issues
        $cleanedJson = $this->cleanJsonForGoogleSheets($jsonString);
        
        // Try to decode the cleaned JSON
        $decoded = json_decode($cleanedJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::warning($this->logFile, "Failed to decode JSON field: " . json_last_error_msg() . " | Raw: " . substr($jsonString, 0, 100));
            return [];
        }
        
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Generate unique identifier for row
     * 
     * @param array $r  Feed row
     * @return string
     */
    private function uid(array $r): string
    {
        $title = trim((string)($r['Product_Name'] ?? ''));
        $sku   = trim((string)($r['sku_code'] ?? ''));
        return md5(strtolower($title . '|' . $sku));
    }

    /**
     * Convert mixed data to array format
     * Handles JSON strings, arrays, and other formats
     * 
     * @param mixed $data  Input data
     * @return array       Converted array
     */
    private function toArrayFromMixed($data): array
    {
        if (empty($data)) {
            return [];
        }

        // If already an array, return as-is
        if (is_array($data)) {
            return $data;
        }

        // If it's a string, try to decode as JSON
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // If not valid JSON, return as single-item array
            return [$data];
        }

        // For other types, convert to array
        return (array)$data;
    }

    /**
     * Upsert product from feed row with real-time logging
     * 
     * @param array $r  Feed row
     */
    public function upsertFromFeedRow(array $r): void
    {
        // Normalize variantKey/variantValue to arrays so variation detection works
        // even when CSV provided JSON strings like "[\"Color\",\"Size\"]"
        $r['variantKey'] = $this->toArrayFromMixed($r['variantKey'] ?? []);
        $r['variantValue'] = $this->toArrayFromMixed($r['variantValue'] ?? []);
        
        // Normalize pc_detail to extract content from JSON structure
        $r['pc_detail'] = $this->extractContentFromPcDetail($r['pc_detail'] ?? '');
        
        Logger::info($this->logFile, sprintf(
            'Normalized variants: Keys=%s, Values=%s',
            json_encode($r['variantKey']),
            json_encode($r['variantValue'])
        ));
        
        $title = trim((string)($r['Product_Name'] ?? ''));
        $sku   = trim((string)($r['sku_code'] ?? ''));
        
        // Add real-time log entry at start of processing
        ProgressStore::addLog('INFO', "Processing: $title (SKU: $sku)");
        
        // SKU is required
        if (empty($sku)) {
            ProgressStore::addLog('ERROR', "SKU is required for product: $title");
            throw new \Exception("SKU is required (Product: $title)");
        }

        // Check if SKU exists globally before processing
        // This ensures we prioritize existing SKU mapping over CSV structure assumptions
        $existingId = wc_get_product_id_by_sku($sku);
        if ($existingId) {
            $existingProduct = wc_get_product($existingId);
            if ($existingProduct) {
                $type = $existingProduct->get_type();
                
                if ($type === 'variation') {
                    // It's a variation.
                    $parentId = $existingProduct->get_parent_id();
                    Logger::info($this->logFile, "SKU $sku found as existing VARIATION (ID: $existingId). Routing to upsertVariation.");
                    $this->upsertVariation($parentId, $r, $sku);
                    ProgressStore::addLog('INFO', "✓ Successfully processed variation (existing): $title (SKU: $sku)");
                    return;
                } elseif ($type === 'simple') {
                    // It's a simple product.
                    Logger::info($this->logFile, "SKU $sku found as existing SIMPLE product (ID: $existingId). Routing to upsertSimple.");
                    $this->upsertSimple($r, $title, $sku);
                    ProgressStore::addLog('INFO', "✓ Successfully processed simple product (existing): $title (SKU: $sku)");
                    return;
                }
            }
        }

        $isVariation = $this->isVariationRow($r);

        if (!$isVariation) {
            $this->upsertSimple($r, $title, $sku);
            ProgressStore::addLog('INFO', "✓ Successfully processed simple product: $title");
            return;
        }

        // Variation flow
        // First check if this variation SKU already exists anywhere
        $existingVariation = $this->findVariationByExternalSKU($sku);
        
        if ($existingVariation) {
            // Found existing variation - update it within its current parent
            $actualParentId = $existingVariation['parent_id'];
            Logger::info($this->logFile, "Found existing variation with SKU '$sku' - updating within existing parent (ID: $actualParentId)");
            $this->upsertVariation($actualParentId, $r, $sku);
        } else {
            // No existing variation found - proceed with normal flow (create parent if needed)
            $parentId = $this->ensureVariableParent($title, $r, $sku);
            $this->upsertVariation($parentId, $r, $sku);
        }   
        
        ProgressStore::addLog('INFO', "✓ Successfully processed variation: $title (SKU: $sku)");
    }

    /**
     * Check if row represents a variation product
     * 
     * @param array $r  Feed row
     * @return bool
     */
    private function isVariationRow(array $r): bool
    {
        $keys = $r['variantKey'] ?? [];
        $vals = $r['variantValue'] ?? [];
        return is_array($keys) && is_array($vals) && count($keys) && count($vals);
    }

    /**
     * Upsert simple product
     * 
     * @param array  $r      Feed row
     * @param string $title  Product title
     * @param string $sku    Product SKU
     */
    private function upsertSimple(array $r, string $title, string $sku): void
    {
        // Treat provided sku as external SKU and allow duplicates across different products
        // 1) Prefer match by external SKU + Title (new behavior)
        // 2) Fallback to legacy Title + internal SKU (backward compatibility)
        $externalSku = $sku;
        $productId = $this->findSimpleProductByExternalSkuAndTitle($externalSku, $title);
        if (!$productId) {
            $productId = $this->findSimpleProductByTitleAndSKU($title, $externalSku);
        }
        
        Logger::info($this->logFile, "Product lookup for '$title' (SKU: $sku) - External SKU match: " . ($productId ? "Found ID $productId" : "Not found") . ", Will " . ($productId ? "UPDATE" : "CREATE"));
        
        $isUpdate  = (bool)$productId;

        if ($isUpdate && $this->importNewOnly) {
            ProgressStore::inc('total_skipped');
            Logger::info($this->logFile, "SKIPPED EXISTING (import_new_only): $title (SKU: $sku, ID: $productId)");
            return;
        }

        if (!$isUpdate && $this->importNewOnly) {
            $existingByTitle = $this->findSimpleProductByTitleOnly($title);
            if ($existingByTitle) {
                ProgressStore::inc('total_skipped');
                Logger::info($this->logFile, "SKIPPED EXISTING BY TITLE (import_new_only): $title (SKU: $sku, ID: $existingByTitle)");
                return;
            }
        }
        
        if ($productId) {
            // Update existing product
            $product = wc_get_product($productId);
            
            if (!$product || $product->get_type() === 'variation') {
                // Safety check - shouldn't happen with our finder
                Logger::warning($this->logFile, "Found product ID $productId but it's not a simple product. Creating new.");
                $product = new WC_Product_Simple();
                $productId = null;
                $isUpdate = false;
            } else {
                Logger::info($this->logFile, "Found existing simple product for update: $title (ID: $productId)");
            }
        } else {
            // Create new product
            $product = new WC_Product_Simple();
            Logger::info($this->logFile, "Creating new simple product: $title");
        }

        // Only update name if it's different from existing
        if (!$isUpdate || $product->get_name() !== $title) {
            $product->set_name($title);
        }
        // Resolve internal SKU uniqueness: use existing for update, or generate unique for create
        $internalSku = $isUpdate && $productId ? (wc_get_product($productId)->get_sku() ?: $externalSku) : $this->generateUniqueSku($externalSku);
        $product->set_sku($internalSku);
        $this->applyCommonFields($product, $r);
        $mappedStatus = $this->resolvePostStatus($r);
        if ($mappedStatus) {
            $product->set_status($mappedStatus);
            Logger::info($this->logFile, "Product status updated to '$mappedStatus'");
        }

        try {
        $productId = $product->save();
            
            if (!$productId || is_wp_error($productId)) {
                throw new \Exception("Failed to save product: " . (is_wp_error($productId) ? $productId->get_error_message() : 'Unknown error'));
            }
        
        $this->applyACFAndMeta($productId, $r, false, $isUpdate);
        // Persist the external SKU for future matching
        $currentExternalSku = get_post_meta($productId, '_external_sku', true);
        if ($currentExternalSku !== $externalSku) {
            update_post_meta($productId, '_external_sku', $externalSku);
        }

        // Skip heavy updates if partial update is enabled for existing products
        if (!$isUpdate || !$this->partialUpdateExisting) {
            $this->assignCategories($productId, $r);
            $this->assignBrand($productId, $r);
            $this->applyImages($productId, $r, true);
        } else {
            Logger::info($this->logFile, "Partial update enabled: Skipped Categories, Brand, and Images for existing product.");
        }
            
        } catch (\Exception $e) {
            // Check if it's a duplicate SKU error
            if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'SKU') !== false) {
                Logger::warning($this->logFile, "⚠️ SKIPPED (Duplicate SKU): $title (SKU: $sku) - {$e->getMessage()}");
                ProgressStore::inc('total_skipped');
                return; // Skip this product and continue with next
            }
            
            // Re-throw other errors
            throw $e;
        }

        if ($isUpdate) {
            ProgressStore::inc('total_updated');
            Logger::info($this->logFile, "✓ UPDATED: $title (SKU: $sku, ID: $productId)\n\n");
        } else {
            ProgressStore::inc('total_created');
            Logger::info($this->logFile, "✓ CREATED: $title (SKU: $sku, ID: $productId)\n\n");
        }
    }

    /**
     * Enhanced parent finding that considers SKU patterns and existing variations
     * This prevents duplicate variable products when titles change
     * 
     * @param string $title Product title
     * @param string $sku   Variation SKU to analyze for patterns
     * @return int|null     Product ID or null
     */
    private function findParentByTitleOrSKUPattern(string $title, string $sku): ?int
    {
        // First try exact title match (existing behavior)
        $parentId = $this->findParentByTitle($title);
        if ($parentId) {
            return $parentId;
        }
        
        // If no exact title match, look for existing variations with similar SKU patterns
        // This helps group variations that belong to the same product family
        $relatedParentId = $this->findParentBySKUPattern($sku);
        if ($relatedParentId) {
            // Update the parent title to the new title
            $this->updateParentTitle($relatedParentId, $title);
            return $relatedParentId;
        }
        
        return null;
    }

    /**
     * Find parent by analyzing SKU patterns of existing variations
     * This groups variations that likely belong to the same product family
     * 
     * @param string $sku Variation SKU to analyze
     * @return int|null   Parent ID if pattern match found
     */
    private function findParentBySKUPattern(string $sku): ?int
    {
        if (!$sku) {
            return null;
        }
        
        global $wpdb;
        
        // Extract potential base pattern from SKU
        // For SKUs like "SAM-SM-S928BZTWXME", we look for similar patterns
        $basePattern = $this->extractSKUBasePattern($sku);
        if (!$basePattern) {
            return null;
        }
        
        // Find variations with similar SKU patterns
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT DISTINCT p.post_parent as parent_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_parent > 0
            AND pm.meta_key = '_external_sku'
            AND pm.meta_value LIKE %s
            LIMIT 1",
            $basePattern . '%'
        ), ARRAY_A);
        
        return $result ? (int)$result['parent_id'] : null;
    }

    /**
     * Extract base pattern from SKU for grouping related variations
     * 
     * @param string $sku Full SKU
     * @return string|null Base pattern or null
     */
    private function extractSKUBasePattern(string $sku): ?string
    {
        if (!$sku) {
            return null;
        }
        
        // For SKUs like "SAM-SM-S928BZTWXME", extract "SAM-SM-S928BZ"
        // This assumes the last few characters are variant-specific
        
        // Method 1: Remove last 4-6 characters (common for color/storage variants)
        if (strlen($sku) > 10) {
            return substr($sku, 0, -4);
        }
        
        // Method 2: For dash-separated SKUs, remove last segment if it's short
        $parts = explode('-', $sku);
        if (count($parts) > 2) {
            $lastPart = end($parts);
            if (strlen($lastPart) <= 6) { // Likely a variant code
                array_pop($parts);
                return implode('-', $parts);
            }
        }
        
        // Fallback: use first 80% of the SKU
        return substr($sku, 0, (int)(strlen($sku) * 0.8));
    }

    /**
     * Update parent product title
     * 
     * @param int    $parentId Parent product ID
     * @param string $newTitle New title to set
     */
    private function updateParentTitle(int $parentId, string $newTitle): void
    {
        $parent = wc_get_product($parentId);
        if (!$parent) {
            return;
        }
        
        $currentTitle = $parent->get_name();
        if ($currentTitle !== $newTitle) {
            Logger::info($this->logFile, sprintf(
                "Updating parent product title: ID %d from '%s' to '%s'",
                $parentId,
                $currentTitle,
                $newTitle
            ));
            
            wp_update_post([
                'ID' => $parentId,
                'post_title' => $newTitle
            ]);
        }
    }

    /**
     * Ensure variable parent product exists (Enhanced with SKU pattern matching)
     * 
     * @param string $title  Product title
     * @param array  $r      Feed row
     * @param string $sku    Variation SKU for pattern analysis
     * @return int           Parent product ID
     */
    private function ensureVariableParent(string $title, array $r, string $sku = ''): int
    {
        // Use enhanced parent finding only if toggle is enabled
        if ($this->useEnhancedParentFinding) {
            $parentId = $this->findParentByTitleOrSKUPattern($title, $sku);
        } else {
            // Use original logic - simple title matching
            $parentId = $this->findParentByTitle($title);
        }
        
        if ($parentId) {
            if ($this->importNewOnly) {
                Logger::info($this->logFile, "Using existing variable parent without updates (import_new_only): $title (ID: $parentId)");
                return $parentId;
            }
            // Parent exists - update its data
            $this->assignCategories($parentId, $r);
            $mappedStatus = $this->resolvePostStatus($r);
            if ($mappedStatus) {
                $parentProduct = wc_get_product($parentId);
                if ($parentProduct && $parentProduct->get_status() !== $mappedStatus) {
                    $parentProduct->set_status($mappedStatus);
                    $parentProduct->save();
                }
            }
            
            // Update parent product description from pc_detail FIRST (only if toggle allows)
            $content = $r['pc_detail'] ?? '';
            if ($content && $this->updateDescriptions) {
                $parentProduct = wc_get_product($parentId);
                if ($parentProduct) {
                    $parentProduct->set_description($content);
                    $parentProduct->save();
                    Logger::info($this->logFile, "Parent product description updated");
                } else {
                    Logger::warning($this->logFile, "Failed to load parent product: $title (ID: $parentId)");
                }
            } elseif ($content && !$this->updateDescriptions) {
                Logger::info($this->logFile, "Parent product description update skipped (existing product, toggle disabled)");
            }
            
            // Apply images AFTER setting description
            $this->applyImages($parentId, $r, true);
            
            if (!empty($r['SKU_ID'])) {
                $this->applyACFAndMeta($parentId, $r, false);
            }
            
            Logger::info($this->logFile, "Using existing variable parent: $title (ID: $parentId)");
            return $parentId;
        }

        // Create new parent
        $p = new WC_Product_Variable();
        $p->set_name($title);
        $mappedStatus = $this->resolvePostStatus($r);
        if ($mappedStatus) {
            $p->set_status($mappedStatus);
        }
        
        // Set description from pc_detail
        Logger::info($this->logFile, "description already imported");
        // pc_detail is already processed and contains extracted content
        $content = $r['pc_detail'] ?? '';
        if ($content) {
            $p->set_description($content);
        }
        
        $pId = $p->save();

        $this->assignCategories($pId, $r);
        // Apply images AFTER setting description
        $this->applyImages($pId, $r, true); // thumbnail only for parent
        
        // Apply ACF fields to parent (excluding Insider Product ID which is only for variations)
        $this->applyACFAndMeta($pId, $r, false);

        ProgressStore::inc('total_created');
        Logger::info($this->logFile, "Created variable parent: $title (ID: $pId)");

        return $pId;
    }

    /**
     * Find parent variable product by title
     * 
     * @param string $title  Product title
     * @return int|null      Product ID or null
     */
    private function findParentByTitle(string $title): ?int
    {
        global $wpdb;
        
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_title = %s 
            AND post_type = 'product' 
            AND post_status IN ('publish', 'draft', 'pending') 
            LIMIT 1",
            $title
        ));
        
        return $id ? (int)$id : null;
    }

    private function findSimpleProductByTitleOnly(string $title): ?int
    {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
            WHERE post_title = %s
            AND post_type = 'product'
            AND post_status IN ('publish','draft','pending')
            LIMIT 1",
            $title
        ));
        if (!$id) {
            return null;
        }
        $product = wc_get_product($id);
        if (!$product || $product->get_type() !== 'simple') {
            return null;
        }
        return (int)$id;
    }

    /**
     * Find variation by SKU under specific parent
     * 
     * @param int    $parentId  Parent product ID
     * @param string $sku       Variation SKU
     * @return int|null         Variation ID or null
     */
    private function findVariationBySKU(int $parentId, string $sku): ?int
    {
        if (!$sku) return null;
        
        $id = wc_get_product_id_by_sku($sku);
        if ($id) {
            $prod = wc_get_product($id);
            if ($prod && $prod->get_parent_id() === $parentId) {
                return $id;
            }
        }
        
        return null;
    }

    /**
     * Find simple product by SKU with new conflict resolution logic
     * 
     * Case A: SKU exists but product name differs -> Update existing product (keep same SKU)
     * Case B: Same SKU, different product type (simple vs variation) -> Allow coexistence
     * 
     * @param string $title  Product title
     * @param string $sku    Product SKU
     * @return int|null      Product ID or null
     */
    private function findSimpleProductByTitleAndSKU(string $title, string $sku): ?int
    {
        global $wpdb;
        
        // First try to find by SKU
        $skuProducts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_sku' 
            AND pm.meta_value = %s
            AND p.post_type = 'product'
            AND p.post_status IN ('publish', 'draft', 'pending')",
            $sku
        ));
        
        // If no products with this SKU, return null (will create new)
        if (empty($skuProducts)) {
            return null;
        }
        
        // Check each product with this SKU
        foreach ($skuProducts as $product) {
            $existingProduct = wc_get_product($product->ID);
            if (!$existingProduct) continue;
            
            // Case B: Different product types can coexist with same SKU
            // Only consider simple products for simple product import
            if ($existingProduct->get_type() !== 'simple') {
                continue; // Skip variations, variable products, etc.
            }
            
            // Case A: SKU exists with simple product
            // Always update the existing simple product regardless of title match
            Logger::info($this->logFile, sprintf(
                "SKU conflict resolution: Found existing simple product (ID: %d, Title: '%s') with SKU '%s'. Will update with new title '%s'",
                $product->ID,
                $product->post_title,
                $sku,
                $title
            ));
            
            return (int)$product->ID;
        }
        
        // No simple product found with this SKU (only variations/other types exist)
        // This allows coexistence per Case B
        return null;
    }

    /**
     * Find simple product by external SKU with new conflict resolution logic
     * 
     * Case A: SKU exists but product name differs -> Update existing product (keep same SKU)
     * Case B: Same SKU, different product type (simple vs variation) -> Allow coexistence
     */
    private function findSimpleProductByExternalSkuAndTitle(string $externalSku, string $title): ?int
    {
        if (!$externalSku) {
            return null;
        }
        
        global $wpdb;
        
        // Find all products with this external SKU
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND p.post_status IN ('publish', 'draft', 'pending')
            AND pm.meta_key = '_external_sku'
            AND pm.meta_value = %s",
            $externalSku
        ));
        
        if (empty($products)) {
            return null;
        }
        
        // Check each product with this external SKU
        foreach ($products as $product) {
            $existingProduct = wc_get_product($product->ID);
            if (!$existingProduct) continue;
            
            // Case B: Different product types can coexist with same SKU
            // Only consider simple products for simple product import
            if ($existingProduct->get_type() !== 'simple') {
                continue; // Skip variations, variable products, etc.
            }
            
            // Case A: External SKU exists with simple product
            // Always update the existing simple product regardless of title match
            Logger::info($this->logFile, sprintf(
                "External SKU conflict resolution: Found existing simple product (ID: %d, Title: '%s') with external SKU '%s'. Will update with new title '%s'",
                $product->ID,
                $product->post_title,
                $externalSku,
                $title
            ));
            
            return (int)$product->ID;
        }
        
        // No simple product found with this external SKU (only variations/other types exist)
        // This allows coexistence per Case B
        return null;
    }

    /**
     * Find variation by Parent ID + SKU combination
     * This allows same SKU to exist under different parents
     * 
     * @param int    $parentId  Parent product ID
     * @param string $sku       Variation SKU
     * @return int|null         Variation ID or null
     */
    private function findVariationByParentAndSKU(int $parentId, string $sku): ?int
    {
        global $wpdb;
        
        // Find variation with this SKU under this specific parent
        $varId = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_parent = %d
            AND pm.meta_key = '_sku'
            AND pm.meta_value = %s
            LIMIT 1",
            $parentId,
            $sku
        ));
        
        return $varId ? (int)$varId : null;
    }

    /**
     * Find variation by external SKU across all products (not limited to a specific parent)
     * 
     * @param string $externalSku  External SKU to search for
     * @return array|null          Array with 'variation_id' and 'parent_id' if found, null otherwise
     */
    private function findVariationByExternalSKU(string $externalSku): ?array
    {
        if (!$externalSku) {
            return null;
        }
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT p.ID as variation_id, p.post_parent as parent_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND pm.meta_key = '_external_sku'
            AND pm.meta_value = %s
            LIMIT 1",
            $externalSku
        ), ARRAY_A);
        
        return $result ? [
            'variation_id' => (int)$result['variation_id'],
            'parent_id' => (int)$result['parent_id']
        ] : null;
    }

    /**
     * Find variation by Parent ID + external SKU
     */
    private function findVariationByParentAndExternalSKU(int $parentId, string $externalSku): ?int
    {
        if (!$externalSku) {
            return null;
        }
        global $wpdb;
        $varId = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_parent = %d
            AND pm.meta_key = '_external_sku'
            AND pm.meta_value = %s
            LIMIT 1",
            $parentId,
            $externalSku
        ));
        return $varId ? (int)$varId : null;
    }

    /**
     * Assign brand to product using BrandHelper
     * 
     * @param int   $productId  Product ID
     * @param array $r          Feed row data
     */
    private function assignBrand(int $productId, array $r): void
    {
        $brandName = trim((string)($r['brand_name'] ?? ($r['product_brand'] ?? '')));
        if (empty($brandName)) {
            Logger::info($this->logFile, "No brand specified for product ID: $productId");
            return;
        }

        try {
            $success = BrandHelper::assignBrand($productId, $brandName);
            if ($success) {
                Logger::info($this->logFile, "✓ Brand assigned successfully: '$brandName' to product ID: $productId");
            } else {
                Logger::warning($this->logFile, "⚠️ Failed to assign brand: '$brandName' to product ID: $productId");
            }
        } catch (\Exception $e) {
            Logger::error($this->logFile, "ERROR assigning brand '$brandName' to product ID $productId: " . $e->getMessage());
        }
    }

    /**
     * Generate a unique internal WooCommerce SKU based on desired value.
     * If the desired SKU is already taken, append -1, -2, ... until unique.
     */
    private function generateUniqueSku(string $desiredSku): string
    {
        $base = trim($desiredSku);
        if ($base === '') {
            // Fallback if somehow empty
            $base = 'sku-' . wp_generate_password(8, false, false);
        }
        $candidate = $base;
        $suffix = 1;
        while ($candidate && wc_get_product_id_by_sku($candidate)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }
        return $candidate;
    }

    /**
     * Set data source file path
     * 
     * @param string $path  Path to CSV file
     */
    public function setDataSource(string $path): void
    {
        $this->dataSource = $path;
    }

    /**
     * Update parent product description from variation's pc_detail
     * 
     * @param int    $parentId  Parent product ID
     * @param string $pcDetail  PC detail content from variation
     * @param string $sku       Variation SKU for logging
     */
    private function updateParentDescriptionFromVariation(int $parentId, string $pcDetail, string $sku): void
    {
        $parentProduct = wc_get_product($parentId);
        if (!$parentProduct) {
            Logger::warning($this->logFile, "Failed to load parent product for description update (ID: $parentId, Variation SKU: $sku)");
            return;
        }

        // Update parent product description with pc_detail content
        $parentProduct->set_description($pcDetail);
        $parentProduct->save();
        
        Logger::info($this->logFile, "🔄 Parent product description updated from variation '$sku' pc_detail (Parent ID: $parentId)");
    }



    /**
     * Upsert variation product
     * 
     * @param int    $parentId  Parent product ID
     * @param array  $r         Feed row
     * @param string $sku       Variation SKU
     */
    private function upsertVariation(int $parentId, array $r, string $sku): void
    {
        $keys = $r['variantKey'] ?? [];
        $vals = $r['variantValue'] ?? [];

        // Ensure arrays are same length
        if (count($keys) !== count($vals)) {
            throw new \Exception("variantKey and variantValue arrays must have same length (SKU: $sku)");
        }

        [$variationAttrs, $attrMap] = AttributeHelper::resolveAttributes($keys, $vals);

        Logger::info($this->logFile, sprintf(
            "Resolved attributes for SKU %s: Keys=%s, Values=%s, VariationAttrs=%s",
            $sku,
            json_encode($keys),
            json_encode($vals),
            json_encode($variationAttrs)
        ));

        // Ensure parent variable attributes are registered first
        AttributeHelper::attachVariableAttributesToParent($parentId, $attrMap);
        
        Logger::info($this->logFile, sprintf(
            "Attached attributes to parent ID %d: %s",
            $parentId,
            json_encode($attrMap)
        ));

        // Convert variation attributes to proper format for global attributes
        $formattedVariationAttrs = [];
        foreach ($variationAttrs as $taxonomy => $termSlug) {
            // For variations, we need to use the term slug as the value
            // but ensure the taxonomy is properly prefixed
            $formattedVariationAttrs[$taxonomy] = $termSlug;
        }

        Logger::info($this->logFile, sprintf(
            "Formatted variation attributes for SKU %s: %s",
            $sku,
            json_encode($formattedVariationAttrs)
        ));

        // Treat provided sku as external SKU
        $externalSku = $sku;
        
        // Look for existing variation within this parent
        $varId = $this->findVariationByParentAndExternalSKU($parentId, $externalSku);
        if (!$varId) {
            $varId = $this->findVariationByParentAndSKU($parentId, $externalSku);
        }
        
        Logger::info($this->logFile, "Variation lookup for SKU '$sku' under parent $parentId - External SKU match: " . ($varId ? "Found ID $varId" : "Not found") . ", Will " . ($varId ? "UPDATE" : "CREATE"));
        
        $isUpdate = (bool)$varId;

        if ($isUpdate && $this->importNewOnly) {
            ProgressStore::inc('total_skipped');
            Logger::info($this->logFile, "SKIPPED EXISTING VARIATION (import_new_only): $sku (ID: $varId, Parent: $parentId)");
            return;
        }
        
        // If this is an existing variation and the toggle is enabled, update parent description
        if ($isUpdate && !$this->importNewOnly && $this->updateParentFromVariations && !empty($r['pc_detail'])) {
            $this->updateParentDescriptionFromVariation($parentId, $r['pc_detail'], $sku);
        }

        
        $var = $varId ? new WC_Product_Variation($varId) : new WC_Product_Variation();
        
        if ($varId) {
            Logger::info($this->logFile, "Found existing variation for update: $sku (ID: $varId, Parent: $parentId)");
        } else {
            Logger::info($this->logFile, "Creating new variation: $sku (Parent: $parentId)");
        }
        
        $var->set_parent_id($parentId);
        // Resolve internal SKU uniqueness for variations too
        $internalSku = $isUpdate && $varId ? (wc_get_product($varId)->get_sku() ?: $externalSku) : $this->generateUniqueSku($externalSku);
        $var->set_sku($internalSku);
        
        // Only update attributes if we have new ones, OR if it's a new product
        // If it's an update and we have no attributes in CSV, preserve existing
        if (!empty($formattedVariationAttrs) || !$isUpdate) {
            $var->set_attributes($formattedVariationAttrs);
        }

        $this->applyCommonFields($var, $r);
        $mappedStatus = $this->resolvePostStatus($r);
        
        // If we have a status from CSV, update the PARENT product's status
        if ($mappedStatus && $parentId) {
            $parentProduct = wc_get_product($parentId);
            // Only update if different to avoid unnecessary saves
            if ($parentProduct && $parentProduct->get_status() !== $mappedStatus) {
                $parentProduct->set_status($mappedStatus);
                $parentProduct->save();
                Logger::info($this->logFile, "Parent Product (ID: $parentId) status updated to '$mappedStatus' from variation row");
            }
        }

        try {
        $varId = $var->save();
            
            if (!$varId || is_wp_error($varId)) {
                throw new \Exception("Failed to save variation: " . (is_wp_error($varId) ? $varId->get_error_message() : 'Unknown error'));
            }

        $this->applyACFAndMeta($varId, $r, true, $isUpdate);
        // Persist the external SKU for future matching
        $currentExternalSku = get_post_meta($varId, '_external_sku', true);
        if ($currentExternalSku !== $externalSku) {
            update_post_meta($varId, '_external_sku', $externalSku);
        }
        
        // Skip heavy updates if partial update is enabled for existing products
        if (!$isUpdate || !$this->partialUpdateExisting) {
            // Assign brand to parent product (variations inherit parent's brand)
            $this->assignBrand($parentId, $r);
            $this->applyImages($varId, $r, false); // gallery typically on parent
        } else {
            Logger::info($this->logFile, "Partial update enabled: Skipped Brand and Images for existing variation.");
        }
            
        } catch (\Exception $e) {
            // Check if it's a duplicate SKU error
            if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'SKU') !== false) {
                Logger::warning($this->logFile, "⚠️ SKIPPED VARIATION (Duplicate SKU): $sku (Parent: $parentId) - {$e->getMessage()}");
                ProgressStore::inc('total_skipped');
                return; // Skip this variation and continue with next
            }
            
            // Re-throw other errors
            throw $e;
        }

        // Clean up unused attributes from parent after variation update (if toggle enabled)
        if ($this->cleanupUnusedAttributes) {
            // Instead of cleaning up immediately, track this parent for cleanup at end of batch
            $this->productsNeedingCleanup[$parentId] = true;
        }

        if ($isUpdate) {
            ProgressStore::inc('total_updated');
            Logger::info($this->logFile, "✓ UPDATED VARIATION: $sku (ID: $varId, Parent: $parentId)\n\n");
        } else {
            ProgressStore::inc('total_variations');
            Logger::info($this->logFile, "✓ CREATED VARIATION: $sku (ID: $varId, Parent: $parentId)\n\n");
        }
    }

    /**
     * Apply common WooCommerce fields
     * 
     * @param WC_Product_Simple|WC_Product_Variation $product  Product object
     * @param array                                   $r        Feed row
     */
    private function applyCommonFields($product, array $r): void
    {
        // Stock management - only update if different
        $product->set_manage_stock(true);
        
        // Determine stock quantity
        $qty = 0;
        if (isset($r['real_quantity']) && $r['real_quantity'] !== '') {
            $qty = (int)$r['real_quantity'];
        } elseif (isset($r['quantity']) && $r['quantity'] !== '') {
            $qty = (int)$r['quantity'];
        }
        
        // Only update stock quantity if it has changed
        $currentQty = $product->get_stock_quantity();
        if ($currentQty != $qty) {
            $product->set_stock_quantity($qty);
        }
        
        // Determine stock status based on quantity
        $newStockStatus = $qty > 0 ? 'instock' : 'outofstock';
        $currentStockStatus = $product->get_stock_status();
        if ($currentStockStatus !== $newStockStatus) {
            $product->set_stock_status($newStockStatus);
        }

        // Description from pc_detail (only for simple products, not variations)
        // Skip description update if partial update is enabled for existing products
        $isExistingProduct = $product->get_id() > 0;
        $skipDescription = $isExistingProduct && $this->partialUpdateExisting;

        if ($product->get_type() !== 'variation' && !$skipDescription) {
            // pc_detail is already processed and contains extracted content
            $content = $r['pc_detail'] ?? '';
            if ($content) {
                $currentDescription = $product->get_description();
                
                // Check if this is an existing product and if description updates are disabled
                $shouldUpdateDescription = !$isExistingProduct || $this->updateDescriptions;
                
                if ($shouldUpdateDescription && $currentDescription !== $content) {
                    $product->set_description($content);
                    Logger::info($this->logFile, "Product Description updated");
                } elseif (!$shouldUpdateDescription) {
                    Logger::info($this->logFile, "Product Description update skipped (existing product, toggle disabled)");
                }
            }
        } elseif ($skipDescription) {
            Logger::info($this->logFile, "Product Description update skipped (Partial Update enabled)");
        }

        $origPrice = null;
        if (isset($r['original_price']) && $r['original_price'] !== '') {
            $origPrice = $r['original_price'];
        } elseif (isset($r['regular_price']) && $r['regular_price'] !== '') {
            $origPrice = $r['regular_price'];
        }

        if ($origPrice !== null) {
            $currentRegularPrice = $product->get_regular_price();
            if ($currentRegularPrice != $origPrice) {
                $product->set_regular_price($origPrice);
            }
        }

        $salePrice = null;
        if (isset($r['sales_price']) && $r['sales_price'] !== '') {
            $salePrice = $r['sales_price'];
        } elseif (isset($r['sale_price']) && $r['sale_price'] !== '') {
            $salePrice = $r['sale_price'];
        }

        if ($salePrice !== null) {
            $currentSalePrice = $product->get_sale_price();
            if ($currentSalePrice != $salePrice) {
                $product->set_sale_price($salePrice);
            }
        }

    }

    /**
     * Apply ACF fields and meta data to product
     * 
     * @param int   $postId      Product ID
     * @param array $r           Feed row
     * @param bool  $isVariation Whether this is a variation
     */
    private function applyACFAndMeta(int $postId, array $r, bool $isVariation, bool $isUpdate = false): void
    {
        // Get product to check if it's a simple product or variable parent
        $product = wc_get_product($postId);
        $isSimpleProduct = $product && $product->get_type() === 'simple';
        
        // Determine if we should skip heavy meta updates (partial update mode)
        $skipHeavyMeta = $isUpdate && $this->partialUpdateExisting;

        // Sales quantity meta (ALWAYS update regardless of partial update)
        if (isset($r['sale_quantity'])) {
            $currentSalesQuantity = get_post_meta($postId, '_sales_quantity', true);
            $newSalesQuantity = (int)$r['sale_quantity'];
            if ((int)$currentSalesQuantity !== $newSalesQuantity) {
                update_post_meta($postId, '_sales_quantity', $newSalesQuantity);
                Logger::info($this->logFile, "Updated sale_quantity for product ID $postId: $newSalesQuantity");
            }
        }

        // ACF fields
        if (function_exists('update_field') && !$skipHeavyMeta) {
            // Set Insider Product ID and s_coin_value for:
            // - Simple products (on the product itself)
            // - Variations (on each variation, parent remains empty)
            if ($isSimpleProduct || $isVariation) {
                if (!empty($r['SKU_ID'])) {
                    $currentInsiderProductId = get_field('insider_product_id', $postId);
                    if ($currentInsiderProductId !== $r['SKU_ID']) {
                        update_field('insider_product_id', $r['SKU_ID'], $postId);
                    }
                }
                if (isset($r['total_s_coin'])) {
                    $currentSCoinValue = get_field('s_coin_value', $postId);
                    if ($currentSCoinValue !== $r['total_s_coin']) {
                        update_field('s_coin_value', $r['total_s_coin'], $postId);
                    }
                }
            }
            // For variable parents: ACF fields are left empty (not set)
        }

        // For variations, also store Insider Product ID in post meta for the variation field
        if ($isVariation && !empty($r['SKU_ID'])) {
            $currentMetaInsiderProductId = get_post_meta($postId, 'insider_product_id', true);
            if ($currentMetaInsiderProductId !== $r['SKU_ID']) {
                update_post_meta($postId, 'insider_product_id', $r['SKU_ID']);
            }
        }

        // Yoast SEO Fields
        // Meta Title (supports 'meta_title', 'ptitle', or 'seo_meta_title' columns)
        if (!$skipHeavyMeta) {
            // Determine target ID for SEO fields
            // If it's a variation, apply SEO to parent instead
            $seoTargetId = $postId;
            $targetDescription = "Product";
            
            if ($isVariation) {
                $seoTargetId = $product->get_parent_id();
                $targetDescription = "Parent Product (from Variation)";
            }
            
            if ($seoTargetId) {
                $seoTitle = $r['seo_meta_title'] ?? ($r['meta_title'] ?? ($r['ptitle'] ?? ''));
                if (!empty($seoTitle)) {
                    $currentSeoTitle = get_post_meta($seoTargetId, '_yoast_wpseo_title', true);
                    if ($currentSeoTitle !== $seoTitle) {
                        update_post_meta($seoTargetId, '_yoast_wpseo_title', $seoTitle);
                        Logger::info($this->logFile, "Yoast SEO Title updated for $targetDescription (ID: $seoTargetId)");
                    }
                }

                // Meta Description (supports 'meta_description' or 'seo_meta_description' columns)
                $seoDesc = $r['seo_meta_description'] ?? ($r['meta_description'] ?? '');
                if (!empty($seoDesc)) {
                    $currentSeoDesc = get_post_meta($seoTargetId, '_yoast_wpseo_metadesc', true);
                    if ($currentSeoDesc !== $seoDesc) {
                        update_post_meta($seoTargetId, '_yoast_wpseo_metadesc', $seoDesc);
                        Logger::info($this->logFile, "Yoast SEO Description updated for $targetDescription (ID: $seoTargetId)");
                    }
                }
            }
        } elseif ($isUpdate && $this->partialUpdateExisting) {
            Logger::info($this->logFile, "Partial update enabled: Skipped Yoast SEO meta updates.");
        }
    }

    /**
     * Assign categories to product
     * 
     * @param int   $postId  Product ID
     * @param array $r       Feed row
     */
    private function assignCategories(int $postId, array $r): void
    {
        CategoryHelper::assignByPath($postId, [
            'L1' => $r['L1Name'] ?? '',
            'L2' => $r['L2Name'] ?? '',
            'L3' => $r['L3Name'] ?? '',
        ]);
    }

    /**
     * Apply images to product
     * 
     * @param int   $postId         Product ID
     * @param array $r              Feed row
     * @param bool  $includeGallery Whether to include gallery images
     */
    private function applyImages(int $postId, array $r, bool $includeGallery): void
    {
        // Featured image
        if (!empty($r['main_image'])) {
            MediaHelper::setFeaturedImageFromUrl($postId, $r['main_image']);
        }
        
        // Gallery images
        if ($includeGallery && !empty($r['image_json'])) {
            MediaHelper::setGalleryFromJsonUrls($postId, $r['image_json']);
        }
    }

    /**
     * Extract content from pc_detail array
     * 
     * @param mixed $pcDetail  pc_detail data
     * @return string          Concatenated content
     */
    private function extractContentFromPcDetail($pcDetail): string
    {
        if (empty($pcDetail)) {
            return '';
        }

        // If it's a string, try to decode as JSON first
        if (is_string($pcDetail)) {
            $decoded = json_decode($pcDetail, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $pcDetail = $decoded;
            } else {
                // If not valid JSON, return the string as-is
                return $pcDetail;
            }
        }

        // If it's an array, extract content from each item
        if (is_array($pcDetail)) {
            $content = '';
            foreach ($pcDetail as $item) {
                if (is_array($item) && isset($item['content'])) {
                    $content .= $item['content'];
                }
            }
            return $content;
        }

        return '';
    }

    private function resolvePostStatus(array $r): ?string
    {
        $raw = isset($r['status']) ? $r['status'] : ($r['Status'] ?? '');
        $val = strtolower(trim((string)$raw));
        if ($val === 'active') return 'publish';
        if ($val === 'inactive') return 'draft';
        return null;
    }

    /**
     * Find product by SKU
     * 
     * @param string $sku  Product SKU
     * @return int|null    Product ID or null
     */
    private function findProductBySKU(string $sku): ?int
    {
        if (!$sku) return null;
        $id = wc_get_product_id_by_sku($sku);
        return $id ?: null;
    }
}

