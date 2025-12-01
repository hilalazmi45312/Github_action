<?php
/**
 * Handles the functionality of customer emails
 *
 * @package Order Status Manager
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Customer_Email')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Customer_Email extends WC_Email {

		protected $map = array();
		public function __construct() {
			// Override the necessary class members of WC_Email.
			$this->map            = $this->get_email_status_data();
			$this->id             = 'custom_order_statuses_customers_emails';
			$this->customer_email = true;
			$this->title          = __('Custom Order Status Emails', 'addify_osm');
			$this->description    = __('Custom order status change notification will be sent to customer when order status has been changed.', 'addify_osm');
			$this->placeholders   = array(
				'{site_title}'   => $this->get_blogname(),
				'{order_date}'   => '',
				'{order_status}' => '',
				'{order_number}' => '',
				'{first_name}'   => '',
				'{last_name}'    => '',

			);
			$this->template_base  = OSM_PLUGIN_DIR;
			$this->template_html  = 'templates/emails/customer-custom-order-status.php';
			$this->template_plain = 'templates/emails/plain/customer-custom-order-status.php';


			if (is_array($this->map) || is_object($this->map)) {
				foreach ($this->map as $key => $status_email) {

					$to_status = $status_email['to_status'];
					// Exclude 'wc-'.
					$to_status = substr($to_status, strpos($to_status, '-') + 1);
					if ('customer' == $status_email['email_recipient']) {
						if ('any' == $status_email['from_status']) { // Check if from status is any.

							add_action('woocommerce_order_status_' . $to_status . '_email_notification', array( $this, 'trigger' ), 99, 2);
						} else {
							$flag        = true;
							$from_status = $status_email['from_status'];
							// Exclude 'wc-.
							$from_status = substr($from_status, strpos($from_status, '-') + 1);
							if (is_array($this->get_auto_status_data()) || is_object($this->get_auto_status_data())) {
								foreach ($this->get_auto_status_data() as $rule_data) {


									// Check if automation statuses matches with order to and from statuses.
									if ($rule_data['rule_from'] == $status_email['from_status'] && $rule_data['rule_to'] == $status_email['to_status']) {
										// Check if disable customer email notification is enabled.
										if ('yes' == $rule_data['customer_notify']) {
											$flag = false;
										}
									}
								}
							}

							if (true == $flag) {
								add_action('woocommerce_order_status_' . $from_status . '_to_' . $to_status . '_email_notification', array( $this, 'trigger' ), 99, 2);
							}
						}
					}
				}
			}
			parent::__construct();
			// Change email subject.
			add_filter('woocommerce_email_subject_custom_order_statuses_customers_emails', array( $this, 'change_email_subject' ), 10, 2);
			// Change email heading.
			add_filter('woocommerce_email_heading_custom_order_statuses_customers_emails', array( $this, 'change_email_heading' ), 10, 2);
			// Change email additional content.
			add_filter('woocommerce_email_additional_content_custom_order_statuses_customers_emails', array( $this, 'change_email_content' ), 10, 2);
		}
		/**
		 * Override default subject
		 */

		public function get_default_subject() {
			return __('Your order from {site_title} on {order_date} status is currently, {order_status}.', 'addify_osm');
		}
		/**
		 * Override default heading
		 */
		public function get_default_heading() {

			return __('The status of your order is {order_status}', 'addify_osm');
		}

		/**
		 * Override default content
		 */
		public function get_default_additional_content() {
			return __('Thanks for ordering from {site_title}.', 'addify_osm');
		}

		/**
		 * Trigger email
		 *
		 * @param mixed $order_id args.
		 * @param bool $order args.
		 * 
		 */
		public function trigger( $order_id, $order = false ) {

			$this->setup_locale();
			if ($order_id && !is_a($order, 'WC_Order')) {
				$order = wc_get_order($order_id);
			}

			if (is_a($order, 'WC_Order')) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{first_name}']   = $this->object->get_billing_first_name();
				$this->placeholders['{last_name}']    = $this->object->get_billing_last_name();
				$this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
				$this->placeholders['{order_status}'] = wc_get_order_status_name('wc-' . $this->object->get_status());

				if ($this->is_enabled() && $this->get_recipient()) {
					$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
				}

				$this->restore_locale();
			}
		}

		/**
		 * Override html template
		 */
		public function get_content_html() {
			return wc_get_template_html($this->template_html, array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			), '', $this->template_base);
		}

		/**
		 * Override plain template
		 */
		public function get_content_plain() {
			return wc_get_template_html($this->template_plain, array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			), '', $this->template_base);
		}
		/**
		 * Initialize form fields
		 */
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __('Enable/Disable', 'addify_osm'),
					'type'    => 'checkbox',
					'label'   => 'Enable this email notification',
					'default' => 'yes',
				),
				'subject'            => array(
					'title'       => __('Subject', 'addify_osm'),
					'type'        => 'text',
					/* translators: %s: search term */
					'description' => sprintf(__('Available placeholders: %s', 'addify_osm'), '<code>{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}</code>'),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __('Email Heading', 'addify_osm'),
					'type'        => 'text',
					/* translators: %s: search term */
					'description' => sprintf(__('Available placeholders: %s', 'addify_osm'), '<code>{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}</code>'),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __('Email Body', 'addify_osm'),
					/* translators: %s: search term */
					'description' => sprintf(__('Available placeholders: %s', 'addify_osm'), '<code>{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}</code>'),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => $this->get_default_additional_content(),
					'type'        => 'textarea',
					'default'     => '',
				),
				'email_type'         => array(
					'title'       => __('Email type', 'addify_osm'),
					'type'        => 'select',
					'description' => __('Choose which format of email to send.', 'addify_osm'),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}
		/**
		 * Function to set email subject defined by user
		 *
		 * @param mixed $subject args.
		 * @param mixed $order args.
		 */
		public function change_email_subject( $subject, $order ) {

			$this->placeholders['{first_name}']   = $order->get_billing_first_name();
			$this->placeholders['{last_name}']    = $order->get_billing_last_name();
			$this->placeholders['{order_date}']   = wc_format_datetime($order->get_date_created());
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_status}'] = wc_get_order_status_name('wc-' . $order->get_status());

			foreach ($this->map as $key => $email) {
				$to_status = $email['to_status'];
				// Exclude 'wc-'. 
				$to_status = substr($to_status, strpos($to_status, '-') + 1);
				if ($order->get_status() == $to_status) {
					if (!empty($email['email_subject'])) {
						$subject = $this->format_string($email['email_subject']);
					}
				}
			}

			return $subject;
		}

		/**
		 * Function to set email heading defined by user
		 *
		 * @param mixed $heading args.
		 * @param mixed $order args.
		 */
		public function change_email_heading( $heading, $order ) {
			$this->placeholders['{first_name}']   = $order->get_billing_first_name();
			$this->placeholders['{last_name}']    = $order->get_billing_last_name();
			$this->placeholders['{order_date}']   = wc_format_datetime($order->get_date_created());
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_status}'] = wc_get_order_status_name('wc-' . $order->get_status());

			foreach ($this->map as $key => $email) {
				$to_status = $email['to_status'];
				// Exclude 'wc-'.
				$to_status = substr($to_status, strpos($to_status, '-') + 1);
				if ($order->get_status() == $to_status) {
					if (!empty($email['email_heading'])) {
						$heading = $this->format_string($email['email_heading']);
					}
				}
			}

			return $heading;
		}

		/**
		 * Function to set email content defined by user
		 *
		 * @param mixed $content args.
		 * @param mixed $order args.
		 */
		public function change_email_content( $content, $order ) {
			$this->placeholders['{first_name}']   = $order->get_billing_first_name();
			$this->placeholders['{last_name}']    = $order->get_billing_last_name();
			$this->placeholders['{order_date}']   = wc_format_datetime($order->get_date_created());
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_status}'] = wc_get_order_status_name('wc-' . $order->get_status());

			foreach ($this->map as $key => $email) {
				$to_status = $email['to_status'];
				// Exclude 'wc-'.
				$to_status = substr($to_status, strpos($to_status, '-') + 1);
				if ($order->get_status() == $to_status) {
					if (!empty($email['custom_msg'])) {
						$content = $this->format_string($email['custom_msg']);
					}
				}
			}

			return $content;
		}

		/**
		 * Function to get data from email status
		 */
		public function get_email_status_data() {
			$order_status_emails = array();
			$arg                 = array(
				'numberposts' => -1,
				'post_type'   => 'status_emails',
				'post_status' => 'publish',
				'fields'      => 'ids',
			);
			$status_emails       = get_posts($arg);
			if (!empty($status_emails)) {
				foreach ($status_emails as $key =>  $status_email) {
					$order_status_emails[ $key ]['to_status']       = get_post_meta($status_email, 'osm_to_select', true);
					$order_status_emails[ $key ]['from_status']     = get_post_meta($status_email, 'osm_from_select', true);
					$order_status_emails[ $key ]['email_recipient'] = get_post_meta($status_email, 'osm_select_recipient', true);
					$order_status_emails[ $key ]['email_subject']   = get_post_meta($status_email, 'osm_email_subject', true);
					$order_status_emails[ $key ]['email_heading']   = get_post_meta($status_email, 'osm_email_heading', true);
					$order_status_emails[ $key ]['custom_msg']      = get_post_meta($status_email, 'osm_email_custom_msg', true);
				}
				return $order_status_emails;
			}
		}

		/**
		 * Function to get data from automation rules
		 */
		public function get_auto_status_data() {
			$order_status_emails = array();
			$arg                 = array(
				'numberposts' => -1,
				'post_type'   => 'status_automation',
				'post_status' => 'publish',
				'fields'      => 'ids',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			);
			$status_rules        = get_posts($arg);
			if (!empty($status_rules)) {
				foreach ($status_rules  as $key =>  $status_rule) {
					$order_status_emails[ $key ]['customer_notify'] = get_post_meta($status_rule, 'osm_disable_customer_notification', true);
					$order_status_emails[ $key ]['rule_from']       = get_post_meta($status_rule, 'osm_auto_from_select', true);
					$order_status_emails[ $key ]['rule_to']         = get_post_meta($status_rule, 'osm_auto_to_select', true);
				}
				return $order_status_emails;
			}
		}
	}

	return new KA_Osm_Customer_Email();
}
