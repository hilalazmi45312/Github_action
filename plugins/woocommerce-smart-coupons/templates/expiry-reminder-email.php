<?php
/**
 * Expiry Reminder Email Content
 *
 * @version     1.0.0
 * @package     woocommerce-smart-coupons/templates/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $woocommerce_smart_coupon;

if ( has_action( 'woocommerce_email_header' ) ) {
	do_action( 'woocommerce_email_header', $email_heading, $email_obj );
} else {
	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	} else {
		woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}
}
/* translators: %s: Coupon code */
echo sprintf( esc_html__( 'To redeem your discount use coupon code %s during checkout or click on the following coupon:', 'woocommerce-smart-coupons' ), '<strong><code>' . esc_html( $coupon_code ) . '</code></strong>' );

echo wp_kses(
	$coupon_html,
	array(
		'div'    => array(
			'style' => array(),
			'class' => array(),
			'title' => array(),
		),
		'a'      => array(
			'href'   => array(),
			'target' => array(),
			'style'  => array(),
			'class'  => array(),
		),
		'style'  => array(),
		'span'   => array(
			'style' => array(),
			'class' => array(),
		),
		'p'      => array(),
		'strong' => array(),
		'code'   => array(),
	)
);

$site_url = ! empty( $url ) ? $url : home_url();
?>
<center>
	<a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html__( 'Visit store', 'woocommerce-smart-coupons' ); ?></a>
	<?php
	$is_print = get_option( 'smart_coupons_is_print_coupon', 'yes' );
	$is_print = apply_filters( 'wc_sc_email_show_print_link', wc_string_to_bool( $is_print ), array( 'source' => $woocommerce_smart_coupon ) );
	if ( true === $is_print ) {
		$print_coupon_url = add_query_arg(
			array(
				'print-coupons' => 'yes',
				'source'        => 'wc-smart-coupons',
				'coupon-codes'  => $coupon_code,
			),
			home_url()
		);
		?>
		|
		<a href="<?php echo esc_url( $print_coupon_url ); ?>" target="_blank"><?php echo esc_html_x( 'Print coupon', 'expiry email print coupon', 'woocommerce-smart-coupons' ); ?></a>
		<?php
	}
	?>
</center>

<div style="clear:both;"></div>

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
