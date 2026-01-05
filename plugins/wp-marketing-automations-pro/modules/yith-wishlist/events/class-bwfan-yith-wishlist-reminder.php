<?php

#[AllowDynamicProperties]
final class BWFAN_Yith_Wishlist_Reminder extends BWFAN_Event {
	private static $instance = null;
	public $wishlist_id = null;
	public $user_id = null;
	public $wishlist_item = array();
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'yith_wishlist' );
		$this->event_name             = esc_html__( 'Wishlist Reminder', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs for wishlist reminder.', 'wp-marketing-automations-pro' );
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
		$this->optgroup_label         = esc_html__( 'Yith Wishlist', 'wp-marketing-automations-pro' );
		$this->support_lang           = true;
		$this->priority               = 25.3;
		$this->is_time_independent    = true;
		$this->v2                     = true;
		$this->v1                     = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * This is a time independent event. A cron is run once a day and it makes all the tasks for the current event.
	 *
	 * @param $automation_id
	 * @param $automation_data
	 *
	 * @throws Exception
	 */
	public function make_task_data( $automation_id, $automation_data ) {
		$date_time   = new DateTime();
		$current_day = $date_time->format( 'Y-m-d' );
		$last_run    = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'last_run' );

		if ( false !== $last_run ) {
			$where = [
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
			];
			$data  = [
				'meta_value' => $current_day,
			];

			BWFAN_Model_Automationmeta::update( $data, $where );
		} else {
			$meta = [
				'bwfan_automation_id' => $automation_id,
				'meta_key'            => 'last_run',
				'meta_value'          => $current_day,
			];

			BWFAN_Model_Automationmeta::insert( $meta );
		}

		global $wpdb;
		$query   = "SELECT lists.id, lists.user_id, GROUP_CONCAT(wl.prod_id) as wishlist_items FROM {$wpdb->prefix}yith_wcwl_lists AS lists JOIN {$wpdb->prefix}yith_wcwl AS wl ON lists.ID = wl.wishlist_id GROUP BY lists.id";
		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $results ) ) {
			return;
		}

		foreach ( $results as $row ) {
			$wishlist_id    = $row['id'];
			$wishlist_items = explode( ',', $row['wishlist_items'] );
			if ( empty( $wishlist_items ) || empty( $row['user_id'] ) ) {
				continue;
			}

			$user_data = get_userdata( $row['user_id'] );
			$email     = $user_data instanceof WP_User ? $user_data->user_email : '';
			if ( empty( $email ) ) {
				continue;
			}

			$this->capture_wishlist( $wishlist_id, $email, $row['user_id'], $wishlist_items );
		}
	}

	public function capture_wishlist( $wishlist_id, $email, $user_id, $wishlist_items ) {
		$this->wishlist_id   = $wishlist_id;
		$this->wishlist_item = $wishlist_items;
		$this->user_id       = $user_id;
		$this->email         = $email;

		$data                  = $this->get_default_data();
		$data['user_id']       = $this->user_id;
		$data['wishlist_id']   = $this->wishlist_id;
		$data['wishlist_item'] = $this->wishlist_item;
		$data['email']         = $this->email;
		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send = [ 'global' => [] ];

		$data_to_send['global']['wishlist_id']   = $this->wishlist_id;
		$data_to_send['global']['email']         = $this->email;
		$data_to_send['global']['user_id']       = $this->user_id;
		$data_to_send['global']['wishlist_item'] = $this->wishlist_item;

		return $data_to_send;
	}

	public function capture_v2_data( $automation_data ) {
		$wishlist_id   = BWFAN_Common::$events_async_data['wishlist_id'];
		$email         = BWFAN_Common::$events_async_data['email'];
		$user_id       = BWFAN_Common::$events_async_data['user_id'];
		$wishlist_item = BWFAN_Common::$events_async_data['wishlist_item'];

		$this->wishlist_id   = $wishlist_id;
		$this->user_id       = $user_id;
		$this->email         = $email;
		$this->wishlist_item = $wishlist_item;

		$automation_data['wishlist_id']   = $this->wishlist_id;
		$automation_data['user_id']       = $this->user_id;
		$automation_data['email']         = $this->email;
		$automation_data['wishlist_item'] = $this->wishlist_item;

		return $automation_data;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['wishlist_id'] ) ) ) {
			$set_data = array(
				'wishlist_id' => intval( $task_meta['global']['wishlist_id'] ),
				'email'       => $task_meta['global']['email'],
				'user_id'     => $task_meta['global']['user_id']
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'scheduled-everyday-at',
				'type'        => 'expression',
				'expression'  => " {{hours/}} {{minutes /}}",
				'label'       => 'Schedule this automation to run everyday at',
				'fields'      => [
					[
						"id"          => 'hours',
						"label"       => '',
						"type"        => 'number',
						"max"         => '23',
						"min"         => '0',
						"class"       => 'bwfan-input-wrapper bwfan-input-s',
						"placeholder" => "HH",
						"description" => "",
						"required"    => false,
					],
					[
						"id"          => 'minutes',
						"label"       => '',
						"type"        => 'number',
						"max"         => '59',
						"min"         => '0',
						"class"       => 'bwfan-input-wrapper bwfan-input-s',
						"placeholder" => "MM",
						"description" => "",
						"required"    => false,
					]
				],
				"description" => ""
			],
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_yith_wishlist_active' ) && bwfan_is_yith_wishlist_active() ) {
	return 'BWFAN_Yith_Wishlist_Reminder';
}
