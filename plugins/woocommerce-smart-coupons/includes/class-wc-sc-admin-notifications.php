<?php
/**
 * Smart Coupons Admin Notifications
 *
 * @author      StoreApps
 * @since       4.0.0
 * @version     1.20.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
if ( ! class_exists( 'WC_SC_Admin_Notifications' ) ) {

	/**
	 * Class for handling admin pages of Smart Coupons
	 */
	class WC_SC_Admin_Notifications {

		/**
		 * Variable to hold instance of WC_SC_Admin_Notifications
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {

			add_filter( 'plugin_action_links_' . plugin_basename( WC_SC_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

			add_action( 'wp_ajax_wc_sc_review_notice_action', array( $this, 'wc_sc_review_notice_action' ) );
			add_action( 'wp_ajax_wc_sc_40_notice_action', array( $this, 'wc_sc_40_notice_action' ) );
			add_action( 'admin_notices', array( $this, 'show_plugin_notice' ) );

			// To update footer text on SC screens.
			add_filter( 'admin_footer_text', array( $this, 'wc_sc_footer_text' ) );
			add_filter( 'update_footer', array( $this, 'wc_sc_update_footer_text' ), 99 );

			// To show 'Connect your store' notice of WC Helper on SC pages.
			add_filter( 'woocommerce_screen_ids', array( $this, 'add_wc_connect_store_notice_on_sc_pages' ) );

			// Show Database update notices.
			add_action( 'admin_notices', array( $this, 'admin_db_update_notices' ) );

			add_action( 'admin_notices', array( $this, 'show_all_feature_notices' ) );

			add_action( 'admin_init', array( $this, 'dismiss_feature_notice' ) );

			add_action( 'wp_ajax_sc_dismiss_notice', array( $this, 'wc_sc_dismiss_notice' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dismissible_notice_script' ) );
		}

		/**
		 * Get single instance of WC_SC_Admin_Pages
		 *
		 * @return WC_SC_Admin_Pages Singleton object of WC_SC_Admin_Pages
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
		 */
		public function __call( $function_name, $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}
		}

		/**
		 * Function to add more action on plugins page
		 *
		 * @param array $links Existing links.
		 * @return array $links
		 */
		public function plugin_action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc-smart-coupons' ) ) . '">' . esc_html__( 'Settings', 'woocommerce-smart-coupons' ) . '</a>',
				'faqs'     => '<a href="' . esc_url( admin_url( 'admin.php?page=sc-faqs' ) ) . '">' . esc_html__( 'FAQ\'s', 'woocommerce-smart-coupons' ) . '</a>',
				'docs'     => '<a target="_blank" href="' . esc_url( 'https://woocommerce.com/document/smart-coupons/' ) . '">' . __( 'Docs', 'woocommerce-smart-coupons' ) . '</a>',
				'support'  => '<a target="_blank" href="' . esc_url( 'https://woocommerce.com/my-account/create-a-ticket/' ) . '">' . __( 'Support', 'woocommerce-smart-coupons' ) . '</a>',
				'review'   => '<a target="_blank" href="' . esc_url( 'https://woocommerce.com/products/smart-coupons/?review' ) . '">' . __( 'Review', 'woocommerce-smart-coupons' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Handle Smart Coupons review notice action
		 */
		public function wc_sc_review_notice_action() {
			check_ajax_referer( 'wc-sc-review-notice-action', 'security' );

			$post_do = ( ! empty( $_POST['do'] ) ) ? wc_clean( wp_unslash( $_POST['do'] ) ) : ''; // phpcs:ignore

			$option = strtotime( '+1 month' );
			if ( 'remove' === $post_do ) {
				$option = 'no';
			}

			update_option( 'wc_sc_is_show_review_notice', $option, 'no' );

			wp_send_json( array( 'success' => 'yes' ) );
		}

		/**
		 * Handle Smart Coupons version 4.0.0 notice action
		 */
		public function wc_sc_40_notice_action() {
			check_ajax_referer( 'wc-sc-40-notice-action', 'security' );

			update_option( 'wc_sc_is_show_40_notice', 'no', 'no' );

			wp_send_json( array( 'success' => 'yes' ) );
		}

		/**
		 * Show plugin review notice
		 */
		public function show_plugin_notice() {
			global $pagenow, $post;

			$valid_post_types      = array( 'shop_coupon', 'shop_order', 'product' );
			$valid_pagenow         = array( 'edit.php', 'post.php', 'plugins.php' );
			$is_show_review_notice = get_option( 'wc_sc_is_show_review_notice' );
			$is_coupon_enabled     = get_option( 'woocommerce_enable_coupons' );
			$get_post_type         = ( ! empty( $post->ID ) ) ? $this->get_post_type( $post->ID ) : '';
			$get_page              = ( ! empty( $_GET['page'] ) ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : '';  // phpcs:ignore
			$get_tab               = ( ! empty( $_GET['tab'] ) ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : '';    // phpcs:ignore
			$design                = get_option( 'wc_sc_setting_coupon_design', 'basic' );

			$is_page = ( in_array( $pagenow, $valid_pagenow, true ) || in_array( $get_post_type, $valid_post_types, true ) || ( 'admin.php' === $pagenow && ( 'wc-smart-coupons' === $get_page || 'wc-smart-coupons' === $get_tab ) ) );

			if ( $is_page && 'yes' !== $is_coupon_enabled ) {
				?>
				<div id="wc_sc_coupon_disabled" class="updated fade error">
					<p>
						<?php
						echo '<strong>' . esc_html__( 'Important', 'woocommerce-smart-coupons' ) . ':</strong> ' . esc_html__( 'Setting "Enable the use of coupon codes" is disabled.', 'woocommerce-smart-coupons' ) . ' ' . sprintf(
							'<a href="%s">%s</a>',
							esc_url(
								add_query_arg(
									array(
										'page' => 'wc-settings',
										'tab'  => 'general',
									),
									admin_url( 'admin.php' )
								)
							),
							esc_html__( 'Enable', 'woocommerce-smart-coupons' )
						) . ' ' . esc_html__( 'it to use', 'woocommerce-smart-coupons' ) . ' <strong>' . esc_html__( 'WooCommerce Smart Coupons', 'woocommerce-smart-coupons' ) . '</strong> ' . esc_html__( 'features.', 'woocommerce-smart-coupons' );
						?>
					</p>
				</div>
				<?php
			}

			// Review Notice.
			if ( $is_page && ! empty( $is_show_review_notice ) && 'no' !== $is_show_review_notice && time() >= absint( $is_show_review_notice ) ) {
				if ( ! wp_script_is( 'jquery' ) ) {
					wp_enqueue_script( 'jquery' );
				}
				?>
				<style type="text/css" media="screen">
					#wc_sc_review_notice .wc_sc_review_notice_action {
						float: right;
						padding: 0.5em 0;
						text-align: right;
					}
				</style>
				<script type="text/javascript">
					jQuery(function(){
						jQuery('body').on('click', '#wc_sc_review_notice .wc_sc_review_notice_action a.wc_sc_review_notice_remind', function( e ){
							jQuery.ajax({
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								type: 'post',
								dataType: 'json',
								data: {
									action: 'wc_sc_review_notice_action',
									security: '<?php echo esc_html( wp_create_nonce( 'wc-sc-review-notice-action' ) ); ?>',
									do: 'remind'
								},
								success: function( response ){
									if ( response.success != undefined && response.success != '' && response.success == 'yes' ) {
										jQuery('#wc_sc_review_notice').fadeOut(500, function(){ jQuery('#wc_sc_review_notice').remove(); });
									}
								}
							});
							return false;
						});
						jQuery('body').on('click', '#wc_sc_review_notice .wc_sc_review_notice_action a.wc_sc_review_notice_remove', function(){
							jQuery.ajax({
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								type: 'post',
								dataType: 'json',
								data: {
									action: 'wc_sc_review_notice_action',
									security: '<?php echo esc_html( wp_create_nonce( 'wc-sc-review-notice-action' ) ); ?>',
									do: 'remove'
								},
								success: function( response ){
									if ( response.success != undefined && response.success != '' && response.success == 'yes' ) {
										jQuery('#wc_sc_review_notice').fadeOut(500, function(){ jQuery('#wc_sc_review_notice').remove(); });
									}
								}
							});
							return false;
						});
					});
				</script>
				<div id="wc_sc_review_notice" class="updated fade">
					<div class="wc_sc_review_notice_action">
						<a href="javascript:void(0)" class="wc_sc_review_notice_remind"><?php echo esc_html__( 'Remind me after a month', 'woocommerce-smart-coupons' ); ?></a><br>
						<a href="javascript:void(0)" class="wc_sc_review_notice_remove"><?php echo esc_html__( 'Never show again', 'woocommerce-smart-coupons' ); ?></a>
					</div>
					<p>
						<?php echo esc_html__( 'Awesome, you successfully auto-generated a coupon! Are you having a great experience with', 'woocommerce-smart-coupons' ) . ' <strong>' . esc_html__( 'WooCommerce Smart Coupons', 'woocommerce-smart-coupons' ) . '</strong> ' . esc_html__( 'so far?', 'woocommerce-smart-coupons' ) . '<br>' . esc_html__( 'Please consider', 'woocommerce-smart-coupons' ) . ' <a href="' . esc_url( 'https://woocommerce.com/products/smart-coupons/#reviews' ) . '">' . esc_html__( 'leaving a review', 'woocommerce-smart-coupons' ) . '</a> ' . esc_html__( '! If things aren\'t going quite as expected, we\'re happy to help -- please reach out to', 'woocommerce-smart-coupons' ) . ' <a href="' . esc_url( 'https://woocommerce.com/my-account/create-a-ticket/' ) . '">' . esc_html__( 'our support team', 'woocommerce-smart-coupons' ) . '</a>.'; ?>
					</p>
				</div>
				<?php
			}

			if ( $is_page && 'custom-design' === $design ) {
				?>
				<div class="updated fade error" style="background-color: #f0fff0;">
					<p>
						<?php
						printf(
							/* translators: 1: WooCommerce Smart Coupons 2: Link for the Smart Coupons settings */
							esc_html__( '%1$s: You are using a custom coupon style which is planned to be removed from the plugin in upcoming versions. New, improved styles & colors are added in the version 4.9.0. We would request you to choose a color scheme & a style for coupon from the newly added colors & styles. You can do this from %2$s.', 'woocommerce-smart-coupons' ),
							'<strong>' . esc_html__( 'WooCommerce Smart Coupons', 'woocommerce-smart-coupons' ) . '</strong>',
							'<a href="' . esc_url(
								add_query_arg(
									array(
										'page' => 'wc-settings',
										'tab'  => 'wc-smart-coupons',
									),
									admin_url( 'admin.php' )
								)
							) . '" target="_blank">' . esc_html__(
								'Smart Coupons settings',
								'woocommerce-smart-coupons'
							) . '</a>'
						);
						?>
					</p>
				</div>
				<?php
			}

			if ( 'admin.php' === $pagenow && 'wc-smart-coupons' === $get_page ) {
				$messages = array();
				if ( ! function_exists( 'mime_content_type' ) ) {
					/* translators: The PHP extension name */
					$messages[] = sprintf( __( 'PHP extension %s is missing or not accessible. It is required for secure coupon imports.', 'woocommerce-smart-coupons' ), '<code>fileinfo</code>' );
				}

				if ( ! empty( $messages ) ) {
					?>
					<div id="wc_sc_import_error" class="notice notice-warning">
						<?php /* translators: 1. Message type 2. Functionality name "Bulk Generate" 3. Functionality name "Import-Export" */ ?>
						<p><?php printf( esc_html__( '%1$s: To ensure %2$s and %3$s work correctly, contact your system administrator or host provider to resolve below:', 'woocommerce-smart-coupons' ), '<strong>' . esc_html__( 'Important', 'woocommerce-smart-coupons' ) . '</strong>', '<strong>' . esc_html__( 'Bulk Generate', 'woocommerce-smart-coupons' ) . '</strong>', '<strong>' . esc_html__( 'Import-Export', 'woocommerce-smart-coupons' ) . '</strong>' ); ?></p>
						<ul>
							<?php
							foreach ( $messages as $message ) {
								?>
									<li><?php echo wp_kses_post( $message ); // phpcs:ignore ?></li>
								<?php
							}
							?>
						</ul>
					</div>
					<?php
				}
			}
		}

		/**
		 * Function to 'Connect your store' notice on Smart Coupons pages in admin
		 *
		 * @param  string $sc_rating_text Text in footer (left).
		 * @return string $sc_rating_text
		 */
		public function wc_sc_footer_text( $sc_rating_text ) {
			global $post, $pagenow;

			if ( ! empty( $pagenow ) ) {
				$get_post_type = ( ! empty( $post->ID ) ) ? $this->get_post_type( $post->ID ) : '';
				$get_page      = ( ! empty( $_GET['page'] ) ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
				$get_tab       = ( ! empty( $_GET['tab'] ) ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore
				$sc_pages      = array( 'wc-smart-coupons', 'sc-about', 'sc-faqs' );

				if ( in_array( $get_page, $sc_pages, true ) || 'shop_coupon' === $get_post_type || 'wc-smart-coupons' === $get_tab ) {
					?>
					<style type="text/css">
						#wpfooter {
							display: block !important;
						}
					</style>
					<?php
					/* translators: %s: link to review WooCommerce Smart Coupons */
					$sc_rating_text = wp_kses_post( sprintf( __( 'Liked WooCommerce Smart Coupons? Leave us a %s. A huge thank you from WooCommerce & StoreApps in advance!', 'woocommerce-smart-coupons' ), '<a target="_blank" href="' . esc_url( 'https://woocommerce.com/products/smart-coupons/?review' ) . '" style="color: #5850EC;">5-star rating here</a>' ) );
				}
			}

			return $sc_rating_text;
		}

		/**
		 * Function to 'Connect your store' notice on Smart Coupons pages in admin
		 *
		 * @param  string $sc_text Text in footer (right).
		 * @return string $sc_text
		 */
		public function wc_sc_update_footer_text( $sc_text ) {
			global $post, $pagenow;

			if ( ! empty( $pagenow ) ) {
				$get_post_type = ( ! empty( $post->ID ) ) ? $this->get_post_type( $post->ID ) : '';
				$get_page      = ( ! empty( $_GET['page'] ) ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
				$get_tab       = ( ! empty( $_GET['tab'] ) ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore
				$sc_pages      = array( 'wc-smart-coupons', 'sc-about', 'sc-faqs' );

				if ( in_array( $get_page, $sc_pages, true ) || 'shop_coupon' === $get_post_type || 'wc-smart-coupons' === $get_tab ) {
					/* translators: %s: link to submit idea for Smart Coupons on WooCommerce idea board */
					$sc_text = sprintf( __( 'Have a feature request? Submit it %s.', 'woocommerce-smart-coupons' ), '<a href="' . esc_url( 'https://woocommerce.com/feature-requests/smart-coupons/' ) . '" target="_blank" style="color: #5850EC;">' . __( 'here', 'woocommerce-smart-coupons' ) . '</a>' );
				}
			}

			return $sc_text;
		}

		/**
		 * Function to 'Connect your store' notice on Smart Coupons pages in admin
		 *
		 * @param  array $screen_ids List of existing screen ids.
		 * @return array $screen_ids
		 */
		public function add_wc_connect_store_notice_on_sc_pages( $screen_ids ) {

			array_push( $screen_ids, 'woocommerce_page_wc-smart-coupons' );

			return $screen_ids;
		}

		/**
		 * Function to render admin notice
		 *
		 * @param string $type         Notice type.
		 * @param string $title        Notice title.
		 * @param string $message      Notice message.
		 * @param string $action       Notice actions.
		 * @param bool   $dismissible  Notice dismissible.
		 * @param string $notice_key   Notice Key.
		 * @return void.
		 */
		public static function show_notice( $type = 'info', $title = '', $message = '', $action = '', $dismissible = false, $notice_key = '' ) {
			$css_classes = array(
				'notice',
				'notice-' . $type,
				'wc-sc-' . $type,
			);
			if ( true === $dismissible ) {
				$css_classes[] = 'is-dismissible';
			}
			if ( is_callable( 'WC_Smart_Coupons::get_smart_coupons_plugin_data' ) ) {
				$plugin_data = WC_Smart_Coupons::get_smart_coupons_plugin_data();
				$version     = $plugin_data['Version'];
			} else {
				$version = '';
			}
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			if ( ! wp_style_is( 'smart-coupons-admin', 'registered' ) ) {
				wp_register_style( 'smart-coupons-admin', untrailingslashit( plugins_url( '/', WC_SC_PLUGIN_FILE ) ) . '/assets/css/smart-coupons-admin' . $suffix . '.css', array(), $version );
			}
			if ( ! wp_style_is( 'smart-coupons-admin' ) ) {
				wp_enqueue_style( 'smart-coupons-admin' );
			}

			// Allow specific HTML tags.
			$allowed_html = array(
				'br'     => array(),
				'a'      => array(
					'href'   => array(),
					'class'  => array(),
					'target' => array(),
				),
				'strong' => array(),
			);

			?>
			<div
				class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>"
				id="<?php echo ! empty( $notice_key ) ? esc_attr( $notice_key ) : ''; ?>"
				<?php echo ! empty( $notice_key ) ? 'data-notice-key="' . esc_attr( $notice_key ) . '"' : ''; ?>
			>
				<?php
				if ( ! empty( $title ) ) {
					printf( '<p>%s</p>', wp_kses_post( $title ) );
				}
				if ( ! empty( $message ) ) {
					printf( '<p>%s</p>', wp_kses( $message, $allowed_html ) );
				}
				if ( ! empty( $action ) ) {
					printf( '<p class="submit">%s</p>', wp_kses_post( $action ) );
				}
				?>
			</div>
			<?php
		}

		/**
		 * Function to show database update notice
		 */
		public function admin_db_update_notices() {
			if ( ! class_exists( 'WC_SC_Background_Upgrade' ) ) {
				include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-background-upgrade.php';
			}

			$wcsc_db       = WC_SC_Background_Upgrade::get_instance();
			$update_status = $wcsc_db->get_status( $wcsc_db->get_current_update_version() );

			if ( 'pending' === $update_status ) {
				// Notice for pending update.
				$this->db_update_pending_notice();
			} elseif ( 'processing' === $update_status ) {
				// Notice for processing update.
				$this->db_update_processing_notice();
			} elseif ( 'completed' === $update_status ) {
				// Notice for completed update.
				$this->db_update_completed_notice();
			} elseif ( 'failed' === $update_status ) {
				// Notice for failed update.
				$this->db_update_failed_notice();
			}
		}

		/**
		 * Function to show pending database update notice
		 */
		public function db_update_pending_notice() {

			if ( ! class_exists( 'WC_SC_Background_Upgrade' ) ) {
				include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-background-upgrade.php';
			}

			$wcsc_db = WC_SC_Background_Upgrade::get_instance();

			$blog_id = is_multisite() ? get_current_blog_id() : null;
			/* translators: %s: Plugin name */
			$title         = sprintf( __( '%s &minus; Database update required', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			$message       = __( 'The database update runs in the background and may take some time. It\'s only for WooCommerce Smart Coupons plugin data and won\'t affect your existing data.', 'woocommerce-smart-coupons' );
			$update_url    = wp_nonce_url(
				add_query_arg(
					array(
						'page'         => 'wc-settings',
						'tab'          => 'wc-smart-coupons',
						'wc_sc_update' => $wcsc_db->get_current_update_version(),
					),
					get_admin_url( $blog_id, 'admin.php' )
				),
				'wc_sc_db_process',
				'wc_sc_db_update_nonce'
			);
			$action_button = sprintf( '<a href="%1$s" class="button button-primary">%2$s</a>', esc_url( $update_url ), __( 'Update WooCommerce Smart Coupons database', 'woocommerce-smart-coupons' ) );

			$this->show_notice( 'warning', $title, $message, $action_button );
		}

		/**
		 * Function to show database update processing notice.
		 */
		public function db_update_processing_notice() {
			if ( 'woocommerce_page_wc-status' === $this->get_current_screen_id() && isset( $_GET['tab'] ) && 'action-scheduler' === wc_clean( wp_unslash( $_GET['tab'] ) ) ) { // phpcs:ignore
				return;
			}

			$actions_url   = add_query_arg(
				array(
					'page'   => 'wc-status',
					'tab'    => 'action-scheduler',
					's'      => 'wcsc_',
					'status' => 'pending',
				),
				admin_url( 'admin.php' )
			);
			$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
			/* translators: %s: Plugin name */
			$message = sprintf( __( '%s is updating the database in the background. The database update process may take a little while, so please be patient.', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			if ( true === $cron_disabled ) {
				$message .= '<br>' . __( 'Note: WP CRON has been disabled on your install which may prevent this update from completing.', 'woocommerce-smart-coupons' );
			}
			$action_button = sprintf( '<a href="%1$s" class="button button-secondary">%2$s</a>', esc_url( $actions_url ), __( 'View status', 'woocommerce-smart-coupons' ) );
			$this->show_notice( 'info', '', $message, $action_button );
		}

		/**
		 * Function to show database update completed notice.
		 */
		public function db_update_completed_notice() {
			/* translators: %s: Plugin name */
			$message = sprintf( __( '%s database update completed. Thank you for updating to the latest version!', 'woocommerce-smart-coupons' ), 'WooCommerce Smart Coupons' );
			$this->show_notice( 'success', '', $message, '', true );
			if ( ! class_exists( 'WC_SC_Background_Upgrade' ) ) {
				include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-background-upgrade.php';
			}
			if ( class_exists( 'WC_SC_Background_Upgrade' ) ) {
				$wcsc_db        = WC_SC_Background_Upgrade::get_instance();
				$update_version = $wcsc_db->get_current_update_version();
				$update_status  = $wcsc_db->get_status( $update_version );
				if ( 'completed' === $update_status ) {
					$wcsc_db->set_status( $update_version, 'done' );
				}
			}
		}

		/**
		 * Function to show database update failed notice.
		 */
		public function db_update_failed_notice() {
			/* Message text */
			$message = sprintf(
				/* translators: %s: Plugin name */
				_x(
					'%1$s database update failed. This may be due to server issues or configuration problems. Please click the "Restart Update" button to try again. If the issue persists, %2$s for assistance.',
					'Message displayed when the database update process fails',
					'woocommerce-smart-coupons'
				),
				'WooCommerce Smart Coupons',
				'<a href="https://woocommerce.com/vendor/storeapps/" target="_blank">' . _x(
					'contact our support team',
					'Link text for contacting support',
					'woocommerce-smart-coupons'
				) . '</a>'
			);

			if ( ! class_exists( 'WC_SC_Background_Upgrade' ) ) {
				include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-background-upgrade.php';
			}

			$wcsc_db = WC_SC_Background_Upgrade::get_instance();
			// URL for restarting the update.
			$restart_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'         => 'wc-settings',
						'tab'          => 'wc-smart-coupons',
						'wc_sc_update' => $wcsc_db->get_current_update_version(),
					),
					get_admin_url( $blog_id, 'admin.php' )
				),
				'wc_sc_db_process',
				'wc_sc_db_update_nonce'
			);

			// Create the Restart button.
			$action_button = sprintf(
				'<a href="%1$s" class="button button-secondary">%2$s</a>',
				esc_url( $restart_url ),
				_x( 'Restart Update', 'Button text for retrying the update process', 'woocommerce-smart-coupons' )
			);

			// Display the notice.
			$this->show_notice( 'error', '', $message, $action_button, true );
		}

		/**
		 * Function to get current screen id.
		 *
		 * @return string.
		 */
		public function get_current_screen_id() {
			$screen = get_current_screen();
			return $screen ? $screen->id : '';
		}

		/**
		 * Display an admin notice for a specific WooCommerce feature.
		 *
		 * This function shows a customizable admin notice with an action button and a skip link
		 * for dismissing the notice. It only appears on WooCommerce admin pages.
		 *
		 * @param string $notice_key Unique identifier for the notice.
		 * @param string $title      Title of the notice.
		 * @param string $message    Message content of the notice.
		 * @param string $action_url URL for the action button.
		 * @param string $action_text Text for the action button.
		 * @param bool   $skip_link   Skip button should visible or not.
		 */
		public function show_feature_notice( $notice_key, $title, $message, $action_url, $action_text, $skip_link = false ) {

			// Check if the current screen is a WooCommerce admin page.
			$screen = get_current_screen();
			if ( ! $screen || false === strpos( $screen->parent_base, 'woocommerce' ) ) {
				return; // Exit early if not on a WooCommerce admin page.
			}

			// Retrieve the dismissed notices array.
			$dismissed_notices = get_option( 'sc_dismissed_notices', array() );

			// Check if the notice has already been dismissed.
			if ( is_array( $dismissed_notices ) && in_array( $notice_key, $dismissed_notices, true ) ) {
				return; // Exit if notice has been dismissed.
			}

			$action_button = '';
			if ( ! empty( $action_text ) ) {
				// Create the action button HTML, linking to the specified URL.
				$action_button = sprintf(
					'<a href="%1$s" class="button button-primary">%2$s</a>',
					esc_url( $action_url ), // Link to the action or settings.
					esc_html( $action_text )
				);
			}

			if ( $skip_link ) {
				// Create the skip link HTML to dismiss the notice.
				$skip_link = sprintf(
					'<a href="%s" class="button button-secondary" style="margin-left: 10px;">%s</a>',
					esc_url( add_query_arg( 'sc_dismiss_notice', $notice_key ) ),
					esc_html( _x( 'Skip', 'Button text for dismissing WooCommerce feature notice', 'woocommerce-smart-coupons' ) )
				);
			}

			// Display the info notice with message, action button, and skip link.
			$this->show_notice( 'info', $title, $message, $action_button . $skip_link, true, $notice_key );
		}

		/**
		 * Handle the dismissal of the WooCommerce feature admin notice.
		 *
		 * This function checks for a dismissal request in the URL and updates the
		 * user's meta data to mark the notice as dismissed, preventing it from
		 * displaying again.
		 */
		public function dismiss_feature_notice() {
			// Check if a dismiss request is present in the URL.
			if ( isset( $_GET['sc_dismiss_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				// Sanitize and clean the notice key from the URL parameter.
				$notice_key = wc_clean( wp_unslash( sanitize_text_field( $_GET['sc_dismiss_notice'] ) ) ); // phpcs:ignore

				// Retrieve the dismissed notices array.
				$dismissed_notices = get_option( 'sc_dismissed_notices', true );

				// Ensure dismissed notices is an array.
				if ( ! is_array( $dismissed_notices ) ) {
					$dismissed_notices = array();
				}

				// Add the notice key if it hasn't already been dismissed.
				if ( ! in_array( $notice_key, $dismissed_notices, true ) ) {
					$dismissed_notices[] = $notice_key;
					update_option( 'sc_dismissed_notices', $dismissed_notices );
				}

				// Redirect to remove the dismiss parameter from the URL.
				wp_safe_redirect( remove_query_arg( 'sc_dismiss_notice' ) );
				exit; // Always call exit after wp_safe_redirect to prevent further execution.
			}
		}

		/**
		 * Display all feature-related admin notices.
		 *
		 * This function checks the settings and conditions for each feature notice
		 * and displays them accordingly on the WooCommerce admin pages.
		 *
		 * @return void
		 */
		public function show_all_feature_notices() {
			global $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			// Ensure WEEK_IN_SECONDS is defined to avoid fatal error.
			if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
				define( 'WEEK_IN_SECONDS', 7 * 24 * 60 * 60 );
			}

		}

		/**
		 * Enqueue Inline script for the Dismissible notice.
		 *
		 * @return void
		 */
		public function enqueue_dismissible_notice_script() {
			// TODO: Check if this can be disabled when on product edit page.
			// Use an empty script handle just to attach inline JS.
			wp_register_script( 'smart-coupons-dismissible-notice', '', array(), $this->get_smart_coupons_version(), true );
			wp_enqueue_script( 'smart-coupons-dismissible-notice' );

			$inline_js = <<<JS
				document.addEventListener('DOMContentLoaded', function () {
					const observer = new MutationObserver(() => {
						const notices = document.querySelectorAll('.notice.is-dismissible[data-notice-key]');
						notices.forEach(function (notice) {
							const noticeKey = notice.getAttribute('data-notice-key');

							// Skip if already handled.
							if (notice.dataset.dismissBound === 'true') return;

							const dismissBtn = notice.querySelector('.notice-dismiss');

							if (!dismissBtn || !noticeKey) return;

							dismissBtn.addEventListener('click', function () {
								fetch(ajaxurl + '?action=sc_dismiss_notice&notice_key=' + encodeURIComponent(noticeKey), {
									method: 'GET',
									credentials: 'same-origin'
								});
							});

							// Mark as bound.
							notice.dataset.dismissBound = 'true';
						});
					});

					observer.observe(document.body, { childList: true, subtree: true });
				});
JS;

			wp_add_inline_script( 'smart-coupons-dismissible-notice', $inline_js );
		}

		/**
		 * Handle Dismissable notice
		 *
		 * @return void
		 */
		public function wc_sc_dismiss_notice() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error();
			}

			$notice_key = sanitize_text_field( wp_unslash( $_GET['notice_key'] ) ?? '' ); //phpcs:disable
			if ( empty( $notice_key ) ) {
				wp_send_json_error();
			}
			if ( 'wc_sc_send_unused_coupon_reminder' === $notice_key ) {
				delete_transient( 'wc_sc_send_unused_coupon_reminder_process_status' );
			}else{
				$dismissed = get_option( 'sc_dismissed_notices', array() );
				if ( ! in_array( $notice_key, $dismissed, true ) ) {
					$dismissed[] = $notice_key;
					update_option( 'sc_dismissed_notices', $dismissed );
				}
			}

			wp_send_json_success();
		}
	}

}

WC_SC_Admin_Notifications::get_instance();
