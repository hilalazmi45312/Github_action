<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var $event BWFAN_Wp_Sendemail
 */
?>

<div class="bwfan_email_tracking bwfan-mb-15">
    <label for="bwfan_append_utm">
        <input type="checkbox" name="bwfan[{{data.action_id}}][data][append_utm]" id="bwfan_append_utm" value="1" {{is_append_utm}}/>
		<?php
		echo esc_html__( 'Add UTM parameters to the links', 'wp-marketing-automations-pro' );
		$message = __( 'Add UTM parameters in all the links present in the email.', 'wp-marketing-automations-pro' );
		echo $event->add_description( $message, 'xl' ); //phpcs:ignore WordPress.Security.EscapeOutput
		?>
    </label>
    <div class="bwfan_utm_sources {{show_utm_parameters}}">
        <div class="bwfan-input-form clearfix">
            <div class="bwfan-col-sm-4 bwfan-pl-0"><span class="bwfan_label_input"><?php echo esc_html__( 'UTM Source', 'wp-marketing-automations-pro' ); ?></span></div>
            <div class="bwfan-col-sm-8 bwfan-pr-0">
                <input type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][utm_source]" value="{{entered_utm_source}}" id="bwfan_utm_source"/>
            </div>
        </div>
        <div class="bwfan-input-form clearfix">
            <div class="bwfan-col-sm-4 bwfan-pl-0"><span class="bwfan_label_input"><?php echo esc_html__( 'UTM Medium', 'wp-marketing-automations-pro' ); ?></span></div>
            <div class="bwfan-col-sm-8 bwfan-pr-0">
                <input type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][utm_medium]" value="{{entered_utm_medium}}" id="bwfan_utm_medium"/>
            </div>
        </div>
        <div class="bwfan-input-form clearfix">
            <div class="bwfan-col-sm-4 bwfan-pl-0"><span class="bwfan_label_input"><?php echo esc_html__( 'UTM Campaign', 'wp-marketing-automations-pro' ); ?></span></div>
            <div class="bwfan-col-sm-8 bwfan-pr-0">
                <input type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][utm_campaign]" value="{{entered_utm_campaign}}" id="bwfan_utm_campaign"/>
            </div>
        </div>
        <div class="bwfan-input-form clearfix">
            <div class="bwfan-col-sm-4 bwfan-pl-0"><span class="bwfan_label_input"><?php echo esc_html__( 'UTM Term', 'wp-marketing-automations-pro' ); ?></span></div>
            <div class="bwfan-col-sm-8 bwfan-pr-0">
                <input type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][utm_term]" value="{{entered_utm_term}}" id="bwfan_utm_term"/>
            </div>
        </div>
    </div>
</div>
