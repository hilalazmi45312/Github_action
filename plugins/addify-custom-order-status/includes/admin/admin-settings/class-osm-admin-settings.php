<?php
/**
 * Settings to organize all custom post types and admin settings in tabs
 *
 * @package Order Status Manager
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Admin_Settings')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Admin_Settings {
		public function __construct() {

			add_action('admin_menu', array( $this, 'remove_submenu_link' ), 100);

			add_action('all_admin_notices', array( $this, 'osm_render_tabs' ), 5);
		}
		/**
		 * Function to render tabs
		 */
		public function osm_render_tabs() {
			global $post, $typenow;
			$screen = get_current_screen();

			// Handle tabs on the relevant WooCommerce pages.
			if ($screen && in_array($screen->id, $this->get_tab_screen_ids(), true)) {

				$tabs = apply_filters('ka_order_status_manager_tabs', array(
					'status'     => array(
						'title' => __('Order Statuses', 'addify_osm'),
						'url'   => admin_url('edit.php?post_type=order_status'),
					),
					'emails'     => array(
						'title' => __('Status Emails', 'addify_osm'),
						'url'   => admin_url('edit.php?post_type=status_emails'),
					),
					'automation' => array(
						'title' => __('Automation Rules', 'addify_osm'),
						'url'   => admin_url('edit.php?post_type=status_automation'),
					),
					'cron'       => array(
						'title' => __('Cron Job Settings', 'addify_osm'),
						'url'   => admin_url('admin.php?page=addify_osm_cron_setting'),
					),
				));

				if (is_array($tabs)) { ?>
					<div class="wrap woocommerce">
						<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
							<?php
							$current_tab = $this->get_current_tab();

							foreach ($tabs as $id => $tab_data) {
								$class = $id === $current_tab ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' );
								printf('<a href="%1$s" class="%2$s">%3$s</a>', esc_url($tab_data['url']), implode(' ', array_map('sanitize_html_class', $class)), esc_html($tab_data['title']));
							}
							?>
						</h2>
					</div>
					<?php
				}
			}
		}
		/**
		 * Function to get tab screen ids
		 */
		public function get_tab_screen_ids() {
			$tabs_screens = array(
				'order_status',
				'edit-order_status',
				'status_emails',
				'edit-status_emails',
				'status_automation',
				'edit-status_automation',
				'woocommerce_page_addify_osm_cron_setting',
			);

			return $tabs_screens;
		}
		/**
		 * Function to get current tabs
		 */
		public function get_current_tab() {
			$screen = get_current_screen();


			switch ($screen->id) {
				case 'order_status':
				case 'edit-order_status':
					return 'status';
				case 'status_emails':
				case 'edit-status_emails':
					return 'emails';
				case 'status_automation':
				case 'edit-status_automation':
					return 'automation';
				case 'woocommerce_page_addify_osm_cron_setting':
					return 'cron';
			}
		}

		/** 
		 * Function to remove submenu link
		 */

		public function remove_submenu_link() {
			global $pagenow, $typenow;

			if (( 'edit.php' === $pagenow && 'order_status' === $typenow )
				|| ( 'post.php' === $pagenow && isset($_GET['post']) && 'order_status' === get_post_type(sanitize_text_field($_GET['post'])) )
			) {
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_emails');
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_automation');
				remove_submenu_page('woocommerce', 'addify_osm_cron_setting');
			} elseif (( 'edit.php' === $pagenow && 'status_emails' === $typenow )
				|| ( 'post.php' === $pagenow && isset($_GET['post']) && 'status_emails' === get_post_type(sanitize_text_field($_GET['post'])) )
			) {

				remove_submenu_page('woocommerce', 'edit.php?post_type=order_status');
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_automation');
				remove_submenu_page('woocommerce', 'addify_osm_cron_setting');
			} elseif (( 'edit.php' === $pagenow && 'status_automation' === $typenow )
				|| ( 'post.php' === $pagenow && isset($_GET['post']) && 'status_automation' === get_post_type(sanitize_text_field($_GET['post'])) )
			) {
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_emails');
				remove_submenu_page('woocommerce', 'edit.php?post_type=order_status');
				remove_submenu_page('woocommerce', 'addify_osm_cron_setting');
			} elseif (( 'admin.php' === $pagenow && isset($_GET['page']) && 'addify_osm_cron_setting' === sanitize_text_field($_GET['page']) )) {

				remove_submenu_page('woocommerce', 'edit.php?post_type=status_emails');
				remove_submenu_page('woocommerce', 'edit.php?post_type=order_status');
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_automation');
			} else {
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_emails');
				remove_submenu_page('woocommerce', 'edit.php?post_type=status_automation');
				remove_submenu_page('woocommerce', 'addify_osm_cron_setting');
			}
		}
	}
	new KA_Osm_Admin_Settings();
}
