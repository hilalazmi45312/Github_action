<?php
namespace Yoast_Meta_IE\Ajax;

/**
 * Handles AJAX requests for exporting and importing Yoast meta data.
 */
class Ajax_Handlers {
    /**
     * List of all Yoast SEO meta fields to export/import.
     *
     * @var array
     */
    private $yoast_fields = array(
        // Core SEO.
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
        '_yoast_wpseo_keywordsynonyms',
        '_yoast_wpseo_focuskeywords',
        '_yoast_wpseo_canonical',
        '_yoast_wpseo_bctitle',

        // Robots/meta.
        '_yoast_wpseo_meta-robots-noindex',
        '_yoast_wpseo_meta-robots-nofollow',
        '_yoast_wpseo_meta-robots-adv',

        // Advanced.
        '_yoast_wpseo_redirect',
        '_yoast_wpseo_is_cornerstone',
        '_yoast_wpseo_primary_category',

        // Open Graph.
        '_yoast_wpseo_opengraph-title',
        '_yoast_wpseo_opengraph-description',
        '_yoast_wpseo_opengraph-image',
        '_yoast_wpseo_opengraph-image-id',

        // Twitter.
        '_yoast_wpseo_twitter-title',
        '_yoast_wpseo_twitter-description',
        '_yoast_wpseo_twitter-image',
        '_yoast_wpseo_twitter-image-id',

        // Schema-ish fields sometimes used by Yoast.
        '_yoast_wpseo_schema_page_type',
        '_yoast_wpseo_schema_article_type',
    );

    /**
     * Constructor. Registers AJAX action hooks.
     */
    public function __construct() {
        add_action( 'wp_ajax_yoast_meta_ie_export', array( $this, 'export_csv' ) );
        add_action( 'wp_ajax_yoast_meta_ie_import_batch', array( $this, 'import_batch' ) );
        add_action( 'wp_ajax_yoast_meta_ie_convert_csv', array( $this, 'convert_csv' ) );
    }

    /**
     * Handles the CSV export AJAX request.
     * Generates and downloads a CSV with all Yoast meta for posts and terms.
     */
    public function export_csv() {
        // Verify nonce and permissions
        check_ajax_referer( 'yoast_meta_ie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $export_options = isset( $_POST['export_options'] ) ? json_decode( stripslashes( $_POST['export_options'] ), true ) : array();
        $selected_post_types = isset( $export_options['postTypes'] ) ? $export_options['postTypes'] : array('post', 'page');
        $selected_taxonomies = isset( $export_options['taxonomies'] ) ? $export_options['taxonomies'] : array('category', 'post_tag');

        $csv_data = array();
        // Build headers: ID, Type, Type_Value, Title/Name, URL, then all Yoast fields
        $headers = array( 'ID', 'Type', 'Type_Value', 'Title/Name', 'URL' );
        $headers = array_merge( $headers, $this->yoast_fields );
        $csv_data[] = $headers;

        // Export posts (selected post types except attachments)
        if ( ! empty( $selected_post_types ) ) {
            $args = array(
                'post_type' => $selected_post_types,
                'posts_per_page' => -1,
                'post_status' => 'any',
                'post_type__not_in' => array('attachment'), // Exclude media attachments
            );

            $posts = get_posts( $args );

            foreach ( $posts as $post ) {
                $row = array( $post->ID, 'post', $post->post_type, $post->post_title, get_permalink( $post->ID ) );
                foreach ( $this->yoast_fields as $field ) {
                    $row[] = get_post_meta( $post->ID, $field, true );
                }
                $csv_data[] = $row;
            }
        }

        // Export taxonomies (selected taxonomies)
        if ( ! empty( $selected_taxonomies ) ) {
            foreach ( $selected_taxonomies as $taxonomy ) {
                $terms = get_terms( array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                ) );

                foreach ( $terms as $term ) {
                    $term_link = get_term_link( $term );
                    $row = array( $term->term_id, 'term', $taxonomy, $term->name, is_wp_error( $term_link ) ? '' : $term_link );
                    foreach ( $this->yoast_fields as $field ) {
                        $row[] = get_term_meta( $term->term_id, $field, true );
                    }
                    $csv_data[] = $row;
                }
            }
        }

        // Output CSV with UTF-8 encoding
        $filename = 'yoast-meta-export-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // Add UTF-8 BOM to ensure proper encoding
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

        foreach ( $csv_data as $row ) {
            fputcsv( $output, $row );
        }

        fclose( $output );
        exit;
    }

