<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Assigned_List extends BWFAN_Event {
	private static $instance = null;
	public $lists = null;
	public $list_id = null;
	public $contact_id = null;
	public $list_name = null;
	public $email = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwfan_crm_lists', 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Added to List', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after an list assigned to contact.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 20;
		$this->customer_email_tag     = '{{contact_email}}';
		$this->v2                     = true;
		$this->is_goal                = true;
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfan_contact_added_to_lists', array( $this, 'process' ), 10, 2 );
	}

	public function process( $lists, $contact ) {
		$this->lists = [];
		if ( ! is_array( $lists ) && $lists instanceof BWFCRM_Lists ) {
			$this->lists[] = $lists;
		} else {
			$this->lists = $lists;
		}

		$this->contact_id = ( $contact instanceof BWFCRM_Contact ) ? $contact->get_id() : 0;
		$this->email      = ( $contact instanceof BWFCRM_Contact ) ? $contact->contact->get_email() : '';

		$this->run_automations();
	}

	public function run_automations() {
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );

		BWFAN_PRO_Common::disable_run_v2_automation_immediately();

		$automation_actions = [];
		foreach ( $this->lists as $list ) {
			if ( ! $list instanceof BWFCRM_Lists ) {
				continue;
			}
			$this->list_id   = $list->get_id();
			$this->list_name = $list->get_name();

			/** v2 run starts */
			$contact_data_v2 = array(
				'contact_id' => absint( $this->contact_id ),
				'email'      => $this->email,
				'list_id'    => $this->list_id,
				'list_name'  => $this->list_name
			);
			BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );

			/** v1 run starts */
			if ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) {
				continue;
			}
			/** Checking for event settings */
			$automation_arr = $this->validate_event_data_before_creating_task( $this->automations_arr );
			if ( ! is_array( $automation_arr ) || count( $automation_arr ) === 0 ) {
				continue;
			}
			foreach ( $automation_arr as $automation_id => $automation_data ) {
				if ( $this->get_slug() !== $automation_data['event'] || 0 !== intval( $automation_data['requires_update'] ) ) {
					continue;
				}
				$ran_actions = $this->handle_single_automation_run( $automation_data, $automation_id );

				$automation_actions[ $automation_id ] = $ran_actions;
			}
		}

		return $automation_actions;
	}

	/**
	 * Validating form id after submission with the selected form id in the event
	 *
	 * @param $automations_arr
	 *
	 * @return mixed
	 */
	public function validate_event_data_before_creating_task( $automations_arr ) {
		$automations_arr_temp = $automations_arr;
		foreach ( $automations_arr as $automation_id => $automation_data ) {
			$list      = ! empty( $automation_data['event_meta']['list'] ) ? $automation_data['event_meta']['list'] : 'any';
			$runs      = isset( $automation_data['event_meta']['bwfan_automation_run'] ) ? $automation_data['event_meta']['bwfan_automation_run'] : 'multiple';
			$run_count = 0;
			if ( 'once' === $runs ) {
				$run_count = BWFAN_Model_Automations::get_contact_automation_run_count( $automation_id, $this->contact_id );
			}

			/** selected any list and runs multiple times selected  */
			if ( 'any' === $list && 'multiple' === $runs ) {
				continue;
			}

			/** selected list id and run multiple times selected */
			if ( absint( $this->list_id ) === absint( $list ) && 'multiple' === $runs ) {
				continue;
			}

			if ( 'any' === $list && 'once' === $runs && 0 === $run_count ) {
				continue;
			}

			if ( absint( $this->list_id ) === absint( $list ) && 'once' === $runs && 0 === $run_count ) {
				continue;
			}

			unset( $automations_arr_temp[ $automation_id ] );

		}

		return $automations_arr_temp;
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
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['list_id']    = $this->list_id;
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['list_name']  = $this->list_name;
		$data_to_send['global']['email']      = $this->email;

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

		$list_id     = isset( $global_data['list_id'] ) ? $global_data['list_id'] : 0;
		$list_url    = admin_url( "admin.php?page=autonami&path=/manage/lists&s=" . $global_data['list_name'] );
		$contact_url = admin_url( "admin.php?page=autonami&path=/contact/" . $global_data['contact_id'] );
		?>
        <li>
            <strong><?php echo esc_html__( 'List Id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo esc_attr( $list_url ); ?>"><?php echo esc_html__( $list_id ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'List Name:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['list_name'] ); ?></span>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Contact_id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a href="<?php echo esc_attr( $contact_url ); ?>"><?php echo esc_html__( $global_data['contact_id'] ); ?></a>
        </li>
		<?php
		return ob_get_clean();
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/javascript" defer>
            jQuery(document).ready(function ($) {
                $(document.body).on('click', '.item_modify_trigger', function () {
                    setTimeout(function () {
                        $('#bwfcrm-select-list').select2({
                            placeholder: 'Search list',
                            minimumInputLength: 2,
                            tags: false,
                            data: [],
                            escapeMarkup(m) {
                                return m;
                            },
                            ajax: {
                                url: ajaxurl,
                                dataType: 'json',
                                type: 'POST',
                                delay: 250,
                                data(term) {
                                    return {
                                        search_term: term,
                                        action: 'bwfan_select2ajax',
                                        type: $('#bwfcrm-select-list').attr('data-search'),
                                        _wpnonce: bwfanParams.ajax_nonce,
                                    };
                                },
                                processResults: function (data) {

                                    var options = [];
                                    if (data) {
                                        // data is the array of arrays, and each of them contains ID and the Label of the option
                                        $.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
                                            options.push({id: text.id, text: text.text});
                                        });
                                    }
                                    return {
                                        results: options
                                    };
                                },
                                cache: true
                            }
                        });
                    }, 300)

                });

                $('body').on('change', '.bwfan-list-search', function () {
                    var temp_list = {id: $(this).val(), name: $(this).find(':selected').text()};

                    $(this).parent().find('.bwfan_searched_list').val(JSON.stringify(temp_list));
                });
            });
        </script>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            selected_list = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'list')) ? data.eventSavedData.list : '';
            selected_runs = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'runs')) ? data.eventSavedData.runs : '';

            searched_list = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'searched_list')) ? data.eventSavedData.searched_list : '';
            if(!_.isEmpty(searched_list)) {
            try {
            searched_list = JSON.parse(searched_list);
            }
            catch(e) {
            //Do Nothing
            }
            }
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Select List', 'wp-marketing-automations-pro' ); ?>
                </label>
                <select id="bwfcrm-select-list" data-search="list" data-search-text="<?php esc_attr_e( 'Select Tag', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-list-search bwfan-input-wrapper" name="event_meta[list]">
                    <#
                    if(_.size(searched_list) >0) {
                    temp_selected_list = _.isObject(searched_list) ? searched_list : JSON.parse(searched_list);
                    if(temp_selected_list.id == selected_list){
                    #>
                    <option value="{{temp_selected_list.id}}" selected>{{temp_selected_list.name}}</option>
                    <#
                    }
                    }

                    stringify_searched_list = _.isObject(searched_list) ? JSON.stringify(searched_list) :
                    searched_list;
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_list" name="event_meta[searched_list]" value="{{stringify_searched_list}}"/>
            </div>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-15 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Runs', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[runs]">
                    <option value="once" {{
                    'once' == selected_runs?'selected':''
                    }}><?php esc_html_e( 'Once', 'wp-marketing-automations-pro' ); ?></option>
                    <option value="multiple_times" {{
                    'multiple_times' == selected_runs?'selected':''
                    }}><?php esc_html_e( 'Multiple Times', 'wp-marketing-automations-pro' ); ?></option>
                </select>
            </div>
        </script>
		<?php
	}

	public function get_user_id_event() {

		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', absint( $this->email ) );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'list_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['list_id'] ) ) ) {
			$set_data = array(
				'list_id'    => intval( $task_meta['global']['list_id'] ),
				'email'      => $task_meta['global']['email'],
				'contact_id' => intval( $task_meta['global']['contact_id'] ),
				'list_name'  => $task_meta['global']['list_name'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->list_id, 'list_id' );
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->list_name, 'list_name' );
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_list( $data );
	}

	public function validate_list( $data ) {
		if ( ! isset( $data['list_id'] ) || ! isset( $data['contact_id'] ) ) {
			return false;
		}

		$crm_list = new BWFCRM_Lists( $data['list_id'] );
		if ( ! $crm_list->is_exists() ) {
			$this->message_validate_event = __( 'List not exist.', 'wp-marketing-automations-pro' );

			return false;
		}

		$contact = new BWFCRM_Contact( $data['contact_id'] );
		$lists   = $contact->get_lists();

		if ( ! in_array( $data['list_id'], $lists ) ) {
			$this->message_validate_event = __( 'Contact is not in the list.', 'wp-marketing-automations-pro' );

			return false;
		}

		return true;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		if ( ! isset( $automation_data['event_meta'] ) ) {
			return true;
		}

		/** If key is not set, then we will assign */
		if ( ! isset( $automation_data['event_meta']['list-contains'] ) ) {
			if ( ! isset( $automation_data['event_meta']['list'] ) || empty( $automation_data['event_meta']['list'] ) ) {
				$automation_data['event_meta']['list-contains'] = 'any';
			}
		}

		if ( isset( $automation_data['event_meta']['list-contains'] ) && 'any' === $automation_data['event_meta']['list-contains'] ) {
			return true;
		}

		/** Selected lists */
		$list_ids = array_map( function ( $list ) {
			return intval( $list['id'] );
		}, $automation_data['event_meta']['list'] );

		if ( empty( $list_ids ) ) {
			return true;
		}

		$assigned_list = intval( $automation_data['list_id'] );

		return in_array( $assigned_list, $list_ids, true );
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'list-contains',
				'label'       => __( 'List Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Specific List', 'wp-marketing-automations-pro' ),
						'value' => 'selected_list'
					],
					[
						'label' => __( 'Any List', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => true,
				"description" => ""
			],
			[
				'id'            => 'list',
				'label'         => __( 'Select List', 'wp-marketing-automations-pro' ),
				'type'          => 'search',
				'autocompleter' => 'lists',
				'class'         => 'bwfan-col-sm-9',
				'placeholder'   => '',
				'required'      => true,
				'toggler'       => [
					'fields' => [
						[
							'id'    => 'list-contains',
							'value' => 'selected_list',
						]
					]
				],
			],
		];
	}

	public function get_view_data() {
		$all_lists = BWFCRM_Lists::get_lists();

		if ( empty( $all_lists ) ) {
			return array();
		}

		$list_data = array();
		foreach ( $all_lists as $list ) {
			$list_data[ $list['ID'] ] = $list['name'];
		}

		return $list_data;
	}

	public function validate_goal_settings( $automation_settings, $automation_data ) {
		$list_contain = isset( $automation_settings['list-contains'] ) ? $automation_settings['list-contains'] : 'any';
		if ( 'any' === $list_contain ) {
			return true;
		}

		/** Goal settings lists not set */
		if ( empty ( $automation_settings['list'] ) || 0 === count( $automation_settings['list'] ) ) {
			return false;
		}

		/** Goal settings lists */
		$lists = array_column( $automation_settings['list'], 'id' );
		if ( empty( $lists ) ) {
			return false;
		}

		return in_array( $automation_data['list_id'], $lists );
	}

	/**
	 * @param $lists
	 * @param $automation_data
	 * @param $v
	 *
	 * @return bool|void
	 */
	public function validate_bulk_action_event_settings( $lists, $automation_data, $v ) {
		$list_ids = array_map( function ( $list ) {
			return absint( $list['id'] );
		}, $lists );

		if ( 1 === absint( $v ) ) {
			$list = $automation_data['meta']['event_meta']['list'];

			if ( 'any' === $list ) {
				return true;
			}

			return in_array( $list, $list_ids );
		}

		if ( 2 === absint( $v ) ) {
			$list_contain = isset( $automation_data['meta']['event_meta']['list-contains'] ) ? $automation_data['meta']['event_meta']['list-contains'] : 'any';
			if ( 'any' === $list_contain ) {
				return true;
			}
			$lists          = $automation_data['meta']['event_meta']['list'];
			$event_list_ids = empty( $lists ) ? [] : array_column( $lists, 'id' );

			return ( count( array_intersect( $list_ids, $event_list_ids ) ) > 0 );
		}
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['list'] ) || empty( $data['list'] ) ) {
			return '';
		}
		$lists = [];
		foreach ( $data['list'] as $list ) {
			if ( ! isset( $list['name'] ) || empty( $list['name'] ) ) {
				continue;
			}
			$lists[] = $list['name'];
		}

		return $lists;
	}

	/** set default values */
	public function get_default_values() {
		return [
			'list-contains' => 'selected_list',
		];
	}

	/** Set default goal values */
	public function get_default_goal_values() {
		return [
			'list-contains' => 'any',
		];
	}

	public function pre_validate_goal( $step_data, $contact_id ) {
		if ( empty( $contact_id ) ) {
			return false;
		}

		$list_contain = $step_data['sidebarData']['list-contains'] ?? 'any';
		if ( 'any' === $list_contain ) {
			return false;
		}

		$contact       = new WooFunnels_Contact( '', '', '', $contact_id );
		$contact_lists = ! empty( $contact->get_lists() ) ? $contact->get_lists() : [];
		$goal_lists    = isset( $step_data['sidebarData']['list'] ) ? array_column( $step_data['sidebarData']['list'], 'id' ) : [];
		$common_lists  = array_intersect( $goal_lists, $contact_lists );

		return ( count( $common_lists ) > 0 );
	}

	/**
	 * get contact automation data
	 *
	 * @param $automation_data
	 * @param $cid
	 *
	 * @return array|null[]
	 */
	public function get_manually_added_contact_automation_data( $automation_data, $cid ) {
		$contact = new WooFunnels_Contact( '', '', '', $cid );

		/** Check if contact exists */
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return [ 'status' => 0, 'type' => 'contact_not_found' ];
		}

		$contact_lists = $contact->get_lists();

		/** return if no lists available as event is added to list */
		if ( empty( $contact_lists ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'There are no assigned lists for the contact', 'wp-marketing-automations-pro' ) ];
		}

		if ( isset( $automation_data['event_meta']['list-contains'] ) && 'selected_list' === $automation_data['event_meta']['list-contains'] ) {
			$event_lists   = $automation_data['event_meta']['list'];
			$matched_lists = array_values( array_filter( $event_lists, function ( $list ) use ( $contact_lists ) {
				return in_array( $list['id'], $contact_lists, true );
			} ) );

			/** return if no lists are matched with contact lists */
			if ( empty( $matched_lists ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No contact lists are matching with event lists' . 'wp-marketing-automations-pro' ) ];
			}

			$contact_lists = array_column( $matched_lists, 'id' );
		}

		$this->contact_id = $cid;
		$this->email      = $contact->get_email();
		$this->list_id    = $contact_lists[0];
		$this->list_name  = BWFCRM_Lists::get_lists( array( $contact_lists[0] ) )[0]['name'];

		return array_merge( $automation_data, [ 'contact_id' => $this->contact_id, 'email' => $this->email, 'list_id' => $this->list_id, 'list_name' => $this->list_name ] );
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Lists' ) ) {
	return 'BWFAN_CRM_Assigned_List';
}
