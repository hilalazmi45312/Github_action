<?php

#[AllowDynamicProperties]
final class BWFAN_Ti_Wc_Wishlist_Item_Onsale extends BWFAN_Event {
	private static $instance = null;
	public $wishlist_ids = null;
	public $wishlist_items_on_sale = null;
	public $wishlist_items = null;
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'ti_wc_wishlist' );

		$this->event_name        = esc_html__( 'Wishlist Item on Sale', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after a wishlist item goes on sale.', 'wp-marketing-automations-pro' );
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
		$this->v2                = true;
		$this->v1                = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		if ( ! bwf_has_action_scheduled( 'bwfan_ti_wc_wishlist_on_item_sale' ) ) {
			bwf_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 30, 'bwfan_ti_wc_wishlist_on_item_sale', null, 'autonami' );
		}
		add_action( 'bwfan_ti_wc_wishlist_on_item_sale', [ $this, 'capture_wishlist_data' ], 10 );
	}

	public function capture_wishlist_data() {
		$old_product_on_sale = bwf_options_get( 'bwfan_products_on_sale' );
		$new_product_on_sale = wc_get_product_ids_on_sale();
		bwf_options_update( 'bwfan_products_on_sale', $new_product_on_sale );

		$new_product_ids_on_sale = array_diff( $new_product_on_sale, $old_product_on_sale );
		if ( empty( $new_product_ids_on_sale ) ) {
			return;
		}

		global $wpdb;

		$query   = "SELECT lists.ID, lists.author AS user_id, GROUP_CONCAT(items.product_id) AS wishlist_items 
					FROM {$wpdb->prefix}tinvwl_lists AS lists 
					JOIN {$wpdb->prefix}tinvwl_items AS items ON lists.ID = items.wishlist_id 
					GROUP BY lists.ID";
		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $results ) ) {
			return;
		}

		foreach ( $results as $row ) {
			$wishlist_id    = $row['ID'];
			$user_id        = $row['user_id'];
			$user_data      = get_userdata( $user_id );
			$email          = $user_data instanceof WP_User ? $user_data->user_email : '';
			$wishlist_items = ! empty( $row['wishlist_items'] ) ? explode( ",", $row['wishlist_items'] ) : [];
			if ( empty( $email ) || empty( $wishlist_items ) ) {
				continue;
			}

			/** Get wishlist item on sale */
			$wishlist_items_on_sale = [];
			foreach ( $wishlist_items as $wishlist_item_id ) {
				if ( in_array( $wishlist_item_id, $new_product_ids_on_sale ) ) {
					$wishlist_items_on_sale[] = $wishlist_item_id;
				}
			}

			if ( empty( $wishlist_items_on_sale ) ) {
				continue;
			}

			$this->process( $wishlist_id, $wishlist_items_on_sale, $wishlist_items, $email );
		}
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $wishlist_id
	 * @param $wishlist_items_on_sale
	 * @param $wishlist_items
	 */
	public function process( $wishlist_id, $wishlist_items_on_sale, $wishlist_items, $email ) {
		if ( empty( $email ) ) {
			return;
		}

		$data                           = $this->get_default_data();
		$data['wishlist_id']            = $wishlist_id;
		$data['wishlist_items_on_sale'] = $wishlist_items_on_sale;
		$data['wishlist_items']         = $wishlist_items;
		$data['email']                  = $email;
		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send = [ 'global' => [] ];

		$data_to_send['global']['wishlist_id']    = $this->wishlist_ids;
		$data_to_send['global']['wishlist_items'] = $this->wishlist_items;
		$data_to_send['global']['items_on_sale']  = $this->wishlist_items_on_sale;
		$data_to_send['global']['email']          = $this->email;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$set_data = array(
			'wishlist_id' => $task_meta['global']['wishlist_id'],
			'email'       => $task_meta['global']['email'],
		);
		BWFAN_Merge_Tag_Loader::set_data( $set_data );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->email                  = BWFAN_Common::$events_async_data['email'];
		$this->wishlist_ids           = BWFAN_Common::$events_async_data['wishlist_id'];
		$this->wishlist_items         = BWFAN_Common::$events_async_data['wishlist_items'];
		$this->wishlist_items_on_sale = BWFAN_Common::$events_async_data['wishlist_items_on_sale'];

		$automation_data['email']                  = $this->email;
		$automation_data['wishlist_id']            = $this->wishlist_ids;
		$automation_data['wishlist_items']         = $this->wishlist_items;
		$automation_data['wishlist_items_on_sale'] = $this->wishlist_items_on_sale;

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
	return 'BWFAN_Ti_Wc_Wishlist_Item_Onsale';
}
