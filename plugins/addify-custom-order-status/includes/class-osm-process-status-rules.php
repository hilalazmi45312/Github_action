<?php

/**
 * Process Order Status Rules
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Process_Status_Rules')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Process_Status_Rules {
		public function __construct() {
			// Enable cancel action in my account.
			add_filter('woocommerce_valid_order_statuses_for_cancel', array( $this, 'custom_order_statuses_for_cancel' ), 100, 2);
			// Make custom status paid.
			add_filter('woocommerce_order_is_paid_statuses', array( $this, 'custom_order_status_is_paid' ));
			// User can pay statuses.
			add_filter('woocommerce_valid_order_statuses_for_payment', array( $this, 'custom_order_status_valid_for_payment' ), 10, 2);
			// Include order status in reports.
			add_filter('woocommerce_reports_order_statuses', array( $this, 'display_custom_order_status_to_reports' ), 20, 1);
			// Change the color of custom status on order listing page.
			add_action('admin_footer', array( $this, 'change_custom_order_bg_color' ));
			// Show custom statuses to customer.
			add_filter('woocommerce_order_get_status', array( $this, 'hide_order_status' ), 10, 2);
		}

		/**
		 * Enable cancel action button on custom status change
		 */
		public function custom_order_statuses_for_cancel( $statuses, $order ) {
			if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
				foreach ($this->get_order_status_data() as $key => $status) {

					if ('yes' == $status['user_can_cancel']) {
						$statuses[] = $status['status_slug'];
					}
				}
			}
			return $statuses;
		}

		/**
		 * Function to add custom order statuses in the list of paid orders
		 */
		public function custom_order_status_is_paid( $statuses ) {
			if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
				foreach ($this->get_order_status_data() as $key => $status) {

					if ('osm_paid_status' == $status['osm_paid']) {
						$statuses[] = $status['status_slug'];
					}
				}
			}
			return $statuses;
		}

		/**
		 * Function to include orders in valid orders for payment list
		 */
		public function custom_order_status_valid_for_payment( $statuses, $order ) {
			if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
				foreach ($this->get_order_status_data() as $key => $status) {

					if ('osm_need_payment' == $status['osm_paid']) {
						$statuses[] = $status['status_slug'];
					}
				}
			}
			return $statuses;
		}

		/** 
		 * Function to hide custom order status from customer
		 */
		public function hide_order_status( $status, $order ) {
			
			if ($order) {
				if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
					foreach ($this->get_order_status_data() as $key => $custom_status) {
						if ($status == $custom_status['status_slug']) {

							if ( !is_admin() && !defined('DOING_CRON') && 'yes' == $custom_status['hide_status']) {
								$status = ' ';
							}
						}
					}
				}
				
			}
			return $status;
		}
		
		/**
		 * Function to include custom order status in reports
		 */
		public function display_custom_order_status_to_reports( $statuses ) {
			if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
				foreach ($this->get_order_status_data() as $key => $status) {

					if ('yes' == $status['display_in_report']) {
						$statuses[] = $status['status_slug'];
					}
				}
			}
			return $statuses;
		}

		/**
		 * Function to change the color of custom order status on orders page
		 */
		public function change_custom_order_bg_color() {
		
			if (is_array($this->get_order_status_data()) || is_object($this->get_order_status_data())) {
				foreach ($this->get_order_status_data() as $key => $status) {
					$slug       = $status['status_slug'];
					$bg_color   = $status['status_color'];
					$text_color = $status['status_text_color'];
					?>
					<style type="text/css">
				mark.status-<?php echo esc_attr($slug); ?> {
					background: <?php echo esc_attr($bg_color); ?> !important;
					color:<?php echo esc_attr($text_color); ?>!important;
					}
				</style>
				<?php
					
				}
			}
		}

		/**
		 * Function to get Order Status Data
		 */
		public function get_order_status_data() {
			$order_status          = array();
			$arg                   = array(
				'numberposts' => -1,
				'post_type'   => 'order_status',
				'post_status' => 'publish',
				'fields'      => 'ids',
			);
			$custom_order_statuses = get_posts($arg);
			if (!empty($custom_order_statuses)) {
				foreach ($custom_order_statuses as $key =>  $status_id) {
					$order_status[ $key ]['status_slug']       = get_post_meta($status_id, 'osm_slug', true);
					$order_status[ $key ]['status_color']      = get_post_meta($status_id, 'osm_color', true);
					$order_status[ $key ]['status_text_color'] = get_post_meta($status_id, 'osm_text_color', true);
					$order_status[ $key ]['user_can_cancel']   = get_post_meta($status_id, 'osm_user_can_cancel', true);
					$order_status[ $key ]['osm_paid']          = get_post_meta($status_id, 'osm_paid_select', true);
					$order_status[ $key ]['hide_status']       = get_post_meta($status_id, 'osm_hide_check', true);
					$order_status[ $key ]['display_in_report'] = get_post_meta($status_id, 'osm_report_check', true);
				}
				return $order_status;
			}
		}
	}
	new KA_Osm_Process_Status_Rules();
}