    /**
     * Handles the batch import AJAX request.
     * Updates Yoast meta for a batch of posts/terms.
     */
    public function import_batch() {
        // Verify nonce and permissions
        check_ajax_referer( 'yoast_meta_ie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $batch = isset( $_POST['batch'] ) ? json_decode( stripslashes( $_POST['batch'] ), true ) : array();
        $options = isset( $_POST['options'] ) ? json_decode( stripslashes( $_POST['options'] ), true ) : array();

        if ( empty( $batch ) ) {
            wp_send_json_error( 'No data to import' );
        }

        $dry_run = isset( $options['dryRun'] ) ? $options['dryRun'] : false;
        $force = isset( $options['force'] ) ? $options['force'] : false;
        $clear_existing = isset( $options['clearExisting'] ) ? $options['clearExisting'] : false;

        $updated = 0;
        $errors = array();

        foreach ( $batch as $item ) {
            $type = isset( $item['type'] ) ? trim( $item['type'] ) : '';
            $id_val = isset( $item['id'] ) ? trim( $item['id'] ) : '';

            // Validate strict requirements: ID and Type must be present
            if ( empty( $id_val ) || empty( $type ) ) {
                $errors[] = "Row skipped: Missing required ID or Type";
                continue;
            }

            $id = intval( $id_val );

            if ( $type === 'post' ) {
                // Check if post exists
                if ( ! get_post( $id ) ) {
                    $errors[] = "Post ID {$id} does not exist";
                    continue;
                }

                // Clear existing meta if requested
                if ( $clear_existing && ! $dry_run ) {
                    foreach ( $this->yoast_fields as $field ) {
                        delete_post_meta( $id, $field );
                    }
                }

                // Update all Yoast fields for the post
                foreach ( $this->yoast_fields as $field ) {
                    if ( isset( $item[$field] ) && $item[$field] !== '' ) {
                        if ( ! $dry_run ) {
                            update_post_meta( $id, $field, sanitize_text_field( $item[$field] ) );
                        }
                    } elseif ( $clear_existing && ! $dry_run ) {
                        // Clear field if it exists but CSV has empty value
                        delete_post_meta( $id, $field );
                    }
                }
            } elseif ( $type === 'term' ) {
                // Check if term exists
                if ( ! get_term( $id ) ) {
                    $errors[] = "Term ID {$id} does not exist";
                    continue;
                }

                // Clear existing meta if requested
                if ( $clear_existing && ! $dry_run ) {
                    foreach ( $this->yoast_fields as $field ) {
                        delete_term_meta( $id, $field );
                    }
                }

                // Update all Yoast fields for the term
                foreach ( $this->yoast_fields as $field ) {
                    if ( isset( $item[$field] ) && $item[$field] !== '' ) {
                        if ( ! $dry_run ) {
                            update_term_meta( $id, $field, sanitize_text_field( $item[$field] ) );
                        }
                    } elseif ( $clear_existing && ! $dry_run ) {
                        // Clear field if it exists but CSV has empty value
                        delete_term_meta( $id, $field );
                    }
                }
            } else {
                $errors[] = "Invalid type for ID {$id}";
                continue;
            }

            $updated++;
        }

        wp_send_json_success( array(
            'updated' => $updated,
            'errors' => $errors,
            'dry_run' => $dry_run,
            'options' => $options,
        ) );
    }

    /**
     * Handles the CSV conversion AJAX request.
     * Converts URL-based CSV to ID-based format for import.
     */
    public function convert_csv() {
        // Output buffering to catch and discard any PHP warnings/notices that might corrupt the JSON response
        ob_start();

        // Verify nonce and permissions
        check_ajax_referer( 'yoast_meta_ie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            ob_end_clean();
            wp_send_json_error( 'Insufficient permissions' );
        }

        $batch = isset( $_POST['batch'] ) ? json_decode( stripslashes( $_POST['batch'] ), true ) : array();

        if ( empty( $batch ) ) {
            ob_end_clean();
            wp_send_json_error( 'No data to convert' );
        }

        $converted = array();
        $errors = array();
        $skipped = array();

        foreach ( $batch as $item ) {
            try {
                $group = isset( $item['groups'] ) ? trim( $item['groups'] ) : '';
                $url = isset( $item['url'] ) ? trim( $item['url'] ) : '';
                $title = isset( $item['meta_status'] ) ? trim( $item['meta_status'] ) : '';
                $description = isset( $item['meta_description'] ) ? trim( $item['meta_description'] ) : '';

                if ( empty( $url ) ) {
                    $errors[] = "Row missing URL";
                    continue;
                }

                // Extract slug from URL safely
                // Use @ to suppress potential malformed URL warnings found in some CSVs
                $parsed_url = @parse_url( $url );
                
                if ( $parsed_url === false || ! isset( $parsed_url['path'] ) ) {
                    // Fallback for root domains or weird URLs
                    $path = '';
                } else {
                    $path = trim( $parsed_url['path'], '/' );
                }
                
                $path_parts = explode( '/', $path );
                $slug = end( $path_parts );

                // Decode slug (URLs might be encoded)
                $slug = urldecode( $slug );

                $id = 0;
                $type = '';
                $type_value = '';
                $name = '';
                
                // Special handling for homepage/root URL (empty slug)
                if ( empty( $slug ) ) {
                    $front_page_id = get_option( 'page_on_front' );
                    if ( $front_page_id ) {
                        $post = get_post( $front_page_id );
                        if ( $post ) {
                            $id = $post->ID;
                            $type = 'post';
                            $type_value = 'page';
                            $name = $post->post_title;
                        }
                    }
                    
                    if ( $id === 0 ) {
                        // If still 0, it really couldn't be found or it's not the homepage
                         $skipped[] = array(
                            'url' => $url,
                            'slug' => '',
                            'group' => $group,
                            'reason' => 'Root URL but no static homepage set'
                        );
                        // Continue to adding formatted row even if skipped
                    }
                } else {
                    // Normal slug lookup

                    // Determine type based on Groups column or URL structure
                    $group_lower = strtolower( $group );

                    if ( strpos( $group_lower, 'brand' ) !== false || strpos( $path, 'brands-corner' ) !== false ) {
                        // Brand page - lookup in product_brand taxonomy
                        $term = get_term_by( 'slug', $slug, 'product_brand' );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $id = $term->term_id;
                            $type = 'term';
                            $type_value = 'product_brand';
                            $name = $term->name;
                        }
                    } elseif ( strpos( $group_lower, 'category' ) !== false ) {
                        // Category page - lookup in product_cat taxonomy
                        $term = get_term_by( 'slug', $slug, 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $id = $term->term_id;
                            $type = 'term';
                            $type_value = 'product_cat';
                            $name = $term->name;
                        }
                    } elseif ( strpos( $group_lower, 'product' ) !== false ) {
                        // Product page - lookup in product post type
                        $post = get_page_by_path( $slug, OBJECT, 'product' );
                        if ( $post ) {
                            $id = $post->ID;
                            $type = 'post';
                            $type_value = 'product';
                            $name = $post->post_title;
                        }
                    } else {
                        // Try to auto-detect: first try product, then page, then post
                        $post = get_page_by_path( $slug, OBJECT, 'product' );
                        if ( $post ) {
                            $id = $post->ID;
                            $type = 'post';
                            $type_value = 'product';
                            $name = $post->post_title;
                        } else {
                            $post = get_page_by_path( $slug, OBJECT, 'page' );
                            if ( $post ) {
                                $id = $post->ID;
                                $type = 'post';
                                $type_value = 'page';
                                $name = $post->post_title;
                            } else {
                                $post = get_page_by_path( $slug, OBJECT, 'post' );
                                if ( $post ) {
                                    $id = $post->ID;
                                    $type = 'post';
                                    $type_value = 'post';
                                    $name = $post->post_title;
                                }
                            }
                        }
                    }
                    
                    if ( $id === 0 ) {
                        $skipped[] = array(
                            'url' => $url,
                            'slug' => $slug,
                            'group' => $group,
                            'reason' => 'No matching post or term found'
                        );
                    }
                } // end else (slug not empty)

                // ---------------------------------------------------------
                // REDIRECT FALLBACK LOGIC
                // ---------------------------------------------------------
                if ( $id === 0 && ! empty( $url ) ) {
                    // Determine EXPECTED context from Groups or URL structure before redirect
                    $expected_type = 'unknown';
                    if ( strpos( $group_lower, 'brand' ) !== false || strpos( $path, 'brands-corner' ) !== false ) {
                        $expected_type = 'brand';
                    } elseif ( strpos( $group_lower, 'category' ) !== false ) {
                        $expected_type = 'category';
                    } elseif ( strpos( $group_lower, 'product' ) !== false ) {
                        $expected_type = 'product';
                    }

                    // If we haven't found a match yet, check if the URL redirects (e.g. Yoast Premium redirects)
                    // We use wp_remote_head with redirection disabled to catch the 3xx response
                    $response = wp_remote_head( $url, array(
                        'redirection' => 0,
                        'timeout'     => 5,
                        'sslverify'   => false, // Skip SSL verification for internal tests to avoid issues
                    ) );

                    if ( ! is_wp_error( $response ) ) {
                        $response_code = wp_remote_retrieve_response_code( $response );
                        // Check for 301, 302, 307etc.
                        if ( in_array( $response_code, array( 301, 302, 303, 307, 308 ) ) ) {
                            $location = wp_remote_retrieve_header( $response, 'location' );
                            
                            if ( ! empty( $location ) ) {
                                // We found a redirect! Parse the new Location URL and try lookup again.
                                $redirect_parsed = @parse_url( $location );
                                if ( $redirect_parsed && isset( $redirect_parsed['path'] ) ) {
                                    $redirect_path = trim( $redirect_parsed['path'], '/' );
                                    $redirect_parts = explode( '/', $redirect_path );
                                    $redirect_slug = end( $redirect_parts );
                                    $redirect_slug = urldecode( $redirect_slug );

                                    // RULE 2: Check for generic roots if it was a Brand or Category
                                    // If we expected a specific brand but got redirected to "/brands-corner" (root), ignore it.
                                    $generic_roots = array( 'brands-corner', 'brands', 'brand', 'category', 'categories', 'shop', 'product', 'products' );
                                    if ( ($expected_type === 'brand' || $expected_type === 'category') && in_array( $redirect_slug, $generic_roots ) ) {
                                        // Skip looking up generic roots
                                        $redirect_slug = ''; 
                                    }

                                    if ( ! empty( $redirect_slug ) ) {
                                        // RE-TRY MATCHING with new slug
                                        
                                        // Try Brand
                                        $match_type = '';
                                        $term = get_term_by( 'slug', $redirect_slug, 'product_brand' );
                                        if ( $term && ! is_wp_error( $term ) ) {
                                            $id = $term->term_id;
                                            $type = 'term';
                                            $type_value = 'product_brand';
                                            $name = $term->name;
                                            $match_type = 'brand';
                                        } 
                                        // Try Category
                                        elseif ( ($term = get_term_by( 'slug', $redirect_slug, 'product_cat' )) && ! is_wp_error( $term ) ) {
                                            $id = $term->term_id;
                                            $type = 'term';
                                            $type_value = 'product_cat';
                                            $name = $term->name;
                                            $match_type = 'category';
                                        }
                                        // Try Product/Page/Post
                                        else {
                                            // Priority: Product -> Page -> Post
                                            $post = get_page_by_path( $redirect_slug, OBJECT, 'product' );
                                            if ( ! $post ) {
                                                $post = get_page_by_path( $redirect_slug, OBJECT, 'page' );
                                            }
                                            if ( ! $post ) {
                                                $post = get_page_by_path( $redirect_slug, OBJECT, 'post' );
                                            }

                                            if ( $post ) {
                                                $id = $post->ID;
                                                $type = 'post';
                                                $type_value = $post->post_type; // Capture actual post type
                                                $name = $post->post_title;
                                                $match_type = 'product';
                                            }
                                        }

                                        // RULE 1: Check match against expected type
                                        // If expected Product but got Brand/Category, INVALIDATE.
                                        if ( $id > 0 && $expected_type === 'product' && ($match_type === 'brand' || $match_type === 'category') ) {
                                            $id = 0; // Reset
                                            $type = '';
                                            $type_value = '';
                                            $name = '';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // ---------------------------------------------------------
                // END REDIRECT FALLBACK
                // ---------------------------------------------------------

                // Get the converted URL (permalink) if we found a match
                $converted_url = '';
                if ( $id > 0 ) {
                    if ( $type === 'post' ) {
                        $converted_url = get_permalink( $id );
                    } elseif ( $type === 'term' ) {
                        $converted_url = get_term_link( (int) $id, $type_value );
                        if ( is_wp_error( $converted_url ) ) {
                            $converted_url = '';
                        }
                    }
                }

                // Build converted row - include ALL items, even unmatched ones (with empty values)
                $converted[] = array(
                    'id' => $id > 0 ? $id : '',
                    'type' => $type,
                    'type_value' => $type_value,
                    'title_name' => $name,
                    '_yoast_wpseo_title' => $title,
                    '_yoast_wpseo_metadesc' => $description,
                    'original_url' => $url,
                    'converted_url' => $converted_url,
                );
            } catch ( \Exception $e ) {
                $errors[] = "Exception processing URL {$url}: " . $e->getMessage();
                // Continue to next item
            }
        }

        // Clean output buffer
        $ob_output = ob_get_clean();
        if ( ! empty( $ob_output ) ) {
             // Return warnings/notices to frontend
             $errors[] = "PHP Warnings/Notices: " . strip_tags($ob_output);
        }

        wp_send_json_success( array(
            'converted' => $converted,
            'skipped' => $skipped,
            'errors' => $errors,
            'stats' => array(
                'total' => count( $batch ),
                'converted' => count( $converted ),
                'skipped' => count( $skipped ),
                'errors' => count( $errors ),
            ),
        ) );
    }
}