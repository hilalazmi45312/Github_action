<?php
/**
 * Customer - Today Product Delivery With Slot.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Customer_Today_Product_Delivery_With_Slot_Notification' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 * */
	class DEY_Customer_Today_Product_Delivery_With_Slot_Notification extends DEY_Notifications {

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 * */
		public function __construct() {
			$this->id          = 'customer_today_product_delivery_with_slot';
			$this->type        = 'customer';
			$this->title       = __( 'Customer Delivery Notification - Product Delivery With Time Slots', 'delivery-slots-for-woocommerce' );
			$this->description = __( 'This email will be sent to customer on the day of delivery.', 'delivery-slots-for-woocommerce' );

			// Triggers for this email.
			add_action( sanitize_key( $this->plugin_slug . '_product_delivery_reminder_email' ), array( $this, 'trigger_automatic_email' ), 10, 1 );
			// Triggers for this manual email.
			add_action( sanitize_key( $this->plugin_slug . '_product_delivery_manual_email' ), array( $this, 'trigger' ), 10, 1 );
			// Render email shortcode information.
			add_action( 'woocommerce_admin_field_dey_display_email_shortcode_' . sanitize_title( $this->id ), array( $this, 'render_email_shortcode_information' ) );

			parent::__construct();
		}

		/**
		 * Get default subject.
		 *
		 * @since 1.0.0
		 * @return string
		 * */
		public function get_default_subject() {
			return 'Delivery Reminder for Product {product_name} on {site_name}';
		}

		/**
		 * Get default message.
		 *
		 * @since 1.0.0
		 * @return string
		 * */
		public function get_default_message() {
			return 'Hi,

			Product {product_name} associated Order {order_id} on {site_name} will be delivered today({delivery_date}) between {time_slot}.

			Thanks.';
		}

		/**
		 * Trigger the sending of automatic email.
		 *
		 * @since 2.6.0
		 * @param object $product_delivery Product delivery object.
		 * @return void
		 * */
		public function trigger_automatic_email( $product_delivery ) {
			// Is enabled?.
			if ( ! $this->is_enabled() ) {
				return;
			}

			return $this->trigger( $product_delivery );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @since 1.0.0
		 * @param object $product_delivery Product delivery object.
		 * @return void
		 */
		public function trigger( $product_delivery ) {
			if ( ! is_a( $product_delivery, 'DEY_Product_Delivery' ) ) {
				$product_delivery = dey_get_product_delivery( $product_delivery );
			}

			if ( ! $product_delivery->exists() || ! $product_delivery->is_time_slot_mode() ) {
				return;
			}

			$this->recipient                       = $product_delivery->get_user_email();
			$this->placeholders['{order_id}']      = $product_delivery->get_order_id();
			$this->placeholders['{delivery_date}'] = $product_delivery->get_formatted_delivery_date();
			$this->placeholders['{product_name}']  = $product_delivery->get_product_name();
			$this->placeholders['{time_slot}']     = $product_delivery->get_formatted_time_slots();
			$this->placeholders['{product_link}']  = $product_delivery->get_product_name( true );
			$this->placeholders['{customer_name}'] = $product_delivery->get_user_name();

			if ( $this->get_recipient() ) {
				if ( $this->send_email( $this->get_recipient(), $this->get_subject(), $this->get_formatted_message() ) ) {
					// Update the today reminder email.
					update_post_meta( $product_delivery->get_id(), 'dey_delivery_email_reminder_sent', 'yes' );
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
				'title' => __( 'Product Delivery With Time Slots', 'delivery-slots-for-woocommerce' ),
				'id'    => 'dey_today_product_delivery_with_slot_email_options',
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
				'id'   => 'dey_today_product_delivery_with_slot_email_options',
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
				'{delivery_date}' => array(
					'description' => __( 'Displays the Delivery Date', 'delivery-slots-for-woocommerce' ),
				),
				'{product_name}'  => array(
					'description' => __( 'Displays the Product Name', 'delivery-slots-for-woocommerce' ),
				),
				'{product_link}'  => array(
					'description' => __( 'Displays the Product Name as Linkable', 'delivery-slots-for-woocommerce' ),
				),
				'{customer_name}' => array(
					'description' => __( 'Displays the Customer Name', 'delivery-slots-for-woocommerce' ),
				),
				'{time_slot}'     => array(
					'description' => __( 'Displays the Time Slot', 'delivery-slots-for-woocommerce' ),
				),
			);

			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-email-shortcodes-info.php';
		}
	}

}
