<?php
/**
 * Plugin Name: Media Auto Sync
 * Description: Automatically sync uploaded media to another WordPress site via REST API.
 * Version: 1.0.0
 * Author: Cloone Corportation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CONFIGURATION
 */
define( 'MEDIA_SYNC_TARGET_ENDPOINT', 'https://site-b.com/wp-json/wp/v2/media' );
define( 'MEDIA_SYNC_AUTH_USER', 'api-user' );
define( 'MEDIA_SYNC_AUTH_PASS', 'APPLICATION_PASSWORD' );

/**
 * Trigger when attachment is created
 */
add_action( 'add_attachment', 'mas_schedule_media_sync' );

function mas_schedule_media_sync( $attachment_id ) {

    // Only sync images
    if ( ! wp_attachment_is_image( $attachment_id ) ) {
        return;
    }

    // Prevent infinite loop
    if ( get_post_meta( $attachment_id, '_mas_synced', true ) ) {
        return;
    }

    // Schedule async job
    wp_schedule_single_event(
        time() + 10,
        'mas_sync_media_event',
        [ $attachment_id ]
    );
}

/**
 * Background job handler
 */
add_action( 'mas_sync_media_event', 'mas_sync_media_to_remote_site' );

function mas_sync_media_to_remote_site( $attachment_id ) {

    $file_path = get_attached_file( $attachment_id );

    if ( ! file_exists( $file_path ) ) {
        return;
    }

    $file_name = basename( $file_path );
    $mime_type = get_post_mime_type( $attachment_id );

    $response = wp_remote_post(
        MEDIA_SYNC_TARGET_ENDPOINT,
        [
            'timeout' => 20,
            'headers' => [
                'Authorization'       => 'Basic ' . base64_encode( MEDIA_SYNC_AUTH_USER . ':' . MEDIA_SYNC_AUTH_PASS ),
                'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
                'Content-Type'        => $mime_type,
            ],
            'body' => file_get_contents( $file_path ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( $code >= 200 && $code < 300 ) {
        update_post_meta( $attachment_id, '_mas_synced', true );
    }
}