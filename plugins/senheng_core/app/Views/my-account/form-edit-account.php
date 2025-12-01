<?php

/**
 * Custom Edit Account Profile (readonly)
 *
 * Override WooCommerce default account form.
 * Place this file in: your-plugin/app/Views/my-account/form-edit-account.php
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');
?>

<h2><?php esc_html_e('Profile', 'woocommerce'); ?></h2>
<p style="color:red; font-size:14px; margin-bottom:20px;">
	**<?php esc_html_e('If you would like to update your profile details, please contact our customer support team via call or WhatsApp us at +6011 3600 4040', 'woocommerce'); ?>
</p>

<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>

	<?php do_action('woocommerce_edit_account_form_start'); ?>

	<?php
	$user           = wp_get_current_user();
	$full_name      = get_user_meta($user->ID, 'cust_name', true) ?: $user->display_name;
	$ic_no          = get_user_meta($user->ID, 'cust_icno', true);
	$mobile_number  = get_user_meta($user->ID, 'cust_contact', true);
	$email          = get_user_meta($user->ID, 'cust_email', true) ?: $user->user_email;
	?>

	<p class="woocommerce-form-row form-row form-row-wide">
		<label for="account_full_name"><?php esc_html_e('Full Name', 'woocommerce'); ?></label>
		<input type="text" class="woocommerce-Input input-text" id="account_full_name" value="<?php echo esc_attr($full_name); ?>" readonly />
	</p>

	<p class="woocommerce-form-row form-row form-row-wide">
		<label for="account_icno"><?php esc_html_e('IC Number', 'woocommerce'); ?></label>
		<input type="text" class="woocommerce-Input input-text" id="account_icno" value="<?php echo esc_attr($ic_no); ?>" readonly />
	</p>

	<p class="woocommerce-form-row form-row form-row-wide">
		<label for="account_mobile_number"><?php esc_html_e('Contact Number', 'woocommerce'); ?></label>
		<input type="text" class="woocommerce-Input input-text" id="account_mobile_number" value="<?php echo esc_attr($mobile_number); ?>" readonly />
	</p>

	<p class="woocommerce-form-row form-row form-row-wide">
		<label for="account_email"><?php esc_html_e('Email', 'woocommerce'); ?></label>
		<input type="email" class="woocommerce-Input input-text" id="account_email" value="<?php echo esc_attr($email); ?>" readonly />
	</p>

	<?php do_action('woocommerce_edit_account_form_fields'); ?>
	<?php do_action('woocommerce_edit_account_form'); ?>
	<?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<?php do_action('woocommerce_after_edit_account_form'); ?>