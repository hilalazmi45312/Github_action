<?php
/**
 * Template for displaying enhanced tooltips
 * @since 3.21.0
 */

$post = get_post( $post_id );
if( ! isset( $post->post_content ) ) {
    return;
}

$content = $post->post_content;
if( ! $content ) {
    return;
} ?>
<div id="pewc-enhanced-tooltip-<?php echo esc_attr( $post_id ); ?>" class="pewc-enhanced-tooltip" role="dialog">
    <div class="pewc-enhanced-tooltip-wrapper">
        <button class="pewc-enhanced-close" type="button">×</button>
        <div class="pewc-enhanced-tooltip-inner">
            <?php printf( '<h3>%s</h3>',
                $post->post_title
            ); ?>
            <?php echo apply_filters( 'the_content', $content ); ?>
        </div>
    </div>
</div>
