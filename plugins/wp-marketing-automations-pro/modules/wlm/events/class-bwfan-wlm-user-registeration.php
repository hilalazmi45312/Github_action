<?php

#[AllowDynamicProperties]
final class BWFAN_WLM_User_Registration extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $form_title = '';
	public $entry = [];
	public $fields = [];
	public $email = '';
	public $level_id = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'wc_customer', 'wlm_forms' );
		$this->event_name             = esc_html__( 'User Submits a Registration Form', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a user submit a registration form.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'wlm',
			'wc_customer',
			'wlm_forms',
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
		$this->priority               = 20;
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wishlistmember_user_registered', [ $this, 'added_new_user' ], 20, 2 );
		add_action( 'wp_ajax_bwfan_get_wlm_form_fields', array( $this, 'bwfan_get_wlm_form_fields' ) );
		add_filter( 'bwfan_all_event_js_data', array( $this, 'add_form_data' ), 10, 2 );

	}

	public function added_new_user( $user_id, $data ) {
		$this->process( $user_id, $data );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $user_id
	 * @param $form_data
	 */
	public function process( $user_id, $form_data ) {
		$data = $this->get_default_data();

		$user_data = get_userdata( $user_id );
		if ( ! $user_data instanceof WP_User ) {
			return;
		}

		if ( ! isset( $form_data['wpm_id'] ) ) {
			return;
		}

		//Get form id from level settings
		global $wpdb;
		$wlm_option_table = $wpdb->prefix . 'wlm_options';
		$wpm_levels       = $wpdb->get_row( "Select * from {$wlm_option_table} WHERE option_name LIKE 'wpm_levels' LIMIT 1" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $wpm_levels ) ) {
			$wpm_levels = maybe_unserialize( $wpm_levels->option_value );
		} else {
			return;
		}

		if ( ! isset( $wpm_levels[ $form_data['wpm_id'] ] ) ) {
			return;
		}

		$level_info      = $wpm_levels[ $form_data['wpm_id'] ];
		$regpage_form_id = 'default';
		if ( isset( $level_info['custom_reg_form'] ) && isset( $level_info['enable_custom_reg_form'] ) ) {
			$regpage_form_id = $level_info['custom_reg_form'];
		}

		$data['level_id']   = $form_data['wpm_id'];
		$data['email']      = $user_data->user_email;
		$data['entry']      = $form_data;
		$data['form_id']    = $form_data['wlm_form_id'];
		$data['form_title'] = $this->get_form_title( $form_data['wlm_form_id'] );
		$data['fields']     = $this->get_form_fields( $form_data['wlm_form_id'] );

		$this->send_async_call( $data );
	}

	/** get form title using form id
	 *
	 * @param $form_id
	 *
	 * @return mixed|string
	 */
	public function get_form_title( $form_id ) {
		$form_title = '';
		$forms      = $this->get_view_data();
		if ( isset( $forms[ $form_id ] ) ) {
			$form_title = $forms[ $form_id ];
		} else {
			$form_title = $forms['default'];
		}

		return $form_title;
	}

	/** get wishlist members levels
	 * @return array
	 */
	public function get_view_data() {
		global $wpdb;
		$options            = array();
		$options['default'] = esc_attr__( 'Default registration form', 'uncanny-automator-pro' );
		$forms              = $wpdb->get_results( "SELECT option_name,option_value FROM `{$wpdb->prefix}wlm_options` WHERE `option_name` LIKE 'CUSTOMREGFORM-%' ORDER BY `option_name` ASC", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $forms as $k => $form ) {
			$form_value                        = maybe_unserialize( wlm_serialize_corrector( $form['option_value'] ) );
			$all_forms[ $form['option_name'] ] = $form_value['form_name'];
		}

		if ( ! empty( $all_forms ) ) {
			foreach ( $all_forms as $key => $form ) {
				$options[ $key ] = $form;
			}
		}

		return $options;
	}

	/** form fields using form id */
	public function get_form_fields( $form_id ) {

		global $wpdb;
		$fields = [];

		if ( $form_id != 'default' ) {
			$form        = $wpdb->get_var( "SELECT option_value FROM `{$wpdb->prefix}wlm_options` WHERE `option_name` LIKE '%{$form_id}%' ORDER BY `option_name` ASC" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$form_value  = maybe_unserialize( wlm_serialize_corrector( $form ) );
			$form_fields = $form_value['form_dissected']['fields'];
			if ( is_array( $form_fields ) ) {
				foreach ( $form_fields as $field ) {
					if ( $field['attributes']['type'] != 'password' ) {
						$fields[ $field['attributes']['name'] ] = str_replace( ':', '', $field['label'] );
					}
				}
			}

		} elseif ( $form_id == 'default' ) {
			$form_fields = $this->get_default_form_fields();
			if ( is_array( $form_fields ) ) {
				foreach ( $form_fields as $key => $field ) {
					$fields[ $key ] = $field;
				}
			}
		}

		return $fields;
	}

	/** default fields for user registration in wishlist member
	 * @return mixed|void
	 */
	public function get_default_form_fields() {

		$fields = [
			'firstname' => __( 'First name', 'wp-marketing-automations-pro' ),
			'lastname'  => __( 'Last name', 'wp-marketing-automations-pro' ),
			'email'     => __( 'Email', 'wp-marketing-automations-pro' ),
			'username'  => __( 'Username', 'wp-marketing-automations-pro' ),
		];

		return apply_filters( 'bwfan_wlm_default_form_field', $fields );
	}

	/** localise wishlist members level options */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$registration_forms = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'registration_forms', $registration_forms );
		}
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_reg_form = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'reg_form')) ? data.eventSavedData.reg_form : '';
            selected_field_map = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'email_map')) ? data.eventSavedData.email_map : '';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select Form', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="bwfan-wlm_form_submit_form_id" class="bwfan-input-wrapper" name="event_meta[reg_form]">
                    <option value=""><?php esc_html_e( 'Choose Form', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'registration_forms') && _.isObject(data.eventFieldsOptions.registration_forms) ) {
                    _.each( data.eventFieldsOptions.registration_forms, function( value, key ){
                    selected = (key == selected_reg_form) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
            <#
            show_mapping = !_.isEmpty(selected_reg_form)?'block':'none';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-wlm-forms-map bwfan-col-sm-12 bwfan-p-0 bwfan-mt-5">
                <div class="bwfan_spinner bwfan_hide"></div>
                <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-wlm-field-map" style="display:{{show_mapping}}">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'Map Email field if exists (optional)', 'wp-marketing-automations-pro' ); ?></label>
                    <select id="bwfan-wlm_email_field_map" class="bwfan-input-wrapper" name="event_meta[email_map]">
                        <option value=""><?php esc_html_e( 'None', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        _.each( bwfan_events_js_data['wlm_user_registration']['selected_form_fields'], function( value, key ){
                        selected =(key == selected_field_map)?'selected':'';
                        #>
                        <option value="{{key}}" {{selected}}>{{value}}</option>
                        <# })
                        #>
                    </select>
                </div>
            </div>
        </script>

        <script>
            jQuery(document).on('change', '#bwfan-wlm_form_submit_form_id', function () {
                var selected_id = jQuery(this).val();
                bwfan_events_js_data['wlm_user_registration']['selected_id'] = selected_id;
                if (_.isEmpty(selected_id)) {
                    jQuery(".bwfan-wlm-field-map").hide();
                    return false;
                }
                jQuery(".bwfan-wlm-forms-map .bwfan_spinner").removeClass('bwfan_hide');
                jQuery(".bwfan-wlm-field-map").hide();
                jQuery.ajax({
                    method: 'post',
                    url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
                    datatype: "JSON",
                    data: {
                        action: 'bwfan_get_wlm_form_fields',
                        id: selected_id,
                    },
                    success: function (response) {
                        jQuery(".bwfan-wlm-forms-map .bwfan_spinner").addClass('bwfan_hide');
                        jQuery(".bwfan-wlm-field-map").show();
                        update_wlm_email_field_map(response.fields);
                        _
                        bwfan_events_js_data['wlm_user_registration']['selected_form_fields'] = response.fields;
                    }
                });
            });

            function update_wlm_email_field_map(fields) {
                jQuery("#bwfan-wlm_email_field_map").html('');
                var option = '<option value="">None</option>';
                if (_.size(fields) > 0 && _.isObject(fields)) {
                    _.each(fields, function (v, e) {
                        option += '<option value="' + e + '">' + v + '</option>';
                    });
                }
                jQuery("#bwfan-wlm_email_field_map").html(option);
            }

            jQuery('body').on('bwfan-change-rule', function (e, v) {
                if ('wlm_form_field' !== v.value) {
                    return;
                }

                var options = '';

                _.each(bwfan_events_js_data['wlm_user_registration']['selected_form_fields'], function (value, key) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });

                v.scope.find('.bwfan_wlm_form_fields').html(options);
            });

            jQuery('body').on('bwfan-selected-merge-tag', function (e, v) {
                if ('wishlist_member_form_field' !== v.tag) {
                    return;
                }

                var options = '';
                var i = 1;
                var selected = '';

                _.each(bwfan_events_js_data['wlm_user_registration']['selected_form_fields'], function (value, key) {
                    selected = (i == 1) ? 'selected' : '';
                    options += '<option value="' + key + '" ' + selected + '>' + value + '</option>';
                    i++;
                });

                jQuery('.bwfan_wlm_form_fields').html(options);
                jQuery('.bwfan_tag_select').trigger('change');
            });
        </script>
		<?php
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		$email_map   = $automation_data['event_meta']['email_map'];
		$this->email = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( trim( $this->entry[ $email_map ] ) ) ) ? $this->entry[ $email_map ] : '';

		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->entry, 'entry' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->level_id, 'level_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_user_id_event() {

		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', $this->email );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return false;
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
		$data_to_send['global']['form_id']    = $this->form_id;
		$data_to_send['global']['form_title'] = $this->form_title;
		$data_to_send['global']['entry']      = $this->entry;
		$data_to_send['global']['fields']     = $this->fields;
		$data_to_send['global']['email']      = $this->email;
		$data_to_send['global']['level_id']   = $this->level_id;

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
		?>
        <li>
            <strong><?php echo esc_html__( 'Form ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['form_id'] ); ?></span>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Form Title:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['form_title'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function get_email_event() {

		return is_email( $this->email ) ? $this->email : null;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {

		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
		if ( ( empty( $get_data ) || strval( $get_data ) !== strval( $task_meta['global']['form_id'] ) ) ) {
			$set_data = array(
				'form_id'    => $task_meta['global']['form_id'],
				'form_title' => $task_meta['global']['form_title'],
				'entry'      => $task_meta['global']['entry'],
				'fields'     => $task_meta['global']['fields'],
				'email'      => $task_meta['global']['email'],
				'level_id'   => $task_meta['global']['level_id'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {

		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->entry      = BWFAN_Common::$events_async_data['entry'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->entry_id   = BWFAN_Common::$events_async_data['entry_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];
		$this->level_id   = BWFAN_Common::$events_async_data['level_id'];

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
			if ( strval( $automation_data['event_meta']['reg_form'] ) === strval( 'default' ) ) {
				$reg_form = 'DEFAULT-' . $this->level_id;
			} else {
				$reg_form = $automation_data['event_meta']['reg_form'];
			}

			if ( strval( $this->form_id ) !== strval( $reg_form ) ) {
				unset( $automations_arr_temp[ $automation_id ] );
			}
		}

		return $automations_arr_temp;
	}

	public function add_form_data( $event_js_data, $automation_meta ) {
		if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $event_js_data['wlm_user_registration'] ) || ! isset( $automation_meta['event_meta']['reg_form'] ) ) {
			return $event_js_data;
		}

		if ( isset( $automation_meta['event'] ) && ! empty( $automation_meta['event'] ) && 'wlm_user_registration' !== $automation_meta['event'] ) {
			return $event_js_data;
		}

		$event_js_data['wlm_user_registration']['selected_id'] = $automation_meta['event_meta']['reg_form'];
		$fields                                                = $this->get_form_fields( $automation_meta['event_meta']['reg_form'] );

		$event_js_data['wlm_user_registration']['selected_form_fields'] = $fields;

		return $event_js_data;
	}

	/**
	 * get form field on ajax
	 */
	public function bwfan_get_wlm_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$form_id = sanitize_text_field( $_POST['id'] ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$fields  = [];
		if ( empty( $form_id ) ) {
			wp_send_json( array(
				'fields' => $fields,
			) );
		}

		$fields = $this->get_form_fields( $form_id );

		/** fields for v2 */
		if ( isset( $_POST['fromApp'] ) && $_POST['fromApp'] ) {
			$finalarr = [];
			foreach ( $fields as $key => $value ) {
				$finalarr[] = [
					'key'   => $key,
					'value' => $value
				];
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
		}

		wp_send_json( array(
			'fields' => $fields,
		) );
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		$matched_form = strval( $automation_data['event_meta']['bwfan-wlm_form_submit_form_id'] );
		if ( $matched_form === 'default' ) {
			$matched_form = strval( 'DEFAULT-' . $automation_data['level_id'] );
		}
		if ( strval( $automation_data['form_id'] ) !== $matched_form ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture v2 data.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {

		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->entry      = BWFAN_Common::$events_async_data['entry'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->entry_id   = BWFAN_Common::$events_async_data['entry_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];
		$this->level_id   = BWFAN_Common::$events_async_data['level_id'];

		$automation_data['form_id']    = $this->form_id;
		$automation_data['form_title'] = $this->form_title;
		$automation_data['entry']      = $this->entry;
		$automation_data['fields']     = $this->fields;
		$automation_data['entry_id']   = $this->entry_id;
		$automation_data['email']      = $this->email;
		$automation_data['level_id']   = $this->level_id;

		return $automation_data;
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {
		$options = array_replace( [ '' => 'Select' ], $this->get_view_data() );
		$options = BWFAN_PRO_Common::prepared_field_options( $options );

		return [
			[
				'id'          => 'bwfan-wlm_form_submit_form_id',
				'label'       => __( "Select Form", 'wp-marketing-automations-pro' ),
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
				"required"    => true,
				"errorMsg"    => __( "Form is required.", 'wp-marketing-automations-pro' ),
				"description" => ""
			],
			[
				'id'          => 'bwfan-wlm_email_field_map',
				'type'        => 'ajax',
				'label'       => __( 'Select Email Field', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "Map the field to be used by appropriate Rules and Actions.",
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_wlm_form_fields',
				"ajax_field"  => [
					'id' => 'bwfan-wlm_form_submit_form_id'
				],
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-wlm_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			],
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
	return 'BWFAN_WLM_User_Registration';
}
