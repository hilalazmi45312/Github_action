<?php

#[AllowDynamicProperties]
final class BWFAN_WC_Wishlist_User_Add_Product extends BWFAN_Event {
	private static $instance = null;
	public $wishlist = null;
	public $wishlist_id = null;
	public $cart_item_data = null;
	public $product_id = null;
	public $email = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_wishlist' );

		$this->event_name        = esc_html__( 'User Adds Product To Wishlist', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after a user adds product to wishlist.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups = array(
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
		add_action( 'woocommerce_wishlist_add_item', [ $this, 'add_item_to_wishlist' ], 20, 7 );

	}

	public function add_item_to_wishlist( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $wishlist_id ) {
		$this->process( $wishlist_id, $product_id, $cart_item_data );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $wishlist_id
	 * @param $product_id
	 * @param $cart_item_data
	 */
	public function process( $wishlist_id, $product_id, $cart_item_data ) {
		$data                   = $this->get_default_data();
		$data['wishlist_id']    = $wishlist_id;
		$data['cart_item_data'] = $cart_item_data;
		$data['product_id']     = $product_id;
		$data['email']          = get_post_meta( $wishlist_id, '_wishlist_email', true );

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
		$added_product   = [];
		$added_product[] = $this->product_id;
		BWFAN_Core()->rules->setRulesData( $this->wishlist_id, 'wishlist_id' );
		BWFAN_Core()->rules->setRulesData( $added_product, 'wishlist_items' );
		BWFAN_Core()->rules->setRulesData( $this->product_id, 'product_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->get_email_event(), $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_email_event() {

		return is_email( $this->email ) ? $this->email : null;
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
		$data_to_send['global']['wishlist_id']    = $this->wishlist_id;
		$data_to_send['global']['cart_item_data'] = $this->cart_item_data;
		$data_to_send['global']['email']          = $this->get_email_event();

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
	 * @return array|bool
	 */
	public function capture_async_data() {

		$wishlist_id          = BWFAN_Common::$events_async_data['wishlist_id'];
		$cart_item_data       = isset( BWFAN_Common::$events_async_data['cart_item_data'] ) ? BWFAN_Common::$events_async_data['cart_item_data'] : [];
		$product_id           = BWFAN_Common::$events_async_data['product_id'];
		$this->wishlist_id    = $wishlist_id;
		$this->wishlist       = new WC_Wishlists_Wishlist( $wishlist_id );
		$this->cart_item_data = $cart_item_data;
		$this->product_id     = $product_id;
		$this->email          = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$wishlist_id                       = BWFAN_Common::$events_async_data['wishlist_id'];
		$this->cart_item_data              = isset( BWFAN_Common::$events_async_data['cart_item_data'] ) ? BWFAN_Common::$events_async_data['cart_item_data'] : [];
		$product_id                        = BWFAN_Common::$events_async_data['product_id'];
		$this->wishlist_id                 = $wishlist_id;
		$this->wishlist                    = new WC_Wishlists_Wishlist( $wishlist_id );
		$this->product_id                  = $product_id;
		$this->email                       = BWFAN_Common::$events_async_data['email'];
		$automation_data['wishlist_id']    = $this->wishlist_id;
		$automation_data['cart_item_data'] = $this->cart_item_data;
		$automation_data['product_id']     = $this->product_id;
		$automation_data['wishlist_items'] = [ $this->product_id ];
		$automation_data['email']          = $this->email;

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
	return 'BWFAN_WC_Wishlist_User_Add_Product';
}
