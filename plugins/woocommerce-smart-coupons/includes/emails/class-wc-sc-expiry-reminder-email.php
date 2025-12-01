<?php
/**
 * Main class for Expiry Reminder Email
 *
 * @author      StoreApps
 * @since       9.16.0
 * @version     1.3.0
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
			return _x( '{site_title}: Your Coupon Are About to Expire!', 'email subject', 'woocommerce-smart-coupons' );
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
		 * Function to get coupon type for current coupon being sent.
		 *
		 * @return string $coupon_type Coupon type.
		 */
		public function get_coupon_type() {

			global $store_credit_label;

			$discount_type = $this->object->get_discount_type();
			$is_gift       = isset( $this->email_args['is_gift'] ) ? $this->email_args['is_gift'] : '';

			if ( 'smart_coupon' === $discount_type && 'yes' === $is_gift ) {
				$smart_coupon_type = __( 'Gift Card', 'woocommerce-smart-coupons' );
			} else {
				$smart_coupon_type = __( 'Store Credit', 'woocommerce-smart-coupons' );
			}

			if ( ! empty( $store_credit_label['singular'] ) ) {
				$smart_coupon_type = ucwords( $store_credit_label['singular'] );
			}

			$coupon_type = ( 'smart_coupon' === $discount_type && ! empty( $smart_coupon_type ) ) ? $smart_coupon_type : __( 'coupon', 'woocommerce-smart-coupons' );

			return $coupon_type;
		}

		/**
		 * Function to get coupon expiry date/time for current coupon being sent.
		 *
		 * @return string $coupon_expiry Coupon expiry.
		 */
		public function get_coupon_expiry() {

			global $woocommerce_smart_coupon, $wpdb;

			$coupon = $this->object;
			if ( $woocommerce_smart_coupon->is_wc_gte_30() ) {
				$coupon_id = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
			} else {
				$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
			}
			// phpcs:disable
			// Get the expiration date and schedule reminder.
			$expiration_date = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT UNIX_TIMESTAMP(date_expires) FROM {$wpdb->prefix}wc_smart_coupons WHERE id = %d",
					$coupon_id
				)
			);
			// phpcs:enable

			return $expiration_date ? $woocommerce_smart_coupon->get_expiration_format( $expiration_date ) : esc_html__( 'Never expires', 'woocommerce-smart-coupons' );
		}

		/**
		 * Load email HTML content.
		 *
		 * @return string Email content HTML
		 */
		public function get_content_html() {
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
					'coupon_html'   => $this->get_coupon_design_html(),
				),
				$template_path,
				$default_path
			);

			return ob_get_clean();
		}

		/**
		 * Load email plain content.
		 *
		 * @return string Email plain content
		 */
		public function get_content_plain() {
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
		 * Generate Coupon Design HTML
		 *
		 * @return string HTML output of the coupon design.
		 */
		public function get_coupon_design_html() {
			global $woocommerce_smart_coupon;

			$coupon      = $this->object;
			$coupon_id   = $this->object->get_id();
			$coupon_data = $woocommerce_smart_coupon->get_coupon_meta_data( $coupon );

			if ( $woocommerce_smart_coupon->is_wc_gte_30() ) {
				$is_free_shipping = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
				$expiry_date      = $coupon->get_date_expires();
				$coupon_code      = $coupon->get_code();
			} else {
				$is_free_shipping = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
				$expiry_date      = ( ! empty( $coupon->expiry_date ) ) ? $coupon->expiry_date : '';
				$coupon_code      = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
			}

			$design                  = get_option( 'wc_sc_setting_coupon_design', 'basic' );
			$background_color        = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
			$foreground_color        = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
			$third_color             = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );
			$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

			// Check if the design is valid.
			$valid_designs = $woocommerce_smart_coupon->get_valid_coupon_designs();
			if ( ! in_array( $design, $valid_designs, true ) ) {
				$design = 'basic';
			}

			$design = ( 'custom-design' !== $design ) ? 'email-coupon' : $design;

			// Coupon-specific parameters.
			$coupon_amount      = $woocommerce_smart_coupon->get_amount( $coupon, true );
			$is_percent         = ( $coupon->get_discount_type() === 'percent' );
			$coupon_description = ( 'yes' === $show_coupon_description ) ? $coupon->get_description() : '';

			$coupon_styles = $woocommerce_smart_coupon->get_coupon_styles( $design, array( 'is_email' => 'yes' ) );
			$coupon_type   = ( ! empty( $coupon_data['coupon_type'] ) ) ? $coupon_data['coupon_type'] : '';

			if ( 'yes' === $is_free_shipping ) {
				if ( ! empty( $coupon_type ) ) {
					$coupon_type .= __( ' & ', 'woocommerce-smart-coupons' );
				}
				$coupon_type .= __( 'Free Shipping', 'woocommerce-smart-coupons' );
			}

			if ( ! empty( $expiry_date ) ) {
				if ( $woocommerce_smart_coupon->is_wc_gte_30() && $expiry_date instanceof WC_DateTime ) {
					$expiry_date = ( is_callable( array( $expiry_date, 'getTimestamp' ) ) ) ? $expiry_date->getTimestamp() : null;
				} elseif ( ! is_int( $expiry_date ) ) {
					$expiry_date = strtotime( $expiry_date );
				}
				if ( ! empty( $expiry_date ) && is_int( $expiry_date ) ) {
					$expiry_time = (int) $woocommerce_smart_coupon->get_post_meta( $coupon_id, 'wc_sc_expiry_time', true );
					if ( ! empty( $expiry_time ) ) {
						$expiry_date += $expiry_time; // Adding expiry time to expiry date.
					}
				}
			}

			$coupon_target              = '';
			$wc_url_coupons_active_urls = get_option( 'wc_url_coupons_active_urls' ); // From plugin WooCommerce URL coupons.
			if ( ! empty( $wc_url_coupons_active_urls ) ) {
				$coupon_target = ( ! empty( $wc_url_coupons_active_urls[ $coupon_id ]['url'] ) ) ? $wc_url_coupons_active_urls[ $coupon_id ]['url'] : '';
			}
			if ( ! empty( $coupon_target ) ) {
				$coupon_target = home_url( '/' . $coupon_target );
			} else {
				$coupon_target = home_url( '/?sc-page=shop&coupon-code=' . $coupon_code );
			}

			$coupon_target = apply_filters( 'sc_coupon_url_in_email', $coupon_target, $coupon );
			// Template arguments.
			$args = array(
				'coupon_object'      => $coupon,
				'coupon_amount'      => $coupon_amount,
				'amount_symbol'      => ( $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
				'discount_type'      => wp_strip_all_tags( $coupon_type ),
				'coupon_description' => ! empty( $coupon_description ) ? $coupon_description : wp_strip_all_tags( $woocommerce_smart_coupon->generate_coupon_description( array( 'coupon_object' => $coupon ) ) ),
				'coupon_code'        => $coupon->get_code(),
				'coupon_expiry'      => ( ! empty( $expiry_date ) ) ? $woocommerce_smart_coupon->get_expiration_format( $expiry_date ) : _x( 'Never expires', 'coupon never expires', 'woocommerce-smart-coupons' ),
				'thumbnail_src'      => $woocommerce_smart_coupon->get_coupon_design_thumbnail_src(
					array(
						'design'        => $design,
						'coupon_object' => $coupon,
					)
				),
				'classes'            => '',
				'template_id'        => $design,
				'is_percent'         => $is_percent,
			);

			// Output the design template.
			ob_start();
			?>
			<style type="text/css">
				.coupon-container {
					margin: .2em;
					box-shadow: 0 0 5px #e0e0e0;
					display: inline-table;
					text-align: center;
					cursor: pointer;
					padding: .55em;
					line-height: 1.4em;
				}

				.coupon-content {
					padding: 0.2em 1.2em;
				}

				.coupon-content .code {
					font-family: monospace;
					font-size: 1.2em;
					font-weight:700;
				}

				.coupon-content .coupon-expire,
				.coupon-content .discount-info {
					font-family: Helvetica, Arial, sans-serif;
					font-size: 1em;
				}
				.coupon-content .discount-description {
					font: .7em/1 Helvetica, Arial, sans-serif;
					width: 250px;
					margin: 10px inherit;
					display: inline-block;
				}
			</style>
			<style type="text/css"><?php echo ( isset( $coupon_styles ) && ! empty( $coupon_styles ) ) ? esc_html( wp_strip_all_tags( $coupon_styles, true ) ) : ''; // phpcs:ignore ?></style>
			<?php if ( 'custom-design' !== $design ) { ?>
				<style type="text/css">
					:root {
						--sc-color1: <?php echo esc_html( $background_color ); ?>;
						--sc-color2: <?php echo esc_html( $foreground_color ); ?>;
						--sc-color3: <?php echo esc_html( $third_color ); ?>;
					}
				</style>
			<?php } ?>
			<div style="margin: 10px 0;" title="<?php echo esc_attr__( 'Click to visit store. This coupon will be applied automatically.', 'woocommerce-smart-coupons' ); ?>">
				<a href="<?php echo esc_url( $coupon_target ); ?>" style="color: #444;">
					<?php wc_get_template( 'coupon-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' ); ?>
				</a>
			</div>
			<?php
			return ob_get_clean();
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
