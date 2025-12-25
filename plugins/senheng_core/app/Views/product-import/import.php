<?php
// Cron functionality removed - using chunked processing instead
$stats = ProductImportController::getStats();
?>

<div class="import-container">
    <div class="import-header">
        <h1><i class="fas fa-file-import"></i>Product Import</h1>
    </div>
    

    <!-- Product Import Section -->
    <div class="import-layout">
        <div class="import-main">
            <div class="import-card">
                <div class="import-instructions">
                    <p><strong>Instructions:</strong> Select your CSV file and click "Upload & Start Import" to begin the import process. The system will automatically create new products or update existing ones based on SKU. You can drag and drop your file or click to select. Only CSV files are accepted.</p>
                    <div style="background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0; font-size: 12px;">
                        <strong>🚀 Chunked Processing (Like WP All Import):</strong> Click "Upload & Start Import" to upload your CSV and automatically start the import process. 
                        The system processes data in small chunks (25 items per chunk) to avoid timeout issues, just like WP All Import.
                    </div>
                </div>
                
                <form id="custom-import-form" method="post" enctype="multipart/form-data">
                    <div id="drop-area">
                        <span class="drop-icon">&#8682;</span>
                        <span class="drop-text">Drag & Drop CSV file here or click to select</span>
                        <input type="file" name="import_csv" accept=".csv" required>
                    </div>
                    <div id="file-info" style="display:none;"></div>
                    
                    <!-- Import Settings -->
                    <div class="import-settings" style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #333;">Import Settings</h4>
                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="update_descriptions" name="update_descriptions" value="1" checked style="margin-right: 8px;">
                            <span><strong>Update Product Descriptions</strong> - Check to update descriptions for existing products. Uncheck to preserve existing descriptions.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When unchecked, existing product descriptions will not be overwritten during import.</p>
                        
                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="update_parent_from_variations" name="update_parent_from_variations" value="1" style="margin-right: 8px;">
                            <span><strong>Update Parent Description from Variations</strong> - Check to update parent product descriptions when variation SKUs exist and match CSV data.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, if a variation SKU exists on the website and matches CSV data, the parent product's description will be updated using the pc_detail column.</p>
                        
                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="cleanup_unused_attributes" name="cleanup_unused_attributes" value="1" checked style="margin-right: 8px;">
                            <span><strong>Cleanup Unused Attributes</strong> - Check to automatically remove attributes from variable products when they are no longer used by any variations.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, attributes that are no longer used by any variations will be removed from the parent product. Global attributes remain available for other products.</p>
                        
                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="use_enhanced_parent_finding" name="use_enhanced_parent_finding" value="1" style="margin-right: 8px;">
                            <span><strong>Enhanced Parent Finding</strong> - Check to enable advanced parent product matching using SKU patterns and title matching to prevent duplicate variable products.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, the system will use enhanced logic to find existing parent products by analyzing SKU patterns and title variations, helping prevent duplicate variable products when product titles change.</p>

                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="import_new_only" name="import_new_only" value="1" style="margin-right: 8px;">
                            <span><strong>Import New Products Only</strong> - Skip products that already exist by matching SKU or Product Title.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, products with matching SKU or Product Title will be skipped and not created or updated.</p>

                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="partial_update_existing" name="partial_update_existing" value="1" style="margin-right: 8px;">
                            <span><strong>Partial Update for Existing Products</strong> - Only update basic fields for existing products.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, existing products will only have their Title, Price, Stock, and Sales Quantity updated. Descriptions, Images, Categories, and other meta data will be preserved.</p>

                        <label style="display: flex; align-items: center; margin-bottom: 2px;">
                            <input type="checkbox" id="update_yoast_from_sku" name="update_yoast_from_sku" value="1" style="margin-right: 8px;">
                            <span><strong>Update YOAST SEO Meta from SKU</strong> - Update YOAST Meta Title and Description based on SKU.</span>
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 2px 0 15px 26px;">When checked, YOAST SEO Meta Title and Description will be updated for the product (or parent product for variations) based on the SKU. Other data will not be affected.</p>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <?php submit_button('Upload & Start Import'); ?>
                </form>
                
                <!-- Start Import Button (hidden - auto-triggered after upload) -->
                <div id="start-import-section" style="display: none;">
                    <button type="button" id="start-import-btn" class="button button-primary button-large">
                        <i class="fas fa-play"></i> Start Import Process
                    </button>
                    <div id="import-status" style="margin-top: 10px; font-weight: bold; color: #0969da;"></div>
                </div>
                
                <!-- Import Progress Display -->
                <div id="import-progress" style="display: none;">
                    <div class="wrap">
                        <div id="progress-bar-container">
                            <div id="progress-bar"></div>
                        </div>
                        <div id="progress-text"></div>
                        <div id="import-stats" style="display: none;">
                            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <span><strong>Processed:</strong> <span id="stat-processed">0</span></span>
                                <span><strong>Created:</strong> <span id="stat-created">0</span></span>
                                <span><strong>Updated:</strong> <span id="stat-updated">0</span></span>
                                <span><strong>Variations:</strong> <span id="stat-variations">0</span></span>
                                <span><strong>Skipped:</strong> <span id="stat-skipped">0</span></span>
                                <span><strong>Errors:</strong> <span id="stat-errors">0</span></span>
                            </div>
                        </div>
                        <div id="import-log-container">
                            <pre id="import-log"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CSV Column Reference Sidebar -->
        <aside class="import-sidebar">
            <h3 class="import-ref-header">CSV Column Reference</h3>
            <div class="import-ref-list">
                <?php
                $import_column_reference = [
                    ['col' => 'Product_ID',     'desc' => 'Product ID (External)'],
                    ['col' => 'SKU_ID',         'desc' => 'SKU ID (External)'],
                    ['col' => 'sku_code',       'desc' => 'SKU Code (Required)'],
                    ['col' => 'Product_Name',   'desc' => 'Product Name (Required)'],
                    ['col' => 'status',         'desc' => 'Product Status'],
                    ['col' => 'shop_name',      'desc' => 'Shop Name'],
                    ['col' => 'shop_id',        'desc' => 'Shop ID'],
                    ['col' => 'seo_canonical',  'desc' => 'SEO Canonical URL'],
                    ['col' => 'main_image',     'desc' => 'Main Product Image URL'],
                    ['col' => 'video_url',      'desc' => 'Product Video URL'],
                    ['col' => 'trade_in',       'desc' => 'Trade In Available'],
                    ['col' => 'trade_in_amount', 'desc' => 'Trade In Amount'],
                    ['col' => 'product_warranty', 'desc' => 'Product Warranty'],
                    ['col' => 'variantKey',     'desc' => 'Variant Keys (JSON)'],
                    ['col' => 'variantValue',   'desc' => 'Variant Values (JSON)'],
                    ['col' => 'low_price',      'desc' => 'Low Price (Sale Price)'],
                    ['col' => 'high_price',     'desc' => 'High Price (Regular Price)'],
                    ['col' => 'total_s_coin',   'desc' => 'S-Coin Value'],
                    ['col' => 'real_quantity',  'desc' => 'Stock Quantity'],

                    ['col' => 'L1Name',         'desc' => 'Category Level 1'],
                    ['col' => 'L1ID',           'desc' => 'Category Level 1 ID'],
                    ['col' => 'L2Name',         'desc' => 'Category Level 2'],
                    ['col' => 'L2ID',           'desc' => 'Category Level 2 ID'],
                    ['col' => 'L3Name',         'desc' => 'Category Level 3'],
                    ['col' => 'L3ID',           'desc' => 'Category Level 3 ID'],
                    ['col' => 'pc_detail',      'desc' => 'Product Details (JSON)'],
                    ['col' => 'image_json',     'desc' => 'Product Images (JSON)'],
                ];
                
                foreach ($import_column_reference as $ref): ?>
                    <div class="import-ref-item">
                        <span class="import-ref-col"><strong><?php echo esc_html($ref['col']); ?></strong></span>
                        <span class="import-ref-desc"><?php echo esc_html($ref['desc']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
    
    <script type="text/javascript">
    document.getElementById('custom-import-form').addEventListener('submit', function(e) {
        if (typeof jQuery === 'undefined') {
            alert('JavaScript is required for this import to work. Please enable JavaScript and try again.');
            e.preventDefault();
            return false;
        }
    });
    </script>
</div>