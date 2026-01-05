<?php

class BWFAN_Email_Advanced {

	private static $ins = null;

	private function __construct() {
		add_action( 'bwfan_wp_sendemail_setting_html', array( $this, 'bwfan_email_advanced_settings' ), 30 );
		add_action( 'bwfan_wp_sendemail_add_script', array( $this, 'bwfan_email_advanced_settings_scripts' ), 30 );
		add_filter( 'bwfan_sendemail_make_data', array( $this, 'bwfan_modify_from_email_data' ), 10, 2 );
	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param $action_obj BWFAN_Action
	 */
	public function bwfan_email_advanced_settings( $action_obj ) {
		?>
        <#
        is_email_from_settings = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'bwfan_email_from_settings')) ? 'checked' : '';
        bwfan_from_override = is_email_from_settings==='checked'?'':'bwfan-display-none';
        bwfan_from_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'bwfan_from_name')) ?data.actionSavedData.data.bwfan_from_name : '';
        bwfan_from_email = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'bwfan_from_email')) ? data.actionSavedData.data.bwfan_from_email : '';
        bwfan_reply_to = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'bwfan_reply_to')) ? data.actionSavedData.data.bwfan_reply_to : '';
        #>
        <div class="bwfan_email_advanced_settings bwfan-mb-15">
            <label for="bwfan_email_from_settings">
                <input type="checkbox" name="bwfan[{{data.action_id}}][data][bwfan_email_from_settings]" id="bwfan_email_from_settings" value="1" {{is_email_from_settings}}/>
				<?php
				esc_html_e( 'Override From Name, From Email & Reply To Email', 'wp-marketing-automations-pro' );
				$message = __( "This overrides the default email settings at FunnelKit Automations > Settings > Advanced tab. Leave unchecked for default values.", 'wp-marketing-automations-pro' );
				echo $action_obj->add_description( esc_html__( $message ), 'xl', 'left' ); //phpcs:ignore WordPress.Security.EscapeOutput
				?>
            </label>
            <div class="bwfan_email_from_html {{bwfan_from_override}}">
                <div class="bwfan-input-form clearfix">
                    <div class="bwfan-col-sm-4 bwfan-pl-0">
                        <span class="bwfan_label_input">From Name</span>
                    </div>
                    <div class="bwfan-col-sm-8 bwfan-pr-0">
                        <input type="text" name="bwfan[{{data.action_id}}][data][bwfan_from_name]" id="bwfan_from_name" class="bwfan-input-wrapper" value="{{bwfan_from_name}}"/>
                    </div>
                </div>
                <div class="bwfan-input-form clearfix">
                    <div class="bwfan-col-sm-4 bwfan-pl-0">
                        <span class="bwfan_label_input">From Email</span>
                    </div>
                    <div class="bwfan-col-sm-8 bwfan-pr-0">
                        <input type="text" name="bwfan[{{data.action_id}}][data][bwfan_from_email]" id="bwfan_from_email" class="bwfan-input-wrapper" value="{{bwfan_from_email}}"/>
                    </div>
                </div>
                <div class="bwfan-input-form clearfix">
                    <div class="bwfan-col-sm-4 bwfan-pl-0">
                        <span class="bwfan_label_input">Reply To Email</span>
                    </div>
                    <div class="bwfan-col-sm-8 bwfan-pr-0">
                        <input type="text" name="bwfan[{{data.action_id}}][data][bwfan_reply_to]" id="bwfan_reply_to" class="bwfan-input-wrapper" value="{{bwfan_reply_to}}"/>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	public function bwfan_email_advanced_settings_scripts() {
		?>
        <script>
            jQuery(document).on('click', '#bwfan_email_from_settings', function () {
                var is_checked = jQuery(this).is(':checked');
                if (is_checked) {
                    jQuery(".bwfan_email_from_html").show();
                } else {
                    jQuery(".bwfan_email_from_html").hide();
                }
            })
        </script>
		<?php
	}

	public function bwfan_modify_from_email_data( $data_to_set, $task_meta ) {

		$from_email_checked = isset( $task_meta['data']['bwfan_email_from_settings'] ) ? $task_meta['data']['bwfan_email_from_settings'] : '';

		if ( 1 !== absint( $from_email_checked ) ) {
			return $data_to_set;
		}

		$data_to_set['from_email']     = ! empty( $task_meta['data']['bwfan_from_email'] ) ? $task_meta['data']['bwfan_from_email'] : $data_to_set['from_email'];
		$data_to_set['from_name']      = ! empty( $task_meta['data']['bwfan_from_name'] ) ? $task_meta['data']['bwfan_from_name'] : $data_to_set['from_name'];
		$data_to_set['reply_to_email'] = ! empty( $task_meta['data']['bwfan_reply_to'] ) ? $task_meta['data']['bwfan_reply_to'] : $data_to_set['reply_to_email'];

		return $data_to_set;
	}

	/** shorten the url in email
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	public function email_shorten_url( $matches ) {

		return preg_replace_callback( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', function ( $matches ) {

			/** Do Shorten via the selected Shortening Service */
			if ( class_exists( 'BWFAN_Connectors_Common' ) && method_exists( BWFAN_Connectors_Common::class, 'get_shorten_url' ) ) {
				return BWFAN_Connectors_Common::get_shorten_url( $matches[0] );
			}

			/** Checking bitly class is exists or not **/
			if ( ! class_exists( 'BWFCO_Bitly' ) ) {
				return $matches[0];
			}

			/** If no service available try to use default method (Bitly) */
			return do_shortcode( '[bwfan_bitly_shorten]' . $matches[0] . '[/bwfan_bitly_shorten]' );
		}, $matches[0] );

	}
}

BWFAN_Email_Advanced::get_instance();
