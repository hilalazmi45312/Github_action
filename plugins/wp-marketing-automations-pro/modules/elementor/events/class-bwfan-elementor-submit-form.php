<?php

// namespace ElementorPro\Classes;

use ElementorPro\Plugin;

#[\AllowDynamicProperties]
final class BWFAN_Elementor_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $page_id = 0;
	public $post_id = 0;
	public $form_title = '';
	public $fields = array();
	public $entry = array();
	public $email = '';
	public $mark_subscribe = false;
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'elementor-forms', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'elementor-forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Elementor', 'wp-marketing-automations-pro' );
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
		add_action( 'wp_ajax_bwfan_get_elementor_page_forms', array( $this, 'bwfan_get_elementor_page_forms' ) );
		add_action( 'wp_ajax_bwfan_get_elementor_form_fields', array( $this, 'bwfan_get_elementor_form_fields' ) );
		add_action( 'elementor_pro/forms/new_record', array( $this, 'process' ), 10, 2 );
		add_filter( 'bwfan_all_event_js_data', array( $this, 'add_form_data' ), 10, 2 );
		add_action( 'in_admin_footer', array( $this, 'bwfan_elementor_form_js' ), 88 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'page_options', $data['page_options'] );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'form_options', $data['form_options'] );
		}
	}

	public function get_view_data() {
		$event_view_data                 = array();
		$post_data                       = BWFAN_Elementor_Common::get_elementor_form_by_type( false, true );
		$event_view_data['page_options'] = empty( $post_data ) ? '' : $post_data;
		$event_form_page_data            = array();

		foreach ( $post_data as $key => $group ) {
			foreach ( $group['posts'] as $page ) {
				$event_form_page_data[ $key ][ $page['post_id'] ] = $this->get_page_form_data( $page['post_id'] );
			}
		}
		$event_view_data['form_options'] = $event_form_page_data;

		return $event_view_data;

	}

	/**
	 * Get page form details
	 *
	 * @param int $post_id
	 *
	 * @return array|void
	 */
	public function get_page_form_data( $post_id = 0 ) {
		if ( empty( $post_id ) ) {
			return array();
		}

		if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
			return;
		}
		try {
			$document = Plugin::elementor()->documents->get( $post_id );
		} catch ( Error $e ) {
			return array();
		}

		if ( empty( $document ) ) {
			return array();
		}

		$data = $document->get_elements_data();

		/** Get Forms for top level form widget (Global Widget) */
		$forms = array();
		if ( isset( $data[0]['widgetType'] ) && 'form' === $data[0]['widgetType'] ) {
			$forms[0] = $data[0];
		}

		/** Get Forms from Elementor Data Iterator, if no top level form present */
		if ( empty( $forms ) ) {
			Plugin::elementor()->db->iterate_data( $data, function ( $element ) use ( &$forms ) {
				if ( isset( $element['widgetType'] ) && in_array( $element['widgetType'], [ 'form', 'global' ] ) ) {
					$forms[] = $element;
				}

				return $element;
			} );
		}

		$elem_post_fields = array();
		foreach ( $forms as $form_key => $elem_fields ) {
			if ( ! isset( $elem_fields['settings'] ) ) {
				continue;
			}
			$elem_post_fields[ $form_key ]['form_name']   = $elem_fields['settings']['form_name'];
			$elem_post_fields[ $form_key ]['form_fields'] = $elem_fields['settings']['form_fields'];
			$elem_post_fields[ $form_key ]['form_id']     = $elem_fields['id'];

			if ( empty( $elem_post_fields[ $form_key ]['form_fields'] ) ) {
				continue;
			}

			foreach ( $elem_post_fields[ $form_key ]['form_fields'] as $field_key => $form_field ) {
				/** Get Field slug */
				$field_slug = isset( $form_field['custom_id'] ) ? $form_field['custom_id'] : ( isset( $form_field['_id'] ) ? $form_field['_id'] : '' );

				/** Get Field Label from either Elementor's field Label, Placeholder or Custom_ID */
				$field_label = isset( $form_field['field_label'] ) ? $form_field['field_label'] : '';
				$field_label = empty( $field_label ) && isset( $form_field['placeholder'] ) ? $form_field['placeholder'] : $field_label;
				$field_label = empty( $field_label ) && ! empty( $field_slug ) ? $field_slug : $field_label;

				/** Get Field Type */
				$field_type = isset( $form_field['field_type'] ) ? $form_field['field_type'] : '';

				/** Unset previous data and Gather new field data */
				unset( $elem_post_fields[ $form_key ]['form_fields'][ $field_key ] );
				$elem_post_fields[ $form_key ]['form_fields'][ $field_key ] = [
					'field_type'  => $field_type,
					'field_label' => $field_label,
					'field_slug'  => $field_slug,
				];
			}
		}

		return $elem_post_fields;
	}

	/**
	 *  load additional script only when elementor_popup_form_submit event selected
	 */
	public function bwfan_elementor_form_js() {
		// you can check if this is the right page

		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'autonami-automations-pro' || ! isset( $_GET['edit'] ) || empty( $_GET['edit'] ) ) {
			return;
		}

		?>
        <script type='text/javascript'>
            jQuery('body').on('bwfan-change-rule', function (e, v) {
                var event = (_.has(BWFAN_Auto, 'uiDataDetail') && _.has(BWFAN_Auto.uiDataDetail, 'trigger') && _.has(BWFAN_Auto.uiDataDetail.trigger, 'event')) ? BWFAN_Auto.uiDataDetail.trigger.event : '';
                if (event === '' || event !== 'elementor_form_submit') {
                    return;
                }

                if ('elementor_form_field' !== v.value) {
                    return;
                }
                var options = '';
                page_form_fields = bwfan_events_js_data['elementor_form_submit']['selected_form_fields'];
                _.each(page_form_fields, function (value, key) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });
                v.scope.find('.bwfan_elementor_form_fields').html(options);
            });
            jQuery('body').on('bwfan-selected-merge-tag', function (e, v) {
                var event = (_.has(BWFAN_Auto, 'uiDataDetail') && _.has(BWFAN_Auto.uiDataDetail, 'trigger') && _.has(BWFAN_Auto.uiDataDetail.trigger, 'event')) ? BWFAN_Auto.uiDataDetail.trigger.event : '';
                if (event === '' || event !== 'elementor_form_submit') {
                    return;
                }
                if ('elementor_form_field' !== v.tag) {
                    return;
                }

                var options = '';
                var i = 1;
                var selected = '';

                _.each(bwfan_events_js_data['elementor_form_submit']['selected_form_fields'], function (value, key) {
                    selected = (i == 1) ? 'selected' : '';
                    options += '<option value="' + key + '" ' + selected + '>' + value + '</option>';
                    i++;
                });

                jQuery('.bwfan_elementor_form_fields').html(options);
                jQuery('.bwfan_tag_select').trigger('change');
            });

        </script>
		<?php
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {

		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_page_id = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'page_id')) ? data.eventSavedData.page_id : '';
            selected_form_id = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'form_id')) ? data.eventSavedData.form_id : '';
            selected_field_map =(_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'email_map')) ? data.eventSavedData.email_map : '';
            form_options = (_.has(data, 'eventFieldsOptions') &&_.has(data.eventFieldsOptions, 'form_options')) ? data.eventFieldsOptions.form_options:{};
            selected_page_forms='';
            selected_form_fields ='';
            page_form ='';
            if(_.size(form_options)>0){
            _.each(form_options, function(group, group_key) {
            _.each(group,function(value, key){
            if(key === selected_page_id){
            page_form = value;
            }
            });
            });
            }
            #>

            <#
            // get form fields
            if(_.isArray(page_form)){
            _.each(page_form,function(value,key){
            if(value['form_id'] === selected_form_id){
            selected_page_forms =value['form_fields'];
            }
            });
            }
            bwfan_events_js_data['elementor_form_submit']['page_form_fields'] = selected_page_forms;
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Page', 'wp-marketing-automations-pro' ); ?></label>
                <select id="bwfan-elementor_page_submit_page_id" class="bwfan-input-wrapper" name="event_meta[page_id]">
                    <option value=""><?php esc_html_e( 'Choose Page', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'page_options') && _.isObject(data.eventFieldsOptions.page_options) ) {
                    _.each( data.eventFieldsOptions.page_options, function( post_type, key ){
                    #>
                    <optgroup label="{{post_type['title']}}">
                        <#
                        _.each( post_type['posts'], function( value, key ){
                        selected =(value['post_id'] == selected_page_id)?'selected':'';
                        #>
                        <option value="{{value['post_id']}}" {{selected}}>{{value['post_title']}}</option>
                        <# }) #>
                    </optgroup>
                    <# })
                    } #>
                </select>
            </div>
            <#
            show_form_select = _.size(page_form)>0?'block':'none';
            select_form_name = _.size(page_form)>0?'name=event_meta[form_id]':'';
            #>
            <div class="bwfan-elementor-form-section bwfan-col-sm-12 bwfan-p-0 bwfan-mb-15">
                <div class="bwfan_spinner bwfan_hide"></div>
                <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-elementor-forms " style="display:{{show_form_select}}">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Form', 'wp-marketing-automations-pro' ); ?></label>
                    <select id="bwfan-elementor_page_submit_form_id" class="bwfan-input-wrapper" data-name="event_meta[form_id]" {{select_form_name}}>
                        <option value=""><?php esc_html_e( 'Choose Form', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        _.each( page_form, function( value, key ){
                        selected =(value['form_id'] == selected_form_id)?'selected':'';
                        #>
                        <option value="{{value['form_id']}}" {{selected}}>{{value['form_name']}}</option>
                        <# })
                        #>
                    </select>
                </div>
            </div>

            <#
            show_form_hidden = _.size(page_form)>0?'none':'block';
            hidden_form_name = _.size(page_form)>0?'':'name=event_meta[form_id]';
            #>
            <input type="hidden" data-name="event_meta[form_id]" {{hidden_form_name}} value="{{selected_form_id}}" id="bwfan-elementor_form_id" style="display:{{show_form_hidden}}"/>

            <#
            show_mapping = !_.isEmpty(selected_form_id)?'block':'none';
            #>
            <div class="bwfan-elementor-forms-map bwfan-col-sm-12 bwfan-p-0">
                <div class="bwfan_spinner bwfan_hide"></div>
                <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-elementor-field-map" style="display:{{show_mapping}}">
                    <label for="" class="bwfan-label-title">
						<?php esc_html_e( 'Select Email', 'wp-marketing-automations-pro' ); ?>
                        <div class="bwfan_tooltip" data-size="2xl">
                            <span class="bwfan_tooltip_text" data-position="top">Map the email field to be used by appropriate Rules and Actions.</span>
                        </div>
                    </label>
                    <select id="bwfan-elementor_email_field_map" class="bwfan-input-wrapper" name="event_meta[email_map]">
                        <option value=""><?php esc_html_e( 'None', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        _.each( bwfan_events_js_data['elementor_form_submit']['selected_form_fields'], function( value, key ){
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
            //on change elementor page
            jQuery(document).on('change', '#bwfan-elementor_page_submit_page_id', function () {
                var selected_id = jQuery(this).val();
                bwfan_events_js_data['elementor_form_submit']['page_selected_id'] = selected_id;
                if (_.isEmpty(selected_id)) {
                    jQuery("#bwfan-elementor_form_id").val('');
                    jQuery(".bwfan-elementor-forms").hide();
                    jQuery("#bwfan-elementor_page_submit_form_id").removeAttr('name');
                    jQuery("#bwfan-elementor_form_id").show();
                    jQuery("#bwfan-elementor_form_id").attr('name', 'event_meta[form_id]');
                    bwfan_events_js_data['elementor_form_submit']['page_form_fields'] = '';
                    jQuery(".bwfan-elementor-field-map").hide();
                    update_elementor_email_field_map([]);
                    return;
                }
                jQuery.ajax({
                    method: 'post',
                    url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
                    datatype: "JSON",
                    data: {
                        action: 'bwfan_get_elementor_page_forms',
                        id: selected_id,
                    },
                    success: function (response) {
                        bwfan_events_js_data['elementor_form_submit']['all_form_data'] = [];

                        jQuery('.bwfan-elementor-form-section .bwfan_spinner').removeClass('bwfan_hide');
                        jQuery(".bwfan-elementor-field-map").hide();
                        option_html = '<option value="">Choose Form</option>';
                        _.each(response.forms, function (value, key) {
                                if (!_.isEmpty(value['form_id']) || 0 !== parseInt(value['form_id'])) {
                                    option_html += '<option value=' + value["form_id"] + '>' + value["form_name"] + '</option>';
                                }
                            }
                        )

                        //get the selected form fields on change
                        setTimeout(function () {
                            jQuery('.bwfan-elementor-form-section .bwfan_spinner').addClass('bwfan_hide');
                            jQuery(".bwfan-elementor-forms").show();
                            jQuery("#bwfan-elementor_page_submit_form_id").attr('name', 'event_meta[form_id]');
                            jQuery("#bwfan-elementor_form_id").hide();
                            jQuery("#bwfan-elementor_form_id").removeAttr('name');
                            jQuery("#bwfan-elementor_page_submit_form_id").html(option_html);
                        }, 500);
                        bwfan_events_js_data['elementor_form_submit']['all_form_data'] = response.forms;
                    }
                });
            });

            //on change elementor page form
            jQuery(document).on('change', '#bwfan-elementor_page_submit_form_id', function () {
                var form_selected_id = jQuery(this).val();
                var page_selected_id = jQuery("#bwfan-elementor_page_submit_page_id option:selected").val();
                bwfan_events_js_data['elementor_form_submit']['form_selected_id'] = form_selected_id;
                if (_.isEmpty(form_selected_id)) {
                    jQuery("#bwfan-elementor_form_id").val('');
                    bwfan_events_js_data['elementor_form_submit']['page_form_fields'] = [];
                    jQuery(".bwfan-elementor-field-map").hide();
                    update_elementor_email_field_map([]);
                } else {
                    _.each(bwfan_events_js_data['elementor_form_submit']['all_form_data'], function (form) {
                        if (form_selected_id == form["form_id"]) {
                            page_form_fields = form['form_fields'];
                            selected_form_fields = {};
                            _.each(page_form_fields, function (field, index) {
                                selected_form_fields[field['field_slug']] = field['field_label'];
                            });

                            bwfan_events_js_data['elementor_form_submit']['page_form_fields'] = page_form_fields;
                            bwfan_events_js_data['elementor_form_submit']['selected_form_fields'] = selected_form_fields;
                            update_elementor_email_field_map(selected_form_fields);
                            jQuery(".bwfan-elementor-forms-map .bwfan_spinner").addClass('bwfan_hide');
                            jQuery(".bwfan-elementor-field-map").show();
                            return;
                        }
                    });

                }
            });

            function update_elementor_email_field_map(fields) {
                jQuery("#bwfan-elementor_email_field_map").html('');
                var option = '<option value="">None</option>';
                if (_.size(fields) > 0 && (_.isObject(fields) || _.isArray(fields))) {
                    _.each(fields, function (v, e) {
                        option += '<option value="' + e + '">' + v + '</option>';
                    });
                }
                jQuery("#bwfan-elementor_email_field_map").html(option);
            }

        </script>
		<?php
	}

	public function bwfan_get_elementor_page_forms() {
		BWFAN_PRO_Common::nocache_headers();
		$page_id  = absint( sanitize_text_field( $_POST['id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$is_popup = isset( $_POST['popup'] ) && sanitize_text_field( $_POST['popup'] ) === 'true'; // WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( version_compare( BWFAN_VERSION, '3.5.4.fix.4003', '>=' ) && $is_popup !== BWFAN_Elementor_Common::is_popup( $page_id ) ) {
			return [];
		}

		$page_form_data = $this->get_page_form_data( $page_id );

		if ( empty( $page_form_data ) ) {
			wp_send_json( array(
				'forms' => '',
			) );
		}
		/** fields for v2 */
		if ( isset( $_POST['fromApp'] ) && $_POST['fromApp'] ) {
			$finalarr = [];
			foreach ( $page_form_data as $key => $value ) {
				$finalarr[ $key ] = [
					'key'   => $value['form_id'],
					'value' => $value['form_name'],
				];
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
		}

		wp_send_json( array(
			'forms' => $page_form_data,
		) );
	}

	public function bwfan_get_elementor_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$page_id = absint( sanitize_text_field( $_POST['page_id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$form_id = sanitize_text_field( $_POST['form_id'] ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( empty( $page_id ) && empty( $form_id ) ) {
			wp_send_json( array(
				'fields' => '',
			) );
		}

		$page_form_data = $this->get_page_form_data( $page_id );
		if ( empty( $page_form_data ) ) {
			wp_send_json( array(
				'fields' => '',
			) );
		}

		$form_fields = $this->get_form_fields_v2( $form_id, $page_form_data );

		if ( empty( $form_fields['form_fields'] ) ) {
			wp_send_json( array(
				'fields' => [],
			) );
		}

		/** fields for v2 */
		if ( isset( $_POST['fromApp'] ) && $_POST['fromApp'] ) {
			$finalarr = [];
			foreach ( $form_fields['form_fields'] as $key => $value ) {
				$finalarr[ $key ] = [
					'key'   => $value['field_slug'],
					'value' => isset( $value['field_label'] ) && ! empty( $value['field_label'] ) ? $value['field_label'] : $value['field_slug'],
				];
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
		}


		wp_send_json( array(
			'fields' => $form_fields,
		) );
	}

	public function get_form_fields_v2( $form_id, $page_data ) {
		$form_fields_data = [];
		foreach ( $page_data as $key => $data ) {
			if ( strval( $form_id ) !== strval( $data['form_id'] ) ) {
				continue;
			}

			$form_fields_data = $page_data[ $key ];
		}

		return $form_fields_data;

	}

	public function process( $record, $ajax_handler ) {
		$form_id   = $record->get_form_settings( 'id' );
		$page_id   = isset( $_POST['queried_id'] ) ? absint( $_POST['queried_id'] ) : absint( $_POST['post_id'] );
		$form_name = $record->get_form_settings( 'form_name' );

		/** If Global Widget exists in Popup */
		if ( BWFAN_Elementor_Common::is_popup( absint( $_POST['post_id'] ) ) ) {
			$page_id = absint( $_POST['post_id'] );
		}

		$form_entries = array_map( function ( $field ) {
			return isset( $field['value'] ) ? $field['value'] : '';
		}, $record->get( 'fields' ) );

		$data                   = $this->get_default_data();
		$data['form_id']        = $form_id;
		$data['form_title']     = $form_name;
		$data['entry']          = $form_entries;
		$data['page_id']        = $page_id;
		$data['post_id']        = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$data['fields']         = $this->get_form_fields( $form_id, $page_id );
		$data['global_form_id'] = $this->global_form_id;

		if ( empty( $data['fields'] ) && 0 < intval( $data['post_id'] ) ) {
			$data['fields'] = $this->get_form_fields( $form_id, intval( $data['post_id'] ) );
		}
		if ( empty( $data['fields'] ) ) {
			BWFAN_Common::log_test_data( $this->get_slug(), 'elementor_form_fields_error', true );
			BWFAN_Common::log_test_data( $data, 'elementor_form_fields_error', true );

			return;
		}
		$this->send_async_call( $data );
	}

	public function get_form_fields( $form_id, $page_id ) {
		if ( empty( $form_id ) || empty( $page_id ) ) {
			return array();
		}

		/** If global form, then set both form_id and page_id as global_form_id */
		$global_form_id       = $this->maybe_get_global_form_wp_id( $page_id, $form_id );
		$this->global_form_id = $global_form_id;
		if ( false !== $global_form_id ) {
			$form_id = $global_form_id;
		}

		$page_forms = $this->get_page_form_data( $page_id );
		if ( ! is_array( $page_forms ) || empty( $page_forms ) ) {
			return array();
		}

		$current_form = false;
		foreach ( $page_forms as $form ) {
			if ( strval( $form_id ) === strval( $form['form_id'] ) ) {
				$current_form = $form;
				break;
			}
		}

		if ( empty( $current_form ) ) {
			return array();
		}

		$fields = array();
		foreach ( $current_form['form_fields'] as $key => $field ) {
			$fields[ $key ] = $field;
		}

		return array(
			$form_id => $fields,
		);
	}

	public function maybe_get_global_form_wp_id( $page_id, $form_id ) {
		$forms = BWFAN_Elementor_Common::get_forms_by_global_form_page( $page_id );
		if ( empty( $forms ) ) {
			return false;
		}

		foreach ( $forms as $form ) {
			if ( strval( $form_id ) === strval( $form['id'] ) ) {
				return $form['id'];
			}
		}

		return false;
	}

	public function maybe_get_global_form_id( $page_id, $form_id ) {
		$forms = BWFAN_Elementor_Common::get_forms_by_page_id( $page_id );
		if ( empty( $forms ) ) {
			return false;
		}

		if ( in_array( $form_id, $forms, true ) ) {
			return $form_id;
		}

		return false;
	}

	public function add_form_data( $event_js_data, $automation_meta ) {
		if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $event_js_data['elementor_form_submit'] ) || ! isset( $automation_meta['event_meta']['page_id'] ) || ! isset( $automation_meta['event_meta']['form_id'] ) ) {
			return $event_js_data;
		}

		if ( isset( $automation_meta['event'] ) && ! empty( $automation_meta['event'] ) && 'elementor_form_submit' !== $automation_meta['event'] ) {
			return $event_js_data;
		}
		$selected_fields                                            = array();
		$event_js_data['elementor_form_submit']['page_selected_id'] = $automation_meta['event_meta']['page_id'];
		$event_js_data['elementor_form_submit']['form_selected_id'] = $automation_meta['event_meta']['form_id'];
		$form_fields                                                = $this->get_form_fields( $automation_meta['event_meta']['form_id'], $automation_meta['event_meta']['page_id'] );

		$page_field = array();
		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $key => $fields ) {
				$page_field[] = $fields;
				foreach ( $fields as $fie ) {
					$selected_fields[ $fie['field_slug'] ] = $fie['field_label'];
				}
			}
		}

		$event_js_data['elementor_form_submit']['page_form_fields']     = $page_field;
		$event_js_data['elementor_form_submit']['selected_form_fields'] = $selected_fields;

		return $event_js_data;
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {

		$email_map   = isset( $automation_data['event_meta']['email_map'] ) ? $automation_data['event_meta']['email_map'] : false;
		$this->email = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : ( isset( $this->entry['email'] ) && is_email( $this->entry['email'] ) ? $this->entry['email'] : '' );

		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->page_id, 'page_id' );
		BWFAN_Core()->rules->setRulesData( $this->post_id, 'post_id' );
		BWFAN_Core()->rules->setRulesData( $this->entry, 'entry' );
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

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
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
		$data_to_send['global']['fields']     = $this->fields;
		$data_to_send['global']['page_id']    = $this->page_id;
		$data_to_send['global']['post_id']    = $this->post_id;
		$data_to_send['global']['entry']      = $this->entry;
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
		if ( isset( $global_data['page_id'] ) && ! empty( $global_data['page_id'] ) ) {
			?>

            <li>
                <strong><?php echo esc_html__( 'Page :', 'wp-marketing-automations-pro' ); ?> </strong>
                <a href="<?php echo esc_url( get_edit_post_link( $global_data['page_id'] ) ); ?>" target="_blank"><?php echo esc_sql( get_the_title( $global_data['page_id'] ) ); ?></a>
            </li>
		<?php } ?>
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
				'form_id'    => $task_meta['global']['form_id'],
				'page_id'    => absint( $task_meta['global']['page_id'] ),
				'post_id'    => absint( $task_meta['global']['post_id'] ),
				'form_title' => $task_meta['global']['form_title'],
				'fields'     => $task_meta['global']['fields'],
				'entry'      => $task_meta['global']['entry'],
				'email'      => $task_meta['global']['email'],
				'slug'       => $this->get_slug(),
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->page_id    = BWFAN_Common::$events_async_data['page_id'];
		$this->post_id    = BWFAN_Common::$events_async_data['post_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->entry      = BWFAN_Common::$events_async_data['entry'];

		return $this->run_automations();
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->form_id        = BWFAN_Common::$events_async_data['form_id'];
		$this->page_id        = BWFAN_Common::$events_async_data['page_id'];
		$this->post_id        = BWFAN_Common::$events_async_data['post_id'];
		$this->form_title     = BWFAN_Common::$events_async_data['form_title'];
		$this->fields         = BWFAN_Common::$events_async_data['fields'];
		$this->entry          = BWFAN_Common::$events_async_data['entry'];
		$this->global_form_id = BWFAN_Common::$events_async_data['global_form_id'] ?? '';


		$map_fields     = isset( $automation_data['event_meta']['bwfan-form-field-map'] ) ? $automation_data['event_meta']['bwfan-form-field-map'] : [];
		$email_map      = isset( $map_fields['bwfan_email_field_map'] ) ? $map_fields['bwfan_email_field_map'] : '';
		$first_name_map = isset( $map_fields['bwfan_first_name_field_map'] ) ? $map_fields['bwfan_first_name_field_map'] : '';
		$last_name_map  = isset( $map_fields['bwfan_last_name_field_map'] ) ? $map_fields['bwfan_last_name_field_map'] : '';
		$phone_map      = isset( $map_fields['bwfan_phone_field_map'] ) ? $map_fields['bwfan_phone_field_map'] : '';

		$this->mark_subscribe = isset( $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ) ? $automation_data['event_meta']['bwfan-mark-contact-subscribed'] : 0;
		$this->email          = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : ( isset( $this->entry['email'] ) && is_email( $this->entry['email'] ) ? $this->entry['email'] : '' );
		$this->first_name     = ( ! empty( $first_name_map ) && isset( $this->entry[ $first_name_map ] ) ) ? $this->entry[ $first_name_map ] : "";
		$this->last_name      = ( ! empty( $last_name_map ) && isset( $this->entry[ $last_name_map ] ) ) ? $this->entry[ $last_name_map ] : '';
		$this->contact_phone  = ( ! empty( $phone_map ) && isset( $this->entry[ $phone_map ] ) ) ? $this->entry[ $phone_map ] : '';

		$automation_data['form_id']                 = $this->form_id;
		$automation_data['form_title']              = $this->form_title;
		$automation_data['fields']                  = $this->fields;
		$automation_data['email']                   = $this->email;
		$automation_data['page_id']                 = $this->page_id;
		$automation_data['post_id']                 = $this->post_id;
		$automation_data['entry']                   = $this->entry;
		$automation_data['first_name']              = $this->first_name;
		$automation_data['last_name']               = $this->last_name;
		$automation_data['contact_phone']           = $this->contact_phone;
		$automation_data['mark_contact_subscribed'] = $this->mark_subscribe;
		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
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
			$form_wp_id = $this->maybe_get_global_form_wp_id( $this->page_id, $this->form_id );
			if ( false === $form_wp_id ) {
				$form_wp_id = $this->maybe_get_global_form_wp_id( $this->post_id, $this->form_id );
			}

			$form_id       = ( false !== $form_wp_id ) ? $form_wp_id : $this->form_id;
			$match_form_id = isset( $automation_data['event_meta']['form_id'] ) ? strval( $automation_data['event_meta']['form_id'] ) : '';
			if ( strval( $form_id ) !== $match_form_id ) {
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
		if ( empty( $automation_data ) || ! isset( $automation_data['form_id'] ) ) {
			return false;
		}

		$is_form_global = isset( $automation_data['event_meta']['is_global_form'] ) ? $automation_data['event_meta']['is_global_form'] : false;
		if ( ! empty( $is_form_global ) ) {
			return ( strval( $automation_data['form_id'] ) === strval( $automation_data['global_form_id'] ) );
		}

		$form_id = $this->maybe_get_global_form_id( $automation_data['page_id'], $automation_data['form_id'] );
		$page_id = $automation_data['page_id'];
		if ( empty( $form_id ) ) {
			$form_id = $this->maybe_get_global_form_id( $automation_data['post_id'], $automation_data['form_id'] );
			$page_id = $automation_data['post_id'];
		}

		$selected_form_id = $automation_data['event_meta']['elementor_popup_submit_page_id'];
		if ( is_array( $selected_form_id ) && ! empty( $selected_form_id[0] ) && is_array( $selected_form_id[0] ) && isset( $selected_form_id[0]['id'] ) ) {
			$selected_form_id = intval( $selected_form_id[0]['id'] );
		}

		return intval( $page_id ) === intval( $selected_form_id ) && strval( $form_id ) === strval( $automation_data['event_meta']['elementor_submit_form_id'] );
	}

	/**
	 * v2 Method: Update event meta data for older saved automations
	 *
	 * @param $event_meta
	 *
	 * @return array
	 */
	public function validate_event_meta_data( $event_meta ) {
		if ( empty( $event_meta['elementor_popup_submit_page_id'] ) || is_array( $event_meta['elementor_popup_submit_page_id'] ) ) {
			return $event_meta;
		}

		$page_id = intval( $event_meta['elementor_popup_submit_page_id'] );
		// get page title
		$page_title = get_the_title( $page_id ) ?? "( $page_id )";

		$event_meta['elementor_popup_submit_page_id'] = [
			[
				'id'   => $page_id,
				'name' => $page_title,
			]
		];

		return $event_meta;
	}

	/**
	 * v2 Method: Get fields Schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$fields = [];

		$toggler = [
			[
				'id'    => 'elementor_popup_submit_page_id',
				'value' => '',
			],
		];


		if ( version_compare( BWFAN_VERSION, '3.5.4.fix.4003', '>=' ) ) {
			$fields = [
				[
					"id"                  => 'elementor_popup_submit_page_id',
					"label"               => __( 'Select Page', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'elementor_search_page',
						'slug'      => 'elementor_search_page',
						'labelText' => __( 'Select page', 'wp-marketing-automations-pro' ),
						'format'    => 'url'
					],
					"fieldChange"         => 'elementor_popup_submit_page_type',
					"allowFreeTextSearch" => false,
					"required"            => true,
					"multiple"            => false,
					"errorMsg"            => __( "Page is required.", 'wp-marketing-automations-pro' ),
					'description'         => '',
				],
			];
		} else {
			$data  = $this->get_view_data();
			$pages = [];
			if ( ! empty( $data['page_options'] ) ) {
				foreach ( $data['page_options'] as $post_data ) {
					if ( ! isset( $post_data['posts'] ) || ! is_array( $post_data['posts'] ) ) {
						continue;
					}
					$pages = array_merge( $pages, $post_data['posts'] );
				}
			}

			$final_pages = [
				[
					'value' => '',
					'label' => ! empty( $pages ) ? __( 'Select', 'wp-marketing-automations-pro' ) : __( 'No pages to select', 'wp-marketing-automations-pro' )
				]
			];

			if ( ! empty( $pages ) ) {
				foreach ( $pages as $page ) {
					$final_pages[] = [
						'value' => $page['post_id'],
						'label' => $page['post_title']
					];
				}
			}

			$fields[] = [
				'id'          => 'elementor_popup_submit_page_id',
				'label'       => __( 'Select Page', 'wp-marketing-automations-pro' ),
				'type'        => 'select',
				'options'     => $final_pages,
				'class'       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				'required'    => true,
				"errorMsg"    => __( "Page is required.", 'wp-marketing-automations-pro' ),
				'description' => '',
			];
		}

		return array(
			...$fields,
			[
				'id'           => 'elementor_submit_form_id',
				'type'         => 'ajax',
				'label'        => __( 'Select Form', 'wp-marketing-automations-pro' ),
				"class"        => 'bwfan-input-wrapper',
				"required"     => true,
				"errorMsg"     => __( "Form is required.", 'wp-marketing-automations-pro' ),
				"emptyMessage" => __( "No embedded form found on this page.", 'wp-marketing-automations-pro' ),
				'placeholder'  => __( 'Select', 'wp-marketing-automations-pro' ),
				"description"  => "",
				"ajax_cb"      => 'bwfan_get_elementor_page_forms',
				"ajax_field"   => [
					'id'    => 'elementor_popup_submit_page_id',
					'popup' => false,
				],
				"fieldChange"  => 'elementor_popup_submit_page_id',
				"toggler"      => [
					'fields'   => $toggler,
					'relation' => 'AND',
				]
			],
			[
				'id'           => 'bwfan-form-field-map',
				'type'         => 'bwf_form_submit',
				"class"        => 'bwfan-input-wrapper',
				"required"     => true,
				'placeholder'  => __( 'Select', 'wp-marketing-automations-pro' ),
				"emptyMessage" => __( "No field found on this form.", 'wp-marketing-automations-pro' ),
				"description"  => "",
				"ajax_cb"      => 'bwfan_get_elementor_form_fields',
				"ajax_field"   => [
					'page_id' => 'elementor_popup_submit_page_id',
					'form_id' => 'elementor_submit_form_id'
				],
				"fieldChange"  => 'elementor_submit_form_id',
				"toggler"      => [
					'fields'   => array(
						...$toggler,
						array(
							'id'    => 'elementor_submit_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			],
			[
				'id'            => 'is_global_form',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark If Global Form Is Used On Multiple Pages', 'wp-marketing-automations-pro' ),
				'description'   => '',
				"fieldChange"   => 'elementor_popup_submit_page_id',
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark Contact as Subscribed', 'wp-marketing-automations-pro' ),
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						...$toggler,
						array(
							'id'    => 'elementor_submit_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			]
		);
	}
}

/**
 * Register this event to a source.
 * This will show the current event in a dropdown in a single automation screen.
 */
if ( bwfan_is_elementorpro_active() ) {
	return 'BWFAN_Elementor_Form_Submit';
}
