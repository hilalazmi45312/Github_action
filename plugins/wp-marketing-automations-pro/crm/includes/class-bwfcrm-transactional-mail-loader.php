<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

#[AllowDynamicProperties]
class BWFCRM_Transactional_Mail_Loader {

	private static $ins = null;

	/**
	 * Available mails
	 *
	 * @var array
	 */
	private static $available_mails = [];

	/**
	 * Enabled mails option data
	 *
	 * @var array
	 */
	private static $enabled_mails_option = [];

	/**
	 * Email class directory
	 *
	 * @var string
	 */
	private static $email_dir = __DIR__ . '/transactional-emails';

	/**
	 * Get instance
	 *
	 * @return BWFCRM_Transactional_Mail_Loader|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * BWFCRM_Transactional_Mail_Loader constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'load_enabled_files' ] );
		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'handle_woocommerce_resend_mail' ), 10, 2 );
	}

	/**
	 * Handle woocommerce resend mail
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $type  Type of mail.
	 *
	 * @return void
	 */
	public function handle_woocommerce_resend_mail( $order, $type ) {
		do_action( "bwfcrm_before_resend_order_email_" . $type, $order );
	}

	/**
	 * Get class name
	 *
	 * @param string $class_name
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_class_name( $class_name = '', $slug = '' ) {
		if ( empty( $class_name ) && empty( $slug ) ) {
			return '';
		}
		if ( $class_name !== '' ) {
			$search  = [ 'class-bwfcrm-', '.php', '-' ];
			$replace = [ '', '', '_' ];

			return 'BWFCRM_' . implode( '_', array_map( 'ucfirst', explode( '_', str_replace( $search, $replace, $class_name ) ) ) );
		}
		if ( $slug !== '' ) {
			return 'BWFCRM_Transactional_Mail_' . implode( '_', array_map( 'ucfirst', explode( '_', $slug ) ) );
		}
	}

	/**
	 * Get path info
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_path_info( $slug = '' ) {
		if ( empty( $slug ) ) {
			return '';
		}

		return self::$email_dir . '/class-bwfcrm-transactional-mail-' . strtolower( str_replace( '_', '-', $slug ) ) . '.php';
	}

	/**
	 * Get slug by class
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public static function get_slug_by_class( $class = '' ) {
		return strtolower( str_replace( 'BWFCRM_Transactional_Mail_', '', $class ) );
	}

	/**
	 * Load enabled files only
	 */
	public function load_enabled_files() {
		if ( ! BWFAN_Common::check_for_lks() ) {
			return;
		}
		$enabled_mails              = $this->get_enabled_transactional_mails_setting();
		self::$enabled_mails_option = $enabled_mails;
		if ( empty( $enabled_mails ) ) {
			return;
		}
		foreach ( $enabled_mails as $class_slug => $data ) {

			if ( isset( $data['status'] ) && $data['status'] === 'disabled' ) {
				continue;
			}

			$class_name = $this->get_class_name( '', $class_slug );
			$path       = $this->get_path_info( $class_slug );
			if ( ! file_exists( $path ) || empty( $class_name ) || class_exists( $class_name ) ) {
				continue;
			}
			require_once( $path );

			if ( ! in_array( $class_name, self::$available_mails ) ) {
				self::register( $class_name, true );
			}
		}
	}

	/**
	 * Load all files
	 */
	public function load_all_files() {
		foreach ( glob( self::$email_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data  = pathinfo( $_field_filename );
			$class_name = $this->get_class_name( $file_data['filename'] );
			if ( ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) || in_array( $class_name, self::$available_mails ) ) {
				continue;
			}
			require_once( $_field_filename );

			if ( class_exists( $class_name ) ) {
				self::register( $class_name, false );
			}
		}
	}

	/**
	 * Register mail
	 *
	 * @param string $className
	 * @param bool $load_hooks
	 */
	public static function register( $className, $load_hooks = true ) {
		$slug  = self::get_slug_by_class( $className );
		$data  = self::$enabled_mails_option[ $slug ] ?? [];
		$class = new $className( $load_hooks, $data );
		if ( ! $class instanceof BWFCRM_Transactional_Mail_Base || ( ! $load_hooks && ! $class->is_valid() ) ) {
			return;
		}
		self::$available_mails[ $class->get_data( 'slug' ) ] = $class->get_api_data();
	}

