<?php

final class BWFAN_Wp_CreateUser extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Create User', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action creates a WordPress user', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'email' );

		$this->excluded_events = array(
			'wcs_before_end',
			'wcs_before_renewal',
			'wcs_card_expiry',
			'wcs_created',
			'wcs_renewal_payment_complete',
			'wcs_renewal_payment_failed',
			'wcs_status_changed',
			'wcs_trial_end',
			'wp_user_creation',
			'wp_user_login',
		);

		$this->action_priority = 5;
		$this->support_v2      = true;
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
			global $wp_roles;
			$roles = $wp_roles->roles;
			$data  = $roles;

			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'user_roles', $data );
		}
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php esc_html_e( $unique_slug ); ?>">
            <#
            selected_event = BWFAN_Auto.uiDataDetail.trigger.event;

            selected_user_role = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'user_role')) ? data.actionSavedData.data.user_role : '';
            email = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'email')) ? data.actionSavedData.data.email : '';
            first_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'first_name')) ? data.actionSavedData.data.first_name : '';
            last_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'last_name')) ? data.actionSavedData.data.last_name : '';

            is_allow = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'allow_notification_email')) ? 'checked' : '';
            is_allow_temp_pass = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'allow_temp_pass')) ? 'checked' : '';
            #>
            <div data-element-type="bwfan-editor" class="bwfan-<?php esc_html_e( $unique_slug ); ?>">
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Email (optional)', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <input required type="text"
                           class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>"
                           name="bwfan[{{data.action_id}}][data][email]" placeholder="E.g. customer_email@gmail.com"
                           value="{{email}}"/>
                    <div class="clearfix bwfan_field_desc"><?php esc_html_e( "Leave blank unless you want to create an account with an email using dynamic merge tags.", 'wp-marketing-automations-pro' ); ?></div>
                    <div class="clearfix bwfan_field_desc"><?php esc_html_e( "Action will be skipped if a user already exists.", 'wp-marketing-automations-pro' ); ?></div>
                </div>
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'First Name (optional)', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <input required type="text"
                           class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>"
                           name="bwfan[{{data.action_id}}][data][first_name]" placeholder="E.g. John"
                           value="{{first_name}}"/>
                </div>
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Last Name (optional)', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <input required type="text"
                           class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>"
                           name="bwfan[{{data.action_id}}][data][last_name]" placeholder="E.g. Doe"
                           value="{{last_name}}"/>
                </div>
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Role', 'wp-marketing-automations-pro' ); ?>
					<?php
					$message = __( 'Roles which defines user authority and responsibility', 'wp-marketing-automations-pro' );
					echo $this->add_description( esc_html__( $message ), '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <select required id="bwfan_user_role"
                            class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>"
                            name="bwfan[{{data.action_id}}][data][user_role]">
                        <option value="">Choose role</option>
                        <#
                        if(_.has(data.actionFieldsOptions, 'user_roles') &&
                        _.isObject(data.actionFieldsOptions.user_roles) ) {
                        _.each( data.actionFieldsOptions.user_roles, function( value, key ){
                        selected = (key == selected_user_role) ? 'selected' : '';
                        #>
                        <option value="{{key}}" {{selected}}>{{value.name}}</option>
                        <# })
                        }
                        #>
                    </select>
                </div>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <label for="bwfan_allow_temp_pass">
                        <input type="checkbox" name="bwfan[{{data.action_id}}][data][allow_temp_pass]"
                               id="bwfan_allow_temp_pass" value="1" {{is_allow_temp_pass}}/>
						<?php
						esc_html_e( "Save password in plain text for one time use via merge tag &#123;&#123;new_user_password&#125;&#125;", 'wp-marketing-automations-pro' );
						$message = __( 'By enabling this option, I understand the security implication that the password will be temporarily saved in plain text. This password will automatically be deleted after a one-time use of the merge tag.', 'wp-marketing-automations-pro' );
						echo $this->add_description( esc_html__( $message ), 'l' ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>
                    </label>
                </div>
                <div class="bwfan_email_tracking bwfan-mb-15">
                    <label for="bwfan_allow_notification_email">
                        <input type="checkbox" name="bwfan[{{data.action_id}}][data][allow_notification_email]"
                               id="bwfan_allow_notification_email" value="1" {{is_allow}}/>
						<?php
						esc_html_e( 'Send WordPress User notification email', 'wp-marketing-automations-pro' );
						$message = __( 'Send default WordPress user email notification on user creation', 'wp-marketing-automations-pro' );
						echo $this->add_description( esc_html__( $message ), 'l' ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>
                    </label>
                </div>
				<?php
				do_action( 'bwfan_' . $this->get_slug() . '_setting_html', $this )
				?>
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
		$data_to_set = array(
			'email'                    => ! empty( $task_meta['data']['email'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['email'] ) : '',
			'user_role'                => isset( $task_meta['data']['user_role'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['user_role'] ) : 'subscriber',
			'first_name'               => isset( $task_meta['data']['first_name'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['first_name'] ) : '',
			'last_name'                => isset( $task_meta['data']['last_name'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['last_name'] ) : '',
			'allow_notification_email' => ( isset( $task_meta['data']['allow_notification_email'] ) ) ? 1 : 0,
			'allow_temp_pass'          => ( isset( $task_meta['data']['allow_temp_pass'] ) ) ? 1 : 0,
		);

		/** passing the global email in case email blank in data */
		if ( empty( $data_to_set['email'] ) && ! empty( $task_meta['global']['email'] ) ) {
			$data_to_set['email'] = $task_meta['global']['email'];
		}

		remove_action( 'network_site_new_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'network_site_users_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'network_user_new_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications' );

		if ( bwfan_is_woocommerce_active() ) {
			remove_action( 'woocommerce_created_customer_notification', array( WC_Emails::instance(), 'customer_new_account' ) );
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array(
			'email'                    => ! empty( $step_data['email'] ) ? BWFAN_Common::decode_merge_tags( $step_data['email'] ) : '',
			'username'                 => isset( $step_data['username'] ) && ! empty( $step_data['username'] ) ? BWFAN_Common::decode_merge_tags( $step_data['username'] ) : '',
			'user_role'                => isset( $step_data['user_role'] ) ? BWFAN_Common::decode_merge_tags( $step_data['user_role'] ) : 'subscriber',
			'first_name'               => isset( $step_data['first_name'] ) ? BWFAN_Common::decode_merge_tags( $step_data['first_name'] ) : '',
			'last_name'                => isset( $step_data['last_name'] ) ? BWFAN_Common::decode_merge_tags( $step_data['last_name'] ) : '',
			'allow_notification_email' => isset( $step_data['allow_notification_email'] ) && 1 === absint( $step_data['allow_notification_email'] ) ? 1 : 0,
			'allow_temp_pass'          => isset( $step_data['allow_temp_pass'] ) && 1 === absint( $step_data['allow_temp_pass'] ) ? 1 : 0,
		);

		/** passing the global email in case email blank in data */
		if ( empty( $data_to_set['email'] ) && ! empty( $automation_data['global']['email'] ) ) {
			$data_to_set['email'] = $automation_data['global']['email'];
		}

		remove_action( 'network_site_new_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'network_site_users_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'network_user_new_created_user', 'wp_send_new_user_notifications' );
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications' );

		if ( bwfan_is_woocommerce_active() ) {
			remove_action( 'woocommerce_created_customer_notification', array( WC_Emails::instance(), 'customer_new_account' ) );
		}

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
		if ( true === $result || ( isset( $result['status'] ) && 3 === intval( $result['status'] ) ) ) {
			return array(
				'status'  => 3,
				'message' => isset( $result['message'] ) ? $result['message'] : __( 'User Created Successfully', 'wp-marketing-automations-pro' ),
			);
		}

		if ( is_array( $result ) && isset( $result['message'] ) ) {
			return array(
				'status'  => 4,
				'message' => $result['message'],
			);
		}

		return array(
			'status'  => 4,
			'message' => __( 'Some error occurred in creating user', 'wp-marketing-automations-pro' )
		);
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

		$user_email = sanitize_text_field( $this->data['email'] );

		/** Verifying if email is valid or not */
		if ( ! is_email( trim( $user_email ) ) ) {
			$resp = array(
				'status'  => 4,
				'message' => __( 'Email ID is not valid', 'wp-marketing-automations-pro' ),
			);

			return $resp;
		}

		$user_first_name = isset( $this->data['first_name'] ) ? $this->data['first_name'] : '';
		$user_last_name  = isset( $this->data['last_name'] ) ? $this->data['last_name'] : '';

		$user_role = isset( $this->data['user_role'] ) && ! empty( $this->data['user_role'] ) ? sanitize_text_field( $this->data['user_role'] ) : '';
		$chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		/** if order id present */
		if ( isset( $this->data['order_id'] ) && ! empty( $this->data['order_id'] && bwfan_is_woocommerce_active() ) ) {
			$order_object    = wc_get_order( $this->data['order_id'] );
			$user_first_name = $order_object instanceof WC_order ? $order_object->get_billing_first_name() : '';
			$user_last_name  = $order_object instanceof WC_order ? $order_object->get_billing_last_name() : '';
		}

		/** if wc subscription id present */
		if ( isset( $this->data['subscription_id'] ) && ! empty( $this->data['subscription_id'] ) && bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
			$subscription = wcs_get_subscription( $this->data['subscription_id'] );
			$order_id     = ! empty( $subscription ) ? $subscription->get_parent_id() : 0;
			if ( ! empty( $order_id ) ) {
				$order_object    = wc_get_order( $order_id );
				$user_first_name = $order_object instanceof WC_order ? $order_object->get_billing_first_name() : '';
				$user_last_name  = $order_object instanceof WC_order ? $order_object->get_billing_last_name() : '';
			}
		}

		$password = substr( str_shuffle( $chars ), 0, 8 );

		/** checking user_id exists with the user_email */
		$user_data = get_user_by( 'email', $user_email );
		if ( $user_data instanceof WP_User ) {
			return array(
				'status'  => 3,
				'message' => __( 'User already exists with ' . $user_email, 'wp-marketing-automations-pro' ),
			);
		}

		$user_id = 0;
		try {
			$user_id = wp_create_user( $user_email, $password, $user_email );
		} catch ( Error $e ) {
			$err_message = $e->getMessage();
		}

		/** if user already exists with the given email id */
		if ( is_wp_error( $user_id ) ) {
			$user_error = $user_id->errors;

			$resp = array(
				'status'  => 4,
				'message' => end( $user_error ),
			);

			return $resp;
		}

		$user = new WP_User( $user_id );
		$user->set_role( $user_role );

		$user_first_name = trim( $user_first_name );
		$user_last_name  = trim( $user_last_name );
		! empty( $user_first_name ) && update_user_meta( $user_id, 'first_name', $user_first_name );
		! empty( $user_last_name ) && update_user_meta( $user_id, 'last_name', $user_last_name );

		/** Send new user email if 'Allow send email' option ticked  */
		if ( isset( $this->data['allow_notification_email'] ) && 1 === absint( $this->data['allow_notification_email'] ) ) {
			wp_send_new_user_notifications( $user_id, 'user' );
		}

		/** create bwf contact and maintain the password of user in contact meta table */
		$created_date = date( 'Y-m-d H:i:s' );

		$contact = new WooFunnels_Contact( $user_id, $user_email );
		$contact->set_wpid( $user_id );
		$contact->set_email( $user_email );
		$contact->set_f_name( $user_first_name );
		$contact->set_l_name( $user_last_name );
		$contact->set_creation_date( $created_date );
		$contact->save();
		/** only allow saving of contact password when checked in create user action */
		if ( $this->data['allow_temp_pass'] ) {
			$contact->update_meta( 'bwfan_userpassword', $password );
		}
		$success_mesg = empty( $err_message ) ? __( 'User Created.', 'wp-marketing-automations-pro' ) : $err_message;

		return [
			'status'  => 3,
			'message' => $success_mesg,
		];
	}

	public function process_v2() {
		$user_email = sanitize_text_field( $this->data['email'] );
		$username   = sanitize_text_field( $this->data['username'] );
		$username   = ! empty( $username ) && ! username_exists( $username ) ? $username : $user_email;

		/** Verifying if email is valid or not */
		if ( ! is_email( trim( $user_email ) ) ) {
			return $this->skipped_response( __( 'Email is not valid', 'wp-marketing-automations-pro' ) );
		}

		$user_first_name = isset( $this->data['first_name'] ) ? $this->data['first_name'] : '';
		$user_last_name  = isset( $this->data['last_name'] ) ? $this->data['last_name'] : '';

		$user_role = isset( $this->data['user_role'] ) && ! empty( $this->data['user_role'] ) ? sanitize_text_field( $this->data['user_role'] ) : '';
		$chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		/** if order id present */
		if ( isset( $this->data['order_id'] ) && ! empty( $this->data['order_id'] ) ) {
			$order_object    = wc_get_order( $this->data['order_id'] );
			$user_first_name = $order_object->get_billing_first_name();
			$user_last_name  = $order_object->get_billing_last_name();
		}

		/** if wc subscription id present */
		if ( isset( $this->data['subscription_id'] ) && ! empty( $this->data['subscription_id'] ) ) {
			$subscription    = wcs_get_subscription( $this->data['subscription_id'] );
			$order_id        = $subscription->get_parent_id();
			$order_object    = wc_get_order( $order_id );
			$user_first_name = $order_object->get_billing_first_name();
			$user_last_name  = $order_object->get_billing_last_name();
		}

		$password = substr( str_shuffle( $chars ), 0, 8 );

		/** checking user_id exists with the user_email */
		$user_data = get_user_by( 'email', $user_email );
		if ( $user_data instanceof WP_User ) {
			return $this->success_message( __( 'User already exists with ' . $user_email, 'wp-marketing-automations-pro' ) );
		}

		$user_id = 0;
		try {
			$user_id = wp_create_user( $username, $password, $user_email );
		} catch ( Error $e ) {
			return $this->error_response( "Error: " . $e->getMessage() );
		}

		if ( is_wp_error( $user_id ) ) {
			return $this->error_response( $user_id->errors );
		}

		/** create display name using first name and appending lastname to it if available*/
		$display_name = trim( $user_first_name );
		$display_name = empty( $display_name ) ? trim( $user_last_name ) : $display_name . ' ' . trim( $user_last_name );
		if ( ! empty( trim( $display_name ) ) ) {
			wp_update_user( array( 'ID' => $user_id, 'display_name' => trim( $display_name ) ) );
		}

		$user = new WP_User( $user_id );
		$user->set_role( $user_role );

		$user_first_name = trim( $user_first_name );
		$user_last_name  = trim( $user_last_name );
		$nickname        = trim( $user_first_name );
		! empty( $user_first_name ) && update_user_meta( $user_id, 'first_name', $user_first_name );
		! empty( $user_last_name ) && update_user_meta( $user_id, 'last_name', $user_last_name );
		! empty( $nickname ) && update_user_meta( $user_id, 'nickname', $nickname );

		/** Send new user email if 'Allow send email' option ticked  */
		if ( isset( $this->data['allow_notification_email'] ) && 1 === absint( $this->data['allow_notification_email'] ) ) {
			wp_send_new_user_notifications( $user_id, 'user' );
		}

		/** create bwf contact and maintain the password of user in contact meta table */
		$created_date = date( 'Y-m-d H:i:s' );

		$contact = new WooFunnels_Contact( $user_id, $user_email );
		$contact->set_wpid( $user_id );
		$contact->set_email( $user_email );
		$contact->set_f_name( $user_first_name );
		$contact->set_l_name( $user_last_name );
		$contact->set_creation_date( $created_date );
		$contact->save();

		/** only allow saving of contact password when checked in create user action */
		if ( $this->data['allow_temp_pass'] ) {
			$contact->update_meta( 'bwfan_userpassword', $password );
		}

		return $this->success_message( __( 'User Created.', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Add back all the action which we unhooked before creating a user
	 */
	public function after_executing_task() {
		add_action( 'network_site_new_created_user', 'wp_send_new_user_notifications' );
		add_action( 'network_site_users_created_user', 'wp_send_new_user_notifications' );
		add_action( 'network_user_new_created_user', 'wp_send_new_user_notifications' );
		add_action( 'register_new_user', 'wp_send_new_user_notifications' );
		add_action( 'edit_user_created_user', 'wp_send_new_user_notifications', 10, 2 );

		if ( bwfan_is_woocommerce_active() ) {
			add_action( 'woocommerce_created_customer_notification', array( WC_Emails::instance(), 'customer_new_account' ) );
		}
	}

	public function get_fields_schema() {
		global $wp_roles;
		$roles = $wp_roles->roles;
		$data  = [];
		foreach ( $roles as $key => $role ) {
			$data[ $key ] = $role['name'];
		}

		$data = BWFAN_PRO_Common::prepared_field_options( $data );

		return [
			[
				"id"          => 'email',
				"label"       => __( 'Email', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				"placeholder" => __( 'E.g. customer_email@gmail.com', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwfan-field-wp_createuser',
				"description" => __( "If user already exists with the above email, action will be ignored", 'wp-marketing-automations-pro' ),
				"required"    => true,
			],
			[
				"id"          => 'username',
				"label"       => __( 'Username', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				"placeholder" => __( 'E.g. username123', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwfan-field-wp_createuser',
				"hint"        => __( "If a username is not set or already exists, your email will be used as the username", 'wp-marketing-automations-pro' ),
				"required"    => false,
			],
			[
				"id"          => 'first_name',
				"label"       => __( 'First Name', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				"placeholder" => __( 'E.g. John', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwfan-field-wp_createuser',
				"description" => "",
				"required"    => false,
			],
			[
				"id"          => 'last_name',
				"label"       => __( 'Last Name', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				"placeholder" => __( 'E.g. Doe', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwfan-field-wp_createuser',
				"description" => "",
				"required"    => false,
			],
			[
				"id"          => 'user_role',
				"label"       => __( 'Role', 'wp-marketing-automations-pro' ),
				"type"        => 'radio',
				"options"     => $data,
				'placeholder' => __( 'Choose role', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper bwfan-field-wp_createuser',
				"tip"         => __( "Roles which defines user authority and responsibility", 'wp-marketing-automations-pro' ),
				"description" => "",
				"required"    => false,
			],
			[
				"id"            => 'allow_temp_pass',
				"checkboxlabel" => __( 'Save password in plain text for one time use via merge tag {{new_user_password}} ', 'wp-marketing-automations-pro' ),
				"type"          => 'checkbox',
				"class"         => '',
				"hint"          => __( "By enabling this option, I understand the security implication that the password will be temporarily saved in plain text. This password will automatically be deleted after a one-time use of the merge tag.", 'wp-marketing-automations-pro' ),
				"required"      => false,
			],
			[
				"id"            => 'allow_notification_email',
				"checkboxlabel" => __( 'Allow WordPress user notification email ', 'wp-marketing-automations-pro' ),
				"type"          => 'checkbox',
				"class"         => '',
				"hint"          => __( "Send default WordPress user email notification on user creation.", 'wp-marketing-automations-pro' ),
				"required"      => false,
			]
		];
	}

	public function get_default_values() {
		return [
			'user_role' => 'subscriber',
		];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_Wp_CreateUser';
