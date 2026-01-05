<?php

final class BWFAN_WLM_User_Move_Level extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Move User to Level', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'user_id', 'members_level' );
		$this->support_v2      = true;
		$this->action_priority = 30;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$membership_levels = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'membership_level', $membership_levels );
		}
	}

	/**
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
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_members_level = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'members_level')) ? data.actionSavedData.data.members_level : '';
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?> clearfix bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Membership Level', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][members_level]">
                    <#
                    if(_.has(data.actionFieldsOptions, 'membership_level') && _.isObject(data.actionFieldsOptions.membership_level) ) {
                    _.each( data.actionFieldsOptions.membership_level, function( value, key ){
                    selected = (key == selected_members_level) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
                <div class="clearfix bwfan_field_desc">
                </div>
            </div>
        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set                  = array();
		$data_to_set['members_level'] = $task_meta['data']['members_level'];
		$user_id                      = BWFAN_PRO_Common::maybe_get_user_id_from_task_meta( $task_meta['global'] );

		if ( ! empty( $user_id ) ) {
			$data_to_set['user_id'] = $user_id;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                  = array();
		$data_to_set['members_level'] = isset( $step_data['members_level'][0]['id'] ) ? $step_data['members_level'][0]['id'] : 0;
		$user_id                      = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		if ( 0 === absint( $user_id ) ) {
			$email   = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
			$user    = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$user_id = $user instanceof WP_User ? $user->ID : 0;
		}

		$data_to_set['user_id'] = $user_id;

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();
		if ( true === $result ) {
			return array(
				'status'  => 3,
				'message' => 'Membership level is assigned / updated to user\'s membership',
			);
		}

		return $result;
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$membership_level = $this->data['members_level'];
		$user             = get_userdata( $this->data['user_id'] );

		if ( false === $user ) {
			return array(
				'status'  => 4,
				'message' => __( 'User does not exists', 'wp-marketing-automations-pro' ),
			);
		}

		$member_level_moved = $this->move_user_to_membership_levels( $this->data['user_id'], $membership_level );

		return $member_level_moved;
	}

	public function move_user_to_membership_levels( $user_id, $membership_level ) {
		global $WishListMemberInstance;
		$move_from_level = $WishListMemberInstance->get_membership_levels( $user_id );

		if ( $move_from_level[0] === $membership_level ) {
			return array(
				'status'  => 4,
				'message' => __( 'User cannot move to same level', 'wp-marketing-automations-pro' ),
			);
		}

		$level_data = array(
			'wlm_level_from' => $move_from_level[0],
			'wlm_levels'     => $membership_level,
			'schedule_date'  => '',
			'userids'        => $user_id,
			'level_action'   => 'move_user_level'
		);

		$WishListMemberInstance->schedule_user_level( $level_data );

		return true;
	}

	public function process_v2() {
		$membership_level = $this->data['members_level'];
		$user             = get_userdata( $this->data['user_id'] );

		if ( false === $user ) {
			return $this->skipped_response( __( 'User does not exists', 'wp-marketing-automations-pro' ) );
		}

		$this->move_user_to_membership_levels( $this->data['user_id'], $membership_level );

		return $this->success_message( __( 'User level cancelled.', 'wp-marketing-automations-pro' ) );
	}

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

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['members_level'] ) || empty( $data['members_level'] ) ) {
			return '';
		}
		$members_levels = [];
		foreach ( $data['members_level'] as $members_level ) {
			if ( ! isset( $members_level['name'] ) || empty( $members_level['name'] ) ) {
				continue;
			}
			$members_levels[] = $members_level['name'];
		}

		return $members_levels;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WLM_User_Move_Level';