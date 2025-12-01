<?php
/**
 * Location Admin - Product Pickup Without Slot.
 *
 * @since 3.5.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Admin_Product_Pickup_Without_Slot_Notification' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.5.0
	 * */
	class DEY_Admin_Product_Pickup_Without_Slot_Notification extends DEY_Notifications {

		/**
		 * Class constructor.
		 *
		 * @since 3.5.0
		 * */
		public function __construct() {
			$this->id          = 'admin_product_pickup_without_slot';
			$this->type        = 'admin';
			$this->title       = __( 'Location Admin Product Pickup Notification - Without Time Slots', 'delivery-slots-for-woocommerce' );
			$this->description = __( 'This email will be sent to customer on the day of pickup.', 'delivery-slots-for-woocommerce' );

			// Triggers for this email.
			add_action( sanitize_key( $this->plugin_slug . '_product_pickup_reminder_email' ), array( $this, 'trigger_automatic_email' ), 10, 1 );
			// Triggers for this manual email.
			add_action( sanitize_key( $this->plugin_slug . '_product_pickup_manual_email' ), array( $this, 'trigger' ), 10, 1 );
			// Render email shortcode information.
			add_action( 'woocommerce_admin_field_dey_display_email_shortcode_' . sanitize_title( $this->id ), array( $this, 'render_email_shortcode_information' ) );

			parent::__construct();
		}

		/**
		 * Get default subject.
		 *
		 * @since 3.5.0
		 * @return string
		 * */
		public function get_default_subject() {
			return 'Pickup Reminder for Product {product_name} on {site_name}';
		}

		/**
		 * Get default message.
		 *
		 * @since 3.5.0
		 * @return string
		 * */
		public function get_default_message() {
			return 'Hi,

			The Product {product_link} associated Order {order_id} on {site_name} should be pickup on({pickup_date}) by {customer_name}.
			
			Thanks.';
		}

		/**
		 * Trigger the sending of automatic email.
		 *
		 * @since 3.5.0
		 * @param object $product_pickup Product pickup object.
		 * @return void
		 * */
		public function trigger_automatic_email( $product_pickup ) {
			// Is enabled?.
			if ( ! $this->is_enabled() ) {
				return;
			}

			$this->trigger( $product_pickup );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @since 3.5.0
		 * @param object $product_pickup Product pickup object.
		 * @return void
		 */
		public function trigger( $product_pickup ) {
			if ( ! is_object( $product_pickup ) || ! is_a( $product_pickup, 'DEY_Product_Local_Pickup' ) ) {
				return;
			}

			if ( ! $product_pickup->exists() || $product_pickup->get_time_slot_id() ) {
				return;
			}

			$this->recipient                       = $this->get_location_admin_emails( $product_pickup );
			$this->placeholders['{order_id}']      = $product_pickup->get_order_id();
			$this->placeholders['{pickup_date}']   = $product_pickup->get_formatted_pickup_date();
			$this->placeholders['{product_name}']  = $product_pickup->get_product_name();
			$this->placeholders['{product_link}']  = $product_pickup->get_product_name( true );
			$this->placeholders['{customer_name}'] = $product_pickup->get_user_name();

			if ( $this->get_recipient() ) {
				$this->send_email( $this->get_recipient(), $this->get_subject(), $this->get_formatted_message() );
				// Update the today reminder email.
				update_post_meta( $product_pickup->get_id(), 'dey_pickup_email_reminder_sent', 'yes' );
			}
		}

		/**
		 * Get the settings array.
		 *
		 * @since 3.5.0
		 * @return array
		 * */
		public function get_settings_array() {
			$section_fields = array();

			// Email Section Start.
			$section_fields[] = array(
				'type'  => 'title',
				'title' => __( 'Product Pickup Without Time Slots', 'delivery-slots-for-woocommerce' ),
				'id'    => 'dey_product_pickup_without_slot_email_options',
			);
			$section_fields[] = array(
				'title'   => __( 'Enable', 'delivery-slots-for-woocommerce' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'id'      => $this->get_option_key( 'enabled' ),
			);
			$section_fields[] = array(
				'title'    => __( 'Location Admin Email(s)', 'delivery-slots-for-woocommerce' ),
				'type'     => 'textarea',
				'default'  => $this->get_from_address(),
				'id'       => $this->get_option_key( 'recipients' ),
				/* translators: %s: From address */
				'desc'     => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'delivery-slots-for-woocommerce' ), esc_attr( $this->get_from_address() ) ),
				'desc_tip' => true,
				'value'    => $this->get_admin_emails(),
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
				'id'   => 'dey_product_pickup_without_slot_email_options',
			);
			// Email Section End.

			return $section_fields;
		}

		/**
		 * Get location admin emails.
		 *
		 * @since 3.5.0
		 * @param bool $product_pickup Product Pickup object.
		 * @return string
		 */
		public function get_location_admin_emails( $product_pickup ) {
			return $product_pickup->get_pickup_location_admin_emails() . ',' . $this->get_admin_emails();
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
				'{product_name}'  => array(
					'description' => __( 'Displays the Product Name', 'delivery-slots-for-woocommerce' ),
				),
				'{product_link}'  => array(
					'description' => __( 'Displays the Product Name as Linkable', 'delivery-slots-for-woocommerce' ),
				),
				'{customer_name}' => array(
					'description' => __( 'Displays the Customer Name', 'delivery-slots-for-woocommerce' ),
				),
			);

			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-email-shortcodes-info.php';
		}
	}

}
