<?php
namespace Yoast_Meta_IE\Ajax;

/**
 * Converts SEO Progress Tracking CSV (URL-based) to Yoast Meta Import format (ID-based).
 * 
 * CSV Input Format: Groups, URL, Meta Status (title), Meta Description
 * CSV Output Format: type, id, _yoast_wpseo_title, _yoast_wpseo_metadesc
 */
class CSV_Converter {
    
    /**
     * Site URL for stripping from full URLs.
     *
     * @var string
     */
    private $site_url;

    /**
     * Constructor. Registers AJAX action hooks.
     */
    public function __construct() {
        $this->site_url = rtrim( get_site_url(), '/' );
        add_action( 'wp_ajax_yoast_meta_ie_convert_csv', array( $this, 'convert_csv' ) );
    }

    /**
     * Handles the CSV conversion AJAX request.
     * Converts URL-based CSV to ID-based format for import.
     */
    public function convert_csv() {
        // Verify nonce and permissions
        check_ajax_referer( 'yoast_meta_ie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $csv_content = isset( $_POST['csv_content'] ) ? stripslashes( $_POST['csv_content'] ) : '';

        if ( empty( $csv_content ) ) {
            wp_send_json_error( 'No CSV content provided' );
        }

        $result = $this->process_csv( $csv_content );

        wp_send_json_success( $result );
    }

    /**
     * Process the CSV content and convert URLs to IDs.
     *
     * @param string $csv_content Raw CSV content.
     * @return array Conversion results.
     */
    private function process_csv( $csv_content ) {
        $lines = explode( "\n", $csv_content );
        $converted = array();
        $errors = array();
        $skipped = array();

        // Parse header row
        $header = str_getcsv( array_shift( $lines ) );
        $header = array_map( 'trim', $header );

        // Find column indices
        $url_col = array_search( 'URL', $header );
        $title_col = array_search( 'Meta Status', $header );
        $desc_col = array_search( 'Meta Description', $header );
        $groups_col = array_search( 'Groups', $header );

        if ( $url_col === false || $title_col === false || $desc_col === false ) {
            return array(
                'success' => false,
                'error' => 'CSV must contain columns: URL, Meta Status, Meta Description',
                'converted' => array(),
                'errors' => array(),
                'skipped' => array(),
            );
        }

        // Build output CSV header
        $output_headers = array( 'type', 'id', 'url', 'group', '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );
        $converted[] = $output_headers;

        foreach ( $lines as $line_num => $line ) {
            $line = trim( $line );
            if ( empty( $line ) ) {
                continue;
            }

            $row = str_getcsv( $line );
            
            if ( count( $row ) < max( $url_col, $title_col, $desc_col ) + 1 ) {
                $errors[] = "Line " . ( $line_num + 2 ) . ": Invalid row format";
                continue;
            }

            $url = trim( $row[ $url_col ] );
            $title = isset( $row[ $title_col ] ) ? trim( $row[ $title_col ] ) : '';
            $description = isset( $row[ $desc_col ] ) ? trim( $row[ $desc_col ] ) : '';
            $group = $groups_col !== false && isset( $row[ $groups_col ] ) ? trim( $row[ $groups_col ] ) : '';

            // Skip empty URLs
            if ( empty( $url ) ) {
                $skipped[] = "Line " . ( $line_num + 2 ) . ": Empty URL";
                continue;
            }

            // Look up ID from URL
            $lookup_result = $this->url_to_id( $url );

            if ( $lookup_result['found'] ) {
                $converted[] = array(
                    $lookup_result['type'],
                    $lookup_result['id'],
                    $url,
                    $group,
                    $title,
                    $description,
                );
            } else {
                $errors[] = "Line " . ( $line_num + 2 ) . ": " . $lookup_result['error'] . " (URL: {$url})";
            }
        }

        // Generate CSV output string
        $csv_output = $this->array_to_csv( $converted );

        return array(
            'success' => true,
            'csv_output' => $csv_output,
            'stats' => array(
                'total_rows' => count( $lines ),
                'converted' => count( $converted ) - 1, // Exclude header
                'errors' => count( $errors ),
                'skipped' => count( $skipped ),
            ),
            'errors' => $errors,
            'skipped' => $skipped,
        );
    }

    /**
     * Convert URL to WordPress post/term ID.
     *
     * @param string $url The URL to look up.
     * @return array Result with 'found', 'type', 'id', and 'error' keys.
     */
    private function url_to_id( $url ) {
        // Strip site URL to get the path
        $path = str_replace( $this->site_url, '', $url );
        $path = trim( $path, '/' );

        // Handle empty path (homepage)
        if ( empty( $path ) ) {
            $front_page_id = get_option( 'page_on_front' );
            if ( $front_page_id ) {
                return array(
                    'found' => true,
                    'type' => 'post',
                    'id' => $front_page_id,
                    'error' => null,
                );
            }
            return array(
                'found' => false,
                'type' => null,
                'id' => null,
                'error' => 'Homepage not found',
            );
        }

        // 1. Check if it's a WooCommerce product by slug
        $path_parts = explode( '/', $path );
        $last_segment = end( $path_parts );

        // Check for product (WooCommerce products)
        $product = $this->find_product_by_slug( $last_segment );
        if ( $product ) {
            return array(
                'found' => true,
                'type' => 'post',
                'id' => $product->ID,
                'error' => null,
            );
        }

        // 2. Check for brand pages (/brands-corner/brand-slug)
        if ( count( $path_parts ) >= 2 && $path_parts[0] === 'brands-corner' ) {
            $brand_slug = $path_parts[1];
            $brand = get_term_by( 'slug', $brand_slug, 'product_brand' );
            if ( $brand ) {
                return array(
                    'found' => true,
                    'type' => 'term',
                    'id' => $brand->term_id,
                    'error' => null,
                );
            }
            return array(
                'found' => false,
                'type' => null,
                'id' => null,
                'error' => "Brand not found: {$brand_slug}",
            );
        }

        // 3. Check for product category pages
        $category = $this->find_product_category_by_path( $path );
        if ( $category ) {
            return array(
                'found' => true,
                'type' => 'term',
                'id' => $category->term_id,
                'error' => null,
            );
        }

        // 4. Check for regular posts/pages using WordPress function
        $post_id = url_to_postid( $url );
        if ( $post_id ) {
            return array(
                'found' => true,
                'type' => 'post',
                'id' => $post_id,
                'error' => null,
            );
        }

        // 5. Try direct slug lookup for pages
        $page = get_page_by_path( $path );
        if ( $page ) {
            return array(
                'found' => true,
                'type' => 'post',
                'id' => $page->ID,
                'error' => null,
            );
        }

        // 6. Check for product by full path (e.g., /air-quality/product-slug)
        if ( count( $path_parts ) >= 2 ) {
            $product_slug = end( $path_parts );
            $product = $this->find_product_by_slug( $product_slug );
            if ( $product ) {
                return array(
                    'found' => true,
                    'type' => 'post',
                    'id' => $product->ID,
                    'error' => null,
                );
            }
        }

        return array(
            'found' => false,
            'type' => null,
            'id' => null,
            'error' => 'URL not found in database',
        );
    }

    /**
     * Find WooCommerce product by slug.
     *
     * @param string $slug Product slug.
     * @return WP_Post|null
     */
    private function find_product_by_slug( $slug ) {
        $args = array(
            'name' => $slug,
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
        );

        $posts = get_posts( $args );
        return ! empty( $posts ) ? $posts[0] : null;
    }

    /**
     * Find product category by path/slug.
     *
     * @param string $path URL path.
     * @return WP_Term|null
     */
    private function find_product_category_by_path( $path ) {
        // Try the path as-is first (single segment)
        $category = get_term_by( 'slug', $path, 'product_cat' );
        if ( $category ) {
            return $category;
        }

        // Try with path parts for hierarchical categories
        $parts = explode( '/', $path );
        foreach ( array_reverse( $parts ) as $slug ) {
            $category = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $category ) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Convert array to CSV string.
     *
     * @param array $data Array of rows.
     * @return string CSV content.
     */
    private function array_to_csv( $data ) {
        $output = fopen( 'php://temp', 'r+' );
        
        // Add UTF-8 BOM
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }
}
