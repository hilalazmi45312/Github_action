<?php

#[AllowDynamicProperties]
final class BWFAN_WLM_Expired_User_Level extends BWFAN_Event {
	private static $instance = null;
	public $user_id = null;
	public $level_id = null;
	public $email = null;
	public $expire_date = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'wc_customer', 'wlm_wishlist', 'wlm_user_level_expire_date' );
		$this->event_name             = esc_html__( 'User Membership Level Expired', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a user membership level get expired.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'wlm',
			'wc_customer',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast',
			'wp_user'
		);
		$this->optgroup_label         = esc_html__( 'WishList Member', 'wp-marketing-automations-pro' );
		$this->support_lang           = true;
		$this->priority               = 40;
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wishlistmember_expire_user_levels', [ $this, 'expired_level_from_user' ], 20, 3 );

	}

	/**
	 *
	 * @param int $user_id
	 * @param array $expired_levels expired level ids
	 */
	public function expired_level_from_user( $user_id, $expired_levels ) {
		$this->process( $user_id, $expired_levels );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param int $user_id
	 * @param array $expired_level
	 */
	public function process( $user_id, $expired_level ) {
		$data      = $this->get_default_data();
		$user_data = get_userdata( $user_id );
		if ( ! $user_data instanceof WP_User ) {
			return;
		}

		$data['user_id'] = $user_id;

		if ( is_array( $expired_level ) ) {
			$expired_level = array_values( $expired_level );
		}

		$data['level_id'] = $expired_level[0];

		global $WishListMemberInstance;
		$wpm_levels = $WishListMemberInstance->GetOption( 'wpm_levels' );

		$data['expire_date'] = strtotime( $wpm_levels[ $data['level_id'] ]['expire_date'] );
		$data['email']       = $user_data->user_email;
		$this->send_async_call( $data );
	}

	/** localise wishlist members level options */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$membership_levels = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'membership_levels', $membership_levels );
		}
	}

	/** get wishlist members levels
	 * @return array
	 */
	public function get_view_data() {
		$levels  = wlmapi_get_levels();
		$options = array();
		if ( ! empty( $levels['levels']['level'] ) ) {
			foreach ( $levels['levels']['level'] as $level ) {
				$options[ $level['id'] ] = $level['name'];
			}
		}

		return $options;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_member_level = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'members_level')) ? data.eventSavedData.members_level : '';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Membship Level', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[members_level]">
                    <option value="any"><?php echo esc_html__( 'Any', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'membership_levels') && _.isObject(data.eventFieldsOptions.membership_levels) ) {
                    _.each( data.eventFieldsOptions.membership_levels, function( value, key ){
                    selected = (key == selected_member_level) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>

        </script>
		<?php
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->level_id, 'level_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->get_email_event(), $this->get_user_id_event() ), 'bwf_customer' );
		BWFAN_Core()->rules->setRulesData( $this->expire_date, 'expire_date' );
	}

	public function get_email_event() {

		return is_email( $this->email ) ? $this->email : null;
	}

	public function get_user_id_event() {

		return $this->user_id;
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();
		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['user_id']     = $this->user_id;
		$data_to_send['global']['level_id']    = $this->level_id;
		$data_to_send['global']['email']       = $this->get_email_event();
		$data_to_send['global']['expire_date'] = $this->expire_date;

		return $data_to_send;
	}

	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		ob_start();

		$new_level_url = admin_url();
		$new_level_url = add_query_arg( array(
			'page'     => 'WishListMember',
			'wl'       => 'setup/levels',
			'level_id' => $global_data['level_id'] . '#levels_access-' . $global_data['level_id'],
		), $new_level_url );
		?>
        <li>
            <strong><?php echo esc_html__( 'Membership Level:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo $new_level_url; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['level_id'] ); ?></a>
        </li>

        <li>
            <strong><?php echo esc_html__( 'Expired On:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( date( 'd-m-Y', $global_data['expire_date'] ) ); ?>
        </li>

        <li>
            <strong><?php echo esc_html__( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {

		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'level_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['level_id'] ) ) ) {
			$set_data = array(
				'user_id'     => intval( $task_meta['global']['user_id'] ),
				'email'       => $task_meta['global']['email'],
				'level_id'    => $task_meta['global']['level_id'],
				'expire_date' => $task_meta['global']['expire_date']
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {

		$user_id           = BWFAN_Common::$events_async_data['user_id'];
		$new_level         = BWFAN_Common::$events_async_data['level_id'];
		$this->user_id     = $user_id;
		$this->email       = BWFAN_Common::$events_async_data['email'];
		$this->level_id    = $new_level;
		$this->expire_date = BWFAN_Common::$events_async_data['expire_date'];

		return $this->run_automations();
	}

	/** validate event data before creating task
	 *
	 * @param $automations_arr
	 *
	 * @return mixed
	 */
	public function validate_event_data_before_creating_task( $automations_arr ) {
		$automations_arr_temp = $automations_arr;
		foreach ( $automations_arr as $automation_id => $automation_data ) {
			if ( 'any' !== $automation_data['event_meta']['members_level'] && absint( $automation_data['event_meta']['members_level'] ) !== absint( $this->level_id ) ) {
				unset( $automations_arr_temp[ $automation_id ] );
			}
		}

		return $automations_arr_temp;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		$saved_level_ids = isset( $automation_data['event_meta']['members_level'] ) && is_array( $automation_data['event_meta']['members_level'] ) ? array_column( $automation_data['event_meta']['members_level'], 'id' ) : [];
		if ( ! in_array( $automation_data['level_id'], $saved_level_ids, false ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$user_id                        = BWFAN_Common::$events_async_data['user_id'];
		$new_level                      = BWFAN_Common::$events_async_data['level_id'];
		$this->user_id                  = $user_id;
		$this->email                    = BWFAN_Common::$events_async_data['email'];
		$this->level_id                 = $new_level;
		$this->expire_date              = BWFAN_Common::$events_async_data['expire_date'];
		$automation_data['user_id']     = $this->user_id;
		$automation_data['email']       = $this->email;
		$automation_data['level_id']    = $this->level_id;
		$automation_data['expire_date'] = $this->expire_date;

		return $automation_data;
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {

		return [
			[
				"id"                  => 'members_level',
				"label"               => __( 'Membership Level', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wlm_levels',
					'slug'      => 'wlm_levels',
					'labelText' => 'level'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Level is required", 'wp-marketing-automations-pro' ),
				"multiple"            => false
			]
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
	return 'BWFAN_WLM_Expired_User_Level';
}
