<?php
/**
 * Gift card email (HTML)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/gift-card-email.php.
 *
 * @author  StoreApps
 * @package WooCommerce Smart Coupons/Templates/Emails
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $store_credit_label, $woocommerce_smart_coupon;

if ( ! isset( $email ) ) {
	$email = null;
}

if ( has_action( 'woocommerce_email_header' ) ) {
	do_action( 'woocommerce_email_header', $email_heading, $email_obj );
} else {
	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	} else {
		woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}
}
?>

	<div style="margin-bottom: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
		<div style="text-align: center; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<h2 style="color: #333; margin: 0 0 20px 0; font-family: Arial, sans-serif;">
				<?php
				/* translators: %s: Sender name */
				printf( esc_html__( 'Gift Card from %s', 'woocommerce-smart-coupons' ), esc_html( $sender ) );
				?>
			</h2>

			<?php if ( ! empty( $recipient_name ) ) : ?>
				<p style="font-size: 18px; color: #666; margin: 0 0 20px 0;">
					<?php
					/* translators: %s: Recipient name */
					printf( esc_html__( 'Dear %s,', 'woocommerce-smart-coupons' ), esc_html( $recipient_name ) );
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $gift_message ) ) : ?>
				<div style="background: #f8f9fa; padding: 0.93rem; border-left: 4px solid #007cba; margin: 1.25rem 0; font-style: italic; color: #555;">
					<?php echo wp_kses_post( wpautop( $gift_message ) ); ?>
				</div>
			<?php endif; ?>

			<div style="background: #f5f5f5; color: #000000; padding: 1.87rem; border-radius: 0.5rem; margin: 1.25rem 0;">
				<?php if ( ! empty( $custom_gift_image_url ) ) : ?>
					<div style="text-align: center; margin-bottom: 20px;">
						<img src="<?php echo esc_url( $custom_gift_image_url ); ?>"
							alt="<?php esc_attr_e( 'Gift card image', 'woocommerce-smart-coupons' ); ?>"
							style="max-width: 25rem; max-height: 18.75rem; border-radius: 0.5rem; inset: 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
					</div>
				<?php endif; ?>
				<div
					class="inline-flex <?php echo esc_attr( ( true === $is_percent ) ? '' : 'flex-row-reverse' ); ?> items-center">
					<?php
					if ( ! empty( $amount_symbol ) && true === $is_percent ) {
						?>
						<span><?php echo esc_html( ( ! empty( $coupon_amount ) ) ? $coupon_amount : '' ); ?></span>
						<span><?php echo esc_html( ( ! empty( $coupon_amount ) ) ? $amount_symbol : '' ); ?></span>
						<?php
					} else {
						?>
						<span><?php echo wp_kses_post( wc_price( $coupon_amount ) ); ?></span>
						<?php
					}
					?>
				</div>
				<div style="font-size: 1.25rem; font-weight: bold; margin: 0.93rem 0; letter-spacing: 2px; font-family: 'Courier New', monospace;">
					<?php echo esc_html( $coupon_code ); ?>
				</div>
			</div>

			<?php if ( function_exists( 'wc_get_page_id' ) ) : ?>
				<?php
				$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
				if ( $shop_url ) :
					?>
					<div style="margin: 30px 0;">
						<a href="<?php echo esc_url( $shop_url ); ?>"
							style="display: inline-block; background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
							<?php esc_html_e( 'Start Shopping', 'woocommerce-smart-coupons' ); ?>
						</a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( is_admin() ) { ?>
			<p style="color: #666; font-size: 14px; margin: 20px 0 0 0;">
				<?php esc_html_e( 'This is a preview of how your gift card will appear in the email.', 'woocommerce-smart-coupons' ); ?>
			</p>
			<?php } ?>
		</div>
	</div>

<?php
if ( has_action( 'woocommerce_email_footer' ) ) {
	do_action( 'woocommerce_email_footer', $email_obj );
} else {
	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template( 'emails/email-footer.php' );
	} else {
		woocommerce_get_template( 'emails/email-footer.php' );
	}
}
