<?php
/**
 * Main class for Unused Coupon Reminder Email
 *
 * @author      StoreApps
 * @since       9.57.0
 * @version     1.1.0
 *
 * @package     woocommerce-smart-coupons/includes/emails/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Unused_Reminder_Email' ) ) {
	/**
	 * The Unused Coupon Reminder Email class
	 *
	 * @extends \WC_SC_Email
	 */
	class WC_SC_Unused_Reminder_Email extends WC_SC_Email {

		/**
		 * Coupon details
		 *
		 * @var array $coupons
		 */
		public $coupons;

		/**
		 * Customer name
		 *
		 * @var string $customer_name
		 */
		public $customer_name;

		/**
		 * Set email defaults
		 */
		public function __construct() {
			$this->id             = 'wc_sc_unused_reminder_email';
			$this->customer_email = true;

			// Set dummy coupon object to avoid fatal error in parent WC_Email class.
			$this->object = new WC_Coupon();

			// Set email title and description.
			$this->title       = _x( 'Smart Coupons - Unused Coupon Reminder', 'unused coupon email title', 'woocommerce-smart-coupons' );
			$this->description = _x( 'Send a reminder email for unused coupons.', 'unused coupon email description', 'woocommerce-smart-coupons' );

			// Use our plugin templates directory as the template base.
			$this->template_base = dirname( WC_SC_PLUGIN_FILE ) . '/templates/';

			// Email template location.
			$this->template_html  = 'unused-coupon-reminder.php';
			$this->template_plain = 'plain/unused-coupon-reminder.php';

			$this->placeholders = array(
				'{coupon_code}'   => '',
				'{coupon_type}'   => '',
				'{coupon_expiry}' => '',
				'{sender_name}'   => '',
			);

			// Trigger for this email.
			add_action( 'wc_sc_unused_coupon_reminder_email_notification', array( $this, 'trigger' ) );
			add_action( 'admin_footer', array( $this, 'add_btn_manual_trigger_reminders' ), 21 );

			// Call parent constructor to load any other defaults not explicitly defined here.
			parent::__construct();
		}

		/**
		 * Get default email subject
		 *
		 * @return string Default email subject
		 */
		public function get_default_subject() {
			/* translators: 1: site title */
			return sprintf( _x( '%s: Don\'t Miss Out! Your Coupon Is Still Unused', 'unused coupon email subject', 'woocommerce-smart-coupons' ), '{site_title}' );
		}

		/**
		 * Get default email heading
		 *
		 * @return string Default email heading
		 */
		public function get_default_heading() {
			return _x( 'Reminder: You Have an Unused Coupon Waiting for You', 'unused coupon email heading', 'woocommerce-smart-coupons' );
		}

		/**
		 * Initialize Settings Form Fields
		 */
		public function init_form_fields() {
			$form_fields = array(
				'reminder_schedule'         => array(
					'title'       => _x( 'How often should reminders be sent', 'unused coupon admin setting title', 'woocommerce-smart-coupons' ),
					'type'        => 'select',
					'description' => _x( 'Choose how frequently to send reminder emails.', 'unused coupon admin setting description', 'woocommerce-smart-coupons' ),
					'default'     => 'weekly',
					'options'     => array(
						'daily'   => _x( 'Once per day', 'reminder frequency', 'woocommerce-smart-coupons' ),
						'weekly'  => _x( 'Once per week', 'reminder frequency', 'woocommerce-smart-coupons' ),
						'monthly' => _x( 'Once per month', 'reminder frequency', 'woocommerce-smart-coupons' ),
					),
				),
				'inactivity_days_threshold' => array(
					'title'             => _x( 'Inactivity period before reminder', 'unused coupon admin setting title', 'woocommerce-smart-coupons' ),
					'type'              => 'number',
					'desc_tip'          => true,
					'description'       => _x( 'Send a reminder if the coupon is unused for X days after being assigned to the customer.', 'unused coupon admin setting description', 'woocommerce-smart-coupons' ),
					'placeholder'       => _x( 'e.g., 7', 'example number of days', 'woocommerce-smart-coupons' ),
					'default'           => 7,
					'custom_attributes' => array(
						'min' => '1',
						'max' => '365',
					),
					'suffix'            => _x( 'days', 'time unit', 'woocommerce-smart-coupons' ),
				),
				'max_reminders_per_coupon'  => array(
					'title'             => _x( 'Maximum reminders per coupon', 'unused coupon admin setting title', 'woocommerce-smart-coupons' ),
					'type'              => 'number',
					'desc_tip'          => true,
					'description'       => _x( 'Maximum number of reminder emails to send for each coupon.', 'unused coupon admin setting description', 'woocommerce-smart-coupons' ),
					'default'           => 3,
					'custom_attributes' => array(
						'min' => '1',
						'max' => '10',
					),
				),
			);

			parent::init_form_fields();
			$this->form_fields['enabled']['default'] = 'no';
			$this->form_fields                       = array_merge( $this->form_fields, $form_fields );
		}

		/**
		 * Determine if the email should actually be sent and setup email merge variables.
		 *
		 * @param int $coupon_id The ID of the coupon.
		 */
		public function trigger( $coupon_id ) {
			global $woocommerce_smart_coupon;
			try {
				if ( $coupon_id ) {
					$coupon = new WC_Coupon( $coupon_id );

					/**
					 * Filter the coupon object before sending the unused reminder email.
					 *
					 * Allows developers to modify or replace the WC_Coupon object
					 * before the email is prepared and sent.
					 *
					 * @param WC_Coupon $coupon    The coupon object.
					 * @param int       $coupon_id The coupon ID.
					 */
					$coupon = apply_filters(
						'wc_sc_unused_reminder_email_coupon',
						$coupon,
						array(
							'coupon_id' => $coupon_id,
						)
					);

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
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Function to set placeholder variables used in email subject/heading
		 */
		public function set_placeholders() {
			$this->placeholders['{coupon_expiry}'] = $this->get_coupon_expiry();
			$this->placeholders['{coupon_code}']   = $this->object->get_code();
			$this->placeholders['{coupon_type}']   = $this->get_coupon_type();
			$this->placeholders['{sender_name}']   = $this->get_sender_name();
		}

		/**
		 * Load email HTML content
		 *
		 * @return string Email content HTML
		 */
		public function get_content_html() {
			global $woocommerce_smart_coupon;
			try {
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
						'coupon_amount' => wc_price( $this->object->get_amount() ),
						'coupon_expiry' => ( $this->object->get_date_expires() ) ? wc_format_datetime( $this->object->get_date_expires() ) : '',
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
		 * Load email plain content
		 *
		 * @return string Email plain content
		 */
		public function get_content_plain() {
			global $woocommerce_smart_coupon;
			try {
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
		 * Process admin options for the email settings
		 *
		 * @return void
		 */
		public function process_admin_options() {
			global $woocommerce_smart_coupon;

			try {
				// Save regular options.
				parent::process_admin_options();

				$is_email_enabled  = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );
				$reminder_schedule = $this->get_field_value( 'reminder_schedule', $this->form_fields['reminder_schedule'] );

				// Schedule or unschedule reminders based on settings.
				if ( 'yes' === $is_email_enabled ) {
					// Initialize the scheduler.
					$scheduler = WC_SC_Coupons_Unused_Reminder_Scheduler::get_instance();
					$scheduler->schedule_reminder_based_on_settings( $reminder_schedule );
				} else {
					// Cancel all scheduled reminders.
					$scheduler = WC_SC_Coupons_Unused_Reminder_Scheduler::get_instance();
					$scheduler->cancel_all_scheduled_reminders();
					delete_transient( 'wc_sc_send_unused_coupon_reminder_process_status' );
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}

		/**
		 * Add the "Manage Coupon Reminder Emails" button next to the "Save changes" button
		 * in the Expiry Email settings tab.
		 *
		 * @return void
		 */
		public function add_btn_manual_trigger_reminders() {
			global $woocommerce_smart_coupon;
			try {
				if ( ! $this->get_field_value( 'enabled', $this->form_fields['enabled'] ) ) {
					return;
				}

				// Only run this on the specific Expiry Email settings tab.
				$current_tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : ''; //phpcs:ignore
				$current_section = isset( $_GET['section'] ) ? $_GET['section'] : ''; //phpcs:ignore

				if ( 'email' === $current_tab && 'wc_sc_unused_reminder_email' === $current_section ) {
					$actions_url = add_query_arg(
						array(
							'page'    => 'wc-settings',
							'tab'     => 'email',
							'section' => 'wc_sc_unused_reminder_email',
							'action'  => 'manual_trigger_unused_coupon_reminder',
						),
						admin_url( 'admin.php' )
					);
					?>
					<script type="text/javascript">
						document.addEventListener('DOMContentLoaded', function () {
							const checkbox = document.getElementById('woocommerce_wc_sc_unused_reminder_email_enabled');
							const saveButton = document.querySelector('.woocommerce-save-button');

							if (!checkbox || !saveButton) {
								return;
							}

							// If checkbox is disabled on load, do nothing
							if (!checkbox.checked) {
								return;
							}

							// Create the manage button (only once)
							const manageButton = document.createElement('a');
							manageButton.href = '<?php echo esc_url_raw( $actions_url ); ?>';
							manageButton.className = 'components-button is-secondary';
							manageButton.style.marginLeft = '10px';
							manageButton.innerHTML = '<?php echo esc_html__( 'Send All Unused Coupon Reminders Now', 'woocommerce-smart-coupons' ); ?>';

							// Add button initially
							saveButton.parentNode.appendChild(manageButton);

							// Handle future toggling
							checkbox.addEventListener('change', function () {
								if (checkbox.checked) {
									if (!document.body.contains(manageButton)) {
										saveButton.parentNode.appendChild(manageButton);
									}
								} else {
									if (document.body.contains(manageButton)) {
										manageButton.remove();
									}
								}
							});
						});
					</script>
					<?php
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
		}
	}
}
