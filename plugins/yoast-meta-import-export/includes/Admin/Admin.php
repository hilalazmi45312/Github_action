<?php
namespace Yoast_Meta_IE\Admin;

class Admin {
    /**
     * Constructor. Sets up admin hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Adds the plugin's admin menu page.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Yoast Meta Import/Export', 'yoast-meta-ie' ),
            __( 'Yoast Meta IE', 'yoast-meta-ie' ),
            'manage_options',
            'yoast-meta-ie',
            array( $this, 'admin_page' ),
            'dashicons-admin-tools',
            30
        );
    }

    /**
     * Renders the admin page with the React app container.
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Yoast Meta Import/Export', 'yoast-meta-ie' ); ?></h1>
            <div id="yoast-meta-ie-app"></div>
        </div>
        <?php
    }

    /**
     * Enqueues scripts and styles for the admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_yoast-meta-ie' !== $hook ) {
            return;
        }

        // Check if build files exist, otherwise use development files
        $build_file = YOAST_META_IE_PLUGIN_DIR . 'build/index.asset.php';

        if ( file_exists( $build_file ) ) {
            $asset_file = include( $build_file );

            foreach( $asset_file["dependencies"] as $style ) {
                wp_enqueue_style( $style );
            }

            wp_register_script(
                "yoast-meta-ie-admin-script",
                YOAST_META_IE_PLUGIN_URL . 'build/index.js',
                array( "wp-api", ...$asset_file["dependencies"] ),
                $asset_file['version'],
                true
            );
            wp_enqueue_script("yoast-meta-ie-admin-script");

            wp_register_style(
                "yoast-meta-ie-admin-styles",
                YOAST_META_IE_PLUGIN_URL . 'build/style-index.css',
                [],
                $asset_file['version']
            );
            wp_enqueue_style("yoast-meta-ie-admin-styles");
        }

        // Localize script with admin data
        wp_localize_script('yoast-meta-ie-admin-script', 'yoastMetaIe', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yoast_meta_ie_nonce'),
            'translations' => $this->get_translations(),
            'postTypes' => $this->get_available_post_types(),
            'taxonomies' => $this->get_available_taxonomies()
        ));
    }

    /**
     * Get translations for React app
     */
    private function get_translations() {
        return array(
            'export' => __('Export', 'yoast-meta-ie'),
            'import' => __('Import', 'yoast-meta-ie'),
            'exportCsv' => __('Export CSV', 'yoast-meta-ie'),
            'exporting' => __('Exporting...', 'yoast-meta-ie'),
            'importCompleted' => __('Import completed. Updated ', 'yoast-meta-ie'),
            'postsTerms' => __(' posts/terms.', 'yoast-meta-ie'),
            'dryRun' => __('Dry Run (Preview Only)', 'yoast-meta-ie'),
            'forceImport' => __('Force Import (Overwrite Existing)', 'yoast-meta-ie'),
            'clearExisting' => __('Clear Existing Meta', 'yoast-meta-ie'),
            'dropCsvFile' => __('Drop CSV file here or click to select', 'yoast-meta-ie'),
            'selectedFile' => __('Selected file:', 'yoast-meta-ie'),
            'clear' => __('Clear', 'yoast-meta-ie'),
            'maxFileSize' => __('Maximum file size: 10MB', 'yoast-meta-ie'),
            'pleaseSelectCsv' => __('Please select a valid CSV file', 'yoast-meta-ie'),
            'fileTooLarge' => __('File is too large. Maximum size is 10MB', 'yoast-meta-ie'),
            'failedToRead' => __('Failed to read CSV file', 'yoast-meta-ie'),
            'exportCompleted' => __('Export completed successfully.', 'yoast-meta-ie'),
            'exportFailed' => __('Export failed.', 'yoast-meta-ie'),
            'exportError' => __('Export error: ', 'yoast-meta-ie'),
            'importCompletedWithErrors' => __('Import completed with errors: ', 'yoast-meta-ie'),
            'exportDescription' => __('Export all Yoast SEO meta fields for post types and taxonomies to a CSV file.', 'yoast-meta-ie'),
            'importDescription' => __('Import Yoast SEO meta fields from a CSV file. The file will be processed client-side.', 'yoast-meta-ie'),
        );
    }
    private function get_available_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $result = array();

        foreach ($post_types as $post_type) {
            // Skip attachments and other non-content types
            if (in_array($post_type->name, array('attachment', 'revision', 'nav_menu_item'))) {
                continue;
            }

            $result[] = array(
                'name' => $post_type->name,
                'label' => $post_type->label
            );
        }

        return $result;
    }

    /**
     * Get available taxonomies for export
     */
    private function get_available_taxonomies() {
        $taxonomies = get_taxonomies(array('public' => true), 'objects');
        $result = array();

        foreach ($taxonomies as $taxonomy) {
            $result[] = array(
                'name' => $taxonomy->name,
                'label' => $taxonomy->label
            );
        }

        return $result;
    }}
