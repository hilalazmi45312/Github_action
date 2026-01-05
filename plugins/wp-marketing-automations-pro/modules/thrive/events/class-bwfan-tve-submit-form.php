<?php

#[AllowDynamicProperties]
final class BWFAN_TVE_Lead_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $form_title = '';
	public $entry = [];
	public $fields = [];
	public $email = '';
	public $screen_id = '';
	public $screen_type = '';
	public $is_triggered = false;
	public $mark_subscribe = false;
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';


	private function __construct() {
		$this->event_merge_tag_groups = array( 'thrive-forms', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'thrive-forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Thrive Leads', 'wp-marketing-automations-pro' );
		$this->priority               = 10;
		$this->customer_email_tag     = '';
		$this->v2                     = true;
		$this->force_async            = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wp_ajax_bwfan_get_tve_form_contents', array( $this, 'bwfan_get_tve_form_contents' ) );
		add_action( 'wp_ajax_bwfan_get_tve_get_fields', array( $this, 'bwfan_get_tve_get_fields' ) );
		add_action( 'wp_ajax_bwfan_tve_form_submit', array( $this, 'bwfan_tve_form_submit' ) );
		add_action( 'tcb_api_form_submit', array( $this, 'process' ), 10, 1 );
		if ( defined( 'TVE_LEADS_ACTION_FORM_CONVERSION' ) ) {
			add_action( TVE_LEADS_ACTION_FORM_CONVERSION, array( $this, 'process_lead_form_conversion' ), 10, 6 );
		}
		add_filter( 'bwfan_all_event_js_data', array( $this, 'add_form_data' ), 10, 2 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'form_options', $data );
		}
	}

	public function get_view_data() {
		$options = array();

		$tve_post_types   = array();
		$tve_post_types[] = defined( 'TVE_LEADS_POST_SHORTCODE_TYPE' ) ? TVE_LEADS_POST_SHORTCODE_TYPE : '';
		$tve_post_types[] = defined( 'TVE_LEADS_POST_FORM_TYPE' ) ? TVE_LEADS_POST_FORM_TYPE : '';
		$tve_post_types[] = defined( 'TVE_LEADS_POST_SHORTCODE_TYPE' ) ? TVE_LEADS_POST_TWO_STEP_LIGHTBOX : '';

		$tve_post_types = array_filter( $tve_post_types );

		if ( empty( $tve_post_types ) ) {
			return $options;
		}

		$posts = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => $tve_post_types,
			'orderby'        => 'post_title',
			'fields'         => 'ids',
			'order'          => 'ASC'
		) );

		foreach ( $posts as $post ) {
			$post_title = get_the_title( $post );
			$parent_id  = wp_get_post_parent_id( $post );
			if ( $parent_id > 0 ) {
				$post_parent       = get_post( $parent_id );
				$post_parent_title = $post_parent->post_title;
				$post_title        = $post_parent_title . ' - ' . $post_title;
			}
			$options[ $post ] = $post_title;
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
            selected_form_id = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'form_id')) ? data.eventSavedData.form_id : '';
            selected_field_map = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'email_map')) ? data.eventSavedData.email_map : '';
            #>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-mt-15 bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Form', 'wp-marketing-automations-pro' ); ?></label>
                <select id="bwfan-tve_form_submit_form_id" class="bwfan-input-wrapper" name="event_meta[form_id]">
                    <option value=""><?php esc_html_e( 'Choose Form', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'form_options') && _.isObject(data.eventFieldsOptions.form_options) ) {
                    _.each( data.eventFieldsOptions.form_options, function( value, key ){
                    selected =(key == selected_form_id)?'selected':'';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    } #>
                </select>
            </div>
            <#
            show_mapping = !_.isEmpty(selected_form_id)?'block':'none';
            #>
            <div class="bwfan-tve-forms-map bwfan-col-sm-12 bwfan-p-0 bwfan-mt-5">
                <div class="bwfan_spinner bwfan_hide"></div>
                <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-tve-field-map" style="display:{{show_mapping}}">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'Map Email field if exists (optional)', 'wp-marketing-automations-pro' ); ?></label>
                    <select id="bwfan-tve_email_field_map" class="bwfan-input-wrapper" name="event_meta[email_map]">
                        <option value=""><?php esc_html_e( 'None', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        _.each( bwfan_events_js_data['tve_lead_form_submit']['selected_form_fields'], function( value, key ){
                        if(key!==''){
                        selected =(key == selected_field_map)?'selected':'';
                        #>
                        <option value="{{key}}" {{selected}}>{{value}}</option>
                        <#
                        }
                        })
                        #>
                    </select>
                </div>
            </div>
        </script>
        <script>
            jQuery(document).on('change', '#bwfan-tve_form_submit_form_id', function () {
                var selected_id = jQuery(this).val();
                bwfan_events_js_data['tve_lead_form_submit']['selected_id'] = selected_id;
                if (_.isEmpty(selected_id)) {
                    jQuery(".bwfan-tve-field-map").hide();
                    return false;
                }
                jQuery.ajax({
                    method: 'post',
                    url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
                    datatype: "JSON",
                    data: {
                        action: 'bwfan_get_tve_form_contents',
                        id: selected_id,
                    },
                    success: function (response) {
                        jQuery('.bwfan-tve-forms-map .bwfan_spinner').removeClass('bwfan_hide');
                        jQuery(".bwfan-tve-field-map").hide();
                        jQuery('.bwfan-tve-forms-map .bwfan_spinner').addClass('bwfan_hide');
                        jQuery(".bwfan-tve-field-map").show();
                        update_email_field_map(response.fields);
                        bwfan_events_js_data['tve_lead_form_submit']['selected_form_fields'] = response.fields;
                    }
                });
            });

            function update_email_field_map(fields) {
                jQuery("#bwfan-tve_email_field_map").html('');
                var option = '<option value="">None</option>';
                if (_.size(fields) > 0 && _.isObject(fields)) {
                    _.each(fields, function (v, e) {
                        option += '<option value="' + e + '">' + v + '</option>';
                    });
                }
                jQuery("#bwfan-tve_email_field_map").html(option);
            }

            jQuery('body').on('bwfan-change-rule', function (e, v) {
                if ('tve_form_field' !== v.value) {
                    return;
                }

                var options = '';

                _.each(bwfan_events_js_data['tve_lead_form_submit']['selected_form_fields'], function (value, key) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });

                v.scope.find('.bwfan_tve_form_fields').html(options);
            });

            jQuery('body').on('bwfan-selected-merge-tag', function (e, v) {
                if ('thrive_form_field' !== v.tag) {
                    return;
                }

                var options = '';
                var i = 1;
                var selected = '';

                _.each(bwfan_events_js_data['tve_lead_form_submit']['selected_form_fields'], function (value, key) {
                    selected = (i == 1) ? 'selected' : '';
                    options += '<option value="' + key + '" ' + selected + '>' + value + '</option>';
                    i++;
                });

                jQuery('.bwfan_tve_form_fields').html(options);
                jQuery('.bwfan_tag_select').trigger('change');
            });
        </script>
		<?php
	}

	public function bwfan_get_tve_form_contents() {
		BWFAN_PRO_Common::nocache_headers();
		$form_id = absint( sanitize_text_field( $_POST['id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( empty( $form_id ) ) {
			wp_send_json( array(
				'contents' => array(),
			) );
		}

		$fields = $this->get_form_fields( $form_id );
		if ( empty( $fields ) ) {
			wp_send_json( array(
				'fields' => array(),
			) );
		}

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

	public function get_form_fields( $form_id ) {
		if ( ! defined( 'TVE_LEADS_STATUS_PUBLISH' ) ) {
			return [];
		}

		$fields = array();
		$lg_ids = get_posts( array(
			'post_type'      => '_tcb_form_settings',
			'fields'         => 'id=>parent',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent'    => $form_id,
		) );
		if ( empty( $lg_ids ) ) {
			return [];
		}

		foreach ( $lg_ids as $lg_id => $lg_parent ) {
			/** Added this as we found an issue where sometimes post parent string was append in the post ID */
			$lg_id   = str_replace( 'post_parent:', '', $lg_id );
			$lg_post = TCB\inc\helpers\FormSettings::get_one( $lg_id );

			$lg_config = $lg_post->get_config( false );
			if ( empty( $lg_config ) ) {
				continue;
			}

			foreach ( $lg_config['inputs'] as $key => $input ) {
				if ( 'undefined' === $key || 'password' === $input['type'] || 'confirm_password' === $input['type'] ) {
					continue;
				}

				$fields[ $key ] = isset( $input['label'] ) ? $input['label'] : '';
			}
		}

		return $fields;
	}

	/**
	 * @param $label_details
	 */
	public function get_tve_fields( $label_details ) {
		if ( empty( $label_details ) ) {
			return array();
		}

		/** in case label details contains form html then get the field using preg_match */
		if ( $label_details != strip_tags( $label_details ) ) {
			$fields = $this->get_fields_from_content( stripslashes( $label_details ) );
		} else {
			$fields = maybe_unserialize( base64_decode( $label_details ) );
		}

		$fields = array_filter( $fields );

		return $fields;
	}

	/**
	 * @return mixed
	 */
	public function get_fields_from_content( $content ) {
		$fields = array();

		if ( empty( $content ) ) {
			return $fields;
		}

		preg_match_all( "/<input\s[^>]*>/", $content, $matches );

		foreach ( $matches[0] as $match ) {

			$string = $match;
			/** $input_type_pattern for getting input type */
			$input_type_pattern = '/<input(?:.*?)type=\"([^"]+).*>/i';
			preg_match( $input_type_pattern, $string, $input_type_matches );
			$input_type = $input_type_matches[1];
			if ( 'hidden' === $input_type ) {
				continue;
			}
			/** $input_type_pattern for getting input name and data-placeholder */
			$input_name_pattern = '/<input(?:.*?)name=\"([^"]+).*>/i';
			preg_match( $input_name_pattern, $string, $input_name_matches );
			$input_label_pattern = '/<input(?:.*?)data-placeholder=\"([^"]+).*>/i';
			preg_match( $input_label_pattern, $string, $input_label_matches );
			if ( ! empty( $input_label_matches ) && ! empty( $input_name_matches ) ) {
				$fields[ $input_name_matches[1] ] = $input_label_matches[1];
			}
		}

		return $fields;
	}

	public function bwfan_get_tve_get_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$label_details = isset( $_POST['label_details'] ) ? $_POST['label_details'] : '';
		if ( empty( $label_details ) ) {
			wp_send_json( array(
				'fields' => array(),
			) );
		}

		$fields = $this->get_tve_fields( $label_details );
		if ( empty( $fields ) ) {
			wp_send_json( array(
				'fields' => array(),
			) );
		}

		wp_send_json( array(
			'fields' => $fields,
		) );
	}

	/**
	 * AJAX callback function for retrieving Thrive form options
	 *
	 * @return void Sends JSON response with form options
	 */
	public function bwfan_tve_form_submit() {
		BWFAN_PRO_Common::nocache_headers();

		$options = $this->get_view_data();

		$response = array();
		foreach ( $options as $key => $data ) {
			$response[] = array(
				'key'   => $key,
				'value' => $data,
			);
		}

		wp_send_json( array(
			'results' => $response,
		) );
	}

	public function process( $post_data ) {
		/** check if triggered already */
		if ( true === $this->is_triggered ) {
			return;
		}

		$data                  = $this->get_default_data();
		$labels                = ! empty( $post_data['tve_labels'] ) ? maybe_unserialize( base64_decode( $post_data['tve_labels'] ) ) : array();
		$post_id               = ! empty( $post_data['post_id'] ) ? $post_data['post_id'] : 0;
		$tl_data               = isset( $post_data['thrive_leads']['tl_data'] ) ? $post_data['thrive_leads']['tl_data'] : [];
		$form_type_id          = ! empty( $tl_data ) && isset( $tl_data['form_type_id'] ) ? $tl_data['form_type_id'] : $post_id;
		$variation_key         = ! empty( $tl_data ) && isset( $tl_data['_key'] ) ? $tl_data['_key'] : '';
		$data['form_id']       = $form_type_id;
		$data['form_title']    = get_the_title( $form_type_id );
		$data['variation_key'] = $variation_key;
		$data['screen_type']   = ! empty( $post_data['current_screen']['screen_type'] ) && isset( $post_data['current_screen']['screen_type'] ) ? $post_data['current_screen']['screen_type'] : '';
		$data['screen_id']     = ! empty( $post_data['current_screen']['screen_id'] ) && isset( $post_data['current_screen']['screen_id'] ) ? $post_data['current_screen']['screen_id'] : '';

		if ( empty( $labels ) ) {
			$form_variation = tve_leads_get_form_variations( $form_type_id );
			if ( ! empty( $form_variation ) ) {
				$content = isset( $form_variation[0]['content'] ) ? $form_variation[0]['content'] : '';
				$labels  = empty( $content ) ? array() : $this->get_form_fields( $form_type_id );
			}
		}

		$data['fields'] = $labels;

		/** pass user submitted data */
		if ( ! empty( $labels ) ) {
			foreach ( $labels as $key => $lab ) {
				$data['entry'][ $key ] = isset( $post_data[ $key ] ) && ! empty( $post_data[ $key ] ) ? $post_data[ $key ] : '';
			}
		}

		$this->is_triggered = true;
		$this->send_async_call( $data );
	}

	/**
	 * Trigger thrive form submit event on lead conversion
	 *
	 * @param $main
	 * @param $form_type
	 * @param $variation
	 * @param $active_test_id
	 * @param $form_data
	 * @param $current_screen
	 */
	public function process_lead_form_conversion( $main, $form_type, $variation, $active_test_id, $form_data, $current_screen ) {
		/** check if triggered already */
		if ( true === $this->is_triggered ) {
			return;
		}

		/** check if valid post */
		if ( ! $main instanceof WP_POST ) {
			return;
		}

		$data                  = $this->get_default_data();
		$post_id               = $form_type instanceof WP_POST ? $form_type->ID : 0;
		$form_type_id          = isset( $form_data['form_type_id'] ) ? $form_data['form_type_id'] : $post_id;
		$data['form_id']       = $form_type_id;
		$data['form_title']    = get_the_title( $form_type_id );
		$data['variation_key'] = isset( $variation['key'] ) ? $variation['key'] : '';
		$data['screen_type']   = ! empty( $current_screen['screen_type'] ) && isset( $current_screen['screen_type'] ) ? $current_screen['screen_type'] : '';
		$data['screen_id']     = ! empty( $current_screen['screen_id'] ) && isset( $current_screen['screen_id'] ) ? $current_screen['screen_id'] : '';
		$form_variation        = tve_leads_get_form_variations( $form_type_id );
		$labels                = array();

		if ( ! empty( $form_variation ) ) {
			$content = isset( $form_variation[0]['content'] ) ? $form_variation[0]['content'] : '';
			$labels  = empty( $content ) ? array() : $this->get_form_fields( $form_type_id );
		}

		$data['fields'] = $labels;

		/** pass user submitted data */
		if ( ! empty( $labels ) ) {
			foreach ( $labels as $key => $lab ) {
				$data['entry'][ $key ] = isset( $form_data[ $key ] ) && ! empty( $form_data[ $key ] ) ? $form_data[ $key ] : '';

				/** data entry empty then check for custom_fields */
				if ( empty( $data['entry'][ $key ] ) && isset( $form_data['custom_fields'] ) && is_array( $form_data['custom_fields'] ) ) {
					$data['entry'][ $key ] = isset( $form_data['custom_fields'][ $key ] ) && ! empty( $form_data['custom_fields'][ $key ] ) ? $form_data['custom_fields'][ $key ] : '';
				}
			}
		}
		$this->is_triggered = true;
		$this->send_async_call( $data );
	}

	public function add_form_data( $event_js_data, $automation_meta ) {
		if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $event_js_data['tve_lead_form_submit'] ) || ! isset( $automation_meta['event_meta']['form_id'] ) ) {
			return $event_js_data;
		}

		if ( isset( $automation_meta['event'] ) && ! empty( $automation_meta['event'] ) && 'tve_lead_form_submit' !== $automation_meta['event'] ) {
			return $event_js_data;
		}

		$event_js_data['tve_lead_form_submit']['selected_id'] = $automation_meta['event_meta']['form_id'];

		$form_variation = tve_leads_get_form_variations( $automation_meta['event_meta']['form_id'] );
		if ( empty( $form_variation ) ) {
			return $event_js_data;
		}

		$variation_contents = isset( $form_variation[0]['content'] ) ? $form_variation[0]['content'] : '';
		preg_match_all( "/<input\s[^>]*>/", $variation_contents, $matches );

		$label_code = '';
		foreach ( $matches[0] as $match ) {
			$check_label_input = strpos( $match, 'name="tve_labels"' );
			if ( $check_label_input ) {
				$string  = $match;
				$pattern = '/<input(?:.*?)id=\"tve_labels\"(?:.*)value=\"([^"]+).*>/i';

				preg_match( $pattern, $string, $matches1123 );
				$label_code = $matches1123[1];
				if ( ! empty( $label_code ) ) {
					break;
				}
			}
		}

		if ( empty( $label_code ) ) {
			$label_code = $variation_contents;
		}

		$fields = $this->get_tve_fields( $label_code );

		if ( empty( $fields ) ) {
			return $event_js_data;
		}

		$event_js_data['tve_lead_form_submit']['selected_form_fields'] = $fields;

		return $event_js_data;
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		$email_map = $automation_data['event_meta']['email_map'];

		$this->email = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : '';

		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->entry, 'entry' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
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

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['form_id'] ) ) ) {
			$set_data = array(
				'form_id'    => intval( $task_meta['global']['form_id'] ),
				'form_title' => $task_meta['global']['form_title'],
				'entry'      => $task_meta['global']['entry'],
				'fields'     => $task_meta['global']['fields'],
				'email'      => $task_meta['global']['email'],
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

		return $this->run_automations();
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
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
			$match_form_id = isset( $automation_data['event_meta']['form_id'] ) ? $automation_data['event_meta']['form_id'] : 0;
			if ( absint( $this->form_id ) !== absint( $match_form_id ) ) {
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
		if ( absint( $automation_data['form_id'] ) !== absint( $automation_data['event_meta']['bwfan-tve_form_submit_form_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$map_fields                                 = isset( $automation_data['event_meta']['bwfan-form-field-map'] ) ? $automation_data['event_meta']['bwfan-form-field-map'] : [];
		$email_map                                  = isset( $map_fields['bwfan_email_field_map'] ) ? $map_fields['bwfan_email_field_map'] : '';
		$first_name_map                             = isset( $map_fields['bwfan_first_name_field_map'] ) ? $map_fields['bwfan_first_name_field_map'] : '';
		$last_name_map                              = isset( $map_fields['bwfan_last_name_field_map'] ) ? $map_fields['bwfan_last_name_field_map'] : '';
		$phone_map                                  = isset( $map_fields['bwfan_phone_field_map'] ) ? $map_fields['bwfan_phone_field_map'] : '';
		$this->mark_subscribe                       = isset( $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ) ? $automation_data['event_meta']['bwfan-mark-contact-subscribed'] : 0;
		$this->form_id                              = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title                           = BWFAN_Common::$events_async_data['form_title'];
		$this->entry                                = BWFAN_Common::$events_async_data['entry'];
		$this->fields                               = BWFAN_Common::$events_async_data['fields'];
		$this->email                                = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : '';
		$this->first_name                           = ( ! empty( $first_name_map ) && isset( $this->entry[ $first_name_map ] ) ) ? $this->entry[ $first_name_map ] : '';
		$this->last_name                            = ( ! empty( $last_name_map ) && isset( $this->entry[ $last_name_map ] ) ) ? $this->entry[ $last_name_map ] : '';
		$this->contact_phone                        = ( ! empty( $phone_map ) && isset( $this->entry[ $phone_map ] ) ) ? $this->entry[ $phone_map ] : '';
		$automation_data['form_id']                 = $this->form_id;
		$automation_data['form_title']              = $this->form_title;
		$automation_data['fields']                  = $this->fields;
		$automation_data['email']                   = $this->email;
		$automation_data['first_name']              = $this->first_name;
		$automation_data['last_name']               = $this->last_name;
		$automation_data['contact_phone']           = $this->contact_phone;
		$automation_data['mark_contact_subscribed'] = $this->mark_subscribe;
		$automation_data['entry']                   = $this->entry;
		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'bwfan-tve_form_submit_form_id',
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				'type'        => 'ajax',
				'multiple'    => false,
				'class'       => 'bwfan-input-wrapper',
				'options'     => array(),
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				'required'    => true,
				"errorMsg"    => __( "Form is required.", 'wp-marketing-automations-pro' ),
				'ajax_cb'     => 'bwfan_tve_form_submit',
				'toggler'     => array(),
			],
			[
				'id'          => 'bwfan-form-field-map',
				'type'        => 'bwf_form_submit',
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_tve_form_contents',
				"ajax_field"  => [
					'id' => 'bwfan-tve_form_submit_form_id'
				],
				"fieldChange" => 'bwfan-tve_form_submit_form_id',
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-tve_form_submit_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark Contact as Subscribed', 'wp-marketing-automations-pro' ),
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-tve_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			]
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_tve_active() ) {
	return 'BWFAN_TVE_Lead_Form_Submit';
}
