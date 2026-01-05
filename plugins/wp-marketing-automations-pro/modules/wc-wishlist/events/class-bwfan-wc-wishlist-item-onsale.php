<?php

#[AllowDynamicProperties]
final class BWFAN_WC_Wishlist_Item_Onsale extends BWFAN_Event {
	private static $instance = null;
	public $wishlist = null;
	public $wishlist_ids = null;
	public $wishlist_items_on_sale = null;
	public $wishlist_items = null;
	public $item_on_sale_obj = null;
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_wishlist' );

		$this->event_name        = esc_html__( 'Wishlist Item on Sale', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after a wishlist item goes on sale.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups = array(
			'wc_customer',
			'wishlist_items',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label    = esc_html__( 'WooCommerce Wishlist', 'wp-marketing-automations-pro' );
		$this->support_lang      = true;
		$this->priority          = 25;
		$this->v2                = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		if ( ! bwf_has_action_scheduled( 'bwfan_wishlist_on_item_sale' ) ) {
			bwf_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 30, 'bwfan_wishlist_on_item_sale', null, 'autonami' );
		}
		add_action( 'bwfan_wishlist_on_item_sale', [ $this, 'wishlist_itemonsale' ], 10 );
	}

	public function wishlist_itemonsale() {
		$old_product_on_sale = get_option( 'bwfan_products_on_sale', [] );
		$new_product_on_sale = wc_get_product_ids_on_sale();
		update_option( 'bwfan_products_on_sale', $new_product_on_sale, false );
		$new_product_ids_onsale = array_diff( $new_product_on_sale, $old_product_on_sale );
		if ( empty( $new_product_ids_onsale ) ) {
			return;
		}

		$query = new WP_Query( [
			'post_type'      => 'wishlist',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		] );

		if ( ! is_array( $query->posts ) || count( $query->posts ) === 0 ) {
			return;
		}
		/**
		 * @todo save the ids in the option table and add new code
		 */
		foreach ( $query->posts as $wishlist_id ) {
			$wishlist_items = get_post_meta( $wishlist_id, '_wishlist_items', true );
			if ( empty( $wishlist_items ) ) {
				continue;
			}
			$wishlist_items_on_sale = [];
			foreach ( $wishlist_items as $data ) {
				$wishlist_item_id = empty( $data['variation_id'] ) ? $data['product_id'] : $data['variation_id'];

				if ( in_array( $wishlist_item_id, $new_product_ids_onsale ) ) {
					$wishlist_items_on_sale[] = $wishlist_item_id;
				}
			}

			if ( ! empty( $wishlist_items_on_sale ) ) {
				$this->process( $wishlist_id, $wishlist_items_on_sale, $wishlist_items );
			}
		}
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $wishlist_id
	 * @param $wishlist_items_on_sale
	 * @param $wishlist_items
	 */
	public function process( $wishlist_id, $wishlist_items_on_sale, $wishlist_items ) {
		$data                           = $this->get_default_data();
		$data['wishlist_id']            = $wishlist_id;
		$data['wishlist_items_on_sale'] = $wishlist_items_on_sale;
		$data['wishlist_items']         = $wishlist_items;
		$data['email']                  = get_post_meta( $wishlist_id, '_wishlist_email', true );

		if ( empty( $data['email'] ) ) {
			return;
		}

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->wishlist_ids, 'wishlist_id' );
		BWFAN_Core()->rules->setRulesData( $this->wishlist, 'wishlist' );
		BWFAN_Core()->rules->setRulesData( $this->wishlist_items_on_sale, 'wishlist_items' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->get_email_event(), $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_email_event() {
		if ( $this->wishlist instanceof WC_Wishlists_Wishlist ) {
			return get_post_meta( $this->wishlist_ids, '_wishlist_email', true );
		}

		return false;
	}

	public function get_user_id_event() {
		if ( $this->wishlist instanceof WC_Wishlists_Wishlist ) {
			return $this->wishlist->get_wishlist_owner();
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
		$data_to_send                             = [ 'global' => [] ];
		$data_to_send['global']['wishlist_id']    = $this->wishlist_ids;
		$data_to_send['global']['wishlist_items'] = $this->wishlist_items;
		$data_to_send['global']['items_on_sale']  = $this->wishlist_items_on_sale;
		$data_to_send['global']['email']          = $this->email;

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
            <strong><?php echo esc_html__( 'Wishlist ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wishlist_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wishlist_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );

		$set_data = array(
			'wishlist_id' => $task_meta['global']['wishlist_id'],
			'email'       => $task_meta['global']['email'],
		);
		BWFAN_Merge_Tag_Loader::set_data( $set_data );

	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {

		$this->wishlist_ids           = BWFAN_Common::$events_async_data['wishlist_id'];
		$wishlist_items_on_sale       = BWFAN_Common::$events_async_data['wishlist_items_on_sale'];
		$this->wishlist_items         = BWFAN_Common::$events_async_data['wishlist_items'];
		$this->wishlist               = new WC_Wishlists_Wishlist( $this->wishlist_ids );
		$this->wishlist_items_on_sale = $wishlist_items_on_sale;
		$this->item_on_sale_obj       = $this;
		$this->email                  = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->wishlist_ids                        = BWFAN_Common::$events_async_data['wishlist_id'];
		$wishlist_items_on_sale                    = BWFAN_Common::$events_async_data['wishlist_items_on_sale'];
		$this->wishlist_items                      = BWFAN_Common::$events_async_data['wishlist_items'];
		$this->wishlist                            = new WC_Wishlists_Wishlist( $this->wishlist_ids );
		$this->wishlist_items_on_sale              = $wishlist_items_on_sale;
		$this->item_on_sale_obj                    = $this;
		$this->email                               = BWFAN_Common::$events_async_data['email'];
		$automation_data['wishlist_id']            = $this->wishlist_ids;
		$automation_data['wishlist_items']         = $this->wishlist_items;
		$automation_data['wishlist']               = $this->wishlist;
		$automation_data['wishlist_items_on_sale'] = $this->wishlist_items_on_sale;
		$automation_data['item_on_sale_obj']       = $this->item_on_sale_obj;
		$automation_data['email']                  = $this->email;

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
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_wc_wishlist_active' ) && bwfan_is_wc_wishlist_active() ) {
	return 'BWFAN_WC_Wishlist_Item_Onsale';
}
