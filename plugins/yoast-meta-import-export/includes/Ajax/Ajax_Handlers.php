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
    }

    /**
     * Handles the CSV export AJAX request.
     * Generates and downloads a CSV with all categories and brands from the website.
     */
    public function export_csv() {
        // Verify nonce and permissions
        check_ajax_referer( 'yoast_meta_ie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $csv_data = array();
        
        // Build headers for categories and brands export
        $headers = array( 
            'ID', 
            'Name', 
            'Slug', 
            'Description', 
            'Parent_ID', 
            'Parent_Name', 
            'Count', 
            'Taxonomy',
            'Taxonomy_Label'
        );
        $csv_data[] = $headers;

        // Define taxonomies to export: product categories and product brands
        $taxonomies_to_export = array(
            'product_cat' => 'Product Category',
            'product_brand' => 'Product Brand'
        );

        foreach ( $taxonomies_to_export as $taxonomy => $taxonomy_label ) {
            // Check if taxonomy exists
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $terms = get_terms( array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                // Get parent name if exists
                $parent_name = '';
                if ( $term->parent > 0 ) {
                    $parent_term = get_term( $term->parent, $taxonomy );
                    if ( $parent_term && ! is_wp_error( $parent_term ) ) {
                        $parent_name = $parent_term->name;
                    }
                }

                $row = array(
                    $term->term_id,
                    $term->name,
                    $term->slug,
                    $term->description,
                    $term->parent,
                    $parent_name,
                    $term->count,
                    $taxonomy,
                    $taxonomy_label
                );
                
                $csv_data[] = $row;
            }
        }

        // Output CSV with UTF-8 encoding
        $filename = 'categories-brands-export-' . date( 'Y-m-d' ) . '.csv';

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
            $type = $item['type'];
            $id = intval( $item['id'] );

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
}