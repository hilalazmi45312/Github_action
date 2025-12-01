<?php

/**
 * Notification Tab.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Notification_Tab' ) ) {
	return new DEY_Notification_Tab();
}

/**
 * Class.
 * */
class DEY_Notification_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 * */
	public function __construct() {
		$this->id    = 'notifications';
		$this->label = __( 'Notifications', 'delivery-slots-for-woocommerce' );

		// Save the notification settings.
		add_action( sanitize_key( $this->plugin_slug . '_after_' . $this->id . '_settings_saved' ), array( $this, 'save_notification_settings' ) );
		// Reset the notification settings.
		add_action( sanitize_key( $this->plugin_slug . '_after_' . $this->id . '_settings_reset' ), array( $this, 'reset_notification_settings' ) );

		parent::__construct();
	}

	/**
	 * Get settings for notifications section array.
	 *
	 * @return array
	 * */
	public function notifications_section_array() {
		global $current_section;

		$section_fields = array();
		if ( 'notifications' !== $current_section ) {
			return $section_fields;
		}

		// Email settings section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Email Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_email_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Email Type', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'email_template_type' ),
			'type'    => 'select',
			'default' => '2',
			'options' => array(
				'1' => __( 'HTML', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'WooCommerce Template', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => __( 'From Name', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'email_from_name' ),
			'type'    => 'text',
			'default' => get_option( 'woocommerce_email_from_name' ),
		);
		$section_fields[] = array(
			'title'   => __( 'From Address', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'email_from_address' ),
			'type'    => 'text',
			'default' => get_option( 'woocommerce_email_from_address' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_email_options',
		);
		// Email settings section end.

		return $section_fields;
	}

	/**
	 * Output the notifications.
	 * */
	public function output() {
		global $current_section;

		if ( ! $current_section ) {
			return;
		}

		if ( 'notifications' === $current_section ) {
			include_once DEY_ABSPATH . 'inc/admin/menu/views/html-email-notifications.php';
			parent::output();
		} else {
			$notification = DEY_Notification_Instances::get_notification_by_id( $current_section );
			if ( ! is_object( $notification ) ) {
				return;
			}

			$notification->output();
		}
	}

	/**
	 * Save the notification settings.
	 */
	public function save_notification_settings() {
		global $current_section;

		if ( 'notifications' !== $current_section ) {
			$notification = DEY_Notification_Instances::get_notification_by_id( $current_section );
			if ( ! $notification ) {
				return;
			}

			$notification->save();
		} else {
			$notifications = DEY()->notifications();
			foreach ( $notifications as $notification ) {
				// Enable/ Disable the Notifications.
				$value = isset( $_REQUEST[ $notification->get_option_key( 'enabled' ) ] ) ? 'yes' : 'no';

				update_option( $notification->get_option_key( 'enabled' ), $value );
			}

			DEY_Notification_Instances::reset();
		}
	}

	/**
	 * Reset the notifications settings.
	 */
	public function reset_notification_settings() {
		global $current_section;

		if ( 'notifications' !== $current_section ) {
			$notification = DEY_Notification_Instances::get_notification_by_id( $current_section );
			if ( ! $notification ) {
				return;
			}

			$notification->reset();
		} else {
			$notifications = DEY()->notifications();
			foreach ( $notifications as $notification ) {
				if ( $notification->is_enabled() ) {
					update_option( $notification->get_option_key( 'enabled' ), '' );
				}
			}

			DEY_Notification_Instances::reset();
		}
	}
}

return new DEY_Notification_Tab();
