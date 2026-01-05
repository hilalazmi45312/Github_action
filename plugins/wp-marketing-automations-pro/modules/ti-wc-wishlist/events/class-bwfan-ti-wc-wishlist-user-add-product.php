<?php

#[AllowDynamicProperties]
final class BWFAN_Ti_Wc_Wishlist_User_Add_Product extends BWFAN_Event {
	private static $instance = null;
	public $user_id = null;
	public $wishlist_id = null;
	public $product_id = null;
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'ti_wc_wishlist' );

		$this->event_name        = esc_html__( 'User Adds Product To Wishlist', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after a user adds product to wishlist.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups = array(
			'ti_wc_wishlist',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label    = esc_html__( 'TI WooCommerce Wishlist', 'wp-marketing-automations-pro' );
		$this->support_lang      = true;
		$this->priority          = 25;
		$this->optgroup_priority = 100;
		$this->v2                = true;
		$this->support_v1        = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'tinvwl_product_added', [ $this, 'process' ], 20, 1 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $product_id
	 * @param $wishlist_id
	 * @param $user_id
	 *
	 * @return void
	 */
	public function process( $data ) {
		$email       = '';
		$wishlist_id = isset( $data['wishlist_id'] ) ? $data['wishlist_id'] : '';
		$product_id  = isset( $data['product_id'] ) ? $data['product_id'] : '';
		$user_id     = isset( $data['author'] ) ? $data['author'] : '';
		if ( ! empty( $user_id ) && intval( $user_id ) > 0 ) {
			$user_data = get_userdata( $user_id );
			if ( $user_data instanceof WP_User ) {
				$email = $user_data->user_email;
			}
		}


		/** If user is not logged in then get email from session */
		if ( empty( $email ) ) {
			$session_data = WC()->session->get_session_data();
			$session      = isset( $session_data['customer'] ) ? maybe_unserialize( $session_data['customer'] ) : [];
			$email        = ! empty( $session['email'] ) ? $session['email'] : '';
		}
		if ( empty( $email ) ) {
			return;
		}

		$data                = $this->get_default_data();
		$data['user_id']     = $user_id;
		$data['email']       = $email;
		$data['wishlist_id'] = $wishlist_id;
		$data['product_id']  = $product_id;
		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['wishlist_id'] = $this->wishlist_id;
		$data_to_send['global']['product_id']  = $this->product_id;
		$data_to_send['global']['email']       = $this->email;
		$data_to_send['global']['user_id']     = $this->user_id;

		return $data_to_send;
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
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->email       = BWFAN_Common::$events_async_data['email'];
		$this->user_id     = BWFAN_Common::$events_async_data['user_id'];
		$this->wishlist_id = BWFAN_Common::$events_async_data['wishlist_id'];
		$this->product_id  = BWFAN_Common::$events_async_data['product_id'];

		$automation_data['email']       = $this->email;
		$automation_data['user_id']     = $this->user_id;
		$automation_data['wishlist_id'] = $this->wishlist_id;
		$automation_data['product_id']  = $this->product_id;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_ti_wc_wishlist_active' ) && bwfan_is_ti_wc_wishlist_active() ) {
	return 'BWFAN_Ti_Wc_Wishlist_User_Add_Product';
}
