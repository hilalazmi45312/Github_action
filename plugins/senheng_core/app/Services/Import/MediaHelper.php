<?php
namespace SenhengCore\App\Services\Import;

class MediaHelper
{
    /**
     * Set featured image from URL
     * 
     * @param int    $postId  Product ID
     * @param string $url     Image URL
     */
    public static function setFeaturedImageFromUrl(int $postId, string $url): void
    {
        if (empty($url)) return;
        
        // Check if the product already has the same featured image
        $currentThumbnailId = get_post_thumbnail_id($postId);
        if ($currentThumbnailId) {
            $currentImageUrl = wp_get_attachment_url($currentThumbnailId);
            if ($currentImageUrl === $url) {
                Logger::info(
                    WP_CONTENT_DIR . '/uploads/senheng_import.log',
                    "Featured image already set for product ID $postId - skipping"
                );
                return;
            }
        }
        
        $attId = self::sideload($url, $postId);
        if ($attId) {
            set_post_thumbnail($postId, $attId);
        }
    }

    /**
     * Set product gallery from JSON array of URLs
     * 
     * @param int          $postId     Product ID
     * @param string|array $imageJson  JSON string or array of objects with 'url' key
     */
    public static function setGalleryFromJsonUrls(int $postId, $imageJson): void
    {
        $arr = is_string($imageJson) ? json_decode($imageJson, true) : $imageJson;
        if (!is_array($arr) || empty($arr)) return;
        
        // Check if the product already has the same gallery images
        $currentGallery = get_post_meta($postId, '_product_image_gallery', true);
        $currentGalleryIds = !empty($currentGallery) ? explode(',', $currentGallery) : [];
        
        // Get URLs of current gallery images
        $currentUrls = [];
        foreach ($currentGalleryIds as $id) {
            $url = wp_get_attachment_url($id);
            if ($url) {
                $currentUrls[] = $url;
            }
        }
        
        // Get new URLs from the JSON
        $newUrls = [];
        foreach ($arr as $row) {
            if (!empty($row['url'])) {
                $newUrls[] = $row['url'];
            }
        }
        
        // Compare arrays - if they're the same, skip update
        if (empty(array_diff($newUrls, $currentUrls)) && empty(array_diff($currentUrls, $newUrls))) {
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Gallery images already set for product ID $postId - skipping"
            );
            return;
        }
        
        $ids = [];
        foreach ($arr as $row) {
            if (empty($row['url'])) continue;
            $id = self::sideload($row['url'], $postId);
            if ($id) $ids[] = $id;
        }
        
        if ($ids) {
            update_post_meta($postId, '_product_image_gallery', implode(',', $ids));
        }
    }

    /**
     * Download and attach image to post
     * 
     * @param string $url     Image URL
     * @param int    $postId  Post ID to attach to
     * @return int|null       Attachment ID or null on failure
     */
    private static function sideload(string $url, int $postId): ?int
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Extract filename from URL for checking
        $filename = basename(parse_url($url, PHP_URL_PATH));
        
        // PRIORITY 1: Check by filename first (most efficient for reusing existing images)
        $existingAttId = self::findExistingImageByFilename($filename);
        if ($existingAttId) {
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Reusing existing image by filename: '$filename' (ID: $existingAttId)"
            );
            return $existingAttId;
        }

        // PRIORITY 2: Check if URL is already imported for this product
        $existingAttId = self::findExistingImageByUrl($url, $postId);
        if ($existingAttId) {
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Reusing existing image by URL: '$url' (ID: $existingAttId)"
            );
            return $existingAttId;
        }

        $tmp = download_url($url, 300); // 5 minute timeout
        if (is_wp_error($tmp)) {
            Logger::error(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Failed to download image '$url': " . $tmp->get_error_message()
            );
            return null;
        }

        $file = [
            'name'     => basename(parse_url($url, PHP_URL_PATH)),
            'type'     => mime_content_type($tmp),
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];

        $id = media_handle_sideload($file, $postId);
        if (is_wp_error($id)) {
            @unlink($tmp);
            Logger::error(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Failed to sideload image '$url': " . $id->get_error_message()
            );
            return null;
        }
        
        // Store original URL as meta for future lookups
        update_post_meta($id, '_source_url', $url);
        
        Logger::info(
            WP_CONTENT_DIR . '/uploads/senheng_import.log',
            "Downloaded new image: '$url' -> '$filename' (ID: $id)"
        );
        
        return (int)$id;
    }

    /**
     * Find existing attachment by filename
     * 
     * @param string $filename  Image filename
     * @return int|null         Attachment ID or null
     */
    private static function findExistingImageByFilename(string $filename): ?int
    {
        global $wpdb;
        
        // Check by post_title (WordPress stores filename as post_title for attachments)
        $attId = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND post_title = %s 
            LIMIT 1",
            pathinfo($filename, PATHINFO_FILENAME) // Remove extension for title match
        ));
        
        if ($attId) {
            return (int)$attId;
        }
        
        // Also check by guid containing the filename (for cases where filename is in the URL)
        $attId = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND guid LIKE %s 
            LIMIT 1",
            '%' . $wpdb->esc_like($filename) . '%'
        ));
        
        return $attId ? (int)$attId : null;
    }

    /**
     * Find existing attachment by source URL
     * 
     * @param string $url     Source URL
     * @param int    $postId  Product ID
     * @return int|null       Attachment ID or null
     */
    private static function findExistingImageByUrl(string $url, int $postId): ?int
    {
        global $wpdb;
        
        // Check by _source_url meta
        $attId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_source_url' AND meta_value = %s 
            LIMIT 1",
            $url
        ));
        
        if ($attId) {
            return (int)$attId;
        }
        
        // Check by guid (for previously imported images)
        $attId = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND guid = %s 
            LIMIT 1",
            $url
        ));
        
        return $attId ? (int)$attId : null;
    }
}

