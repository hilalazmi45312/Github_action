<?php

defined( 'ABSPATH' ) || exit;

$currentVendor   = get_current_user_id();
$waId            = get_user_meta( $currentVendor, 'dokan_whatsapp_id', true );
$waAccountNumber = '';
$waButtonLabel   = '';
$waTitle         = '';
$waPredefinedText = '';

if ( ! empty( $waId ) ) {
	$waAccountInfo = get_post_meta( $waId, 'nta_wa_account_info', true );
	if ( ! empty( $waAccountInfo ) ) {
		$waAccountNumber = ! empty( $waAccountInfo['number'] ) ? $waAccountInfo['number'] : '';
		$waTitle         = ! empty( $waAccountInfo['title'] ) ? $waAccountInfo['title'] : '';
		$waPredefinedText = ! empty( $waAccountInfo['predefinedText'] ) ? $waAccountInfo['predefinedText'] : '';
	}
	$waButtonStyles = get_post_meta( $waId, 'nta_wa_button_styles', true );
	if ( ! empty( $waButtonStyles ) ) {
		$waButtonLabel = ! empty( $waButtonStyles['label'] ) ? $waButtonStyles['label'] : '';
	}
}

$waButtonPosition = get_user_meta( $currentVendor, 'whatsapp_button_position', true );
$waDisplayWidget  = get_user_meta( $currentVendor, 'whatsapp_display_floating_widget', true );

$selectedPositions = array(
	'before_atc'              => 'Before Add to Cart button',
	'after_atc'               => 'After Add to Cart button',
	'after_short_description' => 'After short description',
	'after_long_description'  => 'After long description',
);

?>
<form method="post" id="whatsapp-setting-form"  action="" class="dokan-form-vertical">
	<div class="dokan-form-group">
		<label class="dokan-w12 dokan-control-label" for="dokan_whatsapp_number"><strong><?php esc_html_e( 'Account Number or group chat URL', 'ninjateam-whatsapp' ); ?></strong></label>
		<div class="dokan-w7 dokan-text-left">
			<input id="dokan_whatsapp_number" required value="<?php echo esc_html( $waAccountNumber ); ?>" name="dokan_whatsapp_number" class="dokan-form-control" type="text">
		</div>
		<div class="dokan-w12 dokan-control-label">
			<span><?php esc_html_e( 'Refer to ', 'ninjateam-whatsapp' ); ?><a href="https://faq.whatsapp.com/en/general/21016748" target="_blank"><?php echo esc_url( 'https://faq.whatsapp.com/en/general/21016748' ); ?></a><?php esc_html_e( ' for a detailed explanation.', 'ninjateam-whatsapp' ); ?></span>
		</div>
	</div>
	<div class="dokan-form-group">
		<label class="dokan-w12 dokan-control-label" for="dokan_whatsapp_title"><strong><?php esc_html_e( 'Title', 'ninjateam-whatsapp' ); ?></strong></label>
		<div class="dokan-w7 dokan-text-left">
			<input id="dokan_whatsapp_title" value="<?php echo esc_html( $waTitle ); ?>" name="dokan_whatsapp_title" class="dokan-form-control" type="text">
		</div>
	</div>
	<div class="dokan-form-group">
		<label class="dokan-w12 dokan-control-label" for="dokan_whatsapp_predefined_text"><strong><?php esc_html_e( 'Predefined Text', 'ninjateam-whatsapp' ); ?></strong></label>
		<div class="dokan-w7 dokan-text-left">
			<input id="dokan_whatsapp_predefined_text" value="<?php echo esc_html( $waPredefinedText ); ?>" name="dokan_whatsapp_predefined_text" class="dokan-form-control" type="text">
		</div>
	</div>
	<div class="dokan-form-group">
		<label class="dokan-w12 dokan-control-label" for="dokan_whatsapp_button_label"><strong><?php esc_html_e( 'Button Label', 'ninjateam-whatsapp' ); ?></strong></label>
		<div class="dokan-w7 dokan-text-left">
			<input id="dokan_whatsapp_button_label" name="dokan_whatsapp_button_label" required class="dokan-form-control" type="text"
			<?php
			if ( ! empty( $waButtonLabel ) ) {
				echo 'value="' . esc_html( $waButtonLabel ) . '"';
			} else {
				echo 'value="' . esc_html__( 'Need Help? Chat with us', 'ninjateam-whatsapp' ) . '"'; }
			?>
			placeholder="<?php echo esc_html_e( 'Need help? Chat via WhatsApp', 'ninjateam-whatsapp' ); ?>">
		</div>
	</div>
	<div class="dokan-form-group">
		<div class="dokan-w7 nta-wa-switch-container">
			<label class="dokan-control-label"><strong><?php esc_html_e( 'Button position', 'ninjateam-whatsapp' ); ?></strong></label>
				<div class="nta-wa-dropdown">
					<select id="dokan-whatsapp-position-select">
						<?php foreach ( $selectedPositions as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php echo $key === $waButtonPosition ? 'selected' : ''; ?>><?php esc_html_e( $value, 'ninjateam-whatsapp' ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
		</div>
	</div>
	<div class="dokan-form-group">
		<div class="dokan-w7 nta-wa-switch-container">
			<label class="dokan-control-label" for="dokan_whatsapp_floating_widget"><strong><?php esc_html_e( 'Display WhatsApp Floating Widget', 'ninjateam-whatsapp' ); ?></strong></label>
			<div class="nta-wa-switch-control">
				<input type="checkbox" id="dokan_whatsapp_floating_widget" name="dokan_whatsapp_floating_widget" 
				<?php
				if ( ! empty( $waDisplayWidget ) ) {
					?>
					checked="checked" <?php } ?> >
				<label for="nta-wa-switch"
				<?php
				if ( ! empty( $waDisplayWidget ) ) {
					?>
					class="green" <?php } ?> >
				</label>
			</div>
		</div>
	</div>
	<div class="dokan-form-group">
		<div class="dokan-w7 nta-wa-submit-btn">
			<button id="dokan_update_whatsapp_settings" class="dokan-btn dokan-btn-theme"><?php esc_html_e( 'Update Settings', 'ninjateam-whatsapp' ); ?><span class="dokan-wa-loading-icon"></span></button>
		</div>
	</div>
</form>