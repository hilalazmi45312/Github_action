<?php

/**
 * Process Automation Rules
 *
 * @package Order Status Manager
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Process_Auto_Rules')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Process_Auto_Rules {
		/**
		 * Constructor of the class
		 */
		public function __construct() {

			add_action('schedule_status_custom_status_hook', array( $this, 'schedule_change_order_status' ));

			add_filter('cron_schedules', array( $this, 'add_custom_cron_interval' ));
			// Check if cron is disabled.
			add_action('admin_notices', array( $this, 'check_cron_is_disabled' ));
		}
		/**
		 * Function to add custom cron interval
		 */
		public function add_custom_cron_interval( $schedules ) {
			$_type = get_option('kaosm_time_type');
			$_time = get_option('kaosm_cron_time');

			$cron_time = 15 * 60;

			switch ($_type) {
				case 'minutes':
					$cron_time = $_time * 60;
					break;
				case 'hours':
					$cron_time = $_time * 60 * 60;
					break;
				case 'days':
					$cron_time = $_time * 60 * 60 * 24;
					break;
				default:
					$cron_time = $_time;
					break;
			}

			$schedules['kaosm_cron_schedule'] = array(
				'interval' => $cron_time,
				'display'  => 'Order Status Cron Schedule',
			);

			return $schedules;
		}

		/**
		 * Function to change order status after certain time interval
		 */
		public function schedule_change_order_status() {
			//Create a new post.
			if (is_array($this->get_automation_rules_data()) || is_object($this->get_automation_rules_data())) {
				foreach ($this->get_automation_rules_data() as $key => $auto_rules) {
					$auto_to_status   = $auto_rules['auto_to_status'];
					$auto_from_status = $auto_rules['auto_from_status'];
					$auto_unit        = $auto_rules['auto_unit'];
					$auto_time        = $auto_rules['auto_time'];

					if ('minute' == $auto_unit) {
						$time_trigger = strtotime('- ' . $auto_time . 'minutes');
					}
					if ('hour' == $auto_unit) {
						$time_trigger = strtotime('- ' . $auto_time . 'hours');
					}
					if ('day' == $auto_unit) {
						$time_trigger = strtotime('- ' . $auto_time . 'days');
					}

					$args = array(
						'status'       => array( $auto_from_status ),
						'limit'        => -1,
						'order'        => 'DESC',
						'return'       => 'ids',
						'date_created' => '<' . $time_trigger,
					);


					$orders = wc_get_orders($args);

					// If orders exists then proceed further.
					if ($orders) {
						foreach ($orders as $order_id) {
							$order = wc_get_order($order_id);
							// Exclude wc- for from status and to status.
							$new_from_status = substr($auto_from_status, strpos($auto_from_status, '-') + 1);
							$new_to_status   = substr($auto_to_status, strpos($auto_to_status, '-') + 1);
							// If status order status matches with the from status then update it with to status.
							if ($order->get_status() == $new_from_status) {

								// Check Order Conditions.
								if ($this->check_user($order, $auto_rules['user_roles']) && $this->check_max_order_quantity($order, $auto_rules['maximum_quantity']) && $this->check_min_order_quantity($order, $auto_rules['minimum_quantity']) && $this->check_min_amount($order, $auto_rules['minimum_amount']) && $this->check_max_amount($order, $auto_rules['maximum_amount']) && $this->check_products_categories($order, $auto_rules['order_products'], $auto_rules['order_category']) && $this->check_billing_countries($order, $auto_rules['billing_country']) && $this->check_shipping_countries($order, $auto_rules['shipping_country']) && $this->check_date_created_before($order, $auto_rules['created_before']) && $this->check_date_created_after($order, $auto_rules['created_after'])) {
									// Update Status.
									$updated_order = array(
										'ID'          => $order_id,
										'post_status' => $auto_to_status,
									);
									wp_update_post($updated_order);
									wc()->mailer();
									do_action('woocommerce_order_status_' . $new_from_status . '_to_' . $new_to_status . '_email_notification', $order_id);
								}
							}
						}
					}
				}
			}
		}
		/**
		 * Function to check user roles
		 *
		 * @param mixed $order args.
		 * @param mixed $selected_us args.
		 */

		public function check_user( $order, $selected_users ) {
			$user = $order->get_user();

			if ($user) {
				$user_roles = $user->roles;
			} else {
				$user_roles = array( 'guest' );
			}

			if (empty($selected_users) || array_intersect($user_roles, $selected_users)) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to check order quantity
		 *
		 * @param mixed $order args.
		 * @param mixed $selected_quantity args.
		 */
		public function check_max_order_quantity( $order, $selected_quantity ) {

			$order_quantity = $order->get_item_count();
			if (empty($selected_quantity) ||  $order_quantity <=  $selected_quantity) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to check order quantity
		 *
		 * @param mixed $order args.
		 * @param mixed $selected_quantity args.
		 */
		public function check_min_order_quantity( $order, $selected_quantity ) {

			$order_quantity = $order->get_item_count();
			if (empty($selected_quantity) ||  $order_quantity >=  $selected_quantity) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to check  order maximum and minimum amount
		 *
		 * @param mixed $order args.
		 * @param mixed $max_amount args.
		 * 
		 */
		public function check_max_amount( $order, $max_amount ) {
			$subtotal = $order->get_subtotal();

			if (( empty($max_amount) || $subtotal <= $max_amount )) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to check minimum amount
		 *
		 * @param mixed $order args.
		 * @param mixed $min_amount args.
		 */
		public function check_min_amount( $order, $min_amount ) {
			$subtotal = $order->get_subtotal();


			if (( empty($min_amount) || $subtotal >= $min_amount )) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Check order products, categories.
		 *
		 * @param mixed $order args.
		 * @param mixed $selected_products args.
		 * @param mixed $selected_cat args.
		 */

		public function check_products_categories( $order, $selected_products, $selected_cat ) {
			$items = $order->get_items();

			$product_flag = false;
			$cat_flag     = false;
			$empty_flag   = false;

			foreach ($items as $item) {
				if (!empty($selected_products) && in_array($item['product_id'], $selected_products)) {
					$product_flag = true;
				}
				if (!empty($selected_cat) && has_term($selected_cat, 'product_cat', $item['product_id'])) {
					$cat_flag = true;
				}
			}
			if (empty($selected_products) && empty($selected_cat)) {
				$empty_flag = true;
			}
			if ($product_flag || $cat_flag || $empty_flag) {
				return true;
			} else {
				return false;
			}
		}

		/** Check order billing countries.
		 *
		 * @param mixed $order args.
		 * @param mixed $billing_country args.
		 * */
		public function check_billing_countries( $order, $billing_country ) {

			if (empty($billing_country) || in_array(( is_callable(array( $order, 'get_billing_country' ))  ? $order->get_billing_country()  : '' ), $billing_country)) {
				return true;
			} else {
				return false;
			}
		}
		/** 
		 * Check order shipping countries.
		 *
		 * @param mixed $order args.
		 * @param mixed $shipping_country args.
		 */
		public function check_shipping_countries( $order, $shipping_country ) {
			if (empty($shipping_country) || in_array(( is_callable(array( $order, 'get_shipping_country' ))  ? $order->get_shipping_country()  : '' ), $shipping_country)) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to Check date_created before
		 *
		 * @param mixed $order args.
		 * @param mixed $created_before args.
		 **/
		public function check_date_created_before( $order, $created_before ) {
			$date_created   = $order->get_date_created();
			$date_created   = $date_created->getTimestamp();
			$created_before = strtotime($created_before);
			if (empty($created_before) || $date_created < $created_before) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 *  Function to Check date_created after  
		 *
		 * @param mixed $order args.
		 * @param mixed $created_after args.
		 **/
		public function check_date_created_after( $order, $created_after ) {

			$date_created  = $order->get_date_created();
			$date_created  = $date_created->getTimestamp();
			$created_after = strtotime($created_after);
			if (empty($created_after) || $date_created > $created_after) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Function to check if cron is disabled
		 */
		public function check_cron_is_disabled() {
			if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {

				echo '<div class="notice notice-error"><p>' .
					wp_kses_post(sprintf(
						/* translators: 1: code constant, 2: URL */
						__('Crons (%1$s) are disabled on your server! Crons must be enabled for order status manager to be processed. You can read more <a target="_blank" href="%2$s">here</a>.', 'addify_osm'),
						'<code>DISABLE_WP_CRON</code>',
						esc_url('https://addify.co/how-to-enable-and-disable-cron-in-wordpress/')
					)) .
					'</p></div>';
			}
		}


		/**
		 * Function to get data from automation rules
		 */
		public function get_automation_rules_data() {


			$status_auto         = array();
			$args                = array(
				'numberposts' => -1,
				'post_type'   => 'status_automation',
				'post_status' => 'publish',
				'fields'      => 'ids',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			);
			$statuses_automation = get_posts($args);

			if (!empty($statuses_automation)) {
				foreach ($statuses_automation as $key =>  $auto_id) {
					$status_auto[ $key ]['auto_from_status'] = get_post_meta($auto_id, 'osm_auto_from_select', true);
					$status_auto[ $key ]['auto_to_status']   = get_post_meta($auto_id, 'osm_auto_to_select', true);
					$status_auto[ $key ]['auto_time']        = get_post_meta($auto_id, 'osm_auto_time', true);
					$status_auto[ $key ]['auto_unit']        = get_post_meta($auto_id, 'osm_unit_select', true);
					$status_auto[ $key ]['user_roles']       = get_post_meta($auto_id, 'osm_select_user_roles', true);
					$status_auto[ $key ]['maximum_quantity'] = get_post_meta($auto_id, 'osm_order_max_quantity', true);
					$status_auto[ $key ]['minimum_quantity'] = get_post_meta($auto_id, 'osm_order_min_quantity', true);
					$status_auto[ $key ]['maximum_amount']   = get_post_meta($auto_id, 'osm_maximum_amount', true);
					$status_auto[ $key ]['minimum_amount']   = get_post_meta($auto_id, 'osm_minimum_amount', true);
					$status_auto[ $key ]['order_products']   = get_post_meta($auto_id, 'osm_select_product', true);
					$status_auto[ $key ]['order_category']   = get_post_meta($auto_id, 'osm_select_category', true);
					$status_auto[ $key ]['billing_country']  = get_post_meta($auto_id, 'osm_select_billing_country', true);
					$status_auto[ $key ]['shipping_country'] = get_post_meta($auto_id, 'osm_select_shipping_country', true);
					$status_auto[ $key ]['created_before']   = get_post_meta($auto_id, 'osm_date_created_before', true);
					$status_auto[ $key ]['created_after']    = get_post_meta($auto_id, 'osm_date_created_after', true);
				}
				return $status_auto;
			}
		}
	}
	new KA_Osm_Process_Auto_Rules();
}
