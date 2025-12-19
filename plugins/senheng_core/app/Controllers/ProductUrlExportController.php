<?php

class ProductUrlExportController
{
    public static function renderPage()
    {
        // Handle form submission if any
        if (isset($_POST['senheng_export_urls'])) {
            self::handleExport();
        }

        // Render the view
        if (defined('SENHENG_CORE_VIEW_PATH')) {
            include SENHENG_CORE_VIEW_PATH . 'product-url-export/index.php';
        } else {
            // Fallback if constant is not defined (though it should be)
            include dirname(__DIR__) . '/Views/product-url-export/index.php';
        }
    }

    private static function handleExport()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('senheng_export_urls_action', 'senheng_export_urls_nonce');

        $rows_to_process = [];
        $header_row = [];
        $name_column_index = 0; // Default to first column
        $is_csv_upload = false;

        // Check if CSV file is uploaded
        if (!empty($_FILES['product_csv']['name'])) {
            $is_csv_upload = true;
            $file = $_FILES['product_csv'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return;
            }

            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== FALSE) {
                $row_count = 0;
                while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                    // Skip empty rows
                    if (empty($data) || (count($data) === 1 && empty($data[0]))) {
                        continue;
                    }

                    if ($row_count === 0) {
                        // Header detection logic
                        $header_row = $data;
                        $found_name_header = false;
                        
                        // Try to find a column that looks like a product name header
                        foreach ($header_row as $index => $col_name) {
                            $col_lower = strtolower(trim($col_name));
                            if (in_array($col_lower, ['product name', 'name', 'title', 'product title', 'item name'])) {
                                $name_column_index = $index;
                                $found_name_header = true;
                                break;
                            }
                        }
                        
                        // If no header found, we treat this row as data (unless user strictly follows format)
                        // But usually, CSVs have headers.
                        // If we didn't find a "Name" header, we stick to index 0.
                        // However, we should check if index 0 looks like a header?
                        // For now, let's assume row 0 is ALWAYS header if it's a CSV upload.
                        // And we append 'Product URL' to it.
                    }
                    
                    $rows_to_process[] = $data;
                    $row_count++;
                }
                fclose($handle);
            }
        } 
        // Fallback to text area if provided
        elseif (isset($_POST['product_names']) && !empty($_POST['product_names'])) {
            $names_input = wp_unslash($_POST['product_names']);
            $names = preg_split('/\r\n|\r|\n/', $names_input);
            $names = array_map('trim', $names);
            $names = array_filter($names);
            
            // Convert simple list to array of arrays for uniform processing
            $header_row = ['Product Name'];
            foreach ($names as $name) {
                $rows_to_process[] = [$name];
            }
            // Add header to the top of rows for processing logic below
            array_unshift($rows_to_process, $header_row);
        }

        if (empty($rows_to_process)) {
            return;
        }

        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="product_urls_export_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Process Header Row
        $header = array_shift($rows_to_process);
        $header[] = 'Product URL'; // Append new column header
        fputcsv($output, $header);

        // Process Data Rows
        foreach ($rows_to_process as $row_data) {
            $name = '';
            if (isset($row_data[$name_column_index])) {
                $name = trim($row_data[$name_column_index]);
            }

            $url = 'Not Found';
            $product = null;

            if (!empty($name)) {
                // Try exact match
                $args = array(
                    'post_type' => 'product',
                    'title' => $name,
                    'posts_per_page' => 1,
                    'post_status' => array('publish', 'draft'),
                    'no_found_rows' => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false
                );
                $query = new WP_Query($args);
                
                if ($query->have_posts()) {
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());
                    wp_reset_postdata();
                }

                if ($product) {
                    $post_id = $product->get_id();
                    $post_status = get_post_status($post_id);
                    
                    // For draft products, use get_sample_permalink to get the expected URL structure
                    if ($post_status === 'draft') {
                        // get_sample_permalink returns array: [0] = permalink structure, [1] = post slug
                        $sample_permalink = get_sample_permalink($post_id);
                        // Replace %pagename% or %postname% placeholder with the actual slug
                        $permalink = str_replace(['%pagename%', '%postname%'], $sample_permalink[1], $sample_permalink[0]);
                        $url = wp_make_link_relative($permalink);
                    } else {
                        $permalink = $product->get_permalink();
                        $url = wp_make_link_relative($permalink);
                    }
                }
            }
            
            // Remove trailing slash from URL
            $url = rtrim($url, '/');
            
            // Append URL to the original row data
            $row_data[] = $url;
            fputcsv($output, $row_data);
        }
        
        fclose($output);
        exit;
    }
}
