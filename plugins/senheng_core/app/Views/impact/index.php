<?php
$current_user_id = get_current_user_id();
$getName = get_user_meta($current_user_id, 'cust_name', true);

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- ===========================  HEADER BLOCK  =========================== -->
<div class="affiliate-header">

    <h2 class="program-title"><?php _e('Senheng Affiliate Program', 'mytext'); ?></h2>

    <!-- pill-shaped selector (only one item for now) -->
    <div class="role-selector">
        <span class="role-item active"><?php _e('Staff', 'mytext'); ?></span>
        <!-- If you ever add more roles, duplicate <span> items here -->
    </div>

    <!-- logo – replace # with your logo source -->
    <img src="https://www.senheng.com.my/assets/images/5fe74b4867365a079a8c88ad561d1cd3.png" alt="Senheng Logo" class="program-logo" />

    <p class="program-intro">
        <?php _e("Your journey to rewards starts now! We're thrilled you're here to join the Senheng Affiliate Program! Let's get started!", 'mytext'); ?>
    </p>

    <p class="program-note">
        <?php _e('Provide the details as registered in your Senheng App account.', 'mytext'); ?>
    </p>

</div>
<!-- =======================  END HEADER BLOCK  =========================== -->

<!-- ===========================  FORM MARKUP  =========================== -->
<form class="woocommerce-EditAccountForm edit-account" method="post" id="impact-affiliate-signup-form">

    <?php if (!$getName): ?>
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
            <label><?php _e('First Name', 'mytext'); ?> <span class="required">*</span></label>
            <input type="text" name="first_name" value="" />
        </p>

        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
            <label><?php _e('Last Name', 'mytext'); ?> <span class="required">*</span></label>
            <input type="text" name="last_name" value="" />
        </p>
    <?php endif; ?>

    <p class="woocommerce-form-row form-row form-row-wide">
        <label><?php _e('Email', 'mytext'); ?><small> (A verification e-mail will be sent to this address)</small></label>
        <input type="email" name="email"
            value="<?php echo esc_attr($_POST['email'] ?? wp_get_current_user()->user_email); ?>" />
    </p>

    <p class="woocommerce-form-row form-row form-row-wide">
        <label><?php _e('Username', 'mytext'); ?><small> (This is your sign-in username)</small></label>
        <input class="readonly" type="text" name="username" value="" readonly />
    </p>

    <p class="woocommerce-form-row form-row form-row-wide">
        <label><?php _e('Staff Code', 'mytext'); ?><small> (Please ensure there is no spaces in the staff code)</small></label>
        <input type="text" name="staff_code" value="<?php echo esc_attr($_POST['staff_code'] ?? ''); ?>" oninput="this.value=this.value.replace(/\s/g,'')" />
    </p>

    <p class="woocommerce-form-row form-row">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="agree_terms" id="impact-affiliate-terms" type="checkbox"
                value="1" <?php checked(!empty($_POST['agree_terms'])); ?> />
            <span>By signing up, you agree to the <a href="<?php echo site_url('/my-account/affiliate-terms-conditions/'); ?>" style="color: red;">Terms and Conditions</a> of the Senheng Affiliate Program.</span>
        </label>
    </p>

    <?php wp_nonce_field('affiliate_signup', 'affiliate_signup_nonce'); ?>

    <p>
        <button type="button" class="impact-sign-up-button">
            <?php _e('Sign Up', 'mytext'); ?>
        </button>
    </p>
</form>
<!-- =======================  END FORM MARKUP  =========================== -->
<script src="https://unpkg.com/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const emailInput = document.querySelector('input[name="email"]');
        const usernameInput = document.querySelector('input[name="username"]');
        if (!emailInput || !usernameInput) return;

        // Set initial value on load
        usernameInput.value = emailInput.value;

        // Keep updating as user types
        emailInput.addEventListener('input', function() {
            usernameInput.value = this.value;
        });
    });
</script>