<?php

/**
 * Class BWFAN_Autonami_Webhook_Received
 */
#[AllowDynamicProperties]
final class BWFAN_Autonami_Webhook_Received extends BWFAN_Event {
	private static $instance = null;
	private $automation_id = null;
	private $automation_key = '';
	private $localized_automation_key = '';

	private $webhook_data = array();
	private $referer = '';
	private $email = '';
	private $email_map_key = '';
	private $webhook_version = null;
	private $webhook_automation_id = null;
	private $logs = array();

	private function __construct() {
		$this->optgroup_label         = __( 'Automation', 'wp-marketing-automations-pro' );
		$this->event_name             = __( 'Webhook Received', 'wp-marketing-automations-pro' );
		$this->event_desc             = __( 'This event runs after a webhook URL receives the data', 'wp-marketing-automations-pro' );
		$this->event_merge_tag_groups = array( 'wp_webhook', 'bwf_contact' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast',
			'bwf_webhook'
		);
		$this->customer_email_tag     = '';
		$this->v2                     = true;
		$this->need_unique_key        = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function bwfan_add_webhook_endpoint() {
		register_rest_route( 'autonami/v1', '/webhook(?:/(?P<bwfan_autonami_webhook_id>\d+))?', [
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bwfan_capture_async_events' ),
				'permission_callback' => '__return_true',
				'args'                => [
					array( 'bwfan_autonami_webhook_id' => 0 ),
					array( 'bwfan_autonami_webhook_key' => 0 ),
				],
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'bwfan_capture_async_events' ),
				'permission_callback' => '__return_true',
				'args'                => [
					array( 'bwfan_autonami_webhook_id' => 0 ),
					array( 'bwfan_autonami_webhook_key' => 0 ),
				],
			)
		] );
	}

	public function load_hooks() {
		add_action( 'rest_api_init', array( $this, 'bwfan_add_webhook_endpoint' ) );
		add_action( 'bwfan_webhook_autonami', array( $this, 'process' ), 10, 6 );
		add_action( 'wp_ajax_bwfan_get_refresh_data', array( $this, 'send_latest_webhook_data' ) );
	}

	public function bwfan_capture_async_events( WP_REST_Request $request ) {
		BWFAN_PRO_Common::nocache_headers();
		$content_type = $request->get_content_type();
		$body_params  = [];
		if ( isset( $content_type['value'] ) && 'application/x-www-form-urlencoded' === $content_type['value'] ) {
			$body        = $request->get_body();
			$body_params = ! empty( $body ) ? json_decode( $body, true ) : '';
			$body_params = is_array( $body_params ) ? array_merge( $body_params, $request->get_query_params() ) : [];
		}
		$request_params = ! empty( $body_params ) ? $body_params : $request->get_params();

		$this->set_log( 'fka webhook received' );
		$this->set_log( $request_params );
		$this->log();

		if ( empty( $request_params ) ) {
			wp_send_json( [ 'message' => 'Security failed.', 'success' => false ] );
		}

		//check request params contain both the key and id
		if ( ( ! isset( $request_params['bwfan_autonami_webhook_key'] ) || empty( $request_params['bwfan_autonami_webhook_key'] ) ) && ( ! isset( $request_params['bwfan_autonami_webhook_id'] ) || empty( $request_params['bwfan_autonami_webhook_id'] ) ) ) {
			wp_send_json( [ 'message' => 'Security failed.', 'success' => false ] );
		}

		//get automation key using automation id
		$automation_id  = $request_params['bwfan_autonami_webhook_id'];
		$meta           = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );
		$automation_key = $meta['bwfan_unique_key'];

		//check if the automation key exist in database
		if ( empty( $automation_key ) ) {
			wp_send_json( [ 'message' => 'Automation does not have the unique key.', 'success' => false ] );
		}

		//validate automation key
		if ( $automation_key !== $request_params['bwfan_autonami_webhook_key'] ) {
			wp_send_json( [ 'message' => 'Automation unique key is not matching.', 'success' => false ] );
		}

		$webhook_data = $request_params;
		unset( $webhook_data['bwfan_autonami_webhook_key'] );
		unset( $webhook_data['bwfan_autonami_webhook_id'] );
		unset( $meta['bwfan_webhook_url'] );
		$webhook_data = $this->convert_value_to_string( $webhook_data );

		/** Set Webhook Data in args */
		$args = array(
			'webhook_data' => $webhook_data,
			'received_at'  => ( new DateTime() )->getTimestamp(),
			'referer'      => $request->get_header( 'referer' ),
		);

		/** Save the webhook data in event meta of automation */
		$meta            = array_replace( $meta, $args );
		$data_to_update  = array( 'meta_value' => maybe_serialize( $meta ) );
		$where_to_update = array(
			'bwfan_automation_id' => $automation_id,
			'meta_key'            => 'event_meta'
		);
		BWFAN_Model_Automationmeta::update( $data_to_update, $where_to_update );
		/** as it disturb the passing of data in webhook process **/
		if ( isset( $meta['bwfan_automation_run'] ) ) {
			unset( $meta['bwfan_automation_run'] );
		}

		/** as it disturb the passing of data in webhook process **/
		if ( isset( $meta['enter_automation_on_active_contact'] ) ) {
			unset( $meta['enter_automation_on_active_contact'] );
		}

		/** Only run this when automation is active. (Check is necessary because of Capture Data perspective) */
		$automation_data               = BWFAN_Model_Automations::get( $automation_id );
		$meta['webhook_version']       = isset( $automation_data['v'] ) ? $automation_data['v'] : 1;
		$meta['webhook_automation_id'] = $automation_id;

		if ( isset( $meta['bwfan_email_map_key'] ) ) {
			unset( $meta['bwfan_email_map_key'] );
		}

		/** Deliberately send 0 as this was making every request different */
		$meta['received_at'] = 0;

		if ( 1 === absint( $automation_data['status'] ) ) {
			$this->before_process_webhook( $meta );
		}

		/** Send back 200 response */
		wp_send_json( array( 'success' => true ), 200 );
	}

	/**
	 * convert value in string
	 *
	 * @param $webhook_fields
	 *
	 * @return array
	 */
	public function convert_value_to_string( $webhook_values ) {
		foreach ( $webhook_values as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->convert_value_to_string( $webhook_values[ $key ] );
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			$webhook_values[ $key ] = strval( $value );
		}

		return $webhook_values;
	}

	public function before_process_webhook( $args ) {
		$hook  = 'bwfan_webhook_autonami';
		$group = 'autonami';

		if ( bwf_has_action_scheduled( $hook, $args, $group ) ) {
			return;
		}
		bwf_schedule_single_action( time(), $hook, $args, $group );
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	public function get_user_id_event() {
		$user = is_email( $this->email ) ? get_user_by( 'email', $this->email ) : false;

		return ( $user instanceof WP_User ) ? $user->ID : false;
	}

	public function admin_enqueue_assets() {
		if ( ! BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			return;
		}

		$this->automation_id = filter_input( INPUT_GET, 'edit' );

		$meta = BWFAN_Model_Automationmeta::get_meta( $this->automation_id, 'event_meta' );

		if ( isset( $meta['bwfan_unique_key'] ) && ! empty( $meta['bwfan_unique_key'] ) ) {
			$this->localized_automation_key = $meta['bwfan_unique_key'];
		}

		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'automation_id', $this->automation_id );
		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'saved_localized_automation_key', $this->localized_automation_key );

		/** Set Event UI Data, if webhook data received */
		if ( isset( $meta['webhook_data'] ) ) {
			$webhook_data_received_time = date( 'm-d-Y h:i A', absint( $meta['received_at'] ) );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'webhook_data', $meta['webhook_data'] );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'webhook_data_received_formatted', $webhook_data_received_time );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'webhook_data_received', absint( $meta['received_at'] ) );
			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'webhook_referer', $meta['referer'] );
			if ( isset( $meta['bwfan_email_map_key'] ) ) {
				BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'bwfan_email_map_key', $meta['bwfan_email_map_key'] );
			}
		}
	}

	public function send_latest_webhook_data() {
		BWFAN_PRO_Common::nocache_headers();
		$nonce_check_failed = ( ! isset( $_POST['nonce'] ) || false === wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'bwfan-action-admin' ) );
		if ( $nonce_check_failed || ! isset( $_POST['automation_id'] ) ) {
			wp_send_json( array(
				'status' => 'failed'
			) );
		}

		$automation_id = absint( sanitize_text_field( $_POST['automation_id'] ) );
		$meta          = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );
		if ( ! isset( $meta['bwfan_unique_key'] ) || ! isset( $meta['webhook_data'] ) ) {
			wp_send_json( array(
				'status' => 'failed'
			) );
		}

		if ( isset( $_POST['isv2'] ) && $_POST['isv2'] == true ) {
			wp_send_json( $meta );
			exit;
		}

		$payload = array(
			'saved_localized_automation_key'  => $meta['bwfan_unique_key'],
			'webhook_data'                    => $meta['webhook_data'],
			'webhook_data_received_formatted' => date( 'm-d-Y h:i A', absint( $meta['received_at'] ) ),
			'webhook_data_received'           => $meta['received_at'],
			'webhook_referer'                 => $meta['referer']
		);
		if ( isset( $meta['bwfan_email_map_key'] ) ) {
			$payload['bwfan_email_map_key'] = $meta['bwfan_email_map_key'];
		}

		wp_send_json( $payload );
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
		<?php
		if ( isset( $global_data['email'] ) && ! empty( $global_data['email'] ) ) { ?>
            <li>
                <strong><?php esc_html_e( 'Email: ', 'wp-marketing-automations-pro' ); ?> </strong>
				<?php esc_html_e( $global_data['email'] ); ?>
            </li>
			<?php
		}

		if ( ! isset( $global_data['webhook_data'] ) || empty( $global_data['webhook_data'] ) || ! is_array( $global_data['webhook_data'] ) ) {
			return ob_get_clean();
		}

		$i = 0;
		foreach ( $global_data['webhook_data'] as $key => $value ) {
			if ( $key === $global_data['email_map_key'] ) {
				continue;
			}

			if ( $i >= 2 ) {
				break;
			}
			if ( is_array( $value ) ) {
				?>
                <li>
                    <strong><?php echo esc_html( $key ); ?>: </strong><?php esc_html_e( 'JSON Object', 'wp-marketing-automations-pro' ); ?>
                </li>
				<?php
			} else {
				?>
                <li>
                    <strong><?php echo esc_html( $key ); ?>: </strong><?php echo esc_html( $value ); ?>
                </li>
			<?php }
			$i ++;
		}

		return ob_get_clean();
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_event_meta_saved_value ) {

		?>

        <script type="text/html" id="tmpl-event-webhook-data-preview">
            <table style="border: 1px solid #dadada;width: 100%;text-align: left; padding: 8px 15px;">
                <# _.each( data.data, function( value, key ){
                var newValue = value;
                if(Array.isArray(value) || _.isObject(value)) {
                var is_parent_array = Array.isArray(value);
                newValue = autonami_webhook_preview_fn({data: value, input_name: `${data.input_name}[${key}]`, is_parent_array});
                newValue = decodeHTML(newValue);
                }
                #>
                <tr style="border-bottom: 1px solid #aaa;">
                    <td style="padding: 5px 0; vertical-align:top;"><b>{{key}}</b></td>
                    <td>
                        {{newValue}}
                        <# if(!Array.isArray(value) && !_.isObject(value)) { #>
                        <input type="hidden" value="{{value}}" name="{{data.input_name}}[{{!!data.is_parent_array ? '': key}}]"/>
                        <# } #>
                    </td>
                </tr>
                <# }); #>
            </table>
        </script>

        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <div class="bwfan_wp_webhook_wrapper">
                <#
                var eventslug = '<?php esc_html_e( $this->get_slug() ); ?>';
                var eventData = bwfan_events_js_data[eventslug];
                var event_save_unique_key = eventData.saved_localized_automation_key;
                if(event_save_unique_key.length>0){
                eventData.localized_automation_key = event_save_unique_key
                }
                var webhook_url = '<?php esc_attr_e( home_url( '/' ) ); ?>';
                webhook_url = webhook_url + 'wp-json/autonami/v1/webhook?bwfan_autonami_webhook_id='+eventData.automation_id+'&bwfan_autonami_webhook_key='+eventData.localized_automation_key;

                var webhook_data = _.has(eventData, 'webhook_data') ? eventData.webhook_data : false;
                var webhook_data_received = _.has(eventData, 'webhook_data_received') ? eventData.webhook_data_received : false;
                var webhook_data_received_formatted = _.has(eventData, 'webhook_data_received_formatted') ? eventData.webhook_data_received_formatted : false;
                var webhook_referer = _.has(eventData, 'webhook_referer') ? eventData.webhook_referer : false;
                var bwfan_email_map_key = _.has(eventData, 'bwfan_email_map_key') ? eventData.bwfan_email_map_key : '';

                var isWebhookDataArray = Array.isArray(webhook_data);
                var nestedPreviewData = decodeHTML(autonami_webhook_preview_fn({data: webhook_data, input_name:'event_meta[webhook_data]', is_parent_array: isWebhookDataArray} ));
                #>
                <div class="bwfan_mt15"></div>
                <label for="bwfan-webhook-url" class="bwfan-label-title"><?php esc_html_e( 'Custom Webhook URL', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan-textarea-box">
                    <textarea name="event_meta[bwfan_webhook_url]" class="bwfan-input-wrapper bwfan-webhook-url" id="bwfan-webhook-url" cols="45" rows="2" onclick="select();" readonly>{{webhook_url}}</textarea>
                    <input type="hidden" name="event_meta[bwfan_unique_key]" id="bwfan-unique-key" value={{eventData.localized_automation_key}}>
                    <div class="clearfix bwfan_field_desc bwfan-pt-5">
                        Use this custom webhook URL to send requests to.
                    </div>
                </div>

                <# if( false === webhook_data ) { #>
                <div class="bwfan_mt20"></div>
                <div class="clearfix bwfan-mb-15">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'Connect With Webhook', 'wp-marketing-automations-pro' ); ?></label>
                </div>
                <# } #>

                <!-- Test Data Display -->
                <#
                if( false !== webhook_data ) { #>
                <div class="bwfan_mt20"></div>
                <label for="bwfan-webhook-url" class="bwfan-label-title"><?php esc_html_e( 'Data Found', 'wp-marketing-automations-pro' ); ?></label>
                <div id="bwfan_webhook_preview_data_container"></div>
                <#
                setTimeout(function(){
                jQuery('#bwfan_webhook_preview_data_container').html(nestedPreviewData);
                }, 10);
                #>
                <div class="bwfan_field_desc">
                    Received at {{webhook_data_received_formatted}}
                </div>
                <input type="hidden" value="{{webhook_referer}}" name="event_meta[referer]"/>
                <input type="hidden" value="{{webhook_data_received}}" name="event_meta[received_at]"/>
                <div class="bwfan_mt10"></div>
                <a class="button bwfan_refresh_webhook_data_button"><?php esc_html_e( 'Refresh Data', 'wp-marketing-automations-pro' ); ?></a>

                <!-- Select Email Map Field -->
                <div class="bwfan_mt20"></div>
                <label for="bwfan-webhook-url" class="bwfan-label-title">
					<?php esc_html_e( 'Select Email Field', 'wp-marketing-automations-pro' ); ?>
                    <div class="bwfan_tooltip" data-size="2xl">
                        <span class="bwfan_tooltip_text" data-position="top"><?php esc_html_e( 'Map the email field to be used by appropriate Rules and Actions.', 'wp-marketing-automations-pro' ); ?></span>
                    </div>
                </label>
                <select class="bwfan-input-wrapper" id="bwfan_email_map_key_dropdown" name="event_meta[bwfan_email_map_key]">
                    <option value="">Select Data Key</option>
                    <# _.each( getFields(webhook_data), function( value, key ){
                    selected = key == bwfan_email_map_key ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{key}}</option>
                    <# }); #>
                </select>
                <!-- END: Select Email Map Field -->
                <# } else { #>
                <div align="center">
                    <img width="50" src="<?php esc_attr_e( BWFAN_PRO_PLUGIN_URL . '/admin/assets/webhook.png' ); ?>"/>
                    <label class="bwfan-label-title bwfan_mt10">Test Your Event</label>
                    <div class="bwfan_mt5 bwfan_mb10">Send a request to the webhook URL.</div>
                    <a class="button bwfan_refresh_webhook_data_button"><?php esc_html_e( 'Receive Webhook', 'wp-marketing-automations-pro' ); ?></a>
                    <div class="bwfan_mt10 bwfan_webhook_error"></div>
                </div>
                <# } #>
                <!-- END: Test Data Display -->
            </div>
        </script>

        <script>


            /** To make sure that the email map value is not destroyed on mounting of another admin event or action component */
            jQuery('body').on('change', '#bwfan_email_map_key_dropdown', function () {
                bwfan_events_js_data['autonami_webhook_received']['bwfan_email_map_key'] = jQuery('#bwfan_email_map_key_dropdown').val();
            });

            /** TO Pass the saved fields data to the merge tags */
            jQuery('body').on('bwfan-selected-merge-tag', function (e, v) {
                if ('wp_webhook_data' !== v.tag) {
                    return;
                }

                var options = '';
                var i = 1;
                var selected = '';

                const nestedFieldKeys = getFields(bwfan_events_js_data['autonami_webhook_received']['webhook_data']);

                _.each(nestedFieldKeys, function (value, key) {
                    selected = (i == 1) ? 'selected' : '';
                    options += '<option value="' + key + '" ' + selected + '>' + key + '</option>';
                    i++;
                });

                jQuery('.bwfan_wp_webhook_keys').html(options);
                jQuery('.bwfan_tag_select').trigger('change');
            });

            /** Get latest data, store it in 'bwfan_events_js_data' and Refresh the view to load the latest data */
            jQuery('body').on('click', '.bwfan_refresh_webhook_data_button', function () {
                const automation_id = bwfan_events_js_data['autonami_webhook_received']['automation_id'];
                const payload = {
                    automation_id: automation_id,
                    action: 'bwfan_get_refresh_data',
                    nonce: bwfanParams.ajax_nonce
                };

                const thisButton = jQuery(this);
                thisButton.addClass('bwfan_btn_spin_blue');
                jQuery.post(bwfanParams.ajax_url, payload, function (data) {
                    thisButton.removeClass('bwfan_btn_spin_blue');

                    if (!_.isObject(data) || (_.has(data, 'status') || false === data.status) || !_.has(data, 'webhook_data')) {
                        /** Failure, no data fetched */
                        jQuery('.bwfan_webhook_error').html('No Data Found! Test Again.');
                        return;
                    }

                    _.each(data, function (value, key) {
                        bwfan_events_js_data['autonami_webhook_received'][key] = value;
                    });

                    let $iziWrap = jQuery("#modal_automation_success");
                    if ($iziWrap.length > 0) {
                        $iziWrap.iziModal('setTitle', 'Latest Data Loaded');
                        $iziWrap.iziModal('open');
                    }

                    jQuery('.bwfan_wp_webhook_wrapper').remove();
                    BWFAN_Actions.create_event_meta_ui('<?php esc_attr_e( $this->get_slug() ); ?>');
                });
            });
        </script>

        <script>
            jQuery(document).ready(function () {
                window.autonami_webhook_preview_fn = wp.template('event-webhook-data-preview');
                window.decodeHTML = function (html) {
                    var txt = document.createElement('textarea');
                    txt.innerHTML = html;
                    return txt.value;
                };

                window.getFields = function (data, fields = {}, rootField = '') {
                    const isArray = Array.isArray(data);
                    for (const key in data) {
                        const fieldKey = !rootField ? key : `${rootField}.${key}`;

                        if (Array.isArray(data[key]) || _.isObject(data[key])) {
                            fields = getFields(data[key], fields, fieldKey);
                            continue;
                        }

                        fields[fieldKey] = data[key];
                    }

                    return fields;
                }
            });

        </script>
		<?php
	}

	public function pre_executable_actions( $automation_data ) {
		$email_map           = $automation_data['event_meta']['bwfan_email_map_key'];
		$this->email_map_key = ! empty( $email_map ) ? $email_map : '';

		$entry = $this->webhook_data;

		$fieldKeys = explode( '.', $this->email_map_key );

		foreach ( $fieldKeys as $val ) {
			if ( isset( $entry[ $val ] ) ) {
				$entry = $entry[ $val ];
			}
		}

		$this->email = is_email( trim( $entry ) ) ? trim( $entry ) : '';
		$contact_id  = 0;

		/** get contact id */
		if ( ! empty( $this->email ) ) {
			$woofunnel_contact = new WooFunnels_Contact( '', $this->email );
			if ( $woofunnel_contact->get_id() > 0 ) {
				$contact_id = $woofunnel_contact->get_id();
			}
		}

		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $contact_id, 'contact_id' );
	}

	/**
	 * Action Scheduler action callback
	 *
	 * @param $webhook_key
	 * @param $webhook_data
	 * @param $referer
	 * @param $received_at
	 * @param $webhook_version
	 * @param $webhook_automation_id
	 *
	 * @return array|bool|void
	 */
	public function process( $webhook_key, $webhook_data, $referer, $received_at = '', $webhook_version = '', $webhook_automation_id = '' ) {
		$this->set_log( 'webhook processing start' );
		$this->set_log( $webhook_data );
		$this->automation_key = $webhook_key;
		$this->webhook_data   = $webhook_data;
		$this->referer        = $referer;

		/** Check if webhook automation id is not present */
		$this->get_webhook_automation_id( $webhook_key );

		if ( empty( $this->webhook_automation_id ) ) {
			return;
		}

		/** Check if webhook automation version is not present */
		$this->get_webhook_version();

		/** For automation v2 */
		if ( 2 === absint( $this->webhook_version ) ) {
			$contact_data_v2 = array(
				'automation_key'        => $this->automation_key,
				'webhook_data'          => $this->webhook_data,
				'referer'               => $this->referer,
				'webhook_automation_id' => $this->webhook_automation_id
			);

			/** Cache obj instance */
			$WooFunnels_Cache_obj = WooFunnels_Cache::get_instance();
			try {
				$key = 'bwfan_active_automations_v2_' . $this->get_slug();
				BWFAN_Core()->automations->get_active_automations( 2, $this->get_slug() );

				/** DB rows */
				$v2_all_active_automations = $WooFunnels_Cache_obj->get_cache( $key, 'autonami' );

				$webhook_automation    = [ $this->webhook_automation_id ];
				$v2_active_automations = array_values( array_filter( $v2_all_active_automations, function ( $single_automation ) use ( $webhook_automation ) {
					return in_array( $single_automation['ID'], $webhook_automation );
				} ) );

				/** If automation is not active */
				if ( empty( $v2_active_automations ) ) {
					return;
				}

				/** Set current running automation in cache */
				$WooFunnels_Cache_obj->set_cache( $key, $v2_active_automations, 'autonami' );

				/** Set active v2 automations prop empty */
				BWFAN_Core()->public->active_v2_automations = [];
				BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );

				/** Set all active v2 automations in object cache after running the filtered automation */
				$WooFunnels_Cache_obj->set_cache( $key, $v2_all_active_automations, 'autonami' );
			} catch ( Error $e ) {
				$WooFunnels_Cache_obj->reset_cache();
				BWFAN_Common::log_test_data( 'FunnelKit Automations webhook received error: ' . $e->getMessage(), 'webhook-execution-error', true );
			}

			/** Set active v2 automations prop empty */
			BWFAN_Core()->public->active_v2_automations = [];

			$this->set_log( 'v2 webhook automation ends' );
			$this->log();

			return;
		}

		/** For automation v1 */
		return $this->run_automations();
	}

	/**
	 * Run v1 automations and create tasks
	 *
	 * @return array|bool
	 */
	public function run_automations() {
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );
		if ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 || ! isset( $this->automations_arr[ $this->webhook_automation_id ] ) ) {
			BWFAN_Core()->logger->log( 'Async callback: No active automations found. Event - ' . $this->get_slug(), $this->log_type );

			return false;
		}

		$automation_actions = [];
		$ran_actions        = [];
		$automation_data    = $this->automations_arr[ $this->webhook_automation_id ];

		if ( $this->get_slug() !== $automation_data['event'] || 0 !== intval( $automation_data['requires_update'] ) ) {
			return false;
		}

		/** Check if the automation_key match with the post data */
		if ( isset( $automation_data['event_meta']['bwfan_unique_key'] ) && $this->automation_key === $automation_data['event_meta']['bwfan_unique_key'] ) {
			$ran_actions = $this->handle_single_automation_run( $automation_data, $this->webhook_automation_id );
		}

		$automation_actions[ $this->webhook_automation_id ] = $ran_actions;
		$this->set_log( 'v1 webhook automation ends' );
		$this->log();

		return $automation_actions;
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

		$meta = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );
		if ( '' === $meta || ! is_array( $meta ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();
		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                             = [ 'global' => [] ];
		$data_to_send['global']['automation_key'] = $this->automation_key;
		$data_to_send['global']['webhook_data']   = $this->webhook_data;
		$data_to_send['global']['referer']        = $this->referer;
		$data_to_send['global']['email']          = $this->email;
		$data_to_send['global']['email_map_key']  = $this->email_map_key;

		return $data_to_send;
	}

	public function set_merge_tags_data( $task_meta ) {
		$merge_data                 = [];
		$merge_data['webhook_data'] = $task_meta['global']['webhook_data'];

		$merge_data['referer'] = $task_meta['global']['referer'];
		$merge_data['email']   = $task_meta['global']['email'];
		BWFAN_Merge_Tag_Loader::set_data( $merge_data );
	}

	public function capture_v2_data( $automation_data ) {
		$email_map           = $automation_data['event_meta']['bwfan_email_map_key'];
		$this->email_map_key = ! empty( $email_map ) ? $email_map : '';

		$data = isset( $automation_data['webhook_data'] ) ? $automation_data['webhook_data'] : [];
		if ( empty( $data ) ) {
			$automation_data['email'] = '';

			return $automation_data;
		}

		if ( is_array( $data ) && isset( $data[ $this->email_map_key ] ) ) {
			$data = $data[ $this->email_map_key ];
		} else {
			$fieldKeys = explode( '.', $this->email_map_key );
			foreach ( $fieldKeys as $val ) {
				if ( isset( $data[ $val ] ) ) {
					$data = $data[ $val ];
				}
			}
		}

		$this->email              = ( ! is_array( $data ) && is_email( $data ) ) ? $data : '';
		$automation_data['email'] = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'bwfan_email_map_key',
				'type'        => 'webhook',
				'label'       => __( 'Select Email Field', 'wp-marketing-automations-pro' ),
				'webhook_url' => rest_url( 'autonami/v1/webhook/' ) . '?bwfan_autonami_webhook_id={{automationId}}&bwfan_autonami_webhook_key={{uniqueKey}}',
				'required'    => false,
				'hint'        => "",
				'showmap'     => true,
			]
		];
	}

	/**
	 * Get automation id from webhook unique key
	 *
	 * @param $automation_key
	 *
	 * @return void
	 */
	public function get_webhook_automation_id( $automation_key ) {
		global $wpdb;
		$table         = $wpdb->prefix . 'bwfan_automationmeta';
		$automation_id = $wpdb->get_col( "SELECT `bwfan_automation_id` FROM $table WHERE `meta_key` = 'event_meta' AND `meta_value` LIKE '%" . $automation_key . "%'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->webhook_automation_id = is_array( $automation_id ) && isset( $automation_id[0] ) ? $automation_id[0] : 0;
	}

	/**
	 * Get automation version
	 *
	 * @return void
	 */
	public function get_webhook_version() {
		if ( ! empty( $this->webhook_version ) ) {
			return;
		}

		$automation_data = BWFAN_Model_Automations::get( $this->webhook_automation_id );
		if ( ! isset( $automation_data['v'] ) ) {
			$this->webhook_version = 1;

			return;
		}

		$this->webhook_version = $automation_data['v'];
	}

	public function set_log( $log ) {
		if ( empty( $log ) ) {
			return;
		}
		$this->logs[] = array(
			't' => microtime( true ),
			'm' => $log,
		);
	}

	protected function log() {
		if ( ! is_array( $this->logs ) || 0 === count( $this->logs ) ) {
			return;
		}

		if ( false === apply_filters( 'bwfan_allow_webhook_logging', BWFAN_PRO_Common::is_log_enabled( 'bwfan_webhook_received_logging' ) ) ) {
			return;
		}
		BWFAN_Common::log_test_data( $this->logs, 'fka-webhook-logs', true );
		$this->logs = [];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */

return 'BWFAN_Autonami_Webhook_Received';