	/**
	 * Get registered transactional mails
	 *
	 * @return array
	 */
	public function get_registered_transactional_mails() {
		$mails = self::$available_mails;

		if ( empty( $mails ) ) {
			return [];
		}

		uasort( $mails, function ( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		} );

		return array_values( $mails );
	}

	/**
	 * Returns API data for the transactional mail by slug and language
	 *
	 * @param $slug
	 * @param $lang
	 * @param bool $return_template
	 *
	 * @return array|false|mixed
	 */
	public function get_transactional_mail_by_slug( $slug, $lang = '', $return_template = true ) {
		// Get transactional mail saved data
		$enabled_mails = $this->get_enabled_transactional_mails_setting();
		// Mail data for slug
		$data = $enabled_mails[ $slug ] ?? [];

		// Get dynamic class
		$className = $this->get_class_name( '', $slug );

		// Get Loaded file
		$path = $this->get_path_info( $slug );

		if ( ! file_exists( $path ) || empty( $className ) ) {
			return false;
		}

		if ( ! class_exists( $className ) ) {
			require_once( $path );
		}

		$class = new $className( false, $data );
		if ( ! $class instanceof BWFCRM_Transactional_Mail_Base ) {
			return false;
		}

		$apidata        = $class->get_api_data();
		$template_data  = [];
		$template_id    = 0;
		$template_title = isset( $apidata['name'] ) ? $apidata['name'] : $slug;
		$status         = isset( $apidata['enabled'] ) ? $apidata['enabled'] : false;
		$recipient      = isset( $apidata['recipient'] ) ? $apidata['recipient'] : '';

		if ( ! empty( $apidata['template_data'] ) ) {
			if ( empty( $lang ) && ! empty( $apidata['template_data']['template_id'] ) ) {
				$template_id = $apidata['template_data']['template_id'];
			} else if ( ! empty( $lang ) && ! empty( $apidata['template_data']['lang'][ $lang ] ) ) {
				$template_id = $apidata['template_data']['lang'][ $lang ];
			}

			if ( ! empty( $template_id ) ) {
				$template_data = BWFAN_Model_Templates::bwfan_get_template( $template_id, 0 );
			}
		}

		// If template not found have to create a new template for the mail and save its data to the db
		if ( empty( $template_data ) ) { // Create a new template with mode=7 and save its data to the db
			$create_time = current_time( 'mysql', 1 );

			// Get default template data
			$template_data = $class->get_default_template_data();

			// Set current time
			$template_data['created_at'] = $template_data['updated_at'] = $create_time;

			// Create new template
			$template_id = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );

			// Save template id to transactional mail data
			if ( empty( $lang ) ) {
				$enabled_mails[ $slug ]['template_id'] = $template_id;
				$enabled_mails[ $slug ]['status']      = 'disabled';
			} else {
				$enabled_mails[ $slug ]['lang'][ $lang ] = $template_id;
			}
			update_option( 'bwfan_enabled_transactional_mails', $enabled_mails, true );

		}

		if ( ! $return_template ) {
			return $enabled_mails;
		}
		// check for json encoded value & decode
		if ( BWFAN_Common::is_json( $template_data['data'] ) ) {
			$template_data['data'] = json_decode( $template_data['data'], true );
		}
		$template_data['mode']            = 5;
		$template_data['title']           = $template_title;
		$template_data['slug']            = $slug;
		$template_data['status']          = $status;
		$template_data['recipient']       = $recipient;
		$template_data['merge_tag']       = ! empty( $apidata['merge_tag_group'] ) ? $apidata['merge_tag_group'] : [];
		$template_data['supported_block'] = ! empty( $apidata['supported_block'] ) ? $apidata['supported_block'] : [];

