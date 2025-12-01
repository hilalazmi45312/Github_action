<?php
/**
 * Customer - Today Order Pickup Without Slot.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Customer_Today_Order_Pickup_Without_Slot_Notification' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 * */
	class DEY_Customer_Today_Order_Pickup_Without_Slot_Notification extends DEY_Notifications {

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 * */
		public function __construct() {
			$this->id          = 'customer_today_order_pickup_without_slot';
			$this->type        = 'customer';
			$this->title       = __( 'Customer Pickup Notification - Order Pickup Without Time Slots', 'delivery-slots-for-woocommerce' );
			$this->description = __( 'This email will be sent to customer on the day of pickup.', 'delivery-slots-for-woocommerce' );

			// Triggers for this email.
			add_action( sanitize_key( $this->plugin_slug . '_order_local_pickup_reminder_email' ), array( $this, 'trigger_automatic_email' ), 10, 1 );
			// Triggers for this manual email.
			add_action( sanitize_key( $this->plugin_slug . '_order_local_pickup_manual_email' ), array( $this, 'trigger' ), 10, 1 );
			// Render email shortcode information.
			add_action( 'woocommerce_admin_field_dey_display_email_shortcode_' . sanitize_title( $this->id ), array( $this, 'render_email_shortcode_information' ) );

			parent::__construct();
		}

		/**
		 * Get default subject.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return 'Pickup Reminder for your Order {order_id} on {site_name}';
		}

		/**
		 * Get default message.
		 *
		 * @since 1.0.0
		 * @return string
		 * */
		public function get_default_message() {
			return 'Hi,

			Your order {order_id} on {site_name} should be pickup today({pickup_date}).

			Thanks.';
		}

		/**
		 * Trigger the sending of automatic email.
		 *
		 * @since 2.6.0
		 * @param object $order_local_pickup Order local pickup object.
		 * @return void
		 * */
		public function trigger_automatic_email( $order_local_pickup ) {
			// Is enabled?.
			if ( ! $this->is_enabled() ) {
				return;
			}

			return $this->trigger( $order_local_pickup );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @since 1.0.0
		 * @param object $order_local_pickup Order local pickup object.
		 * @return void
		 */
		public function trigger( $order_local_pickup ) {
			if ( ! is_a( $order_local_pickup, 'DEY_Order_Local_Pickup' ) ) {
				$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup );
			}

			if ( ! $order_local_pickup->exists() || $order_local_pickup->is_time_slot_mode() ) {
				return;
			}

			$this->recipient                     = $order_local_pickup->get_user_email();
			$this->placeholders['{order_id}']    = $order_local_pickup->get_order_id();
			$this->placeholders['{pickup_date}'] = $order_local_pickup->get_formatted_pickup_date();

			if ( $this->get_recipient() ) {
				if ( $this->send_email( $this->get_recipient(), $this->get_subject(), $this->get_formatted_message() ) ) {
					// Update the today reminder email.
					update_post_meta( $order_local_pickup->get_id(), 'dey_pickup_email_reminder_sent', 'yes' );
				}
			}
		}

		/**
		 * Get the settings array.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function get_settings_array() {
			$section_fields = array();

			// Email Section Start.
			$section_fields[] = array(
				'type'  => 'title',
				'title' => __( 'Order Pickup Without Time Slots', 'delivery-slots-for-woocommerce' ),
				'id'    => 'dey_today_order_pickup_without_slot_email_options',
			);
			$section_fields[] = array(
				'title'   => __( 'Enable', 'delivery-slots-for-woocommerce' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'id'      => $this->get_option_key( 'enabled' ),
			);
			$section_fields[] = array(
				'title'   => __( 'Subject', 'delivery-slots-for-woocommerce' ),
				'type'    => 'text',
				'default' => $this->get_default_subject(),
				'id'      => $this->get_option_key( 'subject' ),
			);
			$section_fields[] = array(
				'title'     => __( 'Message', 'delivery-slots-for-woocommerce' ),
				'type'      => 'dey_custom_fields',
				'dey_field' => 'wpeditor',
				'default'   => $this->get_default_message(),
				'id'        => $this->get_option_key( 'message' ),
			);
			$section_fields[] = array(
				'type' => 'dey_display_email_shortcode_' . $this->id,
			);
			$section_fields[] = array(
				'type' => 'sectionend',
				'id'   => 'dey_today_order_pickup_without_slot_email_options',
			);
			// Email Section End.

			return $section_fields;
		}

		/**
		 * Render email shortcode information.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public function render_email_shortcode_information() {
			$shortcodes_info = array(
				'{site_name}'     => array(
					'description' => __( 'Displays the site name', 'delivery-slots-for-woocommerce' ),
				),
				'{order_id}'      => array(
					'description' => __( 'Displays the Order ID', 'delivery-slots-for-woocommerce' ),
				),
				'{pickup_date}'   => array(
					'description' => __( 'Displays the Pickup Date', 'delivery-slots-for-woocommerce' ),
				),
				'{customer_name}' => array(
					'description' => __( 'Displays the Customer Name', 'delivery-slots-for-woocommerce' ),
				),
			);

			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-email-shortcodes-info.php';
		}
	}

}
