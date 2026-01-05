<?php
/**
 * Broadcast Processing Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[AllowDynamicProperties]
final class BWFCRM_Broadcast_Processing {
	/** Constants */
	public static $CAMPAIGN_DRAFT = 1;
	public static $CAMPAIGN_SCHEDULED = 2;
	public static $CAMPAIGN_RUNNING = 3;
	public static $CAMPAIGN_COMPLETED = 4;
	public static $CAMPAIGN_HALT = 5;
	public static $CAMPAIGN_PAUSED = 6;

	public static $CAMPAIGN_EMAIL = 1;
	public static $CAMPAIGN_SMS = 2;
	public static $CAMPAIGN_WHATSAPP = 3;

	public $CONTACT_BATCH_COUNT = 75;
	public $AS_CALL_TIME;

	public $SMS_PER_SECOND_LIMIT = 1;
	public $EMAIL_PER_SECOND_LIMIT = 10000;

	/** Class Properties */
	/** @var BWFCRM_Broadcast_Processing current_broadcast */
	private static $ins = null;

	private $started_send_mail_at = null;
	private $records_processed_in_one_sec = 0;

	private $last_engagement_sent = null;

	public $start_time = 0;

	/** @var array current_broadcast */
	private $current_broadcast = null;

	private $offset = 0;
	private $processed = 0;
	private $count = 0;
	private $daily_message_limit = 0;
	private $daily_achieved_limit = 0;
	private $contact_ids = array();
	public $stop_broadcast_required = false;
	public $stop_broadcast_for_shorten_url = false;

	public $default_email_settings = array();

	/** Email is supposed to utilise Daily Limit feature for now */
	public $supported_daily_limit_broadcast_types = array( 1 );

	private $logs = array();

	public $failed_cids = array();
	public $err_msg = array();

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		/** Scheduler per Run time in seconds */
		$this->AS_CALL_TIME = $this->get_per_call_time();

		add_action( 'bwfcrm_broadcast_run_queue', array( $this, 'do_scheduler_work' ) );
	}

	public function do_scheduler_work() {
		/** If Sandbox mode active */
		if ( true === BWFAN_Common::is_sandbox_mode_active() ) {
			return;
		}

		if ( true === apply_filters( 'bwfcrm_disable_broadcast_run_queue', false ) ) {
			return;
		}

		$this->default_email_settings = BWFAN_Common::get_global_settings();

		/** SMS & EMail limit per second */
		$this->EMAIL_PER_SECOND_LIMIT = isset( $this->default_email_settings['bwfan_email_per_second_limit'] ) ? absint( $this->default_email_settings['bwfan_email_per_second_limit'] ) : 15;
		$this->EMAIL_PER_SECOND_LIMIT = apply_filters( 'bwfan_broadcast_email_per_second_limit', $this->EMAIL_PER_SECOND_LIMIT );
		$this->SMS_PER_SECOND_LIMIT   = apply_filters( 'bwfan_broadcast_sms_per_second_limit', $this->SMS_PER_SECOND_LIMIT );

		$this->start_time = time();

		/** Process scheduled broadcasts, make them running */
		$this->process_scheduled_broadcasts();
		if ( $this->is_time_exceeded() ) {
			return;
		}

		/** Process running broadcasts */
		$this->process_running_broadcasts();
	}

	public function process_scheduled_broadcasts() {
		$scheduled = BWFAN_Model_Broadcast::get_scheduled_broadcasts_to_run();
		if ( ! is_array( $scheduled ) || empty( $scheduled ) ) {
			return;
		}

		foreach ( $scheduled as $broadcast ) {
			if ( $this->is_time_exceeded() ) {
				break;
			}

			$this->process_single_scheduled_broadcasts( $broadcast );
		}
	}

	public function process_single_scheduled_broadcasts( $broadcast ) {
		$args = $this->get_broadcast_query_args( $broadcast );

		$parentBroadcast = isset( $broadcast['parent'] ) ? absint( $broadcast['parent'] ) : false;
		/** Reset broadcast stats */
		delete_transient( 'bwfan_broadcast_stats' );
		/** Fetching last contact ID of a broadcast from the given filters */
		if ( ! empty( $parentBroadcast ) ) {
			$args['props']['limit']          = 1;
			$args['props']['offset']         = 0;
			$args['props']['grab_totals']    = true;
			$args['props']['exclude_unsubs'] = isset( $args['data']['is_promotional'] ) && 1 === intval( $args['data']['is_promotional'] );
			$contacts_array                  = BWFCRM_Core()->campaigns->get_unopen_broadcast_contacts( absint( $parentBroadcast ), $args['props'], OBJECT );
		} else {

			if ( ! isset( $args['props']['exclude_unsubs'] ) || false === $args['props']['exclude_unsubs'] ) {
				$args['props']['exclude_unsubs'] = apply_filters( 'bwfan_force_exclude_unsubscribe_contact', false );
			}
			$contacts_array = BWFCRM_Contact::get_contacts( '', 0, 1, $args['filters'], $args['props'], OBJECT );
		}
		$contacts       = $contacts_array['contacts'];
		$total_contacts = ! empty( $contacts ) && isset( $contacts_array['total_count'] ) ? intval( $contacts_array['total_count'] ) : 0;
		$last_contact   = is_array( $contacts ) && count( $contacts ) > 0 && $contacts[0] instanceof BWFCRM_Contact ? $contacts[0]->get_id() : 0;

		if ( $total_contacts > 0 && absint( $last_contact ) > 0 ) {
			/** Increment the last contact ID by 1, to always get the contacts with IDs less than ($last_contact+1)  */
			$last_contact            = absint( $last_contact ) + 1;
			$args['data']['content'] = $this->get_or_create_multiple_templates( $args['data']['content'], $args['props']['fetch_base'], true );
			BWFAN_Model_Broadcast::update_status_to_run( $broadcast['id'], $last_contact, $args['data'], $total_contacts );

			return;
		}

		$args['data']['halt_reason'] = __( 'No contacts found for this broadcast', 'wp-marketing-automations-pro' );
		BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $args['data'] );
	}

	public function get_or_create_multiple_templates( $emails, $mode = 1, $return_with_contents = false ) {
		if ( ! is_array( $emails ) ) {
			return array();
		}

		$result = array();
		foreach ( $emails as $email ) {
			/** Set template mode 1 - rich, 3 - html 4 - editor */
			$template_type = BWFAN_PRO_Common::get_email_template_type( $email['type'] );
			$data          = array( 'template' => $template_type );
			if ( isset( $email['whatsAppImage'] ) && $email['whatsAppImage'] ) {
				if ( isset( $email['whatsAppImageSetting'] ) && ! empty( $email['whatsAppImageSetting'] ) ) {
					$data = $email['whatsAppImageSetting'];
				}
			}
			$editor_body   = isset( $email['editor'] ) && is_array( $email['editor'] ) && isset( $email['editor']['body'] ) ? $email['editor']['body'] : '';
			$template_body = isset( $email['type'] ) && 'editor' === $email['type'] ? $editor_body : $email['body'];
			$template_body = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $template_body, $email['type'] ) : $template_body;

			/**
			 * If a template is a block or 5, then decode the specific blocks in the mail content.
			 * Decoding the specific blocks before saving the template data for fast execution at the time of sending.
			 * Replacing the global variables with their values as they are contact-specific
			 *
			 * For future versions, if the block is genric, we need to add it in the decode_specific_blocks_before function parameter.
			 *
			 * @since 3.5.3
			 */
			if ( 5 === intval( $template_type ) ) {
				include_once BWFAN_PRO_PLUGIN_DIR . '/crm/includes/class-bwfcrm-block-editor.php';
				if ( method_exists( 'BWFCRM_Block_Editor', 'decode_specific_blocks_before' ) ) {
					$template_body = BWFCRM_Block_Editor::decode_specific_blocks_before( $template_body, [ 'bwfbe_multi_product', 'bwfbe_coupon', 'bwfbe_social_icons' ] );
				}

				$global_val = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings_var : [];
				if ( ! empty( $global_val ) ) {
					$global_val_k  = array_keys( $global_val );
					$global_val_v  = array_values( $global_val );
					$template_body = str_replace( $global_val_k, $global_val_v, $template_body );
				}
			}
			$template_id = BWFCRM_Core()->conversation->get_or_create_template( $mode, 1 === $mode && isset( $email['subject'] ) ? $email['subject'] : '', $template_body, 'id', $data );
			if ( true === $return_with_contents ) {
				$email['template_id'] = $template_id;
				$result[]             = $email;
			} else {
				$result[] = $template_id;
			}
		}

		return $result;
	}

	public function process_running_broadcasts() {
		$this->set_log( 'processing running broadcast' );
		$broadcasts = BWFAN_Model_Broadcast::get_broadcasts_by_status( self::$CAMPAIGN_RUNNING, true );
		if ( ! is_array( $broadcasts ) || empty( $broadcasts ) ) {
			return;
		}

		do_action( 'bwfan_before_executing_broadcast', $broadcasts );
		$this->set_log( count( $broadcasts ) . ' broadcasts found' );

		$this->started_send_mail_at = microtime( true );

		foreach ( $broadcasts as $broadcast ) {
			/** Set delay if daily limit reached and set daily limit count */
			if ( $this->maybe_daily_limit_reached( $broadcast['type'] ) ) {
				BWFAN_Model_Broadcast::set_delay( $broadcast['id'] );

				continue;
			}

			/** Store current broadcast for class-wide access */
			$this->current_broadcast = $broadcast;

			/** Break, if current call request time exceeded */
			if ( $this->is_time_exceeded() ) {
				break;
			}

			/** Check if broadcast is valid */
			if ( ! $this->is_broadcast_valid() ) {
				continue;
			}

			/**
			 * Update last_modified to inform subsequent requests
			 */
			$last_modified_time = current_time( 'mysql', 1 );
			BWFAN_Model_Broadcast::update( array( 'last_modified' => $last_modified_time ), array( 'id' => absint( $broadcast['id'] ) ) );
			$broadcast['last_modified']               = $last_modified_time;
			$this->current_broadcast['last_modified'] = $last_modified_time;

			$data = isset( $broadcast['data'] ) && ! empty( $broadcast['data'] ) ? $broadcast['data'] : array();

			$this->processed = absint( $broadcast['processed'] );
			$this->offset    = absint( $broadcast['offset'] );
			$this->count     = absint( $broadcast['count'] );

			do {
				$this->set_log( 'broadcast_id' . $broadcast['id'] . '_before_fetch_contacts' );
				$this->fetch_contacts_for_broadcast();

				if ( $this->error_while_fetching_contacts() ) {
					break;
				}

				if ( true === $this->stop_broadcast_required ) {
					$this->stop_broadcast_required = false;

					break;
				}

				if ( empty( $this->contact_ids ) ) {
					BWFAN_Model_Broadcast::update_status_to_complete( $broadcast['id'], $data, $this->processed, $this->count );
					self::action_schedule_save_last_sent( $broadcast['id'] );

					/** Reset broadcast stats */
					delete_transient( 'bwfan_broadcast_stats' );
					break;
				}

				$this->process_single_ongoing_broadcast();
				/** Continue to next broadcast, in case needed to end current broadcast process */
				if ( true === $this->stop_broadcast_required ) {
					$this->stop_broadcast_required = false;

					break;
				}

				if ( $this->is_time_exceeded() ) {
					BWFAN_Model_Broadcast::update( array(
						'last_modified' => date( 'Y-m-d H:i:s', time() - 6 ),
					), array(
						'id' => absint( $broadcast['id'] ),
					) );
					BWFCRM_Common::reschedule_broadcast_action();
				}
			} while ( ! $this->is_time_exceeded() && ! BWFCRM_Common::memory_exceeded() );
		}

		$this->set_log( 'broadcasts_loop_end' );
		$this->log();
	}

	public function is_broadcast_valid() {
		$broadcast = $this->current_broadcast;
		$data      = $this->get_current_broadcast_data();

		/** SMS Validation */
		if ( self::$CAMPAIGN_SMS === absint( $broadcast['type'] ) ) {
			$provider = BWFCRM_Common::get_sms_provider_integration();
			if ( is_wp_error( $provider ) ) {
				$error_message       = $provider->get_error_message();
				$data['halt_reason'] = ! empty( $error_message ) ? $error_message : __( 'Invalid SMS settings', 'wp-marketing-automations-pro' );
				BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
				self::action_schedule_save_last_sent( $broadcast['id'] );

				return false;
			}
		}

		/** WhatsApp Validation */
		if ( self::$CAMPAIGN_WHATSAPP === absint( $broadcast['type'] ) && false === $this->maybe_validate_whatsapp_settings() ) {
			return false;
		}

		/** Broadcast Content Validation */
		$broadcast_content = $data['content'];
		if ( empty( $broadcast_content ) || ! is_array( $broadcast_content ) ) {
			$data['halt_reason'] = __( 'Broadcast content not found', 'wp-marketing-automations-pro' );
			BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
			self::action_schedule_save_last_sent( $broadcast['id'] );

			return false;
		}

		/** Validate broadcast might be running */
		if ( true === $this->maybe_broadcast_running() ) {
			return false;
		}
		$this->failed_cids = $data['failed_cids'] ?? [];
		$this->err_msg     = $data['failed_reason'] ?? [];

		return true;
	}

	public function fetch_contacts_for_broadcast() {
		$broadcast = $this->current_broadcast;

		$args = $this->get_broadcast_query_args( $broadcast );

		$args['props']['end_id']         = absint( $this->offset );
		$args['props']['exclude_end_id'] = true;

		/** Get Contacts */
		$parentBroadcast = isset( $broadcast['parent'] ) ? absint( $broadcast['parent'] ) : false;
		if ( ! empty( $parentBroadcast ) ) {
			$args['props']['limit']          = $this->CONTACT_BATCH_COUNT;
			$args['props']['offset']         = 0;
			$args['props']['grab_totals']    = true;
			$args['props']['exclude_unsubs'] = isset( $args['data']['is_promotional'] ) && 1 === intval( $args['data']['is_promotional'] );
			$contacts_array                  = BWFCRM_Core()->campaigns->get_unopen_broadcast_contacts( absint( $parentBroadcast ), $args['props'], OBJECT );
		} else {
			if ( ! isset( $args['props']['exclude_unsubs'] ) || false === $args['props']['exclude_unsubs'] ) {
				$args['props']['exclude_unsubs'] = apply_filters( 'bwfan_force_exclude_unsubscribe_contact', false );
			}
			$contacts_array = BWFCRM_Contact::get_contacts( '', 0, $this->CONTACT_BATCH_COUNT, $args['filters'], $args['props'], OBJECT );
		}

		if ( ! is_array( $contacts_array ) || ! isset( $contacts_array['contacts'] ) || ! is_array( $contacts_array['contacts'] ) ) {
			$this->contact_ids = BWFCRM_Common::crm_error( __( 'Invalid broadcast contact query results', 'wp-marketing-automations-pro' ) );
			$this->set_log( 'broadcast_id' . $broadcast['id'] . '_no_query_result' );

			return;
		}

		/** Get IDs From Contacts */
		$this->contact_ids = array_map( function ( $contact ) {
			return $contact->get_id();
		}, $contacts_array['contacts'] );

		/** Adding failsafe to avoid multiple broadcast processing on the same contact */
		/** getting contacts ids in desc order */
		arsort( $this->contact_ids );
		/** First id to process is */
		$first_id = current( $this->contact_ids );

		if ( $first_id >= $this->offset ) {
			/** If first contact already processed, empty the contact array */
			$this->set_log( 'broadcast_id' . $broadcast['id'] . ' has data but contact already processed' );

			$this->stop_broadcast_required = true;

			return;
		}

		$this->set_log( 'broadcast_id' . $broadcast['id'] . ' - IDs: ' . implode( ',', $this->contact_ids ) );

		return;
	}

	protected function get_broadcast_query_args( $broadcast ) {
		if ( ! is_array( $broadcast ) || empty( $broadcast ) ) {
			return false;
		}

		$data = isset( $broadcast['data'] ) && ! empty( $broadcast['data'] ) ? $broadcast['data'] : false;
		if ( ! is_array( $data ) && ! empty( $data ) ) {
			$data = json_decode( $data, true );
		}

		$exclude = ! empty( $data ) && isset( $data['exclude'] ) && is_array( $data['exclude'] ) ? $data['exclude'] : array();
		$exclude = array_map( 'absint', $exclude );

		$parentBroadcast = isset( $broadcast['parent'] ) ? absint( $broadcast['parent'] ) : false;
		if ( ! empty( $parentBroadcast ) ) {
			return array(
				'filters' => array(),
				'data'    => $data,
				'props'   => array(
					'order'       => 'DESC',
					'exclude_ids' => $exclude,
					'fetch_base'  => 1,
				),
			);
		}

		$filters              = ! empty( $data ) && isset( $data['filters'] ) ? $data['filters'] : array();
		$filters['status_is'] = 1;

		$mode = isset( $broadcast['type'] ) ? absint( $broadcast['type'] ) : 1;

		/** Fetch Base must SMS (Phone Number), if mode is WhatsApp */
		$mode = self::$CAMPAIGN_WHATSAPP === $mode ? self::$CAMPAIGN_SMS : $mode;

		return array(
			'filters' => $filters,
			'data'    => $data,
			'props'   => array(
				'fetch_base'           => $mode,
				'order_by'             => 'id',
				'order'                => 'DESC',
				'grab_totals'          => true,
				'exclude_unsubs_lists' => isset( $filters['lists_any'] ),
				'exclude_ids'          => $exclude,
				'include_soft_bounce'  => ! empty( $data['includeSoftBounce'] ),
				'include_unverified'   => ! empty( $data['includeUnverified'] ),
				'include_unsubscribe'  => empty( $data['is_promotional'] ),
			),
		);
	}

	public function error_while_fetching_contacts() {
		if ( ! $this->contact_ids instanceof WP_Error ) {
			return false;
		}

		$data                = $this->get_current_broadcast_data();
		$data['halt_reason'] = $this->contact_ids->get_error_message();
		BWFAN_Model_Broadcast::update_status_to_halt( $this->current_broadcast['id'], $data );
		self::action_schedule_save_last_sent( $this->current_broadcast['id'] );

		return true;
	}

	public function get_per_second_limit() {
		/** Email */
		if ( self::$CAMPAIGN_EMAIL === absint( $this->current_broadcast['type'] ) ) {
			return absint( $this->EMAIL_PER_SECOND_LIMIT );
		}
		/** SMS */
		if ( self::$CAMPAIGN_SMS === absint( $this->current_broadcast['type'] ) ) {
			return absint( $this->SMS_PER_SECOND_LIMIT );
		}

		/** Not checking WhatsApp here */
		return false;
	}

	public function process_single_ongoing_broadcast() {
		$broadcast = $this->current_broadcast;
		$data      = $this->get_current_broadcast_data();

		$per_second_limit = $this->get_per_second_limit();
		foreach ( $this->contact_ids as $id ) {
			$this->set_log( 'contact#' . $id . '_start' );
			if ( $this->is_time_exceeded() || BWFCRM_Common::memory_exceeded() ) {
				$this->set_log( 'contact#' . $id . '_time exceeded' );
				break;
			}

			/**
			 *  Fallback, in case previously processed ID gets into loop again,
			 *  where offset must be old, and id must be new.
			 */
			if ( $this->offset <= $id ) {
				$this->set_log( 'contact#' . $id . ' moving to next contact ' . $this->offset . ' = ' . $id );
				continue;
			}

			/**
			 * If broadcast support daily limit, check that
			 * currently email type supports daily limit
			 * if daily limit reached then delay and end current broadcast
			 */
			$daily_limit_supported = in_array( absint( $broadcast['type'] ), $this->supported_daily_limit_broadcast_types );
			if ( $daily_limit_supported && $this->daily_achieved_limit >= $this->daily_message_limit ) {
				BWFAN_Model_Broadcast::set_delay( $broadcast['id'] );
				$this->stop_broadcast_required = true;

				return;
			}

			/** Hold for one second to complete, if per second limit achieved */
			if ( ! empty( $per_second_limit ) && $per_second_limit <= $this->records_processed_in_one_sec ) {
				$this->set_log( 'contact#' . $id . '_per_second_limit_reached' );
				$this->delay_time();
			}

			/**
			 * Check if Smart sending is enabled
			 * Maybe need to stop broadcast in case contacts processed
			 * If nothing returned, continue because smart sending is not enabled
			 * If hold returned, stop the broadcast
			 */
			$smart_send = $this->maybe_process_smart_send_data( $broadcast['id'], $data, $this->processed, $this->count, absint( $broadcast['type'] ) );
			if ( 'hold' === $smart_send ) {
				$this->stop_broadcast_required = true;

				return;
			} elseif ( ! empty( $smart_send ) ) {
				$data['winner']    = absint( $smart_send );
				$broadcast['data'] = $data;
			}

			if ( self::$CAMPAIGN_SMS === absint( $broadcast['type'] ) ) {
				$this->process_broadcast_contact_sms( $broadcast, $id );
			} elseif ( self::$CAMPAIGN_WHATSAPP === absint( $broadcast['type'] ) ) {
				$this->whatsapp_delay_time();
				$this->process_broadcast_contact_whatsapp( $broadcast, $id );
			} else {
				$this->process_broadcast_contact_email( $broadcast, $id );
			}

			if ( true === $this->stop_broadcast_for_shorten_url ) {
				return;
			}

			/** set last engagement time */
			$this->last_engagement_sent = microtime( true );

			$this->records_processed_in_one_sec ++;
			$this->processed ++;
			$this->daily_achieved_limit ++;
			$this->offset = $id;

			if ( count( $this->failed_cids ) > 0 ) {
				$data['failed_cids']   = $this->failed_cids;
				$data['failed_reason'] = array_values( array_unique( $this->err_msg ) );
				$status                = count( $this->failed_cids ) >= 20 ? self::$CAMPAIGN_PAUSED : false;
				BWFAN_Model_Broadcast::update_broadcast_data_with_status( $broadcast['id'], $data, $status, $this->offset, $this->processed );

				/** If the last 10 engagements are failed, stop the broadcast */
				if ( count( $this->failed_cids ) >= 20 ) {
					$this->set_log( 'contact #' . $id . ' email failed. 20 consecutive failed contacts are: ' . implode( ', ', $this->failed_cids ) );
					$this->stop_broadcast_required = true;
				}
			} else {
				// No email failure
				if ( isset( $data['failed_cids'] ) ) {
					// case when failed_cids is set
					unset( $data['failed_cids'] );
					if ( isset( $data['failed_reason'] ) ) {
						unset( $data['failed_reason'] );
					}
					BWFAN_Model_Broadcast::update_broadcast_data_with_status( $broadcast['id'], $data, false, $this->offset, $this->processed );
				} else {
					BWFAN_Model_Broadcast::update_campaign_offsets( $broadcast['id'], $this->offset, $this->processed );
				}
			}

			if ( true === $this->stop_broadcast_required ) {
				return;
			}
		}
	}

	/**
	 * @param $broadcast
	 * @param $contact_id
	 *
	 * @return false|void
	 */
	public function process_broadcast_contact_whatsapp( $broadcast, $contact_id ) {
		$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		if ( empty( $contact->get_id() ) ) {
			return;
		}
		$this->set_log( 'contact#' . $contact_id . '_process_whatsapp_message_start' );

		$to = BWFAN_PRO_Common::get_contact_full_number( $contact );
		if ( empty( $to ) ) {
			$this->set_log( 'contact#' . $contact_id . '_no_phone_number_whatsapp' );

			return;
		}

		$data = $broadcast['data'];

		$broadcast_id        = absint( $broadcast['id'] );
		$broadcast_author_id = absint( $broadcast['created_by'] );
		$broadcast_content   = $data['content'];

		$template_winner = isset( $data['winner'] ) ? absint( $data['winner'] ) : 0;
		$chosen_content  = $this->get_current_email_template( $broadcast_content, $this->processed, $template_winner );

		$message_data = BWFCRM_Core()->conversation->create_campaign_conversation( $contact, $broadcast_id, $chosen_content['template_id'], $broadcast_author_id, BWFCRM_Campaigns::$CAMPAIGN_WHATSAPP );
		if ( is_wp_error( $message_data ) ) {
			$e_data = $message_data->get_error_data();
			if ( is_array( $e_data ) && isset( $e_data['conversation_id'] ) ) {
				BWFCRM_Core()->conversation->fail_the_conversation( absint( $e_data['conversation_id'] ), $message_data->get_error_message() );
			}

			return;
		}

		if ( ! isset( $message_data['conversation_id'] ) ) {
			$this->set_log( 'contact#' . $contact_id . '_conversation_id not found' );

			return;
		}
		$conversation_id = absint( $message_data['conversation_id'] );
		$hash_code       = isset( $message_data['hash_code'] ) ? $message_data['hash_code'] : '';
		$template        = isset( $message_data['template'] ) ? $message_data['template'] : '';
		$template_data   = isset( $message_data['template_data'] ) ? $message_data['template_data'] : array();
		$utm_details     = isset( $chosen_content['utm'] ) && is_array( $chosen_content['utm'] ) ? $this->get_utm_data_from_campaign_data( $chosen_content['utm'] ) : '';

		$message_body = BWFCRM_Core()->conversation->prepare_sms_body( $conversation_id, $contact_id, $hash_code, $template, $utm_details, $broadcast_id );
		if ( true === $this->stop_broadcast_for_shorten_url ) {
			return false;
		}
		$textdata = array(
			'type' => 'text',
			'data' => $message_body,
		);
		$message  = array( $textdata );
		if ( ! empty( $template_data ) && isset( $template_data['position'] ) && isset( $template_data['imageURL'] ) && ! empty( $template_data['position'] ) && ! empty( $template_data['imageURL'] ) ) {
			$imagedata = array(
				'type' => 'image',
				'data' => $template_data['imageURL'],
			);

			if ( $template_data['position'] == 'before' ) {
				$message = array( $imagedata, $textdata );
			} else {
				$message = array( $textdata, $imagedata );
			}
		}

		$response = BWFCRM_Core()->conversation->send_whatsapp_message( $to, $message, $utm_details );
		if ( is_array( $response ) && $response['status'] == true ) {
			BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );

			return;
		}

		$error_message = __( 'Message could not be sent. ', 'wp-marketing-automations-pro' );

		if ( isset( $response['msg'] ) && ! empty( $response['msg'] ) ) {
			$error_message = $response['msg'];
		}

		BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $error_message );
	}

	public function process_broadcast_contact_sms( $broadcast, $contact_id ) {
		$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		if ( empty( $contact->get_id() ) ) {
			return false;
		}

		$data = $broadcast['data'];

		$broadcast_id        = absint( $broadcast['id'] );
		$broadcast_author_id = absint( $broadcast['created_by'] );
		$broadcast_content   = $data['content'];
		if ( empty( $broadcast_content ) || ! is_array( $broadcast_content ) ) {
			return false;
		}

		$template_winner = isset( $data['winner'] ) ? absint( $data['winner'] ) : 0;
		$chosen_content  = $this->get_current_email_template( $broadcast_content, $this->processed, $template_winner );
		$sms_data        = BWFCRM_Core()->conversation->create_campaign_conversation( $contact, $broadcast_id, $chosen_content['template_id'], $broadcast_author_id, BWFCRM_Campaigns::$CAMPAIGN_SMS );
		if ( is_wp_error( $sms_data ) ) {
			$e_data = $sms_data->get_error_data();
			if ( is_array( $e_data ) && isset( $e_data['conversation_id'] ) ) {
				BWFCRM_Core()->conversation->fail_the_conversation( absint( $e_data['conversation_id'] ), $sms_data->get_error_message() );
			}

			return false;
		}

		if ( ! isset( $sms_data['conversation_id'] ) ) {
			return false;
		}

		$conversation_id = absint( $sms_data['conversation_id'] );
		$hash_code       = isset( $sms_data['hash_code'] ) ? $sms_data['hash_code'] : '';
		$template        = isset( $sms_data['template'] ) ? $sms_data['template'] : '';
		$utm_details     = isset( $chosen_content['utm'] ) && is_array( $chosen_content['utm'] ) ? $this->get_utm_data_from_campaign_data( $chosen_content['utm'] ) : '';
		$sms_body        = BWFCRM_Core()->conversation->prepare_sms_body( $conversation_id, $contact_id, $hash_code, $template, $utm_details, $broadcast_id );
		if ( true === $this->stop_broadcast_for_shorten_url ) {
			return false;
		}
		if ( is_wp_error( $sms_body ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $sms_body->get_error_message() );

			return false;
		}

		$to = BWFAN_PRO_Common::get_contact_full_number( $contact );

		if ( empty( $to ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'No phone number found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' ) );

			return false;
		}

		$send_sms_result = BWFCRM_Common::send_sms( array(
			'to'   => $to,
			'body' => $sms_body
		) );

		if ( is_wp_error( $send_sms_result ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $send_sms_result->get_error_message() );

			return false;
		}

		return BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );
	}

	/**
	 * Get template data for passing it to conversation
	 *
	 * @param int $tid
	 * @param array $raw_template_data
	 *
	 * @return array
	 * @since 3.5.3
	 *
	 */
	public function get_template_from_id( $tid = 0, $raw_template_data = [] ) {
		if ( empty( $tid ) ) {
			return [
				'template' => 'editor' === $raw_template_data['type'] ? $raw_template_data['editor']['body'] : $raw_template_data['body'],
				'subject'  => $raw_template_data['subject'],
				'type'     => $raw_template_data['type']
			];
		}
		if ( ! empty( $this->current_broadcast['template_data_by_id'][ $tid ] ) ) {
			return $this->current_broadcast['template_data_by_id'][ $tid ];
		}

		$template = BWFAN_Model_Templates::get( $tid );
		if ( ! empty( $template ) ) {
			$this->current_broadcast['template_data_by_id'][ $tid ] = $template;

			return $template;
		}

		return [
			'template' => 'editor' === $raw_template_data['type'] ? $raw_template_data['editor']['body'] : $raw_template_data['body'],
			'subject'  => $raw_template_data['subject'],
			'type'     => $raw_template_data['type']
		];
	}

	public function process_broadcast_contact_email( $broadcast, $contact_id ) {
		$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		if ( empty( $contact->get_id() ) ) {
			return;
		}

		$data = $broadcast['data'];

		$broadcast_id        = absint( $broadcast['id'] );
		$broadcast_author_id = absint( $broadcast['created_by'] );
		$broadcast_content   = $data['content'];
		$template_winner     = isset( $data['winner'] ) ? absint( $data['winner'] ) : 0;
		$chosen_content      = $this->get_current_email_template( $broadcast_content, $this->processed, $template_winner );

		/**
		 * Get template data on the basic of template id if exists.
		 */
		$content_template = $this->get_template_from_id( $chosen_content['template_id'] ?? 0, $chosen_content );
		$email_data       = BWFCRM_Core()->conversation->create_campaign_conversation( $contact, $broadcast_id, $chosen_content['template_id'], $broadcast_author_id, BWFCRM_Campaigns::$CAMPAIGN_EMAIL, false, $content_template );

		if ( true === $this->validate_email_data_error( $email_data ) ) {
			return;
		}

		if ( ! isset( $email_data['conversation_id'] ) ) {
			return;
		}

		$conversation_id = absint( $email_data['conversation_id'] );
		$hash_code       = isset( $email_data['hash_code'] ) ? $email_data['hash_code'] : '';
		$template_type   = isset( $chosen_content['type'] ) ? $chosen_content['type'] : '';
		$email_subject   = isset( $email_data['subject'] ) ? $email_data['subject'] : '';
		$template        = isset( $email_data['template'] ) ? $email_data['template'] : '';
		$pre_header      = isset( $chosen_content['preheader'] ) ? $chosen_content['preheader'] : '';
		$utm_details     = isset( $chosen_content['utm'] ) && is_array( $chosen_content['utm'] ) ? $this->get_utm_data_from_campaign_data( $chosen_content['utm'] ) : '';

		$email_subject = BWFCRM_Core()->conversation->prepare_email_subject( $email_subject, $contact_id );
		try {
			$email_body = BWFCRM_Core()->conversation->prepare_email_body( $conversation_id, $contact_id, $hash_code, $template_type, $template, $pre_header, $utm_details, $broadcast_id );
		} catch ( Error $e ) {
			/** Update broadcast status to halt if any error comes in email body */
			$data['halt_reason'] = __( 'Error in email body: ', 'wp-marketing-automations-pro' ) . $e->getMessage();
			BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
			self::action_schedule_save_last_sent( $broadcast['id'] );
			die();
		}
		if ( is_wp_error( $email_body ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $email_body->get_error_message() );

			return;
		}

		$to = $contact->get_email();
		if ( ! is_email( $to ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'No email found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' ) );

			return;
		}

		$headers = array(
			'MIME-Version: 1.0',
			'From: ' . $data['senders_name'] . ' <' . $data['senders_email'] . '>',
			'Content-type: text/html;charset=UTF-8',
		);

		if ( isset( $data['replyto'] ) && ! empty( $data['replyto'] ) ) {
			$headers[] = 'Reply-To:  ' . $data['replyto'];
		}

		/** Set unsubscribe link in header */
		$unsubscribe_link = BWFAN_PRO_Common::get_unsubscribe_link( [ 'uid' => $contact->get_uid(), 'broadcast_id' => $broadcast_id ] );
		if ( ! empty( $unsubscribe_link ) ) {
			$headers[] = "List-Unsubscribe: <$unsubscribe_link>";
			$headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
		}

		$this->set_log( 'contact#' . $contact_id . '_before_wp_mail' );

		do_action( 'bwfan_before_send_email', $data, $email_body );
		$headers = apply_filters( 'bwfan_email_headers', $headers );

		$result = $error = false;
		try {
			/** Process the email */
			$result = $this->process_email( $to, $email_subject, $email_body, $headers );
		} catch ( Error|Exception $e ) {
			$error = $e->getMessage();
		}

		$this->set_log( 'contact#' . $contact_id . '_after_wp_mail' );

		if ( true === $result ) {
			BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );
			$this->failed_cids = [];
			$this->err_msg     = [];

			return;
		}

		$error = empty( $error ) ? BWFCRM_Common::$captured_email_failed_message : $error;
		$error = ! empty( $error ) ? $error : __( 'Email not sent (Error Unknown)', 'wp-marketing-automations-pro' );
		BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $error );

		$should_pause = apply_filters( 'bwfan_broadcast_should_pause_on_failures', true, $broadcast_id );
		$should_pause = is_bool( $should_pause ) ? $should_pause : true;
		if ( $should_pause ) {
			$this->failed_cids[] = $contact_id;
			$this->err_msg[]     = $error;
		}

		BWFCRM_Common::$captured_email_failed_message = null;
	}

	public function maybe_process_smart_send_data( $broadcast_id, $broadcast_data, $processed, $count, $type = 1 ) {
		/** Return false if smart sending not enabled */
		$smart_send_settings   = is_array( $broadcast_data ) && isset( $broadcast_data['smart_send'] ) ? $broadcast_data['smart_send'] : false;
		$is_smart_send_enabled = is_array( $smart_send_settings ) && isset( $smart_send_settings['enable'] ) && true === $smart_send_settings['enable'];
		if ( ! $is_smart_send_enabled ) {
			return false;
		}

		/** Return winner if already declared */
		$winner = is_array( $broadcast_data ) && isset( $broadcast_data['winner'] ) && ! empty( $broadcast_data['winner'] ) ? absint( $broadcast_data['winner'] ) : false;
		if ( $is_smart_send_enabled && ! empty( $winner ) ) {
			return absint( $winner );
		}

		/** Return if smart sending process already over */
		$is_smart_send_finished = is_array( $broadcast_data ) && isset( $broadcast_data['smart_sending_done'] ) && 1 === absint( $broadcast_data['smart_sending_done'] );
		if ( $is_smart_send_enabled && $is_smart_send_finished ) {
			return false;
		}

		/** Return false, if percent is not achieved yet */
		$processed        = ! empty( $processed ) ? absint( $processed ) : 0;
		$percent          = $is_smart_send_enabled && isset( $smart_send_settings['percent'] ) && ! empty( $smart_send_settings['percent'] ) ? absint( $smart_send_settings['percent'] ) : 0;
		$percent_achieved = $is_smart_send_enabled ? absint( ( $processed / absint( $count ) ) * 100 ) : 0;
		if ( empty( $percent ) || $percent_achieved < $percent ) {
			return false;
		}

		/** Return 'hold', if percent is achieved, and need to wait for the most opens or clicked template */
		$hold_broadcast = is_array( $broadcast_data ) && isset( $broadcast_data['hold_broadcast'] );
		if ( ! $hold_broadcast ) {
			$hours = $is_smart_send_enabled && isset( $smart_send_settings['hours'] ) ? absint( $smart_send_settings['hours'] ) : 0;
			BWFAN_Model_Broadcast::hold_broadcast_for_smart_send( $broadcast_id, $hours );

			return 'hold';
		}

		/** Return false, if unable to get the Winner */
		$db_winner = BWFAN_Model_Broadcast::get_broadcast_tids_stats( intval( $broadcast_id ), intval( $type ) );
		if ( ! is_array( $db_winner ) || empty( $db_winner['winner'] ) ) {
			BWFAN_Model_Broadcast::remove_hold_flag_smart_sending( absint( $broadcast_id ) );

			return false;
		}

		/** Return & declare winner */
		BWFAN_Model_Broadcast::declare_the_winner_email( intval( $broadcast_id ), $db_winner );
		$this->stop_broadcast_required = true;

		return intval( $db_winner['winner'] );
	}

	public function get_current_email_template( $email_content, $processed, $winner_tid ) {
		if ( ! empty( $winner_tid ) ) {
			foreach ( $email_content as $email ) {
				if ( ! is_array( $email ) || ! isset( $email['template_id'] ) || absint( $email['template_id'] ) !== absint( $winner_tid ) ) {
					continue;
				}

				return $email;
			}
		}

		return $email_content[ $processed % count( $email_content ) ];
	}

	public function get_utm_data_from_campaign_data( $utm_data ) {
		$final_data = array();
		$utm_keys   = array(
			'source'  => 'utm_source',
			'medium'  => 'utm_medium',
			'name'    => 'utm_campaign',
			'content' => 'utm_content',
			'term'    => 'utm_term',
		);

		foreach ( $utm_data as $key => $datum ) {
			if ( ! isset( $utm_keys[ $key ] ) ) {
				continue;
			}

			$final_data[ $utm_keys[ $key ] ] = $datum;
		}

		return $final_data;
	}

	/**
	 * Add gap between whatsapp messages
	 */
	public function whatsapp_delay_time() {
		if ( is_null( $this->last_engagement_sent ) ) {
			return;
		}

		$gap             = 1;
		$global_settings = BWFAN_Common::get_global_settings();
		if ( isset( $global_settings['bwfan_whatsapp_gap_btw_message'] ) && ! empty( $global_settings['bwfan_whatsapp_gap_btw_message'] ) ) {
			$gap = floatval( $global_settings['bwfan_whatsapp_gap_btw_message'] );
		}
		$sleep_time = $gap * 1000 * 1000;

		/** Get Remaining sleep time, in Micro-Seconds */
		$sleep_time = $sleep_time - ( ( microtime( true ) - $this->last_engagement_sent ) * 1000000 );

		if ( $sleep_time > 0 && $sleep_time < 1000000 ) {
			usleep( ceil( $sleep_time ) );
		}

		if ( $sleep_time >= 1000000 ) {
			$gap = $sleep_time / 1000000;
			sleep( ceil( $gap ) );
		}
	}

	public function delay_time() {
		$sleep_time = 1000000 - ( microtime( true ) - $this->started_send_mail_at ) * 1000000;
		if ( $sleep_time > 0 ) {
			usleep( ceil( $sleep_time ) );
		}

		$this->records_processed_in_one_sec = 0;
		$this->started_send_mail_at         = microtime( true );
	}

	/**
	 * Check if email daily limit reached
	 *
	 * @param int $type 1 email 2 sms 3 whatsapp
	 *
	 * @return bool
	 */
	public function maybe_daily_limit_reached( $type = 1 ) {
		if ( empty( $this->default_email_settings ) ) {
			$this->default_email_settings = BWFAN_Common::get_global_settings();
		}

		/** If not email campaign, no limit */
		if ( self::$CAMPAIGN_EMAIL !== absint( $type ) ) {
			return false;
		}

		$this->daily_achieved_limit = ( 0 === absint( $this->daily_achieved_limit ) ) ? BWFAN_Model_Engagement_Tracking::get_last_24_hours_conversations_count() : absint( $this->daily_achieved_limit );
		$this->daily_message_limit  = isset( $this->default_email_settings['bwfan_email_daily_limit'] ) ? absint( $this->default_email_settings['bwfan_email_daily_limit'] ) : 10000;

		/** Setting default 10k email daily limit */
		$this->daily_message_limit = empty( $this->daily_message_limit ) ? 10000 : $this->daily_message_limit;

		return ( $this->daily_achieved_limit >= $this->daily_message_limit );
	}

	/**
	 * Set delay in broadcast execution
	 *
	 * @param $campaign
	 * @param string $time
	 * @param string $in
	 */
	public function set_delay( $campaign, $time = '10', $in = 'minutes' ) {
		$execution_time = date( 'Y-m-d H:i:s', strtotime( "+$time $in", strtotime( $campaign['execution_time'] ) ) );
		BWFAN_Model_Broadcast::update( array(
			'execution_time' => $execution_time,
			'last_modified'  => current_time( 'mysql', 1 ),
		), array(
			'id' => $campaign['id'],
		) );
	}

	public function get_daily_limit_status_array() {
		if ( empty( $this->default_email_settings ) ) {
			$this->default_email_settings = BWFAN_Common::get_global_settings();
		}

		return array(
			'reached'     => $this->maybe_daily_limit_reached(),
			'daily_limit' => isset( $this->default_email_settings['bwfan_email_daily_limit'] ) ? absint( $this->default_email_settings['bwfan_email_daily_limit'] ) : 10000,
		);
	}

	public function get_per_call_time() {
		if ( defined( 'BWFCRM_BROADCAST_AS_CALL_SECONDS' ) ) {
			return absint( BWFCRM_BROADCAST_AS_CALL_SECONDS );
		}

		return apply_filters( 'bwfan_as_per_call_time', 30 );
	}

	public function is_time_exceeded() {
		return ( time() - $this->start_time ) >= $this->AS_CALL_TIME;
	}

	public function maybe_validate_twilio_settings() {
		$broadcast = $this->current_broadcast;
		$data      = isset( $broadcast['data'] ) && ! empty( $broadcast['data'] ) ? $broadcast['data'] : array();

		if ( ! BWFCRM_Core()->conversation->is_twilio_connected() ) {
			$data['halt_reason'] = __( 'Twilio Connector is not connected, during the schedule time of this campaign', 'wp-marketing-automations-pro' );
			BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
			self::action_schedule_save_last_sent( $broadcast['id'] );

			return false;
		}

		$sms_settings = BWFCRM_Core()->conversation->get_twilio_settings();
		if ( ! is_array( $sms_settings ) || empty( $sms_settings ) || ! isset( $sms_settings['twilio_no'] ) || ! isset( $sms_settings['account_sid'] ) || ! isset( $sms_settings['auth_token'] ) ) {
			$data['halt_reason'] = __( 'Twilio Connector\'s required details are not found. Please re-connect to Twilio connector again', 'wp-marketing-automations-pro' );
			BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
			self::action_schedule_save_last_sent( $broadcast['id'] );

			return false;
		}

		return true;
	}

	public function maybe_validate_whatsapp_settings() {
		$broadcast = $this->current_broadcast;
		$data      = isset( $broadcast['data'] ) && ! empty( $broadcast['data'] ) ? $broadcast['data'] : array();

		if ( ! BWFAN_Common::is_whatsapp_services_enabled() ) {
			$data['halt_reason'] = __( 'No WhatsApp service connected', 'wp-marketing-automations-pro' );
			BWFAN_Model_Broadcast::update_status_to_halt( $broadcast['id'], $data );
			self::action_schedule_save_last_sent( $broadcast['id'] );

			return false;
		}

		return true;
	}

	public function maybe_broadcast_running() {
		$broadcast = $this->current_broadcast;

		$last_modified = ! empty( $broadcast ) && isset( $broadcast['last_modified'] ) ? $broadcast['last_modified'] : false;
		if ( false !== $last_modified ) {
			$last_modified = time() - strtotime( $last_modified );
			if ( $last_modified <= 5 ) {
				return true;
			}
		}

		return false;
	}

	public function get_current_broadcast_data() {
		if ( ! is_array( $this->current_broadcast ) || ! isset( $this->current_broadcast['data'] ) ) {
			return array();
		}

		return $this->current_broadcast['data'];
	}

	public function validate_email_data_error( $email_data ) {
		if ( ! is_wp_error( $email_data ) ) {
			return false;
		}

		$e_data = $email_data->get_error_data();
		if ( is_array( $e_data ) && isset( $e_data['conversation_id'] ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( absint( $e_data['conversation_id'] ), $email_data->get_error_message() );
		}

		return true;
	}

	public function process_email( $to, $email_subject, $email_body, $headers ) {
		BWFCRM_Common::$captured_email_failed_message = null;

		return wp_mail( $to, $email_subject, $email_body, $headers );
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

		if ( false === apply_filters( 'bwfan_allow_broadcast_logging', BWFAN_PRO_Common::is_log_enabled( 'bwfan_broadcast_logging' ) ) ) {
			return;
		}
		add_filter( 'bwfan_before_making_logs', '__return_true' );
		BWFAN_Core()->logger->log( print_r( $this->logs, true ), 'fka-broadcast' );
		$this->logs = [];
	}

	/**
	 * Schedule action for save last sent
	 *
	 * @param $broadcast_id
	 * @param $delete_key
	 *
	 * @return void
	 */
	public static function action_schedule_save_last_sent( $broadcast_id, $delete_key = true ) {
		if ( empty( $broadcast_id ) ) {
			return;
		}

		if ( ! bwf_has_action_scheduled( 'bwfan_broadcast_last_sent', array( $broadcast_id, $delete_key ), 'bwfcrm' ) ) {
			bwf_schedule_recurring_action( time(), 60, 'bwfan_broadcast_last_sent', array( $broadcast_id, $delete_key ), 'bwfcrm' );
		}
	}
}
