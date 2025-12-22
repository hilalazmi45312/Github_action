<?php
/**
 * Gift card email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/gift-card-email.php.
 *
 * @author  StoreApps
 * @package WooCommerce Smart Coupons/Templates/Emails/Plain
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( $email_heading ) . ' =\n\n';

/* translators: %s: Sender name */
echo sprintf( esc_html__( 'Gift Card from %s', 'woocommerce-smart-coupons' ), esc_html( $sender_name ) ) . "\n\n";

if ( ! empty( $recipient_name ) ) :
	/* translators: %s: Recipient name */
	echo sprintf( esc_html__( 'Dear %s,', 'woocommerce-smart-coupons' ), esc_html( $recipient_name ) ) . "\n\n";
endif;

if ( ! empty( $gift_message ) ) :
	echo esc_html__( 'Personal Message:', 'woocommerce-smart-coupons' ) . "\n";
	echo '"' . esc_html( wp_strip_all_tags( $gift_message ) ) . '"' . "\n\n";
endif;

echo esc_html__( 'YOUR GIFT CARD DETAILS', 'woocommerce-smart-coupons' ) . "\n";
echo "==============================\n\n";

echo esc_html__( 'Gift Card Code:', 'woocommerce-smart-coupons' ) . ' ' . esc_html( $gift_card_code ) . '\n';
/* translators: %s: Gift card amount */
echo sprintf( esc_html__( 'Value: %s', 'woocommerce-smart-coupons' ), esc_html( wp_strip_all_tags( $gift_card_amount ) ) ) . "\n\n";

if ( function_exists( 'wc_get_page_id' ) ) :
	$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
	if ( $shop_url ) :
		echo esc_html__( 'Shop now:', 'woocommerce-smart-coupons' ) . ' ' . esc_url( $shop_url ) . '\n\n';
	endif;
endif;

if ( ! empty( $custom_image_url ) ) :
	echo esc_html__( 'Custom Image:', 'woocommerce-smart-coupons' ) . ' ' . esc_url( $custom_image_url ) . '\n\n';
endif;

if ( is_admin() ) {
	echo esc_html__( 'This is a preview of how your gift card will appear in the email.', 'woocommerce-smart-coupons' ) . "\n\n";
}

// phpcs:ignore
echo apply_filters( 'woocommerce_email_footer_text', esc_html( get_option( 'woocommerce_email_footer_text' ) ) );
