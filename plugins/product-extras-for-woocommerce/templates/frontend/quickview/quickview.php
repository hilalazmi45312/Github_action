<?php
/**
 * Template for displaying QuickView
 * @since 3.26.15
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="pewc-quickview-<?php echo $field_id.'_'.$child_product_id; ?>" class="pewc-quickview-product-wrapper">
    <?php wc_get_template_part( 'content', 'single-product' );
    printf(
        '<a href="#" class="pewc-close-quickview"><span>%s</span></a>',
        apply_filters( 'pewc_close_quickview_icon', '&times;' )
    ); ?>
</div>
