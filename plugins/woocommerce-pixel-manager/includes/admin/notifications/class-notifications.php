<?php

namespace SweetCode\Pixel_Manager\Admin\Notifications;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Environment;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunities;
use SweetCode\Pixel_Manager\Helpers;

defined('ABSPATH') || exit; // Exit if accessed directly

class Notifications {

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action('admin_enqueue_scripts', array( __CLASS__, 'inject_admin_scripts' ));

		add_action('admin_enqueue_scripts', array( __CLASS__, 'wpm_admin_css' ));

		add_action('admin_notices', function () {
			if (Environment::is_allowed_notification_page()) {
				if (true) {
					self::license_expired_warning__premium_only();
				}

				self::opportunities_notification();
				self::show_notifications();
			}
		});
	}

	public static function inject_admin_scripts() {
		wp_enqueue_script(
			'pmw-notifications',
			PMW_PLUGIN_DIR_PATH . 'js/admin/notifications.js',
			array( 'jquery' ),
			PMW_CURRENT_VERSION,
			true
		);

		wp_localize_script(
			'pmw-notifications',
			'pmwNotificationsApi',
			array(
				'root'  => esc_url_raw(rest_url()),
				'nonce' => wp_create_nonce('wp_rest'),
			)
		);
	}

	public static function wpm_admin_css( $hook_suffix ) {

		// Only output the css on PMW pages and the order page
//      if (self::is_not_allowed_to_show_pmw_notification()) {
//          return;
//      }

		wp_enqueue_style(
			'pmw-notifications-css',
			PMW_PLUGIN_DIR_PATH . 'css/notifications.css',
			array(),
			PMW_CURRENT_VERSION
		);
	}

	public static function dismiss_notification( $opportunity_id ) {

		$option = get_option(PMW_DB_NOTIFICATIONS_NAME);

		if (empty($option)) {
			$option = array();
		}

		$option[$opportunity_id]['dismissed'] = time();

		update_option(PMW_DB_NOTIFICATIONS_NAME, $option);

		wp_send_json_success();
	}

	private static function show_notifications() {

		foreach (self::get_notifications() as $notification) {
			if ($notification::is_not_dismissed()) {
				$notification::output_notification();
			}
		}
	}

	private static function get_notifications() {

		self::load_all_notification_classes();

		$classes = get_declared_classes();

		$notifications = array();

		foreach ($classes as $class) {
			if (is_subclass_of($class, 'SweetCode\Pixel_Manager\Admin\Notifications\Notification')) {
				$notifications[] = $class;
			}
		}

		return $notifications;
	}

	// Don't make public, as this could be used for file inclusion attacks.
	private static function load_all_notification_classes() {

		$scan = glob(__DIR__ . '/*');
		foreach ($scan as $path) {
			if (preg_match('/\.php$/', $path)) {
				require_once $path;
			}
		}
	}

	// Only show the notification on the dashboard and on the PMW settings page
	private static function is_not_allowed_to_show_pmw_notification() {
		return !self::is_allowed_to_show_pmw_notification();
	}

	private static function is_allowed_to_show_pmw_notification() {

		if (!self::can_current_page_show_pmw_notification()) {
			return false;
		}

		// Only show the notifications to admins and shop managers
		$user = wp_get_current_user();
		if (!in_array('administrator', $user->roles, true) && !in_array('shop_manager', $user->roles, true)) {
			return false;
		}

		return true;
	}

	public static function can_current_page_show_pmw_notification() {

		global $hook_suffix;

		$allowed_pages = array( 'page_wpm', 'index.php', 'dashboard' );

		/**
		 * We can't use in_array because woocommerce_page_wpm
		 * is malformed on certain installs, but the substring
		 * page_wpm is fine. So we need to search for partial
		 * matches.
		 * */
		foreach ($allowed_pages as $allowed_page) {
			if (strpos($hook_suffix, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	public static function payment_gateway_accuracy_warning() {

		// Only show the warning on the dashboard and on the PMW settings page
		if (self::is_not_allowed_to_show_pmw_notification()) {
			return;
		}

		$pg_report = get_transient('pmw_tracking_accuracy_analysis_weighted');

		// Only run if the weighted payment gateway analysis has been created
		if (!$pg_report) {
			return;
		}

		// Only run if the total of the PGs is in status warning

		// Only run if the user has not dismissed the notification for a specific time period

		?>
		<div class="pmw-payment-gateway-notification notice notice-error is-dismissible">
			<p>
				<?php
				esc_html_e('Payment Gateway Accuracy Warning', 'woocommerce-google-adwords-conversion-tracking-tag');
				?>
			</p>
		</div>
		<?php
	}

	public static function license_expired_warning__premium_only() {

		if (true) {
			return;
		}

		if (!Helpers::is_dashboard()) {
			return;
		}

		$saved_notifications = get_option(PMW_DB_NOTIFICATIONS_NAME);

		// If saved date is in the future, then don't show
		if (
			isset($saved_notifications['dashboard-license-expired-message-dismissed'])
			&& $saved_notifications['dashboard-license-expired-message-dismissed'] > time() - MONTH_IN_SECONDS * 6
		) {
			return;
		}

		?>
		<div id="license-expired-warning" class="notice notice-error pmw license-expired-warning">
			<div>
				<div style="color:red; margin-top: 10px; ">
				<span>

					<?php
					esc_html_e('Your license for the Pixel Manager Pro for WooCommerce has been removed or has expired. Automatic updates and all premium features have been disabled.', 'woocommerce-google-adwords-conversion-tracking-tag');
					?>
				</span> <a href="<?php echo esc_url(Documentation::get_link('license_expired_warning')); ?>"
							target="_blank">
						<?php esc_html_e('More info', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</a><br>
				</div>

				<div id="pmw-purchase-new-license-button" class="button" style="margin: 10px 0 10px 0">

					<a href="<?php echo( esc_url_raw('//sweetcode.com/pricing') ); ?>" target="_blank"
						style="text-decoration: none;">
						<?php esc_html_e('Purchase a new license', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</a>
				</div>
			</div>

			<?php if (Helpers::is_dashboard()) : ?>
				<div style=" margin-bottom: 10px; text-align: right;">

					<div id="pmw-dismiss-license-expiry-message-button"
						class="button pmw-notification-dismiss-button"
						style="white-space:normal; margin-bottom: 6px"
						data-notification-id="dashboard-license-expired-message-dismissed"
					>
						<?php esc_html_e('Click here to dismiss this warning', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</div>
					<div class="pmw dismiss-link-info">
						<a href="<?php echo esc_url(Documentation::get_link('the_dismiss_button_doesnt_work_why')); ?>"
							target="_blank">
							<?php esc_html_e('If the dismiss button is not working, here\'s why >>', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<?php
	}

	public static function dismiss_expired_license_warning__premium_only() {

		$saved_notifications                                 = get_option(PMW_DB_NOTIFICATIONS_NAME);
		$saved_notifications['show_license_expired_warning'] = time() + MONTH_IN_SECONDS * 6;
		update_option(PMW_DB_NOTIFICATIONS_NAME, $saved_notifications);
		wp_send_json_success();
	}

	private static function can_show_dashboard_opportunities_message() {

		$saved_notifications = get_option(PMW_DB_NOTIFICATIONS_NAME);

		if (
			isset($saved_notifications['dashboard-opportunities-message-dismissed'])
			&& $saved_notifications['dashboard-opportunities-message-dismissed'] > time() - MONTH_IN_SECONDS * 3
		) {
			return false;
		}

		if (!Opportunities::active_opportunities_available()) {
			return false;
		}

		return true;
	}

	public static function notification_html( $notification_data ) {

		wp_enqueue_script(
			'wistia',
			'https://fast.wistia.com/assets/external/E-v1.js',
			array(),
			PMW_CURRENT_VERSION,
			false
		);

		?>
		<div class="notice notice-info pmw notification">

			<div id="pmw-notification-<?php echo esc_html($notification_data['id']); ?>">

				<!-- Pixel Manager title -->
				<div class="notification-title">
					<b><?php esc_html_e('Pixel Manager', 'woocommerce-google-adwords-conversion-tracking-tag'); ?></b>

					<!-- Dismiss Link -->
					<?php if (empty($notification_data['dismissed'])) : ?>
						<a class="notification-card-button-link" href="#">
							<div class="notification-dismiss"
								data-notification-id="<?php echo esc_html($notification_data['id']); ?>">
								<!-- Replace 'Dismiss' text with 'X' -->
								<span class="notification-dismiss-cross">&times;</span>
							</div>
						</a>
					<?php endif; ?>
					<!-- Dismiss Link end -->
				</div>

				<hr class="notification-card-hr">

				<!-- notification title -->
				<div class="notification-top">
					<div>
						<?php echo esc_html($notification_data['title']); ?>
					</div>
					<div class="importance-info">
						<span><?php esc_html_e('Importance', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>:</span>
						<span class="notification-card-top-impact-level">
							<?php echo esc_html($notification_data['importance']); ?>
						</span>
					</div>
				</div>

				<hr class="notification-card-hr">

				<!-- description -->
				<div class="notification-card-middle">

					<?php if (!empty($custom_middle_html)) : ?>
						<?php echo esc_html($custom_middle_html); ?>
					<?php else : ?>
						<?php foreach ($notification_data['description'] as $description) : ?>
							<p class="notification-card-description">
								<?php echo esc_html($description); ?>
							</p>
						<?php endforeach; ?>
					<?php endif; ?>

				</div>

				<hr class="notification-card-hr">

				<!-- bottom -->
				<div class="notification-card-bottom">

					<!-- Video Link -->
					<?php if (isset($notification_data['video_id'])) : ?>
						<div class="notification-video-link">
							<script>
								var script   = document.createElement("script")
								script.async = true
								script.src   = 'https://fast.wistia.com/embed/medias/<?php echo esc_html($notification_data['video_id']); ?>.jsonp'
								document.getElementsByTagName("head")[0].appendChild(script)
							</script>

							<div class="wistia_embed wistia_async_<?php echo esc_html($notification_data['video_id']); ?> popover=true popoverContent=link videoFoam=false"
								>
								<span class="dashicons dashicons-video-alt3"></span>
							</div>
						</div>
					<?php endif; ?>
					<!-- Video Link end -->

					<!-- Learn More Link -->
					<?php if (isset($notification_data['learn_more_link'])) : ?>
						<a class="notification-card-button-link"
							href="<?php echo esc_html($notification_data['learn_more_link']); ?>"
							target="_blank"
						>
							<div class="notification-card-bottom-button">
								<?php esc_html_e('Learn more', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							</div>
						</a>
					<?php endif; ?>
					<!-- Learn More Link end -->

					<!-- Settings Link -->
					<?php if (isset($notification_data['settings_link'])) : ?>
						<a class="notification-card-button-link"
							href="<?php echo esc_html($notification_data['settings_link']); ?>"
							target="_blank"
						>
							<div class="notification-card-bottom-button">
								<?php esc_html_e('Settings', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
							</div>
						</a>
					<?php endif; ?>
					<!-- Settings Link end -->

				</div>
				<!-- bottom end -->

			</div>
		</div>
		<?php
	}

	private static function cannot_show_dashboard_opportunities_message() {
		return !self::can_show_dashboard_opportunities_message();
	}

	public static function opportunities_notification() {

		if (self::cannot_show_dashboard_opportunities_message()) {
			return;
		}

		?>
		<div id="active-opportunities-notification"
			class="notice notice-info pmw active-opportunities-notification"
			style="padding: 8px;display: flex;flex-direction: row;justify-content: space-between;">
			<div>
				<div style="color:black;">
						<span>
							<?php esc_html_e('The Pixel Manager has detected new opportunities which can help improve tracking and campaign performance.', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
						</span>
				</div>

				<a href="<?php echo( esc_url_raw('/wp-admin/admin.php?page=wpm&section=opportunities') ); ?>"
					style="text-decoration: none;box-shadow: none;">
					<div id="pmw-purchase-new-license-button" class="button" style="margin: 10px 0 10px 0">
						<?php esc_html_e('Show the opportunities', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
					</div>
				</a>
			</div>

			<div style="text-align: right;display: flex;flex-direction: column;">
				<div id="pmw-dismiss-opportunities-message-button"
					class="button pmw-notification-dismiss-button"
					style="white-space:normal;margin-bottom: 6px;text-align: center;"
					data-notification-id="dashboard-opportunities-message-dismissed"
				><?php esc_html_e('Dismiss', 'woocommerce-google-adwords-conversion-tracking-tag'); ?>
				</div>
			</div>
		</div>

		<?php
	}
}
