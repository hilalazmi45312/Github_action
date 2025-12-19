<?php
/**
 * Main class for Expiry Reminder Email
 *
 * @author      StoreApps
 * @since       9.16.0
 * @version     1.6.0
 *
 * @package     woocommerce-smart-coupons/includes/emails/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Expiry_Reminder_Email' ) ) {
	/**
	 * The Expiry Reminder Email class
	 *
	 * @extends \WC_SC_Email
	 */
	class WC_SC_Expiry_Reminder_Email extends WC_SC_Email {

		/**
		 * Coupon details
		 *
		 * @var array $coupons
		 */
		public $coupons;

		/**
		 * Set email defaults
		 */
		public function __construct() {
			$this->id             = 'wc_sc_expiry_reminder_email';
			$this->customer_email = true;

			// Set dummy coupon object to avoid fatal error in parent WC_Email class, this will be replaced with actual coupon when email is triggered.
			$this->object = new WC_Coupon();

			// Set email title and description.
			$this->title       = _x( 'Smart Coupons - Coupon Expiry Reminder', 'email title', 'woocommerce-smart-coupons' );
			$this->description = _x( 'Send a reminder email for coupons approaching expiry.', 'email description', 'woocommerce-smart-coupons' );

			// Use our plugin templates directory as the template base.
			$this->template_base = dirname( WC_SC_PLUGIN_FILE ) . '/templates/';

			// Email template location.
			$this->template_html  = 'expiry-reminder-email.php';
			$this->template_plain = 'plain/expiry-reminder-email.php';

			$this->placeholders = array(
				'{coupon_code}'   => '',
				'{coupon_type}'   => '',
				'{coupon_expiry}' => '',
				'{sender_name}'   => '',
			);

			// Trigger for this email.
			add_action( 'wc_sc_expiry_reminder_email_notification', array( $this, 'trigger' ) );
			add_action( 'admin_footer', array( $this, 'add_manage_coupon_reminder_button_next_to_save' ) );
			// Call parent constructor to load any other defaults not explicitly defined here.
			parent::__construct();
		}

		/**
		 * Get default email subject.
		 *
		 * @return string Default email subject
		 */
		public function get_default_subject() {
			/* translators: 1: site title */
			return sprintf( _x( '%s: Your Coupon Are About to Expire!', 'email subject', 'woocommerce-smart-coupons' ), '{site_title}' );
		}

		/**
		 * Get default email heading.
		 *
		 * @return string Default email heading
		 */
		public function get_default_heading() {
			return _x( 'Reminder: Your Coupon Are Expiring Soon', 'email heading', 'woocommerce-smart-coupons' );
		}

		/**
		 * Initialize Settings Form Fields
		 */
		public function init_form_fields() {
			global $woocommerce_smart_coupon;
			$description_text = _x( 'Specify the number of days before the coupon expiry date when the reminder email should be sent to the customer. Leave blank for a reminder on the same expiry day.', 'admin setting description', 'woocommerce-smart-coupons' );

			// Translators: %s is an example number of days.
			$placeholder_text = sprintf( _x( 'e.g., %s', 'example number of days', 'woocommerce-smart-coupons' ), '5' );

			$form_fields = array(
				'scheduled_days_before_expiry' => array(
					'title'             => _x( 'Send reminder X days before expiry', 'admin setting title', 'woocommerce-smart-coupons' ),
					'type'              => 'number',
					'desc_tip'          => true,
					'description'       => $description_text,
					'placeholder'       => $placeholder_text,
					'default'           => '',
					'custom_attributes' => array(
						'min' => '0', // Ensures that the value cannot go negative.
					),
					'suffix'            => _x( 'days', 'time unit', 'woocommerce-smart-coupons' ),  // Adding suffix.
				),
			);

			parent::init_form_fields();
			if ( ! in_array( $woocommerce_smart_coupon->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {

				$this->form_fields['enabled']['disabled']    = true;
				$this->form_fields['enabled']['description'] = '<p style="color: red;">' . _x( 'This feature is unavailable until the WooCommerce Smart Coupons database update is complete. Please update the WooCommerce Smart Coupons database to enable coupon expiry reminders.', 'This message is shown when the WooCommerce Smart Coupons database is not updated', 'woocommerce-smart-coupons' ) . '</p>';

			}
			$this->form_fields['enabled']['default'] = 'no';
			$this->form_fields                       = array_merge( $this->form_fields, $form_fields );
		}

		/**
		 * Determine if the email should actually be sent and setup email merge variables.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 */
		public function trigger( $coupon_id ) {
			try {
				if ( $coupon_id ) {
					$coupon = new WC_Coupon( $coupon_id );

					if ( ! $coupon instanceof WC_Coupon ) {
						return;
					}

					$coupon_amount = $coupon->get_amount();

					if ( ! $coupon_amount || ! is_numeric( $coupon_amount ) || $coupon_amount <= 0 ) {
						return;
					}

					$this->object = $coupon;

					$recipients = $coupon->get_email_restrictions();

					// Filter the list of recipients before sending the email.
					$recipients = apply_filters( 'wc_sc_coupon_expiry_reminder_filter_emails', $recipients, $coupon_id );

					// Get email restrictions and send email if valid.
					foreach ( $recipients as $email ) {
						if ( ! is_email( $email ) ) {
							continue; // Skip invalid email addresses.
						}

						$this->setup_locale();
						$this->recipient = $email;

						$this->set_placeholders();

						$email_content = $this->get_content();
						// Replace placeholders with values in the email content.
						$email_content = ( is_callable( array( $this, 'format_string' ) ) ) ? $this->format_string( $email_content ) : $email_content;

						// Send email if enabled and recipient is set.
						if ( $this->is_enabled() && $this->get_recipient() ) {
							$this->send( $this->get_recipient(), $this->get_subject(), $email_content, $this->get_headers(), $this->get_attachments() );
						}

						$this->restore_locale();
					}
				}
			} catch ( \Throwable $e ) {
				global $woocommerce_smart_coupon;
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Function to set placeholder variables used in email subject/heading
		 */
		public function set_placeholders() {
			$this->placeholders['{coupon_code}']   = $this->object->get_code();
			$this->placeholders['{coupon_type}']   = $this->get_coupon_type();
			$this->placeholders['{coupon_expiry}'] = $this->get_coupon_expiry();
			$this->placeholders['{sender_name}']   = $this->get_sender_name();
		}

		/**
		 * Load email HTML content.
		 *
		 * @return string Email content HTML
		 */
		public function get_content_html() {
			try {
				global $woocommerce_smart_coupon;

				$email_heading = $this->get_heading();

				$default_path  = $this->template_base;
				$template_path = $woocommerce_smart_coupon->get_template_base_dir( $this->template_html );

				ob_start();
				wc_get_template(
					$this->template_html,
					array(
						'email_obj'     => $this,
						'email_heading' => $email_heading,
						'coupon_code'   => $this->object->get_code(),
						'url'           => $this->get_url(),
						'coupon_html'   => $this->get_coupon_design_html( $this->object->get_id(), $this->object ),
					),
					$template_path,
					$default_path
				);

				return ob_get_clean();
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				ob_end_clean();
				return '';
			}

		}

		/**
		 * Load email plain content.
		 *
		 * @return string Email plain content
		 */
		public function get_content_plain() {
			try {
				global $woocommerce_smart_coupon;

				$email_heading = $this->get_heading();

				$default_path  = $this->template_base;
				$template_path = $woocommerce_smart_coupon->get_template_base_dir( $this->template_html );

				ob_start();
				wc_get_template(
					$this->template_plain,
					array(
						'email_obj'     => $this,
						'coupon'        => $this->object,
						'email_heading' => $email_heading,
						'coupon_code'   => $this->object->get_code(),
						'url'           => $this->get_url(),
					),
					$template_path,
					$default_path
				);

				return ob_get_clean();
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				ob_end_clean();
				return '';
			}

		}

		/**
		 * Process admin options for the email settings.
		 *
		 * @return void
		 */
		public function process_admin_options() {
			global $woocommerce_smart_coupon;

			if ( ! in_array( $woocommerce_smart_coupon->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {
				return;
			}

			$before_save_is_email_enabled = $this->settings['enabled'];
			// Save regular options.
			parent::process_admin_options();

			$is_email_enabled = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );
			if ( $before_save_is_email_enabled === $is_email_enabled ) {
				return;
			}

			if ( ! empty( $is_email_enabled ) && 'yes' === $is_email_enabled ) {
				set_transient( 'wc_sc_coupons_expiry_reminder_status', 'in-progress' );
				$reminder_process = WC_SC_Coupons_Expiry_Reminder_Scheduler::get_instance();
				$reminder_process->init();
			} else {
				delete_transient( 'wc_sc_coupons_expiry_reminder_status' );
			}
		}

		/**
		 * Add the "Manage Coupon Reminder Emails" button next to the "Save changes" button
		 * in the Expiry Email settings tab.
		 *
		 * @return void
		 */
		public function add_manage_coupon_reminder_button_next_to_save() {
			// Only run this on the specific Expiry Email settings tab.
			$current_tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : ''; //phpcs:ignore
			$current_section = isset( $_GET['section'] ) ? $_GET['section'] : ''; //phpcs:ignore

			if ( 'email' === $current_tab && 'wc_sc_expiry_reminder_email' === $current_section ) :
				$actions_url = add_query_arg(
					array(
						'page'   => 'wc-status',
						'tab'    => 'action-scheduler',
						's'      => 'wc_sc_send_coupon_expiry_reminder',
						'status' => 'pending',
					),
					admin_url( 'admin.php' )
				);
				?>
				<script type="text/javascript">
					document.addEventListener('DOMContentLoaded', function () {
						// Create a new button element.
						const manageButton = document.createElement('a');
						manageButton.href = '<?php echo esc_url_raw( $actions_url ); ?>';
						manageButton.className = 'components-button is-secondary';
						manageButton.style.marginLeft = '10px';
						manageButton.innerHTML = '<?php echo esc_html__( 'Manage Coupon Reminder Emails', 'woocommerce-smart-coupons' ); ?>';

						// Append the button next to the "Save changes" button.
						const saveButton = document.querySelector('.woocommerce-save-button');
						if (saveButton) {
							saveButton.parentNode.appendChild(manageButton);
						}
					});
				</script>
				<?php
			endif;
		}
	}
}
