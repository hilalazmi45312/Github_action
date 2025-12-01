<?php

/**
 * Abstract Notifications Class.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Notifications' ) ) {

	/**
	 * DEY_Notifications Class.
	 * */
	abstract class DEY_Notifications {

		/**
		 * ID.
		 *
		 * @var string
		 * */
		protected $id;

		/**
		 * Enabled.
		 *
		 * @var boolean
		 * */
		protected $enabled;

		/**
		 * Subject.
		 *
		 * @var string
		 * */
		protected $subject = '';

		/**
		 * Recipient.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $recipient;

		/**
		 * Message.
		 *
		 * @var string
		 * */
		protected $message = '';

		/**
		 * Title.
		 *
		 * @var string
		 * */
		protected $title;

		/**
		 * Description.
		 *
		 * @var string
		 * */
		protected $description;

		/**
		 * Type.
		 *
		 * @var string
		 * */
		protected $type;

		/**
		 * Email Type.
		 *
		 * @var string
		 * */
		protected $email_type;

		/**
		 * Place holders.
		 *
		 * @var array
		 * */
		protected $placeholders = array();

		/**
		 * Show in table.
		 *
		 * @var bool
		 * */
		protected $show_in_table = true;

		/**
		 * Plugin slug.
		 *
		 * @var string
		 * */
		protected $plugin_slug = 'dey';

		/**
		 * Class Constructor.
		 * */
		public function __construct() {
			$this->enabled = $this->get_enabled();

			if ( empty( $this->placeholders ) ) {
				$this->placeholders = array(
					'{site_name}' => $this->get_blogname(),
				);
			}
		}

		/**
		 * Get id.
		 * */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Typw.
		 * */
		public function get_type() {
			return $this->type;
		}

		/**
		 * Get title.
		 * */
		public function get_title() {
			return $this->title;
		}

		/**
		 * Get description.
		 * */
		public function get_description() {
			return $this->description;
		}

		/**
		 * Show in table?.
		 * */
		public function show_in_table() {
			return $this->show_in_table;
		}

		/**
		 * Get the enabled.
		 *
		 * @return string
		 * */
		public function get_enabled() {
			return $this->get_option( 'enabled', 'no' );
		}

		/**
		 * Is enabled?.
		 *
		 * @return bool
		 * */
		public function is_enabled() {
			return 'yes' === $this->enabled;
		}

		/**
		 * Get the default subject.
		 *
		 * @return stirng
		 * */
		public function get_default_subject() {
			return '';
		}

		/**
		 * Get the default message.
		 *
		 * @return string
		 * */
		public function get_default_message() {
			return '';
		}

		/**
		 * Get the subject.
		 *
		 * @return string
		 * */
		public function get_subject() {
			return $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) );
		}

		/**
		 * Get the message.
		 *
		 * @return mixed
		 * */
		public function get_message() {
			return $this->format_string( $this->get_option( 'message', $this->get_default_message() ) );
		}

		/**
		 * Get the email headers.
		 *
		 * @return string
		 * */
		public function get_headers() {
			/**
			 * This hook is used to alter the email headers.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_headers', 'Content-Type: ' . $this->get_content_type() . "\r\n", $this );
		}

		/**
		 * Get the content type.
		 *
		 * @return string
		 * */
		public function get_content_type() {
			/**
			 * This hook is used to alter the email content type.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_content_type', 'text/html', $this );
		}

		/**
		 * Get the email type.
		 *
		 * @return string
		 * */
		public function get_email_type() {
			/**
			 * This hook is used to alter the email type.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_type', get_option( 'dey_notifications_email_template_type', 2 ), $this );
		}

		/**
		 * Get the WordPress blog name.
		 *
		 * @return string
		 * */
		public function get_blogname() {
			/**
			 * This hook is used to alter the blog name.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_blogname', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $this );
		}

		/**
		 * Get the valid recipients.
		 *
		 * @since 1.0.0
		 * @return string
		 * */
		public function get_recipient() {
			$recipients = array_filter( array_map( 'trim', explode( ',', $this->recipient ) ), 'is_email' );

			/**
			 * This hook is used to alter the email recipient.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'dey_email_recipient', implode( ', ', $recipients ), $this );
		}

		/**
		 * Get the formatted message.
		 *
		 * @return HTML
		 * */
		public function get_formatted_message() {
			// Initiate the WC Emails.
			WC()->mailer();

			$message = $this->rtl_support( wpautop( $this->get_message() ) );
			$message = $this->email_inline_style( $message );

			if ( '2' == $this->get_email_type() ) {
				ob_start();
				wc_get_template( 'emails/email-header.php', array( 'email_heading' => $this->get_subject() ) );
				echo esc_textarea( $message );
				wc_get_template( 'emails/email-footer.php' );
				$message = ob_get_clean();
			}

			/**
			 * This hook is used to alter the email formatted message.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_formatted_message', htmlspecialchars_decode( $message ), $this );
		}

		/**
		 * Support RTL.
		 *
		 * @return string
		 */
		public function rtl_support( $message ) {
			$direction = ( is_rtl() ) ? 'rtl' : 'ltr';

			$formatted_msg  = '<div class="dey-notifications-wrapper" dir="' . $direction . '">';
			$formatted_msg .= $message;
			$formatted_msg .= '</div>';

			return $formatted_msg;
		}

		/**
		 * Add the inline style in email.
		 *
		 * @return HTML
		 * */
		public function email_inline_style( $content ) {
			if ( ! $this->custom_css() || ! $content ) {
				return $content;
			}

			return dey_add_html_inline_style( $content, $this->custom_css(), true );
		}

		/**
		 * Get the attachments.
		 *
		 * @return array
		 * */
		public function get_attachments() {
			/**
			 * This hook is used to alter the email attachments.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_attachments', array(), $this );
		}

		/**
		 * Format the string.
		 *
		 * @since 1.0.0
		 * @param string $string The string to be formatted.
		 * @return string
		 * */
		public function format_string( $string ) {
			return str_replace( array_keys( $this->placeholders ), array_values( $this->placeholders ), $string );
		}

		/**
		 * Custom CSS.
		 *
		 * @return string
		 * */
		public function custom_css() {
			return '';
		}

		/**
		 * Send an email.
		 *
		 * @return bool
		 * */
		public function send_email( $to, $subject, $message, $headers = false, $attachments = array() ) {
			if ( ! $headers ) {
				$headers = $this->get_headers();
			}

			add_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 12 );
			add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 12 );
			add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 12 );

			if ( '2' == $this->get_email_type() ) {
				$mailer = WC()->mailer();
				$return = $mailer->send( $to, $subject, $message, $headers, $attachments );
			} else {
				$return = wp_mail( $to, $subject, $message, $headers, $attachments );
			}

			remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
			remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
			remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

			return $return;
		}

		/**
		 * Get the from name.
		 *
		 * @return string
		 * */
		public function get_from_name() {
			$from_name = get_option( 'dey_notifications_email_from_name' );
			if ( empty( $from_name ) ) {
				$from_name = $this->get_blogname();
			}

			/**
			 * This hook is used to alter the email from name.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_from_name', wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) );
		}

		/**
		 * Get the from address.
		 *
		 * @return string
		 * */
		public function get_from_address() {
			$from_address = get_option( 'dey_notifications_email_from_address' );
			if ( empty( $from_address ) ) {
				$from_address = get_option( 'new_admin_email' );
			}

			/**
			 * This hook is used to alter the email from address.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_email_from_address', sanitize_email( $from_address ), $this );
		}

		/**
		 * Get the admin emails.
		 *
		 * @return string
		 * */
		public function get_admin_emails() {
			$admin_emails = $this->get_option( 'recipients' );
			if ( empty( $admin_emails ) ) {
				$admin_emails = $this->get_from_address();
			}

			/**
			 * This hook is used to alter the admin emails.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_admin_emails', $admin_emails, $this );
		}

		/**
		 * Get the option.
		 *
		 * @return mixed
		 * */
		public function get_option( $key, $value = false ) {
			return get_option( $this->get_option_key( $key ), $value );
		}

		/**
		 * Get the option key.
		 *
		 * @return string
		 * */
		public function get_option_key( $key ) {
			return sanitize_key( $this->plugin_slug . '_' . $this->id . '_' . $key );
		}

		/**
		 * Get the settings array.
		 *
		 * @return array
		 * */
		public function get_settings_array() {
			return array();
		}

		/**
		 * Output the settings.
		 * */
		public function output() {
			WC_Admin_Settings::output_fields( $this->get_settings_array() );
		}

		/**
		 * Save the settings.
		 * */
		public function save() {
			WC_Admin_Settings::save_fields( $this->get_settings_array() );
		}

		/**
		 * Reset the settings.
		 * */
		public function reset() {
			DEY_Settings::reset_fields( $this->get_settings_array() );
		}
	}

}
