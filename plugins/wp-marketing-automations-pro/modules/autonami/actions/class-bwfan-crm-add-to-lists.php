<?php

final class BWFAN_CRM_Add_To_List extends BWFAN_Action {

	private static $instance = null;

	private function __construct() {
		$this->action_name     = __( 'Add Contact to List', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds a contact to the selected list', 'wp-marketing-automations-pro' );
		$this->action_priority = 15;
		$this->support_v2      = true;
		$this->required_fields = array( 'lists', 'email' );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'list_id_options', $data );
		}
	}

	public function get_view_data() {
		$bwfcrm_lists = BWFCRM_Lists::get_lists();
		$lists        = array();
		if ( empty( $bwfcrm_lists ) ) {
			return $lists;
		}

		foreach ( $bwfcrm_lists as $key => $list ) {
			$lists[ $list['ID'] ] = $list['name'];
		}

		return $lists;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            selected_list_id = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'list_id')) ? data.actionSavedData.data.list_id : '';

            #>
            <label for="" class="bwfan-label-title">
				<?php
				echo esc_html__( 'Select List', 'wp-marketing-automations-pro' );
				$message = __( 'Select list to add contact to and if unable to locate then sync the connector.', 'wp-marketing-automations-pro' );
				echo $this->add_description( $message, '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
				echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
				?>
            </label>
            <select required id="" class="bwfan-input-wrapper bwfan-single-select" name="bwfan[{{data.action_id}}][data][list_id][]">
                <option value=""><?php echo esc_html__( 'Choose A List', 'wp-marketing-automations-pro' ); ?></option>
                <#
                if(_.has(data.actionFieldsOptions, 'list_id_options') && _.isObject(data.actionFieldsOptions.list_id_options) ) {
                _.each( data.actionFieldsOptions.list_id_options, function( value, key ){

                selected = _.contains(selected_list_id, key) ? 'selected' : '';
                #>
                <option value="{{key}}" {{selected}}>{{value}}</option>
                <# })
                }
                #>
            </select>
        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object BWFAN_Integration
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set            = array();
		$user_input_lists       = isset( $task_meta['data']['list_id'] ) ? $task_meta['data']['list_id'] : [];
		$data_to_set['email']   = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';
		$data_to_set['user_id'] = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : 0;
		$data_to_set['phone']   = isset( $task_meta['global']['phone'] ) ? $task_meta['global']['phone'] : '';
		$db_lists               = $this->get_view_data();
		$final_list_data        = array();

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data_to_set['email'] ) && ! empty( $data_to_set['user_id'] ) ) {
			$user_data            = get_userdata( $data_to_set['user_id'] );
			$data_to_set['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		foreach ( $user_input_lists as $fkey => $input_list ) {

			/** checking if the id already there than passing the id and value in array */
			if ( isset( $db_lists[ $input_list ] ) && ! empty( $db_lists[ $input_list ] ) ) {

				$final_list_data[ $fkey ] = array( 'id' => $input_list, 'value' => $db_lists[ $input_list ] );
				unset( $user_input_lists[ $fkey ] );
				continue;
			}

		}

		$data_to_set['lists'] = $final_list_data;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {

		$data_to_set            = array();
		$user_input_lists       = isset( $step_data['list_id'] ) && is_array( $step_data['list_id'] ) ? $step_data['list_id'] : [];
		$data_to_set['email']   = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$data_to_set['user_id'] = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$data_to_set['phone']   = isset( $automation_data['global']['phone'] ) ? $automation_data['global']['phone'] : '';

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data_to_set['email'] ) && ! empty( $data_to_set['user_id'] ) ) {
			$user_data            = get_userdata( $data_to_set['user_id'] );
			$data_to_set['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		$db_lists = $this->get_view_data();

		$final_list_data = array_map( function ( $list ) {
			$list['value'] = BWFAN_Common::decode_merge_tags( $list['name'] );
			unset( $list['name'] );

			return $list;
		}, $user_input_lists );

		$data_to_set['lists'] = $final_list_data;

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
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );

		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {
				return array(
					'status'  => 4,
					'message' => __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ),
				);
			}
		}
		$lists = $this->data['lists'];
		/** adding lists to contact **/
		$lists_added = $contact->add_lists( $lists );
		if ( $lists_added instanceof WP_Error ) {

			return array(
				'status'  => 4,
				'message' => __( $lists_added->get_error_message(), 'wp-marketing-automations-pro' ),
			);
		}

		if ( empty( $lists_added ) ) {
			return array(
				'status'  => 3,
				'message' => __( 'List applied already.', 'wp-marketing-automations-pro' ),
			);
		}

		return array(
			'status'  => 3,
			'message' => __( 'list added', 'wp-marketing-automations-pro' ),
		);
	}

	public function process_v2() {
		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {
				return $this->error_response( __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ) );
			}
		}

		/** Checking for comma in tag values */
		$lists = BWFAN_PRO_Common::check_for_comma_seperated( $this->data['lists'] );

		/** adding lists to contact **/
		$lists_added = $contact->add_lists( $lists );
		if ( $lists_added instanceof WP_Error ) {
			return $this->error_response( $lists_added->get_error_message() );
		}

		if ( empty( $lists_added ) ) {
			$this->success_message( __( 'List already assigned.', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_message( __( 'List assigned successfully.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				"id"                  => 'list_id',
				"label"               => __( 'Add list', 'wp-marketing-automations-pro' ),
				"type"                => 'search',
				'autocompleter'       => 'lists',
				"class"               => "bwfan-col-sm-9",
				"tip"                 => __( "Can add new or available lists.", 'wp-marketing-automations-pro' ),
				"hint"                => __( "Note: Text with comma will break the word and add individually", "wp-marketing-automations-pro" ),
				"allowFreeTextSearch" => true,
				"required"            => true,
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['list_id'] ) || empty( $data['list_id'] ) ) {
			return '';
		}
		$lists = [];
		foreach ( $data['list_id'] as $list ) {
			if ( ! isset( $list['name'] ) || empty( $list['name'] ) ) {
				continue;
			}
			$lists[] = $list['name'];
		}

		return $lists;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_CRM_Add_To_List';
