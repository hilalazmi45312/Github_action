<?php
 /**
 * Dismissible notice.
 *
 * @since 4.1.0
 * @var string $message Message to display.
 * @var string $notice_name Notice name.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div id='message' class='updated woocommerce-message'>
	<a class='woocommerce-message-close notice-dismiss' href="<?php echo esc_url( add_query_arg( array( 'dey_dismiss_notice' => $notice_name, 'dey_notice_nonce' => wp_create_nonce( 'dey-dismiss-notice' ) ) ) ); ?>"><?php esc_html_e( 'Dismiss', 'delivery-slots-for-woocommerce' ); ?></a>
	<p><?php echo wp_kses_post( $message ); ?></p>
</div>