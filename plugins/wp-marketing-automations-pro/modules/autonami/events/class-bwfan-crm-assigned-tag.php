<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Assigned_Tag extends BWFAN_Event {
	private static $instance = null;
	public $tag_id = null;
	public $tags = null;
	public $contact_id = null;
	public $tag_name = null;
	public $email = null;
	public $user_id = null;

	/** v2 */
	public $contact_data_v2 = array();

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwfan_crm_tags', 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Tag is Added', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after an tag assigned to contact.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_field',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 10;
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
		add_action( 'bwfan_tags_added_to_contact', array( $this, 'process' ), 10, 2 );
	}

	public function process( $tags, $contact ) {
		$this->tags = [];
		if ( ! is_array( $tags ) && $tags instanceof BWFCRM_Tag ) {
			$this->tags[] = $tags;
		} else {
			$this->tags = $tags;
		}

		$this->contact_id = ( $contact instanceof BWFCRM_Contact ) ? $contact->get_id() : 0;
		$this->email      = ( $contact instanceof BWFCRM_Contact ) ? $contact->contact->get_email() : '';

		$this->run_automations();
	}

	public function run_automations() {
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );

		BWFAN_PRO_Common::disable_run_v2_automation_immediately();

		$automation_actions = [];
		foreach ( $this->tags as $tag ) {
			if ( ! $tag instanceof BWFCRM_Tag ) {
				continue;
			}
			$this->tag_id   = $tag->get_id();
			$this->tag_name = $tag->get_name();

			/** v2 run starts */
			$contact_data_v2 = array(
				'contact_id' => absint( $this->contact_id ),
				'email'      => $this->email,
				'tag_id'     => $this->tag_id,
				'tag_name'   => $this->tag_name
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
			$tag       = ! empty( $automation_data['event_meta']['tag'] ) ? $automation_data['event_meta']['tag'] : 'any';
			$runs      = isset( $automation_data['event_meta']['bwfan_automation_run'] ) ? $automation_data['event_meta']['bwfan_automation_run'] : 'multiple';
			$run_count = 0;
			if ( 'once' === $runs ) {
				$run_count = BWFAN_Model_Automations::get_contact_automation_run_count( $automation_id, $this->contact_id );
			}

			/** selected any tag and runs multiple times selected  */
			if ( 'any' === $tag && 'multiple' === $runs ) {
				continue;
			}

			/** selected tag id and run multiple times selected */
			if ( absint( $this->tag_id ) === absint( $tag ) && 'multiple' === $runs ) {
				continue;
			}

			if ( 'any' === $tag && 'once' === $runs && 0 === $run_count ) {
				continue;
			}

			if ( absint( $this->tag_id ) === absint( $tag ) && 'once' === $runs && 0 === $run_count ) {
				continue;
			}

			unset( $automations_arr_temp[ $automation_id ] );

		}

		return $automations_arr_temp;
	}

	public function get_view_data() {
		$all_tags = BWFCRM_Tag::get_tags();
		if ( empty( $all_tags ) ) {
			return array();
		}

		$tag_data = array();
		foreach ( $all_tags as $tag ) {
			$tag_data[ $tag['ID'] ] = $tag['name'];
		}

		return $tag_data;
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
		$data_to_send['global']['tag_id']     = $this->tag_id;
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['tag_name']   = $this->tag_name;
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

		$tag_id      = isset( $global_data['tag_id'] ) ? $global_data['tag_id'] : 0;
		$tag_url     = admin_url( "admin.php?page=autonami&path=/manage/tags&s=" . $global_data['tag_name'] );
		$contact_url = admin_url( "admin.php?page=autonami&path=/contact/" . $global_data['contact_id'] );
		?>
        <li>
            <strong><?php echo esc_html__( 'Tag Id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo esc_attr( $tag_url ); ?>"><?php echo esc_html__( $tag_id ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Tag Name:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['tag_name'] ); ?></span>
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
        <script>
            jQuery(document).ready(function ($) {

                $(document.body).on('click', '.item_modify_trigger', function () {

                    setTimeout(select2Tag, 300);

                    function select2Tag() {
                        $('#bwfcrm-select-tag').select2({
                            placeholder: 'Search tag',
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
                                        type: $('#bwfcrm-select-tag').attr('data-search'),
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

                    }
                });
                $('body').on('change', '.bwfan-tag-search', function () {
                    var temp_tag = {id: $(this).val(), name: $(this).find(':selected').text()};

                    $(this).parent().find('.bwfan_searched_tag').val(JSON.stringify(temp_tag));
                });
            });
        </script>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            selected_runs = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'runs')) ? data.eventSavedData.runs : '';
            selected_tag = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'tag')) ? data.eventSavedData.tag : '';

            searched_tag = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'searched_tag')) ? data.eventSavedData.searched_tag : '';
            if(!_.isEmpty(searched_tag)) {
            try {
            searched_tag = JSON.parse(searched_tag);
            }
            catch(e) {
            //Do Nothing
            }
            }
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Select Tag', 'wp-marketing-automations-pro' ); ?>
                </label>
                <select id="bwfcrm-select-tag" data-search="tag" data-search-text="<?php esc_attr_e( 'Select Tag', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-tag-search bwfan-input-wrapper" name="event_meta[tag]">
                    <#
                    if(_.size(searched_tag) >0) {
                    temp_selected_tag = _.isObject(searched_tag) ? searched_tag : JSON.parse(searched_tag);
                    if(temp_selected_tag.id == selected_tag){
                    #>
                    <option value="{{temp_selected_tag.id}}" selected>{{temp_selected_tag.name}}</option>
                    <#
                    }
                    }
                    stringify_searched_tag = _.isObject(searched_tag) ? JSON.stringify(searched_tag) :
                    searched_tag;
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_tag" name="event_meta[searched_tag]" value="{{stringify_searched_tag}}"/>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'tag_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['tag_id'] ) ) ) {
			$set_data = array(
				'tag_id'     => intval( $task_meta['global']['tag_id'] ),
				'email'      => $task_meta['global']['email'],
				'contact_id' => intval( $task_meta['global']['contact_id'] ),
				'tag_name'   => $task_meta['global']['tag_name'],
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
		BWFAN_Core()->rules->setRulesData( $this->tag_id, 'tag_id' );
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->tag_name, 'tag_name' );
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_tag( $data );
	}

	public function validate_tag( $data ) {
		if ( ! isset( $data['tag_id'] ) || ! isset( $data['contact_id'] ) ) {
			return false;
		}

		$crm_tag = new BWFCRM_Tag( $data['tag_id'] );
		if ( ! $crm_tag->is_exists() ) {
			$this->message_validate_event = __( 'Tag not exist.', 'wp-marketing-automations-pro' );

			return false;
		}

		$contact = new BWFCRM_Contact( $data['contact_id'] );
		$tags    = $contact->get_tags();
		if ( ! in_array( $data['tag_id'], $tags ) ) {
			$this->message_validate_event = __( 'Contact is not in the tag.', 'wp-marketing-automations-pro' );

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
		if ( ! isset( $automation_data['event_meta']['tag-contains'] ) ) {
			if ( ! isset( $automation_data['event_meta']['tags'] ) || empty( $automation_data['event_meta']['tags'] ) ) {
				$automation_data['event_meta']['tag-contains'] = 'any';
			}
		}

		if ( isset( $automation_data['event_meta']['tag-contains'] ) && 'any' === $automation_data['event_meta']['tag-contains'] ) {
			return true;
		}

		/** Selected tags */
		$tag_ids = array_map( function ( $tag ) {
			return intval( $tag['id'] );
		}, $automation_data['event_meta']['tags'] );

		if ( empty( $tag_ids ) ) {
			return true;
		}

		$assigned_tag = intval( $automation_data['tag_id'] );

		return in_array( $assigned_tag, $tag_ids, true );
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'tag-contains',
				'label'       => __( 'Tag Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Specific Tag', 'wp-marketing-automations-pro' ),
						'value' => 'selected_tag'
					],
					[
						'label' => __( 'Any Tag', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => true,
				"description" => ""
			],
			[
				'id'            => 'tags',
				'label'         => __( 'Select Tags', 'wp-marketing-automations-pro' ),
				'type'          => 'search',
				'autocompleter' => 'tags',
				'class'         => 'bwfan-col-sm-9',
				'placeholder'   => '',
				'required'      => true,
				'toggler'       => [
					'fields' => [
						[
							'id'    => 'tag-contains',
							'value' => 'selected_tag',
						]
					]
				],
			],
		];
	}

	public function validate_goal_settings( $automation_settings, $automation_data ) {
		$tag_contain = isset( $automation_settings['tag-contains'] ) ? $automation_settings['tag-contains'] : 'any';
		if ( 'any' === $tag_contain ) {
			return true;
		}

		/** Goal settings tags not set */
		if ( empty( $automation_settings['tags'] ) || 0 === count( $automation_settings['tags'] ) ) {
			return false;
		}

		/** Goal settings tags */
		$tags = array_column( $automation_settings['tags'], 'id' );
		if ( empty( $tags ) ) {
			return false;
		}

		return in_array( $automation_data['tag_id'], $tags );
	}

	/**
	 * @param $tags
	 * @param $automation_data
	 * @param $v
	 *
	 * @return bool|void
	 */
	public function validate_bulk_action_event_settings( $tags, $automation_data, $v ) {
		$tag_ids = array_map( function ( $tag ) {
			return absint( $tag['id'] );
		}, $tags );

		if ( 1 === absint( $v ) ) {
			$tag = $automation_data['meta']['event_meta']['tag'];
			if ( 'any' === $tag ) {
				return true;
			}

			return in_array( $tag, $tag_ids );
		}

		if ( 2 === absint( $v ) ) {
			$tag_contain = isset( $automation_data['meta']['event_meta']['tag-contains'] ) ? $automation_data['meta']['event_meta']['tag-contains'] : 'any';
			if ( 'any' === $tag_contain ) {
				return true;
			}
			$tags          = $automation_data['meta']['event_meta']['tags'];
			$event_tag_ids = empty( $tags ) ? [] : array_column( $tags, 'id' );

			return ( count( array_intersect( $tag_ids, $event_tag_ids ) ) > 0 );
		}
	}

	/**
	 * Returns description text
	 *
	 * @param $data
	 *
	 * @return array|string
	 */
	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['tags'] ) || empty( $data['tags'] ) ) {
			return '';
		}
		$tags = [];
		foreach ( $data['tags'] as $tag ) {
			if ( ! isset( $tag['name'] ) || empty( $tag['name'] ) ) {
				continue;
			}
			$tags[] = $tag['name'];
		}

		return $tags;
	}

	/** set default values */
	public function get_default_values() {
		return [
			'tag-contains' => 'selected_tag',
		];
	}

	/** Set default goal values */
	public function get_default_goal_values() {
		return [
			'tag-contains' => 'any',
		];
	}

	public function pre_validate_goal( $step_data, $contact_id ) {
		if ( empty( $contact_id ) ) {
			return false;
		}
		$tag_contain = $step_data['sidebarData']['tag-contains'] ?? 'any';
		if ( 'any' === $tag_contain ) {
			return false;
		}
		$contact      = new WooFunnels_Contact( '', '', '', $contact_id );
		$contact_tags = ! empty( $contact->get_tags() ) ? $contact->get_tags() : [];

		$goal_tags   = isset( $step_data['sidebarData']['tags'] ) ? array_column( $step_data['sidebarData']['tags'], 'id' ) : [];
		$common_tags = array_intersect( $goal_tags, $contact_tags );

		return ( count( $common_tags ) > 0 );
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

		$contact_tags = $contact->get_tags();

		/** return if no tags available as event is tag added */
		if ( empty( $contact_tags ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'There are no assigned tags for the contact', 'wp-marketing-automations-pro' ) ];
		}

		if ( isset( $automation_data['event_meta']['tag-contains'] ) && 'selected_tag' === $automation_data['event_meta']['tag-contains'] ) {
			$event_tags   = $automation_data['event_meta']['tags'];
			$matched_tags = array_values( array_filter( $event_tags, function ( $tag ) use ( $contact_tags ) {
				return in_array( $tag['id'], $contact_tags, true );
			} ) );

			/** return if no tags are matched with contact tags */
			if ( empty( $matched_tags ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No contact tags are matching with event tags', 'wp-marketing-automations-pro' ) ];
			}

			$contact_tags = array_column( $matched_tags, 'id' );
		}

		$this->contact_id = $cid;
		$this->email      = $contact->get_email();
		$this->tag_id     = $contact_tags[0];
		$this->tag_name   = BWFCRM_Tag::get_tags( array( $contact_tags[0] ) )[0]['name'];

		return array_merge( $automation_data, [ 'contact_id' => $this->contact_id, 'email' => $this->email, 'tag_id' => $this->tag_id, 'tag_name' => $this->tag_name ] );
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Tag' ) ) {
	return 'BWFAN_CRM_Assigned_Tag';
}
