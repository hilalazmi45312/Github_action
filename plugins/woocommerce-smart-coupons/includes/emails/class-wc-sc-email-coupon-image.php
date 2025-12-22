<?php
/**
 * Main class for Smart Coupons Email
 *
 * @author      StoreApps
 * @since       9.55.0
 * @version     1.1.0
 *
 * @package     woocommerce-smart-coupons/includes/emails/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Email_Coupon_Image' ) ) {
	/**
	 * The Smart Coupons Gift Email class
	 *
	 * @extends \WC_SC_Email
	 */
	class WC_SC_Email_Coupon_Image extends WC_SC_Email {

		/**
		 * Set email defaults
		 */
		public function __construct() {
			global $store_credit_label;

			$credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			$this->id = 'wc_sc_email_coupon_image';

			$this->customer_email = true;

			// Set email title and description.
			/* translators: Label for store credit */
			$this->title = sprintf( __( 'Smart Coupons - %s email with image', 'woocommerce-smart-coupons' ), $credit_label );
			/* translators: Label for store credit */
			$this->description = sprintf( __( 'Send %s email including image uploaded by the purchaser.', 'woocommerce-smart-coupons' ), $credit_label );

			// Use our plugin templates directory as the template base.
			$this->template_base = dirname( WC_SC_PLUGIN_FILE ) . '/templates/';

			// Email template location.
			$this->template_html  = 'gift-card-email.php';
			$this->template_plain = 'plain/gift-card-email.php';

			$this->placeholders = array( '{sender_name}' => '' );

			// Trigger for this email.
			add_action( 'wc_sc_email_coupon_image_notification', array( $this, 'trigger' ) );

			// Call parent constructor to load any other defaults not explicity defined here.
			parent::__construct();
		}

		/**
		 * Get default email subject.
		 *
		 * @return string Default email subject
		 */
		public function get_default_subject() {
			/* translators: 1: site title, 2: sender name */
			return sprintf( __( '%1$s: Congratulations! You\'ve received gift coupons from %2$s', 'woocommerce-smart-coupons' ), '{site_title}', '{sender_name}' );
		}

		/**
		 * Get default email heading.
		 *
		 * @return string Default email heading
		 */
		public function get_default_heading() {
			return __( 'You have received coupons.', 'woocommerce-smart-coupons' );
		}

		/**
		 * Get email subject.
		 *
		 * @return string Email subject
		 */
		public function get_subject() {
			$this->set_placeholders();
			$subject = parent::get_subject();
			return $subject;
		}

		/**
		 * Determine if the email should actually be sent and setup email merge variables
		 *
		 * @param array $args Email arguments.
		 */
		public function trigger( $args = array() ) {
			$this->email_args = wp_parse_args( $args, $this->email_args );

			if ( ! isset( $this->email_args['email'] ) || empty( $this->email_args['email'] ) ) {
				return;
			}

			$this->setup_locale();

			$this->recipient = $this->email_args['email'];

			$order_id = isset( $this->email_args['order_id'] ) ? $this->email_args['order_id'] : 0;

			// Get order object.
			if ( ! empty( $order_id ) && 0 !== $order_id ) {
				$order = wc_get_order( $order_id );
				if ( is_a( $order, 'WC_Order' ) ) {
					$this->object = $order;
				}
			}

			$this->set_placeholders();

			$email_content = $this->get_content();
			// Replace placeholders with values in the email content.
			$email_content = ( is_callable( array( $this, 'format_string' ) ) ) ? $this->format_string( $email_content ) : $email_content;

			// Send email.
			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $email_content, $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Function to set placeholder variables used in email subject/heading
		 */
		public function set_placeholders() {
			$sender_name                         = ( ( ! empty( $this->email_args['coupon']['code'] ) && 'sc_dummy_coupon' === $this->email_args['coupon']['code'] && ! empty( $this->email_args['gift_certificate_sender_name'] ) ) ? $this->email_args['gift_certificate_sender_name'] : '' );
			$this->placeholders['{sender_name}'] = ( ! empty( $sender_name ) ) ? $sender_name : $this->get_sender_name();
		}


		/**
		 * Get gift card image image URL for the coupon
		 *
		 * @param string $coupon_code Coupon code.
		 *
		 * @return string Custom image URL or fallback default image URL
		 */
		private function get_custom_gift_image_url( $coupon_code ) {
			$order = $this->object;

			// Get fallback image URL.
			$default_image_url = WC_SC_PLUGIN_URL . 'assets/images/giftcard/gift-card.jpg';
			if ( class_exists( 'WC_SC_Gift_Card_Image' ) ) {
				$gift_cert_handler = WC_SC_Gift_Card_Image::get_instance();
				$default_image_url = $gift_cert_handler->get_fallback_image_url();
			}

			if ( ! $order ) {
				return $default_image_url;
			}

			// Get the gift card image handler instance.
			if ( class_exists( 'WC_SC_Gift_Card_Image' ) ) {
				$gift_cert_handler = WC_SC_Gift_Card_Image::get_instance();

				// Use reflection to access private method.
				$reflection = new ReflectionClass( $gift_cert_handler );
				$method     = $reflection->getMethod( 'get_custom_image_for_coupon' );
				$method->setAccessible( true );

				$custom_image_filename = $method->invoke( $gift_cert_handler, $order->get_id(), $coupon_code );

				if ( $custom_image_filename ) {
					// Get image URL using reflection.
					$url_method = $reflection->getMethod( 'get_image_url' );
					$url_method->setAccessible( true );
					$custom_url = $url_method->invoke( $gift_cert_handler, $custom_image_filename );

					// Verify the custom image file exists before using it.
					if ( $custom_url && $this->verify_image_exists( $custom_url ) ) {
						return $custom_url;
					}
				}
			}

			// Always return fallback image if no custom image or custom image doesn't exist.
			return $default_image_url;
		}

		/**
		 * Verify if the image exists at the given URL
		 *
		 * @param string $url Image URL to verify.
		 * @return bool True if image exists, false otherwise
		 */
		private function verify_image_exists( $url ) {
			if ( empty( $url ) ) {
				return false;
			}

			// Convert URL to file path for local files.
			$upload_dir = wp_upload_dir();
			if ( strpos( $url, $upload_dir['baseurl'] ) === 0 ) {
				$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
				return file_exists( $file_path );
			}

			// For external URLs, use HTTP head request.
			$response = wp_remote_head( $url );
			return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		}

		/**
		 * Check if coupon has a custom uploaded image (not fallback)
		 *
		 * @param string $coupon_code Coupon code.
		 * @return bool True if has custom uploaded image, false otherwise
		 */
		private function has_custom_uploaded_image( $coupon_code ) {
			$order = $this->object;
			if ( ! $order ) {
				return false;
			}

			if ( class_exists( 'WC_SC_Gift_Card_Image' ) ) {
				$gift_cert_handler = WC_SC_Gift_Card_Image::get_instance();
				$reflection        = new ReflectionClass( $gift_cert_handler );
				$method            = $reflection->getMethod( 'get_custom_image_for_coupon' );
				$method->setAccessible( true );

				$custom_image_filename = $method->invoke( $gift_cert_handler, $order->get_id(), $coupon_code );
				return ! empty( $custom_image_filename );
			}

			return false;
		}

		/**
		 * Check if this coupon is for a product with gift certificate image support
		 *
		 * @param string $coupon_code Coupon code.
		 * @return bool True if gift certificate has image support enabled, false otherwise
		 */
		private function is_gift_certificate_with_image_support( $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			// First check if the coupon has a parent product with image support enabled.
			$coupon_parent = get_post_meta( $coupon->get_id(), 'coupon_generated_by_product_id', true );

			if ( $coupon_parent ) {
				$image_support = get_post_meta( $coupon_parent, '_wc_sc_enable_custom_gift_image', true );
				if ( 'yes' === $image_support ) {
					return true;
				}
			}

			// Check if this is a store credit/gift certificate coupon by checking discount type.
			$discount_type = $coupon->get_discount_type();

			if ( 'smart_coupon' === $discount_type ) {
				// Allow admin to control via filter whether to show fallback image.
				return apply_filters( 'wc_sc_gift_certificate_show_fallback_image', true, $coupon_code );
			}

			return false;
		}

		/**
		 * Function to load email html content
		 *
		 * @return string Email content html
		 */
		public function get_content_html() {

			global $woocommerce_smart_coupon, $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			$order         = $this->object;
			$url           = $this->get_url();
			$email_heading = $this->get_heading();

			$sender = '';
			$from   = '';

			$coupon_code = isset( $this->email_args['coupon']['code'] ) ? $this->email_args['coupon']['code'] : '';

			$coupon    = new WC_Coupon( $coupon_code );
			$coupon_id = ( $woocommerce_smart_coupon->is_wc_gte_30() ) ? ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ? $coupon->get_id() : 0 ) : ( ! empty( $coupon->id ) ? $coupon->id : 0 );

			if ( empty( $coupon_id ) ) {
				if ( 'sc_dummy_coupon' === $coupon_code ) {
					$email_preview = WC_SC_Email_Preview::get_instance();
					$coupon        = $email_preview->get_sc_dummy_coupon();
					$coupon_id     = ( $woocommerce_smart_coupon->is_wc_gte_30() ) ? ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ? $coupon->get_id() : 0 ) : ( ! empty( $coupon->id ) ? $coupon->id : 0 );
				} else {
					return;
				}
			}

			$order = ( ! empty( $order_id ) ) ? wc_get_order( $order_id ) : null; // phpcs:ignore

			$is_gift = isset( $this->email_args['is_gift'] ) ? $this->email_args['is_gift'] : '';

			if ( 'yes' === $is_gift ) {
				$sender_name  = $this->get_sender_name();
				$sender_email = $this->get_sender_email();
				if ( ! empty( $sender_name ) && ! empty( $sender_email ) ) {
					$sender = $sender_name . ' (' . $sender_email . ') ';
					$from   = ' ' . __( 'from', 'woocommerce-smart-coupons' ) . ' ';
				}
			}

			$email            = isset( $this->email_args['email'] ) ? $this->email_args['email'] : '';
			$receiver_details = isset( $this->email_args['receiver_details'] ) ? $this->email_args['receiver_details'] : '';
			$gift_message     = isset( $this->email_args['message_from_sender'] ) ? $this->email_args['message_from_sender'] : '';
			$recipient_name   = isset( $this->email_args['receiver_name'] ) ? $this->email_args['receiver_name'] : '';
			$design           = get_option( 'wc_sc_setting_coupon_design', 'basic' );
			$background_color = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
			$foreground_color = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
			$third_color      = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );

			$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

			$valid_designs = $woocommerce_smart_coupon->get_valid_coupon_designs();
			$coupon_amount = $woocommerce_smart_coupon->get_amount( $coupon, true, $order );

			if ( ! in_array( $design, $valid_designs, true ) ) {
				$design = 'basic';
			}

			// Check if this product has gift certificate image support enabled.
			$has_image_support = $this->is_gift_certificate_with_image_support( $coupon_code );

			// Get gift certificate image (custom or fallback) only if image support is enabled.
			$custom_gift_image_url = '';
			if ( $has_image_support ) {
				$custom_gift_image_url = $this->get_custom_gift_image_url( $coupon_code );
			}

			// Check if this is a custom uploaded image or fallback.
			$has_custom_image = $this->has_custom_uploaded_image( $coupon_code );

			// Override design if we have image support enabled for gift certificates.
			if ( $has_image_support ) {
				$design = 'custom-gift-image';
			} else {
				$design = ( 'custom-design' !== $design ) ? 'email-coupon' : $design;
			}

			$design = ( 'custom-design' !== $design ) ? 'email-coupon' : $design;

			$coupon_styles = $woocommerce_smart_coupon->get_coupon_styles( $design, array( 'is_email' => 'yes' ) );

			$default_path  = $this->template_base;
			$template_path = $woocommerce_smart_coupon->get_template_base_dir( $this->template_html );
			$is_percent    = $woocommerce_smart_coupon->is_percent_coupon( array( 'coupon_object' => $coupon ) );

			if ( 'sc_dummy_coupon' === $coupon_code ) {
				$custom_gift_image_url = $this->get_custom_gift_image_url( $coupon_code );
				/* translators: %s: Label for gift card */
				$email_heading = sprintf( _x( 'You have received a %s', 'Email heading for preview coupon email with image on admin side', 'woocommerce-smart-coupons' ), $singular_store_credit_label );
			}

			ob_start();

			wc_get_template(
				$this->template_html,
				array(
					'email'                   => $email,
					'email_obj'               => $this,
					'email_heading'           => $email_heading,
					'order'                   => $order,
					'url'                     => $url,
					'from'                    => $from,
					'background_color'        => $background_color,
					'foreground_color'        => $foreground_color,
					'third_color'             => $third_color,
					'coupon_styles'           => $coupon_styles,
					'sender'                  => $sender,
					'receiver_details'        => $receiver_details,
					'show_coupon_description' => $show_coupon_description,
					'design'                  => $design,
					'coupon_code'             => $coupon_code,
					'coupon_amount'           => $coupon_amount,
					'amount_symbol'           => ( true === $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
					'is_percent'              => $is_percent,
					'custom_gift_image_url'   => $custom_gift_image_url,
					'has_custom_image'        => $has_custom_image,
					'has_image_support'       => $has_image_support,
					'gift_message'            => $gift_message,
					'recipient_name'          => $recipient_name,
				),
				$template_path,
				$default_path
			);

			return ob_get_clean();
		}

		/**
		 * Function to load email plain content
		 *
		 * @return string Email plain content
		 */
		public function get_content_plain() {
			global $woocommerce_smart_coupon;

			$order         = $this->object;
			$url           = $this->get_url();
			$email_heading = $this->get_heading();

			$sender = '';
			$from   = '';

			$is_gift = isset( $this->email_args['is_gift'] ) ? $this->email_args['is_gift'] : '';

			if ( 'yes' === $is_gift ) {
				$sender_name  = $this->get_sender_name();
				$sender_email = $this->get_sender_email();
				if ( ! empty( $sender_name ) && ! empty( $sender_email ) ) {
					$sender = $sender_name . ' (' . $sender_email . ') ';
					$from   = ' ' . __( 'from', 'woocommerce-smart-coupons' ) . ' ';
				}
			}

			$email            = isset( $this->email_args['email'] ) ? $this->email_args['email'] : '';
			$receiver_details = isset( $this->email_args['receiver_details'] ) ? $this->email_args['receiver_details'] : '';
			$gift_message     = isset( $this->email_args['message_from_sender'] ) ? $this->email_args['message_from_sender'] : '';
			$recipient_name   = isset( $this->email_args['receiver_name'] ) ? $this->email_args['receiver_name'] : '';
			$coupon_code      = isset( $this->email_args['coupon']['code'] ) ? $this->email_args['coupon']['code'] : '';

			$coupon        = new WC_Coupon( $coupon_code );
			$coupon_amount = $woocommerce_smart_coupon->get_amount( $coupon, true, $order );

			// Get sender name for plain text template.
			$sender_name = $this->get_sender_name();

			// Get custom gift image URL if available.
			$custom_image_url = isset( $this->email_args['custom_gift_image_url'] ) ? $this->email_args['custom_gift_image_url'] : '';

			$default_path  = $this->template_base;
			$template_path = $woocommerce_smart_coupon->get_template_base_dir( $this->template_plain );

			ob_start();

			wc_get_template(
				$this->template_plain,
				array(
					'email'            => $email,
					'email_obj'        => $this,
					'email_heading'    => $email_heading,
					'order'            => $order,
					'url'              => $url,
					'from'             => $from,
					'sender'           => $sender,
					'receiver_details' => $receiver_details,
					'gift_message'     => $gift_message,
					'recipient_name'   => $recipient_name,
					'sender_name'      => $sender_name,
					'gift_card_code'   => $coupon_code,
					'gift_card_amount' => wc_price( $coupon_amount ),
					'custom_image_url' => $custom_image_url,
				),
				$template_path,
				$default_path
			);

			return ob_get_clean();
		}


		/**
		 * Function to update SC admin email settings when WC email settings get updated
		 */
		public function process_admin_options() {
			// Save regular options.
			parent::process_admin_options();

			$is_email_enabled = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );

			if ( ! empty( $is_email_enabled ) ) {
				update_option( 'smart_coupons_is_send_email', $is_email_enabled, 'no' );
			}

		}

	}
}
