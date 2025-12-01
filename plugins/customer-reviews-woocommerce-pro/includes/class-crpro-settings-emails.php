<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Emails_Settings' ) ) :

	class CRPRO_Emails_Settings {

		public function __construct() {
			add_filter( 'cr_settings_email_templates', array( $this, 'extra_email_templates' ), 10, 1 );
			add_filter( 'cr_settings_email_template', array( $this, 'extra_email_template_settings' ), 10, 2 );
			add_filter( 'cr_settings_email_template_php', array( $this, 'email_template_php_settings' ), 10, 2 );
			add_filter( 'cr_settings_emails_sections', array( $this, 'display_email_editor' ), 10, 2 );
			add_filter( 'cr_settings_email_template_title', array( $this, 'email_template_title' ), 10, 2 );
			add_filter( 'cr_settings_email_template_description', array( $this, 'email_template_description' ), 10, 2 );
			add_filter( 'cr_settings_email_template_enabled', array( $this, 'email_template_enabled' ), 10, 2 );
			add_filter( 'cr_settings_email_template_from', array( $this, 'email_template_from' ), 10, 2 );
			add_filter( 'cr_settings_email_mailer_id', array( $this, 'email_mailer_id' ), 10, 2 );
			add_filter( 'cr_settings_email_mailer_options', array( $this, 'email_mailer_options' ), 10, 3 );
			add_filter( 'cr_settings_email_reply_to', array( $this, 'email_reply_to' ), 10, 1 );
			add_filter( 'cr_settings_email_bcc', array( $this, 'email_bcc' ), 10, 1 );
			add_filter( 'cr_settings_email_subject_id', array( $this, 'email_subject_id' ), 10, 2 );
			add_filter( 'cr_settings_email_subject_default', array( self::class, 'email_subject_default' ), 10, 2 );
			add_filter( 'cr_settings_email_heading_id', array( $this, 'email_heading_id' ), 10, 2 );
			add_filter( 'cr_settings_email_heading_default', array( self::class, 'email_heading_default' ), 10, 2 );
			add_filter( 'cr_settings_email_body_id', array( $this, 'email_body_id' ), 10, 2 );
			add_filter( 'cr_settings_email_body_default', array( self::class, 'email_body_default' ), 10, 2 );
			add_filter( 'cr_settings_email_body_variables', array( $this, 'email_body_variables' ), 10, 2 );
			add_filter( 'cr_settings_email_file_name', array( $this, 'email_file_name' ), 10, 2 );
			add_filter( 'cr_settings_email_template_base', array( $this, 'email_template_base' ), 10, 2 );
			add_filter( 'cr_settings_email_colors', array( $this, 'email_colors' ), 10, 1 );
			add_filter( 'cr_settings_email_color_1_id', array( $this, 'email_color_1_id' ), 10, 2 );
			add_filter( 'cr_settings_email_color_1_desc', array( $this, 'email_color_1_desc' ), 10, 2 );
			add_filter( 'cr_settings_email_color_1_class', array( $this, 'email_color_1_class' ), 10, 2 );
			add_filter( 'cr_settings_email_color_2_id', array( $this, 'email_color_2_id' ), 10, 2 );
			add_filter( 'cr_settings_email_color_2_desc', array( $this, 'email_color_2_desc' ), 10, 2 );
			add_filter( 'cr_settings_email_color_2_class', array( $this, 'email_color_2_class' ), 10, 2 );
			add_filter( 'cr_settings_email_footer', array( $this, 'email_footer' ), 10, 1 );
			add_filter( 'cr_settings_send_test', array( $this, 'send_test_email' ), 10, 3 );

			// display information about [cusrev_unsubscribe] on the settings page with shortcodes
			add_filter( 'cr_settings_shortcodes_desc', array( $this, 'shortcodes_settings' ) );

			add_action( 'woocommerce_admin_field_crpro_advanced_email_template', array( $this, 'show_advanced_email_template' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'admin_footer', array( $this, 'output_inline_scripts' ) );
			add_action( 'cr_settings_email_save_fields', array( $this, 'email_save_fields' ) );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_2', array( $this, 'save_email_body' ), 10, 3 );
		}

		public function enqueue_scripts_styles( $hook_suffix ) {
			// email editor scripts and styles
			if(
				isset( $_SERVER['REQUEST_URI'] ) &&
				(
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_reminder_editor' ) ||
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_reminder_2_editor' ) ||
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_discount_editor' )
				)
			) {
				wp_enqueue_style( 'crpro-font-awesome', plugins_url( '/dist/css/font-awesome.min.css', dirname( __FILE__ ) ), array() );
				wp_enqueue_style( 'crpro-email-editor-vendors', plugins_url( '/dist/css/email-editor-vendors.css', dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION  );
				wp_enqueue_style( 'crpro-email-editor-app', plugins_url( '/dist/css/email-editor-app.css', dirname( __FILE__ ) ), array( 'crpro-email-editor-vendors', 'crpro-font-awesome' ), CRPRO::CRPRO_VERSION  );
				wp_enqueue_script( 'crpro-pace', plugins_url( '/dist/js/pace/pace.min.js' , dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION, true );
				wp_enqueue_script( 'crpro-tinymce', plugins_url( '/dist/js/tinymce/tinymce.min.js' , dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION, true );
				wp_enqueue_script( 'crpro-email-editor-vendors', plugins_url( '/dist/js/email-editor-vendors.js' , dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION, true );
				wp_enqueue_script( 'crpro-email-editor-bundle', plugins_url( '/dist/js/email-editor-bundle.js' , dirname( __FILE__ ) ), array( 'crpro-email-editor-vendors', 'crpro-tinymce' ), CRPRO::CRPRO_VERSION, true );
			}
		}

		public function output_inline_scripts() {
			if(
				isset( $_SERVER['REQUEST_URI'] ) &&
				(
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_reminder_editor' ) ||
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_reminder_2_editor' ) ||
					false !== strpos( $_SERVER['REQUEST_URI'], 'page=cr-reviews-settings&tab=emails&section=review_discount_editor' )
				)
			) {
				?>
					<script type="text/javascript" >
						window.paceOptions = {
							restartOnPushState: false,
							ajax: false,
							elements: {
								selectors: ['body.ready']
							},
							target: '.crpro-email-editor-pace',
						}
					</script>
				<?php
			}
		}

		public function extra_email_templates( $templates ) {
			if ( is_array( $templates ) ) {
				$templates = array_slice( $templates, 0, 1) + array( 'review_reminder_2' => 'review_reminder_2' ) + $templates;
			}
			return $templates;
		}

		public function extra_email_template_settings( $fields, $email_type ) {
			if( in_array( $email_type, array( 'review_reminder', 'review_reminder_2', 'review_discount' ) ) ) {
				if( 'no' === get_option( 'ivole_verified_reviews', 'no' )  ) {
					// Advanced email template editor
					$fields[3] = array(
						'title' => __( 'Advanced Email Template', 'customer-reviews-woocommerce-pro' ),
						'type' => 'crpro_advanced_email_template',
						'desc' => __( 'Customize emails with a visual template editor.', 'customer-reviews-woocommerce-pro' ),
						'desc_tip' => true,
						'custom_attributes' => array( $email_type )
					);

					// add a setting for an unsubscribe page
					$fields[63] = array(
						'title' => __( 'Unsubscribe page', 'customer-reviews-woocommerce-pro' ),
						'type' => 'single_select_page_with_search',
						'id' => 'ivole_unsubscribe_page',
						'desc' => __( 'Set a page where customers will be taken to after clicking on an unsubscribe link in an email. Please add [cusrev_unsubscribe] shortcode to that page. If you use a caching plugin, it is recommended to exclude the unsubscribe page from caching.', 'customer-reviews-woocommerce-pro' ),
						'default' => '',
						'class'    => 'wc-page-search',
						'css'      => 'min-width:300px;',
						'args'     => array(
							'exclude' =>
								array(
									wc_get_page_id( 'checkout' ),
									wc_get_page_id( 'myaccount' ),
								),
						),
						'desc_tip' => true,
						'autoload' => false
					);

					$setting_name = self::get_setting_name( $email_type );
					$email_template = get_option( $setting_name, array() );
					if( isset( $email_template[ 'enabled' ] ) && $email_template[ 'enabled' ] ) {
						// hide basic settings for form templates
						unset( $fields[35] );
						unset( $fields[40] );
						unset( $fields[45] );
						unset( $fields[50] );
						unset( $fields[55] );
						unset( $fields[60] );
					}
				}
			}
			return $fields;
		}

		public function email_template_php_settings( $display, $email_type ) {
			$setting_name = self::get_setting_name( $email_type );
			$email_template = get_option( $setting_name, array() );
			if( isset( $email_template[ 'enabled' ] ) && $email_template[ 'enabled' ] ) {
				$display = false;
			}
			return $display;
		}

		public function show_advanced_email_template( $field ) {
			$setting_name = '';
			$setting_status = esc_html( __( 'Disabled', 'customer-reviews-woocommerce-pro' ) );
			$setting_class = ' crpro-settings-status-disabled';
			$editor_section = '';
			if(
				isset( $field['custom_attributes'] ) &&
				is_array( $field['custom_attributes'] ) &&
				0 < count( $field['custom_attributes'] )
			) {
				$setting_name = self::get_setting_name( $field['custom_attributes'][0] );
				$editor_section = $field['custom_attributes'][0] . '_editor';
			}
			if( $setting_name ) {
				$email_template = get_option( $setting_name, array() );
				if( isset( $email_template[ 'enabled' ] ) && $email_template[ 'enabled' ] ) {
					$setting_status = esc_html( __( 'Enabled', 'customer-reviews-woocommerce-pro' ) );
					$setting_class = ' crpro-settings-status-enabled';
				}
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<div class="cr-settings-div-label"><?php echo esc_html( $field['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc'] ); ?>"></span>
					</div>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) ?>">
					<div>
						<span class="cr-settings-manage-span<?php esc_attr_e( $setting_class ); ?>"><?php echo $setting_status; ?></span>
						<a class="button cr-settings-manage-button" href="<?php echo esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=' . $editor_section ) ); ?>"><?php echo esc_html( __( 'Customize', 'customer-reviews-woocommerce-pro' ) ); ?></a>
					</div>
				</td>
			</tr>
			<?php
		}

		public function display_email_editor( $section, $section_slug ) {
			if( 'review_reminder_editor' === $section_slug ) {
				$GLOBALS['hide_save_button'] = true;

				ob_start();

				echo '<h2>' . esc_html( __( 'Review Reminder Email Editor', 'customer-reviews-woocommerce-pro' ) );
				wc_back_link( __( 'Return to settings', 'customer-reviews-woocommerce-pro' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=review_reminder' ) );
				echo '</h2>';
				echo '<p>' . esc_html( __( 'A visual editor to customize email templates for review reminders. It should be used when reviews are collected without third-party verification.', 'customer-reviews-woocommerce-pro' ) ) . '</p>';
				echo '<div class="crpro-email-editor-pace"></div>';
				echo '<input type="hidden" id="crpro_email_editor_nonce" data-nonce="' . wp_create_nonce( 'crpro-email-editor' ) . '" />';
				echo '<input type="hidden" id="crpro_email_editor_type" data-type="review-reminder" />';
				echo '<div id="crpro_email_editor"></div>';

				$section = ob_get_clean();
			} elseif( 'review_reminder_2_editor' === $section_slug ) {
				$GLOBALS['hide_save_button'] = true;

				ob_start();

				echo '<h2>' . esc_html( __( 'Second Reminder Email Editor', 'customer-reviews-woocommerce-pro' ) );
				wc_back_link( __( 'Return to settings', 'customer-reviews-woocommerce-pro' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=review_reminder_2' ) );
				echo '</h2>';
				echo '<p>' . esc_html( __( 'A visual editor to customize email templates for review reminders. It should be used when reviews are collected without third-party verification.', 'customer-reviews-woocommerce-pro' ) ) . '</p>';
				echo '<div class="crpro-email-editor-pace"></div>';
				echo '<input type="hidden" id="crpro_email_editor_nonce" data-nonce="' . wp_create_nonce( 'crpro-email-editor' ) . '" />';
				echo '<input type="hidden" id="crpro_email_editor_type" data-type="review-reminder-2" />';
				echo '<div id="crpro_email_editor"></div>';

				$section = ob_get_clean();
			} elseif( 'review_discount_editor' === $section_slug ) {
				$GLOBALS['hide_save_button'] = true;

				ob_start();

				echo '<h2>' . esc_html( __( 'Review for Discount Email Editor', 'customer-reviews-woocommerce-pro' ) );
				wc_back_link( __( 'Return to settings', 'customer-reviews-woocommerce-pro' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails&section=review_discount' ) );
				echo '</h2>';
				echo '<p>' . esc_html( __( 'A visual editor to customize templates of emails with discounts.', 'customer-reviews-woocommerce-pro' ) ) . '</p>';
				echo '<div class="crpro-email-editor-pace"></div>';
				echo '<input type="hidden" id="crpro_email_editor_nonce" data-nonce="' . wp_create_nonce( 'crpro-email-editor' ) . '" />';
				echo '<input type="hidden" id="crpro_email_editor_type" data-type="coupon-after-review" />';
				echo '<div id="crpro_email_editor"></div>';

				$section = ob_get_clean();
			}
			return $section;
		}

		private static function get_setting_name( $email_type ) {
			$name = '';
			switch( $email_type ) {
				case 'review_reminder':
					$name = 'ivole_email_review_reminder';
					break;
				case 'review_reminder_2':
					$name = 'ivole_email_review_reminder_2';
					break;
				case 'review_discount':
					$name = 'ivole_email_review_discount';
					break;
				default:
					break;
			}
			return $name;
		}

		// display information about [cusrev_unsubscribe] on the settings page with shortcodes
		public function shortcodes_settings( $shortcodes_desc ) {
			if( 'no' === get_option( 'ivole_verified_reviews', 'no' )  ) {
				$unsubscribe_desc = '<br><p class="cr-admin-shortcodes-large"><code>[cusrev_unsubscribe]</code></p>' .
				'<p>' . __( 'Use this shortcode to display a form that customers can use to unsubscribe from review reminders and other emails sent by the plugin. If you use a caching plugin, it is recommended to exclude the unsubscribe page from caching to prevent expiration of security nonces. Here are the default parameters of the shortcode:', 'customer-reviews-woocommerce-pro' ) . '</p>' .
				'<p class="cr-admin-shortcodes"><code>[cusrev_unsubscribe]</code></p>' .
				'<p class="cr-admin-shortcodes"><b>' . __( 'Parameters:', 'customer-reviews-woocommerce-pro' ) . '</b></p>' .
				'<ul>' .
				'<li>' . __( 'This shortcode does not have parameters.', 'customer-reviews-woocommerce-pro' ) . '</li>' .
				'</ul>';
				$shortcodes_desc .= $unsubscribe_desc;
			}
			return $shortcodes_desc;
		}

		public function email_template_title( $title, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$title = __( 'Second Reminder', 'customer-reviews-woocommerce-pro' );
			}
			return $title;
		}

		public function email_template_description( $description, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$description = __( 'A second review reminder can be sent as a follow-up if the customer does not leave a review after the first reminder.', 'customer-reviews-woocommerce-pro' );
			}
			return $description;
		}

		public function email_template_enabled( $enabled, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$delay_option = get_option( 'ivole_delay', 5 );
				if ( is_array( $delay_option ) && 1 < count( $delay_option ) ) {
					$second_reminder = $delay_option[1];
					if ( isset( $second_reminder['enabled'] ) && $second_reminder['enabled'] ) {
						$enabled = true;
					}
				}
			}
			return $enabled;
		}

		public static function email_template_subject( $subject, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$subject = get_option(
					'ivole_email_subject_2',
					self::email_subject_default( '', $template )
				);
			}
			return $subject;
		}

		public static function email_template_heading( $heading, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$heading = get_option(
					'ivole_email_heading_2',
					self::email_heading_default( '', $template )
				);
			}
			return $heading;
		}

		public static function email_template_body( $body, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$body = get_option(
					'ivole_email_body_2',
					self::email_body_default( '', $template )
				);
			}
			return $body;
		}

		public function email_template_from( $from, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$from = CR_Email_Template::get_from_address();
			}
			return $from;
		}

		public function email_mailer_id( $templates ) {
			if ( is_array( $templates ) ) {
				$templates[] = 'review_reminder_2';
			}
			return $templates;
		}

		public function email_mailer_options( $options, $template, $verified ) {
			if ( 'review_reminder_2' === $template ) {
				if ( $verified ) {
					$options = array(
						'cr' => __( 'CusRev (AWS SES)', 'customer-reviews-woocommerce-pro' )
					);
				} else {
					$options = array(
						'wp' => __( 'WordPress Default', 'customer-reviews-woocommerce-pro' )
					);
				}
			}
			return $options;
		}

		public function email_reply_to( $templates ) {
			if ( is_array( $templates ) ) {
				$templates[] = 'review_reminder_2';
			}
			return $templates;
		}

		public function email_bcc( $templates ) {
			if ( is_array( $templates ) ) {
				$templates[] = 'review_reminder_2';
			}
			return $templates;
		}

		public function email_subject_id( $id, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$id = 'ivole_email_subject_2';
			}
			return $id;
		}

		public static function email_subject_default( $default, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$default = '[{site_title}] ' . __( 'Your Feedback Matters! Quick Review Reminder', 'customer-reviews-woocommerce-pro' );
			}
			return $default;
		}

		public function email_heading_id( $id, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$id = 'ivole_email_heading_2';
			}
			return $id;
		}

		public static function email_heading_default( $default, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$default = 'Share Your Feedback';
			}
			return $default;
		}

		public function email_body_id( $id, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$id = 'ivole_email_body_2';
			}
			return $id;
		}

		public static function email_body_default( $default, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$default = CRPRO_Local_Emails::$def_secnd_rmdr_body;
			}
			return $default;
		}

		public function email_body_variables( $variables, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$variables = array(
					'<code>{site_title}</code> - ' . __( 'The title of your WordPress website.', 'customer-reviews-woocommerce-pro' ),
					'<code>{customer_first_name}</code> - ' . __( 'The first name of the customer who purchased from your store.', 'customer-reviews-woocommerce-pro' ),
					'<code>{customer_last_name}</code> - ' . __( 'The last name of the customer who purchased from your store.', 'customer-reviews-woocommerce-pro' ),
					'<code>{customer_name}</code> - ' . __( 'The full name of the customer who purchased from your store.', 'customer-reviews-woocommerce-pro' ),
					'<code>{coupon_code}</code> - ' . __( 'The code of coupon for discount.', 'customer-reviews-woocommerce-pro' ),
					'<code>{discount_amount}</code> - ' . __( 'Amount of the coupon (e.g., $10 or 11% depending on type of the coupon).', 'customer-reviews-woocommerce-pro' )
				);
			}
			return $variables;
		}

		public function email_file_name( $file_name, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$file_name = 'email-review-reminder-2.php';
			}
			return $file_name;
		}

		public function email_template_base( $template_base, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$template_base = dirname( dirname( __FILE__ ) ) . '/templates/';
			}
			return $template_base;
		}

		public function email_colors( $templates ) {
			if ( is_array( $templates ) ) {
				$templates[] = 'review_reminder_2';
			}
			return $templates;
		}

		public function email_color_1_id( $id, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$id = 'ivole_email_color_bg_2';
			}
			return $id;
		}

		public function email_color_1_desc( $desc, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$desc = __( 'Background color for heading of the email and review button.', 'customer-reviews-woocommerce-pro' );
			}
			return $desc;
		}

		public function email_color_1_class( $class, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$class = 'cr_email_color_bg';
			}
			return $class;
		}

		public function email_color_2_id( $id, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$id = 'ivole_email_color_text_2';
			}
			return $id;
		}

		public function email_color_2_desc( $desc, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$desc = __( 'Text color for heading of the email and review button.', 'customer-reviews-woocommerce-pro' );
			}
			return $desc;
		}

		public function email_color_2_class( $class, $template ) {
			if ( 'review_reminder_2' === $template ) {
				$class = 'cr_email_color_text';
			}
			return $class;
		}

		public function email_footer( $templates ) {
			if ( is_array( $templates ) ) {
				$templates[] = 'review_reminder_2';
			}
			return $templates;
		}

		public function send_test_email( $result, $template, $email ) {
			if ( 'review_reminder_2' === $template ) {
				$e = new Ivole_Email( 0, 2 );
				$result = $e->trigger2( null, $email, false );
			}
			return $result;
		}

		public function email_save_fields( $post ) {
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_body_2'] ) ) {
				if ( empty( preg_replace( '#\s#isUu', '', html_entity_decode( $_POST['ivole_email_body_2'] ) ) ) ) {
					WC_Admin_Settings::add_error( __( '\'Email Body\' field cannot be empty', 'customer-reviews-woocommerce-pro' ) );
					$_POST['ivole_email_body_2'] = get_option( 'ivole_email_body_2' );
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_bg_2'] ) ) {
				if ( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_bg_2'] ) ) {
					$_POST['ivole_email_color_bg_2'] = '#0f9d58';
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_text_2'] ) ) {
				if ( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_text_2'] ) ) {
					$_POST['ivole_email_color_text_2'] = '#ffffff';
				}
			}
		}

		public function save_email_body( $value, $option, $raw_value ) {
			return wp_kses_post( $raw_value );
		}

	}

endif;
