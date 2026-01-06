<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Link_Trigger extends BWFAN_Event {
	private static $instance = null;
	public $link_id = null;
	public $contact_id = null;
	public $link_name = null;
	public $email = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Link is Clicked', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a link is click or visited.', 'wp-marketing-automations-pro' );
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
		$this->priority               = 35;
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
		add_action( 'bwfan_link_triggered', array( $this, 'process' ), 10, 2 );
	}

	public function process( $link_id, $contact ) {
		if ( ! $contact instanceof BWFCRM_Contact || 0 === $contact->get_id() || empty( $link_id ) ) {
			return;
		}

		$data               = $this->get_default_data();
		$data['contact_id'] = $contact->get_id();
		$data['link_id']    = $link_id;

		$link_data = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( $link_id );

		if ( empty( $link_data ) ) {
			return;
		}

		$data['email']     = $contact->contact->get_email();
		$data['user_id']   = $contact->contact->get_wpid();
		$data['link_name'] = $link_data['title'];
		$this->send_async_call( $data );
	}

	public function capture_async_data() {
		$this->contact_id = BWFAN_Common::$events_async_data['contact_id'];
		$this->link_id    = BWFAN_Common::$events_async_data['link_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];
		$this->user_id    = ! empty( BWFAN_Common::$events_async_data['user_id'] ) ? BWFAN_Common::$events_async_data['user_id'] : $this->get_user_id_event();
		$this->link_name  = BWFAN_Common::$events_async_data['link_name'];

		return $this->run_automations();
	}

	public function get_user_id_event() {

		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', absint( $this->email ) );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
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
		$data_to_send['global']['link_id']    = $this->link_id;
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['email']      = $this->email;
		$data_to_send['global']['user_id']    = $this->user_id;
		$data_to_send['global']['link_name']  = $this->link_name;

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

		$link_id     = isset( $global_data['link_id'] ) ? $global_data['link_id'] : 0;
		$link_url    = admin_url( "admin.php?page=autonami&path=/link-trigger/" . $global_data['link_id'] );
		$contact_url = admin_url( "admin.php?page=autonami&path=/contact/" . $global_data['contact_id'] );
		?>
        <li>
            <strong><?php echo esc_html__( 'Link Id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo esc_attr( $link_url ); ?>"><?php echo esc_html__( $link_id ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Link Name:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['link_name'] ); ?></span>
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
                        $('#bwfcrm-select-link_triggers').select2({
                            placeholder: 'Search Link Triggers',
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
                                        type: $('#bwfcrm-select-link_triggers').attr('data-search'),
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

                $('body').on('change', '.bwfan-link_trigger-search', function () {
                    var temp_list = {id: $(this).val(), name: $(this).find(':selected').text()};

                    $(this).parent().find('.bwfan_searched_link_trigger').val(JSON.stringify(temp_list));
                });
            });
        </script>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            selected_link_trigger = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'link_trigger')) ? data.eventSavedData.link_trigger : '';
            selected_runs = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'runs')) ? data.eventSavedData.runs : '';

            searched_link_trigger = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'searched_link_trigger')) ? data.eventSavedData.searched_link_trigger : '';
            if(!_.isEmpty(searched_link_trigger)) {
            try {
            searched_link_trigger = JSON.parse(searched_link_trigger);
            }
            catch(e) {
            //Do Nothing
            }
            }
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Select Link Triggers', 'wp-marketing-automations-pro' ); ?>
                </label>
                <select id="bwfcrm-select-link_triggers" data-search="link_trigger" data-search-text="<?php esc_attr_e( 'Select Link Trigger', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-link_trigger-search bwfan-input-wrapper" name="event_meta[link_trigger]">
                    <#
                    if(_.size(searched_link_trigger) >0) {
                    temp_selected_link_trigger = _.isObject(searched_link_trigger) ? searched_link_trigger : JSON.parse(searched_link_trigger);
                    if(temp_selected_link_trigger.id == selected_link_trigger){
                    #>
                    <option value="{{temp_selected_link_trigger.id}}" selected>{{temp_selected_link_trigger.name}}</option>
                    <#
                    }
                    }

                    stringify_searched_link_trigger = _.isObject(searched_link_trigger) ? JSON.stringify(searched_link_trigger) :
                    searched_link_trigger;
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_link_trigger" name="event_meta[searched_link_trigger]" value="{{stringify_searched_link_trigger}}"/>
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

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'link_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['link_id'] ) ) ) {
			$set_data = array(
				'link_id'    => intval( $task_meta['global']['link_id'] ),
				'email'      => $task_meta['global']['email'],
				'contact_id' => intval( $task_meta['global']['contact_id'] ),
				'link_name'  => $task_meta['global']['link_name'],
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
		BWFAN_Core()->rules->setRulesData( $this->link_id, 'link_id' );
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->link_name, 'link_name' );
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
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
			$link_trigger = $automation_data['event_meta']['link_trigger'];
			$runs         = $automation_data['event_meta']['runs'];

			/** selected any list and runs multiple times selected  */
			if ( 'any' === $link_trigger && 'multiple_times' === $runs ) {
				continue;
			}

			if ( 'any' !== $link_trigger && absint( $this->link_id ) !== absint( $link_trigger ) ) {
				unset( $automations_arr_temp[ $automation_id ] );
				continue;
			}

			if ( 'multiple_times' === $runs ) {
				continue;
			}

			$run_count = BWFAN_Model_Automations::get_contact_automation_run_count( $automation_id, $this->contact_id );

			/** Not run yet */
			if ( 0 === $run_count ) {
				continue;
			}

			unset( $automations_arr_temp[ $automation_id ] );
		}

		return $automations_arr_temp;
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_link_trigger( $data );
	}

	public function validate_link_trigger( $data ) {
		/** Selected Links */
		$links_ids = array_map( function ( $link ) {
			return intval( $link['id'] );
		}, $data['event_meta']['link_trigger'] );
		if ( empty( $links_ids ) ) {
			return false;
		}

		$trigger_link_id = intval( $data['link_id'] );

		return in_array( $trigger_link_id, $links_ids, true );
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		$trigger_contains = isset( $automation_data['event_meta']['link-contains'] ) ? $automation_data['event_meta']['link-contains'] : 'selected_link';

		if ( 'any' === $trigger_contains ) {
			return true;
		}

		if ( ! isset( $automation_data['event_meta']['link_trigger'] ) || ! isset( $automation_data['link_id'] ) ) {
			return false;
		}

		return $this->validate_link_trigger( $automation_data );
	}

	/**
	 * Capture the async v2 data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->contact_id              = BWFAN_Common::$events_async_data['contact_id'];
		$this->link_id                 = BWFAN_Common::$events_async_data['link_id'];
		$this->email                   = BWFAN_Common::$events_async_data['email'];
		$this->user_id                 = ! empty( BWFAN_Common::$events_async_data['user_id'] ) ? BWFAN_Common::$events_async_data['user_id'] : $this->get_user_id_event();
		$this->link_name               = BWFAN_Common::$events_async_data['link_name'];
		$automation_data['contact_id'] = $this->contact_id;
		$automation_data['link_id']    = $this->link_id;
		$automation_data['email']      = $this->email;
		$automation_data['user_id']    = $this->user_id;
		$automation_data['link_name']  = $this->link_name;

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'link-contains',
				'label'       => __( 'Link Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any link', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Link', 'wp-marketing-automations-pro' ),
						'value' => 'selected_link'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => true,
				"description" => ""
			],
			[
				'id'                  => 'link_trigger',
				'type'                => 'search',
				'autocompleter'       => 'link_trigger',
				'label'               => __( 'Select Link(s) Trigger', 'wp-marketing-automations-pro' ),
				"class"               => "bwfan-col-sm-9",
				"allowFreeTextSearch" => false,
				"required"            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'link-contains',
							'value' => 'selected_link',
						]
					]
				],
			]
		];
	}

	public function get_default_values() {
		return [
			'link-contains' => 'selected_link',
		];
	}

	public function get_view_data() {
		$all_links = BWFAN_Model_Link_Triggers::get_link_triggers();
		if ( empty( $all_links['links'] ) ) {
			return array();
		}

		$links_data = array();
		foreach ( $all_links['links'] as $link ) {
			$links_data[ $link['ID'] ] = $link['title'];
		}

		return $links_data;
	}

	public function validate_goal_settings( $automation_settings, $automation_data ) {
		/** settings not found */
		if ( ! isset( $automation_data['link_id'] ) ) {
			return false;
		}

		/** If empty */
		if ( empty( $automation_settings['link_trigger'] ) ) {
			return false;
		}

		/** Goal settings tags */
		$tags = array_column( $automation_settings['link_trigger'], 'id' );

		return in_array( $automation_data['link_id'], $tags );
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['link_trigger'] ) || empty( $data['link_trigger'] ) ) {
			return '';
		}
		$links = [];
		foreach ( $data['link_trigger'] as $link ) {
			if ( ! isset( $link['name'] ) || empty( $link['name'] ) ) {
				continue;
			}
			$links[] = $link['name'];
		}

		return $links;
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
		$contact = new BWFCRM_Contact( $cid );

		/** Check if contact exists */
		if ( ! $contact->is_contact_exists() ) {
			return [ 'status' => 0, 'type' => 'contact_not_found' ];
		}

		$clicked_link = $contact->get_field_by_slug( 'link-trigger-click' );
		$clicked_link = json_decode( $clicked_link, true );
		/** Return if contact is not subscribed */
		if ( empty( $clicked_link ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( "Contact doesn't have any link trigger click", 'wp-marketing-automations-pro' ) ];
		}
		$last_clicked_link = end( $clicked_link );
		$link_data         = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( $last_clicked_link );
		if ( empty( $link_data ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( "Link trigger is not exist", 'wp-marketing-automations-pro' ) ];
		}

		$this->contact_id = $contact->get_id();
		$this->link_id    = $last_clicked_link;
		$this->email      = $contact->contact->get_email();
		$this->link_name  = $link_data['title'];

		return array_merge( $automation_data, [ 'contact_id' => $this->contact_id, 'email' => $this->email, 'link_id' => $this->link_id, 'link_name' => $this->link_name ] );
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Link_Trigger_Handler' ) ) {
	return 'BWFAN_CRM_Link_Trigger';
}
