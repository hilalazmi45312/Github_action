<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
?>

<div class="crpro-unsubscribe-cont" style="width:100%;display:block;box-sizing:border-box;">
  <?php if( $crpro_unsubscribe_test ) : ?>
    <div class="crpro-unsubscribe-test" style="display:block;margin:1rem 0;">
      <span style="background-color:#ffe74d;color:#665900;padding:0.4rem 0.6rem;">
        <?php _e( 'Test mode: your email will not be unsubscribed', 'customer-reviews-woocommerce-pro' ); ?>
      </span>
    </div>
  <?php endif; ?>
  <div class="crpro-unsubscribe-success" style="display:block;margin:1rem 0;visibility:hidden;">
    <span style="background-color:#4eff9b;color:#00642C;padding:0.4rem 0.6rem;">
      <?php _e( 'You have successfully unsubscribed from further emails!', 'customer-reviews-woocommerce-pro' ); ?>
    </span>
  </div>
  <div class="crpro-unsubscribe-error" style="display:none;margin:1rem 0;">
    <span style="background-color:#ff8983;color:#800600;padding:0.4rem 0.6rem;">
      <?php _e( 'Sorry, an error occurred. Please report it to the website administrator.', 'customer-reviews-woocommerce-pro' ); ?>
    </span>
  </div>
  <label>
    <?php _e( 'Email', 'customer-reviews-woocommerce-pro' ); ?>
    <input type="email" class="crpro-unsubscribe-email" style="margin:1rem 0;" value="<?php echo $crpro_unsubscribe_email; ?>">
  </label>
  <button type="submit" class="crpro-unsubscribe-button" style="display:block;" data-nonce="<?php echo wp_create_nonce( 'crprounsubscribe' ); ?>">
    <?php _e( 'Unsubscribe', 'customer-reviews-woocommerce-pro' ) ?>
  </button>
</div>