		return $template_data;
	}

	/**
	 * Update all transactional mail status single or all
	 *
	 * @param $type
	 * @param $state
	 *
	 * @return bool
	 */
	public function update_transactional_mail_status( $type = '', $state = false ) {
		if ( empty( $type ) ) {
			return false;
		}

		$data = [];
		if ( $type === 'all' ) {
			self::load_all_files();
			foreach ( self::$available_mails as $slug => $data ) {
				$data = self::get_transactional_mail_by_slug( $slug, '', false );
			}

			$data = array_map( function ( $item ) use ( $state ) {
				$item['status'] = $state ? 'enabled' : 'disabled';

				return $item;
			}, $data );
		} else {
			$enabled_mails = $this->get_enabled_transactional_mails_setting();

			if ( isset( $enabled_mails[ $type ] ) ) {
				$enabled_mails[ $type ]['status'] = $state ? 'enabled' : 'disabled';
				$data                             = $enabled_mails;
			} else {
				$data = self::get_transactional_mail_by_slug( $type, '', false );
				if ( empty( $data ) ) {
					return false;
				}
				$data[ $type ]['status'] = $state ? 'enabled' : 'disabled';
			}
		}

		return update_option( 'bwfan_enabled_transactional_mails', $data, true );
	}

	public function get_enabled_transactional_mails() {
		return self::$enabled_mails_option;
	}

	public function get_transactional_activity( $search = '', $offset = 0, $limit = 25, $ids = [], $cids = [], $total = false ) {
		global $wpdb;

		// Get engagement tracking data with mode = 1, type = 9
		$query = "SELECT " . ( $total ? 'COUNT(ID) as count' : '*' ) . " FROM {table_name} WHERE type = 9 AND mode = 1";

		if ( ! empty( $ids ) ) {
			$query .= $wpdb->prepare( " AND ID in ( " . implode( ',', $ids ) . " )" );
		}

		if ( ! empty( $cids ) ) {
			$query .= $wpdb->prepare( " AND cid in ( " . implode( ',', $cids ) . " )" );
		}


		if ( ! empty( $search ) ) {
			$query .= $wpdb->prepare( " AND send_to LIKE %s", "%" . esc_sql( $search ) . "%" );
		}
		$query .= ' ORDER BY updated_at DESC';
		if ( ! $total && intval( $limit ) > 0 ) {
			$offset = ! empty( $offset ) ? intval( $offset ) : 0;
			$query  .= $wpdb->prepare( " LIMIT %d, %d", $offset, $limit );
		}

		$result = BWFAN_Model_Engagement_Tracking::get_results( $query );
		if ( $total ) {
			return ! empty( $result ) ? $result[0]['count'] : 0;
		}
		$result = is_array( $result ) && ! empty( $result ) ? $this->get_formatted_transactional_data( $result ) : array();

		return $result;
	}

	public function get_formatted_transactional_data( $mails ) {
		$tids = [];
		$cids = [];
		foreach ( $mails as $mail ) {
			if ( ! empty( $mail['tid'] ) ) {
				$tids[] = $mail['tid'];
			}
			if ( ! empty( $mail['cid'] ) ) {
				$cids[] = $mail['cid'];
			}
		}
		$templates    = BWFAN_Model_Templates::get_templates_by_ids( array_unique( $tids ), [ 'ID', 'title' ] );
		$final_data   = [];
		$contact_list = BWFCRM_Model_Contact::get_contacts( '', '', 0, [], [ 'include_ids' => array_unique( $cids ) ] );
		$contact_list = is_array( $contact_list ) && isset( $contact_list['contacts'] ) ? $contact_list['contacts'] : [];
		$contacts     = [];
		foreach ( $contact_list as $item ) {
			$contacts[ $item['id'] ] = [
				'id'     => $item['id'],
				'f_name' => $item['f_name'],
				'l_name' => $item['l_name'],
				'email'  => $item['email'],
			];
		}
		foreach ( $mails as $mail ) {
			if ( ! empty( $mail['tid'] ) && isset( $templates[ $mail['tid'] ] ) ) {
				if ( isset( self::$available_mails[ $templates[ $mail['tid'] ]['title'] ] ) ) {
					$mail['transactional_type'] = self::$available_mails[ $templates[ $mail['tid'] ]['title'] ]['name'];
				}
				if ( isset( self::$available_mails[ $templates[ $mail['tid'] ]['title'] ]['merge_tag_group'] ) ) {
					$mail['is_order'] = in_array( 'order', self::$available_mails[ $templates[ $mail['tid'] ]['title'] ]['supported_block'] );
				}
				if ( isset( $contacts[ $mail['cid'] ] ) ) {
					$mail['contact'] = $contacts[ $mail['cid'] ];
				}
			}

			$final_data[] = $mail;
		}

		return $final_data;

	}

	/**
	 * Update transactional mail template id
	 *
	 * @param string $slug
	 * @param string $template_id
	 * @param string $lang
	 */
	public function update_transactional_mail_template_id( $slug = '', $template_id = '', $lang = '' ) {
		if ( empty( $slug ) || empty( $template_id ) ) {
			return false;
		}
		$enabled_mails = $this->get_enabled_transactional_mails_setting();

		if ( empty( $lang ) ) {
			$enabled_mails[ $slug ]['template_id'] = $template_id;
		} else {
			$enabled_mails[ $slug ]['lang'][ $lang ] = $template_id;
		}

		return update_option( 'bwfan_enabled_transactional_mails', $enabled_mails, true );
	}

	/**
	 * Resend conversation
	 *
	 * @param int $eid
	 *
	 * @return bool
	 */
	public function resend_same_conversation( $eid ) {
		$conversation = new BWFAN_Engagement_Tracking( $eid );
		if ( ! $conversation instanceof BWFAN_Engagement_Tracking ) {
			return false;
		}

		$contact = $conversation->get_contact();
		if ( ! $contact instanceof BWFCRM_Contact ) {
			return false;
		}
		$merge_tags    = $conversation->get_merge_tags();
		$template_id   = $conversation->get_template_id();
		$template_data = BWFCRM_Templates::get_transactional_mail_by_template_id( $template_id );
		if ( empty( $template_data ) ) {
			return false;
		}
		$email_content = isset( $template_data['data'] ) && BWFAN_Common::is_json( $template_data['data'] ) ? json_decode( $template_data['data'], true ) : [];
		$email_body    = ! empty( $template_data['template'] ) ? $template_data['template'] : '';
		$email_subject = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';
		$pre_header    = isset( $email_content['preheader'] ) && ! empty( $email_content['preheader'] ) ? $email_content['preheader'] : '';
		if ( empty( $email_body ) || empty( $email_subject ) ) {
			return false;
		}
		/** @var  $global_email_settings BWFAN_Common settings */
		$global_email_settings = BWFAN_Common::get_global_settings();
		/** Email Subject And body */
		if ( is_array( $merge_tags ) && 0 < count( $merge_tags ) ) {
			$email_subject = BWFAN_Core()->conversations->parse_email_merge_tags( $email_subject, $merge_tags );
			$email_body    = BWFAN_Core()->conversations->parse_email_merge_tags( $email_body, $merge_tags );
			$pre_header    = BWFAN_Core()->conversations->parse_email_merge_tags( $pre_header, $merge_tags );
		}

		/** Email Body */
		$body = BWFAN_Common::bwfan_correct_protocol_url( $email_body );
		$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, 'block', $email_subject );
		/** Apply Click tracking and UTM Params */
		$utm_enabled = isset( $email_content['utmEnabled'] ) && 1 === absint( $email_content['utmEnabled'] );
		$utm_data    = $utm_enabled && isset( $email_content['utm'] ) && is_array( $email_content['utm'] ) ? $email_content['utm'] : array();
		$uid         = $contact->contact->get_uid();
		/** Set contact object */
		BWFCRM_Core()->conversation->contact = $contact;
		$body                                = BWFCRM_Core()->conversation->append_to_email_body_links( $body, $utm_data, $conversation->get_hash(), $uid );
		/** Apply Pre-Header and Hash ID (open pixel id) */

		$body = BWFCRM_Core()->conversation->append_to_email_body( $body, $pre_header, $conversation->get_hash() );
		/** Email Headers */
		$reply_to_email = isset( $email_content['reply_to_email'] ) ? $email_content['reply_to_email'] : $global_email_settings['bwfan_email_reply_to'];
		$from_email     = isset( $email_content['from_email'] ) ? $email_content['from_email'] : $global_email_settings['bwfan_email_from'];
		$from_name      = isset( $email_content['from_name'] ) ? $email_content['from_name'] : $global_email_settings['bwfan_email_from_name'];
		/** Setup Headers */
		$header   = array();
		$header[] = 'MIME-Version: 1.0';
		if ( ! empty( $from_email ) && ! empty( $from_name ) ) {
			$header[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}
		if ( ! empty( $reply_to_email ) ) {
			$header[] = 'Reply-To:  ' . $reply_to_email;
		}
		$header[] = 'Content-type:text/html;charset=UTF-8';
		/** Removed wp mail filters */
		BWFCRM_Common::bwf_remove_filter_before_wp_mail();
		$header = apply_filters( 'bwfan_email_headers', $header );

		BWFCRM_Common::$captured_email_failed_message = null;

		/** Send the Email */
		return wp_mail( $conversation->get_send_to(), $email_subject, $body, $header );
	}

	public function resend_conversation_to_contact( $eid ) {
		$old_conversation = new BWFAN_Engagement_Tracking( $eid );
		if ( ! $old_conversation instanceof BWFAN_Engagement_Tracking || ! $old_conversation->is_valid() ) {
			return false;
		}
		$contact = $old_conversation->get_contact();
		if ( ! $contact instanceof BWFCRM_Contact ) {
			return false;
		}
		$merge_tags    = $old_conversation->get_merge_tags();
		$template_id   = $old_conversation->get_template_id();
		$template_data = BWFCRM_Templates::get_transactional_mail_by_template_id( $template_id );
		if ( empty( $template_data ) ) {
			return false;
		}
		$merge_tags_data = [];
		// If merge tags are not available then set the merge tags data
		if ( empty( $merge_tags ) ) {
			BWFAN_Common::bwfan_before_send_mail( 'block' );
			// Set order merge tags data for the template
			if ( isset( $template_data['title'] ) && in_array( $template_data['title'], [
					'new_account',
					'reset_password'
				] ) ) {
				$merge_tags_data['user_id'] = $old_conversation->get_oid();
			} else {
				$merge_tags_data['order_id'] = $old_conversation->get_oid();

				if ( 'customer_note' === $template_data['title'] ) {
					$merge_tags_data['current_order_note'] = BWFCRM_Common::get_latest_customer_note( $old_conversation->get_oid() );
				}
			}

			$merge_tags_data['cid']   = $contact->contact->get_id();
			$merge_tags_data['email'] = $contact->contact->get_email();
		}
		$email_content = isset( $template_data['data'] ) && BWFAN_Common::is_json( $template_data['data'] ) ? json_decode( $template_data['data'], true ) : [];
		$email_body    = ! empty( $template_data['template'] ) ? $template_data['template'] : '';
		$email_subject = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';
		$pre_header    = isset( $email_content['preheader'] ) && ! empty( $email_content['preheader'] ) ? $email_content['preheader'] : '';
		if ( empty( $email_body ) || empty( $email_subject ) ) {
			return false;
		}
		/** @var  $global_email_settings BWFAN_Common settings */
		$global_email_settings = BWFAN_Common::get_global_settings();

		$new_conversation = new BWFAN_Engagement_Tracking();
		$new_conversation->set_template_id( $template_id );
		$new_conversation->set_oid( $old_conversation->get_oid() );
		$new_conversation->set_mode( BWFAN_Email_Conversations::$MODE_EMAIL );
		$new_conversation->set_contact( $contact );
		$new_conversation->set_send_to( $contact->contact->get_email() );
		$new_conversation->enable_tracking();
		$new_conversation->set_type( BWFAN_Email_Conversations::$TYPE_TRANSACTIONAL );
		$new_conversation->set_status( BWFAN_Email_Conversations::$STATUS_DRAFT );

		if ( ! empty( $merge_tags ) ) {
			// Set the merge tags if not empty
			$new_conversation->set_merge_tags( $merge_tags );
			/** Email Subject, Preheader And Body */
			$email_subject = BWFAN_Core()->conversations->parse_email_merge_tags( $email_subject, $merge_tags );
			$email_body    = BWFAN_Core()->conversations->parse_email_merge_tags( $email_body, $merge_tags );
			$pre_header    = BWFAN_Core()->conversations->parse_email_merge_tags( $pre_header, $merge_tags );
		} else {
			// Set the merge tags if empty by decoding current content
			$new_conversation->add_merge_tags_from_string( $email_body, $merge_tags_data );
			$new_conversation->add_merge_tags_from_string( $email_subject, $merge_tags_data );
			/** Email Subject, Preheader And Body */
			$email_body    = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $email_body, 5 ) : $email_body;
			$email_subject = BWFAN_Common::decode_merge_tags( $email_subject );
			$merge_tags    = $new_conversation->get_merge_tags();
			$email_body    = BWFAN_Common::replace_merge_tags( $email_body, $merge_tags, $contact->get_id() );
			$pre_header    = BWFAN_Common::decode_merge_tags( $pre_header );
		}
		/** Email Body */
		$body = BWFAN_Common::bwfan_correct_protocol_url( $email_body );
		$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, 'block', $email_subject );
		/** Apply Click tracking and UTM Params */
		$utm_enabled = isset( $email_content['utmEnabled'] ) && 1 === absint( $email_content['utmEnabled'] );
		$utm_data    = $utm_enabled && isset( $email_content['utm'] ) && is_array( $email_content['utm'] ) ? $email_content['utm'] : array();
		$uid         = $contact->contact->get_uid();
		/** Set contact object */
		BWFCRM_Core()->conversation->contact = $contact;
		$body                                = BWFCRM_Core()->conversation->append_to_email_body_links( $body, $utm_data, $new_conversation->get_hash(), $uid );
		/** Apply Pre-Header and Hash ID (open pixel id) */
		$body = BWFCRM_Core()->conversation->append_to_email_body( $body, $pre_header, $new_conversation->get_hash() );
		/** Email Headers */
		$reply_to_email = isset( $email_content['reply_to_email'] ) ? $email_content['reply_to_email'] : $global_email_settings['bwfan_email_reply_to'];
		$from_email     = isset( $email_content['from_email'] ) ? $email_content['from_email'] : $global_email_settings['bwfan_email_from'];
		$from_name      = isset( $email_content['from_name'] ) ? $email_content['from_name'] : $global_email_settings['bwfan_email_from_name'];
		/** Setup Headers */
		$header   = array();
		$header[] = 'MIME-Version: 1.0';
		if ( ! empty( $from_email ) && ! empty( $from_name ) ) {
			$header[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}
		if ( ! empty( $reply_to_email ) ) {
			$header[] = 'Reply-To:  ' . $reply_to_email;
		}
		$header[] = 'Content-type:text/html;charset=UTF-8';
		/** Removed wp mail filters */
		BWFCRM_Common::bwf_remove_filter_before_wp_mail();

		$header = apply_filters( 'bwfan_email_headers', $header );

		BWFCRM_Common::$captured_email_failed_message = null;

		/** Send the Email */
		$email_status = wp_mail( $new_conversation->get_send_to(), $email_subject, $body, $header );
		/** Set the status of Email */
		if ( true === $email_status ) {
			$new_conversation->set_status( BWFAN_Email_Conversations::$STATUS_SEND );
		} else {
			$new_conversation->set_status( BWFAN_Email_Conversations::$STATUS_ERROR );
		}
		$new_conversation->save();

		return $email_status;
	}

	/**
	 * Update option template id by slug and lang parameter
	 *
	 * @param string $slug
	 * @param string $lang
	 *
	 * @return bool
	 */
	public function update_option_template_id( $slug = '', $template_id = 0, $lang = '' ) {
		if ( empty( $slug ) || empty( $template_id ) ) {
			return false;
		}
		$enabled_mails = $this->get_enabled_transactional_mails_setting();
		if ( empty( $lang ) ) {
			$enabled_mails[ $slug ]['template_id'] = $template_id;
		} else {
			$enabled_mails[ $slug ]['lang'][ $lang ] = $template_id;
		}

		return update_option( 'bwfan_enabled_transactional_mails', $enabled_mails, true );
	}

	public function get_enabled_transactional_mails_setting() {
		$enabled_mails = get_option( 'bwfan_enabled_transactional_mails', [] );

		return ! is_array( $enabled_mails ) ? [] : $enabled_mails;
	}
}

if ( class_exists( 'BWFCRM_Transactional_Mail_Loader' ) ) {
	BWFCRM_Core::register( 'transactional_mails', 'BWFCRM_Transactional_Mail_Loader' );
}